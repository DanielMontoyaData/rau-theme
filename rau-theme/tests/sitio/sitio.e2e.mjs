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
test('sin errores de JavaScript', () => { assert.deepEqual(errores, []); });
