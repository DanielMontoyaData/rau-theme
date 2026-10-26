/* Centro de Mando de Emergencias — B. Mapa operativo (Leaflet 1.9.4, servido desde el tema).
 *
 * Capas: incidentes activos e históricos, puestos de mando, estaciones, repetidoras, recursos (última
 * ubicación conocida), hospitales, albergues y puntos de apoyo, zonas afectadas y cierres, puntos sin
 * comunicación y de cobertura incierta, ubicaciones de operadores.
 *
 * Reglas: cada elemento muestra su fuente, hora de observación y verificación. La precisión se dibuja:
 * exacta = punto lleno; aproximada = punto con círculo de incertidumbre; solo municipio = círculo grande
 * punteado; desconocida = no se dibuja y se lista aparte. No hay simulaciones de cobertura ni
 * seguimiento en tiempo real.
 *
 * Mapa base: OpenStreetMap necesita internet (el service worker guarda los mosaicos ya vistos). Sin
 * internet se usa un contorno de Colombia y sus departamentos (Natural Earth, dominio público) que va
 * con la aplicación.
 */
(function(){
'use strict';
var E=window.RauEm, core=E.core, C=E.C, $=E.$, $$=E.$$, esc=E.esc, et=core.et;
var cargando=null, contorno=null;
function cargarLeaflet(){
	if(window.L) return Promise.resolve();
	if(cargando) return cargando;
	cargando=new Promise(function(ok, ko){
		var l=document.createElement('link'); l.rel='stylesheet'; l.href=C.leaflet.css; document.head.appendChild(l);
		var s=document.createElement('script'); s.src=C.leaflet.js; s.onload=ok; s.onerror=function(){ cargando=null; ko(); }; document.head.appendChild(s);
	});
	return cargando;
}
function cargarContorno(){ if(contorno) return Promise.resolve(contorno); return fetch(C.contorno).then(function(r){ return r.json(); }).then(function(j){ contorno=j; return j; }); }

var CAPAS = [
	['inc_act','Incidentes activos',true], ['inc_hist','Incidentes resueltos, cerrados o descartados',false], ['puesto_mando','Puestos de mando',true],
	['estaciones','Estaciones de comunicaciones',true], ['repetidoras','Repetidoras (directorio)',true], ['recursos','Recursos (última ubicación)',true],
	['apoyo','Hospitales, albergues y puntos de apoyo',true], ['zonas','Zonas afectadas y cierres de vía',true], ['sincom','Sin comunicación y cobertura incierta',true], ['operadores','Ubicaciones de operadores',false]
];
var CAPA_DE_PT = {puesto_mando:'puesto_mando', hospital:'apoyo', albergue:'apoyo', punto_apoyo:'apoyo', zona_afectada:'zonas', cierre_via:'zonas', sin_comunicacion:'sincom', cobertura_incierta:'sincom', ubicacion_operador:'operadores'};
var COLOR = {critica:'#f87171', alta:'#fbbf24', media:'#38bdf8', baja:'#94a3b8', disponible:'#4ade80', reportado_disponible:'#fbbf24', no_verificado:'#94a3b8', degradado:'#fb923c', fuera_servicio:'#f87171', desconocido:'#64748b',
	puesto_mando:'#38bdf8', hospital:'#f472b6', albergue:'#a78bfa', punto_apoyo:'#a78bfa', zona_afectada:'#f87171', cierre_via:'#fb923c', sin_comunicacion:'#f87171', cobertura_incierta:'#94a3b8', ubicacion_operador:'#38bdf8', recurso:'#4ade80', repetidora:'#22d3ee'};
var visibles = {}; CAPAS.forEach(function(c){ visibles[c[0]]=c[2]; });
var mapas=[];

/* Monta un mapa en un contenedor. opts: {mini, foco(id)} */
function montar(cont, opts){
	opts=opts||{};
	cont.innerHTML='<p class="em-nota em-mapa-carga">Cargando mapa…</p>';
	return Promise.all([cargarLeaflet(), cargarContorno().catch(function(){ return null; })]).then(function(){
		if(!document.body.contains(cont)) return;
		cont.innerHTML='';
		var L=window.L, m=L.map(cont, {zoomControl:!opts.mini, attributionControl:true, preferCanvas:true}).setView([4.6, -74.3], opts.mini?5:6);
		var osm=L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:18, attribution:'© colaboradores de OpenStreetMap'});
		var sinConexion=L.layerGroup();
		if(contorno) L.geoJSON(contorno, {style:function(f){ return {color:f.properties.vecino?'#475569':'#94a3b8', weight:f.properties.vecino?0.6:1, fill:!f.properties.vecino, fillColor:'#1e293b', fillOpacity:0.35}; }, onEachFeature:function(f, l){ if(!f.properties.vecino) l.bindTooltip(f.properties.n, {sticky:true}); }, interactive:true}).addTo(sinConexion);
		var errores=0, base=navigator.onLine===false?sinConexion:osm;
		osm.on('tileerror', function(){ if(++errores===6 && m.hasLayer(osm)){ m.removeLayer(osm); sinConexion.addTo(m); E.aviso('El mapa base no carga (¿sin internet?): se muestra el contorno guardado.', 'aviso'); } });
		base.addTo(m);
		if(!opts.mini) L.control.layers({'Mapa base (requiere internet)':osm, 'Contorno de Colombia (sin conexión)':sinConexion}, null, {position:'topright'}).addTo(m);
		var grupo=L.layerGroup().addTo(m);
		var reg={m:m, grupo:grupo, cont:cont, opts:opts};
		mapas.push(reg);
		dibujar(reg);
		if(!opts.mini) m.on('click', function(e){ menuPunto(e.latlng.lat, e.latlng.lng); });
		if(opts.foco) enfocar(reg, opts.foco);
		if(window.ResizeObserver) new ResizeObserver(function(){ m.invalidateSize(); }).observe(cont);
		return reg;
	}).catch(function(){ cont.innerHTML='<p class="em-error">No se pudo cargar el mapa en este dispositivo. Abre el centro de mando una vez con internet para guardarlo.</p>'; });
}

/* Todo lo que se dibuja, con su procedencia */
function elementos(){
	var S=E.S, ahora=Date.now(), l=[], sinUbic=[];
	Object.keys(S.inc).forEach(function(k){ var x=S.inc[k]; if(x.fusionadoEn) return;
		var o={capa:core.incidenteActivo(x)?'inc_act':'inc_hist', id:x.id, lat:x.lat, lng:x.lng, precision:x.precision, color:COLOR[x.prio]||'#94a3b8', radio:9, borde:x.verif==='verificado'?'#ffffff':'#000000',
			titulo:x.titulo, lineas:[et('tipo_inc',x.tipo)+' · prioridad del reporte '+et('prio',x.prio).toLowerCase(), et('verif',x.verif)+' · '+et('estado_inc',x.estado), 'Fuente: '+x.fuente, 'Recibido '+core.isoZ(x.recibido)+(x.ocurrio?' · ocurrió '+core.isoZ(x.ocurrio):''), et('precision',x.precision)+(x.lugar?' · '+x.lugar:'')], ir:['incidentes', x.id], calidad:x.verif==='verificado'?'confirmado':'reportado', ts:x.act};
		if(x.lat===undefined) sinUbic.push(o); else l.push(o); });
	Object.keys(S.pts).forEach(function(k){ var x=S.pts[k]; if(!x.activo || x.verif==='descartado') return;
		l.push({capa:CAPA_DE_PT[x.tipo]||'apoyo', id:x.id, lat:x.lat, lng:x.lng, precision:x.precision, color:COLOR[x.tipo], radio:7, cuadro:true, area:x.radio_m, titulo:x.nombre,
			lineas:[et('pt_tipo',x.tipo), et('pt_verif',x.verif), 'Fuente: '+x.fuente, 'Observado '+core.isoZ(x.obs)+' · registró '+x.a, et('precision',x.precision)].concat(x.nota?[x.nota]:[]), calidad:x.verif==='verificado'?'confirmado':'reportado', ts:x.act||x.ts, pt:x.id}); });
	Object.keys(S.est).forEach(function(k){ var x=S.est[k], s=core.estadoEstacion(x, ahora, E.ventana); var o={capa:'estaciones', id:x.ind, lat:x.lat, lng:x.lng, precision:x.precision||'aproximada', color:COLOR[s.e], radio:7, titulo:x.ind+(x.nombre?' · '+x.nombre:''),
		lineas:[et('est_op',s.e)+' ('+et('calidad',s.calidad).toLowerCase()+')', s.motivo||'', 'Último dato '+(s.ts?core.isoZ(s.ts):'—'), (x.medios||[]).join(' · ')], ir:['estaciones', x.ind], calidad:s.calidad, ts:s.ts};
		if(x.lat===undefined) sinUbic.push(o); else l.push(o); });
	Object.keys(S.rec).forEach(function(k){ var x=S.rec[k]; if(x.retirado) return; var o={capa:'recursos', id:x.id, lat:x.ubic&&x.ubic.lat, lng:x.ubic&&x.ubic.lng, precision:'aproximada', color:COLOR.recurso, radio:6, cuadro:true, titulo:x.desc,
		lineas:[et('rec_est',x.estado)+' · '+x.cant+' × '+et('rec_tipo',x.tipo), x.ubic?'Última ubicación conocida '+core.isoZ(x.ubic.ts)+' (no es seguimiento en tiempo real)':'', 'Responsable: '+x.resp], ir:['recursos', x.id], calidad:'reportado', ts:x.ubic&&x.ubic.ts};
		if(o.lat===undefined) { if(x.ubic) sinUbic.push(o); } else l.push(o); });
	(C.repes||[]).forEach(function(r){ var rep=null; Object.keys(S.repe).forEach(function(k){ if(k.indexOf(r.f)>=0) rep=S.repe[k]; }); var s=core.estadoRepetidora(rep, ahora, E.ventana);
		l.push({capa:'repetidoras', id:'rep-'+r.c+r.f, lat:r.lat, lng:r.lng, precision:'exacta', color:rep&&s.e!=='desconocido'?(rep.e==='caida'?'#f87171':rep.e==='aire'?'#4ade80':'#fbbf24'):COLOR.repetidora, radio:6, rombo:true, titulo:r.n+' '+r.f,
			lineas:[(r.c||'')+' · '+r.f+' MHz'+(r.t?' · '+r.t+' Hz':''), rep?(s.e==='desconocido'?'Último reporte vencido ('+et('repe_e',rep.e)+')':et('repe_e',rep.e)+' según '+rep.a+' '+core.isoZ(rep.ts)):'Sin reporte en esta operación: que figure no garantiza que esté en el aire', 'Fuente: registro de repetidoras de RAU'], calidad:rep?s.calidad:'desconocido', ts:rep&&rep.ts});
	});
	return {l:l, sinUbic:sinUbic};
}
function popup(o){
	return '<div class="em-pop"><strong>'+esc(o.titulo)+'</strong><br>'+o.lineas.filter(Boolean).map(esc).join('<br>')+'<br><span class="em-pop-q">Calidad: '+esc(et('calidad',o.calidad))+(o.ts?' · '+esc(E.haceTxt(o.ts)):'')+'</span>'+(o.ir?'<br><button type="button" class="em-link" data-ir="'+o.ir[0]+'" data-id="'+esc(o.ir[1])+'">Ver ficha →</button>':'')
		+(o.pt&&E.puede('operar')&&!E.S.cerrada?'<br><button type="button" class="em-link" data-pt-act="'+o.pt+'">Actualizar o retirar</button>':'')+'</div>';
}
function dibujar(reg){
	var L=window.L; if(!L || !reg.m) return;
	reg.grupo.clearLayers();
	var datos=elementos(); reg.datos=datos;
	datos.l.forEach(function(o){
		if(!visibles[o.capa] || o.lat===undefined) return;
		if(reg.filtroTipo && o.capa.indexOf('inc')===0 && E.S.inc[o.id] && E.S.inc[o.id].tipo!==reg.filtroTipo) return;
		var p=[o.lat, o.lng];
		// Incertidumbre de la ubicación
		if(o.precision==='aproximada' && !o.area) L.circle(p, {radius:1500, color:o.color, weight:1, dashArray:'4 4', fillOpacity:0.05, interactive:false}).addTo(reg.grupo);
		if(o.precision==='municipio') L.circle(p, {radius:8000, color:o.color, weight:1, dashArray:'2 6', fillOpacity:0.04, interactive:false}).addTo(reg.grupo);
		if(o.area) L.circle(p, {radius:o.area, color:o.color, weight:1.5, fillOpacity:0.12, interactive:false}).addTo(reg.grupo);
		var mk = o.cuadro||o.rombo
			? L.marker(p, {icon:L.divIcon({className:'em-mk', html:'<span class="em-mk-'+(o.rombo?'rombo':'cuadro')+'" style="background:'+o.color+'"></span>', iconSize:[14,14]}), keyboard:true, title:o.titulo})
			: L.circleMarker(p, {radius:reg.opts.mini?6:o.radio, color:o.borde||'#0b0f17', weight:2, fillColor:o.color, fillOpacity:o.precision==='exacta'?0.95:0.6});
		mk.bindPopup(popup(o)); mk.addTo(reg.grupo); o.capaLeaflet=mk;
	});
	var leyenda=reg.cont.parentNode && reg.cont.parentNode.querySelector('[data-mapa-sin]');
	if(leyenda) leyenda.innerHTML=datos.sinUbic.length?'<b>Sin ubicación ('+datos.sinUbic.length+'):</b> '+datos.sinUbic.map(function(o){ return o.ir?'<button type="button" class="em-link" data-ir="'+o.ir[0]+'" data-id="'+esc(o.ir[1])+'">'+esc(o.titulo)+'</button>':esc(o.titulo); }).join(' · '):'';
}
function enfocar(reg, id){
	var o=(reg.datos||elementos()).l.filter(function(x){ return x.id===id; })[0];
	if(o && o.lat!==undefined){ reg.m.setView([o.lat, o.lng], 13); if(o.capaLeaflet) o.capaLeaflet.openPopup(); }
}
E.alRefrescar.push(function(){ mapas=mapas.filter(function(r){ return document.body.contains(r.cont); }); mapas.forEach(dibujar); });

/* Clic en el mapa: crear un incidente o un punto en ese lugar */
function menuPunto(lat, lng){
	var puede=E.puede('operar') && !E.S.cerrada;
	E.modal('En este lugar', '<p class="em-mono">'+lat.toFixed(5)+', '+lng.toFixed(5)+' · '+core.locator(lat,lng)+'</p><p class="em-nota">La posición tocada en el mapa es aproximada: confírmala con la fuente.</p><div class="em-acciones">'
		+(puede?'<button type="button" class="em-btn primario" id="mp-inc">Crear incidente aquí</button><button type="button" class="em-btn" id="mp-pt">Agregar punto o zona</button>':'')
		+'<button type="button" class="em-btn" id="mp-copiar">Copiar coordenadas</button></div>', function(c){
		$('#mp-copiar',c).addEventListener('click', function(){ E.copiar(lat.toFixed(5)+', '+lng.toFixed(5)); });
		if(!puede) return;
		$('#mp-inc',c).addEventListener('click', function(){ E.formIncidente({lat:lat, lng:lng, precision:'aproximada'}); });
		$('#mp-pt',c).addEventListener('click', function(){ formPunto(lat, lng); });
	});
}
function formPunto(lat, lng){
	E.modal('Punto en el mapa', '<form class="em-form" id="f-pt" novalidate><label>Tipo<select class="em-campo" name="tipo">'+E.opciones(core.ET.pt_tipo, 'albergue')+'</select></label><label>Nombre<input class="em-campo" name="nombre" required></label>'
		+'<div class="em-dos"><label>Posición<input class="em-campo em-mono" name="pos" required value="'+(lat!==undefined?lat.toFixed(5)+', '+lng.toFixed(5):'')+'"></label><label>Precisión<select class="em-campo" name="precision">'+E.opciones(core.ET.precision, 'aproximada')+'</select></label></div>'
		+'<div class="em-dos"><label>Radio de la zona en metros (opcional)<input class="em-campo" type="number" min="0" name="radio_m"></label><label>Hora de la observación<input class="em-campo" type="datetime-local" name="obs"></label></div>'
		+'<label>Fuente *<input class="em-campo" name="fuente" required placeholder="Quién lo reportó: autoridad, operador, comunidad"></label><label>Incidente relacionado<select class="em-campo" name="inc">'+E.incidentesOpciones('')+'</select></label><label>Nota<input class="em-campo" name="nota"></label>'
		+'<p class="em-nota">Queda «Reportado». Una vía bloqueada o una zona sin comunicación se registran solo con un reporte que lo respalde.</p><button class="em-btn primario">Agregar</button><p class="em-error" id="f-pt-err" role="alert"></p></form>', function(c){
		$('#f-pt',c).addEventListener('submit', function(e){
			e.preventDefault(); var f=this, p=core.parsearPosicion(f.pos.value), err=$('#f-pt-err',c);
			if(!f.nombre.value.trim()){ err.textContent='Falta el nombre.'; return; } if(!p){ err.textContent='Revisa la posición.'; return; } if(!f.fuente.value.trim()){ err.textContent='Indica la fuente.'; return; }
			var d={tipo:f.tipo.value, nombre:f.nombre.value, lat:+p.lat.toFixed(6), lng:+p.lng.toFixed(6), precision:f.precision.value, fuente:f.fuente.value, nota:f.nota.value, inc:f.inc.value};
			if(f.radio_m.value) d.radio_m=+f.radio_m.value; if(f.obs.value) d.obs_ts=new Date(f.obs.value).getTime();
			if(E.registrar('pt', d)){ E.cerrarModal(); E.aviso('Punto agregado.'); }
		});
	});
}
document.addEventListener('click', function(e){
	var b=e.target.closest('[data-pt-act]'); if(!b) return; var x=E.S.pts[b.dataset.ptAct]; if(!x) return;
	var co=E.puede('coordinar');
	E.modal('Actualizar: '+x.nombre, '<form class="em-form" id="f-pa"><fieldset><legend>Verificación</legend>'+E.chips('verif', co?core.ET.pt_verif:{reportado:'Reportado'}, x.verif)+'</fieldset><label class="em-check"><input type="checkbox" name="activo"'+(x.activo?'':' checked')+'> <span>Ya no está vigente (por ejemplo, la vía se reabrió)</span></label><label>Nota<input class="em-campo" name="nota" required></label><button class="em-btn primario">Guardar</button></form>', function(c){
		$('#f-pa',c).addEventListener('submit', function(ev){ ev.preventDefault(); if(!this.nota.value.trim()) return; var d={p:x.id, nota:this.nota.value}; var v=E.valorDe(this,'verif'); if(v!==x.verif) d.verif=v; if(this.activo.checked===x.activo) d.activo=!this.activo.checked; if(E.registrar('pt_act', d)) E.cerrarModal(); });
	});
});

/* Búsqueda: posición escrita, elementos propios y, con internet, lugares de Colombia (Nominatim) */
function buscar(reg, q, salida){
	var p=core.parsearPosicion(q), res=[];
	if(p){ reg.m.setView([p.lat, p.lng], p.metodo==='locator'?11:14); window.L.circleMarker([p.lat,p.lng], {radius:10, color:'#fff', weight:2, fillOpacity:0}).addTo(reg.grupo); salida.innerHTML='<p>'+p.lat.toFixed(5)+', '+p.lng.toFixed(5)+' · '+core.locator(p.lat,p.lng)+'</p>'; return; }
	var n=core.normalizar(q);
	(reg.datos||elementos()).l.forEach(function(o){ if(core.normalizar(o.titulo+' '+o.lineas.join(' ')).indexOf(n)>=0) res.push(o); });
	salida.innerHTML=(res.length?'<div class="em-lista">'+res.slice(0,8).map(function(o, i){ return '<button type="button" class="em-fila em-fila-btn" data-mres="'+i+'"><span>'+esc(o.titulo)+'<small>'+esc(o.lineas[0]||'')+'</small></span></button>'; }).join('')+'</div>':'<p class="em-nota">Nada en la operación con ese nombre.</p>')
		+'<button type="button" class="em-btn" id="mp-nom">Buscar «'+esc(q)+'» en lugares de Colombia (requiere internet)</button>';
	$$('[data-mres]',salida).forEach(function(b){ b.addEventListener('click', function(){ var o=res[+b.dataset.mres]; reg.m.setView([o.lat,o.lng], 13); if(o.capaLeaflet) o.capaLeaflet.openPopup(); }); });
	$('#mp-nom',salida).addEventListener('click', function(){
		var btn=this; btn.disabled=true; btn.textContent='Buscando…';
		fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&countrycodes=co&limit=5&accept-language=es&q='+encodeURIComponent(q)).then(function(r){ return r.json(); }).then(function(l){
			btn.remove();
			salida.insertAdjacentHTML('beforeend', l.length?'<div class="em-lista">'+l.map(function(x, i){ return '<button type="button" class="em-fila em-fila-btn" data-nom="'+i+'"><span>'+esc(x.display_name)+'<small>OpenStreetMap / Nominatim</small></span></button>'; }).join('')+'</div>':'<p class="em-nota">Sin resultados.</p>');
			$$('[data-nom]',salida).forEach(function(b){ b.addEventListener('click', function(){ var x=l[+b.dataset.nom]; reg.m.setView([+x.lat, +x.lon], 13); }); });
		}).catch(function(){ btn.disabled=false; btn.textContent='Sin conexión: no se pudo buscar'; });
	});
}

E.registrarVista('mapa', {nombre:'Mapa operativo', icono:'🗺', fn:function(p){
	var v=E.el('<div class="em-vista-mapa"><div class="em-barra"><h1>Mapa operativo</h1><div class="em-acciones">'+(E.puede('operar')&&!E.S.cerrada?'<button type="button" class="em-btn" id="mp-punto">+ Punto o zona</button>':'')+'<button type="button" class="em-btn" id="mp-geo">Exportar GeoJSON</button></div></div>'
		+'<div class="em-mapa-g"><aside class="em-mapa-panel"><form id="mp-bf" role="search"><label class="sr-only" for="mp-q">Buscar en el mapa</label><input class="em-campo" id="mp-q" type="search" placeholder="Lugar, coordenadas, locator, indicativo…"></form><div id="mp-res"></div>'
		+'<fieldset class="em-capas"><legend>Capas</legend>'+CAPAS.map(function(c){ return '<label class="em-check"><input type="checkbox" data-capa="'+c[0]+'"'+(visibles[c[0]]?' checked':'')+'> <span>'+esc(c[1])+'</span></label>'; }).join('')+'</fieldset>'
		+'<label>Tipo de evento<select class="em-campo" id="mp-tipo">'+E.opciones(core.ET.tipo_inc, '', 'Todos')+'</select></label>'
		+'<div class="em-leyenda"><p><b>Precisión:</b> punto lleno = exacta · con círculo punteado = aproximada · círculo grande = solo municipio. Borde blanco = incidente verificado.</p><p><b>Color de incidentes:</b> <i style="background:#f87171"></i>crítica <i style="background:#fbbf24"></i>alta <i style="background:#38bdf8"></i>media <i style="background:#94a3b8"></i>baja</p><p><b>Estaciones:</b> <i style="background:#4ade80"></i>verificada <i style="background:#fbbf24"></i>reportada <i style="background:#fb923c"></i>degradada <i style="background:#f87171"></i>fuera <i style="background:#64748b"></i>desconocida</p><p class="em-nota">El mapa no simula cobertura de radio ni hace seguimiento en tiempo real.</p></div>'
		+'<p class="em-nota" data-mapa-sin></p></aside><div class="em-mapa-cont"><div id="mp-mapa" class="em-mapa" role="region" aria-label="Mapa de la operación. Toca un lugar para crear un incidente o un punto."></div><p class="em-nota" data-mapa-sin></p></div></div></div>');
	return v;
}, alMontar:function(p){
	var cont=$('#mp-mapa'); if(!cont) return;
	montar(cont, {foco:p.id}).then(function(reg){
		if(!reg) return;
		$('#mp-bf').addEventListener('submit', function(e){ e.preventDefault(); var q=$('#mp-q').value.trim(); if(q) buscar(reg, q, $('#mp-res')); });
		$$('[data-capa]').forEach(function(c){ c.addEventListener('change', function(){ visibles[c.dataset.capa]=c.checked; dibujar(reg); }); });
		$('#mp-tipo').addEventListener('change', function(){ reg.filtroTipo=this.value; dibujar(reg); });
		var b=$('#mp-punto'); if(b) b.addEventListener('click', function(){ var c=reg.m.getCenter(); formPunto(c.lat, c.lng); });
		$('#mp-geo').addEventListener('click', function(){
			var extra=(C.repes||[]).map(function(r){ return {lat:r.lat, lng:r.lng, props:{capa:'repetidora', nombre:r.n, frecuencia:r.f, fuente:'Registro de repetidoras de RAU', nota:'Que figure no garantiza que esté en el aire'}}; });
			E.descargar(E.nombreArchivo('mapa','geojson'), JSON.stringify(core.geojson(E.S, E.op, extra)), 'application/geo+json');
		});
	});
}});
E.mapa={montar:montar};
})();
