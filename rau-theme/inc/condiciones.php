<?php
/**
 * Condiciones (19.62.0): todo lo que afecta salir al aire, para un punto.
 *
 * GET  /wp-json/rau/v1/condiciones?lat=&lon=     clima, aire, clima espacial, absorción D-RAP, ionosonda,
 *                                                 sismos, repetidoras cercanas y el semáforo ya calculado.
 * GET  /wp-json/rau/v1/condiciones/buscar?q=      ciudades (Open-Meteo Geocoding), primero de Colombia.
 * POST /wp-json/rau/v1/condiciones/avisos         avisos al celular para un punto: tormenta, viento,
 * DELETE (mismo)                                   llamarada solar, tormenta geomagnética y 6 m abierta.
 *
 * Privacidad: el punto se redondea a 0,1° (unos 11 km) ANTES de consultar o guardar nada; con eso, además,
 * todos los que estén en la misma zona comparten la consulta guardada y no se gasta el cupo gratuito de
 * Open-Meteo (10.000 al día). Nada de esto queda asociado a una persona.
 *
 * Fuentes (gratuitas, sin clave): Open-Meteo (pronóstico, calidad del aire, geocodificación), NOAA SWPC
 * (Kp, rayos X, escalas R/S/G, viento solar, D-RAP), red GIRO vía prop.kc2g.com (ionosondas), USGS (sismos),
 * HamQSL (bandas). Cada fuente se guarda el tiempo que tiene sentido y, si falla, se usa la última buena
 * (marcada como «vieja») en vez de dejar la tarjeta vacía.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

const RAU_COND_PUNTO = array( 6.2442, -75.5812 ); // Medellín, si no se da otro

/* ── Utilidades ─────────────────────────────────────────────────────────── */
function rau_cond_redondear( $lat, $lon ) {
	$lat = max( -89.9, min( 89.9, (float) $lat ) ); $lon = (float) $lon;
	while ( $lon > 180 ) $lon -= 360; while ( $lon < -180 ) $lon += 360;
	return array( round( $lat, 1 ), round( $lon, 1 ) );
}
function rau_cond_km( $a, $b ) {
	if ( function_exists( 'rau_prop_km' ) ) return rau_prop_km( $a, $b );
	$r = M_PI / 180; $x = sin( ( $b[0] - $a[0] ) * $r / 2 ); $y = sin( ( $b[1] - $a[1] ) * $r / 2 );
	return (int) round( 12742 * asin( sqrt( $x * $x + cos( $a[0] * $r ) * cos( $b[0] * $r ) * $y * $y ) ) );
}
/** Descarga con caché y respaldo: devuelve array( datos, edad_en_segundos ) o array( null, null ) */
function rau_cond_traer( $url, $ttl, $formato = 'json', $timeout = 12 ) {
	$k = 'rau_cond_' . md5( $url );
	$c = get_transient( $k );
	if ( is_array( $c ) && array_key_exists( 'd', $c ) ) return array( $c['d'], time() - $c['t'] );
	$r = rau_remote_get( $url, array( 'timeout' => $timeout, 'user-agent' => 'HK4RAU/1.0 (+https://hk4rau.com)' ), 3 );
	$d = null;
	if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
		$cuerpo = wp_remote_retrieve_body( $r );
		$d = 'json' === $formato ? json_decode( $cuerpo, true ) : $cuerpo;
		if ( 'json' === $formato && ( ! is_array( $d ) || ! empty( $d['error'] ) ) ) $d = null;
	}
	if ( null !== $d ) {
		set_transient( $k, array( 'd' => $d, 't' => time() ), $ttl );
		update_option( 'rau_condr_' . md5( $url ), array( 'd' => $d, 't' => time() ), false ); // respaldo
		return array( $d, 0 );
	}
	$viejo = get_option( 'rau_condr_' . md5( $url ) );
	if ( is_array( $viejo ) && time() - $viejo['t'] < DAY_IN_SECONDS ) return array( $viejo['d'], time() - $viejo['t'] );
	return array( null, null );
}

/* Ciudades de Colombia para decir «cerca de …» sin preguntarle a nadie más dónde está la persona */
function rau_cond_ciudades() {
	return array(
		array( 'Medellín', 6.2442, -75.5812 ), array( 'Bogotá', 4.711, -74.0721 ), array( 'Cali', 3.4516, -76.532 ), array( 'Barranquilla', 10.9685, -74.7813 ),
		array( 'Cartagena', 10.391, -75.4794 ), array( 'Bucaramanga', 7.1193, -73.1227 ), array( 'Cúcuta', 7.8939, -72.5078 ), array( 'Pereira', 4.8133, -75.6961 ),
		array( 'Manizales', 5.0703, -75.5138 ), array( 'Armenia', 4.5339, -75.6811 ), array( 'Ibagué', 4.4389, -75.2322 ), array( 'Santa Marta', 11.2408, -74.199 ),
		array( 'Villavicencio', 4.142, -73.6266 ), array( 'Pasto', 1.2136, -77.2811 ), array( 'Montería', 8.7479, -75.8814 ), array( 'Neiva', 2.9273, -75.2819 ),
		array( 'Valledupar', 10.4631, -73.2532 ), array( 'Popayán', 2.4448, -76.6147 ), array( 'Sincelejo', 9.3047, -75.3978 ), array( 'Tunja', 5.5353, -73.3678 ),
		array( 'Riohacha', 11.5444, -72.9072 ), array( 'Quibdó', 5.6947, -76.6611 ), array( 'Florencia', 1.6144, -75.6062 ), array( 'Yopal', 5.3378, -72.3959 ),
		array( 'Arauca', 7.0847, -70.7591 ), array( 'Mocoa', 1.1522, -76.6464 ), array( 'San José del Guaviare', 2.5729, -72.6459 ), array( 'Leticia', -4.2153, -69.9406 ),
		array( 'Mitú', 1.2538, -70.2345 ), array( 'Puerto Carreño', 6.1890, -67.4859 ), array( 'Inírida', 3.8653, -67.9239 ), array( 'San Andrés', 12.5847, -81.7006 ),
		array( 'Rionegro', 6.1551, -75.3737 ), array( 'Apartadó', 7.8829, -76.6258 ), array( 'Turbo', 8.0926, -76.7284 ), array( 'Caucasia', 7.9863, -75.1933 ),
		array( 'Bello', 6.3373, -75.5579 ), array( 'Envigado', 6.1759, -75.5917 ), array( 'Palmira', 3.5394, -76.3036 ), array( 'Buenaventura', 3.8801, -77.0312 ),
		array( 'Tuluá', 4.0847, -76.1954 ), array( 'Girardot', 4.3017, -74.8037 ), array( 'Sogamoso', 5.7145, -72.9339 ), array( 'Duitama', 5.8245, -73.0341 ),
		array( 'Barrancabermeja', 7.0653, -73.8547 ), array( 'Ocaña', 8.2378, -73.3561 ), array( 'Magangué', 9.2414, -74.7537 ), array( 'Tumaco', 1.7986, -78.8156 ),
		array( 'Ipiales', 0.8303, -77.6447 ), array( 'Zipaquirá', 5.0221, -74.0048 ), array( 'Fusagasugá', 4.3365, -74.3638 ), array( 'Chiquinquirá', 5.6167, -73.8197 ),
	);
}
function rau_cond_cerca_de( $lat, $lon ) {
	$mejor = null;
	foreach ( rau_cond_ciudades() as $c ) { $km = rau_cond_km( array( $lat, $lon ), array( $c[1], $c[2] ) ); if ( ! $mejor || $km < $mejor['km'] ) $mejor = array( 'nombre' => $c[0], 'km' => $km ); }
	return $mejor && $mejor['km'] <= 60 ? $mejor : null;
}
function rau_cond_locator( $lat, $lon ) {
	$lon += 180; $lat += 90;
	$a = chr( 65 + (int) floor( $lon / 20 ) ) . chr( 65 + (int) floor( $lat / 10 ) );
	$b = (string) ( (int) floor( fmod( $lon, 20 ) / 2 ) ) . (string) ( (int) floor( fmod( $lat, 10 ) ) );
	$c = chr( 97 + (int) floor( fmod( $lon, 2 ) * 12 ) ) . chr( 97 + (int) floor( fmod( $lat, 1 ) * 24 ) );
	return $a . $b . $c;
}

/* ── Clima local (Open-Meteo) ─────────────────────────────────────────── */
function rau_cond_clima( $lat, $lon ) {
	$url = add_query_arg( array(
		'latitude' => $lat, 'longitude' => $lon, 'timezone' => 'auto', 'forecast_days' => 3, 'wind_speed_unit' => 'kmh',
		'current'  => 'temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,weather_code,cloud_cover,surface_pressure,wind_speed_10m,wind_direction_10m,wind_gusts_10m,visibility,cape',
		'hourly'   => 'temperature_2m,relative_humidity_2m,precipitation_probability,precipitation,weather_code,wind_speed_10m,wind_gusts_10m,cape,lifted_index,visibility,is_day',
		'daily'    => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,wind_gusts_10m_max,uv_index_max,sunrise,sunset',
	), 'https://api.open-meteo.com/v1/forecast' );
	list( $d, $edad ) = rau_cond_traer( $url, 10 * MINUTE_IN_SECONDS );
	if ( ! $d || empty( $d['hourly']['time'] ) ) return rau_cond_clima_met( $lat, $lon ); // respaldo: MET Norway
	// Próximas 48 horas desde la hora actual del lugar
	$ahora_local = gmdate( 'Y-m-d\TH:00', time() + (int) ( $d['utc_offset_seconds'] ?? 0 ) );
	$i0 = array_search( $ahora_local, $d['hourly']['time'], true ); if ( false === $i0 ) $i0 = 0;
	$horas = array();
	for ( $i = $i0; $i < min( $i0 + 48, count( $d['hourly']['time'] ) ); $i++ ) {
		$h = array( 't' => $d['hourly']['time'][ $i ] );
		foreach ( array( 'temperature_2m' => 'temp', 'relative_humidity_2m' => 'hr', 'precipitation_probability' => 'pp', 'precipitation' => 'mm', 'weather_code' => 'cod', 'wind_speed_10m' => 'viento', 'wind_gusts_10m' => 'rafaga', 'cape' => 'cape', 'lifted_index' => 'li', 'visibility' => 'vis', 'is_day' => 'dia' ) as $o => $n ) $h[ $n ] = $d['hourly'][ $o ][ $i ] ?? null;
		$horas[] = $h;
	}
	$dias = array();
	foreach ( (array) ( $d['daily']['time'] ?? array() ) as $i => $t ) {
		$dias[] = array( 'fecha' => $t, 'cod' => $d['daily']['weather_code'][ $i ] ?? null, 'max' => $d['daily']['temperature_2m_max'][ $i ] ?? null, 'min' => $d['daily']['temperature_2m_min'][ $i ] ?? null,
			'mm' => $d['daily']['precipitation_sum'][ $i ] ?? null, 'pp' => $d['daily']['precipitation_probability_max'][ $i ] ?? null, 'rafaga' => $d['daily']['wind_gusts_10m_max'][ $i ] ?? null,
			'uv' => $d['daily']['uv_index_max'][ $i ] ?? null, 'sale' => $d['daily']['sunrise'][ $i ] ?? null, 'pone' => $d['daily']['sunset'][ $i ] ?? null );
	}
	$c = (array) ( $d['current'] ?? array() );
	return array( 'fuente' => 'Open-Meteo', 'zona' => $d['timezone'] ?? '', 'offset' => (int) ( $d['utc_offset_seconds'] ?? 0 ), 'elevacion' => $d['elevation'] ?? null, 'edad' => $edad,
		'actual' => array( 'temp' => $c['temperature_2m'] ?? null, 'sensacion' => $c['apparent_temperature'] ?? null, 'hr' => $c['relative_humidity_2m'] ?? null, 'dia' => $c['is_day'] ?? null,
			'mm' => $c['precipitation'] ?? null, 'cod' => $c['weather_code'] ?? null, 'nubes' => $c['cloud_cover'] ?? null, 'presion' => $c['surface_pressure'] ?? null,
			'viento' => $c['wind_speed_10m'] ?? null, 'dir' => $c['wind_direction_10m'] ?? null, 'rafaga' => $c['wind_gusts_10m'] ?? null, 'vis' => $c['visibility'] ?? null, 'cape' => $c['cape'] ?? null ),
		'horas' => $horas, 'dias' => $dias );
}
/* Respaldo del clima: MET Norway (api.met.no), gratuito y sin clave; pide identificarse con un User-Agent.
   Trae temperatura, humedad, viento, ráfagas (donde las calcula), nubes, lluvia y un símbolo del tiempo
   que se traduce al mismo código WMO que usa Open-Meteo, para que la página no note la diferencia. */
function rau_cond_met_codigo( $sym ) {
	$s = preg_replace( '/_(day|night|polartwilight)$/', '', (string) $sym );
	if ( false !== strpos( $s, 'thunder' ) ) return 95;
	$t = array( 'clearsky' => 0, 'fair' => 1, 'partlycloudy' => 2, 'cloudy' => 3, 'fog' => 45, 'lightrainshowers' => 80, 'rainshowers' => 81, 'heavyrainshowers' => 82,
		'lightrain' => 61, 'rain' => 63, 'heavyrain' => 65, 'lightsleet' => 66, 'sleet' => 66, 'heavysleet' => 67, 'lightsnow' => 71, 'snow' => 73, 'heavysnow' => 75 );
	return $t[ $s ] ?? ( false !== strpos( $s, 'snow' ) ? 73 : ( false !== strpos( $s, 'sleet' ) ? 66 : ( false !== strpos( $s, 'rain' ) ? 63 : null ) ) );
}
function rau_cond_clima_met( $lat, $lon ) {
	$k = 'rau_cond_met_' . md5( $lat . ',' . $lon );
	$d = get_transient( $k );
	if ( ! is_array( $d ) ) {
		$r = rau_remote_get( add_query_arg( array( 'lat' => $lat, 'lon' => $lon ), 'https://api.met.no/weatherapi/locationforecast/2.0/complete' ), array( 'timeout' => 12, 'user-agent' => 'HK4RAU/1.0 https://hk4rau.com' ), 3 );
		if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) return null;
		$d = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( empty( $d['properties']['timeseries'] ) ) return null;
		set_transient( $k, $d, 15 * MINUTE_IN_SECONDS );
	}
	$off = (int) round( $lon / 15 ) * HOUR_IN_SECONDS; // hora local aproximada por longitud (Colombia: UTC−5)
	$horas = array(); $dias = array();
	foreach ( $d['properties']['timeseries'] as $x ) {
		$t = strtotime( $x['time'] ); if ( $t < time() - HOUR_IN_SECONDS ) continue;
		$i = $x['data']['instant']['details']; $n1 = $x['data']['next_1_hours'] ?? ( $x['data']['next_6_hours'] ?? array() );
		$loc = $t + $off; $h = (int) gmdate( 'G', $loc );
		$fila = array( 't' => gmdate( 'Y-m-d\TH:i', $loc ), 'temp' => $i['air_temperature'] ?? null, 'hr' => $i['relative_humidity'] ?? null,
			'pp' => $n1['details']['probability_of_precipitation'] ?? null, 'mm' => $n1['details']['precipitation_amount'] ?? null,
			'cod' => rau_cond_met_codigo( $n1['summary']['symbol_code'] ?? '' ), 'viento' => isset( $i['wind_speed'] ) ? round( $i['wind_speed'] * 3.6, 1 ) : null,
			'rafaga' => isset( $i['wind_speed_of_gust'] ) ? round( $i['wind_speed_of_gust'] * 3.6, 1 ) : null, 'cape' => null, 'li' => null, 'vis' => null, 'dia' => ( $h >= 6 && $h < 18 ) ? 1 : 0,
			'dir' => $i['wind_from_direction'] ?? null, 'nubes' => $i['cloud_area_fraction'] ?? null, 'presion' => $i['air_pressure_at_sea_level'] ?? null, 'sensacion' => $i['apparent_air_temperature'] ?? null );
		if ( count( $horas ) < 48 && isset( $x['data']['next_1_hours'] ) ) $horas[] = $fila;
		$f = gmdate( 'Y-m-d', $loc );
		if ( ! isset( $dias[ $f ] ) ) $dias[ $f ] = array( 'fecha' => $f, 'cod' => null, 'max' => null, 'min' => null, 'mm' => 0, 'pp' => null, 'rafaga' => null, 'uv' => null, 'sale' => null, 'pone' => null );
		$dd = &$dias[ $f ];
		if ( null !== $fila['temp'] ) { $dd['max'] = null === $dd['max'] ? $fila['temp'] : max( $dd['max'], $fila['temp'] ); $dd['min'] = null === $dd['min'] ? $fila['temp'] : min( $dd['min'], $fila['temp'] ); }
		if ( isset( $x['data']['next_1_hours'] ) ) $dd['mm'] += (float) $fila['mm'];
		if ( null !== $fila['cod'] && ( null === $dd['cod'] || $fila['cod'] > $dd['cod'] ) ) $dd['cod'] = $fila['cod'];
		if ( null !== $fila['rafaga'] ) $dd['rafaga'] = max( (float) $dd['rafaga'], $fila['rafaga'] );
		$dd['uv'] = max( (float) $dd['uv'], (float) ( $i['ultraviolet_index_clear_sky'] ?? 0 ) );
		unset( $dd );
	}
	if ( ! $horas ) return null;
	$a = $horas[0];
	return array( 'fuente' => 'MET Norway', 'zona' => '', 'offset' => $off, 'elevacion' => $d['geometry']['coordinates'][2] ?? null, 'edad' => 0,
		'actual' => array( 'temp' => $a['temp'], 'sensacion' => $a['sensacion'], 'hr' => $a['hr'], 'dia' => $a['dia'], 'mm' => $a['mm'], 'cod' => $a['cod'], 'nubes' => $a['nubes'], 'presion' => $a['presion'],
			'viento' => $a['viento'], 'dir' => $a['dir'], 'rafaga' => $a['rafaga'], 'vis' => null, 'cape' => null ),
		'horas' => $horas, 'dias' => array_slice( array_values( $dias ), 0, 3 ) );
}
function rau_cond_aire( $lat, $lon ) {
	$url = add_query_arg( array( 'latitude' => $lat, 'longitude' => $lon, 'current' => 'pm2_5,pm10,us_aqi,uv_index,ozone', 'timezone' => 'auto' ), 'https://air-quality-api.open-meteo.com/v1/air-quality' );
	list( $d, $edad ) = rau_cond_traer( $url, 30 * MINUTE_IN_SECONDS );
	if ( ! $d || empty( $d['current'] ) ) return null;
	$c = $d['current'];
	return array( 'pm25' => $c['pm2_5'] ?? null, 'pm10' => $c['pm10'] ?? null, 'aqi' => $c['us_aqi'] ?? null, 'uv' => $c['uv_index'] ?? null, 'edad' => $edad );
}
/* Código WMO del tiempo → texto e ícono */
function rau_cond_wmo( $c ) {
	$t = array( 0 => array( 'Despejado', '☀️' ), 1 => array( 'Casi despejado', '🌤️' ), 2 => array( 'Parcialmente nublado', '⛅' ), 3 => array( 'Nublado', '☁️' ),
		45 => array( 'Niebla', '🌫️' ), 48 => array( 'Niebla con escarcha', '🌫️' ), 51 => array( 'Llovizna débil', '🌦️' ), 53 => array( 'Llovizna', '🌦️' ), 55 => array( 'Llovizna fuerte', '🌧️' ),
		56 => array( 'Llovizna helada', '🌧️' ), 57 => array( 'Llovizna helada', '🌧️' ), 61 => array( 'Lluvia débil', '🌦️' ), 63 => array( 'Lluvia', '🌧️' ), 65 => array( 'Lluvia fuerte', '🌧️' ),
		66 => array( 'Lluvia helada', '🌧️' ), 67 => array( 'Lluvia helada', '🌧️' ), 71 => array( 'Nieve débil', '🌨️' ), 73 => array( 'Nieve', '🌨️' ), 75 => array( 'Nieve fuerte', '🌨️' ), 77 => array( 'Granos de nieve', '🌨️' ),
		80 => array( 'Chubascos débiles', '🌦️' ), 81 => array( 'Chubascos', '🌧️' ), 82 => array( 'Chubascos fuertes', '⛈️' ), 85 => array( 'Chubascos de nieve', '🌨️' ), 86 => array( 'Chubascos de nieve', '🌨️' ),
		95 => array( 'Tormenta eléctrica', '⛈️' ), 96 => array( 'Tormenta con granizo', '⛈️' ), 99 => array( 'Tormenta fuerte con granizo', '⛈️' ) );
	return $t[ (int) $c ] ?? array( '—', '🌡️' );
}

/* ── Clima espacial (NOAA SWPC + HamQSL) ──────────────────────────────── */
function rau_cond_espacio() {
	$b = 'https://services.swpc.noaa.gov/';
	$out = array( 'kp' => null, 'kp_pron' => null, 'rayosx' => null, 'escalas' => null, 'viento' => null, 'alertas' => array(), 'sfi' => null, 'bandas' => array() );
	list( $kp ) = rau_cond_traer( $b . 'json/planetary_k_index_1m.json', 5 * MINUTE_IN_SECONDS );
	if ( is_array( $kp ) && $kp ) { $u = end( $kp ); $out['kp'] = array( 'valor' => round( (float) ( $u['estimated_kp'] ?? $u['kp_index'] ?? 0 ), 1 ), 'hora' => $u['time_tag'] ?? '' ); }
	list( $kf ) = rau_cond_traer( $b . 'products/noaa-planetary-k-index-forecast.json', HOUR_IN_SECONDS );
	if ( is_array( $kf ) ) {
		$max = null; $hasta = time() + DAY_IN_SECONDS;
		foreach ( $kf as $f ) { if ( ! is_array( $f ) || ( $f['observed'] ?? '' ) !== 'predicted' ) continue; $t = strtotime( ( $f['time_tag'] ?? '' ) . ' UTC' ); if ( $t && $t <= $hasta && $t >= time() - 3 * HOUR_IN_SECONDS ) $max = max( (float) $max, (float) $f['kp'] ); }
		$out['kp_pron'] = null === $max ? null : round( $max, 1 );
	}
	list( $fl ) = rau_cond_traer( $b . 'json/goes/primary/xray-flares-latest.json', 5 * MINUTE_IN_SECONDS );
	if ( is_array( $fl ) && isset( $fl[0] ) ) $out['rayosx'] = array( 'actual' => $fl[0]['current_class'] ?? null, 'max' => $fl[0]['max_class'] ?? null, 'max_hora' => $fl[0]['max_time'] ?? null, 'fin' => $fl[0]['end_time'] ?? null );
	list( $es ) = rau_cond_traer( $b . 'products/noaa-scales.json', 5 * MINUTE_IN_SECONDS );
	if ( is_array( $es ) && isset( $es['0'] ) ) $out['escalas'] = array( 'R' => (int) ( $es['0']['R']['Scale'] ?? 0 ), 'S' => (int) ( $es['0']['S']['Scale'] ?? 0 ), 'G' => (int) ( $es['0']['G']['Scale'] ?? 0 ),
		'prob_r' => isset( $es['1']['R']['MinorProb'] ) ? (int) $es['1']['R']['MinorProb'] : null, 'prob_rx' => isset( $es['1']['R']['MajorProb'] ) ? (int) $es['1']['R']['MajorProb'] : null );
	list( $vw ) = rau_cond_traer( $b . 'json/rtsw/rtsw_wind_1m.json', 5 * MINUTE_IN_SECONDS );
	list( $vm ) = rau_cond_traer( $b . 'json/rtsw/rtsw_mag_1m.json', 5 * MINUTE_IN_SECONDS );
	$prim = function( $l, $campo ) { if ( ! is_array( $l ) ) return null; foreach ( $l as $x ) if ( ! empty( $x['active'] ) && isset( $x[ $campo ] ) && null !== $x[ $campo ] ) return $x; return null; };
	$w = $prim( $vw, 'proton_speed' ); $m = $prim( $vm, 'bz_gsm' );
	if ( $w || $m ) $out['viento'] = array( 'vel' => $w ? round( (float) $w['proton_speed'] ) : null, 'dens' => $w ? round( (float) $w['proton_density'], 1 ) : null, 'bz' => $m ? round( (float) $m['bz_gsm'], 1 ) : null, 'bt' => $m ? round( (float) $m['bt'], 1 ) : null );
	list( $al ) = rau_cond_traer( $b . 'products/alerts.json', 10 * MINUTE_IN_SECONDS );
	if ( is_array( $al ) ) foreach ( $al as $a ) {
		$t = strtotime( ( $a['issue_datetime'] ?? '' ) . ' UTC' ); if ( ! $t || $t < time() - DAY_IN_SECONDS ) continue;
		if ( preg_match( '/^(ALERT|WARNING|WATCH|EXTENDED WARNING|SUMMARY):\s*(.+)$/mi', (string) ( $a['message'] ?? '' ), $mm ) ) $out['alertas'][] = array( 'tipo' => strtoupper( $mm[1] ), 'texto' => trim( $mm[2] ), 'hora' => gmdate( 'c', $t ) );
		if ( count( $out['alertas'] ) >= 6 ) break;
	}
	// Bandas calculadas por HamQSL (la misma fuente del resto del sitio) y el flujo solar
	$s = get_transient( 'rau_solar_data' );
	if ( ! $s && function_exists( 'rau_fetch_solar_xml' ) && ( $xml = rau_fetch_solar_xml() ) ) {
		$bandas = array();
		if ( isset( $xml->solardata->calculatedconditions->band ) ) foreach ( $xml->solardata->calculatedconditions->band as $band ) {
			$n = (string) $band['name']; if ( ! isset( $bandas[ $n ] ) ) $bandas[ $n ] = array( 'name' => $n, 'day' => '—', 'night' => '—' ); $bandas[ $n ][ (string) $band['time'] ] = (string) $band;
		}
		$s = array( 'sfi' => (string) ( $xml->solardata->solarflux ?? '' ), 'ssn' => (string) ( $xml->solardata->sunspots ?? '' ), 'aindex' => (string) ( $xml->solardata->aindex ?? '' ), 'kindex' => (string) ( $xml->solardata->kindex ?? '' ), 'xray' => (string) ( $xml->solardata->xray ?? '' ), 'bands' => array_values( $bandas ) );
		set_transient( 'rau_solar_data', $s, 30 * MINUTE_IN_SECONDS );
	}
	if ( is_array( $s ) ) { $out['sfi'] = is_numeric( trim( (string) $s['sfi'] ) ) ? (int) $s['sfi'] : null; $out['ssn'] = is_numeric( trim( (string) ( $s['ssn'] ?? '' ) ) ) ? (int) $s['ssn'] : null; $out['bandas'] = (array) $s['bands']; }
	return $out;
}
/* Absorción en la capa D en el punto (NOAA D-RAP): la frecuencia más alta que pierde 1 dB o más */
function rau_cond_drap( $lat, $lon ) {
	list( $txt, $edad ) = rau_cond_traer( 'https://services.swpc.noaa.gov/text/drap_global_frequencies.txt', 5 * MINUTE_IN_SECONDS, 'texto' );
	if ( ! is_string( $txt ) ) return null;
	return rau_cond_drap_leer( $txt, $lat, $lon, $edad );
}
function rau_cond_drap_leer( $txt, $lat, $lon, $edad = 0 ) {
	$lons = null; $mejor = null; $dmin = 999;
	foreach ( preg_split( '/\r?\n/', $txt ) as $l ) {
		if ( null === $lons && preg_match( '/^\s+-?\d+(\s+-?\d+){20,}\s*$/', $l ) ) { $lons = array_map( 'intval', preg_split( '/\s+/', trim( $l ) ) ); continue; }
		if ( $lons && preg_match( '/^\s*(-?\d+)\s*\|(.*)$/', $l, $m ) ) {
			$d = abs( (int) $m[1] - $lat ); if ( $d >= $dmin ) continue;
			$dmin = $d; $mejor = array_map( 'floatval', preg_split( '/\s+/', trim( $m[2] ) ) );
		}
	}
	if ( ! $lons || ! $mejor ) return null;
	$j = 0; $dl = 999; foreach ( $lons as $i => $x ) { $dd = abs( $x - $lon ); if ( $dd < $dl ) { $dl = $dd; $j = $i; } }
	$f = $mejor[ $j ] ?? null;
	$msg = preg_match( '/X-RAY Message\s*:\s*(.+)/', $txt, $mm ) ? trim( $mm[1] ) : '';
	return null === $f ? null : array( 'mhz' => $f, 'mensaje' => $msg, 'edad' => $edad );
}
/* Ionosonda más cercana al punto y lo que se deduce para NVIS y DX (reusa inc/pronostico.php) */
function rau_cond_ionosfera( $lat, $lon, $hora_local ) {
	if ( ! function_exists( 'rau_pron_ionosonda' ) ) return null;
	list( $est ) = rau_cond_traer( 'https://prop.kc2g.com/api/stations.json', 10 * MINUTE_IN_SECONDS, 'json', 15 );
	if ( ! is_array( $est ) ) return null;
	$iono = rau_pron_ionosonda( null, array( $lat, $lon ), $est );
	$res = rau_pron_calcular( $iono, array( 'sfi' => null, 'kp' => null ), $hora_local );
	$res['estaciones'] = array();
	foreach ( $est as $s ) {
		if ( empty( $s['station']['latitude'] ) || empty( $s['time'] ) ) continue;
		$t = strtotime( $s['time'] . ' UTC' ); if ( ! $t || time() - $t > 3 * HOUR_IN_SECONDS ) continue;
		$sl = (float) $s['station']['latitude']; $sg = (float) $s['station']['longitude']; if ( $sg > 180 ) $sg -= 360;
		if ( rau_cond_km( array( $lat, $lon ), array( $sl, $sg ) ) > 6000 ) continue;
		$res['estaciones'][] = array( 'n' => $s['station']['name'], 'lat' => $sl, 'lon' => $sg, 'mufd' => isset( $s['mufd'] ) ? round( (float) $s['mufd'], 1 ) : null, 'fof2' => isset( $s['fof2'] ) ? round( (float) $s['fof2'], 1 ) : null, 'hace' => (int) round( ( time() - $t ) / 60 ) );
	}
	if ( function_exists( 'rau_pron_nvis_medido' ) ) { $m = rau_pron_nvis_medido(); $res['medido'] = $m; $res['medido']['texto'] = rau_pron_nvis_medido_texto( $m ); }
	return $res;
}
/* Sismos de los últimos 7 días (M2,5+) a menos de 500 km */
function rau_cond_sismos( $lat, $lon ) {
	list( $d ) = rau_cond_traer( 'https://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/2.5_week.geojson', 15 * MINUTE_IN_SECONDS, 'json', 15 );
	if ( ! is_array( $d ) || empty( $d['features'] ) ) return is_array( $d ) ? array() : null;
	$out = array();
	foreach ( $d['features'] as $f ) {
		$c = $f['geometry']['coordinates'] ?? null; if ( ! $c ) continue;
		$km = rau_cond_km( array( $lat, $lon ), array( (float) $c[1], (float) $c[0] ) ); if ( $km > 500 ) continue;
		$out[] = array( 'mag' => round( (float) $f['properties']['mag'], 1 ), 'lugar' => (string) $f['properties']['place'], 't' => (int) ( $f['properties']['time'] / 1000 ), 'km' => $km, 'prof' => round( (float) ( $c[2] ?? 0 ) ), 'lat' => (float) $c[1], 'lon' => (float) $c[0], 'url' => (string) ( $f['properties']['url'] ?? '' ) );
	}
	usort( $out, function( $a, $b ) { return $b['t'] - $a['t']; } );
	return array_slice( $out, 0, 12 );
}
function rau_cond_repetidoras( $lat, $lon ) {
	if ( ! function_exists( 'rau_get_repeaters_data' ) ) return array();
	$l = array();
	foreach ( rau_get_repeaters_data() as $r ) { $r['km'] = rau_cond_km( array( $lat, $lon ), array( $r['lat'], $r['lng'] ) ); $l[] = $r; }
	usort( $l, function( $a, $b ) { return $a['km'] - $b['km']; } );
	return $l;
}

/* ── Semáforo: 0 bien · 1 atención · 2 alerta. Lo usan la página y los avisos al celular ──── */
function rau_cond_semaforo( $clima, $esp, $drap ) {
	$s = array();
	// Tormenta eléctrica: código de tormenta en las próximas 3 h, o ambiente muy inestable con lluvia probable
	if ( $clima ) {
		$t = array( 'nivel' => 0, 'texto' => 'Sin tormentas previstas en las próximas 3 horas.' );
		foreach ( array_slice( $clima['horas'], 0, 3 ) as $h ) {
			if ( in_array( (int) $h['cod'], array( 95, 96, 99 ), true ) ) { $t = array( 'nivel' => 2, 'texto' => 'Tormenta eléctrica prevista hacia las ' . substr( $h['t'], 11, 5 ) . '. Desconecta las antenas del equipo y no operes desde la torre.', 'hora' => $h['t'] ); break; }
			if ( 0 === $t['nivel'] && (float) $h['cape'] >= 1500 && (float) $h['pp'] >= 50 ) $t = array( 'nivel' => 1, 'texto' => 'Ambiente propicio para tormentas (energía convectiva ' . (int) $h['cape'] . ' J/kg, lluvia ' . (int) $h['pp'] . ' %). Ten listo desconectar antenas.' );
		}
		$s['tormenta'] = $t;
		$con_rafagas = false; foreach ( array_slice( $clima['horas'], 0, 6 ) as $h ) if ( null !== $h['rafaga'] ) $con_rafagas = true;
		$max = 0; $hora = ''; foreach ( array_slice( $clima['horas'], 0, 6 ) as $h ) { $v = (float) ( $con_rafagas ? $h['rafaga'] : $h['viento'] ); if ( $v > $max ) { $max = $v; $hora = substr( $h['t'], 11, 5 ); } }
		$s['viento'] = $max >= 60 ? array( 'nivel' => 2, 'texto' => 'Ráfagas hasta ' . round( $max ) . ' km/h hacia las ' . $hora . ': no subas a la torre y asegura antenas y mástiles.', 'kmh' => round( $max ) )
			: ( $max >= 40 ? array( 'nivel' => 1, 'texto' => 'Ráfagas de ' . round( $max ) . ' km/h hacia las ' . $hora . ': cuidado si vas a trabajar en altura.', 'kmh' => round( $max ) )
			: array( 'nivel' => 0, 'texto' => 'Viento tranquilo: ' . ( $con_rafagas ? 'ráfagas' : 'viento' ) . ' de hasta ' . round( $max ) . ' km/h en 6 horas.', 'kmh' => round( $max ) ) );
	}
	// Sol: llamaradas y escala R (apagones de radio en el lado de día)
	if ( $esp ) {
		$clase = (string) ( $esp['rayosx']['max'] ?? '' ); $ahora = (string) ( $esp['rayosx']['actual'] ?? '' ); $r = (int) ( $esp['escalas']['R'] ?? 0 );
		$letra = strtoupper( substr( $clase, 0, 1 ) ); $reciente = ! empty( $esp['rayosx']['max_hora'] ) && time() - strtotime( $esp['rayosx']['max_hora'] ) < 2 * HOUR_IN_SECONDS;
		if ( $r >= 2 || ( $reciente && 'X' === $letra ) ) $s['sol'] = array( 'nivel' => 2, 'texto' => 'Llamarada ' . $clase . ( $r ? ' (R' . $r . ')' : '' ) . ': apagón de HF en el lado de día. Las bandas bajas son las primeras en caer.' );
		elseif ( $r >= 1 || ( $reciente && 'M' === $letra ) ) $s['sol'] = array( 'nivel' => 1, 'texto' => 'Llamarada ' . $clase . ': posible apagón corto de HF de día, sobre todo por debajo de 10 MHz.' );
		else $s['sol'] = array( 'nivel' => 0, 'texto' => 'Sol tranquilo' . ( $ahora ? ' (rayos X ' . $ahora . ')' : '' ) . '.' );
		$kp = $esp['kp']['valor'] ?? null; $g = (int) ( $esp['escalas']['G'] ?? 0 );
		$s['geomag'] = ( $g >= 1 || ( null !== $kp && $kp >= 5 ) ) ? array( 'nivel' => 2, 'texto' => 'Tormenta geomagnética (Kp ' . number_format( (float) $kp, 1, ',', '' ) . ( $g ? ', G' . $g : '' ) . '): HF inestable, sobre todo en bandas altas y rutas polares.' )
			: ( null !== $kp && $kp >= 4 ? array( 'nivel' => 1, 'texto' => 'Campo geomagnético activo (Kp ' . number_format( (float) $kp, 1, ',', '' ) . '): posibles altibajos.' )
			: array( 'nivel' => 0, 'texto' => 'Campo geomagnético tranquilo' . ( null !== $kp ? ' (Kp ' . number_format( (float) $kp, 1, ',', '' ) . ')' : '' ) . '.' ) );
	}
	// HF: bandas de HamQSL según sea de día o de noche en el punto, con la absorción local encima
	if ( $esp && $esp['bandas'] ) {
		$dia = $clima ? (int) $clima['actual']['dia'] : 1; $ab = array(); $ce = array();
		foreach ( $esp['bandas'] as $b ) { $e = $dia ? $b['day'] : $b['night']; $n = str_replace( 'm-', '–', $b['name'] ); if ( 'Good' === $e ) $ab[] = $n; elseif ( 'Poor' === $e ) $ce[] = $n; }
		$nivel = $ab ? 0 : 1; $txt = ( $ab ? 'Abiertas: ' . implode( ', ', $ab ) : 'Ninguna banda en buenas condiciones' ) . ( $ce ? ' · cerradas: ' . implode( ', ', $ce ) : '' ) . '.';
		if ( $drap && $drap['mhz'] >= 1 ) { $nivel = $drap['mhz'] >= 10 ? 2 : max( $nivel, 1 ); $txt = 'Absorción en tu zona: se pierden las frecuencias por debajo de ~' . number_format( $drap['mhz'], 0 ) . ' MHz. ' . $txt; }
		if ( isset( $s['geomag'] ) && 2 === $s['geomag']['nivel'] ) $nivel = max( $nivel, 1 );
		$s['hf'] = array( 'nivel' => $nivel, 'texto' => $txt );
	}
	return $s;
}

/* ── Todo junto, por punto redondeado ─────────────────────────────────── */
function rau_condiciones( $lat, $lon ) {
	list( $lat, $lon ) = rau_cond_redondear( $lat, $lon );
	$k = 'rau_cond_todo_' . md5( $lat . ',' . $lon );
	$c = get_transient( $k ); if ( is_array( $c ) ) return $c;
	$clima = rau_cond_clima( $lat, $lon );
	$esp   = rau_cond_espacio();
	$drap  = rau_cond_drap( $lat, $lon );
	$hora  = $clima ? (int) gmdate( 'G', time() + $clima['offset'] ) : (int) gmdate( 'G', time() - 5 * HOUR_IN_SECONDS );
	$out = array(
		'punto' => array( 'lat' => $lat, 'lon' => $lon, 'locator' => rau_cond_locator( $lat, $lon ), 'cerca' => rau_cond_cerca_de( $lat, $lon ) ),
		'clima' => $clima, 'aire' => rau_cond_aire( $lat, $lon ), 'espacio' => $esp, 'drap' => $drap,
		'iono' => rau_cond_ionosfera( $lat, $lon, $hora ), 'sismos' => rau_cond_sismos( $lat, $lon ),
		'repetidoras' => array_slice( rau_cond_repetidoras( $lat, $lon ), 0, 3 ),
		'semaforo' => rau_cond_semaforo( $clima, $esp, $drap ), 'generado' => time(),
	);
	if ( $clima ) $out['clima']['texto_actual'] = rau_cond_wmo( $clima['actual']['cod'] );
	set_transient( $k, $out, 5 * MINUTE_IN_SECONDS );
	return $out;
}

add_action( 'rest_api_init', function() {
	register_rest_route( 'rau/v1', '/condiciones', array( 'methods' => 'GET', 'permission_callback' => '__return_true',
		'args' => array( 'lat' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ), 'lon' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ) ),
		'callback' => function( WP_REST_Request $r ) {
			$lat = null !== $r['lat'] ? (float) $r['lat'] : RAU_COND_PUNTO[0]; $lon = null !== $r['lon'] ? (float) $r['lon'] : RAU_COND_PUNTO[1];
			$res = new WP_REST_Response( rau_condiciones( $lat, $lon ) );
			$res->header( 'Cache-Control', 'public, max-age=120' );
			return $res;
		} ) );
	register_rest_route( 'rau/v1', '/condiciones/buscar', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function( WP_REST_Request $r ) {
		$q = trim( sanitize_text_field( (string) $r['q'] ) ); if ( mb_strlen( $q ) < 2 ) return array();
		$q = mb_substr( $q, 0, 60 ); $out = array();
		foreach ( array( 'CO', '' ) as $pais ) {
			$url = add_query_arg( array_filter( array( 'name' => $q, 'count' => 8, 'language' => 'es', 'format' => 'json', 'countryCode' => $pais ) ), 'https://geocoding-api.open-meteo.com/v1/search' );
			list( $d ) = rau_cond_traer( $url, DAY_IN_SECONDS );
			foreach ( (array) ( $d['results'] ?? array() ) as $x ) $out[] = array( 'nombre' => $x['name'], 'zona' => trim( ( $x['admin1'] ?? '' ) . ( isset( $x['country'] ) && 'CO' !== ( $x['country_code'] ?? '' ) ? ', ' . $x['country'] : '' ), ', ' ), 'lat' => (float) $x['latitude'], 'lon' => (float) $x['longitude'] );
			if ( $out ) break;
		}
		return $out;
	} ) );
	register_rest_route( 'rau/v1', '/condiciones/avisos', array(
		array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => 'rau_cond_avisos_guardar' ),
		array( 'methods' => 'DELETE', 'permission_callback' => '__return_true', 'callback' => 'rau_cond_avisos_borrar' ),
	) );
} );

/* ── Avisos al celular ────────────────────────────────────────────────── */
const RAU_COND_TEMAS = array( 'tormenta' => 3, 'viento' => 6, 'sol' => 2, 'geomag' => 6, 'seis' => 4 ); // tema => horas mínimas entre avisos
function rau_cond_avisos_guardar( WP_REST_Request $r ) {
	$ip = md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) );
	if ( get_transient( 'rau_cond_av_' . $ip ) ) return new WP_REST_Response( array( 'error' => 'Espera unos segundos.' ), 429 );
	set_transient( 'rau_cond_av_' . $ip, 1, 10 );
	$b = $r->get_json_params();
	$sub = function_exists( 'rau_push_validar_suscripcion' ) ? rau_push_validar_suscripcion( $b['sub'] ?? null ) : 'Sin avisos en este sitio.';
	if ( is_string( $sub ) ) return new WP_REST_Response( array( 'error' => $sub ), 400 );
	if ( ! is_numeric( $b['lat'] ?? null ) || ! is_numeric( $b['lon'] ?? null ) ) return new WP_REST_Response( array( 'error' => 'Falta la ubicación.' ), 400 );
	$temas = array_values( array_intersect( array_keys( RAU_COND_TEMAS ), (array) ( $b['temas'] ?? array() ) ) );
	if ( ! $temas ) return new WP_REST_Response( array( 'error' => 'Elige al menos un aviso.' ), 400 );
	list( $lat, $lon ) = rau_cond_redondear( $b['lat'], $b['lon'] );
	$todos = get_option( 'rau_cond_avisos', array() ); if ( ! is_array( $todos ) ) $todos = array();
	$id = md5( $sub['endpoint'] );
	if ( ! isset( $todos[ $id ] ) && count( $todos ) >= 3000 ) return new WP_REST_Response( array( 'error' => 'Se alcanzó el máximo de avisos.' ), 503 );
	$todos[ $id ] = array( 'sub' => $sub, 'lat' => $lat, 'lon' => $lon, 'temas' => $temas, 'ult' => $todos[ $id ]['ult'] ?? array(), 'desde' => time() );
	update_option( 'rau_cond_avisos', $todos, false );
	return array( 'ok' => true, 'lat' => $lat, 'lon' => $lon, 'temas' => $temas );
}
function rau_cond_avisos_borrar( WP_REST_Request $r ) {
	$b = $r->get_json_params(); $ep = (string) ( $b['endpoint'] ?? '' );
	$todos = get_option( 'rau_cond_avisos', array() );
	if ( $ep && isset( $todos[ md5( $ep ) ] ) ) { unset( $todos[ md5( $ep ) ] ); update_option( 'rau_cond_avisos', $todos, false ); }
	return array( 'ok' => true );
}
/* 6 m abierta: spots de 50–54 MHz en la última media hora con un extremo en el Caribe o Suramérica */
function rau_cond_seis( $spots = null ) {
	if ( null === $spots ) { $spots = get_transient( 'rau_dx_spots_' . RAU_THEME_VERSION ); if ( ! is_array( $spots ) ) return null; }
	$n = 0; $ej = array();
	foreach ( $spots as $s ) {
		$f = (float) ( $s['freq'] ?? 0 ); if ( $f < 50 || $f > 54 ) continue;
		$n++; if ( count( $ej ) < 4 ) $ej[] = trim( ( $s['sp'] ?? '' ) . ' → ' . ( $s['dx'] ?? '' ) . ' ' . $f );
	}
	return array( 'n' => $n, 'ejemplos' => $ej );
}
function rau_cond_avisos_revisar( $enviar = 'rau_send_web_push' ) {
	$todos = get_option( 'rau_cond_avisos', array() ); if ( ! $todos ) return 0;
	$esp = rau_cond_espacio(); $seis = rau_cond_seis(); $enviados = 0; $celdas = array(); $url = home_url( '/condiciones/' );
	foreach ( $todos as $id => &$a ) {
		$c = $a['lat'] . ',' . $a['lon'];
		if ( ! isset( $celdas[ $c ] ) ) {
			if ( count( $celdas ) >= 150 ) continue; // tope por pasada: el resto en la siguiente
			$clima = ( array_intersect( array( 'tormenta', 'viento' ), $a['temas'] ) ) ? rau_cond_clima( $a['lat'], $a['lon'] ) : null;
			$celdas[ $c ] = rau_cond_semaforo( $clima, $esp, null );
		}
		$sem = $celdas[ $c ];
		$candidatos = array(
			'tormenta' => isset( $sem['tormenta'] ) && 2 === $sem['tormenta']['nivel'] ? array( '⛈️ Tormenta eléctrica cerca', $sem['tormenta']['texto'] ) : null,
			'viento'   => isset( $sem['viento'] ) && 2 === $sem['viento']['nivel'] ? array( '💨 Viento fuerte', $sem['viento']['texto'] ) : null,
			'sol'      => isset( $sem['sol'] ) && 2 === $sem['sol']['nivel'] ? array( '☀️ Llamarada solar', $sem['sol']['texto'] ) : null,
			'geomag'   => isset( $sem['geomag'] ) && 2 === $sem['geomag']['nivel'] ? array( '🧲 Tormenta geomagnética', $sem['geomag']['texto'] ) : null,
			'seis'     => $seis && $seis['n'] >= 3 ? array( '📡 6 metros abierta', $seis['n'] . ' reportes en 6 m en el cluster: ' . implode( ', ', $seis['ejemplos'] ) ) : null,
		);
		foreach ( $a['temas'] as $t ) {
			if ( empty( $candidatos[ $t ] ) ) continue;
			if ( ! empty( $a['ult'][ $t ] ) && time() - $a['ult'][ $t ] < RAU_COND_TEMAS[ $t ] * HOUR_IN_SECONDS ) continue;
			if ( call_user_func( $enviar, $a['sub'], $candidatos[ $t ][0], $candidatos[ $t ][1], $url ) ) { $a['ult'][ $t ] = time(); $enviados++; }
		}
	}
	unset( $a );
	update_option( 'rau_cond_avisos', $todos, false );
	return $enviados;
}
add_action( 'init', function() { if ( ! wp_next_scheduled( 'rau_cond_cron' ) ) wp_schedule_event( time() + 120, 'rau_quarter_hour', 'rau_cond_cron' ); } );
add_action( 'rau_cond_cron', function() { rau_cond_avisos_revisar(); } );

/* ── La página /condiciones/ ──────────────────────────────────────────── */
add_action( 'init', function() { add_rewrite_rule( '^condiciones/?$', 'index.php?rau_condiciones=1', 'top' ); } );
add_filter( 'query_vars', function( $v ) { $v[] = 'rau_condiciones'; return $v; } );
add_filter( 'template_include', function( $t ) { return get_query_var( 'rau_condiciones' ) ? get_template_directory() . '/page-condiciones.php' : $t; } );
add_filter( 'pre_get_document_title', function( $t ) { return get_query_var( 'rau_condiciones' ) ? 'Condiciones para operar — ' . get_bloginfo( 'name' ) : $t; } );
add_filter( 'body_class', function( $c ) { if ( get_query_var( 'rau_condiciones' ) ) $c[] = 'rau-condiciones'; return $c; } );
add_action( 'wp_enqueue_scripts', function() {
	if ( ! get_query_var( 'rau_condiciones' ) ) return;
	$u = get_template_directory_uri(); $v = RAU_THEME_VERSION;
	wp_enqueue_style( 'leaflet', $u . '/assets/vendor/leaflet/leaflet.css', array(), '1.9.4' );
	wp_enqueue_style( 'rau-condiciones', $u . '/assets/condiciones/condiciones.css', array(), $v );
	wp_enqueue_script( 'leaflet', $u . '/assets/vendor/leaflet/leaflet.js', array(), '1.9.4', true );
	wp_enqueue_script( 'rau-condiciones', $u . '/assets/condiciones/condiciones.js', array( 'leaflet' ), $v, true );
	wp_localize_script( 'rau-condiciones', 'RAU_COND', array(
		'api' => rest_url( 'rau/v1/condiciones' ), 'ajax' => admin_url( 'admin-ajax.php' ), 'punto' => RAU_COND_PUNTO,
		'repetidoras' => function_exists( 'rau_get_repeaters_data' ) ? array_map( function( $r ) { return array( 'n' => $r['name'], 'c' => $r['callsign'], 'f' => $r['freq'], 'o' => $r['offset'], 't' => $r['tone'], 'lat' => $r['lat'], 'lon' => $r['lng'], 'url' => $r['url'] ); }, rau_get_repeaters_data() ) : array(),
		'receptor' => home_url( '/receptor/' ), 'herramientas' => home_url( '/herramientas/' ),
		'satjs' => 'https://cdn.jsdelivr.net/npm/satellite.js@5.0.0/dist/satellite.min.js',
	) );
} );

/* Menú: «Condiciones» aparece sola justo después de «Herramientas» en el menú principal, salvo que
   ya la hayan puesto a mano (entonces se respeta donde la pusieron). */
add_filter( 'wp_nav_menu_objects', function( $items, $args ) {
	if ( empty( $args->theme_location ) || ! in_array( $args->theme_location, array( 'primary', 'mobile' ), true ) ) return $items;
	$url = home_url( '/condiciones/' ); $pos = null;
	foreach ( $items as $i => $it ) {
		if ( untrailingslashit( $it->url ) === untrailingslashit( $url ) ) return $items;
		if ( null === $pos && ! $it->menu_item_parent && false !== strpos( untrailingslashit( $it->url ), '/herramientas' ) ) $pos = $i;
	}
	$nuevo = (object) array( 'ID' => 990062, 'db_id' => 990062, 'title' => 'Condiciones', 'url' => $url, 'menu_item_parent' => 0, 'object_id' => 0, 'object' => 'custom', 'type' => 'custom',
		'type_label' => 'Enlace', 'target' => '', 'attr_title' => '', 'description' => '', 'classes' => array( 'menu-item', 'rau-menu-condiciones' ), 'xfn' => '', 'menu_order' => 0, 'post_parent' => 0,
		'current' => (bool) get_query_var( 'rau_condiciones' ), 'current_item_ancestor' => false, 'current_item_parent' => false, 'post_type' => 'nav_menu_item', 'status' => 'publish' );
	if ( $nuevo->current ) $nuevo->classes[] = 'current-menu-item';
	if ( null === $pos ) { $items[] = $nuevo; return $items; }
	$antes = array_slice( $items, 0, array_search( $pos, array_keys( $items ), true ) + 1 ); $despues = array_slice( $items, array_search( $pos, array_keys( $items ), true ) + 1 );
	return array_merge( $antes, array( $nuevo ), $despues );
}, 10, 2 );
