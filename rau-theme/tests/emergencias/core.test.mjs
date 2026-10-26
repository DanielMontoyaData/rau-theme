// Pruebas del núcleo (assets/emergencias/em-core.js): sin navegador ni servidor.
//   node --test tests/emergencias/core.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const C = require('../../assets/emergencias/em-core.js');
C.usarEsquema(require('../../assets/emergencias/esquema.json'));

let reloj = Date.UTC(2026, 9, 26, 14, 0, 0);
let n = 0;
const ev = (t, d, extra = {}) => ({ u: 'aaaaaaaa-0000-4000-8000-' + String(++n).padStart(12, '0'), t, a: extra.a || 'HK6OPR', ts: extra.ts || (reloj += 60000), d, s: 1 });
const inc = (extra = {}) => ev('i_nuevo', { tipo: 'deslizamiento', titulo: 'Deslizamiento sobre la vía a Santa Rosa', precision: 'aproximada', lat: 4.87, lng: -75.62, fuente: 'HK6OPR por 147.120', fuente_tipo: 'operador_radio', prio: 'alta', desc: 'Tierra y rocas tapan la vía principal', ...extra });

test('crear y actualizar un incidente deja historial completo', () => {
	const a = inc();
	const S = C.reducir([a, ev('i_act', { id: a.u, situacion: 'Paso a un carril', necesidades: 'Maquinaria' }), ev('i_verif', { id: a.u, v: 'verificado', nota: 'Confirmó Defensa Civil' }, { a: 'HK6COO' }),
		ev('i_resp', { id: a.u, resp: 'HK6COO', org: 'Defensa Civil' }), ev('i_estado', { id: a.u, e: 'en_atencion' })]);
	const x = S.inc[a.u];
	assert.equal(x.verif, 'verificado'); assert.equal(x.estado, 'en_atencion'); assert.equal(x.resp, 'HK6COO');
	assert.equal(x.situaciones.length, 1); assert.equal(x.necesidades, 'Maquinaria');
	assert.equal(x.hist.length, 5, 'cada cambio queda en el historial');
	assert.equal(x.verifHist[0].a, 'HK6COO');
});

test('la prioridad del reporte y la de despacho son distintas', () => {
	const a = inc({ prio: 'media' });
	const S = C.reducir([a, ev('i_desp', { id: a.u, prio: 'P1', autoridad: 'CMGRD Pereira' })]);
	assert.equal(S.inc[a.u].prio, 'media');
	assert.equal(S.inc[a.u].desp.prio, 'P1');
	assert.match(C.sitrep(S, { nombre: 'X', codigo: 'ABC', tipo: 'real' }), /prioridad del reporte, no de despacho/);
});

test('un reporte no se vuelve verificado solo', () => {
	const a = inc();
	const S = C.reducir([a, ev('i_act', { id: a.u, situacion: 'sigue igual' })]);
	assert.equal(S.inc[a.u].verif, 'reportado');
});

test('detecta posibles duplicados por lugar, tiempo y texto, y respeta «no es duplicado»', () => {
	const a = inc(), b = inc({ titulo: 'Deslizamiento en vía a Santa Rosa de Cabal', lat: 4.871, lng: -75.621, fuente: 'HK6XYZ' }), c = inc({ tipo: 'incendio_forestal', titulo: 'Incendio en Bogotá', lat: 4.6, lng: -74.08 });
	let S = C.reducir([a, b, c]);
	const d = C.duplicados(S);
	assert.equal(d.length, 1); assert.deepEqual([d[0].a, d[0].b].sort(), [a.u, b.u].sort());
	assert.ok(d[0].motivos.some(m => /m$/.test(m)), 'explica la distancia');
	S = C.reducir([a, b, c, ev('i_nodup', { a: a.u, b: b.u })]);
	assert.equal(C.duplicados(S).length, 0);
});

test('fusionar exige un registro explícito y conserva ambos incidentes', () => {
	const a = inc(), b = inc({ fuente: 'HK6XYZ' });
	const S = C.reducir([a, b, ev('i_fusion', { de: b.u, en: a.u, nota: 'mismo evento' }, { a: 'HK6COO' })]);
	assert.equal(S.inc[b.u].fusionadoEn, a.u);
	assert.deepEqual(S.inc[a.u].fusionados, [b.u]);
	assert.equal(C.indicadores(S, {}, reloj).incidentes.activos, 1);
});

test('estados de entrega: solo avanzan con registro y evidencia', () => {
	const m = ev('msg', { prec: 'E', para: 'PMU Pereira', texto: 'SE REQUIERE GRUPO USAR EN CALLE 20', num: '1', ck: 7, tipo: 'radiograma' });
	let S = C.reducir([m]);
	assert.equal(S.msgs[m.u].estado, 'creado');
	S = C.reducir([m, ev('m_est', { m: m.u, e: 'en_cola', canal: '147.120' }), ev('m_int', { m: m.u, canal: '147.120', resultado: 'sin_respuesta' }),
		ev('m_est', { m: m.u, e: 'enviado', canal: '7.060' }), ev('m_est', { m: m.u, e: 'recibido_nodo', nodo: 'HK6NOD' }), ev('m_est', { m: m.u, e: 'entregado', evidencia: 'entregado en mano al jefe de bomberos' })]);
	const x = S.msgs[m.u];
	assert.equal(x.estado, 'entregado');
	assert.deepEqual(x.estados.map(s => s.e), ['creado', 'en_cola', 'enviado', 'recibido_nodo', 'entregado']);
	assert.equal(x.intentos.length, 1);
	assert.equal(C.indicadores(S, {}, reloj).mensajes.porConfirmar, 1, 'entregado pero sin confirmar');
});

test('mensajes de la versión 19.52.0 siguen leyéndose', () => {
	const m = ev('msg', { fmt: 'rg', prec: 'P', para: 'X', texto: 'HOLA', estadoInicial: 'recibido' });
	const S = C.reducir([m, ev('msgest', { m: m.u, e: 'retransmitido', aq: 'HK6ZZZ' })]);
	assert.equal(S.msgs[m.u].estados[0].e, 'recibido_nodo');
	assert.equal(S.msgs[m.u].estado, 'enviado');
});

test('estaciones: reportado ≠ verificado, y un dato viejo es «desconocido»', () => {
	const ahora = reloj;
	const r = ev('e_reg', { ind: 'HK6ABC', medios: ['VHF FM'] }, { ts: ahora - 5 * 60000 });
	let S = C.reducir([r]);
	assert.equal(C.estadoEstacion(S.est.HK6ABC, ahora).e, 'no_verificado');
	S = C.reducir([r, ev('e_est', { ind: 'HK6ABC', e: 'disponible' }, { ts: ahora - 60000 })]);
	assert.equal(C.estadoEstacion(S.est.HK6ABC, ahora).e, 'reportado_disponible');
	S = C.reducir([r, ev('e_verif', { ind: 'HK6ABC', resultado: 'ok', metodo: 'Contacto en 147.120' }, { ts: ahora - 60000 })]);
	assert.equal(C.estadoEstacion(S.est.HK6ABC, ahora).e, 'disponible');
	assert.equal(C.estadoEstacion(S.est.HK6ABC, ahora + 3 * 3600000).e, 'desconocido', 'pasada la ventana, no se asume disponible');
	S = C.reducir([r, ev('e_verif', { ind: 'HK6ABC', resultado: 'ok', metodo: 'x' }, { ts: ahora - 120000 }), ev('e_est', { ind: 'HK6ABC', e: 'degradado', nota: 'sin antena' }, { ts: ahora - 60000 })]);
	assert.equal(C.estadoEstacion(S.est.HK6ABC, ahora).e, 'degradado', 'un reporte de falla posterior manda sobre la verificación vieja');
});

test('una prueba de enlace verifica a las dos estaciones', () => {
	const S = C.reducir([ev('enlace', { de: 'HK6AAA', a: 'HK6BBB', canal: '7.060 LSB', resultado: 'ok', senal: '57' })]);
	assert.equal(C.estadoEstacion(S.est.HK6AAA, reloj).e, 'disponible');
	assert.equal(C.estadoEstacion(S.est.HK6BBB, reloj).e, 'disponible');
});

test('recursos: ciclo de solicitud y asignación, con última ubicación conocida', () => {
	const r = ev('r_reg', { tipo: 'radio', desc: '15 portátiles VHF', cant: 15, resp: 'HK6REC', disp: 'disponible', lugar: 'Sede Asorec' });
	const s = ev('r_sol', { tipo: 'radio', cant: 5, desc: 'Para la Gobernación', solicita: 'DIGER', urgencia: 'inmediata' });
	const i = inc();
	let S = C.reducir([r, s, i, ev('r_sol_est', { s: s.u, e: 'aprobada' }), ev('r_est', { r: r.u, e: 'asignado', inc: i.u, sol: s.u }), ev('r_est', { r: r.u, e: 'en_desplazamiento' }),
		ev('r_est', { r: r.u, e: 'desplegado', lugar: 'Gobernación de Risaralda', lat: 4.81, lng: -75.69 })]);
	assert.equal(S.rec[r.u].estado, 'desplegado');
	assert.equal(S.rec[r.u].ubic.lugar, 'Gobernación de Risaralda');
	assert.ok(S.inc[i.u].vinc.rec.includes(r.u), 'queda vinculado al incidente');
	const I = C.indicadores(S, {}, reloj);
	assert.equal(I.recursos.asignados, 1);
	S = C.reducir(S.todo.concat([ev('r_est', { r: r.u, e: 'liberado', resultado: 'Devueltos 15 de 15' })]));
	assert.equal(S.rec[r.u].inc, '');
});

test('cambios simultáneos sobre lo mismo se detectan y se resuelven a mano', () => {
	const a = inc();
	const base = a.u; // ninguno de los dos vio el cambio del otro
	const x = ev('i_estado', { id: a.u, e: 'en_atencion', base }, { a: 'HK6AAA' });
	const y = ev('i_estado', { id: a.u, e: 'resuelto', base }, { a: 'HK6COO' });
	let S = C.reducir([a, x, y]);
	assert.equal(S.conflictos.length, 1);
	assert.equal(S.conflictos[0].resuelto, false);
	S = C.reducir([a, x, y, ev('resuelve', { ent: S.conflictos[0].ent, gana: x.u, nota: 'Sigue en atención' })]);
	assert.equal(S.conflictos[0].resuelto, true);
	assert.equal(S.inc[a.u].estado, 'en_atencion', 'gana el elegido');
	// Con la base correcta no hay conflicto
	const z = ev('i_estado', { id: a.u, e: 'resuelto', base: x.u });
	assert.equal(C.reducir([a, x, z]).conflictos.length, 0);
});

test('validación del navegador: misma regla que el servidor', () => {
	const ok = inc();
	assert.equal(C.validar(ok, { permisos: ['ver', 'operar'] }), '');
	assert.match(C.validar({ t: 'i_nuevo', d: { ...ok.d, tipo: 'meteorito' } }, {}), /no permitido/);
	assert.match(C.validar({ t: 'i_nuevo', d: { tipo: 'sismo' } }, {}), /obligatorio/);
	assert.match(C.validar({ t: 'i_verif', d: { id: ok.u, v: 'verificado' } }, { permisos: ['ver', 'operar'] }), /rol/);
	assert.equal(C.validar({ t: 'i_verif', d: { id: ok.u, v: 'en_verificacion' } }, { permisos: ['ver', 'operar'] }), '');
	assert.match(C.validar({ t: 'sim', d: { escenario: 'x', paso: 1, texto: 'y' } }, { sim: false }), /simulación/);
	assert.equal(C.validar({ t: 'sim', d: { escenario: 'x', paso: 1, texto: 'y' } }, { sim: true }), '');
	assert.match(C.validar({ t: 'pt', d: { tipo: 'albergue', nombre: 'x', lat: 4.8, precision: 'exacta', fuente: 'x' } }, {}), /obligatorio|latitud/);
});

test('el SITREP sale de los registros, con corte, fuentes y limitaciones, sin cifras inventadas', () => {
	const a = inc({ afectados: '12 familias evacuadas' });
	const S = C.reducir([a, ev('decision', { id: a.u, texto: 'Cerrar la vía', autoridad: 'CMGRD' }), ev('accion', { id: a.u, texto: 'Confirmar con bomberos' })]);
	const t = C.sitrep(S, { nombre: 'Lluvias Risaralda', codigo: 'ABC123', tipo: 'real' }, { corte: reloj, responsable: 'HK6COO' });
	assert.match(t, /Fecha de corte: /); assert.match(t, /Elaboró: HK6COO/);
	assert.match(t, /8\. FUENTES[\s\S]*HK6OPR por 147\.120/);
	assert.match(t, /9\. LIMITACIONES/);
	assert.match(t, /según HK6OPR por 147\.120 \(sin verificar\): 12 familias/);
	assert.match(t, /Cerrar la vía \(decidió CMGRD\)/);
	assert.match(t, /Confirmar con bomberos/);
	assert.doesNotMatch(t, /muertos|fallecidos|víctimas/i, 'no agrega cifras que nadie registró');
	const sim = C.sitrep(S, { nombre: 'X', codigo: 'Y', tipo: 'simulacro' }, { corte: reloj });
	assert.match(sim, /^\*\*\* SIMULACRO/);
});

test('GeoJSON con procedencia y sin datos de personas', () => {
	const a = inc();
	const S = C.reducir([a, ev('persona', { nombre: 'María Pérez', estado: 'bien', ubic: 'Coliseo' }), ev('pt', { tipo: 'albergue', nombre: 'Coliseo', lat: 4.81, lng: -75.69, precision: 'exacta', fuente: 'Alcaldía' })]);
	const g = C.geojson(S, { nombre: 'X', codigo: 'Y', tipo: 'real' });
	assert.equal(g.type, 'FeatureCollection');
	assert.equal(g.features.length, 2);
	assert.ok(g.features.every(f => f.properties.fuente), 'cada punto lleva su fuente');
	assert.doesNotMatch(JSON.stringify(g), /María/);
	assert.equal(g.features[0].geometry.coordinates[0], -75.62, 'GeoJSON va longitud, latitud');
});

test('CAP: borrador o ejercicio, nunca una alerta «Actual»', () => {
	const a = inc();
	const S = C.reducir([a]);
	const real = C.cap(S.inc[a.u], { nombre: 'X', codigo: 'Y', tipo: 'real' });
	assert.match(real, /<status>Draft<\/status>/); assert.match(real, /<scope>Restricted<\/scope>/); assert.doesNotMatch(real, /Actual/);
	assert.match(C.cap(S.inc[a.u], { nombre: 'X', codigo: 'Y', tipo: 'simulacro' }), /<status>Exercise<\/status>/);
	assert.match(real, /<certainty>Possible<\/certainty>/, 'un reporte sin verificar es «posible»');
});

test('posiciones escritas: decimal, grados y locator', () => {
	assert.deepEqual(C.parsearPosicion('4.81, -75.69').lat, 4.81);
	const d = C.parsearPosicion('4°48\'36"N 75°41\'24"W'); assert.ok(Math.abs(d.lat - 4.81) < 0.001 && Math.abs(d.lng + 75.69) < 0.001);
	const l = C.parsearPosicion('FJ24ep'); assert.equal(l.metodo, 'locator'); assert.equal(C.locator(l.lat, l.lng), 'FJ24ep');
	assert.equal(C.parsearPosicion('hola'), null);
	assert.equal(C.locator(4.64, -75.62), 'FJ24ep');
});

test('formatos de mensaje: radiograma, voz e ICS-213 sin atribuirse carácter oficial', () => {
	const m = C.reducir([ev('msg', { prec: 'P', para: 'Cruz Roja Risaralda', texto: 'SE NECESITAN 20 FRAZADAS', num: '12', origen: 'HK6ABC', hora: '1432Z', fecha: '26 OCT', firma: 'Coordinador' })]).msgs;
	const x = Object.values(m)[0];
	assert.match(C.textoMensaje(x, 'escrito'), /^N\.º 12 PRIORIDAD HK6ABC CK 4/);
	assert.match(C.textoMensaje(x, 'voz'), /Hotel Kilo Seis Alfa Bravo Charlie/);
	assert.match(C.textoMensaje(x, 'voz'), /26 de octubre/);
	assert.match(C.textoMensaje(x, 'ics213', { nombre: 'Op' }), /no es un formulario oficial/);
});

test('indicadores con filtros por prioridad, lugar y periodo', () => {
	const a = inc({ prio: 'critica', lugar: 'Dosquebradas' }), b = inc({ prio: 'baja', lugar: 'Armenia', lat: 4.53, lng: -75.68 });
	const S = C.reducir([a, b]);
	assert.equal(C.indicadores(S, { prio: 'critica' }, reloj).incidentes.activos, 1);
	assert.equal(C.indicadores(S, { lugar: 'armenia' }, reloj).incidentes.activos, 1);
	assert.equal(C.indicadores(S, { horas: 1 }, reloj + 10 * 3600000).incidentes.activos, 0);
});
