/* Centro de Mando de Emergencias — sala de mando y control de red.
 * Sala: estado de la operación de un vistazo, alarmas con antigüedad, red, tráfico, incidentes y registro en vivo.
 * Red: consola del control de red (abrir/dirigir/cerrar, cola de palabra, dar paso, guiones, ICS-309).
 * Línea de comandos para registrar al ritmo de la radio. Alarmas con sonido y aviso del sistema.
 * Período operacional (SCI-202), relevo de turno y ajustes de las alarmas.
 */
(function(){
'use strict';
var E=window.RauEm, core=E.core, $=E.$, $$=E.$$, esc=E.esc, et=core.et, DB=E.db;
var CLASE_NIVEL={critica:'peligro', alta:'aviso', media:'apagado', baja:'apagado'};
var CLASE_PRIO={critica:'peligro', alta:'aviso', media:'ok', baja:'apagado'};
var CLASE_PREC={E:'peligro', P:'aviso', B:'ok', R:'apagado'};
function cerrada(){ return E.S.cerrada; }
function puedeOperar(){ return E.puede('operar') && !cerrada(); }
function mm(min){ if(min===Infinity) return '—'; min=Math.max(0, Math.floor(min)); return min<60 ? min+' min' : Math.floor(min/60)+' h '+E.dos(min%60); }
function reloj(ms){ ms=Math.max(0, ms); var m=Math.floor(ms/60000); return Math.floor(m/60)+':'+E.dos(m%60); }
var MESES=['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
/* Grupo fecha-hora: 261405Z OCT 26 */
E.gfh = function(ts){ var d=new Date(ts); return E.dos(d.getUTCDate())+E.dos(d.getUTCHours())+E.dos(d.getUTCMinutes())+'Z '+MESES[d.getUTCMonth()]+' '+String(d.getUTCFullYear()).slice(2); };
function inicioOp(){ return E.S.todo.length ? E.S.todo[0].ts : Date.now(); }

/* ── Alarmas: enterado por dispositivo, sonido y aviso del sistema ───────── */
var ack={}, anunciadas={}, sonido=false;
E.alEntrar.push(function(){ ack={}; anunciadas={}; DB.kvGet('sonido').then(function(v){ sonido=!!v; }).catch(function(){}); DB.kvGet('ack:'+E.op.codigo).then(function(v){ ack=v||{}; revisarAlarmas(); }).catch(function(){}); });
E.alarmasActivas = function(){ return E.op ? core.alarmas(E.S, Date.now()) : []; };
var RANGO={critica:0, alta:1, media:2, baja:3};
// Enterada si alguien la atendió en este dispositivo con el mismo nivel o uno más grave; si sube de nivel, vuelve a avisar
function enterada(a){ return !!ack[a.id] && RANGO[ack[a.id]]<=RANGO[a.nivel]; }
function marcarEnterado(ids){ ids.forEach(function(id){ var a=E.alarmasActivas().filter(function(x){ return x.id===id; })[0]; if(a) ack[a.id]=a.nivel; }); if(E.op) DB.kvSet('ack:'+E.op.codigo, ack).catch(function(){}); revisarAlarmas(); refrescarSala(); }
var audio=null;
function pitar(nivel){
	try {
		audio=audio||new (window.AudioContext||window.webkitAudioContext)();
		var t=audio.currentTime, n=nivel==='critica'?3:1;
		for(var i=0;i<n;i++){ var o=audio.createOscillator(), g=audio.createGain(), t0=t+i*0.32;
			o.type='square'; o.frequency.value=nivel==='critica'?880:660; g.gain.setValueAtTime(0.0001, t0); g.gain.exponentialRampToValueAtTime(0.18, t0+0.02); g.gain.exponentialRampToValueAtTime(0.0001, t0+0.24);
			o.connect(g); g.connect(audio.destination); o.start(t0); o.stop(t0+0.26); }
	} catch(e){}
}
function revisarAlarmas(){
	var b=$('#em-alarmas'); if(!b) return;
	if(!E.op){ b.hidden=true; return; }
	var l=E.alarmasActivas(), sinVer=l.filter(function(a){ return !enterada(a); });
	b.hidden=false;
	var peor=l.length ? l[0].nivel : '';
	b.className='em-alarmas-b'+(peor?' n-'+peor:'')+(sinVer.length?' sin-ver':'');
	b.innerHTML='<span aria-hidden="true">'+(l.length?'⚠':'✓')+'</span> '+(l.length ? l.length+(l.length===1?' alarma':' alarmas') : 'Sin alarmas')+(sonido?'':' <span class="em-mudo" title="Sonido apagado">🔕</span>');
	b.setAttribute('aria-label', l.length ? l.length+' alarmas, '+sinVer.length+' sin atender. Abrir alarmas' : 'Sin alarmas');
	// Sonar una vez por alarma nueva de nivel crítico o alto (y otra vez si sube de nivel)
	var nuevas=sinVer.filter(function(a){ return (a.nivel==='critica'||a.nivel==='alta') && anunciadas[a.id]!==a.nivel; });
	nuevas.forEach(function(a){ anunciadas[a.id]=a.nivel; });
	if(nuevas.length){
		if(sonido) pitar(nuevas[0].nivel);
		if(document.hidden && window.Notification && Notification.permission==='granted') try { new Notification((E.esSim()?'[SIMULACRO] ':'')+'Centro de mando · '+E.op.nombre, {body:nuevas.map(function(a){ return a.texto; }).join('\n'), tag:'rau-em-'+E.op.codigo}); } catch(e){}
	}
}
E.revisarAlarmas = revisarAlarmas;
function filaAlarma(a, botones){
	var ent=enterada(a);
	return '<div class="em-alarma n-'+a.nivel+(ent?' enterada':'')+'"><span class="em-al-n">'+esc(et('nivel',a.nivel))+'</span><span class="em-al-t">'+esc(a.texto)+(a.desde?'<small>desde '+core.horaZ(a.desde)+'</small>':'')+'</span>'
		+(botones?'<span class="em-al-b"><button type="button" class="em-btn" data-al-ir="'+esc(a.ir)+'" data-al-ref="'+esc(a.ref)+'">Ir</button>'+(ent?'':'<button type="button" class="em-btn" data-al-ok="'+esc(a.id)+'">Enterado</button>')+'</span>':'')+'</div>';
}
function irAlarma(ir, ref){
	E.cerrarModal();
	if(ir==='comunicaciones' && ref && E.verMensaje){ E.ir('comunicaciones'); E.verMensaje(ref); return; }
	if(ir==='incidentes' && ref){ E.ir('incidentes', {id:ref}); return; }
	if(ir==='informes'){ E.ir('informes'); return; }
	E.ir(ir||'sala');
}
document.addEventListener('click', function(e){
	var b=e.target.closest('[data-al-ok]'); if(b){ marcarEnterado([b.dataset.alOk]); if(!$('#em-modal').hidden && $('#al-lista')) $('#al-lista').innerHTML=listaAlarmas(); return; }
	b=e.target.closest('[data-al-ir]'); if(b){ irAlarma(b.dataset.alIr, b.dataset.alRef); }
});
function listaAlarmas(){ var l=E.alarmasActivas(); return l.length ? l.map(function(a){ return filaAlarma(a, true); }).join('') : '<p class="em-nota">Sin alarmas. Los límites: estaciones en silencio '+core.ajustes(E.S).silencio_min+' min, SITREP cada '+core.ajustes(E.S).sitrep_h+' h, mensajes E/P/B/R '+[core.LIMITE_MSG.E, core.LIMITE_MSG.P, core.LIMITE_MSG.B, core.LIMITE_MSG.R].join('/')+' min.</p>'; }
function abrirAlarmas(){
	E.modal('Alarmas', '<div class="em-acciones"><button type="button" class="em-btn" id="al-todas">Enterado todas</button><button type="button" class="em-btn" id="al-son" aria-pressed="'+sonido+'">'+(sonido?'🔔 Sonido encendido':'🔕 Sonido apagado')+'</button>'
		+(window.Notification && Notification.permission!=='granted' ? '<button type="button" class="em-btn" id="al-not">Avisos del sistema</button>' : '')
		+(E.puede('coordinar')&&!cerrada()?'<button type="button" class="em-btn" id="al-aj">Ajustar límites</button>':'')+'</div>'
		+'<div id="al-lista" class="em-alarmas">'+listaAlarmas()+'</div><p class="em-nota">«Enterado» quita el aviso en este dispositivo; la alarma sigue hasta que se atiende la causa. Si sube de nivel, vuelve a sonar.</p>', function(c){
		$('#al-todas',c).addEventListener('click', function(){ marcarEnterado(E.alarmasActivas().map(function(a){ return a.id; })); $('#al-lista',c).innerHTML=listaAlarmas(); });
		$('#al-son',c).addEventListener('click', function(){ sonido=!sonido; DB.kvSet('sonido', sonido).catch(function(){}); this.textContent=sonido?'🔔 Sonido encendido':'🔕 Sonido apagado'; this.setAttribute('aria-pressed', sonido); if(sonido) pitar('alta'); revisarAlarmas(); });
		if($('#al-not',c)) $('#al-not',c).addEventListener('click', function(){ var b=this; Notification.requestPermission().then(function(p){ b.textContent=p==='granted'?'Avisos activados':'Avisos bloqueados por el navegador'; b.disabled=true; }); });
		if($('#al-aj',c)) $('#al-aj',c).addEventListener('click', formAjustes);
	}, true);
}
E.abrirAlarmas = abrirAlarmas;
document.addEventListener('click', function(e){ if(e.target.closest('#em-alarmas')) abrirAlarmas(); });
setInterval(function(){ revisarAlarmas(); refrescarSala(); }, 10000);
E.alRefrescar.push(revisarAlarmas);

/* ── Línea de comandos ───────────────────────────────────────────────────── */
var historial=[];
var AYUDA='<table class="em-tabla em-ayuda-cmd"><tbody>'
	+[['HK4ABC','Se reporta en la red'],['HK4ABC sin novedad','Contacto, sin tráfico'],['HK4ABC tráfico 2 P','Tiene 2 mensajes de prioridad (E, P, B, R)'],['HK4ABC urgente vivienda colapsada','URGENCIA: queda primera en la cola'],
	['HK4ABC apoyo','Pide apoyo'],['HK4ABC desde Santa Rosa','Contacto con ubicación'],['HK4ABC no responde','Llamado sin respuesta'],['HK4ABC qrt','Sale de la red'],['HK4ABC fuera de servicio','Estación fuera de servicio'],
	['red dirigida 7.060 LSB','Estado de la red (abierta, dirigida, cerrada) y canal'],['nota …','Nota en la bitácora'],['decisión …','Decisión (coordinación)'],['inc …','Abre el formulario de incidente'],['msg Alcaldía: …','Abre el formulario de mensaje']]
	.map(function(r){ return '<tr><td class="em-mono">'+esc(r[0])+'</td><td>'+esc(r[1])+'</td></tr>'; }).join('')+'</tbody></table>'
	+'<p class="em-nota">Lo que escribes se muestra antes de registrarse. ↑ y ↓ recorren lo último que escribiste. Tecla <b>:</b> lleva aquí desde cualquier parte.</p>';
function formComando(id){
	return '<form class="em-cmd" id="'+id+'" autocomplete="off" novalidate><label class="em-cmd-p" for="'+id+'-i"><span aria-hidden="true">›</span><span class="sr-only">Comando</span></label>'
		+'<input class="em-campo em-mono" id="'+id+'-i" name="c" spellcheck="false" autocapitalize="off" enterkeyhint="send" placeholder="HK4ABC sin novedad · HK4ABC tráfico 2 P · red dirigida 7.060 · nota …" aria-describedby="'+id+'-o">'
		+'<button class="em-btn primario">Registrar</button><button type="button" class="em-btn" data-cmd-ayuda aria-label="Ayuda de la línea de comandos">?</button>'
		+'<output class="em-cmd-o" id="'+id+'-o" aria-live="polite"></output></form>';
}
function montarComando(f){
	if(!f) return;
	var i=f.c, o=$('output', f), pos=historial.length;
	function ver(){ var r=core.comando(i.value, E.S, E.yo.ind); o.className='em-cmd-o'+(r&&r.error?' error':''); o.textContent=!r?'':r.error?r.error:'→ '+r.resumen; return r; }
	i.addEventListener('input', ver);
	i.addEventListener('keydown', function(e){
		if(e.key==='ArrowUp' && pos>0){ e.preventDefault(); i.value=historial[--pos]; ver(); }
		else if(e.key==='ArrowDown'){ e.preventDefault(); pos=Math.min(historial.length, pos+1); i.value=historial[pos]||''; ver(); }
		else if(e.key==='Escape'){ i.value=''; ver(); i.blur(); }
	});
	f.addEventListener('submit', function(e){
		e.preventDefault(); var r=ver(); if(!r) return;
		if(r.error){ i.focus(); return; }
		historial.push(i.value); pos=historial.length;
		if(r.abrir==='incidente'){ i.value=''; ver(); E.formIncidente({titulo:r.pre.titulo, fuente:'Por radio a '+E.yo.ind}); return; }
		if(r.abrir==='mensaje'){ i.value=''; ver(); E.formMensaje(r.pre); return; }
		if(r.t==='decision' && !E.puede('coordinar')){ o.className='em-cmd-o error'; o.textContent='Las decisiones las registra la coordinación.'; return; }
		var ev=E.registrar(r.t, r.d);
		if(ev){ E.aviso('Registrado: '+r.resumen); i.value=''; o.textContent=''; i.focus(); }
	});
	$('[data-cmd-ayuda]', f).addEventListener('click', function(){ E.modal('Línea de comandos', AYUDA); });
}
document.addEventListener('keydown', function(e){
	if(e.key!==':' || !E.op || !$('#em-modal').hidden || /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) return;
	var i=$('.em-cmd input'); if(i){ e.preventDefault(); i.focus(); }
});

/* ── Acciones sobre estaciones de la red ─────────────────────────────────── */
function accionRed(acc, ind){
	var x=E.S.est[ind]; if(!x || !puedeOperar()) return;
	if(acc==='ok') E.registrar('contacto', {ind:ind, estado:'bien'});
	else if(acc==='paso'){ var t=x.trafico||0, prec=x.prec||'R'; E.registrar('contacto', {ind:ind, trafico:Math.max(0, t-1), prec:t>1?prec:undefined, nota:'Pasa tráfico'}); E.formMensaje({origen:ind, prec:prec}); }
	else if(acc==='nr') E.registrar('sinresp', {ind:ind});
	else if(acc==='sale') E.registrar('contacto', {ind:ind, sale:true});
	else if(acc==='entra') E.registrar('contacto', {ind:ind, estado:'bien'});
	else if(acc==='fuera') E.confirmar('Fuera de servicio', ind+' queda fuera de servicio (por ejemplo, sin energía o sin antena).', 'Registrar').then(function(si){ if(si) E.registrar('e_est', {ind:ind, e:'fuera_servicio', fuente:'Control de red'}); });
}
document.addEventListener('click', function(e){ var b=e.target.closest('[data-red]'); if(b){ e.preventDefault(); accionRed(b.dataset.red, b.dataset.ind); } });
function botonesRed(c, completo){
	if(!puedeOperar()) return '';
	var ind=esc(c.ind);
	return '<span class="em-red-b">'
		+(c.trafico||c.urgente?'<button type="button" class="em-btn primario" data-red="paso" data-ind="'+ind+'" title="Dar paso: la estación pasa su tráfico">▶'+(completo?' Paso':'')+'</button>':'')
		+'<button type="button" class="em-btn ok" data-red="ok" data-ind="'+ind+'" title="Contacto: respondió">✓</button>'
		+'<button type="button" class="em-btn" data-red="nr" data-ind="'+ind+'" title="Llamado sin respuesta">✗</button>'
		+(completo?'<button type="button" class="em-btn" data-red="sale" data-ind="'+ind+'" title="Sale de la red">Sale</button><button type="button" class="em-btn" data-red="fuera" data-ind="'+ind+'" title="Fuera de servicio">Fuera</button>':'')
		+'</span>';
}
function traficoTxt(c){ return c.urgente ? E.pill('peligro','URGENCIA') : c.trafico ? E.pill(CLASE_PREC[c.prec], c.trafico+' '+c.prec, c.trafico+' mensajes de '+et('prec',c.prec).toLowerCase()) : ''; }
function filaRed(c, completo){
	var sil=c.silencioMin;
	return '<div class="em-red-f n-'+c.nivel+(c.urgente?' urgente':'')+'"><span class="em-led" aria-hidden="true"></span>'
		+'<span class="em-red-i"><strong class="em-mono">'+esc(c.ind)+'</strong>'+traficoTxt(c)
		+'<small>'+(sil===Infinity?'sin contacto':'último '+mm(sil))+(c.espera&&c.trafico?' · espera '+mm((Date.now()-c.espera)/60000):'')+(completo&&c.x.lugar?' · '+esc(c.x.lugar):'')+(c.x.situacion&&c.x.situacion.nota?' · '+esc(c.x.situacion.nota):'')+'</small></span>'
		+botonesRed(c, completo)+'</div>';
}

/* ── Bloques vivos de la sala ────────────────────────────────────────────── */
E.VIVO.salaEstado = function(){
	var ahora=Date.now(), S=E.S, al=core.alarmas(S, ahora), pa=core.periodoActual(S, ahora), cola=core.colaRed(S, ahora), I=core.indicadores(S, {}, ahora, E.ventana);
	var crit=al.filter(function(a){ return a.nivel==='critica'; }).length, msgs=Object.keys(S.msgs).map(function(k){ return S.msgs[k]; }).filter(function(m){ return !core.MSG_FINAL[m.estado]; });
	function caja(ir, et_, valor, sub, cls){ return '<button type="button" class="em-est-c '+(cls||'')+'" data-ir="'+ir+'"><span class="em-est-e">'+et_+'</span><strong>'+valor+'</strong><small>'+sub+'</small></button>'; }
	return caja('sala', 'GFH', '<span class="em-mono">'+E.gfh(ahora)+'</span>', 'T+'+reloj(ahora-inicioOp())+' desde la activación')
		+caja('sala', 'Período', pa ? 'P'+esc(pa.n)+' · '+reloj(pa.fin-ahora) : 'Sin período', pa ? 'termina '+core.horaZ(pa.fin)+(pa.resp?' · '+esc(pa.resp):'') : (E.puede('coordinar')?'defínelo abajo':'no definido'), pa ? (pa.fin-ahora<=1800000?'aviso':'') : 'apagado')
		+caja('red', 'Red', S.red ? esc(et('red_est',S.red.e)) : 'Sin red', S.red ? 'control '+esc(S.red.ncs)+(S.red.canal?' · '+esc(S.red.canal):'') : 'escribe «red dirigida …»', S.red ? (S.red.e==='cerrada'?'apagado':'ok') : 'apagado')
		+caja('sala', 'Alarmas', al.length, crit ? crit+(crit===1?' crítica':' críticas') : al.length ? 'sin críticas' : 'todo en orden', crit ? 'peligro' : al.length ? 'aviso' : 'ok')
		+caja('incidentes', 'Incidentes', I.incidentes.activos, 'crítica '+I.incidentes.porPrio.critica+' · alta '+I.incidentes.porPrio.alta, I.incidentes.porPrio.critica ? 'peligro' : '')
		+caja('comunicaciones', 'Tráfico', msgs.length, 'E '+msgs.filter(function(m){ return m.d.prec==='E'; }).length+' · P '+msgs.filter(function(m){ return m.d.prec==='P'; }).length+' · en espera '+cola.filter(function(c){ return c.trafico; }).length, msgs.some(function(m){ return m.d.prec==='E'; }) ? 'peligro' : '')
		+caja('red', 'Estaciones', cola.length+' en red', cola.filter(function(c){ return c.nivel!=='ok'; }).length+' en silencio · '+I.estaciones.verificadas+' verificadas', cola.some(function(c){ return c.nivel==='rojo'; }) ? 'aviso' : '');
};
E.VIVO.salaRed = function(){
	var cola=core.colaRed(E.S, Date.now());
	if(!cola.length) return '<p class="em-nota">Ninguna estación en la red. Escribe su indicativo en la línea de comandos para registrar que se reporta.</p>';
	return '<div class="em-red-l">'+cola.slice(0, 14).map(function(c){ return filaRed(c, false); }).join('')+'</div>'+(cola.length>14?'<button type="button" class="em-link" data-ir="red">y '+(cola.length-14)+' más →</button>':'');
};
E.VIVO.salaAlarmas = function(){
	var l=E.alarmasActivas();
	return l.length ? '<div class="em-alarmas">'+l.slice(0, 8).map(function(a){ return filaAlarma(a, true); }).join('')+'</div>'+(l.length>8?'<button type="button" class="em-link" id="sl-al-mas">ver las '+l.length+' →</button>':'') : '<p class="em-ok-txt">Sin alarmas.</p>';
};
E.VIVO.salaTrafico = function(){
	var ahora=Date.now(), S=E.S, msgs=Object.keys(S.msgs).map(function(k){ return S.msgs[k]; }).filter(function(m){ return !core.MSG_FINAL[m.estado]; })
		.sort(function(a,b){ return core.ORDEN_PREC[a.d.prec]-core.ORDEN_PREC[b.d.prec] || a.ts-b.ts; });
	var espera=core.colaRed(S, ahora).filter(function(c){ return c.trafico || c.urgente; });
	if(!msgs.length && !espera.length) return '<p class="em-nota">Sin tráfico pendiente.</p>';
	return (espera.length?'<p class="em-sub">En espera de turno</p><div class="em-lista">'+espera.map(function(c){ return '<div class="em-fila"><span><strong class="em-mono">'+esc(c.ind)+'</strong> '+traficoTxt(c)+'<small>'+(c.espera?'espera '+mm((ahora-c.espera)/60000):'')+'</small></span>'+(puedeOperar()?'<button type="button" class="em-btn primario" data-red="paso" data-ind="'+esc(c.ind)+'">▶ Paso</button>':'')+'</div>'; }).join('')+'</div>':'')
		+(msgs.length?'<p class="em-sub">Mensajes por entregar</p><div class="em-lista">'+msgs.slice(0, 10).map(function(m){
			var lim=core.LIMITE_MSG[m.d.prec]||240, edad=(ahora-m.ts)/60000;
			return '<button type="button" class="em-fila em-fila-btn" data-ver-msg="'+m.id+'"><span>'+E.pill(CLASE_PREC[m.d.prec], m.d.prec)+' <b>n.º '+esc(m.d.num||'s/n')+'</b> → '+esc(m.d.para)+'<small>'+esc(et('msg_est',m.estado))+'</small></span><span class="em-mono '+(edad>=lim?'em-rojo':edad>=lim/2?'em-ambar':'')+'">'+mm(edad)+'</span></button>';
		}).join('')+'</div>':'');
};
document.addEventListener('click', function(e){ var b=e.target.closest('[data-ver-msg]'); if(b && E.verMensaje){ E.verMensaje(b.dataset.verMsg); } if(e.target.closest('#sl-al-mas')) abrirAlarmas(); });
E.VIVO.salaInc = function(){
	var ahora=Date.now(), l=Object.keys(E.S.inc).map(function(k){ return E.S.inc[k]; }).filter(core.incidenteActivo).sort(function(a,b){ return core.ORDEN_PRIO[a.prio]-core.ORDEN_PRIO[b.prio] || a.recibido-b.recibido; });
	if(!l.length) return '<p class="em-nota">Sin incidentes activos.</p>';
	return '<div class="em-lista">'+l.slice(0, 10).map(function(x){
		return '<button type="button" class="em-fila em-fila-btn em-borde-'+CLASE_PRIO[x.prio]+'" data-ir="incidentes" data-id="'+x.id+'"><span>'+E.pill(CLASE_PRIO[x.prio], et('prio',x.prio))+' '+esc(x.titulo)
			+'<small>'+esc(x.lugar||et('precision',x.precision))+' · '+esc(et('verif',x.verif).toLowerCase())+' · '+(x.resp?esc(x.resp):'<b class="em-rojo">sin responsable</b>')+'</small></span><span class="em-mono">'+mm((ahora-x.recibido)/60000)+'</span></button>';
	}).join('')+'</div>'+(l.length>10?'<button type="button" class="em-link" data-ir="incidentes">y '+(l.length-10)+' más →</button>':'');
};
E.VIVO.salaLog = function(){
	var l=E.S.todo.slice(-14).reverse();
	return l.length ? '<ol class="em-bit em-bit-vivo">'+l.map(E.filaBit).join('')+'</ol>' : '<p class="em-nota">Sin registros todavía.</p>';
};
E.VIVO.salaPeriodo = function(){
	var pa=core.periodoActual(E.S, Date.now());
	if(!pa) return '<p class="em-nota">Sin período operacional vigente. El período fija los objetivos, el responsable y la hora del próximo relevo.</p>';
	return '<p><b>Período '+esc(pa.n)+'</b> · '+core.horaZ(pa.inicio)+' → '+core.horaZ(pa.fin)+(pa.resp?' · '+esc(pa.resp):'')+'</p><p class="em-pre">'+esc(pa.objetivos)+'</p>'+(pa.seguridad?'<p><small>Seguridad: '+esc(pa.seguridad)+'</small></p>':'');
};
var VIVOS_RELOJ=['salaEstado','salaRed','salaAlarmas','salaTrafico','salaInc','redTabla','redEstado'];
function refrescarSala(){ if(!E.op) return; $$('[data-vivo]').forEach(function(el){ if(VIVOS_RELOJ.indexOf(el.dataset.vivo)>=0 && !el.contains(document.activeElement)){ var f=E.VIVO[el.dataset.vivo]; if(f) el.innerHTML=f(el); } }); }

/* ── Sala de mando ───────────────────────────────────────────────────────── */
function panel(clase, titulo, vivo, extra){ return '<section class="em-panel '+clase+'" aria-label="'+esc(titulo)+'"><header><h2>'+titulo+'</h2>'+(extra||'')+'</header><div data-vivo="'+vivo+'">'+E.VIVO[vivo]()+'</div></section>'; }
E.registrarVista('sala', {nombre:'Sala de mando', icono:'◎', fn:function(){
	var coord=E.puede('coordinar') && !cerrada();
	var v=E.el('<div class="em-sala">'
		+'<h1 class="sr-only">Sala de mando</h1>'
		+'<section class="em-estado" data-vivo="salaEstado" aria-label="Estado de la operación">'+E.VIVO.salaEstado()+'</section>'
		+(puedeOperar()?formComando('sl-cmd'):'<p class="em-nota">'+(cerrada()?'Operación cerrada: solo consulta.':'Tu rol es de consulta: ves todo, no registras.')+'</p>')
		+'<div class="em-sala-g">'
		+panel('em-p-red', 'Red', 'salaRed', '<button type="button" class="em-link" data-ir="red">consola →</button>')
		+'<section class="em-panel em-p-mapa" aria-label="Situación"><header><h2>Situación</h2><button type="button" class="em-link" data-ir="mapa">mapa completo →</button></header><div id="sl-mapa" class="em-mapa-sala" role="region" aria-label="Mapa de la operación"></div></section>'
		+panel('em-p-alarmas', 'Alarmas', 'salaAlarmas', '<button type="button" class="em-link" id="sl-al">todas →</button>')
		+panel('em-p-trafico', 'Cola de tráfico', 'salaTrafico', '<button type="button" class="em-link" data-ir="comunicaciones">mensajes →</button>')
		+panel('em-p-inc', 'Incidentes activos', 'salaInc', puedeOperar()?'<button type="button" class="em-link" id="sl-inc">+ nuevo</button>':'')
		+panel('em-p-log', 'Registro en vivo', 'salaLog', '<button type="button" class="em-link" data-ir="bitacora">bitácora →</button>')
		+panel('em-p-periodo', 'Período operacional', 'salaPeriodo', coord?'<button type="button" class="em-link" id="sl-per">'+(core.periodoActual(E.S, Date.now())?'nuevo período':'definir')+'</button>':'')
		+'</div>'
		+'<div class="em-acciones em-sala-acc"><button type="button" class="em-btn" id="sl-pant" aria-pressed="false">⛶ Pantalla de sala</button><button type="button" class="em-btn" id="sl-rel">Relevo de turno</button>'
		+'<button type="button" class="em-btn" data-ir="informes">SITREP</button>'+(coord?'<button type="button" class="em-btn" id="sl-aj">Límites de alarmas</button>':'')+'<button type="button" class="em-btn" data-ir="campo">Pasar registros sin internet</button></div>'
		+'</div>');
	montarComando($('#sl-cmd', v));
	$('#sl-al', v).addEventListener('click', abrirAlarmas);
	if($('#sl-inc', v)) $('#sl-inc', v).addEventListener('click', function(){ E.formIncidente(); });
	if($('#sl-per', v)) $('#sl-per', v).addEventListener('click', formPeriodo);
	if($('#sl-aj', v)) $('#sl-aj', v).addEventListener('click', formAjustes);
	$('#sl-rel', v).addEventListener('click', formRelevo);
	$('#sl-pant', v).addEventListener('click', pantallaSala);
	return v;
}, alMontar:function(){ var m=$('#sl-mapa'); if(m && E.mapa && m.offsetParent) E.mapa.montar(m, {mini:true}); }});

/* Pantalla de sala: sin menú, letra más grande, a pantalla completa (para un televisor o un proyector) */
function pantallaSala(){
	var on=!document.body.classList.contains('em-modo-sala');
	document.body.classList.toggle('em-modo-sala', on);
	var b=$('#sl-pant'); if(b){ b.setAttribute('aria-pressed', on); b.textContent=on?'Salir de la pantalla de sala':'⛶ Pantalla de sala'; }
	try { if(on && document.documentElement.requestFullscreen) document.documentElement.requestFullscreen().catch(function(){}); else if(!on && document.fullscreenElement) document.exitFullscreen().catch(function(){}); } catch(e){}
}
document.addEventListener('fullscreenchange', function(){ if(!document.fullscreenElement && document.body.classList.contains('em-modo-sala')) pantallaSala(); });
E.alEntrar.push(function(){ document.body.classList.remove('em-modo-sala'); });

/* ── Consola del control de red ──────────────────────────────────────────── */
E.VIVO.redEstado = function(){
	var r=E.S.red, cola=core.colaRed(E.S, Date.now());
	return '<div class="em-red-estado n-'+(r?r.e:'ninguna')+'"><span class="em-est-e">Estado de la red</span><strong>'+(r?esc(et('red_est',r.e)):'Sin abrir')+'</strong>'
		+'<small>'+(r?'Control: <b class="em-mono">'+esc(r.ncs)+'</b>'+(r.canal?' · '+esc(r.canal):'')+' · desde '+core.horaZ(r.ts):'Nadie ha abierto la red en esta operación.')+'</small>'
		+'<small>'+cola.length+' estaciones en la red · '+cola.filter(function(c){ return c.trafico||c.urgente; }).length+' con tráfico · '+cola.filter(function(c){ return c.nivel!=='ok'; }).length+' en silencio (límite '+core.ajustes(E.S).silencio_min+' min)</small></div>';
};
E.VIVO.redTabla = function(){
	var cola=core.colaRed(E.S, Date.now()), fuera=Object.keys(E.S.est).map(function(k){ return E.S.est[k]; }).filter(function(x){ return !x.enRed; });
	return (cola.length ? '<div class="em-red-l em-red-l-ancha">'+cola.map(function(c, i){ return '<div class="em-red-orden">'+(i+1)+'</div>'+filaRed(c, true); }).join('')+'</div>' : '<p class="em-nota">Ninguna estación en la red todavía.</p>')
		+(fuera.length ? '<h3>Registradas fuera de la red</h3><div class="em-lista">'+fuera.map(function(x){ return '<div class="em-fila"><span><strong class="em-mono">'+esc(x.ind)+'</strong><small>'+(x.salio?'salió '+core.horaZ(x.salio):'nunca se reportó en la red')+(x.lugar?' · '+esc(x.lugar):'')+'</small></span>'+(puedeOperar()?'<button type="button" class="em-btn" data-red="entra" data-ind="'+esc(x.ind)+'">Entra a la red</button>':'')+'</div>'; }).join('')+'</div>' : '');
};
E.registrarVista('red', {nombre:'Control de red', icono:'⌘', fn:function(){
	var op=puedeOperar();
	var v=E.el('<div class="em-red">'
		+'<div class="em-barra"><h1>Control de red</h1>'+(op?'<div class="em-acciones"><button type="button" class="em-btn" id="rd-estado">Abrir, dirigir o cerrar la red</button><button type="button" class="em-btn" id="rd-lista">Pasar lista</button><button type="button" class="em-btn" id="rd-guion">Guiones</button><button type="button" class="em-btn" id="rd-309">Registro ICS-309</button></div>':'<div class="em-acciones"><button type="button" class="em-btn" id="rd-309">Registro ICS-309</button></div>')+'</div>'
		+'<section data-vivo="redEstado">'+E.VIVO.redEstado()+'</section>'
		+(op?formComando('rd-cmd'):'')
		+'<section class="em-tarjeta"><h2>Cola de la red <small>urgencias, luego tráfico por precedencia y tiempo de espera, luego quien lleva más en silencio</small></h2><div data-vivo="redTabla">'+E.VIVO.redTabla()+'</div></section>'
		+'</div>');
	montarComando($('#rd-cmd', v));
	if($('#rd-estado', v)) $('#rd-estado', v).addEventListener('click', formRed);
	if($('#rd-lista', v)) $('#rd-lista', v).addEventListener('click', function(){ E.pasarLista(); });
	if($('#rd-guion', v)) $('#rd-guion', v).addEventListener('click', guiones);
	$('#rd-309', v).addEventListener('click', ver309);
	return v;
}});
function formRed(){
	var r=E.S.red;
	E.modal('Estado de la red', '<form class="em-form" id="f-red"><fieldset><legend>La red queda</legend>'+E.chips('e', core.ET.red_est, r&&r.e!=='cerrada'?(r.e==='abierta'?'dirigida':'cerrada'):'dirigida', {abierta:'ok', dirigida:'aviso', cerrada:'apagado'})+'</fieldset>'
		+'<p class="em-nota"><b>Dirigida</b>: toda estación llama solo al control y espera turno (lo normal en emergencia). <b>Abierta</b>: las estaciones se llaman entre sí; el control vigila.</p>'
		+'<div class="em-dos"><label>Control de red<input class="em-campo em-mono" name="ncs" required value="'+esc(r&&r.e!=='cerrada'?r.ncs:E.yo.ind)+'"></label><label>Canal<input class="em-campo" name="canal" value="'+esc(r?r.canal:'')+'" placeholder="7.060 LSB · 146.520 FM · repetidora"></label></div>'
		+'<label>Nota<input class="em-campo" name="nota" maxlength="300"></label><button class="em-btn primario em-grande">Registrar</button></form>', function(c){
		$('#f-red',c).addEventListener('submit', function(e){ e.preventDefault(); if(E.registrar('red', {e:E.valorDe(this,'e'), ncs:core.indicativo(this.ncs.value), canal:this.canal.value, nota:this.nota.value})) E.cerrarModal(); });
	});
}
/* Guiones del control de red: modelos para leer por radio, con los datos de la operación. No son un procedimiento oficial. */
function guiones(){
	var op=E.op, r=E.S.red, ncs=r&&r.e!=='cerrada'?r.ncs:E.yo.ind, canal=r&&r.canal?r.canal:'[canal]', sim=E.esSim(), fon=core.fonetico(ncs), h=core.horaZ(Date.now());
	var pre=sim?'Esto es un ejercicio, esto es un ejercicio. ':'';
	var G=[
		['Apertura', pre+'Atención todas las estaciones, atención todas las estaciones. Esta es '+ncs+', '+fon+', estación de control de la red de emergencia «'+op.nombre+'» en '+canal+'. Siendo las '+h+', se abre la red en modo DIRIGIDO: toda estación llama únicamente a la estación de control y espera turno. Mantengan el canal libre para tráfico de emergencia. '+pre+'Primero, estaciones con tráfico de EMERGENCIA o PRIORIDAD, llamen ahora. Cambio.'],
		['Incorporación', pre+'Esta es '+ncs+', control de la red. Estaciones que deseen incorporarse, den su indicativo, ubicación y si tienen tráfico, una a la vez. Adelante. Cambio.'],
		['Tráfico de emergencia', 'BREAK, BREAK. Todas las estaciones guarden silencio. Estación con tráfico de emergencia, adelante con su mensaje para '+ncs+'. Cambio.'],
		['Dar paso', '[INDICATIVO], adelante con su tráfico. '+ncs+' listo para copiar. Cambio.'],
		['Pase de lista', pre+'Esta es '+ncs+'. Pase de lista de estaciones de la red. Al escuchar su indicativo respondan con: sin novedad, o el número y la precedencia de sus mensajes. Cambio.'],
		['Cierre', pre+'Atención todas las estaciones. Esta es '+ncs+'. Siendo las [HORA], se cierra la red de emergencia «'+op.nombre+'». Gracias a todas las estaciones. Se mantiene escucha en '+canal+'. '+ncs+' fuera.']];
	E.modal('Guiones del control de red', '<p class="em-nota">Modelos para adaptar y leer por radio. No son un procedimiento oficial: sigue el plan de comunicaciones aprobado y las instrucciones de la autoridad.</p>'
		+G.map(function(g, i){ return '<section class="em-guion"><h3>'+esc(g[0])+'</h3><p class="em-pre em-voz">'+esc(g[1])+'</p><button type="button" class="em-btn" data-guion="'+i+'">Copiar</button></section>'; }).join(''), function(c){
		$$('[data-guion]', c).forEach(function(b){ b.addEventListener('click', function(){ E.copiar(G[+b.dataset.guion][1]); }); });
	}, true);
}
function ver309(){
	var F=core.ics309(E.S), op=E.op;
	var texto='REGISTRO DE COMUNICACIONES (formato basado en ICS-309)\nOperación: '+op.nombre+' · código '+op.codigo+'\nGenerado: '+core.isoZ(Date.now())+' por '+E.yo.ind+'\n\nHORA   DE           A            MENSAJE\n'
		+F.map(function(f){ return core.horaZ(f.ts)+'  '+(f.de+'            ').slice(0,12)+' '+(f.a+'            ').slice(0,12)+' '+f.texto; }).join('\n');
	E.modal('Registro de comunicaciones (ICS-309)', '<div class="em-acciones"><button type="button" class="em-btn" id="r9-imp">Imprimir</button><button type="button" class="em-btn" id="r9-csv">CSV</button></div>'
		+(F.length?'<div class="em-tabla-c"><table class="em-tabla"><thead><tr><th>Hora</th><th>De</th><th>A</th><th>Mensaje</th></tr></thead><tbody>'+F.slice().reverse().map(function(f){ return '<tr><td class="em-mono">'+core.horaZ(f.ts)+'</td><td class="em-mono">'+esc(f.de)+'</td><td class="em-mono">'+esc(f.a)+'</td><td>'+esc(f.texto)+'</td></tr>'; }).join('')+'</tbody></table></div>':'<p class="em-nota">Sin comunicaciones registradas.</p>'), function(c){
		$('#r9-imp',c).addEventListener('click', function(){ E.imprimir('ICS-309 '+op.nombre, texto); });
		$('#r9-csv',c).addEventListener('click', function(){ E.descargar(E.nombreArchivo?E.nombreArchivo('ics309','csv'):'ics309.csv', core.csv([['hora_utc','de','a','mensaje','registro']].concat(F.map(function(f){ return [new Date(f.ts).toISOString(), f.de, f.a, f.texto, f.autor]; }))), 'text/csv'); });
	}, true);
}

/* ── Período operacional (objetivos al estilo SCI-202), relevo y ajustes ─── */
function aLocal(ts){ var d=new Date(ts-new Date(ts).getTimezoneOffset()*60000); return d.toISOString().slice(0,16); }
function formPeriodo(){
	if(!E.puede('coordinar') || cerrada()) return;
	var ps=E.S.periodos, ult=ps[ps.length-1], ahora=Date.now(), ini=ult && ult.fin>ahora ? ult.fin : ahora;
	E.modal('Período operacional', '<form class="em-form" id="f-per" novalidate>'
		+'<div class="em-dos"><label>Número<input class="em-campo" name="n" type="number" min="1" max="999" required value="'+((ult?ult.n:0)+1)+'"></label><label>Responsable<input class="em-campo" name="resp" value="'+esc(E.yo.ind)+'"></label></div>'
		+'<div class="em-dos"><label>Inicia<input class="em-campo" name="inicio" type="datetime-local" required value="'+aLocal(ini)+'"></label><label>Duración<select class="em-campo" name="h">'+E.opciones({4:'4 horas', 6:'6 horas', 8:'8 horas', 12:'12 horas', 24:'24 horas'}, '12')+'</select></label></div>'
		+'<label>Objetivos del período <small>(medibles: qué debe estar logrado al terminar)</small><textarea class="em-campo" name="objetivos" rows="4" required maxlength="2000" placeholder="1. Mantener enlace cada 30 min con las estaciones de Santa Rosa y Dosquebradas.&#10;2. Pasar el tráfico de los albergues al CMGRD.">'+esc(ult?ult.objetivos:'')+'</textarea></label>'
		+'<label>Énfasis del mando<textarea class="em-campo" name="enfasis" rows="2" maxlength="1000"></textarea></label>'
		+'<label>Mensaje de seguridad<textarea class="em-campo" name="seguridad" rows="2" maxlength="1000" placeholder="Ej.: no desplazarse de noche por la vía a Santa Rosa">'+esc(ult?ult.seguridad:'')+'</textarea></label>'
		+'<button class="em-btn primario em-grande">Registrar período</button><p class="em-error" id="f-per-err" role="alert"></p></form>', function(c){
		$('#f-per',c).addEventListener('submit', function(e){
			e.preventDefault(); var f=this, i=new Date(f.inicio.value).getTime();
			if(!f.objetivos.value.trim()){ $('#f-per-err',c).textContent='Escribe al menos un objetivo.'; f.objetivos.focus(); return; }
			if(!i){ $('#f-per-err',c).textContent='Indica cuándo inicia.'; return; }
			if(E.registrar('periodo', {n:+f.n.value, inicio:i, fin:i+(+f.h.value)*3600000, objetivos:f.objetivos.value, enfasis:f.enfasis.value, seguridad:f.seguridad.value, resp:f.resp.value})) E.cerrarModal();
		});
	});
}
function formAjustes(){
	if(!E.puede('coordinar') || cerrada()) return;
	var a=core.ajustes(E.S);
	E.modal('Límites de las alarmas', '<form class="em-form" id="f-aj"><div class="em-dos"><label>Estación en silencio (min)<input class="em-campo" type="number" name="silencio_min" min="5" max="240" value="'+a.silencio_min+'"></label><label>SITREP cada (h)<input class="em-campo" type="number" name="sitrep_h" min="1" max="24" value="'+a.sitrep_h+'"></label></div>'
		+'<p class="em-nota">Rige para todos en la operación. Fijos: mensaje de emergencia sin entregar '+core.LIMITE_MSG.E+' min, prioridad '+core.LIMITE_MSG.P+' min, bienestar '+core.LIMITE_MSG.B+' min, rutina '+core.LIMITE_MSG.R+' min; incidente crítico o alto sin responsable 15 min y sin verificar 30 min; solicitud inmediata sin respuesta 30 min.</p>'
		+'<button class="em-btn primario">Guardar</button></form>', function(c){
		$('#f-aj',c).addEventListener('submit', function(e){ e.preventDefault(); if(E.registrar('ajuste', {silencio_min:+this.silencio_min.value, sitrep_h:+this.sitrep_h.value})) E.cerrarModal(); });
	});
}
function formRelevo(){
	var ahora=Date.now(), pa=core.periodoActual(E.S, ahora), rel=E.S.informes.filter(function(e){ return e.d.tipo==='relevo'; }), ultRel=rel.length?rel[rel.length-1].d.corte:0;
	var opciones={}; if(ultRel) opciones.relevo='Desde el último relevo ('+core.horaZ(ultRel)+')'; if(pa) opciones.periodo='Desde el inicio del período '+pa.n+' ('+core.horaZ(pa.inicio)+')';
	opciones['6']='Últimas 6 horas'; opciones['12']='Últimas 12 horas'; opciones.todo='Desde el inicio de la operación';
	E.modal('Relevo de turno', '<form class="em-form" id="f-rel"><div class="em-dos"><label>Turno<select class="em-campo" name="desde">'+E.opciones(opciones, Object.keys(opciones)[0])+'</select></label><label>Recibe<input class="em-campo em-mono" name="recibe" placeholder="Indicativo de quien recibe"></label></div></form>'
		+'<pre class="em-pre em-informe" id="rel-t"></pre><div class="em-acciones"><button type="button" class="em-btn" id="rel-cop">Copiar</button><button type="button" class="em-btn" id="rel-imp">Imprimir</button>'+(puedeOperar()?'<button type="button" class="em-btn primario" id="rel-reg">Registrar el relevo</button>':'')+'</div>', function(c){
		var f=$('#f-rel',c), texto='';
		function gen(){
			var v=f.desde.value, desde=v==='relevo'?ultRel:v==='periodo'?pa.inicio:v==='todo'?0:ahora-(+v)*3600000;
			texto=core.relevo(E.S, E.op, {desde:desde, corte:ahora, entrega:E.yo.ind, recibe:core.indicativo(f.recibe.value)}); $('#rel-t',c).textContent=texto;
		}
		f.addEventListener('input', gen); gen();
		$('#rel-cop',c).addEventListener('click', function(){ E.copiar(texto); });
		$('#rel-imp',c).addEventListener('click', function(){ E.imprimir('Relevo de turno', texto); });
		if($('#rel-reg',c)) $('#rel-reg',c).addEventListener('click', function(){ if(E.registrar('informe', {tipo:'relevo', corte:ahora})){ E.cerrarModal(); E.aviso('Relevo registrado en la bitácora.'); } });
	}, true);
}
E.formPeriodo=formPeriodo; E.formRelevo=formRelevo; E.formAjustes=formAjustes;
})();
