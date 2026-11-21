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

/* Pronóstico de 3 días de NOAA (formato real) */
$GLOBALS['pron3_txt'] = ":Product: 3-Day Forecast\n:Issued: 2026 Nov 20 1230 UTC\n#\nA. NOAA Geomagnetic Activity Observation and Forecast\n\nNOAA Kp index breakdown Nov 20-Nov 22 2026\n\n             Nov 20       Nov 21       Nov 22\n00-03UT       0.67         2.33         2.33\n03-06UT       2.00         2.67         2.00\n06-09UT       1.33         2.00         2.00\n09-12UT       0.67         2.00         2.67\n12-15UT       1.67         1.33         5.67\n15-18UT       2.67         2.00         2.67\n18-21UT       2.67         2.00         2.67\n21-00UT       3.00         2.33         3.00\n\nRationale: G1 (Minor) storm levels are likely on 22 Nov due to CME arrival.\n\nB. NOAA Solar Radiation\n              Nov 20  Nov 21  Nov 22\nS1 or greater    1%      1%      5%\n\nC. NOAA Radio Blackout\n              Nov 20        Nov 21        Nov 22\nR1-R2           10%           15%           20%\nR3 or greater    1%            1%            5%\n";
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
	if ( false !== strpos( $url, 'noaa-planetary-k-index.json' ) ) return $r( array( array( 'time_tag' => gmdate( 'Y-m-d H:00:00', time() - 3 * 3600 ), 'Kp' => 1.67 ) ) );
	if ( false !== strpos( $url, 'k-index-forecast' ) ) return $r( array( array( 'time_tag' => gmdate( 'Y-m-d\TH:00:00', time() + 3 * HOUR_IN_SECONDS ), 'kp' => 3.67, 'observed' => 'predicted' ) ) );
	if ( false !== strpos( $url, 'xray-flares-latest' ) ) return $r( array( $flare ) );
	if ( false !== strpos( $url, 'noaa-scales' ) ) return $r( array( '0' => array( 'R' => array( 'Scale' => (string) $escalaR ), 'S' => array( 'Scale' => '0' ), 'G' => array( 'Scale' => '0' ) ), '1' => array( 'R' => array( 'MinorProb' => '10', 'MajorProb' => '5' ) ) ) );
	if ( false !== strpos( $url, 'rtsw_wind' ) ) return $r( array( array( 'active' => true, 'proton_speed' => 420.4, 'proton_density' => 3.2 ) ) );
	if ( false !== strpos( $url, 'rtsw_mag' ) ) return $r( array( array( 'active' => true, 'bz_gsm' => -4.2, 'bt' => 6 ) ) );
	if ( false !== strpos( $url, 'alerts.json' ) ) return $r( array( array( 'issue_datetime' => gmdate( 'Y-m-d H:i:s', time() - 3600 ), 'message' => "Space Weather Message Code: ALTK04\r\nALERT: Geomagnetic K-index of 4\nmore" ) ) );
	if ( false !== strpos( $url, 'drap_global' ) ) return $r( $drap );
	if ( false !== strpos( $url, 'goes/primary/xrays-' ) || false !== strpos( $url, 'xray-flares-7-day' ) ) return $r( array() );
	if ( false !== strpos( $url, 'hamqsl.com' ) ) return $r( '<?xml version="1.0"?><solar><solardata><solarflux>140</solarflux><sunspots>90</sunspots><aindex>8</aindex><kindex>1</kindex><xray>B2</xray><calculatedconditions><band name="80m-40m" time="day">Good</band><band name="80m-40m" time="night">Good</band><band name="12m-10m" time="day">Poor</band><band name="12m-10m" time="night">Poor</band></calculatedconditions></solardata></solar>' );
	if ( false !== strpos( $url, 'prop.kc2g.com' ) ) return $r( array( array( 'fof2' => 8.6, 'mufd' => 27.0, 'cs' => 75, 'time' => gmdate( 'Y-m-d\TH:i:s', time() - 1200 ), 'station' => array( 'name' => 'Boa Vista, Brazil', 'code' => 'BVJ03', 'latitude' => '2.8', 'longitude' => '299.3' ) ) ) );
	if ( false !== strpos( $url, 'earthquake.usgs.gov' ) ) return $r( array( 'features' => array(
		array( 'properties' => array( 'mag' => 4.1, 'place' => 'Antioquia', 'time' => ( time() - 7200 ) * 1000, 'url' => 'u' ), 'geometry' => array( 'coordinates' => array( -75.9, 6.9, 30 ) ) ),
		array( 'properties' => array( 'mag' => 6.0, 'place' => 'Japón', 'time' => ( time() - 3600 ) * 1000, 'url' => 'u' ), 'geometry' => array( 'coordinates' => array( 140, 35, 10 ) ) ) ) ) );
	if ( false !== strpos( $url, '3-day-forecast' ) ) return $r( $GLOBALS['pron3_txt'] );
	if ( false !== strpos( $url, '27-day-outlook' ) ) return $r( "#  UTC      Radio Flux   Planetary   Largest\n2026 Nov 16     119          15          4\n2026 Nov 17     113          20          5\n" );
	if ( false !== strpos( $url, 'solar_regions' ) ) return $r( array( array( 'observed_date' => '2026-11-19', 'region' => 1, 'number_spots' => 3, 'location' => 'N1W1', 'm_flare_probability' => 99 ), array( 'observed_date' => '2026-11-20', 'region' => 4579, 'location' => 'N16W09', 'mag_class' => 'BGD', 'number_spots' => 12, 'area' => 300, 'c_flare_probability' => 60, 'm_flare_probability' => 30, 'x_flare_probability' => 10 ), array( 'observed_date' => '2026-11-20', 'region' => 4581, 'location' => 'S07E25', 'mag_class' => 'A', 'number_spots' => 2, 'c_flare_probability' => 5, 'm_flare_probability' => 1, 'x_flare_probability' => 1 ) ) );
	if ( false !== strpos( $url, 'integral-protons' ) ) return $r( array( array( 'time_tag' => gmdate( 'Y-m-d\TH:i:s\Z', time() - 600 ), 'energy' => '>=10 MeV', 'flux' => 0.4 ), array( 'time_tag' => gmdate( 'Y-m-d\TH:i:s\Z', time() - 600 ), 'energy' => '>=100 MeV', 'flux' => 0.01 ) ) );
	if ( false !== strpos( $url, 'kyoto-dst' ) ) return $r( array( array( 'time_tag' => gmdate( 'Y-m-d\TH:00:00', time() - 3600 ), 'dst' => -35 ) ) );
	if ( false !== strpos( $url, 'observed-solar-cycle' ) ) return $r( array( array( 'time-tag' => '2019-11', 'ssn' => 1 ), array( 'time-tag' => '2024-08', 'ssn' => 215.5, 'smoothed_ssn' => 160 ) ) );
	if ( false !== strpos( $url, 'predicted-solar-cycle' ) ) return $r( array( array( 'time-tag' => '2026-12', 'predicted_ssn' => 80, 'low_ssn' => 70, 'high_ssn' => 90 ), array( 'time-tag' => '2029-06', 'predicted_ssn' => 20 ) ) );
	if ( false !== strpos( $url, 'api.nasa.gov/DONKI' ) ) return $r( array( array( 'activityID' => '2026-11-19T08:09:00-CME-001', 'startTime' => gmdate( 'Y-m-d\\TH:i\\Z', time() - 6 * 3600 ), 'link' => 'https://ccmc/1', 'note' => 'Halo CME',
		'cmeAnalyses' => array( array( 'isMostAccurate' => true, 'speed' => 1200, 'halfAngle' => 45, 'type' => 'O', 'enlilList' => array( array( 'isEarthGB' => true, 'estimatedShockArrivalTime' => gmdate( 'Y-m-d\TH:i\Z', time() + 36 * 3600 ), 'kp_90' => 5, 'kp_180' => 7 ) ) ) ) ),
		array( 'activityID' => '2026-11-20T12:15:00-CME-001', 'startTime' => '2026-11-20T12:15Z', 'cmeAnalyses' => array( array( 'isMostAccurate' => true, 'speed' => 400, 'halfAngle' => 10, 'type' => 'S' ) ) ) ) );
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
ok( 0 === $c['semaforo']['sol']['nivel'] && 1 === $c['semaforo']['geomag']['nivel'] && ! empty( $c['semaforo']['geomag']['pronostico'] ), 'sol tranquilo; campo magnético en atención por lo que NOAA pronostica' );
ok( 'Boa Vista, Brazil' === $c['iono']['iono']['nombre'] && '40 m' === $c['iono']['nvis']['banda'], 'ionosonda más cercana al punto y NVIS en 40 m' );
ok( 1 === count( $c['sismos'] ) && 'Antioquia' === $c['sismos'][0]['lugar'], 'solo los sismos a menos de 500 km' );
ok( 3.7 === $c['espacio']['kp_pron'] && 420 == $c['espacio']['viento']['vel'] && 'ALERT' === $c['espacio']['alertas'][0]['tipo'], 'clima espacial: pronóstico de Kp, viento solar y avisos de NOAA' );
ok( 140 === $c['espacio']['sfi'] && 2 === count( $c['espacio']['bandas'] ), 'bandas y flujo solar de HamQSL' );
ok( ! isset( $c['aire'] ), 'sin calidad del aire: no sirve para operar radio (19.67)' );
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

/* ── 19.63.0: centro de mando ── */
// Tormenta en la hora actual: «en curso», no «prevista hacia las …»
$limpiar(); $om['hourly']['weather_code'] = $col( 3 ); $om['hourly']['weather_code'][2] = 95; $om['hourly']['weather_code'][3] = 95; // a partir del minuto 40 la hora actual ya casi termina: cuenta la siguiente
$c4 = rau_condiciones( 5.07, -75.51 );
ok( ! empty( $c4['semaforo']['tormenta']['en_curso'] ) && false !== strpos( $c4['semaforo']['tormenta']['texto'], 'en curso' ), 'tormenta en la hora actual: «en curso», no una hora que ya pasó' );
ok( ! empty( $c4['eventos'] ) && preg_grep( '/Tormenta eléctrica (prevista|en curso)/', wp_list_pluck( $c4['eventos'], 'texto' ) ), 'registro de eventos con la tormenta (en curso si ya es la hora)' );
ok( isset( $c4['tend']['kp'], $c4['salud'], $c4['drap_mapa'] ), 'la respuesta trae tendencias, salud de fuentes y capa D-RAP' );
ok( 'Advertencia: se espera un índice K geomagnético de 4' === rau_cond_traducir( 'WARNING: Geomagnetic K-index of 4 expected' ) && false !== strpos( rau_cond_traducir( 'ALERT: X-ray Flux exceeded M5' ), 'llamarada' ), 'avisos de NOAA en español' );
// Salud: Open-Meteo y NOAA respondieron
$salud = array(); foreach ( rau_cond_salud() as $x ) $salud[ $x['fuente'] ] = $x['estado'];
ok( 'bien' === ( $salud['Open-Meteo'] ?? '' ) && 'bien' === ( $salud['NOAA SWPC'] ?? '' ), 'salud de las fuentes: Open-Meteo y NOAA al día' );
// D-RAP en el mapa: solo celdas con pérdida, de las Américas
$dm = rau_cond_drap_mapa(); ok( 2 === count( $dm ) && 12.5 === $dm[0][2] && -74 === $dm[0][1], 'D-RAP para el mapa: las 2 celdas con pérdida' );
// Historial de ionosondas y matriz por persistencia
delete_option( 'rau_cond_ionoh' ); $base = (int) floor( time() / 3600 ) * 3600; $hist = array();
for ( $k = 0; $k < 26; $k++ ) $hist[] = array( $base - DAY_IN_SECONDS + $k * 3600, 6 + ( $k % 6 ), 20 + $k % 6 );
update_option( 'rau_cond_ionoh', array( 'BVJ03' => $hist ), false );
$m = rau_cond_matriz( 'BVJ03', -18000 );
ok( 24 === count( $m['horas'] ) && $m['completa'] && 6.0 == $m['horas'][0]['fof2'] && 20.0 == $m['horas'][0]['mufd'], 'matriz de 24 h con lo medido ayer a la misma hora' );
ok( (int) gmdate( 'G', $base - 18000 ) === $m['horas'][0]['h'], 'las horas de la matriz en la hora local del punto' );
delete_option( 'rau_cond_ionoh' );
ok( 1 === rau_cond_iono_grabar() && 1 === count( get_option( 'rau_cond_ionoh' )['BVJ03'] ?? array() ), 'graba la ionosonda de la región (no hay historial público)' );
ok( 0 === rau_cond_iono_grabar(), 'no duplica la misma medición' );
// Sitios RAU
delete_transient( 'rau_cond_sitios' ); $si = rau_cond_sitios();
ok( is_array( $si ) && ( ! $si || isset( $si[0]['tormenta'], $si[0]['viento'], $si[0]['temp'] ) ), 'sitios RAU: tiempo, tormenta y viento por repetidora' );
// Avisos: varios puntos, umbral de viento, prueba
delete_option( 'rau_cond_avisos' );
$r1 = $post( array( 'sub' => $sub, 'lat' => 6.24, 'lon' => -75.58, 'temas' => array( 'viento' ), 'nombre' => 'Casa', 'umbral' => 40 ) );
$r2 = $post( array( 'sub' => $sub, 'lat' => 4.71, 'lon' => -74.07, 'temas' => array( 'viento' ), 'nombre' => 'Repetidora', 'umbral' => 70 ) );
$mios = rest_do_request( ( function() use ( $sub ) { $q = new WP_REST_Request( 'POST', '/rau/v1/condiciones/avisos/mios' ); $q->set_header( 'Content-Type', 'application/json' ); $q->set_body( wp_json_encode( array( 'endpoint' => $sub['endpoint'] ) ) ); return $q; } )() )->get_data();
ok( 200 === $r1->get_status() && 200 === $r2->get_status() && 2 === count( $mios ), 'dos puntos con avisos en el mismo celular' );
ok( 40 === $r1->get_data()['umbral'] && in_array( 'Casa', wp_list_pluck( $mios, 'nombre' ), true ), 'cada punto con su nombre y su umbral' );
for ( $i = 0; $i < 3; $i++ ) $post( array( 'sub' => $sub, 'lat' => 1 + $i, 'lon' => -70, 'temas' => array( 'viento' ) ) );
ok( 400 === $post( array( 'sub' => $sub, 'lat' => 9, 'lon' => -70, 'temas' => array( 'viento' ) ) )->get_status(), 'máximo 5 puntos por celular' );
$limpiar(); $om['hourly']['weather_code'] = $col( 3 ); $om['hourly']['wind_gusts_10m'] = $col( 45 );
$enviados = array(); rau_cond_avisos_revisar( $falso );
ok( 1 === count( preg_grep( '/Casa/', $enviados ) ) && ! preg_grep( '/Repetidora/', $enviados ), 'ráfagas de 45 km/h: avisa al punto con umbral 40, no al de 70' );
$q = new WP_REST_Request( 'DELETE', '/rau/v1/condiciones/avisos' ); $q->set_header( 'Content-Type', 'application/json' ); $q->set_body( wp_json_encode( array( 'endpoint' => $sub['endpoint'], 'id' => $r1->get_data()['id'] ) ) );
rest_do_request( $q ); ok( 4 === count( get_option( 'rau_cond_avisos' ) ), 'quitar un punto deja los demás' );
$q = new WP_REST_Request( 'POST', '/rau/v1/condiciones/avisos/prueba' ); $q->set_header( 'Content-Type', 'application/json' ); $q->set_body( wp_json_encode( array( 'endpoint' => 'https://fcm.googleapis.com/fcm/send/otro' ) ) );
ok( 404 === rest_do_request( $q )->get_status(), 'la prueba solo va a un celular que tenga avisos' );

/* ── 19.64.0: tormentas solares y arreglos ── */
$p3 = rau_cond_pron3_leer( $GLOBALS['pron3_txt'] );
ok( 3 === count( $p3['dias'] ) && 24 === count( $p3['kp'] ) && 5.67 === $p3['kp_max'] && '2026-11-22' === gmdate( 'Y-m-d', $p3['dias'][2] ), 'pronóstico de NOAA: 3 días × 8 bloques de Kp' );
ok( gmmktime( 12, 0, 0, 11, 22, 2026 ) === $p3['kp'][20][0] && 5.67 === $p3['kp'][20][1], 'cada Kp con su hora UTC' );
ok( array( 10, 15, 20 ) === $p3['r12'] && array( 1, 1, 5 ) === $p3['s1'] && false !== strpos( $p3['razon_g'], 'CME arrival' ), 'probabilidades de R y S y la explicación de NOAA' );
$limpiar(); delete_transient( 'rau_cond_cmes' ); delete_option( 'rau_cond_cmes_ult' );
$so = rau_cond_solar();
ok( 2 === count( $so['o27'] ) && 5 === $so['o27'][1][3], 'pronóstico de 27 días' );
ok( 2 === count( $so['regiones'] ) && 4579 === $so['regiones'][0]['n'] && 30 === $so['regiones'][0]['pm'], 'regiones activas del último día, la más peligrosa primero' );
ok( 1 === count( $so['protones'] ) && -35 === $so['dst'][0][1] && 1 === count( $so['ciclo']['obs'] ) && 1 === count( $so['ciclo']['pred'] ), 'protones, Dst y ciclo 25 (desde 2019-12, pronóstico hasta 2028)' );
$ct = array_values( array_filter( $so['cmes'], function( $x ) { return $x['tierra']; } ) );
ok( 2 === count( $so['cmes'] ) && 1 === count( $ct ) && $ct[0]['llegada'] > time() && '5–7' === $ct[0]['kp'] && 1200 === $ct[0]['vel'], 'eyecciones de NASA: la dirigida a la Tierra con su llegada y Kp esperado' );
ok( 1 === $so['tormenta']['nivel'] && false !== strpos( $so['tormenta']['viene'], 'Eyección solar dirigida a la Tierra' ), 'nivel de tormenta solar con la llegada de la eyección' );
$c6 = rau_condiciones( 3.4, -76.5 );
ok( 1 === $c6['semaforo']['geomag']['nivel'] && ! empty( $c6['semaforo']['geomag']['pronostico'] ), 'el semáforo avisa lo que viene aunque el campo esté tranquilo ahora' );
ok( preg_grep( '/Llegada estimada de la eyección solar/', wp_list_pluck( $c6['eventos'], 'texto' ) ) && preg_grep( '/Eyección de masa coronal a 1\.200 km\/s · dirigida a la Tierra/', wp_list_pluck( $c6['eventos'], 'texto' ) ), 'registro: la eyección y su llegada' );
ok( isset( $c6['espacio']['kp_oficial'] ), 'Kp oficial de 3 horas junto al estimado' );
// La ionosonda se graba al consultar, sin esperar la tarea programada
delete_option( 'rau_cond_ionoh' ); $limpiar(); rau_condiciones( 10.9, -74.8 );
ok( ! empty( get_option( 'rau_cond_ionoh' )['BVJ03'] ), 'la ionosonda se registra al consultar' );
// Avisos: una sola vez por eyección
delete_option( 'rau_cond_avisos' ); $post( array( 'sub' => $sub, 'lat' => 6.24, 'lon' => -75.58, 'temas' => array( 'geomag' ) ) );
$limpiar(); $enviados = array(); rau_cond_avisos_revisar( $falso ); rau_cond_avisos_revisar( $falso );
ok( 1 === count( preg_grep( '/Eyección solar hacia la Tierra/', $enviados ) ), 'aviso de eyección hacia la Tierra, una sola vez' );
delete_transient( 'rau_cond_cmes' ); delete_transient( 'rau_cond_solar' );

/* ── 19.65.0: radio y local ── */
$t_hss = time() - 2 * HOUR_IN_SECONDS;
add_filter( 'pre_http_request', function( $pre, $args, $url ) use ( $t_hss ) {
	$r = function( $b, $code = 200 ) { return array( 'headers' => array(), 'body' => is_string( $b ) ? $b : wp_json_encode( $b ), 'response' => array( 'code' => $code, 'message' => '' ), 'cookies' => array() ); };
	$linea = function( $v, $lat ) { return array( 'type' => 'Feature', 'properties' => array( 'level-value' => $v, 'stroke' => '#00ff00' ), 'geometry' => array( 'type' => 'LineString', 'coordinates' => array( array( -120, $lat ), array( -100, $lat ), array( -80, $lat ), array( -60, $lat ), array( -40, $lat ), array( -20, $lat ), array( 0, $lat ), array( 20, $lat ) ) ) ); };
	if ( false !== strpos( $url, 'mufd-normal-now.geojson' ) ) return $r( array( 'type' => 'FeatureCollection', 'features' => array( $linea( 20, 0 ), $linea( 10, 40 ) ) ) );
	if ( false !== strpos( $url, 'db1.wspr.live' ) ) { $sale = false !== strpos( urldecode( $url ), 'substring(tx_loc' );
		return $r( array( 'data' => array( array( 'time' => '2026-11-21 10:00:00', 'band' => 7, 'tx_sign' => $sale ? 'HK4X' : 'EA4Y', 'tx_loc' => $sale ? 'FJ26' : 'IN80', 'tx_lat' => $sale ? 6.2 : 40.4, 'tx_lon' => $sale ? -75.6 : -3.7, 'rx_sign' => $sale ? 'EA4Y' : 'HK4X', 'rx_loc' => $sale ? 'IN80' : 'FJ26', 'rx_lat' => $sale ? 40.4 : 6.2, 'rx_lon' => $sale ? -3.7 : -75.6, 'distance' => 8032, 'snr' => -18 ),
			array( 'time' => '2026-11-21 10:02:00', 'band' => 14, 'tx_sign' => 'HK4X', 'tx_loc' => 'FJ26', 'tx_lat' => 6.2, 'tx_lon' => -75.6, 'rx_sign' => 'K1Z', 'rx_loc' => 'FN42', 'rx_lat' => 42, 'rx_lon' => -71, 'distance' => 4100, 'snr' => -10 ) ) ) ); }
	if ( false !== strpos( $url, 'api.pota.app/spot/activator' ) ) return $r( array( array( 'activator' => 'HK4P', 'frequency' => '14280', 'mode' => 'SSB', 'reference' => 'CO-0001', 'name' => 'Parque Arví', 'locationDesc' => 'CO-ANT', 'latitude' => 6.28, 'longitude' => -75.5, 'spotTime' => '2026-11-21T10:00:00' ),
		array( 'activator' => 'MAL', 'frequency' => '7000', 'reference' => 'X', 'invalid' => 1, 'latitude' => 1, 'longitude' => 1 ), array( 'activator' => 'SINPOS', 'frequency' => '7000', 'reference' => 'Y' ) ) );
	if ( false !== strpos( $url, 'geodataJson/2/niveles' ) ) { $f = function( $n, $act, $col ) { return array( 'type' => 'Feature', 'geometry' => array( 'coordinates' => array( -75.57, 6.25 ) ), 'properties' => array( 'nombreEstacion' => $n, 'municipio' => 'Medellín', 'nivelActual' => $act, 'nivelSeguro' => 50, 'nivelPrecaucion' => 100, 'inundacionMenor' => 150, 'inundacionMayor' => 200, 'color' => $col, 'fechaUltimoDato' => '2026-11-21 10:00' ) ); };
		return $r( array( 'features' => array( $f( 'Q. La Normal', 40, '#79c454' ), $f( 'Q. Crecida', 160, '#F29436' ), $f( 'Q. Sensor malo', 900, '#fc3a3a' ), $f( 'Q. Sin dato', 0, '#0e0e0e' ) ) ) ); }
	if ( false !== strpos( $url, 'geodataJson/3/pluvios_v2' ) ) return $r( array( 'features' => array( array( 'geometry' => array( 'coordinates' => array( -75.6, 6.2 ) ), 'properties' => array( 'nombre' => 'P1', 'ubicacion' => 'Itagüí', 'acumulado_15min' => 2.5, 'flag_precipitacion' => 'si' ) ),
		array( 'geometry' => array( 'coordinates' => array( -75.5, 6.3 ) ), 'properties' => array( 'nombre' => 'P2', 'ubicacion' => 'Bello', 'acumulado_15min' => 0, 'flag_precipitacion' => 'no' ) ) ) ) );
	if ( false !== strpos( $url, 'geodataJson/3/tempVient' ) ) return $r( array( 'features' => array( array( 'geometry' => array( 'coordinates' => array( -75.6, 6.2 ) ), 'properties' => array( 'NombreEstacion' => 'E1', 'Temperatura' => 21.3, 'Magnitud_viento' => 2, 'Direccion_viento' => 90, 'Humedad_relativa' => 80 ) ) ) ) );
	if ( false !== strpos( $url, 'api.nasa.gov/DONKI/HSS' ) ) return $r( array( array( 'hssID' => 'HSS-1', 'eventTime' => gmdate( 'Y-m-d\TH:i\Z', $t_hss ), 'link' => 'https://ccmc/hss' ) ) );
	return $pre;
}, 20, 3 );
$limpiar(); foreach ( array( 'rau_cond_mufgeo', 'rau_cond_wspr', 'rau_cond_hss', 'rau_cond_pota' ) as $tr ) delete_transient( $tr ); delete_option( 'rau_cond_hss_ult' );
$geo = rau_cond_muf_geo();
ok( 2 === count( $geo ) && 20.0 === $geo[0]['v'] && '#00ff00' === $geo[0]['c'], 'MUF de KC2G: isolíneas con su valor y color' );
ok( 20.0 === rau_cond_muf_en( 0, -75, $geo ) && 10.0 === rau_cond_muf_en( 40, -75, $geo ), 'MUF sobre la isolínea: su valor' );
$mm = rau_cond_muf_en( 20, -75, $geo ); ok( $mm > 14 && $mm < 16, 'MUF entre dos isolíneas: interpolada (' . $mm . ')' );
ok( abs( rau_cond_rumbo( array( 6.2, -75.6 ), array( 40.4, -3.7 ) ) - 49 ) < 2 && abs( rau_cond_rumbo( array( 0, 0 ), array( 0, -10 ) ) - 270 ) < .5, 'rumbo de antena: Madrid a 49°, oeste a 270°' );
$en = rau_cond_enlace( array( 6.2, -75.6 ), array( 40.4, -3.7 ), $geo );
ok( abs( $en['km'] - 8030 ) < 60 && 2 === count( $en['puntos'] ) && 3 === $en['saltos'] && abs( $en['lp'] - fmod( $en['sp'] + 180, 360 ) ) < 1, 'enlace a Madrid: 8.030 km, 3 saltos, 2 puntos de control, ruta larga opuesta' );
$est = array(); foreach ( $en['bandas'] as $bb ) $est[ $bb['b'] ] = $bb['e'];
ok( 10 === count( $est ) && 'cerrada' === $est['10 m'] && $en['muf'] > 0 && $en['fot'] < $en['muf'], 'bandas del enlace: 10 m cerrada por encima de la MUF' );
ok( null === rau_cond_enlace( array( 6.2, -75.6 ), array( 6.2, -75.6 ), $geo ), 'sin enlace al mismo punto' );
$w = rau_cond_wspr();
ok( $w['ok'] && 2 === count( $w['sale'] ) && 2 === count( $w['entra'] ) && 1 === $w['bandas']['40 m']['sale'] && 1 === $w['bandas']['40 m']['entra'] && 8032 === $w['bandas']['40 m']['max'], 'WSPR: quién oye a la región y a quién oye, por banda' );
$po = rau_cond_pota();
ok( 1 === count( $po ) && 'HK4P' === $po[0]['act'] && 'Parque Arví' === $po[0]['parque'], 'POTA: solo activaciones válidas y con posición' );
$hs = rau_cond_hss();
ok( 1 === count( $hs ) && 'HSS-1' === $hs[0]['id'] && abs( $hs[0]['regreso'] - $t_hss - 27.27 * DAY_IN_SECONDS ) < 60, 'corriente rápida y su posible regreso a los 27 días' );
delete_transient( 'rau_cond_solar' ); $so2 = rau_cond_solar();
ok( ! empty( $so2['hss'] ), 'el panel solar trae las corrientes rápidas' );
// Sismos: solo los que pudieron sentirse
ok( rau_cond_sismo_fuerte( array( 'mag' => 4.6, 'km' => 150, 't' => time() - 3600 ) ) && rau_cond_sismo_fuerte( array( 'mag' => 6.2, 'km' => 450, 't' => time() - 3600 ) ), 'sismo fuerte: M4,6 a 150 km y M6,2 a 450 km' );
ok( ! rau_cond_sismo_fuerte( array( 'mag' => 4.1, 'km' => 80, 't' => time() - 3600 ) ) && ! rau_cond_sismo_fuerte( array( 'mag' => 5.5, 'km' => 300, 't' => time() - 3600 ) ) && ! rau_cond_sismo_fuerte( array( 'mag' => 6.5, 'km' => 50, 't' => time() - 2 * DAY_IN_SECONDS ) ), 'no es fuerte: M4,1 a 80 km, M5,5 a 300 km, ni uno de hace 2 días' );
$c7 = rau_condiciones( 6.24, -75.58 );
ok( ! preg_grep( '/^Sismo/', wp_list_pluck( $c7['eventos'], 'texto' ) ) && 1 === count( $c7['sismos'] ), 'el registro no se llena de sismos pequeños (siguen en la capa del mapa)' );
// Archivo y semana
delete_option( 'rau_cond_archivo' ); rau_cond_archivar(); rau_cond_archivar(); $ar = get_option( 'rau_cond_archivo' ); $hoy = rau_cond_dia( time() );
ok( isset( $ar[ $hoy ] ) && count( $ar[ $hoy ]['ev'] ) === count( array_unique( array_map( 'serialize', $ar[ $hoy ]['ev'] ) ) ) && preg_grep( '/Corriente rápida/', wp_list_pluck( $ar[ rau_cond_dia( $t_hss ) ]['ev'], 'x' ) ), 'archivo diario: sin duplicados y con la corriente rápida' );
ok( '3' . sprintf( '%05.1f', 2.4 ) === rau_cond_clase_num( 'M2.4' ) && strcmp( rau_cond_clase_num( 'X1.0' ), rau_cond_clase_num( 'M9.9' ) ) > 0, 'llamaradas comparables por clase (X1 > M9,9)' );
$se = rau_cond_semana( 6.24, -75.58 );
ok( 3 === count( $se['dias'] ) && 1 === $se['tormentas'] && false !== strpos( $se['texto'], '1 día con tormenta' ) && false !== strpos( $se['texto'], 'ráfagas de hasta 65 km/h' ) && false === strpos( $se['texto'], 'mm de lluvia' ), 'resumen de la semana del punto' );
$q = new WP_REST_Request( 'GET', '/rau/v1/condiciones/enlace' ); $q->set_query_params( array( 'lat' => 6.2, 'lon' => -75.6, 'dlat' => 40.4, 'dlon' => -3.7 ) );
$re = rest_do_request( $q ); ok( 200 === $re->get_status() && abs( $re->get_data()['km'] - 8030 ) < 60, 'REST: /condiciones/enlace' );
foreach ( array( 'muf', 'wspr', 'pota', 'archivo' ) as $ru ) { $re = rest_do_request( new WP_REST_Request( 'GET', '/rau/v1/condiciones/' . $ru ) ); ok( 200 === $re->get_status(), 'REST: /condiciones/' . $ru ); }
// Tarjeta para compartir
$tj = rau_cond_tarjeta_archivo( 6.2, -75.6 );
ok( ! $tj || ( file_exists( $tj[0] ) && "\x89PNG" === substr( file_get_contents( $tj[0] ), 0, 4 ) && array( 1200, 630 ) === array_slice( getimagesize( $tj[0] ), 0, 2 ) ), 'tarjeta PNG de 1200×630 con el semáforo del punto' );
// Avisos nuevos: llamarada desde la clase elegida, corriente rápida, sismo fuerte
delete_option( 'rau_cond_avisos' ); $limpiar(); delete_transient( 'rau_cond_tend' );
$post( array( 'sub' => $sub, 'lat' => 6.24, 'lon' => -75.58, 'temas' => array( 'llamarada', 'hss', 'sismo' ), 'clase' => 'X' ) );
$av = array_values( get_option( 'rau_cond_avisos' ) );
ok( 'X' === $av[0]['clase'] && array( 'llamarada', 'hss', 'sismo' ) === $av[0]['temas'], 'aviso guarda la clase mínima de llamarada y los temas nuevos' );
$enviados = array(); rau_cond_avisos_revisar( $falso ); rau_cond_avisos_revisar( $falso );
ok( 1 === count( preg_grep( '/Corriente rápida/', $enviados ) ) && ! preg_grep( '/Llamarada/', $enviados ) && ! preg_grep( '/Sismo/', $enviados ), 'corriente rápida avisada una vez; sin llamarada X ni sismo fuerte no se avisa' );

/* ── 19.66.0 ── */
// 6 m abierta es oportunidad, no alerta
$s66 = rau_cond_semaforo( null, rau_cond_espacio(), null );
ok( ! isset( $s66['seis'] ) || ! empty( $s66['seis']['op'] ), 'el semáforo de 6 m va marcado como oportunidad' );
ok( 404 === rest_do_request( new WP_REST_Request( 'GET', '/rau/v1/condiciones/siata' ) )->get_status(), 'SIATA ya no está en Condiciones (19.67)' );
// Bandas por región
$rg = rau_cond_regiones( 6.2, -75.6 );
ok( 8 === count( $rg ) && isset( $rg[0]['bandas']['40 m'] ) && 'Caribe' === $rg[0]['n'], 'bandas por región: 8 regiones con el estado de cada banda' );
// MUF en el punto aunque no haya ionosonda
$limpiar(); $c8 = rau_condiciones( 6.24, -75.58 ); ok( array_key_exists( 'muf_punto', (array) $c8['iono'] ), 'la respuesta trae la MUF del mapa de KC2G en el punto' );
// Archivo: se pone al día solo y abre en el último día con eventos
update_option( 'rau_cond_archivo_t', 0, false ); update_option( 'rau_cond_archivo', array( '2030-01-02' => array( 'ev' => array(), 'kp' => 1, 'sfi' => null, 'fl' => '' ) ), false );
$ra = rest_do_request( new WP_REST_Request( 'GET', '/rau/v1/condiciones/archivo' ) )->get_data();
ok( (int) get_option( 'rau_cond_archivo_t' ) > time() - 60 && $ra['dia'] !== '2030-01-02' && ! empty( $ra['eventos'] ), 'archivo: se pone al día al consultarlo y abre en el último día con eventos' );

$limpiar(); delete_option( 'rau_cond_avisos' );
echo "\n$n comprobaciones, $fallos fallas\n"; exit( $fallos ? 1 : 0 );
