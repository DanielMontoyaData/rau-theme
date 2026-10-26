/* Centro de Mando de Emergencias — aplicación (estructura, sincronización, inicio, campo y administración).
 *
 * Archivos: em-core.js (lógica), em-db.js (IndexedDB), em.js (esto), em-vistas.js (vistas operativas),
 * em-mapa.js (mapa), em-sim.js (simulador). Todos comparten window.RauEm.
 *
 * Reglas de la interfaz:
 *  - Nada se da por transmitido mientras esté solo en el dispositivo: cada registro muestra si está
 *    pendiente, guardado en el servidor o rechazado (con el motivo).
 *  - Los permisos se comprueban en el servidor; aquí solo se ocultan los botones que el rol no puede usar.
 *  - Lo simulado lleva siempre la franja de SIMULACRO y vive en operaciones aparte.
 */
(function(){
'use strict';
var C = window.RAU_EM || {}, core = window.RauEmCore, DB = window.RauEmDB;
core.usarEsquema(C.esquema);
var E = window.RauEm = { C:C, core:core, db:DB, VISTAS:{}, VIVO:{}, op:null, eventos:[], S:core.reducir([]), yo:{ind:'', nombre:''}, cuenta:null, actor:null, ventana:60, filtros:{}, vista:'tablero', params:{} };

/* ── Utilidades de interfaz ──────────────────────────────────────────────── */
var $ = E.$ = function(s, el){ return (el||document).querySelector(s); };
var $$ = E.$$ = function(s, el){ return Array.prototype.slice.call((el||document).querySelectorAll(s)); };
var esc = E.esc = function(s){ return String(s==null?'':s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };
E.el = function(html){ var d=document.createElement('div'); d.innerHTML=html.trim(); return d.firstElementChild; };
E.dos = function(n){ return (n<10?'0':'')+n; };
E.horaLocal = function(ts){ var d=new Date(ts); return E.dos(d.getHours())+':'+E.dos(d.getMinutes()); };
E.hz = function(ts){ return ts ? core.horaZ(ts) : '—'; };
E.edadMin = function(ts){ return Math.max(0, Math.floor((Date.now()-ts)/60000)); };
E.haceTxt = function(ts){ if(!ts) return 'nunca'; var m=E.edadMin(ts); return m<1?'ahora':m<60?'hace '+m+' min':m<1440?'hace '+Math.floor(m/60)+' h '+(m%60)+' min':'hace '+Math.floor(m/1440)+' d'; };
E.pill = function(c, t, titulo){ return '<span class="em-pill '+c+'"'+(titulo?' title="'+esc(titulo)+'"':'')+'>'+esc(t)+'</span>'; };
E.calidad = function(q){ return E.pill({confirmado:'ok', reportado:'aviso', estimado:'aviso', vencido:'apagado', desconocido:'apagado'}[q]||'apagado', core.et('calidad', q)); };
E.puede = function(p){ return !!(E.actor && E.actor.permisos && E.actor.permisos.indexOf(p)>=0); };
E.esSim = function(){ return !!(E.op && E.op.tipo==='simulacro'); };
E.chips = function(nombre, opciones, valor, clases){
	return '<div class="em-chips" role="radiogroup">'+Object.keys(opciones).map(function(k){
		return '<label class="em-chip '+((clases&&clases[k])||'')+'"><input type="radio" name="'+nombre+'" value="'+esc(k)+'"'+(k===valor?' checked':'')+'><span>'+esc(opciones[k])+'</span></label>';
	}).join('')+'</div>';
};
E.opciones = function(obj, valor, vacio){ return (vacio!==undefined?'<option value="">'+esc(vacio)+'</option>':'')+Object.keys(obj).map(function(k){ return '<option value="'+esc(k)+'"'+(k===valor?' selected':'')+'>'+esc(obj[k])+'</option>'; }).join(''); };
E.valorDe = function(form, nombre){ var x=form.querySelector('[name="'+nombre+'"]:checked'); return x?x.value:''; };
E.incidentesOpciones = function(sel){ return '<option value="">— Ninguno —</option>'+Object.keys(E.S.inc).map(function(k){ var x=E.S.inc[k]; return x.fusionadoEn?'':'<option value="'+x.id+'"'+(x.id===sel?' selected':'')+'>'+esc(x.titulo)+' · '+esc(core.et('prio',x.prio))+'</option>'; }).join(''); };

E.aviso = function(t, tipo){
	var el=$('#em-toast'); if(!el) return;
	el.textContent=t; el.className='em-toast is-on '+(tipo||''); clearTimeout(E.aviso.t);
	E.aviso.t=setTimeout(function(){ el.className='em-toast'; }, tipo==='peligro'?9000:4000);
};
var focoPrevio=null;
E.modal = function(titulo, html, alAbrir, ancho){
	var m=$('#em-modal'); if(m.hidden) focoPrevio=document.activeElement;
	$('#em-modal-t').textContent=titulo; $('#em-modal-c').innerHTML=html; m.hidden=false; $('.em-modal-caja',m).classList.toggle('ancho', !!ancho);
	document.body.classList.add('em-con-modal');
	if(alAbrir) alAbrir($('#em-modal-c'));
	var f=$('input:not([type=hidden]):not([readonly]),textarea,select', $('#em-modal-c')) || $('#em-modal-x'); if(f) f.focus();
};
E.cerrarModal = function(){ var m=$('#em-modal'); if(m.hidden) return; m.hidden=true; document.body.classList.remove('em-con-modal'); if(focoPrevio && focoPrevio.focus && document.body.contains(focoPrevio)) try { focoPrevio.focus(); } catch(e){} };
/* Confirmación para lo destructivo o lo que no se puede deshacer */
E.confirmar = function(titulo, texto, boton, peligro){
	return new Promise(function(ok){
		E.modal(titulo, '<p>'+esc(texto)+'</p><div class="em-acciones"><button type="button" class="em-btn '+(peligro?'peligro':'primario')+'" id="cf-si">'+esc(boton||'Confirmar')+'</button><button type="button" class="em-btn" id="cf-no">Cancelar</button></div>', function(c){
			$('#cf-si',c).addEventListener('click', function(){ E.cerrarModal(); ok(true); });
			$('#cf-no',c).addEventListener('click', function(){ E.cerrarModal(); ok(false); });
		});
	});
};
E.copiar = function(t){
	(navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function(){ E.aviso('Copiado.'); }, function(){
		E.modal('Copia este texto', '<textarea class="em-campo em-copia" rows="14" readonly>'+esc(t)+'</textarea>', function(c){ $('textarea',c).select(); });
	});
};
E.descargar = function(nombre, texto, tipo){
	var a=document.createElement('a'); a.href=URL.createObjectURL(new Blob([texto], {type:tipo||'text/plain;charset=utf-8'})); a.download=nombre;
	document.body.appendChild(a); a.click(); a.remove(); setTimeout(function(){ URL.revokeObjectURL(a.href); }, 4000);
};
/* Imprime un texto en una página aparte (para «Guardar como PDF» del navegador) */
E.imprimir = function(titulo, texto){
	var w=window.open('', '_blank'); if(!w){ E.aviso('El navegador bloqueó la ventana de impresión.', 'aviso'); return; }
	w.document.write('<!doctype html><meta charset="utf-8"><title>'+esc(titulo)+'</title><style>body{font:13px/1.5 ui-monospace,Menlo,Consolas,monospace;margin:2cm;white-space:pre-wrap}h1{font:700 16px system-ui}</style><h1>'+esc(titulo)+'</h1>'+esc(texto));
	w.document.close(); w.focus(); setTimeout(function(){ w.print(); }, 300);
};
E.nombreArchivo = function(base, ext){ return base+'-'+(E.op?E.op.codigo:'')+'-'+new Date().toISOString().slice(0,16).replace(/[:T]/g,'')+'.'+ext; };

/* ── Servidor ────────────────────────────────────────────────────────────── */
E.rest = function(ruta, opts){
	opts=opts||{};
	var h={'Content-Type':'application/json'};
	if(opts.token) h['X-RAU-EM-Token']=opts.token;
	else if(E.op && E.op.token && opts.token!==false) h['X-RAU-EM-Token']=E.op.token;
	if(C.nonce) h['X-WP-Nonce']=C.nonce;
	return fetch(C.rest+ruta, {method:opts.metodo||'GET', headers:h, credentials:'same-origin', cache:'no-store', body:opts.cuerpo?JSON.stringify(opts.cuerpo):undefined})
		.then(function(r){ return r.json().catch(function(){ return null; }).then(function(j){ return {estado:r.status, j:j}; }); });
};

/* ── Registrar: validar, guardar en el dispositivo, encolar para el servidor ── */
E.registrar = function(tipo, d, opts){
	opts=opts||{};
	if(!E.op){ E.aviso('Primero entra a una operación.', 'aviso'); return null; }
	d=core.limpiar(d);
	if(d.base===undefined){ var b=core.baseDe(E.S, tipo, d); if(b) d.base=b; }
	var err=core.validar({t:tipo, d:d}, {permisos:E.actor?E.actor.permisos:[], sim:E.esSim()});
	if(err){ E.aviso('No se registró: '+err, 'peligro'); return null; }
	var e={u:core.uuid(), t:tipo, a:E.yo.ind, ts:Date.now(), d:d, s:0};
	E.eventos.push(e);
	DB.guardarEventos(E.op.codigo, [e]).catch(function(){ E.aviso('No se pudo guardar en el dispositivo. Exporta un respaldo.', 'peligro'); });
	E.recalcular(); E.refrescar(); E.sincronizarPronto();
	return e;
};
E.recalcular = function(){ E.S = core.reducir(E.eventos); };
// En una operación local no hay servidor: nada está «pendiente de enviar»
E.pendientes = function(){ if(E.op && E.op.local) return []; return E.eventos.filter(function(e){ return e.s===0; }); };
E.rechazados = function(){ return E.eventos.filter(function(e){ return e.s==='r' && !e.descartado; }); };

/* ── Sincronización con reintentos ───────────────────────────────────────── */
var sync = E.sync = {enCurso:false, ultimo:0, error:'', reintento:0, espera:5000, t:null, tr:null};
E.sincronizarPronto = function(){ clearTimeout(sync.t); sync.t=setTimeout(E.sincronizar, 700); };
E.sincronizar = function(){
	var op=E.op; if(!op || op.local || sync.enCurso) return Promise.resolve();
	if(!op.token && !(E.actor && E.actor.cuenta)) return Promise.resolve();
	if(navigator.onLine===false){ sync.error='Sin internet'; pintarSync(); return Promise.resolve(); }
	sync.enCurso=true; pintarSync();
	var cod=op.codigo, porU={}; E.eventos.forEach(function(e){ porU[e.u]=e; });
	function enviar(){
		var pend=E.pendientes().slice(0, 50); if(!pend.length) return Promise.resolve();
		return E.rest('operaciones/'+cod+'/eventos', {metodo:'POST', cuerpo:{eventos:pend.map(function(e){ return {u:e.u, t:e.t, ts:e.ts, d:e.d}; })}}).then(function(r){
			if(r.estado===401) throw {auth:true, msg:(r.j&&r.j.error)||'Sin acceso'};
			if(r.estado===403) throw {msg:(r.j&&r.j.error)||'Tu rol no permite registrar'};
			if(r.estado>=400) throw {msg:(r.j&&r.j.error)||('Error '+r.estado)};
			var cambiados=[];
			r.j.resultados.forEach(function(x){ var e=porU[x.u]; if(!e) return; if(x.ok){ e.s=x.s; delete e.err; } else { e.s='r'; e.err=x.error; } cambiados.push(e); });
			return DB.guardarEventos(cod, cambiados).then(function(){ if(E.pendientes().length && cambiados.length) return enviar(); });
		});
	}
	function traer(){
		return E.rest('operaciones/'+cod+'/eventos?desde='+(op.cursor||0)).then(function(r){
			if(r.estado===401) throw {auth:true, msg:(r.j&&r.j.error)||'Sin acceso'};
			if(r.estado>=400) throw {msg:(r.j&&r.j.error)||('Error '+r.estado)};
			var nuevos=[];
			r.j.eventos.forEach(function(x){
				var e=porU[x.u];
				if(e){ if(e.s!==x.s || e.a!==x.a){ e.s=x.s; e.a=x.a; e.srv=x.srv; nuevos.push(e); } }
				else { e={u:x.u, t:x.t, a:x.a, ts:x.ts, d:x.d||{}, s:x.s, srv:x.srv, rol:x.rol}; E.eventos.push(e); porU[e.u]=e; nuevos.push(e); }
				op.cursor=Math.max(op.cursor||0, x.s);
			});
			['nombre','tipo','zona','estado'].forEach(function(k){ if(r.j.operacion[k]!==undefined) op[k]=r.j.operacion[k]; });
			op.org=r.j.operacion.org; op.actor=E.actor=r.j.actor;
			return DB.guardarEventos(cod, nuevos).then(function(){ return DB.guardarOp(op); }).then(function(){ if(r.j.mas) return traer(); });
		});
	}
	return enviar().then(traer).then(function(){
		sync.enCurso=false; sync.ultimo=Date.now(); sync.error=''; sync.reintento=0; sync.espera=5000;
		E.recalcular(); E.refrescar();
	}).catch(function(err){
		sync.enCurso=false;
		if(err && err.auth){ op.token=''; DB.guardarOp(op); sync.error='Sin acceso: vuelve a entrar'; E.aviso('La sesión ya no es válida (revocada o vencida). Vuelve a entrar con el código y una clave. Tus registros pendientes siguen guardados.', 'peligro'); }
		else {
			sync.error=(err && err.msg) || 'Sin conexión con el servidor';
			// Reintento con espera creciente: 5 s, 10 s, 20 s… hasta 2 min
			sync.reintento=Date.now()+sync.espera; clearTimeout(sync.tr); sync.tr=setTimeout(E.sincronizar, sync.espera); sync.espera=Math.min(sync.espera*2, 120000);
		}
		pintarSync();
	});
};
setInterval(function(){ if(document.visibilityState==='visible' && E.op && !E.op.local && !sync.reintento) E.sincronizar(); }, 15000);
window.addEventListener('online', function(){ sync.espera=5000; sync.reintento=0; pintarSync(); E.sincronizar(); });
window.addEventListener('offline', function(){ pintarSync(); });

function pintarSync(){
	var b=$('#em-sync'); if(!b) return; var op=E.op, p=op?E.pendientes().length:0, r=op?E.rechazados().length:0;
	if(!op){ b.hidden=true; return; } b.hidden=false;
	var cls, t;
	if(op.local){ cls='apagado'; t='Solo en este dispositivo'; }
	else if(!op.token && !(E.actor && E.actor.cuenta)){ cls='peligro'; t='Sin acceso: toca para entrar'+(p?' · '+p+' sin enviar':''); }
	else if(navigator.onLine===false){ cls='aviso'; t='Sin internet'+(p?' · '+p+' sin enviar':''); }
	else if(sync.enCurso){ cls='ok'; t='Sincronizando…'+(p?' '+p+' por enviar':''); }
	else if(sync.error){ cls='aviso'; t=sync.error+(p?' · '+p+' sin enviar':'')+(sync.reintento>Date.now()?' · reintento en '+Math.ceil((sync.reintento-Date.now())/1000)+' s':''); }
	else { cls=p?'aviso':'ok'; t=p ? p+' sin enviar' : (sync.ultimo ? 'Al día · '+E.horaLocal(sync.ultimo) : 'En línea'); }
	if(r){ cls='peligro'; t+=' · '+r+' rechazados'; }
	b.className='em-sync '+cls; b.textContent=t;
	b.setAttribute('aria-label', 'Estado de sincronización: '+t);
}
E.pintarSync = pintarSync;
setInterval(function(){ if(sync.reintento>Date.now()) pintarSync(); else if(sync.reintento){ sync.reintento=0; } }, 1000);

/* ── Estructura de la página ─────────────────────────────────────────────── */
var GRUPOS = [['Operación',['tablero','mapa','incidentes','comunicaciones','estaciones','plan','recursos']],['Registro e informes',['bitacora','informes','personas','directorio']],['Campo y administración',['campo','herramientas','simulador','admin']]];
var MOVIL = ['tablero','incidentes','comunicaciones','estaciones','mas'];
E.registrarVista = function(id, def){ E.VISTAS[id]=def; };
function armar(){
	$('#em-app').innerHTML =
		'<a class="em-saltar" href="#em-main">Saltar al contenido</a>'
		+'<header class="em-cab"><a class="em-marca" href="'+esc(C.sitio)+'" title="Volver a hk4rau.com" aria-label="HK4RAU: volver al sitio">RAU</a>'
		+'<div class="em-titulo" id="em-titulo"></div>'
		+'<form class="em-buscar-cab" id="em-buscar-f" role="search"><label class="sr-only" for="em-buscar">Buscar en la operación</label><input id="em-buscar" type="search" placeholder="Buscar ( / )" autocomplete="off"></form>'
		+'<div class="em-reloj" aria-label="Hora"><strong id="em-utc">--:--Z</strong><span id="em-hora-local">--:-- local</span></div>'
		+'<button type="button" class="em-sync" id="em-sync" aria-live="polite"></button></header>'
		+'<div class="em-franja" id="em-franja" hidden></div>'
		+'<div class="em-cuerpo"><nav class="em-nav" id="em-nav" aria-label="Secciones del centro de mando"></nav><main class="em-main" id="em-main" tabindex="-1"></main></div>'
		+'<div class="em-toast" id="em-toast" role="status" aria-live="polite"></div>'
		+'<div class="em-modal" id="em-modal" hidden><div class="em-modal-caja" role="dialog" aria-modal="true" aria-labelledby="em-modal-t"><div class="em-modal-cab"><h2 id="em-modal-t"></h2><button type="button" class="em-x" id="em-modal-x" aria-label="Cerrar">×</button></div><div id="em-modal-c"></div></div></div>';
	$('#em-modal-x').addEventListener('click', E.cerrarModal);
	$('#em-modal').addEventListener('click', function(e){ if(e.target.id==='em-modal') E.cerrarModal(); });
	$('#em-modal').addEventListener('keydown', function(e){
		if(e.key==='Escape'){ E.cerrarModal(); return; }
		if(e.key!=='Tab') return; // el foco no sale del diálogo
		var f=$$('button,input,select,textarea,a[href]', $('#em-modal')).filter(function(x){ return !x.disabled && x.offsetParent!==null; }); if(!f.length) return;
		if(e.shiftKey && document.activeElement===f[0]){ e.preventDefault(); f[f.length-1].focus(); } else if(!e.shiftKey && document.activeElement===f[f.length-1]){ e.preventDefault(); f[0].focus(); }
	});
	$('#em-sync').addEventListener('click', function(){ if(!E.op || E.op.local) return; if(!E.op.token && !(E.actor&&E.actor.cuenta)) pedirEntrada(E.op.codigo); else { sync.espera=5000; E.sincronizar(); } });
	$('#em-nav').addEventListener('click', function(e){ var b=e.target.closest('[data-v]'); if(b) E.ir(b.dataset.v); });
	document.addEventListener('click', function(e){ var b=e.target.closest('[data-ir]'); if(b && !b.closest('#em-nav')){ e.preventDefault(); var p={}; if(b.dataset.id) p.id=b.dataset.id; if(b.dataset.filtro) p.filtro=b.dataset.filtro; E.cerrarModal(); E.ir(b.dataset.ir, p); } });
	$('#em-buscar-f').addEventListener('submit', function(e){ e.preventDefault(); buscar($('#em-buscar').value); });
	document.addEventListener('keydown', atajos);
	setInterval(reloj, 1000); reloj();
}
function reloj(){ var n=Date.now(); $('#em-utc').textContent=core.horaZ(n).replace(/(\d\d)(\d\d)Z/,'$1:$2Z'); $('#em-hora-local').textContent=E.horaLocal(n)+' local'; }
function pintarCabecera(){
	var op=E.op, t=$('#em-titulo'), fr=$('#em-franja');
	$('#em-buscar-f').hidden=!op;
	if(!op){ t.innerHTML='<strong>Centro de Mando de Emergencias</strong><span>HK4RAU · radioaficionados de Colombia</span>'; fr.hidden=true; }
	else {
		t.innerHTML='<strong>'+esc(op.nombre)+'</strong><span>'+(op.local?'Solo en este dispositivo':'Código <b class="em-mono">'+esc(op.codigo)+'</b>')+(op.zona?' · '+esc(op.zona):'')+(E.actor?' · '+esc((C.roles&&C.roles[E.actor.rol])||E.actor.rol):'')+'</span>';
		var sim=op.tipo==='simulacro', cer=E.S.cerrada||op.estado==='cerrado';
		fr.hidden=!sim && !cer; fr.className='em-franja'+(sim?' simulacro':'')+(cer?' cerrado':'');
		fr.innerHTML='<span>'+(sim?'SIMULACRO · EJERCICIO · NO ES UNA EMERGENCIA REAL':'')+(sim&&cer?' · ':'')+(cer?'OPERACIÓN CERRADA':'')+'</span>';
	}
	var nav=$('#em-nav'); nav.hidden=!op;
	if(op) nav.innerHTML = GRUPOS.map(function(g){ return '<p class="em-nav-g">'+esc(g[0])+'</p>'+g[1].map(botonNav).join(''); }).join('')+botonNav('mas');
	pintarCuentas();
}
function botonNav(id){
	var v=E.VISTAS[id]; if(!v) return '';
	return '<button type="button" data-v="'+id+'" class="em-nav-b'+(E.vista===id?' is-on':'')+(MOVIL.indexOf(id)<0?' em-solo-ancho':'')+(id==='mas'?' em-solo-movil':'')+'"'+(E.vista===id?' aria-current="page"':'')+'><span aria-hidden="true">'+v.icono+'</span>'+esc(v.nombre)+'<i class="em-cuenta" data-cuenta="'+id+'" hidden></i></button>';
}
function pintarCuentas(){
	if(!E.op) return;
	var I=core.indicadores(E.S, {}, Date.now(), E.ventana), c={tablero:I.vencidos.length+I.conflictos+I.duplicados, incidentes:I.incidentes.porPrio.critica, comunicaciones:I.mensajes.emergencia, campo:E.pendientes().length+E.rechazados().length};
	$$('[data-cuenta]').forEach(function(el){ var n=c[el.dataset.cuenta]||0; el.textContent=n||''; el.hidden=!n; });
}
E.ir = function(v, params){
	if(!E.VISTAS[v]) v='tablero';
	E.vista=v; E.params=params||{};
	try { sessionStorage.setItem('rau_em_vista', v); } catch(e){}
	pintarCabecera(); pintarVista(); window.scrollTo(0,0);
	var m=$('#em-main'); if(m && E.op) try { m.focus({preventScroll:true}); } catch(e){}
};
function pintarVista(){
	var m=$('#em-main'); m.innerHTML='';
	if(!E.yo.ind){ m.appendChild(vistaIdentidad()); return; }
	if(!E.op){ m.appendChild(vistaInicio()); return; }
	var v=E.VISTAS[E.vista]||E.VISTAS.tablero;
	m.appendChild(v.fn(E.params||{}));
	if(v.alMontar) v.alMontar(E.params||{});
}
/* Solo cambia lo que depende de los datos; los formularios abiertos no se tocan */
E.refrescar = function(){
	pintarCabecera(); pintarSync();
	$$('[data-vivo]').forEach(function(el){ var f=E.VIVO[el.dataset.vivo]; if(f) el.innerHTML=f(el); });
	E.alRefrescar.forEach(function(f){ try { f(); } catch(e){} });
};
E.alRefrescar=[];

/* Atajos de teclado: / buscar · n incidente · m mensaje · e estación · Alt+1…9 secciones · ? ayuda */
function atajos(e){
	if(!$('#em-modal').hidden || /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) || e.ctrlKey || e.metaKey) return;
	if(!E.op) return;
	var orden=['tablero','mapa','incidentes','comunicaciones','estaciones','plan','recursos','bitacora','informes'];
	if(e.altKey && /^[1-9]$/.test(e.key)){ e.preventDefault(); E.ir(orden[+e.key-1]); return; }
	if(e.altKey) return;
	if(e.key==='/'){ e.preventDefault(); var b=$('#em-buscar'); if(b && b.offsetParent) b.focus(); else buscar(''); }
	else if(e.key==='n' && E.formIncidente){ e.preventDefault(); E.formIncidente(); }
	else if(e.key==='m' && E.formMensaje){ e.preventDefault(); E.formMensaje(); }
	else if(e.key==='e' && E.formEstacion){ e.preventDefault(); E.formEstacion(); }
	else if(e.key==='?'){ e.preventDefault(); E.modal('Atajos de teclado', '<table class="em-tabla"><tbody><tr><td class="em-mono">/</td><td>Buscar</td></tr><tr><td class="em-mono">n</td><td>Nuevo incidente</td></tr><tr><td class="em-mono">m</td><td>Nuevo mensaje</td></tr><tr><td class="em-mono">e</td><td>Registrar estación</td></tr><tr><td class="em-mono">Alt + 1…9</td><td>Tablero, mapa, incidentes, comunicaciones, estaciones, plan, recursos, bitácora, informes</td></tr><tr><td class="em-mono">Esc</td><td>Cerrar ventana</td></tr></tbody></table>'); }
}

/* Búsqueda rápida en la operación */
function buscar(q){
	q=core.normalizar(q); var S=E.S, r=[];
	function ve(t){ return !q || core.normalizar(t).indexOf(q)>=0; }
	Object.keys(S.inc).forEach(function(k){ var x=S.inc[k]; if(ve(x.titulo+' '+x.lugar+' '+x.desc+' '+x.fuente)) r.push(['incidentes', x.id, '⚠ '+x.titulo, core.et('prio',x.prio)+' · '+core.et('verif',x.verif)+' · '+(x.lugar||'')]); });
	Object.keys(S.est).forEach(function(k){ var x=S.est[k]; if(ve(x.ind+' '+(x.nombre||'')+' '+(x.lugar||'')+' '+(x.org||''))) r.push(['estaciones', x.ind, '📡 '+x.ind, (x.nombre||'')+' '+(x.lugar||'')]); });
	Object.keys(S.msgs).forEach(function(k){ var x=S.msgs[k]; if(ve(x.d.para+' '+x.d.texto+' '+(x.d.origen||'')+' '+(x.d.num||''))) r.push(['comunicaciones', x.id, '✉ N.º '+(x.d.num||'')+' para '+x.d.para, core.et('msg_est',x.estado)]); });
	Object.keys(S.rec).forEach(function(k){ var x=S.rec[k]; if(ve(x.desc+' '+x.cod+' '+x.resp+' '+(x.ubic?x.ubic.lugar:''))) r.push(['recursos', x.id, '⚙ '+x.desc, core.et('rec_est',x.estado)]); });
	Object.keys(S.personas).forEach(function(k){ var x=S.personas[k]; if(q && ve(x.d.nombre+' '+(x.d.ubic||''))) r.push(['personas', x.id, '♥ '+x.d.nombre, core.et('persona_e',x.estado)]); });
	E.modal('Buscar', '<form id="bq-f" role="search"><label class="sr-only" for="bq">Buscar</label><input class="em-campo" id="bq" type="search" value="'+esc(q)+'" placeholder="Incidente, indicativo, lugar, mensaje…"></form><p class="em-nota" role="status">'+r.length+' resultados</p><div class="em-lista">'+r.slice(0,60).map(function(x){ return '<button type="button" class="em-fila em-fila-btn" data-ir="'+x[0]+'" data-id="'+esc(x[1])+'"><span><strong>'+esc(x[2])+'</strong><small>'+esc(x[3])+'</small></span><span aria-hidden="true">→</span></button>'; }).join('')+'</div>', function(c){
		$('#bq-f',c).addEventListener('submit', function(ev){ ev.preventDefault(); buscar($('#bq',c).value); });
	});
}

/* ── Identidad, operaciones, entrada ─────────────────────────────────────── */
function vistaIdentidad(){
	var v=E.el('<section class="em-bienvenida"><h1>Centro de Mando de Emergencias</h1>'
		+'<p>Coordinación de comunicaciones de emergencia para radioaficionados y organizaciones de respuesta: incidentes, mensajes con trazabilidad, estaciones, recursos, plan de comunicaciones e informes. <strong>Funciona sin internet</strong>: lo que registras queda en este dispositivo y se comparte cuando hay señal.</p>'
		+'<form class="em-tarjeta em-form" id="em-yo" novalidate><h2>¿Quién opera este dispositivo?</h2>'
		+'<label>Tu indicativo<input class="em-campo em-mono em-grande" name="ind" required autocomplete="off" autocapitalize="characters" placeholder="HK4ABC" value="'+esc(E.yo.ind)+'" aria-describedby="yo-err"></label><p class="em-error" id="yo-err" role="alert"></p>'
		+'<label>Tu nombre (opcional)<input class="em-campo" name="nombre" autocomplete="name" value="'+esc(E.yo.nombre)+'"></label>'
		+'<button class="em-btn primario em-grande">Continuar</button></form>'
		+'<p class="em-nota">Esta plataforma apoya la coordinación entre operadores. No reemplaza los sistemas oficiales de atención ni la línea 123.</p></section>');
	$('#em-yo', v).addEventListener('submit', function(e){
		e.preventDefault(); var x=core.indicativo(this.ind.value);
		if(x.length<3){ $('#yo-err',v).textContent='Escribe tu indicativo (al menos 3 caracteres).'; this.ind.focus(); return; }
		E.yo={ind:x, nombre:this.nombre.value.trim()}; DB.kvSet('yo', E.yo); pintarCabecera(); pintarVista();
	});
	return v;
}
function vistaInicio(){
	var c=E.cuenta;
	var v=E.el('<div class="em-grid-inicio">'
		+'<section class="em-tarjeta em-hero"><p class="em-sup">Guardia · '+esc(E.yo.ind)+' <button type="button" class="em-link" id="in-yo">cambiar</button></p>'
		+'<h1>Centro de mando</h1><p>Activa una operación y dicta por radio el código y la <strong>clave de operadores</strong>. La clave de coordinación es solo para quien coordina.</p>'
		+'<div class="em-acciones"><button type="button" class="em-btn peligro em-grande" id="in-activar">Activar operación real</button>'
		+'<button type="button" class="em-btn em-grande" id="in-unirme">Unirme con código y clave</button>'
		+'<button type="button" class="em-btn em-grande" id="in-sim">Simulacro y entrenamiento</button>'
		+'<button type="button" class="em-btn" id="in-local">Trabajar sin conexión</button></div>'
		+'<p class="em-nota">'+(c?'Cuenta: <b>'+esc(c.login)+'</b> · '+esc((C.roles||{})[c.rol]||c.rol)+(c.org?' · '+esc(c.org.nombre):' · sin organización'):'¿Tienes cuenta de coordinador u operador? <a href="'+esc(C.login)+'">Entra con tu cuenta</a> para ver las operaciones de tu organización.')+'</p></section>'
		+'<section class="em-tarjeta"><h2>Operaciones abiertas</h2><div data-vivo="abiertas">'+E.VIVO.abiertas()+'</div></section>'
		+'<section class="em-tarjeta"><h2>En este dispositivo</h2><div data-vivo="mias">'+E.VIVO.mias()+'</div></section>'
		+'<section class="em-tarjeta"><h2>Ahora mismo</h2><div data-vivo="alertas">'+(E.VIVO.alertas?E.VIVO.alertas():'')+'</div></section>'
		+'<section class="em-tarjeta"><h2>Frecuencias</h2><div data-vivo="frecuencias">'+(E.VIVO.frecuencias?E.VIVO.frecuencias():'')+'</div></section>'
		+'<section class="em-tarjeta"><h2>Preparación</h2><p class="em-nota">En el sismo de agosto de 2026 fallaron generadores y transferencias automáticas en repetidoras. Revisa esto antes, no durante.</p><div data-vivo="prep">'+E.VIVO.prep()+'</div><p class="em-nota"><a href="'+esc(C.turnos)+'">Turnos de guardia</a> · <a href="'+esc(C.checkin)+'">Check-in de la malla</a></p></section>'
		+'<section class="em-tarjeta"><h2>Teléfonos</h2>'+E.telefonos()+'</section></div>');
	$('#in-yo',v).addEventListener('click', function(){ E.yo.ind=''; pintarVista(); });
	$('#in-activar',v).addEventListener('click', function(){ formCrear('real'); });
	$('#in-sim',v).addEventListener('click', function(){ if(E.abrirSimulador) E.abrirSimulador(); else formCrear('simulacro'); });
	$('#in-unirme',v).addEventListener('click', function(){ pedirEntrada(''); });
	$('#in-local',v).addEventListener('click', function(){ formLocal(false); });
	v.addEventListener('click', function(e){
		var r=e.target.closest('[data-abrir]'); if(r) E.entrar(r.dataset.abrir);
		var u=e.target.closest('[data-unir]'); if(u) pedirEntrada(u.dataset.unir, u.dataset.cuenta==='1');
		var b=e.target.closest('[data-olvidar]'); if(b) E.confirmar('Olvidar en este dispositivo', 'Se borran de este dispositivo los registros de «'+b.dataset.nombre+'». Lo que ya está en el servidor no se toca. Lo que esté pendiente de enviar se pierde.', 'Olvidar', true).then(function(si){ if(si) DB.borrarOp(b.dataset.olvidar).then(cargarMias); });
	});
	cargarAbiertas(); cargarMias();
	return v;
}
var abiertas=null, mias=[];
function cargarAbiertas(){
	E.rest('operaciones', {token:false}).then(function(r){ abiertas=r.estado===200?r.j:[]; E.refrescar(); }).catch(function(){ abiertas='sin'; E.refrescar(); });
}
function cargarMias(){ DB.ops().then(function(l){ mias=l.sort(function(a,b){ return (b.visto||0)-(a.visto||0); }); E.refrescar(); }); }
E.VIVO.abiertas = function(){
	if(abiertas===null) return '<p class="em-nota">Consultando…</p>';
	if(abiertas==='sin') return '<p class="em-nota">Sin conexión: no se pueden consultar. Si te dictaron un código y una clave, únete apenas haya señal.</p>';
	if(!abiertas.length) return '<p class="em-nota">No hay operaciones abiertas públicas. Las de organizaciones sin verificar solo se ven con su código.</p>';
	return '<div class="em-lista">'+abiertas.map(function(o){ return '<div class="em-fila"><span><strong>'+esc(o.nombre)+'</strong> '+(o.tipo==='simulacro'?E.pill('aviso','Simulacro'):E.pill('peligro','Real'))+(o.estado==='cerrado'?' '+E.pill('apagado','Cerrada'):'')+'<small>'+esc(o.zona||'Sin zona')+(o.org?' · '+esc(o.org.sigla||o.org.nombre):'')+' · actividad '+E.haceTxt(o.actividad*1000)+'</small></span><button type="button" class="em-btn" data-unir="'+esc(o.codigo)+'"'+(o.mia?' data-cuenta="1"':'')+'>'+(o.mia?'Abrir':'Unirme')+'</button></div>'; }).join('')+'</div>';
};
E.VIVO.mias = function(){
	if(!mias.length) return '<p class="em-nota">Ninguna todavía.</p>';
	return '<div class="em-lista">'+mias.map(function(o){ return '<div class="em-fila"><span><strong>'+esc(o.nombre)+'</strong> '+(o.tipo==='simulacro'?E.pill('aviso','Simulacro'):'')+(o.local?' '+E.pill('apagado','Sin servidor'):'')+'<small>'+(o.local?'Solo en este dispositivo':esc(o.codigo)+(o.token||(o.actor&&o.actor.cuenta)?'':' · hay que volver a entrar'))+(o.visto?' · abierta '+E.haceTxt(o.visto):'')+'</small></span><span class="em-acciones"><button type="button" class="em-btn" data-abrir="'+esc(o.codigo)+'">Abrir</button><button type="button" class="em-x" data-olvidar="'+esc(o.codigo)+'" data-nombre="'+esc(o.nombre)+'" aria-label="Olvidar «'+esc(o.nombre)+'» en este dispositivo">×</button></span></div>'; }).join('')+'</div>';
};
function claveSugerida(n){ var p=['CONDOR','CEIBA','GUADUA','PALMA','NEVADO','TOLIMA','CAFETO','ORQUIDEA','COLIBRI','FRAILEJON','YARUMO','SAMAN','QUINDIO','MACANA']; var s=p[Math.floor(Math.random()*p.length)]; for(var i=0;i<(n||1);i++) s+='-'+(10+Math.floor(Math.random()*90)); return s; }
/* Crea una operación en el servidor. alCrear(op) se llama después de entrar (lo usa el simulador). */
function formCrear(tipo, prefijo, alCrear){
	var sim=tipo==='simulacro';
	E.modal(sim?'Nuevo simulacro':'Activar una operación real', '<form class="em-form" id="f-crear" novalidate>'
		+(sim?'':'<p class="em-aviso">Úsala solo para una emergencia real. Para practicar, crea un simulacro: queda separado y marcado.</p>')
		+'<label>Nombre<input class="em-campo" name="nombre" required minlength="3" value="'+esc(prefijo||'')+'" placeholder="'+(sim?'Ej.: Simulacro de sismo, octubre':'Ej.: Lluvias en Risaralda')+'"></label>'
		+'<label>Zona<input class="em-campo" name="zona" placeholder="Municipios, departamento"></label>'
		+'<label>Clave de operadores<input class="em-campo em-mono" name="clave" required minlength="6" value="'+claveSugerida(1)+'"><small>Se dicta por radio a los operadores. Mínimo 6 caracteres.</small></label>'
		+'<label>Clave de coordinación<input class="em-campo em-mono" name="clave_coord" required minlength="8" value="'+claveSugerida(2)+'"><small>Solo para quien coordina: verifica, prioriza, cierra, revoca accesos. Distinta de la anterior; no la dictes en la red abierta.</small></label>'
		+'<button class="em-btn '+(sim?'primario':'peligro')+' em-grande">'+(sim?'Crear simulacro':'Activar operación')+'</button><p class="em-error" id="f-crear-msg" role="alert"></p></form>', function(c){
		$('#f-crear',c).addEventListener('submit', function(e){
			e.preventDefault(); var f=this, b=$('button',f), msg=$('#f-crear-msg',c);
			if(f.nombre.value.trim().length<3){ msg.textContent='Ponle un nombre de al menos 3 letras.'; f.nombre.focus(); return; }
			if(f.clave.value.length<6 || f.clave_coord.value.length<8 || f.clave.value===f.clave_coord.value){ msg.textContent='Las claves deben tener al menos 6 y 8 caracteres, y ser distintas.'; return; }
			b.disabled=true; msg.textContent='Creando…';
			E.rest('operaciones', {metodo:'POST', token:false, cuerpo:{nombre:f.nombre.value, zona:f.zona.value, tipo:tipo, indicativo:E.yo.ind, clave:f.clave.value, clave_coord:f.clave_coord.value, org_id:(E.cuenta&&E.cuenta.org)?E.cuenta.org.id:0}}).then(function(r){
				b.disabled=false;
				if(r.estado!==200){ msg.textContent=(r.j&&r.j.error)||'No se pudo crear.'; return; }
				var o=r.j.operacion, op={codigo:o.codigo, nombre:o.nombre, tipo:o.tipo, zona:o.zona, estado:o.estado, org:o.org, token:r.j.token, actor:r.j.actor, cursor:0, local:false};
				var clave=f.clave.value, ccoord=f.clave_coord.value;
				DB.guardarOp(op).then(function(){ return E.entrar(o.codigo); }).then(function(){
					if(alCrear){ E.cerrarModal(); alCrear(op); return; }
					E.modal('Dicta esto por radio', '<p class="em-dictar">Código <strong class="em-mono">'+esc(o.codigo)+'</strong><br><small>'+core.fonetico(o.codigo)+'</small></p><p class="em-dictar">Clave de operadores <strong class="em-mono">'+esc(clave)+'</strong></p><p>Cada operador entra a <strong>'+esc(location.host)+'/emergencias</strong>, toca «Unirme con código y clave» y escribe ambos.</p><p class="em-aviso">Guarda la clave de coordinación: <b class="em-mono">'+esc(ccoord)+'</b>. No se puede recuperar; un coordinador con cuenta o con esta clave puede cambiarla en Administración.</p><button type="button" class="em-btn primario" id="ok-dictar">Entendido</button>', function(c2){ $('#ok-dictar',c2).addEventListener('click', E.cerrarModal); });
				});
			}).catch(function(){ b.disabled=false; msg.textContent='Sin conexión: no se puede crear en el servidor. Usa «Trabajar sin conexión» y exporta los registros después.'; });
		});
	});
}
E.formCrear = formCrear;
function formLocal(sim, nombre, alCrear){
	E.modal('Trabajar sin conexión', '<form class="em-form" id="f-local"><p class="em-aviso">Modo de un solo dispositivo: <b>no es un sistema multiusuario</b> ni tiene control de acceso. Los registros se pasan a otro dispositivo exportando un respaldo. Úsalo cuando no hay servidor.</p><label>Nombre<input class="em-campo" name="nombre" required value="'+esc(nombre||'')+'" placeholder="Ej.: Corte total, Dosquebradas"></label><label class="em-check"><input type="checkbox" name="sim"'+(sim?' checked':'')+'> Es un simulacro</label><button class="em-btn primario">Empezar</button></form>', function(c){
		$('#f-local',c).addEventListener('submit', function(e){
			e.preventDefault(); if(this.nombre.value.trim().length<3) return;
			var cod='LOCAL'+core.uuid().replace(/-/g,'').slice(0,6).toUpperCase();
			var op={codigo:cod, nombre:this.nombre.value.trim(), tipo:this.sim.checked?'simulacro':'real', zona:'', estado:'abierto', local:true, cursor:0, actor:{rol:'local', permisos:['ver','operar','recursos','coordinar'], nombre:E.yo.ind}};
			DB.guardarOp(op).then(function(){ E.cerrarModal(); return E.entrar(cod); }).then(function(){ if(alCrear) alCrear(op); });
		});
	});
}
E.formLocal = formLocal;
function pedirEntrada(codigo, conCuenta){
	if(conCuenta){
		E.rest('operaciones/'+codigo, {token:false}).then(function(r){
			if(r.estado!==200){ E.aviso((r.j&&r.j.error)||'Sin acceso', 'aviso'); return; }
			var o=r.j.operacion; DB.op(o.codigo).then(function(prev){ prev=prev||{cursor:0}; var op=Object.assign(prev, {codigo:o.codigo, nombre:o.nombre, tipo:o.tipo, zona:o.zona, estado:o.estado, org:o.org, actor:r.j.actor, local:false}); return DB.guardarOp(op); }).then(function(){ E.entrar(o.codigo); });
		}).catch(function(){ E.aviso('Sin conexión.', 'aviso'); });
		return;
	}
	E.modal('Unirme a una operación', '<form class="em-form" id="f-unir" novalidate><label>Código<input class="em-campo em-mono em-grande" name="codigo" required value="'+esc(codigo)+'" autocapitalize="characters" autocomplete="off"></label>'
		+'<label>Clave<input class="em-campo em-mono" name="clave" required autocomplete="off"></label><p class="em-nota">La da la estación de control por radio. La clave define tu rol: operador, coordinación o una invitación (observador, recursos).</p><button class="em-btn primario em-grande">Entrar como '+esc(E.yo.ind)+'</button><p class="em-error" id="f-unir-msg" role="alert"></p></form>', function(c){
		$('#f-unir',c).addEventListener('submit', function(e){
			e.preventDefault(); var f=this, msg=$('#f-unir-msg',c), cod=f.codigo.value.toUpperCase().replace(/[^A-Z0-9]/g,'');
			if(cod.length<4 || !f.clave.value){ msg.textContent='Escribe el código y la clave.'; return; }
			msg.textContent='Verificando…';
			E.rest('operaciones/'+cod+'/sesion', {metodo:'POST', token:false, cuerpo:{clave:f.clave.value, indicativo:E.yo.ind, nombre:E.yo.nombre}}).then(function(r){
				if(r.estado!==200){ msg.textContent=(r.j&&r.j.error)||'No se pudo entrar.'; return; }
				var o=r.j.operacion;
				DB.op(o.codigo).then(function(prev){ prev=prev||{cursor:0}; var op=Object.assign(prev, {codigo:o.codigo, nombre:o.nombre, tipo:o.tipo, zona:o.zona, estado:o.estado, org:o.org, token:r.j.token, actor:r.j.actor, local:false}); return DB.guardarOp(op); })
					.then(function(){ E.cerrarModal(); return E.entrar(o.codigo); }).then(function(){ E.aviso('Entraste como '+((C.roles||{})[r.j.actor.rol]||r.j.actor.rol)+'.'); });
			}).catch(function(){ msg.textContent='Sin conexión. Inténtalo cuando haya señal; mientras tanto puedes trabajar sin conexión.'; });
		});
	});
}
E.pedirEntrada = pedirEntrada;
E.entrar = function(cod){
	return Promise.all([DB.op(cod), DB.eventos(cod)]).then(function(r){
		var op=r[0]; if(!op){ E.aviso('No se encontró esa operación en el dispositivo.', 'aviso'); return; }
		E.op=op; E.actor=op.actor||null; E.eventos=(r[1]||[]).filter(function(e){ return !e.descartado; }); op.visto=Date.now(); DB.guardarOp(op);
		try { sessionStorage.setItem('rau_em_op', cod); } catch(e){}
		sync.error=''; sync.espera=5000; sync.reintento=0; E.recalcular();
		var v='tablero'; try { v=sessionStorage.getItem('rau_em_vista')||'tablero'; } catch(e){}
		E.ir(v); pintarSync();
		E.alEntrar.forEach(function(f){ try { f(); } catch(e){} });
		if(!op.local) return E.sincronizar();
	});
};
E.alEntrar=[];
E.salir = function(){ E.op=null; E.actor=null; E.eventos=[]; E.recalcular(); try { sessionStorage.removeItem('rau_em_op'); } catch(e){} E.vista='tablero'; pintarCabecera(); pintarVista(); pintarSync(); cargarMias(); };

/* ── Preparación (en el dispositivo) ─────────────────────────────────────── */
var PREPARACION = [
	{g:'Mi maletín (72 horas)', dias:90, items:['Radio portátil con batería cargada','Batería de repuesto y cargador de carro','Antena de repuesto o de más ganancia','Lista de frecuencias impresa','Libreta, lápiz y formularios de mensaje','Linterna con pilas','Agua y comida para 3 días','Botiquín y medicamentos propios','Documentos en bolsa plástica','Batería externa para el teléfono']},
	{g:'Mi estación', dias:30, items:['Batería probada con carga real','Generador probado con carga','Transferencia al generador probada','Antena de HF para cubrir Colombia (dipolo bajo, NVIS) lista para armar','Memorias del radio programadas (llamada y repetidoras de la zona)','Este centro de mando abierto una vez con internet en este dispositivo']},
	{g:'Coordinación', dias:180, items:['Sé quién es la estación de control de mi zona','Conozco al consejo de gestión del riesgo de mi municipio','Participé en el último simulacro','Mi familia sabe a dónde ir y cómo reportarse']}
];
E.prep={};
E.VIVO.prep = function(){
	return PREPARACION.map(function(g, gi){
		return '<details class="em-prep"><summary>'+esc(g.g)+'</summary><ul>'+g.items.map(function(t, ii){
			var k=gi+'.'+ii, f=E.prep[k], vencido=f && (Date.now()-f)>g.dias*864e5;
			return '<li><label class="em-check"><input type="checkbox" data-prep="'+k+'"'+(f&&!vencido?' checked':'')+'> <span>'+esc(t)+(f?'<small class="'+(vencido?'em-rojo':'')+'"> · revisado '+new Date(f).toLocaleDateString('es-CO')+(vencido?' (revisar de nuevo)':'')+'</small>':'')+'</span></label></li>';
		}).join('')+'</ul></details>';
	}).join('');
};
document.addEventListener('change', function(e){ var k=e.target.dataset && e.target.dataset.prep; if(!k) return; if(e.target.checked) E.prep[k]=Date.now(); else delete E.prep[k]; DB.kvSet('prep', E.prep); });
E.telefonos = function(){
	return '<div class="em-tels"><a href="tel:123"><strong>123</strong>Línea única de emergencias</a><a href="tel:132"><strong>132</strong>Cruz Roja</a><a href="tel:144"><strong>144</strong>Defensa Civil</a><a href="tel:119"><strong>119</strong>Bomberos</a></div>';
};

/* ── J. Modo de campo y operación sin conexión ───────────────────────────── */
var instalar=null;
window.addEventListener('beforeinstallprompt', function(e){ e.preventDefault(); instalar=e; });
E.registrarVista('campo', {nombre:'Campo y sin conexión', icono:'⛺', fn:function(){
	var v=E.el('<div><h1>Modo de campo y sin conexión</h1><div class="em-grid2">'
		+'<section class="em-tarjeta"><h2>Interfaz</h2><label class="em-check"><input type="checkbox" id="cp-compacto"'+(document.documentElement.classList.contains('em-compacto')?' checked':'')+'> <span>Modo compacto: botones grandes, solo lo esencial</span></label>'
		+'<label class="em-check"><input type="checkbox" id="cp-sol"'+(document.documentElement.dataset.tema==='sol'?' checked':'')+'> <span>Modo sol: pantalla clara de alto contraste</span></label>'
		+'<label class="em-inline">Un reporte o verificación vale <select class="em-campo" id="cp-ventana">'+[30,60,120,240].map(function(n){ return '<option value="'+n+'"'+(E.ventana===n?' selected':'')+'>'+n+' min</option>'; }).join('')+'</select></label>'
		+'<div class="em-acciones"><button type="button" class="em-btn" id="cp-instalar">Instalar como aplicación</button></div><p class="em-nota" id="cp-instalar-n">En el celular también: menú del navegador → «Agregar a la pantalla de inicio».</p></section>'
		+'<section class="em-tarjeta"><h2>Sincronización</h2><div data-vivo="cola">'+E.VIVO.cola()+'</div></section>'
		+'<section class="em-tarjeta"><h2>Almacenamiento</h2><div id="cp-esp"><p class="em-nota">Calculando…</p></div><p class="em-aviso">Los datos de este dispositivo <b>no son un respaldo suficiente</b>: un teléfono se pierde, se moja o se borra. En una operación real, exporta respaldos con frecuencia (Informes → Exportar).</p></section>'
		+'<section class="em-tarjeta"><h2>Qué funciona sin internet</h2><ul class="em-lista-txt"><li>Abrir esta página (después de una primera visita con internet).</li><li>Registrar incidentes, mensajes, estaciones, recursos y notas: quedan en cola y se envían solos al volver la señal.</li><li>Consultar lo ya descargado de la operación, el plan, el directorio de frecuencias y las herramientas.</li><li>El mapa con las capas de la operación sobre un contorno de Colombia guardado. El mapa base detallado necesita internet: solo se conservan los mosaicos ya vistos.</li></ul><p class="em-nota">No funciona sin internet: crear operaciones en el servidor, unirse, alertas en vivo, búsqueda de lugares.</p></section>'
		+'</div></div>');
	$('#cp-compacto',v).addEventListener('change', function(){ document.documentElement.classList.toggle('em-compacto', this.checked); DB.kvSet('compacto', this.checked); });
	$('#cp-sol',v).addEventListener('change', function(){ E.tema(this.checked?'sol':''); });
	$('#cp-ventana',v).addEventListener('change', function(){ E.ventana=+this.value; DB.kvSet('ventana', E.ventana); E.refrescar(); });
	$('#cp-instalar',v).addEventListener('click', function(){ if(instalar){ instalar.prompt(); instalar=null; } else $('#cp-instalar-n',v).textContent='Este navegador no ofrece la instalación directa (o ya está instalada). Usa «Agregar a la pantalla de inicio» en su menú.'; });
	DB.espacio().then(function(s){
		$('#cp-esp',v).innerHTML=(s.memoria?'<p class="em-error">Este navegador no permite guardar datos (modo privado): todo se pierde al cerrar la pestaña.</p>':'')
			+'<p>Usado: '+(s.uso?(s.uso/1048576).toFixed(1)+' MB':'—')+(s.cuota?' de '+(s.cuota/1073741824).toFixed(1)+' GB disponibles':'')+'</p><p>'+(s.persistente?'El navegador se comprometió a no borrar estos datos por falta de espacio.':'El navegador podría borrar estos datos si se queda sin espacio.')+'</p>'
			+(s.persistente||s.memoria?'':'<button type="button" class="em-btn" id="cp-persist">Pedir almacenamiento persistente</button>');
		var b=$('#cp-persist',v); if(b) b.addEventListener('click', function(){ DB.pedirPersistencia().then(function(ok){ E.aviso(ok?'Concedido.':'El navegador no lo concedió.'); }); });
	});
	v.addEventListener('click', function(e){
		if(e.target.id==='cp-sync'){ sync.espera=5000; sync.reintento=0; E.sincronizar(); return; }
		var d=e.target.closest('[data-descartar]'); if(!d) return;
		var ev=E.eventos.filter(function(x){ return x.u===d.dataset.descartar; })[0]; if(!ev) return;
		E.confirmar('Descartar registro rechazado', 'Se quita de este dispositivo. El servidor nunca lo aceptó, así que nadie más lo tiene.', 'Descartar', true).then(function(si){
			if(!si) return; ev.descartado=true; DB.guardarEventos(E.op.codigo, [ev]); E.eventos=E.eventos.filter(function(x){ return x!==ev; }); E.recalcular(); E.refrescar();
		});
	});
	return v;
}});
E.VIVO.cola = function(){
	var p=E.pendientes(), r=E.rechazados();
	return '<p>'+(E.op.local?'Operación solo en este dispositivo: nada se envía.':navigator.onLine===false?'<b>Sin internet.</b> Lo registrado queda en cola.':'Con internet.')+'</p>'
		+'<p>'+p.length+' registros sin enviar · '+E.eventos.filter(function(e){ return e.s>0; }).length+' guardados en el servidor'+(sync.ultimo?' · última sincronización '+E.horaLocal(sync.ultimo):'')+'</p>'
		+(sync.error?'<p class="em-error">'+esc(sync.error)+'</p>':'')
		+(p.length?'<details><summary>Ver los '+p.length+' pendientes</summary><ol class="em-bit">'+p.slice(-50).map(function(e){ return '<li><time>'+E.hz(e.ts)+'</time><span class="em-autor">'+esc(e.t)+'</span><span>'+(E.describir?E.describir(e):'')+'</span></li>'; }).join('')+'</ol></details>':'')
		+(r.length?'<h3>Rechazados por el servidor</h3><div class="em-lista">'+r.map(function(e){ return '<div class="em-fila em-borde-peligro"><span><strong>'+esc(e.t)+'</strong> '+(E.describir?E.describir(e):'')+'<small class="em-rojo">'+esc(e.err||'')+'</small></span><button type="button" class="em-btn" data-descartar="'+e.u+'">Descartar</button></div>'; }).join('')+'</div>':'')
		+(!E.op.local?'<button type="button" class="em-btn" id="cp-sync">Sincronizar ahora</button>':'');
};

/* ── K. Administración, auditoría y seguridad ────────────────────────────── */
E.registrarVista('admin', {nombre:'Administración', icono:'🔐', fn:function(){
	var a=E.actor||{}, coord=E.puede('coordinar') && !E.op.local;
	var v=E.el('<div><h1>Administración y seguridad</h1><div class="em-grid2">'
		+'<section class="em-tarjeta"><h2>Mi acceso</h2><p>Operador: <b class="em-mono">'+esc(E.yo.ind)+'</b> · rol <b>'+esc((C.roles||{})[a.rol]||a.rol||'—')+'</b>'+(a.cuenta?' (cuenta del sitio)':E.op.local?'':' (sesión de este dispositivo)')+'</p><p class="em-nota">Permisos: '+esc((a.permisos||[]).join(', '))+'</p>'
		+'<div class="em-acciones"><button type="button" class="em-btn" id="ad-salir">Volver al inicio</button>'+(!E.op.local&&E.op.token?'<button type="button" class="em-btn" id="ad-cerrar-ses">Cerrar mi sesión en este dispositivo</button>':'')+'</div></section>'
		+(coord?'<section class="em-tarjeta"><h2>Accesos a la operación</h2><div id="ad-ses"><p class="em-nota">Cargando…</p></div></section>'
			+'<section class="em-tarjeta"><h2>Invitar</h2><form class="em-form" id="ad-inv"><div class="em-tres"><label>Rol<select class="em-campo" name="rol"><option value="operador">Operador</option><option value="observador">Observador (sin datos de personas)</option><option value="recursos">Responsable de recursos</option></select></label><label>Vigencia (horas)<input class="em-campo" type="number" name="horas" min="1" max="168" value="24"></label><label>Usos<input class="em-campo" type="number" name="usos" min="1" max="50" value="1"></label></div><button class="em-btn primario">Crear invitación</button></form><p id="ad-inv-r" role="status"></p></section>'
			+'<section class="em-tarjeta"><h2>Cambiar claves y revocar</h2><form class="em-form" id="ad-cl"><label>Nueva clave de operadores (opcional)<input class="em-campo em-mono" name="clave" minlength="6" autocomplete="off"></label><label>Nueva clave de coordinación (opcional)<input class="em-campo em-mono" name="clave_coord" minlength="8" autocomplete="off"></label><label class="em-check"><input type="checkbox" name="revocar"> <span>Revocar también todas las sesiones e invitaciones (menos la mía)</span></label><button class="em-btn peligro">Cambiar</button></form></section>'
			+'<section class="em-tarjeta"><h2>Operación</h2><div class="em-acciones">'+(E.S.cerrada?'<button type="button" class="em-btn" id="ad-reabrir">Reabrir operación</button>':'<button type="button" class="em-btn peligro" id="ad-cerrar">Cerrar operación</button>')+'<button type="button" class="em-btn" id="ad-respaldo">Descargar respaldo del servidor</button></div><p class="em-nota">Cerrar no borra nada: impide nuevos registros operativos y deja escribir lecciones aprendidas. El borrado definitivo lo hace un administrador del sitio.</p></section>'
			+'<section class="em-tarjeta em-ancho"><h2>Auditoría</h2><div id="ad-aud"><p class="em-nota">Cargando…</p></div></section>'
		:(E.op.local?'<section class="em-tarjeta"><h2>Operación local</h2><p>Sin servidor no hay control de acceso: cualquiera con este dispositivo puede registrar. Para operar entre varias personas con permisos, crea la operación en el servidor.</p><div class="em-acciones">'+(E.S.cerrada?'<button type="button" class="em-btn" id="ad-reabrir">Reabrir</button>':'<button type="button" class="em-btn peligro" id="ad-cerrar">Cerrar operación</button>')+'</div></section>':'<section class="em-tarjeta"><p>Las funciones de administración de la operación son para coordinación.</p></section>'))
		+(E.cuenta && (E.cuenta.rol==='admin'||E.cuenta.rol==='coordinador')?'<section class="em-tarjeta"><h2>Directorio de frecuencias</h2><p>Puedes corregir o agregar entradas (con fuente obligatoria) en Directorio. Cada cambio queda en la auditoría del sitio.</p><button type="button" class="em-btn" data-ir="directorio">Ir al directorio</button>'+(E.cuenta.rol==='admin'?' <a class="em-btn" href="'+esc(C.admin)+'">Panel del sitio</a>':'')+'</section>':'')
		+'</div></div>');
	$('#ad-salir',v).addEventListener('click', E.salir);
	var cs=$('#ad-cerrar-ses',v); if(cs) cs.addEventListener('click', function(){ E.confirmar('Cerrar mi sesión', 'Este dispositivo deja de tener acceso a la operación. Lo pendiente de enviar se queda guardado hasta que vuelvas a entrar.', 'Cerrar sesión', true).then(function(si){ if(!si) return; E.rest('operaciones/'+E.op.codigo+'/salir', {metodo:'POST'}).catch(function(){}).then(function(){ E.op.token=''; DB.guardarOp(E.op); E.salir(); }); }); });
	var cer=$('#ad-cerrar',v); if(cer) cer.addEventListener('click', function(){
		E.modal('Cerrar operación', '<form class="em-form" id="f-cer"><p>Después de cerrarla solo se admiten lecciones aprendidas e informes, o reabrirla.</p><label>Nota de cierre<textarea class="em-campo" name="nota" rows="3"></textarea></label><button class="em-btn peligro">Cerrar operación</button></form>', function(c){ $('#f-cer',c).addEventListener('submit', function(e){ e.preventDefault(); if(E.registrar('op', {estado:'cerrado', nota:this.nota.value})){ E.cerrarModal(); E.ir('admin'); } }); });
	});
	var re=$('#ad-reabrir',v); if(re) re.addEventListener('click', function(){ if(E.registrar('op', {estado:'abierto', nota:'Reabierta por '+E.yo.ind})) E.ir('admin'); });
	if(!coord) return v;
	function cargarSes(){
		E.rest('operaciones/'+E.op.codigo+'/sesiones').then(function(r){
			if(r.estado!==200){ $('#ad-ses',v).innerHTML='<p class="em-error">'+esc((r.j&&r.j.error)||'No disponible')+'</p>'; return; }
			$('#ad-ses',v).innerHTML='<table class="em-tabla"><thead><tr><th>Indicativo</th><th>Rol</th><th>Origen</th><th>Último uso</th><th></th></tr></thead><tbody>'+r.j.map(function(s){ return '<tr'+(s.revocado?' class="em-tenue"':'')+'><td class="em-mono">'+esc(s.indicativo)+'</td><td>'+esc((C.roles||{})[s.rol]||s.rol)+'</td><td>'+esc(s.origen)+'</td><td>'+E.haceTxt(s.ultimo*1000)+'</td><td>'+(s.revocado?'Revocada':'<button type="button" class="em-btn" data-revocar="'+s.id+'" data-ind="'+esc(s.indicativo)+'">Revocar</button>')+'</td></tr>'; }).join('')+'</tbody></table>';
		}).catch(function(){ $('#ad-ses',v).innerHTML='<p class="em-nota">Sin conexión.</p>'; });
		E.rest('operaciones/'+E.op.codigo+'/auditoria').then(function(r){
			if(r.estado!==200) return;
			$('#ad-aud',v).innerHTML=r.j.length?'<ol class="em-bit">'+r.j.slice(0,200).map(function(x){ return '<li><time>'+E.hz(x.ts*1000)+'</time><span class="em-autor">'+esc(x.actor)+'</span><span>'+esc(x.accion)+' '+esc(x.objeto)+' <small>'+esc(x.rol)+'</small></span></li>'; }).join('')+'</ol>':'<p class="em-nota">Sin acciones sensibles registradas.</p>';
		}).catch(function(){ $('#ad-aud',v).innerHTML='<p class="em-nota">Sin conexión.</p>'; });
	}
	cargarSes();
	v.addEventListener('click', function(e){
		var b=e.target.closest('[data-revocar]'); if(!b) return;
		E.confirmar('Revocar acceso', 'La sesión de '+b.dataset.ind+' deja de funcionar de inmediato. Lo que ya registró se conserva.', 'Revocar', true).then(function(si){
			if(si) E.rest('operaciones/'+E.op.codigo+'/sesiones/'+b.dataset.revocar+'/revocar', {metodo:'POST'}).then(function(){ E.aviso('Acceso revocado.'); cargarSes(); });
		});
	});
	$('#ad-inv',v).addEventListener('submit', function(e){
		e.preventDefault(); var f=this;
		E.rest('operaciones/'+E.op.codigo+'/invitaciones', {metodo:'POST', cuerpo:{rol:f.rol.value, horas:+f.horas.value, usos:+f.usos.value}}).then(function(r){
			$('#ad-inv-r',v).innerHTML=r.estado===200?'Clave de invitación (se muestra una sola vez): <b class="em-mono em-grande">'+esc(r.j.clave)+'</b><br><small>Rol '+esc((C.roles||{})[r.j.rol])+' · vence '+new Date(r.j.expira*1000).toLocaleString('es-CO')+' · '+r.j.usos+' uso(s)</small>':'<span class="em-error">'+esc((r.j&&r.j.error)||'Error')+'</span>';
		}).catch(function(){ E.aviso('Sin conexión.', 'aviso'); });
	});
	$('#ad-cl',v).addEventListener('submit', function(e){
		e.preventDefault(); var f=this;
		if(!f.clave.value && !f.clave_coord.value && !f.revocar.checked){ E.aviso('No hay nada que cambiar.', 'aviso'); return; }
		E.confirmar('Cambiar claves', f.revocar.checked?'Todas las demás sesiones e invitaciones dejan de funcionar. Tendrás que dictar la clave nueva.':'Las sesiones abiertas siguen funcionando; la clave anterior ya no sirve para entrar.', 'Cambiar', true).then(function(si){
			if(!si) return;
			E.rest('operaciones/'+E.op.codigo+'/claves', {metodo:'POST', cuerpo:{clave:f.clave.value, clave_coord:f.clave_coord.value, revocar_sesiones:f.revocar.checked}}).then(function(r){
				if(r.estado===200){ E.aviso('Claves cambiadas'+(r.j.sesiones_revocadas?' · '+r.j.sesiones_revocadas+' sesiones revocadas':'')+'.'); f.reset(); cargarSes(); } else E.aviso((r.j&&r.j.error)||'Error', 'peligro');
			});
		});
	});
	$('#ad-respaldo',v).addEventListener('click', function(){
		E.rest('operaciones/'+E.op.codigo+'/respaldo').then(function(r){ if(r.estado===200) E.descargar(E.nombreArchivo('respaldo-servidor','json'), JSON.stringify(r.j), 'application/json'); else E.aviso((r.j&&r.j.error)||'Error', 'peligro'); }).catch(function(){ E.aviso('Sin conexión.', 'aviso'); });
	});
	return v;
}});

/* Más (celular): el resto de secciones */
E.registrarVista('mas', {nombre:'Más', icono:'☰', fn:function(){
	var todas=[].concat.apply([], GRUPOS.map(function(g){ return g[1]; })).filter(function(id){ return MOVIL.indexOf(id)<0 && E.VISTAS[id]; });
	var v=E.el('<div><h1>Más</h1><div class="em-acciones em-mas">'+todas.map(function(id){ var x=E.VISTAS[id]; return '<button type="button" class="em-btn em-grande" data-ir="'+id+'"><span aria-hidden="true">'+x.icono+'</span> '+esc(x.nombre)+'</button>'; }).join('')+'<button type="button" class="em-btn em-grande" id="mas-buscar">Buscar</button><button type="button" class="em-btn em-grande" id="mas-salir">Volver al inicio</button></div></div>');
	$('#mas-buscar',v).addEventListener('click', function(){ buscar(''); });
	$('#mas-salir',v).addEventListener('click', E.salir);
	return v;
}});

E.tema = function(t){ document.documentElement.dataset.tema=t; DB.kvSet('tema', t); };

/* ── Arranque ────────────────────────────────────────────────────────────── */
function arrancar(){
	armar();
	DB.abrir().then(function(){ return Promise.all([DB.kvGet('yo'), DB.kvGet('prep'), DB.kvGet('tema'), DB.kvGet('compacto'), DB.kvGet('ventana'), DB.kvGet('cuenta')]); }).then(function(r){
		if(r[0]) E.yo=r[0]; E.prep=r[1]||{}; if(r[2]) document.documentElement.dataset.tema=r[2]; if(r[3]) document.documentElement.classList.add('em-compacto'); if(r[4]) E.ventana=r[4]; E.cuenta=r[5]||null;
		if(DB.enMemoria()) setTimeout(function(){ E.aviso('Este navegador no permite guardar datos: todo se pierde al cerrar. Exporta respaldos.', 'peligro'); }, 1500);
		var cod=null; try { cod=sessionStorage.getItem('rau_em_op'); } catch(e){}
		var q=new URLSearchParams(location.search).get('op');
		pintarCabecera(); pintarVista();
		E.alArrancar.forEach(function(f){ try { f(); } catch(e){} });
		if(cod) E.entrar(cod);
		else if(q && E.yo.ind) pedirEntrada(q);
		// Cuenta de WordPress (si hay sesión iniciada en el sitio)
		// Solo se redibuja el inicio: el formulario del indicativo no, para no borrar lo que la persona está escribiendo
		E.rest('yo', {token:false}).then(function(x){ if(x.estado===200){ E.cuenta=x.j.usuario; DB.kvSet('cuenta', E.cuenta); if(!E.op && E.yo.ind && !$('#em-main').contains(document.activeElement)) pintarVista(); } }).catch(function(){});
	});
}
E.alArrancar=[];
document.addEventListener('DOMContentLoaded', arrancar);
})();
