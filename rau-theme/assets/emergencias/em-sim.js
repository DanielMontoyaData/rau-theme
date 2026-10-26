/* Centro de Mando de Emergencias — L. Simulador y entrenamiento.
 *
 * Solo funciona dentro de una operación de tipo SIMULACRO (el servidor además rechaza registros de
 * simulación en operaciones reales). Cada escenario «inyecta» por tiempos lo que llegaría por radio:
 * el equipo debe registrarlo, verificarlo, asignar recursos e informar. Algunas inyecciones crean
 * estaciones o estados ficticios, siempre con indicativos «SIM…» y marcados como simulación.
 *
 * Mide: tiempo hasta registrar el incidente, confirmar un mensaje, asignar un recurso, generar el
 * informe y resolver duplicados. No envía alertas reales ni toca la bitácora de ninguna operación real.
 */
(function(){
'use strict';
var E=window.RauEm, core=E.core, $=E.$, $$=E.$$, esc=E.esc;
var P={dosquebradas:[4.839,-75.668], pereira:[4.814,-75.694], santarosa:[4.869,-75.621], armenia:[4.534,-75.681], filandia:[4.674,-75.658], manizales:[5.068,-75.517], chinchina:[4.983,-75.603], mistrato:[5.297,-75.882], calarca:[4.520,-75.644]};
var R='[SIMULACIÓN] ';
/* Cada paso: t (segundos desde el inicio a velocidad 1×), texto que «llega por radio», espera (qué se espera
   que haga el equipo) y, opcionalmente, auto (registros ficticios que el simulador crea). */
var ESC = {
	deslizamiento:{n:'Deslizamiento', d:'Una vía principal queda tapada y un vehículo atrapado.', pasos:[
		{t:0, texto:'SIM01 a control: deslizamiento sobre la vía Santa Rosa–Chinchiná, kilómetro 4. Hay un carro atrapado, no sabemos cuántas personas. Cambio.', espera:'incidente', pre:{tipo:'deslizamiento', titulo:'Deslizamiento vía Santa Rosa–Chinchiná km 4', prio:'alta', fuente:'SIM01 por radio', lugar:'Vía Santa Rosa–Chinchiná km 4', lat:4.91, lng:-75.61}},
		{t:120, texto:'SIM02: confirmo el deslizamiento, lo estoy viendo desde la estación de servicio. Bomberos ya van. Cambio.', espera:'verificar'},
		{t:240, texto:'Bomberos piden por intermedio de SIM01 una retroexcavadora y dos radios portátiles para el frente. Cambio.', espera:'recurso'},
		{t:420, texto:'SIM01: mensaje prioritario para el PMU de Santa Rosa: vía cerrada en ambos sentidos, desvío por La Florida. Necesito acuse. Cambio.', espera:'mensaje', pre:{prec:'P', para:'PMU Santa Rosa de Cabal', texto:'VIA CERRADA AMBOS SENTIDOS KM 4 DESVIO POR LA FLORIDA'}},
		{t:600, texto:'Control: prepare el primer informe de situación para la coordinación departamental.', espera:'sitrep'}]},
	incendio_forestal:{n:'Incendio forestal', d:'Fuego en zona rural con viento; riesgo para viviendas.', pasos:[
		{t:0, texto:'SIM03: columna de humo grande en la vereda La Cauchera de Filandia, se ve llama en el filo. Cambio.', espera:'incidente', pre:{tipo:'incendio_forestal', titulo:'Incendio forestal vereda La Cauchera, Filandia', prio:'alta', fuente:'SIM03 por radio', lugar:'Vereda La Cauchera, Filandia', lat:4.69, lng:-75.67}},
		{t:180, texto:'SIM03: el viento lo empuja hacia tres casas. La gente está saliendo. Cambio.', espera:'actualizar'},
		{t:300, texto:'Defensa Civil pide un punto de encuentro para los evacuados. Proponen la escuela de la vereda. Cambio.', espera:'punto'},
		{t:480, texto:'Bomberos de Filandia piden agua y un operador con radio para el puesto de mando. Cambio.', espera:'recurso'},
		{t:660, texto:'Control: genere el informe de situación.', espera:'sitrep'}]},
	inundacion:{n:'Inundación', d:'Creciente de un río urbano con barrios afectados.', pasos:[
		{t:0, texto:'SIM04: el río Consota se está saliendo en el barrio Málaga de Pereira, el agua llega a las rodillas. Cambio.', espera:'incidente', pre:{tipo:'inundacion', titulo:'Creciente del río Consota, barrio Málaga', prio:'alta', fuente:'SIM04 por radio', lugar:'Barrio Málaga, Pereira', lat:4.80, lng:-75.68}},
		{t:150, texto:'SIM05: también hay agua en el barrio Málaga, dicen que hay una señora en un segundo piso que no puede bajar. Cambio.', espera:'duplicado', pre:{tipo:'inundacion', titulo:'Persona atrapada por inundación en Málaga', prio:'critica', fuente:'SIM05 por radio', lugar:'Barrio Málaga, Pereira', lat:4.801, lng:-75.681}},
		{t:300, texto:'Mensaje de bienestar: la familia Ruiz pregunta por Ana Ruiz, vive en Málaga. Cambio.', espera:'bienestar'},
		{t:450, texto:'La Cruz Roja abrió albergue en el coliseo menor. Cambio.', espera:'punto'},
		{t:600, texto:'Control: informe de situación para la alcaldía.', espera:'sitrep'}]},
	sismo:{n:'Terremoto', d:'Sismo fuerte: colapsos, reportes duplicados, red celular caída y una repetidora sin energía.', pasos:[
		{t:0, texto:'Control: sismo fuerte sentido en todo el Eje Cafetero. La red celular no funciona. Abran la red: todas las estaciones repórtense.', espera:'estaciones', auto:'estaciones_sismo'},
		{t:90, texto:'SIM06: en Dosquebradas se cayó una casa de dos pisos en la calle 20 con carrera 8. Hay gente atrapada. Cambio.', espera:'incidente', pre:{tipo:'colapso_estructural', titulo:'Colapso de vivienda calle 20 con carrera 8, Dosquebradas', prio:'critica', fuente:'SIM06 por radio', lugar:'Calle 20 con carrera 8, Dosquebradas', lat:4.839, lng:-75.669}},
		{t:180, texto:'SIM07: reporto un derrumbe de vivienda en Dosquebradas, por la carrera 8, hay personas atrapadas. Cambio.', espera:'duplicado', pre:{tipo:'colapso_estructural', titulo:'Derrumbe de vivienda carrera 8 Dosquebradas', prio:'critica', fuente:'SIM07 por radio', lugar:'Carrera 8, Dosquebradas', lat:4.8395, lng:-75.6685}},
		{t:300, texto:'La repetidora del cerro se quedó sin energía: la transferencia al generador no funcionó. Pasen a símplex.', espera:'nodo', auto:'repetidora_caida'},
		{t:420, texto:'SIM06: mensaje de EMERGENCIA para el puesto de mando de Pereira: se requiere grupo de búsqueda y rescate urbano en la calle 20 con carrera 8. Necesito acuse. Cambio.', espera:'mensaje', pre:{prec:'E', para:'PMU Pereira', texto:'SE REQUIERE GRUPO USAR CALLE 20 CON CARRERA 8 DOSQUEBRADAS PERSONAS ATRAPADAS'}},
		{t:600, texto:'La gobernación pide quince radios portátiles con doble batería. Cambio.', espera:'recurso'},
		{t:780, texto:'Control: primer informe de situación para la coordinación nacional.', espera:'sitrep'}]},
	aislamiento:{n:'Comunidad aislada', d:'Una vereda queda sin vías ni teléfono; solo hay HF.', pasos:[
		{t:0, texto:'SIM08 desde Mistrató por 7.060: la vereda Puerto de Oro quedó incomunicada, se cayó el puente y no hay señal de teléfono. Cambio.', espera:'incidente', pre:{tipo:'aislamiento_comunidad', titulo:'Vereda Puerto de Oro incomunicada, Mistrató', prio:'alta', fuente:'SIM08 por 7.060 LSB', lugar:'Vereda Puerto de Oro, Mistrató', lat:5.33, lng:-75.90}},
		{t:180, texto:'SIM08: hay un niño con fiebre alta y no pueden salir. Piden orientación médica. Cambio.', espera:'mensaje', pre:{prec:'P', para:'Hospital de Mistrató', texto:'NINO CON FIEBRE ALTA EN VEREDA PUERTO DE ORO SIN SALIDA SOLICITAN ORIENTACION MEDICA'}},
		{t:360, texto:'SIM08: nos queda batería para unas seis horas. Cambio.', espera:'estacion'},
		{t:540, texto:'Control: registre en el mapa el punto sin comunicación y genere el informe.', espera:'sitrep'}]},
	perdida_conectividad:{n:'Pérdida de conectividad', d:'Cae la red celular y el internet: todo pasa por radio.', pasos:[
		{t:0, texto:'Control: no hay internet ni celular en Pereira y Dosquebradas. Los mensajes a las instituciones van por radio. Verifiquen estaciones.', espera:'estaciones', auto:'estaciones_basicas'},
		{t:150, texto:'El hospital San Jorge pide pasar un mensaje a la Secretaría de Salud: necesitan dos ambulancias adicionales. Cambio.', espera:'mensaje', pre:{prec:'P', para:'Secretaría de Salud de Risaralda', texto:'HOSPITAL SAN JORGE SOLICITA DOS AMBULANCIAS ADICIONALES'}},
		{t:330, texto:'SIM02 no responde desde hace varios minutos. Cambio.', espera:'estacion'},
		{t:480, texto:'Control: informe de situación con los mensajes pendientes.', espera:'sitrep'}]},
	caida_nodo:{n:'Caída de un nodo', d:'Una repetidora clave se apaga en plena operación.', pasos:[
		{t:0, texto:'Control: inicia la malla de prueba. Todas las estaciones repórtense por la repetidora.', espera:'estaciones', auto:'estaciones_basicas'},
		{t:180, texto:'La repetidora del cerro dejó de transmitir. Se oye solo portadora sin audio. Cambio.', espera:'nodo', auto:'repetidora_caida'},
		{t:330, texto:'SIM01 y SIM02 se oyen en símplex 146.520 con señal débil. Cambio.', espera:'enlace'},
		{t:480, texto:'Control: actualice el plan de comunicaciones con el canal alterno.', espera:'plan'}]},
	duplicados:{n:'Reportes duplicados', d:'Tres estaciones reportan el mismo evento con datos distintos.', pasos:[
		{t:0, texto:'SIM01: incendio en una bodega en el sector de La Badea, Dosquebradas. Cambio.', espera:'incidente', pre:{tipo:'incendio_estructural', titulo:'Incendio en bodega, La Badea', prio:'alta', fuente:'SIM01 por radio', lugar:'La Badea, Dosquebradas', lat:4.835, lng:-75.69}},
		{t:120, texto:'SIM02: reporto fuego en una fábrica cerca a La Badea. Cambio.', espera:'duplicado', pre:{tipo:'incendio_estructural', titulo:'Fuego en fábrica cerca de La Badea', prio:'media', fuente:'SIM02 por radio', lugar:'La Badea, Dosquebradas', lat:4.8355, lng:-75.6895}},
		{t:240, texto:'SIM03: hay humo negro en La Badea, parece una bodega de químicos. Cambio.', espera:'duplicado', pre:{tipo:'incendio_estructural', titulo:'Humo negro, posible bodega de químicos en La Badea', prio:'critica', fuente:'SIM03 por radio', lugar:'La Badea, Dosquebradas', lat:4.8352, lng:-75.6898}},
		{t:420, texto:'Control: revise los duplicados, deje un solo incidente y genere el informe.', espera:'sitrep'}]},
	recuperacion:{n:'Recuperación de comunicaciones', d:'Las redes vuelven: hay que verificar, actualizar y cerrar.', pasos:[
		{t:0, texto:'Control: la repetidora del cerro volvió al aire con generador. Verifiquen las estaciones.', espera:'nodo', auto:'repetidora_vuelve'},
		{t:150, texto:'Regresó el internet en Pereira. Los mensajes pendientes deben confirmarse con su destinatario.', espera:'mensaje'},
		{t:300, texto:'Control: actualice el estado de los incidentes resueltos y registre las lecciones aprendidas.', espera:'cierre'},
		{t:450, texto:'Control: informe de cierre.', espera:'sitrep'}]}
};
var ESPERA = {incidente:'Registrar el incidente', verificar:'Verificar el incidente', actualizar:'Actualizar la situación', recurso:'Gestionar el recurso', mensaje:'Registrar y entregar el mensaje', sitrep:'Generar el informe', punto:'Agregar el punto al mapa', duplicado:'Registrar y resolver el duplicado', bienestar:'Registrar a la persona', estaciones:'Pasar lista o verificar estaciones', estacion:'Actualizar la estación', nodo:'Reportar el estado de la repetidora', enlace:'Registrar la prueba de enlace', plan:'Actualizar el plan', cierre:'Cerrar incidentes y registrar lecciones'};

/* Registros ficticios que crea el simulador (siempre con indicativos SIM…) */
function auto(tipo, iny){
	function est(ind, nombre, lugar, pos){ E.registrar('e_reg', {ind:ind, nombre:'[SIM] '+nombre, lugar:lugar, precision:'aproximada', lat:pos[0], lng:pos[1], medios:['VHF FM'], sim:iny}); }
	if(tipo==='estaciones_sismo'||tipo==='estaciones_basicas'){ est('SIM01','Estación ficticia 1','Santa Rosa de Cabal',P.santarosa); est('SIM02','Estación ficticia 2','Pereira',P.pereira); est('SIM06','Estación ficticia 6','Dosquebradas',P.dosquebradas); est('SIM07','Estación ficticia 7','Dosquebradas',[4.845,-75.66]); }
	if(tipo==='repetidora_caida') E.registrar('repe', {r:'[SIM] Repetidora del cerro', e:'caida', n:'Sin energía: la transferencia al generador no funcionó (simulado)', sim:iny});
	if(tipo==='repetidora_vuelve') E.registrar('repe', {r:'[SIM] Repetidora del cerro', e:'generador', n:'De nuevo en el aire con generador (simulado)', sim:iny});
}

/* ── Motor ───────────────────────────────────────────────────────────────── */
var sim=null, reloj=null;
function clave(){ return 'sim:'+E.op.codigo; }
function guardar(){ if(sim && E.op) E.db.kvSet(clave(), sim); }
function enSimulacro(){ return E.op && E.op.tipo==='simulacro'; }
function transcurrido(){ if(!sim) return 0; return (sim.pausadoEn||Date.now())-sim.inicio-sim.pausaTotal; }
function iniciar(escenario, velocidad){
	if(!enSimulacro()){ E.aviso('El simulador solo corre dentro de un simulacro.', 'peligro'); return; }
	sim={escenario:escenario, velocidad:velocidad||5, inicio:Date.now(), pausaTotal:0, pausadoEn:0, entregados:{}, terminado:false};
	guardar(); E.registrar('nota', {tipo:'Simulación', texto:'Comienza el escenario «'+ESC[escenario].n+'» a '+sim.velocidad+'× ('+ESC[escenario].pasos.length+' inyecciones).'});
	arrancar(); E.ir('simulador');
}
function arrancar(){ clearInterval(reloj); reloj=setInterval(tic, 1000); tic(); }
function tic(){
	if(!sim || sim.terminado || sim.pausadoEn || !enSimulacro()) return;
	var esc_=ESC[sim.escenario], seg=transcurrido()/1000*sim.velocidad;
	esc_.pasos.forEach(function(p, i){
		if(sim.entregados[i] || seg<p.t) return;
		var ev=E.registrar('sim', {escenario:sim.escenario, paso:i, texto:p.texto, espera:p.espera}, {silencioso:true});
		sim.entregados[i]=ev?ev.u:'error'; guardar();
		if(p.auto && ev) auto(p.auto, ev.u);
		if(ev) tarjeta(i, ev.u);
	});
	if(Object.keys(sim.entregados).length===esc_.pasos.length && !sim.ultimo){ sim.ultimo=Date.now(); guardar(); }
	E.refrescar();
}
function tarjeta(i, uIny){
	var p=ESC[sim.escenario].pasos[i];
	E.modal('📻 Llega por radio · SIMULACIÓN', '<p class="em-sim-msg">'+esc(R+p.texto)+'</p><p class="em-nota">Se espera: <b>'+esc(ESPERA[p.espera]||'')+'</b>. El tiempo se mide desde ahora.</p><div class="em-acciones">'+accionesPara(p, uIny)+'<button type="button" class="em-btn" id="sim-luego">Después</button></div>', function(c){
		$('#sim-luego',c).addEventListener('click', E.cerrarModal);
		$$('[data-sa]',c).forEach(function(b){ b.addEventListener('click', function(){ hacer(b.dataset.sa, p, uIny); }); });
	});
}
function accionesPara(p, u){
	var m={incidente:['inc','Registrar incidente'], duplicado:['inc','Registrar el reporte'], mensaje:['msg','Registrar mensaje'], recurso:['sol','Registrar solicitud'], sitrep:['sitrep','Ir a informes'], punto:['mapa','Ir al mapa'], bienestar:['per','Ir a bienestar'],
		estaciones:['lista','Pasar lista'], estacion:['est','Ir a estaciones'], nodo:['est','Ir a estaciones'], enlace:['est','Ir a estaciones'], verificar:['incs','Ir a incidentes'], actualizar:['incs','Ir a incidentes'], plan:['plan','Ir al plan'], cierre:['incs','Ir a incidentes']}[p.espera];
	return m ? '<button type="button" class="em-btn primario" data-sa="'+m[0]+'">'+m[1]+'</button>' : '';
}
function hacer(a, p, u){
	var pre=Object.assign({}, p.pre||{}, {sim:u});
	if(a==='inc') E.formIncidente(Object.assign({fuente_tipo:'operador_radio', precision:'aproximada'}, pre));
	else if(a==='msg') E.formMensaje(pre);
	else if(a==='sol') E.formSolicitud({});
	else if(a==='lista'){ E.cerrarModal(); E.pasarLista(); }
	else { E.cerrarModal(); E.ir({sitrep:'informes', mapa:'mapa', per:'personas', est:'estaciones', incs:'incidentes', plan:'plan'}[a]); }
}

/* Métricas: desde cada inyección hasta la respuesta registrada */
function metricas(){
	if(!sim) return [];
	var S=E.S, todos=S.todo;
	return ESC[sim.escenario].pasos.map(function(p, i){
		var u=sim.entregados[i], iny=u && S.porU[u]; if(!iny) return {i:i, p:p, estado:'pendiente'};
		var desde=iny.ts, resp=null;
		function primero(f){ for(var k=0;k<todos.length;k++){ var e=todos[k]; if(e.ts>=desde && e.t!=='sim' && f(e)) return e; } return null; }
		switch(p.espera){
		case 'incidente': resp=primero(function(e){ return e.t==='i_nuevo' && e.d.sim===u; }) || primero(function(e){ return e.t==='i_nuevo'; }); break;
		case 'duplicado': resp=primero(function(e){ return e.t==='i_fusion' || e.t==='i_nodup'; }); break;
		case 'verificar': resp=primero(function(e){ return e.t==='i_verif'; }); break;
		case 'actualizar': resp=primero(function(e){ return e.t==='i_act'; }); break;
		case 'mensaje': var m=primero(function(e){ return e.t==='msg'; }); resp=m; if(m){ var conf=primero(function(e){ return e.t==='m_est' && e.d.m===m.u && (e.d.e==='confirmado'||e.d.e==='entregado'); }); return {i:i, p:p, estado:'respondido', seg:(m.ts-desde)/1000, extra:conf?'entregado/confirmado a los '+Math.round((conf.ts-desde)/1000)+' s':'sin entrega registrada'}; } break;
		case 'recurso': resp=primero(function(e){ return e.t==='r_sol' || (e.t==='r_est' && e.d.e==='asignado'); }); var asig=primero(function(e){ return e.t==='r_est' && e.d.e==='asignado'; }); if(resp) return {i:i, p:p, estado:'respondido', seg:(resp.ts-desde)/1000, extra:asig?'asignado a los '+Math.round((asig.ts-desde)/1000)+' s':'sin asignación'}; break;
		case 'sitrep': resp=primero(function(e){ return e.t==='informe'; }); break;
		case 'punto': resp=primero(function(e){ return e.t==='pt'; }); break;
		case 'bienestar': resp=primero(function(e){ return e.t==='persona'; }); break;
		case 'estaciones': resp=primero(function(e){ return e.t==='e_verif' || e.t==='contacto' || e.t==='enlace'; }); break;
		case 'estacion': resp=primero(function(e){ return e.t==='e_est' || e.t==='e_verif' || e.t==='e_reg'; }); break;
		case 'nodo': resp=primero(function(e){ return e.t==='repe' && !e.d.sim; }); break;
		case 'enlace': resp=primero(function(e){ return e.t==='enlace'; }); break;
		case 'plan': resp=primero(function(e){ return e.t==='p_ver' || e.t==='plan'; }); break;
		case 'cierre': resp=primero(function(e){ return e.t==='leccion' || (e.t==='i_estado' && (e.d.e==='resuelto'||e.d.e==='cerrado')); }); break;
		}
		return resp ? {i:i, p:p, estado:'respondido', seg:(resp.ts-desde)/1000} : {i:i, p:p, estado:'esperando', seg:(Date.now()-desde)/1000};
	});
}
function seg(s){ s=Math.round(s); return s<60?s+' s':Math.floor(s/60)+' min '+(s%60)+' s'; }
E.VIVO.sim = function(){
	if(!sim) return '';
	var M=metricas(), resp=M.filter(function(x){ return x.estado==='respondido'; }), n=ESC[sim.escenario].pasos.length, ent=Object.keys(sim.entregados).length;
	return '<p>Escenario <b>'+esc(ESC[sim.escenario].n)+'</b> · velocidad '+sim.velocidad+'× · '+ent+' de '+n+' inyecciones entregadas · '+(sim.terminado?'terminado':sim.pausadoEn?'en pausa':'en curso')+'</p>'
		+'<table class="em-tabla"><thead><tr><th>#</th><th>Inyección</th><th>Se espera</th><th>Tiempo de respuesta</th></tr></thead><tbody>'+M.map(function(x){ return '<tr><td>'+(x.i+1)+'</td><td><small>'+esc(x.p.texto)+'</small></td><td>'+esc(ESPERA[x.p.espera]||'')+'</td><td>'+(x.estado==='pendiente'?'<small>Aún no llega</small>':x.estado==='esperando'?'<span class="em-ambar">Esperando · '+seg(x.seg)+'</span>':'<b>'+seg(x.seg)+'</b>'+(x.extra?'<br><small>'+esc(x.extra)+'</small>':''))+'</td></tr>'; }).join('')+'</tbody></table>'
		+(resp.length?'<p>Mediana de respuesta: <b>'+seg(core.mediana(resp.map(function(x){ return x.seg; })))+'</b> ('+resp.length+' respondidas).</p>':'');
};
function vistaSimulador(){
	if(!enSimulacro()) return E.el('<div><h1>Simulador y entrenamiento</h1><p>El simulador corre en operaciones de tipo simulacro, aparte de cualquier operación real: no envía alertas reales ni escribe en la bitácora de una operación real.</p><button type="button" class="em-btn primario em-grande" onclick="RauEm.abrirSimulador()">Crear un simulacro</button></div>');
	var v=E.el('<div><h1>Simulador</h1>'
		+(sim?'<div class="em-acciones">'+(sim.terminado?'':(sim.pausadoEn?'<button type="button" class="em-btn primario" id="sm-seguir">Continuar</button>':'<button type="button" class="em-btn" id="sm-pausa">Pausar</button>')+'<button type="button" class="em-btn" id="sm-fin">Terminar</button>')+'<button type="button" class="em-btn" id="sm-csv">Métricas en CSV</button></div><section class="em-tarjeta"><div data-vivo="sim">'+E.VIVO.sim()+'</div></section>'
			:'<p>Elige un escenario para este simulacro. Las inyecciones llegan por tiempos como mensajes de radio; responde registrando en el centro de mando.</p>'+listaEscenarios())
		+'</div>');
	var b;
	if((b=$('#sm-pausa',v))) b.addEventListener('click', function(){ sim.pausadoEn=Date.now(); guardar(); E.ir('simulador'); });
	if((b=$('#sm-seguir',v))) b.addEventListener('click', function(){ sim.pausaTotal+=Date.now()-sim.pausadoEn; sim.pausadoEn=0; guardar(); arrancar(); E.ir('simulador'); });
	if((b=$('#sm-fin',v))) b.addEventListener('click', function(){ E.confirmar('Terminar el escenario', 'Se detienen las inyecciones. Las métricas quedan.', 'Terminar').then(function(si){ if(!si) return; sim.terminado=true; guardar(); clearInterval(reloj); E.registrar('nota', {tipo:'Simulación', texto:'Fin del escenario «'+ESC[sim.escenario].n+'».'}); E.ir('simulador'); }); });
	if((b=$('#sm-csv',v))) b.addEventListener('click', function(){ E.descargar(E.nombreArchivo('metricas-simulacro','csv'), core.csv([['paso','inyeccion','se_espera','estado','segundos','detalle']].concat(metricas().map(function(x){ return [x.i+1, x.p.texto, ESPERA[x.p.espera], x.estado, x.seg!=null?Math.round(x.seg):'', x.extra||'']; }))), 'text/csv;charset=utf-8'); });
	v.addEventListener('click', function(e){ var x=e.target.closest('[data-esc]'); if(x) iniciar(x.dataset.esc, +($('#sm-vel',v)||{value:5}).value); });
	return v;
}
function listaEscenarios(){
	return '<label class="em-inline">Velocidad <select class="em-campo" id="sm-vel"><option value="1">1× (tiempo real)</option><option value="5" selected>5×</option><option value="10">10×</option></select></label><div class="em-lista">'+Object.keys(ESC).map(function(k){ var x=ESC[k]; return '<button type="button" class="em-fila em-fila-btn" data-esc="'+k+'"><span><strong>'+esc(x.n)+'</strong><small>'+esc(x.d)+' · '+x.pasos.length+' inyecciones · unos '+Math.round(x.pasos[x.pasos.length-1].t/60)+' min a 1×</small></span><span aria-hidden="true">▶</span></button>'; }).join('')+'</div>';
}
E.registrarVista('simulador', {nombre:'Simulador', icono:'🎯', fn:vistaSimulador});

/* Desde el inicio: crear el simulacro (en el servidor para practicar en grupo, o solo en este dispositivo) */
E.abrirSimulador = function(){
	E.modal('Simulacro y entrenamiento', '<p>Se crea una operación de SIMULACRO, separada de cualquier operación real y marcada en todas sus pantallas e informes.</p><div class="em-lista">'+Object.keys(ESC).map(function(k){ return '<label class="em-fila"><span><strong>'+esc(ESC[k].n)+'</strong><small>'+esc(ESC[k].d)+'</small></span><input type="radio" name="esc" value="'+k+'"'+(k==='sismo'?' checked':'')+'></label>'; }).join('')+'</div>'
		+'<label class="em-inline">Velocidad <select class="em-campo" id="ab-vel"><option value="1">1× (tiempo real)</option><option value="5" selected>5×</option><option value="10">10×</option></select></label>'
		+'<div class="em-acciones"><button type="button" class="em-btn primario" id="ab-srv">En el servidor (para practicar en grupo)</button><button type="button" class="em-btn" id="ab-loc">Solo en este dispositivo</button><button type="button" class="em-btn" id="ab-vacio">Simulacro sin escenario</button></div>', function(c){
		function elegido(){ var r=$('input[name=esc]:checked',c); return r?r.value:'sismo'; }
		$('#ab-srv',c).addEventListener('click', function(){ var k=elegido(), vel=+$('#ab-vel',c).value; E.formCrear('simulacro', 'Simulacro: '+ESC[k].n, function(){ iniciar(k, vel); }); });
		$('#ab-loc',c).addEventListener('click', function(){ var k=elegido(), vel=+$('#ab-vel',c).value; E.formLocal(true, 'Simulacro: '+ESC[k].n, function(){ iniciar(k, vel); }); });
		$('#ab-vacio',c).addEventListener('click', function(){ E.formCrear('simulacro'); });
	}, true);
};
// Al volver a entrar a un simulacro con un escenario en curso, se reanuda
E.alEntrar.push(function(){
	clearInterval(reloj); sim=null;
	if(!enSimulacro()) return;
	E.db.kvGet(clave()).then(function(s){ if(s && E.op && E.op.tipo==='simulacro'){ sim=s; if(!s.terminado && !s.pausadoEn) arrancar(); E.refrescar(); } });
});
E.simulador={escenarios:ESC, metricas:metricas};
})();
