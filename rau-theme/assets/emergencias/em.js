/* Centro de Mando de Emergencias — RAU
 *
 * Todo vive en el teléfono (localStorage) y funciona sin internet. Cada cosa que se registra es un
 * evento con un identificador único que nunca se modifica: el estado (estaciones, mensajes, personas…)
 * se calcula recorriendo los eventos en orden. Así los registros de varios operadores se combinan sin
 * conflictos, y un teléfono que estuvo horas sin señal entrega lo suyo al volver.
 */
(function(){
'use strict';
var C = window.RAU_EM || {};
var $ = function(s, el){ return (el||document).querySelector(s); };
var $$ = function(s, el){ return Array.prototype.slice.call((el||document).querySelectorAll(s)); };
function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function ind(s){ return String(s||'').toUpperCase().replace(/[^A-Z0-9\/]/g,'').slice(0,20); }

/* ── Datos fijos ─────────────────────────────────────────────────────────── */
var PRECEDENCIAS = {
	E:{n:'Emergencia', d:'Peligro para la vida. Va antes que todo.', o:0},
	P:{n:'Prioridad',  d:'Importante y con plazo: oficial, heridos, muertes.', o:1},
	B:{n:'Bienestar',  d:'Personas en la zona: «estoy bien», búsquedas.', o:2},
	R:{n:'Rutina',     d:'Lo demás. En emergencia, al final o no se pasa.', o:3}
};
var HX = {'':'Ninguna', HXA:'HXA · llamar por cobrar hasta… km', HXB:'HXB · anular si no se entrega en… h', HXC:'HXC · informar fecha y hora de entrega', HXD:'HXD · informar quién lo recibió y cuándo', HXE:'HXE · pedir respuesta al destinatario', HXF:'HXF · entregar en la fecha…', HXG:'HXG · entregar sin llamada de larga distancia'};
var ESTADOS_EST = { bien:{n:'Bien', c:'ok'}, apoyo:{n:'Necesita apoyo', c:'aviso'}, urgente:{n:'Urgente', c:'peligro'}, fuera:{n:'Fuera de la red', c:'apagado'} };
var ENERGIA = { red:'Red eléctrica', bateria:'Batería', generador:'Generador', solar:'Solar', vehiculo:'Vehículo' };
var ESTADOS_MSG = { pendiente:'Pendiente', recibido:'Recibido', retransmitido:'Retransmitido', entregado:'Entregado', anulado:'Anulado' };
var ESTADOS_PER = { bien:{n:'Está bien', c:'ok'}, herido:{n:'Herido', c:'aviso'}, albergue:{n:'En albergue', c:'ok'}, sinnoticias:{n:'Sin noticias', c:'peligro'}, buscado:{n:'Lo buscan', c:'aviso'}, encontrado:{n:'Encontrado', c:'ok'} };
var ESTADOS_REPE = { aire:{n:'En el aire', c:'ok'}, bateria:{n:'Con batería', c:'aviso'}, generador:{n:'Con generador', c:'aviso'}, caida:{n:'Caída', c:'peligro'} };
var FUNCIONES = ['Mando (estación de control)','Táctico local','Logística','Salud','Bienestar','Enlace nacional HF','Enlace con autoridades','Otro'];
var RECURSOS = ['Radio VHF/UHF','Radio HF','Batería','Generador','Panel solar','Antena','Vehículo','Persona/operador','Otro'];
var FON = {A:'Alfa',B:'Bravo',C:'Charlie',D:'Delta',E:'Echo',F:'Foxtrot',G:'Golf',H:'Hotel',I:'India',J:'Juliett',K:'Kilo',L:'Lima',M:'Mike',N:'November',O:'Oscar',P:'Papa',Q:'Quebec',R:'Romeo',S:'Sierra',T:'Tango',U:'Uniform',V:'Victor',W:'Whiskey',X:'X-ray',Y:'Yankee',Z:'Zulu',
	'0':'Cero','1':'Uno','2':'Dos','3':'Tres','4':'Cuatro','5':'Cinco','6':'Seis','7':'Siete','8':'Ocho','9':'Nueve','/':'barra'};
var MESES = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
var MESES_LARGOS = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
var PREPARACION = [
	{g:'Mi maletín (72 horas)', dias:90, items:['Radio portátil con batería cargada','Batería de repuesto y cargador de carro','Antena de repuesto o de mayor ganancia','Lista de frecuencias impresa (esta página, «Imprimir»)','Libreta, lápiz y formularios de mensaje','Linterna o frontal con pilas','Agua y comida para 3 días','Botiquín, silbato y medicamentos propios','Documentos en bolsa plástica','Batería externa para el teléfono']},
	{g:'Mi estación', dias:30, items:['Batería probada con carga real (no solo el voltímetro)','Generador encendido y probado con carga','Transferencia al generador probada (falló en las repetidoras en el sismo de 2026)','Antena de HF para cubrir Colombia (dipolo bajo, NVIS) lista para armar','Memorias del radio: 146.520 y las repetidoras de la zona','Este centro de mando abierto una vez con internet (para que funcione sin él)']},
	{g:'Coordinación', dias:180, items:['Sé quién es la estación de control de mi zona','Conozco al consejo municipal o departamental de gestión del riesgo','Participé en la última malla o simulacro','Mi familia sabe a dónde ir y cómo reportarse']}
];

/* ── Estado local ────────────────────────────────────────────────────────── */
var LS = 'rau_em_v1';
var db = (function(){ try { return JSON.parse(localStorage.getItem(LS)) || null; } catch(e){ return null; } })() || {};
db.yo = db.yo || {ind:'', nombre:''};
db.incs = db.incs || {};       // código → {codigo, nombre, tipo, zona, estado, token, local}
db.ev = db.ev || {};           // código → [eventos]
db.cursor = db.cursor || {};   // código → último id del servidor ya recibido
db.prep = db.prep || {};       // casilla de preparación → fecha de revisión
db.intervalo = db.intervalo || 30;
var errorGuardar = false;
function guardar(){
	try { localStorage.setItem(LS, JSON.stringify(db)); errorGuardar=false; }
	catch(e){ if(!errorGuardar){ errorGuardar=true; aviso('El teléfono no tiene espacio para guardar. Exporta un respaldo (Más → Exportar) cuanto antes.', 'peligro'); } }
}
function uuid(){
	if(window.crypto && crypto.randomUUID) return crypto.randomUUID();
	var s=''; for(var i=0;i<32;i++){ s+=Math.floor(Math.random()*16).toString(16); if(i===7||i===11||i===15||i===19) s+='-'; } return s;
}
function inc(){ return db.actual && db.incs[db.actual] ? db.incs[db.actual] : null; }
function eventos(){ return inc() ? (db.ev[db.actual] = db.ev[db.actual] || []) : []; }
function registrar(tipo, d){
	if(!inc()) return null;
	var e = {u:uuid(), t:tipo, a:db.yo.ind, ts:Date.now(), d:d, s:0};
	eventos().push(e); guardar(); recalcular(); refrescar(); sincronizarPronto();
	return e;
}

/* ── Estado calculado a partir de los eventos ───────────────────────────── */
var S = {};
function recalcular(){
	var lista = eventos().slice().sort(function(a,b){ return a.ts-b.ts || (a.u<b.u?-1:1); });
	S = {est:{}, msgs:{}, plan:{}, per:{}, rec:{}, repe:{}, dir:{}, notas:[], todo:lista, cerrado:false};
	lista.forEach(function(e){
		var d=e.d||{}, x;
		switch(e.t){
		case 'checkin':
			x = S.est[d.ind] = S.est[d.ind] || {ind:d.ind, primero:e.ts, sinresp:0, hist:[]};
			['nombre','ubic','lat','lng','grid','energia','estado','trafico','nota'].forEach(function(k){ if(d[k]!==undefined && d[k]!=='') x[k]=d[k]; });
			x.ultimo=e.ts; x.sinresp=0; x.por=e.a; x.hist.push(e); break;
		case 'contacto':
			x = S.est[d.ind]; if(!x) break;
			if(d.estado) x.estado=d.estado; if(d.trafico!==undefined) x.trafico=d.trafico; if(d.nota) x.nota=d.nota;
			x.ultimo=e.ts; x.sinresp=0; x.hist.push(e); break;
		case 'sinresp':
			x = S.est[d.ind]; if(!x) break; x.sinresp++; x.intento=e.ts; x.hist.push(e); break;
		case 'msg':
			S.msgs[e.u] = {u:e.u, d:d, a:e.a, ts:e.ts, est:d.estadoInicial||'pendiente', hist:[]}; break;
		case 'msgest':
			x = S.msgs[d.m]; if(!x) break; x.est=d.e; x.hist.push({e:d.e, a:e.a, ts:e.ts, a_quien:d.aq, n:d.n}); break;
		case 'nota': S.notas.push(e); break;
		case 'plan': S.plan[e.u]={u:e.u, d:d, a:e.a, ts:e.ts}; break;
		case 'planx': delete S.plan[d.p]; break;
		case 'persona': S.per[e.u]={u:e.u, d:d, a:e.a, ts:e.ts, est:d.estado, hist:[]}; break;
		case 'personaact':
			x = S.per[d.p]; if(!x) break; if(d.estado) x.est=d.estado; if(d.ubic) x.d.ubic=d.ubic; x.hist.push({ts:e.ts, a:e.a, estado:d.estado, n:d.n}); x.act=e.ts; break;
		case 'recurso': S.rec[e.u]={u:e.u, d:d, a:e.a, ts:e.ts}; break;
		case 'recursox': delete S.rec[d.p]; break;
		case 'repe': S.repe[d.r]={r:d.r, e:d.e, n:d.n, ts:e.ts, a:e.a}; break;
		case 'dir': S.dir[e.u]={u:e.u, d:d, a:e.a, ts:e.ts}; break;
		case 'dirx': delete S.dir[d.p]; break;
		case 'inc': if(d.estado==='cerrado') S.cerrado=true; if(d.estado==='abierto') S.cerrado=false; break;
		}
	});
}
// Minutos desde el último contacto, y si ya se pasó del intervalo de reporte
function edad(ts){ return Math.max(0, Math.floor((Date.now()-ts)/60000)); }
function haceTxt(ts){ var m=edad(ts); return m<1?'ahora':m<60?'hace '+m+' min':m<1440?'hace '+Math.floor(m/60)+' h '+(m%60)+' min':'hace '+Math.floor(m/1440)+' d'; }
function silenciosa(x){ return x.estado!=='fuera' && edad(x.ultimo) >= db.intervalo; }

/* ── Hora y formatos ─────────────────────────────────────────────────────── */
function dos(n){ return (n<10?'0':'')+n; }
function horaZ(ts){ var d=new Date(ts); return dos(d.getUTCHours())+dos(d.getUTCMinutes())+'Z'; }
function fechaZ(ts){ var d=new Date(ts); return d.getUTCDate()+' '+MESES[d.getUTCMonth()]; }
function horaLocal(ts){ var d=new Date(ts); return dos(d.getHours())+':'+dos(d.getMinutes()); }
function fonetico(s){ return String(s||'').toUpperCase().split('').map(function(c){ return FON[c]||c; }).join(' '); }
function palabras(t){ return String(t||'').trim().split(/\s+/).filter(Boolean).length; }
// Maidenhead de 6 caracteres a partir de latitud y longitud
function locator(lat, lng){
	var x=lng+180, y=lat+90, A='ABCDEFGHIJKLMNOPQRSTUVWX';
	var a=Math.floor(x/20), b=Math.floor(y/10), c=Math.floor((x%20)/2), d=Math.floor(y%10), e=Math.floor((x%2)*12), f=Math.floor((y%1)*24);
	return A[a]+A[b]+c+d+A[e].toLowerCase()+A[f].toLowerCase();
}
function distancia(a1,o1,a2,o2){ var R=6371, r=Math.PI/180, da=(a2-a1)*r, dO=(o2-o1)*r, x=Math.sin(da/2)*Math.sin(da/2)+Math.cos(a1*r)*Math.cos(a2*r)*Math.sin(dO/2)*Math.sin(dO/2); return 2*R*Math.asin(Math.sqrt(x)); }

/* ── Avisos y modales ────────────────────────────────────────────────────── */
function aviso(t, tipo){
	var el=$('#em-toast'); if(!el) return;
	el.textContent=t; el.className='em-toast is-on '+(tipo||''); clearTimeout(aviso.t);
	aviso.t=setTimeout(function(){ el.className='em-toast'; }, tipo==='peligro'?9000:3500);
}
function modal(titulo, html, alAbrir){
	var m=$('#em-modal'); $('#em-modal-t').textContent=titulo; $('#em-modal-c').innerHTML=html; m.hidden=false;
	document.body.classList.add('em-con-modal'); if(alAbrir) alAbrir($('#em-modal-c'));
	var f=$('input,textarea,select,button', $('#em-modal-c')); if(f && f.tagName!=='BUTTON') f.focus();
}
function cerrarModal(){ $('#em-modal').hidden=true; document.body.classList.remove('em-con-modal'); }
function copiar(t){
	(navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function(){ aviso('Copiado.'); }, function(){
		modal('Copia este texto', '<textarea class="em-campo em-copia" rows="12" readonly>'+esc(t)+'</textarea>', function(c){ var x=$('textarea',c); x.select(); });
	});
}
function descargar(nombre, texto, tipo){
	var a=document.createElement('a'); a.href=URL.createObjectURL(new Blob([texto], {type:tipo||'text/plain'})); a.download=nombre;
	document.body.appendChild(a); a.click(); a.remove(); setTimeout(function(){ URL.revokeObjectURL(a.href); }, 4000);
}

/* ── Sincronización ──────────────────────────────────────────────────────── */
var sync = {enCurso:false, ultimo:0, error:'', t:null};
function post(datos){
	var b=new URLSearchParams(); Object.keys(datos).forEach(function(k){ b.append(k, datos[k]); });
	return fetch(C.ajax, {method:'POST', body:b, credentials:'same-origin'}).then(function(r){ return r.json(); });
}
function pendientes(){ return eventos().filter(function(e){ return !e.s; }); }
function sincronizarPronto(){ clearTimeout(sync.t); sync.t=setTimeout(sincronizar, 800); }
function sincronizar(){
	var i=inc(); if(!i || i.local || !i.token || sync.enCurso) return;
	if(navigator.onLine===false){ pintarSync(); return; }
	sync.enCurso=true; pintarSync();
	var cod=i.codigo, pend=pendientes().slice(0,200);
	post({action:'rau_em_sync', codigo:cod, token:i.token, desde:db.cursor[cod]||0, eventos:JSON.stringify(pend.map(function(e){ return {u:e.u,t:e.t,a:e.a,ts:e.ts,d:e.d}; }))})
	.then(function(j){
		sync.enCurso=false;
		if(!j.success){
			sync.error=(j.data && j.data.msg)||'El servidor no aceptó la sincronización.';
			if(j.data && j.data.auth){ i.token=''; guardar(); aviso(sync.error, 'peligro'); }
			pintarSync(); return;
		}
		var lista=db.ev[cod]=db.ev[cod]||[], porU={};
		lista.forEach(function(e){ porU[e.u]=e; });
		j.data.guardados.forEach(function(u){ if(porU[u] && !porU[u].s) porU[u].s=-1; });
		var hubo=false;
		j.data.eventos.forEach(function(e){
			if(porU[e.u]) porU[e.u].s=e.s; else { lista.push(e); porU[e.u]=e; hubo=true; }
			db.cursor[cod]=Math.max(db.cursor[cod]||0, e.s);
		});
		var inf=j.data.incidente; ['nombre','tipo','zona','estado','creador'].forEach(function(k){ i[k]=inf[k]; });
		sync.ultimo=Date.now(); sync.error=''; guardar();
		if(hubo){ recalcular(); refrescar(); }
		pintarSync();
		if(j.data.mas || pendientes().length) setTimeout(sincronizar, 300);
	}).catch(function(){ sync.enCurso=false; sync.error='Sin conexión con el servidor'; pintarSync(); });
}
setInterval(function(){ if(document.visibilityState==='visible') sincronizar(); }, 15000);
window.addEventListener('online', function(){ pintarSync(); sincronizar(); });
window.addEventListener('offline', pintarSync);

/* ── Alertas (sismos, GDACS) y bandas que oye el receptor HK4RAU ────────── */
function cargarAlertas(){
	fetch(C.ajax+'?action=rau_em_alertas', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
		if(!j.success) return; db.alertas=j.data; db.alertas.recibido=Date.now(); guardar(); refrescar();
	}).catch(function(){});
}

/* ── Interfaz: estructura ────────────────────────────────────────────────── */
var VISTAS = [
	['tablero','Tablero','◉'], ['red','Red','📡'], ['mensajes','Mensajes','✉'], ['bitacora','Bitácora','☰'],
	['plan','Plan de frecuencias','⌁'], ['personas','Bienestar','♥'], ['recursos','Recursos','⚙'], ['directorio','Directorio','☎'], ['herramientas','Herramientas','✎'], ['mas','Más','…']
];
var vista = 'tablero';
function armar(){
	var app=$('#em-app');
	app.innerHTML =
		'<header class="em-cab">'
		+'<a class="em-marca" href="'+esc(C.sitio)+'" title="Volver a hk4rau.com">RAU</a>'
		+'<div class="em-titulo" id="em-titulo"></div>'
		+'<div class="em-reloj"><strong id="em-utc">--:--Z</strong><span id="em-hora-local">--:-- local</span></div>'
		+'<button type="button" class="em-sync" id="em-sync" aria-live="polite"></button>'
		+'</header>'
		+'<div class="em-franja" id="em-franja" hidden></div>'
		+'<div class="em-cuerpo"><nav class="em-nav" id="em-nav" aria-label="Secciones"></nav><main class="em-main" id="em-main"></main></div>'
		+'<div class="em-toast" id="em-toast" role="status" aria-live="polite"></div>'
		+'<div class="em-modal" id="em-modal" hidden><div class="em-modal-caja" role="dialog" aria-modal="true" aria-labelledby="em-modal-t"><div class="em-modal-cab"><h2 id="em-modal-t"></h2><button type="button" class="em-x" id="em-modal-x" aria-label="Cerrar">×</button></div><div id="em-modal-c"></div></div></div>';
	$('#em-modal-x').addEventListener('click', cerrarModal);
	$('#em-modal').addEventListener('click', function(e){ if(e.target.id==='em-modal') cerrarModal(); });
	document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !$('#em-modal').hidden) cerrarModal(); });
	$('#em-sync').addEventListener('click', function(){ if(inc() && !inc().local){ if(!inc().token) pedirClave(inc().codigo); else sincronizar(); } });
	$('#em-nav').addEventListener('click', function(e){ var b=e.target.closest('[data-v]'); if(b) ir(b.dataset.v); });
	$('#em-main').addEventListener('click', function(e){ var b=e.target.closest('[data-ir]'); if(b){ e.preventDefault(); ir(b.dataset.ir); } });
	setInterval(reloj, 1000); reloj();
}
function reloj(){ var n=Date.now(); $('#em-utc').textContent=horaZ(n).replace(/(\d\d)(\d\d)Z/,'$1:$2Z'); $('#em-hora-local').textContent=horaLocal(n)+' local'; }
function pintarSync(){
	var b=$('#em-sync'); if(!b) return; var i=inc(), p=i?pendientes().length:0;
	if(!i){ b.hidden=true; return; } b.hidden=false;
	var cls, t;
	if(i.local){ cls='apagado'; t='Solo en este teléfono'; }
	else if(!i.token){ cls='peligro'; t='Sin acceso: toca para entrar'; }
	else if(navigator.onLine===false){ cls='aviso'; t='Sin internet'+(p?' · '+p+' por enviar':''); }
	else if(sync.enCurso){ cls='ok'; t='Sincronizando…'; }
	else if(sync.error){ cls='aviso'; t=sync.error+(p?' · '+p+' por enviar':''); }
	else { cls='ok'; t=p ? p+' por enviar' : (sync.ultimo ? 'Al día · '+horaLocal(sync.ultimo) : 'En línea'); }
	b.className='em-sync '+cls; b.textContent=t;
}
function pintarCabecera(){
	var i=inc(), t=$('#em-titulo'), fr=$('#em-franja');
	if(!i){ t.innerHTML='<strong>Centro de Mando de Emergencias</strong><span>Radioaficionados de Colombia</span>'; fr.hidden=true; }
	else {
		t.innerHTML='<strong>'+esc(i.nombre)+'</strong><span>'+(i.local?'Sin conexión':'Código <b class="em-mono">'+esc(i.codigo)+'</b>')+(i.zona?' · '+esc(i.zona):'')+'</span>';
		var sim=i.tipo==='simulacro', cer=S.cerrado||i.estado==='cerrado';
		fr.hidden=!sim && !cer; fr.className='em-franja'+(sim?' simulacro':'')+(cer?' cerrado':'');
		fr.innerHTML='<span>'+(sim?'SIMULACRO · EJERCICIO · NO ES UNA EMERGENCIA REAL':'')+(sim&&cer?' · ':'')+(cer?'INCIDENTE CERRADO':'')+'</span>';
	}
	var nav=$('#em-nav');
	nav.innerHTML = i ? VISTAS.map(function(v){ return '<button type="button" data-v="'+v[0]+'" class="em-nav-b'+(vista===v[0]?' is-on':'')+(['tablero','red','mensajes','bitacora','mas'].indexOf(v[0])<0?' em-solo-ancho':'')+'"'+(vista===v[0]?' aria-current="page"':'')+'><span aria-hidden="true">'+v[2]+'</span>'+v[1]+'<i class="em-cuenta" data-cuenta="'+v[0]+'"></i></button>'; }).join('') : '';
	nav.hidden=!i;
	pintarCuentas();
}
function pintarCuentas(){
	if(!inc()) return;
	var at=atencion().length, msg=Object.keys(S.msgs).filter(function(k){ return S.msgs[k].est==='pendiente'||S.msgs[k].est==='recibido'; }).length;
	$$('[data-cuenta]').forEach(function(el){ var n=el.dataset.cuenta==='tablero'?at:el.dataset.cuenta==='mensajes'?msg:0; el.textContent=n||''; el.hidden=!n; });
}
function ir(v){
	vista=v; if(inc()) try { sessionStorage.setItem('rau_em_vista', v); } catch(e){}
	pintarCabecera(); pintarVista(); window.scrollTo(0,0);
}
var VISTA_FN = {};
function pintarVista(){
	var m=$('#em-main');
	if(!db.yo.ind){ m.innerHTML=''; m.appendChild(vistaIdentidad()); return; }
	if(!inc()){ m.innerHTML=''; m.appendChild(vistaInicio()); return; }
	m.innerHTML=''; m.appendChild((VISTA_FN[vista]||VISTA_FN.tablero)());
}
// Solo las partes que cambian con los datos; los formularios abiertos no se tocan
function refrescar(){
	pintarCabecera(); pintarSync();
	$$('[data-vivo]').forEach(function(el){ var f=VIVO[el.dataset.vivo]; if(f) el.innerHTML=f(el); });
}
var VIVO = {};
function el(html){ var d=document.createElement('div'); d.innerHTML=html; return d.firstElementChild; }
function chips(nombre, opciones, valor){
	return '<div class="em-chips" role="radiogroup">'+Object.keys(opciones).map(function(k){
		var o=opciones[k], n=typeof o==='string'?o:o.n, c=typeof o==='string'?'':o.c||'';
		return '<label class="em-chip '+c+'"><input type="radio" name="'+nombre+'" value="'+esc(k)+'"'+(k===valor?' checked':'')+'><span>'+esc(n)+'</span></label>';
	}).join('')+'</div>';
}
function valorDe(form, nombre){ var x=form.querySelector('[name="'+nombre+'"]:checked'); return x?x.value:''; }
function pill(c, t){ return '<span class="em-pill '+c+'">'+esc(t)+'</span>'; }

/* ── Primera vez: mi indicativo ──────────────────────────────────────────── */
function vistaIdentidad(){
	var v=el('<section class="em-bienvenida"><h1>Centro de Mando de Emergencias</h1>'
		+'<p>Para radioaficionados de toda Colombia: lleva la red de estaciones, los mensajes formales, el plan de frecuencias y el bienestar de las personas en un incidente. <strong>Funciona sin internet</strong>: lo que registras queda en este teléfono y se comparte con los demás operadores cuando hay señal.</p>'
		+'<form class="em-tarjeta em-form" id="em-yo"><h2>¿Quién opera este teléfono?</h2>'
		+'<label>Tu indicativo<input class="em-campo em-mono em-grande" name="ind" required autocomplete="off" autocapitalize="characters" placeholder="HK4ABC" value="'+esc(db.yo.ind)+'"></label>'
		+'<label>Tu nombre (opcional)<input class="em-campo" name="nombre" autocomplete="name" value="'+esc(db.yo.nombre)+'"></label>'
		+'<button class="em-btn primario">Continuar</button></form>'
		+'<p class="em-nota">Ábrelo una vez con internet y quedará guardado para usarlo sin conexión. En el celular, «Agregar a la pantalla de inicio» lo deja como una aplicación.</p></section>');
	$('#em-yo', v).addEventListener('submit', function(e){ e.preventDefault(); var x=ind(this.ind.value); if(x.length<3){ aviso('Escribe tu indicativo.','aviso'); return; } db.yo={ind:x, nombre:this.nombre.value.trim()}; guardar(); pintarCabecera(); pintarVista(); });
	return v;
}

/* ── Inicio: guardia, incidentes, alertas, frecuencias, preparación ─────── */
function vistaInicio(){
	var mios=Object.keys(db.incs).map(function(k){ return db.incs[k]; });
	var v=el('<div class="em-grid-inicio">'
		+'<section class="em-tarjeta em-hero"><p class="em-sup">Guardia · '+esc(db.yo.ind)+' <button type="button" class="em-link" id="em-cambiar-yo">cambiar</button></p>'
		+'<h1>¿Hay una emergencia?</h1><p>Actívala aquí y dicta el código y la clave de red por radio: cada operador que se una registra estaciones y mensajes en el mismo tablero.</p>'
		+'<div class="em-acciones"><button type="button" class="em-btn peligro em-grande" id="em-activar">🚨 Activar incidente</button>'
		+'<button type="button" class="em-btn" id="em-unirme">Unirme con código y clave</button>'
		+'<button type="button" class="em-btn" id="em-simulacro">Hacer un simulacro</button>'
		+'<button type="button" class="em-btn" id="em-local">Trabajar sin conexión</button></div></section>'
		+'<section class="em-tarjeta"><h2>Incidentes abiertos en Colombia</h2><div data-vivo="abiertos">'+VIVO.abiertos()+'</div></section>'
		+(mios.length ? '<section class="em-tarjeta"><h2>En este teléfono</h2><div class="em-lista">'+mios.sort(function(a,b){ return (b.visto||0)-(a.visto||0); }).map(function(i){
			return '<button type="button" class="em-fila em-fila-btn" data-retomar="'+esc(i.codigo)+'"><span><strong>'+esc(i.nombre)+'</strong><small>'+(i.local?'Sin conexión':esc(i.codigo))+' · '+(db.ev[i.codigo]||[]).length+' registros'+(i.tipo==='simulacro'?' · simulacro':'')+'</small></span><span>Abrir →</span></button>'; }).join('')+'</div></section>' : '')
		+'<section class="em-tarjeta"><h2>Ahora mismo</h2><div data-vivo="alertas">'+VIVO.alertas()+'</div></section>'
		+'<section class="em-tarjeta"><h2>Frecuencias nacionales</h2>'+tablaFrecuencias()+'</section>'
		+'<section class="em-tarjeta"><h2>Preparación</h2><p class="em-nota">Lo que falló en el sismo de agosto de 2026 fue la energía: generadores que no arrancaron y transferencias que no cambiaron. Revisa esto antes, no durante.</p><div data-vivo="prep">'+VIVO.prep()+'</div>'
		+'<p class="em-nota"><a href="'+esc(C.turnos)+'">Turnos de guardia</a> · <a href="'+esc(C.checkin)+'">Check-in de la malla</a></p></section>'
		+'<section class="em-tarjeta"><h2>Teléfonos</h2>'+telefonos()+'</section>'
		+'</div>');
	$('#em-cambiar-yo', v).addEventListener('click', function(){ db.yo.ind=''; pintarVista(); });
	$('#em-activar', v).addEventListener('click', function(){ formCrear('real'); });
	$('#em-simulacro', v).addEventListener('click', function(){ formCrear('simulacro'); });
	$('#em-unirme', v).addEventListener('click', function(){ pedirClave(''); });
	$('#em-local', v).addEventListener('click', function(){
		modal('Trabajar sin conexión', '<form class="em-form" id="f-local"><p>Todo queda en este teléfono. Puedes pasar los registros a otro con «Exportar» e «Importar».</p><label>Nombre<input class="em-campo" name="nombre" required placeholder="Ej.: Inundación Dosquebradas"></label><label class="em-check"><input type="checkbox" name="sim"> Es un simulacro</label><button class="em-btn primario">Empezar</button></form>', function(c){
			$('#f-local',c).addEventListener('submit', function(e){ e.preventDefault(); var cod='LOCAL-'+uuid().slice(0,6).toUpperCase(); db.incs[cod]={codigo:cod, nombre:this.nombre.value.trim(), tipo:this.sim.checked?'simulacro':'real', zona:'', estado:'abierto', local:true, creador:db.yo.ind}; entrar(cod); cerrarModal(); });
		});
	});
	v.addEventListener('click', function(e){
		var r=e.target.closest('[data-retomar]'); if(r) entrar(r.dataset.retomar);
		var u=e.target.closest('[data-unir]'); if(u){ if(db.incs[u.dataset.unir] && db.incs[u.dataset.unir].token) entrar(u.dataset.unir); else pedirClave(u.dataset.unir); }
	});
	cargarAbiertos();
	return v;
}
var abiertos=null;
function cargarAbiertos(){
	fetch(C.ajax+'?action=rau_em_lista', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){ abiertos=j.success?j.data:[]; refrescar(); }).catch(function(){ abiertos='sin'; refrescar(); });
}
VIVO.abiertos = function(){
	if(abiertos===null) return '<p class="em-nota">Consultando…</p>';
	if(abiertos==='sin') return '<p class="em-nota">Sin conexión: no se puede consultar. Si te dictaron un código y una clave, únete apenas haya señal.</p>';
	if(!abiertos.length) return '<p class="em-nota">No hay incidentes abiertos en este momento.</p>';
	return '<div class="em-lista">'+abiertos.map(function(i){ return '<div class="em-fila"><span><strong>'+esc(i.nombre)+'</strong> '+(i.tipo==='simulacro'?pill('aviso','Simulacro'):pill('peligro','Real'))+'<small>'+esc(i.zona||'Sin zona')+' · abrió '+esc(i.creador)+' · actividad '+haceTxt(i.actividad*1000)+'</small></span><button type="button" class="em-btn" data-unir="'+esc(i.codigo)+'">Unirme</button></div>'; }).join('')+'</div>';
};
function claveSugerida(){ var p=['CONDOR','CEIBA','GUADUA','PALMA','NEVADO','TOLIMA','CAFETO','ORQUIDEA','COLIBRI','FRAILEJON','YARUMO','SAMAN']; return p[Math.floor(Math.random()*p.length)]+'-'+(10+Math.floor(Math.random()*90)); }
function formCrear(tipo){
	var sim=tipo==='simulacro';
	modal(sim?'Nuevo simulacro':'Activar un incidente', '<form class="em-form" id="f-crear">'
		+'<label>Nombre<input class="em-campo" name="nombre" required placeholder="'+(sim?'Ej.: Simulacro nacional de octubre':'Ej.: Sismo Eje Cafetero')+'"></label>'
		+'<label>Zona<input class="em-campo" name="zona" placeholder="Municipio, departamento"></label>'
		+'<label>Clave de red<input class="em-campo em-mono" name="clave" required minlength="4" value="'+claveSugerida()+'"><small>Fácil de dictar por radio. Quien la tenga podrá registrar en este incidente.</small></label>'
		+'<button class="em-btn '+(sim?'primario':'peligro')+'">'+(sim?'Crear simulacro':'Activar incidente')+'</button><p class="em-nota" id="f-crear-msg"></p></form>', function(c){
		$('#f-crear',c).addEventListener('submit', function(e){
			e.preventDefault(); var f=this, b=$('button',f); b.disabled=true; $('#f-crear-msg').textContent='Creando…';
			post({action:'rau_em_crear', nombre:f.nombre.value, zona:f.zona.value, tipo:tipo, indicativo:db.yo.ind, clave:f.clave.value}).then(function(j){
				b.disabled=false;
				if(!j.success){ $('#f-crear-msg').textContent=j.data||'No se pudo crear.'; return; }
				var i=j.data.incidente; i.token=j.data.token; db.incs[i.codigo]=i; entrar(i.codigo);
				registrar('inc', {estado:'abierto', nota:'Incidente activado por '+db.yo.ind});
				modal('Dicta esto por radio', '<p class="em-dictar">Código <strong class="em-mono">'+esc(i.codigo)+'</strong><br><small>'+fonetico(i.codigo)+'</small></p><p class="em-dictar">Clave <strong class="em-mono">'+esc(f.clave.value)+'</strong></p><p>Cada operador entra a <strong>'+esc(location.host)+'/emergencias</strong>, toca «Unirme con código y clave» y escribe ambos. El código también aparece en la cabecera.</p><button type="button" class="em-btn primario" id="ok-dictar">Entendido</button>', function(c2){ $('#ok-dictar',c2).addEventListener('click', cerrarModal); });
			}).catch(function(){ b.disabled=false; $('#f-crear-msg').textContent='Sin conexión: no se puede crear en el servidor. Usa «Trabajar sin conexión» y exporta los registros después.'; });
		});
	});
}
function pedirClave(codigo){
	modal('Unirme a un incidente', '<form class="em-form" id="f-unir"><label>Código<input class="em-campo em-mono em-grande" name="codigo" required value="'+esc(codigo)+'" autocapitalize="characters" autocomplete="off"></label>'
		+'<label>Clave de red<input class="em-campo em-mono" name="clave" required autocomplete="off"></label><button class="em-btn primario">Entrar</button><p class="em-nota" id="f-unir-msg">La da la estación de control por radio.</p></form>', function(c){
		$('#f-unir',c).addEventListener('submit', function(e){
			e.preventDefault(); var f=this; $('#f-unir-msg').textContent='Verificando…';
			post({action:'rau_em_entrar', codigo:f.codigo.value, clave:f.clave.value}).then(function(j){
				if(!j.success){ $('#f-unir-msg').textContent=j.data||'No se pudo entrar.'; return; }
				var i=j.data.incidente, prev=db.incs[i.codigo]||{}; i.token=j.data.token; i.visto=prev.visto; db.incs[i.codigo]=i; cerrarModal(); entrar(i.codigo);
			}).catch(function(){ $('#f-unir-msg').textContent='Sin conexión. Inténtalo cuando haya señal; mientras tanto puedes «Trabajar sin conexión».'; });
		});
	});
}
function entrar(cod){
	db.actual=cod; db.incs[cod].visto=Date.now(); db.ev[cod]=db.ev[cod]||[]; guardar(); recalcular();
	vista='tablero'; sync.error=''; pintarCabecera(); pintarVista(); pintarSync(); sincronizar();
}
function salir(){ db.actual=''; guardar(); vista='tablero'; pintarCabecera(); pintarVista(); pintarSync(); }

VIVO.prep = function(){
	return PREPARACION.map(function(g, gi){
		return '<details class="em-prep"><summary>'+esc(g.g)+' <span class="em-prep-n" data-prep-n="'+gi+'"></span></summary><ul>'+g.items.map(function(t, ii){
			var k=gi+'.'+ii, f=db.prep[k], vencido=f && (Date.now()-f)>g.dias*864e5;
			return '<li><label class="em-check"><input type="checkbox" data-prep="'+k+'"'+(f&&!vencido?' checked':'')+'> <span>'+esc(t)+(f?'<small class="'+(vencido?'em-rojo':'')+'"> · revisado '+new Date(f).toLocaleDateString('es-CO')+(vencido?' (revisar de nuevo)':'')+'</small>':'')+'</span></label></li>';
		}).join('')+'</ul></details>';
	}).join('');
};
document.addEventListener('change', function(e){
	var k=e.target.dataset && e.target.dataset.prep; if(!k) return;
	if(e.target.checked) db.prep[k]=Date.now(); else delete db.prep[k]; guardar();
});
function telefonos(){
	return '<div class="em-tels"><a href="tel:123"><strong>123</strong>Línea única de emergencias</a><a href="tel:132"><strong>132</strong>Cruz Roja</a><a href="tel:144"><strong>144</strong>Defensa Civil</a><a href="tel:119"><strong>119</strong>Bomberos</a></div>';
}
function tablaFrecuencias(){
	return '<table class="em-tabla"><tbody>'+(C.frec||[]).map(function(f){ return '<tr><td class="em-f">'+esc(f.f)+'</td><td class="em-nw">'+esc(f.b)+' · '+esc(f.m)+'</td><td>'+esc(f.uso)+'</td></tr>'; }).join('')+'</tbody></table>'
		+'<p class="em-nota">Centros de actividad de la IARU Región 2 que activó la Liga Colombiana de Radioaficionados en el sismo de agosto de 2026 (reportes de la ARRL y la IARU): el tráfico puede estar hasta ±20 kHz alrededor.</p>';
}

/* Recomendación de banda: dentro de Colombia (hasta ~600 km) se usa onda de cielo casi vertical
   (NVIS); de día 40 m, de noche 80 m. Lo que oye el receptor de HK4RAU dice qué bandas abren lejos. */
function recomendacionBanda(){
	var h=new Date().getUTCHours()-5; if(h<0) h+=24;
	var dia=h>=7 && h<18, o=db.alertas && db.alertas.oido;
	var r='<p><strong>Dentro de Colombia:</strong> '+(dia?'40 m (7.060). De día la onda de cielo vuelve cerca.':'80 m (3.985). De noche 40 m salta lejos y deja de oírse a 100–500 km.')+'</p>';
	if(o && o.bandas){
		var b=['40m','20m','15m','10m'].map(function(k){ var x=o.bandas[k]; return k.replace('m',' m')+': '+(x&&x.n?'<strong>abierta</strong> ('+x.n+' estaciones, hasta '+Math.round(x.lejos/100)/10+' mil km)':'sin estaciones'); }).join(' · ');
		r+='<p><strong>Hacia afuera</strong> (lo que oye el receptor HK4RAU en '+esc(o.lugar||'Colombia')+', últimos 30 min): '+b+'.</p>';
	}
	return r;
}
VIVO.alertas = function(){
	var a=db.alertas && db.alertas.alertas, h='';
	if(!db.alertas) return '<p class="em-nota">Cargando alertas… (necesita internet)</p>'+recomendacionBanda();
	h+=recomendacionBanda();
	if(a && a.gdacs && a.gdacs.length) h+='<h3>Alertas de desastre (GDACS)</h3><div class="em-lista">'+a.gdacs.slice(0,4).map(function(g){ return '<div class="em-fila"><span>'+pill(g.nivel==='red'?'peligro':g.nivel==='orange'?'aviso':'ok', g.nivel==='red'?'Roja':g.nivel==='orange'?'Naranja':'Verde')+' <strong>'+esc(g.tipo)+'</strong> '+esc(g.nombre)+'<small>desde '+new Date(g.desde*1000).toLocaleDateString('es-CO')+'</small></span>'+(g.url?'<a href="'+esc(g.url)+'" target="_blank" rel="noopener">Ver</a>':'')+'</div>'; }).join('')+'</div>';
	if(a && a.sismos){
		var s=a.sismos.slice(0,6);
		h+='<h3>Sismos de magnitud 3,5 o más, últimos 7 días (USGS)</h3>'+(s.length?'<div class="em-lista">'+s.map(function(x){ return '<div class="em-fila"><span><strong class="em-mag'+(x.m>=6?' em-rojo':x.m>=5?' em-ambar':'')+'">M'+x.m.toFixed(1)+'</strong> '+esc(x.lugar)+'<small>'+haceTxt(x.t*1000)+' · '+x.prof+' km de profundidad</small></span><a href="'+esc(x.url)+'" target="_blank" rel="noopener">Ver</a></div>'; }).join('')+'</div>':'<p class="em-nota">Ninguno en Colombia y alrededores.</p>');
	}
	h+='<p class="em-nota">Datos de '+haceTxt(db.alertas.recibido)+(navigator.onLine===false?' · sin internet, se muestra lo último guardado':'')+'. El Servicio Geológico Colombiano no publica sus datos para otras aplicaciones; confirma en sgc.gov.co.</p>';
	return h;
};

/* ── Tablero ─────────────────────────────────────────────────────────────── */
function atencion(){
	var l=[];
	Object.keys(S.est).forEach(function(k){ var x=S.est[k];
		if(x.estado==='urgente') l.push({o:0, c:'peligro', t:x.ind+' reporta URGENCIA'+(x.nota?': '+x.nota:''), ir:'red'});
		else if(x.estado==='apoyo') l.push({o:2, c:'aviso', t:x.ind+' necesita apoyo'+(x.nota?': '+x.nota:''), ir:'red'});
		if(silenciosa(x)) l.push({o:3, c:'aviso', t:x.ind+' sin contacto '+haceTxt(x.ultimo)+(x.sinresp?' · '+x.sinresp+' llamadas sin respuesta':''), ir:'red'});
	});
	Object.keys(S.msgs).forEach(function(k){ var m=S.msgs[k];
		if((m.est==='pendiente'||m.est==='recibido') && (m.d.prec==='E'||m.d.prec==='P')) l.push({o:m.d.prec==='E'?1:4, c:m.d.prec==='E'?'peligro':'aviso', t:'Mensaje '+PRECEDENCIAS[m.d.prec].n.toUpperCase()+' n.º '+m.d.num+' de '+m.d.origen+' sin entregar', ir:'mensajes'});
	});
	Object.keys(S.repe).forEach(function(k){ if(S.repe[k].e==='caida') l.push({o:5, c:'peligro', t:'Repetidora caída: '+k, ir:'plan'}); });
	return l.sort(function(a,b){ return a.o-b.o; });
}
VIVO.atencion = function(){
	var l=atencion();
	return l.length ? '<div class="em-lista">'+l.map(function(x){ return '<button type="button" class="em-fila em-fila-btn em-borde-'+x.c+'" data-ir="'+x.ir+'"><span>'+esc(x.t)+'</span><span>→</span></button>'; }).join('')+'</div>' : '<p class="em-ok-grande">Nada pendiente.</p>';
};
VIVO.resumen = function(){
	var e=Object.keys(S.est).map(function(k){ return S.est[k]; }), act=e.filter(function(x){ return x.estado!=='fuera' && !silenciosa(x); }).length;
	var msgs=Object.keys(S.msgs).map(function(k){ return S.msgs[k]; }), pend=msgs.filter(function(m){ return m.est==='pendiente'||m.est==='recibido'; });
	var per=Object.keys(S.per).map(function(k){ return S.per[k]; });
	var rep=Object.keys(S.repe).map(function(k){ return S.repe[k]; });
	function caja(n, t, sub, irA, c){ return '<button type="button" class="em-cifra '+(c||'')+'" data-ir="'+irA+'"><strong>'+n+'</strong><span>'+t+'</span><small>'+sub+'</small></button>'; }
	return caja(act+'/'+e.length, 'Estaciones activas', e.filter(silenciosa).length+' sin contacto', 'red', e.some(function(x){ return x.estado==='urgente'; })?'peligro':'')
		+caja(pend.length, 'Mensajes por entregar', ['E','P','B','R'].map(function(p){ var n=pend.filter(function(m){ return m.d.prec===p; }).length; return n?n+' '+PRECEDENCIAS[p].n.toLowerCase():''; }).filter(Boolean).join(' · ')||'ninguno', 'mensajes', pend.some(function(m){ return m.d.prec==='E'; })?'peligro':'')
		+caja(per.length, 'Personas registradas', per.filter(function(p){ return p.est==='sinnoticias'||p.est==='buscado'; }).length+' sin noticias o buscadas', 'personas')
		+caja(rep.filter(function(r){ return r.e!=='caida'; }).length+'/'+rep.length, 'Repetidoras en el aire', (rep.length?rep.filter(function(r){ return r.e==='caida'; }).length+' caídas':'sin reportes todavía'), 'plan', rep.some(function(r){ return r.e==='caida'; })?'aviso':'');
};
function describir(e){
	var d=e.d||{};
	switch(e.t){
	case 'checkin': return 'Registró a <b>'+esc(d.ind)+'</b>'+(d.ubic?' en '+esc(d.ubic):'')+(d.estado?' · '+esc((ESTADOS_EST[d.estado]||{}).n||d.estado):'')+(d.energia?' · '+esc(ENERGIA[d.energia]||d.energia):'')+(d.trafico?' · '+d.trafico+' mensajes':'')+(d.nota?' — '+esc(d.nota):'');
	case 'contacto': return 'Contacto con <b>'+esc(d.ind)+'</b>'+(d.estado?' · '+esc((ESTADOS_EST[d.estado]||{}).n||''):'')+(d.nota?' — '+esc(d.nota):'');
	case 'sinresp': return '<b>'+esc(d.ind)+'</b> no respondió';
	case 'msg': return 'Mensaje '+esc((PRECEDENCIAS[d.prec]||{}).n||'')+' n.º '+esc(d.num)+' de <b>'+esc(d.origen)+'</b> para '+esc(d.para)+(d.estadoInicial==='recibido'?' (recibido)':'');
	case 'msgest': var m=S.msgs[d.m]; return 'Mensaje n.º '+esc(m?m.d.num:'?')+' de '+esc(m?m.d.origen:'?')+': '+esc(ESTADOS_MSG[d.e]||d.e)+(d.aq?' a '+esc(d.aq):'')+(d.n?' — '+esc(d.n):'');
	case 'nota': return '<span class="em-tipo-nota">'+esc(d.tipo||'Nota')+'</span> '+esc(d.texto);
	case 'plan': return 'Plan: '+esc(d.funcion)+' en '+esc(d.f)+' '+esc(d.modo||'');
	case 'planx': return 'Plan: se quitó una asignación';
	case 'persona': return 'Bienestar: <b>'+esc(d.nombre)+'</b> · '+esc((ESTADOS_PER[d.estado]||{}).n||'')+(d.ubic?' · '+esc(d.ubic):'');
	case 'personaact': var p=S.per[d.p]; return 'Bienestar: '+esc(p?p.d.nombre:'?')+' → '+esc((ESTADOS_PER[d.estado]||{}).n||'')+(d.n?' — '+esc(d.n):'');
	case 'recurso': return 'Recurso: '+esc(d.cant||1)+' × '+esc(d.tipo)+' '+esc(d.desc||'')+(d.donde?' en '+esc(d.donde):'');
	case 'recursox': return 'Recurso retirado';
	case 'repe': return 'Repetidora '+esc(d.r)+': '+esc((ESTADOS_REPE[d.e]||{}).n||d.e)+(d.n?' — '+esc(d.n):'');
	case 'dir': return 'Directorio: '+esc(d.nombre)+' ('+esc(d.funcion||'')+')';
	case 'dirx': return 'Directorio: se quitó un contacto';
	case 'inc': return d.estado==='cerrado'?'<b>Incidente cerrado</b>'+(d.nota?' — '+esc(d.nota):''):esc(d.nota||'Incidente abierto');
	}
	return esc(e.t);
}
function filaBitacora(e){ return '<li><time>'+horaZ(e.ts)+'</time><span class="em-autor">'+esc(e.a)+'</span><span>'+describir(e)+'</span>'+(e.s?'':'<i class="em-pend" title="Todavía no se envió al servidor">●</i>')+'</li>'; }
VIVO.ultimos = function(){ var l=S.todo.slice(-8).reverse(); return l.length?'<ol class="em-bit">'+l.map(filaBitacora).join('')+'</ol>':'<p class="em-nota">Todavía no hay registros.</p>'; };
VISTA_FN.tablero = function(){
	var v=el('<div class="em-tablero">'
		+'<section class="em-resumen" data-vivo="resumen">'+VIVO.resumen()+'</section>'
		+'<section class="em-tarjeta em-atencion"><h2>Atención ahora</h2><div data-vivo="atencion">'+VIVO.atencion()+'</div></section>'
		+'<section class="em-tarjeta"><h2>Acciones rápidas</h2><div class="em-acciones">'
		+'<button type="button" class="em-btn primario em-grande" data-accion="checkin">+ Registrar estación</button>'
		+'<button type="button" class="em-btn em-grande" data-accion="lista">Pasar lista</button>'
		+'<button type="button" class="em-btn em-grande" data-accion="msg">+ Mensaje</button>'
		+'<button type="button" class="em-btn em-grande" data-accion="nota">+ Nota en la bitácora</button></div></section>'
		+'<section class="em-tarjeta"><h2>Últimos registros</h2><div data-vivo="ultimos">'+VIVO.ultimos()+'</div><button type="button" class="em-link" data-ir="bitacora">Ver toda la bitácora →</button></section>'
		+'<section class="em-tarjeta"><h2>Bandas y alertas</h2><div data-vivo="alertas">'+VIVO.alertas()+'</div></section>'
		+'</div>');
	v.addEventListener('click', function(e){ var b=e.target.closest('[data-accion]'); if(!b) return;
		({checkin:function(){ formEstacion(); }, lista:function(){ ir('red'); setTimeout(pasarLista, 50); }, msg:function(){ formMensaje(); }, nota:function(){ formNota(); }})[b.dataset.accion](); });
	return v;
};

/* ── Red: estaciones, pase de lista ──────────────────────────────────────── */
function formEstacion(pre){
	var x=pre||{};
	modal(pre?'Actualizar '+pre.ind:'Registrar estación', '<form class="em-form" id="f-est">'
		+'<label>Indicativo<input class="em-campo em-mono em-grande" name="ind" required autocomplete="off" autocapitalize="characters" value="'+esc(x.ind||'')+'"'+(pre?' readonly':'')+'></label>'
		+'<div class="em-dos"><label>Nombre o función<input class="em-campo" name="nombre" value="'+esc(x.nombre||'')+'" placeholder="Ej.: Bomberos Dosquebradas"></label>'
		+'<label>Ubicación<span class="em-con-boton"><input class="em-campo" name="ubic" value="'+esc(x.ubic||'')+'" placeholder="Municipio, sitio"><button type="button" class="em-btn" id="f-est-gps" title="Usar el GPS de este teléfono (solo si la estación eres tú)">📍</button></span></label></div>'
		+'<input type="hidden" name="lat" value="'+esc(x.lat||'')+'"><input type="hidden" name="lng" value="'+esc(x.lng||'')+'"><input type="hidden" name="grid" value="'+esc(x.grid||'')+'">'
		+'<fieldset><legend>Estado</legend>'+chips('estado', ESTADOS_EST, x.estado||'bien')+'</fieldset>'
		+'<fieldset><legend>Energía</legend>'+chips('energia', ENERGIA, x.energia||'')+'</fieldset>'
		+'<div class="em-dos"><label>Mensajes que tiene para pasar<input class="em-campo" type="number" min="0" max="99" name="trafico" value="'+esc(x.trafico||0)+'"></label>'
		+'<label>Nota<input class="em-campo" name="nota" value="" placeholder="Ej.: sin agua, vía cerrada"></label></div>'
		+'<button class="em-btn primario em-grande">'+(pre?'Guardar':'Registrar')+'</button></form>', function(c){
		var f=$('#f-est',c);
		f.ind.addEventListener('input', function(){ var y=ind(this.value); if(y!==this.value) this.value=y; });
		$('#f-est-gps',c).addEventListener('click', function(){
			if(!navigator.geolocation){ aviso('Este teléfono no tiene GPS disponible.','aviso'); return; }
			aviso('Buscando posición…');
			navigator.geolocation.getCurrentPosition(function(p){ var la=p.coords.latitude, lo=p.coords.longitude; f.lat.value=la.toFixed(4); f.lng.value=lo.toFixed(4); f.grid.value=locator(la,lo); if(!f.ubic.value) f.ubic.value=f.grid.value; aviso('Posición: '+f.grid.value); }, function(){ aviso('No se pudo leer el GPS.','aviso'); }, {enableHighAccuracy:true, timeout:15000});
		});
		f.addEventListener('submit', function(e){
			e.preventDefault(); var i=ind(f.ind.value); if(i.length<3){ aviso('Falta el indicativo.','aviso'); return; }
			var d={ind:i, nombre:f.nombre.value.trim(), ubic:f.ubic.value.trim(), estado:valorDe(f,'estado'), energia:valorDe(f,'energia'), trafico:+f.trafico.value||0, nota:f.nota.value.trim()};
			if(f.lat.value){ d.lat=+f.lat.value; d.lng=+f.lng.value; d.grid=f.grid.value; }
			registrar('checkin', d); cerrarModal(); aviso(i+' registrada.', d.estado==='urgente'?'peligro':'');
		});
	});
}
var filtroRed='todas';
VIVO.estaciones = function(){
	var l=Object.keys(S.est).map(function(k){ return S.est[k]; });
	var orden={urgente:0, apoyo:1, bien:2, fuera:4};
	l.sort(function(a,b){ return (orden[a.estado]||2)-(orden[b.estado]||2) || (silenciosa(b)-silenciosa(a)) || a.ultimo-b.ultimo; });
	if(filtroRed==='atencion') l=l.filter(function(x){ return x.estado==='urgente'||x.estado==='apoyo'||silenciosa(x); });
	if(!l.length) return '<p class="em-nota">'+(filtroRed==='atencion'?'Ninguna estación necesita atención.':'Todavía no hay estaciones. Registra la primera con «+ Registrar estación».')+'</p>';
	return '<div class="em-estaciones">'+l.map(function(x){
		var es=ESTADOS_EST[x.estado]||ESTADOS_EST.bien, m=edad(x.ultimo), sil=silenciosa(x);
		return '<article class="em-est em-borde-'+es.c+(lista.actual===x.ind?' is-lista':'')+'" data-est="'+esc(x.ind)+'">'
			+'<div class="em-est-cab"><strong class="em-mono em-ind">'+esc(x.ind)+'</strong>'+pill(es.c, es.n)+(x.energia?pill('apagado', ENERGIA[x.energia]||x.energia):'')+(x.trafico?pill('aviso', x.trafico+' msj'):'')+'</div>'
			+'<p>'+esc(x.nombre||'')+(x.nombre&&x.ubic?' · ':'')+esc(x.ubic||'')+(x.grid&&x.grid!==x.ubic?' <span class="em-mono">'+esc(x.grid)+'</span>':'')+'</p>'
			+(x.nota?'<p class="em-nota-est">'+esc(x.nota)+'</p>':'')
			+'<p class="em-edad '+(sil?'em-rojo':m>=db.intervalo/2?'em-ambar':'em-verde')+'">Último contacto '+haceTxt(x.ultimo)+' ('+horaZ(x.ultimo)+')'+(x.sinresp?' · '+x.sinresp+' sin respuesta':'')+'</p>'
			+'<div class="em-est-acc"><button type="button" class="em-btn ok" data-est-acc="si">✓ Respondió</button><button type="button" class="em-btn" data-est-acc="no">✗ Sin respuesta</button><button type="button" class="em-btn" data-est-acc="editar">Editar</button><button type="button" class="em-btn" data-est-acc="msg">Mensaje</button></div>'
			+'</article>';
	}).join('')+'</div>';
};
var lista={cola:[], actual:''};
function pasarLista(){
	lista.cola=Object.keys(S.est).filter(function(k){ return S.est[k].estado!=='fuera'; }).sort(function(a,b){ return S.est[a].ultimo-S.est[b].ultimo; });
	if(!lista.cola.length){ aviso('No hay estaciones para llamar.','aviso'); return; }
	siguienteLista();
}
function siguienteLista(){
	lista.actual=lista.cola.shift()||'';
	if(!lista.actual){ aviso('Lista terminada.'); refrescar(); return; }
	var x=S.est[lista.actual];
	modal('Pase de lista · faltan '+lista.cola.length, '<p class="em-dictar"><strong class="em-mono">'+esc(x.ind)+'</strong><br><small>'+fonetico(x.ind)+'</small></p><p class="em-nota">Último contacto '+haceTxt(x.ultimo)+(x.ubic?' · '+esc(x.ubic):'')+'</p>'
		+'<form class="em-form" id="f-lista"><fieldset><legend>Estado</legend>'+chips('estado', ESTADOS_EST, x.estado||'bien')+'</fieldset><label>Mensajes para pasar<input class="em-campo" type="number" min="0" name="trafico" value="'+(x.trafico||0)+'"></label><label>Nota<input class="em-campo" name="nota"></label>'
		+'<div class="em-acciones"><button class="em-btn ok em-grande">✓ Respondió</button><button type="button" class="em-btn em-grande" id="f-lista-no">✗ Sin respuesta</button><button type="button" class="em-btn" id="f-lista-fin">Terminar</button></div></form>', function(c){
		var f=$('#f-lista',c);
		f.addEventListener('submit', function(e){ e.preventDefault(); registrar('contacto', {ind:x.ind, estado:valorDe(f,'estado'), trafico:+f.trafico.value||0, nota:f.nota.value.trim()}); siguienteLista(); });
		$('#f-lista-no',c).addEventListener('click', function(){ registrar('sinresp', {ind:x.ind}); siguienteLista(); });
		$('#f-lista-fin',c).addEventListener('click', function(){ lista.cola=[]; lista.actual=''; cerrarModal(); refrescar(); });
	});
}
VISTA_FN.red = function(){
	var v=el('<div><div class="em-barra"><h1>Red de estaciones</h1><div class="em-acciones"><button type="button" class="em-btn primario" id="r-nueva">+ Registrar estación</button><button type="button" class="em-btn" id="r-lista">Pasar lista</button></div></div>'
		+'<div class="em-barra em-filtros"><div class="em-seg"><button type="button" data-f="todas" class="'+(filtroRed==='todas'?'is-on':'')+'">Todas</button><button type="button" data-f="atencion" class="'+(filtroRed==='atencion'?'is-on':'')+'">Necesitan atención</button></div>'
		+'<label class="em-inline">Reporte esperado cada <select class="em-campo" id="r-int">'+[10,15,30,60,120].map(function(n){ return '<option value="'+n+'"'+(db.intervalo===n?' selected':'')+'>'+n+' min</option>'; }).join('')+'</select></label></div>'
		+'<div data-vivo="estaciones">'+VIVO.estaciones()+'</div></div>');
	$('#r-nueva',v).addEventListener('click', function(){ formEstacion(); });
	$('#r-lista',v).addEventListener('click', pasarLista);
	$('#r-int',v).addEventListener('change', function(){ db.intervalo=+this.value; guardar(); refrescar(); });
	$$('.em-seg button',v).forEach(function(b){ b.addEventListener('click', function(){ filtroRed=b.dataset.f; $$('.em-seg button',v).forEach(function(x){ x.classList.toggle('is-on', x===b); }); refrescar(); }); });
	v.addEventListener('click', function(e){
		var b=e.target.closest('[data-est-acc]'); if(!b) return; var k=b.closest('[data-est]').dataset.est, x=S.est[k];
		if(b.dataset.estAcc==='si') { registrar('contacto', {ind:k}); aviso(k+': contacto registrado.'); }
		if(b.dataset.estAcc==='no') { registrar('sinresp', {ind:k}); }
		if(b.dataset.estAcc==='editar') formEstacion(x);
		if(b.dataset.estAcc==='msg') formMensaje({origen:k});
	});
	return v;
};

/* ── Mensajes formales: radiograma y SCI-213 ─────────────────────────────── */
function siguienteNumero(){ var n=0; Object.keys(S.msgs).forEach(function(k){ var m=S.msgs[k]; if(m.a===db.yo.ind && m.d.origen===db.yo.ind) n=Math.max(n, +m.d.num||0); }); return n+1; }
function formMensaje(pre){
	pre=pre||{};
	var sim=inc().tipo==='simulacro', ahora=Date.now(), origen=pre.origen||db.yo.ind;
	modal('Nuevo mensaje', '<form class="em-form" id="f-msg">'
		+'<div class="em-seg em-seg-ancho" id="f-msg-fmt"><button type="button" data-fmt="rg" class="is-on">Radiograma</button><button type="button" data-fmt="sci">SCI-213 (a organismos)</button></div>'
		+'<fieldset><legend>Precedencia</legend>'+chips('prec', {E:{n:'Emergencia',c:'peligro'},P:{n:'Prioridad',c:'aviso'},B:{n:'Bienestar',c:'ok'},R:{n:'Rutina',c:''}}, pre.prec||'P')+'<small id="f-msg-prec"></small></fieldset>'
		+'<label class="em-check"><input type="checkbox" name="prueba"'+(sim?' checked':'')+'> Mensaje de prueba (simulacro): se transmite como «PRUEBA»</label>'
		+'<div class="em-tres"><label>Estación de origen<input class="em-campo em-mono" name="origen" value="'+esc(origen)+'" autocapitalize="characters"></label>'
		+'<label>Número<input class="em-campo em-mono" name="num" inputmode="numeric" value="'+(origen===db.yo.ind?siguienteNumero():'')+'"></label>'
		+'<label>Lugar de origen<input class="em-campo" name="lugar" value="'+esc(pre.lugar||'')+'" placeholder="Municipio"></label></div>'
		+'<div class="em-solo-rg"><label>Instrucción de manejo<select class="em-campo" name="hx">'+Object.keys(HX).map(function(k){ return '<option value="'+k+'">'+esc(HX[k])+'</option>'; }).join('')+'</select></label></div>'
		+'<div class="em-dos"><label><span class="em-solo-rg">Para (nombre y dirección)</span><span class="em-solo-sci">Para (nombre y cargo)</span><input class="em-campo" name="para" required></label>'
		+'<label class="em-solo-rg">Teléfono del destinatario<input class="em-campo" name="tel" inputmode="tel"></label><label class="em-solo-sci">De (nombre y cargo)<input class="em-campo" name="de"></label></div>'
		+'<label class="em-solo-sci">Asunto<input class="em-campo" name="asunto"></label>'
		+'<label>Texto <small id="f-msg-ck">0 palabras</small><textarea class="em-campo em-mono" name="texto" rows="5" required placeholder="Corto y sin signos: PUNTO en vez de «.» · Máximo 25 palabras en un radiograma"></textarea></label>'
		+'<label>Firma<input class="em-campo" name="firma" placeholder="Nombre y cargo de quien lo envía"></label>'
		+'<label class="em-check"><input type="checkbox" name="recibido"'+(origen!==db.yo.ind?' checked':'')+'> Lo recibí de otra estación (no lo originé yo)</label>'
		+'<button class="em-btn primario em-grande">Guardar mensaje</button></form>', function(c){
		var f=$('#f-msg',c), fmt='rg';
		function verFmt(){ $$('.em-solo-rg',c).forEach(function(x){ x.hidden=fmt!=='rg'; }); $$('.em-solo-sci',c).forEach(function(x){ x.hidden=fmt!=='sci'; }); }
		$$('#f-msg-fmt button',c).forEach(function(b){ b.addEventListener('click', function(){ fmt=b.dataset.fmt; $$('#f-msg-fmt button',c).forEach(function(x){ x.classList.toggle('is-on', x===b); }); verFmt(); }); });
		function verPrec(){ var p=PRECEDENCIAS[valorDe(f,'prec')]; $('#f-msg-prec',c).textContent=p?p.d:''; }
		f.addEventListener('change', verPrec); verPrec(); verFmt();
		f.texto.addEventListener('input', function(){ var n=palabras(this.value); $('#f-msg-ck',c).textContent=n+' palabras (cuenta o «check»)'+(fmt==='rg'&&n>25?' · largo para un radiograma':''); });
		f.origen.addEventListener('input', function(){ var y=ind(this.value); if(y!==this.value) this.value=y; f.recibido.checked = y!==db.yo.ind; if(y===db.yo.ind && !f.num.value) f.num.value=siguienteNumero(); });
		f.addEventListener('submit', function(e){
			e.preventDefault();
			var d={fmt:fmt, prec:valorDe(f,'prec'), prueba:f.prueba.checked, origen:ind(f.origen.value)||db.yo.ind, num:f.num.value.trim()||String(siguienteNumero()), lugar:f.lugar.value.trim(),
				hx:fmt==='rg'?f.hx.value:'', para:f.para.value.trim(), tel:fmt==='rg'?f.tel.value.trim():'', de:fmt==='sci'?f.de.value.trim():'', asunto:fmt==='sci'?f.asunto.value.trim():'',
				texto:f.texto.value.trim(), firma:f.firma.value.trim(), ck:palabras(f.texto.value), hora:horaZ(ahora), fecha:fechaZ(ahora), estadoInicial:f.recibido.checked?'recibido':'pendiente'};
			var e2=registrar('msg', d); cerrarModal(); aviso('Mensaje n.º '+d.num+' guardado.'); if(e2) leerMensaje(e2.u);
		});
	});
}
function textoMensaje(m, hablado){
	var d=m.d, p=PRECEDENCIAS[d.prec]||{n:''}, pr=(d.prueba?'PRUEBA ':'')+p.n.toUpperCase();
	if(d.fmt==='sci'){
		return 'FORMULARIO SCI-213 · MENSAJE GENERAL'+(d.prueba?' · PRUEBA':'')+'\nIncidente: '+inc().nombre+'\nPrecedencia: '+pr+'\nPara: '+d.para+'\nDe: '+(d.de||d.origen)+'\nAsunto: '+(d.asunto||'')+'\nFecha y hora: '+d.fecha+' '+d.hora+'\nMensaje: '+d.texto+'\nFirma: '+(d.firma||'')+'\nTransmitido por: '+d.origen+' n.º '+d.num;
	}
	if(hablado){
		return 'Número '+d.num+', '+pr.toLowerCase()+(d.hx?', '+d.hx:'')+', estación de origen '+fonetico(d.origen)+', cuenta '+d.ck+(d.lugar?', '+d.lugar:'')+', hora '+d.hora.replace('Z',' zulú')+', '+d.fecha.replace(/^(\d+) (\w+)$/, function(x, dia, mes){ return dia+' de '+MESES_LARGOS[MESES.indexOf(mes)]; })+'.\n\nPara: '+d.para+(d.tel?', teléfono '+d.tel.split('').join(' '):'')+'.\n\nTexto: (pausa) '+d.texto+' (pausa) fin del texto.\n\nFirma: '+(d.firma||'sin firma')+'.\n\nCambio.';
	}
	return 'N.º '+d.num+' '+pr+(d.hx?' '+d.hx:'')+' '+d.origen+' CK '+d.ck+' '+(d.lugar||'')+' '+d.hora+' '+d.fecha+'\nPARA: '+d.para+(d.tel?' · TEL '+d.tel:'')+'\nBT\n'+d.texto.toUpperCase()+'\nBT\n'+(d.firma||'');
}
function leerMensaje(u){
	var m=S.msgs[u]; if(!m) return;
	modal('Mensaje n.º '+m.d.num+' · '+(PRECEDENCIAS[m.d.prec]||{}).n, '<div class="em-seg em-seg-ancho" id="lm-modo"><button type="button" data-m="0" class="is-on">Para leer por radio</button><button type="button" data-m="1">Formato escrito</button></div>'
		+'<pre class="em-leer" id="lm-texto"></pre>'
		+'<div class="em-acciones"><button type="button" class="em-btn" id="lm-copiar">Copiar</button>'
		+(m.est!=='entregado'&&m.est!=='anulado'?'<button type="button" class="em-btn" data-msg-est="retransmitido">Retransmitido a…</button><button type="button" class="em-btn ok" data-msg-est="entregado">Entregado</button>':'')
		+'</div><h3>Historial</h3><ol class="em-bit">'+[{ts:m.ts, a:m.a, e:m.d.estadoInicial||'pendiente'}].concat(m.hist).map(function(h){ return '<li><time>'+horaZ(h.ts)+'</time><span class="em-autor">'+esc(h.a)+'</span><span>'+esc(ESTADOS_MSG[h.e]||h.e)+(h.a_quien?' a '+esc(h.a_quien):'')+(h.n?' — '+esc(h.n):'')+'</span></li>'; }).join('')+'</ol>', function(c){
		var modo=0; function pintar(){ $('#lm-texto',c).textContent=textoMensaje(m, !modo); }
		$$('#lm-modo button',c).forEach(function(b){ b.addEventListener('click', function(){ modo=+b.dataset.m; $$('#lm-modo button',c).forEach(function(x){ x.classList.toggle('is-on', x===b); }); pintar(); }); });
		pintar();
		$('#lm-copiar',c).addEventListener('click', function(){ copiar(textoMensaje(m, false)); });
		$$('[data-msg-est]',c).forEach(function(b){ b.addEventListener('click', function(){
			var e=b.dataset.msgEst, aq='';
			if(e==='retransmitido'){ aq=ind(prompt('¿A qué estación se lo pasaste?')||''); if(!aq) return; }
			var n=e==='entregado'?(prompt('¿A quién se entregó y cómo? (opcional)')||''):'';
			registrar('msgest', {m:u, e:e, aq:aq, n:n}); cerrarModal(); aviso('Mensaje n.º '+m.d.num+': '+ESTADOS_MSG[e].toLowerCase()+'.');
		}); });
	});
}
var filtroMsg='pendientes';
VIVO.mensajes = function(){
	var l=Object.keys(S.msgs).map(function(k){ return S.msgs[k]; });
	if(filtroMsg==='pendientes') l=l.filter(function(m){ return m.est==='pendiente'||m.est==='recibido'||m.est==='retransmitido'; });
	l.sort(function(a,b){ return ((PRECEDENCIAS[a.d.prec]||{o:9}).o-(PRECEDENCIAS[b.d.prec]||{o:9}).o) || a.ts-b.ts; });
	if(!l.length) return '<p class="em-nota">'+(filtroMsg==='pendientes'?'No hay mensajes por entregar.':'Todavía no hay mensajes.')+'</p>';
	return '<div class="em-lista">'+l.map(function(m){
		var p=m.d.prec, c=p==='E'?'peligro':p==='P'?'aviso':p==='B'?'ok':'apagado';
		return '<button type="button" class="em-msg em-borde-'+c+'" data-msg="'+m.u+'"><span class="em-msg-cab">'+pill(c, (m.d.prueba?'Prueba · ':'')+(PRECEDENCIAS[p]||{n:p}).n)+' <b class="em-mono">n.º '+esc(m.d.num)+'</b> de <b class="em-mono">'+esc(m.d.origen)+'</b> · '+esc(m.d.hora)+(m.d.fmt==='sci'?' · SCI-213':'')+'<span class="em-msg-est">'+esc(ESTADOS_MSG[m.est]||m.est)+'</span></span><span class="em-msg-para">Para '+esc(m.d.para)+'</span><span class="em-msg-txt">'+esc(m.d.texto)+'</span></button>';
	}).join('')+'</div>';
};
VISTA_FN.mensajes = function(){
	var v=el('<div><div class="em-barra"><h1>Mensajes</h1><button type="button" class="em-btn primario" id="m-nuevo">+ Mensaje</button></div>'
		+'<div class="em-barra em-filtros"><div class="em-seg"><button type="button" data-f="pendientes" class="'+(filtroMsg==='pendientes'?'is-on':'')+'">Por entregar</button><button type="button" data-f="todos" class="'+(filtroMsg==='todos'?'is-on':'')+'">Todos</button></div><span class="em-nota">Ordenados por precedencia: emergencia, prioridad, bienestar, rutina.</span></div>'
		+'<div data-vivo="mensajes">'+VIVO.mensajes()+'</div></div>');
	$('#m-nuevo',v).addEventListener('click', function(){ formMensaje(); });
	$$('.em-seg button',v).forEach(function(b){ b.addEventListener('click', function(){ filtroMsg=b.dataset.f; $$('.em-seg button',v).forEach(function(x){ x.classList.toggle('is-on', x===b); }); refrescar(); }); });
	v.addEventListener('click', function(e){ var b=e.target.closest('[data-msg]'); if(b) leerMensaje(b.dataset.msg); });
	return v;
};

/* ── Bitácora (SCI-214) ──────────────────────────────────────────────────── */
function formNota(){
	modal('Nota en la bitácora', '<form class="em-form" id="f-nota"><fieldset><legend>Tipo</legend>'+chips('tipo', {Novedad:'Novedad', Decisión:'Decisión', Solicitud:'Solicitud', Nota:'Nota'}, 'Novedad')+'</fieldset><label>Texto<textarea class="em-campo" name="texto" rows="3" required></textarea></label><button class="em-btn primario">Guardar</button></form>', function(c){
		$('#f-nota',c).addEventListener('submit', function(e){ e.preventDefault(); registrar('nota', {tipo:valorDe(this,'tipo'), texto:this.texto.value.trim()}); cerrarModal(); });
	});
}
var filtroBit='';
VIVO.bitacora = function(){
	var l=S.todo.slice().reverse();
	if(filtroBit) l=l.filter(function(e){ return (e.a+' '+describir(e)).toUpperCase().indexOf(filtroBit.toUpperCase())>=0; });
	return l.length?'<ol class="em-bit em-bit-larga">'+l.map(filaBitacora).join('')+'</ol>':'<p class="em-nota">Sin registros.</p>';
};
VISTA_FN.bitacora = function(){
	var v=el('<div><div class="em-barra"><h1>Bitácora del incidente</h1><div class="em-acciones"><button type="button" class="em-btn primario" id="b-nota">+ Nota</button><button type="button" class="em-btn" id="b-sci">Exportar SCI-214</button></div></div>'
		+'<label class="em-buscar"><input class="em-campo" type="search" id="b-buscar" placeholder="Buscar indicativo, lugar, palabra…" value="'+esc(filtroBit)+'"></label>'
		+'<p class="em-nota">Todo lo que se registra en este incidente, de todos los operadores, en orden. Hora UTC. ● = todavía no llegó al servidor.</p><div data-vivo="bitacora">'+VIVO.bitacora()+'</div></div>');
	$('#b-nota',v).addEventListener('click', formNota);
	$('#b-sci',v).addEventListener('click', function(){ copiar(textoSci214()); });
	$('#b-buscar',v).addEventListener('input', function(){ filtroBit=this.value; refrescar(); });
	return v;
};
function textoPlano(html){ var d=document.createElement('div'); d.innerHTML=html; return d.textContent; }
function textoSci214(){
	var i=inc();
	return 'FORMULARIO SCI-214 · REGISTRO DE ACTIVIDADES\nIncidente: '+i.nombre+(i.zona?' · '+i.zona:'')+(i.tipo==='simulacro'?' · SIMULACRO':'')+'\nCódigo: '+i.codigo+'\nGenerado por: '+db.yo.ind+' · '+new Date().toISOString().slice(0,16).replace('T',' ')+' UTC\n\n'
		+S.todo.map(function(e){ return fechaZ(e.ts)+' '+horaZ(e.ts)+'  '+(e.a||'').padEnd(9)+' '+textoPlano(describir(e)); }).join('\n');
}

/* ── Plan de frecuencias (SCI-205) y repetidoras ─────────────────────────── */
function repetidoras(){
	var l=(C.repes||[]).map(function(r){ return {k:(r.c||r.n)+' '+r.f, n:r.n, f:r.f, extra:(r.o?(+r.o>0?'+':'')+r.o+' MHz':'')+(r.t?' · '+r.t+' Hz':''), l:r.l}; });
	(C.reportadas||[]).forEach(function(r){ l.push({k:r.n+' '+r.f, n:r.n, f:r.f, extra:'confirmar tono', l:''}); });
	Object.keys(S.repe).forEach(function(k){ if(!l.some(function(x){ return x.k===k; })) l.push({k:k, n:k, f:'', extra:'', l:''}); });
	return l;
}
VIVO.plan = function(){
	var a=Object.keys(S.plan).map(function(k){ return S.plan[k]; });
	return a.length?'<table class="em-tabla"><thead><tr><th>Función</th><th>Frecuencia</th><th>Modo</th><th>Observaciones</th><th></th></tr></thead><tbody>'+a.map(function(p){ return '<tr><td>'+esc(p.d.funcion)+'</td><td class="em-f">'+esc(p.d.f)+'</td><td>'+esc(p.d.modo||'')+'</td><td>'+esc(p.d.obs||'')+'</td><td><button type="button" class="em-x" data-planx="'+p.u+'" aria-label="Quitar">×</button></td></tr>'; }).join('')+'</tbody></table>':'<p class="em-nota">Asigna qué frecuencia usa cada función. Lo ven todos los operadores del incidente.</p>';
};
VIVO.repes = function(){
	return '<div class="em-lista">'+repetidoras().map(function(r){ var s=S.repe[r.k];
		return '<div class="em-fila em-repe" data-repe="'+esc(r.k)+'"><span><strong class="em-mono">'+esc(r.f)+'</strong> '+esc(r.n)+'<small>'+esc(r.extra)+(r.l?' · '+esc(r.l):'')+(s?' · '+esc((ESTADOS_REPE[s.e]||{}).n)+' según '+esc(s.a)+' '+haceTxt(s.ts)+(s.n?' — '+esc(s.n):''):' · sin reporte')+'</small></span>'
			+'<span class="em-chips em-chips-mini">'+Object.keys(ESTADOS_REPE).map(function(k){ return '<button type="button" class="em-chip '+ESTADOS_REPE[k].c+(s&&s.e===k?' is-on':'')+'" data-repe-e="'+k+'">'+ESTADOS_REPE[k].n+'</button>'; }).join('')+'</span></div>';
	}).join('')+'</div>';
};
VISTA_FN.plan = function(){
	var v=el('<div><div class="em-barra"><h1>Plan de comunicaciones</h1><div class="em-acciones"><button type="button" class="em-btn primario" id="p-nuevo">+ Asignar frecuencia</button><button type="button" class="em-btn" id="p-imp">Imprimir</button></div></div>'
		+'<section class="em-tarjeta"><h2>Asignaciones del incidente (SCI-205)</h2><div data-vivo="plan">'+VIVO.plan()+'</div></section>'
		+'<section class="em-tarjeta"><h2>Repetidoras</h2><p class="em-nota">Toca el estado cuando sepas cómo está. En el sismo de 2026 varias cayeron porque la transferencia al generador no funcionó.</p><div data-vivo="repes">'+VIVO.repes()+'</div><button type="button" class="em-link" id="p-otra">+ Otra repetidora</button></section>'
		+'<section class="em-tarjeta"><h2>Frecuencias nacionales</h2>'+tablaFrecuencias()+'</section></div>');
	$('#p-imp',v).addEventListener('click', function(){ window.print(); });
	$('#p-otra',v).addEventListener('click', function(){ var n=prompt('Nombre y frecuencia de la repetidora (ej.: Cerro Tatamá 146.940)'); if(n) registrar('repe', {r:n.trim().slice(0,80), e:'aire', n:''}); });
	$('#p-nuevo',v).addEventListener('click', function(){
		modal('Asignar frecuencia', '<form class="em-form" id="f-plan"><label>Función<select class="em-campo" name="funcion">'+FUNCIONES.map(function(x){ return '<option>'+esc(x)+'</option>'; }).join('')+'</select></label>'
			+'<div class="em-dos"><label>Frecuencia o canal<input class="em-campo em-mono" name="f" required placeholder="7.060 · 146.520 · Rep. Cerro…" list="f-plan-l"></label><label>Modo<select class="em-campo" name="modo"><option>FM</option><option>LSB</option><option>USB</option><option>Digital</option><option>EchoLink</option><option>Otro</option></select></label></div>'
			+'<datalist id="f-plan-l">'+(C.frec||[]).map(function(x){ return '<option value="'+esc(x.f)+'">'; }).join('')+repetidoras().map(function(r){ return '<option value="'+esc(r.f+' '+r.n)+'">'; }).join('')+'</datalist>'
			+'<label>Observaciones<input class="em-campo" name="obs" placeholder="Horario, tono, quién controla…"></label><button class="em-btn primario">Asignar</button></form>', function(c){
			$('#f-plan',c).addEventListener('submit', function(e){ e.preventDefault(); registrar('plan', {funcion:this.funcion.value, f:this.f.value.trim(), modo:this.modo.value, obs:this.obs.value.trim()}); cerrarModal(); });
		});
	});
	v.addEventListener('click', function(e){
		var x=e.target.closest('[data-planx]'); if(x && confirm('¿Quitar esta asignación?')) registrar('planx', {p:x.dataset.planx});
		var r=e.target.closest('[data-repe-e]'); if(r){ var k=r.closest('[data-repe]').dataset.repe, est=r.dataset.repeE; var n=est==='caida'||est==='bateria'?(prompt('Nota (opcional): causa, autonomía…')||''):''; registrar('repe', {r:k, e:est, n:n}); }
	});
	return v;
};

/* ── Bienestar: personas ─────────────────────────────────────────────────── */
var filtroPer='';
VIVO.personas = function(){
	var l=Object.keys(S.per).map(function(k){ return S.per[k]; }).sort(function(a,b){ return (b.act||b.ts)-(a.act||a.ts); });
	if(filtroPer){ var q=filtroPer.toUpperCase(); l=l.filter(function(p){ return (p.d.nombre+' '+(p.d.ubic||'')+' '+(p.d.contacto||'')).toUpperCase().indexOf(q)>=0; }); }
	if(!l.length) return '<p class="em-nota">'+(filtroPer?'Nadie con ese nombre. Si lo buscan, regístralo como «Lo buscan».':'Todavía no hay personas registradas.')+'</p>';
	return '<div class="em-lista">'+l.map(function(p){ var es=ESTADOS_PER[p.est]||{n:p.est,c:''};
		return '<div class="em-fila em-per em-borde-'+es.c+'" data-per="'+p.u+'"><span><strong>'+esc(p.d.nombre)+'</strong> '+pill(es.c, es.n)+'<small>'+esc(p.d.ubic||'Ubicación desconocida')+(p.d.contacto?' · avisar a '+esc(p.d.contacto):'')+' · registró '+esc(p.a)+' '+haceTxt(p.ts)+'</small>'+(p.d.msg?'<em>«'+esc(p.d.msg)+'»</em>':'')+(p.hist.length?'<small>'+p.hist.map(function(h){ return horaZ(h.ts)+' '+esc((ESTADOS_PER[h.estado]||{}).n||'')+(h.n?' — '+esc(h.n):''); }).join(' · ')+'</small>':'')+'</span>'
			+'<button type="button" class="em-btn" data-per-act>Actualizar</button></div>'; }).join('')+'</div>';
};
VISTA_FN.personas = function(){
	var v=el('<div><div class="em-barra"><h1>Bienestar</h1><button type="button" class="em-btn primario" id="pe-nueva">+ Registrar persona</button></div>'
		+'<p class="em-nota">«Estoy bien» y búsqueda de familiares. Solo lo ven quienes tienen la clave de red. No registres diagnósticos médicos ni números de documento.</p>'
		+'<label class="em-buscar"><input class="em-campo" type="search" id="pe-buscar" placeholder="Buscar por nombre o lugar…" value="'+esc(filtroPer)+'"></label>'
		+'<div data-vivo="personas">'+VIVO.personas()+'</div></div>');
	$('#pe-buscar',v).addEventListener('input', function(){ filtroPer=this.value; refrescar(); });
	$('#pe-nueva',v).addEventListener('click', function(){
		modal('Registrar persona', '<form class="em-form" id="f-per"><label>Nombre completo<input class="em-campo" name="nombre" required></label><fieldset><legend>Estado</legend>'+chips('estado', ESTADOS_PER, 'bien')+'</fieldset>'
			+'<label>Dónde está (o dónde se le vio por última vez)<input class="em-campo" name="ubic"></label><label>A quién avisar (nombre, ciudad, teléfono)<input class="em-campo" name="contacto"></label><label>Mensaje para la familia<input class="em-campo" name="msg" placeholder="Ej.: Estoy bien, en el albergue del coliseo"></label><button class="em-btn primario">Registrar</button></form>', function(c){
			$('#f-per',c).addEventListener('submit', function(e){ e.preventDefault(); registrar('persona', {nombre:this.nombre.value.trim(), estado:valorDe(this,'estado'), ubic:this.ubic.value.trim(), contacto:this.contacto.value.trim(), msg:this.msg.value.trim()}); cerrarModal(); });
		});
	});
	v.addEventListener('click', function(e){
		var b=e.target.closest('[data-per-act]'); if(!b) return; var u=b.closest('[data-per]').dataset.per, p=S.per[u];
		modal('Actualizar: '+p.d.nombre, '<form class="em-form" id="f-pa"><fieldset><legend>Estado</legend>'+chips('estado', ESTADOS_PER, p.est)+'</fieldset><label>Ubicación<input class="em-campo" name="ubic" value="'+esc(p.d.ubic||'')+'"></label><label>Nota<input class="em-campo" name="n" placeholder="Ej.: la familia ya fue avisada"></label><button class="em-btn primario">Guardar</button></form>', function(c){
			$('#f-pa',c).addEventListener('submit', function(ev){ ev.preventDefault(); registrar('personaact', {p:u, estado:valorDe(this,'estado'), ubic:this.ubic.value.trim(), n:this.n.value.trim()}); cerrarModal(); });
		});
	});
	return v;
};

/* ── Recursos ────────────────────────────────────────────────────────────── */
VIVO.recursos = function(){
	var l=Object.keys(S.rec).map(function(k){ return S.rec[k]; });
	if(!l.length) return '<p class="em-nota">Radios, baterías, generadores, antenas, vehículos y personas disponibles. En el sismo de 2026 se entregaron 15 radios con doble batería a la gobernación: saber quién tiene qué ahorra horas.</p>';
	return '<table class="em-tabla"><thead><tr><th>Recurso</th><th>Cant.</th><th>Dónde</th><th>Responsable</th><th>Estado</th><th></th></tr></thead><tbody>'+l.map(function(r){ return '<tr><td><strong>'+esc(r.d.tipo)+'</strong> '+esc(r.d.desc||'')+'</td><td>'+esc(r.d.cant||1)+'</td><td>'+esc(r.d.donde||'')+'</td><td>'+esc(r.d.resp||'')+'</td><td>'+esc(r.d.estado||'')+'</td><td><button type="button" class="em-x" data-recx="'+r.u+'" aria-label="Quitar">×</button></td></tr>'; }).join('')+'</tbody></table>';
};
VISTA_FN.recursos = function(){
	var v=el('<div><div class="em-barra"><h1>Recursos</h1><button type="button" class="em-btn primario" id="re-nuevo">+ Recurso</button></div><section class="em-tarjeta" data-vivo="recursos">'+VIVO.recursos()+'</section></div>');
	$('#re-nuevo',v).addEventListener('click', function(){
		modal('Agregar recurso', '<form class="em-form" id="f-rec"><div class="em-dos"><label>Tipo<select class="em-campo" name="tipo">'+RECURSOS.map(function(x){ return '<option>'+esc(x)+'</option>'; }).join('')+'</select></label><label>Cantidad<input class="em-campo" type="number" min="1" name="cant" value="1"></label></div>'
			+'<label>Descripción<input class="em-campo" name="desc" placeholder="Ej.: portátiles VHF con doble batería"></label><div class="em-dos"><label>Dónde está<input class="em-campo" name="donde"></label><label>Responsable<input class="em-campo" name="resp" value="'+esc(db.yo.ind)+'"></label></div>'
			+'<fieldset><legend>Estado</legend>'+chips('estado', {Disponible:'Disponible', Asignado:'Asignado', 'En camino':'En camino', 'Se necesita':'Se necesita'}, 'Disponible')+'</fieldset><button class="em-btn primario">Agregar</button></form>', function(c){
			$('#f-rec',c).addEventListener('submit', function(e){ e.preventDefault(); registrar('recurso', {tipo:this.tipo.value, cant:+this.cant.value||1, desc:this.desc.value.trim(), donde:this.donde.value.trim(), resp:this.resp.value.trim(), estado:valorDe(this,'estado')}); cerrarModal(); });
		});
	});
	v.addEventListener('click', function(e){ var x=e.target.closest('[data-recx]'); if(x && confirm('¿Quitar este recurso?')) registrar('recursox', {p:x.dataset.recx}); });
	return v;
};

/* ── Directorio ──────────────────────────────────────────────────────────── */
VIVO.dir = function(){
	var l=Object.keys(S.dir).map(function(k){ return S.dir[k]; });
	return l.length?'<div class="em-lista">'+l.map(function(x){ return '<div class="em-fila"><span><strong>'+esc(x.d.nombre)+'</strong> · '+esc(x.d.funcion||'')+'<small>'+esc(x.d.contacto||'')+(x.d.nota?' · '+esc(x.d.nota):'')+'</small></span><button type="button" class="em-x" data-dirx="'+x.u+'" aria-label="Quitar">×</button></div>'; }).join('')+'</div>':'<p class="em-nota">Agrega los contactos de este incidente: puesto de mando unificado, consejo de gestión del riesgo, Cruz Roja local, albergues…</p>';
};
VISTA_FN.directorio = function(){
	var v=el('<div><div class="em-barra"><h1>Directorio</h1><button type="button" class="em-btn primario" id="d-nuevo">+ Contacto</button></div>'
		+'<section class="em-tarjeta"><h2>Contactos del incidente</h2><div data-vivo="dir">'+VIVO.dir()+'</div></section>'
		+'<section class="em-tarjeta"><h2>Teléfonos nacionales</h2>'+telefonos()+'</section>'
		+'<section class="em-tarjeta"><h2>Radioaficionados</h2><p>Liga Colombiana de Radioaficionados: coordina la red nacional de emergencia.<br>IARU Región 2: coordinación de emergencias de las Américas.<br>En el sismo de 2026 se enlazaron por EchoLink las repetidoras de Medellín, Cali, Armenia, Manizales y Popayán.</p></section></div>');
	$('#d-nuevo',v).addEventListener('click', function(){
		modal('Agregar contacto', '<form class="em-form" id="f-dir"><label>Nombre o entidad<input class="em-campo" name="nombre" required></label><label>Función<input class="em-campo" name="funcion" placeholder="Ej.: PMU, coordinador de albergues"></label><label>Cómo contactarlo<input class="em-campo" name="contacto" placeholder="Teléfono, frecuencia, indicativo"></label><label>Nota<input class="em-campo" name="nota"></label><button class="em-btn primario">Agregar</button></form>', function(c){
			$('#f-dir',c).addEventListener('submit', function(e){ e.preventDefault(); registrar('dir', {nombre:this.nombre.value.trim(), funcion:this.funcion.value.trim(), contacto:this.contacto.value.trim(), nota:this.nota.value.trim()}); cerrarModal(); });
		});
	});
	v.addEventListener('click', function(e){ var x=e.target.closest('[data-dirx]'); if(x && confirm('¿Quitar este contacto?')) registrar('dirx', {p:x.dataset.dirx}); });
	return v;
};

/* ── Herramientas de campo ───────────────────────────────────────────────── */
VISTA_FN.herramientas = function(){
	var v=el('<div><h1>Herramientas de campo</h1><div class="em-grid2">'
		+'<section class="em-tarjeta"><h2>¿Cuánto dura mi batería?</h2><form class="em-form" id="h-bat"><div class="em-tres"><label>Capacidad (Ah)<input class="em-campo" type="number" step="0.1" name="ah" value="7"></label><label>Consumo al recibir (A)<input class="em-campo" type="number" step="0.01" name="rx" value="0.4"></label><label>Consumo al transmitir (A)<input class="em-campo" type="number" step="0.1" name="tx" value="8"></label></div><label>Tiempo transmitiendo: <span id="h-bat-p">10</span>%<input type="range" name="pct" min="0" max="50" value="10"></label></form><p class="em-resultado" id="h-bat-r"></p><p class="em-nota">Se usa el 80 % de la capacidad: descargar una batería de plomo por completo la daña.</p></section>'
		+'<section class="em-tarjeta"><h2>Antena para cubrir Colombia (NVIS)</h2><form class="em-form" id="h-nvis"><label>Frecuencia (MHz)<input class="em-campo" type="number" step="0.001" name="f" value="7.060"></label></form><p class="em-resultado" id="h-nvis-r"></p><p class="em-nota">Dipolo horizontal y bajo: la señal sube casi vertical y vuelve en un radio de 0 a 500 km, sin zona de silencio. Ideal para enlazar municipios cuando las repetidoras caen.</p></section>'
		+'<section class="em-tarjeta"><h2>Deletreo fonético</h2><form class="em-form" id="h-fon"><input class="em-campo em-mono" name="t" placeholder="Escribe un indicativo o una palabra" autocapitalize="characters"></form><p class="em-resultado" id="h-fon-r"></p></section>'
		+'<section class="em-tarjeta"><h2>Mi posición</h2><button type="button" class="em-btn" id="h-gps">📍 Leer GPS</button><p class="em-resultado" id="h-gps-r"></p><p class="em-nota">Funciona sin internet: el GPS no necesita datos.</p></section>'
		+'</div></div>');
	function bat(){ var f=$('#h-bat',v), p=+f.pct.value/100, a=(+f.rx.value)*(1-p)+(+f.tx.value)*p, h=a>0?(+f.ah.value)*0.8/a:0; $('#h-bat-p',v).textContent=f.pct.value; $('#h-bat-r',v).textContent=a>0?'Consumo medio '+a.toFixed(2)+' A → unas '+(h>=1?Math.floor(h)+' h '+Math.round((h%1)*60)+' min':Math.round(h*60)+' min'):''; }
	$('#h-bat',v).addEventListener('input', bat); bat();
	function nvis(){ var f=+$('#h-nvis',v).f.value; if(!(f>0)){ $('#h-nvis-r',v).textContent=''; return; } var mitad=142.5/f, alto=300/f; $('#h-nvis-r',v).innerHTML='Cada brazo: <strong>'+(mitad/2).toFixed(2)+' m</strong> (total '+mitad.toFixed(2)+' m)<br>Altura sobre el suelo: <strong>'+(alto*0.1).toFixed(1)+' a '+(alto*0.2).toFixed(1)+' m</strong> (0,1 a 0,2 longitudes de onda)'; }
	$('#h-nvis',v).addEventListener('input', nvis); nvis();
	$('#h-fon',v).addEventListener('input', function(){ $('#h-fon-r',v).textContent=fonetico(this.t.value); });
	$('#h-fon',v).addEventListener('submit', function(e){ e.preventDefault(); });
	$('#h-gps',v).addEventListener('click', function(){
		var r=$('#h-gps-r',v); if(!navigator.geolocation){ r.textContent='Este teléfono no tiene GPS disponible.'; return; } r.textContent='Buscando…';
		navigator.geolocation.getCurrentPosition(function(p){ var la=p.coords.latitude, lo=p.coords.longitude, cerca='';
			(C.repes||[]).forEach(function(x){ var d=distancia(la,lo,x.lat,x.lng); if(!cerca||d<cerca.d) cerca={d:d, x:x}; });
			r.innerHTML='Locator <strong class="em-mono">'+locator(la,lo)+'</strong><br>'+la.toFixed(5)+', '+lo.toFixed(5)+' (±'+Math.round(p.coords.accuracy)+' m)'+(cerca?'<br>Repetidora RAU más cercana: '+esc(cerca.x.n)+' '+esc(cerca.x.f)+' a '+Math.round(cerca.d)+' km':''); },
			function(){ r.textContent='No se pudo leer el GPS. Revisa el permiso de ubicación.'; }, {enableHighAccuracy:true, timeout:20000});
	});
	return v;
};

/* ── Más: exportar, importar, cerrar, salir ──────────────────────────────── */
function resumenTexto(){
	var i=inc(), e=Object.keys(S.est).map(function(k){ return S.est[k]; }), m=Object.keys(S.msgs).map(function(k){ return S.msgs[k]; }), p=Object.keys(S.per).map(function(k){ return S.per[k]; });
	return 'RESUMEN DE SITUACIÓN · '+i.nombre.toUpperCase()+(i.tipo==='simulacro'?' · SIMULACRO':'')+'\n'+new Date().toISOString().slice(0,16).replace('T',' ')+' UTC · elaboró '+db.yo.ind+'\n\n'
		+'ESTACIONES ('+e.length+'):\n'+e.map(function(x){ return '- '+x.ind+' · '+(ESTADOS_EST[x.estado]||{n:''}).n+' · '+(x.ubic||'')+' · '+(ENERGIA[x.energia]||'')+' · último contacto '+horaZ(x.ultimo)+(x.nota?' · '+x.nota:''); }).join('\n')
		+'\n\nMENSAJES: '+m.length+' en total, '+m.filter(function(x){ return x.est==='entregado'; }).length+' entregados, '+m.filter(function(x){ return x.est==='pendiente'||x.est==='recibido'; }).length+' por entregar.'
		+'\n\nPERSONAS ('+p.length+'): '+Object.keys(ESTADOS_PER).map(function(k){ var n=p.filter(function(x){ return x.est===k; }).length; return n?ESTADOS_PER[k].n.toLowerCase()+': '+n:''; }).filter(Boolean).join(' · ')
		+'\n\nREPETIDORAS: '+Object.keys(S.repe).map(function(k){ return k+' '+(ESTADOS_REPE[S.repe[k].e]||{n:''}).n.toLowerCase(); }).join(' · ')
		+'\n\nATENCIÓN:\n'+atencion().map(function(x){ return '- '+x.t; }).join('\n');
}
VISTA_FN.mas = function(){
	var i=inc();
	var v=el('<div><h1>Más</h1><div class="em-grid2">'
		+'<section class="em-tarjeta em-solo-movil"><h2>Secciones</h2><div class="em-acciones">'+VISTAS.slice(4,9).map(function(x){ return '<button type="button" class="em-btn em-grande" data-ir="'+x[0]+'">'+x[2]+' '+x[1]+'</button>'; }).join('')+'</div></section>'
		+'<section class="em-tarjeta"><h2>Compartir y respaldar</h2><div class="em-acciones">'
		+'<button type="button" class="em-btn" id="x-resumen">Resumen para la autoridad</button>'
		+'<button type="button" class="em-btn" id="x-sci">Bitácora SCI-214</button>'
		+'<button type="button" class="em-btn" id="x-json">Respaldo completo (archivo)</button>'
		+'<label class="em-btn em-archivo">Importar respaldo<input type="file" id="x-imp" accept=".json,application/json"></label></div>'
		+'<p class="em-nota">El respaldo sirve para pasar los registros a otro teléfono sin internet (por Bluetooth, cable o WhatsApp cuando vuelva la señal). Al importarlo se combinan sin duplicar.</p></section>'
		+'<section class="em-tarjeta"><h2>Incidente</h2><p>'+esc(i.nombre)+' · '+(i.local?'solo en este teléfono':'código <b class="em-mono">'+esc(i.codigo)+'</b> <small>'+fonetico(i.codigo)+'</small>')+'</p><div class="em-acciones">'
		+'<button type="button" class="em-btn" id="x-salir">Volver al inicio</button>'
		+(S.cerrado?'<button type="button" class="em-btn" id="x-reabrir">Reabrir incidente</button>':'<button type="button" class="em-btn peligro" id="x-cerrar">Cerrar incidente</button>')+'</div></section>'
		+'<section class="em-tarjeta"><h2>Este teléfono</h2><p>Operador: <b class="em-mono">'+esc(db.yo.ind)+'</b> · '+eventos().length+' registros de este incidente</p><div class="em-acciones"><button type="button" class="em-btn" id="x-sol">'+(document.documentElement.dataset.tema==='sol'?'Modo noche':'Modo sol (pantalla clara)')+'</button></div></section>'
		+'</div></div>');
	$('#x-resumen',v).addEventListener('click', function(){ copiar(resumenTexto()); });
	$('#x-sci',v).addEventListener('click', function(){ copiar(textoSci214()); });
	$('#x-json',v).addEventListener('click', function(){ descargar('emergencia-'+i.codigo+'-'+new Date().toISOString().slice(0,16).replace(/[:T]/g,'')+'.json', JSON.stringify({rau_em:1, incidente:{codigo:i.codigo, nombre:i.nombre, tipo:i.tipo, zona:i.zona}, eventos:eventos().map(function(e){ return {u:e.u,t:e.t,a:e.a,ts:e.ts,d:e.d}; })}), 'application/json'); });
	$('#x-imp',v).addEventListener('change', function(){
		var f=this.files[0]; if(!f) return; var lector=new FileReader();
		lector.onload=function(){ try {
			var j=JSON.parse(lector.result); if(!j.rau_em || !Array.isArray(j.eventos)) throw 0;
			var lista=eventos(), hay={}, n=0; lista.forEach(function(e){ hay[e.u]=1; });
			j.eventos.forEach(function(e){ if(e && e.u && e.t && !hay[e.u]){ lista.push({u:e.u, t:e.t, a:e.a, ts:e.ts, d:e.d||{}, s:0}); hay[e.u]=1; n++; } });
			guardar(); recalcular(); refrescar(); sincronizarPronto(); aviso(n+' registros nuevos importados de «'+(j.incidente&&j.incidente.nombre||'respaldo')+'».');
		} catch(e){ aviso('Ese archivo no es un respaldo del centro de mando.','aviso'); } };
		lector.readAsText(f);
	});
	$('#x-salir',v).addEventListener('click', salir);
	var cer=$('#x-cerrar',v); if(cer) cer.addEventListener('click', function(){ var n=prompt('¿Cerrar el incidente? Los registros quedan guardados. Nota de cierre (opcional):'); if(n===null) return; registrar('inc', {estado:'cerrado', nota:n}); ir('mas'); });
	var re=$('#x-reabrir',v); if(re) re.addEventListener('click', function(){ registrar('inc', {estado:'abierto', nota:'Incidente reabierto por '+db.yo.ind}); ir('mas'); });
	$('#x-sol',v).addEventListener('click', function(){ tema(document.documentElement.dataset.tema==='sol'?'':'sol'); ir('mas'); });
	return v;
};
function tema(t){ document.documentElement.dataset.tema=t; try { localStorage.setItem('rau_em_tema', t); } catch(e){} }

/* ── Arranque ────────────────────────────────────────────────────────────── */
try { tema(localStorage.getItem('rau_em_tema')||''); } catch(e){}
armar();
if(inc()){ recalcular(); try { vista=sessionStorage.getItem('rau_em_vista')||'tablero'; } catch(e){} }
pintarCabecera(); pintarVista(); pintarSync();
cargarAlertas(); setInterval(cargarAlertas, 5*60000);
sincronizar();
// Las edades («hace 12 min») y las estaciones que se quedan calladas cambian solas con el tiempo
setInterval(function(){ if(document.visibilityState==='visible' && !$('#em-main').querySelector('input:focus,textarea:focus,select:focus')) refrescar(); }, 30000);
})();
