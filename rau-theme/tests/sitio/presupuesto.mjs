// Presupuesto de rendimiento en un celular de gama baja emulado (CPU 4× más lenta, red «4G lenta»).
// Mide por página: bytes transferidos, peticiones, JavaScript, LCP y bloqueo del hilo principal (TBT aproximado).
//   RAU_EM_BASE=http://localhost:8099 node --test tests/rendimiento/presupuesto.mjs
// Con MEDIR=1 solo imprime la tabla. Las cifras del servidor local no son las del sitio real (sin
// LiteSpeed ni CDN): los límites miden peso y trabajo en el navegador, que sí son comparables.
import { test, after } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { chromium, devices } = require('playwright');
const BASE = process.env.RAU_EM_BASE || 'http://localhost:8099';
// KB transferidos (sin fuentes de Google ni mosaicos de mapas), KB de JS, LCP en ms, TBT en ms
const PRESUPUESTO = {
	'/':              { kb: 900, js: 300, lcp: 4000, tbt: 600 },
	'/receptor/':     { kb: 900, js: 350, lcp: 4000, tbt: 800 },
	'/emergencias/':  { kb: 700, js: 450, lcp: 4000, tbt: 800 },
	'/herramientas/': { kb: 1600, js: 900, lcp: 5000, tbt: 2500 },
	'/modem/':        { kb: 150, js: 120, lcp: 2500, tbt: 300 },
};
let nav; const filas = [];
after(async () => { if (nav) await nav.close(); if (filas.length) console.table(filas); });
async function medir(ruta) {
	nav = nav || await chromium.launch({ args: ['--no-proxy-server'] });
	const ctx = await nav.newContext({ ...devices['Moto G4'], serviceWorkers: 'block' });
	const p = await ctx.newPage();
	await p.route(/^https?:\/\/(?!localhost)/, r => r.abort());
	const cdp = await ctx.newCDPSession(p);
	await cdp.send('Network.enable');
	await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
	await cdp.send('Network.emulateNetworkConditions', { offline: false, latency: 150, downloadThroughput: 1.6e6 / 8, uploadThroughput: 750e3 / 8 });
	let bytes = 0, js = 0, n = 0; const tipos = {};
	cdp.on('Network.responseReceived', e => { tipos[e.requestId] = e.type; });
	cdp.on('Network.loadingFinished', e => { n++; bytes += e.encodedDataLength; if (tipos[e.requestId] === 'Script') js += e.encodedDataLength; });
	await p.addInitScript(() => {
		window.__lcp = 0; window.__tbt = 0;
		new PerformanceObserver(l => { for (const e of l.getEntries()) window.__lcp = e.startTime; }).observe({ type: 'largest-contentful-paint', buffered: true });
		new PerformanceObserver(l => { for (const e of l.getEntries()) window.__tbt += Math.max(0, e.duration - 50); }).observe({ type: 'longtask', buffered: true });
	});
	await p.goto(BASE + ruta, { waitUntil: 'load', timeout: 120000 });
	await p.waitForTimeout(3000);
	const { lcp, tbt } = await p.evaluate(() => ({ lcp: Math.round(window.__lcp), tbt: Math.round(window.__tbt) }));
	await ctx.close();
	const r = { ruta, peticiones: n, kb: Math.round(bytes / 1024), js_kb: Math.round(js / 1024), lcp_ms: lcp, tbt_ms: tbt };
	filas.push(r); return r;
}
for (const [ruta, lim] of Object.entries(PRESUPUESTO)) {
	test(`presupuesto de ${ruta}`, async () => {
		const r = await medir(ruta);
		if (process.env.MEDIR) return;
		assert.ok(r.kb <= lim.kb, `${r.kb} KB > ${lim.kb} KB`);
		assert.ok(r.js_kb <= lim.js, `JS ${r.js_kb} KB > ${lim.js} KB`);
		assert.ok(r.lcp_ms <= lim.lcp, `LCP ${r.lcp_ms} ms > ${lim.lcp} ms`);
		assert.ok(r.tbt_ms <= lim.tbt, `bloqueo ${r.tbt_ms} ms > ${lim.tbt} ms`);
	});
}
