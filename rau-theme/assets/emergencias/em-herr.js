/* Centro de Mando de Emergencias — herramientas de trabajo.
 * Perfil de enlace sobre el terreno real · lluvia observada y pronosticada en un incidente ·
 * tablero de tráfico por estado y antigüedad · paquetes para pasar registros sin internet (texto y QR).
 * Las consultas externas (Open-Meteo) solo se hacen cuando alguien las pide y necesitan internet.
 */
(function(){
'use strict';
var E=window.RauEm, core=E.core, C=E.C, $=E.$, $$=E.$$, esc=E.esc, et=core.et, DB=E.db;
var CLASE_PREC={E:'peligro', P:'aviso', B:'ok', R:'apagado'};
var OPEN_METEO='https://api.open-meteo.com/v1/';
function mm(min){ min=Math.max(0, Math.floor(min)); return min<60 ? min+' min' : Math.floor(min/60)+' h '+E.dos(min%60); }
function num(n, d){ return Number(n).toLocaleString('es-CO', {maximumFractionDigits:d===undefined?1:d, minimumFractionDigits:d===undefined?0:d}); }
function pedirJSON(url){ return fetch(url, {cache:'no-store'}).then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); }); }

/* ── Perfil de enlace ────────────────────────────────────────────────────── */
function rumbo(lat1, lng1, lat2, lng2){ var r=Math.PI/180, y=Math.sin((lng2-lng1)*r)*Math.cos(lat2*r), x=Math.cos(lat1*r)*Math.sin(lat2*r)-Math.sin(lat1*r)*Math.cos(lat2*r)*Math.cos((lng2-lng1)*r); return (Math.atan2(y, x)/r+360)%360; }
/* Lugares con coordenadas que ya conoce la operación */
function lugares(){
	var l=[], S=E.S;
	Object.keys(S.est).forEach(function(k){ var x=S.est[k]; if(x.lat!==undefined) l.push({g:'Estaciones', n:x.ind+(x.lugar?' · '+x.lugar:''), lat:x.lat, lng:x.lng}); });
	(C.repes||[]).forEach(function(r){ if(r.lat && r.lng) l.push({g:'Repetidoras RAU', n:r.n+' '+r.f, lat:+r.lat, lng:+r.lng}); });
	Object.keys(S.pts).forEach(function(k){ var p=S.pts[k]; if(p.activo) l.push({g:'Puntos del mapa', n:p.nombre, lat:p.lat, lng:p.lng}); });
	Object.keys(S.inc).forEach(function(k){ var x=S.inc[k]; if(core.incidenteActivo(x) && x.lat!==undefined) l.push({g:'Incidentes', n:x.titulo, lat:x.lat, lng:x.lng}); });
	return l;
}
function selectorLugar(nombre, l){
	var g={}; l.forEach(function(x, i){ (g[x.g]=g[x.g]||[]).push('<option value="'+i+'">'+esc(x.n)+'</option>'); });
	return '<select class="em-campo" name="'+nombre+'"><option value="">Escribir coordenadas o locator…</option>'+Object.keys(g).map(function(k){ return '<optgroup label="'+esc(k)+'">'+g[k].join('')+'</optgroup>'; }).join('')+'</select>'
		+'<input class="em-campo em-mono" name="'+nombre+'_pos" placeholder="4.8133, -75.6961 · FJ24ep"><small class="em-campo-n" data-pos="'+nombre+'"></small>';
}
function svgPerfil(o){
	var W=640, H=220, p=o.pts, minY=Infinity, maxY=-Infinity;
	p.forEach(function(x){ minY=Math.min(minY, x.suelo, x.linea-0.6*x.f1); maxY=Math.max(maxY, x.terreno, x.linea); });
	minY=Math.floor((minY-20)/50)*50; maxY=Math.ceil((maxY+20)/50)*50;
	function X(i){ return 40+(W-50)*i/(p.length-1); } function Y(v){ return H-24-(H-34)*(v-minY)/(maxY-minY); }
	var suelo='M'+X(0)+','+(H-24)+p.map(function(x, i){ return ' L'+X(i).toFixed(1)+','+Y(x.terreno).toFixed(1); }).join('')+' L'+X(p.length-1)+','+(H-24)+' Z';
	var fres=p.map(function(x, i){ return (i?'L':'M')+X(i).toFixed(1)+','+Y(x.linea-0.6*x.f1).toFixed(1); }).join(' ');
	var ip=o.peor ? p.indexOf(o.peor) : -1;
	return '<svg class="em-perfil" viewBox="0 0 '+W+' '+H+'" role="img" aria-label="Perfil del terreno entre los dos puntos">'
		+[minY, (minY+maxY)/2, maxY].map(function(v){ return '<line x1="40" x2="'+(W-10)+'" y1="'+Y(v)+'" y2="'+Y(v)+'" class="em-pf-g"/><text x="36" y="'+(Y(v)+4)+'" text-anchor="end" class="em-pf-t">'+Math.round(v)+'</text>'; }).join('')
		+'<path d="'+suelo+'" class="em-pf-suelo"/><path d="'+fres+'" class="em-pf-fresnel"/>'
		+'<line x1="'+X(0)+'" y1="'+Y(p[0].linea)+'" x2="'+X(p.length-1)+'" y2="'+Y(p[p.length-1].linea)+'" class="em-pf-linea '+o.estado+'"/>'
		+(ip>0?'<circle cx="'+X(ip)+'" cy="'+Y(p[ip].terreno)+'" r="5" class="em-pf-peor"/>':'')
		+'<text x="40" y="'+(H-6)+'" class="em-pf-t">A · 0 km</text><text x="'+(W-10)+'" y="'+(H-6)+'" text-anchor="end" class="em-pf-t">B · '+num(o.distKm)+' km</text></svg>';
}
E.perfilEnlace = function(){
	var l=lugares();
	E.modal('Perfil de enlace', '<form class="em-form" id="f-pf" novalidate>'
		+'<div class="em-dos"><label>Punto A'+selectorLugar('a', l)+'</label><label>Punto B'+selectorLugar('b', l)+'</label></div>'
		+'<div class="em-tres"><label>Frecuencia (MHz)<input class="em-campo" name="f" type="number" step="any" min="1" max="6000" value="146.52"></label><label>Antena en A (m)<input class="em-campo" name="ha" type="number" min="0" max="300" value="10"></label><label>Antena en B (m)<input class="em-campo" name="hb" type="number" min="0" max="300" value="10"></label></div>'
		+'<button class="em-btn primario">Calcular sobre el terreno</button><p class="em-error" id="f-pf-err" role="alert"></p></form><div id="pf-res" aria-live="polite"></div>'
		+'<p class="em-nota">Terreno: modelo digital de elevación Copernicus GLO-90 (90 m) consultado a Open-Meteo con 100 puntos entre A y B. Curvatura con k = 4/3. No incluye árboles, edificios ni difracción: un «despejado» es necesario, no suficiente. Confirma con una prueba de enlace. Las coordenadas de A y B se envían a Open-Meteo para la consulta.</p>', function(c){
		var f=$('#f-pf',c);
		function punto(n){ var s=f[n].value; if(s!=='') return l[+s]; var p=core.parsearPosicion(f[n+'_pos'].value); return p ? {n:f[n+'_pos'].value, lat:p.lat, lng:p.lng} : null; }
		['a','b'].forEach(function(n){ function ver(){ var p=punto(n), s=$('[data-pos="'+n+'"]',c); f[n+'_pos'].hidden=f[n].value!==''; s.textContent=p?core.locator(p.lat,p.lng)+' · '+p.lat.toFixed(4)+', '+p.lng.toFixed(4):''; } f[n].addEventListener('change', ver); f[n+'_pos'].addEventListener('input', ver); ver(); });
		f.addEventListener('submit', function(e){
			e.preventDefault(); var err=$('#f-pf-err',c), A=punto('a'), B=punto('b'), fMHz=+f.f.value, res=$('#pf-res',c);
			err.textContent='';
			if(!A || !B){ err.textContent='Elige o escribe los dos puntos.'; return; }
			var d=core.distanciaKm(A.lat, A.lng, B.lat, B.lng);
			if(d<0.05){ err.textContent='Los puntos están demasiado cerca.'; return; }
			if(d>300){ err.textContent='Más de 300 km: a esa distancia manda la propagación, no la línea de vista. Usa HF.'; return; }
			if(!(fMHz>0)){ err.textContent='Indica la frecuencia.'; return; }
			var pts=core.puntosEntre(A.lat, A.lng, B.lat, B.lng, 100), clave='elev:'+[A.lat, A.lng, B.lat, B.lng].map(function(v){ return v.toFixed(4); }).join(',');
			res.innerHTML='<p class="em-nota">Consultando el terreno…</p>';
			DB.kvGet(clave).catch(function(){ return null; }).then(function(guardado){
				if(guardado) return guardado;
				if(navigator.onLine===false) throw new Error('sin internet');
				return pedirJSON(OPEN_METEO+'elevation?latitude='+pts.map(function(p){ return p.lat.toFixed(5); }).join(',')+'&longitude='+pts.map(function(p){ return p.lng.toFixed(5); }).join(','))
					.then(function(j){ if(!j.elevation || j.elevation.length!==100) throw new Error('respuesta incompleta'); DB.kvSet(clave, j.elevation).catch(function(){}); return j.elevation; });
			}).then(function(elev){
				var o=core.perfilEnlace({elev:elev, distKm:d, fMHz:fMHz, hA:+f.ha.value||0, hB:+f.hb.value||0});
				var txt={despejado:['ok','Línea de vista despejada','El terreno deja libre al menos el 60 % de la primera zona de Fresnel en todo el trayecto.'],
					fresnel:['aviso','Línea de vista justa','No hay cerro de por medio, pero el terreno invade la zona de Fresnel: espera señal más débil o variable. Subir las antenas ayuda.'],
					obstruido:['peligro','Obstruido por el terreno','Un cerro corta la línea entre las antenas. En VHF/UHF directo es poco probable el enlace: busca un punto intermedio o una repetidora.']}[o.estado];
				var pe=o.peor;
				res.innerHTML='<div class="em-pf-res"><p>'+E.pill(txt[0], txt[1])+' <small>'+esc(txt[2])+'</small></p>'
					+'<dl class="em-dl"><dt>Distancia</dt><dd>'+num(d, 2)+' km</dd><dt>Rumbo de A a B</dt><dd>'+Math.round(rumbo(A.lat, A.lng, B.lat, B.lng))+'° · de B a A '+Math.round(rumbo(B.lat, B.lng, A.lat, A.lng))+'°</dd>'
					+'<dt>Altura del suelo</dt><dd>A '+Math.round(elev[0])+' m · B '+Math.round(elev[99])+' m s. n. m.</dd>'
					+'<dt>Pérdida en espacio libre</dt><dd>'+num(o.fspl)+' dB a '+num(fMHz, 3)+' MHz</dd>'
					+(pe?'<dt>Punto más crítico</dt><dd>a '+num(pe.dKm, 1)+' km de A, suelo a '+Math.round(pe.suelo)+' m: '+(pe.despeje<0?'el terreno queda '+Math.round(-pe.despeje)+' m por encima de la línea':'la línea pasa '+Math.round(pe.despeje)+' m sobre el terreno')+' (60 % de Fresnel ahí: '+Math.round(0.6*pe.f1)+' m)</dd>':'')+'</dl>'
					+svgPerfil(o)+'<p class="em-nota">Línea azul: entre antenas. Punteada: borde del 60 % de la primera zona de Fresnel. Sombreado: terreno con la curvatura de la Tierra.</p></div>';
			}).catch(function(x){ res.innerHTML='<p class="em-error">'+(String(x&&x.message)==='sin internet'?'Sin internet: el perfil necesita consultar el terreno una vez (después queda guardado para estos dos puntos).':'No se pudo consultar el terreno ('+esc(x&&x.message)+'). Intenta de nuevo.')+'</p>'; });
		});
	}, true);
};
document.addEventListener('click', function(e){ if(e.target.closest('[data-perfil]')) E.perfilEnlace(); });

/* ── Lluvia en la zona de un incidente ───────────────────────────────────── */
var lluvias={};
function cargarLluvia(id){
	var x=E.S.inc[id], cont=$('[data-lluvia="'+id+'"]'); if(!x || x.lat===undefined || !cont) return;
	// Punto redondeado a 0,1° (unos 11 km): basta para un pronóstico y no revela la ubicación exacta
	var la=Math.round(x.lat*10)/10, lo=Math.round(x.lng*10)/10, k=la+','+lo, g=lluvias[k];
	cont.innerHTML='<p class="em-nota">Consultando…</p>';
	(g && Date.now()-g.t<1800000 ? Promise.resolve(g.j) : pedirJSON(OPEN_METEO+'forecast?latitude='+la+'&longitude='+lo+'&hourly=precipitation,precipitation_probability&past_days=1&forecast_days=2&timezone=GMT').then(function(j){ lluvias[k]={t:Date.now(), j:j}; return j; }))
	.then(function(j){ cont.innerHTML=htmlLluvia(j, la, lo); })
	.catch(function(){ cont.innerHTML='<p class="em-error">No se pudo consultar (¿sin internet?).</p><button type="button" class="em-btn" data-lluvia-cargar="'+id+'">Reintentar</button>'; });
}
function htmlLluvia(j, la, lo){
	var h=j.hourly, ahora=Date.now(), t=h.time.map(function(s){ return Date.parse(s+':00Z'); }), p=h.precipitation, pr=h.precipitation_probability||[];
	var i0=0; while(i0<t.length && t[i0]<=ahora-3600000) i0++;
	function suma(a, b){ var s=0; for(var i=Math.max(0,a); i<Math.min(p.length,b); i++) s+=p[i]||0; return s; }
	var atras=suma(i0-24, i0), adelante=suma(i0, i0+24), max=0, imax=-1, pmax=0;
	for(var i=i0; i<Math.min(p.length, i0+48); i++){ if((p[i]||0)>max){ max=p[i]; imax=i; } if(i<i0+24) pmax=Math.max(pmax, pr[i]||0); }
	var desde=Math.max(0, i0-24), hasta=Math.min(p.length, i0+48), n=hasta-desde, top=Math.max(2, Math.max.apply(null, p.slice(desde, hasta)));
	var W=640, H=120, bw=(W-20)/n;
	var barras=p.slice(desde, hasta).map(function(v, k){ var hh=(H-30)*(v||0)/top; return '<rect x="'+(10+k*bw).toFixed(1)+'" y="'+(H-20-hh).toFixed(1)+'" width="'+Math.max(1, bw-1).toFixed(1)+'" height="'+hh.toFixed(1)+'" class="'+(desde+k<i0?'em-ll-pasado':'em-ll-futuro')+'"/>'; }).join('');
	var xa=10+(i0-desde)*bw;
	return '<dl class="em-dl"><dt>Últimas 24 h</dt><dd><b>'+num(atras)+' mm</b></dd><dt>Próximas 24 h</dt><dd><b>'+num(adelante)+' mm</b>'+(pmax?' · probabilidad máxima '+pmax+' %':'')+'</dd>'
		+(imax>=0&&max>0?'<dt>Hora más lluviosa (48 h)</dt><dd>'+num(max)+' mm a las '+core.horaZ(t[imax])+' ('+E.horaLocal(t[imax])+' local)</dd>':'<dt>Próximas 48 h</dt><dd>Sin lluvia pronosticada</dd>')+'</dl>'
		+'<svg class="em-lluvia" viewBox="0 0 '+W+' '+H+'" role="img" aria-label="Lluvia por hora: 24 horas atrás y 48 adelante">'+barras+'<line x1="'+xa+'" x2="'+xa+'" y1="4" y2="'+(H-20)+'" class="em-ll-ahora"/><text x="'+xa+'" y="'+(H-6)+'" text-anchor="middle" class="em-pf-t">ahora</text><text x="10" y="12" class="em-pf-t">'+num(top)+' mm/h</text></svg>'
		+'<p class="em-nota">Pronóstico de modelos numéricos (Open-Meteo) para la celda de '+la+', '+lo+' (punto redondeado a unos 11 km). No es una alerta oficial: las alertas por lluvia y deslizamientos en Colombia las emite el IDEAM.</p>';
}
document.addEventListener('click', function(e){ var b=e.target.closest('[data-lluvia-cargar]'); if(b) cargarLluvia(b.dataset.lluviaCargar); });

/* ── Tablero de tráfico: dónde está cada mensaje y cuánto lleva ahí ──────── */
var COLUMNAS=[['Por enviar',['creado','en_cola']],['En tránsito',['enviado','recibido_nodo']],['Entregado, sin confirmar',['entregado']],['Con problemas',['fallido']]];
E.VIVO.trafTablero = function(){
	var ahora=Date.now(), S=E.S, msgs=Object.keys(S.msgs).map(function(k){ return S.msgs[k]; });
	var hechos=msgs.filter(function(m){ return (m.estado==='confirmado'||m.estado==='anulado') && ahora-m.estados[m.estados.length-1].ts<7200000; }).length;
	return '<div class="em-kanban">'+COLUMNAS.map(function(col){
		var l=msgs.filter(function(m){ return col[1].indexOf(m.estado)>=0; }).sort(function(a,b){ return core.ORDEN_PREC[a.d.prec]-core.ORDEN_PREC[b.d.prec] || a.ts-b.ts; });
		return '<section class="em-kb-col"><h3>'+esc(col[0])+' <span class="em-mono">'+l.length+'</span></h3>'+(l.length?l.map(function(m){
			var edad=(ahora-m.ts)/60000, lim=core.LIMITE_MSG[m.d.prec]||240, ult=m.estados[m.estados.length-1];
			return '<button type="button" class="em-kb-t em-borde-'+CLASE_PREC[m.d.prec]+'" data-ver-msg="'+m.id+'">'+E.pill(CLASE_PREC[m.d.prec], et('prec',m.d.prec))+' <b>n.º '+esc(m.d.num||'s/n')+'</b><span>→ '+esc(m.d.para)+'</span>'
				+'<small>'+esc(et('msg_est',m.estado))+(ult.nodo?' en '+esc(ult.nodo):'')+' · desde '+core.horaZ(ult.ts)+'</small><span class="em-mono '+(edad>=lim?'em-rojo':edad>=lim/2?'em-ambar':'em-verde')+'">'+mm(edad)+' de '+lim+' min</span></button>';
		}).join(''):'<p class="em-nota">—</p>')+'</section>';
	}).join('')+'</div><p class="em-nota">Cerrados (confirmados o anulados) en las últimas 2 h: '+hechos+'. El tiempo cuenta desde que se registró el mensaje; el límite depende de la precedencia.</p>';
};

/* ── Paquetes sin internet ───────────────────────────────────────────────── */
var MEDIOS={radio:['Radio (fldigi, Winlink): partes de 600 caracteres', 600], qr:['Código QR en pantalla: partes de 900', 900], texto:['Mensajería o correo: una sola parte', 20000]};
function cargarQR(){ if(window.qrcode) return Promise.resolve(window.qrcode); return new Promise(function(ok, mal){ var s=document.createElement('script'); s.src=C.qr; s.onload=function(){ ok(window.qrcode); }; s.onerror=mal; document.head.appendChild(s); }); }
function svgQR(texto){ var q=window.qrcode(0, 'L'); q.addData(texto); q.make(); return q.createSvgTag({cellSize:3, margin:4, scalable:true}); }
E.montarPaquetes = function(cont){
	if(!cont || !E.op) return;
	var ahora=Date.now();
	cont.innerHTML='<p class="em-nota">Cuando no hay internet, los registros pasan de un dispositivo a otro como texto: por radio digital, por un mensaje o mostrando un código QR. Cada parte lleva una suma de verificación; si llega alterada se rechaza. Quien recibe ve los registros marcados «por paquete».</p>'
		+'<div class="em-dos"><form class="em-form" id="pq-env"><h3>Enviar</h3><label>Qué registros<select class="em-campo" name="que">'+E.opciones({todo:'Todos los de esta operación', '1':'De la última hora', '6':'De las últimas 6 horas', '24':'De las últimas 24 horas', mios:'Solo los que registré yo'}, 'todo')+'</select></label>'
		+'<label>Por dónde<select class="em-campo" name="medio">'+E.opciones(Object.keys(MEDIOS).reduce(function(o,k){ o[k]=MEDIOS[k][0]; return o; }, {}), 'radio')+'</select></label><button class="em-btn primario">Preparar paquete</button><div id="pq-sal" aria-live="polite"></div></form>'
		+FORM_RECIBIR+'</div>';
	var partes=[], iq=0;
	function verParte(sal, medio){
		if(medio==='qr'){
			cargarQR().then(function(){ sal.innerHTML='<p>Parte <b>'+(iq+1)+'</b> de '+partes.length+'</p><div class="em-qr">'+svgQR(partes[iq])+'</div><div class="em-acciones"><button type="button" class="em-btn" id="pq-ant"'+(iq?'':' disabled')+'>← Anterior</button><button type="button" class="em-btn" id="pq-sig"'+(iq<partes.length-1?'':' disabled')+'>Siguiente →</button></div><p class="em-nota">Quien recibe la escanea con «Escanear QR» (Android) o con la cámara del teléfono y pega el texto.</p>';
				$('#pq-ant',sal).addEventListener('click', function(){ iq--; verParte(sal, medio); }); $('#pq-sig',sal).addEventListener('click', function(){ iq++; verParte(sal, medio); });
			}).catch(function(){ sal.innerHTML='<p class="em-error">No se pudo cargar el generador de QR en este dispositivo.</p>'; });
			return;
		}
		var todo=partes.join('\n\n');
		sal.innerHTML='<p>'+partes.length+(partes.length===1?' parte':' partes')+' · '+todo.length+' caracteres</p><textarea class="em-campo em-mono" rows="6" readonly>'+esc(todo)+'</textarea><div class="em-acciones"><button type="button" class="em-btn" id="pq-cop">Copiar todo</button>'+(navigator.share?'<button type="button" class="em-btn" id="pq-comp">Compartir</button>':'')+'</div>';
		$('#pq-cop',sal).addEventListener('click', function(){ E.copiar(todo); });
		if($('#pq-comp',sal)) $('#pq-comp',sal).addEventListener('click', function(){ navigator.share({text:todo}).catch(function(){}); });
	}
	$('#pq-env',cont).addEventListener('submit', function(e){
		e.preventDefault(); var f=this, sal=$('#pq-sal',cont), q=f.que.value, medio=f.medio.value;
		var ev=E.eventos.filter(function(x){ return x.s!=='r' && !x.descartado && (q==='todo' || (q==='mios' ? x.a===E.yo.ind : x.ts>=ahora-(+q)*3600000)); });
		if(!ev.length){ sal.innerHTML='<p class="em-nota">No hay registros con ese filtro.</p>'; return; }
		sal.innerHTML='<p class="em-nota">Comprimiendo '+ev.length+' registros…</p>';
		core.paqueteCrear(ev, E.op, {tam:MEDIOS[medio][1]}).then(function(p){ partes=p; iq=0; verParte(sal, medio); })
			.catch(function(){ sal.innerHTML='<p class="em-error">Este navegador no puede comprimir (es muy antiguo). Usa el respaldo en archivo.</p>'; });
	});
	montarRecibir(cont);
};
var FORM_RECIBIR='<form class="em-form" id="pq-rec"><h3>Recibir</h3><label>Pega las partes (en cualquier orden)<textarea class="em-campo em-mono" name="t" rows="6" placeholder="RAUEM1 ABC123 1/3 …"></textarea></label><div class="em-acciones"><button class="em-btn primario">Leer paquete</button>'
	+('BarcodeDetector' in window && navigator.mediaDevices ? '<button type="button" class="em-btn" id="pq-cam">Escanear QR con la cámara</button>' : '')+'</div><div id="pq-est" aria-live="polite"></div></form>';
function montarRecibir(cont){
	function leer(texto){
		var est=$('#pq-est',cont);
		return core.paqueteLeer(texto).then(function(r){
			if(r.error){ est.innerHTML='<p class="'+(r.faltan?'em-aviso':'em-error')+'">'+esc(r.error)+'</p>'; return r; }
			return importar(r).then(function(res){
				est.innerHTML='<p class="em-ok-txt">'+esc(res.msg)+'</p>'+(res.entrar?'<button type="button" class="em-btn primario" id="pq-entrar">Entrar a «'+esc(res.nombre)+'»</button>':'');
				var b=$('#pq-entrar',est); if(b) b.addEventListener('click', function(){ E.cerrarModal(); E.entrar(res.entrar); });
				return r;
			}, function(x){ est.innerHTML='<p class="em-error">No se importó: '+esc(x&&x.message||x)+'</p>'; return {error:true}; });
		});
	}
	$('#pq-rec',cont).addEventListener('submit', function(e){ e.preventDefault(); leer(this.t.value); });
	var cam=$('#pq-cam',cont); if(cam) cam.addEventListener('click', function(){ escanear(function(t){ var ta=$('#pq-rec',cont).t; if(ta.value.indexOf(t)<0) ta.value+=(ta.value?'\n':'')+t; return leer(ta.value).then(function(r){ return !r.error; }); }); });
}
E.recibirPaquete = function(){
	E.modal('Recibir paquete', '<p class="em-nota">Registros que otra estación preparó en «Campo y sin conexión → Pasar registros sin internet». Si la operación no está en este dispositivo, se crea una copia local.</p><div id="pq-modal">'+FORM_RECIBIR+'</div>', function(c){ montarRecibir($('#pq-modal',c)); });
};
/* Une los registros del paquete con los del dispositivo: los que ya están no se repiten (cada uno tiene su identificador) */
function importar(r){
	var sim=r.tipo==='simulacro';
	var validos=r.eventos.filter(function(e){ return !core.validar({t:e.t, d:e.d}, {sim:sim}); }), malos=r.eventos.length-validos.length+(r.descartados||0);
	return DB.op(r.op).then(function(op){
		if(op && (op.tipo==='simulacro')!==sim) throw new Error(sim?'Es un paquete de simulacro y la operación del dispositivo es real: no se mezclan.':'Es un paquete de una operación real y aquí es un simulacro: no se mezclan.');
		if(!op){ op={codigo:r.op, nombre:(r.nombre||r.op)+' (recibida por paquete)', tipo:sim?'simulacro':'real', zona:'', estado:'abierto', local:true, cursor:0, actor:{rol:'local', permisos:['ver','operar','recursos','coordinar'], nombre:E.yo.ind}}; return DB.guardarOp(op).then(function(){ return op; }); }
		return op;
	}).then(function(op){
		return DB.eventos(op.codigo).then(function(ya){
			var tiene={}; (ya||[]).forEach(function(e){ tiene[e.u]=1; });
			var nuevos=validos.filter(function(e){ return !tiene[e.u]; }).map(function(e){ return {u:e.u, t:e.t, a:e.a, ts:e.ts, d:e.d, s:'p'}; });
			return DB.guardarEventos(op.codigo, nuevos).then(function(){
				if(E.op && E.op.codigo===op.codigo){ E.eventos=E.eventos.concat(nuevos); E.recalcular(); E.refrescar(); }
				var msg=nuevos.length+' registros nuevos, '+(validos.length-nuevos.length)+' ya estaban'+(malos?', '+malos+' no válidos (descartados)':'')+'.';
				var otra=!E.op || E.op.codigo!==op.codigo;
				if(otra) msg+=' Quedaron en la operación «'+op.nombre+'».';
				return {msg:msg, entrar:otra?op.codigo:'', nombre:op.nombre};
			});
		});
	});
}
function escanear(alLeer){
	var det; try { det=new window.BarcodeDetector({formats:['qr_code']}); } catch(e){ E.aviso('Este navegador no puede leer códigos QR.', 'aviso'); return; }
	var flujo=null, vivo=true, vistos={};
	E.modal('Escanear paquete', '<video id="qr-v" class="em-qr-v" playsinline muted></video><p id="qr-e" class="em-nota" aria-live="polite">Apunta a la primera parte.</p><button type="button" class="em-btn" id="qr-fin">Terminar</button>', function(c){
		function parar(){ vivo=false; if(flujo) flujo.getTracks().forEach(function(t){ t.stop(); }); }
		$('#qr-fin',c).addEventListener('click', function(){ parar(); E.cerrarModal(); });
		new MutationObserver(function(_, o){ if($('#em-modal').hidden){ parar(); o.disconnect(); } }).observe($('#em-modal'), {attributes:true});
		navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}}).then(function(s){
			flujo=s; var v=$('#qr-v',c); v.srcObject=s; v.play();
			(function ciclo(){ if(!vivo) return; det.detect(v).then(function(l){
				var t=l.map(function(x){ return x.rawValue; }).filter(function(x){ return /^RAUEM1 /.test(x) && !vistos[x]; })[0];
				if(t){ vistos[t]=1; var m=t.match(/^RAUEM1 \S+ (\d+)\/(\d+)/); $('#qr-e',c).textContent='Leída la parte '+m[1]+' de '+m[2]+' ('+Object.keys(vistos).length+' en total).';
					alLeer(t).then(function(listo){ if(listo){ parar(); E.cerrarModal(); E.aviso('Paquete completo e importado.'); } }); }
			}).catch(function(){}).then(function(){ setTimeout(ciclo, 300); }); })();
		}).catch(function(){ $('#qr-e',c).textContent='No hay permiso para usar la cámara.'; });
	});
}
})();
