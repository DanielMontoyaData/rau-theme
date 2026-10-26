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

/* ── 19.54: sala de mando ─────────────────────────────────────────────────── */
test('línea de comandos: indicativos, tráfico con precedencia, urgencia y palabras clave', () => {
	const S = C.reducir([]);
	const c = (t) => C.comando(t, S, 'HK6NCS');
	assert.deepEqual(c('hk4abc').d, { ind: 'HK4ABC', estado: 'bien' });
	assert.deepEqual(c('HK4ABC sin novedad').d, { ind: 'HK4ABC', estado: 'bien', trafico: 0 });
	const t = c('HK4ABC tráfico 2 P para la alcaldía');
	assert.equal(t.t, 'contacto'); assert.equal(t.d.trafico, 2); assert.equal(t.d.prec, 'P'); assert.equal(t.d.nota, 'para la alcaldía');
	assert.equal(c('HK4ABC qtc').d.trafico, 1, 'sin número es un mensaje'); assert.equal(c('HK4ABC qtc').d.prec, 'R', 'sin precedencia es rutina');
	const u = c('HK4ABC urgente vivienda colapsada');
	assert.equal(u.d.estado, 'urgente'); assert.equal(u.d.prec, 'E'); assert.equal(u.d.nota, 'vivienda colapsada');
	assert.equal(c('HK4ABC no responde').t, 'sinresp');
	assert.equal(c('HK4ABC qrt').d.sale, true);
	assert.equal(c('HK4ABC fuera de servicio sin batería').t, 'e_est');
	assert.equal(c('HK4ABC desde Santa Rosa de Cabal').d.ubic, 'Santa Rosa de Cabal');
	assert.equal(c('red dirigida 7.060 LSB').d.e, 'dirigida'); assert.equal(c('red dirigida 7.060 LSB').d.ncs, 'HK6NCS'); assert.equal(c('red dirigida 7.060 LSB').d.canal, '7.060 LSB');
	assert.equal(c('nota se cae la energía en Dosquebradas').t, 'nota');
	assert.equal(c('decisión activar repetidora de respaldo').t, 'decision');
	assert.equal(c('inc deslizamiento vía Chinchiná').abrir, 'incidente');
	assert.ok(c('hola mundo').error, 'una palabra suelta no es un indicativo');
	// Lo que produce el analizador lo acepta el esquema
	['hk4abc', 'HK4ABC tráfico 2 P', 'HK4ABC urgente x', 'HK4ABC qrt', 'HK4ABC fuera de servicio', 'red abierta', 'nota x y z'].forEach((l) => {
		const r = c(l); assert.equal(C.validar({ t: r.t, d: C.limpiar(r.d) }, { permisos: ['ver', 'operar', 'coordinar'] }), '', l);
	});
});

test('cola de la red: urgencias, luego tráfico por precedencia, luego silencio', () => {
	reloj = Date.UTC(2026, 9, 26, 15, 0, 0);
	const S = C.reducir([
		ev('contacto', { ind: 'HK4AAA', estado: 'bien' }),
		ev('contacto', { ind: 'HK4BBB', trafico: 1, prec: 'R' }),
		ev('contacto', { ind: 'HK4CCC', trafico: 2, prec: 'P' }),
		ev('contacto', { ind: 'HK4DDD', estado: 'urgente', trafico: 1, prec: 'E' }),
		ev('contacto', { ind: 'HK4EEE', sale: true }),
	]);
	const cola = C.colaRed(S, reloj + 60000);
	assert.deepEqual(cola.map((x) => x.ind), ['HK4DDD', 'HK4CCC', 'HK4BBB', 'HK4AAA'], 'la que salió no está');
	assert.equal(cola[1].prec, 'P'); assert.equal(cola[3].nivel, 'ok');
	assert.equal(C.colaRed(S, reloj + 31 * 60000).find((x) => x.ind === 'HK4AAA').nivel, 'ambar');
});

test('alarmas: silencio, mensaje de emergencia, incidente sin responsable, SITREP y período', () => {
	reloj = Date.UTC(2026, 9, 26, 16, 0, 0);
	const t0 = reloj;
	const a = inc({ prio: 'critica' });
	const S = C.reducir([
		ev('ajuste', { silencio_min: 20, sitrep_h: 2 }, { a: 'HK6COO' }),
		ev('contacto', { ind: 'HK4AAA', estado: 'bien' }),
		ev('msg', { prec: 'E', para: 'CMGRD', texto: 'Se necesitan ambulancias', num: '1' }),
		a,
		ev('periodo', { n: 1, inicio: t0, fin: t0 + 3 * 3600000, objetivos: 'Mantener la red' }, { a: 'HK6COO' }),
	]);
	assert.deepEqual(C.alarmas(S, reloj + 60000), [], 'recién registrado no hay alarmas');
	const al = C.alarmas(S, t0 + 2.6 * 3600000);
	const ids = al.map((x) => x.id.split(':')[0]);
	['sil', 'msg', 'resp', 'ver', 'sitrep', 'per'].forEach((k) => assert.ok(ids.includes(k), 'falta la alarma ' + k + ': ' + ids));
	assert.equal(al[0].nivel, 'critica', 'las críticas primero');
	assert.match(al.find((x) => x.id === 'sitrep').texto, /cada 2 h/);
	// Responder apaga cada alarma
	const m = Object.keys(S.msgs)[0];
	const S2 = C.reducir([...S.todo, ev('contacto', { ind: 'HK4AAA', estado: 'bien' }, { ts: t0 + 2.5 * 3600000 }), ev('m_est', { m, e: 'entregado', evidencia: 'Recibió el operador del CMGRD' }, { ts: t0 + 2.5 * 3600000 }),
		ev('i_resp', { id: a.u, resp: 'Bomberos Pereira' }), ev('informe', { tipo: 'sitrep', corte: t0 + 2.5 * 3600000 }, { ts: t0 + 2.5 * 3600000 })]);
	const ids2 = C.alarmas(S2, t0 + 2.6 * 3600000).map((x) => x.id.split(':')[0]);
	['sil', 'msg', 'resp', 'sitrep'].forEach((k) => assert.ok(!ids2.includes(k), 'sigue la alarma ' + k));
	assert.deepEqual(C.alarmas(C.reducir([...S2.todo, ev('op', { estado: 'cerrado' })]), t0 + 9e7), [], 'una operación cerrada no alarma');
});

test('perfil de enlace: terreno plano despejado, cerro en medio obstruye, Fresnel invadido', () => {
	// Radio de la 1.ª zona de Fresnel en el centro, 10 km y 146 MHz: 17,32·√(5·5/(0,146·10)) ≈ 71,7 m; el 60 % ≈ 43 m
	const plano = C.perfilEnlace({ elev: Array(51).fill(1000), distKm: 10, fMHz: 146, hA: 60, hB: 60 });
	assert.equal(plano.estado, 'despejado');
	assert.ok(Math.abs(plano.fspl - 95.73) < 0.05, 'pérdida en espacio libre a 10 km en 146 MHz ≈ 95,7 dB: ' + plano.fspl);
	const cerro = Array(50).fill(1000); cerro[25] = 1200;
	const o = C.perfilEnlace({ elev: cerro, distKm: 10, fMHz: 146, hA: 10, hB: 10 });
	assert.equal(o.estado, 'obstruido'); assert.ok(Math.abs(o.peor.dKm - 5.1) < 0.2);
	// Un cerro que no corta la línea (va a 1060 m) pero deja menos del 60 % de la zona libre
	const roza = Array(51).fill(1000); roza[25] = 1040; // despeje ≈ 60 − 40 − 1,5 (curvatura) ≈ 18,5 m < 43 m
	const f = C.perfilEnlace({ elev: roza, distKm: 10, fMHz: 146, hA: 60, hB: 60 });
	assert.equal(f.estado, 'fresnel');
	assert.ok(Math.abs(f.pts[25].f1 - 71.7) < 0.3, 'radio de Fresnel ' + f.pts[25].f1);
	assert.ok(Math.abs(f.pts[25].despeje - 18.53) < 0.1, 'despeje con curvatura k=4/3: ' + f.pts[25].despeje);
	assert.equal(C.puntosEntre(4, -75, 5, -74, 3)[1].lat, 4.5);
});

test('relevo de turno e ICS-309 salen de la bitácora', () => {
	reloj = Date.UTC(2026, 9, 26, 18, 0, 0);
	const a = inc({ prio: 'alta' });
	const S = C.reducir([ev('red', { e: 'dirigida', ncs: 'HK6NCS', canal: '7.060 LSB' }), ev('contacto', { ind: 'HK4AAA', trafico: 1, prec: 'P' }), a, ev('decision', { texto: 'Abrir albergue en el coliseo' }, { a: 'HK6COO' }), ev('sinresp', { ind: 'HK4BBB' }, { a: 'HK6NCS' })]);
	const t = C.relevo(S, { nombre: 'Lluvias', codigo: 'ABC123', tipo: 'real' }, { desde: reloj - 3600000, corte: reloj + 60000, entrega: 'HK6NCS', recibe: 'HK6REL' });
	assert.match(t, /RELEVO DE TURNO/); assert.match(t, /Recibe: HK6REL/); assert.match(t, /Red dirigida · control HK6NCS · 7\.060 LSB/);
	assert.match(t, /SIN RESPONSABLE \(nuevo\)/); assert.match(t, /HK4AAA · 1 por pasar \(prioridad\)/); assert.match(t, /Abrir albergue/);
	const r = C.ics309(S);
	assert.equal(r.length, 3); assert.deepEqual([r[1].de, r[1].a], ['HK4AAA', 'HK6OPR']); assert.match(r[1].texto, /tráfico 1 P/); assert.equal(r[2].texto, 'Llamado sin respuesta');
});

test('paquete sin internet: ida y vuelta, partes en desorden, faltantes y alterado', async () => {
	reloj = Date.UTC(2026, 9, 26, 19, 0, 0);
	const evs = [inc({ desc: 'x'.repeat(3000) + ' ' + Math.random() }), ev('contacto', { ind: 'HK4AAA', estado: 'bien' }), ev('nota', { texto: 'Ñandú, tildes: áéíóú' })];
	const p = await C.paqueteCrear(evs, { codigo: 'ABC123', nombre: 'Prueba', tipo: 'real' }, { tam: 200 });
	assert.ok(p.length > 1, 'se parte en varias'); assert.match(p[0], /^RAUEM1 ABC123 1\/\d+ [0-9A-F]{8} /);
	const ok = await C.paqueteLeer(p.slice().reverse().join('\n\n'));
	assert.equal(ok.op, 'ABC123'); assert.equal(ok.eventos.length, 3); assert.equal(ok.eventos[2].d.texto, 'Ñandú, tildes: áéíóú');
	assert.deepEqual(ok.eventos.map((e) => e.u), evs.map((e) => e.u));
	const falta = await C.paqueteLeer(p.slice(1).join('\n'));
	assert.deepEqual(falta.faltan, [1]);
	const malo = p.slice(); malo[0] = malo[0].slice(0, -3) + (malo[0].slice(-3) === 'AAA' ? 'BBB' : 'AAA');
	assert.match((await C.paqueteLeer(malo.join('\n'))).error, /alterado/);
	assert.match((await C.paqueteLeer('hola')).error, /No se encontró/);
});
