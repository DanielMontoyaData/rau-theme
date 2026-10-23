<?php
/**
 * Template Name: RAU Field
 * Vista simplificada para operación portátil / POTA — botones grandes,
 * mínimo scroll, pensada para usarse con una mano y sol en la pantalla.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$repetidores = rau_get_repeaters_data();
$rau_field_rep = rau_get_repetidor_principal();
?>

<div class="mx-auto max-w-2xl px-4 py-6 sm:px-6">

	<div class="mb-5 flex items-center justify-between">
		<div class="flex items-center gap-2">
			<?php rau_logo_html( 'h-7 w-7' ); ?>
			<h1 class="font-display text-base font-bold">RAU Field</h1>
		</div>
		<a href="<?php echo esc_url( home_url( '/herramientas/' ) ); ?>" class="font-mono text-xs text-muted-foreground hover:text-primary">Herramientas completas →</a>
	</div>

	<!-- MODO VOZ (manos libres) -->
	<button id="field-voz-btn" type="button" class="mb-4 flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-primary/40 bg-primary/10 py-4 text-base font-bold text-primary">
		🎙️ Activar manos libres
	</button>
	<p id="field-voz-estado" class="mb-4 hidden text-center font-mono text-sm text-muted-foreground"></p>

	<button class="rau-push-generic-btn mb-4 flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-border bg-card py-3 text-sm font-bold text-foreground">
		🔔 Activar notificaciones de RAU
	</button>
	<p class="rau-push-generic-status mb-4 hidden text-center font-mono text-xs text-muted-foreground"></p>

	<!-- COMPARTIR QTH EN VIVO — Privacy First -->
	<div class="mb-4 rounded-2xl border-2 border-border bg-card p-5">
		<p class="mb-1 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Compartir QTH en vivo</p>
		<p class="mb-3 text-xs text-muted-foreground">Comparte solo tu grid square (~4 km de precisión, no tu ubicación exacta) durante una activación o simulacro. Se apaga solo a las 4 horas.</p>
		<button id="qth-share-btn" type="button" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-bold text-primary-foreground">
			📍 Activar compartir en vivo
		</button>
		<div id="qth-share-activo" class="hidden mt-3">
			<p class="mb-2 text-center text-xs text-muted-foreground">Enlace para compartir:</p>
			<div class="flex gap-2">
				<input id="qth-share-link" aria-label="Enlace para compartir tu QTH" type="text" readonly class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-xs">
				<button id="qth-share-copiar" type="button" class="shrink-0 rounded-md border border-border bg-background px-3 text-xs font-medium">Copiar</button>
			</div>
			<button id="qth-share-detener" type="button" class="mt-3 w-full text-center text-xs font-medium text-rose-400 hover:underline">Detener y borrar ahora</button>
		</div>
	</div>

	<!-- RELOJ -->
	<div class="mb-4 rounded-2xl border-2 border-primary/30 bg-card p-6 text-center">
		<p id="field-utc" class="font-mono text-5xl font-bold tabular-nums text-primary">--:--:--</p>
		<p class="mt-1 font-mono text-xs uppercase tracking-wider text-muted-foreground">UTC</p>
	</div>

	<!-- GRID / GPS -->
	<div class="mb-4 rounded-2xl border-2 border-border bg-card p-5">
		<p class="mb-2 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Tu ubicación</p>
		<div class="flex gap-2">
			<input aria-label="Toca 📍 para ubicarte" type="text" id="field-grid" placeholder="Toca 📍 para ubicarte" readonly class="w-full rounded-md border border-input bg-background px-3 py-3 text-center font-mono text-lg font-bold uppercase text-primary">
			<button id="field-gps" type="button" aria-label="Obtener mi posición desde el GPS del dispositivo" class="shrink-0 rounded-md bg-primary px-5 text-2xl text-primary-foreground"><span aria-hidden="true">📍</span></button>
		</div>
	</div>

	<!-- REPETIDOR MÁS CERCANO -->
	<div class="mb-4 rounded-2xl border-2 border-border bg-card p-5">
		<p class="mb-2 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Repetidor más cercano</p>
		<div id="field-repetidor">
			<p class="text-sm text-muted-foreground">Ubícate arriba para calcularlo.</p>
		</div>
	</div>

	<!-- EMERGENCIA -->
	<div class="mb-4 rounded-2xl border-2 border-accent/40 bg-accent/5 p-5">
		<p class="mb-2 font-mono text-xs font-semibold uppercase tracking-wider text-accent">🚨 Frecuencia de emergencia</p>
		<?php // Datos leídos de la repetidora principal, no escritos a mano: si el club cambia
		      // frecuencia, tono u offset, esta pantalla de emergencia se actualiza sola. ?>
		<p class="font-mono text-2xl font-bold text-accent"><?php echo esc_html( $rau_field_rep['frequency'] ); ?> MHz</p>
		<p class="font-mono text-xs text-muted-foreground">T<?php echo esc_html( rtrim( rtrim( $rau_field_rep['tone'], '0' ), '.' ) ); ?> · <?php echo esc_html( ( (float) $rau_field_rep['offset'] * 1000 ) ); ?> kHz · Red <?php echo esc_html( $rau_field_rep['callsign'] ); ?></p>
		<p class="mt-2 font-mono text-sm text-muted-foreground">Simplex de llamada: <strong class="text-foreground">146.520 MHz</strong></p>
	</div>

	<!-- PRÓXIMA PASADA ISS -->
	<div class="mb-4 rounded-2xl border-2 border-border bg-card p-5">
		<p class="mb-2 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Próxima pasada — ISS</p>
		<div id="field-iss"><p class="text-sm text-muted-foreground">Calculando…</p></div>
	</div>

	<!-- BATERÍA RÁPIDA -->
	<div class="rounded-2xl border-2 border-border bg-card p-5">
		<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Autonomía rápida de batería</p>
		<div class="grid grid-cols-2 gap-3">
			<div>
				<label for="field-cap" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Capacidad (mAh)</label>
				<input type="number" id="field-cap" value="10000" class="w-full rounded-md border border-input bg-background px-3 py-2 text-center font-mono text-sm">
			</div>
			<div>
				<label for="field-consumo" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Consumo prom. (mA)</label>
				<input type="number" id="field-consumo" value="600" class="w-full rounded-md border border-input bg-background px-3 py-2 text-center font-mono text-sm">
			</div>
		</div>
		<p class="mt-3 text-center font-mono text-2xl font-bold text-primary" id="field-horas">—</p>
	</div>

</div>

<script>
(function(){
	function pad(n){ return String(n).padStart(2,'0'); }
	function tick(){
		var now=new Date();
		document.getElementById('field-utc').textContent = pad(now.getUTCHours())+':'+pad(now.getUTCMinutes())+':'+pad(now.getUTCSeconds());
	}
	tick(); setInterval(tick, 1000);

	var REPS = <?php echo wp_json_encode( $repetidores ); ?>;
	var origenActual=null;

	function distanciaKm(o,d){
		var R=6371;
		var lat1=o.lat*Math.PI/180,lat2=d.lat*Math.PI/180;
		var dLat=(d.lat-o.lat)*Math.PI/180,dLng=(d.lng-o.lng)*Math.PI/180;
		var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(lat1)*Math.cos(lat2)*Math.sin(dLng/2)*Math.sin(dLng/2);
		return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
	}
	function azimut(o,d){
		var lat1=o.lat*Math.PI/180,lat2=d.lat*Math.PI/180;
		var y=Math.sin((d.lng-o.lng)*Math.PI/180)*Math.cos(lat2);
		var x=Math.cos(lat1)*Math.sin(lat2)-Math.sin(lat1)*Math.cos(lat2)*Math.cos((d.lng-o.lng)*Math.PI/180);
		return (Math.atan2(y,x)*180/Math.PI+360)%360;
	}
	function latLngToMHLocal(lat,lng){
		lat+=90; lng+=180;
		var A='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		var campo=A[Math.floor(lng/20)]+A[Math.floor(lat/10)];
		var cuadro=Math.floor((lng%20)/2)+''+Math.floor(lat%10);
		var sub=A[Math.floor((lng%2)*12)].toLowerCase()+A[Math.floor((lat%1)*24)].toLowerCase();
		return campo+cuadro+sub;
	}

	function actualizarRepetidor(){
		if(!origenActual || !REPS.length) return;
		var mejor=null, mejorDist=Infinity;
		REPS.forEach(function(r){
			var d=distanciaKm(origenActual, r);
			if(d<mejorDist){ mejorDist=d; mejor=r; }
		});
		if(!mejor) return;
		var az=azimut(origenActual, mejor).toFixed(0);
		document.getElementById('field-repetidor').innerHTML =
			'<p class="font-display text-lg font-bold">'+mejor.name+'</p>'
			+'<p class="font-mono text-sm text-primary">'+(mejor.callsign||'')+' · '+(mejor.freq||'')+' '+(mejor.offset||'')+'</p>'
			+'<div class="mt-2 flex gap-4 font-mono text-sm"><span>📏 '+mejorDist.toFixed(1)+' km</span><span>🧭 '+az+'°</span></div>';
	}

	document.getElementById('field-gps').addEventListener('click', function(){
		if(!navigator.geolocation){ alert('Tu navegador no soporta geolocalización.'); return; }
		navigator.geolocation.getCurrentPosition(function(pos){
			origenActual={lat:pos.coords.latitude, lng:pos.coords.longitude};
			document.getElementById('field-grid').value = latLngToMHLocal(pos.coords.latitude, pos.coords.longitude);
			actualizarRepetidor();
		}, function(){ alert('No se pudo obtener tu ubicación.'); });
	});

	/* Batería rápida */
	function calcBateria(){
		// Capacidad vacía no es batería descargada: con ||0 salía "0h 0m".
		var cap=parseFloat(document.getElementById('field-cap').value);
		var consumo=parseFloat(document.getElementById('field-consumo').value);
		if(isNaN(cap) || isNaN(consumo) || consumo<=0){ document.getElementById('field-horas').textContent='—'; return; }
		var horas=(cap/consumo)*0.85;
		var h=Math.floor(horas), m=Math.round((horas-h)*60);
		document.getElementById('field-horas').textContent=h+'h '+m+'m';
	}
	document.getElementById('field-cap').addEventListener('input', calcBateria);
	document.getElementById('field-consumo').addEventListener('input', calcBateria);
	calcBateria();

	/* Próxima pasada ISS — carga satellite.js diferido, mismo patrón que Herramientas */
	var RAU_QTH_DEFAULT={lat:6.2442,lng:-75.5812};
	function loadISS(){
		var el=document.getElementById('field-iss');
		var origen = origenActual || RAU_QTH_DEFAULT;
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_tle', {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				if(!json.success || !json.data) throw 0;
				var iss=json.data.find(function(s){ return /ISS|ZARYA/i.test(s.name); });
				if(!iss || typeof satellite==='undefined') throw 0;
				var satrec=satellite.twoline2satrec(iss.line1, iss.line2);
				var observerGd={longitude:satellite.degreesToRadians(origen.lng), latitude:satellite.degreesToRadians(origen.lat), height:0.15};
				var ahora=new Date(), pasoMs=30000, encontrado=null, enPasada=false, actual=null;
				for(var i=0;i<Math.floor(48*60*60*1000/pasoMs) && !encontrado;i++){
					var t=new Date(ahora.getTime()+i*pasoMs);
					var pv=satellite.propagate(satrec, t);
					if(!pv || !pv.position) continue;
					var gmst=satellite.gstime(t);
					var posEcf=satellite.eciToEcf(pv.position, gmst);
					var look=satellite.ecfToLookAngles(observerGd, posEcf);
					var elDeg=satellite.radiansToDegrees(look.elevation);
					if(elDeg>=0 && !enPasada){ enPasada=true; actual={aos:t, maxEl:elDeg}; }
					else if(elDeg>=0 && enPasada){ if(elDeg>actual.maxEl) actual.maxEl=elDeg; }
					else if(elDeg<0 && enPasada){ encontrado=actual; }
				}
				if(!encontrado){ el.innerHTML='<p class="text-sm text-muted-foreground">Sin pasadas en 48h.</p>'; return; }
				var ms=encontrado.aos-new Date();
				var h=Math.floor(ms/3600000), m=Math.floor((ms%3600000)/60000);
				el.innerHTML='<p class="font-mono text-2xl font-bold text-primary">'+(h>0?h+'h ':'')+m+'m</p><p class="font-mono text-xs text-muted-foreground">Elev. máx '+encontrado.maxEl.toFixed(0)+'° · '+encontrado.aos.toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'})+'</p>';
			}).catch(function(e){ rauErr(e);
				el.innerHTML='<p class="text-sm text-muted-foreground">No se pudo calcular.</p>';
			});
	}
	var s=document.createElement('script');
	s.src='https://cdn.jsdelivr.net/npm/satellite.js@5.0.0/dist/satellite.min.js';
	s.onload=loadISS;
	document.head.appendChild(s);

	/* ── MODO VOZ (manos libres) — Web Speech API, sin servidor ── */
	var vozBtn=document.getElementById('field-voz-btn');
	var vozEstado=document.getElementById('field-voz-estado');
	var SpeechRecognitionAPI = window.SpeechRecognition || window.webkitSpeechRecognition;
	var reconocimiento = null;
	var vozActiva = false;

	function hablar(texto){
		if(!('speechSynthesis' in window)) return;
		var u=new SpeechSynthesisUtterance(texto);
		u.lang='es-CO';
		window.speechSynthesis.cancel(); // no encimar respuestas
		window.speechSynthesis.speak(u);
	}

	function responderComando(texto){
		var t=texto.toLowerCase();
		if(t.indexOf('hora')>-1 || t.indexOf('utc')>-1){
			var ahora=new Date();
			hablar('Son las '+pad(ahora.getUTCHours())+' y '+pad(ahora.getUTCMinutes())+' UTC.');
		} else if(t.indexOf('bateria')>-1 || t.indexOf('batería')>-1 || t.indexOf('autonomia')>-1){
			var horasTxt=document.getElementById('field-horas').textContent;
			hablar(horasTxt==='—' ? 'Todavía no he calculado la batería.' : 'Te quedan aproximadamente '+horasTxt+' de autonomía.');
		} else if(t.indexOf('repetidor')>-1){
			if(!origenActual){ hablar('Primero necesito tu ubicación. Toca el botón de geolocalización.'); return; }
			var rep=document.getElementById('field-repetidor').textContent.trim();
			hablar(rep ? 'El repetidor más cercano es '+rep.split('\n')[0]+'.' : 'No encontré repetidores cerca.');
		} else if(t.indexOf('satelite')>-1 || t.indexOf('satélite')>-1 || t.indexOf('iss')>-1){
			var issTxt=document.getElementById('field-iss').textContent.trim();
			hablar(issTxt ? 'Próxima pasada de la estación espacial: '+issTxt : 'Todavía estoy calculando la próxima pasada.');
		} else if(t.indexOf('emergencia')>-1 || t.indexOf('socorro')>-1){
			hablar('Frecuencia de emergencia: <?php echo esc_js( str_replace( '.', ' punto ', $rau_field_rep['frequency'] ) ); ?> megahertz, tono <?php echo esc_js( (int) $rau_field_rep['tone'] ); ?>. Simplex de llamada: 146 punto 520.');
		} else if(t.indexOf('ubicacion')>-1 || t.indexOf('ubicación')>-1 || t.indexOf('grid')>-1){
			var grid=document.getElementById('field-grid').value;
			hablar(grid ? 'Tu grid actual es '+grid.split('').join(' ')+'.' : 'Todavía no tengo tu ubicación. Toca el botón de geolocalización.');
		} else {
			hablar('No entendí. Puedes preguntar por la hora, la batería, el repetidor, el satélite, o la emergencia.');
		}
	}

	function iniciarEscucha(){
		reconocimiento = new SpeechRecognitionAPI();
		reconocimiento.lang = 'es-CO';
		reconocimiento.continuous = true;
		reconocimiento.interimResults = false;

		reconocimiento.onresult = function(event){
			var ultimo = event.results[event.results.length-1];
			if(!ultimo.isFinal) return;
			var texto = ultimo[0].transcript;
			vozEstado.textContent = 'Escuché: "'+texto+'"';
			responderComando(texto);
		};
		reconocimiento.onerror = function(event){
			if(event.error==='not-allowed' || event.error==='service-not-allowed'){
				vozEstado.textContent = 'Permiso de micrófono bloqueado.';
				vozActiva = false;
				vozBtn.textContent = '🎙️ Activar manos libres';
			}
		};
		reconocimiento.onend = function(){
			if(vozActiva) reconocimiento.start(); // se reinicia solo — el reconocimiento se corta tras cada pausa
		};
		reconocimiento.start();
	}

	if(vozBtn){
		if(!SpeechRecognitionAPI){
			vozBtn.disabled=true;
			vozBtn.textContent='Tu navegador no soporta reconocimiento de voz';
		} else {
			vozBtn.addEventListener('click', function(){
				vozActiva = !vozActiva;
				vozEstado.classList.remove('hidden');
				if(vozActiva){
					vozBtn.textContent='🔴 Escuchando… (di "hora", "batería", "repetidor", "satélite" o "emergencia")';
					vozEstado.textContent='Manos libres activo.';
					hablar('Manos libres activado.');
					iniciarEscucha();
				} else {
					vozBtn.textContent='🎙️ Activar manos libres';
					vozEstado.textContent='Manos libres desactivado.';
					if(reconocimiento) reconocimiento.stop();
					window.speechSynthesis.cancel();
				}
			});
		}
	}

	/* ── COMPARTIR QTH EN VIVO ── */
	var qthBtn=document.getElementById('qth-share-btn');
	var qthActivo=document.getElementById('qth-share-activo');
	var qthToken=null, qthInterval=null;

	function qthEnviarUbicacion(){
		if(!navigator.geolocation) return;
		navigator.geolocation.getCurrentPosition(function(pos){
			var body=new URLSearchParams();
			body.set('lat', pos.coords.latitude);
			body.set('lng', pos.coords.longitude);
			if(qthToken) body.set('token', qthToken);
			fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_qth_share', { method:'POST', body:body })
				.then(function(r){ return r.json(); })
				.then(function(json){
					if(!json.success) return;
					qthToken=json.data.token;
					document.getElementById('qth-share-link').value=json.data.url;
				}).catch(function(e){ rauErr(e);});
		}, function(){ alert('No se pudo obtener tu ubicación.'); }, { enableHighAccuracy:false, maximumAge:60000 });
	}

	if(qthBtn){
		qthBtn.addEventListener('click', function(){
			if(!navigator.geolocation){ alert('Tu navegador no soporta geolocalización.'); return; }
			if(!confirm('Vas a compartir tu grid square aproximado (no tu ubicación exacta) por hasta 4 horas, con quien tenga el enlace. ¿Continuar?')) return;
			qthEnviarUbicacion();
			qthInterval=setInterval(qthEnviarUbicacion, 5*60*1000); // actualiza cada 5 min mientras esté activo
			qthBtn.classList.add('hidden');
			qthActivo.classList.remove('hidden');
		});
	}
	var qthCopiarBtn=document.getElementById('qth-share-copiar');
	if(qthCopiarBtn){
		qthCopiarBtn.addEventListener('click', function(){
			var input=document.getElementById('qth-share-link');
			input.select();
			navigator.clipboard && navigator.clipboard.writeText(input.value);
		});
	}
	var qthDetenerBtn=document.getElementById('qth-share-detener');
	if(qthDetenerBtn){
		qthDetenerBtn.addEventListener('click', function(){
			if(qthInterval) clearInterval(qthInterval);
			if(qthToken){
				var body=new URLSearchParams(); body.set('token', qthToken);
				fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_qth_stop', { method:'POST', body:body }).catch(function(e){ rauErr(e);});
			}
			qthToken=null;
			qthBtn.classList.remove('hidden');
			qthActivo.classList.add('hidden');
		});
	}
})();
</script>

<?php get_footer(); ?>
