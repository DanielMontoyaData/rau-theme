// Sitio: datos estructurados, imagen para compartir, encabezados de seguridad y buscador de Herramientas.
//   RAU_EM_BASE=http://localhost:8099 node --test --test-concurrency=1 tests/sitio/sitio.e2e.mjs
import { test, after } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const BASE = process.env.RAU_EM_BASE || 'http://localhost:8099';
let nav; const errores = [];
after(async () => { if (nav) await nav.close(); });
const html = async (ruta) => (await fetch(BASE + ruta)).text();
const ld = (h) => [...h.matchAll(/<script type="application\/ld\+json">(.*?)<\/script>/gs)].map(m => JSON.parse(m[1]));
const meta = (h, p) => (h.match(new RegExp('<meta (?:property|name)="' + p + '" content="([^"]*)"')) || [])[1];

test('datos estructurados válidos: organización, sitio con buscador y migas', async () => {
	const h = await html('/'), j = ld(h);
	const org = j.find(x => x['@type'] === 'Organization'), web = j.find(x => x['@type'] === 'WebSite');
	assert.ok(org && org['@id'].endsWith('/#organizacion') && org.alternateName.includes('HK4RAU'));
	assert.ok(web && web.potentialAction['@type'] === 'SearchAction' && /\{search_term_string\}/.test(web.potentialAction.target.urlTemplate));
	const r = ld(await html('/receptor/'));
	assert.ok(r.find(x => x['@type'] === 'BreadcrumbList'));
});
test('cada página comparte su propia imagen de 1200×630 con el título', async () => {
	for (const ruta of ['/receptor/', '/emergencias/', '/modem/']) {
		const h = await html(ruta), img = meta(h, 'og:image');
		assert.match(img, /\/rau-og\/[0-9a-f]{16}\.png$/, ruta);
		const r = await fetch(img); assert.equal(r.status, 200);
		const b = Buffer.from(await r.arrayBuffer());
		assert.equal(b.readUInt32BE(16), 1200); assert.equal(b.readUInt32BE(20), 630);
	}
	const h = await html('/receptor/');
	assert.ok(meta(h, 'og:image:alt'));
	assert.equal(meta(h, 'og:image:width'), '1200');
});
test('encabezados de seguridad: permisos solo para el sitio y CSP', async () => {
	const r = await fetch(BASE + '/');
	assert.match(r.headers.get('permissions-policy'), /geolocation=\(self\).*microphone=\(self\).*payment=\(\)/);
	assert.match(r.headers.get('content-security-policy'), /frame-ancestors 'self'.*object-src 'none'/);
	assert.match(r.headers.get('content-security-policy-report-only'), /default-src 'self'.*report-uri .*\/rau\/v1\/csp/);
	const c = await fetch(BASE + '/wp-json/rau/v1/csp', { method: 'POST', headers: { 'Content-Type': 'application/csp-report' }, body: JSON.stringify({ 'csp-report': { 'effective-directive': 'img-src', 'blocked-uri': 'https://prueba-csp.example/x.png', 'document-uri': BASE + '/' } }) });
	assert.equal(c.status, 204);
});
test('buscador de Herramientas: atajo «/», sin tildes, palabras clave y flechas', async () => {
	nav = await chromium.launch({ args: ['--no-proxy-server'] });
	const p = await (await nav.newContext({ viewport: { width: 1280, height: 900 } })).newPage();
	p.on('pageerror', e => errores.push(e.message));
	await p.route(/^https?:\/\/(?!localhost)/, r => r.abort());
	await p.goto(BASE + '/herramientas/'); await p.waitForSelector('#rau-tool-search', { state: 'attached' });
	await p.locator('body').click({ position: { x: 5, y: 5 } });
	await p.keyboard.press('/');
	assert.ok(await p.evaluate(() => document.activeElement && document.activeElement.id === 'rau-tool-search'), '«/» lleva al buscador');
	await p.keyboard.type('swr');
	await p.waitForSelector('#rau-search-results .rau-search-item');
	assert.equal(await p.getAttribute('#rau-search-results .rau-search-item', 'data-tab'), 'roe', 'SWR encuentra el diagnóstico de ROE');
	await p.fill('#rau-tool-search', 'angulo radiacion');
	await p.waitForFunction(() => /Ángulo de Radiación/.test(document.querySelector('#rau-search-results').textContent));
	await p.fill('#rau-tool-search', 'azimut');
	await p.waitForFunction(() => /en «Dónde y a qué distancia»/.test(document.querySelector('#rau-search-results').textContent));
	await p.fill('#rau-tool-search', 'qth');
	await p.keyboard.press('ArrowDown'); await p.keyboard.press('Enter');
	await p.waitForURL(/\/herramientas\/maidenhead\/$/);
});
test('Herramientas: buscador arriba, qué hace cada una y vista de herramienta limpia', async () => {
	nav = nav || await chromium.launch({ args: ['--no-proxy-server'] });
	const p = await (await nav.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true })).newPage(); p.on('pageerror', e => errores.push(e.message));
	await p.route(/^https?:\/\/(?!localhost)/, r => r.abort());
	await p.goto(BASE + '/herramientas/'); await p.waitForSelector('#rau-tool-search');
	// El buscador está en la primera pantalla del celular, y el número del texto es el del catálogo
	assert.ok(await p.evaluate(() => document.getElementById('rau-tool-search').getBoundingClientRect().top < innerHeight));
	const n = (await p.textContent('#rau-intro')).match(/(\d+) herramientas/)[1];
	assert.match(await p.textContent('.consola-modo-btn[data-modo="catalogo"]'), new RegExp('\\(' + n + '\\)'));
	assert.equal(await p.getAttribute('#rau-tool-search', 'placeholder'), 'Buscar una herramienta…', 'sin atajo de teclado en pantalla táctil');
	// Cada resultado dice qué hace; una palabra de la descripción también encuentra la herramienta
	await p.fill('#rau-tool-search', 'impedancia');
	assert.match(await p.textContent('.rau-search-item'), /Carta de Smith.*impedancia/s);
	await p.keyboard.press('Enter');
	await p.waitForFunction(() => location.pathname.endsWith('/smith/'));
	assert.equal(await p.textContent('#rau-h1'), 'Carta de Smith');
	assert.match(await p.textContent('#rau-tool-desc'), /impedancia/);
	assert.equal(await p.isVisible('#rau-consola'), false, 'sin el recuadro vacío del centro de mando');
	assert.equal(await p.isVisible('#rau-intro'), false);
	await p.click('#rau-volver-consola');
	assert.equal(await p.textContent('#rau-h1'), 'Herramientas de Radio');
	assert.equal(await p.isVisible('#rau-consola'), true);
	// «Ver las N herramientas» abre el catálogo; las frecuencias de emergencia ya no salen dos veces
	await p.click('#rau-ir-catalogo'); await p.waitForSelector('#rau-cat-grid', { state: 'visible' });
	assert.equal(await p.locator('.rau-tool-pill', { hasText: /^\s*Emergencia\s*$/ }).count(), 0);
	assert.ok(await p.getAttribute('.rau-tool-pill[data-tab="roe"]', 'title'));
	// El botón de compartir grabación no aparece antes de grabar
	assert.equal(await p.isVisible('#rx-grab-subir'), false);
	await p.close();
});
test('Herramientas: POTA carga al entrar por su dirección, y los datos compartidos se piden una sola vez', async () => {
	nav = nav || await chromium.launch({ args: ['--no-proxy-server'] });
	const p = await nav.newPage(); p.on('pageerror', e => errores.push(e.message));
	await p.route(/^https?:\/\/(?!localhost)/, r => r.abort());
	let solar = 0;
	await p.route(/action=rau_pota_spots/, r => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: [{ activator: 'HK4POT', frequency: '14285', mode: 'SSB', reference: 'CO-0001', name: 'Parque' }] }) }));
	await p.route(/action=rau_solar(&|$)/, r => { solar++; r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: { sfi: '118', ssn: '79', aindex: '10', kindex: '2', xray: 'B5.6', bands: [] } }) }); });
	await p.goto(BASE + '/herramientas/pota/');
	await p.waitForFunction(() => /HK4POT/.test(document.getElementById('panel-pota').innerText), null, { timeout: 10000 });
	await p.waitForTimeout(1500);
	assert.equal(solar, 1, 'los índices solares se piden una vez aunque varios bloques los usen');
	await p.close();
});
test('19.60: cada herramienta carga solo lo suyo y se navega por dirección', async () => {
	nav = nav || await chromium.launch({ args: ['--no-proxy-server'] });
	const p = await nav.newPage({ viewport: { width: 1280, height: 900 } }); p.on('pageerror', e => errores.push(e.message));
	await p.route(/^https?:\/\/(?!localhost)/, r => r.abort());
	await p.goto(BASE + '/herramientas/smith/'); await p.waitForSelector('#panel-smith');
	const r = await p.evaluate(() => ({ consola: !!document.getElementById('rau-consola'), paneles: document.querySelectorAll('.rau-panel').length, nodos: document.getElementsByTagName('*').length,
		h2oculto: document.querySelector('#panel-smith h2').classList.contains('sr-only') }));
	assert.equal(r.consola, false, 'sin consola en la página de una herramienta');
	assert.equal(r.paneles, 1, 'solo su panel');
	assert.ok(r.nodos < 1500, 'página liviana: ' + r.nodos + ' elementos');
	assert.ok(r.h2oculto, 'un solo título visible');
	await p.click('#rau-cat-jumps button:has-text("Conversor RF")'); await p.waitForURL(/\/herramientas\/rfconv\/$/);
	await p.click('#rau-volver-consola'); await p.waitForURL(/\/herramientas\/$/);
	assert.ok(await p.$('#rau-consola'));
	assert.equal(await p.$$eval('.rau-panel', l => l.filter(x => x.id !== 'panel-receptor').length), 0, 'la principal no trae los paneles');
	await p.close();
});
test('19.60: DX propio con escuchar y anotar; tablas como tarjetas y botones de 44 px en el celular', async () => {
	const c = await nav.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true }); const p = await c.newPage(); p.on('pageerror', e => errores.push(e.message));
	await p.route(/^https?:\/\/(?!localhost)/, r => r.abort());
	await p.route(/action=rau_dx_spots/, r => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: [{ dx: 'EA7XYZ', freq: '14.205', sp: 'K1AA', t: '12:00', info: 'CQ SSB' }, { dx: 'JA1ABC', freq: '7.074', sp: 'W1AW', t: '12:01', info: 'FT8' }] }) }));
	await p.goto(BASE + '/herramientas/dx/'); await p.waitForSelector('#dxl-lista > div');
	assert.match(await p.innerText('#dxl-lista'), /EA7XYZ[\s\S]*España/);
	assert.equal(await p.$$eval('#panel-dx iframe', l => l.length), 0, 'sin el recuadro de DXheat');
	assert.match(await p.getAttribute('#dxl-lista a', 'href'), /\/receptor\/\?f=14205\.0&m=usb/);
	await p.click('.dxl-anotar');
	assert.equal(await p.evaluate(() => JSON.parse(localStorage.getItem('rau_log_adif')).slice(-1)[0].call), 'EA7XYZ');
	await p.goto(BASE + '/herramientas/ctcss/'); await p.waitForSelector('#panel-ctcss table');
	const alto = await p.$eval('#panel-ctcss button, #panel-ctcss input, #panel-ctcss select', e => e.getBoundingClientRect().height).catch(() => 44);
	assert.ok(alto >= 43.5, 'blancos táctiles de 44 px: ' + alto);
	await p.goto(BASE + '/herramientas/brandmeister/');
	assert.equal(await p.inputValue('#bm-buscar'), '732', 'Colombia es el TG 732');
	await c.close();
});
test('19.61: portada nueva — qué es RAU, bandas, receptor lleno, actividades con concursos y sin Radio RAU', async () => {
	nav = nav || await chromium.launch({ args: ['--no-proxy-server'] });
	const p = await nav.newPage({ viewport: { width: 1280, height: 900 } }); p.on('pageerror', e => errores.push(e.message));
	await p.route(/^https?:\/\/(?!localhost)/, r => r.abort());
	await p.route(/action=rau_solar(&|$)/, r => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: { sfi: '107', aindex: '14', kindex: '1', bands: [{ name: '80m-40m', day: 'Good', night: 'Good' }, { name: '12m-10m', day: 'Poor', night: 'Poor' }] } }) }));
	await p.route(/action=rau_sdr_status/, r => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: { libres: 0, max: 7 } }) }));
	await p.route(/action=rau_sdr_historial/, r => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: { muestras: 100, horas: Array.from({ length: 24 }, (_, h) => ({ hora: h, libres: h === 5 ? 6 : 1 })) } }) }));
	await p.route(/action=rau_yt_live/, r => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: { live: false } }) }));
	await p.goto(BASE + '/'); await p.waitForSelector('#gauge-bandas li');
	assert.equal(await p.$('#radio-power'), null, 'sin Radio RAU');
	assert.equal(await p.$('#home-concurso-widget'), null, 'sin la franja suelta del concurso');
	assert.ok(await p.$('#que-es-rau ol li:nth-child(3)'), '«Empieza aquí» con tres pasos');
	assert.match(await p.innerText('#gauge-bandas'), /80–40m abierta[\s\S]*12–10m cerrada/);
	assert.doesNotMatch(await p.innerText('#gauge-estado'), /Excelente/, 'el índice K ya no promete «condiciones óptimas»');
	await p.waitForFunction(() => /igual puedes entrar[\s\S]*hacia las 5 h/.test(document.getElementById('sdr-lleno-nota').textContent));
	assert.equal((await p.textContent('#sdr-abrir')).trim(), 'Abrir el receptor', 'aunque esté lleno se puede entrar');
	assert.ok((await p.$$('#eventos .grid > *')).length >= 2, 'actividades con al menos un concurso');
	assert.match(await p.innerText('#eventos'), /Concurso · fin de semana/i);
	assert.match(await p.getAttribute('#eventos a[href*="calendar.google.com"]', 'href'), /cid=webcal/);
	await p.waitForSelector('#home-hoy:not(.hidden)');
	assert.equal(await p.$$eval('#home-envivo:not(.hidden)', l => l.length), 0, 'sin «en vivo» fuera de hora');
	// Durante la actividad: la tarjeta pasa a «En el aire ahora» y ofrece la frecuencia
	const inicio = +(await p.getAttribute('#home-proxima', 'data-inicio'));
	await p.close();
	if (inicio) {
		const q = await nav.newPage(); q.on('pageerror', e => errores.push(e.message));
		await q.route(/^https?:\/\/(?!localhost)/, r => r.abort());
		await q.clock.install({ time: new Date(inicio * 1000 + 10 * 60000) });
		await q.goto(BASE + '/'); await q.waitForSelector('#home-envivo:not(.hidden)');
		assert.match(await q.innerText('#home-proxima'), /En el aire ahora/i);
		await q.close();
	}
});
test('19.62–19.63: Condiciones — centro de mando: semáforo, barra, mapa con radar y capas, Windy, matriz, sitios, sala, buscar y menú', async () => {
	nav = nav || await chromium.launch({ args: ['--no-proxy-server'] });
	// Sin service worker: lo que él pide no pasa por las rutas simuladas de la prueba
	const ctxC = await nav.newContext({ viewport: { width: 1280, height: 900 }, serviceWorkers: 'block' });
	const p = await ctxC.newPage(); p.on('pageerror', e => errores.push(e.message));
	const horas = Array.from({ length: 48 }, (_, i) => ({ t: '2026-11-20T' + String((10 + i) % 24).padStart(2, '0') + ':00', temp: 20 + (i % 6), hr: 70, pp: i === 1 ? 90 : 20, mm: 0, cod: i === 1 ? 95 : 3, viento: 8, rafaga: 20, cape: 900, li: 1, vis: 20000, dia: 1 }));
	const datos = { punto: { lat: 6.2, lon: -75.6, locator: 'FJ26ee', cerca: { nombre: 'Envigado', km: 3 } },
		clima: { fuente: 'Open-Meteo', offset: -18000, elevacion: 1495, actual: { temp: 22.4, sensacion: 23, hr: 70, dia: 1, mm: 0, cod: 3, nubes: 80, presion: 850, viento: 8, dir: 90, rafaga: 20, vis: 20000, cape: 900 }, horas, dias: [{ fecha: '2026-11-20', cod: 95, max: 26, min: 16, mm: 5, pp: 80 }, { fecha: '2026-11-21', cod: 3, max: 27, min: 17, mm: 0, pp: 10 }, { fecha: '2026-11-22', cod: 61, max: 25, min: 16, mm: 2, pp: 60 }] },
		aire: { pm25: 12, pm10: 20, aqi: 55, uv: 4 },
		espacio: { kp: { valor: 1.3 }, kp_pron: 3.7, rayosx: { actual: 'B2.0', max: 'B5.0' }, escalas: { R: 0, S: 0, G: 0, prob_r: 10, prob_rx: 5 }, viento: { vel: 420, dens: 3.2, bz: -4.2, bt: 6 }, alertas: [], sfi: 140, ssn: 90, bandas: [{ name: '80m-40m', day: 'Good', night: 'Good' }, { name: '12m-10m', day: 'Poor', night: 'Poor' }] },
		drap: { mhz: 0, mensaje: 'Normal' },
		iono: { matriz: { completa: true, con_dato: 24, horas: Array.from({ length: 24 }, (_, i) => ({ h: (21 + i) % 24, fof2: 6 + (i % 4), mufd: 18 + (i % 6) })), serie: [] }, muf_punto: 19.1, iono: { nombre: 'Boa Vista, Brazil', km: 1694, fof2: 8.6, mufd: 27, hace: 20 }, nvis: { banda: '40 m', texto: 'foF2 8,6 MHz.' }, dx: ['40 m', '20 m'], estaciones: [{ n: 'Boa Vista, Brazil', lat: 2.8, lon: -60.7, mufd: 27, fof2: 8.6, hace: 20 }] },
		sismos: [{ mag: 4.8, lugar: 'Antioquia', t: Math.floor(Date.now() / 1000) - 3600, km: 80, prof: 30, lat: 6.9, lon: -75.9, url: '' }, { mag: 3.1, lugar: 'Los Santos', t: Math.floor(Date.now() / 1000) - 7200, km: 300, prof: 150, lat: 6.8, lon: -73.1, url: '' }],
		tend: { kp: [[Math.floor(Date.now() / 1000) - 3 * 3600, 2]], kp7: [[Math.floor(Date.now() / 1000) - 3 * 86400, 3], [Math.floor(Date.now() / 1000) - 3 * 3600, 2]], kp_pron: [], rayosx: [], rayosx7: [], viento: [], viento24: [], llamaradas: [] },
		repetidoras: [{ name: 'Repetidora Medellín', freq: '146.675', offset: '-600 kHz', tone: '100.0', km: 11 }],
		semaforo: { tormenta: { nivel: 2, texto: 'Tormenta eléctrica prevista hacia las 11:00. Desconecta las antenas.' }, viento: { nivel: 0, texto: 'Viento tranquilo.' }, hf: { nivel: 0, texto: 'Abiertas: 80–40m.' }, sol: { nivel: 0, texto: 'Sol tranquilo.' }, geomag: { nivel: 0, texto: 'Campo tranquilo.' } },
		generado: 1795200000 };
	const ionoOrig = JSON.parse(JSON.stringify(datos.iono));
	const pedidos = [];
	await p.route(/^https?:\/\/(?!localhost)/, r => r.abort());
	await p.route(/wp-json\/rau\/v1\/condiciones\?/, r => { pedidos.push(r.request().url()); r.fulfill({ contentType: 'application/json', body: JSON.stringify(datos) }); });
	const solarMock = { pron3: { emitido: 1795195800, dias: [1795132800, 1795219200, 1795305600], kp: Array.from({ length: 24 }, (_, i) => [1795132800 + i * 10800, i === 20 ? 5.67 : 2]), kp_max: 5.67, r12: [10, 15, 20], r3: [1, 1, 5], s1: [1, 1, 5], razon_g: 'G1 likely.' },
		o27: [[1795132800, 119, 15, 4], [1795219200, 113, 20, 5]], regiones: [{ n: 4579, lugar: 'N16W09', mag: 'BGD', manchas: 12, area: 300, pc: 60, pm: 30, px: 10 }], regiones_fecha: '2026-11-20',
		protones: [[1795190000, 0.4], [1795193600, 0.5]], dst: [[1795100000, -10], [1795190000, -35]], ciclo: { obs: [['2024-08', 215, 160], ['2024-09', 140, null]], pred: [['2026-12', 80, 70, 90], ['2027-06', 60, 50, 70]] },
		cmes: [{ id: 'a', t: 1795170000, vel: 1200, tipo: 'O', tierra: true, llegada: Math.floor(Date.now() / 1000) + 36 * 3600, kp: '5–7', url: '' }, { id: 'b', t: 1795180000, vel: 400, tipo: 'S', tierra: false, llegada: null, kp: null, url: '' }],
		tormenta: { nivel: 1, viene: 'Eyección solar dirigida a la Tierra: llegada estimada dom 22.' } };
	await p.route(/wp-json\/rau\/v1\/condiciones\/solar/, r => r.fulfill({ contentType: 'application/json', body: JSON.stringify(solarMock) }));
	await p.route(/wp-json\/rau\/v1\/condiciones\/sitios/, r => r.fulfill({ contentType: 'application/json', body: JSON.stringify([{ n: 'Repetidora Medellín — Monte Alvernia', f: '146.675', lat: 6.25, lon: -75.6, url: '/x', temp: 18, cod: 95, lluvia: 90, tormenta: { nivel: 2, texto: 'Tormenta' }, viento: { nivel: 0, kmh: 12 } }]) }));
	await p.route(/api\.rainviewer\.com/, r => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ host: 'https://tilecache.rainviewer.com', radar: { past: [{ time: 1795200000, path: '/v2/radar/a' }, { time: 1795200600, path: '/v2/radar/b' }], nowcast: [] } }) }));
	// 19.65: radio y local
	const j = (re, o) => p.route(re, r => r.fulfill({ contentType: 'application/json', body: JSON.stringify(o) }));
	await j(/condiciones\/siata/, { ok: true, niveles: [{ n: 'Q. La Iguaná', mun: 'Medellín', lat: 6.27, lon: -75.6, cm: 160, pre: 100, e: 2, dudoso: false, pct: 160 }, { n: 'Q. Rara', mun: 'Bello', lat: 6.33, lon: -75.55, cm: 900, pre: 100, e: 3, dudoso: true, pct: 900 }],
		lluvia: [{ n: 'P1', mun: 'Itagüí', lat: 6.17, lon: -75.6, mm: 2.5, llueve: true }], meteo: [], resumen: { llueve: 1, pluvios: 185, max_mm: 2.5, alerta: 1, niveles: 168, peor: 2 } });
	await j(/condiciones\/wspr/, { ok: true, sale: [{ b: '40 m', tx: 'HK4X', rx: 'EA4Y', a: [6.2, -75.6], z: [40.4, -3.7], km: 8032, snr: -18 }], entra: [], bandas: { '40 m': { sale: 1, entra: 0, max: 8032 } } });
	await j(/condiciones\/pota/, [{ act: 'HK4P', f: '14280', modo: 'SSB', ref: 'CO-0001', parque: 'Parque Arví', lugar: 'CO-ANT', lat: 6.28, lon: -75.5, t: 0 }]);
	await j(/condiciones\/semana/, { dias: [{ dia: '2026-11-20', mm: 5, rafaga: 40, tormenta: true, kp: 3.3, fl: 'M2.4' }], texto: 'En los últimos 7 días: 5 mm de lluvia, 1 día con tormenta eléctrica.' });
	await j(/condiciones\/archivo/, { dia: '2026-11-20', dias: [{ dia: '2026-11-20', kp: 3.3, fl: 'M2.4', n: 1 }], eventos: [{ t: 1795200000, tipo: 'sol', n: 1, x: 'Llamarada M2.4' }] });
	await j(/condiciones\/regiones/, [{ n: 'Caribe', lat: 18.5, lon: -66.1, km: 1712, sp: 36, muf: 22, bandas: { '80 m': 'buena', '40 m': 'buena', '30 m': 'buena', '20 m': 'marginal', '17 m': 'cerrada', '15 m': 'cerrada', '12 m': 'cerrada', '10 m': 'cerrada' } },
		{ n: 'Europa', lat: 48.9, lon: 2.35, km: 8609, sp: 41, muf: 16, bandas: { '80 m': 'buena', '40 m': 'buena', '30 m': 'buena', '20 m': 'cerrada', '17 m': 'cerrada', '15 m': 'cerrada', '12 m': 'cerrada', '10 m': 'cerrada' } }]);
	await j(/condiciones\/muf/, [{ v: 20, c: '#00ff00', p: [[-80, 0], [-70, 0]] }]);
	await j(/condiciones\/enlace/, { km: 8032, sp: 49, lp: 229, saltos: 3, puntos: [{ lat: 20, lon: -60, muf: 18, sol: -10, drap: null }, { lat: 35, lon: -20, muf: 15, sol: -20, drap: null }], muf: 16.5, fot: 14, luf: 3, dia: false,
		bandas: [{ b: '40 m', f: 7.1, e: 'buena' }, { b: '20 m', f: 14.2, e: 'marginal' }, { b: '10 m', f: 28.5, e: 'cerrada' }] });
	await p.addInitScript(() => { try { localStorage.removeItem('rau_cond_lugares'); localStorage.removeItem('rau_cond_rango'); } catch (e) {} });
	await p.goto(BASE + '/condiciones/?lat=6.24&lon=-75.58');
	await p.waitForSelector('#cnd-luz-tormenta[data-n="2"]');
	assert.match(await p.innerText('#cnd-luz-tormenta'), /Desconecta las antenas/);
	assert.match(await p.innerText('#cnd-ahora'), /22°[\s\S]*CAPE/i);
	assert.match(await p.innerText('#cnd-hf'), /Boa Vista[\s\S]*NVIS/i);
	assert.match(await p.innerText('#cnd-esp'), /R0[\s\S]*Kp estimado ahora/i);
	await p.waitForSelector('#cnd-sismo:not([hidden])');
	assert.match(await p.innerText('#cnd-sismo'), /Sismo M4,8 a 80 km[\s\S]*Antioquia/, 'sismo fuerte cercano: franja de alerta');
	assert.doesNotMatch(await p.innerText('#cnd-sismo'), /Los Santos/, 'los sismos pequeños no se anuncian');
	assert.equal(await p.$('#cnd-sis'), null, 'sin panel fijo de sismos');
	assert.equal(await p.isChecked('[data-capa-mapa="sismos"]'), false, 'la capa de sismos arranca apagada');
	// 19.65: SIATA, alcance WSPR, POTA, semana, archivo, balizas, enlace, rango, tablas accesibles, mis lugares
	await p.waitForSelector('#cnd-siata .cnd-kpis');
	assert.match(await p.innerText('#cnd-siata'), /Q\. La Iguaná[\s\S]*Precaución/i, 'quebradas con su estado de SIATA');
	assert.match(await p.innerText('#cnd-siata'), /dato dudoso/i, 'lectura imposible marcada como dudosa');
	assert.match(await p.innerText('#cnd-siata'), /Fuente: SIATA – Área Metropolitana del Valle de Aburrá/, 'atribución de SIATA');
	await p.waitForSelector('#cnd-alcance table'); assert.match(await p.innerText('#cnd-alcance'), /40 m[\s\S]*8\.032 km/);
	await p.waitForSelector('#cnd-pota li'); assert.match(await p.innerText('#cnd-pota'), /HK4P[\s\S]*Parque Arví/);
	await p.waitForSelector('#cnd-semana table'); assert.match(await p.innerText('#cnd-semana'), /1 día con tormenta/);
	await p.waitForSelector('#cnd-archivo li'); assert.match(await p.innerText('#cnd-archivo'), /Llamarada M2\.4/);
	assert.equal(await p.$$eval('#cnd-balizas tbody tr', l => l.length), 5, 'balizas NCDXF: una por banda');
	await p.click('.cnd-enl-rap[data-n="Madrid"]');
	await p.waitForSelector('#cnd-enl-res .cnd-kpis');
	assert.match(await p.innerText('#cnd-enl-res'), /8\.032[\s\S]*49°[\s\S]*prueba 40 m/i, 'enlace: distancia, rumbo y bandas');
	assert.ok(await p.$('#cnd-clima .cnd-sr table caption'), 'las gráficas traen su tabla de datos para lectores de pantalla');
	await p.click('[data-rango="7d"]');
	assert.match(await p.innerText('#cnd-tend'), /Índice Kp · 7 días/i, 'selector de rango de las gráficas');
	await p.click('[data-rango="6h"]');
	p.once('dialog', d => d.accept('Casa'));
	await p.click('#cnd-fav');
	assert.match(await p.innerText('#cnd-lugares'), /Casa/, 'mis lugares');
	assert.equal(await p.getAttribute('#cnd-fav', 'aria-pressed'), 'true');
	assert.match(await p.getAttribute('#cnd-franja', 'href'), /\/condiciones\/franja\/\?lat=6\.2&lon=-75\.6/, 'franja para OBS con el punto');
	assert.equal(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true, 'sin desborde horizontal');
	// 19.66: regiones × bandas, secciones, 6 m como oportunidad
	await p.waitForSelector('.cnd-regtab tbody tr');
	assert.equal(await p.$$eval('.cnd-regtab tbody tr', l => l.length), 2, 'tabla de bandas por región');
	assert.equal(await p.$$eval('.cnd-regtab tbody tr:first-child td.cnd-rc.ok', l => l.length), 3, 'Caribe: 80, 40 y 30 m abiertas');
	assert.equal(await p.$$eval('details.cnd-sec', l => l.length), 4, 'cuatro secciones');
	assert.equal(await p.$$eval('.cnd-secnav a', l => l.length), 4, 'navegación por secciones');
	await p.evaluate(() => { const l = document.getElementById('cnd-luz-seis'); l.dataset.op = '1'; });
	assert.doesNotMatch(await p.innerText('#cnd-estado'), /6 m abierta.*ALERTA|ALERTA · 6 m/i, '6 m abierta no pone la página en alerta');
	assert.ok(pedidos.every(u => /lat=6\.2&lon=-75\.6/.test(u)), 'al servidor solo va el punto redondeado: ' + pedidos[0]);
	// 19.63: barra de mando, estados con texto, relojes, matriz de bandas, gráficas y sitios
	assert.match(await p.innerText('#cnd-estado'), /ALERTA · Tormenta/i, 'estado general en la barra');
	assert.equal((await p.innerText('#cnd-luz-tormenta .cnd-palabra')).trim(), 'ALERTA', 'el estado se dice en texto, no solo con color');
	assert.match(await p.innerText('#cnd-reloj-utc'), /^\d\d:\d\d:\d\d$/);
	assert.match(await p.innerText('#cnd-reloj-local-et'), /UTC−5/i, 'hora local del punto');
	assert.equal(await p.$$eval('#cnd-matriz .c', l => l.length), 240, 'matriz de 10 bandas × 24 horas');
	datos.iono.matriz = { completa: false, con_dato: 3, horas: [] }; datos.iono.iono = null;
	await p.click('#cnd-recargar'); await p.waitForFunction(() => !document.getElementById('cnd-matriz'));
	assert.match(await p.innerText('#cnd-hf'), /MUF 3000 km aquí[\s\S]*19,1[\s\S]*Bandas ahora en tu punto/i, 'sin ionosonda ni 12 h de datos: MUF de KC2G en el punto y bandas de ahora');
	datos.iono = JSON.parse(JSON.stringify(ionoOrig)); await p.click('#cnd-recargar'); await p.waitForSelector('#cnd-matriz .c');
	assert.ok(await p.$('#cnd-clima .cnd-graf svg'), 'gráfica de 48 horas');
	await p.waitForSelector('#cnd-sitios table');
	assert.match(await p.innerText('#cnd-sitios'), /Medellín[\s\S]*ALERTA/i, 'sitios RAU con su estado');
	await p.click('#cnd-sala');
	assert.ok(await p.evaluate(() => document.body.classList.contains('cnd-sala') && getComputedStyle(document.querySelector('.cnd-pie')).display === 'none'), 'modo sala');
	await p.click('#cnd-sala');
	assert.ok(await p.evaluate(() => !document.body.classList.contains('cnd-sala')));
	assert.ok(await p.$('#cnd-imprimir'), 'botón de imprimir o PDF');
	// 19.64: tormentas solares, registro en dos partes, barra debajo del menú y sala en una sola pantalla
	await p.waitForSelector('#cnd-solar .cnd-kp3');
	assert.equal(await p.$$eval('#cnd-solar .cnd-kp3 .c', l => l.length), 24, 'pronóstico de NOAA: 3 días × 8 bloques');
	assert.match(await p.innerText('#cnd-solar'), /HACIA LA TIERRA[\s\S]*Llegada estimada[\s\S]*Kp esperado 5–7/i, 'la eyección dirigida a la Tierra con su llegada');
	assert.match(await p.innerText('#cnd-solar'), /4579[\s\S]*30%/, 'regiones activas con su probabilidad de llamarada');
	assert.ok(await p.$('#cnd-solar img[src*="sdo.gsfc.nasa.gov"]') && await p.$('#cnd-solar img[src*="lasco-c3"]'), 'imágenes del sol y de la corona');
	assert.ok((await p.$$('#cnd-solar .cnd-graf svg')).length >= 4, 'protones, Dst, 27 días y ciclo solar');
	assert.match(await p.innerText('#cnd-reg'), /Últimas 24 horas/i, 'registro separado en próximo y pasado');
	const top = await p.evaluate(() => { scrollTo(0, 1500); return new Promise(r => setTimeout(() => r([document.querySelector('header').getBoundingClientRect().bottom, document.querySelector('.cnd-barra').getBoundingClientRect().top]), 300)); });
	assert.ok(top[1] >= top[0] - 1, 'la barra de mando queda debajo del menú del sitio, no detrás: ' + top);
	await p.evaluate(() => scrollTo(0, 0));
	await p.setViewportSize({ width: 1920, height: 1080 }); await p.click('#cnd-sala'); await p.waitForTimeout(600);
	const sala = await p.evaluate(() => [document.documentElement.scrollHeight, innerHeight, (document.querySelector('#cnd-rota > .cnd-caja') || {}).id]);
	assert.ok(sala[0] <= sala[1] + 2, 'el modo sala cabe en una pantalla de 1920×1080: ' + sala);
	assert.ok(sala[2], 'la columna del modo sala muestra un panel rotativo');
	await p.click('#cnd-sala'); await p.waitForTimeout(300);
	assert.equal(await p.$$eval('#cnd-rota > *', l => l.length), 0, 'al salir, los paneles vuelven a su lugar');
	await p.waitForSelector('#cnd-mapa .leaflet-marker-icon');
	await p.waitForFunction(() => !document.getElementById('cnd-linea').hidden, null, { timeout: 15000 });
	assert.equal(await p.inputValue('#cnd-cuadro'), '1', 'el radar arranca en el cuadro más reciente');
	await p.click('#cnd-capas-btn'); await p.check('[data-capa-mapa="iono"]');
	await p.waitForFunction(() => [...document.querySelectorAll('#cnd-mapa .cnd-marca')].some(m => m.textContent.trim() === '27'));
	await p.click('[data-vista="windy"]');
	assert.match(await p.getAttribute('#cnd-windy', 'src'), /embed\.windy\.com\/embed2\.html\?lat=6\.24&lon=-75\.58.*overlay=wind/);
	await p.click('[data-capa="thunder"]');
	assert.match(await p.getAttribute('#cnd-windy', 'src'), /overlay=thunder/);
	assert.equal(await p.isVisible('#cnd-mapa'), false, 'en la vista de Windy no se ve el otro mapa');
	await p.fill('#cnd-buscar', 'FJ35'); await p.click('#cnd-sug button');
	await p.waitForFunction(() => /lat=5\.5/.test(location.search));
	assert.match(await p.innerText('#cnd-lugar'), /FJ35/);
	assert.ok(await p.$('header a[href*="/condiciones"]'), 'Condiciones está en el menú');
	await p.close();
	const h = await (await fetch(BASE + '/wp-sitemap-herramientas-1.xml')).text().catch(() => '');
	if (h) assert.match(h, /\/condiciones\//, 'en el mapa del sitio');
});
test('sin errores de JavaScript', () => { assert.deepEqual(errores, []); });
