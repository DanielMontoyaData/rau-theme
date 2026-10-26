<?php
/**
 * Contenido real del Service Worker — servido vía la ruta /service-worker.js
 * definida en functions.php. No se accede a este archivo directamente.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$herramientas_url = home_url( '/herramientas/' );
$cache_version    = 'rau-cache-' . RAU_THEME_VERSION;
$em_url           = home_url( '/emergencias/' );
$em_base          = get_template_directory_uri() . '/assets/';
$em_assets        = array( $em_base . 'emergencias/em.css?ver=' . RAU_THEME_VERSION, $em_base . 'emergencias/colombia.geojson?ver=' . RAU_THEME_VERSION,
	$em_base . 'vendor/leaflet/leaflet.js?ver=1.9.4', $em_base . 'vendor/leaflet/leaflet.css?ver=1.9.4', $em_base . 'vendor/leaflet/images/layers.png', $em_base . 'vendor/leaflet/images/layers-2x.png' );
foreach ( array( 'em-core', 'em-db', 'em', 'em-vistas', 'em-mapa', 'em-sim' ) as $js ) $em_assets[] = $em_base . 'emergencias/' . $js . '.js?ver=' . RAU_THEME_VERSION;
?>
const CACHE_NAME = '<?php echo esc_js( $cache_version ); ?>';
const APP_SHELL_URL = '<?php echo esc_js( $herramientas_url ); ?>';
// Centro de Mando de Emergencias: tiene que abrir sin internet, que es justo cuando más se necesita
const EM_URL = '<?php echo esc_js( $em_url ); ?>';
const EM_ASSETS = <?php echo wp_json_encode( $em_assets ); ?>;
/* Mosaicos de OpenStreetMap: solo los que la persona ya vio (nada se descarga por adelantado, como pide
   la política de uso de OSM), hasta 1500, para que el mapa siga sirviendo sin internet en esa zona. */
const CACHE_MOSAICOS = 'rau-mosaicos';
const MAX_MOSAICOS = 1500;

/**
 * Instalación: guarda una copia del "shell" de Herramientas — la página
 * completa con las <?php echo count( rau_get_herramientas_tabs() ); ?> herramientas ya en el DOM. Las que son puramente
 * locales (Maidenhead, Código Q, CTCSS, RST, Fonético, Glosario, Código
 * de Colores) siguen funcionando sin internet porque no dependen de
 * ningún dato en vivo, solo de JS que ya está cargado.
 */
self.addEventListener('install', function(event){
	event.waitUntil(
		caches.open(CACHE_NAME).then(function(cache){
			// Cada uno por separado: si uno falla, los demás igual quedan guardados
			return Promise.all([APP_SHELL_URL, EM_URL].concat(EM_ASSETS).map(function(u){ return cache.add(u).catch(function(){}); }));
		})
	);
	self.skipWaiting();
});

self.addEventListener('activate', function(event){
	event.waitUntil(
		caches.keys().then(function(nombres){
			// Los mosaicos del mapa no dependen de la versión del tema: se conservan
			return Promise.all(nombres.filter(function(n){ return n!==CACHE_NAME && n!==CACHE_MOSAICOS; }).map(function(n){ return caches.delete(n); }));
		})
	);
	self.clients.claim();
});

/**
 * Estrategia: red primero, caché de respaldo. Así, con internet siempre
 * se ve la versión más reciente (con datos en vivo); sin internet, cae
 * al shell guardado — las calculadoras locales igual funcionan.
 */
self.addEventListener('fetch', function(event){
	if(event.request.method !== 'GET') return;
	if(event.request.url.indexOf('https://tile.openstreetmap.org/') === 0){
		event.respondWith(fetch(event.request).then(function(r){
			if(r.ok){ var copia=r.clone(); caches.open(CACHE_MOSAICOS).then(function(c){ c.put(event.request, copia).then(function(){ return c.keys(); }).then(function(k){ if(k.length>MAX_MOSAICOS) return Promise.all(k.slice(0, k.length-MAX_MOSAICOS).map(function(x){ return c.delete(x); })); }); }); }
			return r;
		}).catch(function(){ return caches.open(CACHE_MOSAICOS).then(function(c){ return c.match(event.request); }).then(function(m){ return m || new Response('', {status:504}); }); }));
		return;
	}
	// Archivos del centro de mando (llevan la versión en la URL): primero la copia guardada
	if(EM_ASSETS.indexOf(event.request.url) !== -1){
		event.respondWith(caches.match(event.request).then(function(c){ return c || fetch(event.request).then(function(r){ var copia=r.clone(); caches.open(CACHE_NAME).then(function(cache){ cache.put(event.request, copia); }); return r; }); }));
		return;
	}
	var esEm = event.request.url.split('?')[0] === EM_URL;
	if(!esEm && event.request.url.indexOf(APP_SHELL_URL) === -1) return; // solo Herramientas y el centro de mando

	event.respondWith(
		fetch(event.request).then(function(respuesta){
			var copia = respuesta.clone();
			caches.open(CACHE_NAME).then(function(cache){ cache.put(event.request, copia); });
			return respuesta;
		}).catch(function(){
			return caches.match(event.request).then(function(cacheada){
				if(!cacheada && esEm) return caches.match(EM_URL);
				return cacheada || new Response('Sin conexión y sin copia guardada todavía. Visita Herramientas una vez con internet primero.', { status: 503 });
			});
		})
	);
});

/**
 * Notificaciones push reales — llegan aunque el sitio no esté abierto.
 */
self.addEventListener('push', function(event){
	var datos = {};
	try { datos = event.data ? event.data.json() : {}; } catch(e){ datos = { title:'RAU', body:event.data ? event.data.text() : '' }; }

	var titulo = datos.title || 'RAU · Radioaficionados Unidos';
	var opciones = {
		body: datos.body || '',
		icon: '<?php echo esc_js( get_template_directory_uri() . "/assets/images/icon-192.png" ); ?>',
		badge: '<?php echo esc_js( get_template_directory_uri() . "/assets/images/favicon-32x32.png" ); ?>',
		data: { url: datos.url || APP_SHELL_URL }
	};
	event.waitUntil(self.registration.showNotification(titulo, opciones));
});

self.addEventListener('notificationclick', function(event){
	event.notification.close();
	var url = (event.notification.data && event.notification.data.url) || APP_SHELL_URL;
	event.waitUntil(
		clients.matchAll({ type:'window' }).then(function(lista){
			for(var i=0;i<lista.length;i++){
				if(lista[i].url===url && 'focus' in lista[i]) return lista[i].focus();
			}
			if(clients.openWindow) return clients.openWindow(url);
		})
	);
});
