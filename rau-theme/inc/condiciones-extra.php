<?php
/**
 * Condiciones 19.65.0: lo que tienen las herramientas de referencia (HamClock, KC2G, SpaceWeatherLive, SIATA)
 * y nos faltaba, con fuentes verificadas:
 *  · Mapa mundial de MUF en vivo (KC2G, isolíneas GeoJSON cada 15 min) y MUF estimada en cualquier punto.
 *  · «¿Llego a…?»: enlace punto a punto con distancia, rumbos, puntos de control, MUF y bandas.
 *  · Alcance real: WSPR (wspr.live, base pública) de la región FJ/FK/FI en la última hora.
 *  · Activaciones POTA (api.pota.app).
 *  · (19.67.0: se quitó lo que no sirve para operar radio: SIATA, calidad del aire, humedad, presión.)
 *  ·
 *    documentación oficial: se cita la fuente y se guarda 5 minutos para no cargarlo).
 *  · Corrientes rápidas de agujeros coronales (NASA DONKI HSS) y su posible regreso a los 27 días.
 *  · Archivo diario de eventos, resumen de la semana, tarjeta PNG para compartir y franja para OBS.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── MUF en vivo (KC2G) ─────────────────────────────────────────────────── */
function rau_cond_muf_geo() {
	$c = get_transient( 'rau_cond_mufgeo' ); if ( is_array( $c ) ) return $c;
	list( $g ) = rau_cond_traer( 'https://prop.kc2g.com/renders/current/mufd-normal-now.geojson', 15 * MINUTE_IN_SECONDS, 'json', 20 );
	$out = array();
	foreach ( (array) ( $g['features'] ?? array() ) as $f ) {
		$v = (float) ( $f['properties']['level-value'] ?? 0 ); $geo = $f['geometry'] ?? null; if ( ! $v || ! $geo ) continue;
		$lineas = 'LineString' === $geo['type'] ? array( $geo['coordinates'] ) : ( 'MultiLineString' === $geo['type'] ? $geo['coordinates'] : array() );
		foreach ( $lineas as $l ) { $p = array(); foreach ( $l as $i => $q ) if ( 0 === $i % 2 || $i === count( $l ) - 1 ) $p[] = array( round( $q[0], 2 ), round( $q[1], 2 ) ); if ( count( $p ) > 1 ) $out[] = array( 'v' => $v, 'c' => (string) ( $f['properties']['stroke'] ?? '#888' ), 'p' => $p ); }
	}
	set_transient( 'rau_cond_mufgeo', $out, 15 * MINUTE_IN_SECONDS );
	return $out;
}
/* MUF(3000) estimada en un punto: entre las dos isolíneas más cercanas de valor distinto */
function rau_cond_muf_en( $lat, $lon, $geo = null ) {
	$geo = null === $geo ? rau_cond_muf_geo() : $geo; if ( ! $geo ) return null;
	$cos = cos( deg2rad( $lat ) ); $mejor = array();
	foreach ( $geo as $l ) {
		$min = INF; $p = $l['p'];
		for ( $i = 1; $i < count( $p ); $i++ ) {
			$ax = ( $p[ $i - 1 ][0] - $lon ) * $cos; $ay = $p[ $i - 1 ][1] - $lat; $bx = ( $p[ $i ][0] - $lon ) * $cos; $by = $p[ $i ][1] - $lat;
			if ( abs( $ax ) > 90 || abs( $bx ) > 90 ) continue; // segmento al otro lado del mundo
			$dx = $bx - $ax; $dy = $by - $ay; $t = ( $dx || $dy ) ? max( 0, min( 1, -( $ax * $dx + $ay * $dy ) / ( $dx * $dx + $dy * $dy ) ) ) : 0;
			$d = sqrt( pow( $ax + $t * $dx, 2 ) + pow( $ay + $t * $dy, 2 ) ); if ( $d < $min ) $min = $d;
		}
		$k = (string) $l['v']; if ( ! isset( $mejor[ $k ] ) || $min < $mejor[ $k ] ) $mejor[ $k ] = $min;
	}
	asort( $mejor ); $niv = array_keys( $mejor ); if ( ! $niv ) return null;
	$v1 = (float) $niv[0]; $d1 = $mejor[ $niv[0] ];
	if ( count( $niv ) < 2 ) return round( $v1, 1 );
	$v2 = (float) $niv[1]; $d2 = $mejor[ $niv[1] ];
	return round( ( $v1 * $d2 + $v2 * $d1 ) / max( 1e-6, $d1 + $d2 ), 1 );
}

/* ── «¿Llego a…?»: enlace punto a punto ─────────────────────────────────── */
function rau_cond_rumbo( $a, $b ) {
	$f1 = deg2rad( $a[0] ); $f2 = deg2rad( $b[0] ); $dl = deg2rad( $b[1] - $a[1] );
	$y = sin( $dl ) * cos( $f2 ); $x = cos( $f1 ) * sin( $f2 ) - sin( $f1 ) * cos( $f2 ) * cos( $dl );
	return fmod( rad2deg( atan2( $y, $x ) ) + 360, 360 );
}
function rau_cond_intermedio( $a, $b, $frac ) {
	$f1 = deg2rad( $a[0] ); $l1 = deg2rad( $a[1] ); $f2 = deg2rad( $b[0] ); $l2 = deg2rad( $b[1] );
	$d = 2 * asin( sqrt( pow( sin( ( $f2 - $f1 ) / 2 ), 2 ) + cos( $f1 ) * cos( $f2 ) * pow( sin( ( $l2 - $l1 ) / 2 ), 2 ) ) ); if ( $d < 1e-9 ) return $a;
	$A = sin( ( 1 - $frac ) * $d ) / sin( $d ); $B = sin( $frac * $d ) / sin( $d );
	$x = $A * cos( $f1 ) * cos( $l1 ) + $B * cos( $f2 ) * cos( $l2 ); $y = $A * cos( $f1 ) * sin( $l1 ) + $B * cos( $f2 ) * sin( $l2 ); $z = $A * sin( $f1 ) + $B * sin( $f2 );
	return array( round( rad2deg( atan2( $z, sqrt( $x * $x + $y * $y ) ) ), 3 ), round( rad2deg( atan2( $y, $x ) ), 3 ) );
}
function rau_cond_sol_alto( $lat, $lon, $t = null ) { // elevación del sol en grados (fórmula corta, ±1°)
	$t = $t ?: time(); $n = $t / 86400 + 2440587.5 - 2451545.0; $L = fmod( 280.46 + 0.9856474 * $n, 360 ); $g = deg2rad( fmod( 357.528 + 0.9856003 * $n, 360 ) );
	$lam = deg2rad( $L + 1.915 * sin( $g ) + 0.02 * sin( 2 * $g ) ); $eps = deg2rad( 23.439 - 0.0000004 * $n );
	$dec = asin( sin( $eps ) * sin( $lam ) ); $ra = atan2( cos( $eps ) * sin( $lam ), cos( $lam ) );
	$gmst = fmod( 280.46061837 + 360.98564736629 * $n, 360 ); $ha = deg2rad( fmod( $gmst + $lon, 360 ) ) - $ra;
	return rad2deg( asin( sin( deg2rad( $lat ) ) * sin( $dec ) + cos( deg2rad( $lat ) ) * cos( $dec ) * cos( $ha ) ) );
}
function rau_cond_enlace( $a, $b, $geo = null ) {
	$d = rau_cond_km( $a, $b ); if ( $d < 1 ) return null;
	$sp = rau_cond_rumbo( $a, $b ); $lp = fmod( $sp + 180, 360 );
	$cps = $d <= 4000 ? array( rau_cond_intermedio( $a, $b, .5 ) ) : array( rau_cond_intermedio( $a, $b, 2000 / $d ), rau_cond_intermedio( $a, $b, 1 - 2000 / $d ) );
	$drap_txt = null; list( $txt ) = rau_cond_traer( 'https://services.swpc.noaa.gov/text/drap_global_frequencies.txt', 5 * MINUTE_IN_SECONDS, 'texto' );
	$puntos = array(); $min = INF; $dia = false; $abs = 0;
	foreach ( $cps as $p ) {
		$m = rau_cond_muf_en( $p[0], $p[1], $geo ); $alto = rau_cond_sol_alto( $p[0], $p[1] ); $dr = is_string( $txt ) ? rau_cond_drap_leer( $txt, $p[0], $p[1] ) : null;
		$puntos[] = array( 'lat' => $p[0], 'lon' => $p[1], 'muf' => $m, 'sol' => round( $alto ), 'drap' => $dr ? $dr['mhz'] : null );
		if ( null !== $m ) $min = min( $min, $m ); if ( $alto > 0 ) $dia = true; if ( $dr ) $abs = max( $abs, (float) $dr['mhz'] );
	}
	if ( INF === $min ) return array( 'km' => $d, 'sp' => round( $sp ), 'lp' => round( $lp ), 'puntos' => $puntos, 'muf' => null, 'bandas' => array(), 'saltos' => (int) ceil( $d / 4000 ) );
	$ratio = $d <= 4000 ? min( 1.1, 0.3 + 0.7 * $d / 3000 ) : 1.1; // MUF(d)/MUF(3000): ~foF2 en vertical, ×1,1 a 4000 km
	$muf = round( $min * $ratio, 1 ); $fot = round( 0.85 * $muf, 1 );
	$luf = max( $abs, $dia ? ( $d > 1500 ? 7.0 : 4.0 ) : ( $d > 3000 ? 3.0 : 1.8 ) );
	$bandas = array();
	foreach ( array( '160 m' => 1.85, '80 m' => 3.7, '60 m' => 5.35, '40 m' => 7.1, '30 m' => 10.12, '20 m' => 14.2, '17 m' => 18.1, '15 m' => 21.2, '12 m' => 24.9, '10 m' => 28.5 ) as $bn => $f ) {
		$e = $f > $muf ? 'cerrada' : ( $f < $luf ? 'absorbida' : ( $f <= $fot ? 'buena' : 'marginal' ) );
		$bandas[] = array( 'b' => $bn, 'f' => $f, 'e' => $e );
	}
	return array( 'km' => $d, 'sp' => round( $sp ), 'lp' => round( $lp ), 'saltos' => (int) ceil( $d / 4000 ), 'puntos' => $puntos, 'muf' => $muf, 'fot' => $fot, 'luf' => round( $luf, 1 ), 'dia' => $dia, 'bandas' => $bandas );
}

/* ── Alcance real: WSPR de la región (FJ, FK, FI) en la última hora ─────── */
function rau_cond_wspr() {
	$c = get_transient( 'rau_cond_wspr' ); if ( is_array( $c ) ) return $c;
	$BANDA = array( 1 => '160 m', 3 => '80 m', 5 => '60 m', 7 => '40 m', 10 => '30 m', 14 => '20 m', 18 => '17 m', 21 => '15 m', 24 => '12 m', 28 => '10 m', 50 => '6 m', 144 => '2 m' );
	$q = function( $donde ) { return "SELECT time, band, tx_sign, tx_loc, tx_lat, tx_lon, rx_sign, rx_loc, rx_lat, rx_lon, distance, snr FROM wspr.rx WHERE time > now() - INTERVAL 1 HOUR AND substring($donde,1,2) IN ('FJ','FK','FI') ORDER BY distance DESC LIMIT 250 FORMAT JSON"; };
	$out = array( 'sale' => array(), 'entra' => array(), 'bandas' => array(), 'ok' => false );
	foreach ( array( 'sale' => 'tx_loc', 'entra' => 'rx_loc' ) as $k => $col ) {
		$r = rau_remote_get( 'https://db1.wspr.live/?query=' . rawurlencode( $q( $col ) ), array( 'timeout' => 20, 'user-agent' => 'HK4RAU/1.0 (+https://hk4rau.com)' ), 10 );
		$ok = ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ); rau_cond_salud_anotar( 'https://db1.wspr.live/', $ok ); if ( ! $ok ) continue;
		$d = json_decode( wp_remote_retrieve_body( $r ), true ); if ( ! isset( $d['data'] ) ) continue; $out['ok'] = true;
		foreach ( $d['data'] as $x ) {
			$b = $BANDA[ (int) $x['band'] ] ?? null; if ( ! $b ) continue;
			$out[ $k ][] = array( 'b' => $b, 'tx' => $x['tx_sign'], 'rx' => $x['rx_sign'], 'a' => array( (float) $x['tx_lat'], (float) $x['tx_lon'] ), 'z' => array( (float) $x['rx_lat'], (float) $x['rx_lon'] ), 'km' => (int) $x['distance'], 'snr' => (int) $x['snr'] );
			$bb = &$out['bandas'][ $b ]; if ( ! $bb ) $bb = array( 'sale' => 0, 'entra' => 0, 'max' => 0 ); $bb[ $k ]++; $bb['max'] = max( $bb['max'], (int) $x['distance'] ); unset( $bb );
		}
	}
	set_transient( 'rau_cond_wspr', $out, 10 * MINUTE_IN_SECONDS );
	return $out;
}

/* ── POTA ───────────────────────────────────────────────────────────────── */
function rau_cond_pota() {
	list( $d ) = rau_cond_traer( 'https://api.pota.app/spot/activator', 3 * MINUTE_IN_SECONDS, 'json', 15 );
	$out = array();
	foreach ( (array) $d as $x ) {
		if ( ! isset( $x['latitude'], $x['longitude'] ) || ! empty( $x['invalid'] ) ) continue;
		$out[] = array( 'act' => (string) $x['activator'], 'f' => (string) $x['frequency'], 'modo' => (string) ( $x['mode'] ?? '' ), 'ref' => (string) $x['reference'], 'parque' => (string) ( $x['name'] ?? $x['parkName'] ?? '' ),
			'lugar' => (string) ( $x['locationDesc'] ?? '' ), 'lat' => (float) $x['latitude'], 'lon' => (float) $x['longitude'], 't' => strtotime( ( $x['spotTime'] ?? '' ) . ' UTC' ) );
	}
	return $out;
}

/* ── Corrientes rápidas de agujeros coronales (NASA DONKI HSS) ───────────── */
function rau_cond_hss() {
	$c = get_transient( 'rau_cond_hss' ); if ( is_array( $c ) ) return $c;
	$clave = defined( 'RAU_NASA_KEY' ) && RAU_NASA_KEY ? RAU_NASA_KEY : 'DEMO_KEY';
	$r = rau_remote_get( 'https://api.nasa.gov/DONKI/HSS?startDate=' . gmdate( 'Y-m-d', time() - 30 * DAY_IN_SECONDS ) . '&endDate=' . gmdate( 'Y-m-d' ) . '&api_key=' . rawurlencode( $clave ), array( 'timeout' => 20 ), 30 );
	$d = ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ? json_decode( wp_remote_retrieve_body( $r ), true ) : null;
	if ( ! is_array( $d ) ) { $v = get_option( 'rau_cond_hss_ult' ); $v = is_array( $v ) ? $v : array(); set_transient( 'rau_cond_hss', $v, 30 * MINUTE_IN_SECONDS ); return $v; }
	$out = array();
	foreach ( $d as $x ) { $t = strtotime( (string) ( $x['eventTime'] ?? '' ) ); if ( ! $t ) continue; $out[] = array( 'id' => (string) $x['hssID'], 't' => $t, 'regreso' => $t + (int) round( 27.27 * DAY_IN_SECONDS ), 'url' => (string) ( $x['link'] ?? '' ) ); }
	usort( $out, function( $a, $b ) { return $b['t'] - $a['t']; } );
	update_option( 'rau_cond_hss_ult', $out, false ); set_transient( 'rau_cond_hss', $out, 3 * HOUR_IN_SECONDS );
	return $out;
}
add_filter( 'rau_cond_solar_extra', function( $s ) { $s['hss'] = rau_cond_hss(); return $s; } );

/* ── Archivo diario de eventos (90 días) ───────────────────────────────── */
function rau_cond_dia( $t ) { return wp_date( 'Y-m-d', $t, new DateTimeZone( 'America/Bogota' ) ); }
function rau_cond_archivar() {
	$a = get_option( 'rau_cond_archivo', array() ); if ( ! is_array( $a ) ) $a = array();
	$nuevo = function( $t, $tipo, $nivel, $texto ) use ( &$a ) {
		$d = rau_cond_dia( $t ); $k = substr( md5( $tipo . '|' . $t . '|' . $texto ), 0, 10 );
		if ( ! isset( $a[ $d ] ) ) $a[ $d ] = array( 'ev' => array(), 'kp' => null, 'sfi' => null, 'fl' => '' );
		if ( ! isset( $a[ $d ]['ev'][ $k ] ) ) $a[ $d ]['ev'][ $k ] = array( 't' => (int) $t, 'tipo' => $tipo, 'n' => $nivel, 'x' => $texto );
	};
	$tend = rau_cond_tendencias();
	foreach ( (array) $tend['llamaradas'] as $f ) { $nuevo( $f['t'], 'sol', 'X' === $f['clase'][0] ? 2 : ( 'M' === $f['clase'][0] ? 1 : 0 ), 'Llamarada ' . $f['clase'] ); $d = rau_cond_dia( $f['t'] ); if ( isset( $a[ $d ] ) && strcmp( rau_cond_clase_num( $f['clase'] ), rau_cond_clase_num( $a[ $d ]['fl'] ) ) > 0 ) $a[ $d ]['fl'] = $f['clase']; }
	foreach ( (array) ( ! empty( $tend['kp7'] ) ? $tend['kp7'] : $tend['kp'] ) as $k ) { $d = rau_cond_dia( $k[0] ); if ( ! isset( $a[ $d ] ) ) $a[ $d ] = array( 'ev' => array(), 'kp' => null, 'sfi' => null, 'fl' => '' ); $a[ $d ]['kp'] = max( (float) $a[ $d ]['kp'], (float) $k[1] ); if ( $k[1] >= 5 ) $nuevo( $k[0], 'geomag', 2, 'Tormenta geomagnética: Kp ' . number_format( $k[1], 1, ',', '' ) ); }
	$esp = rau_cond_espacio(); $hoy = rau_cond_dia( time() ); if ( isset( $a[ $hoy ] ) && $esp['sfi'] ) $a[ $hoy ]['sfi'] = $esp['sfi'];
	foreach ( (array) $esp['alertas'] as $al ) $nuevo( strtotime( $al['hora'] ), 'noaa', 'ALERT' === $al['tipo'] ? 1 : 0, 'NOAA · ' . rau_cond_traducir( $al['tipo'] . ': ' . $al['texto'] ) );
	$sol = rau_cond_solar();
	foreach ( (array) $sol['cmes'] as $c ) $nuevo( $c['t'], 'cme', $c['tierra'] ? 1 : 0, 'Eyección de masa coronal' . ( $c['vel'] ? ' a ' . $c['vel'] . ' km/s' : '' ) . ( $c['tierra'] ? ' (hacia la Tierra)' : '' ) );
	foreach ( (array) ( $sol['hss'] ?? array() ) as $h ) $nuevo( $h['t'], 'hss', 1, 'Corriente rápida de viento solar (agujero coronal)' );
	krsort( $a ); $a = array_slice( $a, 0, 90, true );
	update_option( 'rau_cond_archivo', $a, false ); update_option( 'rau_cond_archivo_t', time(), false );
	return count( $a );
}
function rau_cond_clase_num( $c ) { $c = strtoupper( (string) $c ); $o = strpos( 'ABCMX', substr( $c, 0, 1 ) ); return false === $o ? '0' : $o . sprintf( '%05.1f', (float) substr( $c, 1 ) ); }
add_action( 'rau_cond_precarga', 'rau_cond_archivar', 20 );
/* El cron de WordPress casi no corre detrás de la caché de página: si el archivo tiene más de 30 min, se pone al día al consultarlo */
function rau_cond_archivo_al_dia() {
	if ( (int) get_option( 'rau_cond_archivo_t', 0 ) < time() - 30 * MINUTE_IN_SECONDS ) rau_cond_archivar();
	return get_option( 'rau_cond_archivo', array() );
}

/* ── Resumen de la semana para un punto ────────────────────────────────── */
function rau_cond_semana( $lat, $lon ) {
	list( $lat, $lon ) = rau_cond_redondear( $lat, $lon );
	$url = add_query_arg( array( 'latitude' => $lat, 'longitude' => $lon, 'timezone' => 'auto', 'past_days' => 7, 'forecast_days' => 1, 'wind_speed_unit' => 'kmh',
		'daily' => 'weather_code,precipitation_sum,wind_gusts_10m_max,temperature_2m_max,temperature_2m_min' ), 'https://api.open-meteo.com/v1/forecast' );
	list( $d ) = rau_cond_traer( $url, 3 * HOUR_IN_SECONDS );
	$dias = array();
	foreach ( (array) ( $d['daily']['time'] ?? array() ) as $i => $t ) { if ( $i >= 7 ) break; $dias[ $t ] = array( 'dia' => $t, 'mm' => $d['daily']['precipitation_sum'][ $i ] ?? null, 'rafaga' => $d['daily']['wind_gusts_10m_max'][ $i ] ?? null, 'tormenta' => in_array( (int) ( $d['daily']['weather_code'][ $i ] ?? 0 ), array( 95, 96, 99 ), true ), 'max' => $d['daily']['temperature_2m_max'][ $i ] ?? null, 'min' => $d['daily']['temperature_2m_min'][ $i ] ?? null ); }
	$a = rau_cond_archivo_al_dia(); $ev = 0; $fl = '';
	foreach ( $dias as $t => &$x ) { $ar = $a[ $t ] ?? null; $x['kp'] = $ar['kp'] ?? null; $x['sfi'] = $ar['sfi'] ?? null; $x['fl'] = $ar['fl'] ?? ''; $ev += $ar ? count( $ar['ev'] ) : 0; if ( strcmp( rau_cond_clase_num( $x['fl'] ), rau_cond_clase_num( $fl ) ) > 0 ) $fl = $x['fl']; }
	unset( $x );
	$lluvia = array_sum( array_map( function( $x ) { return (float) $x['mm']; }, $dias ) ); $torm = count( array_filter( $dias, function( $x ) { return $x['tormenta']; } ) );
	$kps = array_filter( array_map( function( $x ) { return $x['kp']; }, $dias ), 'is_numeric' ); $kpmax = $kps ? max( $kps ) : null;
	$rafm = max( array_map( function( $x ) { return (float) $x['rafaga']; }, $dias ) ?: array( 0 ) );
	$txt = 'En los últimos 7 días: ' . ( $torm ? $torm . ' día' . ( 1 === $torm ? '' : 's' ) . ' con tormenta eléctrica' : 'sin tormentas eléctricas' ) . ( $rafm ? ', ráfagas de hasta ' . round( $rafm ) . ' km/h' : '' ) . '. '
		. ( null !== $kpmax ? 'Kp máximo ' . number_format( $kpmax, 1, ',', '' ) . ( $kpmax >= 5 ? ' (hubo tormenta geomagnética)' : '' ) . '. ' : '' ) . ( $fl ? 'Llamarada más fuerte: ' . $fl . '.' : '' );
	return array( 'dias' => array_values( $dias ), 'lluvia' => round( $lluvia, 1 ), 'tormentas' => $torm, 'kp_max' => $kpmax, 'llamarada' => $fl, 'eventos' => $ev, 'texto' => $txt );
}

/* ── REST ───────────────────────────────────────────────────────────────── */
add_action( 'rest_api_init', function() {
	$num = function( $v ) { return is_numeric( $v ); };
	$cache = function( $datos, $seg ) { $r = new WP_REST_Response( $datos ); $r->header( 'Cache-Control', 'public, max-age=' . $seg ); return $r; };
	register_rest_route( 'rau/v1', '/condiciones/muf', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function() use ( $cache ) { return $cache( rau_cond_muf_geo(), 600 ); } ) );
	register_rest_route( 'rau/v1', '/condiciones/enlace', array( 'methods' => 'GET', 'permission_callback' => '__return_true',
		'args' => array( 'lat' => array( 'required' => true, 'validate_callback' => $num ), 'lon' => array( 'required' => true, 'validate_callback' => $num ), 'dlat' => array( 'required' => true, 'validate_callback' => $num ), 'dlon' => array( 'required' => true, 'validate_callback' => $num ) ),
		'callback' => function( WP_REST_Request $r ) use ( $cache ) { $a = rau_cond_redondear( $r['lat'], $r['lon'] ); $b = array( max( -89.9, min( 89.9, (float) $r['dlat'] ) ), (float) $r['dlon'] ); $e = rau_cond_enlace( $a, $b ); return $e ? $cache( $e, 300 ) : new WP_REST_Response( array( 'error' => 'El destino está en el mismo punto.' ), 400 ); } ) );
	register_rest_route( 'rau/v1', '/condiciones/regiones', array( 'methods' => 'GET', 'permission_callback' => '__return_true',
		'args' => array( 'lat' => array( 'required' => true, 'validate_callback' => $num ), 'lon' => array( 'required' => true, 'validate_callback' => $num ) ),
		'callback' => function( WP_REST_Request $r ) use ( $cache ) { $a = rau_cond_redondear( $r['lat'], $r['lon'] ); return $cache( rau_cond_regiones( $a[0], $a[1] ), 300 ); } ) );
	register_rest_route( 'rau/v1', '/condiciones/wspr', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function() use ( $cache ) { return $cache( rau_cond_wspr(), 300 ); } ) );
	register_rest_route( 'rau/v1', '/condiciones/pota', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function() use ( $cache ) { return $cache( rau_cond_pota(), 120 ); } ) );
	register_rest_route( 'rau/v1', '/condiciones/archivo', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function( WP_REST_Request $r ) use ( $cache ) {
		$a = rau_cond_archivo_al_dia(); if ( ! is_array( $a ) ) $a = array();
		$dias = array(); foreach ( $a as $d => $x ) $dias[] = array( 'dia' => $d, 'kp' => $x['kp'], 'sfi' => $x['sfi'], 'fl' => $x['fl'], 'n' => count( $x['ev'] ) );
		$con = array_values( array_filter( $dias, function( $x ) { return $x['n'] > 0; } ) ); // por defecto, el último día con eventos
		$dia = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $r['dia'] ) ? (string) $r['dia'] : ( $con ? $con[0]['dia'] : ( $dias ? $dias[0]['dia'] : rau_cond_dia( time() ) ) );
		$ev = array_values( (array) ( $a[ $dia ]['ev'] ?? array() ) ); usort( $ev, function( $p, $q ) { return $q['t'] - $p['t']; } );
		return $cache( array( 'dias' => $dias, 'dia' => $dia, 'eventos' => $ev ), 300 );
	} ) );
	register_rest_route( 'rau/v1', '/condiciones/semana', array( 'methods' => 'GET', 'permission_callback' => '__return_true',
		'args' => array( 'lat' => array( 'validate_callback' => $num ), 'lon' => array( 'validate_callback' => $num ) ),
		'callback' => function( WP_REST_Request $r ) use ( $cache ) { return $cache( rau_cond_semana( null !== $r['lat'] ? (float) $r['lat'] : RAU_COND_PUNTO[0], null !== $r['lon'] ? (float) $r['lon'] : RAU_COND_PUNTO[1] ), 1800 ); } ) );
} );

/* ── Tarjeta PNG para compartir (también es la imagen al compartir el enlace) y franja para OBS ── */
add_action( 'init', function() {
	add_rewrite_rule( '^condiciones/tarjeta\.png$', 'index.php?rau_cond_tarjeta=1', 'top' );
	add_rewrite_rule( '^condiciones/franja/?$', 'index.php?rau_cond_franja=1', 'top' );
} );
add_filter( 'query_vars', function( $v ) { $v[] = 'rau_cond_tarjeta'; $v[] = 'rau_cond_franja'; return $v; } );
/* 19.66.0: qué bandas sirven ahora hacia cada región del mundo (una fila por región) */
const RAU_COND_REGIONES = array(
	array( 'Caribe', 18.5, -66.1 ), array( 'EE. UU. este', 40.7, -74.0 ), array( 'EE. UU. oeste', 34.1, -118.2 ), array( 'Suramérica sur', -34.6, -58.4 ),
	array( 'Europa', 48.9, 2.35 ), array( 'África', -26.2, 28.0 ), array( 'Japón', 35.7, 139.7 ), array( 'Oceanía', -33.9, 151.2 ),
);
function rau_cond_regiones( $lat, $lon ) {
	$k = 'rau_cond_reg_' . md5( $lat . ',' . $lon ); $c = get_transient( $k ); if ( is_array( $c ) ) return $c;
	$geo = rau_cond_muf_geo(); $out = array();
	foreach ( RAU_COND_REGIONES as $rg ) { $e = rau_cond_enlace( array( $lat, $lon ), array( $rg[1], $rg[2] ), $geo ); if ( ! $e ) continue;
		$out[] = array( 'n' => $rg[0], 'lat' => $rg[1], 'lon' => $rg[2], 'km' => $e['km'], 'sp' => $e['sp'], 'muf' => $e['muf'], 'bandas' => wp_list_pluck( $e['bandas'], 'e', 'b' ) ); }
	set_transient( $k, $out, 10 * MINUTE_IN_SECONDS );
	return $out;
}
function rau_cond_punto_get() {
	$lat = isset( $_GET['lat'] ) && is_numeric( $_GET['lat'] ) ? (float) $_GET['lat'] : RAU_COND_PUNTO[0];
	$lon = isset( $_GET['lon'] ) && is_numeric( $_GET['lon'] ) ? (float) $_GET['lon'] : RAU_COND_PUNTO[1];
	return rau_cond_redondear( $lat, $lon );
}
function rau_cond_tarjeta_archivo( $lat, $lon ) {
	if ( ! function_exists( 'imagettftext' ) || ! function_exists( 'rau_og_dir' ) ) return null;
	list( $dir, $url ) = rau_og_dir(); if ( ! wp_mkdir_p( $dir . '/cond' ) ) return null;
	$nombre = 'cond/' . md5( $lat . ',' . $lon . '|' . floor( time() / 600 ) ) . '.png';
	if ( file_exists( "$dir/$nombre" ) ) return array( "$dir/$nombre", "$url/$nombre" );
	$c = rau_condiciones( $lat, $lon ); $s = $c['semaforo'] ?? array();
	$fb = get_template_directory() . '/assets/fonts/SpaceGrotesk-Bold.ttf'; $fm = get_template_directory() . '/assets/fonts/SpaceGrotesk-Medium.ttf';
	$W = 1200; $H = 630; $im = imagecreatetruecolor( $W, $H );
	for ( $y = 0; $y < $H; $y++ ) { $k = $y / $H; imageline( $im, 0, $y, $W, $y, imagecolorallocate( $im, (int) ( 8 + 6 * $k ), (int) ( 12 + 8 * $k ), (int) ( 22 + 14 * $k ) ) ); }
	$cian = imagecolorallocate( $im, 56, 189, 248 ); $blanco = imagecolorallocate( $im, 241, 245, 249 ); $gris = imagecolorallocate( $im, 148, 163, 184 );
	$col = array( imagecolorallocate( $im, 34, 197, 94 ), imagecolorallocate( $im, 245, 158, 11 ), imagecolorallocate( $im, 244, 63, 94 ) ); $pal = array( 'NORMAL', 'ATENCIÓN', 'ALERTA' );
	imagefilledrectangle( $im, 0, 0, $W, 8, $cian );
	$lugar = ! empty( $c['punto']['cerca'] ) ? 'cerca de ' . $c['punto']['cerca']['nombre'] : $lat . ', ' . $lon;
	$off = $c['clima']['offset'] ?? -18000;
	imagettftext( $im, 20, 0, 60, 70, $cian, $fm, 'RAU · CONDICIONES PARA OPERAR' );
	imagettftext( $im, 40, 0, 60, 128, $blanco, $fb, mb_substr( ucfirst( $lugar ), 0, 34 ) );
	imagettftext( $im, 20, 0, 60, 168, $gris, $fm, gmdate( 'd/m/Y H:i', time() + $off ) . ' hora local · ' . ( $c['punto']['locator'] ?? '' ) );
	$y = 230; $nom = array( 'tormenta' => 'Tormenta', 'viento' => 'Viento', 'hf' => 'HF', 'sol' => 'Sol', 'geomag' => 'Campo magnético', 'seis' => '6 m' );
	foreach ( $nom as $k => $n ) {
		if ( empty( $s[ $k ] ) ) continue; $nv = (int) $s[ $k ]['nivel'];
		$cc = ! empty( $s[ $k ]['op'] ) ? ( $nv ? $cian : $gris ) : $col[ $nv ]; $pp = ! empty( $s[ $k ]['op'] ) ? array( 'CERRADA', 'ALGO', 'ABIERTA' )[ $nv ] : $pal[ $nv ];
		imagefilledellipse( $im, 76, $y - 10, 22, 22, $cc );
		imagettftext( $im, 24, 0, 100, $y, $blanco, $fb, $n );
		imagettftext( $im, 17, 0, 340, $y - 2, $cc, $fb, $pp );
		$t = $s[ $k ]['texto']; $t = mb_strlen( $t ) > 62 ? mb_substr( $t, 0, 60 ) . '…' : $t;
		imagettftext( $im, 18, 0, 480, $y - 2, $gris, $fm, $t );
		$y += 58;
	}
	imagettftext( $im, 20, 0, 60, $H - 40, $cian, $fm, preg_replace( '#^https?://#', '', home_url( '/condiciones/' ) ) );
	imagepng( $im, "$dir/$nombre", 6 ); imagedestroy( $im );
	$viejos = glob( "$dir/cond/*.png" ) ?: array(); if ( count( $viejos ) > 300 ) { usort( $viejos, function( $a, $b ) { return filemtime( $a ) - filemtime( $b ); } ); foreach ( array_slice( $viejos, 0, count( $viejos ) - 200 ) as $f ) @unlink( $f ); }
	return array( "$dir/$nombre", "$url/$nombre" );
}
add_action( 'template_redirect', function() {
	if ( get_query_var( 'rau_cond_tarjeta' ) ) {
		list( $lat, $lon ) = rau_cond_punto_get(); $f = rau_cond_tarjeta_archivo( $lat, $lon );
		if ( ! $f ) { status_header( 404 ); exit; }
		header( 'Content-Type: image/png' ); header( 'Cache-Control: public, max-age=600' ); readfile( $f[0] ); exit;
	}
	if ( get_query_var( 'rau_cond_franja' ) ) { rau_cond_franja(); exit; }
} );
/* Al compartir /condiciones/?lat=&lon= en WhatsApp o redes, la vista previa es la tarjeta con el semáforo de ese punto */
add_filter( 'rau_og_extra', function( $o ) {
	if ( ! get_query_var( 'rau_condiciones' ) ) return $o;
	list( $lat, $lon ) = rau_cond_punto_get(); $c = rau_condiciones( $lat, $lon );
	$lugar = ! empty( $c['punto']['cerca'] ) ? $c['punto']['cerca']['nombre'] : $c['punto']['locator'];
	$al = array(); foreach ( (array) $c['semaforo'] as $s ) if ( 2 === (int) $s['nivel'] && empty( $s['op'] ) ) $al[] = $s['texto'];
	return array( 'title' => 'Condiciones para operar en ' . $lugar, 'desc' => $al ? 'ALERTA: ' . implode( ' ', array_slice( $al, 0, 2 ) ) : 'Tiempo, tormentas, sol, campo magnético y bandas de HF para ' . $lugar . ', en vivo.',
		'img' => add_query_arg( array( 'lat' => $lat, 'lon' => $lon, 'v' => floor( time() / 600 ) ), home_url( '/condiciones/tarjeta.png' ) ) );
} );
function rau_cond_franja() {
	list( $lat, $lon ) = rau_cond_punto_get(); $claro = isset( $_GET['claro'] ); $transp = isset( $_GET['transparente'] );
	header( 'Content-Type: text/html; charset=utf-8' ); header( 'X-LiteSpeed-Cache-Control: public,max-age=300' );
	$api = add_query_arg( array( 'lat' => $lat, 'lon' => $lon ), rest_url( 'rau/v1/condiciones' ) );
	?><!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Franja de condiciones · RAU</title>
<style>
:root{--f:<?php echo $transp ? 'transparent' : ( $claro ? '#f6f7fb' : '#080c16' ); ?>;--c:<?php echo $claro ? '#ffffff' : '#111827'; ?>;--t:<?php echo $claro ? '#0f172a' : '#e5e7eb'; ?>;--m:<?php echo $claro ? '#475569' : '#94a3b8'; ?>}
html,body{margin:0;background:var(--f);color:var(--t);font:600 18px/1.2 system-ui,-apple-system,"Segoe UI",sans-serif;overflow:hidden}
.f{display:flex;align-items:stretch;gap:8px;padding:8px}.b{background:var(--c);border-radius:10px;padding:8px 12px;display:flex;flex-direction:column;justify-content:center;border-top:4px solid #64748b;min-width:0}
.b small{font:700 11px ui-monospace,monospace;letter-spacing:.12em;text-transform:uppercase;color:var(--m)}.b b{font-size:19px;white-space:nowrap}.r{font-family:ui-monospace,monospace}
.n0{border-top-color:#22c55e}.n1{border-top-color:#f59e0b}.n2{border-top-color:#f43f5e}.n2 b{color:#f43f5e}.marca{color:#38bdf8}
</style></head><body><div class="f" id="f"><div class="b"><small class="marca">RAU · condiciones</small><b class="r" id="h">--:--</b></div></div>
<script>
(function(){ var nom={tormenta:'Tormenta',viento:'Viento',hf:'HF',sol:'Sol',geomag:'Geomagnético',seis:'6 m'}, pal=['Normal','Atención','ALERTA'], f=document.getElementById('f'), off=-18000, esp={};
function esc(t){ return String(t).replace(/[&<>"]/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
function pad(x){ return (x<10?'0':'')+x; }
function hora(){ var d=new Date(Date.now()+off*1000), u=new Date(); document.getElementById('h').textContent=pad(d.getUTCHours())+':'+pad(d.getUTCMinutes())+' · '+pad(u.getUTCHours())+':'+pad(u.getUTCMinutes())+' UTC'; }
function pintar(j){ if(j.clima&&j.clima.offset!=null) off=j.clima.offset; var s=j.semaforo||{}, e=j.espacio||{};
	f.innerHTML='<div class="b"><small class="marca">RAU · '+esc(j.punto&&j.punto.cerca?j.punto.cerca.nombre:'condiciones')+'</small><b class="r" id="h"></b></div>'
		+Object.keys(nom).filter(function(k){ return s[k]; }).map(function(k){ return '<div class="b n'+s[k].nivel+'" title="'+esc(s[k].texto)+'"><small>'+nom[k]+'</small><b>'+pal[s[k].nivel]+'</b></div>'; }).join('')
		+'<div class="b"><small>Kp · SFI</small><b class="r">'+(e.kp?e.kp.valor.toFixed(1):'—')+' · '+(e.sfi||'—')+'</b></div>'; hora(); }
function cargar(){ fetch(<?php echo wp_json_encode( $api ); ?>).then(function(r){ return r.json(); }).then(pintar).catch(function(){}); }
cargar(); setInterval(cargar,120000); setInterval(hora,15000); })();
</script></body></html><?php
}
