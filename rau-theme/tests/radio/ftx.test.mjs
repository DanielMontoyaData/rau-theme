// Decodificador FT8/FT4 (assets/radio/ftx.js) con señales sintéticas: sin navegador ni red.
//   node --test tests/radio/ftx.test.mjs
// Con FT8LIB_WAV=<carpeta test/wav de ft8_lib> compara además con lo que decodificó WSJT-X en esas grabaciones.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const F = require('../../assets/radio/ftx.js');

let semilla = 1;
const azar = () => { semilla = (semilla * 1103515245 + 12345) % 2147483648; return semilla / 2147483648; };
const gauss = () => { let u = 0; while (!u) u = azar(); return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * azar()); };
/* Ranura con ruido blanco (σ=1) y mensajes a la SNR dada en 2500 Hz, empezando a 0,5 s + dt */
function ranura(P, msgs, snr) {
	const n = Math.round(P.ranura * 12000), x = new Float32Array(n);
	for (let i = 0; i < n; i++) x[i] = gauss();
	msgs.forEach(([m, f, dt]) => {
		const s = F.sintetizar(F.tonos(F.empacar(m), P), f, P, 12000), A = Math.sqrt(2 * Math.pow(10, snr / 10) * 2500 / 6000), i0 = Math.round((0.5 + (dt || 0)) * 12000);
		for (let i = 0; i < s.length && i0 + i < n; i++) x[i0 + i] += A * s[i];
	});
	return x;
}
const MSGS = [['CQ HK4RAU FJ26', 600, 0.2], ['HK4ABC HK6XYZ -12', 1100, -0.3], ['HK6XYZ HK4ABC R-15', 1600, 0], ['CQ DX K1ABC FN42', 2100, 0.4], ['W9XYZ K1ABC RR73', 2600, -0.1]];

test('empaquetar y desempaquetar: mensajes estándar, CQ con modificador, reportes, libre y no estándar', () => {
	const H = new F.Hashes();
	['CQ HK4RAU FJ26', 'HK4ABC HK6XYZ -12', 'HK6XYZ HK4ABC R-15', 'HK4ABC HK6XYZ RR73', 'HK4ABC HK6XYZ 73', 'CQ DX K1ABC FN42', 'CQ POTA HK4RAU FJ26', 'K1ABC/R W9XYZ EN37', 'TNX BOB 73 GL', 'CQ PJ4/K1ABC']
		.forEach((m) => assert.equal(F.desempacar(F.empacar(m, H), H).texto, m, m));
	assert.equal(F.desempacar(F.empacar('HELLO WORLD 1234567')).texto, 'HELLO WORLD 1', 'texto libre: 13 caracteres');
	assert.equal(F.crc14(new Uint8Array(12), 82), 0);
});

for (const P of [F.FT8, F.FT4]) {
	test(P.nombre + ': cinco mensajes a −10 dB, con su frecuencia, DT y SNR', () => {
		semilla = 11;
		const r = F.decodificar(ranura(P, MSGS, -10), 12000, { modo: P.nombre });
		MSGS.forEach(([m, f, dt]) => {
			const d = r.find((x) => x.texto === m); assert.ok(d, 'falta ' + m + ': ' + r.map((x) => x.texto));
			assert.ok(Math.abs(d.freq - f) <= 2, m + ' frecuencia ' + d.freq);
			assert.ok(Math.abs(d.dt - dt) <= 0.05, m + ' DT ' + d.dt);
			assert.ok(Math.abs(d.snr + 10) <= 4, m + ' SNR ' + d.snr);
		});
		assert.equal(r.length, MSGS.length, 'sin decodificaciones falsas');
	});
	test(P.nombre + ': cerca del límite (' + (P === F.FT8 ? '−18' : '−14') + ' dB) todavía decodifica', () => {
		semilla = 21;
		const r = F.decodificar(ranura(P, MSGS, P === F.FT8 ? -18 : -14), 12000, { modo: P.nombre });
		assert.ok(r.filter((d) => MSGS.some(([m]) => m === d.texto)).length >= 4, r.map((d) => d.texto).join(' / '));
	});
	test(P.nombre + ': solo ruido no produce mensajes', () => {
		semilla = 31;
		assert.deepEqual(F.decodificar(ranura(P, [], 0), 12000, { modo: P.nombre }), []);
	});
}

test('señal débil escondida bajo una fuerte en la misma frecuencia se recupera restando (segunda pasada)', () => {
	semilla = 41;
	const P = F.FT8, n = 180000, x = ranura(P, [['CQ HK4RAU FJ26', 1000, 0]], 10);
	const s = F.sintetizar(F.tonos(F.empacar('HK4ABC K1ABC -05'), P), 1010, P, 12000), A = Math.sqrt(2 * Math.pow(10, -12 / 10) * 2500 / 6000);
	for (let i = 0; i < s.length; i++) x[6000 + 1800 + i] += A * s[i];
	const r = F.decodificar(x, 12000, { modo: 'FT8' });
	assert.ok(r.some((d) => d.texto === 'CQ HK4RAU FJ26'));
	const debil = r.find((d) => d.texto === 'HK4ABC K1ABC -05');
	assert.ok(debil, 'la débil sale: ' + r.map((d) => d.texto + ' (pasada ' + d.pasada + ')'));
	assert.ok(debil.pasada >= 1);
});

test('grabaciones reales de ft8_lib frente a WSJT-X (opcional)', { skip: !process.env.FT8LIB_WAV }, () => {
	const dir = process.env.FT8LIB_WAV; let ok = 0, ref = 0;
	fs.readdirSync(dir).filter((f) => f.endsWith('.txt')).forEach((t) => {
		const b = fs.readFileSync(dir + '/' + t.replace('.txt', '.wav')); let o = 12, datos = null;
		while (o < b.length) { const id = b.toString('ascii', o, o + 4), n = b.readUInt32LE(o + 4); if (id === 'data') datos = b.subarray(o + 8, o + 8 + n); o += 8 + n + (n & 1); }
		const x = new Float32Array(datos.length / 2); for (let i = 0; i < x.length; i++) x[i] = datos.readInt16LE(2 * i) / 32768;
		const textos = new Set(F.decodificar(x, 12000, { modo: 'FT8' }).map((d) => d.texto));
		fs.readFileSync(dir + '/' + t, 'utf8').trim().split('\n').forEach((l) => { const m = l.match(/~\s+(.*?)\s*$/); if (m) { ref++; if (textos.has(m[1].replace(/\s{2,}.*$/, '').trim())) ok++; } });
	});
	assert.ok(ok / ref >= 0.9, ok + ' de ' + ref);
});
