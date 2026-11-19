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
const J8 = require('../../assets/radio/js8.js');
const DG = require('../../assets/radio/digi.js');
const BASE = process.env.RAU_EM_BASE || 'http://localhost:8099';
let nav, p; const errores = [];

let semilla = 3; const azar = () => { semilla = (semilla + 0x6D2B79F5) | 0; let t = Math.imul(semilla ^ (semilla >>> 15), 1 | semilla); t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t; return ((t ^ (t >>> 14)) >>> 0) / 4294967296; }; // mulberry32
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
/* Una ranura JS8 (15 s): mensaje dirigido al grupo @EMCOMM y un latido con cuadrícula */
const AN = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ /@', NB = 37 * 36 * 10 * 27 * 27 * 27;
const bitsDe = (v, n) => { const b = []; for (let i = n - 1; i >= 0; i--) b.push(Math.floor(v / 2 ** i) % 2); return b; };
const indic = c => c === '@EMCOMM' ? NB + 36 : 36 * 10 * 27 ** 3 * AN.indexOf(c[0]) + 10 * 27 ** 3 * AN.indexOf(c[1]) + 27 ** 3 * AN.indexOf(c[2]) + 27 * 27 * (AN.indexOf(c[3]) - 10) + 27 * (AN.indexOf(c[4]) - 10) + (AN.indexOf(c[5]) - 10);
function ranuraJS8() {
	const x = new Float32Array(180000); for (let i = 0; i < x.length; i++) x[i] = 0.05 * gauss();
	const dir = [...bitsDe(3, 3), ...bitsDe(indic('HK4ABC'), 28), ...bitsDe(indic('@EMCOMM'), 28), ...bitsDe(25, 5), 0, 0, ...bitsDe(31 - 12, 6)];
	const s = J8.sintetizar(J8.codificar(dir, 3), 1300, 12000); for (let i = 0; i < s.length; i++) x[i] += 0.3 * s[i];
	return x;
}
/* RTTY, PSK31 y SSTV (Robot 36) que se repiten, con algo de ruido */
function conRuido(x, n, a = 0.02) { const y = new Float32Array(n); for (let i = 0; i < n; i++) y[i] = (i < x.length ? 0.3 * x[i] : 0) + a * gauss(); return y; }
const rttyA = () => conRuido(DG.sintetizar.rtty('RYRYRY CQ CQ DE HK4RAU HK4RAU K ', 12000, 1615), 12000 * 9);
const pskA = () => conRuido(DG.sintetizar.psk31('  CQ CQ DE HK4RAU HK4RAU PSE K  ', 12000, 1200), 12000 * 9);
const sstvA = () => { const x = DG.sintetizar.sstv(8, (px, py) => [px < 160 ? 220 : 40, 120, py], 12000); return conRuido(x, x.length + 12000 * 20); };
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
		var FT=f32('${b64(ranuraFT4())}'), CW=f32('${b64(cw())}'), J8=f32('${b64(ranuraJS8())}'), RT=f32('${b64(rttyA())}'), PK=f32('${b64(pskA())}'), SV=f32('${b64(sstvA())}'), modo='usb', js8=false, dig='';
		function msg(ws, t){ var e=new TextEncoder().encode('MSG '+t), u=new Uint8Array(e.length); u.set(e); ws.onmessage && ws.onmessage({data:u.buffer}); }
		window.WebSocket=function(url){
			var ws=this, timer=null, ult=Date.now(), seq=0; ws.url=url; ws.readyState=0; (window.__urls=window.__urls||[]).push(url);
			ws.send=function(t){ (window.__enviados=window.__enviados||[]).push(t); var m=/mod=(\\w+)/.exec(t); if(m) modo=m[1]; var fq=/freq=([\\d.]+)/.exec(t); if(fq){ var F=+fq[1], cerca=function(a){ return a.some(function(v){ return Math.abs(F-v)<1; }); }; js8=cerca([7078,14078]); dig=cerca([7040,14085])?'rtty':cerca([7070,14070])?'psk':cerca([7171,14230])?'sstv':''; } };
			ws.close=function(){ clearInterval(timer); ws.readyState=3; };
			setTimeout(function(){
				ws.readyState=1; ws.onopen && ws.onopen();
				if(!/SND$/.test(url)){ msg(ws, 'wf_fft_size=1024 zoom_max=14 bandwidth=30000000'); return; }
				msg(ws, 'audio_rate=12000'); msg(ws, 'sample_rate=12000.000');
				timer=setInterval(function(){
					var ahora=Date.now(), n=Math.round((ahora-ult)*12); if(n<=0) return;
					var buf=new ArrayBuffer(10+2*n), dv=new DataView(buf), u=new Uint8Array(buf);
					u[0]=83; u[1]=78; u[2]=68; u[3]=0; dv.setUint32(4, seq++, true); dv.setUint16(8, url.indexOf('/r/prueba/')>=0 ? 600 : 800, false);
					for(var i=0;i<n;i++){ var t=(ult+i/12)/1000, v;
						if(modo==='cw') v=CW[Math.floor(t*12000)%CW.length]; else if(js8) v=J8[Math.floor((t%15)*12000)]; else if(dig==='rtty') v=RT[Math.floor(t*12000)%RT.length]; else if(dig==='psk') v=PK[Math.floor(t*12000)%PK.length]; else if(dig==='sstv') v=SV[Math.floor(t*12000)%SV.length]; else v=FT[Math.floor((t%7.5)*12000)];
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
	// Quien transmitió queda anotado sobre la cascada, en su frecuencia
	const dec = await p.evaluate(() => RauRx.decodificados());
	assert.ok(dec.some(x => x.dx === 'HK4RAU' && Math.abs(x.khz - (7047.5 + 1.2)) < 0.01 || x.dx === 'HK4RAU' && Math.abs(x.khz - (14080 + 1.2)) < 0.01), JSON.stringify(dec));
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

test('JS8: decodifica un mensaje al grupo @EMCOMM con el texto que muestra JS8Call', async () => {
	await p.uncheck('#rx-ft-cq');
	await p.click('.rx-dec[data-d=JS8]');
	assert.match(await p.textContent('#rx-ft-titulo'), /JS8/);
	await p.waitForFunction(() => /HK4ABC: @EMCOMM SNR -12/.test(document.querySelector('#rx-ft-filas').textContent), null, { timeout: 60000 });
	assert.match(await p.textContent('#rx-ft-info'), /1 mensaje en [\d.]+ s · dial 7\.0780|1 mensaje en [\d.]+ s · dial 14\.0780/);
});

test('RTTY y PSK31: texto en el panel digital', async () => {
	await p.click('.rx-dec[data-d=rtty]');
	await p.waitForFunction(() => /HK4RAU HK4RAU K/.test(document.querySelector('#rx-dig-texto').textContent), null, { timeout: 40000 });
	assert.match(await p.textContent('#rx-dig-info'), /RTTY 45,45 Bd · marca 1[56]\d\d Hz/);
	await p.click('.rx-dec[data-d=psk31]');
	await p.waitForFunction(() => /DE HK4RAU HK4RAU PSE K/.test(document.querySelector('#rx-dig-texto').textContent), null, { timeout: 40000 });
	assert.match(await p.textContent('#rx-dig-info'), /PSK31 · 1\d{3}\.\d Hz/);
});
test('SSTV: el VIS arranca la imagen y queda en la galería para descargar', async () => {
	await p.click('.rx-dec[data-d=sstv]');
	assert.match(await p.textContent('#rx-modo-txt'), /LSB|USB/);
	await p.waitForFunction(() => /imagen completa/.test(document.querySelector('#rx-sstv-info').textContent), null, { timeout: 120000 });
	assert.match(await p.textContent('#rx-sstv-info'), /Robot 36/);
	assert.ok(await p.$('#rx-sstv-galeria img'));
	// La mitad izquierda sale rojiza y la derecha no
	const [izq, der] = await p.evaluate(() => { const c = document.querySelector('#rx-sstv-cv').getContext('2d'); return [c.getImageData(60, 120, 1, 1).data[0], c.getImageData(260, 120, 1, 1).data[0]]; });
	assert.ok(izq > der + 60, izq + ' / ' + der);
});

test('CW: el decodificador nuevo lee la señal del receptor', async () => {
	await p.evaluate(() => { window.__enviados = []; });
	await p.click('.rx-dec[data-d=cw]');
	assert.match(await p.textContent('#rx-modo-txt'), /CW/);
	assert.match(await p.evaluate(() => window.__enviados.join('\n')), /SET compression=1/, 'al salir de los modos digitales se restaura la compresión');
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

test('otro receptor: comparar el S-meter y cambiar de receptor', { skip: false }, async () => {
	if (!(await p.$('#rx-comparar'))) { console.log('   (sin rau_receptores configurado: se omite)'); return; }
	await p.selectOption('#rx-comparar', 'prueba');
	await p.waitForFunction(() => /Receptor de prueba: S\d.*diferencia \+20 dB/.test(document.querySelector('#rx-comp').textContent), null, { timeout: 15000 });
	assert.ok(await p.evaluate(() => window.__urls.some(u => /\/r\/prueba\/ws\/kiwi\/\d+\/SND$/.test(u))), 'la comparación va por /r/prueba/');
	await p.selectOption('#rx-comparar', '');
	await p.selectOption('#rx-receptor', 'prueba');
	await p.waitForFunction(() => /Receptor de prueba/.test(document.querySelector('#rx-placa-marca').textContent));
	await p.waitForFunction(() => /Conectado/.test(document.querySelector('#rx-libres').textContent), null, { timeout: 10000 });
	// El S-meter es el del otro receptor (600 en la trama = −67 dBm)
	await p.waitForFunction(() => /-67 dBm/.test(document.querySelector('#rx-s').textContent), null, { timeout: 10000 });
	await p.selectOption('#rx-receptor', '');
	await p.waitForFunction(() => document.querySelector('#rx-placa-marca').textContent === 'HK4RAU');
});

test('hora del servidor disponible para comparar el reloj', async () => {
	const r = await p.evaluate(async () => { const t0 = Date.now(), j = await (await fetch('/wp-admin/admin-ajax.php?action=rau_hora', { cache: 'no-store' })).json(); return { off: (t0 + Date.now()) / 2 - j.t }; });
	assert.ok(Math.abs(r.off) < 2000, 'diferencia ' + r.off);
});

test('sin errores de JavaScript', () => { assert.deepEqual(errores, []); });
