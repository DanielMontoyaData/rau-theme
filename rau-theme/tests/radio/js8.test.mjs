// Decodificador JS8 (assets/radio/js8.js).
//   node --test tests/radio/js8.test.mjs
//   JS8CALL_WAV=/ruta/js8call/media/tests node --test tests/radio/js8.test.mjs   (grabaciones de prueba de JS8Call)
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import fs from 'node:fs';
import zlib from 'node:zlib';
import path from 'node:path';
const require = createRequire(import.meta.url);
const raiz = path.resolve(path.dirname(new URL(import.meta.url).pathname), '../..');
const J = require(raiz + '/assets/radio/js8.js');
const DICC = JSON.parse(zlib.gunzipSync(fs.readFileSync(raiz + '/assets/radio/js8-jsc.json.gz')).toString('utf8'));

// Empaquetado mínimo (el de varicode.cpp) para armar marcos de prueba
const AN = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ /@', NBASE = 37 * 36 * 10 * 27 * 27 * 27;
const bitsDe = (v, n) => { const b = []; for (let i = n - 1; i >= 0; i--) b.push(Math.floor(v / 2 ** i) % 2); return b; };
function indic(c) {
	if (c === '@EMCOMM') return NBASE + 36; if (c === '@ALLCALL') return NBASE + 2;
	let w = c.length === 5 ? ' ' + c : c; w = w.padEnd(6, ' ');
	return 36 * 10 * 27 * 27 * 27 * AN.indexOf(w[0]) + 10 * 27 * 27 * 27 * AN.indexOf(w[1]) + 27 * 27 * 27 * AN.indexOf(w[2]) + 27 * 27 * (AN.indexOf(w[3]) - 10) + 27 * (AN.indexOf(w[4]) - 10) + (AN.indexOf(w[5]) - 10);
}
const dirigido = (de, para, cmd, extra) => [...bitsDe(3, 3), ...bitsDe(indic(de), 28), ...bitsDe(indic(para), 28), ...bitsDe(cmd, 5), 0, 0, ...bitsDe(extra, 6)];
function latido(ind, grid) {
	let w = ind; if (w.length > 3 && w[3] !== '/') w = w.slice(0, 3) + ' ' + w.slice(3); w = w.padEnd(11, ' ');
	let v = 0; const pesos = [39, 38, 38, 2, 38, 38, 38, 2, 38, 38, 38];
	for (let i = 0; i < 11; i++) v = v * pesos[i] + (pesos[i] === 2 ? (w[i] === '/' ? 1 : 0) : AN.indexOf(w[i]));
	// packGrid de varicode.cpp (longitud positiva al oeste, centro del cuadro)
	const lng = 180 - 20 * (grid.charCodeAt(0) - 65) - 2 * (+grid[2]) - 62.5 / 60, lat = -90 + 10 * (grid.charCodeAt(1) - 65) + (+grid[3]) + 31.25 / 60;
	const g = Math.floor((Math.trunc(lng) + 180) / 2) * 180 + Math.trunc(lat + 90);
	return [...bitsDe(0, 3), ...bitsDe(v, 50), ...bitsDe(g >> 5, 11), ...bitsDe(g & 31, 5), ...bitsDe(0, 3)];
}
let semilla = 3; const azar = () => (semilla = (semilla * 1103515245 + 12345) % 2147483648) / 2147483648;
const gauss = () => { let u = 0; while (!u) u = azar(); return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * azar()); };
function conRuido(x, snr) { const s = Math.sqrt(0.5 / 10 ** (snr / 10) * 6000 / 2500), y = new Float32Array(x.length); for (let i = 0; i < x.length; i++) y[i] = x[i] + s * gauss(); return y; }

test('mensaje dirigido a un grupo, con SNR: ida y vuelta por audio', () => {
	const b = dirigido('HK4ABC', '@EMCOMM', 25, -12 + 31), y = J.sintetizar(J.codificar(b, 3), 1200, 12000);
	const d = J.decodificar(y, 12000);
	assert.equal(d.length, 1);
	assert.equal(d[0].texto, 'HK4ABC: @EMCOMM SNR -12 ');
	assert.equal(d[0].grupo, '@EMCOMM'); assert.equal(d[0].de, 'HK4ABC'); assert.ok(Math.abs(d[0].freq - 1200) <= 1);
	assert.ok(d[0].primero && d[0].ultimo);
});
test('latido con cuadrícula y CQ', () => {
	assert.equal(J.interpretar(latido('HK4RAU', 'FJ26'), 0).texto, 'HK4RAU: @HB HEARTBEAT FJ26 ');
	const cq = latido('HK4RAU', 'FJ26'); cq[53] = 1; // bit alto de num: CQ
	const r = J.interpretar(cq, 0); assert.equal(r.tipo, 'cq'); assert.match(r.texto, /^HK4RAU: @ALLCALL CQ CQ CQ FJ26 $/);
});
test('texto con Huffman y con compresión JSC', () => {
	const H = { ' ': '01', E: '100', T: '1101', A: '0011', O: '11111', S: '10100', R: '00000', N: '10111', D: '111011' };
	const pal = 'SE ESTA ONDA'.split('').map(c => H[c] || '').join('');
	const bits = [1, 0, ...pal.split('').map(Number)]; bits.push(0); while (bits.length < 72) bits.push(1);
	assert.equal(J.interpretar(bits, 0).texto, 'SE ESTA ONDA'.replace('ONDA', 'ONDA'));
	// JSC: cada palabra es un índice del diccionario en código (s,c)-denso con s=7, c=9
	J.usarDiccionario(DICC);
	const cod = (idx, sep) => { const out = []; out.unshift(...bitsDe(((idx % 7) << 1) + (sep ? 1 : 0), 5)); let x = Math.floor(idx / 7); while (x > 0) { x -= 1; out.unshift(...bitsDe((x % 9) + 7, 4)); x = Math.floor(x / 9); } return out; };
	const a = DICC.indexOf('HELLO'), b2 = DICC.indexOf('WORLD');
	assert.ok(a > 0 && b2 > 0);
	const jb = [1, 1, ...cod(a, true), ...cod(b2, false)]; jb.push(0); while (jb.length < 72) jb.push(1);
	const r = J.interpretar(jb, 0); assert.ok(r.comprimido); assert.equal(r.texto, 'HELLO WORLD');
});
test('con ruido a −18 dB y otra señal al lado', () => {
	const a = J.sintetizar(J.codificar(dirigido('HK4ABC', 'HK6XYZ', 21, 0), 3), 900, 12000);
	const b = J.sintetizar(J.codificar(latido('HK3QQ', 'FJ24'), 0), 1500, 12000, 0.9);
	const x = new Float32Array(a.length); for (let i = 0; i < x.length; i++) x[i] = a[i] + b[i];
	const d = J.decodificar(conRuido(x, -18), 12000).map(r => r.texto);
	assert.ok(d.includes('HK4ABC: HK6XYZ RR '), JSON.stringify(d));
	assert.ok(d.includes('HK3QQ: @HB HEARTBEAT FJ24 '), JSON.stringify(d));
});
test('ruido solo: nada', () => {
	semilla = 77; const x = new Float32Array(180000); for (let i = 0; i < x.length; i++) x[i] = gauss() * 0.3;
	assert.equal(J.decodificar(x, 12000).length, 0);
});
test('ensamblador: junta los marcos de un mensaje largo por frecuencia', () => {
	const E = new J.Ensamblador();
	assert.equal(E.agregar({ freq: 1500, texto: 'HK4ABC: HK6XYZ  ', primero: true, ultimo: false }, 1), null);
	assert.equal(E.agregar({ freq: 1800, texto: 'OTRO ', primero: true, ultimo: true }, 1).texto, 'OTRO');
	const m = E.agregar({ freq: 1503, texto: 'PUENTE CAIDO VIA LA FLORIDA', primero: false, ultimo: true }, 2);
	assert.equal(m.texto, 'HK4ABC: HK6XYZ  PUENTE CAIDO VIA LA FLORIDA'); assert.equal(m.partes, 2);
});
const DIR = process.env.JS8CALL_WAV;
test('grabaciones de JS8Call (modo Normal): al menos 90 % de lo que JS8Call decodifica', { skip: !DIR && 'sin JS8CALL_WAV' }, () => {
	let esperado = 0, obtenido = 0; J.usarDiccionario(DICC);
	for (const f of fs.readdirSync(DIR).filter(f => /^A_\d_\d+\.wav$/.test(f))) {
		const b = fs.readFileSync(path.join(DIR, f)), n = (b.length - 44) / 2, x = new Float32Array(n);
		for (let i = 0; i < n; i++) x[i] = b.readInt16LE(44 + 2 * i) / 32768;
		const d = J.decodificar(x, 12000); esperado += +f.split('_')[2].split('.')[0]; obtenido += Math.min(d.length, +f.split('_')[2].split('.')[0]);
		if (f === 'A_1_4.wav') assert.ok(d.some(r => r.texto === 'KN4ZXG: @HB HEARTBEAT FM16 '));
	}
	console.log(`   ${obtenido} de ${esperado}`);
	assert.ok(obtenido / esperado >= 0.9);
});
