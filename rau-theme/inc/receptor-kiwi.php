<?php
/**
 * Receptor KiwiSDR de RAU: componente compartido por la página /receptor/ y la herramienta
 * "Receptor KiwiSDR" de Herramientas. Va en línea (HTML + JS) por la misma razón que el resto del
 * JavaScript de Herramientas: servido como archivo aparte falló en este alojamiento (verificar.php, 16).
 *
 * Protocolo del KiwiSDR (web/openwebrx/openwebrx.js, audio.js y kiwiclient del proyecto oficial):
 *  - Dos WebSocket con la MISMA marca de tiempo: <relevo>/ws/kiwi/<marca>/SND (audio) y /W/F
 *    (cascada). Con marcas distintas el receptor no los empareja y la cascada llega vacía.
 *  - Cada uno empieza con "SET auth t=kiwi p=". El servidor manda texto "MSG clave=valor ..." y
 *    binarios con etiqueta de 3 letras ("SND", "W/F").
 *  - Audio: audio_rate → "SET AR OK in=<tasa> out=<navegador>"; sample_rate → modo, AGC y compresión.
 *    Trama SND: etiqueta(3) flags(1) secuencia(4 LE) S-metro(2 BE; dBm = 0.1·v − 127) y muestras
 *    IMA-ADPCM si flags & 0x10 (el estado sigue entre tramas) o PCM 16 bits big-endian.
 *  - Cascada con "SET wf_comp=0" y "SET send_dB=1": etiqueta(3) relleno(1) x_bin(4) zoom(4)
 *    secuencia(4) y 1024 bytes, dBm = byte − 255. Borde izquierdo = x_bin / (fft · 2^zoom_max) · ancho.
 *  - "SET keepalive" periódico en las dos conexiones.
 *
 * Expone window.RauRx (sintonizar, encender) y window.rauEscuchar(kHz, pista), que usan otras
 * herramientas para abrir el receptor en una frecuencia ("Escuchar" en los spots de DX y POTA).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Plan de banda que se dibuja sobre la cascada. Bandas de radioaficionado de la Región 2 de la
 * IARU, que adopta el Decreto 1078 de 2015 (art. 2.2.5.6.1), y los segmentos de la licencia de
 * Novicio del art. 2.2.5.6.5. Frecuencias de FT8 y de las señales horarias WWV para orientarse.
 */
function rau_receptor_plan() {
	return array(
		'ham'     => array( array( 1800, 2000, '160 m' ), array( 3500, 4000, '80 m' ), array( 7000, 7300, '40 m' ), array( 10100, 10150, '30 m' ), array( 14000, 14350, '20 m' ), array( 18068, 18168, '17 m' ), array( 21000, 21450, '15 m' ), array( 24890, 24990, '12 m' ), array( 28000, 29700, '10 m' ) ),
		'novicio' => array( array( 1800, 2000, 'CW y fonía' ), array( 3500, 3525, 'CW' ), array( 3525, 3750, 'CW y fonía' ), array( 7000, 7040, 'CW' ), array( 7040, 7300, 'CW y fonía' ), array( 21000, 21450, 'CW' ), array( 28000, 28300, 'CW' ), array( 28300, 29500, 'CW y fonía' ) ),
		'marcas'  => array( array( 1840, 'FT8' ), array( 3573, 'FT8' ), array( 7074, 'FT8' ), array( 10136, 'FT8' ), array( 14074, 'FT8' ), array( 18100, 'FT8' ), array( 21074, 'FT8' ), array( 24915, 'FT8' ), array( 28074, 'FT8' ), array( 2500, 'WWV' ), array( 5000, 'WWV' ), array( 10000, 'WWV' ), array( 15000, 'WWV' ), array( 20000, 'WWV' ), array( 25000, 'WWV' ) ),
	);
}

function rau_receptor_kiwi( $args = array() ) {
	$args  = wp_parse_args( $args, array( 'estado' => true ) );
	$relay = get_option( 'rau_kiwi_relay', '' );
	$btn   = 'rounded-md border border-border bg-background px-3 py-2 font-mono text-xs hover:border-primary/50';
	$sel   = 'rounded-md border border-border bg-background px-2 py-2 font-mono text-xs';
	?>
	<?php if ( $relay ) : ?>
	<div id="rx-app" tabindex="0" class="rounded-2xl border border-primary/30 bg-card p-4 outline-none sm:p-6" aria-label="Receptor KiwiSDR: usa las flechas para sintonizar">
		<div class="flex flex-wrap items-center justify-between gap-4">
			<div>
				<p class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Frecuencia · <span id="rx-modo-txt">LSB</span></p>
				<p class="font-mono text-4xl font-bold tabular-nums text-primary sm:text-5xl"><span id="rx-freq">7150.00</span> <span class="text-base text-muted-foreground">kHz</span></p>
			</div>
			<div class="min-w-[12rem] flex-1 sm:max-w-xs">
				<div class="flex items-center justify-between font-mono text-[11px] text-muted-foreground"><span>Señal</span><span id="rx-s">—</span></div>
				<div class="relative mt-1 h-2 overflow-hidden rounded-full bg-background"><div id="rx-smeter" class="h-full w-0 rounded-full bg-primary transition-all duration-150"></div><div id="rx-sq-marca" class="absolute inset-y-0 hidden w-0.5 bg-accent"></div></div>
			</div>
			<div class="flex flex-wrap items-center gap-2">
				<button id="rx-power" type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-6 text-sm font-semibold text-primary-foreground hover:bg-primary/90">▶ Escuchar</button>
				<button id="rx-mute" type="button" class="<?php echo esc_attr( $btn ); ?> h-11" aria-pressed="false" title="Silenciar">🔊</button>
				<button id="rx-rec" type="button" class="<?php echo esc_attr( $btn ); ?> h-11" aria-pressed="false" title="Grabar lo que suena (WAV)">● Grabar</button>
				<button id="rx-share" type="button" class="<?php echo esc_attr( $btn ); ?> h-11" title="Compartir esta frecuencia">Compartir</button>
			</div>
		</div>

		<div id="rx-pantalla" class="relative mt-5 touch-none select-none overflow-hidden rounded-xl border border-border/60 bg-background" style="cursor:crosshair">
			<canvas id="rx-plan" class="block h-[20px] w-full" aria-hidden="true"></canvas>
			<canvas id="rx-esp" class="block h-[80px] w-full" aria-hidden="true"></canvas>
			<canvas id="rx-wf" width="1024" height="200" class="block h-[200px] w-full" aria-label="Cascada de frecuencias: toca para sintonizar, arrastra para recorrer la banda, usa la rueda para ajustar"></canvas>
			<canvas id="rx-esc" class="block h-[22px] w-full" aria-hidden="true"></canvas>
			<div id="rx-filtro" class="pointer-events-none absolute bg-white/10" style="top:20px;height:280px;left:50%;width:0"></div>
			<div id="rx-marca" class="pointer-events-none absolute w-px bg-accent" style="top:20px;height:280px;left:50%"></div>
			<p id="rx-ayuda" class="pointer-events-none absolute inset-x-0 top-[140px] text-center font-mono text-xs text-muted-foreground">Pulsa «Escuchar». Luego toca la cascada para sintonizar.</p>
		</div>

		<div class="mt-5">
			<p class="mb-2 font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Bandas</p>
			<div id="rx-bandas" class="flex flex-wrap gap-2"></div>
		</div>

		<div class="mt-5 flex flex-wrap items-end gap-x-6 gap-y-4">
			<div>
				<p class="mb-2 font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Modo</p>
				<div id="rx-modos" class="flex flex-wrap gap-2"></div>
			</div>
			<label class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Filtro<br>
				<select id="rx-filtro-sel" class="<?php echo esc_attr( $sel ); ?> mt-2 normal-case tracking-normal"><option value="0">Angosto</option><option value="1" selected>Normal</option><option value="2">Ancho</option></select>
			</label>
			<label class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Paso<br>
				<select id="rx-paso-sel" class="<?php echo esc_attr( $sel ); ?> mt-2 normal-case tracking-normal"><option value="0.01">10 Hz</option><option value="0.1">100 Hz</option><option value="1" selected>1 kHz</option><option value="5">5 kHz</option></select>
			</label>
			<div>
				<p class="mb-2 font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Sintonía</p>
				<div class="flex flex-wrap items-center gap-1">
					<button type="button" data-pasos="-1" class="rx-paso <?php echo esc_attr( $btn ); ?>" aria-label="Bajar un paso">◀</button>
					<label class="sr-only" for="rx-input">Frecuencia en kHz</label>
					<input id="rx-input" type="number" inputmode="decimal" step="0.01" min="10" max="30000" class="w-28 rounded-md border border-border bg-background px-3 py-2 font-mono text-sm" placeholder="kHz">
					<button type="button" data-pasos="1" class="rx-paso <?php echo esc_attr( $btn ); ?>" aria-label="Subir un paso">▶</button>
					<button type="button" id="rx-zoom-menos" class="<?php echo esc_attr( $btn ); ?>" aria-label="Alejar cascada">−</button>
					<span class="px-1 font-mono text-xs text-muted-foreground">Zoom <span id="rx-zoom">7</span></span>
					<button type="button" id="rx-zoom-mas" class="<?php echo esc_attr( $btn ); ?>" aria-label="Acercar cascada">+</button>
				</div>
			</div>
		</div>

		<div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
			<label class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Colores de la cascada
				<select id="rx-paleta" class="<?php echo esc_attr( $sel ); ?> mt-2 block w-full normal-case tracking-normal">
					<option value="ambar">Ámbar (trazas amarillas)</option><option value="rau">Cian RAU</option><option value="clasica">Clásica KiwiSDR</option><option value="turbo">Turbo</option><option value="fosforo">Fósforo verde</option><option value="grises">Grises</option>
				</select>
			</label>
			<label class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Contraste <span id="rx-contraste-v" class="normal-case tracking-normal">30 dB</span>
				<input id="rx-contraste" type="range" min="15" max="60" value="30" class="mt-3 block w-full accent-primary">
			</label>
			<label class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Silenciador <span id="rx-sq-v" class="normal-case tracking-normal">apagado</span>
				<input id="rx-sq" type="range" min="0" max="9" value="0" class="mt-3 block w-full accent-primary">
			</label>
			<div class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Volumen
				<div class="mt-2 flex items-center gap-3"><input id="rx-vol" type="range" min="0" max="100" value="60" class="w-full accent-primary" aria-label="Volumen">
				<label class="flex shrink-0 items-center gap-1 whitespace-nowrap normal-case tracking-normal"><input id="rx-agc" type="checkbox" checked class="accent-primary"> AGC</label></div>
			</div>
		</div>

		<div class="mt-5">
			<div class="mb-2 flex items-center justify-between gap-3"><p class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Favoritos</p><button type="button" id="rx-fav-add" class="font-mono text-xs text-primary hover:underline">★ Guardar esta frecuencia</button></div>
			<div id="rx-favs" class="flex flex-wrap gap-2"><p class="text-xs text-muted-foreground">Guarda aquí las frecuencias que más escuchas. Quedan en este navegador.</p></div>
		</div>

		<p id="rx-msg" class="mt-4 font-mono text-xs text-muted-foreground" role="status" aria-live="polite">Pulsa «Escuchar» para conectarte. Usarás uno de los canales del receptor mientras escuchas.</p>
	</div>
	<div id="rx-mini" style="bottom:calc(1rem + env(safe-area-inset-bottom));left:1rem" class="fixed z-50 hidden items-center gap-2 rounded-full border border-primary/40 bg-card px-3 py-2 font-mono text-xs shadow-lg" role="region" aria-label="Receptor sonando">
		<span class="text-primary">🎧 <span id="rx-mini-f">—</span></span>
		<button type="button" id="rx-mini-ver" class="rounded-full border border-border px-2 py-1 hover:border-primary/50">Ver</button>
		<button type="button" id="rx-mini-stop" class="rounded-full border border-border px-2 py-1 hover:border-primary/50" aria-label="Detener el receptor">■</button>
	</div>
	<p class="mt-3 text-xs text-muted-foreground">En computador: flechas ← → para sintonizar un paso, ↑ ↓ para diez. ¿Necesitas decodificadores o «acampar» cuando está lleno? Usa la <a href="<?php echo esc_url( $relay . '/' ); ?>" target="_blank" rel="noopener" class="text-primary hover:underline">interfaz completa del KiwiSDR</a>.</p>
	<?php else : ?>
	<div class="rounded-2xl border border-border/70 bg-card p-6">
		<h2 class="font-display text-xl font-bold">Escúchalo en la interfaz del KiwiSDR</h2>
		<p class="mt-2 max-w-2xl text-sm text-muted-foreground">El receptor todavía no tiene dirección segura (HTTPS), así que los navegadores no permiten escucharlo dentro de esta página. Mientras tanto se abre en su propia pestaña.</p>
		<a href="<?php echo esc_url( RAU_KIWI_HTTP . '/' ); ?>" target="_blank" rel="noopener" class="mt-4 inline-flex h-11 items-center justify-center rounded-md bg-primary px-6 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Abrir el KiwiSDR en otra pestaña</a>
	</div>
	<?php endif; ?>

	<?php if ( $args['estado'] ) : ?>
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
			<dl class="space-y-2 text-sm">
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Estado</dt><dd id="rx-estado" class="text-right font-mono text-xs">Consultando…</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Canales en uso</dt><dd id="rx-d-users" class="font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Ceden el canal</dt><dd id="rx-d-pre" class="font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Antena</dt><dd id="rx-d-ant" class="text-right font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Encendido hace</dt><dd id="rx-d-up" class="font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Versión</dt><dd id="rx-d-ver" class="font-mono">—</dd></div>
			</dl>
			<p class="mt-4 text-xs text-muted-foreground">El receptor topa la cuenta en su máximo: «7 de 7» no siempre significa lleno. Prueba igual. «Ceden el canal» son decodificadores automáticos que se apartan cuando entra una persona.</p>
		</div>
	</div>
	<script>
	(function(){
		var AJAX=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		function uptime(s){ if(s==null) return '—'; var d=Math.floor(s/86400), h=Math.floor(s%86400/3600); return d ? d+' d '+h+' h' : h+' h '+Math.floor(s%3600/60)+' min'; }
		function estado(){
			fetch(AJAX+'?action=rau_sdr_status', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
				var el=document.getElementById('rx-estado');
				if(!j.success){ el.innerHTML='<span class="text-rose-400">Fuera de línea</span>'; return; }
				var d=j.data;
				el.innerHTML=d.libres===0 ? '<span class="text-amber-400">Muy ocupado: prueba igual</span>' : '<span class="text-emerald-400">'+d.libres+' de '+d.max+' libres</span>';
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
				if(hs.every(function(h){ return !h.libres; })){ nota.textContent='Estos días el receptor ha estado casi siempre en su máximo: los decodificadores automáticos ocupan los canales. Aun así a veces se consigue uno.'; return; }
				var mejores=hs.filter(function(h){ return h.libres!=null; }).sort(function(a,b){ return b.libres-a.libres; }).slice(0,3).map(function(h){ return h.hora+' h'; });
				nota.textContent='Mejores horas para conseguir canal: '+mejores.join(', ')+'. La barra resaltada es la hora actual.';
			}).catch(function(e){ rauErr(e); });
		}
		estado(); horas(); setInterval(estado, 60000);
	})();
	</script>
	<?php endif; ?>

	<?php if ( $relay ) : ?>
	<script>
	(function(){
		var RELAY=<?php echo wp_json_encode( preg_replace( '#^http(s?)://#', 'ws$1://', $relay ) ); ?>;
		var PAGINA=<?php echo wp_json_encode( home_url( '/receptor/' ) ); ?>;
		var PLAN=<?php echo wp_json_encode( rau_receptor_plan() ); ?>;
		var BANDAS=[
			{n:'160 m',f:1840,m:'lsb'},{n:'80 m',f:3700,m:'lsb'},{n:'40 m',f:7150,m:'lsb'},{n:'30 m',f:10120,m:'cw'},
			{n:'20 m',f:14200,m:'usb'},{n:'17 m',f:18130,m:'usb'},{n:'15 m',f:21250,m:'usb'},{n:'12 m',f:24950,m:'usb'},
			{n:'10 m',f:28500,m:'usb'},{n:'FT8 20 m',f:14074,m:'usb'},{n:'Onda corta 49 m',f:6000,m:'am'},
			{n:'Onda corta 31 m',f:9650,m:'am'},{n:'WWV 10 MHz',f:10000,m:'am'}
		];
		// Bordes del filtro en Hz por modo: angosto, normal, ancho
		var MODOS={
			am:  {n:'AM',  f:[[-2500,2500],[-4900,4900],[-5500,5500]]},
			lsb: {n:'LSB', f:[[-2400,-300],[-2700,-300],[-3300,-200]]},
			usb: {n:'USB', f:[[300,2400],[300,2700],[200,3300]]},
			cw:  {n:'CW',  f:[[450,550],[300,700],[200,1000]]},
			nbfm:{n:'FM',  f:[[-6000,6000],[-6000,6000],[-6000,6000]]}
		};
		var PALETAS={
			ambar:  [[0,[6,8,16]],[0.3,[40,20,8]],[0.55,[180,83,9]],[0.78,[250,204,21]],[1,[255,251,220]]],
			rau:    null,
			clasica:[[0,[0,0,0]],[0.2,[0,0,150]],[0.4,[0,180,255]],[0.6,[0,255,0]],[0.8,[255,255,0]],[1,[255,0,0]]],
			turbo:  [[0,[48,18,59]],[0.15,[70,107,227]],[0.35,[27,207,212]],[0.5,[97,252,108]],[0.65,[210,233,53]],[0.8,[254,155,45]],[1,[122,4,3]]],
			fosforo:[[0,[2,8,4]],[0.6,[34,197,94]],[1,[220,252,231]]],
			grises: [[0,[0,0,0]],[1,[255,255,255]]]
		};
		var STEP=[7,8,9,10,11,12,13,14,16,17,19,21,23,25,28,31,34,37,41,45,50,55,60,66,73,80,88,97,107,118,130,143,157,173,190,209,230,253,279,307,337,371,408,449,494,544,598,658,724,796,876,963,1060,1166,1282,1411,1552,1707,1878,2066,2272,2499,2749,3024,3327,3660,4026,4428,4871,5358,5894,6484,7132,7845,8630,9493,10442,11487,12635,13899,15289,16818,18500,20350,22385,24623,27086,29794,32767];
		var ADJ=[-1,-1,-1,-1,2,4,6,8,-1,-1,-1,-1,2,4,6,8];

		var $=function(id){ return document.getElementById(id); };
		var st={freq:7150, modo:'lsb', filtro:1, paso:1, zoom:7, zoomMax:14, fft:1024, bw:30000000, on:false, snd:null, wf:null, ctx:null, gain:null,
			t:0, dec:{i:0,p:0}, rate:12000, rpos:0, rprev:0, ka:null, ts:0, paleta:'ambar', contraste:30, sq:0, agc:true, mute:false, sqAbierto:true, sqHasta:0,
			grab:null, esp:[], wfStart:0, wfSpan:0, piso:null, lut:null, ultXbin:-1, ultZoom:-1};
		try {
			var g=JSON.parse(localStorage.getItem('rau_rx')||'null');
			if(g){ if(g.freq) st.freq=g.freq; if(MODOS[g.modo]) st.modo=g.modo; if(g.zoom!=null) st.zoom=g.zoom|0; if(PALETAS.hasOwnProperty(g.paleta)) st.paleta=g.paleta;
				if(g.contraste) st.contraste=g.contraste; if(g.paso) st.paso=g.paso; if(g.filtro!=null) st.filtro=g.filtro; }
		} catch(e){}
		var q=new URLSearchParams(location.search);
		if(q.get('f') && +q.get('f')>0){ st.freq=+q.get('f'); st.modo=MODOS[q.get('m')]?q.get('m'):modoPara(st.freq,''); }

		function guardar(){ try { localStorage.setItem('rau_rx', JSON.stringify({freq:st.freq, modo:st.modo, zoom:st.zoom, paleta:st.paleta, contraste:st.contraste, paso:st.paso, filtro:st.filtro})); } catch(e){} }
		function msg(t){ $('rx-msg').textContent=t; }
		function send(ws, t){ if(ws && ws.readyState===1) ws.send(t); }

		/* Modo probable para una frecuencia: la pista (comentario del spot) manda; si no, AM en
		   radiodifusión y señales horarias, CW en los segmentos de telegrafía, y LSB/USB según la
		   convención de radioaficionado (por debajo / encima de 10 MHz). */
		function modoPara(f, pista){
			pista=(pista||'').toUpperCase();
			if(/\bCW\b/.test(pista)) return 'cw';
			if(/FT8|FT4|JS8|PSK|RTTY|DIGI|SSTV|USB/.test(pista)) return 'usb';
			if(/\bLSB\b/.test(pista)) return 'lsb';
			if(/\bAM\b/.test(pista)) return 'am';
			if(PLAN.marcas.some(function(m){ return m[1]==='WWV' && Math.abs(m[0]-f)<2; })) return 'am';
			var ham=PLAN.ham.some(function(b){ return f>=b[0] && f<=b[1]; });
			if(!ham) return 'am';
			if((f>=7000&&f<7040)||(f>=3500&&f<3570)||(f>=14000&&f<14070)||(f>=21000&&f<21070)||(f>=28000&&f<28070)||(f>=10100&&f<10130)||(f>=18068&&f<18095)||(f>=24890&&f<24915)) return 'cw';
			return f<10000 ? 'lsb' : 'usb';
		}

		/* ── Paletas y dibujo ─────────────────────────────────────────────── */
		function rgbDeVar(v, def){
			var p=(getComputedStyle(document.documentElement).getPropertyValue(v)||'').trim().split(/\s+/).map(parseFloat);
			if(p.length!==3||p.some(isNaN)) return def;
			var h=p[0], s=p[1]/100, l=p[2]/100, a=s*Math.min(l,1-l), k=function(n){ return (n+h/30)%12; };
			return [0,8,4].map(function(n){ return 255*(l-a*Math.max(-1,Math.min(k(n)-3,Math.min(9-k(n),1)))); });
		}
		function construirLut(){
			var stops=PALETAS[st.paleta];
			if(!stops) stops=[[0,rgbDeVar('--background',[8,12,24])],[0.7,rgbDeVar('--primary',[31,213,249])],[1,[255,255,255]]];
			st.lut=[];
			for(var i=0;i<256;i++){ var t=i/255, j=1; while(j<stops.length-1 && t>stops[j][0]) j++;
				var a=stops[j-1], b=stops[j], u=Math.max(0,Math.min(1,(t-a[0])/(b[0]-a[0])));
				st.lut.push([a[1][0]+(b[1][0]-a[1][0])*u, a[1][1]+(b[1][1]-a[1][1])*u, a[1][2]+(b[1][2]-a[1][2])*u]); }
			var c=st.lut[205]; st.trazo='rgb('+c.map(Math.round).join(',')+')'; st.relleno='rgba('+c.map(Math.round).join(',')+',.15)';
		}
		function ajustar(cv){ var r=cv.getBoundingClientRect(), d=window.devicePixelRatio||1; var w=Math.max(1,Math.round(r.width*d)), h=Math.max(1,Math.round(r.height*d)); if(cv.width!==w||cv.height!==h){ cv.width=w; cv.height=h; } return d; }
		function xDe(fk, w){ return (fk*1000-st.wfStart)/st.wfSpan*w; }

		function dibujarPlan(){
			var cv=$('rx-plan'), d=ajustar(cv), c=cv.getContext('2d'), w=cv.width, h=cv.height;
			c.clearRect(0,0,w,h); if(!st.wfSpan) return;
			c.font=(10*d)+'px ui-monospace,monospace'; c.textBaseline='middle';
			PLAN.ham.forEach(function(b){ var x0=xDe(b[0],w), x1=xDe(b[1],w); if(x1<0||x0>w) return;
				c.fillStyle='rgba(148,163,184,.12)'; c.fillRect(x0,0,x1-x0,h); });
			PLAN.novicio.forEach(function(s){ var x0=xDe(s[0],w), x1=xDe(s[1],w); if(x1<0||x0>w) return;
				var cw=s[2]==='CW'; c.fillStyle=cw?'rgba(59,130,246,.28)':'rgba(16,185,129,.25)'; c.fillRect(x0,0,x1-x0,h);
				c.fillStyle=cw?'#93c5fd':'#6ee7b7'; var tx='Novicio: '+s[2], xi=Math.max(x0,0)+4*d; if(Math.min(x1,w)-xi>c.measureText(tx).width) c.fillText(tx, xi, h/2); });
			// Bandas sin segmento de novicio (30 m, 20 m, 17 m…): el rótulo va sobre la propia banda
			PLAN.ham.forEach(function(b){ var x0=xDe(b[0],w), x1=xDe(b[1],w); if(x1<0||x0>w) return;
				if(PLAN.novicio.some(function(s){ return s[0]<b[1] && s[1]>b[0]; })) return;
				c.fillStyle='#94a3b8'; var xi=Math.max(x0,0)+4*d, libre=Math.min(x1,w)-xi;
				[b[2]+' · Primera y Avanzada', b[2]].some(function(tx){ if(libre>c.measureText(tx).width){ c.fillText(tx, xi, h/2); return true; } }); });
			PLAN.marcas.forEach(function(m){ var x=xDe(m[0],w); if(x<0||x>w) return; c.fillStyle='#fbbf24'; c.fillRect(x-d/2,0,d,h); c.fillText(m[1], x+3*d, h/2); });
		}
		function dibujarEscala(){
			var cv=$('rx-esc'), d=ajustar(cv), c=cv.getContext('2d'), w=cv.width, h=cv.height;
			c.clearRect(0,0,w,h); if(!st.wfSpan) return;
			var spanK=st.wfSpan/1000, pasos=[0.1,0.2,0.5,1,2,5,10,20,50,100,200,500,1000,2000,5000], ideal=spanK/(w/d/90), p=pasos.find(function(x){ return x>=ideal; })||5000;
			c.font=(10*d)+'px ui-monospace,monospace'; c.fillStyle='#94a3b8'; c.strokeStyle='#334155'; c.textAlign='center';
			for(var f=Math.ceil(st.wfStart/1000/p)*p; f<(st.wfStart+st.wfSpan)/1000; f+=p){ var x=xDe(f,w);
				c.beginPath(); c.moveTo(x,0); c.lineTo(x,5*d); c.stroke(); c.fillText(p<1?f.toFixed(1):Math.round(f), x, 15*d); }
			for(var f2=Math.ceil(st.wfStart/1000/(p/5))*(p/5); f2<(st.wfStart+st.wfSpan)/1000; f2+=p/5){ var x2=xDe(f2,w); c.beginPath(); c.moveTo(x2,0); c.lineTo(x2,2*d); c.stroke(); }
		}
		function dibujarEspectro(){
			var cv=$('rx-esp'), d=ajustar(cv), c=cv.getContext('2d'), w=cv.width, h=cv.height;
			c.clearRect(0,0,w,h); if(!st.esp.length) return;
			var n=st.esp[0].length, prom=new Float32Array(n);
			st.esp.forEach(function(l){ for(var i=0;i<n;i++) prom[i]+=l[i]/st.esp.length; });
			var suave=new Float32Array(n); for(var i=0;i<n;i++){ var a=0,k=0; for(var j=Math.max(0,i-2);j<=Math.min(n-1,i+2);j++){ a+=prom[j]; k++; } suave[i]=a/k; }
			var ord=Array.prototype.slice.call(suave).sort(function(a,b){ return a-b; }), mn=ord[Math.floor(n*0.05)]-2, mx=Math.max(ord[n-2]+3, mn+20);
			var y=function(v){ return Math.max(1,Math.min(h-1, h-2-(v-mn)/(mx-mn)*(h-6))); };
			c.beginPath(); c.moveTo(0,h); for(var x=0;x<n;x++) c.lineTo(x/n*w, y(suave[x])); c.lineTo(w,h); c.closePath(); c.fillStyle=st.relleno; c.fill();
			c.beginPath(); for(var x2=0;x2<n;x2++){ var X=x2/n*w, Y=y(suave[x2]); x2?c.lineTo(X,Y):c.moveTo(X,Y); } c.strokeStyle=st.trazo; c.lineWidth=1.2*d; c.stroke();
		}
		var wf=$('rx-wf'), w2d=wf.getContext('2d');
		function limpiarCascada(){ var c=st.lut?st.lut[0]:[0,0,0]; w2d.fillStyle='rgb('+c.map(Math.round).join(',')+')'; w2d.fillRect(0,0,wf.width,wf.height); }
		function lineaCascada(datos){
			var w=wf.width, h=wf.height, n=datos.length;
			w2d.drawImage(wf, 0, 0, w, h-1, 0, 1, w, h-1);
			var orden=Array.prototype.slice.call(datos).sort(function(a,b){ return a-b; });
			var med=orden[n>>1]-255;                               // mediana de la línea = ruido de fondo
			st.piso = st.piso===null ? med : st.piso*0.9+med*0.1;   // la primera línea fija el piso: sin esto, las primeras salían saturadas
			var img=w2d.createImageData(w,1);
			for(var x=0;x<w;x++){
				var dbm=datos[Math.floor(x*n/w)]-255, t=Math.max(0,Math.min(1,(dbm-st.piso-2)/st.contraste)), col=st.lut[Math.round(Math.pow(t,0.8)*255)];
				img.data[4*x]=col[0]; img.data[4*x+1]=col[1]; img.data[4*x+2]=col[2]; img.data[4*x+3]=255;
			}
			w2d.putImageData(img,0,0);
		}
		function marcas(){
			var pant=$('rx-pantalla').getBoundingClientRect().width;
			if(!st.wfSpan){ $('rx-marca').style.left='50%'; $('rx-filtro').style.width='0'; return; }
			var f=MODOS[st.modo].f[st.filtro], x=(st.freq*1000-st.wfStart)/st.wfSpan*pant;
			var a=((st.freq*1000+f[0])-st.wfStart)/st.wfSpan*pant, b=((st.freq*1000+f[1])-st.wfStart)/st.wfSpan*pant;
			$('rx-marca').style.left=Math.max(0,Math.min(pant,x))+'px';
			$('rx-filtro').style.left=Math.max(0,a)+'px'; $('rx-filtro').style.width=Math.max(1,Math.min(pant,b)-Math.max(0,a))+'px';
		}
		function redibujarFijos(){ dibujarPlan(); dibujarEscala(); dibujarEspectro(); marcas(); }

		/* ── Sintonía ─────────────────────────────────────────────────────── */
		function pintarFreq(){
			$('rx-freq').textContent=st.freq.toFixed(2); if(document.activeElement!==$('rx-input')) $('rx-input').value=st.freq.toFixed(2);
			$('rx-zoom').textContent=st.zoom; $('rx-modo-txt').textContent=MODOS[st.modo].n;
			document.querySelectorAll('.rx-modo').forEach(function(b){ var on=b.dataset.m===st.modo; b.classList.toggle('bg-primary',on); b.classList.toggle('text-primary-foreground',on); });
		}
		function enviarModo(){ var f=MODOS[st.modo].f[st.filtro]; send(st.snd, 'SET mod='+st.modo+' low_cut='+f[0]+' high_cut='+f[1]+' freq='+st.freq.toFixed(3)); }
		function sintonizar(f, modo, recentrar){
			st.freq=Math.min(30000, Math.max(10, Math.round(f*100)/100));
			if(modo && MODOS[modo]) st.modo=modo;
			enviarModo();
			if(recentrar!==false) zoomA(st.zoom);
			pintarFreq(); guardar(); marcas();
		}
		function zoomA(z){ st.zoom=Math.max(0, Math.min(st.zoomMax, z)); send(st.wf, 'SET zoom='+st.zoom+' cf='+st.freq.toFixed(3)); $('rx-zoom').textContent=st.zoom; }
		function redondear(f){ return Math.round(f/st.paso)*st.paso; }

		/* ── Audio ────────────────────────────────────────────────────────── */
		function adpcm(bytes, dec){
			var out=new Float32Array(bytes.length*2), k=0;
			function uno(code){ var step=STEP[dec.i]; dec.i=Math.max(0,Math.min(88,dec.i+ADJ[code]));
				var diff=step>>3; if(code&1) diff+=step>>2; if(code&2) diff+=step>>1; if(code&4) diff+=step; if(code&8) diff=-diff;
				dec.p=Math.max(-32768,Math.min(32767,dec.p+diff)); return dec.p; }
			for(var i=0;i<bytes.length;i++){ var b=bytes[i]; out[k++]=uno(b&15)/32768; out[k++]=uno(b>>4)/32768; }
			return out;
		}
		// Remuestreo lineal continuo de ~12 kHz a la tasa del navegador (Safari antiguo no acepta buffers de 12 kHz)
		function remuestrear(inp){
			var r=st.rate/st.ctx.sampleRate, out=[], pos=st.rpos;
			while(pos<inp.length-1){ var i=Math.floor(pos), f=pos-i, a=i<0?st.rprev:inp[i]; out.push(a+(inp[i+1]-a)*f); pos+=r; }
			st.rpos=pos-inp.length; st.rprev=inp[inp.length-1];
			return Float32Array.from(out);
		}
		function reproducir(m){
			var ctx=st.ctx; if(!ctx || !m.length) return;
			if(st.grab) grabar(m);
			m=remuestrear(m); if(!m.length) return;
			var buf=ctx.createBuffer(1, m.length, ctx.sampleRate); buf.getChannelData(0).set(m);
			var src=ctx.createBufferSource(); src.buffer=buf; src.connect(st.gain);
			var ahora=ctx.currentTime;
			if(st.t<ahora+0.05) st.t=ahora+0.25;          // buffer vacío: margen para la variación de la red
			if(st.t>ahora+1.2) return;                    // demasiado atrasado: se descarta para no acumular eco
			src.start(st.t); st.t+=buf.duration;
		}
		function volumen(){
			if(!st.gain) return;
			var v=st.mute||!st.sqAbierto ? 0 : Math.pow($('rx-vol').value/100,2)*2;
			st.gain.gain.setTargetAtTime(v, st.ctx.currentTime, 0.03);
		}
		function smeter(raw){
			var dbm=0.1*raw-127, s=dbm<=-73 ? Math.max(0,Math.round((dbm+127)/6)) : 9;
			$('rx-s').textContent=(dbm<=-73 ? 'S'+s : 'S9+'+Math.round(dbm+73)+' dB')+' · '+Math.round(dbm)+' dBm';
			$('rx-smeter').style.width=Math.max(0,Math.min(100,(dbm+127)/94*100))+'%';
			// Silenciador del lado del navegador: abre por encima del umbral y espera 0,8 s antes de cerrar
			if(st.sq>0){ var umbral=-127+6*st.sq, ahora=Date.now();
				if(dbm>=umbral){ st.sqHasta=ahora+800; if(!st.sqAbierto){ st.sqAbierto=true; volumen(); } }
				else if(st.sqAbierto && ahora>st.sqHasta){ st.sqAbierto=false; volumen(); } }
		}

		/* Grabación: muestras a la tasa del receptor, WAV PCM 16 bits mono. Máximo 10 minutos. */
		function grabar(m){
			var g=st.grab; if(g.n+m.length>st.rate*600){ pararGrabacion(); msg('La grabación llegó a 10 minutos y se guardó.'); return; }
			var i16=new Int16Array(m.length); for(var i=0;i<m.length;i++) i16[i]=Math.max(-32768,Math.min(32767,m[i]*32768));
			g.partes.push(i16); g.n+=m.length;
		}
		function pararGrabacion(){
			var g=st.grab; st.grab=null; $('rx-rec').textContent='● Grabar'; $('rx-rec').setAttribute('aria-pressed','false'); $('rx-rec').classList.remove('text-rose-400');
			if(!g || !g.n) return;
			var tasa=Math.round(st.rate), dv=new DataView(new ArrayBuffer(44)), s=function(o,t){ for(var i=0;i<t.length;i++) dv.setUint8(o+i,t.charCodeAt(i)); };
			s(0,'RIFF'); dv.setUint32(4,36+g.n*2,true); s(8,'WAVE'); s(12,'fmt '); dv.setUint32(16,16,true); dv.setUint16(20,1,true); dv.setUint16(22,1,true);
			dv.setUint32(24,tasa,true); dv.setUint32(28,tasa*2,true); dv.setUint16(32,2,true); dv.setUint16(34,16,true); s(36,'data'); dv.setUint32(40,g.n*2,true);
			var url=URL.createObjectURL(new Blob([dv.buffer].concat(g.partes), {type:'audio/wav'})), a=document.createElement('a');
			a.href=url; a.download='HK4RAU-'+g.freq.toFixed(2)+'kHz-'+g.modo+'-'+new Date(g.inicio).toISOString().slice(0,16).replace(/[:T]/g,'')+'.wav';
			document.body.appendChild(a); a.click(); a.remove(); setTimeout(function(){ URL.revokeObjectURL(url); }, 5000);
		}

		/* ── Conexión ─────────────────────────────────────────────────────── */
		function pares(t){ var o={}; t.split(' ').forEach(function(p){ var i=p.indexOf('='); if(i>0){ var v=p.slice(i+1); try { v=decodeURIComponent(v); } catch(e){} o[p.slice(0,i)]=v; } else if(p) o[p]=''; }); return o; }
		function avisoMsg(o, stream){
			// El HK4RAU tiene 8 canales de audio y solo 3 con cascada: si esos están ocupados se sigue escuchando.
			if('too_busy' in o && stream==='W/F'){ $('rx-ayuda').textContent='Cascada no disponible: sus canales están ocupados. El audio sigue.'; $('rx-ayuda').classList.remove('hidden'); try{ st.wf.onclose=null; st.wf.close(); }catch(e){} st.wf=null; return true; }
			if('too_busy' in o){ msg('Los '+o.too_busy+' canales del receptor están ocupados en este momento. Mira abajo a qué horas suele haber canal libre.'); apagar(true); return true; }
			if('badp' in o && o.badp!=='0'){ msg('El receptor no aceptó la conexión: todos los canales libres requieren clave.'); apagar(true); return true; }
			if('down' in o){ msg('El receptor está en mantenimiento. Intenta más tarde.'); apagar(true); return true; }
			if('inactivity_timeout' in o || 'tlimit_exp' in o){ msg('El receptor cerró la sesión por tiempo. Pulsa «Escuchar» para volver.'); apagar(true); return true; }
			return false;
		}
		function abrir(stream, onmsg, onbin){
			var ws=new WebSocket(RELAY+'/ws/kiwi/'+st.ts+'/'+stream);
			ws.binaryType='arraybuffer';
			ws.onopen=function(){ ws.send('SET auth t=kiwi p='); ws.send('SERVER DE CLIENT hk4rau.com '+stream); };
			ws.onmessage=function(ev){
				if(typeof ev.data==='string') return;
				var u8=new Uint8Array(ev.data), tag=String.fromCharCode(u8[0],u8[1],u8[2]);
				if(tag==='MSG'){ var o=pares(new TextDecoder().decode(u8.subarray(4))); if(!avisoMsg(o, stream)) onmsg(o); }
				else onbin(tag, ev.data, u8);
			};
			ws.onerror=function(){ if(st.on) msg('No fue posible conectar con el receptor. Revisa tu conexión o intenta en unos minutos.'); };
			ws.onclose=function(){ if(st.on && stream==='SND'){ msg('Se cerró la conexión con el receptor.'); apagar(true); } };
			return ws;
		}
		function agc(){ send(st.snd, st.agc ? 'SET agc=1 hang=0 thresh=-100 slope=6 decay=1000 manGain=50' : 'SET agc=0 hang=0 thresh=-100 slope=6 decay=1000 manGain=60'); }
		function encender(){
			if(st.on) return;
			st.ctx=st.ctx||new (window.AudioContext||window.webkitAudioContext)(); st.ctx.resume();
			if(!st.gain){ st.gain=st.ctx.createGain(); st.gain.connect(st.ctx.destination); }
			st.on=true; st.t=0; st.dec={i:0,p:0}; st.rpos=0; st.rprev=0; st.ts=Date.now(); st.esp=[]; st.sqAbierto=st.sq===0; volumen();
			$('rx-power').textContent='■ Detener'; $('rx-ayuda').classList.add('hidden');
			msg('Conectando con el receptor…');
			st.snd=abrir('SND', function(o){
				if(o.audio_rate) send(st.snd, 'SET AR OK in='+o.audio_rate+' out='+Math.round(st.ctx.sampleRate));
				if(o.sample_rate){
					st.rate=parseFloat(o.sample_rate)||12000;
					send(st.snd, 'SET squelch=0 max=0'); send(st.snd, 'SET genattn=0'); send(st.snd, 'SET gen=0 mix=-1');
					agc(); send(st.snd, 'SET compression=1'); send(st.snd, 'SET ident_user=hk4rau.com');
					sintonizar(st.freq, st.modo);
					msg('Escuchando. Toca la cascada para sintonizar, arrástrala para recorrer la banda o usa la rueda.');
				}
				if(o.audio_adpcm_state){ var v=o.audio_adpcm_state.split(','); st.dec={i:+v[0]|0,p:+v[1]|0}; }
			}, function(tag, buf, u8){
				if(tag!=='SND') return;
				var dv=new DataView(buf); smeter(dv.getUint16(8, false));
				var datos=u8.subarray(10), m;
				if(u8[3]&0x10) m=adpcm(datos, st.dec);
				else { m=new Float32Array(datos.length>>1); for(var i=0;i<m.length;i++) m[i]=dv.getInt16(10+2*i,false)/32768; }
				reproducir(m);
			});
			// La cascada se abre un poco después del audio, con la misma marca, como la interfaz oficial
			setTimeout(function(){ if(!st.on) return; st.wf=abrir('W/F', function(o){
				if(o.wf_fft_size) st.fft=+o.wf_fft_size;
				if(o.zoom_max) st.zoomMax=+o.zoom_max;
				if(o.bandwidth) st.bw=+o.bandwidth;
				if('wf_setup' in o){ send(st.wf,'SET send_dB=1'); send(st.wf,'SET wf_comp=0'); send(st.wf,'SET maxdb=-20 mindb=-120'); send(st.wf,'SET wf_speed=4'); zoomA(st.zoom); }
			}, function(tag, buf){
				if(tag!=='W/F') return;
				var dv=new DataView(buf), xbin=dv.getUint32(4,true), z=dv.getUint32(8,true)&0xffff, datos=new Uint8Array(buf,16);
				st.wfStart=xbin/(st.fft*Math.pow(2,st.zoomMax))*st.bw; st.wfSpan=st.bw/Math.pow(2,z);
				if(xbin!==st.ultXbin || z!==st.ultZoom){ st.ultXbin=xbin; st.ultZoom=z; limpiarCascada(); st.esp=[]; st.piso=null; dibujarPlan(); dibujarEscala(); marcas(); }
				var lin=new Float32Array(datos.length); for(var i=0;i<datos.length;i++) lin[i]=datos[i]-255;
				st.esp.push(lin); if(st.esp.length>10) st.esp.shift();
				lineaCascada(datos);
				if(!st.dibujoPend){ st.dibujoPend=true; requestAnimationFrame(function(){ st.dibujoPend=false; dibujarEspectro(); }); }
			}); }, 300);
			clearInterval(st.ka);
			st.ka=setInterval(function(){ send(st.snd,'SET keepalive'); send(st.wf,'SET keepalive'); }, 3000);
			clearInterval(st.miniT); st.miniT=setInterval(mini, 700);
		}
		/* Mini barra flotante: en Herramientas el panel se guarda al cambiar de herramienta y el audio
		   sigue; sin esto quedaba sonando sin forma visible de pararlo. */
		function mini(){
			var app=$('rx-app'), r=app.getBoundingClientRect();
			var aLaVista=app.offsetParent!==null && r.bottom>0 && r.top<innerHeight;
			var el=$('rx-mini'), ver=st.on && !aLaVista;
			el.classList.toggle('hidden', !ver); el.classList.toggle('flex', ver);
			if(ver) $('rx-mini-f').textContent=st.freq.toFixed(2)+' kHz '+MODOS[st.modo].n+($('rx-s').textContent!=='—' ? ' · '+$('rx-s').textContent.split(' ·')[0] : '');
		}
		function apagar(silencioso){
			if(st.grab) pararGrabacion();
			st.on=false; clearInterval(st.ka); clearInterval(st.miniT); mini();
			[st.snd, st.wf].forEach(function(ws){ if(ws){ ws.onclose=null; try{ ws.close(); }catch(e){} } });
			st.snd=st.wf=null; st.ultXbin=-1;
			$('rx-power').textContent='▶ Escuchar'; $('rx-s').textContent='—'; $('rx-smeter').style.width='0';
			if(!silencioso) msg('Desconectado. El canal quedó libre para otra persona.');
		}

		/* ── Controles ────────────────────────────────────────────────────── */
		$('rx-bandas').innerHTML=BANDAS.map(function(b,i){ return '<button type="button" data-i="'+i+'" class="rx-banda rounded-md border border-border bg-background px-3 py-2 font-mono text-xs hover:border-primary/50">'+b.n+'</button>'; }).join('');
		$('rx-modos').innerHTML=Object.keys(MODOS).map(function(k){ return '<button type="button" data-m="'+k+'" class="rx-modo rounded-md border border-border bg-background px-3 py-2 font-mono text-xs">'+MODOS[k].n+'</button>'; }).join('');
		document.querySelectorAll('.rx-banda').forEach(function(b){ b.addEventListener('click', function(){ var x=BANDAS[b.dataset.i]; sintonizar(x.f, x.m); }); });
		document.querySelectorAll('.rx-modo').forEach(function(b){ b.addEventListener('click', function(){ sintonizar(st.freq, b.dataset.m, false); }); });
		document.querySelectorAll('.rx-paso').forEach(function(b){ b.addEventListener('click', function(){ sintonizar(redondear(st.freq)+st.paso*(+b.dataset.pasos), null, false); }); });
		$('rx-input').addEventListener('change', function(){ var f=parseFloat(this.value); if(f>0) sintonizar(f); });
		document.body.appendChild($('rx-mini')); // fuera del panel, que Herramientas oculta al cambiar de herramienta
		$('rx-mini-stop').addEventListener('click', function(){ apagar(); });
		$('rx-mini-ver').addEventListener('click', function(){
			if(typeof window.rauAbrirHerramienta==='function') window.rauAbrirHerramienta('receptor');
			$('rx-app').scrollIntoView({block:'start', behavior:'smooth'}); mini();
		});
		$('rx-zoom-mas').addEventListener('click', function(){ zoomA(st.zoom+1); guardar(); });
		$('rx-zoom-menos').addEventListener('click', function(){ zoomA(st.zoom-1); guardar(); });
		$('rx-filtro-sel').value=st.filtro; $('rx-filtro-sel').addEventListener('change', function(){ st.filtro=+this.value; enviarModo(); marcas(); guardar(); });
		$('rx-paso-sel').value=st.paso; $('rx-paso-sel').addEventListener('change', function(){ st.paso=+this.value; guardar(); });
		$('rx-paleta').value=st.paleta; $('rx-paleta').addEventListener('change', function(){ st.paleta=this.value; construirLut(); limpiarCascada(); dibujarEspectro(); guardar(); });
		$('rx-contraste').value=st.contraste; $('rx-contraste-v').textContent=st.contraste+' dB';
		$('rx-contraste').addEventListener('input', function(){ st.contraste=+this.value; $('rx-contraste-v').textContent=st.contraste+' dB'; guardar(); });
		$('rx-sq').addEventListener('input', function(){
			st.sq=+this.value; $('rx-sq-v').textContent=st.sq?'S'+st.sq:'apagado';
			$('rx-sq-marca').classList.toggle('hidden', !st.sq); $('rx-sq-marca').style.left=(6*st.sq/94*100)+'%';
			if(!st.sq){ st.sqAbierto=true; volumen(); }
		});
		$('rx-vol').addEventListener('input', volumen);
		$('rx-agc').addEventListener('change', function(){ st.agc=this.checked; agc(); });
		$('rx-mute').addEventListener('click', function(){ st.mute=!st.mute; this.textContent=st.mute?'🔇':'🔊'; this.setAttribute('aria-pressed', st.mute); volumen(); });
		$('rx-rec').addEventListener('click', function(){
			if(st.grab){ pararGrabacion(); msg('Grabación guardada en tus descargas.'); return; }
			if(!st.on){ msg('Primero pulsa «Escuchar»; luego graba.'); return; }
			st.grab={partes:[], n:0, freq:st.freq, modo:st.modo, inicio:Date.now()};
			this.textContent='■ Parar grabación'; this.setAttribute('aria-pressed','true'); this.classList.add('text-rose-400');
			msg('Grabando '+st.freq.toFixed(2)+' kHz. Pulsa «Parar grabación» para descargar el WAV.');
		});
		$('rx-share').addEventListener('click', function(){
			var url=PAGINA+'?f='+st.freq.toFixed(2)+'&m='+st.modo, texto='Escucha '+st.freq.toFixed(2)+' kHz '+MODOS[st.modo].n+' en el KiwiSDR de RAU';
			if(navigator.share){ navigator.share({title:'KiwiSDR HK4RAU', text:texto, url:url}).catch(function(){}); return; }
			(navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(function(){ msg('Enlace copiado: '+url); }, function(){ prompt('Copia este enlace:', url); });
		});
		$('rx-power').addEventListener('click', function(){ st.on ? apagar() : encender(); });

		/* Pantalla: tocar sintoniza, arrastrar recorre la banda, la rueda mueve un paso */
		var pant=$('rx-pantalla'), arr=null;
		function freqEn(clientX){ var r=pant.getBoundingClientRect(); return (st.wfStart+(clientX-r.left)/r.width*st.wfSpan)/1000; }
		pant.addEventListener('pointerdown', function(e){ if(!st.wfSpan) return; arr={x:e.clientX, f:st.freq, movido:false}; pant.setPointerCapture(e.pointerId); });
		pant.addEventListener('pointermove', function(e){
			if(!arr) return; var dx=e.clientX-arr.x; if(Math.abs(dx)>6) arr.movido=true;
			if(arr.movido){ var r=pant.getBoundingClientRect(); st.freq=Math.max(10,arr.f-dx/r.width*st.wfSpan/1000); pintarFreq(); marcas(); }
		});
		pant.addEventListener('pointerup', function(e){
			if(!arr) return; var a=arr; arr=null;
			if(a.movido) sintonizar(redondear(st.freq));
			else { var f=freqEn(e.clientX); sintonizar(st.modo==='am'||st.modo==='nbfm' ? Math.round(f) : redondear(f), null, false); }
		});
		pant.addEventListener('wheel', function(e){ if(!st.on) return; e.preventDefault(); sintonizar(redondear(st.freq)+(e.deltaY<0?1:-1)*st.paso, null, false); }, {passive:false});
		$('rx-app').addEventListener('keydown', function(e){
			if(/INPUT|SELECT|TEXTAREA/.test(e.target.tagName)) return;
			var k={ArrowRight:1, ArrowLeft:-1, ArrowUp:10, ArrowDown:-10}[e.key]; if(!k) return;
			e.preventDefault(); sintonizar(redondear(st.freq)+k*st.paso, null, Math.abs(k)>1);
		});

		/* Favoritos */
		function favs(){ try { return JSON.parse(localStorage.getItem('rau_rx_favs')||'[]'); } catch(e){ return []; } }
		function pintarFavs(){
			var l=favs(), el=$('rx-favs');
			if(!l.length){ el.innerHTML='<p class="text-xs text-muted-foreground">Guarda aquí las frecuencias que más escuchas. Quedan en este navegador.</p>'; return; }
			el.innerHTML=l.map(function(x,i){ return '<span class="inline-flex items-center gap-1 rounded-md border border-border bg-background font-mono text-xs"><button type="button" data-i="'+i+'" class="rx-fav-ir px-3 py-2 hover:text-primary">'+(x.n?x.n.replace(/[<>&"]/g,'')+' · ':'')+x.f.toFixed(2)+' '+MODOS[x.m].n+'</button><button type="button" data-i="'+i+'" class="rx-fav-x px-2 py-2 text-muted-foreground hover:text-rose-400" aria-label="Quitar">×</button></span>'; }).join('');
			el.querySelectorAll('.rx-fav-ir').forEach(function(b){ b.addEventListener('click', function(){ var x=favs()[b.dataset.i]; sintonizar(x.f, x.m); }); });
			el.querySelectorAll('.rx-fav-x').forEach(function(b){ b.addEventListener('click', function(){ var l=favs(); l.splice(b.dataset.i,1); try{ localStorage.setItem('rau_rx_favs', JSON.stringify(l)); }catch(e){} pintarFavs(); }); });
		}
		$('rx-fav-add').addEventListener('click', function(){
			var n=prompt('Nombre para '+st.freq.toFixed(2)+' kHz (opcional):', '');
			if(n===null) return;
			var l=favs().filter(function(x){ return Math.abs(x.f-st.freq)>0.005 || x.m!==st.modo; }); l.unshift({f:st.freq, m:st.modo, n:n.slice(0,30)}); l=l.slice(0,20);
			try{ localStorage.setItem('rau_rx_favs', JSON.stringify(l)); }catch(e){} pintarFavs();
		});

		/* También al mostrarse el panel en Herramientas (antes medía 0 px de ancho) */
		if(window.ResizeObserver) new ResizeObserver(function(){ redibujarFijos(); }).observe($('rx-pantalla')); else window.addEventListener('resize', function(){ redibujarFijos(); });
		window.addEventListener('pagehide', function(){ if(st.on) apagar(true); });
		construirLut(); limpiarCascada(); pintarFreq(); pintarFavs(); redibujarFijos();

		/* API para otras herramientas: abrir el receptor en una frecuencia */
		window.RauRx={ sintonizar:function(f,m){ sintonizar(f, m||modoPara(f,'')); }, encender:encender, modoPara:modoPara, encendido:function(){ return st.on; } };
		window.rauEscuchar=function(fk, pista){
			fk=+fk; if(!(fk>0)) return;
			var m=modoPara(fk, pista);
			if(typeof window.rauAbrirHerramienta==='function') window.rauAbrirHerramienta('receptor');
			sintonizar(fk, m); if(!st.on) encender();
			setTimeout(function(){ $('rx-app').scrollIntoView({block:'start', behavior:'smooth'}); }, 50);
		};
	})();
	</script>
	<?php endif;
}
