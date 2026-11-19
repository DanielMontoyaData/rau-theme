<?php
/**
 * /modem/ — página liviana del módem de audio RAU, para usar sin datos.
 *
 * Una sola página sin el resto del sitio (unos 40 KB con el módem) que el service worker guarda en la
 * primera visita. Sirve para:
 *  · chat por sonido: mensajes cortos entre celulares por la radio, el cable o de parlante a micrófono;
 *  · recibir paquetes del centro de mando y dejarlos listos para importarlos allá (aunque esa página
 *    nunca se haya abierto en este celular, se pueden copiar como texto).
 * Formato de un mensaje de chat: «RAUCHAT1\n<indicativo>\n<texto>» en UTF-8.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function() { add_rewrite_rule( '^modem/?$', 'index.php?rau_modem=1', 'top' ); } );
add_filter( 'query_vars', function( $v ) { $v[] = 'rau_modem'; return $v; } );
add_action( 'template_redirect', function() {
	if ( ! get_query_var( 'rau_modem' ) ) return;
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'X-LiteSpeed-Cache-Control: public,max-age=3600' );
	$radio = get_template_directory_uri() . '/assets/radio/';
	$v = RAU_THEME_VERSION;
	?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Módem RAU · mensajes por sonido</title>
<meta name="description" content="Chat y paquetes del centro de mando por sonido entre celulares, por la radio o de parlante a micrófono. Funciona sin datos.">
<meta property="og:title" content="Módem RAU · mensajes por sonido">
<meta property="og:description" content="Mensajes entre celulares por la radio, sin internet. De Radioaficionados Unidos.">
<?php $og = function_exists( 'rau_og_imagen_url' ) ? rau_og_imagen_url( 'Módem RAU: mensajes por sonido, sin internet', 'Emergencias' ) : null; ?>
<meta property="og:image" content="<?php echo esc_url( $og ?: get_template_directory_uri() . '/assets/images/rau-badge.png' ); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#080c16">
<link rel="manifest" href="<?php echo esc_url( get_template_directory_uri() . '/manifest.json' ); ?>">
<style>
:root{--f:#080c16;--c:#111827;--b:#253046;--t:#e5e7eb;--m:#94a3b8;--p:#38bdf8;--a:#ffb547;--ok:#34d399;--mal:#f87171}
@media (prefers-color-scheme: light){:root{--f:#f6f7fb;--c:#fff;--b:#d6dbe6;--t:#0f172a;--m:#475569;--p:#0369a1;--a:#b45309;--ok:#047857;--mal:#b91c1c}}
*{box-sizing:border-box}body{margin:0;background:var(--f);color:var(--t);font:16px/1.45 system-ui,-apple-system,"Segoe UI",sans-serif}
main{max-width:40rem;margin:0 auto;padding:16px}h1{font-size:1.35rem;margin:.2rem 0}h2{font-size:1rem;margin:0 0 .5rem}
.n{color:var(--m);font-size:.88rem}.c{background:var(--c);border:1px solid var(--b);border-radius:12px;padding:14px;margin:12px 0}
label{display:block;font-size:.85rem;color:var(--m);margin:.4rem 0}input,select,textarea{width:100%;font:inherit;color:var(--t);background:var(--f);border:1px solid var(--b);border-radius:8px;padding:.55rem}
button{font:inherit;font-weight:600;border:0;border-radius:8px;padding:.6rem .9rem;background:var(--p);color:#001018;cursor:pointer}button.s{background:transparent;color:var(--t);border:1px solid var(--b)}button:disabled{opacity:.5}
.fila{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.dos{display:grid;grid-template-columns:1fr 1fr;gap:8px}
#log{list-style:none;padding:0;margin:0;max-height:22rem;overflow-y:auto}#log li{padding:.45rem .2rem;border-bottom:1px solid var(--b)}#log b{color:var(--a)}#log .yo b{color:var(--p)}#log small{color:var(--m);display:block}
.ok{color:var(--ok)}.mal{color:var(--mal)}progress{width:100%}.mono{font-family:ui-monospace,monospace}
</style>
</head>
<body>
<main>
	<p class="n">RAU · Radioaficionados Unidos</p>
	<h1>Módem RAU: mensajes por sonido</h1>
	<p class="n">Para cuando no hay datos: escribe un mensaje y transmítelo por la radio (salida de audio del celular a la entrada de micrófono, o el celular junto al micrófono) con PTT o VOX. Del otro lado, esta misma página escucha y lo muestra. Funciona sin internet después de la primera visita.</p>

	<section class="c" aria-labelledby="t-env">
		<h2 id="t-env">Enviar</h2>
		<div class="dos">
			<label>Tu indicativo<input id="ind" autocomplete="off" maxlength="12" placeholder="HK4ABC" class="mono"></label>
			<label>Velocidad<select id="modo"><option value="robusto">Robusta (altavoz)</option><option value="rapido">Rápida (cable o FM)</option></select></label>
		</div>
		<label>Mensaje<textarea id="txt" rows="3" maxlength="600" placeholder="Puente caído en la vía a La Florida. Sin heridos."></textarea></label>
		<div class="fila"><button id="tx">▶ Transmitir</button><button id="parar" class="s" disabled>■ Detener</button><span id="dur" class="n"></span></div>
		<progress id="prog" max="1" value="0"></progress>
	</section>

	<section class="c" aria-labelledby="t-rx">
		<h2 id="t-rx">Escuchar</h2>
		<div class="fila"><button id="rx">🎤 Escuchar</button><span id="nivel" class="mono n" aria-hidden="true"></span></div>
		<p id="est" class="n" aria-live="polite">Los mensajes recibidos aparecen abajo. Los paquetes del centro de mando también se pueden recibir aquí.</p>
	</section>

	<section class="c" aria-labelledby="t-log">
		<div class="fila" style="justify-content:space-between"><h2 id="t-log">Conversación</h2><button id="borrar" class="s">Borrar</button></div>
		<ul id="log" aria-live="polite"></ul>
	</section>
	<p class="n">Para paquetes grandes y con protección contra tramas perdidas, usa el <a href="<?php echo esc_url( home_url( '/emergencias/' ) ); ?>">centro de mando</a> (Campo → Pasar registros sin internet).</p>
</main>
<script src="<?php echo esc_url( $radio . 'ftx.js?ver=' . $v ); ?>"></script>
<script src="<?php echo esc_url( $radio . 'modem.js?ver=' . $v ); ?>"></script>
<script>
(function(){
	var $=function(id){ return document.getElementById(id); }, M=window.RauModem, tx=null, rx=null;
	var CLAVE='rau_modem_chat', log=[];
	function guardar(){ try{ localStorage.setItem(CLAVE, JSON.stringify(log.slice(-200))); }catch(e){} }
	try{ log=JSON.parse(localStorage.getItem(CLAVE)||'[]'); $('ind').value=localStorage.getItem('rau_modem_ind')||''; }catch(e){}
	function esc(s){ return String(s).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
	function hora(t){ return new Date(t).toLocaleTimeString('es-CO', {hour:'2-digit', minute:'2-digit'}); }
	function pintar(){
		$('log').innerHTML=log.map(function(m, i){
			if(m.paquete) return '<li><b>Paquete del centro de mando</b> · '+esc(m.bytes)+' bytes<small>'+hora(m.t)+'</small><div class="fila"><button class="s" data-copiar="'+i+'">Copiar texto</button><a href="<?php echo esc_url( home_url( '/emergencias/' ) ); ?>" data-abrir="'+i+'">Abrir en el centro de mando</a></div></li>';
			return '<li class="'+(m.yo?'yo':'')+'"><b>'+esc(m.de||'¿?')+'</b>: '+esc(m.texto)+'<small>'+(m.yo?'enviado':'recibido')+' · '+hora(m.t)+(m.snr!=null?' · SNR '+m.snr+' dB':'')+'</small></li>';
		}).join('') || '<li class="n">Todavía nada.</li>';
		$('log').scrollTop=1e9;
	}
	pintar();
	$('log').addEventListener('click', function(e){
		var c=e.target.closest('[data-copiar]'), a=e.target.closest('[data-abrir]');
		if(c && navigator.clipboard) navigator.clipboard.writeText(log[+c.dataset.copiar].texto).then(function(){ c.textContent='Copiado'; });
		// El centro de mando lo encuentra al abrir «Recibir paquete»
		if(a) try{ localStorage.setItem('rau_em_paquete_pendiente', log[+a.dataset.abrir].texto); }catch(x){}
	});
	$('borrar').addEventListener('click', function(){ log=[]; guardar(); pintar(); });
	function bytesChat(){ var ind=$('ind').value.trim().toUpperCase(), t=$('txt').value.trim(); return t ? new TextEncoder().encode('RAUCHAT1\n'+ind+'\n'+t) : null; }
	function dur(){ var b=bytesChat(); $('dur').textContent=b ? Math.round(M.duracion(M.cuantasTramas(b, 'baja'), $('modo').value))+' s' : ''; }
	$('txt').addEventListener('input', dur); $('modo').addEventListener('change', dur);
	function pararTx(){ if(tx){ try{ tx.src.stop(); }catch(e){} tx.ctx.close(); tx=null; } $('tx').disabled=false; $('parar').disabled=true; }
	$('parar').addEventListener('click', pararTx);
	$('tx').addEventListener('click', function(){
		var b=bytesChat(); if(!b || tx) return;
		try{ localStorage.setItem('rau_modem_ind', $('ind').value.trim().toUpperCase()); }catch(e){}
		var ctx=new (window.AudioContext||window.webkitAudioContext)(), y=M.sintetizar(b, ctx.sampleRate, {modo:$('modo').value, proteccion:'baja'}), buf=ctx.createBuffer(1, y.length, ctx.sampleRate);
		buf.getChannelData(0).set(y); var src=ctx.createBufferSource(); src.buffer=buf; src.connect(ctx.destination);
		tx={ctx:ctx, src:src}; var t0=ctx.currentTime;
		src.onended=function(){ if(tx && tx.src===src){ pararTx(); $('prog').value=1; log.push({yo:true, de:$('ind').value.trim().toUpperCase(), texto:$('txt').value.trim(), t:Date.now()}); guardar(); pintar(); $('txt').value=''; dur(); } };
		src.start(); $('tx').disabled=true; $('parar').disabled=false;
		(function av(){ if(!tx || tx.src!==src) return; $('prog').value=Math.min(1, (ctx.currentTime-t0)/buf.duration); requestAnimationFrame(av); })();
	});
	function recibido(b){
		var t=new TextDecoder().decode(b);
		if(/^RAUCHAT1\n/.test(t)){ var p=t.split('\n'); log.push({de:p[1], texto:p.slice(2).join('\n'), t:Date.now(), snr:rx&&rx.snr}); }
		else if(/^RAUEM1 /.test(t)){
			// Paquete: cabecera de texto + bytes comprimidos → texto RAUEM1 normal (base64) para el centro de mando
			var esp=0, i=0; for(;i<b.length && i<80;i++) if(b[i]===32 && ++esp===4) break;
			var s=''; for(var j=i+1;j<b.length;j++) s+=String.fromCharCode(b[j]);
			log.push({paquete:true, texto:new TextDecoder().decode(b.subarray(0, i+1))+btoa(s), bytes:b.length, t:Date.now()});
		} else log.push({de:'?', texto:t.slice(0, 600), t:Date.now()});
		guardar(); pintar();
	}
	function pararRx(){ if(rx){ rx.flujo.getTracks().forEach(function(t){ t.stop(); }); rx.ctx.close(); rx=null; } $('rx').textContent='🎤 Escuchar'; $('nivel').textContent=''; }
	$('rx').addEventListener('click', function(){
		if(rx){ pararRx(); $('est').textContent='Dejé de escuchar.'; return; }
		navigator.mediaDevices.getUserMedia({audio:{echoCancellation:false, noiseSuppression:false, autoGainControl:false}}).then(function(flujo){
			var ctx=new (window.AudioContext||window.webkitAudioContext)(), f=ctx.createMediaStreamSource(flujo), proc=ctx.createScriptProcessor(4096, 1, 1), mudo=ctx.createGain(); mudo.gain.value=0;
			var r=M.crearReceptor(ctx.sampleRate, function(e){
				rx.snr=e.ultima.snr;
				$('est').innerHTML='Recibiendo: '+e.recibidas+' tramas'+(e.total?' de '+e.total:'')+(e.recuperadas?' · '+e.recuperadas+' reconstruidas':'')+(e.faltan.length?' · faltan <span class="mono">'+e.faltan.join(', ')+'</span>':'')+(e.sugerencia?'<br>'+esc(e.sugerencia.texto):'');
			}, function(b){ recibido(b); $('est').innerHTML='<span class="ok">Mensaje recibido.</span> Sigo escuchando.'; }, function(err){ $('est').innerHTML='<span class="mal">'+esc(err)+'</span>'; });
			var nivel=0;
			proc.onaudioprocess=function(ev){ var x=ev.inputBuffer.getChannelData(0), s=0; for(var i=0;i<x.length;i++) s+=x[i]*x[i]; nivel=0.8*nivel+0.2*Math.sqrt(s/x.length); $('nivel').textContent='▮'.repeat(Math.min(10, Math.round(nivel*40))); r.alimentar(new Float32Array(x)); };
			f.connect(proc); proc.connect(mudo); mudo.connect(ctx.destination);
			rx={ctx:ctx, flujo:flujo, snr:null}; $('rx').textContent='■ Dejar de escuchar'; $('est').textContent='Escuchando… acerca el celular al parlante de la radio.';
		}).catch(function(e){ $('est').innerHTML='<span class="mal">'+(e && e.name==='NotAllowedError'?'Sin permiso para usar el micrófono.':'No se pudo usar el micrófono.')+'</span>'; });
	});
	dur();
	if('serviceWorker' in navigator) navigator.serviceWorker.register('<?php echo esc_js( home_url( '/service-worker.js' ) ); ?>').catch(function(){});
})();
</script>
</body>
</html>
<?php
	exit;
} );
