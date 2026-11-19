// Decodificadores del receptor en un navegador real, con un KiwiSDR simulado: se reemplaza el WebSocket
// por uno que entrega paquetes de audio en el formato del Kiwi (SND, int16), con una señal FT4 alineada
// con las ranuras UTC reales y una de CW. Prueba la cadena completa: audio → reloj → worker → tabla.
//   RAU_EM_BASE=http://localhost:8099 node --test tests/radio/receptor.e2e.mjs
// Necesita el sitio con el tema, la página /receptor/ y la opción rau_kiwi_relay con cualquier dirección.
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const F = require('../../assets/radio/ftx.js');
const BASE = process.env.RAU_EM_BASE || 'http://localhost:8099';
let nav, p; const errores = [];

let semilla = 3; const azar = () => { semilla = (semilla * 1103515245 + 12345) % 2147483648; return semilla / 2147483648; };
const gauss = () => { let u = 0; while (!u) u = azar(); return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * azar()); };
/* Una ranura FT4 (7,5 s) con dos mensajes que empiezan a los 0,5 s, más ruido */
function ranuraFT4() {
	const x = new Float32Array(90000); for (let i = 0; i < x.length; i++) x[i] = 0.05 * gauss();
	[['CQ HK4RAU FJ26', 1200], ['HK4ABC K1ABC -10', 1800]].forEach(([m, f]) => {
		const s = F.sintetizar(F.tonos(F.empacar(m), F.FT4), f, F.FT4, 12000);
		for (let i = 0; i < s.length; i++) x[6000 + i] += 0.3 * s[i];
	});
	return x;
}
/* CW a 20 PPM, tono de 700 Hz, que se repite */
function cw() {
	const M = { C: '-.-.', Q: '--.-', D: '-..', E: '.', H: '....', K: '-.-', '4': '....-', R: '.-.', A: '.-', U: '..-' }, u = 0.06, tr = []; let t = 0.5;
	'CQ CQ DE HK4RAU HK4RAU K '.split('').forEach(ch => { if (ch === ' ') { t += 4 * u; return; } M[ch].split('').forEach((e, k, a) => { const d = (e === '.' ? 1 : 3) * u; tr.push([t, t + d]); t += d + (k < a.length - 1 ? u : 0); }); t += 3 * u; });
	t += 1; const n = Math.round(t * 12000), x = new Float32Array(n);
	for (let i = 0; i < n; i++) { const tt = i / 12000, on = tr.some(([a, b]) => tt >= a && tt <= b); x[i] = (on ? 0.3 : 0) * Math.sin(2 * Math.PI * 700 * tt) + 0.03 * gauss(); }
	return x;
}
const b64 = (a) => Buffer.from(a.buffer).toString('base64');

before(async () => {
	nav = await chromium.launch({ args: ['--no-proxy-server'] });
	p = await (await nav.newContext({ viewport: { width: 1300, height: 1000 } })).newPage();
	p.on('pageerror', e => errores.push(e.message));
	await p.route(/^https?:\/\/(?!localhost)/, r => r.abort());
	await p.addInitScript({ content: `(function(){
		function f32(s){ var b=atob(s), u=new Uint8Array(b.length); for(var i=0;i<b.length;i++) u[i]=b.charCodeAt(i); return new Float32Array(u.buffer); }
		var FT=f32('${b64(ranuraFT4())}'), CW=f32('${b64(cw())}'), modo='usb';
		function msg(ws, t){ var e=new TextEncoder().encode('MSG '+t), u=new Uint8Array(e.length); u.set(e); ws.onmessage && ws.onmessage({data:u.buffer}); }
		window.WebSocket=function(url){
			var ws=this, timer=null, ult=Date.now(), seq=0; ws.url=url; ws.readyState=0;
			ws.send=function(t){ (window.__enviados=window.__enviados||[]).push(t); var m=/mod=(\\w+)/.exec(t); if(m) modo=m[1]; };
			ws.close=function(){ clearInterval(timer); ws.readyState=3; };
			setTimeout(function(){
				ws.readyState=1; ws.onopen && ws.onopen();
				if(!/SND$/.test(url)){ msg(ws, 'wf_fft_size=1024 zoom_max=14 bandwidth=30000000'); return; }
				msg(ws, 'audio_rate=12000'); msg(ws, 'sample_rate=12000.000');
				timer=setInterval(function(){
					var ahora=Date.now(), n=Math.round((ahora-ult)*12); if(n<=0) return;
					var buf=new ArrayBuffer(10+2*n), dv=new DataView(buf), u=new Uint8Array(buf);
					u[0]=83; u[1]=78; u[2]=68; u[3]=0; dv.setUint32(4, seq++, true); dv.setUint16(8, 2000, false);
					for(var i=0;i<n;i++){ var t=(ult+i/12)/1000, v;
						if(modo==='cw') v=CW[Math.floor(t*12000)%CW.length]; else v=FT[Math.floor((t%7.5)*12000)];
						dv.setInt16(10+2*i, Math.max(-32767, Math.min(32767, Math.round(v*32767))), false); }
					ult=ahora; ws.onmessage && ws.onmessage({data:buf});
				}, 40);
			}, 30);
		};
		window.WebSocket.OPEN=1;
	})();` });
});
after(async () => { await nav.close(); });

test('FT4: decodifica en el navegador, alinea con la hora UTC y resalta CQ y colombianos', async () => {
	await p.goto(BASE + '/receptor/'); await p.waitForSelector('#rx-power');
	await p.click('#rx-power'); await p.waitForFunction(() => /Conectado/.test(document.querySelector('#rx-libres').textContent), null, { timeout: 10000 });
	await p.click('.rx-dec[data-d=FT4]');
	assert.match(await p.textContent('#rx-modo-txt'), /USB/);
	await p.waitForFunction(() => /CQ HK4RAU FJ26/.test(document.querySelector('#rx-ft-filas').textContent), null, { timeout: 40000 });
	const t = await p.textContent('#rx-ft-filas');
	assert.match(t, /HK4ABC K1ABC -10/);
	assert.ok(await p.$('#rx-ft-filas .rx-ft-co'), 'resalta indicativos colombianos');
	assert.ok(await p.$('#rx-ft-filas tr.rx-ft-cqf'), 'resalta los CQ');
	assert.match(await p.textContent('#rx-ft-info'), /2 mensajes en [\d.]+ s · dial/);
	// Con la corrección de hora, en las ranuras siguientes el DT queda cerca de 0
	await p.waitForFunction(() => { const f = [...document.querySelectorAll('#rx-ft-filas tr.rx-ft-ult td:nth-child(3)')].map(td => Math.abs(+td.textContent)); return f.length && f.every(v => v <= 0.2); }, null, { timeout: 40000 });
	if (process.env.CAPTURA) { await p.$eval('#rx-ft', x => x.scrollIntoView({ block: 'center' })); await p.screenshot({ path: process.env.CAPTURA + '/receptor-ft4.png' }); }
	// Para decodificar: ganancia fija y audio sin compresión
	const env = await p.evaluate(() => window.__enviados.join('\n'));
	assert.match(env, /SET agc=0 [^\n]*manGain=50/); assert.match(env, /SET compression=0/);
	// Mapa: el que llama CQ con su locator queda ubicado, con la distancia al receptor
	await p.click('#rx-ft-mapa-b');
	await p.waitForSelector('#rx-ft-mapa .leaflet-interactive', { timeout: 10000 });
	assert.ok(await p.evaluate(() => [...document.querySelectorAll('#rx-ft-mapa path.leaflet-interactive')].length >= 1));
	await p.check('#rx-ft-cq');
	assert.doesNotMatch(await p.textContent('#rx-ft-filas'), /HK4ABC K1ABC/, 'el filtro deja solo los CQ');
});

test('CW: el decodificador nuevo lee la señal del receptor', async () => {
	await p.evaluate(() => { window.__enviados = []; });
	await p.click('.rx-dec[data-d=cw]');
	assert.match(await p.textContent('#rx-modo-txt'), /CW/);
	assert.match(await p.evaluate(() => window.__enviados.join('\n')), /SET compression=1/, 'al salir de FT se restaura la compresión');
	assert.equal(await p.isHidden('#rx-ft'), true);
	try { await p.waitForFunction(() => /CQ CQ DE HK4RAU/.test(document.querySelector('#rx-cw-texto').textContent), null, { timeout: 40000 }); } catch (e) { console.log('DEP', await p.textContent('#rx-cw-texto'), '|', await p.textContent('#rx-cw-info'), '|', await p.evaluate(() => window.__enviados.slice(-6).join(' ; '))); throw e; }
	assert.match(await p.textContent('#rx-cw-info'), /2\d PPM · tono 7\d\d Hz/);
	if (process.env.CAPTURA) { await p.$eval('#rx-cw', x => x.scrollIntoView({ block: 'center' })); await p.screenshot({ path: process.env.CAPTURA + '/receptor-cw.png' }); }
});

test('CW skimmer: lista la estación y tocarla la sintoniza', async () => {
	await p.check('#rx-cw-skim');
	await p.waitForFunction(() => /HK4RAU/.test(document.querySelector('#rx-cw-canales').textContent), null, { timeout: 40000 });
	const f0 = await p.inputValue('#rx-input');
	const fCanal = await p.$eval('.rx-cw-canal', (b) => b.dataset.f);
	assert.ok(Math.abs(+fCanal - (+f0 + 0.2)) < 0.02, 'tono de 700 Hz con portadora en 500: +0,2 kHz (' + fCanal + ' frente a ' + f0 + ')');
	await p.click('.rx-cw-canal');
	assert.equal(+(await p.inputValue('#rx-input')), +fCanal);
});

test('hora del servidor disponible para comparar el reloj', async () => {
	const r = await p.evaluate(async () => { const t0 = Date.now(), j = await (await fetch('/wp-admin/admin-ajax.php?action=rau_hora', { cache: 'no-store' })).json(); return { off: (t0 + Date.now()) / 2 - j.t }; });
	assert.ok(Math.abs(r.off) < 2000, 'diferencia ' + r.off);
});

test('sin errores de JavaScript', () => { assert.deepEqual(errores, []); });
