/* Condiciones (19.62.0) — llena /condiciones/ con lo que arma /wp-json/rau/v1/condiciones.
   Lo que depende del instante exacto (sol, luna, satélites, línea gris) se calcula aquí, en el navegador.
   Nada de la ubicación se guarda en el servidor: se manda redondeada a 0,1°. */
(function(){
'use strict';
var C=window.RAU_COND||{}, $=function(id){ return document.getElementById(id); };
if(!$('cnd')) return;
var R=Math.PI/180, punto=null, datos=null, mapa=null, capas={}, fondo=null, marcaYo=null, circulo=null, radarCuadros=[], radarCapa=null, radarI=0, radarTimer=null, vista='radar', satCache=null;

/* ── utilidades ── */
function esc(t){ return String(t==null?'':t).replace(/[&<>"']/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function n(v,d){ return v==null||isNaN(v)?'—':Number(v).toLocaleString('es-CO',{maximumFractionDigits:d==null?0:d,minimumFractionDigits:d||0}); }
function guardar(k,v){ try{ localStorage.setItem(k,JSON.stringify(v)); }catch(e){} }
function leer(k){ try{ return JSON.parse(localStorage.getItem(k)||'null'); }catch(e){ return null; } }
function km(a,b){ var x=Math.sin((b[0]-a[0])*R/2), y=Math.sin((b[1]-a[1])*R/2); return Math.round(12742*Math.asin(Math.sqrt(x*x+Math.cos(a[0]*R)*Math.cos(b[0]*R)*y*y))); }
function hora(d,conSeg){ return d.toLocaleTimeString('es-CO',{hour:'numeric',minute:'2-digit',second:conSeg?'2-digit':undefined}); }
function rumbo(g){ return ['N','NE','E','SE','S','SO','O','NO'][Math.round(((g%360)+360)%360/45)%8]; }
function luz(id,nivel,texto){ var el=$('cnd-luz-'+id); if(!el) return; el.dataset.n=nivel; el.querySelector('p').textContent=texto; var p=el.querySelector('.cnd-punto'); p.classList.toggle('cnd-esq', nivel===2); }
function locator(lat,lon){ lon+=180; lat+=90; return String.fromCharCode(65+Math.floor(lon/20),65+Math.floor(lat/10))+Math.floor(lon%20/2)+Math.floor(lat%10)+String.fromCharCode(97+Math.floor(lon%2*12),97+Math.floor(lat%1*24)); }
function deLocator(l){ l=String(l||'').trim(); if(!/^[A-Ra-r]{2}[0-9]{2}([A-Xa-x]{2})?$/.test(l)) return null; l=l.toUpperCase();
	var lon=(l.charCodeAt(0)-65)*20-180+(+l[2])*2, lat=(l.charCodeAt(1)-65)*10-90+(+l[3]);
	if(l.length===6){ lon+=(l.charCodeAt(4)-65)/12+1/24; lat+=(l.charCodeAt(5)-65)/24+1/48; } else { lon+=1; lat+=.5; }
	return [Math.round(lat*1e4)/1e4, Math.round(lon*1e4)/1e4]; }
function caja(id,html){ var s=$(id); var h=s.querySelector('h2'); while(h.nextSibling) s.removeChild(h.nextSibling); s.insertAdjacentHTML('beforeend',html); }

/* ── sol y luna (algoritmo de NOAA, precisión de un minuto) ── */
function diaJuliano(d){ return d.getTime()/86400000+2440587.5; }
function solar(d){
	var jc=(diaJuliano(d)-2451545)/36525, L=(280.46646+jc*(36000.76983+jc*.0003032))%360, M=357.52911+jc*(35999.05029-.0001537*jc), e=.016708634-jc*(.000042037+.0000001267*jc);
	var Cc=Math.sin(M*R)*(1.914602-jc*(.004817+.000014*jc))+Math.sin(2*M*R)*(.019993-.000101*jc)+Math.sin(3*M*R)*.000289, lv=L+Cc-.00569-.00478*Math.sin((125.04-1934.136*jc)*R);
	var eps=23+(26+(21.448-jc*(46.815+jc*(.00059-jc*.001813)))/60)/60+.00256*Math.cos((125.04-1934.136*jc)*R), dec=Math.asin(Math.sin(eps*R)*Math.sin(lv*R))/R;
	var y=Math.pow(Math.tan(eps/2*R),2), eot=4*(y*Math.sin(2*L*R)-2*e*Math.sin(M*R)+4*e*y*Math.sin(M*R)*Math.cos(2*L*R)-.5*y*y*Math.sin(4*L*R)-1.25*e*e*Math.sin(2*M*R))/R;
	return {dec:dec, eot:eot};
}
function salidaPuesta(fecha,lat,lon,alt){ // alt: −0,833 (sol) o −6 (crepúsculo civil); devuelve [salida, puesta] en Date
	var m=Date.UTC(fecha.getUTCFullYear(),fecha.getUTCMonth(),fecha.getUTCDate(),12), s=solar(new Date(m));
	var c=(Math.sin(alt*R)-Math.sin(lat*R)*Math.sin(s.dec*R))/(Math.cos(lat*R)*Math.cos(s.dec*R)); if(c<-1||c>1) return null;
	var ha=Math.acos(c)/R, mediodia=720-4*lon-s.eot;
	return [new Date(m-720*60000+(mediodia-4*ha)*60000), new Date(m-720*60000+(mediodia+4*ha)*60000)];
}
function subsolar(d){ var s=solar(d), utc=d.getUTCHours()+d.getUTCMinutes()/60+d.getUTCSeconds()/3600; return [s.dec, ((12-utc-s.eot/60)*15+540)%360-180]; }
function luna(d){ var edad=((diaJuliano(d)-2451550.1)/29.530588853)%1; if(edad<0) edad+=1; var ilum=(1-Math.cos(edad*2*Math.PI))/2;
	var nombres=['Luna nueva','Creciente','Cuarto creciente','Gibosa creciente','Luna llena','Gibosa menguante','Cuarto menguante','Menguante']; return {edad:edad, ilum:ilum, nombre:nombres[Math.round(edad*8)%8], dias:edad*29.53}; }

/* ── mapa ── */
var FONDOS={
	oscuro:['https://tile.openstreetmap.org/{z}/{x}/{y}.png','© colaboradores de OpenStreetMap','rau-tiles-oscuras'],
	claro:['https://tile.openstreetmap.org/{z}/{x}/{y}.png','© colaboradores de OpenStreetMap'],
	satelite:['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}','Imágenes © Esri, Maxar, Earthstar'],
	relieve:['https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png','© OpenStreetMap, SRTM · estilo © OpenTopoMap']
};
function iniciarMapa(){
	if(mapa||!window.L) return;
	mapa=L.map('cnd-mapa',{zoomControl:true,worldCopyJump:true,attributionControl:true}).setView([punto.lat,punto.lon],7);
	var tema=document.documentElement.getAttribute('data-theme')==='light'?'claro':'oscuro';
	document.querySelector('input[name="cnd-fondo"][value="'+tema+'"]').checked=true;
	ponerFondo(tema);
	['radar','noche','repetidoras','sismos','iono','radio'].forEach(function(k){ capas[k]=L.layerGroup(); });
	document.querySelectorAll('[data-capa-mapa]').forEach(function(c){ if(c.checked) capas[c.dataset.capaMapa].addTo(mapa);
		c.addEventListener('change',function(){ if(c.checked) capas[c.dataset.capaMapa].addTo(mapa); else mapa.removeLayer(capas[c.dataset.capaMapa]); $('cnd-linea').hidden=!$('cnd-mapa').offsetParent||!mapa.hasLayer(capas.radar); }); });
	document.querySelectorAll('input[name="cnd-fondo"]').forEach(function(r){ r.addEventListener('change',function(){ ponerFondo(r.value); }); });
	mapa.on('click',function(e){
		var p=L.popup().setLatLng(e.latlng).setContent('<div class="cnd-mono">'+e.latlng.lat.toFixed(2)+', '+e.latlng.lng.toFixed(2)+' · '+locator(e.latlng.lat,e.latlng.lng)+'</div><button type="button" class="cnd-btn pri" style="margin-top:.4rem" id="cnd-aqui">Ver condiciones aquí</button>').openOn(mapa);
		setTimeout(function(){ var b=$('cnd-aqui'); if(b) b.onclick=function(){ mapa.closePopup(p); fijar({lat:e.latlng.lat,lon:e.latlng.lng,nombre:null}); }; },0);
	});
	pintarRepetidoras(); pintarNoche(); setInterval(pintarNoche,60000); cargarRadar(); setInterval(cargarRadar,10*60000);
}
function ponerFondo(k){ if(fondo) mapa.removeLayer(fondo); var f=FONDOS[k]; fondo=L.tileLayer(f[0],{attribution:f[1],maxZoom:k==='relieve'?17:19,subdomains:'abc',className:f[2]||''}).addTo(mapa); fondo.bringToBack(); }
function marcar(){
	if(!mapa) return;
	if(marcaYo) mapa.removeLayer(marcaYo);
	marcaYo=L.marker([punto.lat,punto.lon],{title:'Punto consultado',icon:L.divIcon({className:'',html:'<div class="cnd-marca" style="width:22px;height:22px;background:#0ea5e9">●</div>',iconSize:[22,22],iconAnchor:[11,11]})}).addTo(mapa).bindTooltip('Aquí: '+(punto.nombre||locator(punto.lat,punto.lon)));
	capas.radio.clearLayers(); L.circle([punto.lat,punto.lon],{radius:50000,color:'#0ea5e9',weight:1.5,fill:false,dashArray:'6 6'}).addTo(capas.radio);
}
function pintarRepetidoras(){
	(C.repetidoras||[]).forEach(function(r){
		L.marker([r.lat,r.lon],{icon:L.divIcon({className:'',html:'<div class="cnd-marca" style="width:26px;height:26px;background:#16a34a">📻</div>',iconSize:[26,26],iconAnchor:[13,13]})})
			.bindPopup('<b>'+esc(r.n)+'</b><br><span class="cnd-mono">'+esc(r.f)+' '+esc(r.o||'')+(r.t?' · tono '+esc(r.t):'')+'</span><br>'+esc(r.c||'')+(r.url?'<br><a href="'+esc(r.url)+'">Ver ficha</a>':'')).addTo(capas.repetidoras);
	});
}
function pintarNoche(){
	if(!mapa) return; capas.noche.clearLayers();
	var ahora=new Date(), ss=subsolar(ahora), dec=ss[0], sl=ss[1], linea=[], i, lon, lat;
	for(lon=-180;lon<=180;lon+=2){ lat=Math.atan(-Math.cos((lon-sl)*R)/Math.tan(dec*R))/R; linea.push([lat,lon]); }
	var polo=dec>0?-90:90, poli=[[polo,-180]].concat(linea,[[polo,180]]);
	L.polygon(poli,{stroke:false,fillColor:'#020617',fillOpacity:.38,interactive:false}).addTo(capas.noche);
	L.polyline(linea,{color:'#fbbf24',weight:2,opacity:.85,interactive:false}).addTo(capas.noche).bindTooltip('Línea gris (amanecer/anochecer)',{sticky:true});
	L.circleMarker([dec,sl],{radius:7,color:'#f59e0b',fillColor:'#fde047',fillOpacity:1,weight:2}).addTo(capas.noche).bindTooltip('El sol está en el cenit aquí');
}
function cargarRadar(){
	fetch('https://api.rainviewer.com/public/weather-maps.json').then(function(r){ return r.json(); }).then(function(j){
		var host=j.host||'https://tilecache.rainviewer.com'; radarCuadros=(j.radar&&j.radar.past||[]).concat(j.radar&&j.radar.nowcast||[]).map(function(f){ return {t:f.time,url:host+f.path+'/256/{z}/{x}/{y}/2/1_1.png'}; });
		if(!radarCuadros.length) return;
		var s=$('cnd-cuadro'); s.max=radarCuadros.length-1; radarI=radarCuadros.length-1; s.value=radarI; mostrarCuadro(radarI);
		$('cnd-linea').hidden=!mapa.hasLayer(capas.radar);
	}).catch(function(){ $('cnd-cuadro-h').textContent='sin radar'; });
}
function mostrarCuadro(i){
	var f=radarCuadros[i]; if(!f) return;
	var nueva=L.tileLayer(f.url,{opacity:.72,maxNativeZoom:7,maxZoom:19,attribution:'Radar © RainViewer',zIndex:400});
	nueva.addTo(capas.radar); if(radarCapa){ var vieja=radarCapa; setTimeout(function(){ capas.radar.removeLayer(vieja); },350); } radarCapa=nueva;
	var d=new Date(f.t*1000), min=Math.round((Date.now()-d)/60000); $('cnd-cuadro-h').textContent=hora(d)+(min>5?' (−'+min+' min)':'');
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
			.bindPopup('<b>M'+n(s.mag,1)+'</b> · '+esc(s.lugar)+'<br>'+new Date(s.t*1000).toLocaleString('es-CO')+' · profundidad '+n(s.prof)+' km · a '+n(s.km)+' km'+(s.url?'<br><a href="'+esc(s.url)+'" target="_blank" rel="noopener">USGS</a>':'')).addTo(capas.sismos);
	});
	capas.iono.clearLayers(); ((datos.iono&&datos.iono.estaciones)||[]).forEach(function(e){
		L.marker([e.lat,e.lon],{icon:L.divIcon({className:'',html:'<div class="cnd-marca" style="width:44px;height:22px;border-radius:6px;background:#7c3aed">'+(e.mufd!=null?n(e.mufd):'—')+'</div>',iconSize:[44,22],iconAnchor:[22,11]})})
			.bindPopup('<b>'+esc(e.n)+'</b><br>MUF(3000) '+n(e.mufd,1)+' MHz · foF2 '+n(e.fof2,1)+' MHz<br>hace '+e.hace+' min').addTo(capas.iono);
	});
}
function windy(capa){
	var u='https://embed.windy.com/embed2.html?lat='+punto.lat.toFixed(2)+'&lon='+punto.lon.toFixed(2)+'&detailLat='+punto.lat.toFixed(2)+'&detailLon='+punto.lon.toFixed(2)+'&width=650&height=450&zoom=7&level=surface&overlay='+capa+'&product=ecmwf&menu=&message=true&marker=true&calendar=now&pressure=&type=map&location=coordinates&detail=&metricWind=km%2Fh&metricTemp=%C2%B0C&radarRange=-1';
	var f=$('cnd-windy'); if(f.dataset.u!==u){ f.dataset.u=u; f.src=u; }
}
function cambiarVista(v){
	vista=v; document.querySelectorAll('[data-vista]').forEach(function(b){ b.setAttribute('aria-pressed',String(b.dataset.vista===v)); });
	var w=v==='windy'; $('cnd-mapa').style.display=w?'none':''; $('cnd-windy').hidden=!w; $('cnd-windy-capas').hidden=!w; $('cnd-capas-btn').hidden=w; $('cnd-capas').hidden=true;
	$('cnd-linea').hidden=w||!mapa||!mapa.hasLayer(capas.radar);
	if(w){ var a=document.querySelector('[data-capa][aria-pressed="true"]'); windy(a?a.dataset.capa:'wind'); } else if(mapa) setTimeout(function(){ mapa.invalidateSize(); },50);
}

/* ── paneles ── */
function pintarSemaforo(){
	var s=datos.semaforo||{};
	['tormenta','viento','hf','sol','geomag'].forEach(function(k){ if(s[k]) luz(k,s[k].nivel,s[k].texto); else luz(k,-1,'Sin datos en este momento.'); });
}
function grafico(horas){
	if(!horas||!horas.length) return '';
	var W=640,H=150,pl=28,pr=8,pt=12,pb=22,w=W-pl-pr,h=H-pt-pb, ts=horas.map(function(x){ return x.temp; }).filter(function(v){ return v!=null; });
	var tmin=Math.floor(Math.min.apply(null,ts)-1), tmax=Math.ceil(Math.max.apply(null,ts)+1), x=function(i){ return pl+i*w/(horas.length-1); }, y=function(t){ return pt+h-(t-tmin)/(tmax-tmin)*h; };
	var barras=horas.map(function(c,i){ var p=c.pp!=null?c.pp:(c.mm?Math.min(100,c.mm*25):0); return p?'<rect x="'+(x(i)-3)+'" y="'+(pt+h-p/100*h)+'" width="6" height="'+(p/100*h)+'" fill="hsl(200 90% 55% / .35)"><title>'+c.t.slice(11,16)+': lluvia '+(c.pp!=null?c.pp+' %':n(c.mm,1)+' mm')+'</title></rect>':''; }).join('');
	var tormentas=horas.map(function(c,i){ return [95,96,99].indexOf(+c.cod)>=0?'<text x="'+x(i)+'" y="'+(pt+8)+'" text-anchor="middle" style="font-size:12px">⚡</text>':''; }).join('');
	var noche=horas.map(function(c,i){ return c.dia===0?'<rect x="'+(x(i)-w/(horas.length-1)/2)+'" y="'+pt+'" width="'+(w/(horas.length-1))+'" height="'+h+'" style="fill:hsl(var(--muted-foreground) / .1)"/>':''; }).join('');
	var linea=horas.map(function(c,i){ return c.temp==null?'':(i?'L':'M')+x(i).toFixed(1)+' '+y(c.temp).toFixed(1); }).join(' ');
	var et=horas.map(function(c,i){ return i%6===0?'<text x="'+x(i)+'" y="'+(H-6)+'" text-anchor="middle">'+c.t.slice(11,13)+'h</text>':''; }).join('');
	return '<svg class="cnd-graf" viewBox="0 0 '+W+' '+H+'" role="img" aria-label="Temperatura y lluvia en las próximas 48 horas">'+noche+barras
		+'<text x="2" y="'+(y(tmax)+4)+'">'+tmax+'°</text><text x="2" y="'+(y(tmin)+4)+'">'+tmin+'°</text>'
		+'<path d="'+linea+'" fill="none" style="stroke:hsl(var(--primary))" stroke-width="2.2" stroke-linejoin="round"/>'+tormentas+et+'</svg>'
		+'<p class="cnd-mut cnd-chico" style="margin:.2rem 0 0">Línea: temperatura · barras: probabilidad de lluvia · ⚡ tormenta · sombreado: noche.</p>';
}
var WMO={0:['Despejado','☀️'],1:['Casi despejado','🌤️'],2:['Parcialmente nublado','⛅'],3:['Nublado','☁️'],45:['Niebla','🌫️'],48:['Niebla','🌫️'],51:['Llovizna','🌦️'],53:['Llovizna','🌦️'],55:['Llovizna fuerte','🌧️'],61:['Lluvia débil','🌦️'],63:['Lluvia','🌧️'],65:['Lluvia fuerte','🌧️'],66:['Lluvia helada','🌧️'],67:['Lluvia helada','🌧️'],71:['Nieve','🌨️'],73:['Nieve','🌨️'],75:['Nieve','🌨️'],80:['Chubascos','🌦️'],81:['Chubascos','🌧️'],82:['Chubascos fuertes','⛈️'],95:['Tormenta','⛈️'],96:['Tormenta con granizo','⛈️'],99:['Tormenta con granizo','⛈️']};
function wmo(c){ return WMO[c]||['—','🌡️']; }
function pintarClima(){
	var c=datos.clima, a=datos.aire;
	if(!c){ caja('cnd-clima','<p class="cnd-vacio">El servicio del tiempo no respondió. Vuelve a intentar en unos minutos.</p>'); return; }
	var t=c.actual, w=wmo(t.cod), aqi=a&&a.aqi!=null?a.aqi:null, aqiTxt=aqi==null?'—':aqi<=50?'Buena':aqi<=100?'Moderada':aqi<=150?'Dañina para sensibles':'Dañina';
	var dias=(c.dias||[]).map(function(d,i){ var x=wmo(d.cod), f=new Date(d.fecha+'T12:00:00'); return '<div class="cnd-dia"><div class="cnd-chico cnd-mut">'+(i?f.toLocaleDateString('es-CO',{weekday:'short',day:'numeric'}):'Hoy')+'</div><div class="i" title="'+esc(x[0])+'">'+x[1]+'</div><div><b>'+n(d.max)+'°</b> <span class="cnd-mut">'+n(d.min)+'°</span></div><div class="cnd-chico cnd-mut">'+(d.pp!=null?'💧'+d.pp+' %':'💧'+n(d.mm,1)+' mm')+'</div></div>'; }).join('');
	caja('cnd-clima','<div class="cnd-ahora"><span class="icono" aria-hidden="true">'+w[1]+'</span><div><div class="grande">'+n(t.temp)+'°</div><div class="cnd-mut">'+esc(w[0])+(t.sensacion!=null?' · sensación '+n(t.sensacion)+'°':'')+'</div></div></div>'
		+'<div class="cnd-kpis">'
		+'<div class="cnd-kpi"><span>Viento</span><b>'+n(t.viento)+'</b><small>km/h'+(t.dir!=null?' del '+rumbo(t.dir):'')+'</small></div>'
		+(t.rafaga!=null?'<div class="cnd-kpi"><span>Ráfagas</span><b>'+n(t.rafaga)+'</b><small>km/h</small></div>':'')
		+'<div class="cnd-kpi"><span>Humedad</span><b>'+n(t.hr)+'%</b><small>relativa</small></div>'
		+'<div class="cnd-kpi"><span>Presión</span><b>'+n(t.presion)+'</b><small>hPa</small></div>'
		+'<div class="cnd-kpi"><span>Nubes</span><b>'+n(t.nubes)+'%</b><small>cobertura</small></div>'
		+(t.cape!=null?'<div class="cnd-kpi"><span>Inestabilidad</span><b>'+n(t.cape)+'</b><small>J/kg (CAPE)</small></div>':'')
		+(t.vis!=null?'<div class="cnd-kpi"><span>Visibilidad</span><b>'+n(t.vis/1000,1)+'</b><small>km</small></div>':'')
		+(a?'<div class="cnd-kpi"><span>Aire</span><b>'+n(aqi)+'</b><small>'+aqiTxt+' · PM2,5 '+n(a.pm25,1)+'</small></div>':'')
		+(a&&a.uv!=null?'<div class="cnd-kpi"><span>UV</span><b>'+n(a.uv,1)+'</b><small>'+(a.uv<3?'bajo':a.uv<6?'moderado':a.uv<8?'alto':'muy alto')+'</small></div>':'')
		+'</div>'+grafico(c.horas)+'<div class="cnd-dias">'+dias+'</div>'
		+'<p class="cnd-mut cnd-chico" style="margin:.6rem 0 0">Fuente: '+esc(c.fuente||'Open-Meteo')+(c.elevacion!=null?' · altura del punto '+n(c.elevacion)+' m':'')+'.</p>');
}
function pintarHF(){
	var e=datos.espacio||{}, io=datos.iono, d=datos.drap, dia=datos.clima?datos.clima.actual.dia:1, h='';
	var cls={Good:['ok','buena'],Fair:['at','regular'],Poor:['al','cerrada']};
	if(e.bandas&&e.bandas.length) h+='<p class="cnd-chico cnd-mut" style="margin:0">Bandas ahora ('+(dia?'de día':'de noche')+' en tu punto):</p><div class="cnd-chips">'+e.bandas.map(function(b){ var x=cls[dia?b.day:b.night]||['','—']; return '<span class="cnd-chip '+x[0]+'">'+esc(String(b.name).replace('m-','–'))+' '+x[1]+'</span>'; }).join('')+'</div>';
	if(io&&io.iono){ var i=io.iono;
		h+='<div class="cnd-kpis"><div class="cnd-kpi"><span>foF2</span><b>'+n(i.fof2,1)+'</b><small>MHz · NVIS</small></div><div class="cnd-kpi"><span>MUF 3000 km</span><b>'+n(i.mufd,1)+'</b><small>MHz · DX</small></div><div class="cnd-kpi"><span>Ionosonda</span><b style="font-size:1rem">'+esc(i.nombre.split(',')[0])+'</b><small>a '+n(i.km)+' km · hace '+i.hace+' min</small></div></div>';
		if(io.nvis) h+='<p class="cnd-chico"><b>NVIS (0–400 km)'+(io.nvis.banda?': '+esc(io.nvis.banda):'')+'.</b> '+esc(io.nvis.texto)+'</p>';
		if(io.dx&&io.dx.length) h+='<p class="cnd-chico"><b>Para DX (hasta 0,85·MUF):</b> '+io.dx.map(esc).join(', ')+'.</p>';
	} else h+='<p class="cnd-chico cnd-mut">Ninguna ionosonda cercana tiene una medición de las últimas 3 horas.</p>';
	if(io&&io.medido&&io.medido.texto) h+='<p class="cnd-chico">📶 '+esc(io.medido.texto)+'</p>';
	if(d) h+='<p class="cnd-chico"><b>Absorción (D-RAP de NOAA) en tu zona:</b> '+(d.mhz>=1?'se pierden las frecuencias por debajo de ~'+n(d.mhz)+' MHz.':'ninguna; las bandas bajas pasan sin pérdida extra.')+'</p>';
	var sp=salidaPuesta(new Date(),punto.lat,punto.lon,-.833), cv=salidaPuesta(new Date(),punto.lat,punto.lon,-6);
	if(sp&&cv) h+='<p class="cnd-chico"><b>Línea gris hoy:</b> '+hora(cv[0])+'–'+hora(new Date(sp[0].getTime()+30*60000))+' y '+hora(new Date(sp[1].getTime()-30*60000))+'–'+hora(cv[1])+'. En esos ratos las bandas bajas llegan más lejos.</p>';
	h+='<p class="cnd-chico" style="margin-bottom:0"><a href="'+esc(C.herramientas)+'prop/">Condiciones HF completas →</a> · <a href="'+esc(C.receptor)+'">Escuchar en el receptor del club →</a></p>';
	caja('cnd-hf',h);
}
var ESC={R:['Apagones de radio','Sin apagones','Menor: HF débil de día un rato','Moderado: HF cae en el lado de día ~1 h','Fuerte: HF cerrado de día ~1 h','Severo: HF cerrado varias horas','Extremo: HF cerrado horas en el lado de día'],
	S:['Tormenta de protones','Sin tormenta','Menor: afecta HF polar','Moderada','Fuerte','Severa','Extrema'],
	G:['Tormenta geomagnética','Sin tormenta','Menor: HF inestable en latitudes altas','Moderada: HF se degrada','Fuerte: HF intermitente','Severa: HF casi cerrado','Extrema: HF cerrado']};
function pintarEspacio(){
	var e=datos.espacio; if(!e){ caja('cnd-esp','<p class="cnd-vacio">NOAA no respondió.</p>'); return; }
	var kp=e.kp?e.kp.valor:null, v=e.viento||{}, x=e.rayosx||{}, es=e.escalas||{R:0,S:0,G:0};
	var h='<div class="cnd-escalas">'+['R','S','G'].map(function(k){ var s=+es[k]||0; return '<div class="cnd-escala" data-n="'+s+'" title="'+esc(ESC[k][s+1])+'"><span class="cnd-chico cnd-mut">'+ESC[k][0]+'</span><b>'+k+s+'</b><span class="cnd-chico">'+esc(ESC[k][s+1])+'</span></div>'; }).join('')+'</div>';
	h+='<div class="cnd-kpis"><div class="cnd-kpi"><span>Kp ahora</span><b>'+n(kp,1)+'</b><small>'+(e.kp_pron!=null?'máx. previsto 24 h: '+n(e.kp_pron,1):'0 tranquilo · 9 extremo')+'</small><div class="cnd-barra"><i style="width:'+Math.min(100,(kp||0)/9*100)+'%;background:'+(kp>=5?'hsl(var(--al))':kp>=4?'hsl(var(--at))':'hsl(var(--ok))')+'"></i></div></div>'
		+'<div class="cnd-kpi"><span>Rayos X</span><b>'+esc(x.actual||'—')+'</b><small>'+(x.max?'pico reciente '+esc(x.max)+(x.max_hora?' a las '+hora(new Date(x.max_hora)):''):'')+'</small></div>'
		+'<div class="cnd-kpi"><span>Flujo solar</span><b>'+n(e.sfi)+'</b><small>SFI'+(e.ssn!=null?' · manchas '+n(e.ssn):'')+'</small></div>'
		+'<div class="cnd-kpi"><span>Viento solar</span><b>'+n(v.vel)+'</b><small>km/s · densidad '+n(v.dens,1)+'</small></div>'
		+'<div class="cnd-kpi"><span>Bz</span><b>'+n(v.bz,1)+'</b><small>nT · '+(v.bz==null?'—':v.bz<=-10?'muy al sur: tormenta probable':v.bz<0?'hacia el sur':'hacia el norte (tranquilo)')+'</small></div></div>';
	if(es.prob_r!=null) h+='<p class="cnd-chico">Probabilidad de apagón de radio hoy: menor '+es.prob_r+' %, fuerte '+es.prob_rx+' %.</p>';
	if(e.alertas&&e.alertas.length) h+='<p class="cnd-chico" style="margin-bottom:.2rem"><b>Avisos de NOAA (24 h, en inglés):</b></p><ul class="cnd-lista">'+e.alertas.map(function(a){ return '<li><span>'+esc(a.texto)+'</span><span class="der cnd-mut cnd-chico">'+hora(new Date(a.hora))+'</span></li>'; }).join('')+'</ul>';
	else h+='<p class="cnd-chico cnd-mut">NOAA no ha emitido avisos en las últimas 24 horas.</p>';
	caja('cnd-esp',h);
}
function pintarSismos(){
	var s=datos.sismos;
	if(s==null){ caja('cnd-sis','<p class="cnd-vacio">El USGS no respondió.</p>'); return; }
	caja('cnd-sis', s.length?'<ul class="cnd-lista">'+s.map(function(x){ return '<li><span><b>M'+n(x.mag,1)+'</b> '+esc(x.lugar)+'<br><span class="cnd-mut cnd-chico">a '+n(x.km)+' km · profundidad '+n(x.prof)+' km</span></span><span class="der cnd-chico cnd-mut">'+new Date(x.t*1000).toLocaleString('es-CO',{weekday:'short',hour:'numeric',minute:'2-digit'})+'</span></li>'; }).join('')+'</ul><p class="cnd-mut cnd-chico" style="margin:.5rem 0 0">Magnitud 2,5 o más en los últimos 7 días a menos de 500 km (USGS). Para el reporte oficial, el Servicio Geológico Colombiano.</p>'
		:'<p class="cnd-vacio">Ningún sismo de magnitud 2,5 o más a menos de 500 km en los últimos 7 días.</p>');
}
function pintarRAU(){
	var r=datos.repetidoras||[], h='';
	h+=r.length?'<ul class="cnd-lista">'+r.map(function(x){ return '<li><span><b>'+esc(x.name)+'</b><br><span class="cnd-mono cnd-chico">'+esc(x.freq)+' '+esc(x.offset||'')+(x.tone?' · tono '+esc(x.tone):'')+'</span></span><span class="der cnd-chico">'+n(x.km)+' km</span></li>'; }).join('')+'</ul>':'<p class="cnd-vacio">Sin repetidoras con coordenadas.</p>';
	h+='<p class="cnd-chico" id="cnd-kiwi" style="margin:.6rem 0 0">📻 Receptor del club: consultando…</p>';
	$('cnd-rau-cont').className=''; $('cnd-rau-cont').innerHTML=h;
	fetch(C.ajax+'?action=rau_sdr_status').then(function(x){ return x.json(); }).then(function(j){
		var el=$('cnd-kiwi'); if(!el) return;
		el.innerHTML=j.success?'📻 Receptor del club en línea · '+(j.data.libres===0?'lleno, pero igual puedes entrar':j.data.libres+' de '+j.data.max+' canales libres')+' · <a href="'+esc(C.receptor)+'">Abrir</a>':'📻 Receptor del club: fuera de línea ahora.';
	}).catch(function(){});
}
/* VHF: 6 m y 2 m en el cluster (spots de los últimos minutos) y lluvias de meteoros */
var METEOROS=[['Cuadrántidas',1,3,110],['Líridas',4,22,18],['Eta Acuáridas',5,6,50],['Delta Acuáridas',7,30,25],['Perseidas',8,12,100],['Dracónidas',10,8,10],['Oriónidas',10,21,20],['Táuridas',11,12,5],['Leónidas',11,17,15],['Gemínidas',12,14,150],['Úrsidas',12,22,10]];
function pintarVHF(){
	var hoy=new Date(), lista=METEOROS.map(function(m){ var f=new Date(hoy.getFullYear(),m[1]-1,m[2]); if(f<new Date(hoy.getTime()-3*864e5)) f=new Date(hoy.getFullYear()+1,m[1]-1,m[2]); return {n:m[0],f:f,zhr:m[3],dias:Math.round((f-hoy)/864e5)}; }).sort(function(a,b){ return a.f-b.f; }).slice(0,3);
	var met='<p class="cnd-chico" style="margin-bottom:.2rem"><b>Lluvias de meteoros</b> (sirven para dispersión meteórica en 6 y 2 m, con MSK144):</p><ul class="cnd-lista">'+lista.map(function(m){ return '<li><span>'+esc(m.n)+' <span class="cnd-mut cnd-chico">· hasta '+m.zhr+' por hora</span></span><span class="der cnd-chico">'+(Math.abs(m.dias)<=2?'<b>activa ahora</b>':m.f.toLocaleDateString('es-CO',{day:'numeric',month:'short'})+' · en '+m.dias+' días')+'</span></li>'; }).join('')+'</ul>';
	caja('cnd-vhf','<div id="cnd-seis"><p class="cnd-vacio">Revisando el cluster DX…</p></div>'+met);
	fetch(C.ajax+'?action=rau_dx_spots').then(function(r){ return r.json(); }).then(function(j){
		var sp=(j&&j.success&&j.data)||[], seis=sp.filter(function(s){ var f=parseFloat(s.freq); return f>=50&&f<=54; }), dos=sp.filter(function(s){ var f=parseFloat(s.freq); return f>=144&&f<=148; });
		var nivel=seis.length>=3?2:seis.length?1:0;
		luz('seis', nivel===2?0:nivel===1?1:0, seis.length?seis.length+' reporte'+(seis.length>1?'s':'')+' en 6 m en el cluster ahora: '+(seis.length>=3?'¡la banda está abierta en alguna parte!':'actividad aislada.'):'Sin reportes en 6 m en el cluster ahora mismo.');
		var el=$('cnd-seis'); if(!el) return;
		el.innerHTML=(seis.concat(dos).length?'<ul class="cnd-lista">'+seis.concat(dos).slice(0,8).map(function(s){ return '<li><span><b class="cnd-mono">'+esc(s.dx)+'</b> <span class="cnd-mut cnd-chico">por '+esc(s.sp)+' · '+esc(s.info||'')+'</span></span><span class="der cnd-mono">'+esc(s.freq)+'</span></li>'; }).join('')+'</ul>'
			:'<p class="cnd-chico">Ni 6 m ni 2 m tienen reportes en los últimos spots del cluster. Llama CQ de todos modos: las aperturas de esporádica E duran minutos y casi nadie las reporta.</p>');
	}).catch(function(){ luz('seis',-1,'El cluster DX no respondió.'); });
}
/* Cielo: sol, luna y pasadas de satélites de radioaficionado en las próximas 12 horas */
var SATS=/^(ISS|SO-50|AO-91|AO-27|PO-101|RS-44|IO-117|AO-7|AO-123|SO-124|SO-125|TEVEL)/i;
function pintarCielo(){
	var hoy=new Date(), sp=salidaPuesta(hoy,punto.lat,punto.lon,-.833), l=luna(hoy), sombra=(l.edad<.5?'linear-gradient(90deg,#111827 '+(100-l.ilum*100)+'%,#f1f5f9 0)':'linear-gradient(90deg,#f1f5f9 '+(l.ilum*100)+'%,#111827 0)');
	var h='<div class="cnd-kpis"><div class="cnd-kpi"><span>Amanecer</span><b>'+(sp?hora(sp[0]):'—')+'</b></div><div class="cnd-kpi"><span>Atardecer</span><b>'+(sp?hora(sp[1]):'—')+'</b></div>'
		+'<div class="cnd-kpi"><span>Luz del día</span><b>'+(sp?Math.floor((sp[1]-sp[0])/36e5)+' h '+Math.round((sp[1]-sp[0])%36e5/6e4)+' m':'—')+'</b></div></div>'
		+'<p style="display:flex;align-items:center;gap:.75rem;margin:.5rem 0"><span class="cnd-luna" style="background:'+sombra+'" aria-hidden="true"></span><span><b>'+l.nombre+'</b> · '+Math.round(l.ilum*100)+' % iluminada · '+n(l.dias,1)+' días<br><span class="cnd-chico cnd-mut">'+(l.ilum>.9?'Buenos días para rebote lunar (EME) si la luna está alta.':'Para rebote lunar (EME) conviene luna alta; cerca de la llena el ruido de fondo es menor.')+'</span></span></p>'
		+'<div id="cnd-sats"><p class="cnd-chico cnd-mut">Calculando pasadas de satélites…</p></div>';
	caja('cnd-cielo',h);
	pasadas();
}
function cargarSatJs(cb){ if(window.satellite) return cb(); var s=document.createElement('script'); s.src=C.satjs; s.onload=cb; s.onerror=function(){ var el=$('cnd-sats'); if(el) el.innerHTML='<p class="cnd-chico cnd-mut">No se pudo cargar el calculador de órbitas.</p>'; luz('sat',-1,'Sin cálculo de pasadas.'); }; document.head.appendChild(s); }
function pasadas(){
	var obtener=satCache?Promise.resolve(satCache):fetch(C.ajax+'?action=rau_tle').then(function(r){ return r.json(); }).then(function(j){ satCache=(j&&j.success&&j.data)||[]; return satCache; });
	obtener.then(function(tle){ cargarSatJs(function(){
		var sat=window.satellite, obs={latitude:punto.lat*R,longitude:punto.lon*R,height:.5}, inicio=Date.now(), res=[];
		tle.filter(function(s){ return SATS.test(s.name); }).slice(0,14).forEach(function(s){
			var rec; try{ rec=sat.twoline2satrec(s.line1,s.line2); }catch(e){ return; }
			var dentro=false, p=null;
			for(var t=0;t<12*3600;t+=30){
				var d=new Date(inicio+t*1000), pv=sat.propagate(rec,d); if(!pv||!pv.position) continue;
				var la=sat.ecfToLookAngles(obs,sat.eciToEcf(pv.position,sat.gstime(d))), el=la.elevation/R;
				if(el>0&&!dentro){ dentro=true; p={n:s.name.replace(/\s*\(.*\)/,''),aos:d,max:el,az:la.azimuth/R}; }
				else if(el>0&&dentro){ if(el>p.max){ p.max=el; p.az=la.azimuth/R; } }
				else if(el<=0&&dentro){ dentro=false; p.los=d; if(p.max>=10) res.push(p); p=null; }
			}
		});
		res.sort(function(a,b){ return a.aos-b.aos; });
		var el=$('cnd-sats'); if(!el) return;
		el.innerHTML=res.length?'<p class="cnd-chico" style="margin-bottom:.2rem"><b>Próximas pasadas</b> (más de 10° de elevación):</p><ul class="cnd-lista">'+res.slice(0,7).map(function(p){ return '<li><span><b>'+esc(p.n)+'</b> <span class="cnd-mut cnd-chico">· máx. '+Math.round(p.max)+'° al '+rumbo(p.az)+' · '+Math.round((p.los-p.aos)/60000)+' min</span></span><span class="der cnd-mono">'+hora(p.aos)+'</span></li>'; }).join('')+'</ul><p class="cnd-chico" style="margin:.4rem 0 0"><a href="'+esc(C.herramientas)+'satpass/">Todas las pasadas →</a></p>'
			:'<p class="cnd-chico cnd-mut">Ninguna pasada de más de 10° en las próximas 12 horas.</p>';
		var prox=res[0]; luz('sat',0, prox?prox.n+' pasa a las '+hora(prox.aos)+', '+Math.round(prox.max)+'° de elevación al '+rumbo(prox.az)+'.':'Sin pasadas buenas en las próximas 12 horas.');
	}); }).catch(function(){ luz('sat',-1,'No se pudieron traer las órbitas.'); });
}

/* ── ubicación ── */
function textoLugar(){ $('cnd-lugar-txt').textContent=punto.nombre||(datos&&datos.punto&&datos.punto.cerca?'cerca de '+datos.punto.cerca.nombre:punto.lat.toFixed(2)+', '+punto.lon.toFixed(2)); $('cnd-loc').textContent=locator(punto.lat,punto.lon); }
function fijar(p,sinCargar){
	punto={lat:+p.lat,lon:+p.lon,nombre:p.nombre||null}; guardar('rau_cond_punto',punto); textoLugar();
	if(mapa){ mapa.setView([punto.lat,punto.lon],Math.max(mapa.getZoom(),7)); marcar(); }
	if(vista==='windy'){ var a=document.querySelector('[data-capa][aria-pressed="true"]'); windy(a?a.dataset.capa:'wind'); }
	var u=new URL(location.href); u.searchParams.set('lat',punto.lat.toFixed(2)); u.searchParams.set('lon',punto.lon.toFixed(2)); history.replaceState(null,'',u);
	if(!sinCargar) cargar();
}
function cargar(){
	$('cnd-act').textContent='Actualizando…';
	fetch(C.api+'?lat='+punto.lat.toFixed(1)+'&lon='+punto.lon.toFixed(1)).then(function(r){ if(!r.ok) throw new Error(r.status); return r.json(); }).then(function(j){
		datos=j; textoLugar();
		[pintarSemaforo,pintarClima,pintarHF,pintarEspacio,pintarSismos,pintarRAU,pintarCielo,pintarVHF,pintarCapasDatos].forEach(function(f){ try{ f(); }catch(e){ if(window.console) console.warn(f.name,e); } });
		$('cnd-act').textContent='Actualizado a las '+hora(new Date(j.generado*1000));
	}).catch(function(){ $('cnd-act').textContent='No se pudo actualizar. Revisa tu conexión.'; });
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
	marcar(); cargar();
	setInterval(function(){ if(!document.hidden) cargar(); },5*60000);
}

/* ── controles ── */
$('cnd-gps').addEventListener('click',function(){
	var b=this; if(!navigator.geolocation){ b.textContent='Tu navegador no da la ubicación'; return; }
	b.disabled=true; b.textContent='Buscando…';
	navigator.geolocation.getCurrentPosition(function(p){ b.disabled=false; b.textContent='🎯 Usar mi ubicación'; fijar({lat:p.coords.latitude,lon:p.coords.longitude,nombre:'Tu ubicación'}); },
		function(){ b.disabled=false; b.textContent='🎯 Usar mi ubicación'; $('cnd-act').textContent='No diste permiso de ubicación. Busca tu ciudad o escribe tu locator.'; },{enableHighAccuracy:false,timeout:15000,maximumAge:600000});
});
var tBus=null, sug=$('cnd-sug');
$('cnd-buscar').addEventListener('input',function(){
	var q=this.value.trim(); clearTimeout(tBus);
	var l=deLocator(q); if(l){ sug.hidden=false; sug.innerHTML='<button type="button" role="option" data-lat="'+l[0]+'" data-lon="'+l[1]+'" data-n="'+esc(q.toUpperCase())+'">📍 Locator '+esc(q.toUpperCase())+'</button>'; return; }
	if(q.length<2){ sug.hidden=true; return; }
	tBus=setTimeout(function(){ fetch(C.api+'/buscar?q='+encodeURIComponent(q)).then(function(r){ return r.json(); }).then(function(l){
		sug.hidden=false; sug.innerHTML=(l&&l.length)?l.map(function(x){ return '<button type="button" role="option" data-lat="'+x.lat+'" data-lon="'+x.lon+'" data-n="'+esc(x.nombre)+'">'+esc(x.nombre)+' <span class="cnd-mut cnd-chico">'+esc(x.zona)+'</span></button>'; }).join(''):'<p class="cnd-mut cnd-chico" style="padding:.6rem .8rem;margin:0">No encontré ese lugar.</p>';
	}).catch(function(){}); },300);
});
$('cnd-buscar').addEventListener('keydown',function(e){ if(e.key==='Enter'){ var b=sug.querySelector('button'); if(b){ e.preventDefault(); b.click(); } } else if(e.key==='Escape') sug.hidden=true; });
sug.addEventListener('click',function(e){ var b=e.target.closest('button'); if(!b) return; sug.hidden=true; $('cnd-buscar').value=''; fijar({lat:+b.dataset.lat,lon:+b.dataset.lon,nombre:b.dataset.n}); });
document.addEventListener('click',function(e){ if(!e.target.closest('.cnd-buscar')) sug.hidden=true; });
$('cnd-recargar').addEventListener('click',cargar);
$('cnd-compartir').addEventListener('click',function(){ var b=this, u=location.origin+location.pathname+'?lat='+punto.lat.toFixed(1)+'&lon='+punto.lon.toFixed(1);
	(navigator.share?navigator.share({title:'Condiciones para operar',url:u}):navigator.clipboard.writeText(u).then(function(){ b.textContent='✓ Enlace copiado'; setTimeout(function(){ b.textContent='🔗 Compartir'; },2000); })).catch(function(){}); });
document.querySelectorAll('[data-vista]').forEach(function(b){ b.addEventListener('click',function(){ cambiarVista(b.dataset.vista); }); });
document.querySelectorAll('[data-capa]').forEach(function(b){ b.addEventListener('click',function(){ document.querySelectorAll('[data-capa]').forEach(function(x){ x.setAttribute('aria-pressed',String(x===b)); }); windy(b.dataset.capa); }); });
$('cnd-capas-btn').addEventListener('click',function(){ var c=$('cnd-capas'); c.hidden=!c.hidden; this.setAttribute('aria-expanded',String(!c.hidden)); });
$('cnd-full').addEventListener('click',function(){ var el=document.querySelector('.cnd-mapa-caja'); if(document.fullscreenElement) document.exitFullscreen(); else if(el.requestFullscreen) el.requestFullscreen(); });
document.addEventListener('fullscreenchange',function(){ var full=!!document.fullscreenElement; $('cnd-mapa').style.height=full?'calc(100vh - 3.6rem)':''; $('cnd-windy').style.height=full?'calc(100vh - 3.6rem)':''; if(mapa) setTimeout(function(){ mapa.invalidateSize(); },100); });
$('cnd-play').addEventListener('click',animar);
$('cnd-cuadro').addEventListener('input',function(){ if(radarTimer) animar(); radarI=+this.value; mostrarCuadro(radarI); });

/* ── avisos al celular ── */
function b64(s){ var p='='.repeat((4-s.length%4)%4), r=atob((s+p).replace(/-/g,'+').replace(/_/g,'/')), o=new Uint8Array(r.length); for(var i=0;i<r.length;i++) o[i]=r.charCodeAt(i); return o; }
function estadoAvisos(){ var g=leer('rau_cond_avisos'); $('cnd-av-off').hidden=!g; if(g) $('cnd-av-est').textContent='Avisos activos para '+(g.nombre||g.lat+', '+g.lon)+': '+g.temas.join(', ')+'.'; }
$('cnd-av-on').addEventListener('click',function(){
	var b=this, est=$('cnd-av-est'), temas=[].slice.call(document.querySelectorAll('#cnd-av input:checked')).map(function(i){ return i.value; });
	if(!temas.length){ est.textContent='Elige al menos un aviso.'; return; }
	if(!('serviceWorker' in navigator)||!('PushManager' in window)){ est.textContent='Este navegador no permite avisos. En iPhone, primero agrega el sitio a la pantalla de inicio.'; return; }
	b.disabled=true; est.textContent='Activando…';
	Notification.requestPermission().then(function(p){ if(p!=='granted') throw new Error('permiso');
		return navigator.serviceWorker.register('/service-worker.js').then(function(){ return navigator.serviceWorker.ready; }); })
	.then(function(reg){ return reg.pushManager.getSubscription().then(function(s){ return s||fetch(C.ajax+'?action=rau_vapid_public_key').then(function(r){ return r.json(); }).then(function(j){ if(!j.success||!j.data.key) throw new Error('vapid'); return reg.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:b64(j.data.key)}); }); }); })
	.then(function(sub){ return fetch(C.api+'/avisos',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sub:sub.toJSON(),lat:punto.lat,lon:punto.lon,temas:temas})}).then(function(r){ return r.json().then(function(j){ if(!r.ok) throw new Error(j.error||'error'); return [j,sub]; }); }); })
	.then(function(x){ guardar('rau_cond_avisos',{endpoint:x[1].endpoint,lat:x[0].lat,lon:x[0].lon,temas:x[0].temas,nombre:punto.nombre}); b.disabled=false; b.textContent='🔔 Actualizar avisos'; estadoAvisos(); })
	.catch(function(e){ b.disabled=false; est.textContent=e.message==='permiso'?'No diste permiso para notificaciones. Puedes darlo en la configuración del navegador.':'No se pudo activar: '+e.message; });
});
$('cnd-av-off').addEventListener('click',function(){ var g=leer('rau_cond_avisos'); if(!g) return;
	fetch(C.api+'/avisos',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({endpoint:g.endpoint})}).finally(function(){ try{ localStorage.removeItem('rau_cond_avisos'); }catch(e){} $('cnd-av-est').textContent='Avisos quitados.'; $('cnd-av-off').hidden=true; $('cnd-av-on').textContent='🔔 Activar avisos aquí'; }); });
estadoAvisos();

inicio();
})();
