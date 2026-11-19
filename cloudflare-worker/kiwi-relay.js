/**
 * Relevo HTTPS/WSS para el KiwiSDR de RAU (Cloudflare Worker).
 *
 * El KiwiSDR solo habla HTTP y el proxy de kiwisdr.com no acepta TLS, así que una página HTTPS
 * (hk4rau.com) no puede abrirle un WebSocket: el navegador lo bloquea por contenido mixto. Este
 * Worker recibe https:// y wss:// en *.workers.dev y los pasa tal cual al receptor por HTTP.
 *
 * Requiere fecha de compatibilidad 2024-09-02 o posterior (la que trae un Worker nuevo): antes
 * de eso los Workers ignoraban el puerto 8073 y se conectaban al 80.
 */
const ORIGEN = 'http://hk4rau.proxy.kiwisdr.com:8073';

// Otros receptores, solo con permiso de su dueño: se usan como /r/<id>/… (por ejemplo
// /r/ejemplo/ws/kiwi/…/SND). El sitio los lista en Ajustes → Generales → Receptores SDR con el mismo id.
// Vacío = solo el HK4RAU.
const RECEPTORES = {
	// ejemplo: 'http://otro-receptor.proxy.kiwisdr.com:8073',
};

// Quién puede abrir el receptor por WebSocket a través de este relevo: el sitio de RAU y la propia
// interfaz del KiwiSDR servida por el Worker. Un cliente que no sea navegador puede falsear Origin;
// esto solo evita que otras páginas gasten la cuota gratuita del Worker.
const ORIGENES_PERMITIDOS = ['https://hk4rau.com', 'https://www.hk4rau.com'];
const SITIO = 'https://hk4rau.com';

export default {
	// Cada 5 minutos (ver [triggers] en wrangler.toml) despierta las tareas programadas de WordPress:
	// avisos por tiempo, propagación, PSK Reporter y limpiezas. Sin esto solo corrían cuando alguien
	// visitaba el sitio, y de madrugada podían pasar horas.
	async scheduled(evento, env, ctx) {
		ctx.waitUntil(fetch(SITIO + '/wp-cron.php?doing_wp_cron=' + Date.now() / 1000, { headers: { 'User-Agent': 'RAU-cron/1.0 (Cloudflare Worker)' } }));
	},
	async fetch(request) {
		const url = new URL(request.url);
		const esWebSocket = (request.headers.get('Upgrade') || '').toLowerCase() === 'websocket';
		if (esWebSocket) {
			const origen = request.headers.get('Origin') || '';
			if (origen && origen !== url.origin && !ORIGENES_PERMITIDOS.includes(origen)) {
				return new Response('Origen no permitido', { status: 403 });
			}
		}

		const headers = new Headers(request.headers);
		const ip = request.headers.get('CF-Connecting-IP');
		if (ip) { headers.set('X-Real-IP', ip); headers.set('X-Forwarded-For', ip); }
		headers.delete('Origin');

		// /r/<id>/resto → el receptor <id>; cualquier otra ruta, el HK4RAU
		const m = url.pathname.match(/^\/r\/([a-z0-9-]{1,32})(\/.*)$/);
		if (m) {
			const otro = RECEPTORES[m[1]];
			if (!otro) return new Response('Receptor no configurado', { status: 404 });
			return pasar(new URL(m[2] + url.search, otro), request, headers, 0);
		}
		return pasar(new URL(url.pathname + url.search, ORIGEN), request, headers, 0);
	},
};

// El proxy de kiwisdr.com puede redirigir a otro servidor (proxy2…). Se sigue aquí, en HTTP,
// porque si la redirección llegara al navegador lo mandaría a una dirección http:// bloqueada.
async function pasar(destino, request, headers, saltos) {
	const respuesta = await fetch(new Request(destino, {
		method: request.method,
		headers,
		body: ['GET', 'HEAD'].includes(request.method) ? undefined : request.body,
		redirect: 'manual',
	}));
	const ubicacion = respuesta.headers.get('Location');
	if ([301, 302, 303, 307, 308].includes(respuesta.status) && ubicacion && saltos < 3) {
		const siguiente = new URL(ubicacion, destino);
		siguiente.protocol = 'http:';
		return pasar(siguiente, request, headers, saltos + 1);
	}
	return respuesta;
}
