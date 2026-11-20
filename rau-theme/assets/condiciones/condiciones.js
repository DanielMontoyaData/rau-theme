/* Condiciones — centro de mando (19.62.0; rediseño 19.63.0). Llena /condiciones/ con /wp-json/rau/v1/condiciones.
   Todas las horas se muestran en la hora local del PUNTO consultado (y UTC en la barra), no en la del navegador.
   Lo que depende del instante (sol, luna, satélites, línea gris) se calcula aquí. La ubicación va redondeada a 0,1°. */
(function(){
'use strict';
var C=window.RAU_COND||{}, $=function(id){ return document.getElementById(id); };
if(!$('cnd')) return;
var R=Math.PI/180, punto=null, datos=null, sitios=null, mapa=null, capas={}, fondo=null, marcaYo=null, radarCuadros=[], radarCapa=null, radarI=0, radarTimer=null, vista='radar', satCache=null, pasadasLista=[], OFF=-18000, proxima=0, salaTimer=null, REFRESCO=5*60000;

/* ── utilidades ── */
function esc(t){ return String(t==null?'':t).replace(/[&<>"']/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function n(v,d){ return v==null||isNaN(v)?'—':Number(v).toLocaleString('es-CO',{maximumFractionDigits:d==null?0:d,minimumFractionDigits:d||0}); }
function guardar(k,v){ try{ localStorage.setItem(k,JSON.stringify(v)); }catch(e){} }
function leer(k){ try{ return JSON.parse(localStorage.getItem(k)||'null'); }catch(e){ return null; } }
function rumbo(g){ return ['N','NE','E','SE','S','SO','O','NO'][Math.round(((g%360)+360)%360/45)%8]; }
function ic(id){ return '<svg class="cnd-ic" aria-hidden="true"><use href="#cnd-i-'+id+'"/></svg>'; }
function pad(x){ return (x<10?'0':'')+x; }
function locator(lat,lon){ lon+=180; lat+=90; return String.fromCharCode(65+Math.floor(lon/20),65+Math.floor(lat/10))+Math.floor(lon%20/2)+Math.floor(lat%10)+String.fromCharCode(97+Math.floor(lon%2*12),97+Math.floor(lat%1*24)); }
function deLocator(l){ l=String(l||'').trim(); if(!/^[A-Ra-r]{2}[0-9]{2}([A-Xa-x]{2})?$/.test(l)) return null; l=l.toUpperCase();
	var lon=(l.charCodeAt(0)-65)*20-180+(+l[2])*2, lat=(l.charCodeAt(1)-65)*10-90+(+l[3]);
	if(l.length===6){ lon+=(l.charCodeAt(4)-65)/12+1/24; lat+=(l.charCodeAt(5)-65)/24+1/48; } else { lon+=1; lat+=.5; }
	return [Math.round(lat*1e4)/1e4, Math.round(lon*1e4)/1e4]; }
/* Horas en la zona del punto */
function enPunto(ms){ return new Date(ms+OFF*1000); }
function fh(ms,seg){ var d=enPunto(ms); return pad(d.getUTCHours())+':'+pad(d.getUTCMinutes())+(seg?':'+pad(d.getUTCSeconds()):''); }
function fdia(ms){ var d=enPunto(ms), hoy=enPunto(Date.now()), dif=Math.round((Date.UTC(d.getUTCFullYear(),d.getUTCMonth(),d.getUTCDate())-Date.UTC(hoy.getUTCFullYear(),hoy.getUTCMonth(),hoy.getUTCDate()))/864e5);
	return dif===0?'hoy':dif===-1?'ayer':dif===1?'mañana':d.toLocaleDateString('es-CO',{timeZone:'UTC',weekday:'short',day:'numeric',month:'short'}); }
function fhd(ms){ var x=fdia(ms); return (x==='hoy'?'':x+' ')+fh(ms); }
function msLocal(t){ var p=String(t).split(/[-T:]/); return Date.UTC(+p[0],+p[1]-1,+p[2],+p[3]||0,+p[4]||0)-OFF*1000; }
function zona(){ var h=-OFF/3600; return 'UTC'+(h>0?'−':'+')+Math.abs(h); }
function cuerpo(id,html){ var s=$(id); if(!s) return; var c=s.querySelector('.cnd-cuerpo'); if(c) c.innerHTML=html; }
function edad(id,txt,viejo){ var e=$(id)&&$(id).querySelector('[data-edad]'); if(!e) return; e.textContent=txt||''; if(viejo) e.setAttribute('data-viejo',''); else e.removeAttribute('data-viejo'); }
function haceTxt(seg){ if(seg==null) return ''; var m=Math.round(seg/60); return m<1?'ahora':m<60?'hace '+m+' min':'hace '+Math.round(m/60)+' h'; }
var PALABRA=['NORMAL','ATENCIÓN','ALERTA'];
function luz(id,nivel,texto){ var el=$('cnd-luz-'+id); if(!el) return; el.dataset.n=nivel; el.querySelector('p').textContent=texto; el.querySelector('.cnd-palabra').textContent=nivel>=0?PALABRA[nivel]:'SIN DATOS'; estadoGeneral(); }

/* ── sol y luna (NOAA, precisión de un minuto) ── */
function diaJuliano(d){ return d.getTime()/86400000+2440587.5; }
function solar(d){
	var jc=(diaJuliano(d)-2451545)/36525, L=(280.46646+jc*(36000.76983+jc*.0003032))%360, M=357.52911+jc*(35999.05029-.0001537*jc), e=.016708634-jc*(.000042037+.0000001267*jc);
	var Cc=Math.sin(M*R)*(1.914602-jc*(.004817+.000014*jc))+Math.sin(2*M*R)*(.019993-.000101*jc)+Math.sin(3*M*R)*.000289, lv=L+Cc-.00569-.00478*Math.sin((125.04-1934.136*jc)*R);
	var eps=23+(26+(21.448-jc*(46.815+jc*(.00059-jc*.001813)))/60)/60+.00256*Math.cos((125.04-1934.136*jc)*R), dec=Math.asin(Math.sin(eps*R)*Math.sin(lv*R))/R;
	var y=Math.pow(Math.tan(eps/2*R),2), eot=4*(y*Math.sin(2*L*R)-2*e*Math.sin(M*R)+4*e*y*Math.sin(M*R)*Math.cos(2*L*R)-.5*y*y*Math.sin(4*L*R)-1.25*e*e*Math.sin(2*M*R))/R;
	return {dec:dec, eot:eot};
}
function salidaPuesta(lat,lon,alt){ // del día de hoy en el punto, en ms
	var hoy=enPunto(Date.now()), base=Date.UTC(hoy.getUTCFullYear(),hoy.getUTCMonth(),hoy.getUTCDate()), s=solar(new Date(base+12*36e5));
	var c=(Math.sin(alt*R)-Math.sin(lat*R)*Math.sin(s.dec*R))/(Math.cos(lat*R)*Math.cos(s.dec*R)); if(c<-1||c>1) return null;
	var ha=Math.acos(c)/R, mediodia=720-4*lon-s.eot;
	return [base+(mediodia-4*ha)*60000, base+(mediodia+4*ha)*60000];
}
function subsolar(d){ var s=solar(d), utc=d.getUTCHours()+d.getUTCMinutes()/60+d.getUTCSeconds()/3600; return [s.dec, ((12-utc-s.eot/60)*15+540)%360-180]; }
function luna(d){ var e=((diaJuliano(d)-2451550.1)/29.530588853)%1; if(e<0) e+=1; var il=(1-Math.cos(e*2*Math.PI))/2;
	return {edad:e, ilum:il, nombre:['Luna nueva','Creciente','Cuarto creciente','Gibosa creciente','Luna llena','Gibosa menguante','Cuarto menguante','Menguante'][Math.round(e*8)%8], dias:e*29.53}; }

/* ── gráficas SVG con cursor ── */
function grafica(cont,cfg){
	var W=Math.max(300,Math.round(cont.clientWidth||cfg.W||640)), H=Math.round((cfg.H||150)*(W<520?1.15:1)), pl=cfg.pl||34, pr=cfg.pr||(cfg.der?34:10), pt=10, pb=20, w=W-pl-pr, h=H-pt-pb, x0=cfg.x0, x1=cfg.x1;
	var X=function(t){ return pl+(t-x0)/(x1-x0)*w; };
	function escala(e){ if(!e) return null; return e.log?function(v){ v=Math.max(e.min,Math.min(e.max,v)); return pt+h-(Math.log10(v)-Math.log10(e.min))/(Math.log10(e.max)-Math.log10(e.min))*h; }:function(v){ return pt+h-(v-e.min)/(e.max-e.min)*h; }; }
	var YI=escala(cfg.izq), YD=escala(cfg.der), s='';
	(cfg.bandas||[]).forEach(function(b){ s+='<rect x="'+X(Math.max(b.x0,x0)).toFixed(1)+'" y="'+pt+'" width="'+Math.max(0,X(Math.min(b.x1,x1))-X(Math.max(b.x0,x0))).toFixed(1)+'" height="'+h+'" style="fill:'+b.color+'"/>'; });
	(cfg.izq.ticks||[]).forEach(function(v){ var y=YI(v); s+='<line class="rejilla" x1="'+pl+'" x2="'+(pl+w)+'" y1="'+y+'" y2="'+y+'"/><text x="'+(pl-4)+'" y="'+(y+3)+'" text-anchor="end">'+(cfg.izq.fmt?cfg.izq.fmt(v):v)+'</text>'; });
	if(cfg.der) (cfg.der.ticks||[]).forEach(function(v){ var y=YD(v); s+='<text x="'+(pl+w+4)+'" y="'+(y+3)+'">'+(cfg.der.fmt?cfg.der.fmt(v):v)+'</text>'; });
	(cfg.hlineas||[]).forEach(function(l){ var y=(l.eje==='der'?YD:YI)(l.v); s+='<line x1="'+pl+'" x2="'+(pl+w)+'" y1="'+y+'" y2="'+y+'" style="stroke:'+l.color+';stroke-dasharray:4 3"/>'+(l.et?'<text x="'+(pl+w-2)+'" y="'+(y-3)+'" text-anchor="end" style="fill:'+l.color+'">'+l.et+'</text>':''); });
	var paso=cfg.pasoX||((x1-x0)>864e5?6*36e5:(x1-x0)>6*36e5?3*36e5:36e5);
	if(cfg.ticksX) cfg.ticksX.forEach(function(t){ var xx=X(t); s+='<line class="rejilla" x1="'+xx+'" x2="'+xx+'" y1="'+pt+'" y2="'+(pt+h)+'"/><text x="'+xx+'" y="'+(H-6)+'" text-anchor="middle">'+esc(cfg.etX(t))+'</text>'; });
	else for(var t=Math.ceil((x0+OFF*1000)/paso)*paso-OFF*1000;t<=x1;t+=paso){ var xx=X(t); s+='<line class="rejilla" x1="'+xx+'" x2="'+xx+'" y1="'+pt+'" y2="'+(pt+h)+'"/><text x="'+xx+'" y="'+(H-6)+'" text-anchor="middle">'+(paso>=864e5||fh(t)==='00:00'?enPunto(t).getUTCDate()+'/'+(enPunto(t).getUTCMonth()+1):fh(t).slice(0,2)+'h')+'</text>'; }
	cfg.series.forEach(function(se){
		var Y=se.eje==='der'?YD:YI, d=se.datos.filter(function(p){ return p[1]!=null&&p[0]>=x0-36e5&&p[0]<=x1+36e5; });
		if(se.tipo==='barras'){ var bw=se.ancho||Math.max(2,w/(Math.max(d.length,1))*.7); d.forEach(function(p){ var y=Y(p[1]), y0=Y(se.base!=null?se.base:(se.eje==='der'?cfg.der.min:cfg.izq.min)); s+='<rect x="'+(X(p[0]+(se.centro||0))-bw/2).toFixed(1)+'" y="'+Math.min(y,y0).toFixed(1)+'" width="'+bw.toFixed(1)+'" height="'+Math.abs(y0-y).toFixed(1)+'" style="fill:'+(typeof se.color==='function'?se.color(p):se.color)+(se.opacidad?';opacity:'+se.opacidad:'')+'"/>'; }); }
		else { var path=''; d.forEach(function(p,i){ var corte=i&&se.corte&&p[0]-d[i-1][0]>se.corte; path+=(i===0||corte?'M':'L')+X(p[0]).toFixed(1)+' '+Y(p[1]).toFixed(1)+' '; });
			if(se.tipo==='area'&&d.length) s+='<path d="'+path+'L'+X(d[d.length-1][0]).toFixed(1)+' '+(pt+h)+' L'+X(d[0][0]).toFixed(1)+' '+(pt+h)+'Z" style="fill:'+se.color+';opacity:.15"/>';
			s+='<path d="'+path+'" style="fill:none;stroke:'+se.color+';stroke-width:'+(se.grosor||2)+(se.guion?';stroke-dasharray:'+se.guion:'')+'" stroke-linejoin="round"/>'; }
	});
	var ah=Date.now(); if(cfg.ahora!==false&&ah>=x0&&ah<=x1) s+='<line class="ahora" x1="'+X(ah)+'" x2="'+X(ah)+'" y1="'+pt+'" y2="'+(pt+h)+'"/><text x="'+(X(ah)+3)+'" y="'+(pt+8)+'" style="fill:hsl(var(--primary))">ahora</text>';
	s+='<line class="eje" x1="'+pl+'" x2="'+(pl+w)+'" y1="'+(pt+h)+'" y2="'+(pt+h)+'"/><line class="cursor" x1="0" x2="0" y1="'+pt+'" y2="'+(pt+h)+'" style="display:none"/>';
	cont.innerHTML='<div class="cnd-graf"><svg viewBox="0 0 '+W+' '+H+'" role="img" aria-label="'+esc(cfg.titulo||'Gráfica')+'">'+s+'</svg><div class="cnd-tip" hidden></div></div>'+(cfg.leyenda?'<div class="cnd-leyenda-g">'+cfg.leyenda.map(function(l){ return '<span><i style="background:'+l[1]+'"></i>'+esc(l[0])+'</span>'; }).join('')+'</div>':'');
	var g=cont.querySelector('.cnd-graf'), svg=g.querySelector('svg'), tip=g.querySelector('.cnd-tip'), cur=svg.querySelector('.cursor');
	function ocultar(){ tip.hidden=true; cur.style.display='none'; }
	function mover(ev){
		var r=svg.getBoundingClientRect(), px=((ev.touches?ev.touches[0].clientX:ev.clientX)-r.left)/r.width*W; if(px<pl||px>pl+w){ ocultar(); return; }
		var t=x0+(px-pl)/w*(x1-x0), filas=[];
		cfg.series.forEach(function(se){ if(se.sinTip) return; var mejor=null; se.datos.forEach(function(p){ if(p[1]==null) return; if(!mejor||Math.abs(p[0]-t)<Math.abs(mejor[0]-t)) mejor=p; }); if(mejor&&Math.abs(mejor[0]-t)<(cfg.tolerancia||3*36e5)) filas.push('<span style="color:'+(typeof se.color==='function'?se.color(mejor):se.color)+'">■</span> '+esc(se.nombre)+': '+(se.fmt?se.fmt(mejor[1]):n(mejor[1],1))+(se.unidad?' '+se.unidad:'')); });
		if(!filas.length){ ocultar(); return; }
		cur.style.display=''; cur.setAttribute('x1',px); cur.setAttribute('x2',px);
		tip.hidden=false; tip.innerHTML='<b>'+(cfg.etTip?cfg.etTip(t):fhd(t))+'</b><br>'+filas.join('<br>'); var izq=(px/W)*r.width; tip.style.left=Math.min(Math.max(0,izq+10),r.width-tip.offsetWidth)+'px'; tip.style.top='4px';
	}
	svg.addEventListener('mousemove',mover); svg.addEventListener('touchmove',mover,{passive:true}); svg.addEventListener('mouseleave',ocultar); svg.addEventListener('touchend',ocultar);
}

/* ── mapa ── */
var FONDOS={
	oscuro:['https://tile.openstreetmap.org/{z}/{x}/{y}.png','© colaboradores de OpenStreetMap','rau-tiles-oscuras'],
	claro:['https://tile.openstreetmap.org/{z}/{x}/{y}.png','© colaboradores de OpenStreetMap'],
	satelite:['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}','Imágenes © Esri, Maxar, Earthstar'],
	relieve:['https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png','© OpenStreetMap, SRTM · estilo © OpenTopoMap']
};
var COLOR_NIVEL=['#16a34a','#f59e0b','#e11d48'];
function iniciarMapa(){
	if(mapa||!window.L) return;
	mapa=L.map('cnd-mapa',{zoomControl:true,worldCopyJump:true}).setView([punto.lat,punto.lon],7);
	var tema=document.documentElement.getAttribute('data-theme')==='light'?'claro':'oscuro';
	document.querySelector('input[name="cnd-fondo"][value="'+tema+'"]').checked=true; ponerFondo(tema);
	['radar','noche','repetidoras','cobertura','sismos','iono','drap','radio'].forEach(function(k){ capas[k]=L.layerGroup(); });
	document.querySelectorAll('[data-capa-mapa]').forEach(function(c){ if(c.checked) capas[c.dataset.capaMapa].addTo(mapa);
		c.addEventListener('change',function(){ if(c.checked) capas[c.dataset.capaMapa].addTo(mapa); else mapa.removeLayer(capas[c.dataset.capaMapa]); lineaRadar(); }); });
	document.querySelectorAll('input[name="cnd-fondo"]').forEach(function(r){ r.addEventListener('change',function(){ ponerFondo(r.value); }); });
	mapa.on('click',function(e){
		var p=L.popup().setLatLng(e.latlng).setContent('<div class="cnd-mono">'+e.latlng.lat.toFixed(2)+', '+e.latlng.lng.toFixed(2)+' · '+locator(e.latlng.lat,e.latlng.lng)+'</div><button type="button" class="cnd-btn pri" style="margin-top:.4rem" id="cnd-aqui">Ver condiciones aquí</button>').openOn(mapa);
		setTimeout(function(){ var b=$('cnd-aqui'); if(b) b.onclick=function(){ mapa.closePopup(p); fijar({lat:e.latlng.lat,lon:e.latlng.lng,nombre:null}); }; },0);
	});
	var Yo=L.Control.extend({options:{position:'topleft'},onAdd:function(){ var a=L.DomUtil.create('a','leaflet-bar cnd-yo-btn'); a.href='#'; a.title='Centrar en mi ubicación'; a.setAttribute('role','button'); a.setAttribute('aria-label','Centrar en mi ubicación'); a.innerHTML=ic('gps');
		L.DomEvent.on(a,'click',function(ev){ L.DomEvent.preventDefault(ev); L.DomEvent.stopPropagation(ev); usarGPS(); }); return a; }});
	mapa.addControl(new Yo());
	var Ley=L.Control.extend({options:{position:'bottomleft'},onAdd:function(){ var d=L.DomUtil.create('div','cnd-leyenda-m');
		d.innerHTML='<div><i style="background:#0ea5e9"></i>Punto consultado</div><div><i style="background:#16a34a"></i>Sitio RAU normal</div><div><i style="background:#f59e0b"></i>Sitio en atención</div><div><i style="background:#e11d48"></i>Sitio en alerta</div><div><i style="background:#f97316"></i>Sismo</div><div><i style="background:#7c3aed;border-radius:2px"></i>Ionosonda (MUF)</div><div><i style="background:#fbbf24;border-radius:0;height:3px;border:0"></i>Línea gris</div>'; return d; }});
	mapa.addControl(new Ley());
	pintarRepetidoras(); pintarNoche(); setInterval(pintarNoche,60000); cargarRadar(); setInterval(cargarRadar,10*60000);
}
function ponerFondo(k){ if(fondo) mapa.removeLayer(fondo); var f=FONDOS[k]; fondo=L.tileLayer(f[0],{attribution:f[1],maxZoom:k==='relieve'?17:19,subdomains:'abc',className:f[2]||''}).addTo(mapa); fondo.bringToBack(); }
function marcar(){
	if(!mapa) return; if(marcaYo) mapa.removeLayer(marcaYo);
	marcaYo=L.marker([punto.lat,punto.lon],{title:'Punto consultado',zIndexOffset:1000,icon:L.divIcon({className:'',html:'<div class="cnd-marca" style="width:22px;height:22px;background:#0ea5e9">●</div>',iconSize:[22,22],iconAnchor:[11,11]})}).addTo(mapa).bindTooltip('Aquí: '+esc(punto.nombre||locator(punto.lat,punto.lon)));
	capas.radio.clearLayers(); L.circle([punto.lat,punto.lon],{radius:50000,color:'#0ea5e9',weight:1.5,fill:false,dashArray:'6 6'}).addTo(capas.radio);
}
function estadoSitio(nombre){ if(!sitios) return -1; for(var i=0;i<sitios.length;i++) if(sitios[i].n===nombre) return Math.max(sitios[i].tormenta?sitios[i].tormenta.nivel:0, sitios[i].viento?sitios[i].viento.nivel:0); return -1; }
function pintarRepetidoras(){
	if(!mapa) return; capas.repetidoras.clearLayers(); capas.cobertura.clearLayers();
	(C.repetidoras||[]).forEach(function(r){
		var nv=estadoSitio(r.n), col=nv>=0?COLOR_NIVEL[nv]:'#16a34a';
		L.marker([r.lat,r.lon],{icon:L.divIcon({className:'',html:'<div class="cnd-marca" style="width:28px;height:28px;background:'+col+'">'+ic('radio')+'</div>',iconSize:[28,28],iconAnchor:[14,14]})})
			.bindPopup('<b>'+esc(r.n)+'</b><br><span class="cnd-mono">'+esc(r.f)+' '+esc(r.o||'')+(r.t?' · tono '+esc(r.t):'')+'</span><br>'+esc(r.c||'')+(nv>0?'<br><b style="color:'+col+'">'+(nv===2?'En alerta':'En atención')+' por el tiempo</b>':'')+(r.url?'<br><a href="'+esc(r.url)+'">Ver ficha</a>':'')).addTo(capas.repetidoras);
		var kmc=4.12*(Math.sqrt(r.h||30)+Math.sqrt(1.5)); // horizonte de radio hasta una portátil a 1,5 m
		L.circle([r.lat,r.lon],{radius:kmc*1000,color:col,weight:1,fillOpacity:.06}).bindTooltip(esc(r.n)+': cobertura estimada ~'+Math.round(kmc)+' km (horizonte de radio, sin relieve)').addTo(capas.cobertura);
	});
}
function pintarNoche(){
	if(!mapa) return; capas.noche.clearLayers();
	var ss=subsolar(new Date()), dec=ss[0], sl=ss[1], linea=[], lon, lat;
	for(lon=-180;lon<=180;lon+=2){ lat=Math.atan(-Math.cos((lon-sl)*R)/Math.tan(dec*R))/R; linea.push([lat,lon]); }
	var polo=dec>0?-90:90;
	L.polygon([[polo,-180]].concat(linea,[[polo,180]]),{stroke:false,fillColor:'#020617',fillOpacity:.38,interactive:false}).addTo(capas.noche);
	L.polyline(linea,{color:'#fbbf24',weight:2,opacity:.85}).addTo(capas.noche).bindTooltip('Línea gris (amanecer y anochecer)',{sticky:true});
	L.circleMarker([dec,sl],{radius:7,color:'#f59e0b',fillColor:'#fde047',fillOpacity:1,weight:2}).addTo(capas.noche).bindTooltip('El sol está en el cenit aquí');
}
function cargarRadar(){
	fetch('https://api.rainviewer.com/public/weather-maps.json').then(function(r){ return r.json(); }).then(function(j){
		var host=j.host||'https://tilecache.rainviewer.com', pasado=(j.radar&&j.radar.past)||[], futuro=(j.radar&&j.radar.nowcast)||[];
		radarCuadros=pasado.concat(futuro).map(function(f,i){ return {t:f.time*1000,url:host+f.path+'/256/{z}/{x}/{y}/2/1_1.png',pron:i>=pasado.length}; });
		if(!radarCuadros.length) return;
		var s=$('cnd-cuadro'); s.max=radarCuadros.length-1; radarI=pasado.length-1; s.value=radarI; mostrarCuadro(radarI); lineaRadar();
	}).catch(function(){ $('cnd-cuadro-h').textContent='sin radar'; });
}
function lineaRadar(){ $('cnd-linea').hidden=vista!=='radar'||!mapa||!mapa.hasLayer(capas.radar)||!radarCuadros.length; }
function mostrarCuadro(i){
	var f=radarCuadros[i]; if(!f||!mapa) return;
	var nueva=L.tileLayer(f.url,{opacity:.72,maxNativeZoom:7,maxZoom:19,attribution:'Radar © RainViewer',zIndex:400});
	nueva.addTo(capas.radar); if(radarCapa){ var vieja=radarCapa; setTimeout(function(){ capas.radar.removeLayer(vieja); },350); } radarCapa=nueva;
	var min=Math.round((Date.now()-f.t)/60000); $('cnd-cuadro-h').textContent=fh(f.t)+(f.pron?' (pronóstico)':min>5?' (−'+min+' min)':' (ahora)');
}
function animar(){
	if(radarTimer){ clearInterval(radarTimer); radarTimer=null; $('cnd-play').textContent='▶'; return; }
	$('cnd-play').textContent='❚❚';
	radarTimer=setInterval(function(){ radarI=(radarI+1)%radarCuadros.length; $('cnd-cuadro').value=radarI; mostrarCuadro(radarI); },700);
}
function pintarCapasDatos(){
	if(!mapa||!datos) return;
	capas.sismos.clearLayers(); (datos.sismos||[]).forEach(function(s){
		L.circleMarker([s.lat,s.lon],{radius:4+s.mag*2.2,color:'#f97316',weight:2,fillColor:'#f97316',fillOpacity:.35})
			.bindPopup('<b>M'+n(s.mag,1)+'</b> · '+esc(s.lugar)+'<br>'+fhd(s.t*1000)+' ('+zona()+') · profundidad '+n(s.prof)+' km · a '+n(s.km)+' km'+(s.url?'<br><a href="'+esc(s.url)+'" target="_blank" rel="noopener">USGS</a>':'')).addTo(capas.sismos);
	});
	capas.iono.clearLayers(); ((datos.iono&&datos.iono.estaciones)||[]).forEach(function(e){
		L.marker([e.lat,e.lon],{icon:L.divIcon({className:'',html:'<div class="cnd-marca" style="width:44px;height:22px;border-radius:6px;background:#7c3aed">'+(e.mufd!=null?n(e.mufd):'—')+'</div>',iconSize:[44,22],iconAnchor:[22,11]})})
			.bindPopup('<b>'+esc(e.n)+'</b><br>MUF(3000) '+n(e.mufd,1)+' MHz · foF2 '+n(e.fof2,1)+' MHz<br>hace '+e.hace+' min').addTo(capas.iono);
	});
	capas.drap.clearLayers(); (datos.drap_mapa||[]).forEach(function(c){
		var col=c[2]>=10?'#e11d48':c[2]>=5?'#f97316':'#facc15';
		L.rectangle([[c[0]-1,c[1]-2],[c[0]+1,c[1]+2]],{stroke:false,fillColor:col,fillOpacity:.35}).bindTooltip('Absorción: se pierde HF por debajo de '+n(c[2],1)+' MHz',{sticky:true}).addTo(capas.drap);
	});
}
function windy(capa){
	var u='https://embed.windy.com/embed2.html?lat='+punto.lat.toFixed(2)+'&lon='+punto.lon.toFixed(2)+'&detailLat='+punto.lat.toFixed(2)+'&detailLon='+punto.lon.toFixed(2)+'&width=650&height=450&zoom=7&level=surface&overlay='+capa+'&product=ecmwf&menu=&message=true&marker=true&calendar=now&pressure=&type=map&location=coordinates&detail=&metricWind=km%2Fh&metricTemp=%C2%B0C&radarRange=-1';
	var f=$('cnd-windy'); if(f.dataset.u!==u){ f.dataset.u=u; f.src=u; }
}
function cambiarVista(v){
	vista=v; document.querySelectorAll('[data-vista]').forEach(function(b){ b.setAttribute('aria-pressed',String(b.dataset.vista===v)); });
	var w=v==='windy'; $('cnd-mapa').style.display=w?'none':''; $('cnd-windy').hidden=!w; $('cnd-windy-capas').hidden=!w; $('cnd-capas-btn').hidden=w; $('cnd-capas').hidden=true; lineaRadar();
	if(w){ var a=document.querySelector('[data-capa][aria-pressed="true"]'); windy(a?a.dataset.capa:'wind'); } else if(mapa) setTimeout(function(){ mapa.invalidateSize(); },50);
}

/* ── barra de mando ── */
var NOMBRES={tormenta:'Tormenta eléctrica',viento:'Viento',hf:'HF',sol:'Llamarada solar',geomag:'Campo magnético',seis:'6 m abierta',sat:'Satélites'};
function estadoGeneral(){
	if(!datos) return;
	var al=[], at=[]; document.querySelectorAll('.cnd-luz').forEach(function(l){ var k=l.id.replace('cnd-luz-',''); if(l.dataset.n==='2') al.push(NOMBRES[k]); else if(l.dataset.n==='1') at.push(NOMBRES[k]); });
	var e=$('cnd-estado');
	if(al.length){ e.dataset.n=2; e.innerHTML=ic('tormenta')+' ALERTA · '+esc(al.join(', ')); }
	else if(at.length){ e.dataset.n=1; e.innerHTML=ic('campana')+' ATENCIÓN · '+esc(at.join(', ')); }
	else { e.dataset.n=0; e.innerHTML=ic('escudo')+' NORMAL · sin alertas'; }
	document.title=(al.length?'⚠ ':'')+'Condiciones · '+(punto.nombre||locator(punto.lat,punto.lon))+' — RAU';
}
function tic(){
	var ahora=Date.now(); $('cnd-reloj-local').textContent=fh(ahora,true); $('cnd-reloj-utc').textContent=new Date(ahora).toISOString().slice(11,19);
	if(proxima){ var s=Math.max(0,Math.round((proxima-ahora)/1000)); $('cnd-cuenta').textContent='↻ '+Math.floor(s/60)+':'+pad(s%60); }
	var p=pasadasLista[0], el=$('cnd-sat-cuenta'); if(p&&el){ var d=Math.round((p.aos-ahora)/1000); el.textContent=d>0?'en '+(d>=3600?Math.floor(d/3600)+' h ':'')+Math.floor(d%3600/60)+' min '+pad(d%60)+' s':(ahora<p.los?'¡pasando ahora!':'ya pasó'); }
}
function pintarFuentes(){
	var f=$('cnd-fuentes'), l=datos.salud||[], et={bien:'al día',viejo:'con datos viejos',falla:'sin responder',respaldo:'en uso como respaldo',sin_datos:'sin consultar aún'};
	f.innerHTML=l.map(function(x){ return '<i data-e="'+x.estado+'"></i>'; }).join('');
	var t=l.map(function(x){ return x.fuente+': '+(et[x.estado]||x.estado)+(x.hace!=null?' ('+haceTxt(x.hace*60)+')':''); }).join('\n');
	f.title=t; f.setAttribute('aria-label','Fuentes de datos: '+t.replace(/\n/g,'; '));
}

/* ── paneles ── */
function pintarSemaforo(){
	var s=datos.semaforo||{};
	['tormenta','viento','hf','sol','geomag','seis'].forEach(function(k){ if(s[k]) luz(k,s[k].nivel,s[k].texto); else luz(k,-1,'Sin datos en este momento.'); });
}
var WMO={0:['Despejado','sol'],1:['Casi despejado','sol'],2:['Parcialmente nublado','nube'],3:['Nublado','nube'],45:['Niebla','nube'],48:['Niebla','nube'],51:['Llovizna','gota'],53:['Llovizna','gota'],55:['Llovizna fuerte','gota'],61:['Lluvia débil','gota'],63:['Lluvia','gota'],65:['Lluvia fuerte','gota'],66:['Lluvia helada','gota'],67:['Lluvia helada','gota'],71:['Nieve','nube'],73:['Nieve','nube'],75:['Nieve','nube'],80:['Chubascos','gota'],81:['Chubascos','gota'],82:['Chubascos fuertes','tormenta'],95:['Tormenta eléctrica','tormenta'],96:['Tormenta con granizo','tormenta'],99:['Tormenta con granizo','tormenta']};
function wmo(c){ return WMO[c]||['—','nube']; }
function pintarAhora(){
	var c=datos.clima, a=datos.aire;
	if(!c){ cuerpo('cnd-ahora','<p class="cnd-vacio">El servicio del tiempo no respondió. Vuelve a intentar en unos minutos.</p>'); edad('cnd-ahora','sin datos',true); return; }
	var t=c.actual, w=wmo(t.cod), aqi=a&&a.aqi!=null?a.aqi:null, aqiTxt=aqi==null?'—':aqi<=50?'buena':aqi<=100?'moderada':aqi<=150?'dañina p/ sensibles':'dañina';
	cuerpo('cnd-ahora','<div class="cnd-ahora">'+ic(w[1])+'<div><div class="grande">'+n(t.temp)+'°</div><div class="cnd-mut cnd-chico">'+esc(w[0])+(t.sensacion!=null?' · sensación '+n(t.sensacion)+'°':'')+'</div></div></div>'
		+'<div class="cnd-kpis">'
		+'<div class="cnd-kpi"><span>Viento</span><b>'+n(t.viento)+'</b><small>km/h'+(t.dir!=null?' del '+rumbo(t.dir):'')+'</small></div>'
		+(t.rafaga!=null?'<div class="cnd-kpi"><span>Ráfagas</span><b>'+n(t.rafaga)+'</b><small>km/h</small></div>':'')
		+'<div class="cnd-kpi"><span>Humedad</span><b>'+n(t.hr)+'%</b><small>relativa</small></div>'
		+'<div class="cnd-kpi"><span>Presión</span><b>'+n(t.presion)+'</b><small>hPa</small></div>'
		+'<div class="cnd-kpi"><span>Nubes</span><b>'+n(t.nubes)+'%</b><small>cobertura</small></div>'
		+(t.cape!=null?'<div class="cnd-kpi"><span>CAPE</span><b>'+n(t.cape)+'</b><small>J/kg · '+(t.cape>=2500?'muy inestable':t.cape>=1000?'inestable':'estable')+'</small></div>':'')
		+(t.vis!=null?'<div class="cnd-kpi"><span>Visibilidad</span><b>'+n(t.vis/1000,1)+'</b><small>km</small></div>':'')
		+(a?'<div class="cnd-kpi"><span>Aire (ICA)</span><b>'+n(aqi)+'</b><small>'+aqiTxt+' · PM2,5 '+n(a.pm25,1)+'</small></div>':'')
		+(a&&a.uv!=null?'<div class="cnd-kpi"><span>UV</span><b>'+n(a.uv,1)+'</b><small>'+(a.uv<3?'bajo':a.uv<6?'moderado':a.uv<8?'alto':'muy alto')+'</small></div>':'')
		+'</div>');
	edad('cnd-ahora',(c.fuente||'Open-Meteo')+' · '+haceTxt(c.edad),c.fuente==='MET Norway'||c.edad>1800);
}
function pintarClima(){
	var c=datos.clima; if(!c||!c.horas||!c.horas.length){ cuerpo('cnd-clima','<p class="cnd-vacio">Sin pronóstico en este momento.</p>'); return; }
	var hs=c.horas.map(function(h){ return {t:msLocal(h.t),h:h}; }), x0=hs[0].t, x1=hs[hs.length-1].t, ts=hs.map(function(x){ return x.h.temp; }).filter(function(v){ return v!=null; });
	var conRaf=hs[0].h.rafaga!=null, tmin=Math.floor(Math.min.apply(null,ts)-1), tmax=Math.ceil(Math.max.apply(null,ts)+1), vmax=Math.max(30,Math.ceil(Math.max.apply(null,hs.map(function(x){ return +(conRaf?x.h.rafaga:x.h.viento)||0; }))/10)*10+10);
	var bandas=[]; hs.forEach(function(x){ if([95,96,99].indexOf(+x.h.cod)>=0) bandas.push({x0:x.t-18e5,x1:x.t+18e5,color:'hsl(var(--al) / .18)'}); else if(x.h.dia===0) bandas.push({x0:x.t-18e5,x1:x.t+18e5,color:'hsl(var(--muted-foreground) / .07)'}); });
	var dias=(c.dias||[]).map(function(d,i){ var x=wmo(d.cod); return '<div class="cnd-kpi" style="text-align:center"><span>'+(i?new Date(d.fecha+'T12:00:00Z').toLocaleDateString('es-CO',{timeZone:'UTC',weekday:'long'}):'Hoy')+'</span><div style="color:hsl(var(--primary));margin:.2rem 0">'+ic(x[1])+'</div><b style="font-size:1.05rem;white-space:normal">'+n(d.max)+'° / '+n(d.min)+'°</b><small>'+esc(x[0])+' · '+(d.pp!=null?d.pp+' %':n(d.mm,1)+' mm')+(d.rafaga?' · '+n(d.rafaga)+' km/h':'')+'</small></div>'; }).join('');
	cuerpo('cnd-clima','<div id="cnd-clima-g"></div><div class="cnd-kpis" style="grid-template-columns:repeat(3,minmax(0,1fr))">'+dias+'</div>');
	grafica($('cnd-clima-g'),{titulo:'Temperatura, lluvia y viento en 48 horas',W:900,H:190,x0:x0,x1:x1,izq:{min:tmin,max:tmax,ticks:[tmin,Math.round((tmin+tmax)/2),tmax],fmt:function(v){ return v+'°'; }},der:{min:0,max:vmax,ticks:[0,vmax/2,vmax]},
		bandas:bandas, tolerancia:36e5,
		series:[{tipo:'barras',eje:'der',datos:hs.map(function(x){ return [x.t,x.h.pp!=null?x.h.pp/100*vmax:(x.h.mm?Math.min(vmax,x.h.mm*vmax/10):0)]; }),color:'hsl(200 90% 55% / .45)',nombre:'Lluvia',fmt:function(v){ return Math.round(v/vmax*100)+' %'; }},
			{tipo:'linea',eje:'der',datos:hs.map(function(x){ return [x.t,conRaf?x.h.rafaga:x.h.viento]; }),color:'hsl(var(--at))',guion:'4 3',grosor:1.6,nombre:conRaf?'Ráfagas':'Viento',unidad:'km/h',fmt:function(v){ return n(v); }},
			{tipo:'linea',datos:hs.map(function(x){ return [x.t,x.h.temp]; }),color:'hsl(var(--primary))',grosor:2.4,nombre:'Temperatura',unidad:'°C'}],
		leyenda:[['Temperatura (°C, izq.)','hsl(var(--primary))'],[(conRaf?'Ráfagas':'Viento')+' (km/h, der.)','hsl(var(--at))'],['Probabilidad de lluvia','hsl(200 90% 55% / .6)'],['Tormenta prevista','hsl(var(--al) / .5)'],['Noche','hsl(var(--muted-foreground) / .3)']]});
	edad('cnd-clima',(c.fuente||'Open-Meteo')+' · horas '+zona());
}
function pintarSitios(){
	if(!sitios) return;
	if(!sitios.length){ cuerpo('cnd-sitios','<p class="cnd-vacio">No hay repetidoras con coordenadas.</p>'); return; }
	var chip=function(x,txt){ return x?'<span class="cnd-chip '+(['ok','at','al'][x.nivel]||'')+'">'+txt+'</span>':'—'; };
	cuerpo('cnd-sitios','<div class="cnd-tabla-caja"><table class="cnd-tabla"><thead><tr><th>Sitio</th><th class="num">Temp.</th><th class="num cnd-solo-ancho">Lluvia 3 h</th><th>Tormenta</th><th>Viento</th></tr></thead><tbody>'
		+sitios.map(function(s){ var w=wmo(s.cod); return '<tr><td><a href="'+esc(s.url)+'">'+esc(String(s.n).replace(/^Repetidor(a)?\s+/,''))+'</a><div class="cnd-mini cnd-mut cnd-mono">'+esc(s.f)+' · '+esc(w[0])+'</div></td><td class="num">'+n(s.temp)+'°</td><td class="num cnd-solo-ancho">'+(s.lluvia!=null?s.lluvia+' %':'—')+'</td><td>'+chip(s.tormenta,s.tormenta?PALABRA[s.tormenta.nivel]:'')+'</td><td>'+chip(s.viento,s.viento?n(s.viento.kmh)+' km/h':'')+'</td></tr>'; }).join('')+'</tbody></table></div>'
		+'<p class="cnd-mini cnd-mut" style="margin:0">El tiempo en cada repetidora, para saber cuál sitio está en riesgo. Se actualiza cada 10 minutos.</p>');
	edad('cnd-sitios','cada 10 min');
	pintarRepetidoras();
}
var BANDAS=[['160 m',1.85],['80 m',3.7],['60 m',5.35],['40 m',7.1],['30 m',10.12],['20 m',14.2],['17 m',18.1],['15 m',21.2],['12 m',24.9],['10 m',28.5]];
function celda(f,fof2,mufd,hora){ if(fof2==null&&mufd==null) return 'nada'; var dia=hora>=7&&hora<18, nv=fof2!=null&&f<=.9*fof2, dx=mufd!=null&&f<=.85*mufd&&(f>=7||!dia), ab=dia&&f<5;
	if(ab&&(nv||dx)) return 'abs'; return nv&&dx?'ambas':dx?'dx':nv?'nvis':'cerrada'; }
function pintarHF(){
	var e=datos.espacio||{}, io=datos.iono, d=datos.drap, dia=datos.clima?datos.clima.actual.dia:1, h='';
	var cls={Good:['ok','buena'],Fair:['at','regular'],Poor:['al','cerrada']};
	if(e.bandas&&e.bandas.length) h+='<div class="cnd-chips">'+e.bandas.map(function(b){ var x=cls[dia?b.day:b.night]||['','—']; return '<span class="cnd-chip '+x[0]+'">'+esc(String(b.name).replace('m-','–'))+' '+x[1]+'</span>'; }).join('')+'<span class="cnd-mini cnd-mut" style="align-self:center">HamQSL, '+(dia?'de día':'de noche')+' en tu punto</span></div>';
	if(io&&io.iono){ var i=io.iono;
		h+='<div class="cnd-kpis"><div class="cnd-kpi"><span>foF2 · NVIS</span><b>'+n(i.fof2,1)+'</b><small>MHz · '+(io.nvis&&io.nvis.banda?'NVIS en '+esc(io.nvis.banda):'sin NVIS')+'</small></div><div class="cnd-kpi"><span>MUF 3000 km</span><b>'+n(i.mufd,1)+'</b><small>MHz · DX: '+esc((io.dx||[]).slice(0,4).join(', ')||'—')+'</small></div>'
			+'<div class="cnd-kpi"><span>Absorción D-RAP</span><b>'+(d?(d.mhz>=1?n(d.mhz)+' MHz':'0'):'—')+'</b><small>'+(d&&d.mhz>=1?'se pierde por debajo':'sin pérdida extra')+'</small></div><div class="cnd-kpi"><span>Ionosonda</span><b style="font-size:1rem">'+esc(i.nombre.split(',')[0])+'</b><small>a '+n(i.km)+' km · '+haceTxt(i.hace*60)+'</small></div></div>';
	} else h+='<p class="cnd-chico cnd-mut" style="margin:0">Ninguna ionosonda cercana tiene una medición de las últimas 3 horas.</p>';
	var m=io&&io.matriz;
	h+='<div class="cnd-graf-tit"><span>Bandas por hora · próximas 24 h ('+zona()+')</span><span>'+(m&&m.completa?'por persistencia: lo medido ayer a la misma hora':'recolectando mediciones ('+(m?m.con_dato:0)+' de 24 h)')+'</span></div><div class="cnd-tabla-caja"><div class="cnd-matriz" id="cnd-matriz"></div></div>'
		+'<div class="cnd-leyenda-g"><span><i style="background:hsl(var(--ok))"></i>DX</span><span><i style="background:hsl(var(--nv))"></i>NVIS (0–400 km)</span><span><i style="background:linear-gradient(135deg,hsl(var(--ok)) 50%,hsl(var(--nv)) 50%)"></i>DX y NVIS</span><span><i style="background:hsl(var(--at) / .7)"></i>Absorbida de día</span><span><i style="background:hsl(var(--muted-foreground) / .2)"></i>Cerrada</span></div>';
	if(io&&io.medido&&io.medido.texto) h+='<p class="cnd-chico" style="margin:0">'+ic('antena')+' '+esc(io.medido.texto)+'</p>';
	var sp=salidaPuesta(punto.lat,punto.lon,-.833), cv=salidaPuesta(punto.lat,punto.lon,-6);
	if(sp&&cv) h+='<p class="cnd-chico" style="margin:0"><b>Línea gris hoy ('+zona()+'):</b> '+fh(cv[0])+'–'+fh(sp[0]+18e5)+' y '+fh(sp[1]-18e5)+'–'+fh(cv[1])+'. En esos ratos las bandas bajas llegan más lejos.</p>';
	h+='<div id="cnd-muf-g"></div><p class="cnd-chico" style="margin:0"><a href="'+esc(C.herramientas)+'prop/">Condiciones HF completas →</a> · <a href="'+esc(C.receptor)+'">Escuchar en el receptor del club →</a></p>';
	cuerpo('cnd-hf',h);
	var mz=$('cnd-matriz'), horas=((m&&m.horas)||[]).slice(), s='<div></div>';
	if(!horas.length){ var hh=enPunto(Date.now()).getUTCHours(); for(var k=0;k<24;k++) horas.push({h:(hh+k)%24,fof2:null,mufd:null}); }
	if(io&&io.iono) horas[0]={h:horas[0].h,fof2:io.iono.fof2,mufd:io.iono.mufd};
	horas.forEach(function(x){ s+='<div class="h">'+(x.h%3===0?pad(x.h):'')+'</div>'; });
	var NOM={dx:'DX',nvis:'NVIS',ambas:'DX y NVIS',abs:'absorbida de día',cerrada:'cerrada',nada:'sin dato'};
	BANDAS.slice().reverse().forEach(function(b){ s+='<div class="r">'+b[0]+'</div>'; horas.forEach(function(x,i){ var e2=celda(b[1],x.fof2,x.mufd,x.h); s+='<div class="c'+(i===0?' ahora':'')+'" data-e="'+e2+'" title="'+b[0]+' a las '+pad(x.h)+':00: '+NOM[e2]+'"></div>'; }); });
	mz.innerHTML=s;
	var serie=(m&&m.serie)||[];
	if(serie.length>=3) grafica($('cnd-muf-g'),{titulo:'MUF y foF2 de las últimas 24 horas',W:900,H:140,x0:Date.now()-864e5,x1:Date.now(),izq:{min:0,max:Math.max(30,Math.ceil(Math.max.apply(null,serie.map(function(x){ return x[2]||0; }))/5)*5),ticks:[0,10,20,30]},tolerancia:36e5,
		series:[{tipo:'linea',datos:serie.map(function(x){ return [x[0]*1000,x[2]]; }),color:'hsl(var(--ok))',nombre:'MUF 3000 km',unidad:'MHz',corte:2*36e5},{tipo:'linea',datos:serie.map(function(x){ return [x[0]*1000,x[1]]; }),color:'hsl(var(--nv))',nombre:'foF2',unidad:'MHz',corte:2*36e5}],
		hlineas:[{v:14.2,color:'hsl(var(--muted-foreground) / .6)',et:'20 m'},{v:7.1,color:'hsl(var(--muted-foreground) / .6)',et:'40 m'}],
		leyenda:[['MUF 3000 km (MHz)','hsl(var(--ok))'],['foF2 (MHz)','hsl(var(--nv))']]});
	else $('cnd-muf-g').innerHTML='<p class="cnd-mini cnd-mut" style="margin:0">La gráfica de MUF y foF2 de 24 h aparece cuando haya mediciones guardadas (se registran cada 10 minutos).</p>';
	edad('cnd-hf',io&&io.iono?'ionosonda '+haceTxt(io.iono.hace*60):'',io&&io.iono&&io.iono.hace>90);
}
var ESC={R:['Apagones de radio','Sin apagones','Menor: HF débil de día un rato','Moderado: HF cae de día ~1 h','Fuerte: HF cerrado de día ~1 h','Severo: HF cerrado horas','Extremo'],
	S:['Tormenta de protones','Sin tormenta','Menor: afecta HF polar','Moderada','Fuerte','Severa','Extrema'],
	G:['Tormenta geomagnética','Sin tormenta','Menor: HF inestable','Moderada: HF degradado','Fuerte: HF intermitente','Severa','Extrema']};
function pintarEspacio(){
	var e=datos.espacio; if(!e){ cuerpo('cnd-esp','<p class="cnd-vacio">NOAA no respondió.</p>'); return; }
	var kp=e.kp?e.kp.valor:null, v=e.viento||{}, x=e.rayosx||{}, es=e.escalas||{R:0,S:0,G:0};
	var h='<div class="cnd-escalas">'+['R','S','G'].map(function(k){ var s=+es[k]||0; return '<div class="cnd-escala" data-n="'+s+'"><span class="cnd-mini cnd-mut">'+ESC[k][0]+'</span><b>'+k+s+'</b><span class="cnd-mini">'+esc(ESC[k][s+1])+'</span></div>'; }).join('')+'</div>';
	var ko=e.kp_oficial?e.kp_oficial.valor:null;
	h+='<div class="cnd-kpis"><div class="cnd-kpi"><span>Kp estimado ahora</span><b style="color:'+(kp>=5?'hsl(var(--al))':kp>=4?'hsl(var(--at))':'hsl(var(--ok))')+'">'+n(kp,1)+'</b><small>'+(ko!=null?'oficial (3 h): '+n(ko,1)+' · ':'')+(e.kp_pron!=null?'máx. 24 h: '+n(e.kp_pron,1):'')+'</small></div>'
		+'<div class="cnd-kpi"><span>Rayos X</span><b>'+esc(x.actual||'—')+'</b><small>'+(x.max?'pico '+esc(x.max)+(x.max_hora?' a las '+fh(new Date(x.max_hora).getTime()):''):'')+'</small></div>'
		+'<div class="cnd-kpi"><span>Flujo solar</span><b>'+n(e.sfi)+'</b><small>SFI'+(e.ssn!=null?' · manchas '+n(e.ssn):'')+'</small></div>'
		+'<div class="cnd-kpi"><span>Viento solar</span><b>'+n(v.vel)+'</b><small>km/s · '+n(v.dens,1)+' p/cm³</small></div>'
		+'<div class="cnd-kpi"><span>Bz</span><b style="color:'+(v.bz<=-10?'hsl(var(--al))':v.bz<0?'hsl(var(--at))':'inherit')+'">'+n(v.bz,1)+'</b><small>nT · '+(v.bz==null?'—':v.bz<=-10?'muy al sur':v.bz<0?'al sur':'al norte')+'</small></div></div>';
	if(es.prob_r!=null) h+='<p class="cnd-chico" style="margin:0">Probabilidad de apagón de radio hoy: menor '+es.prob_r+' % · fuerte '+es.prob_rx+' %.</p>';
	h+=(e.alertas&&e.alertas.length)?'<ul class="cnd-lista cnd-scroll" style="max-height:10rem">'+e.alertas.map(function(a){ return '<li><span>'+esc(a.es||a.texto)+'</span><span class="der cnd-mut cnd-mini">'+fhd(new Date(a.hora).getTime())+'</span></li>'; }).join('')+'</ul>':'<p class="cnd-chico cnd-mut" style="margin:0">NOAA no ha emitido avisos en las últimas 24 horas.</p>';
	cuerpo('cnd-esp',h); edad('cnd-esp','NOAA SWPC');
}
function pintarTendencias(){
	var t=datos.tend; if(!t){ cuerpo('cnd-tend','<p class="cnd-vacio">Sin series de NOAA.</p>'); return; }
	cuerpo('cnd-tend','<div class="cnd-graf-tit"><span>Índice Kp · 24 h y pronóstico de 3 días</span></div><div id="cnd-g-kp"></div><div class="cnd-graf-tit"><span>Rayos X del sol (GOES) · 6 h</span><span>escala logarítmica</span></div><div id="cnd-g-rx"></div><div class="cnd-graf-tit"><span>Viento solar y Bz · 6 h</span></div><div id="cnd-g-vs"></div><div id="cnd-g-bz"></div>'
		+(t.llamaradas&&t.llamaradas.length?'<div class="cnd-graf-tit"><span>Llamaradas de los últimos 7 días</span></div><div class="cnd-chips">'+t.llamaradas.map(function(f){ return '<span class="cnd-chip '+(f.clase[0]==='X'?'al':f.clase[0]==='M'?'at':'')+'">'+esc(f.clase)+' · '+fhd(f.t*1000)+'</span>'; }).join('')+'</div>':''));
	var kp=(t.kp||[]).map(function(x){ return [x[0]*1000,x[1]]; }), kpp=(t.kp_pron||[]).map(function(x){ return [x[0]*1000,x[1]]; }), todos=kp.concat(kpp);
	if(todos.length){ var col=function(p){ return p[1]>=5?'hsl(var(--al))':p[1]>=4?'hsl(var(--at))':'hsl(var(--ok))'; };
		grafica($('cnd-g-kp'),{titulo:'Índice Kp',W:900,H:130,x0:todos[0][0],x1:todos[todos.length-1][0]+3*36e5,pasoX:864e5,izq:{min:0,max:9,ticks:[0,3,5,7,9]},hlineas:[{v:5,color:'hsl(var(--al) / .7)',et:'tormenta (G1)'}],tolerancia:2*36e5,
			series:[{tipo:'barras',datos:kp,color:col,nombre:'Kp medido',centro:15e5,base:0},{tipo:'barras',datos:kpp,color:col,opacidad:.4,nombre:'Kp pronosticado',centro:15e5,base:0}],
			leyenda:[['Medido','hsl(var(--ok))'],['Pronóstico (más claro)','hsl(var(--ok) / .4)']]}); }
	var rx=(t.rayosx||[]).map(function(x){ return [x[0]*1000,x[1]]; });
	if(rx.length) grafica($('cnd-g-rx'),{titulo:'Flujo de rayos X',W:900,H:130,x0:Date.now()-6*36e5,x1:Date.now(),izq:{min:1e-8,max:1e-3,log:true,ticks:[1e-8,1e-7,1e-6,1e-5,1e-4],fmt:function(v){ return ['A','B','C','M','X'][Math.round(Math.log10(v))+8]||''; }},
		hlineas:[{v:1e-5,color:'hsl(var(--at) / .8)',et:'M'},{v:1e-4,color:'hsl(var(--al) / .8)',et:'X'}],tolerancia:6e5,
		series:[{tipo:'area',datos:rx,color:'hsl(var(--at))',nombre:'Rayos X',fmt:function(v){ var e=Math.floor(Math.log10(v)); return e<-8?'<A':(['A','B','C','M','X'][Math.min(4,e+8)])+(v/Math.pow(10,Math.min(e,-4))).toFixed(1); }}]});
	var vs=(t.viento||[]).filter(function(x){ return x[1]!=null; }).map(function(x){ return [x[0]*1000,x[1]]; }), bz=(t.viento||[]).filter(function(x){ return x[2]!=null; }).map(function(x){ return [x[0]*1000,x[2]]; });
	if(vs.length) grafica($('cnd-g-vs'),{titulo:'Velocidad del viento solar',W:900,H:110,x0:Date.now()-6*36e5,x1:Date.now(),izq:{min:250,max:Math.max(700,Math.ceil(Math.max.apply(null,vs.map(function(x){ return x[1]; }))/100)*100),ticks:[300,500,700]},hlineas:[{v:500,color:'hsl(var(--at) / .6)',et:'rápido'}],tolerancia:9e5,
		series:[{tipo:'linea',datos:vs,color:'hsl(var(--primary))',nombre:'Viento solar',unidad:'km/s'}]});
	if(bz.length) grafica($('cnd-g-bz'),{titulo:'Bz del campo magnético interplanetario',W:900,H:110,x0:Date.now()-6*36e5,x1:Date.now(),izq:{min:-20,max:20,ticks:[-20,-10,0,10,20]},hlineas:[{v:0,color:'hsl(var(--muted-foreground) / .7)'},{v:-10,color:'hsl(var(--al) / .7)',et:'Bz muy al sur'}],tolerancia:9e5,
		series:[{tipo:'linea',datos:bz,color:'hsl(var(--al))',nombre:'Bz',unidad:'nT'}],leyenda:[['Viento solar (km/s)','hsl(var(--primary))'],['Bz (nT): negativo sostenido = tormenta probable','hsl(var(--al))']]});
	edad('cnd-tend','NOAA SWPC · '+zona());
}
function pintarSismos(){
	var s=datos.sismos;
	if(s==null){ cuerpo('cnd-sis','<p class="cnd-vacio">El USGS no respondió.</p>'); return; }
	cuerpo('cnd-sis',(s.length?'<ul class="cnd-lista cnd-scroll">'+s.map(function(x){ return '<li><span><b>M'+n(x.mag,1)+'</b> '+esc(x.lugar)+'<br><span class="cnd-mut cnd-mini">a '+n(x.km)+' km · profundidad '+n(x.prof)+' km</span></span><span class="der cnd-mini cnd-mut">'+fhd(x.t*1000)+'</span></li>'; }).join('')+'</ul>'
		:'<p class="cnd-vacio">Ningún sismo de magnitud 2,5 o más a menos de 500 km en los últimos 7 días.</p>')+'<p class="cnd-mini cnd-mut" style="margin:0">USGS, magnitud 2,5+ y 7 días. El Servicio Geológico Colombiano (botón «SGC sismos») publica también los menores; su servicio de datos no se puede leer desde otros sitios.</p>');
	edad('cnd-sis','USGS · '+zona());
}
function pintarRAU(){
	var r=datos.repetidoras||[], h='';
	h+=r.length?'<ul class="cnd-lista">'+r.map(function(x){ return '<li><span><b>'+esc(x.name)+'</b><br><span class="cnd-mono cnd-mini">'+esc(x.freq)+' '+esc(x.offset||'')+(x.tone?' · tono '+esc(x.tone):'')+'</span></span><span class="der cnd-chico">'+n(x.km)+' km</span></li>'; }).join('')+'</ul>':'<p class="cnd-vacio">Sin repetidoras con coordenadas.</p>';
	h+='<p class="cnd-chico" id="cnd-kiwi" style="margin:0">'+ic('radio')+' Receptor del club: consultando…</p>';
	$('cnd-rau-cont').className=''; $('cnd-rau-cont').innerHTML=h;
	fetch(C.ajax+'?action=rau_sdr_status').then(function(x){ return x.json(); }).then(function(j){
		var el=$('cnd-kiwi'); if(!el) return;
		el.innerHTML=ic('radio')+' '+(j.success?'Receptor del club en línea · '+(j.data.libres===0?'lleno, pero igual puedes entrar':j.data.libres+' de '+j.data.max+' canales libres')+' · <a href="'+esc(C.receptor)+'">Abrir</a>':'Receptor del club: fuera de línea ahora.');
	}).catch(function(){});
}
var METEOROS=[['Cuadrántidas',1,3,110],['Líridas',4,22,18],['Eta Acuáridas',5,6,50],['Delta Acuáridas',7,30,25],['Perseidas',8,12,100],['Dracónidas',10,8,10],['Oriónidas',10,21,20],['Táuridas',11,12,5],['Leónidas',11,17,15],['Gemínidas',12,14,150],['Úrsidas',12,22,10]];
function pintarVHF(){
	var hoy=new Date(), lista=METEOROS.map(function(m){ var f=new Date(hoy.getFullYear(),m[1]-1,m[2]); if(f<new Date(hoy.getTime()-3*864e5)) f=new Date(hoy.getFullYear()+1,m[1]-1,m[2]); return {n:m[0],f:f,zhr:m[3],dias:Math.round((f-hoy)/864e5)}; }).sort(function(a,b){ return a.f-b.f; }).slice(0,3);
	var s6=(datos.semaforo&&datos.semaforo.seis)||null;
	var met='<div class="cnd-graf-tit"><span>Lluvias de meteoros (dispersión meteórica en 6 y 2 m, MSK144)</span></div><ul class="cnd-lista">'+lista.map(function(m){ return '<li><span>'+esc(m.n)+' <span class="cnd-mut cnd-mini">· hasta '+m.zhr+'/h</span></span><span class="der cnd-mini">'+(Math.abs(m.dias)<=2?'<b>activa ahora</b>':m.f.toLocaleDateString('es-CO',{day:'numeric',month:'short'})+' · en '+m.dias+' d')+'</span></li>'; }).join('')+'</ul>';
	cuerpo('cnd-vhf','<p class="cnd-chico" style="margin:0">'+esc(s6?s6.texto:'Revisando 6 m…')+'</p><div id="cnd-seis"><p class="cnd-mini cnd-mut" style="margin:0">Revisando el cluster DX…</p></div>'+met);
	fetch(C.ajax+'?action=rau_dx_spots').then(function(r){ return r.json(); }).then(function(j){
		var sp=(j&&j.success&&j.data)||[], v=sp.filter(function(s){ var f=parseFloat(s.freq); return (f>=50&&f<=54)||(f>=144&&f<=148); }), el=$('cnd-seis');
		var n6=sp.filter(function(s){ var f=parseFloat(s.freq); return f>=50&&f<=54; }).length, psk=s6&&s6.texto&&/PSK/.test(s6.texto)?(s6.texto.match(/^(\d+)/)||[0,0])[1]*1:0, tot=n6+psk;
		if(n6){ var tx=(psk?s6.texto.replace(/\.$/,'')+' y ':'')+n6+' reporte'+(n6>1?'s':'')+' en 6 m en el cluster ahora.'; luz('seis',tot>=3?2:1,tx); var p0=$('cnd-vhf')&&$('cnd-vhf').querySelector('.cnd-cuerpo p'); if(p0) p0.textContent=tx; }
		if(!el) return;
		el.innerHTML=v.length?'<ul class="cnd-lista">'+v.slice(0,6).map(function(s){ return '<li><span><b class="cnd-mono">'+esc(s.dx)+'</b> <span class="cnd-mut cnd-mini">por '+esc(s.sp)+' · '+esc(s.info||'')+'</span></span><span class="der cnd-mono">'+esc(s.freq)+'</span></li>'; }).join('')+'</ul>'
			:'<p class="cnd-mini cnd-mut" style="margin:0">Ni 6 m ni 2 m tienen reportes en los últimos spots del cluster. Las aperturas de esporádica E duran minutos: llama CQ de todos modos.</p>';
	}).catch(function(){});
	edad('cnd-vhf','PSK Reporter + cluster');
}
/* Satélites: frecuencias de AMSAT para los más usados (confirmar que estén activos en su página de estado) */
var SATF={'ISS':'↓145.800 FM voz · APRS 145.825 · repetidor ↑145.990 (tono 67) ↓437.800','SO-50':'↑145.850 FM (tono 67) ↓436.795','AO-91':'↑435.250 FM (tono 67) ↓145.960','PO-101':'↑437.500 FM (tono 141,3) ↓145.900','AO-27':'↑145.850 FM ↓436.795','RS-44':'lineal ↑145.935–145.995 ↓435.610–435.670 (SSB/CW)','AO-7':'lineal modo B ↑432.125–432.175 ↓145.975–145.925','IO-117':'digipetidor 435.310'};
var SATS=/^(ISS|SO-50|AO-91|AO-27|PO-101|RS-44|IO-117|AO-7)\b/i;
function nombreSat(s){ var m=String(s).match(/ISS|SO-50|AO-91|AO-27|PO-101|RS-44|IO-117|AO-7\b/i); return m?m[0].toUpperCase():String(s).replace(/\s*\(.*\)/,''); }
function pintarCielo(){
	var sp=salidaPuesta(punto.lat,punto.lon,-.833), l=luna(new Date());
	cuerpo('cnd-cielo','<div class="cnd-kpis"><div class="cnd-kpi"><span>Amanecer</span><b>'+(sp?fh(sp[0]):'—')+'</b></div><div class="cnd-kpi"><span>Atardecer</span><b>'+(sp?fh(sp[1]):'—')+'</b></div><div class="cnd-kpi"><span>Luna</span><b>'+Math.round(l.ilum*100)+'%</b><small>'+l.nombre+'</small></div></div>'
		+'<div id="cnd-sats"><p class="cnd-chico cnd-mut" style="margin:0">Calculando pasadas de satélites…</p></div>');
	edad('cnd-cielo',zona());
	pasadas();
}
function cargarSatJs(cb){ if(window.satellite) return cb(); var s=document.createElement('script'); s.src=C.satjs; s.onload=cb; s.onerror=function(){ var el=$('cnd-sats'); if(el) el.innerHTML='<p class="cnd-chico cnd-mut">No se pudo cargar el calculador de órbitas.</p>'; luz('sat',-1,'Sin cálculo de pasadas.'); }; document.head.appendChild(s); }
function polar(p){
	var c=60, s='<svg class="cnd-polar" viewBox="0 0 140 140" role="img" aria-label="Trayectoria de la próxima pasada en el cielo"><g transform="translate(70 70)"><circle r="60"/><circle r="40"/><circle r="20"/><line x1="-60" x2="60" y1="0" y2="0"/><line y1="-60" y2="60" x1="0" x2="0"/><text x="0" y="-63" text-anchor="middle">N</text><text x="64" y="3">E</text><text x="0" y="69" text-anchor="middle">S</text><text x="-68" y="3">O</text>';
	var pts=p.traza.map(function(q){ var r=(90-q[1])/90*c; return [r*Math.sin(q[0]*R), -r*Math.cos(q[0]*R)]; });
	if(pts.length) s+='<path d="M'+pts.map(function(q){ return q[0].toFixed(1)+' '+q[1].toFixed(1); }).join(' L')+'"/><circle class="ini" r="3.5" cx="'+pts[0][0].toFixed(1)+'" cy="'+pts[0][1].toFixed(1)+'"/><circle class="fin" r="3.5" cx="'+pts[pts.length-1][0].toFixed(1)+'" cy="'+pts[pts.length-1][1].toFixed(1)+'"/>';
	return s+'</g></svg>';
}
function pasadas(){
	var obtener=satCache?Promise.resolve(satCache):fetch(C.ajax+'?action=rau_tle').then(function(r){ return r.json(); }).then(function(j){ satCache=(j&&j.success&&j.data)||[]; return satCache; });
	obtener.then(function(tle){ cargarSatJs(function(){
		var sat=window.satellite, obs={latitude:punto.lat*R,longitude:punto.lon*R,height:.5}, inicio=Date.now(), res=[], vistos={};
		tle.filter(function(s){ return SATS.test(s.name); }).forEach(function(s){
			var nm=nombreSat(s.name); if(vistos[nm]) return; vistos[nm]=1;
			var rec; try{ rec=sat.twoline2satrec(s.line1,s.line2); }catch(e){ return; }
			var dentro=false, p=null;
			for(var t=0;t<12*3600;t+=20){
				var d=new Date(inicio+t*1000), pv=sat.propagate(rec,d); if(!pv||!pv.position) continue;
				var la=sat.ecfToLookAngles(obs,sat.eciToEcf(pv.position,sat.gstime(d))), el=la.elevation/R, az=la.azimuth/R;
				if(el>0){ if(!dentro){ dentro=true; p={n:nm,aos:d.getTime(),max:el,az:az,traza:[]}; } p.traza.push([az,el]); if(el>p.max){ p.max=el; p.az=az; } }
				else if(dentro){ dentro=false; p.los=d.getTime(); if(p.max>=10) res.push(p); p=null; }
			}
		});
		res.sort(function(a,b){ return a.aos-b.aos; }); pasadasLista=res;
		var el=$('cnd-sats'); if(!el) return;
		if(!res.length){ el.innerHTML='<p class="cnd-chico cnd-mut" style="margin:0">Ninguna pasada de más de 10° en las próximas 12 horas.</p>'; luz('sat',0,'Sin pasadas buenas en las próximas 12 horas.'); return; }
		var p=res[0];
		el.innerHTML='<div style="display:grid;grid-template-columns:minmax(0,9rem) 1fr;gap:.7rem;align-items:center"><div>'+polar(p)+'</div><div><div class="cnd-mini cnd-mut cnd-mono">PRÓXIMA PASADA</div><b style="font-size:1.1rem">'+esc(p.n)+'</b><div class="cnd-mono" style="font-size:1.05rem;color:hsl(var(--primary))" id="cnd-sat-cuenta"></div><div class="cnd-chico">'+fh(p.aos)+'–'+fh(p.los)+' · máx. '+Math.round(p.max)+'° al '+rumbo(p.az)+'</div>'+(SATF[p.n]?'<div class="cnd-mini cnd-mono cnd-mut">'+esc(SATF[p.n])+'</div>':'')+'</div></div>'
			+'<ul class="cnd-lista">'+res.slice(1,6).map(function(q){ return '<li><span><b>'+esc(q.n)+'</b> <span class="cnd-mut cnd-mini">· '+Math.round(q.max)+'° · '+Math.round((q.los-q.aos)/60000)+' min'+(SATF[q.n]?'<br>'+esc(SATF[q.n]):'')+'</span></span><span class="der cnd-mono">'+fhd(q.aos)+'</span></li>'; }).join('')+'</ul>'
			+'<p class="cnd-mini" style="margin:0"><a href="'+esc(C.herramientas)+'satpass/">Todas las pasadas →</a> · <a href="https://www.amsat.org/status/" target="_blank" rel="noopener">Estado en AMSAT</a> (confirma que esté activo)</p>';
		luz('sat',0,p.n+' pasa a las '+fh(p.aos)+', '+Math.round(p.max)+'° al '+rumbo(p.az)+'.'); tic(); pintarRegistro();
	}); }).catch(function(){ luz('sat',-1,'No se pudieron traer las órbitas.'); });
}
function pintarRegistro(){
	var e=((datos&&datos.eventos)||[]).map(function(x){ return {t:x.t*1000,n:x.nivel,txt:x.texto,fut:!!x.futuro}; });
	pasadasLista.slice(0,2).forEach(function(p){ e.push({t:p.aos,n:0,txt:'Pasada de '+p.n+' ('+Math.round(p.max)+'°)',fut:true}); });
	var sp=salidaPuesta(punto.lat,punto.lon,-.833); if(sp) [[sp[0],'Amanecer: línea gris'],[sp[1],'Atardecer: línea gris']].forEach(function(x){ if(x[0]>Date.now()) e.push({t:x[0],n:0,txt:x[1],fut:true}); });
	var ah=Date.now(), fut=e.filter(function(x){ return x.fut&&x.t>ah; }).sort(function(a,b){ return a.t-b.t; }), pas=e.filter(function(x){ return !(x.fut&&x.t>ah); }).sort(function(a,b){ return b.t-a.t; });
	var fila=function(x){ return '<li data-n="'+x.n+'"'+(x.fut?' data-fut':'')+'><time>'+(fdia(x.t)==='hoy'?'':'<span class="cnd-mini">'+esc(fdia(x.t))+'</span><br>')+fh(x.t)+'</time><span>'+esc(x.txt)+'</span></li>'; };
	cuerpo('cnd-reg','<div class="cnd-scroll">'+(fut.length?'<p class="cnd-reg-sec">Próximo</p><ul class="cnd-lista cnd-reg">'+fut.slice(0,6).map(fila).join('')+'</ul>':'')
		+'<p class="cnd-reg-sec">Últimas 24 horas</p>'+(pas.length?'<ul class="cnd-lista cnd-reg">'+pas.map(fila).join('')+'</ul>':'<p class="cnd-vacio" style="padding:.2rem 0">Sin eventos.</p>')+'</div>');
	edad('cnd-reg','24 h · '+zona());
}

var anchoPrevio=0, tRes=null;
window.addEventListener('resize',function(){ clearTimeout(tRes); tRes=setTimeout(function(){ var w=document.documentElement.clientWidth; if(!datos||Math.abs(w-anchoPrevio)<40) return; anchoPrevio=w; [pintarClima,pintarHF,pintarTendencias,pintarSolar].forEach(function(f){ try{ f(); }catch(e){} }); },250); });

/* ── tormentas solares (NOAA SWPC + NASA DONKI + imágenes de SDO y LASCO) ── */
var solDat=null, solarT=0;
function colKp(v){ return v>=5?'hsl(var(--al))':v>=4?'hsl(var(--at))':'hsl(var(--ok))'; }
function mesMs(tt){ var p=String(tt).split('-'); return Date.UTC(+p[0],+p[1]-1,15); }
function pintarSolar(){
	if(!solDat){ return; }
	var so=solDat, p=so.pron3, tor=so.tormenta||{}, esp=datos&&datos.espacio||{}, g=+(esp.escalas&&esp.escalas.G)||0, s=+(esp.escalas&&esp.escalas.S)||0, r=+(esp.escalas&&esp.escalas.R)||0;
	var nivel=Math.max(g||s||r?2:0, tor.nivel||0), txt=g||s||r?'En curso: '+(g?'tormenta geomagnética G'+g+' ':'')+(s?'tormenta de protones S'+s+' ':'')+(r?'apagón de radio R'+r:''):(tor.viene||'Sin tormentas solares en curso ni previstas en los próximos 3 días.');
	var ts=Date.now(), c1='<div class="cnd-nivel-sol" data-n="'+nivel+'">'+ic(nivel?'cme':'escudo')+'<span><b>'+(nivel===2?'TORMENTA EN CURSO':nivel===1?'ATENCIÓN':'SIN TORMENTA')+'</b> · '+esc(txt)+'</span></div>'
		+'<div class="cnd-sol-img"><figure><img loading="lazy" decoding="async" src="https://sdo.gsfc.nasa.gov/assets/img/latest/latest_512_0193.jpg?'+Math.floor(ts/9e5)+'" alt="El Sol en ultravioleta extremo (193 Å) visto por SDO de la NASA"><figcaption>Sol hoy · SDO 193 Å (llamaradas)</figcaption></figure>'
		+'<figure><img loading="lazy" decoding="async" src="https://services.swpc.noaa.gov/images/animations/lasco-c3/latest.jpg?'+Math.floor(ts/9e5)+'" alt="La corona solar vista por el coronógrafo LASCO C3 de SOHO"><figcaption>Corona · LASCO C3 (eyecciones)</figcaption></figure></div>'
		+(so.regiones&&so.regiones.length?'<div class="cnd-graf-tit"><span>Regiones activas · probabilidad de llamarada hoy</span><span>'+esc(so.regiones_fecha||'')+'</span></div><div class="cnd-tabla-caja"><table class="cnd-tabla"><thead><tr><th>Región</th><th>Clase</th><th class="num">Manchas</th><th class="num">C</th><th class="num">M</th><th class="num">X</th></tr></thead><tbody>'
			+so.regiones.map(function(x){ return '<tr><td class="cnd-mono">'+x.n+' <span class="cnd-mut cnd-mini">'+esc(x.lugar)+'</span></td><td class="cnd-mono">'+esc(x.mag||'—')+'</td><td class="num">'+x.manchas+'</td><td class="num">'+x.pc+'%</td><td class="num" style="color:'+(x.pm>=25?'hsl(var(--at))':'inherit')+'">'+x.pm+'%</td><td class="num" style="color:'+(x.px>=10?'hsl(var(--al))':'inherit')+'">'+x.px+'%</td></tr>'; }).join('')+'</tbody></table></div><p class="cnd-mini cnd-mut" style="margin:0">Clase magnética: α y β simples; γ y δ son las que producen llamaradas fuertes.</p>':'');
	var c2='';
	if(p&&p.kp&&p.kp.length===24){
		c2+='<div class="cnd-graf-tit"><span>Pronóstico de NOAA · Kp cada 3 h (UTC)</span><span>emitido '+fhd(p.emitido*1000)+'</span></div><div class="cnd-kp3"><div></div>'+['00','03','06','09','12','15','18','21'].map(function(x){ return '<div class="h">'+x+'</div>'; }).join('');
		for(var d=0;d<3;d++){ c2+='<div class="d">'+new Date(p.dias[d]*1000).toLocaleDateString('es-CO',{timeZone:'UTC',weekday:'short',day:'numeric'})+'</div>'; for(var k=0;k<8;k++){ var v=p.kp[d*8+k]; v=v?v[1]:null; c2+='<div class="c" style="background:'+(v==null?'transparent':'hsl(var(--'+(v>=5?'al':v>=4?'at':'ok')+') / '+(v>=4?.85:.25)+')')+';color:'+(v>=4?'#fff':'inherit')+'">'+(v==null?'—':n(v,1))+'</div>'; } }
		c2+='</div><div class="cnd-tabla-caja"><table class="cnd-tabla"><thead><tr><th>Probabilidad</th>'+p.dias.map(function(t){ return '<th class="num">'+new Date(t*1000).toLocaleDateString('es-CO',{timeZone:'UTC',weekday:'short',day:'numeric'})+'</th>'; }).join('')+'</tr></thead><tbody>'
			+[['Apagón de radio R1–R2',p.r12],['Apagón fuerte R3 o más',p.r3],['Tormenta de protones S1+',p.s1]].map(function(f){ return f[1]&&f[1].length?'<tr><td>'+f[0]+'</td>'+f[1].map(function(x){ return '<td class="num" style="color:'+(x>=25?'hsl(var(--at))':'inherit')+'">'+x+' %</td>'; }).join('')+'</tr>':''; }).join('')+'</tbody></table></div>'
			+(p.razon_g?'<p class="cnd-mini cnd-mut" style="margin:0">NOAA (en inglés): «'+esc(p.razon_g)+'»</p>':'');
	}
	var cm=(so.cmes||[]).slice().sort(function(a,b){ return (b.tierra&&b.llegada?1:0)-(a.tierra&&a.llegada?1:0)||b.t-a.t; });
	c2+='<div class="cnd-graf-tit"><span>Eyecciones de masa coronal · 10 días (NASA DONKI)</span></div>'+(cm.length?'<ul class="cnd-lista cnd-cme cnd-scroll" style="max-height:15rem">'+cm.map(function(c){ return '<li><span>'+(c.tierra?'<b class="t" style="background:hsl(var(--al));color:#fff">HACIA LA TIERRA</b> ':'')+'<b class="cnd-mono">'+(c.vel?n(c.vel)+' km/s':'velocidad sin medir')+'</b> <span class="cnd-mini cnd-mut">· '+fhd(c.t*1000)+(c.tipo?' · tipo '+esc(c.tipo):'')+'</span>'
		+(c.tierra&&c.llegada?'<br><span class="cnd-chico" style="color:hsl(var(--al))">Llegada estimada: '+fhd(c.llegada*1000)+' ('+zona()+')'+(c.kp?' · Kp esperado '+esc(c.kp):'')+'</span>':'')+'</span><span class="der">'+(c.url?'<a class="cnd-mini" href="'+esc(c.url)+'" target="_blank" rel="noopener">detalle</a>':'')+'</span></li>'; }).join('')+'</ul>':'<p class="cnd-vacio">Sin eyecciones registradas en los últimos 10 días (o NASA no respondió).</p>')
		+'<p class="cnd-mini cnd-mut" style="margin:0">Una eyección (CME) tarda de 1 a 4 días en llegar. Si llega con el campo hacia el sur (Bz negativo), provoca tormenta geomagnética: HF inestable y bandas altas cerradas.</p>';
	var c3='<div class="cnd-graf-tit"><span>Protones ≥10 MeV · 24 h</span><span>S1 = 10 pfu</span></div><div id="cnd-g-pr"></div><div class="cnd-graf-tit"><span>Índice Dst · 3 días</span><span>negativo = tormenta</span></div><div id="cnd-g-dst"></div>'
		+'<div class="cnd-graf-tit"><span>Pronóstico de 27 días (NOAA)</span><span>para planear concursos</span></div><div id="cnd-g-27"></div><div class="cnd-graf-tit"><span>Ciclo solar 25 · manchas por mes</span></div><div id="cnd-g-ciclo"></div>';
	cuerpo('cnd-solar','<div class="cnd-solar-rej"><div style="display:flex;flex-direction:column;gap:.6rem">'+c1+'</div><div style="display:flex;flex-direction:column;gap:.6rem">'+c2+'</div><div style="display:flex;flex-direction:column;gap:.3rem">'+c3+'</div></div>');
	var pr=(so.protones||[]).map(function(x){ return [x[0]*1000,x[1]]; });
	if(pr.length) grafica($('cnd-g-pr'),{titulo:'Protones de 10 MeV o más',H:110,x0:Date.now()-864e5,x1:Date.now(),izq:{min:.01,max:1e4,log:true,ticks:[.1,1,10,100,1000],fmt:function(v){ return v>=1?String(v):v; }},hlineas:[{v:10,color:'hsl(var(--at) / .8)',et:'S1'},{v:100,color:'hsl(var(--al) / .8)',et:'S2'}],tolerancia:18e5,
		series:[{tipo:'area',datos:pr,color:'hsl(var(--nv))',nombre:'Protones ≥10 MeV',unidad:'pfu',fmt:function(v){ return v<1?v.toFixed(2):n(v,1); }}]});
	var ds=(so.dst||[]).map(function(x){ return [x[0]*1000,x[1]]; });
	if(ds.length){ var mn=Math.min(-60,Math.floor(Math.min.apply(null,ds.map(function(x){ return x[1]; }))/20)*20);
		grafica($('cnd-g-dst'),{titulo:'Índice Dst',H:110,x0:ds[0][0],x1:Date.now(),pasoX:864e5,izq:{min:mn,max:40,ticks:[mn,-50,0,40]},hlineas:[{v:-50,color:'hsl(var(--at) / .8)',et:'moderada'},{v:-100,color:'hsl(var(--al) / .8)',et:'intensa'}],tolerancia:36e5,
			series:[{tipo:'linea',datos:ds,color:'hsl(var(--primary))',nombre:'Dst',unidad:'nT'}]}); }
	var o=(so.o27||[]);
	if(o.length){ var tks=o.filter(function(x,i){ return i%4===0; }).map(function(x){ return x[0]*1000; });
		grafica($('cnd-g-27'),{titulo:'Pronóstico de 27 días',H:120,x0:o[0][0]*1000-432e5,x1:o[o.length-1][0]*1000+432e5,ticksX:tks,etX:function(t){ var d=new Date(t); return d.getUTCDate()+'/'+(d.getUTCMonth()+1); },etTip:function(t){ return new Date(t).toLocaleDateString('es-CO',{timeZone:'UTC',weekday:'short',day:'numeric',month:'short'}); },
			izq:{min:0,max:9,ticks:[0,3,5,9]},der:{min:60,max:Math.max(200,Math.ceil(Math.max.apply(null,o.map(function(x){ return x[1]; }))/20)*20),ticks:[100,150]},tolerancia:432e5,ahora:false,
			series:[{tipo:'barras',datos:o.map(function(x){ return [x[0]*1000,x[3]]; }),color:function(q){ return colKp(q[1]); },nombre:'Kp máximo',base:0},{tipo:'linea',eje:'der',datos:o.map(function(x){ return [x[0]*1000,x[1]]; }),color:'hsl(var(--at))',nombre:'Flujo solar',unidad:'sfu'}],
			leyenda:[['Kp máximo del día (izq.)','hsl(var(--ok))'],['Flujo solar 10,7 cm (der.)','hsl(var(--at))']]}); }
	var ob=((so.ciclo&&so.ciclo.obs)||[]), pd=((so.ciclo&&so.ciclo.pred)||[]);
	if(ob.length){ var x0=mesMs(ob[0][0]), x1=mesMs((pd.length?pd[pd.length-1]:ob[ob.length-1])[0]), tk=[]; for(var y=new Date(x0).getUTCFullYear()+1;y<=new Date(x1).getUTCFullYear();y++) tk.push(Date.UTC(y,0,1));
		grafica($('cnd-g-ciclo'),{titulo:'Ciclo solar 25',H:120,x0:x0,x1:x1,ticksX:tk,etX:function(t){ return String(new Date(t).getUTCFullYear()); },etTip:function(t){ return new Date(t).toLocaleDateString('es-CO',{timeZone:'UTC',month:'short',year:'numeric'}); },
			izq:{min:0,max:250,ticks:[0,100,200]},tolerancia:20*864e5,
			bandas:pd.length?[{x0:mesMs(pd[0][0]),x1:x1,color:'hsl(var(--muted-foreground) / .06)'}]:[],
			series:[{tipo:'barras',datos:ob.map(function(x){ return [mesMs(x[0]),x[1]]; }),color:'hsl(var(--at) / .55)',nombre:'Manchas del mes',base:0},
				{tipo:'linea',datos:pd.map(function(x){ return [mesMs(x[0]),x[3]]; }),color:'hsl(var(--muted-foreground) / .6)',guion:'3 3',grosor:1,nombre:'Pronóstico alto'},
				{tipo:'linea',datos:pd.map(function(x){ return [mesMs(x[0]),x[1]]; }),color:'hsl(var(--primary))',nombre:'Pronóstico NOAA'},
				{tipo:'linea',datos:pd.map(function(x){ return [mesMs(x[0]),x[2]]; }),color:'hsl(var(--muted-foreground) / .6)',guion:'3 3',grosor:1,nombre:'Pronóstico bajo'}],
			leyenda:[['Manchas observadas','hsl(var(--at) / .7)'],['Pronóstico de NOAA y su rango','hsl(var(--primary))']]}); }
	edad('cnd-solar','NOAA · NASA · '+zona());
}
function cargarSolar(){
	if(solDat&&Date.now()-solarT<10*60000){ pintarSolar(); return; }
	fetch(C.api+'/solar').then(function(r){ return r.json(); }).then(function(j){ solDat=j; solarT=Date.now(); try{ pintarSolar(); }catch(e){ if(window.console) console.warn('solar',e); } }).catch(function(){ cuerpo('cnd-solar','<p class="cnd-vacio">No se pudo consultar el clima espacial.</p>'); });
}

/* ── ubicación y carga ── */
function textoLugar(){ $('cnd-lugar-txt').textContent=punto.nombre||(datos&&datos.punto&&datos.punto.cerca?'cerca de '+datos.punto.cerca.nombre:punto.lat.toFixed(2)+', '+punto.lon.toFixed(2)); $('cnd-loc').textContent=locator(punto.lat,punto.lon); $('cnd-reloj-local-et').textContent='Hora local · '+zona(); }
function fijar(p,sinCargar){
	punto={lat:+p.lat,lon:+p.lon,nombre:p.nombre||null}; guardar('rau_cond_punto',punto); textoLugar();
	if(mapa){ mapa.setView([punto.lat,punto.lon],Math.max(mapa.getZoom(),7)); marcar(); }
	if(vista==='windy'){ var a=document.querySelector('[data-capa][aria-pressed="true"]'); windy(a?a.dataset.capa:'wind'); }
	var u=new URL(location.href); u.searchParams.set('lat',punto.lat.toFixed(2)); u.searchParams.set('lon',punto.lon.toFixed(2)); u.searchParams.delete('loc'); history.replaceState(null,'',u);
	if(!sinCargar) cargar();
}
function usarGPS(){
	var b=$('cnd-gps'); if(!navigator.geolocation){ alert('Tu navegador no da la ubicación. Busca tu ciudad o escribe tu locator.'); return; }
	b.disabled=true;
	navigator.geolocation.getCurrentPosition(function(p){ b.disabled=false; fijar({lat:p.coords.latitude,lon:p.coords.longitude,nombre:'Tu ubicación'}); },
		function(){ b.disabled=false; alert('No diste permiso de ubicación. Busca tu ciudad o escribe tu locator.'); },{enableHighAccuracy:false,timeout:15000,maximumAge:600000});
}
function cargar(){
	proxima=Date.now()+REFRESCO;
	fetch(C.api+'?lat='+punto.lat.toFixed(1)+'&lon='+punto.lon.toFixed(1)).then(function(r){ if(!r.ok) throw new Error(r.status); return r.json(); }).then(function(j){
		datos=j; OFF=(j.clima&&j.clima.offset!=null)?j.clima.offset:Math.round(punto.lon/15)*3600; textoLugar();
		[pintarFuentes,pintarSemaforo,pintarAhora,pintarClima,pintarHF,pintarEspacio,pintarTendencias,cargarSolar,pintarSismos,pintarRAU,pintarCielo,pintarVHF,pintarRegistro,pintarCapasDatos,estadoGeneral].forEach(function(f){ try{ f(); }catch(e){ if(window.console) console.warn('condiciones',e); } });
		$('cnd-impresion').innerHTML='<b>Hoja de condiciones · RAU</b> · '+esc(punto.nombre||'')+' '+locator(punto.lat,punto.lon)+' ('+punto.lat.toFixed(2)+', '+punto.lon.toFixed(2)+') · '+fhd(Date.now())+' '+zona()+' / '+new Date().toISOString().slice(11,16)+' UTC · '+esc(location.origin)+'/condiciones/';
	}).catch(function(){ var e=$('cnd-estado'); e.dataset.n=-1; e.textContent='Sin conexión con el servidor · reintentando'; });
	fetch(C.api+'/sitios').then(function(r){ return r.json(); }).then(function(s){ sitios=Array.isArray(s)?s:[]; pintarSitios(); }).catch(function(){ cuerpo('cnd-sitios','<p class="cnd-vacio">No se pudo consultar el tiempo de los sitios.</p>'); });
}
function inicio(){
	var q=new URLSearchParams(location.search), g=null;
	if(q.get('lat')&&q.get('lon')) g={lat:+q.get('lat'),lon:+q.get('lon')};
	else if(q.get('loc')&&deLocator(q.get('loc'))){ var l=deLocator(q.get('loc')); g={lat:l[0],lon:l[1],nombre:q.get('loc').toUpperCase()}; }
	else g=leer('rau_cond_punto');
	if(!g){ var est=leer('rau_mi_estacion'); if(est&&est.lat&&est.lng) g={lat:+est.lat,lon:+est.lng,nombre:est.indicativo?'Tu estación':null}; }
	if(!g||isNaN(g.lat)||isNaN(g.lon)) g={lat:C.punto[0],lon:C.punto[1],nombre:'Medellín'};
	fijar(g,true);
	if(window.L) iniciarMapa(); else window.addEventListener('load',iniciarMapa);
	marcar(); cargar(); tic(); setInterval(tic,1000);
	setInterval(function(){ if(!document.hidden&&Date.now()>=proxima) cargar(); },15000);
	if(q.get('sala')==='1') sala(true);
	avisosIniciar();
}

/* ── controles ── */
function alturaMenu(){ var h=document.querySelector('header'), cs=h&&getComputedStyle(h); var top=(h&&cs.position==='sticky'&&cs.display!=='none'&&!document.body.classList.contains('cnd-sala'))?h.offsetHeight:0; $('cnd').style.setProperty('--cnd-top',top+'px'); }
alturaMenu(); window.addEventListener('resize',alturaMenu);
$('cnd-buscar-btn').addEventListener('click',function(){ var b=document.querySelector('.cnd-buscar'), on=!b.classList.contains('abierta'); b.classList.toggle('abierta',on); this.setAttribute('aria-expanded',String(on)); if(on) $('cnd-buscar').focus(); });
$('cnd-gps').addEventListener('click',usarGPS);
var tBus=null, sug=$('cnd-sug');
$('cnd-buscar').addEventListener('input',function(){
	var q=this.value.trim(); clearTimeout(tBus);
	var l=deLocator(q); if(l){ sug.hidden=false; sug.innerHTML='<button type="button" role="option" data-lat="'+l[0]+'" data-lon="'+l[1]+'" data-n="'+esc(q.toUpperCase())+'">Locator '+esc(q.toUpperCase())+'</button>'; return; }
	if(q.length<2){ sug.hidden=true; return; }
	tBus=setTimeout(function(){ fetch(C.api+'/buscar?q='+encodeURIComponent(q)).then(function(r){ return r.json(); }).then(function(l){
		sug.hidden=false; sug.innerHTML=(l&&l.length)?l.map(function(x){ return '<button type="button" role="option" data-lat="'+x.lat+'" data-lon="'+x.lon+'" data-n="'+esc(x.nombre)+'">'+esc(x.nombre)+' <span class="cnd-mut cnd-chico">'+esc(x.zona)+'</span></button>'; }).join(''):'<p class="cnd-mut cnd-chico" style="padding:.6rem .8rem;margin:0">No encontré ese lugar.</p>';
	}).catch(function(){}); },300);
});
$('cnd-buscar').addEventListener('keydown',function(e){ if(e.key==='Enter'){ var b=sug.querySelector('button'); if(b){ e.preventDefault(); b.click(); } } else if(e.key==='Escape') sug.hidden=true; });
sug.addEventListener('click',function(e){ var b=e.target.closest('button'); if(!b) return; sug.hidden=true; $('cnd-buscar').value=''; fijar({lat:+b.dataset.lat,lon:+b.dataset.lon,nombre:b.dataset.n}); });
document.addEventListener('click',function(e){ if(!e.target.closest('.cnd-buscar')) sug.hidden=true; });
$('cnd-recargar').addEventListener('click',cargar);
$('cnd-compartir').addEventListener('click',function(){ var u=location.origin+location.pathname+'?lat='+punto.lat.toFixed(1)+'&lon='+punto.lon.toFixed(1);
	(navigator.share?navigator.share({title:'Condiciones para operar',url:u}):navigator.clipboard.writeText(u).then(function(){ $('cnd-cuenta').textContent='✓ enlace copiado'; })).catch(function(){}); });
$('cnd-imprimir').addEventListener('click',function(){ window.print(); });
document.querySelectorAll('[data-vista]').forEach(function(b){ b.addEventListener('click',function(){ cambiarVista(b.dataset.vista); }); });
document.querySelectorAll('[data-capa]').forEach(function(b){ b.addEventListener('click',function(){ document.querySelectorAll('[data-capa]').forEach(function(x){ x.setAttribute('aria-pressed',String(x===b)); }); windy(b.dataset.capa); }); });
$('cnd-capas-btn').addEventListener('click',function(){ var c=$('cnd-capas'); c.hidden=!c.hidden; this.setAttribute('aria-expanded',String(!c.hidden)); });
$('cnd-full').addEventListener('click',function(){ var el=document.querySelector('.cnd-mapa-caja'); if(document.fullscreenElement) document.exitFullscreen(); else if(el.requestFullscreen) el.requestFullscreen(); });
document.addEventListener('fullscreenchange',function(){ var full=document.fullscreenElement&&document.fullscreenElement.classList.contains('cnd-mapa-caja'); $('cnd-mapa').style.height=full?'calc(100vh - 3.6rem)':''; $('cnd-windy').style.height=full?'calc(100vh - 3.6rem)':''; if(mapa) setTimeout(function(){ mapa.invalidateSize(); },100);
	if(!document.fullscreenElement&&document.body.classList.contains('cnd-sala')) sala(false); });
$('cnd-play').addEventListener('click',animar);
$('cnd-cuadro').addEventListener('input',function(){ if(radarTimer) animar(); radarI=+this.value; mostrarCuadro(radarI); });
/* Modo sala: pantalla completa sin el resto del sitio; el mapa alterna radar y Windy cada minuto */
var ROTA=['cnd-sitios','cnd-solar','cnd-hf','cnd-tend','cnd-clima'], rotaI=0, rotaTimer=null, marcas={};
var PINTA={'cnd-solar':function(){ pintarSolar(); },'cnd-hf':function(){ pintarHF(); },'cnd-tend':function(){ pintarTendencias(); },'cnd-clima':function(){ pintarClima(); },'cnd-sitios':function(){ pintarSitios(); }};
function rotar(){
	var slot=$('cnd-rota'); ROTA.forEach(function(id){ var el=$(id); if(el&&el.parentNode===slot&&marcas[id]) marcas[id].parentNode.insertBefore(el,marcas[id]); });
	var id=ROTA[rotaI%ROTA.length], el=$(id); rotaI++; if(!el) return;
	if(!marcas[id]){ marcas[id]=document.createComment(id); el.parentNode.insertBefore(marcas[id],el); }
	slot.appendChild(el); try{ if(datos&&PINTA[id]) PINTA[id](); }catch(e){}
}
function devolver(){ var slot=$('cnd-rota'); ROTA.forEach(function(id){ var el=$(id); if(el&&el.parentNode===slot&&marcas[id]) marcas[id].parentNode.insertBefore(el,marcas[id]); }); [pintarClima,pintarHF,pintarTendencias,pintarSolar].forEach(function(f){ try{ if(datos) f(); }catch(e){} }); }
function sala(on){
	document.body.classList.toggle('cnd-sala',on); alturaMenu();
	clearInterval(rotaTimer); rotaTimer=null; if(on){ rotar(); rotaTimer=setInterval(rotar,20000); } else devolver(); $('cnd-sala').setAttribute('aria-pressed',String(on)); $('cnd-sala').querySelector('span').textContent=on?'Salir de sala':'Modo sala';
	clearInterval(salaTimer); salaTimer=null;
	if(on){ if(document.documentElement.requestFullscreen&&!document.fullscreenElement) document.documentElement.requestFullscreen().catch(function(){});
		if(radarCuadros.length&&!radarTimer) animar();
		salaTimer=setInterval(function(){ cambiarVista(vista==='radar'?'windy':'radar'); },60000); }
	else if(document.fullscreenElement) document.exitFullscreen().catch(function(){});
	if(mapa) setTimeout(function(){ mapa.invalidateSize(); },200);
}
$('cnd-sala').addEventListener('click',function(){ sala(!document.body.classList.contains('cnd-sala')); });

/* ── avisos al celular: varios puntos, umbral de viento y prueba ── */
function b64(s){ var p='='.repeat((4-s.length%4)%4), r=atob((s+p).replace(/-/g,'+').replace(/_/g,'/')), o=new Uint8Array(r.length); for(var i=0;i<r.length;i++) o[i]=r.charCodeAt(i); return o; }
var miSub=null;
function avisosIniciar(){
	if(!('serviceWorker' in navigator)||!('PushManager' in window)) return;
	navigator.serviceWorker.getRegistration().then(function(reg){ return reg&&reg.pushManager.getSubscription(); }).then(function(s){ if(s){ miSub=s; listarAvisos(); } }).catch(function(){});
}
function listarAvisos(){
	if(!miSub) return;
	fetch(C.api+'/avisos/mios',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({endpoint:miSub.endpoint})}).then(function(r){ return r.json(); }).then(function(l){
		var ul=$('cnd-av-lista'); l=Array.isArray(l)?l:[]; $('cnd-av-prueba').hidden=!l.length;
		ul.innerHTML=l.map(function(a){ return '<li><span><b>'+esc(a.nombre||(a.lat+', '+a.lon))+'</b><br><span class="cnd-mini cnd-mut">'+esc(a.temas.join(', '))+' · viento desde '+a.umbral+' km/h</span></span><span class="der"><button type="button" class="cnd-btn" data-quitar="'+esc(a.id)+'">Quitar</button></span></li>'; }).join('');
	}).catch(function(){});
}
$('cnd-av-lista').addEventListener('click',function(e){ var b=e.target.closest('[data-quitar]'); if(!b||!miSub) return; b.disabled=true;
	fetch(C.api+'/avisos',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({endpoint:miSub.endpoint,id:b.dataset.quitar})}).then(listarAvisos); });
$('cnd-av-prueba').addEventListener('click',function(){ if(!miSub) return; var b=this; b.disabled=true;
	fetch(C.api+'/avisos/prueba',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({endpoint:miSub.endpoint})}).then(function(r){ return r.json(); }).then(function(j){ $('cnd-av-est').textContent=j.ok?'Prueba enviada: debería llegarte en segundos.':(j.error||'No se pudo enviar la prueba.'); }).catch(function(){}).then(function(){ setTimeout(function(){ b.disabled=false; },60000); }); });
$('cnd-av-on').addEventListener('click',function(){
	var b=this, est=$('cnd-av-est'), temas=[].slice.call(document.querySelectorAll('#cnd-av input[type=checkbox]:checked')).map(function(i){ return i.value; });
	if(!temas.length){ est.textContent='Elige al menos un aviso.'; return; }
	if(!('serviceWorker' in navigator)||!('PushManager' in window)){ est.textContent='Este navegador no permite avisos. En iPhone, primero agrega el sitio a la pantalla de inicio.'; return; }
	b.disabled=true; est.textContent='Activando…';
	Notification.requestPermission().then(function(p){ if(p!=='granted') throw new Error('permiso');
		return navigator.serviceWorker.register('/service-worker.js').then(function(){ return navigator.serviceWorker.ready; }); })
	.then(function(reg){ return reg.pushManager.getSubscription().then(function(s){ return s||fetch(C.ajax+'?action=rau_vapid_public_key').then(function(r){ return r.json(); }).then(function(j){ if(!j.success||!j.data.key) throw new Error('vapid'); return reg.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:b64(j.data.key)}); }); }); })
	.then(function(sub){ miSub=sub; return fetch(C.api+'/avisos',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sub:sub.toJSON(),lat:punto.lat,lon:punto.lon,temas:temas,nombre:$('cnd-av-nombre').value.trim()||punto.nombre||'',umbral:+$('cnd-av-umbral').value})}).then(function(r){ return r.json().then(function(j){ if(!r.ok) throw new Error(j.error||'error'); return j; }); }); })
	.then(function(){ b.disabled=false; est.textContent='Listo: avisos activos para este punto.'; $('cnd-av-nombre').value=''; listarAvisos(); })
	.catch(function(e){ b.disabled=false; est.textContent=e.message==='permiso'?'No diste permiso para notificaciones. Puedes darlo en la configuración del navegador.':'No se pudo activar: '+e.message; });
});

inicio();
})();
