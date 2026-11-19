// Página /modem/: chat por sonido entre dos navegadores (micrófono simulado de Chromium) y uso sin conexión.
//   RAU_EM_BASE=http://localhost:8099 node --test tests/radio/modem-pagina.e2e.mjs
import { test, after } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import fs from 'node:fs'; import os from 'node:os'; import path from 'node:path';
const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const BASE = process.env.RAU_EM_BASE || 'http://localhost:8099';
const errores = []; const navs = [];
after(async () => { for (const n of navs) await n.close(); });
async function pagina(nav, ctxOpts = {}) { const c = await nav.newContext(ctxOpts); const p = await c.newPage(); p.on('pageerror', e => errores.push(e.message)); return p; }

let wav;
test('se escribe y se transmite un mensaje', async () => {
	const nav = await chromium.launch({ args: ['--no-proxy-server', '--autoplay-policy=no-user-gesture-required'] }); navs.push(nav);
	const p = await pagina(nav);
	await p.goto(BASE + '/modem/');
	await p.fill('#ind', 'hk4abc'); await p.selectOption('#modo', 'rapido');
	await p.fill('#txt', 'Puente caído en la vía a La Florida. Sin heridos.');
	assert.match(await p.textContent('#dur'), /\d+ s/);
	await p.evaluate(() => { const o = AudioBufferSourceNode.prototype.start; AudioBufferSourceNode.prototype.start = function () { window.__audio = { d: this.buffer.getChannelData(0).slice(), sr: this.buffer.sampleRate }; return o.apply(this, arguments); }; });
	await p.click('#tx');
	await p.waitForFunction(() => window.__audio);
	const { b64, sr } = await p.evaluate(() => { const a = window.__audio.d, i16 = new Int16Array(a.length); for (let i = 0; i < a.length; i++) i16[i] = Math.max(-1, Math.min(1, a[i])) * 32767; let s = ''; const u = new Uint8Array(i16.buffer); for (let i = 0; i < u.length; i += 0x8000) s += String.fromCharCode.apply(null, u.subarray(i, i + 0x8000)); return { b64: btoa(s), sr: window.__audio.sr }; });
	// Se espera a que termine: queda en la conversación como enviado
	await p.waitForFunction(() => /HK4ABC.*Puente caído/.test(document.querySelector('#log').textContent), null, { timeout: 60000 });
	const pcm = Buffer.from(b64, 'base64'), sil = Buffer.alloc(sr * 2), cuerpo = Buffer.concat([sil, pcm, sil, sil]);
	const cab = Buffer.alloc(44); cab.write('RIFF', 0); cab.writeUInt32LE(36 + cuerpo.length, 4); cab.write('WAVEfmt ', 8); cab.writeUInt32LE(16, 16); cab.writeUInt16LE(1, 20); cab.writeUInt16LE(1, 22);
	cab.writeUInt32LE(sr, 24); cab.writeUInt32LE(sr * 2, 28); cab.writeUInt16LE(2, 32); cab.writeUInt16LE(16, 34); cab.write('data', 36); cab.writeUInt32LE(cuerpo.length, 40);
	wav = path.join(fs.mkdtempSync(path.join(os.tmpdir(), 'rau-chat-')), 'chat.wav'); fs.writeFileSync(wav, Buffer.concat([cab, cuerpo]));
});
test('otro celular lo oye por el micrófono y lo muestra', async () => {
	const nav = await chromium.launch({ args: ['--no-proxy-server', '--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream', '--use-file-for-fake-audio-capture=' + wav] }); navs.push(nav);
	const q = await pagina(nav, { permissions: ['microphone'] });
	await q.goto(BASE + '/modem/');
	await q.click('#rx');
	await q.waitForFunction(() => /HK4ABC: Puente caído en la vía a La Florida\. Sin heridos\./.test(document.querySelector('#log').textContent), null, { timeout: 90000 });
	assert.match(await q.textContent('#log'), /recibido/);
});
test('sin conexión la página sigue abriendo (service worker)', async () => {
	const nav = await chromium.launch({ args: ['--no-proxy-server'] }); navs.push(nav);
	const c = await nav.newContext(); const p = await c.newPage(); p.on('pageerror', e => errores.push(e.message));
	await p.goto(BASE + '/modem/');
	await p.waitForFunction(() => navigator.serviceWorker && navigator.serviceWorker.controller || navigator.serviceWorker.ready.then(() => true), null, { timeout: 20000 });
	await p.reload(); await p.waitForFunction(() => !!navigator.serviceWorker.controller, null, { timeout: 20000 });
	await p.waitForTimeout(1500);
	await c.setOffline(true);
	await p.reload();
	assert.match(await p.textContent('h1'), /Módem RAU/);
	assert.ok(await p.evaluate(() => !!window.RauModem), 'el módem carga desde la copia guardada');
});
test('sin errores de JavaScript', () => { assert.deepEqual(errores, []); });
