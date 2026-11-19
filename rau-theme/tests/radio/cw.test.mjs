// Decodificador de CW (assets/radio/cw.js) con señales sintéticas: velocidad, ruido y mano humana.
//   node --test tests/radio/cw.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const CW = require('../../assets/radio/cw.js');
const INV = {}; Object.keys(CW.MORSE).forEach((k) => { if (CW.MORSE[k].length === 1) INV[CW.MORSE[k]] = k; });
let semilla = 7;
const azar = () => { semilla = (semilla * 1103515245 + 12345) % 2147483648; return semilla / 2147483648; };
const gauss = () => { let u = 0; while (!u) u = azar(); return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * azar()); };
/* CW con ruido blanco (σ=1); snr en 500 Hz; mano: dispersión de las duraciones */
function generar(texto, ppm, snr, mano) {
	const u = 1.2 / ppm, tr = []; let t = 1;
	texto.split('').forEach((ch) => { if (ch === ' ') { t += 4 * u; return; } const c = INV[ch]; c.split('').forEach((e, k) => { const d = (e === '.' ? 1 : 3) * u * Math.exp(gauss() * mano); tr.push([t, t + d]); t += d + (k < c.length - 1 ? u * Math.exp(gauss() * mano) : 0); }); t += 3 * u; });
	const n = Math.round((t + 1) * 12000), x = new Float32Array(n), A = Math.sqrt(2 * Math.pow(10, snr / 10) * 500 / 6000); let j = 0;
	for (let i = 0; i < n; i++) { const tt = i / 12000; while (j < tr.length && tr[j][1] < tt - 0.005) j++; let env = 0; if (j < tr.length && tt >= tr[j][0] && tt <= tr[j][1]) env = Math.min(1, (tt - tr[j][0]) / 0.005, (tr[j][1] - tt) / 0.005); x[i] = A * env * Math.sin(2 * Math.PI * 650 * tt) + gauss(); }
	return x;
}
function leer(x) { let t = ''; const d = CW.crear(12000, (c, conf) => { if (conf === undefined || conf >= 0.2) t += c; }); for (let i = 0; i < x.length; i += 512) d.procesar(x.subarray(i, i + 512)); return t.replace(/\s+/g, ' ').trim(); }
function errores(a, b) { const d = []; for (let i = 0; i <= a.length; i++) d[i] = [i]; for (let j = 0; j <= b.length; j++) d[0][j] = j; for (let i = 1; i <= a.length; i++) for (let j = 1; j <= b.length; j++) d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1)); return d[a.length][b.length] / a.length; }
const TXT = 'CQ CQ DE HK4RAU HK4RAU K';

for (const ppm of [12, 20, 30, 40]) test(ppm + ' PPM con buena señal (10 dB en 500 Hz): sin errores, desde la primera letra', () => {
	semilla = ppm; const r = leer(generar(TXT, ppm, 10, 0.06));
	assert.ok(errores(TXT, r) <= 0.05, r);
});
test('señal moderada (3 dB en 500 Hz) a 20 PPM: casi todo legible', () => { semilla = 3; const r = leer(generar(TXT + ' QTH MEDELLIN', 20, 3, 0.06)); assert.ok(errores(TXT + ' QTH MEDELLIN', r) <= 0.15, r); });
test('mano irregular (±18 %) a 20 PPM', () => { semilla = 5; const r = leer(generar(TXT, 20, 8, 0.18)); assert.ok(errores(TXT, r) <= 0.1, r); });
test('solo ruido: no inventa texto', () => { semilla = 9; const x = new Float32Array(12000 * 20); for (let i = 0; i < x.length; i++) x[i] = gauss(); assert.ok(leer(x).length <= 2, leer(x)); });
test('informa velocidad, tono y relación señal/ruido', () => {
	semilla = 13; let info = null; const d = CW.crear(12000, () => {}, (o) => { info = o; }); const x = generar(TXT, 25, 10, 0.05);
	for (let i = 0; i < x.length; i += 512) d.procesar(x.subarray(i, i + 512));
	assert.ok(Math.abs(info.ppm - 25) <= 3, 'ppm ' + info.ppm); assert.ok(Math.abs(info.tono - 650) <= 5, 'tono ' + info.tono); assert.ok(info.senal);
});
