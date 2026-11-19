<?php
/**
 * Propagación de Colombia en vivo.
 *
 * Cada 10 minutos se consultan en PSK Reporter (retrieve.pskreporter.info, la API pública documentada en
 * pskreporter.info/pskdev.html) los reportes de las cuadrículas FJ y FK de la última media hora, y se guardan
 * los enlaces en que un lado está en Colombia: quién de aquí fue oído afuera y a quién oyeron desde aquí,
 * en qué banda, modo y con qué SNR. Entran también los del receptor HK4RAU, que reporta a PSK Reporter.
 * PSK Reporter pide no consultar más de una vez cada 5 minutos: con dos consultas cada 10 se cumple.
 *
 * Público: GET /wp-json/rau/v1/propagacion (la última hora, en caché 2 minutos).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function rau_prop_banda( $hz ) {
	$k = $hz / 1000;
	foreach ( array( '160 m' => array( 1800, 2000 ), '80 m' => array( 3500, 4000 ), '60 m' => array( 5300, 5410 ), '40 m' => array( 7000, 7300 ), '30 m' => array( 10100, 10150 ),
		'20 m' => array( 14000, 14350 ), '17 m' => array( 18068, 18168 ), '15 m' => array( 21000, 21450 ), '12 m' => array( 24890, 24990 ), '10 m' => array( 28000, 29700 ), '6 m' => array( 50000, 54000 ) ) as $b => $r ) {
		if ( $k >= $r[0] && $k <= $r[1] ) return $b;
	}
	return '';
}
/* Centro aproximado de un locator de 4 o 6 caracteres */
function rau_prop_loc( $g ) {
	$g = strtoupper( (string) $g );
	if ( ! preg_match( '/^([A-R])([A-R])([0-9])([0-9])([A-X]{2})?/', $g, $m ) ) return null;
	$lng = ( ord( $m[1] ) - 65 ) * 20 - 180 + (int) $m[3] * 2; $lat = ( ord( $m[2] ) - 65 ) * 10 - 90 + (int) $m[4];
	if ( ! empty( $m[5] ) ) { $lng += ( ord( $m[5][0] ) - 65 ) / 12 + 1 / 24; $lat += ( ord( $m[5][1] ) - 65 ) / 24 + 1 / 48; } else { $lng += 1; $lat += 0.5; }
	return array( round( $lat, 3 ), round( $lng, 3 ) );
}

function rau_prop_es_co( $d ) { return (bool) preg_match( '/^(Colombia|San Andr|Malpelo)/i', $d ); }

function rau_prop_actualizar() {
	$previo = get_option( 'rau_prop_co', array() );
	$reg    = is_array( $previo ) && isset( $previo['r'] ) ? $previo['r'] : array();
	$ahora  = time(); $nuevos = 0; $error = '';
	foreach ( array( 'FJ', 'FK' ) as $cuad ) {
		$r = rau_remote_get( add_query_arg( array( 'callsign' => $cuad, 'modify' => 'grid', 'flowStartSeconds' => -1800, 'rronly' => 1, 'appcontact' => 'info@hk4rau.com' ), 'https://retrieve.pskreporter.info/query' ), array( 'timeout' => 25 ), 9 );
		if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) { $error = is_wp_error( $r ) ? $r->get_error_message() : 'PSK Reporter respondió ' . wp_remote_retrieve_response_code( $r ); continue; }
		if ( ! preg_match_all( '/<receptionReport ([^>]+)\/>/', wp_remote_retrieve_body( $r ), $ms ) ) continue;
		foreach ( $ms[1] as $attrs ) {
			preg_match_all( '/(\w+)="([^"]*)"/', $attrs, $kv ); $a = array_combine( $kv[1], array_map( 'html_entity_decode', $kv[2] ) );
			// Colombia, San Andrés y Providencia, y Malpelo cuentan como entidades DXCC aparte
			$co_tx = rau_prop_es_co( $a['senderDXCC'] ?? '' ); $co_rx = rau_prop_es_co( $a['receiverDXCC'] ?? '' );
			if ( ! $co_tx && ! $co_rx ) continue;
			$banda = rau_prop_banda( (float) ( $a['frequency'] ?? 0 ) ); if ( ! $banda ) continue;
			// El lado colombiano y el otro; si los dos son de aquí, cuenta como transmisión
			$co   = $co_tx ? array( $a['senderCallsign'] ?? '', $a['senderLocator'] ?? '' ) : array( $a['receiverCallsign'] ?? '', $a['receiverLocator'] ?? '' );
			$otro = $co_tx ? array( $a['receiverCallsign'] ?? '', $a['receiverLocator'] ?? '', $a['receiverDXCC'] ?? '' ) : array( $a['senderCallsign'] ?? '', $a['senderLocator'] ?? '', $a['senderDXCC'] ?? '' );
			$pc = rau_prop_loc( $co[1] ); $po = rau_prop_loc( $otro[1] );
			if ( ! $pc || ! $po || ! preg_match( '/^[A-Z0-9\/]{3,12}$/', $co[0] ) ) continue;
			$k = $co[0] . '|' . $otro[0] . '|' . $banda . '|' . ( $co_tx ? 't' : 'r' );
			$reg[ $k ] = array( 'c' => $co[0], 'cp' => $pc, 'o' => $otro[0], 'op' => $po, 'od' => $otro[2], 'b' => $banda, 'm' => $a['mode'] ?? '', 's' => isset( $a['sNR'] ) ? (int) $a['sNR'] : null,
				't' => (int) ( $a['flowStartSeconds'] ?? $ahora ), 'x' => $co_tx ? 1 : 0 );
			$nuevos++;
		}
	}
	// Solo la última hora, y como mucho 4000 enlaces (los más recientes)
	$reg = array_filter( $reg, function( $x ) use ( $ahora ) { return $x['t'] > $ahora - HOUR_IN_SECONDS; } );
	if ( count( $reg ) > 4000 ) { uasort( $reg, function( $a, $b ) { return $b['t'] - $a['t']; } ); $reg = array_slice( $reg, 0, 4000, true ); }
	update_option( 'rau_prop_co', array( 'act' => $nuevos || ! $error ? $ahora : ( $previo['act'] ?? 0 ), 'error' => $error, 'r' => $reg ), false );
	return $nuevos;
}
add_filter( 'cron_schedules', function( $s ) { $s['rau_10min'] = array( 'interval' => 600, 'display' => 'Cada 10 minutos' ); return $s; } );
add_action( 'init', function() { if ( ! wp_next_scheduled( 'rau_prop_cron' ) ) wp_schedule_event( time() + 120, 'rau_10min', 'rau_prop_cron' ); } );
add_action( 'rau_prop_cron', 'rau_prop_actualizar' );

/* Resumen por banda: enlaces, distancia máxima y regiones, separando lo oído afuera y lo oído aquí */
function rau_prop_region( $p ) {
	list( $lat, $lng ) = $p;
	if ( $lng < -30 ) return $lat > 18 ? 'Norteamérica' : ( $lat > 8 && $lng > -90 ? 'Caribe y Centroamérica' : 'Suramérica' );
	if ( $lng < 60 ) return $lat > 35 ? 'Europa' : ( $lat > 12 && $lng > 34 ? 'Oriente Medio' : 'África' );
	return $lat < -10 && $lng > 110 ? 'Oceanía' : 'Asia';
}
function rau_prop_km( $a, $b ) { $r = M_PI / 180; $x = sin( ( $b[0] - $a[0] ) * $r / 2 ); $y = sin( ( $b[1] - $a[1] ) * $r / 2 ); return (int) round( 12742 * asin( sqrt( $x * $x + cos( $a[0] * $r ) * cos( $b[0] * $r ) * $y * $y ) ) ); }
function rau_prop_datos() {
	$p = get_option( 'rau_prop_co', array() ); $reg = isset( $p['r'] ) ? array_values( $p['r'] ) : array(); $bandas = array();
	foreach ( $reg as &$x ) {
		$x['km'] = rau_prop_km( $x['cp'], $x['op'] ); $x['rg'] = rau_prop_es_co( $x['od'] ) ? 'Colombia' : rau_prop_region( $x['op'] );
		$b = &$bandas[ $x['b'] ];
		if ( ! $b ) $b = array( 'n' => 0, 'max' => 0, 'reg' => array(), 'ult' => 0, 'tx' => 0, 'rx' => 0 );
		$b['n']++; $b['max'] = max( $b['max'], $x['km'] ); $b['ult'] = max( $b['ult'], $x['t'] ); $b[ $x['x'] ? 'tx' : 'rx' ]++;
		$b['reg'][ $x['rg'] ] = ( $b['reg'][ $x['rg'] ] ?? 0 ) + 1;
		unset( $b );
	}
	unset( $x );
	foreach ( $bandas as &$b ) arsort( $b['reg'] );
	unset( $b );
	return array( 'actualizado' => (int) ( $p['act'] ?? 0 ), 'error' => $p['error'] ?? '', 'bandas' => $bandas, 'enlaces' => $reg, 'fuente' => 'PSK Reporter (pskreporter.info), cuadrículas FJ y FK, última hora' );
}
add_action( 'rest_api_init', function() {
	register_rest_route( 'rau/v1', '/propagacion', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function() {
		$d = rau_prop_datos(); $d['pronostico'] = function_exists( 'rau_pronostico' ) ? rau_pronostico() : null;
		$r = rest_ensure_response( $d );
		$r->header( 'Cache-Control', 'public, max-age=120' );
		return $r;
	} ) );
} );

/* Componente: mapa y resumen por banda. Lo usan el receptor (página y pestaña de Herramientas). */
function rau_mapa_propagacion() {
	$leaflet = get_template_directory_uri() . '/assets/vendor/leaflet/';
	?>
	<section id="prop-co" class="mt-8 rounded-2xl border border-border/70 bg-card p-5 sm:p-6" aria-labelledby="prop-co-t">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">En vivo · última hora</p>
		<h2 id="prop-co-t" class="font-display text-lg font-bold">Propagación desde Colombia</h2>
		<p class="mt-1 text-sm text-muted-foreground">Quién de Colombia está siendo oído afuera y a quién se oye desde aquí, por banda, según los reportes automáticos de PSK Reporter (FT8, FT4, CW y otros modos digitales). Se actualiza cada 10 minutos.</p>
		<div id="prop-co-pron" class="mt-4 rounded-xl border border-border/70 p-4 text-sm" aria-live="polite"></div>
		<div id="prop-co-bandas" class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Banda"></div>
		<div id="prop-co-res" class="mt-3 text-sm" aria-live="polite">Cargando…</div>
		<div id="prop-co-mapa" class="mt-3 overflow-hidden rounded-xl border border-border/70" style="height:22rem;position:relative;z-index:0;isolation:isolate" role="region" aria-label="Mapa de enlaces"></div>
		<p id="prop-co-fuente" class="mt-2 text-xs text-muted-foreground"></p>
	</section>
	<script>
	(function(){
		var URL=<?php echo wp_json_encode( rest_url( 'rau/v1/propagacion' ) ); ?>, LF=<?php echo wp_json_encode( array( 'js' => $leaflet . 'leaflet.js?ver=1.9.4', 'css' => $leaflet . 'leaflet.css?ver=1.9.4' ) ); ?>;
		var COLOR={'160 m':'#a78bfa','80 m':'#818cf8','60 m':'#60a5fa','40 m':'#38bdf8','30 m':'#22d3ee','20 m':'#34d399','17 m':'#a3e635','15 m':'#facc15','12 m':'#fb923c','10 m':'#f87171','6 m':'#f472b6'};
		var ORDEN=Object.keys(COLOR), datos=null, banda='', mapa=null, capa=null;
		var $=function(id){ return document.getElementById(id); };
		function esc(s){ return String(s).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
		function hace(t){ var m=Math.round((Date.now()/1000-t)/60); return m<1?'ahora':'hace '+m+' min'; }
		function cargarLeaflet(){ if(window.L) return Promise.resolve(); return new Promise(function(ok, mal){ var l=document.createElement('link'); l.rel='stylesheet'; l.href=LF.css; document.head.appendChild(l); var s=document.createElement('script'); s.src=LF.js; s.onload=ok; s.onerror=mal; document.head.appendChild(s); }); }
		function pron(){
			var p=datos.pronostico, e=$('prop-co-pron'); if(!p){ e.classList.add('hidden'); return; }
			var h='<p class="mb-1 font-mono text-xs uppercase tracking-[0.2em] text-primary">Pronóstico ahora</p>';
			if(p.nvis) h+='<p><b>Cerca (NVIS, 0–400 km):</b> '+(p.nvis.banda?'<b>'+esc(p.nvis.banda)+'</b>. ':'')+esc(p.nvis.texto)+'</p>';
			else h+='<p><b>Cerca (NVIS):</b> sin ionosonda con datos recientes; regla general: 40 m de día, 80 m de noche.</p>';
			if(p.medido && p.medido.texto) h+='<p class="mt-1"><b>'+(p.medido.banda?'NVIS en uso: '+esc(p.medido.banda)+'.':'NVIS en uso:')+'</b> '+esc(p.medido.texto)+'</p>';
			if(p.dx && p.dx.length){
				var oidas=Object.keys(datos.bandas||{});
				h+='<p class="mt-1"><b>Lejos (unos 3000 km):</b> según la MUF de la ionosonda, abiertas '+p.dx.map(function(b){ return '<b>'+esc(b)+'</b>'+(oidas.indexOf(b)>=0?' ✓':''); }).join(', ')+'. <small>✓ = además se están oyendo enlaces desde Colombia.</small></p>';
			}
			var sol=[]; if(p.sfi!==null) sol.push('flujo solar '+p.sfi); if(p.kp!==null) sol.push('Kp '+String(p.kp).replace('.', ','));
			if(sol.length) h+='<p class="mt-1 text-muted-foreground">'+sol.join(' · ')+(p.iono?' · ionosonda '+esc(p.iono.nombre)+', a '+p.iono.km.toLocaleString('es-CO')+' km, medición de hace '+p.iono.hace+' min':'')+'</p>';
			(p.notas||[]).forEach(function(n){ h+='<p class="mt-1 text-amber-400">'+esc(n)+'</p>'; });
			e.innerHTML=h;
		}
		function pintar(){
			pron();
			var bs=ORDEN.filter(function(b){ return datos.bandas[b]; });
			if(!banda || !datos.bandas[banda]) banda=bs.slice().sort(function(a,b){ return datos.bandas[b].n-datos.bandas[a].n; })[0]||'';
			$('prop-co-bandas').innerHTML=bs.map(function(b){ var x=datos.bandas[b]; return '<button type="button" data-b="'+b+'" aria-pressed="'+(b===banda)+'" class="rounded-md border px-3 py-1.5 font-mono text-xs '+(b===banda?'border-primary bg-primary/15 text-primary':'border-border text-muted-foreground hover:text-foreground')+'"><span style="color:'+COLOR[b]+'">●</span> '+b+' · '+x.n+'</button>'; }).join('') || '';
			if(!bs.length){ $('prop-co-res').textContent='Sin enlaces reportados desde Colombia en la última hora'+(datos.error?' (la fuente no respondió: '+datos.error+')':'')+'.'; }
			else {
				var x=datos.bandas[banda], regs=Object.keys(x.reg).slice(0,4).map(function(r){ return r+' ('+x.reg[r]+')'; }).join(', ');
				$('prop-co-res').innerHTML='<b>'+banda+'</b>: '+x.n+' enlaces · '+x.tx+' oídos afuera, '+x.rx+' oídos aquí · hasta '+x.max.toLocaleString('es-CO')+' km · hacia '+esc(regs)+' · último '+hace(x.ult)+'.';
			}
			$('prop-co-fuente').textContent='Fuente: '+datos.fuente+'. Actualizado '+(datos.actualizado?hace(datos.actualizado):'—')+'. Es lo que alguien reportó, no todo lo que se oye.';
			cargarLeaflet().then(function(){
				if(!mapa){ mapa=L.map('prop-co-mapa', {worldCopyJump:true}).setView([15, -50], 2); L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:7, attribution:'© colaboradores de OpenStreetMap'}).addTo(mapa); }
				if(capa) capa.remove(); capa=L.layerGroup();
				var col=COLOR[banda]||'#38bdf8', aqui={};
				datos.enlaces.filter(function(e){ return e.b===banda; }).slice(0,600).forEach(function(e){
					L.polyline([e.cp, e.op], {color:col, weight:1, opacity:0.35}).addTo(capa);
					L.circleMarker(e.op, {radius:3, color:col, weight:1, fillOpacity:0.8}).bindTooltip(esc(e.o)+' · '+esc(e.od)+' · '+e.km.toLocaleString('es-CO')+' km · '+(e.x?'oyó a ':'oído por ')+esc(e.c)+' · '+esc(e.m)+(e.s!==null?' '+(e.s>0?'+':'')+e.s+' dB':'')+' · '+hace(e.t)).addTo(capa);
					aqui[e.c]=e.cp;
				});
				Object.keys(aqui).forEach(function(c){ L.circleMarker(aqui[c], {radius:5, color:'#0b0f17', weight:1, fillColor:'#ffb547', fillOpacity:1}).bindTooltip(esc(c)).addTo(capa); });
				capa.addTo(mapa); mapa.invalidateSize();
			}).catch(function(){ $('prop-co-mapa').textContent='No se pudo cargar el mapa.'; });
		}
		function cargar(){ fetch(URL).then(function(r){ return r.json(); }).then(function(j){ datos=j; pintar(); }).catch(function(){ $('prop-co-res').textContent='No se pudo consultar la propagación.'; }); }
		$('prop-co-bandas').addEventListener('click', function(e){ var b=e.target.closest('[data-b]'); if(b){ banda=b.dataset.b; pintar(); } });
		// El mapa se carga cuando la sección está a la vista
		if('IntersectionObserver' in window){ var io=new IntersectionObserver(function(es){ if(es[0].isIntersecting){ io.disconnect(); cargar(); setInterval(cargar, 300000); } }); io.observe($('prop-co')); } else cargar();
	})();
	</script>
	<?php
}
