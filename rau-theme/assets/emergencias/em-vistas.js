/* Centro de Mando de Emergencias — vistas operativas.
 * A tablero · C incidentes · D comunicaciones y mensajes · E estaciones y enlaces · F plan · G recursos
 * H bitácora · I informes · bienestar · directorio · herramientas. (B mapa en em-mapa.js, L simulador
 * en em-sim.js, J campo y K administración en em.js.)
 */
(function(){
'use strict';
var E=window.RauEm, core=E.core, C=E.C, $=E.$, $$=E.$$, esc=E.esc, et=core.et;
var CLASE_PRIO={critica:'peligro', alta:'aviso', media:'ok', baja:'apagado'}, CLASE_VERIF={reportado:'aviso', en_verificacion:'aviso', verificado:'ok', descartado:'apagado'};
var CLASE_EST={disponible:'ok', reportado_disponible:'aviso', no_verificado:'apagado', degradado:'aviso', fuera_servicio:'peligro', desconocido:'apagado'};
var CLASE_MSG={creado:'apagado', en_cola:'aviso', enviado:'aviso', recibido_nodo:'aviso', entregado:'ok', confirmado:'ok', fallido:'peligro', anulado:'apagado'};
var CLASE_REC={disponible:'ok', reservado:'aviso', asignado:'aviso', en_desplazamiento:'aviso', desplegado:'ok', liberado:'ok', fuera_servicio:'peligro'};
var MEDIOS=['VHF FM','UHF FM','HF voz','HF digital','Winlink','APRS','EchoLink','DMR','Satelital','Teléfono','Internet'];
function prio(x){ return E.pill(CLASE_PRIO[x.prio], 'Reporte: '+et('prio',x.prio), 'Prioridad del reporte (no es la de despacho)'); }
function verif(v){ return E.pill(CLASE_VERIF[v], et('verif',v)); }
function sinPermiso(p){ return !E.puede(p); }
function boton(id, texto, clase, perm, extra){ return perm && sinPermiso(perm) ? '' : '<button type="button" class="em-btn '+(clase||'')+'" '+(id?'id="'+id+'" ':'')+(extra||'')+'>'+texto+'</button>'; }
function cerrada(){ return E.S.cerrada; }

/* ── Descripción de cada registro (bitácora, cola, historial) ────────────── */
E.describir = function(e){
	var d=e.d||{}, S=E.S, x;
	function inc(id){ return S.inc[id] ? '«'+esc(S.inc[id].titulo)+'»' : 'un incidente'; }
	function msg(id){ var m=S.msgs[id]; return m ? 'mensaje n.º '+esc(m.d.num||'?')+' para '+esc(m.d.para) : 'un mensaje'; }
	function rec(id){ return S.rec[id] ? esc(S.rec[id].desc) : 'un recurso'; }
	switch(e.t){
	case 'op': case 'inc': return d.estado==='cerrado' ? '<b>Operación cerrada</b>'+(d.nota?' — '+esc(d.nota):'') : 'Operación '+(d.nota?esc(d.nota):'abierta');
	case 'i_nuevo': return 'Incidente nuevo '+E.pill(CLASE_PRIO[d.prio], et('prio',d.prio))+' <b>'+esc(d.titulo)+'</b> · '+esc(et('tipo_inc',d.tipo))+' · '+esc(d.lugar||et('precision',d.precision))+' · fuente: '+esc(d.fuente);
	case 'i_act': return 'Actualización de '+inc(d.id)+(d.situacion?': '+esc(d.situacion):'');
	case 'i_verif': return inc(d.id)+' → '+esc(et('verif',d.v))+(d.nota?' — '+esc(d.nota):'');
	case 'i_estado': return inc(d.id)+' → '+esc(et('estado_inc',d.e))+(d.nota?' — '+esc(d.nota):'');
	case 'i_prio': return 'Prioridad del reporte de '+inc(d.id)+' → '+esc(et('prio',d.prio))+' ('+esc(d.just)+')';
	case 'i_desp': return 'Prioridad de despacho de '+inc(d.id)+': '+esc(d.prio)+' (decidió '+esc(d.autoridad)+')';
	case 'i_resp': return 'Responsable de '+inc(d.id)+': '+esc(d.resp)+(d.org?' ('+esc(d.org)+')':'');
	case 'i_vinc': return 'Se vinculó '+(d.tipo==='msg'?msg(d.ref):d.tipo==='rec'?rec(d.ref):d.tipo==='sol'?'una solicitud':'un punto del mapa')+' a '+inc(d.id);
	case 'i_fusion': return '<b>Fusión:</b> '+inc(d.de)+' es duplicado de '+inc(d.en)+(d.nota?' — '+esc(d.nota):'');
	case 'i_nodup': return inc(d.a)+' y '+inc(d.b)+' no son duplicados';
	case 'decision': return '<span class="em-tipo-nota">Decisión</span> '+esc(d.texto)+(d.autoridad?' (decidió '+esc(d.autoridad)+')':'')+(d.id?' · '+inc(d.id):'');
	case 'accion': return 'Acción pendiente: '+esc(d.texto)+(d.resp?' · '+esc(d.resp):'');
	case 'accion_ok': x=S.acciones[d.a]; return 'Acción hecha: '+esc(x?x.texto:'')+(d.nota?' — '+esc(d.nota):'');
	case 'msg': return 'Mensaje '+esc(et('prec',d.prec))+' n.º '+esc(d.num||'')+' de <b>'+esc(d.origen||e.a)+'</b> para '+esc(d.para)+(d.estadoInicial==='recibido'?' (recibido)':'');
	case 'm_est': return msg(d.m)+' → <b>'+esc(et('msg_est',d.e))+'</b>'+(d.nodo?' en '+esc(d.nodo):'')+(d.canal?' por '+esc(d.canal):'')+(d.evidencia?' — '+esc(d.evidencia):'')+(d.nota?' — '+esc(d.nota):'');
	case 'msgest': return msg(d.m)+' → '+esc(d.e)+(d.aq?' a '+esc(d.aq):'');
	case 'm_int': return 'Intento de '+msg(d.m)+' por '+esc(d.canal)+': '+esc(et('msg_int_res',d.resultado))+(d.nota?' — '+esc(d.nota):'');
	case 'e_reg': return 'Estación <b>'+esc(d.ind)+'</b> registrada o actualizada'+(d.lugar?' en '+esc(d.lugar):'');
	case 'e_est': return 'Estación <b>'+esc(d.ind)+'</b> reporta: '+esc(et('est_estado',d.e))+(d.nota?' — '+esc(d.nota):'');
	case 'e_verif': return 'Verificación de <b>'+esc(d.ind)+'</b>: '+(d.resultado==='ok'?'respondió':'no respondió')+' ('+esc(d.metodo)+')';
	case 'enlace': return 'Prueba de enlace '+esc(d.de)+' – '+esc(d.a)+' por '+esc(d.canal)+': '+esc({ok:'buena', debil:'débil', fallo:'falló'}[d.resultado])+(d.senal?' ('+esc(d.senal)+')':'');
	case 'checkin': return 'Registró a <b>'+esc(d.ind)+'</b>'+(d.ubic?' en '+esc(d.ubic):'');
	case 'contacto': return (d.sale?'<b>'+esc(d.ind)+'</b> sale de la red':d.estado==='urgente'?'<b class="em-rojo">URGENCIA</b> de <b>'+esc(d.ind)+'</b>':d.estado==='apoyo'?'<b>'+esc(d.ind)+'</b> pide apoyo':'Contacto con <b>'+esc(d.ind)+'</b>')
		+(d.trafico?' · tráfico '+esc(d.trafico)+' '+esc(et('prec',d.prec||'R').toLowerCase()):d.trafico===0?' · sin tráfico':'')+(d.ubic?' · desde '+esc(d.ubic):'')+(d.nota?' — '+esc(d.nota):'');
	case 'red': return '<span class="em-tipo-nota">Red</span> '+esc(et('red_est',d.e))+' · control <b>'+esc(d.ncs)+'</b>'+(d.canal?' · '+esc(d.canal):'')+(d.nota?' — '+esc(d.nota):'');
	case 'periodo': return '<span class="em-tipo-nota">Período '+esc(d.n)+'</span> '+core.isoZ(d.inicio)+' → '+core.isoZ(d.fin)+' · '+esc(d.objetivos);
	case 'ajuste': return 'Ajustes de alarmas: '+(d.silencio_min?'silencio '+esc(d.silencio_min)+' min':'')+(d.silencio_min&&d.sitrep_h?' · ':'')+(d.sitrep_h?'SITREP cada '+esc(d.sitrep_h)+' h':'');
	case 'sinresp': return '<b>'+esc(d.ind)+'</b> no respondió';
	case 'repe': return 'Repetidora '+esc(d.r)+': '+esc(et('repe_e',d.e))+(d.n?' — '+esc(d.n):'');
	case 'r_reg': return 'Recurso registrado: '+esc(d.cant||1)+' × '+esc(d.desc)+' ('+esc(et('rec_tipo',d.tipo))+')';
	case 'r_est': return rec(d.r)+' → '+esc(et('rec_est',d.e))+(d.inc?' · '+inc(d.inc):'')+(d.lugar?' · '+esc(d.lugar):'')+(d.resultado?' · '+esc(d.resultado):'');
	case 'r_sol': return 'Solicitud: '+esc(d.cant)+' × '+esc(et('rec_tipo',d.tipo))+' — '+esc(d.desc)+' ('+esc(et('urgencia',d.urgencia))+')';
	case 'r_sol_est': x=S.sol[d.s]; return 'Solicitud «'+esc(x?x.desc:'')+'» → '+esc(et('sol_est',d.e));
	case 'recurso': return 'Recurso: '+esc(d.tipo)+' '+esc(d.desc||'');
	case 'recursox': return 'Recurso retirado';
	case 'p_ver': return 'Nueva versión del plan de comunicaciones';
	case 'p_est': return 'Plan de comunicaciones → '+esc(et('plan_est',d.e))+(d.por?' por '+esc(d.por):'');
	case 'plan': return 'Canal asignado: '+esc(d.funcion)+' en '+esc(d.f);
	case 'planx': return 'Se quitó una asignación de canal';
	case 'pt': return 'Mapa: '+esc(et('pt_tipo',d.tipo))+' «'+esc(d.nombre)+'» (fuente: '+esc(d.fuente)+')';
	case 'pt_act': x=S.pts[d.p]; return 'Mapa: «'+esc(x?x.nombre:'')+'»'+(d.verif?' → '+esc(et('pt_verif',d.verif)):'')+(d.activo===false?' → ya no vigente':'')+(d.nota?' — '+esc(d.nota):'');
	case 'informe': return 'Se generó '+({sitrep:'un SITREP', cierre:'el informe de cierre', relevo:'el relevo de turno'}[d.tipo]||'un informe')+' con corte '+core.isoZ(d.corte);
	case 'leccion': return 'Lección ('+esc(et('leccion_cat',d.cat))+'): '+esc(d.texto);
	case 'resuelve': return 'Conflicto resuelto en '+esc(d.ent)+(d.nota?' — '+esc(d.nota):'');
	case 'sim': return '<span class="em-tipo-nota">Simulación</span> '+esc(d.texto);
	case 'nota': return '<span class="em-tipo-nota">'+esc(d.tipo||'Nota')+'</span> '+esc(d.texto);
	case 'persona': return 'Bienestar: <b>'+esc(d.nombre)+'</b> · '+esc(et('persona_e',d.estado));
	case 'personaact': x=S.personas[d.p]; return 'Bienestar: '+esc(x?x.d.nombre:'')+' → '+esc(et('persona_e',d.estado));
	case 'dir': return 'Contacto: '+esc(d.nombre);
	case 'dirx': return 'Se quitó un contacto';
	}
	return esc(e.t);
};
function estadoReg(e){ if(e.s==='p') return '<i class="em-pend" title="Llegó en un paquete sin internet. Lo envía al servidor quien lo registró.">por paquete</i>'; if(E.op && E.op.local) return ''; return e.s==='r' ? '<i class="em-marca-r" title="'+esc(e.err||'Rechazado')+'">rechazado</i>' : e.s ? '' : '<i class="em-pend" title="Solo en este dispositivo: todavía no llegó al servidor">sin enviar</i>'; }
E.filaBit = function(e){ return '<li><time>'+core.horaZ(e.ts)+'</time><span class="em-autor">'+esc(e.a)+'</span><span>'+E.describir(e)+'</span>'+estadoReg(e)+'</li>'; };
function edadClase(ts){ var m=E.edadMin(ts); return m>=E.ventana?'em-rojo':m>=E.ventana/2?'em-ambar':'em-verde'; }

/* ── Alertas y frecuencias (inicio, tablero, directorio) ─────────────────── */
E.alertas=null; E.directorio=null;
function cargarAlertas(){ E.rest('alertas', {token:false}).then(function(r){ if(r.estado===200){ E.alertas=r.j; E.alertas.recibido=Date.now(); E.db.kvSet('alertas', E.alertas); E.refrescar(); } }).catch(function(){}); }
function cargarDirectorio(){ E.rest('directorio', {token:false}).then(function(r){ if(r.estado===200){ E.directorio=r.j; E.directorio.recibido=Date.now(); E.db.kvSet('directorio', E.directorio); E.refrescar(); } }).catch(function(){}); }
E.alArrancar.push(function(){
	E.db.kvGet('alertas').then(function(a){ if(a && !E.alertas) E.alertas=a; });
	E.db.kvGet('directorio').then(function(d){ if(d && !E.directorio) E.directorio=d; if(!E.directorio && C.directorio) E.directorio=C.directorio; E.refrescar(); });
	cargarAlertas(); cargarDirectorio(); setInterval(cargarAlertas, 5*60000);
});
function recomendacionBanda(){
	var h=new Date().getUTCHours()-5; if(h<0) h+=24;
	var dia=h>=7 && h<18, o=E.alertas && E.alertas.oido;
	var r='<p><strong>Dentro de Colombia (NVIS):</strong> '+(dia?'40 m (centro de actividad 7.060). De día la onda de cielo vuelve cerca.':'80 m (centro de actividad 3.985). De noche 40 m salta lejos y deja de oírse a 100–500 km.')+' <small>Regla general de propagación, no una medición.</small></p>';
	if(o && o.bandas){
		r+='<p><strong>Medido</strong> por el receptor HK4RAU en '+esc(o.lugar||'Colombia')+' ('+E.haceTxt(o.actualizado*1000)+', últimos 30 min): '+['40m','20m','15m','10m'].map(function(k){ var x=o.bandas[k]; return k.replace('m',' m')+': '+(x&&x.n?'<strong>oye '+x.n+' estaciones</strong>':'sin estaciones'); }).join(' · ')+'.</p>';
	}
	return r;
}
/* USGS escribe el lugar en inglés: «37 km ESE of Cañaveral, Peru» → «a 37 km al ESE de Cañaveral, Perú» */
var PAISES={Peru:'Perú', Panama:'Panamá', Brazil:'Brasil', Mexico:'México', 'Dominican Republic':'República Dominicana', Haiti:'Haití', 'Trinidad and Tobago':'Trinidad y Tobago'};
E.lugarSismo = function(t){
	t=String(t||''); var m=t.match(/^(\d+(?:\.\d+)?) km ([NSEW]{1,3}) of (.+)$/);
	function pais(x){ return x.replace(/([^,]+)$/, function(p){ p=p.trim(); return ' '+(PAISES[p]||p); }).replace(/^ /,''); }
	if(m) return 'a '+m[1]+' km al '+m[2].replace(/W/g,'O')+' de '+pais(m[3]);
	return pais(t.replace(/^(near|off) the coast of /i, 'frente a la costa de ').replace(/ region$/i, ' (región)'));
};
E.VIVO.alertas = function(){
	var a=E.alertas && E.alertas.alertas, h=(E.op && E.esSim() ? '<p class="em-aviso em-real">Datos REALES de fuentes externas: no forman parte del simulacro.</p>' : '')+recomendacionBanda();
	if(!E.alertas) return h+'<p class="em-nota">Alertas en vivo: necesitan internet.</p>';
	if(a && a.gdacs && a.gdacs.length) h+='<h3>Alertas de desastre (GDACS)</h3><div class="em-lista">'+a.gdacs.slice(0,4).map(function(g){ return '<div class="em-fila"><span>'+E.pill(g.nivel==='red'?'peligro':g.nivel==='orange'?'aviso':'ok', g.nivel==='red'?'Roja':g.nivel==='orange'?'Naranja':'Verde')+' <strong>'+esc(g.tipo)+'</strong> '+esc(g.nombre)+'<small>desde '+new Date(g.desde*1000).toLocaleDateString('es-CO')+'</small></span>'+(g.url?'<a href="'+esc(g.url)+'" target="_blank" rel="noopener">Ver</a>':'')+'</div>'; }).join('')+'</div>';
	if(a && a.sismos){ var s=a.sismos.slice(0,5); h+='<h3>Sismos M3,5+ · 7 días (USGS)</h3>'+(s.length?'<div class="em-lista">'+s.map(function(x){ return '<div class="em-fila"><span><strong class="'+(x.m>=6?'em-rojo':x.m>=5?'em-ambar':'')+'">M'+x.m.toFixed(1)+'</strong> '+esc(E.lugarSismo(x.lugar))+'<small>'+E.haceTxt(x.t*1000)+' · '+x.prof+' km de profundidad</small></span><a href="'+esc(x.url)+'" target="_blank" rel="noopener">Ver</a></div>'; }).join('')+'</div>':'<p class="em-nota">Ninguno en Colombia y alrededores.</p>'); }
	h+='<p class="em-nota">Fuente: USGS y GDACS, recibido '+E.haceTxt(E.alertas.recibido)+(E.alertas.alertas&&E.alertas.alertas.viejo?' (copia anterior: la fuente no respondió)':'')+'. El Servicio Geológico Colombiano no publica sus datos para otras aplicaciones: confirma en sgc.gov.co. Una alerta no crea incidentes por sí sola.</p>';
	return h;
};
E.VIVO.frecuencias = function(){
	var d=E.directorio; if(!d) return '<p class="em-nota">Cargando…</p>';
	var e=d.entradas.filter(function(x){ return x.cat==='actividad_ra'||x.cat==='llamada_ra'; });
	return tablaCompacta(e)+'<p class="em-nota">Centros de actividad de radioaficionados: puntos de reunión, no canales exclusivos. Ver todo en <button type="button" class="em-link" data-ir="directorio">Directorio</button>.</p>';
};
function tablaCompacta(l){
	var notas=[];
	function ref(t){ if(!t) return ''; var i=notas.indexOf(t); if(i<0){ notas.push(t); i=notas.length-1; } return '<sup>'+(i+1)+'</sup>'; }
	var filas=l.map(function(x){ return '<tr><td class="em-f">'+esc(x.f)+' <small>'+esc(x.unidad)+'</small></td><td class="em-nw">'+esc(x.banda)+' · '+esc(x.modo)+'</td><td>'+esc(x.uso)+ref(x.condiciones)+ref(x.fuente+(x.verificado?' Verificado '+x.verificado+'.':' Sin verificar.'))+'</td></tr>'; }).join('');
	return '<div class="em-tabla-c"><table class="em-tabla em-tabla-compacta"><thead><tr><th>Frecuencia</th><th>Banda · modo</th><th>Uso</th></tr></thead><tbody>'+filas+'</tbody></table></div>'
		+'<ol class="em-notas">'+notas.map(function(n){ return '<li>'+esc(n)+'</li>'; }).join('')+'</ol>';
}
function tablaDirectorio(l, editar){
	return '<div class="em-tabla-c"><table class="em-tabla"><thead><tr><th>Frecuencia</th><th>Banda · modo</th><th>Uso</th><th>Fuente y verificación</th>'+(editar?'<th></th>':'')+'</tr></thead><tbody>'+l.map(function(x){
		return '<tr><td class="em-f">'+esc(x.f)+' <small>'+esc(x.unidad)+'</small>'+(x.tono?'<small class="em-bloque">T '+esc(x.tono)+' Hz'+(x.desplazamiento?' · '+esc(x.desplazamiento):'')+'</small>':'')+'</td><td class="em-nw">'+esc(x.banda)+' · '+esc(x.modo)+'</td><td>'+esc(x.uso)+'<small class="em-bloque">'+esc(x.condiciones)+'</small></td><td><small>'+esc(x.fuente)+'<br>'+(x.verificado?'Verificado '+esc(x.verificado):'Sin verificar')+(x.mod?' · cambió '+esc(x.mod.por):'')+'</small></td>'
			+(editar?'<td>'+(x.fijo?'<small>Se edita en Repetidoras</small>':'<button type="button" class="em-btn" data-dir-edit="'+esc(x.id)+'">Editar</button>')+'</td>':'')+'</tr>';
	}).join('')+'</tbody></table></div>';
}

/* ── A. Tablero ──────────────────────────────────────────────────────────── */
function barraFiltros(){
	var f=E.filtros;
	return '<form class="em-filtros-t" id="tb-f" aria-label="Filtros del tablero"><label>Lugar<input class="em-campo" name="lugar" value="'+esc(f.lugar||'')+'" placeholder="Municipio, sitio"></label>'
		+'<label>Incidente<select class="em-campo" name="id">'+E.incidentesOpciones(f.id||'').replace('— Ninguno —','Todos')+'</select></label>'
		+'<label>Prioridad<select class="em-campo" name="prio">'+E.opciones(core.ET.prio, f.prio||'', 'Todas')+'</select></label>'
		+'<label>Estado<select class="em-campo" name="estado">'+E.opciones(Object.assign({}, core.ET.estado_inc, core.ET.verif), f.estado||'', 'Todos')+'</select></label>'
		+'<label>Organización<input class="em-campo" name="org" value="'+esc(f.org||'')+'"></label>'
		+'<label>Periodo<select class="em-campo" name="horas">'+E.opciones({1:'Última hora', 6:'6 horas', 24:'24 horas', 72:'3 días'}, String(f.horas||''), 'Todo')+'</select></label>'
		+'<button type="button" class="em-btn" id="tb-limpiar">Limpiar</button></form>';
}
function act(ts){ return ts ? '<small class="em-act">act. '+E.horaLocal(ts)+'</small>' : '<small class="em-act">sin datos</small>'; }
E.VIVO.cifras = function(){
	var I=core.indicadores(E.S, E.filtros, Date.now(), E.ventana), i=I.incidentes;
	function caja(irA, n, t, sub, c, ts, cal){ return '<button type="button" class="em-cifra '+(c||'')+'" data-ir="'+irA+'"><strong>'+n+'</strong><span>'+t+'</span><small>'+sub+'</small>'+act(ts)+(cal?' '+cal:'')+'</button>'; }
	return caja('incidentes', i.activos, 'Incidentes activos', 'Crítica '+i.porPrio.critica+' · alta '+i.porPrio.alta+' · media '+i.porPrio.media+' · baja '+i.porPrio.baja+'<br>'+i.porVerif.verificado+' verificados · '+(i.porVerif.reportado+i.porVerif.en_verificacion)+' sin verificar', i.porPrio.critica?'peligro':'', i.ultima)
		+caja('recursos', I.recursos.disponibles, 'Recursos disponibles', I.recursos.asignados+' asignados o en uso · '+I.recursos.fuera+' fuera de servicio', I.recursos.fuera?'aviso':'', I.recursos.ultima)
		+caja('estaciones', I.estaciones.verificadas+'/'+I.estaciones.registradas, 'Estaciones verificadas', I.estaciones.reportadas+' solo reportadas · '+I.estaciones.desconocidas+' sin datos vigentes ('+E.ventana+' min)', I.estaciones.desconocidas?'aviso':'', I.estaciones.ultima)
		+caja('comunicaciones', I.mensajes.porEntregar, 'Mensajes por entregar', I.mensajes.emergencia+' de emergencia · '+I.mensajes.porConfirmar+' entregados sin confirmar', I.mensajes.emergencia?'peligro':'', I.mensajes.ultima);
};
function atencion(){
	var I=core.indicadores(E.S, E.filtros, Date.now(), E.ventana), l=[];
	Object.keys(E.S.est).forEach(function(k){ var x=E.S.est[k]; if(x.situacion && x.situacion.e==='urgente') l.push({o:0, c:'peligro', t:x.ind+' reporta URGENCIA'+(x.situacion.nota?': '+x.situacion.nota:''), ir:'estaciones', id:x.ind}); });
	Object.keys(E.S.msgs).forEach(function(k){ var m=E.S.msgs[k]; if(!core.MSG_FINAL[m.estado] && m.d.prec==='E') l.push({o:1, c:'peligro', t:'Mensaje de EMERGENCIA n.º '+(m.d.num||'')+' para '+m.d.para+': '+et('msg_est',m.estado), ir:'comunicaciones', id:m.id}); });
	core.filtrarIncidentes(E.S, E.filtros).filter(core.incidenteActivo).forEach(function(x){ if(x.prio==='critica' && x.verif!=='verificado') l.push({o:2, c:'peligro', t:'Incidente crítico sin verificar: '+x.titulo, ir:'incidentes', id:x.id}); });
	if(I.duplicados) l.push({o:3, c:'aviso', t:I.duplicados+' posibles duplicados por revisar', ir:'incidentes', filtro:'duplicados'});
	if(I.conflictos) l.push({o:3, c:'aviso', t:I.conflictos+' cambios simultáneos sin resolver', ir:'informes', filtro:'calidad'});
	I.solicitudes.filter(function(s){ return s.estado==='pendiente'; }).forEach(function(s){ l.push({o:4, c:'aviso', t:'Solicitud pendiente: '+s.cant+' × '+et('rec_tipo',s.tipo)+' ('+et('urgencia',s.urgencia)+')', ir:'recursos', filtro:'solicitudes'}); });
	I.acciones.filter(function(a){ return a.vence && a.vence<Date.now(); }).forEach(function(a){ l.push({o:4, c:'aviso', t:'Acción vencida: '+a.texto, ir:'incidentes', id:a.inc}); });
	I.vencidos.forEach(function(v){ l.push({o:5, c:'apagado', t:'Dato vencido · '+v.texto, ir:v.tipo==='estacion'?'estaciones':v.tipo==='incidente'?'incidentes':'plan', id:v.tipo==='incidente'?v.id:''}); });
	var r=E.rechazados().length; if(r) l.push({o:1, c:'peligro', t:r+' registros rechazados por el servidor', ir:'campo'});
	return l.sort(function(a,b){ return a.o-b.o; });
}
E.VIVO.atencion = function(){
	var l=atencion();
	return l.length ? '<div class="em-lista">'+l.slice(0,12).map(function(x){ return '<button type="button" class="em-fila em-fila-btn em-borde-'+x.c+'" data-ir="'+x.ir+'"'+(x.id?' data-id="'+esc(x.id)+'"':'')+(x.filtro?' data-filtro="'+x.filtro+'"':'')+'><span>'+esc(x.t)+'</span><span aria-hidden="true">→</span></button>'; }).join('')+(l.length>12?'<p class="em-nota">Y '+(l.length-12)+' más.</p>':'')+'</div>' : '<p class="em-ok-grande">Nada pendiente.</p>';
};
E.VIVO.recientes = function(){
	var I=core.indicadores(E.S, E.filtros, Date.now(), E.ventana);
	return '<h3>Últimos reportes</h3>'+(I.reportes.length?'<ol class="em-bit">'+I.reportes.map(E.filaBit).join('')+'</ol>':'<p class="em-nota">Ninguno.</p>')
		+'<h3>Últimas decisiones</h3>'+(I.decisiones.length?'<ol class="em-bit">'+I.decisiones.map(E.filaBit).join('')+'</ol>':'<p class="em-nota">Ninguna registrada.</p>');
};
E.VIVO.enlacesTab = function(){
	var I=core.indicadores(E.S, E.filtros, Date.now(), E.ventana);
	if(!I.enlaces.length) return '<p class="em-nota">Sin reportes de repetidoras ni pruebas de enlace. Repórtalos en Estaciones.</p>';
	return '<div class="em-lista">'+I.enlaces.slice(0,8).map(function(x){
		if(x.r) return '<div class="em-fila"><span><strong>'+esc(x.nombre)+'</strong> '+(x.s.e==='desconocido'?E.pill('apagado','Desconocido'):E.pill(x.r.e==='caida'?'peligro':x.r.e==='aire'?'ok':'aviso', et('repe_e',x.r.e)))+' '+E.calidad(x.s.calidad)+'<small>'+(x.s.e==='desconocido'?'último: '+esc(et('repe_e',x.r.e))+' · ':'')+'según '+esc(x.r.a)+' '+E.haceTxt(x.r.ts)+'</small></span></div>';
		var l=x.enlace; return '<div class="em-fila"><span><strong>'+esc(x.nombre)+'</strong> '+E.pill(l.resultado==='fallo'?'peligro':l.resultado==='debil'?'aviso':'ok', {ok:'Buena', debil:'Débil', fallo:'Falló'}[l.resultado])+'<small>prueba de '+esc(l.autor)+' '+E.haceTxt(l.ts)+'</small></span></div>';
	}).join('')+'</div>';
};
E.VIVO.tareas = function(){
	var I=core.indicadores(E.S, E.filtros, Date.now(), E.ventana);
	if(!I.acciones.length && !I.solicitudes.length) return '<p class="em-nota">No hay tareas ni solicitudes abiertas.</p>';
	return '<div class="em-lista">'+I.acciones.map(function(a){ return '<div class="em-fila"><span>'+esc(a.texto)+'<small>'+(a.resp?esc(a.resp)+' · ':'')+(a.vence?'vence '+E.horaLocal(a.vence)+' · ':'')+'registró '+esc(a.a)+'</small></span>'+(E.puede('operar')&&!cerrada()?'<button type="button" class="em-btn" data-accion-ok="'+a.id+'">Hecha</button>':'')+'</div>'; }).join('')
		+I.solicitudes.map(function(s){ return '<button type="button" class="em-fila em-fila-btn" data-ir="recursos" data-filtro="solicitudes"><span>Solicitud: '+s.cant+' × '+esc(et('rec_tipo',s.tipo))+' — '+esc(s.desc)+'<small>'+esc(et('sol_est',s.estado))+' · '+esc(et('urgencia',s.urgencia))+'</small></span></button>'; }).join('')+'</div>';
};
document.addEventListener('click', function(e){
	var b=e.target.closest('[data-accion-ok]'); if(!b) return;
	E.modal('Marcar acción como hecha', '<form class="em-form" id="f-aok"><label>Resultado (opcional)<input class="em-campo" name="nota"></label><button class="em-btn primario">Marcar hecha</button></form>', function(c){
		$('#f-aok',c).addEventListener('submit', function(ev){ ev.preventDefault(); if(E.registrar('accion_ok', {a:b.dataset.accionOk, nota:this.nota.value})) E.cerrarModal(); });
	});
});
E.registrarVista('tablero', {nombre:'Indicadores', icono:'▦', fn:function(){
	var puede=E.puede('operar') && !cerrada();
	var v=E.el('<div class="em-tablero">'
		+'<div class="em-barra"><h1 class="sr-only">Tablero</h1>'+(puede?'<div class="em-acciones">'+boton('tb-inc','+ Incidente','primario')+boton('tb-msg','+ Mensaje','')+boton('tb-est','+ Estación','')+boton('tb-lista','Pasar lista','')+'</div>':'<p class="em-nota">'+(cerrada()?'Operación cerrada: solo consulta.':'Tu rol es de consulta.')+'</p>')+'</div>'
		+'<details class="em-filtros-d"'+(Object.keys(E.filtros).some(function(k){ return E.filtros[k]; })?' open':'')+'><summary>Filtros</summary>'+barraFiltros()+'</details>'
		+'<section class="em-resumen" data-vivo="cifras" aria-label="Indicadores">'+E.VIVO.cifras()+'</section>'
		+'<div class="em-tablero-g">'
		+'<section class="em-tarjeta em-atencion"><h2>Atención ahora</h2><div data-vivo="atencion">'+E.VIVO.atencion()+'</div></section>'
		+'<section class="em-tarjeta em-tablero-mapa em-solo-ancho-b"><h2>Mapa <button type="button" class="em-link" data-ir="mapa">abrir →</button></h2><div id="tb-mapa" class="em-mapa-mini" role="region" aria-label="Mapa de la operación"></div></section>'
		+'<section class="em-tarjeta"><h2>Actividad reciente</h2><div data-vivo="recientes">'+E.VIVO.recientes()+'</div><button type="button" class="em-link" data-ir="bitacora">Toda la bitácora →</button></section>'
		+'<section class="em-tarjeta"><h2>Enlaces y repetidoras</h2><div data-vivo="enlacesTab">'+E.VIVO.enlacesTab()+'</div></section>'
		+'<section class="em-tarjeta"><h2>Tareas y solicitudes</h2><div data-vivo="tareas">'+E.VIVO.tareas()+'</div></section>'
		+'<section class="em-tarjeta"><h2>Propagación y alertas</h2><div data-vivo="alertas">'+E.VIVO.alertas()+'</div></section>'
		+'</div></div>');
	var f=$('#tb-f',v);
	f.addEventListener('input', function(){ var d={}; $$('input,select',f).forEach(function(i){ if(i.value) d[i.name]=i.name==='horas'?+i.value:i.value; }); E.filtros=d; E.refrescar(); });
	$('#tb-limpiar',v).addEventListener('click', function(){ E.filtros={}; E.ir('tablero'); });
	if(puede){ $('#tb-inc',v).addEventListener('click', function(){ E.formIncidente(); }); $('#tb-msg',v).addEventListener('click', function(){ E.formMensaje(); }); $('#tb-est',v).addEventListener('click', function(){ E.formEstacion(); }); $('#tb-lista',v).addEventListener('click', E.pasarLista); }
	return v;
}, alMontar:function(){ var m=$('#tb-mapa'); if(m && E.mapa && m.offsetParent) E.mapa.montar(m, {mini:true}); }});

/* ── C. Incidentes ───────────────────────────────────────────────────────── */
E.formIncidente = function(pre){
	pre=pre||{};
	if(!E.puede('operar') || cerrada()){ E.aviso('Tu rol o el estado de la operación no permiten registrar.', 'aviso'); return; }
	E.modal(pre.id?'Actualizar incidente':'Nuevo incidente', '<form class="em-form" id="f-inc" novalidate>'
		+'<div class="em-dos"><label>Tipo de evento<select class="em-campo" name="tipo" required>'+E.opciones(core.ET.tipo_inc, pre.tipo||'', 'Elige…')+'</select></label><label>Título corto<input class="em-campo" name="titulo" required maxlength="120" value="'+esc(pre.titulo||'')+'" placeholder="Ej.: Deslizamiento sobre la vía a Santa Rosa"></label></div>'
		+'<fieldset><legend>Prioridad del reporte <small>(la de despacho la decide la autoridad competente)</small></legend>'+E.chips('prio', core.ET.prio, pre.prio||'media', CLASE_PRIO)+'</fieldset>'
		+'<label>Por qué esa prioridad<input class="em-campo" name="prio_just" maxlength="300" placeholder="Ej.: personas atrapadas, vía única"></label>'
		+'<div class="em-dos"><label>Fuente del reporte<input class="em-campo" name="fuente" required maxlength="160" value="'+esc(pre.fuente||'')+'" placeholder="Quién lo reportó y por dónde: HK6ABC por 147.120"></label><label>Tipo de fuente<select class="em-campo" name="fuente_tipo">'+E.opciones(core.ET.fuente_tipo, pre.fuente_tipo||'operador_radio')+'</select></label></div>'
		+'<div class="em-dos"><label>Lugar<input class="em-campo" name="lugar" maxlength="160" value="'+esc(pre.lugar||'')+'" placeholder="Municipio, vereda, dirección"></label><label>Precisión de la ubicación<select class="em-campo" name="precision">'+E.opciones(core.ET.precision, pre.precision||(pre.lat!==undefined?'aproximada':'desconocida'))+'</select></label></div>'
		+'<label>Coordenadas (opcional)<span class="em-con-boton"><input class="em-campo em-mono" name="pos" value="'+(pre.lat!==undefined?pre.lat.toFixed(5)+', '+pre.lng.toFixed(5):'')+'" placeholder="4.8133, -75.6961 · 4°48\'N 75°41\'W · FJ24ep"><button type="button" class="em-btn" id="f-inc-gps" title="Usar el GPS de este dispositivo (solo si estás en el lugar)">📍</button></span><small id="f-inc-pos"></small></label>'
		+'<label>Fecha y hora en que ocurrió (si se conoce)<input class="em-campo" type="datetime-local" name="ocurrio"></label>'
		+'<label>Descripción<textarea class="em-campo" name="desc" rows="3" maxlength="2000">'+esc(pre.desc||'')+'</textarea></label>'
		+'<label>Necesidades<textarea class="em-campo" name="necesidades" rows="2" maxlength="1000"></textarea></label>'
		+'<div class="em-dos"><label>Personas afectadas <small>(solo si la fuente lo dio)</small><input class="em-campo" name="afectados" maxlength="300" placeholder="Tal como lo reportó la fuente"></label><label>Organizaciones participantes<input class="em-campo" name="orgs" maxlength="300"></label></div>'
		+'<label>Riesgos identificados<textarea class="em-campo" name="riesgos" rows="2" maxlength="1000"></textarea></label>'
		+(pre.sim?'<input type="hidden" name="sim" value="'+esc(pre.sim)+'">':'')
		+'<p class="em-nota">Queda como «Reportado». Solo coordinación puede marcarlo como verificado o descartado.</p><button class="em-btn primario em-grande">Registrar incidente</button><p class="em-error" id="f-inc-err" role="alert"></p></form>', function(c){
		var f=$('#f-inc',c);
		function leerPos(){ var p=core.parsearPosicion(f.pos.value); $('#f-inc-pos',c).textContent=f.pos.value&&!p?'No se entiende esa posición.':p?'Locator '+core.locator(p.lat,p.lng):''; if(p && f.precision.value==='desconocida') f.precision.value='aproximada'; return p; }
		f.pos.addEventListener('input', leerPos); leerPos();
		$('#f-inc-gps',c).addEventListener('click', function(){ gps(function(p){ f.pos.value=p.lat.toFixed(5)+', '+p.lng.toFixed(5); f.precision.value=p.acc<=100?'exacta':'aproximada'; leerPos(); }); });
		f.addEventListener('submit', function(e){
			e.preventDefault(); var err=$('#f-inc-err',c);
			if(!f.tipo.value){ err.textContent='Elige el tipo de evento.'; f.tipo.focus(); return; }
			if(f.titulo.value.trim().length<3){ err.textContent='Escribe un título.'; f.titulo.focus(); return; }
			if(!f.fuente.value.trim()){ err.textContent='Indica la fuente del reporte: sin fuente no se puede verificar.'; f.fuente.focus(); return; }
			var p=leerPos(); if(f.pos.value && !p){ err.textContent='Revisa las coordenadas.'; f.pos.focus(); return; }
			var d={tipo:f.tipo.value, titulo:f.titulo.value, prio:E.valorDe(f,'prio'), prio_just:f.prio_just.value, fuente:f.fuente.value, fuente_tipo:f.fuente_tipo.value, lugar:f.lugar.value, precision:p?f.precision.value:(f.precision.value==='exacta'||f.precision.value==='aproximada'?'desconocida':f.precision.value),
				ocurrio:f.ocurrio.value?new Date(f.ocurrio.value).getTime():undefined, desc:f.desc.value, necesidades:f.necesidades.value, afectados:f.afectados.value, orgs:f.orgs.value, riesgos:f.riesgos.value};
			if(p){ d.lat=+p.lat.toFixed(6); d.lng=+p.lng.toFixed(6); }
			if(f.sim) d.sim=f.sim.value;
			var ev=E.registrar('i_nuevo', d); if(!ev) return;
			E.cerrarModal();
			var dup=core.duplicados(E.S).filter(function(x){ return x.a===ev.u||x.b===ev.u; });
			E.aviso(dup.length?'Registrado. Atención: parece duplicado de otro incidente — revísalo.':'Incidente registrado.', dup.length?'aviso':'');
			E.ir('incidentes', {id:ev.u});
		});
	});
};
function gps(ok){
	if(!navigator.geolocation){ E.aviso('Este dispositivo no tiene GPS disponible.', 'aviso'); return; }
	E.aviso('Leyendo el GPS…');
	navigator.geolocation.getCurrentPosition(function(p){ ok({lat:p.coords.latitude, lng:p.coords.longitude, acc:p.coords.accuracy}); }, function(){ E.aviso('No se pudo leer el GPS. Revisa el permiso de ubicación.', 'aviso'); }, {enableHighAccuracy:true, timeout:20000});
}
E.gps=gps;
var filtroInc='activos';
E.VIVO.duplicados = function(){
	var d=core.duplicados(E.S); if(!d.length) return '';
	return '<section class="em-tarjeta em-borde-aviso"><h2>Posibles duplicados ('+d.length+')</h2><p class="em-nota">Detectados por cercanía, hora, tipo y texto. Nada se fusiona solo: decide una persona.</p><div class="em-lista">'+d.slice(0,8).map(function(p){
		var a=E.S.inc[p.a], b=E.S.inc[p.b];
		return '<div class="em-fila em-dup"><span><strong>«'+esc(a.titulo)+'»</strong> y <strong>«'+esc(b.titulo)+'»</strong><small>'+esc(p.motivos.join(', '))+' · coincidencia '+Math.round(p.puntaje*100)+' %</small></span><span class="em-acciones">'
			+(E.puede('coordinar')&&!cerrada()?'<button type="button" class="em-btn" data-fusion="'+p.b+'" data-en="'+p.a+'">Fusionar</button>':'')
			+(E.puede('operar')&&!cerrada()?'<button type="button" class="em-btn" data-nodup="'+p.a+'" data-b="'+p.b+'">No son el mismo</button>':'')+'</span></div>';
	}).join('')+'</div></section>';
};
E.VIVO.incidentes = function(){
	var l=Object.keys(E.S.inc).map(function(k){ return E.S.inc[k]; });
	if(filtroInc==='activos') l=l.filter(core.incidenteActivo);
	else if(filtroInc==='verificar') l=l.filter(function(x){ return core.incidenteActivo(x) && x.verif!=='verificado'; });
	else if(filtroInc==='cerrados') l=l.filter(function(x){ return !core.incidenteActivo(x); });
	l.sort(function(a,b){ return core.ORDEN_PRIO[a.prio]-core.ORDEN_PRIO[b.prio] || b.act-a.act; });
	if(!l.length) return '<p class="em-nota">No hay incidentes en esta lista.</p>';
	return '<div class="em-lista">'+l.map(function(x){
		return '<button type="button" class="em-inc em-borde-'+CLASE_PRIO[x.prio]+(x.fusionadoEn?' em-tenue':'')+'" data-inc="'+x.id+'"><span class="em-inc-cab">'+prio(x)+' '+verif(x.verif)+' '+E.pill('apagado', et('estado_inc',x.estado))+(x.desp?' '+E.pill('peligro','Despacho: '+x.desp.prio):'')+(x.fusionadoEn?' '+E.pill('apagado','Fusionado'):'')+(x.sim?' '+E.pill('aviso','SIM'):'')+'</span>'
			+'<strong>'+esc(x.titulo)+'</strong><small>'+esc(et('tipo_inc',x.tipo))+' · '+esc(x.lugar||et('precision',x.precision))+(x.lat===undefined?' · sin coordenadas':'')+' · recibido '+core.horaZ(x.recibido)+' · act. '+E.haceTxt(x.act)+' · fuente: '+esc(x.fuente)+'</small></button>';
	}).join('')+'</div>';
};
function verIncidente(id){
	var x=E.S.inc[id]; if(!x){ E.aviso('No se encontró ese incidente.', 'aviso'); return null; }
	var op=E.puede('operar')&&!cerrada(), co=E.puede('coordinar')&&!cerrada();
	var acciones=x.acciones.map(function(a){ return E.S.acciones[a]; }).filter(Boolean);
	var msgs=x.vinc.msg.map(function(m){ return E.S.msgs[m]; }).filter(Boolean), recs=x.vinc.rec.map(function(r){ return E.S.rec[r]; }).filter(Boolean), sols=x.vinc.sol.map(function(s){ return E.S.sol[s]; }).filter(Boolean);
	var v=E.el('<div class="em-detalle"><div class="em-barra"><button type="button" class="em-link" data-ir="incidentes">← Incidentes</button></div>'
		+'<div class="em-inc-cab">'+prio(x)+' '+verif(x.verif)+' '+E.pill('apagado', et('estado_inc',x.estado))+(x.sim?' '+E.pill('aviso','SIMULACIÓN'):'')+'</div><h1>'+esc(x.titulo)+'</h1>'
		+(x.fusionadoEn?'<p class="em-aviso">Este incidente se fusionó como duplicado de <button type="button" class="em-link" data-inc="'+x.fusionadoEn+'">«'+esc(E.S.inc[x.fusionadoEn].titulo)+'»</button>.</p>':'')
		+'<div class="em-grid2"><section class="em-tarjeta"><h2>Datos</h2><dl class="em-dl">'
		+'<dt>Identificador</dt><dd class="em-mono">'+esc(x.id.slice(0,8))+'</dd><dt>Tipo</dt><dd>'+esc(et('tipo_inc',x.tipo))+'</dd>'
		+'<dt>Ocurrió</dt><dd>'+(x.ocurrio?core.isoZ(x.ocurrio):'No se sabe')+'</dd><dt>Recibido</dt><dd>'+core.isoZ(x.recibido)+' por '+esc(x.autor)+'</dd>'
		+'<dt>Ubicación</dt><dd>'+esc(x.lugar||'—')+' · '+esc(et('precision',x.precision))+(x.lat!==undefined?'<br><span class="em-mono">'+x.lat.toFixed(5)+', '+x.lng.toFixed(5)+' · '+core.locator(x.lat,x.lng)+'</span> <button type="button" class="em-link" data-ir="mapa" data-id="'+x.id+'">ver en el mapa</button>':'')+'</dd>'
		+'<dt>Fuente</dt><dd>'+esc(x.fuente)+' ('+esc(et('fuente_tipo',x.fuenteTipo))+')</dd>'
		+'<dt>Prioridad del reporte</dt><dd>'+esc(et('prio',x.prio))+(x.prioJust?' — '+esc(x.prioJust):'')+'</dd>'
		+'<dt>Prioridad de despacho</dt><dd>'+(x.desp?esc(x.desp.prio)+' · decidió '+esc(x.desp.autoridad)+' ('+core.horaZ(x.desp.ts)+')':'Sin registrar (la decide la autoridad competente)')+'</dd>'
		+'<dt>Responsable operativo</dt><dd>'+(x.resp?esc(x.resp)+(x.respOrg?' ('+esc(x.respOrg)+')':''):'Sin asignar')+'</dd>'
		+'<dt>Organizaciones</dt><dd>'+esc(x.orgs||'—')+'</dd><dt>Descripción</dt><dd>'+esc(x.desc||'—')+'</dd><dt>Necesidades</dt><dd>'+esc(x.necesidades||'—')+'</dd>'
		+'<dt>Personas afectadas</dt><dd>'+(x.afectados?esc(x.afectados)+' <small>(según la fuente'+(x.verif==='verificado'?'':', sin verificar')+')</small>':'Sin información')+'</dd><dt>Riesgos</dt><dd>'+esc(x.riesgos||'—')+'</dd></dl></section>'
		+(x.lat!==undefined?'<section class="em-tarjeta"><h2>Lluvia en la zona</h2><div data-lluvia="'+x.id+'"><button type="button" class="em-btn" data-lluvia-cargar="'+x.id+'">Consultar lluvia (24 h atrás y 48 h adelante)</button></div></section>':'')
		+'<section class="em-tarjeta"><h2>Acciones</h2><div class="em-acciones">'
		+(op?'<button type="button" class="em-btn primario" data-ia="situacion">Actualizar situación</button>':'')
		+(op&&x.verif==='reportado'?'<button type="button" class="em-btn" data-ia="enverif">Poner en verificación</button>':'')
		+(co&&x.verif!=='verificado'?'<button type="button" class="em-btn ok" data-ia="verificar">Verificar</button>':'')
		+(co&&x.verif!=='descartado'?'<button type="button" class="em-btn" data-ia="descartar">Descartar</button>':'')
		+(op&&x.estado==='abierto'?'<button type="button" class="em-btn" data-ia="atencion">En atención</button>':'')
		+(co&&x.estado!=='resuelto'&&x.estado!=='cerrado'?'<button type="button" class="em-btn" data-ia="resolver">Resolver</button>':'')
		+(co&&x.estado!=='cerrado'?'<button type="button" class="em-btn" data-ia="cerrar">Cerrar</button>':'')
		+(co&&(x.estado==='cerrado'||x.estado==='resuelto')?'<button type="button" class="em-btn" data-ia="reabrir">Reabrir</button>':'')
		+(op?'<button type="button" class="em-btn" data-ia="prio">Cambiar prioridad del reporte</button>':'')
		+(co?'<button type="button" class="em-btn" data-ia="desp">Registrar prioridad de despacho</button>':'')
		+(co?'<button type="button" class="em-btn" data-ia="resp">Asignar responsable</button>':'')
		+(co?'<button type="button" class="em-btn" data-ia="decision">Registrar decisión</button>':'')
		+(op?'<button type="button" class="em-btn" data-ia="accion">+ Acción pendiente</button>':'')
		+(op?'<button type="button" class="em-btn" data-ia="msg">+ Mensaje vinculado</button>':'')
		+(op?'<button type="button" class="em-btn" data-ia="vincular">Vincular mensaje o recurso</button>':'')
		+(op?'<button type="button" class="em-btn" data-ia="solicitar">Solicitar recurso</button>':'')
		+'<button type="button" class="em-btn" data-ia="cap">Exportar CAP</button></div>'
		+(!op?'<p class="em-nota">'+(cerrada()?'Operación cerrada.':'Tu rol es de consulta.')+'</p>':'')
		+'<h3>Acciones pendientes</h3>'+(acciones.length?'<ul class="em-lista-txt">'+acciones.map(function(a){ return '<li>'+(a.hecha?'<s>':'')+esc(a.texto)+(a.hecha?'</s> <small>hecha por '+esc(a.hecha.a)+'</small>':(op?' <button type="button" class="em-link" data-accion-ok="'+a.id+'">marcar hecha</button>':''))+(a.resp?' <small>· '+esc(a.resp)+'</small>':'')+'</li>'; }).join('')+'</ul>':'<p class="em-nota">Ninguna.</p>')
		+'<h3>Mensajes vinculados</h3>'+(msgs.length?'<ul class="em-lista-txt">'+msgs.map(function(m){ return '<li><button type="button" class="em-link" data-msg="'+m.id+'">N.º '+esc(m.d.num||'')+' para '+esc(m.d.para)+'</button> · '+esc(et('msg_est',m.estado))+'</li>'; }).join('')+'</ul>':'<p class="em-nota">Ninguno.</p>')
		+'<h3>Recursos</h3>'+(recs.length||sols.length?'<ul class="em-lista-txt">'+recs.map(function(r){ return '<li>'+esc(r.desc)+' · '+esc(et('rec_est',r.estado))+'</li>'; }).join('')+sols.map(function(s){ return '<li>Solicitud: '+s.cant+' × '+esc(et('rec_tipo',s.tipo))+' — '+esc(et('sol_est',s.estado))+'</li>'; }).join('')+'</ul>':'<p class="em-nota">Ninguno.</p>')
		+'</section></div>'
		+'<section class="em-tarjeta"><h2>Historial</h2><ol class="em-bit">'+x.hist.slice().reverse().map(E.filaBit).join('')+'</ol></section></div>');
	v.addEventListener('click', function(e){
		var b=e.target.closest('[data-ia]'); if(!b) return;
		var a=b.dataset.ia, base={id:x.id};
		function simple(titulo, campos, tipo, datos){
			E.modal(titulo, '<form class="em-form" id="f-ia">'+campos+'<button class="em-btn primario">Guardar</button></form>', function(c){
				$('#f-ia',c).addEventListener('submit', function(ev){ ev.preventDefault(); var d=datos(this); if(!d) return; if(E.registrar(tipo, Object.assign({}, base, d))){ E.cerrarModal(); E.ir('incidentes', {id:x.id}); } });
			});
		}
		if(a==='situacion') simple('Actualizar situación', '<label>Situación actual<textarea class="em-campo" name="situacion" rows="3" required></textarea></label><label>Necesidades (si cambiaron)<input class="em-campo" name="necesidades" value="'+esc(x.necesidades)+'"></label><label>Personas afectadas, según la fuente<input class="em-campo" name="afectados" value="'+esc(x.afectados)+'"></label><label>Riesgos<input class="em-campo" name="riesgos" value="'+esc(x.riesgos)+'"></label>', 'i_act', function(f){ if(!f.situacion.value.trim()) return null; var d={situacion:f.situacion.value}; if(f.necesidades.value!==x.necesidades) d.necesidades=f.necesidades.value; if(f.afectados.value!==x.afectados) d.afectados=f.afectados.value; if(f.riesgos.value!==x.riesgos) d.riesgos=f.riesgos.value; return d; });
		else if(a==='enverif') { if(E.registrar('i_verif', {id:x.id, v:'en_verificacion'})) E.ir('incidentes', {id:x.id}); }
		else if(a==='verificar') simple('Verificar incidente', '<p>Confirma que hay evidencia suficiente (otra fuente, autoridad, observación directa).</p><label>Cómo se verificó<input class="em-campo" name="nota" required placeholder="Ej.: confirmado por bomberos en el sitio"></label>', 'i_verif', function(f){ return f.nota.value.trim()?{v:'verificado', nota:f.nota.value}:null; });
		else if(a==='descartar') simple('Descartar incidente', '<label>Motivo<input class="em-campo" name="nota" required placeholder="Ej.: falsa alarma confirmada"></label>', 'i_verif', function(f){ return f.nota.value.trim()?{v:'descartado', nota:f.nota.value}:null; });
		else if(a==='atencion') { if(E.registrar('i_estado', {id:x.id, e:'en_atencion'})) E.ir('incidentes', {id:x.id}); }
		else if(a==='resolver'||a==='cerrar'||a==='reabrir') simple(a==='reabrir'?'Reabrir':a==='resolver'?'Resolver incidente':'Cerrar incidente', '<label>Nota<input class="em-campo" name="nota"'+(a==='reabrir'?'':' required')+'></label>', 'i_estado', function(f){ return {e:a==='reabrir'?'abierto':a==='resolver'?'resuelto':'cerrado', nota:f.nota.value}; });
		else if(a==='prio') simple('Prioridad del reporte', E.chips('prio', core.ET.prio, x.prio, CLASE_PRIO)+'<label>Justificación<input class="em-campo" name="just" required></label>', 'i_prio', function(f){ return f.just.value.trim()?{prio:E.valorDe(f,'prio'), just:f.just.value}:null; });
		else if(a==='desp') simple('Prioridad de despacho', '<p class="em-nota">Registra la prioridad que asignó la autoridad competente (no la decide esta plataforma).</p><label>Prioridad asignada<input class="em-campo" name="prio" required placeholder="Ej.: P1, inmediata"></label><label>Quién la decidió<input class="em-campo" name="autoridad" required placeholder="Ej.: CMGRD Pereira"></label><label>Nota<input class="em-campo" name="nota"></label>', 'i_desp', function(f){ return {prio:f.prio.value, autoridad:f.autoridad.value, nota:f.nota.value}; });
		else if(a==='resp') simple('Responsable operativo', '<label>Responsable<input class="em-campo" name="resp" required value="'+esc(x.resp)+'"></label><label>Organización<input class="em-campo" name="org" value="'+esc(x.respOrg)+'"></label>', 'i_resp', function(f){ return {resp:f.resp.value, org:f.org.value}; });
		else if(a==='decision') simple('Registrar decisión', '<label>Decisión<textarea class="em-campo" name="texto" rows="3" required></textarea></label><label>Quién decidió<input class="em-campo" name="autoridad"></label>', 'decision', function(f){ return f.texto.value.trim()?{texto:f.texto.value, autoridad:f.autoridad.value}:null; });
		else if(a==='accion') simple('Acción pendiente', '<label>Qué hay que hacer<input class="em-campo" name="texto" required></label><div class="em-dos"><label>Responsable<input class="em-campo" name="resp"></label><label>Vence<input class="em-campo" type="datetime-local" name="vence"></label></div>', 'accion', function(f){ return f.texto.value.trim()?{texto:f.texto.value, resp:f.resp.value, vence:f.vence.value?new Date(f.vence.value).getTime():undefined}:null; });
		else if(a==='msg') E.formMensaje({inc:x.id});
		else if(a==='solicitar') E.formSolicitud({inc:x.id});
		else if(a==='vincular'){
			var opsM=Object.keys(E.S.msgs).filter(function(k){ return x.vinc.msg.indexOf(k)<0; }).map(function(k){ var m=E.S.msgs[k]; return '<option value="msg:'+k+'">Mensaje n.º '+esc(m.d.num||'')+' para '+esc(m.d.para)+'</option>'; });
			var opsR=Object.keys(E.S.rec).filter(function(k){ return x.vinc.rec.indexOf(k)<0; }).map(function(k){ return '<option value="rec:'+k+'">Recurso: '+esc(E.S.rec[k].desc)+'</option>'; });
			if(!opsM.length && !opsR.length){ E.aviso('No hay mensajes ni recursos sin vincular.', 'aviso'); return; }
			simple('Vincular', '<label>Qué vincular<select class="em-campo" name="ref">'+opsM.concat(opsR).join('')+'</select></label>', 'i_vinc', function(f){ var p=f.ref.value.split(':'); return {tipo:p[0], ref:p[1]}; });
		}
		else if(a==='cap') E.descargar('incidente-'+x.id.slice(0,8)+'.cap.xml', core.cap(x, E.op, location.host), 'application/xml');
	});
	return v;
}
E.registrarVista('incidentes', {nombre:'Incidentes', icono:'⚠', fn:function(p){
	if(p.id){ var d=verIncidente(p.id); if(d) return d; }
	if(p.filtro==='duplicados') filtroInc='activos';
	var v=E.el('<div><div class="em-barra"><h1>Incidentes</h1>'+(E.puede('operar')&&!cerrada()?boton('in-nuevo','+ Incidente','primario'):'')+'</div>'
		+'<div data-vivo="duplicados">'+E.VIVO.duplicados()+'</div>'
		+'<div class="em-barra em-filtros"><div class="em-seg" role="group" aria-label="Filtro">'+[['activos','Activos'],['verificar','Por verificar'],['cerrados','Resueltos, cerrados o descartados'],['todos','Todos']].map(function(o){ return '<button type="button" data-f="'+o[0]+'" aria-pressed="'+(filtroInc===o[0])+'" class="'+(filtroInc===o[0]?'is-on':'')+'">'+o[1]+'</button>'; }).join('')+'</div></div>'
		+'<div data-vivo="incidentes">'+E.VIVO.incidentes()+'</div></div>');
	var n=$('#in-nuevo',v); if(n) n.addEventListener('click', function(){ E.formIncidente(); });
	$$('.em-seg button',v).forEach(function(b){ b.addEventListener('click', function(){ filtroInc=b.dataset.f; $$('.em-seg button',v).forEach(function(x){ x.classList.toggle('is-on', x===b); x.setAttribute('aria-pressed', x===b); }); E.refrescar(); }); });
	return v;
}});
document.addEventListener('click', function(e){
	var i=e.target.closest('[data-inc]'); if(i){ E.cerrarModal(); E.ir('incidentes', {id:i.dataset.inc}); return; }
	var f=e.target.closest('[data-fusion]');
	if(f){ var de=E.S.inc[f.dataset.fusion], en=E.S.inc[f.dataset.en];
		E.modal('Fusionar incidentes', '<form class="em-form" id="f-fus"><p>Elige cuál queda como principal. El otro se marca como duplicado; nada se borra y su historial se conserva.</p>'+E.chips('en', (function(){ var o={}; o[en.id]='Queda «'+en.titulo+'»'; o[de.id]='Queda «'+de.titulo+'»'; return o; })(), en.id)+'<label>Motivo<input class="em-campo" name="nota" required placeholder="Ej.: misma vía, mismo deslizamiento"></label><button class="em-btn peligro">Fusionar</button></form>', function(c){
			$('#f-fus',c).addEventListener('submit', function(ev){ ev.preventDefault(); if(!this.nota.value.trim()) return; var queda=E.valorDe(this,'en'), sale=queda===en.id?de.id:en.id; if(E.registrar('i_fusion', {de:sale, en:queda, nota:this.nota.value})){ E.cerrarModal(); E.aviso('Fusionados.'); } });
		}); return; }
	var nd=e.target.closest('[data-nodup]'); if(nd){ E.registrar('i_nodup', {a:nd.dataset.nodup, b:nd.dataset.b}); }
});

/* ── D. Centro de comunicaciones y mensajería ────────────────────────────── */
function siguienteNumero(){ var n=0; Object.keys(E.S.msgs).forEach(function(k){ var m=E.S.msgs[k]; if(m.a===E.yo.ind && (m.d.origen||m.a)===E.yo.ind) n=Math.max(n, parseInt(m.d.num,10)||0); }); return n+1; }
E.formMensaje = function(pre){
	pre=pre||{};
	if(!E.puede('operar') || cerrada()){ E.aviso('Tu rol o el estado de la operación no permiten registrar.', 'aviso'); return; }
	var ahora=Date.now(), origen=pre.origen||E.yo.ind;
	E.modal('Nuevo mensaje', '<form class="em-form" id="f-msg" novalidate>'
		+'<div class="em-dos"><label>Tipo<select class="em-campo" name="tipo">'+E.opciones(core.ET.msg_tipo, pre.tipo||'radiograma')+'</select></label><label>Incidente relacionado<select class="em-campo" name="inc">'+E.incidentesOpciones(pre.inc||'')+'</select></label></div>'
		+'<fieldset><legend>Precedencia</legend>'+E.chips('prec', core.ET.prec, pre.prec||'P', {E:'peligro', P:'aviso', B:'ok', R:''})+'</fieldset>'
		+'<label class="em-check"><input type="checkbox" name="prueba"'+(E.esSim()?' checked disabled':'')+'> <span>Mensaje de prueba: se transmite como «PRUEBA»</span></label>'
		+'<div class="em-tres"><label>Estación de origen<input class="em-campo em-mono" name="origen" value="'+esc(origen)+'" autocapitalize="characters"></label><label>Número<input class="em-campo em-mono" name="num" inputmode="numeric" value="'+(origen===E.yo.ind?siguienteNumero():'')+'"></label><label>Lugar de origen<input class="em-campo" name="lugar"></label></div>'
		+'<div class="em-dos"><label>De (remitente)<input class="em-campo" name="de" placeholder="Nombre y cargo"></label><label>Para (destinatario)<input class="em-campo" name="para" required value="'+esc(pre.para||'')+'" placeholder="Nombre, cargo o estación"></label></div>'
		+'<div class="em-tres"><label>Organización<input class="em-campo" name="org"></label><label>Teléfono del destinatario<input class="em-campo" name="tel" inputmode="tel"></label><label>Instrucción de manejo<select class="em-campo" name="hx"><option value="">Ninguna</option><option>HXA</option><option>HXB</option><option>HXC</option><option>HXD</option><option>HXE</option><option>HXF</option><option>HXG</option></select></label></div>'
		+'<label>Asunto<input class="em-campo" name="asunto"></label>'
		+'<label>Texto <small id="f-msg-ck" aria-live="polite">0 palabras</small><textarea class="em-campo em-mono" name="texto" rows="5" required placeholder="Corto y sin signos: PUNTO en vez de «.»">'+esc(pre.texto||'')+'</textarea></label>'
		+'<div class="em-tres"><label>Canal de origen<input class="em-campo" name="canal_o" list="lst-canales" placeholder="147.120 · 7.060 LSB"></label><label>Canal de destino<input class="em-campo" name="canal_d" list="lst-canales"></label><label>Autoriza<input class="em-campo" name="autoriza" placeholder="Si aplica"></label></div>'
		+'<label>Firma<input class="em-campo" name="firma"></label>'
		+'<label class="em-check"><input type="checkbox" name="recibido"'+(origen!==E.yo.ind?' checked':'')+'> <span>Lo recibí de otra estación (queda «Recibido por nodo» en mi estación)</span></label>'
		+listaCanales()+(pre.sim?'<input type="hidden" name="sim" value="'+esc(pre.sim)+'">':'')
		+'<button class="em-btn primario em-grande">Guardar mensaje</button><p class="em-error" id="f-msg-err" role="alert"></p></form>', function(c){
		var f=$('#f-msg',c);
		f.texto.addEventListener('input', function(){ var n=core.palabras(this.value); $('#f-msg-ck',c).textContent=n+' palabras (cuenta)'+(f.tipo.value==='radiograma'&&n>25?' · largo para un radiograma':''); });
		f.origen.addEventListener('input', function(){ var y=core.indicativo(this.value); if(y!==this.value) this.value=y; f.recibido.checked = y!==E.yo.ind; });
		f.addEventListener('submit', function(e){
			e.preventDefault(); var err=$('#f-msg-err',c);
			if(!f.para.value.trim()){ err.textContent='Falta el destinatario.'; f.para.focus(); return; }
			if(!f.texto.value.trim()){ err.textContent='Falta el texto.'; f.texto.focus(); return; }
			var d={tipo:f.tipo.value, inc:f.inc.value, prec:E.valorDe(f,'prec'), prueba:E.esSim()||f.prueba.checked, origen:core.indicativo(f.origen.value)||E.yo.ind, num:f.num.value.trim()||String(siguienteNumero()), lugar:f.lugar.value,
				de:f.de.value, para:f.para.value, org:f.org.value, tel:f.tel.value, hx:f.hx.value, asunto:f.asunto.value, texto:f.texto.value, canal_o:f.canal_o.value, canal_d:f.canal_d.value, autoriza:f.autoriza.value, firma:f.firma.value,
				ck:core.palabras(f.texto.value), hora:core.horaZ(ahora), fecha:core.fechaZ(ahora), estadoInicial:f.recibido.checked?'recibido':''};
			if(f.sim) d.sim=f.sim.value;
			var ev=E.registrar('msg', d); if(!ev) return;
			E.cerrarModal(); E.aviso('Mensaje n.º '+d.num+' guardado: queda «'+(f.recibido.checked?'Recibido por nodo':'Creado')+'».'); E.verMensaje(ev.u);
		});
	});
};
function listaCanales(){
	var l=[]; if(E.directorio) E.directorio.entradas.forEach(function(x){ l.push(x.f+' '+x.modo+(x.cat==='repetidora_ra'?' '+x.uso.split(' · ')[0]:'')); });
	Object.keys(E.S.asig).forEach(function(k){ l.push(E.S.asig[k].d.f); });
	return '<datalist id="lst-canales">'+l.map(function(x){ return '<option value="'+esc(x)+'">'; }).join('')+'</datalist>';
}
/* Transiciones de entrega que se ofrecen según el estado, con la evidencia que cada una exige */
var TRANS = {
	creado:['en_cola','enviado','fallido'], en_cola:['enviado','fallido'], enviado:['recibido_nodo','entregado','fallido'], recibido_nodo:['enviado','entregado','fallido'],
	entregado:['confirmado'], confirmado:[], fallido:['en_cola','enviado'], anulado:[]
};
var PIDE = { en_cola:['canal','Canal por el que saldrá'], enviado:['canal','Canal por el que se transmitió'], recibido_nodo:['nodo','Estación que lo recibió (indicativo)'], entregado:['evidencia','A quién se entregó y cómo'], confirmado:['evidencia','Acuse: quién confirmó, por dónde y cuándo'], fallido:['nota','Qué falló'] };
E.verMensaje = function(id){
	var m=E.S.msgs[id]; if(!m) return;
	var op=E.puede('operar')&&!cerrada(), sig=TRANS[m.estado]||[];
	E.modal('Mensaje n.º '+(m.d.num||'')+' · '+et('prec',m.d.prec), '<div class="em-inc-cab">'+E.pill(CLASE_MSG[m.estado], et('msg_est',m.estado))+' '+E.pill('apagado', et('msg_tipo',m.d.tipo||'radiograma'))+(m.d.prueba?' '+E.pill('aviso','PRUEBA'):'')+(m.inc&&E.S.inc[m.inc]?' <button type="button" class="em-link" data-inc="'+m.inc+'">'+esc(E.S.inc[m.inc].titulo)+'</button>':'')+'</div>'
		+'<div class="em-seg em-seg-ancho" role="group" aria-label="Formato"><button type="button" data-m="voz" class="is-on" aria-pressed="true">Para leer por radio</button><button type="button" data-m="escrito" aria-pressed="false">Radiograma escrito</button><button type="button" data-m="ics213" aria-pressed="false">ICS-213</button></div>'
		+'<pre class="em-leer" id="lm-texto"></pre><div class="em-acciones"><button type="button" class="em-btn" id="lm-copiar">Copiar</button><button type="button" class="em-btn" id="lm-imprimir">Imprimir</button><button type="button" class="em-btn" id="lm-txt">Descargar .txt</button></div>'
		+(op&&(sig.length||m.estado!=='anulado')?'<h3>Registrar entrega</h3><p class="em-nota">Registra solo lo que puedas demostrar. Un mensaje enviado por radio no está entregado hasta que alguien lo entrega al destinatario.</p><div class="em-acciones">'+sig.map(function(s){ return '<button type="button" class="em-btn'+(s==='fallido'?'':' ok')+'" data-mest="'+s+'">'+esc(et('msg_est',s))+'</button>'; }).join('')+(!core.MSG_FINAL[m.estado]?'<button type="button" class="em-btn" data-mint="1">Registrar intento</button>':'')+(E.puede('coordinar')&&m.estado!=='anulado'?'<button type="button" class="em-btn" data-mest="anulado">Anular</button>':'')+'</div>':'')
		+'<h3>Historial de entrega</h3><ol class="em-bit">'+m.estados.map(function(s){ return '<li><time>'+core.horaZ(s.ts)+'</time><span class="em-autor">'+esc(s.a)+'</span><span><b>'+esc(et('msg_est',s.e))+'</b>'+(s.canal?' · canal '+esc(s.canal):'')+(s.nodo?' · nodo '+esc(s.nodo):'')+(s.evidencia?' · '+esc(s.evidencia):'')+(s.nota?' · '+esc(s.nota):'')+'</span></li>'; }).join('')+'</ol>'
		+'<h3>Intentos y errores ('+m.intentos.length+')</h3>'+(m.intentos.length?'<ol class="em-bit">'+m.intentos.map(function(t){ return '<li><time>'+core.horaZ(t.ts)+'</time><span class="em-autor">'+esc(t.a)+'</span><span>'+esc(t.canal)+': '+esc(et('msg_int_res',t.resultado))+(t.nota?' — '+esc(t.nota):'')+'</span></li>'; }).join('')+'</ol>':'<p class="em-nota">Ninguno.</p>'), function(c){
		var modo='voz'; function pintar(){ $('#lm-texto',c).textContent=core.textoMensaje(m, modo, E.op); }
		$$('[data-m]',c).forEach(function(b){ b.addEventListener('click', function(){ modo=b.dataset.m; $$('[data-m]',c).forEach(function(x){ x.classList.toggle('is-on', x===b); x.setAttribute('aria-pressed', x===b); }); pintar(); }); });
		pintar();
		$('#lm-copiar',c).addEventListener('click', function(){ E.copiar(core.textoMensaje(m, modo==='voz'?'escrito':modo, E.op)); });
		$('#lm-imprimir',c).addEventListener('click', function(){ E.imprimir('Mensaje n.º '+(m.d.num||''), core.textoMensaje(m, modo==='voz'?'escrito':modo, E.op)); });
		$('#lm-txt',c).addEventListener('click', function(){ E.descargar('mensaje-'+(m.d.num||m.id.slice(0,6))+'.txt', core.textoMensaje(m, 'escrito', E.op)+'\n\n'+core.textoMensaje(m, 'ics213', E.op)); });
		$$('[data-mest]',c).forEach(function(b){ b.addEventListener('click', function(){
			var e=b.dataset.mest, pide=PIDE[e]||['nota','Nota'];
			E.modal(et('msg_est',e)+': mensaje n.º '+(m.d.num||''), '<form class="em-form" id="f-mest"><label>'+esc(pide[1])+'<input class="em-campo" name="v" required'+(pide[0]==='canal'?' list="lst-canales" value="'+esc(m.d.canal_d||m.d.canal_o||'')+'"':'')+(pide[0]==='nodo'?' autocapitalize="characters"':'')+'></label>'+(pide[0]!=='nota'?'<label>Nota<input class="em-campo" name="nota"></label>':'')+listaCanales()+'<button class="em-btn primario">Registrar</button></form>', function(c2){
				$('#f-mest',c2).addEventListener('submit', function(ev){ ev.preventDefault(); if(!this.v.value.trim()) return; var d={m:m.id, e:e}; d[pide[0]]=pide[0]==='nodo'?core.indicativo(this.v.value):this.v.value; if(this.nota&&pide[0]!=='nota') d.nota=this.nota.value; if(E.registrar('m_est', d)){ E.verMensaje(m.id); } });
			});
		}); });
		var it=$('[data-mint]',c); if(it) it.addEventListener('click', function(){
			E.modal('Intento de transmisión', '<form class="em-form" id="f-mint"><label>Canal<input class="em-campo" name="canal" required list="lst-canales" value="'+esc(m.d.canal_d||m.d.canal_o||'')+'"></label><fieldset><legend>Resultado</legend>'+E.chips('resultado', core.ET.msg_int_res, 'sin_respuesta')+'</fieldset><label>Nota<input class="em-campo" name="nota"></label>'+listaCanales()+'<button class="em-btn primario">Registrar</button></form>', function(c2){
				$('#f-mint',c2).addEventListener('submit', function(ev){ ev.preventDefault(); if(!this.canal.value.trim()) return; if(E.registrar('m_int', {m:m.id, canal:this.canal.value, resultado:E.valorDe(this,'resultado'), nota:this.nota.value})) E.verMensaje(m.id); });
			});
		});
	}, true);
};
var pestanaCom='tablero';
E.VIVO.mensajes = function(){
	var l=Object.keys(E.S.msgs).map(function(k){ return E.S.msgs[k]; });
	if(pestanaCom==='cola') l=l.filter(function(m){ return !core.MSG_FINAL[m.estado]; });
	if(pestanaCom==='confirmar') l=l.filter(function(m){ return m.estado==='entregado'; });
	l.sort(function(a,b){ return (core.ORDEN_PREC[a.d.prec]-core.ORDEN_PREC[b.d.prec]) || a.ts-b.ts; });
	if(!l.length) return '<p class="em-nota">No hay mensajes en esta lista.</p>';
	return '<div class="em-lista">'+l.map(function(m){
		var p=m.d.prec, c=p==='E'?'peligro':p==='P'?'aviso':p==='B'?'ok':'apagado', ult=m.estados[m.estados.length-1];
		return '<button type="button" class="em-msg em-borde-'+c+'" data-msg="'+m.id+'"><span class="em-msg-cab">'+E.pill(c, (m.d.prueba?'Prueba · ':'')+et('prec',p))+' <b class="em-mono">n.º '+esc(m.d.num||'')+'</b> de <b class="em-mono">'+esc(m.d.origen||m.a)+'</b> · '+esc(m.d.hora||'')+(m.intentos.length?' · '+m.intentos.length+' intentos':'')+'<span class="em-msg-est">'+E.pill(CLASE_MSG[m.estado], et('msg_est',m.estado))+'</span></span><span class="em-msg-para">Para '+esc(m.d.para)+(m.d.org?' ('+esc(m.d.org)+')':'')+'</span><span class="em-msg-txt">'+esc(m.d.texto)+'</span><small>Último cambio '+E.haceTxt(ult.ts)+' por '+esc(ult.a)+'</small></button>';
	}).join('')+'</div>';
};
document.addEventListener('click', function(e){ var b=e.target.closest('[data-msg]'); if(b && !b.closest('#lm-texto')) E.verMensaje(b.dataset.msg); });
var ADAPTADORES = [
	['Voz y CW (manual)', 'activo', 'El operador transmite y registra cada paso. Es el único transporte activo: esta plataforma no transmite por radio.'],
	['Winlink (correo por radio)', 'no configurado', 'Interfaz prevista: exportar el mensaje como texto plano o ICS-213 y adjuntarlo en el cliente Winlink. No hay conexión automática.'],
	['APRS', 'no configurado', 'Interfaz prevista: mensajes de hasta 67 caracteres por una pasarela APRS-IS. Requiere autorización y un servidor puente.'],
	['EchoLink / enlaces de repetidoras', 'no configurado', 'Se registran como canales del plan. No hay control remoto de nodos.'],
	['Correo o mensajería por internet', 'manual', 'Copiar o descargar el texto y enviarlo por el medio disponible; registrar «Enviado» con el canal usado.']
];
E.registrarVista('comunicaciones', {nombre:'Comunicaciones', icono:'✉', fn:function(p){
	if(p.id && E.S.msgs[p.id]) setTimeout(function(){ E.verMensaje(p.id); }, 50);
	var v=E.el('<div><div class="em-barra"><h1>Centro de comunicaciones</h1>'+(E.puede('operar')&&!cerrada()?boton('cm-nuevo','+ Mensaje','primario'):'')+'</div>'
		+'<div class="em-seg em-seg-tabs" role="tablist">'+[['tablero','Tablero de tráfico'],['cola','Cola por entregar'],['confirmar','Por confirmar'],['todos','Todos'],['canales','Canales'],['conectividad','Conectividad'],['procedimientos','Procedimientos'],['adaptadores','Transportes']].map(function(o){ return '<button type="button" role="tab" data-t="'+o[0]+'" aria-selected="'+(pestanaCom===o[0])+'" class="'+(pestanaCom===o[0]?'is-on':'')+'">'+o[1]+'</button>'; }).join('')+'</div>'
		+'<div id="cm-c"></div></div>');
	function pintar(){
		var c=$('#cm-c',v);
		if(pestanaCom==='tablero'){ c.innerHTML='<div data-vivo="trafTablero">'+E.VIVO.trafTablero()+'</div>'; }
		else if(['cola','confirmar','todos'].indexOf(pestanaCom)>=0){ c.innerHTML='<p class="em-nota">Ordenados por precedencia: emergencia, prioridad, bienestar, rutina. Estados: '+['creado','en_cola','enviado','recibido_nodo','entregado','confirmado'].map(function(s){ return et('msg_est',s); }).join(' → ')+'.</p><div data-vivo="mensajes">'+E.VIVO.mensajes()+'</div>'; }
		else if(pestanaCom==='canales'){ c.innerHTML='<section class="em-tarjeta"><h2>Canales asignados en esta operación</h2><div data-vivo="asig">'+E.VIVO.asig()+'</div><button type="button" class="em-link" data-ir="plan">Editar en el plan →</button></section><section class="em-tarjeta"><h2>Directorio de frecuencias</h2><div data-vivo="frecuencias">'+E.VIVO.frecuencias()+'</div></section>'; }
		else if(pestanaCom==='conectividad'){ c.innerHTML='<section class="em-tarjeta"><h2>Último estado conocido de enlaces y repetidoras</h2><p class="em-nota">Cada estado lleva su antigüedad: pasado el plazo de vigencia ('+E.ventana+' min para estaciones, el doble para repetidoras) se muestra como desconocido.</p><div data-vivo="enlacesTab">'+E.VIVO.enlacesTab()+'</div><button type="button" class="em-link" data-ir="estaciones">Estaciones y pruebas de enlace →</button></section>'; }
		else if(pestanaCom==='procedimientos'){ c.innerHTML='<section class="em-tarjeta"><h2>Procedimientos alternativos</h2><ol class="em-lista-txt"><li><b>Pérdida de la repetidora:</b> pasar a símplex en la frecuencia acordada en el plan (o la de llamada 146.520) y avisar a la estación de control.</li><li><b>Estación sin respuesta:</b> tres llamadas espaciadas; registrar «sin respuesta»; pedir relevo a otra estación cercana; marcar la estación como desconocida, no como caída.</li><li><b>Sin VHF entre municipios:</b> HF en onda de cielo casi vertical (NVIS): 40 m de día, 80 m de noche, dipolo bajo.</li><li><b>Retransmisión:</b> quien recibe para otro destino registra «Recibido por nodo» y luego «Enviado» con el canal; el número y la cuenta del mensaje no cambian.</li><li><b>Sin energía de red:</b> anunciar la autonomía restante en cada reporte; reducir potencia y ciclo de transmisión.</li><li><b>Sin internet:</b> esta aplicación sigue registrando; los datos se envían al volver la señal o se pasan con un respaldo exportado.</li></ol><p class="em-nota">Guía de trabajo de HK4RAU. El plan de comunicaciones aprobado de cada operación manda sobre esta lista.</p></section>'; }
		else { c.innerHTML='<section class="em-tarjeta"><h2>Transportes</h2><p class="em-nota">El modelo de mensaje es independiente del medio. Ninguna integración automática está activa: no se transmite nada por radio ni por pasarelas sin un operador.</p><table class="em-tabla"><tbody>'+ADAPTADORES.map(function(a){ return '<tr><td><b>'+esc(a[0])+'</b></td><td>'+E.pill(a[1]==='activo'?'ok':a[1]==='manual'?'aviso':'apagado', a[1])+'</td><td>'+esc(a[2])+'</td></tr>'; }).join('')+'</tbody></table></section>'; }
	}
	pintar();
	var n=$('#cm-nuevo',v); if(n) n.addEventListener('click', function(){ E.formMensaje(); });
	$$('[data-t]',v).forEach(function(b){ b.addEventListener('click', function(){ pestanaCom=b.dataset.t; $$('[data-t]',v).forEach(function(x){ x.classList.toggle('is-on', x===b); x.setAttribute('aria-selected', x===b); }); pintar(); }); });
	return v;
}});

/* ── E. Estaciones y enlaces ─────────────────────────────────────────────── */
E.formEstacion = function(pre){
	pre=pre||{};
	if(!E.puede('operar') || cerrada()){ E.aviso('Tu rol o el estado de la operación no permiten registrar.', 'aviso'); return; }
	E.modal(pre.ind?'Actualizar '+pre.ind:'Registrar estación', '<form class="em-form" id="f-est" novalidate>'
		+'<div class="em-dos"><label>Indicativo o identificador<input class="em-campo em-mono em-grande" name="ind" required autocapitalize="characters" value="'+esc(pre.ind||'')+'"'+(pre.ind?' readonly':'')+'></label><label>Nombre de la estación<input class="em-campo" name="nombre" value="'+esc(pre.nombre||'')+'" placeholder="Ej.: Bomberos Dosquebradas"></label></div>'
		+'<div class="em-dos"><label>Organización<input class="em-campo" name="org" value="'+esc(pre.org||'')+'"></label><label>Responsable de la información<input class="em-campo" name="resp" value="'+esc(pre.resp||E.yo.ind)+'"></label></div>'
		+'<div class="em-dos"><label>Ubicación<input class="em-campo" name="lugar" value="'+esc(pre.lugar||'')+'" placeholder="Municipio, sitio"></label><label>Precisión<select class="em-campo" name="precision">'+E.opciones(core.ET.precision, pre.precision||'municipio')+'</select></label></div>'
		+'<label>Coordenadas (opcional)<span class="em-con-boton"><input class="em-campo em-mono" name="pos" value="'+(pre.lat!==undefined?pre.lat.toFixed(5)+', '+pre.lng.toFixed(5):'')+'" placeholder="4.81, -75.69 o locator"><button type="button" class="em-btn" id="f-est-gps" title="GPS de este dispositivo (solo si la estación eres tú)">📍</button></span></label>'
		+'<fieldset><legend>Medios disponibles</legend><div class="em-chips">'+MEDIOS.map(function(m){ return '<label class="em-chip"><input type="checkbox" name="medios" data-lista="1" value="'+esc(m)+'"'+((pre.medios||[]).indexOf(m)>=0?' checked':'')+'><span>'+esc(m)+'</span></label>'; }).join('')+'</div></fieldset>'
		+'<div class="em-tres"><label>Modos y capacidades<input class="em-campo" name="modos" value="'+esc(pre.modos||'')+'" placeholder="FM, SSB, VARA…"></label><label>Energía<select class="em-campo" name="energia">'+E.opciones(core.ET.energia, pre.energia||'', '—')+'</select></label><label>Autonomía reportada<input class="em-campo" name="autonomia" value="'+esc(pre.autonomia||'')+'" placeholder="Ej.: 6 h"></label></div>'
		+'<label>Limitaciones y observaciones<input class="em-campo" name="limitaciones" value="'+esc(pre.limitaciones||'')+'"></label>'
		+'<p class="em-nota">Registrar una estación no la hace «disponible»: eso lo dice un reporte de estado o una verificación.</p><button class="em-btn primario em-grande">Guardar</button><p class="em-error" id="f-est-err" role="alert"></p></form>', function(c){
		var f=$('#f-est',c);
		f.ind.addEventListener('input', function(){ var y=core.indicativo(this.value); if(y!==this.value) this.value=y; });
		$('#f-est-gps',c).addEventListener('click', function(){ gps(function(p){ f.pos.value=p.lat.toFixed(5)+', '+p.lng.toFixed(5); f.precision.value=p.acc<=100?'exacta':'aproximada'; }); });
		f.addEventListener('submit', function(e){
			e.preventDefault(); var ind=core.indicativo(f.ind.value), err=$('#f-est-err',c);
			if(ind.length<3){ err.textContent='Falta el indicativo.'; f.ind.focus(); return; }
			var p=core.parsearPosicion(f.pos.value); if(f.pos.value && !p){ err.textContent='No se entiende la posición.'; return; }
			var medios=$$('input[name=medios]:checked',f).map(function(i){ return i.value; });
			var d={ind:ind, nombre:f.nombre.value, org:f.org.value, resp:f.resp.value, lugar:f.lugar.value, precision:f.precision.value, medios:medios, modos:f.modos.value, energia:f.energia.value, autonomia:f.autonomia.value, limitaciones:f.limitaciones.value};
			if(p){ d.lat=+p.lat.toFixed(6); d.lng=+p.lng.toFixed(6); }
			if(E.registrar('e_reg', d)){ E.cerrarModal(); E.aviso(ind+' guardada.'); }
		});
	});
};
function formEstado(ind){
	E.modal('Reportar estado de '+ind, '<form class="em-form" id="f-ee"><fieldset><legend>Estado que reporta</legend>'+E.chips('e', core.ET.est_estado, 'disponible', {disponible:'ok', degradado:'aviso', fuera_servicio:'peligro'})+'</fieldset><label>Quién lo reporta y por dónde<input class="em-campo" name="fuente" placeholder="Ej.: la misma estación por 147.120"></label><label>Nota<input class="em-campo" name="nota" placeholder="Ej.: sin energía de red, 4 h de batería"></label><p class="em-nota">Queda como estado REPORTADO. Para que sea verificado, registra una verificación o una prueba de enlace.</p><button class="em-btn primario">Registrar</button></form>', function(c){
		$('#f-ee',c).addEventListener('submit', function(e){ e.preventDefault(); if(E.registrar('e_est', {ind:ind, e:E.valorDe(this,'e'), fuente:this.fuente.value, nota:this.nota.value})) E.cerrarModal(); });
	});
}
function formVerificar(ind){
	E.modal('Verificar '+ind, '<form class="em-form" id="f-ev"><fieldset><legend>Resultado</legend>'+E.chips('resultado', {ok:'Respondió', fallo:'No respondió'}, 'ok', {ok:'ok', fallo:'peligro'})+'</fieldset><label>Método<input class="em-campo" name="metodo" required value="Llamada por radio" list="lst-canales"></label><label>Nota<input class="em-campo" name="nota"></label>'+listaCanales()+'<button class="em-btn primario">Registrar</button></form>', function(c){
		$('#f-ev',c).addEventListener('submit', function(e){ e.preventDefault(); if(!this.metodo.value.trim()) return; if(E.registrar('e_verif', {ind:ind, resultado:E.valorDe(this,'resultado'), metodo:this.metodo.value, nota:this.nota.value})) E.cerrarModal(); });
	});
}
function formEnlace(de){
	E.modal('Prueba de enlace', '<form class="em-form" id="f-en"><div class="em-dos"><label>Desde<input class="em-campo em-mono" name="de" required value="'+esc(de||E.yo.ind)+'" autocapitalize="characters"></label><label>Hasta<input class="em-campo em-mono" name="a" required autocapitalize="characters" list="lst-est"></label></div><label>Canal<input class="em-campo" name="canal" required list="lst-canales" placeholder="147.120 FM · 7.060 LSB"></label><fieldset><legend>Resultado</legend>'+E.chips('resultado', {ok:'Buena', debil:'Débil', fallo:'Falló'}, 'ok', {ok:'ok', debil:'aviso', fallo:'peligro'})+'</fieldset><div class="em-dos"><label>Reporte de señal<input class="em-campo" name="senal" placeholder="59, 5/9…"></label><label>Nota<input class="em-campo" name="nota"></label></div>'
		+'<datalist id="lst-est">'+Object.keys(E.S.est).map(function(k){ return '<option value="'+esc(k)+'">'; }).join('')+'</datalist>'+listaCanales()+'<button class="em-btn primario">Registrar</button></form>', function(c){
		$('#f-en',c).addEventListener('submit', function(e){ e.preventDefault(); var de=core.indicativo(this.de.value), a=core.indicativo(this.a.value); if(de.length<3||a.length<3||!this.canal.value.trim()) return; if(E.registrar('enlace', {de:de, a:a, canal:this.canal.value, resultado:E.valorDe(this,'resultado'), senal:this.senal.value, nota:this.nota.value})) E.cerrarModal(); });
	});
}
var lista={cola:[]};
E.pasarLista = function(){
	lista.cola=Object.keys(E.S.est).sort(function(a,b){ return (E.S.est[a].contacto||0)-(E.S.est[b].contacto||0); });
	if(!lista.cola.length){ E.aviso('No hay estaciones para llamar.', 'aviso'); return; }
	siguiente();
	function siguiente(){
		var k=lista.cola.shift(); if(!k){ E.cerrarModal(); E.aviso('Lista terminada.'); return; }
		var x=E.S.est[k], s=core.estadoEstacion(x, Date.now(), E.ventana);
		E.modal('Pase de lista · faltan '+lista.cola.length, '<p class="em-dictar"><strong class="em-mono">'+esc(x.ind)+'</strong><br><small>'+core.fonetico(x.ind)+'</small></p><p class="em-nota">'+esc(et('est_op',s.e))+' · último dato '+E.haceTxt(s.ts)+(x.lugar?' · '+esc(x.lugar):'')+'</p>'
			+'<form class="em-form" id="f-lista"><label>Nota (opcional)<input class="em-campo" name="nota" placeholder="Ej.: sin novedad, 2 mensajes para pasar"></label><div class="em-acciones"><button class="em-btn ok em-grande">✓ Respondió</button><button type="button" class="em-btn em-grande" id="fl-no">✗ Sin respuesta</button><button type="button" class="em-btn" id="fl-fin">Terminar</button></div></form>', function(c){
			$('#f-lista',c).addEventListener('submit', function(e){ e.preventDefault(); E.registrar('e_verif', {ind:x.ind, resultado:'ok', metodo:'Pase de lista', nota:this.nota.value}); siguiente(); });
			$('#fl-no',c).addEventListener('click', function(){ E.registrar('e_verif', {ind:x.ind, resultado:'fallo', metodo:'Pase de lista'}); E.registrar('sinresp', {ind:x.ind}); siguiente(); });
			$('#fl-fin',c).addEventListener('click', function(){ lista.cola=[]; E.cerrarModal(); });
		});
	}
};
var filtroEst='';
E.VIVO.estaciones = function(){
	var ahora=Date.now(), l=Object.keys(E.S.est).map(function(k){ var x=E.S.est[k]; return {x:x, s:core.estadoEstacion(x, ahora, E.ventana)}; });
	var orden={fuera_servicio:0, degradado:1, desconocido:2, no_verificado:3, reportado_disponible:4, disponible:5};
	if(filtroEst) l=l.filter(function(o){ return o.s.e===filtroEst; });
	l.sort(function(a,b){ return orden[a.s.e]-orden[b.s.e] || a.x.ind.localeCompare(b.x.ind); });
	if(!l.length) return '<p class="em-nota">'+(filtroEst?'Ninguna estación en ese estado.':'Todavía no hay estaciones. Registra la primera.')+'</p>';
	var op=E.puede('operar')&&!cerrada();
	return '<div class="em-estaciones">'+l.map(function(o){ var x=o.x, s=o.s;
		return '<article class="em-est em-borde-'+CLASE_EST[s.e]+'" data-est="'+esc(x.ind)+'"><div class="em-est-cab"><strong class="em-mono em-ind">'+esc(x.ind)+'</strong>'+E.pill(CLASE_EST[s.e], et('est_op',s.e))+' '+E.calidad(s.calidad)+(x.situacion?' '+E.pill(x.situacion.e==='urgente'?'peligro':'aviso', x.situacion.e==='urgente'?'Urgencia':'Pide apoyo'):'')+'</div>'
			+'<p>'+[x.nombre, x.org, x.lugar].filter(Boolean).map(esc).join(' · ')+(x.lat!==undefined?' <span class="em-mono">'+core.locator(x.lat,x.lng)+'</span>':'')+'</p>'
			+(x.medios&&x.medios.length?'<p class="em-nota">'+esc(x.medios.join(' · '))+(x.energia?' · energía: '+esc(et('energia',x.energia)):'')+(x.autonomia?' ('+esc(x.autonomia)+')':'')+'</p>':'')
			+'<p class="em-nota">'+esc(s.motivo||'')+'</p>'
			+'<p class="em-edad '+edadClase(s.ts||x.alta)+'">Último contacto '+(x.contacto?E.haceTxt(x.contacto)+' ('+core.horaZ(x.contacto)+')':'—')+' · última verificación '+(x.verif?E.haceTxt(x.verif.ts)+(x.verif.resultado==='fallo'?' (falló)':''):'nunca')+(x.sinresp?' · '+x.sinresp+' sin respuesta':'')+'</p>'
			+(x.limitaciones?'<p class="em-nota-est">'+esc(x.limitaciones)+'</p>':'')
			+(op?'<div class="em-est-acc"><button type="button" class="em-btn ok" data-ea="verificar">Verificar</button><button type="button" class="em-btn" data-ea="estado">Reportar estado</button><button type="button" class="em-btn" data-ea="enlace">Prueba de enlace</button><button type="button" class="em-btn" data-ea="editar">Editar</button></div>':'')
			+'<details><summary>Historial ('+x.hist.length+')</summary><ol class="em-bit">'+x.hist.slice(-15).reverse().map(E.filaBit).join('')+'</ol></details></article>';
	}).join('')+'</div>';
};
E.VIVO.repes = function(){
	var l=[], ya={};
	if(E.directorio) E.directorio.entradas.filter(function(x){ return x.cat==='repetidora_ra'||(x.cat==='pendiente'&&/Repetidora/.test(x.uso)); }).forEach(function(x){ var k=x.uso.split(' · ')[0].replace(/^Repetidora /,'')+' '+x.f; ya[k]=1; l.push({k:k, x:x}); });
	Object.keys(E.S.repe).forEach(function(k){ if(!ya[k]) l.push({k:k}); });
	if(!l.length) return '<p class="em-nota">Sin repetidoras en el directorio.</p>';
	var op=E.puede('operar')&&!cerrada();
	return '<div class="em-lista">'+l.map(function(o){ var r=E.S.repe[o.k], s=core.estadoRepetidora(r, Date.now(), E.ventana);
		return '<div class="em-fila em-repe" data-repe="'+esc(o.k)+'"><span><strong>'+esc(o.k)+'</strong> '+(r?(s.e==='desconocido'?E.pill('apagado','Desconocido'):E.pill(r.e==='caida'?'peligro':r.e==='aire'?'ok':'aviso', et('repe_e',r.e)))+' '+E.calidad(s.calidad):E.pill('apagado','Sin reporte'))+'<small>'+(r?'según '+esc(r.a)+' '+E.haceTxt(r.ts)+(r.n?' — '+esc(r.n):''):'Que esté en el directorio no garantiza que esté en el aire.')+(o.x&&o.x.cat==='pendiente'?' · datos pendientes de validación':'')+'</small></span>'
			+(op?'<span class="em-chips em-chips-mini">'+Object.keys(core.ET.repe_e).map(function(k){ return '<button type="button" class="em-chip '+({aire:'ok', bateria:'aviso', generador:'aviso', caida:'peligro'}[k])+(r&&r.e===k&&s.e!=='desconocido'?' is-on':'')+'" data-repe-e="'+k+'">'+esc(et('repe_e',k))+'</button>'; }).join('')+'</span>':'')+'</div>';
	}).join('')+'</div>';
};
E.registrarVista('estaciones', {nombre:'Estaciones y enlaces', icono:'📡', fn:function(p){
	var op=E.puede('operar')&&!cerrada();
	var v=E.el('<div><div class="em-barra"><h1>Estaciones y enlaces</h1>'+(op?'<div class="em-acciones">'+boton('es-nueva','+ Estación','primario')+boton('es-lista','Pasar lista','')+boton('es-enlace','Prueba de enlace','')+'<button type="button" class="em-btn" data-perfil>Perfil de enlace</button></div>':'<div class="em-acciones"><button type="button" class="em-btn" data-perfil>Perfil de enlace</button></div>')+'</div>'
		+'<div class="em-barra em-filtros"><label class="em-inline">Mostrar <select class="em-campo" id="es-f">'+E.opciones(core.ET.est_op, filtroEst, 'Todas')+'</select></label><span class="em-nota">Un reporte o una verificación vale '+E.ventana+' min (cámbialo en Campo). Después, el estado es «desconocido».</span></div>'
		+'<div data-vivo="estaciones">'+E.VIVO.estaciones()+'</div>'
		+'<section class="em-tarjeta"><h2>Repetidoras</h2><div data-vivo="repes">'+E.VIVO.repes()+'</div>'+(op?'<button type="button" class="em-link" id="es-otra">+ Otra repetidora</button>':'')+'</section></div>');
	if(op){ $('#es-nueva',v).addEventListener('click', function(){ E.formEstacion(); }); $('#es-lista',v).addEventListener('click', E.pasarLista); $('#es-enlace',v).addEventListener('click', function(){ formEnlace(); });
		$('#es-otra',v).addEventListener('click', function(){ E.modal('Otra repetidora', '<form class="em-form" id="f-or"><label>Nombre y frecuencia<input class="em-campo" name="r" required placeholder="Ej.: Cerro Tatamá 146.940"></label><fieldset><legend>Estado</legend>'+E.chips('e', core.ET.repe_e, 'aire')+'</fieldset><label>Nota<input class="em-campo" name="n"></label><button class="em-btn primario">Registrar</button></form>', function(c){ $('#f-or',c).addEventListener('submit', function(e){ e.preventDefault(); if(E.registrar('repe', {r:this.r.value, e:E.valorDe(this,'e'), n:this.n.value})) E.cerrarModal(); }); }); }); }
	$('#es-f',v).addEventListener('change', function(){ filtroEst=this.value; E.refrescar(); });
	v.addEventListener('click', function(e){
		var b=e.target.closest('[data-ea]'); if(b){ var k=b.closest('[data-est]').dataset.est, a=b.dataset.ea; if(a==='verificar') formVerificar(k); if(a==='estado') formEstado(k); if(a==='enlace') formEnlace(k); if(a==='editar') E.formEstacion(E.S.est[k]); return; }
		var r=e.target.closest('[data-repe-e]'); if(r){ var nombre=r.closest('[data-repe]').dataset.repe, est=r.dataset.repeE;
			E.modal('Repetidora '+nombre, '<form class="em-form" id="f-re"><p>Reportar: <b>'+esc(et('repe_e',est))+'</b></p><label>Nota (causa, autonomía, fuente)<input class="em-campo" name="n"></label><button class="em-btn primario">Registrar</button></form>', function(c){ $('#f-re',c).addEventListener('submit', function(ev){ ev.preventDefault(); if(E.registrar('repe', {r:nombre, e:est, n:this.n.value})) E.cerrarModal(); }); }); }
	});
	if(p.id && E.S.est[p.id]) setTimeout(function(){ var el=v.querySelector('[data-est="'+p.id.replace(/"/g,'')+'"]'); if(el){ el.scrollIntoView({block:'center'}); el.classList.add('is-lista'); } }, 60);
	return v;
}});

/* ── F. Plan de comunicaciones ───────────────────────────────────────────── */
var CAMPOS_PLAN=[['objetivo','Objetivo de comunicaciones',true],['resp','Responsable',true],['estaciones','Estaciones participantes'],['canal_p','Canales principales',true],['canal_a','Canales alternos'],['modos','Modos disponibles'],['llamada','Procedimiento de llamada'],['mallas','Horarios de las mallas'],['formato','Formato de reporte'],['perdida','Procedimiento de pérdida de contacto'],['retrans','Procedimiento de retransmisión'],['energia','Autonomía energética reportada'],['restricciones','Restricciones y observaciones']];
function planActual(){
	var l=Object.keys(E.S.planes).map(function(k){ return E.S.planes[k]; }).filter(function(p){ return p.estado!=='retirado'; }).sort(function(a,b){ return b.ts-a.ts; });
	return l.filter(function(p){ return p.estado==='aprobado'; })[0] || l[0] || null;
}
function textoPlan(p){
	if(!p) return '';
	return (E.esSim()?'*** SIMULACRO ***\n':'')+'PLAN DE COMUNICACIONES (adaptación del formato SCI-205)\nOperación: '+E.op.nombre+' · '+E.op.codigo+'\nEstado: '+et('plan_est',p.estado).toUpperCase()+(p.estado!=='aprobado'?' — PROPUESTA: no es una instrucción hasta que la apruebe el responsable':'')+'\nVersión de '+core.isoZ(p.ts)+' por '+p.a+'\n'
		+(p.hist.length?p.hist.map(function(h){ return '  '+et('plan_est',h.e)+' '+core.isoZ(h.ts)+' por '+(h.por||h.a)+(h.nota?' ('+h.nota+')':''); }).join('\n')+'\n':'')+'\n'
		+CAMPOS_PLAN.map(function(c){ return c[1].toUpperCase()+':\n'+(p.d[c[0]]||'—'); }).join('\n\n')
		+'\n\nCANALES ASIGNADOS:\n'+(Object.keys(E.S.asig).map(function(k){ var a=E.S.asig[k].d; return '- '+a.funcion+': '+a.f+' '+(a.modo||'')+(a.obs?' ('+a.obs+')':''); }).join('\n')||'—');
}
E.VIVO.plan = function(){
	var p=planActual();
	if(!p) return '<p class="em-nota">Todavía no hay un plan. Prepara una propuesta: el responsable la aprueba.</p>';
	return '<div class="em-inc-cab">'+E.pill(p.estado==='aprobado'?'ok':p.estado==='propuesto'?'aviso':'apagado', et('plan_est',p.estado))+' <small>versión de '+core.isoZ(p.ts)+' por '+esc(p.a)+'</small></div>'
		+(p.estado!=='aprobado'?'<p class="em-aviso">Propuesta: no es una instrucción oficial hasta que la apruebe el responsable correspondiente. No se distribuye sola.</p>':'')
		+'<dl class="em-dl">'+CAMPOS_PLAN.map(function(c){ return '<dt>'+esc(c[1])+'</dt><dd>'+esc(p.d[c[0]]||'—')+'</dd>'; }).join('')+'</dl>';
};
E.VIVO.asig = function(){
	var a=Object.keys(E.S.asig).map(function(k){ return E.S.asig[k]; });
	return a.length?'<table class="em-tabla"><thead><tr><th>Función</th><th>Canal</th><th>Modo</th><th>Observaciones</th><th></th></tr></thead><tbody>'+a.map(function(p){ return '<tr><td>'+esc(p.d.funcion)+'</td><td class="em-f">'+esc(p.d.f)+'</td><td>'+esc(p.d.modo||'')+'</td><td>'+esc(p.d.obs||'')+'</td><td>'+(E.puede('operar')&&!cerrada()?'<button type="button" class="em-x" data-planx="'+p.id+'" aria-label="Quitar la asignación '+esc(p.d.funcion)+'">×</button>':'')+'</td></tr>'; }).join('')+'</tbody></table>':'<p class="em-nota">Sin canales asignados.</p>';
};
E.registrarVista('plan', {nombre:'Plan de comunicaciones', icono:'⌁', fn:function(){
	var op=E.puede('operar')&&!cerrada(), p=planActual();
	var versiones=Object.keys(E.S.planes).map(function(k){ return E.S.planes[k]; }).sort(function(a,b){ return b.ts-a.ts; });
	var v=E.el('<div><div class="em-barra"><h1>Plan de comunicaciones</h1><div class="em-acciones">'+(op?boton('pl-nuevo', p?'Nueva versión':'Preparar plan','primario'):'')
		+(p&&op&&p.estado==='borrador'?boton('pl-proponer','Proponer',''):'')+(p&&E.puede('coordinar')&&!cerrada()&&p.estado!=='aprobado'?boton('pl-aprobar','Aprobar','ok'):'')+(p&&E.puede('coordinar')&&!cerrada()?boton('pl-retirar','Retirar',''):'')
		+(p?boton('pl-imp','Imprimir','')+boton('pl-txt','Exportar texto','')+boton('pl-json','Exportar JSON',''):'')+'</div></div>'
		+'<section class="em-tarjeta"><div data-vivo="plan">'+E.VIVO.plan()+'</div></section>'
		+'<section class="em-tarjeta"><h2>Canales asignados</h2><div data-vivo="asig">'+E.VIVO.asig()+'</div>'+(op?'<button type="button" class="em-btn" id="pl-asig">+ Asignar canal</button>':'')+'</section>'
		+(versiones.length>1?'<section class="em-tarjeta"><h2>Versiones</h2><ol class="em-bit">'+versiones.map(function(x){ return '<li><time>'+core.horaZ(x.ts)+'</time><span class="em-autor">'+esc(x.a)+'</span><span>'+esc(et('plan_est',x.estado))+'</span></li>'; }).join('')+'</ol></section>':'')
		+'</div>');
	function estado(e, titulo){ E.modal(titulo, '<form class="em-form" id="f-pe"><label>'+(e==='aprobado'?'Quién aprueba (nombre y cargo)':'Nota')+'<input class="em-campo" name="v" required></label><button class="em-btn primario">'+esc(titulo)+'</button></form>', function(c){ $('#f-pe',c).addEventListener('submit', function(ev){ ev.preventDefault(); var d={p:p.id, e:e}; if(e==='aprobado') d.por=this.v.value; else d.nota=this.v.value; if(E.registrar('p_est', d)){ E.cerrarModal(); E.ir('plan'); } }); }); }
	var b;
	if((b=$('#pl-nuevo',v))) b.addEventListener('click', function(){
		var d=p?p.d:{};
		E.modal('Plan de comunicaciones', '<form class="em-form" id="f-plan"><p class="em-nota">Se guarda como una versión nueva en borrador; las anteriores se conservan.</p>'+CAMPOS_PLAN.map(function(c){ return '<label>'+esc(c[1])+(c[2]?' *':'')+'<textarea class="em-campo" name="'+c[0]+'" rows="2"'+(c[2]?' required':'')+'>'+esc(d[c[0]]||'')+'</textarea></label>'; }).join('')+'<button class="em-btn primario">Guardar versión</button></form>', function(c){
			$('#f-plan',c).addEventListener('submit', function(e){ e.preventDefault(); var x={}; CAMPOS_PLAN.forEach(function(k){ x[k[0]]=this[k[0]].value; }, this); if(E.registrar('p_ver', x)){ E.cerrarModal(); E.ir('plan'); } });
		}, true);
	});
	if((b=$('#pl-proponer',v))) b.addEventListener('click', function(){ if(E.registrar('p_est', {p:p.id, e:'propuesto'})) E.ir('plan'); });
	if((b=$('#pl-aprobar',v))) b.addEventListener('click', function(){ estado('aprobado', 'Aprobar plan'); });
	if((b=$('#pl-retirar',v))) b.addEventListener('click', function(){ estado('retirado', 'Retirar plan'); });
	if((b=$('#pl-imp',v))) b.addEventListener('click', function(){ E.imprimir('Plan de comunicaciones', textoPlan(planActual())); });
	if((b=$('#pl-txt',v))) b.addEventListener('click', function(){ E.descargar(E.nombreArchivo('plan-comunicaciones','txt'), textoPlan(planActual())); });
	if((b=$('#pl-json',v))) b.addEventListener('click', function(){ var x=planActual(); E.descargar(E.nombreArchivo('plan-comunicaciones','json'), JSON.stringify({operacion:E.op.codigo, plan:x, canales:Object.keys(E.S.asig).map(function(k){ return E.S.asig[k].d; })}, null, 2), 'application/json'); });
	if((b=$('#pl-asig',v))) b.addEventListener('click', function(){
		E.modal('Asignar canal', '<form class="em-form" id="f-as"><label>Función<input class="em-campo" name="funcion" required list="lst-fun"></label><datalist id="lst-fun"><option value="Mando (estación de control)"><option value="Táctico local"><option value="Logística"><option value="Salud"><option value="Bienestar"><option value="Enlace nacional HF"><option value="Enlace con autoridades"></datalist><div class="em-dos"><label>Canal<input class="em-campo em-mono" name="f" required list="lst-canales"></label><label>Modo<select class="em-campo" name="modo"><option>FM</option><option>LSB</option><option>USB</option><option>Digital</option><option>EchoLink</option><option>Otro</option></select></label></div><label>Observaciones<input class="em-campo" name="obs"></label>'+listaCanales()+'<button class="em-btn primario">Asignar</button></form>', function(c){
			$('#f-as',c).addEventListener('submit', function(e){ e.preventDefault(); if(E.registrar('plan', {funcion:this.funcion.value, f:this.f.value, modo:this.modo.value, obs:this.obs.value})) E.cerrarModal(); });
		});
	});
	v.addEventListener('click', function(e){ var x=e.target.closest('[data-planx]'); if(x) E.confirmar('Quitar asignación', 'Queda constancia en la bitácora.', 'Quitar', true).then(function(si){ if(si) E.registrar('planx', {p:x.dataset.planx}); }); });
	return v;
}});

/* ── G. Recursos ─────────────────────────────────────────────────────────── */
E.formSolicitud = function(pre){
	pre=pre||{};
	if(!E.puede('operar') || cerrada()) return;
	E.modal('Solicitar recurso', '<form class="em-form" id="f-sol"><div class="em-dos"><label>Tipo<select class="em-campo" name="tipo">'+E.opciones(core.ET.rec_tipo, pre.tipo||'radio')+'</select></label><label>Cantidad<input class="em-campo" type="number" min="1" name="cant" value="1" required></label></div><label>Qué se necesita y para qué<input class="em-campo" name="desc" required></label><div class="em-dos"><label>Quién lo pide<input class="em-campo" name="solicita" required value="'+esc(E.yo.ind)+'"></label><label>Incidente<select class="em-campo" name="inc">'+E.incidentesOpciones(pre.inc||'')+'</select></label></div><fieldset><legend>Urgencia</legend>'+E.chips('urgencia', core.ET.urgencia, 'horas')+'</fieldset><button class="em-btn primario">Solicitar</button></form>', function(c){
		$('#f-sol',c).addEventListener('submit', function(e){ e.preventDefault(); if(!this.desc.value.trim()) return; if(E.registrar('r_sol', {tipo:this.tipo.value, cant:+this.cant.value, desc:this.desc.value, solicita:this.solicita.value, inc:this.inc.value, urgencia:E.valorDe(this,'urgencia')})){ E.cerrarModal(); E.aviso('Solicitud registrada: queda pendiente de aprobación.'); } });
	});
};
var pestanaRec='inventario';
E.VIVO.recursos = function(){
	var l=Object.keys(E.S.rec).map(function(k){ return E.S.rec[k]; }).filter(function(r){ return !r.retirado; }), gestiona=E.puede('recursos')&&!cerrada();
	if(!l.length) return '<p class="em-nota">Sin recursos registrados: personal, radios, antenas, vehículos, energía, repetidores de campaña, material.</p>';
	var orden={fuera_servicio:0, en_desplazamiento:1, desplegado:2, asignado:3, reservado:4, disponible:5, liberado:6};
	l.sort(function(a,b){ return orden[a.estado]-orden[b.estado]; });
	return '<div class="em-lista">'+l.map(function(r){
		return '<div class="em-fila em-rec em-borde-'+CLASE_REC[r.estado]+'" data-rec="'+r.id+'"><span><strong>'+(r.cod?'<span class="em-mono">'+esc(r.cod)+'</span> ':'')+esc(r.cant)+' × '+esc(r.desc)+'</strong> '+E.pill(CLASE_REC[r.estado], et('rec_est',r.estado))+'<small>'+esc(et('rec_tipo',r.tipo))+(r.org?' · '+esc(r.org):'')+' · responsable '+esc(r.resp)+(r.inc&&E.S.inc[r.inc]?' · para «'+esc(E.S.inc[r.inc].titulo)+'»':'')+' · act. '+E.haceTxt(r.act)+'</small>'
			+'<small>'+(r.ubic?'Última ubicación conocida: '+esc(r.ubic.lugar||'')+(r.ubic.lat!==undefined?' ('+r.ubic.lat.toFixed(4)+', '+r.ubic.lng.toFixed(4)+')':'')+' · '+E.haceTxt(r.ubic.ts)+' · no es seguimiento en tiempo real':'Ubicación desconocida')+'</small>'+(r.resultado?'<small>Resultado: '+esc(r.resultado)+'</small>':'')+'</span>'
			+(gestiona?'<select class="em-campo em-rec-sel" aria-label="Cambiar estado de '+esc(r.desc)+'"><option value="">Cambiar estado…</option>'+Object.keys(core.ET.rec_est).filter(function(k){ return k!==r.estado; }).map(function(k){ return '<option value="'+k+'">'+esc(et('rec_est',k))+'</option>'; }).join('')+'</select>':'')+'</div>';
	}).join('')+'</div>';
};
E.VIVO.solicitudes = function(){
	var l=Object.keys(E.S.sol).map(function(k){ return E.S.sol[k]; }).sort(function(a,b){ return (a.estado==='pendiente'?0:1)-(b.estado==='pendiente'?0:1) || b.ts-a.ts; }), gestiona=E.puede('recursos')&&!cerrada();
	if(!l.length) return '<p class="em-nota">Sin solicitudes.</p>';
	return '<div class="em-lista">'+l.map(function(s){
		return '<div class="em-fila" data-sol="'+s.id+'"><span><strong>'+s.cant+' × '+esc(et('rec_tipo',s.tipo))+'</strong> '+E.pill(s.estado==='pendiente'?'aviso':s.estado==='rechazada'||s.estado==='cancelada'?'apagado':'ok', et('sol_est',s.estado))+' '+E.pill(s.urgencia==='inmediata'?'peligro':'apagado', et('urgencia',s.urgencia))+'<small>'+esc(s.desc)+' · pidió '+esc(s.solicita)+' '+E.haceTxt(s.ts)+(s.inc&&E.S.inc[s.inc]?' · «'+esc(E.S.inc[s.inc].titulo)+'»':'')+(s.rec&&E.S.rec[s.rec]?' · atendida con '+esc(E.S.rec[s.rec].desc):'')+'</small></span>'
			+(gestiona&&(s.estado==='pendiente'||s.estado==='aprobada')?'<span class="em-acciones">'+(s.estado==='pendiente'?'<button type="button" class="em-btn ok" data-se="aprobada">Aprobar</button><button type="button" class="em-btn" data-se="rechazada">Rechazar</button>':'<button type="button" class="em-btn ok" data-se="atendida">Atender</button>')+'<button type="button" class="em-btn" data-se="cancelada">Cancelar</button></span>':'')+'</div>';
	}).join('')+'</div>';
};
E.registrarVista('recursos', {nombre:'Recursos y personal', icono:'⚙', fn:function(p){
	if(p.filtro==='solicitudes') pestanaRec='solicitudes';
	var op=E.puede('operar')&&!cerrada();
	var v=E.el('<div><div class="em-barra"><h1>Recursos y personal</h1>'+(op?'<div class="em-acciones">'+boton('re-nuevo','+ Recurso','primario')+boton('re-sol','Solicitar','')+'</div>':'')+'</div>'
		+'<p class="em-nota">Apoya la coordinación entre organizaciones; no reemplaza los sistemas oficiales de despacho. '+(E.puede('recursos')?'':'Cambiar el estado de recursos y solicitudes es del responsable de recursos o de coordinación.')+'</p>'
		+'<div class="em-seg em-seg-tabs" role="tablist"><button type="button" role="tab" data-t="inventario" class="'+(pestanaRec==='inventario'?'is-on':'')+'" aria-selected="'+(pestanaRec==='inventario')+'">Inventario</button><button type="button" role="tab" data-t="solicitudes" class="'+(pestanaRec==='solicitudes'?'is-on':'')+'" aria-selected="'+(pestanaRec==='solicitudes')+'">Solicitudes</button></div>'
		+'<div id="re-c"></div></div>');
	function pintar(){ $('#re-c',v).innerHTML=pestanaRec==='inventario'?'<div data-vivo="recursos">'+E.VIVO.recursos()+'</div>':'<div data-vivo="solicitudes">'+E.VIVO.solicitudes()+'</div>'; }
	pintar();
	$$('[data-t]',v).forEach(function(b){ b.addEventListener('click', function(){ pestanaRec=b.dataset.t; $$('[data-t]',v).forEach(function(x){ x.classList.toggle('is-on', x===b); x.setAttribute('aria-selected', x===b); }); pintar(); }); });
	if(op){
		$('#re-sol',v).addEventListener('click', function(){ E.formSolicitud(); });
		$('#re-nuevo',v).addEventListener('click', function(){
			E.modal('Registrar recurso', '<form class="em-form" id="f-rec"><div class="em-tres"><label>Tipo<select class="em-campo" name="tipo">'+E.opciones(core.ET.rec_tipo, 'radio')+'</select></label><label>Cantidad<input class="em-campo" type="number" min="1" name="cant" value="1"></label><label>Identificador<input class="em-campo em-mono" name="cod" placeholder="Opcional: R-01"></label></div><label>Descripción<input class="em-campo" name="desc" required placeholder="Ej.: portátiles VHF con doble batería"></label><div class="em-dos"><label>Organización responsable<input class="em-campo" name="org"></label><label>Responsable<input class="em-campo" name="resp" required value="'+esc(E.yo.ind)+'"></label></div><label>Dónde está<input class="em-campo" name="lugar"></label><fieldset><legend>Disponibilidad</legend>'+E.chips('disp', {disponible:'Disponible', fuera_servicio:'Fuera de servicio'}, 'disponible')+'</fieldset><button class="em-btn primario">Registrar</button></form>', function(c){
				$('#f-rec',c).addEventListener('submit', function(e){ e.preventDefault(); if(!this.desc.value.trim()) return; if(E.registrar('r_reg', {tipo:this.tipo.value, cant:+this.cant.value||1, cod:this.cod.value, desc:this.desc.value, org:this.org.value, resp:this.resp.value, lugar:this.lugar.value, disp:E.valorDe(this,'disp')})) E.cerrarModal(); });
			});
		});
	}
	v.addEventListener('change', function(e){
		var s=e.target.closest('.em-rec-sel'); if(!s || !s.value) return; var r=E.S.rec[s.closest('[data-rec]').dataset.rec], est=s.value; s.value='';
		var sols=Object.keys(E.S.sol).map(function(k){ return E.S.sol[k]; }).filter(function(x){ return x.estado==='aprobada'||x.estado==='pendiente'; });
		E.modal(r.desc+' → '+et('rec_est',est), '<form class="em-form" id="f-re">'
			+(est==='asignado'||est==='reservado'?'<label>Incidente<select class="em-campo" name="inc">'+E.incidentesOpciones(r.inc)+'</select></label><label>Solicitud que atiende<select class="em-campo" name="sol"><option value="">— Ninguna —</option>'+sols.map(function(x){ return '<option value="'+x.id+'">'+x.cant+' × '+esc(et('rec_tipo',x.tipo))+' — '+esc(x.desc)+'</option>'; }).join('')+'</select></label>':'')
			+(est==='desplegado'||est==='en_desplazamiento'?'<label>Ubicación (última conocida)<input class="em-campo" name="lugar"></label><label>Coordenadas (opcional)<input class="em-campo em-mono" name="pos"></label>':'')
			+(est==='liberado'?'<label>Resultado de la asignación<input class="em-campo" name="resultado" required placeholder="Ej.: devueltos 15 de 15, sin novedad"></label>':'')
			+'<label>Nota<input class="em-campo" name="nota"></label><button class="em-btn primario">Registrar</button></form>', function(c){
			$('#f-re',c).addEventListener('submit', function(ev){
				ev.preventDefault(); var f=this, d={r:r.id, e:est, nota:f.nota.value};
				if(f.inc) d.inc=f.inc.value; if(f.sol) d.sol=f.sol.value; if(f.lugar) d.lugar=f.lugar.value; if(f.resultado){ if(!f.resultado.value.trim()) return; d.resultado=f.resultado.value; }
				if(f.pos && f.pos.value){ var p=core.parsearPosicion(f.pos.value); if(!p){ E.aviso('No se entiende la posición.', 'aviso'); return; } d.lat=+p.lat.toFixed(6); d.lng=+p.lng.toFixed(6); }
				if(E.registrar('r_est', d)){ if(d.sol){ var so=E.S.sol[d.sol]; if(so && so.estado!=='atendida') E.registrar('r_sol_est', {s:d.sol, e:'atendida', rec:r.id}); } E.cerrarModal(); }
			});
		});
	});
	v.addEventListener('click', function(e){
		var b=e.target.closest('[data-se]'); if(!b) return; var s=b.closest('[data-sol]').dataset.sol, est=b.dataset.se;
		E.modal('Solicitud → '+et('sol_est',est), '<form class="em-form" id="f-se"><label>Nota'+(est==='rechazada'?' (motivo)':'')+'<input class="em-campo" name="nota"'+(est==='rechazada'?' required':'')+'></label><button class="em-btn primario">Registrar</button></form>', function(c){ $('#f-se',c).addEventListener('submit', function(ev){ ev.preventDefault(); if(E.registrar('r_sol_est', {s:s, e:est, nota:this.nota.value})) E.cerrarModal(); }); });
	});
	return v;
}});

/* ── H. Bitácora ─────────────────────────────────────────────────────────── */
var filtroBit={q:'', g:''};
var GRUPOS_BIT={incidentes:/^i_|^decision|^accion/, mensajes:/^m|^msg/, estaciones:/^e_|^enlace|^checkin|^contacto|^sinresp|^repe/, recursos:/^r_|^recurso/, notas:/^nota/};
E.VIVO.bitacora = function(){
	var l=E.S.todo.slice().reverse().concat(E.rechazados());
	if(filtroBit.g) l=l.filter(function(e){ return GRUPOS_BIT[filtroBit.g].test(e.t); });
	if(filtroBit.q){ var q=core.normalizar(filtroBit.q); l=l.filter(function(e){ return core.normalizar(e.a+' '+E.describir(e).replace(/<[^>]+>/g,'')).indexOf(q)>=0; }); }
	return l.length?'<ol class="em-bit em-bit-larga">'+l.slice(0,500).map(E.filaBit).join('')+'</ol>'+(l.length>500?'<p class="em-nota">Se muestran los 500 más recientes; exporta para ver todo.</p>':''):'<p class="em-nota">Sin registros.</p>';
};
function textoPlano(html){ var d=document.createElement('div'); d.innerHTML=html; return d.textContent; }
E.textoSci214 = function(){
	return (E.esSim()?'*** SIMULACRO ***\n':'')+'REGISTRO DE ACTIVIDADES (adaptación del formato SCI-214)\nOperación: '+E.op.nombre+(E.op.zona?' · '+E.op.zona:'')+' · código '+E.op.codigo+'\nGenerado por: '+E.yo.ind+' · '+core.isoZ(Date.now())+'\nCada línea: fecha, hora UTC, autor, registro. (*) = solo en el dispositivo, sin confirmar en el servidor.\n\n'
		+E.S.todo.map(function(e){ return core.fechaZ(e.ts)+' '+core.horaZ(e.ts)+'  '+String(e.a||'').padEnd(9)+' '+textoPlano(E.describir(e))+(e.s?'':' (*)'); }).join('\n');
};
E.registrarVista('bitacora', {nombre:'Bitácora', icono:'☰', fn:function(){
	var op=E.puede('operar');
	var v=E.el('<div><div class="em-barra"><h1>Bitácora de la operación</h1><div class="em-acciones">'+(op?boton('bi-nota','+ Nota','primario'):'')+(E.puede('coordinar')&&!cerrada()?boton('bi-dec','+ Decisión',''):'')+boton('bi-sci','Exportar SCI-214','')+boton('bi-csv','CSV','')+'</div></div>'
		+'<div class="em-barra em-filtros"><label class="em-buscar"><span class="sr-only">Buscar en la bitácora</span><input class="em-campo" type="search" id="bi-q" placeholder="Buscar indicativo, lugar, palabra…" value="'+esc(filtroBit.q)+'"></label><select class="em-campo" id="bi-g" aria-label="Tipo de registro">'+E.opciones({incidentes:'Incidentes y decisiones', mensajes:'Mensajes', estaciones:'Estaciones y enlaces', recursos:'Recursos', notas:'Notas'}, filtroBit.g, 'Todo')+'</select></div>'
		+'<p class="em-nota">Cronología unificada de todos los operadores, en hora UTC. Nada se borra: las correcciones son registros nuevos.</p><div data-vivo="bitacora">'+E.VIVO.bitacora()+'</div></div>');
	$('#bi-q',v).addEventListener('input', function(){ filtroBit.q=this.value; E.refrescar(); });
	$('#bi-g',v).addEventListener('change', function(){ filtroBit.g=this.value; E.refrescar(); });
	var b=$('#bi-nota',v); if(b) b.addEventListener('click', function(){
		E.modal('Nota en la bitácora', '<form class="em-form" id="f-nota"><fieldset><legend>Tipo</legend>'+E.chips('tipo', {Novedad:'Novedad', Solicitud:'Solicitud', Nota:'Nota'}, 'Novedad')+'</fieldset><label>Incidente<select class="em-campo" name="inc">'+E.incidentesOpciones('')+'</select></label><label>Texto<textarea class="em-campo" name="texto" rows="3" required></textarea></label><button class="em-btn primario">Guardar</button></form>', function(c){
			$('#f-nota',c).addEventListener('submit', function(e){ e.preventDefault(); if(!this.texto.value.trim()) return; if(E.registrar('nota', {tipo:E.valorDe(this,'tipo'), texto:this.texto.value, inc:this.inc.value})) E.cerrarModal(); });
		});
	});
	b=$('#bi-dec',v); if(b) b.addEventListener('click', function(){
		E.modal('Registrar decisión', '<form class="em-form" id="f-dec"><label>Decisión<textarea class="em-campo" name="texto" rows="3" required></textarea></label><label>Quién decidió<input class="em-campo" name="autoridad"></label><label>Incidente<select class="em-campo" name="id">'+E.incidentesOpciones('')+'</select></label><button class="em-btn primario">Registrar</button></form>', function(c){
			$('#f-dec',c).addEventListener('submit', function(e){ e.preventDefault(); if(!this.texto.value.trim()) return; if(E.registrar('decision', {texto:this.texto.value, autoridad:this.autoridad.value, id:this.id.value})) E.cerrarModal(); });
		});
	});
	$('#bi-sci',v).addEventListener('click', function(){ E.descargar(E.nombreArchivo('bitacora-sci214','txt'), E.textoSci214()); });
	$('#bi-csv',v).addEventListener('click', function(){ E.descargar(E.nombreArchivo('bitacora','csv'), core.csv([['fecha_utc','autor','tipo','registro','en_servidor']].concat(E.S.todo.map(function(e){ return [new Date(e.ts).toISOString(), e.a, e.t, textoPlano(E.describir(e)), e.s?'sí':'no']; }))), 'text/csv;charset=utf-8'); });
	return v;
}});

/* ── I. Informes, calidad de datos y exportaciones ───────────────────────── */
E.VIVO.calidad = function(){
	var con=E.S.conflictos.filter(function(c){ return !c.resuelto; }), dup=core.duplicados(E.S), I=core.indicadores(E.S, {}, Date.now(), E.ventana), h='';
	h+='<h3>Cambios simultáneos sin resolver ('+con.length+')</h3>';
	h+=con.length?'<div class="em-lista">'+con.map(function(c){ return '<div class="em-fila em-borde-aviso"><span>'+esc(c.ent)+': dos personas cambiaron el estado sin ver el cambio del otro.<small>'+c.eventos.map(function(u){ var e=E.S.porU[u]; return e?esc(e.a)+' '+core.horaZ(e.ts)+': '+textoPlano(E.describir(e)):''; }).join(' · ')+'</small></span>'+(E.puede('operar')&&!cerrada()?'<span class="em-acciones">'+c.eventos.map(function(u, i){ return '<button type="button" class="em-btn" data-resuelve="'+esc(c.ent)+'" data-gana="'+u+'">Vale el '+(i+1)+'.º</button>'; }).join('')+'</span>':'')+'</div>'; }).join('')+'</div>':'<p class="em-nota">Ninguno.</p>';
	h+='<h3>Posibles duplicados ('+dup.length+')</h3>'+(dup.length?'<button type="button" class="em-link" data-ir="incidentes">Revisar en Incidentes →</button>':'<p class="em-nota">Ninguno.</p>');
	h+='<h3>Datos vencidos ('+I.vencidos.length+')</h3>'+(I.vencidos.length?'<ul class="em-lista-txt">'+I.vencidos.map(function(x){ return '<li>'+esc(x.texto)+'</li>'; }).join('')+'</ul>':'<p class="em-nota">Ninguno.</p>');
	var sinVer=Object.keys(E.S.inc).map(function(k){ return E.S.inc[k]; }).filter(function(x){ return core.incidenteActivo(x) && x.verif!=='verificado'; });
	h+='<h3>Incidentes activos sin verificar ('+sinVer.length+')</h3>'+(sinVer.length?'<ul class="em-lista-txt">'+sinVer.map(function(x){ return '<li><button type="button" class="em-link" data-inc="'+x.id+'">'+esc(x.titulo)+'</button> · '+esc(et('prio',x.prio))+'</li>'; }).join('')+'</ul>':'<p class="em-nota">Ninguno.</p>');
	var sinUbic=Object.keys(E.S.inc).map(function(k){ return E.S.inc[k]; }).filter(function(x){ return core.incidenteActivo(x) && x.lat===undefined; });
	h+='<h3>Vacíos de información</h3><ul class="em-lista-txt"><li>'+sinUbic.length+' incidentes activos sin coordenadas.</li><li>'+Object.keys(E.S.inc).filter(function(k){ return core.incidenteActivo(E.S.inc[k]) && !E.S.inc[k].resp; }).length+' incidentes activos sin responsable.</li><li>'+Object.keys(E.S.est).filter(function(k){ return !E.S.est[k].verif; }).length+' estaciones nunca verificadas.</li><li>'+E.pendientes().length+' registros de este dispositivo sin enviar al servidor.</li></ul>';
	return h;
};
document.addEventListener('click', function(e){ var b=e.target.closest('[data-resuelve]'); if(!b) return; E.modal('Resolver conflicto', '<form class="em-form" id="f-res"><label>Por qué vale ese<input class="em-campo" name="nota" required></label><button class="em-btn primario">Resolver</button></form>', function(c){ $('#f-res',c).addEventListener('submit', function(ev){ ev.preventDefault(); if(!this.nota.value.trim()) return; if(E.registrar('resuelve', {ent:b.dataset.resuelve, gana:b.dataset.gana, nota:this.nota.value})) E.cerrarModal(); }); }); });
var pestanaInf='sitrep';
E.registrarVista('informes', {nombre:'Informes', icono:'📄', fn:function(p){
	if(p.filtro==='calidad') pestanaInf='calidad';
	var v=E.el('<div><h1>Inteligencia operacional e informes</h1><div class="em-seg em-seg-tabs" role="tablist">'+[['sitrep','SITREP'],['cierre','Cierre y lecciones'],['calidad','Calidad de los datos'],['exportar','Exportar']].map(function(o){ return '<button type="button" role="tab" data-t="'+o[0]+'" aria-selected="'+(pestanaInf===o[0])+'" class="'+(pestanaInf===o[0]?'is-on':'')+'">'+o[1]+'</button>'; }).join('')+'</div><div id="if-c"></div></div>');
	function pintar(){
		var c=$('#if-c',v);
		if(pestanaInf==='sitrep'){
			var t=core.sitrep(E.S, E.op, {corte:Date.now(), responsable:E.yo.ind, ventanaMin:E.ventana});
			c.innerHTML='<p class="em-nota">Generado de los registros con fecha de corte de ahora. Incluye fuentes, responsable y limitaciones; no estima cifras.</p><div class="em-acciones">'+boton('if-copiar','Copiar','')+boton('if-imp','Imprimir / PDF','')+boton('if-txt','Descargar .txt','')+boton('if-json','Datos estructurados (JSON)','')+'</div><pre class="em-leer em-informe">'+esc(t)+'</pre>';
			function reg(){ if(E.puede('operar') && !cerrada()) E.registrar('informe', {tipo:'sitrep', corte:Date.now()}); }
			$('#if-copiar',c).addEventListener('click', function(){ E.copiar(t); reg(); });
			$('#if-imp',c).addEventListener('click', function(){ E.imprimir('SITREP '+E.op.nombre, t); reg(); });
			$('#if-txt',c).addEventListener('click', function(){ E.descargar(E.nombreArchivo('sitrep','txt'), t); reg(); });
			$('#if-json',c).addEventListener('click', function(){ var I=core.indicadores(E.S, {}, Date.now(), E.ventana); E.descargar(E.nombreArchivo('sitrep','json'), JSON.stringify({operacion:{codigo:E.op.codigo, nombre:E.op.nombre, simulacro:E.esSim()}, corte:new Date().toISOString(), responsable:E.yo.ind, indicadores:{incidentes:I.incidentes, recursos:I.recursos, estaciones:I.estaciones, mensajes:I.mensajes}, incidentes:Object.keys(E.S.inc).map(function(k){ var x=E.S.inc[k]; return {id:x.id, titulo:x.titulo, tipo:x.tipo, prioridad_reporte:x.prio, prioridad_despacho:x.desp, verificacion:x.verif, estado:x.estado, lugar:x.lugar, precision:x.precision, fuente:x.fuente, recibido:new Date(x.recibido).toISOString(), actualizado:new Date(x.act).toISOString(), necesidades:x.necesidades, afectados_segun_fuente:x.afectados, fusionado_en:x.fusionadoEn||null}; }), limitaciones:'Registros de operadores; no es un informe oficial; no incluye estimaciones.'}, null, 2), 'application/json'); reg(); });
		}
		else if(pestanaInf==='cierre'){
			var t2=core.informeCierre(E.S, E.op, {responsable:E.yo.ind});
			c.innerHTML=(E.puede('coordinar')?'<form class="em-form em-tarjeta" id="if-lec"><h2>Agregar lección aprendida</h2><fieldset><legend>Categoría</legend>'+E.chips('cat', core.ET.leccion_cat, 'mejorar')+'</fieldset><label>Lección<textarea class="em-campo" name="texto" rows="2" required></textarea></label><button class="em-btn primario">Agregar</button></form>':'<p class="em-nota">Las lecciones aprendidas las registra coordinación.</p>')
				+'<div class="em-acciones">'+boton('if-c-imp','Imprimir / PDF','')+boton('if-c-txt','Descargar .txt','')+'</div><pre class="em-leer em-informe">'+esc(t2)+'</pre>';
			var f=$('#if-lec',c); if(f) f.addEventListener('submit', function(e){ e.preventDefault(); if(!this.texto.value.trim()) return; if(E.registrar('leccion', {cat:E.valorDe(this,'cat'), texto:this.texto.value})) pintar(); });
			$('#if-c-imp',c).addEventListener('click', function(){ E.imprimir('Informe de cierre '+E.op.nombre, t2); if(E.puede('operar')) E.registrar('informe', {tipo:'cierre', corte:Date.now()}); });
			$('#if-c-txt',c).addEventListener('click', function(){ E.descargar(E.nombreArchivo('informe-cierre','txt'), t2); });
		}
		else if(pestanaInf==='calidad'){ c.innerHTML='<section class="em-tarjeta"><div data-vivo="calidad">'+E.VIVO.calidad()+'</div></section>'; }
		else {
			c.innerHTML='<div class="em-grid2"><section class="em-tarjeta"><h2>Geográfico</h2><p class="em-nota">GeoJSON con incidentes, puntos del mapa, estaciones y última ubicación de recursos, cada uno con fuente, hora y verificación. Sin datos de personas.</p><button type="button" class="em-btn" id="ex-geo">GeoJSON</button></section>'
				+'<section class="em-tarjeta"><h2>Tablas (CSV)</h2><div class="em-acciones"><button type="button" class="em-btn" data-csv="inc">Incidentes</button><button type="button" class="em-btn" data-csv="msg">Mensajes</button><button type="button" class="em-btn" data-csv="est">Estaciones</button><button type="button" class="em-btn" data-csv="rec">Recursos</button></div></section>'
				+'<section class="em-tarjeta"><h2>Respaldo completo</h2><p class="em-nota">Todos los registros de este dispositivo (incluidos los sin enviar). Sirve para pasarlos a otro dispositivo sin internet: allá se importan y se combinan sin duplicar.</p><div class="em-acciones"><button type="button" class="em-btn" id="ex-json">Descargar respaldo</button><label class="em-btn em-archivo">Importar respaldo<input type="file" id="ex-imp" accept=".json,application/json"></label></div></section>'
				+'<section class="em-tarjeta"><h2>CAP</h2><p class="em-nota">Cada incidente se exporta en CAP 1.2 desde su ficha, en estado «Draft» (o «Exercise» en simulacro) y alcance restringido. No es una alerta pública.</p></section></div>';
			$('#ex-geo',c).addEventListener('click', function(){ E.descargar(E.nombreArchivo('operacion','geojson'), JSON.stringify(core.geojson(E.S, E.op)), 'application/geo+json'); });
			$$('[data-csv]',c).forEach(function(b){ b.addEventListener('click', function(){ E.descargar(E.nombreArchivo(b.dataset.csv,'csv'), csvDe(b.dataset.csv), 'text/csv;charset=utf-8'); }); });
			$('#ex-json',c).addEventListener('click', function(){ E.descargar(E.nombreArchivo('respaldo','json'), JSON.stringify({rau_em:2, generado:Date.now(), operacion:{codigo:E.op.codigo, nombre:E.op.nombre, tipo:E.op.tipo, zona:E.op.zona}, eventos:E.eventos.filter(function(e){ return e.s!=='r'; }).map(function(e){ return {u:e.u, t:e.t, a:e.a, ts:e.ts, d:e.d}; })}), 'application/json'); });
			$('#ex-imp',c).addEventListener('change', function(){ importar(this.files[0]); this.value=''; });
		}
	}
	pintar();
	$$('[data-t]',v).forEach(function(b){ b.addEventListener('click', function(){ pestanaInf=b.dataset.t; $$('[data-t]',v).forEach(function(x){ x.classList.toggle('is-on', x===b); x.setAttribute('aria-selected', x===b); }); pintar(); }); });
	return v;
}});
function csvDe(q){
	var S=E.S, ahora=Date.now();
	if(q==='inc') return core.csv([['id','titulo','tipo','prioridad_reporte','prioridad_despacho','verificacion','estado','lugar','precision','lat','lng','fuente','recibido_utc','actualizado_utc','responsable','necesidades','afectados_segun_fuente']].concat(Object.keys(S.inc).map(function(k){ var x=S.inc[k]; return [x.id, x.titulo, x.tipo, x.prio, x.desp?x.desp.prio:'', x.verif, x.estado, x.lugar, x.precision, x.lat, x.lng, x.fuente, new Date(x.recibido).toISOString(), new Date(x.act).toISOString(), x.resp, x.necesidades, x.afectados]; })));
	if(q==='msg') return core.csv([['id','numero','precedencia','tipo','origen','para','estado','creado_utc','ultimo_cambio_utc','intentos','canal_origen','canal_destino','texto']].concat(Object.keys(S.msgs).map(function(k){ var m=S.msgs[k], u=m.estados[m.estados.length-1]; return [m.id, m.d.num, m.d.prec, m.d.tipo, m.d.origen||m.a, m.d.para, m.estado, new Date(m.ts).toISOString(), new Date(u.ts).toISOString(), m.intentos.length, m.d.canal_o, m.d.canal_d, m.d.texto]; })));
	if(q==='est') return core.csv([['indicativo','nombre','organizacion','estado','calidad','motivo','ultimo_contacto_utc','ultima_verificacion_utc','lugar','lat','lng','medios']].concat(Object.keys(S.est).map(function(k){ var x=S.est[k], s=core.estadoEstacion(x, ahora, E.ventana); return [x.ind, x.nombre, x.org, s.e, s.calidad, s.motivo, x.contacto?new Date(x.contacto).toISOString():'', x.verif?new Date(x.verif.ts).toISOString():'', x.lugar, x.lat, x.lng, (x.medios||[]).join(' / ')]; })));
	return core.csv([['id','codigo','tipo','descripcion','cantidad','organizacion','responsable','estado','incidente','ultima_ubicacion','ubicacion_utc','actualizado_utc']].concat(Object.keys(S.rec).map(function(k){ var r=S.rec[k]; return [r.id, r.cod, r.tipo, r.desc, r.cant, r.org, r.resp, r.estado, r.inc, r.ubic?r.ubic.lugar:'', r.ubic?new Date(r.ubic.ts).toISOString():'', new Date(r.act).toISOString()]; })));
}
function importar(f){
	if(!f) return; var lector=new FileReader();
	lector.onload=function(){ try {
		var j=JSON.parse(lector.result); if(!j.rau_em || !Array.isArray(j.eventos)) throw 0;
		if(j.operacion && j.operacion.tipo && j.operacion.tipo!==E.op.tipo){ E.aviso('No se mezclan simulacros con operaciones reales: ese respaldo es de un '+(j.operacion.tipo==='simulacro'?'simulacro':'operación real')+'.', 'peligro'); return; }
		var hay={}, nuevos=[], malos=0; E.eventos.forEach(function(e){ hay[e.u]=1; });
		j.eventos.forEach(function(e){
			if(!e || !e.u || !e.t || hay[e.u]) return;
			if(core.validar({t:e.t, d:e.d||{}}, {sim:E.esSim()})){ malos++; return; }
			var n={u:e.u, t:e.t, a:e.a, ts:e.ts, d:e.d||{}, s:0}; nuevos.push(n); hay[e.u]=1;
		});
		E.eventos=E.eventos.concat(nuevos); E.db.guardarEventos(E.op.codigo, nuevos); E.recalcular(); E.refrescar(); E.sincronizarPronto();
		E.aviso(nuevos.length+' registros importados'+(malos?' · '+malos+' no cumplen el esquema y se ignoraron':'')+'. Se enviarán al servidor con tu sesión; quedan con tu rol como autor del envío.');
	} catch(e){ E.aviso('Ese archivo no es un respaldo del centro de mando.', 'aviso'); } };
	lector.readAsText(f);
}

/* ── Bienestar ───────────────────────────────────────────────────────────── */
var filtroPer='';
E.VIVO.personas = function(){
	var l=Object.keys(E.S.personas).map(function(k){ return E.S.personas[k]; }).sort(function(a,b){ return (b.act||b.ts)-(a.act||a.ts); });
	if(filtroPer){ var q=core.normalizar(filtroPer); l=l.filter(function(p){ return core.normalizar(p.d.nombre+' '+(p.d.ubic||'')+' '+(p.d.contacto||'')).indexOf(q)>=0; }); }
	if(!l.length) return '<p class="em-nota">'+(filtroPer?'Nadie con ese nombre.':'Sin personas registradas.')+'</p>';
	var cls={bien:'ok', herido:'aviso', albergue:'ok', sinnoticias:'peligro', buscado:'aviso', encontrado:'ok'};
	return '<div class="em-lista">'+l.map(function(p){
		return '<div class="em-fila em-per em-borde-'+cls[p.estado]+'" data-per="'+p.id+'"><span><strong>'+esc(p.d.nombre)+'</strong> '+E.pill(cls[p.estado], et('persona_e',p.estado))+(p.d.redactado?' '+E.pill('apagado','Datos reducidos'):'')+'<small>'+esc(p.d.ubic||'Ubicación desconocida')+(p.d.contacto?' · avisar a '+esc(p.d.contacto):'')+' · registró '+esc(p.a)+' '+E.haceTxt(p.ts)+'</small>'+(p.d.msg?'<em>«'+esc(p.d.msg)+'»</em>':'')+'</span>'+(E.puede('operar')&&!cerrada()&&!p.d.redactado?'<button type="button" class="em-btn" data-per-act>Actualizar</button>':'')+'</div>';
	}).join('')+'</div>';
};
E.registrarVista('personas', {nombre:'Bienestar', icono:'♥', fn:function(){
	var v=E.el('<div><div class="em-barra"><h1>Bienestar</h1>'+(E.puede('operar')&&!cerrada()?boton('pe-nueva','+ Persona','primario'):'')+'</div>'
		+'<p class="em-nota">Mensajes «estoy bien» y búsqueda de familiares. Solo lo ven operadores y coordinación de esta operación (los observadores no ven datos personales). No registres diagnósticos médicos ni números de documento. Al cerrar la operación y pasar el plazo de conservación, estos datos se reducen a iniciales.</p>'
		+'<label class="em-buscar"><span class="sr-only">Buscar persona</span><input class="em-campo" type="search" id="pe-q" placeholder="Buscar por nombre o lugar…" value="'+esc(filtroPer)+'"></label><div data-vivo="personas">'+E.VIVO.personas()+'</div></div>');
	$('#pe-q',v).addEventListener('input', function(){ filtroPer=this.value; E.refrescar(); });
	var n=$('#pe-nueva',v); if(n) n.addEventListener('click', function(){
		E.modal('Registrar persona', '<form class="em-form" id="f-per"><label>Nombre completo<input class="em-campo" name="nombre" required></label><fieldset><legend>Estado</legend>'+E.chips('estado', core.ET.persona_e, 'bien')+'</fieldset><label>Dónde está (o última vez que se le vio)<input class="em-campo" name="ubic"></label><label>A quién avisar<input class="em-campo" name="contacto"></label><label>Mensaje para la familia<input class="em-campo" name="msg"></label><button class="em-btn primario">Registrar</button></form>', function(c){
			$('#f-per',c).addEventListener('submit', function(e){ e.preventDefault(); if(!this.nombre.value.trim()) return; if(E.registrar('persona', {nombre:this.nombre.value, estado:E.valorDe(this,'estado'), ubic:this.ubic.value, contacto:this.contacto.value, msg:this.msg.value})) E.cerrarModal(); });
		});
	});
	v.addEventListener('click', function(e){
		var b=e.target.closest('[data-per-act]'); if(!b) return; var p=E.S.personas[b.closest('[data-per]').dataset.per];
		E.modal('Actualizar: '+p.d.nombre, '<form class="em-form" id="f-pa"><fieldset><legend>Estado</legend>'+E.chips('estado', core.ET.persona_e, p.estado)+'</fieldset><label>Ubicación<input class="em-campo" name="ubic" value="'+esc(p.d.ubic||'')+'"></label><label>Nota<input class="em-campo" name="n"></label><button class="em-btn primario">Guardar</button></form>', function(c){
			$('#f-pa',c).addEventListener('submit', function(ev){ ev.preventDefault(); if(E.registrar('personaact', {p:p.id, estado:E.valorDe(this,'estado'), ubic:this.ubic.value, n:this.n.value})) E.cerrarModal(); });
		});
	});
	return v;
}});

/* ── Directorio: frecuencias (global) y contactos (de la operación) ─────── */
E.VIVO.dirFrec = function(){
	var d=E.directorio; if(!d) return '<p class="em-nota">Cargando… (necesita una primera visita con internet)</p>';
	var editar=E.cuenta && (E.cuenta.rol==='admin'||E.cuenta.rol==='coordinador');
	return Object.keys(d.categorias).map(function(cat){
		var l=d.entradas.filter(function(x){ return x.cat===cat; });
		return '<h3>'+esc(d.categorias[cat])+' ('+l.length+')</h3>'+(l.length?tablaDirectorio(l, editar):'<p class="em-nota">'+(cat==='institucional'?'Vacío a propósito: no se publican frecuencias de organismos sin una fuente oficial verificable.':'Ninguna.')+'</p>');
	}).join('')+'<p class="em-nota">Recibido '+E.haceTxt(d.recibido)+'. Que una frecuencia figure aquí no garantiza que alguien esté escuchando ni que el equipo esté en el aire.</p>';
};
E.VIVO.dir = function(){
	var l=Object.keys(E.S.dir).map(function(k){ return E.S.dir[k]; });
	return l.length?'<div class="em-lista">'+l.map(function(x){ return '<div class="em-fila"><span><strong>'+esc(x.d.nombre)+'</strong> · '+esc(x.d.funcion||'')+'<small>'+esc(x.d.contacto||'')+(x.d.nota?' · '+esc(x.d.nota):'')+'</small></span>'+(E.puede('operar')&&!cerrada()?'<button type="button" class="em-x" data-dirx="'+x.id+'" aria-label="Quitar '+esc(x.d.nombre)+'">×</button>':'')+'</div>'; }).join('')+'</div>':'<p class="em-nota">Sin contactos: puesto de mando, consejo de gestión del riesgo, Cruz Roja local, albergues…</p>';
};
function formDirectorio(x){
	x=x||{};
	E.modal(x.id?'Editar entrada del directorio':'Nueva entrada del directorio', '<form class="em-form" id="f-df"><p class="em-nota">Todo cambio exige fuente y queda en la auditoría con el antes y el después. No agregues frecuencias de organismos sin una fuente oficial.</p>'
		+'<div class="em-tres"><label>Categoría<select class="em-campo" name="cat">'+E.opciones(Object.keys(E.directorio.categorias).filter(function(k){ return k!=='repetidora_ra' || !x.fijo; }).reduce(function(o,k){ o[k]=E.directorio.categorias[k]; return o; }, {}), x.cat||'pendiente')+'</select></label><label>Frecuencia<input class="em-campo em-mono" name="f" required value="'+esc(x.f||'')+'"></label><label>Unidad<select class="em-campo" name="unidad">'+E.opciones({MHz:'MHz', kHz:'kHz'}, x.unidad||'MHz')+'</select></label></div>'
		+'<div class="em-tres"><label>Banda<input class="em-campo" name="banda" value="'+esc(x.banda||'')+'"></label><label>Modo<input class="em-campo" name="modo" value="'+esc(x.modo||'')+'"></label><label>Tono y desplazamiento<input class="em-campo" name="tono" value="'+esc(x.tono||'')+'" placeholder="Tono"><input class="em-campo" name="desplazamiento" value="'+esc(x.desplazamiento||'')+'" placeholder="Desplazamiento"></label></div>'
		+'<label>Uso<input class="em-campo" name="uso" value="'+esc(x.uso||'')+'"></label><label>Condiciones de uso<input class="em-campo" name="condiciones" value="'+esc(x.condiciones||'')+'"></label><div class="em-dos"><label>Jurisdicción<input class="em-campo" name="jurisdiccion" value="'+esc(x.jurisdiccion||'')+'"></label><label>Verificado (AAAA-MM-DD)<input class="em-campo" name="verificado" value="'+esc(x.verificado||'')+'" pattern="\\d{4}-\\d{2}-\\d{2}"></label></div>'
		+'<label>Fuente *<input class="em-campo" name="fuente" required value="'+esc(x.fuente||'')+'"></label><button class="em-btn primario">Guardar</button><p class="em-error" id="f-df-err" role="alert"></p></form>', function(c){
		$('#f-df',c).addEventListener('submit', function(e){
			e.preventDefault(); var f=this, d={id:x.id||''}; $$('input,select',f).forEach(function(i){ if(i.name) d[i.name]=i.value.trim(); });
			E.rest('directorio', {metodo:'POST', token:false, cuerpo:d}).then(function(r){ if(r.estado===200){ E.cerrarModal(); E.aviso('Directorio actualizado.'); E.rest('directorio', {token:false}).then(function(r2){ if(r2.estado===200){ E.directorio=r2.j; E.directorio.recibido=Date.now(); E.db.kvSet('directorio', E.directorio); E.refrescar(); } }); } else $('#f-df-err',c).textContent=(r.j&&r.j.error)||'Error'; }).catch(function(){ $('#f-df-err',c).textContent='Sin conexión.'; });
		});
	}, true);
}
E.registrarVista('directorio', {nombre:'Directorio', icono:'☎', fn:function(){
	var editar=E.cuenta && (E.cuenta.rol==='admin'||E.cuenta.rol==='coordinador');
	var v=E.el('<div><h1>Directorio</h1>'
		+'<section class="em-tarjeta"><div class="em-barra"><h2>Contactos de esta operación</h2>'+(E.puede('operar')&&!cerrada()?boton('di-nuevo','+ Contacto',''):'')+'</div><div data-vivo="dir">'+E.VIVO.dir()+'</div></section>'
		+'<section class="em-tarjeta"><h2>Teléfonos nacionales</h2>'+E.telefonos()+'</section>'
		+'<section class="em-tarjeta"><div class="em-barra"><h2>Frecuencias</h2>'+(editar?boton('di-frec','+ Entrada',''):'')+'</div><div data-vivo="dirFrec">'+E.VIVO.dirFrec()+'</div></section></div>');
	var b=$('#di-nuevo',v); if(b) b.addEventListener('click', function(){
		E.modal('Agregar contacto', '<form class="em-form" id="f-dir"><label>Nombre o entidad<input class="em-campo" name="nombre" required></label><label>Función<input class="em-campo" name="funcion"></label><label>Cómo contactarlo<input class="em-campo" name="contacto" placeholder="Teléfono, frecuencia, indicativo"></label><label>Nota<input class="em-campo" name="nota"></label><button class="em-btn primario">Agregar</button></form>', function(c){
			$('#f-dir',c).addEventListener('submit', function(e){ e.preventDefault(); if(!this.nombre.value.trim()) return; if(E.registrar('dir', {nombre:this.nombre.value, funcion:this.funcion.value, contacto:this.contacto.value, nota:this.nota.value})) E.cerrarModal(); });
		});
	});
	b=$('#di-frec',v); if(b) b.addEventListener('click', function(){ formDirectorio(); });
	v.addEventListener('click', function(e){
		var x=e.target.closest('[data-dirx]'); if(x) E.confirmar('Quitar contacto', 'Queda constancia en la bitácora.', 'Quitar', true).then(function(si){ if(si) E.registrar('dirx', {p:x.dataset.dirx}); });
		var ed=e.target.closest('[data-dir-edit]'); if(ed) formDirectorio(E.directorio.entradas.filter(function(y){ return y.id===ed.dataset.dirEdit; })[0]);
	});
	return v;
}});

/* ── Herramientas de campo ───────────────────────────────────────────────── */
E.registrarVista('herramientas', {nombre:'Herramientas', icono:'✎', fn:function(){
	var v=E.el('<div><h1>Herramientas de campo</h1><div class="em-grid2">'
		+'<section class="em-tarjeta"><h2>Autonomía de batería</h2><form class="em-form" id="h-bat"><div class="em-tres"><label>Capacidad (Ah)<input class="em-campo" type="number" step="0.1" name="ah" value="7"></label><label>Al recibir (A)<input class="em-campo" type="number" step="0.01" name="rx" value="0.4"></label><label>Al transmitir (A)<input class="em-campo" type="number" step="0.1" name="tx" value="8"></label></div><label>Tiempo transmitiendo: <span id="h-bat-p">10</span> %<input type="range" name="pct" min="0" max="50" value="10"></label></form><p class="em-resultado" id="h-bat-r" aria-live="polite"></p><p class="em-nota">Usa el 80 % de la capacidad: descargar del todo una batería de plomo la daña. Es un cálculo, no una medición.</p></section>'
		+'<section class="em-tarjeta"><h2>Dipolo NVIS</h2><form class="em-form" id="h-nvis"><label>Frecuencia (MHz)<input class="em-campo" type="number" step="0.001" name="f" value="7.060"></label></form><p class="em-resultado" id="h-nvis-r" aria-live="polite"></p><p class="em-nota">Dipolo horizontal y bajo: la señal sube casi vertical y vuelve en 0 a 500 km. Sirve para enlazar municipios cuando caen las repetidoras.</p></section>'
		+'<section class="em-tarjeta"><h2>Deletreo fonético</h2><form class="em-form" id="h-fon"><input class="em-campo em-mono" name="t" aria-label="Texto a deletrear" placeholder="Indicativo o palabra" autocapitalize="characters"></form><p class="em-resultado" id="h-fon-r" aria-live="polite"></p></section>'
		+'<section class="em-tarjeta"><h2>Posición</h2><form class="em-form" id="h-pos"><label>Convertir una posición<input class="em-campo em-mono" name="p" placeholder="4.81, -75.69 · 4°48\'N 75°41\'W · FJ24ep"></label></form><button type="button" class="em-btn" id="h-gps">📍 Leer GPS</button><p class="em-resultado" id="h-pos-r" aria-live="polite"></p><p class="em-nota">El GPS funciona sin internet.</p></section>'
		+'</div></div>');
	function bat(){ var f=$('#h-bat',v), p=+f.pct.value/100, a=(+f.rx.value)*(1-p)+(+f.tx.value)*p, h=a>0?(+f.ah.value)*0.8/a:0; $('#h-bat-p',v).textContent=f.pct.value; $('#h-bat-r',v).textContent=a>0?'Consumo medio '+a.toFixed(2)+' A → unas '+(h>=1?Math.floor(h)+' h '+Math.round((h%1)*60)+' min':Math.round(h*60)+' min'):''; }
	$('#h-bat',v).addEventListener('input', bat); bat();
	function nvis(){ var f=+$('#h-nvis',v).f.value; if(!(f>0)){ $('#h-nvis-r',v).textContent=''; return; } var t=142.5/f, l=300/f; $('#h-nvis-r',v).innerHTML='Cada brazo: <strong>'+(t/2).toFixed(2)+' m</strong> (total '+t.toFixed(2)+' m)<br>Altura: <strong>'+(l*0.1).toFixed(1)+' a '+(l*0.2).toFixed(1)+' m</strong> sobre el suelo'; }
	$('#h-nvis',v).addEventListener('input', nvis); nvis();
	$('#h-fon',v).addEventListener('input', function(){ $('#h-fon-r',v).textContent=core.fonetico(this.t.value); });
	function pos(p, extra){ var r=$('#h-pos-r',v); if(!p){ r.textContent='No se entiende esa posición.'; return; } var cerca=null; (C.repes||[]).forEach(function(x){ var d=core.distanciaKm(p.lat,p.lng,x.lat,x.lng); if(!cerca||d<cerca.d) cerca={d:d,x:x}; }); r.innerHTML='Locator <strong class="em-mono">'+core.locator(p.lat,p.lng)+'</strong><br>'+p.lat.toFixed(5)+', '+p.lng.toFixed(5)+(extra||'')+(cerca?'<br>Repetidora RAU más cercana: '+esc(cerca.x.n)+' '+esc(cerca.x.f)+' a '+Math.round(cerca.d)+' km (en línea recta; no garantiza cobertura)':''); }
	$('#h-pos',v).addEventListener('input', function(){ if(this.p.value) pos(core.parsearPosicion(this.p.value)); });
	$('#h-pos',v).addEventListener('submit', function(e){ e.preventDefault(); });
	$('#h-gps',v).addEventListener('click', function(){ gps(function(p){ pos(p, ' (±'+Math.round(p.acc)+' m)'); }); });
	return v;
}});
})();
