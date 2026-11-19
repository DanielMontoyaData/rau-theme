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
test('sin errores de JavaScript', () => { assert.deepEqual(errores, []); });
