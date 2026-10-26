/* Centro de Mando de Emergencias — núcleo (lógica sin interfaz).
 *
 * Funciona igual en el navegador (window.RauEmCore) y en Node (pruebas). Todo lo que se muestra o se
 * exporta sale de aquí a partir de los registros: el estado se CALCULA recorriendo los eventos en
 * orden; ninguna cifra se inventa ni se estima.
 *
 * Conceptos que no se deben confundir (y que esta lógica mantiene separados):
 *  - Prioridad del REPORTE (la da quien registra) ≠ prioridad de DESPACHO (la decide la autoridad).
 *  - Estado REPORTADO de una estación o recurso ≠ estado VERIFICADO por una prueba.
 *  - Un mensaje solo avanza a los estados que alguien registró con evidencia.
 *  - Un dato viejo no es vigente: pasado el plazo, el estado es «desconocido».
 */
(function(raiz, fabrica){ if(typeof module==='object' && module.exports) module.exports=fabrica(); else raiz.RauEmCore=fabrica(); })(this, function(){
'use strict';
var ESQ = null;
function usarEsquema(e){ ESQ = e; }

/* ── Etiquetas ───────────────────────────────────────────────────────────── */
var ET = {
	tipo_inc: {deslizamiento:'Deslizamiento', inundacion:'Inundación', avenida_torrencial:'Avenida torrencial', sismo:'Sismo', incendio_forestal:'Incendio forestal', incendio_estructural:'Incendio estructural', vendaval:'Vendaval', erupcion_volcanica:'Erupción volcánica', colapso_estructural:'Colapso de estructura', accidente:'Accidente', aislamiento_comunidad:'Comunidad aislada', falla_comunicaciones:'Falla de comunicaciones', salud_publica:'Salud pública', otro:'Otro'},
	precision: {exacta:'Ubicación exacta', aproximada:'Ubicación aproximada', municipio:'Solo el municipio', desconocida:'Ubicación desconocida'},
	fuente_tipo: {operador_radio:'Operador de radio', autoridad:'Autoridad', organismo_socorro:'Organismo de socorro', comunidad:'Comunidad', medios:'Medios', sensor:'Sensor o red de monitoreo', otro:'Otra'},
	prio: {critica:'Crítica', alta:'Alta', media:'Media', baja:'Baja'},
	verif: {reportado:'Reportado', en_verificacion:'En verificación', verificado:'Verificado', descartado:'Descartado'},
	estado_inc: {abierto:'Abierto', en_atencion:'En atención', resuelto:'Resuelto', cerrado:'Cerrado'},
	prec: {E:'Emergencia', P:'Prioridad', B:'Bienestar', R:'Rutina'},
	msg_tipo: {radiograma:'Radiograma', ics213:'ICS-213 / SCI-213', tactico:'Táctico', informe_situacion:'Informe de situación', solicitud:'Solicitud', otro:'Otro'},
	msg_est: {creado:'Creado', en_cola:'En cola', enviado:'Enviado', recibido_nodo:'Recibido por nodo', entregado:'Entregado al destinatario', confirmado:'Confirmado', fallido:'Fallido', anulado:'Anulado'},
	msg_int_res: {ok:'Pasó', sin_respuesta:'Sin respuesta', ruido:'Ruido o señal débil', ocupado:'Canal ocupado', error_equipo:'Falla del equipo', otro:'Otro'},
	est_op: {disponible:'Disponible (verificada)', reportado_disponible:'Reportada disponible', no_verificado:'No verificada', degradado:'Degradada', fuera_servicio:'Fuera de servicio', desconocido:'Desconocido'},
	est_estado: {disponible:'Disponible', degradado:'Degradada', fuera_servicio:'Fuera de servicio'},
	energia: {red:'Red eléctrica', bateria:'Batería', generador:'Generador', solar:'Solar', vehiculo:'Vehículo', desconocida:'Desconocida'},
	rec_tipo: {personal:'Personal u operador', radio:'Equipo de radio', antena_nodo:'Antena o nodo portátil', vehiculo:'Vehículo o logística', energia:'Batería, energía o combustible', repetidor_campana:'Repetidor o estación de campaña', material:'Material de apoyo'},
	rec_est: {disponible:'Disponible', reservado:'Reservado', asignado:'Asignado', en_desplazamiento:'En desplazamiento', desplegado:'Desplegado', liberado:'Liberado', fuera_servicio:'Fuera de servicio'},
	sol_est: {pendiente:'Pendiente', aprobada:'Aprobada', rechazada:'Rechazada', atendida:'Atendida', cancelada:'Cancelada'},
	urgencia: {inmediata:'Inmediata', horas:'En horas', dia:'En el día'},
	plan_est: {borrador:'Borrador', propuesto:'Propuesto', aprobado:'Aprobado', retirado:'Retirado'},
	pt_tipo: {puesto_mando:'Puesto de mando', hospital:'Hospital o centro de salud', albergue:'Albergue', punto_apoyo:'Punto de apoyo', zona_afectada:'Zona afectada', cierre_via:'Cierre de vía', sin_comunicacion:'Punto sin comunicación', cobertura_incierta:'Cobertura incierta', ubicacion_operador:'Ubicación de operador'},
	pt_verif: {reportado:'Reportado', verificado:'Verificado', descartado:'Descartado'},
	repe_e: {aire:'En el aire', bateria:'Con batería', generador:'Con generador', caida:'Caída'},
	persona_e: {bien:'Está bien', herido:'Herido', albergue:'En albergue', sinnoticias:'Sin noticias', buscado:'Lo buscan', encontrado:'Encontrado'},
	leccion_cat: {funciono:'Funcionó', fallo:'Falló', mejorar:'Para mejorar'},
	calidad: {confirmado:'Confirmado', reportado:'Reportado', estimado:'Estimado', vencido:'Vencido', desconocido:'Desconocido'}
};
function et(grupo, v){ return (ET[grupo] && ET[grupo][v]) || v || ''; }
var ORDEN_PRIO = {critica:0, alta:1, media:2, baja:3};
var ORDEN_PREC = {E:0, P:1, B:2, R:3};
var MSG_FINAL = {entregado:1, confirmado:1, anulado:1};
var REC_OCUPADO = {reservado:1, asignado:1, en_desplazamiento:1, desplegado:1};

/* ── Utilidades ──────────────────────────────────────────────────────────── */
var MESES = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
var MESES_LARGOS = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
var FON = {A:'Alfa',B:'Bravo',C:'Charlie',D:'Delta',E:'Echo',F:'Foxtrot',G:'Golf',H:'Hotel',I:'India',J:'Juliett',K:'Kilo',L:'Lima',M:'Mike',N:'November',O:'Oscar',P:'Papa',Q:'Quebec',R:'Romeo',S:'Sierra',T:'Tango',U:'Uniform',V:'Victor',W:'Whiskey',X:'X-ray',Y:'Yankee',Z:'Zulu','0':'Cero','1':'Uno','2':'Dos','3':'Tres','4':'Cuatro','5':'Cinco','6':'Seis','7':'Siete','8':'Ocho','9':'Nueve','/':'barra','-':'guion'};
function dos(n){ return (n<10?'0':'')+n; }
function horaZ(ts){ var d=new Date(ts); return dos(d.getUTCHours())+dos(d.getUTCMinutes())+'Z'; }
function fechaZ(ts){ var d=new Date(ts); return d.getUTCDate()+' '+MESES[d.getUTCMonth()]; }
function isoZ(ts){ return new Date(ts).toISOString().slice(0,16).replace('T',' ')+' UTC'; }
function fonetico(s){ return String(s||'').toUpperCase().split('').map(function(c){ return FON[c]||c; }).join(' '); }
function palabras(t){ return String(t||'').trim().split(/\s+/).filter(Boolean).length; }
function indicativo(s){ return String(s||'').toUpperCase().replace(/[^A-Z0-9\/]/g,'').slice(0,20); }
function locator(lat, lng){
	var x=lng+180, y=lat+90, A='ABCDEFGHIJKLMNOPQRSTUVWX';
	var a=Math.floor(x/20), b=Math.floor(y/10), c=Math.floor((x%20)/2), d=Math.floor(y%10), e=Math.floor((x%2)*12), f=Math.floor((y%1)*24);
	return A[a]+A[b]+c+d+A[e].toLowerCase()+A[f].toLowerCase();
}
// Centro de un cuadro Maidenhead de 4 o 6 caracteres
function desdeLocator(g){
	g=String(g||'').trim(); if(!/^[A-Ra-r]{2}\d{2}([A-Xa-x]{2})?$/.test(g)) return null;
	g=g.toUpperCase(); var A='ABCDEFGHIJKLMNOPQRSTUVWX';
	var lng=(A.indexOf(g[0])*20)-180+(+g[2])*2, lat=(A.indexOf(g[1])*10)-90+(+g[3]);
	if(g.length===6){ lng+=A.indexOf(g[4])/12+1/24; lat+=A.indexOf(g[5])/24+1/48; } else { lng+=1; lat+=0.5; }
	return {lat:lat, lng:lng, precision:g.length===6?'aproximada':'municipio', metodo:'locator'};
}
/* Lee una posición escrita: «4.81, -75.69», «4°48'36"N 75°41'24"W» o un locator («FJ24ep»). */
function parsearPosicion(t){
	t=String(t||'').trim(); if(!t) return null;
	var l=desdeLocator(t); if(l) return l;
	var m=t.match(/^(-?\d{1,2}(?:[.,]\d+)?)\s*[,; ]\s*(-?\d{1,3}(?:[.,]\d+)?)$/);
	if(m){ var la=+m[1].replace(',','.'), lo=+m[2].replace(',','.'); if(Math.abs(la)<=90 && Math.abs(lo)<=180) return {lat:la, lng:lo, precision:'exacta', metodo:'decimal'}; }
	var dms=/(\d{1,3})[°º\s]+(\d{1,2})['′\s]+(\d{1,2}(?:[.,]\d+)?)?["″]?\s*([NSEWOnsewo])/g, partes=[], x;
	while((x=dms.exec(t))){ var v=+x[1]+(+x[2])/60+(x[3]?+x[3].replace(',','.')/3600:0), h=x[4].toUpperCase(); if(h==='S'||h==='W'||h==='O') v=-v; partes.push({v:v, h:h}); }
	if(partes.length===2){ var lat=partes.filter(function(p){ return p.h==='N'||p.h==='S'; })[0], lng=partes.filter(function(p){ return p.h!=='N'&&p.h!=='S'; })[0]; if(lat&&lng) return {lat:lat.v, lng:lng.v, precision:'exacta', metodo:'dms'}; }
	return null;
}
function distanciaKm(a1,o1,a2,o2){ var R=6371, r=Math.PI/180, da=(a2-a1)*r, dO=(o2-o1)*r, x=Math.sin(da/2)*Math.sin(da/2)+Math.cos(a1*r)*Math.cos(a2*r)*Math.sin(dO/2)*Math.sin(dO/2); return 2*R*Math.asin(Math.sqrt(x)); }
function normalizar(t){ return String(t||'').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9 ]/g,' ').replace(/\s+/g,' ').trim(); }
function similitud(a, b){
	var A={}, B={}, n=0, u=0, k;
	normalizar(a).split(' ').forEach(function(w){ if(w.length>3) A[w]=1; });
	normalizar(b).split(' ').forEach(function(w){ if(w.length>3) B[w]=1; });
	for(k in A){ u++; if(B[k]) n++; } for(k in B) if(!A[k]) u++;
	return u ? n/u : 0;
}
function uuid(){
	if(typeof crypto!=='undefined' && crypto.randomUUID) return crypto.randomUUID();
	var s=''; for(var i=0;i<32;i++){ s+=Math.floor(Math.random()*16).toString(16); if(i===7||i===11||i===15||i===19) s+='-'; } return s;
}

/* ── Validación (la misma regla que aplica el servidor, para avisar antes de guardar) ── */
function validarCampo(spec, v, nombre){
	var tipo=spec[0], arg=spec[1];
	switch(tipo){
	case 's': case 't': if(typeof v!=='string' && typeof v!=='number') return nombre+' debe ser texto'; if(String(v).length>arg) return nombre+' supera '+arg+' caracteres'; return '';
	case 'n': if(typeof v!=='number' || !isFinite(v)) return nombre+' debe ser un número'; if(arg && (v<arg[0]||v>arg[1])) return nombre+' fuera de rango'; return '';
	case 'b': return typeof v==='boolean' ? '' : nombre+' debe ser sí o no';
	case 'e': return ESQ.enums[arg].indexOf(v)>=0 ? '' : nombre+': valor no permitido';
	case 'u': return typeof v==='string' && /^[a-z0-9-]{8,36}$/.test(v) ? '' : nombre+': referencia inválida';
	case 'ind': return indicativo(v).length>=3 ? '' : nombre+': indicativo inválido';
	case 'a': return Array.isArray(v) && v.length<=arg && v.every(function(x){ return typeof x==='string'; }) ? '' : nombre+': lista inválida';
	}
	return nombre+': tipo desconocido';
}
/* Devuelve '' si el registro es válido, o el motivo. opts: {permisos:[], sim:bool} */
function validar(e, opts){
	opts=opts||{};
	if(!ESQ) return 'Sin esquema';
	var def=ESQ.tipos[e.t]; if(!def) return 'Tipo de registro desconocido';
	var d=e.d||{};
	if((def.soloSim || d.sim!==undefined) && !opts.sim) return 'Los registros de simulación no se aceptan en una operación real';
	var perm=permisoPara(e.t, d);
	if(opts.permisos && opts.permisos.indexOf(perm)<0) return 'Tu rol no permite este registro ('+perm+')';
	var campos=Object.assign({}, def.campos, ESQ.comunes), k, err;
	for(k in d){ if(!campos[k]) return 'Campo no permitido: '+k; if(d[k]===null||d[k]==='') continue; err=validarCampo(campos[k], d[k], k); if(err) return err; }
	for(k in campos){ if(campos[k][2] && (d[k]===undefined||d[k]===null||d[k]==='')) return 'Falta el campo obligatorio: '+k; }
	if((d.lat!==undefined) !== (d.lng!==undefined)) return 'La ubicación necesita latitud y longitud';
	return '';
}
function permisoPara(t, d){
	var def=ESQ.tipos[t]; if(!def) return 'admin';
	var p=def.perm;
	if(def.permPor && d && typeof d[def.permPor.campo]==='string' && def.permPor.valores[d[def.permPor.campo]]) p=def.permPor.valores[d[def.permPor.campo]];
	return p;
}
// Quita campos vacíos antes de validar o guardar
function limpiar(d){ var o={}; Object.keys(d||{}).forEach(function(k){ var v=d[k]; if(v===undefined||v===null||v==='') return; if(typeof v==='string') v=v.trim(); if(v==='') return; o[k]=v; }); return o; }

/* ── Reductor: del registro de eventos al estado ─────────────────────────── */
// Clave de entidad de los registros que cambian un estado (para detectar cambios simultáneos)
var CLAVE_ESTADO = { i_verif:'id', i_estado:'id', m_est:'m', r_est:'r', e_est:'ind', p_est:'p', r_sol_est:'s', pt_act:'p' };
function claveEntidad(e){ var c=CLAVE_ESTADO[e.t]; return c && e.d ? e.t.split('_')[0]+':'+e.d[c] : ''; }
/* Base que debe llevar un nuevo cambio de estado: el último cambio que este dispositivo conoce. */
function baseDe(S, t, d){ var ce=claveEntidad({t:t, d:d}); return ce ? (S.ultimoEstado[ce] || entidadUuid(d[CLAVE_ESTADO[t]])) : undefined; }
// Las estaciones se identifican por indicativo, no por un identificador de registro: ahí no hay base inicial
function entidadUuid(v){ return typeof v==='string' && /^[a-z0-9-]{8,36}$/.test(v) ? v : undefined; }

function reducir(eventos, opts){
	opts=opts||{};
	var lista=(eventos||[]).filter(function(e){ return e && e.t && e.s!=='r'; }).slice().sort(function(a,b){ return (a.ts-b.ts) || (a.u<b.u?-1:a.u>b.u?1:0); });
	var S={ inc:{}, msgs:{}, est:{}, enlaces:[], rec:{}, sol:{}, planes:{}, asig:{}, pts:{}, personas:{}, dir:{}, repe:{}, decisiones:[], acciones:{}, notas:[], sims:[], lecciones:[], informes:[],
		conflictos:[], cerrada:false, todo:lista, porU:{}, ultimoEstado:{} };
	lista.forEach(function(e){ S.porU[e.u]=e; });
	lista.forEach(function(e){ aplicar(S, e, false); });
	return S;
}
function aplicar(S, e, reaplicando){
	var d=e.d||{}, x, k;
	// Cambios de estado simultáneos: el registro se hizo sin ver el último cambio de esa entidad
	var ce=claveEntidad(e);
	if(ce && !reaplicando){
		// Sin cambios previos, la base es la entidad misma: dos «primeros cambios» simultáneos también chocan
		var ult=S.ultimoEstado[ce] || entidadUuid(d[CLAVE_ESTADO[e.t]]);
		if(d.base!==undefined && ult && d.base!==ult && !S.conflictos.some(function(c){ return c.ent===ce && c.eventos.indexOf(e.u)>=0; }))
			S.conflictos.push({ent:ce, tipo:e.t, eventos:[ult, e.u], ts:e.ts, resuelto:false});
		S.ultimoEstado[ce]=e.u;
	}
	switch(e.t){
	/* Operación */
	case 'op': case 'inc': S.cerrada = d.estado==='cerrado'; break;
	case 'leccion': S.lecciones.push(e); break;
	case 'informe': S.informes.push(e); break;
	case 'sim': S.sims.push(e); break;
	case 'nota': S.notas.push(e); break;
	case 'resuelve':
		S.conflictos.forEach(function(c){ if(c.ent===d.ent && !c.resuelto){ c.resuelto=true; c.gana=d.gana; c.por=e.a; c.nota=d.nota; } });
		if(S.porU[d.gana]) aplicar(S, Object.assign({}, S.porU[d.gana], {ts:e.ts}), true);
		S.ultimoEstado[d.ent]=d.gana;
		break;

	/* Incidentes */
	case 'i_nuevo':
		S.inc[e.u]={id:e.u, d:d, tipo:d.tipo, titulo:d.titulo, recibido:e.ts, autor:e.a, prio:d.prio, prioJust:d.prio_just||'', prioHist:[], verif:'reportado', verifHist:[], estado:'abierto', estadoHist:[],
			lat:d.lat, lng:d.lng, precision:d.precision, lugar:d.lugar||'', fuente:d.fuente, fuenteTipo:d.fuente_tipo, desc:d.desc||'', necesidades:d.necesidades||'', afectados:d.afectados||'', riesgos:d.riesgos||'', orgs:d.orgs||'',
			ocurrio:d.ocurrio||0, desp:null, resp:'', respOrg:'', vinc:{msg:[], rec:[], sol:[], pt:[]}, situaciones:[], decisiones:[], acciones:[], hist:[e], act:e.ts, fusionadoEn:'', fusionados:[], noDup:{}, sim:d.sim||''};
		break;
	case 'i_act':
		x=S.inc[d.id]; if(!x) break;
		['desc','necesidades','afectados','riesgos','orgs','lugar','lat','lng','precision','fuente'].forEach(function(c){ if(d[c]!==undefined) x[c]=d[c]; });
		if(d.situacion) x.situaciones.push({ts:e.ts, a:e.a, texto:d.situacion});
		x.hist.push(e); x.act=e.ts; break;
	case 'i_verif': x=S.inc[d.id]; if(!x) break; x.verif=d.v; x.verifHist.push({v:d.v, ts:e.ts, a:e.a, nota:d.nota}); x.hist.push(e); x.act=e.ts; break;
	case 'i_estado': x=S.inc[d.id]; if(!x) break; x.estado=d.e; x.estadoHist.push({e:d.e, ts:e.ts, a:e.a, nota:d.nota}); x.hist.push(e); x.act=e.ts; break;
	case 'i_prio': x=S.inc[d.id]; if(!x) break; x.prioHist.push({de:x.prio, a_:d.prio, just:d.just, ts:e.ts, a:e.a}); x.prio=d.prio; x.prioJust=d.just; x.hist.push(e); x.act=e.ts; break;
	case 'i_desp': x=S.inc[d.id]; if(!x) break; x.desp={prio:d.prio, autoridad:d.autoridad, nota:d.nota, ts:e.ts, a:e.a}; x.hist.push(e); x.act=e.ts; break;
	case 'i_resp': x=S.inc[d.id]; if(!x) break; x.resp=d.resp; x.respOrg=d.org||''; x.hist.push(e); x.act=e.ts; break;
	case 'i_vinc': x=S.inc[d.id]; if(!x) break; if(x.vinc[d.tipo].indexOf(d.ref)<0) x.vinc[d.tipo].push(d.ref); x.hist.push(e); break;
	case 'i_fusion':
		x=S.inc[d.de]; var en=S.inc[d.en]; if(!x||!en||d.de===d.en) break;
		x.fusionadoEn=d.en; en.fusionados.push(d.de); x.hist.push(e); en.hist.push(e); en.act=e.ts; break;
	case 'i_nodup': if(S.inc[d.a]) S.inc[d.a].noDup[d.b]=1; if(S.inc[d.b]) S.inc[d.b].noDup[d.a]=1; break;
	case 'decision': S.decisiones.push(e); if(d.id && S.inc[d.id]){ S.inc[d.id].decisiones.push(e); S.inc[d.id].hist.push(e); } break;
	case 'accion': S.acciones[e.u]={id:e.u, inc:d.id||'', texto:d.texto, resp:d.resp||'', vence:d.vence||0, ts:e.ts, a:e.a, hecha:null}; if(d.id && S.inc[d.id]) S.inc[d.id].acciones.push(e.u); break;
	case 'accion_ok': x=S.acciones[d.a]; if(x) x.hecha={ts:e.ts, a:e.a, nota:d.nota}; break;

	/* Mensajes */
	case 'msg':
		var ini = d.estadoInicial==='recibido' ? 'recibido_nodo' : 'creado';
		S.msgs[e.u]={id:e.u, d:d, a:e.a, ts:e.ts, estado:ini, estados:[{e:ini, ts:e.ts, a:e.a, nodo:ini==='recibido_nodo'?e.a:''}], intentos:[], inc:d.inc||'', sim:d.sim||''};
		if(d.inc && S.inc[d.inc] && S.inc[d.inc].vinc.msg.indexOf(e.u)<0) S.inc[d.inc].vinc.msg.push(e.u);
		break;
	case 'm_est': x=S.msgs[d.m]; if(!x) break; x.estado=d.e; x.estados.push({e:d.e, ts:e.ts, a:e.a, canal:d.canal||'', nodo:d.nodo||'', evidencia:d.evidencia||'', nota:d.nota||''}); break;
	case 'msgest': // versión 19.52.0
		x=S.msgs[d.m]; if(!x) break;
		var map={pendiente:'creado', recibido:'recibido_nodo', retransmitido:'enviado', entregado:'entregado', anulado:'anulado'};
		x.estado=map[d.e]||d.e; x.estados.push({e:x.estado, ts:e.ts, a:e.a, canal:'', nodo:d.aq||'', evidencia:'', nota:d.n||''}); break;
	case 'm_int': x=S.msgs[d.m]; if(!x) break; x.intentos.push({ts:e.ts, a:e.a, canal:d.canal, resultado:d.resultado, nota:d.nota||''}); break;

	/* Estaciones y enlaces */
	case 'e_reg':
		x=S.est[d.ind]=S.est[d.ind]||nuevaEstacion(d.ind, e);
		['nombre','org','lat','lng','precision','lugar','medios','modos','energia','autonomia','limitaciones','resp'].forEach(function(c){ if(d[c]!==undefined) x[c]=d[c]; });
		x.hist.push(e); x.act=e.ts; break;
	case 'e_est': x=S.est[d.ind]=S.est[d.ind]||nuevaEstacion(d.ind, e); x.reportado={e:d.e, ts:e.ts, a:e.a, fuente:d.fuente||'', nota:d.nota||''}; x.hist.push(e); x.act=e.ts; break;
	case 'e_verif': x=S.est[d.ind]=S.est[d.ind]||nuevaEstacion(d.ind, e); x.verif={resultado:d.resultado, ts:e.ts, a:e.a, metodo:d.metodo, nota:d.nota||''}; x.hist.push(e); x.act=e.ts; if(d.resultado==='ok') x.contacto=e.ts; break;
	case 'enlace':
		S.enlaces.push({id:e.u, de:d.de, a:d.a, canal:d.canal, resultado:d.resultado, senal:d.senal||'', nota:d.nota||'', ts:e.ts, autor:e.a});
		[d.de, d.a].forEach(function(i){ var s=S.est[i]=S.est[i]||nuevaEstacion(i, e); s.hist.push(e); if(d.resultado!=='fallo'){ s.verif={resultado:'ok', ts:e.ts, a:e.a, metodo:'Prueba de enlace '+d.de+'–'+d.a+' por '+d.canal+(d.resultado==='debil'?' (señal débil)':''), nota:d.nota||''}; s.contacto=e.ts; } s.act=e.ts; });
		break;
	case 'checkin': // versión 19.52.0: se reparte en registro, estado reportado y contacto
		x=S.est[d.ind]=S.est[d.ind]||nuevaEstacion(d.ind, e);
		if(d.nombre) x.nombre=d.nombre; if(d.ubic) x.lugar=d.ubic; if(d.lat!==undefined){ x.lat=d.lat; x.lng=d.lng; x.precision='exacta'; } if(d.energia) x.energia=d.energia;
		x.reportado={e:d.estado==='fuera'?'fuera_servicio':'disponible', ts:e.ts, a:e.a, fuente:'Registro de la versión anterior', nota:d.nota||''};
		if(d.estado==='apoyo'||d.estado==='urgente') x.situacion={e:d.estado, ts:e.ts, nota:d.nota||''};
		x.contacto=e.ts; x.trafico=d.trafico||0; x.hist.push(e); x.act=e.ts; break;
	case 'contacto':
		x=S.est[d.ind]=S.est[d.ind]||nuevaEstacion(d.ind, e); x.contacto=e.ts; x.sinresp=0;
		if(d.estado==='apoyo'||d.estado==='urgente') x.situacion={e:d.estado, ts:e.ts, nota:d.nota||''}; else if(d.estado==='bien') x.situacion=null;
		if(d.trafico!==undefined) x.trafico=d.trafico; x.hist.push(e); x.act=e.ts; break;
	case 'sinresp': x=S.est[d.ind]; if(!x) break; x.sinresp=(x.sinresp||0)+1; x.intento=e.ts; x.hist.push(e); break;
	case 'repe': S.repe[d.r]={r:d.r, e:d.e, n:d.n||'', ts:e.ts, a:e.a}; break;

	/* Recursos */
	case 'r_reg':
		x=S.rec[e.u]={id:e.u, cod:d.cod||'', tipo:d.tipo, desc:d.desc, cant:d.cant||1, org:d.org||'', resp:d.resp, estado:d.disp, ts:e.ts, a:e.a, act:e.ts, hist:[e], inc:'', sol:'',
			ubic: (d.lugar||d.lat!==undefined) ? {lugar:d.lugar||'', lat:d.lat, lng:d.lng, ts:e.ts, a:e.a} : null};
		break;
	case 'r_est':
		x=S.rec[d.r]; if(!x) break; x.estado=d.e; x.act=e.ts; x.hist.push(e);
		if(d.inc!==undefined) x.inc=d.inc; if(d.sol) x.sol=d.sol; if(d.e==='liberado'||d.e==='disponible'){ x.inc=''; x.sol=''; }
		if(d.resultado) x.resultado=d.resultado;
		if(d.lugar||d.lat!==undefined) x.ubic={lugar:d.lugar||'', lat:d.lat, lng:d.lng, ts:e.ts, a:e.a};
		if(d.inc && S.inc[d.inc] && S.inc[d.inc].vinc.rec.indexOf(d.r)<0) S.inc[d.inc].vinc.rec.push(d.r);
		break;
	case 'r_sol':
		S.sol[e.u]={id:e.u, inc:d.inc||'', tipo:d.tipo, cant:d.cant, desc:d.desc, solicita:d.solicita, urgencia:d.urgencia, estado:'pendiente', ts:e.ts, a:e.a, hist:[e], rec:''};
		if(d.inc && S.inc[d.inc]) S.inc[d.inc].vinc.sol.push(e.u);
		break;
	case 'r_sol_est': x=S.sol[d.s]; if(!x) break; x.estado=d.e; if(d.rec) x.rec=d.rec; x.hist.push(e); x.act=e.ts; break;
	case 'recurso': // versión 19.52.0
		S.rec[e.u]={id:e.u, cod:'', tipo:'material', desc:(d.tipo||'')+(d.desc?' · '+d.desc:''), cant:d.cant||1, org:'', resp:d.resp||'', estado:'disponible', ts:e.ts, a:e.a, act:e.ts, hist:[e], inc:'', sol:'', ubic:d.donde?{lugar:d.donde, ts:e.ts, a:e.a}:null};
		break;
	case 'recursox': if(S.rec[d.p]) { S.rec[d.p].estado='liberado'; S.rec[d.p].retirado=true; } break;

	/* Plan de comunicaciones */
	case 'p_ver': S.planes[e.u]={id:e.u, d:d, ts:e.ts, a:e.a, estado:'borrador', hist:[]}; break;
	case 'p_est': x=S.planes[d.p]; if(!x) break; x.estado=d.e; x.hist.push({e:d.e, ts:e.ts, a:e.a, por:d.por||'', nota:d.nota||''}); break;
	case 'plan': S.asig[e.u]={id:e.u, d:d, a:e.a, ts:e.ts}; break;
	case 'planx': delete S.asig[d.p]; break;

	/* Mapa */
	case 'pt': S.pts[e.u]={id:e.u, tipo:d.tipo, nombre:d.nombre, lat:d.lat, lng:d.lng, radio_m:d.radio_m||0, precision:d.precision, fuente:d.fuente, obs:d.obs_ts||e.ts, verif:'reportado', activo:true, nota:d.nota||'', inc:d.inc||'', ts:e.ts, a:e.a, hist:[e]};
		if(d.inc && S.inc[d.inc]) S.inc[d.inc].vinc.pt.push(e.u); break;
	case 'pt_act': x=S.pts[d.p]; if(!x) break; if(d.verif) x.verif=d.verif; if(d.activo!==undefined) x.activo=d.activo; if(d.nota) x.nota=d.nota; x.hist.push(e); x.act=e.ts; break;

	/* Bienestar y directorio */
	case 'persona': S.personas[e.u]={id:e.u, d:d, a:e.a, ts:e.ts, estado:d.estado, hist:[]}; break;
	case 'personaact': x=S.personas[d.p]; if(!x) break; x.estado=d.estado; if(d.ubic) x.d.ubic=d.ubic; x.hist.push({ts:e.ts, a:e.a, estado:d.estado, n:d.n||''}); x.act=e.ts; break;
	case 'dir': S.dir[e.u]={id:e.u, d:d, a:e.a, ts:e.ts}; break;
	case 'dirx': delete S.dir[d.p]; break;
	}
}
function nuevaEstacion(ind, e){ return {ind:ind, alta:e.ts, act:e.ts, hist:[], reportado:null, verif:null, contacto:0, sinresp:0, medios:[], situacion:null}; }

/* ── Vigencia de los datos ───────────────────────────────────────────────── */
/* Estado operativo de una estación: nunca se infiere disponibilidad de datos viejos.
   ventanaMin: cuánto vale un reporte o una verificación (60 min por defecto). */
function estadoEstacion(x, ahora, ventanaMin){
	var V=(ventanaMin||60)*60000, rep=x.reportado, ver=x.verif;
	var ultimo=Math.max(rep?rep.ts:0, ver?ver.ts:0, x.contacto||0);
	if(!ultimo) return {e:'no_verificado', calidad:'desconocido', ts:x.alta, motivo:'Registrada sin reportes ni verificaciones'};
	if(ahora-ultimo>V) return {e:'desconocido', calidad:'vencido', ts:ultimo, motivo:'Último dato de hace '+Math.round((ahora-ultimo)/60000)+' min'};
	if(ver && ver.resultado==='ok' && ahora-ver.ts<=V && (!rep || rep.e==='disponible' || ver.ts>=rep.ts)) return {e:'disponible', calidad:'confirmado', ts:ver.ts, motivo:ver.metodo};
	if(rep && rep.e==='fuera_servicio') return {e:'fuera_servicio', calidad:'reportado', ts:rep.ts, motivo:rep.nota||('Reportado por '+rep.a)};
	if(rep && rep.e==='degradado') return {e:'degradado', calidad:'reportado', ts:rep.ts, motivo:rep.nota||('Reportado por '+rep.a)};
	if(ver && ver.resultado==='fallo' && (!rep || ver.ts>=rep.ts)) return {e:'no_verificado', calidad:'reportado', ts:ver.ts, motivo:'La última verificación falló: '+ver.metodo};
	if(rep && rep.e==='disponible') return {e:'reportado_disponible', calidad:'reportado', ts:rep.ts, motivo:'Reportado por '+rep.a+(rep.fuente?' ('+rep.fuente+')':'')};
	return {e:'reportado_disponible', calidad:'reportado', ts:x.contacto, motivo:'Respondió por radio'};
}
function estadoRepetidora(r, ahora, ventanaMin){
	if(!r) return {e:'desconocido', calidad:'desconocido'};
	var V=(ventanaMin||60)*60000*2;
	if(ahora-r.ts>V) return {e:'desconocido', calidad:'vencido', ultimo:r.e};
	return {e:r.e, calidad:'reportado'};
}
function incidenteActivo(x){ return !x.fusionadoEn && x.verif!=='descartado' && x.estado!=='cerrado' && x.estado!=='resuelto'; }

/* ── Detección de posibles duplicados (siempre la decide una persona) ───── */
function duplicados(S, opts){
	opts=opts||{}; var minimo=opts.minimo||0.55, l=Object.keys(S.inc).map(function(k){ return S.inc[k]; }).filter(function(x){ return !x.fusionadoEn && x.verif!=='descartado'; }), out=[];
	for(var i=0;i<l.length;i++) for(var j=i+1;j<l.length;j++){
		var a=l[i], b=l[j]; if(a.noDup[b.id]) continue;
		var p=0, mot=[];
		if(a.lat!==undefined && b.lat!==undefined){
			var km=distanciaKm(a.lat,a.lng,b.lat,b.lng); if(km>20) continue;
			if(km<=0.5){ p+=0.5; mot.push('a '+Math.round(km*1000)+' m'); } else if(km<=2){ p+=0.35; mot.push('a '+km.toFixed(1)+' km'); } else if(km<=5){ p+=0.15; mot.push('a '+km.toFixed(1)+' km'); }
		} else if(a.lugar && b.lugar && normalizar(a.lugar)===normalizar(b.lugar)){ p+=0.3; mot.push('mismo lugar escrito'); }
		var ta=a.ocurrio||a.recibido, tb=b.ocurrio||b.recibido, dh=Math.abs(ta-tb)/3600000;
		if(dh<=1){ p+=0.25; mot.push('con menos de 1 h de diferencia'); } else if(dh<=6){ p+=0.15; mot.push('con '+Math.round(dh)+' h de diferencia'); } else if(dh<=24) p+=0.05;
		if(a.tipo===b.tipo){ p+=0.15; mot.push('mismo tipo'); }
		var sim=similitud(a.titulo+' '+a.desc, b.titulo+' '+b.desc); if(sim>0){ p+=sim*0.3; if(sim>=0.2) mot.push('texto parecido ('+Math.round(sim*100)+' %)'); }
		if(p>=minimo) out.push({a:a.id, b:b.id, puntaje:Math.round(p*100)/100, motivos:mot});
	}
	return out.sort(function(x,y){ return y.puntaje-x.puntaje; });
}

/* ── Indicadores del tablero (con filtros) ───────────────────────────────── */
function filtrarIncidentes(S, f){
	f=f||{}; var desde=f.horas ? (f.ahora||Date.now())-f.horas*3600000 : 0, q=normalizar(f.lugar||''), org=normalizar(f.org||'');
	return Object.keys(S.inc).map(function(k){ return S.inc[k]; }).filter(function(x){
		if(x.fusionadoEn) return false;
		if(f.id && x.id!==f.id) return false;
		if(f.prio && x.prio!==f.prio) return false;
		if(f.estado && x.estado!==f.estado && x.verif!==f.estado) return false;
		if(desde && x.act<desde) return false;
		if(q && normalizar(x.lugar+' '+x.titulo).indexOf(q)<0) return false;
		if(org && normalizar(x.orgs+' '+x.respOrg).indexOf(org)<0) return false;
		return true;
	});
}
function indicadores(S, f, ahora, ventanaMin){
	ahora=ahora||Date.now(); f=Object.assign({ahora:ahora}, f||{});
	var incs=filtrarIncidentes(S, f), act=incs.filter(incidenteActivo), V=(ventanaMin||60)*60000;
	var porPrio={critica:0, alta:0, media:0, baja:0}, porEstado={abierto:0, en_atencion:0, resuelto:0, cerrado:0}, porVerif={reportado:0, en_verificacion:0, verificado:0, descartado:0};
	act.forEach(function(x){ porPrio[x.prio]++; }); incs.forEach(function(x){ porEstado[x.estado]++; porVerif[x.verif]++; });
	var recs=Object.keys(S.rec).map(function(k){ return S.rec[k]; }).filter(function(r){ return !r.retirado; });
	var est=Object.keys(S.est).map(function(k){ var x=S.est[k]; return {x:x, s:estadoEstacion(x, ahora, ventanaMin)}; });
	var msgs=Object.keys(S.msgs).map(function(k){ return S.msgs[k]; });
	var vencidos=[];
	est.forEach(function(o){ if(o.s.e==='desconocido') vencidos.push({tipo:'estacion', id:o.x.ind, texto:'Estación '+o.x.ind+': sin datos hace '+Math.round((ahora-o.s.ts)/60000)+' min', ts:o.s.ts}); });
	act.forEach(function(x){ if(ahora-x.act>2*V) vencidos.push({tipo:'incidente', id:x.id, texto:'Incidente «'+x.titulo+'» sin actualización hace '+Math.round((ahora-x.act)/3600000*10)/10+' h', ts:x.act}); });
	Object.keys(S.repe).forEach(function(k){ var r=estadoRepetidora(S.repe[k], ahora, ventanaMin); if(r.e==='desconocido') vencidos.push({tipo:'repetidora', id:k, texto:'Repetidora '+k+': último reporte vencido', ts:S.repe[k].ts}); });
	var acciones=Object.keys(S.acciones).map(function(k){ return S.acciones[k]; }).filter(function(a){ return !a.hecha && (!f.id || a.inc===f.id); });
	var solicitudes=Object.keys(S.sol).map(function(k){ return S.sol[k]; }).filter(function(s){ return (s.estado==='pendiente'||s.estado==='aprobada') && (!f.id || s.inc===f.id); });
	var reportes=S.todo.filter(function(e){ return /^(i_nuevo|i_act|e_est|repe|pt|checkin)$/.test(e.t); }).slice(-8).reverse();
	return {
		ahora:ahora,
		incidentes:{activos:act.length, total:incs.length, porPrio:porPrio, porEstado:porEstado, porVerif:porVerif, ultima:max(incs.map(function(x){ return x.act; }))},
		recursos:{disponibles:recs.filter(function(r){ return r.estado==='disponible'||r.estado==='liberado'; }).length, asignados:recs.filter(function(r){ return REC_OCUPADO[r.estado]; }).length,
			fuera:recs.filter(function(r){ return r.estado==='fuera_servicio'; }).length, total:recs.length, ultima:max(recs.map(function(r){ return r.act; }))},
		estaciones:{registradas:est.length, verificadas:est.filter(function(o){ return o.s.e==='disponible'; }).length, reportadas:est.filter(function(o){ return o.s.calidad==='reportado'; }).length,
			desconocidas:est.filter(function(o){ return o.s.e==='desconocido'||o.s.e==='no_verificado'; }).length, ultima:max(est.map(function(o){ return o.x.act; }))},
		mensajes:{porEntregar:msgs.filter(function(m){ return !MSG_FINAL[m.estado]; }).length, porConfirmar:msgs.filter(function(m){ return m.estado==='entregado'; }).length,
			emergencia:msgs.filter(function(m){ return !MSG_FINAL[m.estado] && m.d.prec==='E'; }).length, ultima:max(msgs.map(function(m){ return m.estados[m.estados.length-1].ts; }))},
		reportes:reportes, decisiones:S.decisiones.slice(-5).reverse(),
		enlaces:Object.keys(S.repe).map(function(k){ return {nombre:k, r:S.repe[k], s:estadoRepetidora(S.repe[k], ahora, ventanaMin)}; }).concat(S.enlaces.slice(-5).reverse().map(function(x){ return {nombre:x.de+' – '+x.a+' ('+x.canal+')', enlace:x}; })),
		vencidos:vencidos, acciones:acciones, solicitudes:solicitudes,
		conflictos:S.conflictos.filter(function(c){ return !c.resuelto; }).length, duplicados:duplicados(S).length
	};
}
function max(a){ return a.length ? Math.max.apply(null, a) : 0; }

/* ── Textos de mensajes ──────────────────────────────────────────────────── */
function fechaHablada(fecha){ var m=String(fecha||'').match(/^(\d+) (\w+)$/); return m ? m[1]+' de '+MESES_LARGOS[MESES.indexOf(m[2])] : fecha; }
function textoMensaje(m, modo, meta){
	var d=m.d, pr=(d.prueba?'PRUEBA ':'')+et('prec', d.prec).toUpperCase(), ori=d.origen||m.a;
	if(modo==='ics213'){
		return 'MENSAJE GENERAL (formato ICS-213 / SCI-213)\nAdaptación de HK4RAU: no es un formulario oficial validado.'+(d.prueba?'\n*** PRUEBA / SIMULACRO ***':'')
			+'\n\n1. Incidente: '+((meta&&meta.nombre)||'')+'\n2. Para: '+d.para+(d.org?' ('+d.org+')':'')+'\n3. De: '+(d.de||ori)+'\n4. Asunto: '+(d.asunto||'')+'\n5. Fecha: '+(d.fecha||'')+'   6. Hora: '+(d.hora||'')
			+'\n7. Mensaje:\n'+d.texto+'\n\n8. Aprobado por: '+(d.autoriza||'')+'\n   Firma: '+(d.firma||'')+'\n\nPrecedencia: '+pr+' · Transmitido por: '+ori+' · N.º '+(d.num||'')+' · Canal: '+(d.canal_o||'')+(d.canal_d?' → '+d.canal_d:'');
	}
	if(modo==='voz'){
		return 'Número '+(d.num||'')+', '+pr.toLowerCase()+(d.hx?', '+d.hx:'')+', estación de origen '+fonetico(ori)+', cuenta '+(d.ck!=null?d.ck:palabras(d.texto))+(d.lugar?', '+d.lugar:'')+', hora '+String(d.hora||'').replace('Z',' zulú')+', '+fechaHablada(d.fecha)+'.\n\nPara: '+d.para+(d.tel?', teléfono '+String(d.tel).split('').join(' '):'')+'.\n\nTexto: (pausa) '+d.texto+' (pausa) fin del texto.\n\nFirma: '+(d.firma||'sin firma')+'.\n\nCambio.';
	}
	return 'N.º '+(d.num||'')+' '+pr+(d.hx?' '+d.hx:'')+' '+ori+' CK '+(d.ck!=null?d.ck:palabras(d.texto))+' '+(d.lugar||'')+' '+(d.hora||'')+' '+(d.fecha||'')+'\nPARA: '+d.para+(d.tel?' · TEL '+d.tel:'')+'\nBT\n'+String(d.texto).toUpperCase()+'\nBT\n'+(d.firma||'');
}

/* ── Informes ────────────────────────────────────────────────────────────── */
/* SITREP: solo lo registrado, con fecha de corte, fuentes, responsable y limitaciones. */
function sitrep(S, meta, opts){
	opts=opts||{}; var ahora=opts.corte||Date.now(), V=opts.ventanaMin||60, I=indicadores(S, {}, ahora, V), sim=meta.tipo==='simulacro', L=[];
	var incs=Object.keys(S.inc).map(function(k){ return S.inc[k]; }).filter(function(x){ return !x.fusionadoEn; }).filter(function(x){ return x.recibido<=ahora; });
	var act=incs.filter(incidenteActivo).sort(function(a,b){ return ORDEN_PRIO[a.prio]-ORDEN_PRIO[b.prio] || a.recibido-b.recibido; });
	var pendientesConf=incs.filter(function(x){ return incidenteActivo(x) && x.verif!=='verificado'; });
	if(sim) L.push('*** SIMULACRO — EJERCICIO — NO ES UNA EMERGENCIA REAL ***','');
	L.push('INFORME DE SITUACIÓN (SITREP)', 'Operación: '+meta.nombre+(meta.zona?' · '+meta.zona:'')+' · código '+meta.codigo, 'Fecha de corte: '+isoZ(ahora), 'Elaboró: '+(opts.responsable||''), 'Fuente: registros del centro de mando HK4RAU ('+S.todo.filter(function(e){ return e.ts<=ahora; }).length+' registros de '+contarAutores(S, ahora)+' operadores).', '');
	L.push('1. SITUACIÓN GENERAL');
	L.push('Incidentes activos: '+I.incidentes.activos+' (crítica '+I.incidentes.porPrio.critica+', alta '+I.incidentes.porPrio.alta+', media '+I.incidentes.porPrio.media+', baja '+I.incidentes.porPrio.baja+'; prioridad del reporte, no de despacho).');
	L.push('Verificación: '+I.incidentes.porVerif.verificado+' verificados, '+I.incidentes.porVerif.reportado+' solo reportados, '+I.incidentes.porVerif.en_verificacion+' en verificación, '+I.incidentes.porVerif.descartado+' descartados.');
	L.push('', '2. INCIDENTES ACTIVOS');
	if(!act.length) L.push('Ninguno registrado.');
	act.forEach(function(x){
		L.push('- ['+et('prio',x.prio).toUpperCase()+'] '+x.titulo+' · '+et('tipo_inc',x.tipo)+' · '+(x.lugar||et('precision',x.precision))+(x.lat!==undefined?' ('+x.lat.toFixed(4)+', '+x.lng.toFixed(4)+', '+et('precision',x.precision).toLowerCase()+')':''));
		L.push('  '+et('verif',x.verif)+' · '+et('estado_inc',x.estado)+' · fuente: '+x.fuente+' · recibido '+isoZ(x.recibido)+' · última actualización '+isoZ(x.act));
		if(x.desp) L.push('  Prioridad de despacho: '+x.desp.prio+' (decidió '+x.desp.autoridad+')');
		if(x.resp) L.push('  Responsable: '+x.resp+(x.respOrg?' ('+x.respOrg+')':''));
		if(x.necesidades) L.push('  Necesidades: '+x.necesidades);
		if(x.afectados) L.push('  Personas afectadas según '+x.fuente+(x.verif==='verificado'?'':' (sin verificar)')+': '+x.afectados);
		var sit=x.situaciones[x.situaciones.length-1]; if(sit) L.push('  Última situación ('+horaZ(sit.ts)+', '+sit.a+'): '+sit.texto);
	});
	L.push('', '3. DECISIONES REGISTRADAS');
	var dec=S.decisiones.filter(function(e){ return e.ts<=ahora; }); if(!dec.length) L.push('Ninguna.');
	dec.forEach(function(e){ L.push('- '+horaZ(e.ts)+' '+e.d.texto+(e.d.autoridad?' (decidió '+e.d.autoridad+')':'')+' · registró '+e.a); });
	L.push('', '4. RECURSOS');
	L.push('Disponibles: '+I.recursos.disponibles+' · asignados o en uso: '+I.recursos.asignados+' · fuera de servicio: '+I.recursos.fuera+' (de '+I.recursos.total+' registrados).');
	L.push('Solicitudes abiertas: '+I.solicitudes.length+(I.solicitudes.length?':':'.'));
	I.solicitudes.forEach(function(s){ L.push('- '+s.cant+' × '+et('rec_tipo',s.tipo)+': '+s.desc+' · '+et('urgencia',s.urgencia)+' · '+et('sol_est',s.estado)+' · pidió '+s.solicita); });
	L.push('', '5. COMUNICACIONES');
	L.push('Estaciones: '+I.estaciones.registradas+' registradas, '+I.estaciones.verificadas+' verificadas en los últimos '+V+' min, '+I.estaciones.reportadas+' solo reportadas, '+I.estaciones.desconocidas+' sin datos vigentes.');
	Object.keys(S.repe).forEach(function(k){ var r=S.repe[k], s=estadoRepetidora(r, ahora, V); L.push('- Repetidora '+k+': '+(s.e==='desconocido'?'desconocido (último reporte: '+et('repe_e',r.e)+', '+isoZ(r.ts)+')':et('repe_e',r.e)+' según '+r.a+', '+isoZ(r.ts))); });
	L.push('Mensajes por entregar: '+I.mensajes.porEntregar+' ('+I.mensajes.emergencia+' de emergencia) · entregados sin confirmar: '+I.mensajes.porConfirmar+'.');
	L.push('', '6. RIESGOS Y DATOS CONTRADICTORIOS');
	var r6=0;
	act.filter(function(x){ return x.riesgos; }).forEach(function(x){ r6++; L.push('- Riesgo en «'+x.titulo+'»: '+x.riesgos); });
	duplicados(S).forEach(function(p){ r6++; L.push('- Posible duplicado: «'+S.inc[p.a].titulo+'» y «'+S.inc[p.b].titulo+'» ('+p.motivos.join(', ')+'). Pendiente de revisión humana.'); });
	S.conflictos.filter(function(c){ return !c.resuelto; }).forEach(function(c){ r6++; L.push('- Cambios simultáneos sin resolver en '+c.ent+'.'); });
	I.vencidos.forEach(function(v){ r6++; L.push('- Dato vencido: '+v.texto); });
	if(!r6) L.push('Ninguno registrado.');
	L.push('', '7. PRÓXIMAS ACCIONES');
	if(!I.acciones.length) L.push('Ninguna pendiente.');
	I.acciones.forEach(function(a){ L.push('- '+a.texto+(a.resp?' · responsable '+a.resp:'')+(a.vence?' · vence '+isoZ(a.vence):'')); });
	L.push('', '8. FUENTES');
	var fuentes={}; incs.forEach(function(x){ fuentes[x.fuente+' ('+et('fuente_tipo',x.fuenteTipo).toLowerCase()+')']=1; });
	Object.keys(fuentes).forEach(function(f){ L.push('- '+f); }); L.push('- Operadores del centro de mando (registros con autor y hora).');
	L.push('', '9. LIMITACIONES');
	L.push('- Este informe resume lo registrado por operadores de radioaficionados. No es un informe oficial ni reemplaza los sistemas de las autoridades.');
	L.push('- Pendiente de confirmar: '+pendientesConf.length+' incidentes activos sin verificación.');
	L.push('- No incluye estimaciones: las cifras de personas afectadas se citan tal como las reportó cada fuente.');
	if(S.conflictos.some(function(c){ return !c.resuelto; }) || duplicados(S).length) L.push('- Hay registros con posibles duplicados o cambios simultáneos sin resolver.');
	if(sim) L.push('', '*** FIN DEL SIMULACRO ***');
	return L.join('\n');
}
function contarAutores(S, ahora){ var a={}; S.todo.forEach(function(e){ if(e.ts<=ahora) a[e.a]=1; }); return Object.keys(a).length; }
function informeCierre(S, meta, opts){
	opts=opts||{}; var L=[], todo=S.todo, ini=todo.length?todo[0].ts:0, fin=todo.length?todo[todo.length-1].ts:0;
	var incs=Object.keys(S.inc).map(function(k){ return S.inc[k]; }), msgs=Object.keys(S.msgs).map(function(k){ return S.msgs[k]; });
	if(meta.tipo==='simulacro') L.push('*** SIMULACRO — EJERCICIO ***','');
	L.push('INFORME DE CIERRE', 'Operación: '+meta.nombre+' · código '+meta.codigo, 'Periodo registrado: '+(ini?isoZ(ini)+' a '+isoZ(fin):'sin registros'), 'Elaboró: '+(opts.responsable||''), '');
	L.push('RESUMEN DE LO REGISTRADO');
	L.push('- Incidentes: '+incs.length+' ('+incs.filter(function(x){ return x.verif==='verificado'; }).length+' verificados, '+incs.filter(function(x){ return x.verif==='descartado'; }).length+' descartados, '+incs.filter(function(x){ return x.fusionadoEn; }).length+' fusionados como duplicados).');
	L.push('- Mensajes: '+msgs.length+' ('+msgs.filter(function(m){ return m.estado==='confirmado'; }).length+' confirmados, '+msgs.filter(function(m){ return m.estado==='entregado'; }).length+' entregados sin confirmar, '+msgs.filter(function(m){ return !MSG_FINAL[m.estado]; }).length+' sin entregar).');
	var tiempos=msgs.map(function(m){ var en=m.estados.filter(function(s){ return s.e==='entregado'||s.e==='confirmado'; })[0]; return en ? (en.ts-m.ts)/60000 : null; }).filter(function(x){ return x!==null; });
	if(tiempos.length) L.push('- Tiempo de entrega de mensajes: mediana '+Math.round(mediana(tiempos))+' min (de '+tiempos.length+' mensajes con entrega registrada).');
	L.push('- Estaciones registradas: '+Object.keys(S.est).length+' · pruebas de enlace: '+S.enlaces.length+' · decisiones: '+S.decisiones.length+'.');
	L.push('', 'LECCIONES APRENDIDAS');
	['funciono','fallo','mejorar'].forEach(function(c){ var l=S.lecciones.filter(function(e){ return e.d.cat===c; }); if(l.length){ L.push(et('leccion_cat',c)+':'); l.forEach(function(e){ L.push('- '+e.d.texto+' ('+e.a+')'); }); } });
	if(!S.lecciones.length) L.push('Sin lecciones registradas todavía.');
	L.push('', 'Este informe resume registros de operadores; no es un documento oficial.');
	return L.join('\n');
}
function mediana(a){ var s=a.slice().sort(function(x,y){ return x-y; }), m=Math.floor(s.length/2); return s.length%2 ? s[m] : (s[m-1]+s[m])/2; }

/* ── Exportaciones ───────────────────────────────────────────────────────── */
/* GeoJSON con procedencia; sin datos de personas (bienestar). extra: capas fijas (repetidoras del sitio). */
function geojson(S, meta, extra){
	var F=[]; function p(lat,lng,props){ if(lat===undefined||lng===undefined) return; F.push({type:'Feature', geometry:{type:'Point', coordinates:[+lng.toFixed(6), +lat.toFixed(6)]}, properties:props}); }
	Object.keys(S.inc).forEach(function(k){ var x=S.inc[k]; p(x.lat, x.lng, {capa:'incidente', id:x.id, nombre:x.titulo, tipo:et('tipo_inc',x.tipo), prioridad_reporte:x.prio, prioridad_despacho:x.desp?x.desp.prio:null, verificacion:x.verif, estado:x.estado, precision:x.precision, fuente:x.fuente, recibido:new Date(x.recibido).toISOString(), actualizado:new Date(x.act).toISOString(), fusionado_en:x.fusionadoEn||null}); });
	Object.keys(S.pts).forEach(function(k){ var x=S.pts[k]; p(x.lat, x.lng, {capa:x.tipo, id:x.id, nombre:x.nombre, verificacion:x.verif, activo:x.activo, precision:x.precision, fuente:x.fuente, observado:new Date(x.obs).toISOString(), radio_m:x.radio_m||null}); });
	Object.keys(S.est).forEach(function(k){ var x=S.est[k], s=estadoEstacion(x, Date.now()); p(x.lat, x.lng, {capa:'estacion', id:x.ind, nombre:x.nombre||x.ind, estado:s.e, calidad:s.calidad, dato:new Date(s.ts||x.alta).toISOString(), precision:x.precision||'', organizacion:x.org||''}); });
	Object.keys(S.rec).forEach(function(k){ var x=S.rec[k]; if(x.ubic) p(x.ubic.lat, x.ubic.lng, {capa:'recurso', id:x.id, nombre:x.desc, tipo:et('rec_tipo',x.tipo), estado:x.estado, ultima_ubicacion:new Date(x.ubic.ts).toISOString(), nota:'Última ubicación reportada, no seguimiento en tiempo real'}); });
	(extra||[]).forEach(function(x){ p(x.lat, x.lng, x.props); });
	return {type:'FeatureCollection', name:(meta&&meta.nombre)||'operacion', metadata:{generado:new Date().toISOString(), operacion:meta&&meta.codigo, simulacro:!!(meta&&meta.tipo==='simulacro'), nota:'Datos registrados por operadores; incluye procedencia y nivel de verificación.'}, features:F};
}
function csv(filas){
	return filas.map(function(f){ return f.map(function(v){ v=v==null?'':String(v); return /[",\n;]/.test(v) ? '"'+v.replace(/"/g,'""')+'"' : v; }).join(','); }).join('\n');
}
function xmlEsc(s){ return String(s==null?'':s).replace(/[<>&'"]/g, function(c){ return {'<':'&lt;','>':'&gt;','&':'&amp;',"'":'&apos;','"':'&quot;'}[c]; }); }
/* CAP 1.2 de un incidente: en estado «Draft» (o «Exercise» en simulacro), alcance restringido. No es
   una alerta pública: sirve para pasar el incidente a otro sistema que entienda CAP. */
function cap(x, meta, remitente){
	var sev={critica:'Extreme', alta:'Severe', media:'Moderate', baja:'Minor'}[x.prio]||'Unknown', cert={verificado:'Observed', reportado:'Possible', en_verificacion:'Likely', descartado:'Unlikely'}[x.verif]||'Unknown';
	var cat={sismo:'Geo', deslizamiento:'Geo', erupcion_volcanica:'Geo', inundacion:'Met', avenida_torrencial:'Met', vendaval:'Met', incendio_forestal:'Fire', incendio_estructural:'Fire', salud_publica:'Health', falla_comunicaciones:'Infra', colapso_estructural:'Infra', accidente:'Transport'}[x.tipo]||'Other';
	return '<?xml version="1.0" encoding="UTF-8"?>\n<alert xmlns="urn:oasis:names:tc:emergency:cap:1.2">\n'
		+'  <identifier>HK4RAU-'+xmlEsc(meta.codigo)+'-'+xmlEsc(x.id)+'</identifier>\n  <sender>'+xmlEsc(remitente||'hk4rau.com')+'</sender>\n  <sent>'+new Date().toISOString().replace(/\.\d+Z$/,'+00:00')+'</sent>\n'
		+'  <status>'+(meta.tipo==='simulacro'?'Exercise':'Draft')+'</status>\n  <msgType>Alert</msgType>\n  <scope>Restricted</scope>\n  <restriction>Coordinación entre operadores. No es una alerta oficial ni pública.</restriction>\n'
		+'  <info>\n    <language>es-CO</language>\n    <category>'+cat+'</category>\n    <event>'+xmlEsc(et('tipo_inc',x.tipo))+'</event>\n    <urgency>Unknown</urgency>\n    <severity>'+sev+'</severity>\n    <certainty>'+cert+'</certainty>\n'
		+(x.ocurrio?'    <onset>'+new Date(x.ocurrio).toISOString().replace(/\.\d+Z$/,'+00:00')+'</onset>\n':'')
		+'    <senderName>Centro de mando HK4RAU · '+xmlEsc(meta.nombre)+'</senderName>\n    <headline>'+xmlEsc(x.titulo)+'</headline>\n    <description>'+xmlEsc(x.desc)+'</description>\n'
		+(x.necesidades?'    <instruction>'+xmlEsc('Necesidades reportadas: '+x.necesidades)+'</instruction>\n':'')
		+'    <parameter><valueName>fuente</valueName><value>'+xmlEsc(x.fuente)+'</value></parameter>\n    <parameter><valueName>verificacion</valueName><value>'+xmlEsc(x.verif)+'</value></parameter>\n'
		+'    <area>\n      <areaDesc>'+xmlEsc(x.lugar||et('precision',x.precision))+'</areaDesc>\n'+(x.lat!==undefined?'      <circle>'+x.lat.toFixed(5)+','+x.lng.toFixed(5)+' '+(x.precision==='exacta'?'0.1':x.precision==='aproximada'?'2':'10')+'</circle>\n':'')+'    </area>\n  </info>\n</alert>\n';
}

return { usarEsquema:usarEsquema, ET:ET, et:et, ORDEN_PRIO:ORDEN_PRIO, ORDEN_PREC:ORDEN_PREC, MSG_FINAL:MSG_FINAL, REC_OCUPADO:REC_OCUPADO,
	horaZ:horaZ, fechaZ:fechaZ, isoZ:isoZ, fonetico:fonetico, palabras:palabras, indicativo:indicativo, locator:locator, desdeLocator:desdeLocator, parsearPosicion:parsearPosicion,
	distanciaKm:distanciaKm, normalizar:normalizar, similitud:similitud, uuid:uuid, validar:validar, permisoPara:permisoPara, limpiar:limpiar,
	reducir:reducir, claveEntidad:claveEntidad, baseDe:baseDe, estadoEstacion:estadoEstacion, estadoRepetidora:estadoRepetidora, incidenteActivo:incidenteActivo,
	duplicados:duplicados, filtrarIncidentes:filtrarIncidentes, indicadores:indicadores, textoMensaje:textoMensaje, sitrep:sitrep, informeCierre:informeCierre,
	geojson:geojson, csv:csv, cap:cap, mediana:mediana };
});
