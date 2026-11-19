// Módem de audio: ida y vuelta con ruido, desfase, otra tasa de muestreo, eco y tramas perdidas.
//   node --test tests/radio/modem.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const M = require('../../assets/radio/modem.js');

let semilla = 7; const azar = () => (semilla = (semilla * 1103515245 + 12345) % 2147483648) / 2147483648;
const gauss = () => { let u = 0; while (!u) u = azar(); return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * azar()); };
// Ruido blanco con la SNR pedida en 2500 Hz (la señal tiene amplitud 0,6: potencia 0,18)
function ruido(x, tasa, snr) {
	const pn = 0.18 / Math.pow(10, snr / 10) * (tasa / 2) / 2500, s = Math.sqrt(pn), y = new Float32Array(x.length + tasa);
	for (let i = 0; i < y.length; i++) y[i] = (i < x.length ? x[i] : 0) + s * gauss();
	return y;
}
function recibir(audio, tasa, bloque = 4096) {
	const est = { mensajes: [], estados: [], errores: [] };
	const r = M.crearReceptor(tasa, e => est.estados.push(e), m => est.mensajes.push(m), e => est.errores.push(e));
	for (let i = 0; i < audio.length; i += bloque) r.alimentar(audio.subarray(i, i + bloque));
	est.r = r; return est;
}
const texto = n => { let s = ''; while (s.length < n) s += 'RAUEM1 ABC123 1/1 1A2B3C4D Deslizamiento en la vía, 3 heridos. '; return new TextEncoder().encode(s.slice(0, n)); };
const igual = (a, b) => a.length === b.length && a.every((v, i) => v === b[i]);

test('tramas: 8 bytes cada una más el CRC del mensaje, la última marcada', () => {
	assert.equal(M.tramas(new Uint8Array(6)).length, 1);
	assert.equal(M.tramas(new Uint8Array(14)).length, 2);
	assert.equal(M.tramas(new Uint8Array(15)).length, 3);
	assert.throws(() => M.tramas(new Uint8Array(M.MAX_BYTES + 1)));
});
for (const modo of ['robusto', 'rapido']) {
	test(`${modo}: ida y vuelta limpia a 48 kHz`, () => {
		const b = texto(100), a = M.sintetizar(b, 48000, { modo });
		assert.ok(Math.abs(a.length / 48000 - M.duracion(M.tramas(b).length, modo)) < 0.05);
		const e = recibir(a, 48000);
		assert.equal(e.mensajes.length, 1); assert.ok(igual(e.mensajes[0], b));
		assert.equal(e.estados.at(-1).ultima.modo, modo);
	});
}
test('robusto con ruido a −8 dB, 90 Hz de desfase y 44,1 kHz', () => {
	const b = texto(60), e = recibir(ruido(M.sintetizar(b, 44100, { modo: 'robusto', desfase: 90 }), 44100, -8), 44100);
	assert.equal(e.mensajes.length, 1); assert.ok(igual(e.mensajes[0], b));
	assert.ok(Math.abs(e.estados[0].ultima.desfase - 90) <= 13, 'desfase medido ' + e.estados[0].ultima.desfase);
});
test('rápido con ruido a −3 dB y −120 Hz', () => {
	const b = texto(60), e = recibir(ruido(M.sintetizar(b, 48000, { modo: 'rapido', desfase: -120 }), 48000, -3), 48000);
	assert.equal(e.mensajes.length, 1); assert.ok(igual(e.mensajes[0], b));
});
test('relojes distintos (300 ppm) y eco de sala (20 ms, mitad de volumen)', () => {
	const b = texto(80), x = M.sintetizar(b, 48000, { modo: 'robusto' });
	const y = new Float32Array(x.length + 2000), d = 960; // eco
	for (let i = 0; i < x.length; i++) { y[i] += x[i]; y[i + d] += 0.5 * x[i]; }
	const z = new Float32Array(Math.floor(y.length / 1.0003)); // el emisor va un poco más rápido
	for (let i = 0; i < z.length; i++) { const c = i * 1.0003, i0 = Math.floor(c), f = c - i0; z[i] = y[i0] * (1 - f) + (y[i0 + 1] || 0) * f; }
	const e = recibir(ruido(z, 48000, 0), 48000);
	assert.equal(e.mensajes.length, 1); assert.ok(igual(e.mensajes[0], b));
});
test('tramas perdidas: dice cuáles faltan y se completa con el reenvío de solo esas', () => {
	const b = texto(120), n = M.tramas(b).length, todas = [...Array(n).keys()], perdidas = [3, 7];
	const r = M.crearReceptor(48000, e => (ult = e), m => (msg = m)); let ult = null, msg = null;
	r.alimentar(M.sintetizar(b, 48000, { modo: 'rapido', solo: todas.filter(i => !perdidas.includes(i)) }));
	r.alimentar(new Float32Array(48000));
	assert.equal(msg, null); assert.deepEqual(ult.faltan, perdidas); assert.equal(ult.total, n);
	r.alimentar(M.sintetizar(b, 48000, { modo: 'rapido', solo: perdidas })); r.alimentar(new Float32Array(48000));
	assert.ok(msg && igual(msg, b));
});
test('solo ruido o voz no producen tramas', () => {
	semilla = 99; const n = 48000 * 30, x = new Float32Array(n);
	for (let i = 0; i < n; i++) x[i] = 0.3 * gauss() + 0.4 * Math.sin(2 * Math.PI * (300 + 200 * Math.sin(i / 9000)) * i / 48000) * (Math.sin(i / 3000) > 0 ? 1 : 0);
	const e = recibir(x, 48000);
	assert.equal(e.estados.length, 0);
});
test('umbral: tasa de éxito por SNR (informativo)', () => {
	const fila = [];
	for (const [modo, snrs] of [['robusto', [-12, -10, -8]], ['rapido', [-8, -6, -4]]]) for (const snr of snrs) {
		let ok = 0, tot = 0;
		for (let rep = 0; rep < 4; rep++) { const b = texto(16), est = recibir(ruido(M.sintetizar(b, 8000, { modo }), 8000, snr), 8000); tot += M.tramas(b).length; ok += est.estados.length; }
		fila.push(`${modo} ${snr} dB: ${Math.round(100 * ok / tot)}%`);
	}
	console.log('   ' + fila.join(' · '));
});
