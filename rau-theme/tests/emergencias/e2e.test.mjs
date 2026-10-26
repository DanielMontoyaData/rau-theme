// Pruebas de punta a punta del centro de mando en un navegador real (Playwright).
//   RAU_EM_BASE=http://localhost:8099 node --test tests/emergencias/e2e.test.mjs
// Necesita el sitio con el tema, la página /emergencias/ y el service worker (/service-worker.js).
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { chromium, devices } = require('playwright');

const BASE = process.env.RAU_EM_BASE || 'http://localhost:8099';
const URL = BASE + '/emergencias/';
let nav, coord, oper, errores = [], datos = {};

async function pagina(ctx, nombre) {
	const p = await ctx.newPage();
	p.on('pageerror', e => errores.push(nombre + ': ' + e.message));
	p.on('dialog', d => d.accept());
	await p.route(/^https?:\/\/(?!localhost)/, r => r.abort()); // nada sale a internet en las pruebas
	return p;
}
async function entrarComo(p, ind) {
	await p.goto(URL); await p.waitForSelector('#em-yo, #in-activar');
	if (await p.$('#em-yo')) { await p.fill('#em-yo [name=ind]', ind); await p.click('#em-yo button'); }
	try { await p.waitForSelector('#in-activar', { timeout: 8000 }); }
	catch (e) { await p.screenshot({ path: '/tmp/claude-0/-home-user-rau-theme/88878e9c-418e-5174-8b24-d13e04614ebc/scratchpad/e2e-falla.png' }); console.log('DOM:', (await p.$eval('#em-app', x => x.innerText)).slice(0, 400)); throw e; }
}
const texto = (p, sel) => p.$eval(sel, e => e.innerText);
async function modalListo(p) { await p.waitForSelector('#em-modal:not([hidden])'); }
async function enviar(p, sel) { await p.click(sel + ' button.em-btn.primario, ' + sel + ' button.em-btn.peligro, ' + sel + ' button.em-btn.ok'); }
async function sincronizado(p) { await p.waitForFunction(() => window.RauEm && RauEm.op && RauEm.pendientes().length === 0 && !RauEm.sync.enCurso, null, { timeout: 15000 }); }

before(async () => {
	nav = await chromium.launch({ args: ['--no-proxy-server'] });
	coord = await pagina(await nav.newContext({ viewport: { width: 1440, height: 950 } }), 'coordinador');
	oper = await pagina(await nav.newContext({ ...devices['Pixel 7'] }), 'operador');
});
after(async () => { await nav.close(); });

test('1. el coordinador activa una operación real y dicta código y clave', async () => {
	await entrarComo(coord, 'hk6coo');
	await coord.click('#in-activar'); await modalListo(coord);
	await coord.fill('#f-crear [name=nombre]', 'Prueba E2E lluvias Risaralda');
	await coord.fill('#f-crear [name=zona]', 'Pereira, Dosquebradas');
	datos.clave = await coord.inputValue('#f-crear [name=clave]');
	datos.claveCoord = await coord.inputValue('#f-crear [name=clave_coord]');
	await coord.click('#f-crear button');
	await coord.waitForSelector('#ok-dictar');
	datos.codigo = (await texto(coord, '.em-dictar strong')).trim();
	assert.match(datos.codigo, /^[A-Z0-9]{6}$/);
	await coord.click('#ok-dictar');
	assert.match(await texto(coord, '#em-titulo'), /Coordinador/);
});

test('2. registra un incidente con ubicación y su historial', async () => {
	await coord.keyboard.press('n'); await modalListo(coord);
	await coord.selectOption('#f-inc [name=tipo]', 'colapso_estructural');
	await coord.fill('#f-inc [name=titulo]', 'Colapso de vivienda calle 20 con carrera 8');
	await coord.check('#f-inc input[name=prio][value=critica]', { force: true });
	await coord.fill('#f-inc [name=fuente]', 'HK6ABC por 147.120');
	await coord.fill('#f-inc [name=lugar]', 'Dosquebradas');
	await coord.fill('#f-inc [name=pos]', '4.8392, -75.6690');
	await coord.selectOption('#f-inc [name=precision]', 'aproximada');
	await coord.fill('#f-inc [name=afectados]', 'Dos personas atrapadas según el reportante');
	await coord.click('#f-inc button.primario');
	await coord.waitForSelector('.em-detalle');
	const t = await texto(coord, '.em-detalle');
	assert.match(t, /Reportado/); assert.match(t, /Prioridad de despacho\s*Sin registrar/);
	assert.match(t, /sin verificar/);
	await coord.click('[data-ia=verificar]'); await modalListo(coord);
	await coord.fill('#f-ia [name=nota]', 'Confirmado por bomberos en el sitio');
	await coord.click('#f-ia button');
	await coord.waitForSelector('.em-detalle');
	assert.match(await texto(coord, '.em-detalle .em-inc-cab'), /Verificado/);
	await sincronizado(coord);
});

test('3. un mensaje solo avanza con registro y evidencia', async () => {
	await coord.keyboard.press('m'); await modalListo(coord);
	await coord.check('#f-msg input[name=prec][value=E]', { force: true });
	await coord.fill('#f-msg [name=para]', 'PMU Pereira');
	await coord.fill('#f-msg [name=texto]', 'SE REQUIERE GRUPO USAR EN CALLE 20 CON CARRERA 8');
	await coord.click('#f-msg button.primario');
	await coord.waitForSelector('#lm-texto');
	assert.match(await texto(coord, '#lm-texto'), /Número 1, emergencia, estación de origen Hotel Kilo Seis Charlie Oscar Oscar, cuenta 10/);
	for (const [estado, valor] of [['en_cola', '147.120'], ['enviado', '147.120'], ['recibido_nodo', 'hk6nod'], ['entregado', 'Entregado al jefe del PMU'], ['confirmado', 'Acuse por radio de HK6NOD 1432Z']]) {
		await coord.click(`[data-mest=${estado}]`); await coord.waitForSelector('#f-mest');
		await coord.fill('#f-mest [name=v]', valor); await coord.click('#f-mest button');
		await coord.waitForSelector('#lm-texto');
	}
	const h = await texto(coord, '#em-modal-c');
	assert.match(h, /Creado[\s\S]*En cola[\s\S]*Enviado[\s\S]*Recibido por nodo[\s\S]*HK6NOD[\s\S]*Entregado al destinatario[\s\S]*Confirmado/);
	await coord.click('#em-modal-x');
	await sincronizado(coord);
});

test('4. el operador se une por el celular, con su rol y sin botones de coordinación', async () => {
	await entrarComo(oper, 'hk6opr');
	await oper.click('#in-unirme'); await modalListo(oper);
	await oper.fill('#f-unir [name=codigo]', datos.codigo);
	await oper.fill('#f-unir [name=clave]', 'clave-equivocada');
	await oper.click('#f-unir button');
	await oper.waitForFunction(() => /no coincide/.test(document.querySelector('#f-unir-msg').textContent));
	await oper.fill('#f-unir [name=clave]', datos.clave);
	await oper.click('#f-unir button');
	await oper.waitForSelector('[data-vivo=cifras]');
	assert.match(await texto(oper, '#em-titulo'), /Operador/);
	await sincronizado(oper);
	assert.match(await texto(oper, '[data-vivo=cifras]'), /1\s*Incidentes activos/);
	await oper.click('[data-v=incidentes]'); await oper.click('.em-inc');
	await oper.waitForSelector('.em-detalle');
	assert.equal(await oper.$('[data-ia=verificar]'), null, 'el operador no ve «Verificar»');
	assert.equal(await oper.$('[data-ia=desp]'), null, 'ni registra la prioridad de despacho');
	assert.ok(await oper.$('[data-ia=situacion]'), 'pero sí actualiza la situación');
	const w = await oper.evaluate(() => document.documentElement.scrollWidth);
	assert.ok(w <= 412, 'sin desplazamiento horizontal en el celular: ' + w);
});

test('5. el operador reporta lo mismo, el sistema sugiere el duplicado y la coordinación lo fusiona', async () => {
	await oper.click('[data-ir=incidentes]');
	await oper.click('#in-nuevo'); await modalListo(oper);
	await oper.selectOption('#f-inc [name=tipo]', 'colapso_estructural');
	await oper.fill('#f-inc [name=titulo]', 'Derrumbe de vivienda carrera 8 Dosquebradas');
	await oper.fill('#f-inc [name=fuente]', 'Vecino por 146.520');
	await oper.fill('#f-inc [name=pos]', '4.8394, -75.6688');
	await oper.click('#f-inc button.primario');
	await oper.waitForSelector('.em-detalle');
	await sincronizado(oper);
	await coord.click('[data-v=incidentes]');
	await coord.evaluate(() => RauEm.sincronizar());
	await coord.waitForSelector('[data-fusion]', { timeout: 10000 });
	assert.match(await texto(coord, '[data-vivo=duplicados]'), /Posibles duplicados \(1\)/);
	await coord.click('[data-fusion]'); await modalListo(coord);
	await coord.fill('#f-fus [name=nota]', 'Mismo colapso');
	await coord.click('#f-fus button');
	await coord.waitForFunction(() => !document.querySelector('[data-fusion]'));
	await sincronizado(coord);
});

test('6. sin internet: registra, recarga la página, conserva y envía al volver', async () => {
	await oper.context().setOffline(true);
	await oper.click('[data-ir=incidentes]').catch(() => {});
	await oper.evaluate(() => RauEm.ir('bitacora'));
	await oper.click('#bi-nota'); await modalListo(oper);
	await oper.fill('#f-nota [name=texto]', 'Nota escrita sin internet');
	await oper.click('#f-nota button.primario');
	await oper.waitForFunction(() => /sin enviar/i.test(document.querySelector('#em-sync').textContent));
	await oper.reload(); // la página sale del service worker y los datos de IndexedDB
	await oper.waitForSelector('[data-vivo=cifras], [data-vivo=bitacora]');
	await oper.evaluate(() => RauEm.ir('bitacora'));
	assert.match(await texto(oper, '[data-vivo=bitacora]'), /Nota escrita sin internet[\s\S]*sin enviar/i, 'sigue ahí y marcada como no enviada');
	await oper.context().setOffline(false);
	await oper.evaluate(() => window.dispatchEvent(new Event('online')));
	await sincronizado(oper);
	await coord.evaluate(() => RauEm.sincronizar());
	await coord.evaluate(() => RauEm.ir('bitacora'));
	await coord.waitForFunction(() => /Nota escrita sin internet/.test(document.querySelector('[data-vivo=bitacora]').innerText), null, { timeout: 10000 });
});

test('7. recursos: solicitud, aprobación y asignación con última ubicación', async () => {
	await coord.evaluate(() => RauEm.ir('recursos'));
	await coord.click('#re-nuevo'); await modalListo(coord);
	await coord.fill('#f-rec [name=desc]', 'Portátiles VHF con doble batería'); await coord.fill('#f-rec [name=cant]', '15');
	await coord.click('#f-rec button.primario');
	await coord.click('#re-sol'); await modalListo(coord);
	await coord.fill('#f-sol [name=desc]', 'Radios para la Gobernación'); await coord.fill('#f-sol [name=cant]', '15');
	await coord.click('#f-sol button.primario');
	await coord.click('[data-t=solicitudes]');
	await coord.click('[data-se=aprobada]'); await modalListo(coord); await coord.click('#f-se button');
	await coord.click('[data-t=inventario]');
	await coord.selectOption('.em-rec-sel', 'desplegado'); await modalListo(coord);
	await coord.fill('#f-re [name=lugar]', 'Gobernación de Risaralda');
	await coord.click('#f-re button');
	const t = await texto(coord, '[data-vivo=recursos]');
	assert.match(t, /Desplegado/); assert.match(t, /Última ubicación conocida: Gobernación de Risaralda/); assert.match(t, /no es seguimiento en tiempo real/);
	await sincronizado(coord);
});

test('8. estaciones: registrada no es disponible; verificada sí', async () => {
	await coord.evaluate(() => RauEm.ir('estaciones'));
	await coord.click('#es-nueva'); await modalListo(coord);
	await coord.fill('#f-est [name=ind]', 'hk6abc'); await coord.fill('#f-est [name=lugar]', 'Dosquebradas');
	await coord.click('#f-est button.primario');
	assert.match(await texto(coord, '[data-est=HK6ABC]'), /No verificada/);
	await coord.click('[data-est=HK6ABC] [data-ea=verificar]'); await modalListo(coord);
	await coord.click('#f-ev button');
	assert.match(await texto(coord, '[data-est=HK6ABC]'), /Disponible \(verificada\)[\s\S]*Confirmado/);
	await sincronizado(coord);
});

test('9. plan de comunicaciones: propuesta que se aprueba, imprimible', async () => {
	await coord.evaluate(() => RauEm.ir('plan'));
	await coord.click('#pl-nuevo'); await modalListo(coord);
	await coord.fill('#f-plan [name=objetivo]', 'Enlazar PMU de Pereira y Dosquebradas');
	await coord.fill('#f-plan [name=resp]', 'HK6COO');
	await coord.fill('#f-plan [name=canal_p]', '147.120 FM');
	await coord.click('#f-plan button');
	assert.match(await texto(coord, '[data-vivo=plan]'), /Borrador[\s\S]*no es una instrucción oficial/);
	await coord.click('#pl-aprobar'); await modalListo(coord); await coord.fill('#f-pe [name=v]', 'HK6COO, coordinador'); await coord.click('#f-pe button');
	assert.match(await texto(coord, '[data-vivo=plan]'), /Aprobado/);
	await sincronizado(coord);
});

test('10. SITREP generado de los registros, con corte, fuentes y limitaciones', async () => {
	await coord.evaluate(() => RauEm.ir('informes'));
	const t = await texto(coord, '.em-informe');
	assert.match(t, /Fecha de corte:/); assert.match(t, /Elaboró: HK6COO/);
	assert.match(t, /Colapso de vivienda calle 20/); assert.match(t, /HK6ABC por 147\.120/);
	assert.match(t, /9\. LIMITACIONES/);
	assert.doesNotMatch(t, /Derrumbe de vivienda carrera 8.*\n.*Verificado/, 'el fusionado no cuenta como activo');
	const [descarga] = await Promise.all([coord.waitForEvent('download'), coord.click('#if-txt')]);
	assert.match(descarga.suggestedFilename(), /^sitrep-.*\.txt$/);
	await coord.click('[data-t=exportar]');
	const [geo] = await Promise.all([coord.waitForEvent('download'), coord.click('#ex-geo')]);
	const g = JSON.parse(await (await import('node:fs/promises')).readFile(await geo.path(), 'utf8'));
	assert.equal(g.type, 'FeatureCollection'); assert.ok(g.features.every(f => f.properties.fuente || f.properties.capa === 'estacion' || f.properties.capa === 'recurso'));
});

test('11. el mapa carga Leaflet del propio sitio y dibuja con procedencia', async () => {
	await coord.evaluate(() => RauEm.ir('mapa'));
	await coord.waitForSelector('#mp-mapa .leaflet-container, #mp-mapa.leaflet-container', { timeout: 10000 });
	await coord.waitForTimeout(800);
	const n = await coord.$$eval('#mp-mapa path.leaflet-interactive, #mp-mapa .em-mk', l => l.length);
	assert.ok(n >= 2, 'dibuja los elementos con ubicación: ' + n);
	assert.match(await texto(coord, '.em-leyenda'), /no simula cobertura/);
	// Un formulario abierto sobre el mapa queda encima de Leaflet (sus capas usan z-index 400 a 1000).
	await coord.keyboard.press('n'); await modalListo(coord);
	// Las capas de Leaflet no reciben el puntero y elementFromPoint las salta: se activan para medir qué queda encima.
	const tapados = await coord.evaluate(() => {
		document.querySelectorAll('#mp-mapa, #mp-mapa *').forEach(x => { x.style.pointerEvents = 'auto'; });
		const c = document.querySelector('#em-modal .em-modal-caja').getBoundingClientRect(), m = document.querySelector('#mp-mapa').getBoundingClientRect(), fuera = [];
		for (let y = Math.max(c.top, m.top) + 5; y < Math.min(c.bottom, m.bottom); y += 25) for (let x = Math.max(c.left, m.left) + 5; x < Math.min(c.right, m.right); x += 60) { const e = document.elementFromPoint(x, y); if (e && !e.closest('#em-modal')) fuera.push(Math.round(x) + ',' + Math.round(y)); }
		document.querySelectorAll('#mp-mapa, #mp-mapa *').forEach(x => { x.style.pointerEvents = ''; });
		return fuera;
	});
	assert.deepEqual(tapados.slice(0, 5), [], 'el mapa tapa el modal en esos puntos');
	await coord.keyboard.press('Escape'); await coord.waitForSelector('#em-modal', { state: 'hidden' });
});

test('12. observador por invitación: lee, no registra, no ve datos de personas', async () => {
	await coord.evaluate(() => RauEm.ir('personas'));
	await coord.click('#pe-nueva'); await modalListo(coord);
	await coord.fill('#f-per [name=nombre]', 'María Pérez Gómez'); await coord.fill('#f-per [name=contacto]', '3001234567');
	await coord.click('#f-per button.primario'); await sincronizado(coord);
	await coord.evaluate(() => RauEm.ir('admin'));
	await coord.waitForSelector('#ad-inv');
	await coord.selectOption('#ad-inv [name=rol]', 'observador'); await coord.click('#ad-inv button');
	await coord.waitForFunction(() => /se muestra una sola vez/.test(document.querySelector('#ad-inv-r').textContent));
	const claveObs = (await texto(coord, '#ad-inv-r b')).trim();
	const obs = await pagina(await nav.newContext({ viewport: { width: 1280, height: 900 } }), 'observador');
	await entrarComo(obs, 'hk6obs');
	await obs.click('#in-unirme'); await modalListo(obs);
	await obs.fill('#f-unir [name=codigo]', datos.codigo); await obs.fill('#f-unir [name=clave]', claveObs); await obs.click('#f-unir button');
	await obs.waitForSelector('[data-vivo=cifras]'); await sincronizado(obs);
	assert.equal(await obs.$('#tb-inc'), null, 'sin botones para registrar');
	await obs.evaluate(() => RauEm.ir('personas'));
	const t = await texto(obs, '[data-vivo=personas]');
	assert.doesNotMatch(t, /María|3001234567/); assert.match(t, /Persona registrada/);
	// Aunque fuerce un registro desde la consola, la validación local y el servidor lo rechazan
	const r = await obs.evaluate(() => RauEm.registrar('nota', { texto: 'intento' }));
	assert.equal(r, null);
	await obs.context().close();
});

test('13. revocar una sesión corta el acceso de inmediato', async () => {
	await coord.evaluate(() => RauEm.ir('admin'));
	await coord.waitForSelector('[data-revocar][data-ind=HK6OPR]');
	await coord.click('[data-revocar][data-ind=HK6OPR]'); await modalListo(coord); await coord.click('#cf-si');
	await oper.evaluate(() => RauEm.sincronizar());
	await oper.waitForFunction(() => /Sin acceso/.test(document.querySelector('#em-sync').textContent), null, { timeout: 10000 });
});

test('14. simulador: aislado, marcado y con métricas', async () => {
	const p = await pagina(await nav.newContext({ viewport: { width: 1280, height: 900 } }), 'simulacro');
	await entrarComo(p, 'hk6sim');
	await p.click('#in-sim'); await modalListo(p);
	await p.check('input[name=esc][value=deslizamiento]');
	await p.selectOption('#ab-vel', '10');
	await p.click('#ab-loc'); await p.waitForSelector('#f-local'); await p.click('#f-local button');
	await p.waitForSelector('.em-sim-msg', { timeout: 10000 });
	assert.match(await texto(p, '.em-sim-msg'), /^\[SIMULACIÓN\]/);
	assert.match(await texto(p, '#em-franja'), /SIMULACRO/);
	await p.click('[data-sa=inc]'); await p.waitForSelector('#f-inc');
	assert.equal(await p.inputValue('#f-inc [name=titulo]'), 'Deslizamiento vía Santa Rosa–Chinchiná km 4');
	await p.click('#f-inc button.primario');
	await p.evaluate(() => RauEm.ir('simulador'));
	await p.waitForFunction(() => /Registrar el incidente[\s\S]*\d+ s/.test(document.querySelector('[data-vivo=sim]').innerText));
	assert.match(await texto(p, '[data-vivo=sim]'), /Escenario Deslizamiento/);
	await p.context().close();
});

test('15. sin errores de JavaScript en ninguna pantalla', () => {
	assert.deepEqual(errores, []);
});
