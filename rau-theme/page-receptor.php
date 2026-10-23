<?php
/**
 * Template Name: Receptor KiwiSDR
 *
 * El KiwiSDR de RAU escuchado desde la propia página, con el diseño del sitio.
 *
 * El receptor solo habla HTTP; una página HTTPS no puede abrirle un WebSocket (contenido mixto).
 * Con un relevo HTTPS configurado (opción rau_kiwi_relay, ver cloudflare-worker/ en el repositorio)
 * el navegador se conecta a wss://relevo/ws/kiwi/<marca>/SND y /W/F, el mismo protocolo que usa la
 * interfaz oficial (web/openwebrx/openwebrx.js y kiwiclient/kiwi/client.py del proyecto KiwiSDR).
 * Sin relevo, la página muestra el estado y la ocupación por hora, y enlaza la interfaz original.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$relay = get_option( 'rau_kiwi_relay', '' );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Receptor SDR · Medellín</p>
	<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
		<div>
			<h1 class="font-display text-3xl font-bold sm:text-4xl">KiwiSDR HK4RAU</h1>
			<p class="mt-2 max-w-2xl text-muted-foreground">Un receptor de 0 a 30 MHz en Medellín que cualquiera puede escuchar desde el navegador: bandas de radioaficionado, onda corta, señales horarias. Hasta <span id="rx-max">7</span> personas a la vez.</p>
		</div>
		<div id="rx-estado" class="inline-flex items-center gap-2 rounded-full border border-border/60 bg-card px-3 py-1.5 font-mono text-xs text-muted-foreground">
			<span class="h-2 w-2 rounded-full bg-muted-foreground"></span> Consultando el receptor…
		</div>
	</div>

	<?php if ( $relay ) : ?>
	<div id="rx-app" class="rounded-2xl border border-primary/30 bg-card p-4 sm:p-6">
		<div class="flex flex-wrap items-center justify-between gap-4">
			<div>
				<p class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Frecuencia</p>
				<p class="font-mono text-4xl font-bold tabular-nums text-primary sm:text-5xl"><span id="rx-freq">7150.00</span> <span class="text-base text-muted-foreground">kHz</span></p>
			</div>
			<div class="min-w-[12rem] flex-1 sm:max-w-xs">
				<div class="flex items-center justify-between font-mono text-[11px] text-muted-foreground"><span>Señal</span><span id="rx-s">—</span></div>
				<div class="mt-1 h-2 overflow-hidden rounded-full bg-background"><div id="rx-smeter" class="h-full w-0 rounded-full bg-primary transition-all duration-150"></div></div>
			</div>
			<button id="rx-power" type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-6 text-sm font-semibold text-primary-foreground hover:bg-primary/90">▶ Escuchar</button>
		</div>

		<div class="relative mt-5 overflow-hidden rounded-xl border border-border/60 bg-background">
			<canvas id="rx-wf" width="1024" height="220" class="block h-[220px] w-full cursor-crosshair" aria-label="Cascada de frecuencias: toca para sintonizar"></canvas>
			<div id="rx-marca" class="pointer-events-none absolute inset-y-0 w-px bg-accent" style="left:50%"></div>
			<div class="flex justify-between border-t border-border/50 px-2 py-1 font-mono text-[10px] text-muted-foreground"><span id="rx-esc-izq">—</span><span id="rx-esc-cen">Toca la cascada para sintonizar</span><span id="rx-esc-der">—</span></div>
		</div>

		<div class="mt-5 grid gap-5 lg:grid-cols-[1fr_auto]">
			<div>
				<p class="mb-2 font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Bandas</p>
				<div id="rx-bandas" class="flex flex-wrap gap-2"></div>
			</div>
			<div>
				<p class="mb-2 font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Modo</p>
				<div id="rx-modos" class="flex flex-wrap gap-2"></div>
			</div>
		</div>

		<div class="mt-5 flex flex-wrap items-center gap-3">
			<div class="flex flex-wrap items-center gap-1">
				<button type="button" data-paso="-5" class="rx-paso rounded-md border border-border bg-background px-3 py-2 font-mono text-sm">−5</button>
				<button type="button" data-paso="-1" class="rx-paso rounded-md border border-border bg-background px-3 py-2 font-mono text-sm">−1</button>
				<button type="button" data-paso="-0.1" class="rx-paso rounded-md border border-border bg-background px-3 py-2 font-mono text-sm">−0.1</button>
				<label class="sr-only" for="rx-input">Frecuencia en kHz</label>
				<input id="rx-input" type="number" inputmode="decimal" step="0.01" min="10" max="30000" class="w-32 rounded-md border border-border bg-background px-3 py-2 font-mono text-sm" placeholder="kHz">
				<button type="button" data-paso="0.1" class="rx-paso rounded-md border border-border bg-background px-3 py-2 font-mono text-sm">+0.1</button>
				<button type="button" data-paso="1" class="rx-paso rounded-md border border-border bg-background px-3 py-2 font-mono text-sm">+1</button>
				<button type="button" data-paso="5" class="rx-paso rounded-md border border-border bg-background px-3 py-2 font-mono text-sm">+5</button>
			</div>
			<div class="flex items-center gap-2">
				<button type="button" id="rx-zoom-menos" class="rounded-md border border-border bg-background px-3 py-2 font-mono text-sm" aria-label="Alejar cascada">−</button>
				<span class="font-mono text-xs text-muted-foreground">Zoom <span id="rx-zoom">7</span></span>
				<button type="button" id="rx-zoom-mas" class="rounded-md border border-border bg-background px-3 py-2 font-mono text-sm" aria-label="Acercar cascada">+</button>
			</div>
			<label class="ml-auto flex items-center gap-2 font-mono text-xs text-muted-foreground">Volumen <input id="rx-vol" type="range" min="0" max="100" value="60" class="w-28 accent-primary"></label>
		</div>
		<p id="rx-msg" class="mt-4 font-mono text-xs text-muted-foreground" role="status" aria-live="polite">Pulsa «Escuchar» para conectarte. Usarás uno de los canales del receptor mientras escuchas.</p>
	</div>
	<p class="mt-3 text-xs text-muted-foreground">¿Necesitas decodificadores, grabación o «acampar» cuando está lleno? Usa la <a href="<?php echo esc_url( $relay . '/' ); ?>" target="_blank" rel="noopener" class="text-primary hover:underline">interfaz completa del KiwiSDR</a>.</p>
	<?php else : ?>
	<div class="rounded-2xl border border-border/70 bg-card p-6">
		<h2 class="font-display text-xl font-bold">Escúchalo en la interfaz del KiwiSDR</h2>
		<p class="mt-2 max-w-2xl text-sm text-muted-foreground">El receptor todavía no tiene dirección segura (HTTPS), así que los navegadores no permiten escucharlo dentro de esta página. Mientras tanto se abre en su propia pestaña. Cuando el canal esté lleno, esa interfaz te ofrece «acampar»: escuchar lo que sintoniza otra persona hasta que se libere uno.</p>
		<a href="<?php echo esc_url( RAU_KIWI_HTTP . '/' ); ?>" target="_blank" rel="noopener" class="mt-4 inline-flex h-11 items-center justify-center rounded-md bg-primary px-6 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Abrir el KiwiSDR en otra pestaña</a>
	</div>
	<?php endif; ?>

	<div class="mt-8 grid gap-4 lg:grid-cols-[2fr_1fr]">
		<div class="rounded-2xl border border-border/70 bg-card p-5 sm:p-6">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Cuándo entrar</p>
			<h2 class="font-display text-lg font-bold">Canales libres por hora</h2>
			<p class="mt-1 text-sm text-muted-foreground">Promedio de canales libres a cada hora del día (hora de Colombia), medido cada 10 minutos.</p>
			<div id="rx-horas" class="mt-4 flex h-32 items-end gap-1" aria-label="Canales libres por hora"></div>
			<div class="mt-1 flex justify-between font-mono text-[10px] text-muted-foreground"><span>0 h</span><span>6 h</span><span>12 h</span><span>18 h</span><span>23 h</span></div>
			<p id="rx-horas-nota" class="mt-3 text-xs text-muted-foreground">Recolectando datos…</p>
		</div>
		<div class="rounded-2xl border border-border/70 bg-card p-5 sm:p-6">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Ahora</p>
			<dl id="rx-datos" class="space-y-2 text-sm">
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Canales libres</dt><dd id="rx-d-libres" class="font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Ocupados</dt><dd id="rx-d-users" class="font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Ceden el canal</dt><dd id="rx-d-pre" class="font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Antena</dt><dd id="rx-d-ant" class="text-right font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Encendido hace</dt><dd id="rx-d-up" class="font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Versión</dt><dd id="rx-d-ver" class="font-mono">—</dd></div>
			</dl>
			<p class="mt-4 text-xs text-muted-foreground">«Ceden el canal» son decodificadores automáticos (FT8, WSPR) que se apartan cuando entra una persona.</p>
		</div>
	</div>
</section>

<script>
(function(){
	var AJAX=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

	function uptime(s){
		if(s==null) return '—';
		var d=Math.floor(s/86400), h=Math.floor(s%86400/3600);
		return d ? d+' d '+h+' h' : h+' h '+Math.floor(s%3600/60)+' min';
	}
	function estado(){
		fetch(AJAX+'?action=rau_sdr_status', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
			var el=document.getElementById('rx-estado');
			if(!j.success){ el.innerHTML='<span class="h-2 w-2 rounded-full bg-rose-400"></span> Receptor fuera de línea'; return; }
			var d=j.data, lleno=d.libres===0;
			el.innerHTML='<span class="h-2 w-2 rounded-full '+(lleno?'bg-amber-400':'bg-emerald-400')+'"></span> '+(lleno?'Todos los canales ocupados':(d.libres+' de '+d.max+' canales libres'));
			document.getElementById('rx-max').textContent=d.max;
			document.getElementById('rx-d-libres').textContent=d.libres==null?'—':d.libres;
			document.getElementById('rx-d-users').textContent=d.users==null?'—':d.users+' de '+d.max;
			document.getElementById('rx-d-pre').textContent=d.preempt;
			document.getElementById('rx-d-ant').textContent=d.antenna||'—';
			document.getElementById('rx-d-up').textContent=uptime(d.uptime);
			document.getElementById('rx-d-ver').textContent=d.version||'—';
		}).catch(function(e){ rauErr(e); });
	}
	function horas(){
		fetch(AJAX+'?action=rau_sdr_historial', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
			if(!j.success) return;
			var cont=document.getElementById('rx-horas'), nota=document.getElementById('rx-horas-nota');
			var hs=j.data.horas, max=Math.max(1, Math.max.apply(null, hs.map(function(h){ return h.libres||0; })));
			var ahora=new Date().toLocaleString('en-US',{timeZone:'America/Bogota',hour:'numeric',hour12:false})|0;
			cont.innerHTML=hs.map(function(h){
				var alto=h.libres==null?0:Math.max(4, Math.round(100*h.libres/max));
				var titulo=h.libres==null?(h.hora+' h: sin datos'):(h.hora+' h: '+h.libres+' libres en promedio, '+h.con_canal+' % del tiempo con canal');
				return '<div title="'+titulo+'" class="flex-1 rounded-t '+(h.hora===ahora?'bg-accent':'bg-primary/60')+'" style="height:'+alto+'%"></div>';
			}).join('');
			if(j.data.muestras<36){ nota.textContent='Recolectando datos: '+j.data.muestras+' mediciones hasta ahora. En un par de días la gráfica será confiable.'; return; }
			var mejores=hs.filter(function(h){ return h.libres!=null; }).sort(function(a,b){ return b.libres-a.libres; }).slice(0,3).map(function(h){ return h.hora+' h'; });
			nota.textContent='Mejores horas para conseguir canal: '+mejores.join(', ')+'. La barra resaltada es la hora actual.';
		}).catch(function(e){ rauErr(e); });
	}
	estado(); horas();
	setInterval(estado, 60000);
})();
</script>

<?php if ( $relay ) : ?>
<script>
/* ── Receptor KiwiSDR integrado ────────────────────────────────────────────────────────────────
   Protocolo del KiwiSDR (web/openwebrx/openwebrx.js, web/openwebrx/audio.js, kiwiclient):
   - Dos WebSocket: <relevo>/ws/kiwi/<marca>/SND (audio) y .../W/F (cascada). Cada uno empieza con
     "SET auth t=kiwi p=". El servidor manda texto "MSG clave=valor ..." y binarios con etiqueta de
     3 letras: "SND" y "W/F".
   - Audio: al recibir audio_rate se responde "SET AR OK in=<tasa> out=<tasa del navegador>"; al
     recibir sample_rate se configura modo, AGC y compresión. Trama SND: etiqueta(3) flags(1)
     secuencia(4, LE) S-metro(2, BE; dBm = 0.1·valor − 127) y muestras: IMA-ADPCM si el bit 0x10 de
     flags está activo (el estado del decodificador sigue de una trama a la siguiente), si no PCM de
     16 bits big-endian. Mono, unas 12 000 muestras por segundo.
   - Cascada: con "SET wf_comp=0" y "SET send_dB=1", cada trama W/F trae etiqueta(3) relleno(1)
     x_bin(4) zoom|flags(4) secuencia(4) y 1024 bytes; dBm = −(255 − byte). El borde izquierdo en
     frecuencia es x_bin / (fft · 2^zoom_max) · ancho de banda.
   - "SET keepalive" periódico en las dos conexiones, o el receptor cierra la sesión. */
(function(){
	var RELAY=<?php echo wp_json_encode( preg_replace( '#^http(s?)://#', 'ws$1://', $relay ) ); ?>;
	var BANDAS=[
		{n:'160 m',f:1840,m:'lsb'},{n:'80 m',f:3700,m:'lsb'},{n:'40 m',f:7150,m:'lsb'},{n:'30 m',f:10120,m:'usb'},
		{n:'20 m',f:14200,m:'usb'},{n:'17 m',f:18130,m:'usb'},{n:'15 m',f:21250,m:'usb'},{n:'12 m',f:24950,m:'usb'},
		{n:'10 m',f:28500,m:'usb'},{n:'FT8 20 m',f:14074,m:'usb'},{n:'Onda corta 49 m',f:6000,m:'am'},
		{n:'Onda corta 31 m',f:9650,m:'am'},{n:'WWV 10 MHz',f:10000,m:'am'}
	];
	var MODOS={am:{n:'AM',lo:-4900,hi:4900},lsb:{n:'LSB',lo:-2700,hi:-300},usb:{n:'USB',lo:300,hi:2700},cw:{n:'CW',lo:300,hi:700},nbfm:{n:'FM',lo:-6000,hi:6000}};
	var STEP=[7,8,9,10,11,12,13,14,16,17,19,21,23,25,28,31,34,37,41,45,50,55,60,66,73,80,88,97,107,118,130,143,157,173,190,209,230,253,279,307,337,371,408,449,494,544,598,658,724,796,876,963,1060,1166,1282,1411,1552,1707,1878,2066,2272,2499,2749,3024,3327,3660,4026,4428,4871,5358,5894,6484,7132,7845,8630,9493,10442,11487,12635,13899,15289,16818,18500,20350,22385,24623,27086,29794,32767];
	var ADJ=[-1,-1,-1,-1,2,4,6,8,-1,-1,-1,-1,2,4,6,8];

	var st={freq:7150, modo:'lsb', zoom:7, zoomMax:14, fft:1024, bw:30000000, on:false, snd:null, wf:null, ctx:null, gain:null, t:0, dec:{i:0,p:0}, rate:12000, rpos:0, rprev:0, ka:null};
	try { var g=JSON.parse(localStorage.getItem('rau_rx')||'null'); if(g&&g.freq){ st.freq=g.freq; st.modo=MODOS[g.modo]?g.modo:st.modo; st.zoom=g.zoom|0||7; } } catch(e){}
	var $=function(id){ return document.getElementById(id); };
	var canvas=$('rx-wf'), c2d=canvas.getContext('2d'), wfStart=0, wfSpan=0;

	function msg(t){ $('rx-msg').textContent=t; }
	function guardar(){ try { localStorage.setItem('rau_rx', JSON.stringify({freq:st.freq, modo:st.modo, zoom:st.zoom})); } catch(e){} }
	function pintarFreq(){ $('rx-freq').textContent=st.freq.toFixed(2); $('rx-input').value=st.freq.toFixed(2); $('rx-zoom').textContent=st.zoom;
		document.querySelectorAll('.rx-modo').forEach(function(b){ var on=b.dataset.m===st.modo; b.classList.toggle('bg-primary',on); b.classList.toggle('text-primary-foreground',on); }); }
	function send(ws, t){ if(ws && ws.readyState===1) ws.send(t); }

	function sintonizar(f, modo){
		st.freq=Math.min(30000, Math.max(10, Math.round(f*100)/100));
		if(modo) st.modo=modo;
		var p=MODOS[st.modo];
		send(st.snd, 'SET mod='+st.modo+' low_cut='+p.lo+' high_cut='+p.hi+' freq='+st.freq.toFixed(3));
		zoomA(st.zoom);
		pintarFreq(); guardar(); marca();
	}
	function zoomA(z){
		st.zoom=Math.max(0, Math.min(st.zoomMax, z));
		send(st.wf, 'SET zoom='+st.zoom+' cf='+st.freq.toFixed(3));
		$('rx-zoom').textContent=st.zoom;
	}
	function marca(){
		if(!wfSpan){ $('rx-marca').style.left='50%'; return; }
		var x=(st.freq*1000-wfStart)/wfSpan;
		$('rx-marca').style.left=(Math.max(0,Math.min(1,x))*100)+'%';
	}

	/* IMA-ADPCM, igual que ImaAdpcmDecoder de kiwiclient */
	function adpcm(bytes, dec){
		var out=new Float32Array(bytes.length*2), k=0;
		function uno(code){
			var step=STEP[dec.i]; dec.i=Math.max(0,Math.min(88,dec.i+ADJ[code]));
			var diff=step>>3; if(code&1) diff+=step>>2; if(code&2) diff+=step>>1; if(code&4) diff+=step; if(code&8) diff=-diff;
			dec.p=Math.max(-32768,Math.min(32767,dec.p+diff)); return dec.p;
		}
		for(var i=0;i<bytes.length;i++){ var b=bytes[i]; out[k++]=uno(b&15)/32768; out[k++]=uno(b>>4)/32768; }
		return out;
	}
	/* Remuestreo lineal continuo de la tasa del receptor (~12 kHz) a la del navegador. Se hace aquí y
	   no con createBuffer(…, 12000) porque Safari antiguo no acepta buffers por debajo de 22 kHz. */
	function remuestrear(inp){
		var r=st.rate/st.ctx.sampleRate, out=[], pos=st.rpos;
		while(pos<inp.length-1){ var i=Math.floor(pos), f=pos-i, a=i<0?st.rprev:inp[i]; out.push(a+(inp[i+1]-a)*f); pos+=r; }
		st.rpos=pos-inp.length; st.rprev=inp[inp.length-1];
		return Float32Array.from(out);
	}
	function reproducir(muestras){
		var ctx=st.ctx; if(!ctx || !muestras.length) return;
		muestras=remuestrear(muestras); if(!muestras.length) return;
		var buf=ctx.createBuffer(1, muestras.length, ctx.sampleRate); buf.getChannelData(0).set(muestras);
		var src=ctx.createBufferSource(); src.buffer=buf; src.connect(st.gain);
		var ahora=ctx.currentTime;
		if(st.t<ahora+0.05) st.t=ahora+0.25;          // buffer vacío: margen para la variación de la red
		if(st.t>ahora+1.2) return;                    // demasiado atrasado: descarta para no acumular eco
		src.start(st.t); st.t+=buf.duration;
	}
	function smeter(raw){
		var dbm=0.1*raw-127, s=dbm<=-73 ? Math.max(0,Math.round((dbm+127)/6)) : 9;
		var txt=dbm<=-73 ? 'S'+s : 'S9+'+Math.round(dbm+73)+' dB';
		$('rx-s').textContent=txt+' · '+Math.round(dbm)+' dBm';
		$('rx-smeter').style.width=Math.max(0,Math.min(100,(dbm+127)/(127-33)*100))+'%';
	}
	function pares(t){ var o={}; t.split(' ').forEach(function(p){ var i=p.indexOf('='); if(i>0){ var v=p.slice(i+1); try { v=decodeURIComponent(v); } catch(e){} o[p.slice(0,i)]=v; } else if(p) o[p]=''; }); return o; }

	function avisoMsg(o){
		if('too_busy' in o){ msg('Los '+o.too_busy+' canales del receptor están ocupados. Mira abajo a qué horas suele haber canal libre.'); apagar(true); return true; }
		if('badp' in o && o.badp!=='0'){ msg('El receptor no aceptó la conexión (todos los canales libres requieren clave).'); apagar(true); return true; }
		if('down' in o){ msg('El receptor está en mantenimiento. Intenta más tarde.'); apagar(true); return true; }
		if('inactivity_timeout' in o || 'tlimit_exp' in o){ msg('El receptor cerró la sesión por tiempo. Pulsa «Escuchar» para volver.'); apagar(true); return true; }
		return false;
	}

	function abrir(stream, onmsg, onbin){
		var ws=new WebSocket(RELAY+'/ws/kiwi/'+Date.now()+'/'+stream);
		ws.binaryType='arraybuffer';
		ws.onopen=function(){ ws.send('SET auth t=kiwi p='); ws.send('SERVER DE CLIENT hk4rau.com '+stream); };
		ws.onmessage=function(ev){
			if(typeof ev.data==='string') return;
			var u8=new Uint8Array(ev.data), tag=String.fromCharCode(u8[0],u8[1],u8[2]);
			if(tag==='MSG'){ var o=pares(new TextDecoder().decode(u8.subarray(4))); if(!avisoMsg(o)) onmsg(o); }
			else onbin(tag, ev.data, u8);
		};
		ws.onerror=function(){ if(st.on) msg('No fue posible conectar con el receptor. Revisa tu conexión o intenta en unos minutos.'); };
		ws.onclose=function(){ if(st.on){ msg('Se cerró la conexión con el receptor.'); apagar(true); } };
		return ws;
	}

	function encender(){
		st.ctx=st.ctx||new (window.AudioContext||window.webkitAudioContext)();
		st.ctx.resume();
		st.gain=st.gain||st.ctx.createGain(); st.gain.connect(st.ctx.destination); volumen();
		st.on=true; st.t=0; st.dec={i:0,p:0}; st.rpos=0; st.rprev=0;
		$('rx-power').textContent='■ Detener';
		msg('Conectando con el receptor…');
		st.snd=abrir('SND', function(o){
			if(o.audio_rate) send(st.snd, 'SET AR OK in='+o.audio_rate+' out='+Math.round(st.ctx.sampleRate));
			if(o.sample_rate){
				st.rate=Math.round(parseFloat(o.sample_rate));
				send(st.snd, 'SET squelch=0 max=0'); send(st.snd, 'SET genattn=0'); send(st.snd, 'SET gen=0 mix=-1');
				send(st.snd, 'SET agc=1 hang=0 thresh=-100 slope=6 decay=1000 manGain=50');
				send(st.snd, 'SET compression=1'); send(st.snd, 'SET ident_user=hk4rau.com');
				sintonizar(st.freq, st.modo);
				msg('Escuchando. Toca la cascada o elige una banda para cambiar de frecuencia.');
			}
			if(o.audio_adpcm_state){ var v=o.audio_adpcm_state.split(','); st.dec={i:+v[0]|0,p:+v[1]|0}; }
		}, function(tag, buf, u8){
			if(tag!=='SND') return;
			var flags=u8[3], dv=new DataView(buf);
			smeter(dv.getUint16(8, false));
			var datos=u8.subarray(10), m;
			if(flags&0x10) m=adpcm(datos, st.dec);
			else { m=new Float32Array(datos.length>>1); for(var i=0;i<m.length;i++) m[i]=dv.getInt16(10+2*i,false)/32768; }
			reproducir(m);
		});
		st.wf=abrir('W/F', function(o){
			if(o.wf_fft_size) st.fft=+o.wf_fft_size;
			if(o.zoom_max) st.zoomMax=+o.zoom_max;
			if(o.bandwidth) st.bw=+o.bandwidth;
			if('wf_setup' in o){
				send(st.wf, 'SET send_dB=1'); send(st.wf, 'SET wf_comp=0'); send(st.wf, 'SET maxdb=-20 mindb=-120'); send(st.wf, 'SET wf_speed=4');
				zoomA(st.zoom);
			}
		}, function(tag, buf){
			if(tag!=='W/F') return;
			var dv=new DataView(buf), xbin=dv.getUint32(4,true), z=dv.getUint32(8,true)&0xffff;
			var datos=new Uint8Array(buf,16);
			var maxBins=st.fft*Math.pow(2,st.zoomMax);
			wfStart=xbin/maxBins*st.bw; wfSpan=st.bw/Math.pow(2,z);
			$('rx-esc-izq').textContent=(wfStart/1000).toFixed(1)+' kHz'; $('rx-esc-der').textContent=((wfStart+wfSpan)/1000).toFixed(1)+' kHz';
			linea(datos); marca();
		});
		clearInterval(st.ka);
		st.ka=setInterval(function(){ send(st.snd,'SET keepalive'); send(st.wf,'SET keepalive'); }, 3000);
	}
	function apagar(silencioso){
		st.on=false; clearInterval(st.ka);
		[st.snd, st.wf].forEach(function(ws){ if(ws){ ws.onclose=null; try{ ws.close(); }catch(e){} } });
		st.snd=st.wf=null;
		if(st.gain) try{ st.gain.disconnect(); }catch(e){}
		$('rx-power').textContent='▶ Escuchar'; $('rx-s').textContent='—'; $('rx-smeter').style.width='0';
		if(!silencioso) msg('Desconectado. El canal quedó libre para otra persona.');
	}

	/* Cascada con la paleta del sitio: fondo → primario (cian) → blanco. Piso de ruido automático. */
	var pal=(function(){
		var css=getComputedStyle(document.documentElement), hsl=function(v){ return (css.getPropertyValue(v)||'').trim().split(/\s+/).map(parseFloat); };
		var bg=hsl('--background'), pr=hsl('--primary');
		function rgb(h,s,l){ s/=100; l/=100; var k=function(n){ return (n+h/30)%12; }, a=s*Math.min(l,1-l), f=function(n){ return l-a*Math.max(-1,Math.min(k(n)-3,Math.min(9-k(n),1))); }; return [255*f(0),255*f(8),255*f(4)]; }
		var c0=bg.length===3?rgb(bg[0],bg[1],bg[2]):[8,12,24], c1=pr.length===3?rgb(pr[0],pr[1],pr[2]):[31,213,249], out=[];
		for(var i=0;i<256;i++){ var t=i/255, a, b, u; if(t<0.7){ a=c0; b=c1; u=t/0.7; } else { a=c1; b=[255,255,255]; u=(t-0.7)/0.3; }
			out.push([a[0]+(b[0]-a[0])*u, a[1]+(b[1]-a[1])*u, a[2]+(b[2]-a[2])*u]); }
		return out;
	})();
	var piso=-110;
	function linea(datos){
		var w=canvas.width, h=canvas.height;
		c2d.drawImage(canvas, 0, 0, w, h-1, 0, 1, w, h-1);
		var img=c2d.createImageData(w,1), n=datos.length, orden=Array.prototype.slice.call(datos).sort(function(a,b){ return a-b; });
		piso=piso*0.9+(-(255-orden[n>>2]))*0.1;           // cuartil inferior como piso de ruido
		for(var x=0;x<w;x++){
			var dbm=-(255-datos[Math.floor(x*n/w)]), v=Math.max(0,Math.min(255,Math.round((dbm-piso+3)/45*255))), c=pal[v];
			img.data[4*x]=c[0]; img.data[4*x+1]=c[1]; img.data[4*x+2]=c[2]; img.data[4*x+3]=255;
		}
		c2d.putImageData(img,0,0);
	}

	function volumen(){ if(st.gain) st.gain.gain.value=Math.pow($('rx-vol').value/100,2)*2; }

	$('rx-bandas').innerHTML=BANDAS.map(function(b,i){ return '<button type="button" data-i="'+i+'" class="rx-banda rounded-md border border-border bg-background px-3 py-2 font-mono text-xs hover:border-primary/50">'+b.n+'</button>'; }).join('');
	$('rx-modos').innerHTML=Object.keys(MODOS).map(function(k){ return '<button type="button" data-m="'+k+'" class="rx-modo rounded-md border border-border bg-background px-3 py-2 font-mono text-xs">'+MODOS[k].n+'</button>'; }).join('');
	document.querySelectorAll('.rx-banda').forEach(function(b){ b.addEventListener('click', function(){ var x=BANDAS[b.dataset.i]; sintonizar(x.f, x.m); }); });
	document.querySelectorAll('.rx-modo').forEach(function(b){ b.addEventListener('click', function(){ sintonizar(st.freq, b.dataset.m); }); });
	document.querySelectorAll('.rx-paso').forEach(function(b){ b.addEventListener('click', function(){ sintonizar(st.freq+parseFloat(b.dataset.paso)); }); });
	$('rx-input').addEventListener('change', function(){ var f=parseFloat(this.value); if(f>0) sintonizar(f); });
	$('rx-zoom-mas').addEventListener('click', function(){ zoomA(st.zoom+1); guardar(); });
	$('rx-zoom-menos').addEventListener('click', function(){ zoomA(st.zoom-1); guardar(); });
	$('rx-vol').addEventListener('input', volumen);
	canvas.addEventListener('click', function(ev){
		if(!wfSpan) return;
		var r=canvas.getBoundingClientRect(), f=(wfStart+(ev.clientX-r.left)/r.width*wfSpan)/1000;
		sintonizar(st.modo==='am'||st.modo==='nbfm' ? Math.round(f) : Math.round(f*10)/10);
	});
	$('rx-power').addEventListener('click', function(){ st.on ? apagar() : encender(); });
	window.addEventListener('pagehide', function(){ if(st.on) apagar(true); });
	pintarFreq();
})();
</script>
<?php endif; ?>
<?php get_footer(); ?>
