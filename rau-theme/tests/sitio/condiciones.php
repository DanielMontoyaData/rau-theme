<?php
/**
 * Condiciones (inc/condiciones.php): cada fuente simulada, sin salir a internet.
 *   php tests/sitio/condiciones.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php condiciones.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
global $wpdb;
$limpiar = function() use ( $wpdb ) { $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%rau_cond%' OR option_name LIKE 'rau_condr_%' OR option_name LIKE '_transient%rau_caida_%' OR option_name LIKE '_transient%rau_solar_data%'" ); wp_cache_flush(); };
$limpiar();

/* Respuestas simuladas */
$ahora_local = gmdate( 'Y-m-d\TH:00', time() - 5 * HOUR_IN_SECONDS );
$horas = array(); for ( $i = -2; $i < 50; $i++ ) $horas[] = gmdate( 'Y-m-d\TH:00', time() - 5 * HOUR_IN_SECONDS + $i * HOUR_IN_SECONDS );
$col = function( $v ) use ( $horas ) { return array_fill( 0, count( $horas ), $v ); };
$cod = $col( 3 ); $cod[3] = 95; // tormenta dentro de la próxima hora (índice 2 = ahora)
$raf = $col( 20 ); $raf[4] = 65;
$om = array( 'utc_offset_seconds' => -18000, 'timezone' => 'America/Bogota', 'elevation' => 1495,
	'current' => array( 'temperature_2m' => 22.5, 'apparent_temperature' => 23, 'relative_humidity_2m' => 70, 'is_day' => 1, 'precipitation' => 0, 'weather_code' => 3, 'cloud_cover' => 80, 'surface_pressure' => 850, 'wind_speed_10m' => 8, 'wind_direction_10m' => 90, 'wind_gusts_10m' => 20, 'visibility' => 20000, 'cape' => 900 ),
	'hourly' => array( 'time' => $horas, 'temperature_2m' => $col( 21 ), 'relative_humidity_2m' => $col( 70 ), 'precipitation_probability' => $col( 40 ), 'precipitation' => $col( 0 ), 'weather_code' => $cod,
		'wind_speed_10m' => $col( 10 ), 'wind_gusts_10m' => $raf, 'cape' => $col( 800 ), 'lifted_index' => $col( 1 ), 'visibility' => $col( 20000 ), 'is_day' => $col( 1 ) ),
	'daily' => array( 'time' => array( '2026-11-20', '2026-11-21', '2026-11-22' ), 'weather_code' => array( 95, 3, 61 ), 'temperature_2m_max' => array( 26, 27, 25 ), 'temperature_2m_min' => array( 16, 17, 16 ),
		'precipitation_sum' => array( 5, 0, 2 ), 'precipitation_probability_max' => array( 80, 10, 60 ), 'wind_gusts_10m_max' => array( 65, 30, 25 ), 'uv_index_max' => array( 9, 10, 8 ), 'sunrise' => array( '', '', '' ), 'sunset' => array( '', '', '' ) ) );
$open_meteo_ok = true; $flare = array( 'current_class' => 'B2.0', 'max_class' => 'B5.0', 'max_time' => gmdate( 'Y-m-d\TH:i:s\Z', time() - 3 * HOUR_IN_SECONDS ) ); $escalaR = 0; $kp = 1.3;
$drap = "# DRAP\n#  X-RAY Message : Normal X-ray Background\n#\n      -178 -174 -170 -166 -162 -158 -154 -150 -146 -142 -138 -134 -130 -126 -122 -118 -114 -110 -106 -102  -98  -94  -90  -86  -82  -78  -74  -70  -66\n---\n"
	. "  7 |  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0 12.5  0.0  0.0\n"
	. "  5 |  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  0.0  3.0  0.0  0.0  0.0\n";
$pedidas = array();
add_filter( 'pre_http_request', function( $pre, $args, $url ) use ( &$om, &$open_meteo_ok, &$flare, &$escalaR, &$kp, $drap, &$pedidas ) {
	$pedidas[] = $url;
	$r = function( $b, $code = 200 ) { return array( 'headers' => array(), 'body' => is_string( $b ) ? $b : wp_json_encode( $b ), 'response' => array( 'code' => $code, 'message' => '' ), 'cookies' => array() ); };
	if ( false !== strpos( $url, '//api.open-meteo.com' ) ) return $open_meteo_ok ? $r( $om ) : $r( array( 'error' => true, 'reason' => 'Daily API request limit exceeded' ), 429 );
	if ( false !== strpos( $url, 'air-quality-api' ) ) return $r( array( 'current' => array( 'pm2_5' => 12, 'pm10' => 20, 'us_aqi' => 55, 'uv_index' => 4 ) ) );
	if ( false !== strpos( $url, 'api.met.no' ) ) return $r( array( 'geometry' => array( 'coordinates' => array( -75.6, 6.2, 1500 ) ), 'properties' => array( 'timeseries' => array(
		array( 'time' => gmdate( 'Y-m-d\TH:00:00\Z', time() + 1800 ), 'data' => array( 'instant' => array( 'details' => array( 'air_temperature' => 19, 'relative_humidity' => 90, 'wind_speed' => 5, 'wind_from_direction' => 45, 'cloud_area_fraction' => 100 ) ), 'next_1_hours' => array( 'summary' => array( 'symbol_code' => 'heavyrainandthunder' ), 'details' => array( 'precipitation_amount' => 4 ) ) ) ) ) ) ) );
	if ( false !== strpos( $url, 'planetary_k_index_1m' ) ) return $r( array( array( 'time_tag' => 'x', 'estimated_kp' => $kp ) ) );
	if ( false !== strpos( $url, 'k-index-forecast' ) ) return $r( array( array( 'time_tag' => gmdate( 'Y-m-d\TH:00:00', time() + 3 * HOUR_IN_SECONDS ), 'kp' => 3.67, 'observed' => 'predicted' ) ) );
	if ( false !== strpos( $url, 'xray-flares-latest' ) ) return $r( array( $flare ) );
	if ( false !== strpos( $url, 'noaa-scales' ) ) return $r( array( '0' => array( 'R' => array( 'Scale' => (string) $escalaR ), 'S' => array( 'Scale' => '0' ), 'G' => array( 'Scale' => '0' ) ), '1' => array( 'R' => array( 'MinorProb' => '10', 'MajorProb' => '5' ) ) ) );
	if ( false !== strpos( $url, 'rtsw_wind' ) ) return $r( array( array( 'active' => true, 'proton_speed' => 420.4, 'proton_density' => 3.2 ) ) );
	if ( false !== strpos( $url, 'rtsw_mag' ) ) return $r( array( array( 'active' => true, 'bz_gsm' => -4.2, 'bt' => 6 ) ) );
	if ( false !== strpos( $url, 'alerts.json' ) ) return $r( array( array( 'issue_datetime' => gmdate( 'Y-m-d H:i:s', time() - 3600 ), 'message' => "Space Weather Message Code: ALTK04\r\nALERT: Geomagnetic K-index of 4\nmore" ) ) );
	if ( false !== strpos( $url, 'drap_global' ) ) return $r( $drap );
	if ( false !== strpos( $url, 'hamqsl.com' ) ) return $r( '<?xml version="1.0"?><solar><solardata><solarflux>140</solarflux><sunspots>90</sunspots><aindex>8</aindex><kindex>1</kindex><xray>B2</xray><calculatedconditions><band name="80m-40m" time="day">Good</band><band name="80m-40m" time="night">Good</band><band name="12m-10m" time="day">Poor</band><band name="12m-10m" time="night">Poor</band></calculatedconditions></solardata></solar>' );
	if ( false !== strpos( $url, 'prop.kc2g.com' ) ) return $r( array( array( 'fof2' => 8.6, 'mufd' => 27.0, 'cs' => 75, 'time' => gmdate( 'Y-m-d\TH:i:s', time() - 1200 ), 'station' => array( 'name' => 'Boa Vista, Brazil', 'latitude' => '2.8', 'longitude' => '299.3' ) ) ) );
	if ( false !== strpos( $url, 'earthquake.usgs.gov' ) ) return $r( array( 'features' => array(
		array( 'properties' => array( 'mag' => 4.1, 'place' => 'Antioquia', 'time' => ( time() - 7200 ) * 1000, 'url' => 'u' ), 'geometry' => array( 'coordinates' => array( -75.9, 6.9, 30 ) ) ),
		array( 'properties' => array( 'mag' => 6.0, 'place' => 'Japón', 'time' => ( time() - 3600 ) * 1000, 'url' => 'u' ), 'geometry' => array( 'coordinates' => array( 140, 35, 10 ) ) ) ) ) );
	if ( false !== strpos( $url, 'geocoding-api' ) ) return $r( array( 'results' => array( array( 'name' => 'Rionegro', 'admin1' => 'Antioquia', 'country_code' => 'CO', 'country' => 'Colombia', 'latitude' => 6.155, 'longitude' => -75.374 ) ) ) );
	return $r( '', 404 );
}, 10, 3 );

/* Privacidad y utilidades */
ok( array( 6.2, -75.6 ) === rau_cond_redondear( 6.24423, -75.58123 ), 'el punto se redondea a 0,1° antes de todo' );
ok( 'FJ26' === substr( rau_cond_locator( 6.2442, -75.5812 ), 0, 4 ), 'locator de Medellín: FJ26' );
ok( 95 === rau_cond_met_codigo( 'heavyrainshowersandthunder_day' ) && 63 === rau_cond_met_codigo( 'rain' ) && 0 === rau_cond_met_codigo( 'clearsky_night' ), 'símbolos de MET Norway al código WMO' );
$d = rau_cond_drap_leer( $drap, 6.2, -75.6 );
ok( $d && 12.5 === $d['mhz'] && 'Normal X-ray Background' === $d['mensaje'], 'D-RAP: la celda más cercana (lat 7, lon −74) da 12,5 MHz' );
ok( 'Envigado' === rau_cond_cerca_de( 6.2, -75.6 )['nombre'] && null === rau_cond_cerca_de( 0, -30 ), 'ciudad cercana, y nada en mitad del mar' );

/* Todo junto */
$c = rau_condiciones( 6.24, -75.58 );
ok( 'Open-Meteo' === $c['clima']['fuente'] && 48 === count( $c['clima']['horas'] ) && $ahora_local === $c['clima']['horas'][0]['t'], 'tiempo de Open-Meteo: 48 horas desde la hora actual del lugar' );
ok( 2 === $c['semaforo']['tormenta']['nivel'] && false !== strpos( $c['semaforo']['tormenta']['texto'], 'Desconecta las antenas' ), 'tormenta en la próxima hora: alerta con qué hacer' );
ok( 2 === $c['semaforo']['viento']['nivel'] && 65 == $c['semaforo']['viento']['kmh'], 'ráfagas de 65 km/h: alerta de no subir a la torre' );
ok( 2 === $c['semaforo']['hf']['nivel'] && false !== strpos( $c['semaforo']['hf']['texto'], 'por debajo de ~13 MHz' ), 'absorción de 12,5 MHz en la zona: HF en alerta' );
ok( 0 === $c['semaforo']['sol']['nivel'] && 0 === $c['semaforo']['geomag']['nivel'], 'sol y campo magnético tranquilos' );
ok( 'Boa Vista, Brazil' === $c['iono']['iono']['nombre'] && '40 m' === $c['iono']['nvis']['banda'], 'ionosonda más cercana al punto y NVIS en 40 m' );
ok( 1 === count( $c['sismos'] ) && 'Antioquia' === $c['sismos'][0]['lugar'], 'solo los sismos a menos de 500 km' );
ok( 3.7 === $c['espacio']['kp_pron'] && 420 == $c['espacio']['viento']['vel'] && 'ALERT' === $c['espacio']['alertas'][0]['tipo'], 'clima espacial: pronóstico de Kp, viento solar y avisos de NOAA' );
ok( 140 === $c['espacio']['sfi'] && 2 === count( $c['espacio']['bandas'] ), 'bandas y flujo solar de HamQSL' );
ok( 55 === $c['aire']['aqi'], 'calidad del aire' );
$antes = count( $pedidas ); rau_condiciones( 6.21, -75.61 );
ok( count( $pedidas ) === $antes, 'otro punto de la misma celda no vuelve a consultar nada' );
ok( ! preg_grep( '/latitude=6\.24|lat=6\.24|6\.2442/', $pedidas ), 'nunca se manda la ubicación sin redondear' );

/* Respaldo: si Open-Meteo falla, MET Norway */
$limpiar(); $open_meteo_ok = false;
$c2 = rau_condiciones( 6.24, -75.58 );
ok( 'MET Norway' === $c2['clima']['fuente'] && 95 === $c2['clima']['actual']['cod'] && 18 == $c2['clima']['actual']['viento'], 'sin Open-Meteo: MET Norway, con tormenta y viento en km/h' );
ok( 2 === $c2['semaforo']['tormenta']['nivel'] && false !== strpos( $c2['semaforo']['viento']['texto'], 'viento de hasta' ), 'el semáforo funciona igual con el respaldo (sin ráfagas lo dice)' );
$open_meteo_ok = true;

/* Sol y campo magnético en alerta */
$limpiar(); $flare = array( 'current_class' => 'M6.1', 'max_class' => 'X1.2', 'max_time' => gmdate( 'Y-m-d\TH:i:s\Z', time() - 600 ) ); $kp = 6.3;
$c3 = rau_condiciones( 4.7, -74.1 );
ok( 2 === $c3['semaforo']['sol']['nivel'] && false !== strpos( $c3['semaforo']['sol']['texto'], 'X1.2' ), 'llamarada X hace 10 min: alerta de apagón de HF' );
ok( 2 === $c3['semaforo']['geomag']['nivel'], 'Kp 6,3: tormenta geomagnética' );

/* REST */
$q = new WP_REST_Request( 'GET', '/rau/v1/condiciones' ); $q->set_param( 'lat', 'abc' ); $q->set_param( 'lon', '-75' );
ok( 400 === rest_do_request( $q )->get_status(), 'coordenadas no numéricas: 400' );
$q = new WP_REST_Request( 'GET', '/rau/v1/condiciones' ); $q->set_param( 'lat', '4.71' ); $q->set_param( 'lon', '-74.07' );
$res = rest_do_request( $q ); ok( 200 === $res->get_status() && 4.7 === $res->get_data()['punto']['lat'], 'GET con coordenadas: 200 y punto redondeado' );
$q = new WP_REST_Request( 'GET', '/rau/v1/condiciones/buscar' ); $q->set_param( 'q', 'Rionegro' );
$b = rest_do_request( $q )->get_data(); ok( 'Rionegro' === $b[0]['nombre'] && 'Antioquia' === $b[0]['zona'], 'buscar ciudad' );

/* Avisos al celular */
delete_option( 'rau_cond_avisos' );
$sub = array( 'endpoint' => 'https://fcm.googleapis.com/fcm/send/prueba-' . wp_generate_password( 8, false ), 'keys' => array( 'p256dh' => str_repeat( 'B', 87 ), 'auth' => str_repeat( 'a', 22 ) ) );
$post = function( $cuerpo ) { delete_transient( 'rau_cond_av_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) ) ); $q = new WP_REST_Request( 'POST', '/rau/v1/condiciones/avisos' ); $q->set_header( 'Content-Type', 'application/json' ); $q->set_body( wp_json_encode( $cuerpo ) ); return rest_do_request( $q ); };
ok( 400 === $post( array( 'sub' => array( 'endpoint' => 'http://10.0.0.1/x', 'keys' => $sub['keys'] ), 'lat' => 6, 'lon' => -75, 'temas' => array( 'tormenta' ) ) )->get_status(), 'rechaza un endpoint que no es de un servicio push' );
ok( 400 === $post( array( 'sub' => $sub, 'lat' => 6, 'lon' => -75, 'temas' => array( 'inventado' ) ) )->get_status(), 'rechaza temas que no existen' );
$r = $post( array( 'sub' => $sub, 'lat' => 6.2442, 'lon' => -75.5812, 'temas' => array( 'tormenta', 'viento', 'sol' ) ) );
$g = get_option( 'rau_cond_avisos' ); $a = reset( $g );
ok( 200 === $r->get_status() && 6.2 === $a['lat'] && -75.6 === $a['lon'], 'guarda el aviso con el punto redondeado' );
$limpiar(); $flare = array( 'current_class' => 'B1', 'max_class' => 'B2', 'max_time' => gmdate( 'Y-m-d\TH:i:s\Z', time() - 9 * HOUR_IN_SECONDS ) ); $kp = 1;
$enviados = array(); $falso = function( $s, $t, $cuerpo ) use ( &$enviados ) { $enviados[] = $t; return true; };
rau_cond_avisos_revisar( $falso );
ok( 2 === count( $enviados ) && preg_grep( '/Tormenta/', $enviados ) && preg_grep( '/Viento/', $enviados ), 'manda tormenta y viento (el sol está tranquilo)' );
rau_cond_avisos_revisar( $falso );
ok( 2 === count( $enviados ), 'no repite el mismo aviso antes de su espera mínima' );
$q = new WP_REST_Request( 'DELETE', '/rau/v1/condiciones/avisos' ); $q->set_header( 'Content-Type', 'application/json' ); $q->set_body( wp_json_encode( array( 'endpoint' => $sub['endpoint'] ) ) );
rest_do_request( $q ); ok( ! get_option( 'rau_cond_avisos' ), 'quitar avisos borra la suscripción' );

/* 6 m en el cluster */
$s6 = rau_cond_seis( array( array( 'freq' => '50.313', 'dx' => 'PY2XX', 'sp' => 'HK4A' ), array( 'freq' => '14.074', 'dx' => 'K1A', 'sp' => 'HK3' ), array( 'freq' => '50.125', 'dx' => 'LU1X', 'sp' => 'HK1' ) ) );
ok( 2 === $s6['n'] && false !== strpos( $s6['ejemplos'][0], 'PY2XX' ), '6 m: solo los spots entre 50 y 54 MHz' );

$limpiar(); delete_option( 'rau_cond_avisos' );
echo "\n$n comprobaciones, $fallos fallas\n"; exit( $fallos ? 1 : 0 );
