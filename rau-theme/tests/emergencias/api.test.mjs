// Pruebas de la API REST del centro de mando contra un WordPress con el tema activo.
//   RAU_EM_BASE=http://localhost:8099 node --test tests/emergencias/
import { test, before } from 'node:test';
import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';

const BASE = (process.env.RAU_EM_BASE || 'http://localhost:8099') + '/wp-json/rau-em/v1';
async function api(ruta, { metodo = 'GET', cuerpo, token } = {}) {
	const r = await fetch(BASE + ruta, {
		method: metodo,
		headers: { 'Content-Type': 'application/json', ...(token ? { 'X-RAU-EM-Token': token } : {}) },
		body: cuerpo ? JSON.stringify(cuerpo) : undefined,
	});
	let j = null; try { j = await r.json(); } catch (e) {}
	return { estado: r.status, j, cache: r.headers.get('cache-control') };
}
const ev = (t, d) => ({ u: randomUUID(), t, ts: Date.now(), d });
function nuevaOp(tipo = 'real', extra = {}) {
	return api('/operaciones', { metodo: 'POST', cuerpo: { nombre: 'Prueba ' + tipo + ' ' + Date.now(), zona: 'Pereira', tipo, indicativo: 'HK6TST', clave: 'OPER-' + tipo + '1', clave_coord: 'COORD-' + tipo + '99', ...extra } });
}
async function sesion(cod, clave, ind = 'HK6OPR') {
	const r = await api(`/operaciones/${cod}/sesion`, { metodo: 'POST', cuerpo: { clave, indicativo: ind } });
	return r;
}
const incidenteValido = () => ev('i_nuevo', { tipo: 'deslizamiento', titulo: 'Deslizamiento en la vía', precision: 'aproximada', lat: 4.81, lng: -75.69, fuente: 'HK6OPR por 147.120', fuente_tipo: 'operador_radio', prio: 'alta', desc: 'Tierra sobre la calzada' });

let real, sim, coordReal, operReal, coordSim;
before(async () => {
	real = await nuevaOp('real'); sim = await nuevaOp('simulacro');
	assert.equal(real.estado, 200, JSON.stringify(real.j));
	coordReal = real.j.token; coordSim = sim.j.token;
	operReal = (await sesion(real.j.operacion.codigo, 'OPER-real1')).j.token;
});

test('crear una operación valida las claves', async () => {
	assert.equal((await nuevaOp('real', { clave: 'corta' })).estado, 400);
	assert.equal((await nuevaOp('real', { clave: 'MISMA-CLAVE-1', clave_coord: 'MISMA-CLAVE-1' })).estado, 400);
	assert.equal((await nuevaOp('real', { nombre: '' })).estado, 400);
});

test('la lista pública no muestra operaciones sin organización ni claves', async () => {
	const r = await api('/operaciones');
	assert.equal(r.estado, 200);
	assert.ok(!r.j.some(o => o.codigo === real.j.operacion.codigo), 'una operación sin organización no se lista');
	const txt = JSON.stringify(r.j);
	assert.ok(!/clave|hash|token|lat|lng/.test(txt), 'sin claves ni coordenadas');
	assert.match(r.cache || '', /no-store/);
});

test('sin sesión no hay acceso a los registros', async () => {
	const r = await api(`/operaciones/${real.j.operacion.codigo}/eventos`);
	assert.equal(r.estado, 401);
	assert.equal((await api(`/operaciones/${real.j.operacion.codigo}/eventos`, { token: 'f'.repeat(64) })).estado, 401);
});

test('la clave equivocada no abre sesión; cada clave da su rol', async () => {
	const cod = real.j.operacion.codigo;
	assert.equal((await sesion(cod, 'no-es-la-clave')).estado, 403);
	assert.equal((await sesion(cod, 'OPER-real1')).j.actor.rol, 'operador');
	assert.equal((await sesion(cod, 'COORD-real99')).j.actor.rol, 'coordinador');
});

test('un operador registra un incidente válido y el servidor limpia el HTML', async () => {
	const cod = real.j.operacion.codigo;
	const e = incidenteValido(); e.d.desc = 'Vía cerrada <script>alert(1)</script><b>ya</b>';
	const r = await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: operReal, cuerpo: { eventos: [e] } });
	assert.equal(r.estado, 200);
	assert.equal(r.j.resultados[0].ok, true, JSON.stringify(r.j));
	const l = await api(`/operaciones/${cod}/eventos`, { token: operReal });
	const guardado = l.j.eventos.find(x => x.u === e.u);
	assert.ok(guardado && !/<script|<b>/.test(guardado.d.desc), 'sin etiquetas');
	assert.equal(guardado.a, 'HK6OPR', 'el autor lo pone el servidor, no el navegador');
	// Repetido: no se duplica
	const r2 = await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: operReal, cuerpo: { eventos: [e] } });
	assert.equal(r2.j.resultados[0].repetido, true);
});

test('se rechazan datos inválidos con su motivo', async () => {
	const cod = real.j.operacion.codigo;
	const malos = [
		ev('i_nuevo', { ...incidenteValido().d, tipo: 'meteorito' }),
		ev('i_nuevo', { tipo: 'sismo', precision: 'exacta', fuente: 'x', fuente_tipo: 'otro', prio: 'alta' }),
		ev('i_nuevo', { ...incidenteValido().d, campo_raro: 1 }),
		ev('i_nuevo', { ...incidenteValido().d, lat: 200 }),
		ev('i_nuevo', { ...incidenteValido().d, lng: undefined }),
		ev('tipo_que_no_existe', {}),
		{ u: 'NO VALIDO', t: 'nota', d: { texto: 'x' } },
	];
	const r = await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: operReal, cuerpo: { eventos: malos } });
	assert.equal(r.estado, 200);
	r.j.resultados.forEach((x, i) => { assert.equal(x.ok, false, 'el ' + i + ' debía rechazarse'); assert.ok(x.error); });
});

test('los permisos se aplican por rol en el servidor', async () => {
	const cod = real.j.operacion.codigo;
	const inc = incidenteValido();
	await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: operReal, cuerpo: { eventos: [inc] } });
	const verif = ev('i_verif', { id: inc.u, v: 'verificado' });
	const r = await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: operReal, cuerpo: { eventos: [verif, ev('i_verif', { id: inc.u, v: 'en_verificacion' })] } });
	assert.equal(r.j.resultados[0].ok, false, 'un operador no verifica');
	assert.equal(r.j.resultados[1].ok, true, 'pero sí lo pone en verificación');
	const r2 = await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: coordReal, cuerpo: { eventos: [verif] } });
	assert.equal(r2.j.resultados[0].ok, true, 'el coordinador sí');
	assert.equal((await api(`/operaciones/${cod}/sesiones`, { token: operReal })).estado, 403);
	assert.equal((await api(`/operaciones/${cod}/auditoria`, { token: operReal })).estado, 403);
	assert.equal((await api(`/operaciones/${cod}/respaldo`, { token: operReal })).estado, 403);
	const aud = await api(`/operaciones/${cod}/auditoria`, { token: coordReal });
	assert.equal(aud.estado, 200);
	assert.ok(aud.j.some(x => x.accion === 'registro_i_verif'), 'la verificación queda auditada');
});

test('el observador lee sin datos de personas y no puede registrar', async () => {
	const cod = real.j.operacion.codigo;
	await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: operReal, cuerpo: { eventos: [ev('persona', { nombre: 'María Pérez', estado: 'sinnoticias', contacto: '300 000 0000', ubic: 'Barrio Molinos' })] } });
	const inv = await api(`/operaciones/${cod}/invitaciones`, { metodo: 'POST', token: coordReal, cuerpo: { rol: 'observador', horas: 2, usos: 1 } });
	assert.equal(inv.estado, 200);
	const obs = await sesion(cod, inv.j.clave, 'HK6OBS');
	assert.equal(obs.j.actor.rol, 'observador');
	assert.equal((await sesion(cod, inv.j.clave, 'HK6OB2')).estado, 403, 'la invitación de un uso no sirve dos veces');
	const l = await api(`/operaciones/${cod}/eventos`, { token: obs.j.token });
	const txt = JSON.stringify(l.j.eventos.filter(x => x.t === 'persona'));
	assert.ok(!/María|300 000|Molinos/.test(txt), 'sin datos personales para el observador');
	const p = await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: obs.j.token, cuerpo: { eventos: [ev('nota', { texto: 'hola' })] } });
	assert.equal(p.estado, 403);
	assert.equal((await api(`/operaciones/${cod}/invitaciones`, { metodo: 'POST', token: coordReal, cuerpo: { rol: 'coordinador' } })).estado, 400, 'la coordinación no se da por invitación');
});

test('simulación y operación real no se mezclan', async () => {
	const r = await api(`/operaciones/${real.j.operacion.codigo}/eventos`, { metodo: 'POST', token: coordReal, cuerpo: { eventos: [
		ev('sim', { escenario: 'sismo', paso: 1, texto: 'Inyección' }), ev('nota', { texto: 'x', sim: 'iny-1' }) ] } });
	assert.equal(r.j.resultados[0].ok, false);
	assert.equal(r.j.resultados[1].ok, false);
	const s = await api(`/operaciones/${sim.j.operacion.codigo}/eventos`, { metodo: 'POST', token: coordSim, cuerpo: { eventos: [ev('sim', { escenario: 'sismo', paso: 1, texto: 'Inyección' })] } });
	assert.equal(s.j.resultados[0].ok, true);
	// Un token de una operación no sirve en otra
	assert.equal((await api(`/operaciones/${real.j.operacion.codigo}/eventos`, { token: coordSim })).estado, 401);
});

test('una operación cerrada no admite registros operativos hasta reabrirla', async () => {
	const o = await nuevaOp('real'); const cod = o.j.operacion.codigo, tk = o.j.token;
	assert.equal((await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: tk, cuerpo: { eventos: [ev('op', { estado: 'cerrado', nota: 'fin' })] } })).j.resultados[0].ok, true);
	assert.equal((await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: tk, cuerpo: { eventos: [ev('nota', { texto: 'tarde' })] } })).j.resultados[0].ok, false);
	assert.equal((await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: tk, cuerpo: { eventos: [ev('leccion', { cat: 'mejorar', texto: 'Probar el generador' })] } })).j.resultados[0].ok, true);
	assert.equal((await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: tk, cuerpo: { eventos: [ev('op', { estado: 'abierto' })] } })).j.resultados[0].ok, true);
	assert.equal((await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: tk, cuerpo: { eventos: [ev('nota', { texto: 'ya' })] } })).j.resultados[0].ok, true);
});

test('revocar: una sesión, o todas al cambiar las claves', async () => {
	const o = await nuevaOp('real'); const cod = o.j.operacion.codigo, coord = o.j.token;
	const a = await sesion(cod, 'OPER-real1', 'HK6AAA'), b = await sesion(cod, 'OPER-real1', 'HK6BBB');
	const ses = await api(`/operaciones/${cod}/sesiones`, { token: coord });
	const idA = ses.j.find(s => s.indicativo === 'HK6AAA').id;
	assert.ok(!JSON.stringify(ses.j).includes(a.j.token), 'la lista de sesiones no expone tokens');
	await api(`/operaciones/${cod}/sesiones/${idA}/revocar`, { metodo: 'POST', token: coord });
	assert.equal((await api(`/operaciones/${cod}/eventos`, { token: a.j.token })).estado, 401);
	assert.equal((await api(`/operaciones/${cod}/eventos`, { token: b.j.token })).estado, 200);
	await api(`/operaciones/${cod}/claves`, { metodo: 'POST', token: coord, cuerpo: { clave: 'NUEVA-OPER-1', revocar_sesiones: true } });
	assert.equal((await api(`/operaciones/${cod}/eventos`, { token: b.j.token })).estado, 401);
	assert.equal((await api(`/operaciones/${cod}/eventos`, { token: coord })).estado, 200, 'quien cambia las claves conserva su sesión');
	assert.equal((await sesion(cod, 'OPER-real1')).estado, 403, 'la clave vieja ya no sirve');
	assert.equal((await sesion(cod, 'NUEVA-OPER-1')).estado, 200);
});

test('directorio: público para leer, con fuentes y categorías; cambiarlo exige cuenta', async () => {
	const r = await api('/directorio');
	assert.equal(r.estado, 200);
	assert.ok(r.j.entradas.length > 5);
	r.j.entradas.forEach(x => { assert.ok(x.fuente, 'cada entrada tiene fuente: ' + x.f); assert.ok(r.j.categorias[x.cat], 'categoría conocida'); });
	const llamada = r.j.entradas.find(x => x.f === '146.520');
	assert.equal(llamada.cat, 'llamada_ra', '146.520 es de llamada, no de emergencia');
	assert.ok(!r.j.entradas.some(x => x.cat === 'institucional'), 'no se inventan frecuencias institucionales');
	assert.equal((await api('/directorio', { metodo: 'POST', cuerpo: { cat: 'institucional', f: '150.000', fuente: 'inventada' } })).estado, 403);
	assert.equal((await api('/directorio', { metodo: 'POST', token: coordReal, cuerpo: { cat: 'actividad_ra', f: '7.100', fuente: 'x' } })).estado, 403, 'una sesión de operación no basta');
});

test('un bloque grande se rechaza entero; la hora absurda del dispositivo se corrige', async () => {
	const cod = real.j.operacion.codigo;
	const muchos = Array.from({ length: 101 }, () => ev('nota', { texto: 'x' }));
	assert.equal((await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: operReal, cuerpo: { eventos: muchos } })).estado, 400);
	const e = ev('nota', { texto: 'reloj en 1970' }); e.ts = 1000;
	await api(`/operaciones/${cod}/eventos`, { metodo: 'POST', token: operReal, cuerpo: { eventos: [e] } });
	const l = await api(`/operaciones/${cod}/eventos`, { token: operReal });
	assert.ok(l.j.eventos.find(x => x.u === e.u).ts > Date.now() - 3600e3);
});
