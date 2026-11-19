// RTTY, PSK31 y SSTV (assets/radio/digi.js): codificar, pasar por ruido y decodificar.
//   node --test tests/radio/digi.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const D = require('../../assets/radio/digi.js');
const T = 12000;
let semilla = 5; const azar = () => { semilla = (semilla + 0x6D2B79F5) | 0; let t = Math.imul(semilla ^ (semilla >>> 15), 1 | semilla); t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t; return ((t ^ (t >>> 14)) >>> 0) / 4294967296; }; // mulberry32
const gauss = () => { let u = 0; while (!u) u = azar(); return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * azar()); };
// Ruido con la SNR pedida en 3 kHz (señal de amplitud 1: potencia 0,5)
function ruido(x, snr, antes = 1) { const s = Math.sqrt(0.5 / 10 ** (snr / 10) * (T / 2) / 3000), n = Math.round(antes * T), y = new Float32Array(x.length + 2 * n); for (let i = 0; i < y.length; i++) y[i] = (i >= n && i - n < x.length ? x[i - n] : 0) + s * gauss(); return y; }
function correr(dec, x, bloque = 1024) { for (let i = 0; i < x.length; i += bloque) dec.procesar(x.subarray(i, i + bloque)); }
const MSJ = 'CQ CQ DE HK4RAU HK4RAU PSE K';

test('RTTY: texto con ruido a 10 dB, tonos en cualquier lugar', () => {
	let t = ''; const d = D.rtty(T, x => (t = x));
	correr(d, ruido(D.sintetizar.rtty('RYRYRY ' + MSJ + ' 73 599', T, 1615), 10, 2));
	assert.match(t, /CQ CQ DE HK4RAU HK4RAU PSE K 73 599/);
});
test('RTTY: polaridad invertida se detecta sola', () => {
	let t = ''; const d = D.rtty(T, x => (t = x));
	correr(d, ruido(D.sintetizar.rtty('RYRYRY ' + MSJ + ' ' + MSJ, T, 2125, true), 12, 2));
	assert.match(t, /HK4RAU HK4RAU PSE K/);
});
test('PSK31: texto exacto con ruido a 0 dB (en 3 kHz), en una frecuencia cualquiera', () => {
	let t = ''; const d = D.psk31(T, x => (t = x));
	correr(d, ruido(D.sintetizar.psk31('  ' + MSJ + ' ' + MSJ + ' 73', T, 1003.4), 0, 2));
	assert.match(t, /CQ CQ DE HK4RAU HK4RAU PSE K 73/);
});
test('PSK31 a −6 dB: menos del 10 % de caracteres errados', () => {
	let t = ''; const d = D.psk31(T, x => (t = x)), m = (MSJ + ' ').repeat(3).trim();
	correr(d, ruido(D.sintetizar.psk31('  ' + m, T, 1503), -6, 2));
	// Distancia de edición contra lo enviado
	const a = t.trim(), b = m, dp = Array.from({ length: a.length + 1 }, (_, i) => [i, ...Array(b.length).fill(0)]);
	for (let j = 1; j <= b.length; j++) dp[0][j] = j;
	for (let i = 1; i <= a.length; i++) for (let j = 1; j <= b.length; j++) dp[i][j] = Math.min(dp[i - 1][j] + 1, dp[i][j - 1] + 1, dp[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
	const cer = dp[a.length][b.length] / b.length; console.log(`   PSK31 −6 dB: ${(cer * 100).toFixed(1)} % errados`);
	assert.ok(cer < 0.1, JSON.stringify(t));
});
test('ruido solo (20 s): RTTY y PSK31 no escriben', () => {
	semilla = 9; let a = '', b = ''; const x = new Float32Array(T * 20); for (let i = 0; i < x.length; i++) x[i] = 0.3 * gauss();
	correr(D.rtty(T, s => (a = s)), x); correr(D.psk31(T, s => (b = s)), x);
	assert.equal(a.length + b.length, 0);
});
const img = (x, y) => [x < 160 ? 230 : 30, (y * 255 / 256) | 0, ((x * 255) / 320) | 0];
for (const [vis, nombre] of [[44, 'Martin 1'], [60, 'Scottie 1'], [8, 'Robot 36']]) {
	test(`SSTV ${nombre}: VIS, imagen completa y error medio bajo con ruido a 15 dB`, () => {
		let ult = null, infos = []; const d = D.sstv(T, i => (ult = i), m => infos.push(m));
		// El reloj del emisor va 200 ppm distinto: la inclinación se corrige con los sincronismos
		const x = D.sintetizar.sstv(vis, img, T * 1.0002);
		correr(d, ruido(x, 15, 1), 4096);
		assert.ok(ult, 'sin imagen: ' + infos.join(' | '));
		assert.equal(ult.modo, nombre); assert.equal(ult.linea, ult.alto);
		let err = 0, n = 0;
		for (let y = 8; y < ult.alto - 8; y += 4) for (let x2 = 12; x2 < ult.ancho - 12; x2 += 4) {
			if (Math.abs(x2 - 160) < 8) continue; // el borde vertical no cuenta
			const o = (y * ult.ancho + x2) * 4, p = img(x2, y); for (let c = 0; c < 3; c++) { err += Math.abs(ult.img[o + c] - p[c]); n++; }
		}
		const medio = err / n; console.log(`   ${nombre}: error medio ${medio.toFixed(1)} de 255`);
		assert.ok(medio < 25, 'error medio ' + medio);
	});
}
