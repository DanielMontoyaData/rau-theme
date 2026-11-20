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
	rau_cond_salud_anotar( $url, null !== $d );
	$por_punto = (bool) preg_match( '/[?&](latitude|lat)=/', $url ); // las de un punto no se respaldan: serían miles
	if ( null !== $d ) {
		set_transient( $k, array( 'd' => $d, 't' => time() ), $ttl );
		if ( ! $por_punto ) update_option( 'rau_condr_' . md5( $url ), array( 'd' => $d, 't' => time() ), false ); // respaldo
		return array( $d, 0 );
	}
	$viejo = $por_punto ? null : get_option( 'rau_condr_' . md5( $url ) );
	if ( is_array( $viejo ) && time() - $viejo['t'] < DAY_IN_SECONDS ) return array( $viejo['d'], time() - $viejo['t'] );
	return array( null, null );
}

/* Salud de las fuentes: último acierto y último fallo por servicio, para el tablero y el monitoreo */
function rau_cond_fuente_de( $url ) {
	$h = (string) wp_parse_url( $url, PHP_URL_HOST );
	$m = array( 'api.open-meteo.com' => 'Open-Meteo', 'air-quality-api.open-meteo.com' => 'Calidad del aire', 'geocoding-api.open-meteo.com' => 'Buscador', 'api.met.no' => 'MET Norway',
		'services.swpc.noaa.gov' => 'NOAA SWPC', 'prop.kc2g.com' => 'Ionosondas (KC2G)', 'earthquake.usgs.gov' => 'USGS', 'www.hamqsl.com' => 'HamQSL' );
	return $m[ $h ] ?? $h;
}
function rau_cond_salud_anotar( $url, $ok ) {
	static $hecho = array(); $f = rau_cond_fuente_de( $url ); $clave = $f . ( $ok ? 1 : 0 );
	if ( isset( $hecho[ $clave ] ) ) return; $hecho[ $clave ] = 1;
	$s = get_option( 'rau_cond_salud', array() ); if ( ! is_array( $s ) ) $s = array();
	$s[ $f ] = array_merge( array( 'ok' => 0, 'fallo' => 0 ), $s[ $f ] ?? array(), array( $ok ? 'ok' : 'fallo' => time() ) );
	update_option( 'rau_cond_salud', $s, false );
}
/* Estado de cada fuente: verde si respondió hace poco, ámbar si el último dato es viejo, rojo si falla */
function rau_cond_salud() {
	$s = get_option( 'rau_cond_salud', array() ); $out = array();
	$esperado = array( 'Open-Meteo' => 30, 'NOAA SWPC' => 30, 'Ionosondas (KC2G)' => 60, 'USGS' => 60, 'HamQSL' => 120, 'Calidad del aire' => 120, 'MET Norway' => 0, 'Buscador' => 0 );
	foreach ( $esperado as $f => $min ) {
		$x = $s[ $f ] ?? null; if ( ! $x ) { if ( $min ) $out[] = array( 'fuente' => $f, 'estado' => 'sin_datos', 'hace' => null ); continue; }
		$hace = $x['ok'] ? (int) round( ( time() - $x['ok'] ) / 60 ) : null;
		$estado = ! $x['ok'] ? 'falla' : ( $x['fallo'] > $x['ok'] ? 'falla' : ( $min && $hace > $min * 3 ? 'viejo' : 'bien' ) );
		if ( 'MET Norway' === $f && 'falla' !== $estado ) $estado = 'respaldo';
		$out[] = array( 'fuente' => $f, 'estado' => $estado, 'hace' => $hace );
	}
	return $out;
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
		$ok = ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ); rau_cond_salud_anotar( 'https://api.met.no/', $ok );
		if ( ! $ok ) return null;
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
	rau_cond_salud_anotar( 'https://www.hamqsl.com/', is_array( $s ) );
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
	$res['codigo'] = null;
	if ( $iono ) foreach ( $est as $x ) if ( ( $x['station']['name'] ?? '' ) === $iono['nombre'] ) { $res['codigo'] = $x['station']['code'] ?? null; break; }
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
		$tormentosa = array( 95, 96, 99 );
		if ( in_array( (int) $clima['actual']['cod'], $tormentosa, true ) ) $t = array( 'nivel' => 2, 'texto' => 'Tormenta eléctrica en curso en tu zona. Desconecta las antenas del equipo y no operes desde la torre.', 'en_curso' => true );
		foreach ( 2 === $t['nivel'] ? array() : array_slice( $clima['horas'], 0, 3 ) as $i => $h ) {
			if ( in_array( (int) $h['cod'], $tormentosa, true ) ) {
				$t = 0 === $i ? array( 'nivel' => 2, 'texto' => 'Tormenta eléctrica en curso o a punto de empezar. Desconecta las antenas del equipo y no operes desde la torre.', 'hora' => $h['t'], 'en_curso' => true )
					: array( 'nivel' => 2, 'texto' => 'Tormenta eléctrica prevista hacia las ' . substr( $h['t'], 11, 5 ) . '. Desconecta las antenas del equipo y no operes desde la torre.', 'hora' => $h['t'] );
				break;
			}
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
	$seis = rau_cond_seis_todo();
	if ( $seis ) $s['seis'] = $seis['n'] >= 3 ? array( 'nivel' => 2, 'texto' => $seis['texto'], 'abierta' => true ) : array( 'nivel' => $seis['n'] ? 1 : 0, 'texto' => $seis['texto'] );
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
		'tend' => rau_cond_tendencias(), 'drap_mapa' => rau_cond_drap_mapa(), 'salud' => rau_cond_salud(),
	);
	if ( $clima ) $out['clima']['texto_actual'] = rau_cond_wmo( $clima['actual']['cod'] );
	if ( $out['iono'] && ! empty( $out['iono']['codigo'] ) ) $out['iono']['matriz'] = rau_cond_matriz( $out['iono']['codigo'], $clima ? $clima['offset'] : -18000 );
	if ( ! empty( $esp['alertas'] ) ) foreach ( $out['espacio']['alertas'] as &$al ) $al['es'] = rau_cond_traducir( $al['tipo'] . ': ' . $al['texto'] );
	unset( $al );
	$out['eventos'] = rau_cond_eventos( $out );
	set_transient( $k, $out, 10 * MINUTE_IN_SECONDS );
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
	// 19.63.0: varios puntos por navegador (casa, repetidora, finca), hasta 5, cada uno con su nombre y su umbral de viento
	$id = md5( $sub['endpoint'] . '|' . $lat . ',' . $lon );
	$mios = 0; foreach ( $todos as $k => $a ) if ( $k !== $id && $a['sub']['endpoint'] === $sub['endpoint'] ) $mios++;
	if ( ! isset( $todos[ $id ] ) && $mios >= 5 ) return new WP_REST_Response( array( 'error' => 'Ya tienes 5 puntos con avisos. Quita uno para agregar otro.' ), 400 );
	if ( ! isset( $todos[ $id ] ) && count( $todos ) >= 3000 ) return new WP_REST_Response( array( 'error' => 'Se alcanzó el máximo de avisos.' ), 503 );
	$umbral = (int) ( $b['umbral'] ?? 60 ); if ( ! in_array( $umbral, array( 40, 50, 60, 70, 80 ), true ) ) $umbral = 60;
	$nombre = mb_substr( sanitize_text_field( (string) ( $b['nombre'] ?? '' ) ), 0, 60 );
	$todos[ $id ] = array( 'sub' => $sub, 'lat' => $lat, 'lon' => $lon, 'temas' => $temas, 'umbral' => $umbral, 'nombre' => $nombre, 'ult' => $todos[ $id ]['ult'] ?? array(), 'desde' => time() );
	update_option( 'rau_cond_avisos', $todos, false );
	return array( 'ok' => true, 'id' => $id, 'lat' => $lat, 'lon' => $lon, 'temas' => $temas, 'umbral' => $umbral );
}
function rau_cond_avisos_borrar( WP_REST_Request $r ) {
	$b = $r->get_json_params(); $ep = (string) ( $b['endpoint'] ?? '' ); $id = (string) ( $b['id'] ?? '' );
	$todos = get_option( 'rau_cond_avisos', array() ); if ( ! is_array( $todos ) || ! $ep ) return array( 'ok' => true );
	foreach ( $todos as $k => $a ) if ( hash_equals( $a['sub']['endpoint'], $ep ) && ( ! $id || $id === $k ) ) unset( $todos[ $k ] ); // con id, solo ese punto; sin id, todos
	update_option( 'rau_cond_avisos', $todos, false );
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
	$esp = rau_cond_espacio(); $seis = rau_cond_seis_todo(); $enviados = 0; $celdas = array(); $url = home_url( '/condiciones/' );
	foreach ( $todos as $id => &$a ) {
		$c = $a['lat'] . ',' . $a['lon'];
		if ( ! isset( $celdas[ $c ] ) ) {
			if ( count( $celdas ) >= 150 ) continue; // tope por pasada: el resto en la siguiente
			$clima = ( array_intersect( array( 'tormenta', 'viento' ), $a['temas'] ) ) ? rau_cond_clima( $a['lat'], $a['lon'] ) : null;
			$celdas[ $c ] = rau_cond_semaforo( $clima, $esp, null );
		}
		$sem = $celdas[ $c ];
		$candidatos = array(
			'tormenta' => isset( $sem['tormenta'] ) && 2 === $sem['tormenta']['nivel'] ? array( '⛈️ Tormenta eléctrica' . ( ! empty( $a['nombre'] ) ? ' · ' . $a['nombre'] : '' ), $sem['tormenta']['texto'] ) : null,
			'viento'   => isset( $sem['viento'] ) && (float) $sem['viento']['kmh'] >= (int) ( $a['umbral'] ?? 60 ) ? array( '💨 Viento fuerte' . ( ! empty( $a['nombre'] ) ? ' · ' . $a['nombre'] : '' ), 'Hasta ' . $sem['viento']['kmh'] . ' km/h en las próximas 6 horas: no subas a la torre y asegura antenas.' ) : null,
			'sol'      => isset( $sem['sol'] ) && 2 === $sem['sol']['nivel'] ? array( '☀️ Llamarada solar', $sem['sol']['texto'] ) : null,
			'geomag'   => isset( $sem['geomag'] ) && 2 === $sem['geomag']['nivel'] ? array( '🧲 Tormenta geomagnética', $sem['geomag']['texto'] ) : null,
			'seis'     => $seis && $seis['n'] >= 3 ? array( '📡 6 metros abierta', $seis['texto'] ) : null,
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
		'repetidoras' => function_exists( 'rau_get_repeaters_data' ) ? array_map( function( $r ) { return array( 'n' => $r['name'], 'c' => $r['callsign'], 'f' => $r['freq'], 'o' => $r['offset'], 't' => $r['tone'], 'lat' => $r['lat'], 'lon' => $r['lng'], 'url' => $r['url'], 'h' => $r['altura'] ?? 30 ); }, rau_get_repeaters_data() ) : array(),
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

/* ════════ 19.63.0: centro de mando ════════ */

/* Tendencias de clima espacial: Kp de 24 h y su pronóstico de 3 días, rayos X de 6 h, viento solar y Bz de 6 h, llamaradas de 7 días */
function rau_cond_tendencias() {
	$c = get_transient( 'rau_cond_tend' ); if ( is_array( $c ) ) return $c;
	$b = 'https://services.swpc.noaa.gov/'; $out = array( 'kp' => array(), 'kp_pron' => array(), 'rayosx' => array(), 'viento' => array(), 'llamaradas' => array() );
	list( $kp ) = rau_cond_traer( $b . 'products/noaa-planetary-k-index.json', 30 * MINUTE_IN_SECONDS );
	if ( is_array( $kp ) ) foreach ( $kp as $x ) { if ( ! is_array( $x ) || ! isset( $x['time_tag'] ) ) continue; $t = strtotime( $x['time_tag'] . ' UTC' ); if ( $t && $t >= time() - DAY_IN_SECONDS - 3 * HOUR_IN_SECONDS ) $out['kp'][] = array( $t, round( (float) $x['Kp'], 2 ) ); }
	list( $kf ) = rau_cond_traer( $b . 'products/noaa-planetary-k-index-forecast.json', HOUR_IN_SECONDS );
	if ( is_array( $kf ) ) foreach ( $kf as $x ) { if ( ! is_array( $x ) || 'predicted' !== ( $x['observed'] ?? '' ) ) continue; $t = strtotime( $x['time_tag'] . ' UTC' ); if ( $t && $t > time() - 3 * HOUR_IN_SECONDS ) $out['kp_pron'][] = array( $t, round( (float) $x['kp'], 2 ) ); }
	list( $xr ) = rau_cond_traer( $b . 'json/goes/primary/xrays-1-day.json', 5 * MINUTE_IN_SECONDS, 'json', 20 );
	if ( is_array( $xr ) ) { $grupo = array();
		foreach ( $xr as $x ) { if ( '0.1-0.8nm' !== ( $x['energy'] ?? '' ) || empty( $x['flux'] ) ) continue; $t = strtotime( $x['time_tag'] ); if ( $t < time() - 6 * HOUR_IN_SECONDS ) continue; $g = (int) floor( $t / 300 ) * 300; $grupo[ $g ] = max( $grupo[ $g ] ?? 0, (float) $x['flux'] ); }
		ksort( $grupo ); foreach ( $grupo as $t => $f ) $out['rayosx'][] = array( $t, $f ); }
	list( $vw ) = rau_cond_traer( $b . 'json/rtsw/rtsw_wind_1m.json', 5 * MINUTE_IN_SECONDS, 'json', 20 );
	list( $vm ) = rau_cond_traer( $b . 'json/rtsw/rtsw_mag_1m.json', 5 * MINUTE_IN_SECONDS, 'json', 20 );
	$serie = array();
	foreach ( array( array( $vw, 'proton_speed', 'v' ), array( $vm, 'bz_gsm', 'bz' ) ) as $par ) {
		if ( ! is_array( $par[0] ) ) continue;
		foreach ( $par[0] as $x ) { if ( empty( $x['active'] ) || empty( $x['time_tag'] ) || ! isset( $x[ $par[1] ] ) || null === $x[ $par[1] ] ) continue; $t = strtotime( $x['time_tag'] . ' UTC' ); if ( $t < time() - 6 * HOUR_IN_SECONDS ) continue;
			$g = (int) floor( $t / 600 ) * 600; $serie[ $g ][ $par[2] ][] = (float) $x[ $par[1] ]; }
	}
	ksort( $serie );
	foreach ( $serie as $t => $v ) $out['viento'][] = array( $t, isset( $v['v'] ) ? round( array_sum( $v['v'] ) / count( $v['v'] ) ) : null, isset( $v['bz'] ) ? round( array_sum( $v['bz'] ) / count( $v['bz'] ), 1 ) : null );
	list( $fl ) = rau_cond_traer( $b . 'json/goes/primary/xray-flares-7-day.json', 15 * MINUTE_IN_SECONDS );
	if ( is_array( $fl ) ) foreach ( $fl as $x ) { $cl = (string) ( $x['max_class'] ?? '' ); if ( ! preg_match( '/^[CMX]/', $cl ) ) continue; $out['llamaradas'][] = array( 't' => strtotime( $x['max_time'] ), 'clase' => $cl ); }
	usort( $out['llamaradas'], function( $a, $b ) { return $b['t'] - $a['t']; } ); $out['llamaradas'] = array_slice( $out['llamaradas'], 0, 12 );
	set_transient( 'rau_cond_tend', $out, 5 * MINUTE_IN_SECONDS );
	return $out;
}

/* Historial propio de las ionosondas de la región (no hay API pública de historial): cada 15 min, 50 horas */
function rau_cond_iono_grabar( $est = null ) {
	if ( null === $est ) list( $est ) = rau_cond_traer( 'https://prop.kc2g.com/api/stations.json', 10 * MINUTE_IN_SECONDS, 'json', 15 );
	if ( ! is_array( $est ) ) return 0;
	$h = get_option( 'rau_cond_ionoh', array() ); if ( ! is_array( $h ) ) $h = array(); $n = 0;
	foreach ( $est as $s ) {
		$code = $s['station']['code'] ?? ''; if ( ! $code || empty( $s['fof2'] ) || empty( $s['time'] ) ) continue;
		$lat = (float) $s['station']['latitude']; $lon = (float) $s['station']['longitude']; if ( $lon > 180 ) $lon -= 360;
		if ( $lat < -40 || $lat > 40 || $lon < -110 || $lon > -25 ) continue; // las Américas tropicales
		$t = strtotime( $s['time'] . ' UTC' ); if ( ! $t || time() - $t > 3 * HOUR_IN_SECONDS ) continue;
		$ult = ! empty( $h[ $code ] ) ? end( $h[ $code ] ) : null; if ( $ult && $ult[0] >= $t ) continue;
		$h[ $code ][] = array( $t, round( (float) $s['fof2'], 2 ), isset( $s['mufd'] ) ? round( (float) $s['mufd'], 1 ) : null ); $n++;
	}
	foreach ( $h as $code => $l ) { $h[ $code ] = array_values( array_filter( $l, function( $x ) { return $x[0] > time() - 50 * HOUR_IN_SECONDS; } ) ); if ( ! $h[ $code ] ) unset( $h[ $code ] ); }
	update_option( 'rau_cond_ionoh', $h, false );
	return $n;
}
/* Matriz de las próximas 24 horas: lo medido ayer a la misma hora (pronóstico por persistencia) */
function rau_cond_matriz( $code, $offset, $ahora = null ) {
	$ahora = $ahora ?: time(); $h = get_option( 'rau_cond_ionoh', array() ); $l = $h[ $code ] ?? array(); $out = array(); $con = 0;
	$base = (int) floor( $ahora / 3600 ) * 3600;
	for ( $k = 0; $k < 24; $k++ ) {
		$obj = $base + $k * 3600 - DAY_IN_SECONDS; $mejor = null;
		foreach ( $l as $x ) if ( abs( $x[0] - $obj ) <= 45 * 60 && ( ! $mejor || abs( $x[0] - $obj ) < abs( $mejor[0] - $obj ) ) ) $mejor = $x;
		if ( $mejor ) $con++;
		$out[] = array( 'h' => (int) gmdate( 'G', $base + $k * 3600 + $offset ), 'fof2' => $mejor ? $mejor[1] : null, 'mufd' => $mejor ? $mejor[2] : null );
	}
	$serie = array(); foreach ( $l as $x ) if ( $x[0] > $ahora - DAY_IN_SECONDS ) $serie[] = $x;
	return array( 'horas' => $out, 'completa' => $con >= 18, 'con_dato' => $con, 'serie' => $serie );
}

/* Absorción D-RAP sobre el mapa: solo las celdas de las Américas con pérdida (casi siempre ninguna) */
function rau_cond_drap_mapa() {
	list( $txt ) = rau_cond_traer( 'https://services.swpc.noaa.gov/text/drap_global_frequencies.txt', 5 * MINUTE_IN_SECONDS, 'texto' );
	if ( ! is_string( $txt ) ) return array();
	$lons = null; $out = array();
	foreach ( preg_split( '/\r?\n/', $txt ) as $l ) {
		if ( null === $lons && preg_match( '/^\s+-?\d+(\s+-?\d+){20,}\s*$/', $l ) ) { $lons = array_map( 'intval', preg_split( '/\s+/', trim( $l ) ) ); continue; }
		if ( ! $lons || ! preg_match( '/^\s*(-?\d+)\s*\|(.*)$/', $l, $m ) ) continue;
		$lat = (int) $m[1]; if ( $lat < -45 || $lat > 45 ) continue;
		foreach ( preg_split( '/\s+/', trim( $m[2] ) ) as $i => $f ) { $f = (float) $f; $lo = $lons[ $i ] ?? null; if ( $f >= 1 && null !== $lo && $lo >= -130 && $lo <= -10 ) $out[] = array( $lat, $lo, $f ); }
	}
	return $out;
}

/* 6 m abierta: el cluster DX y lo que reportó PSK Reporter en Colombia la última hora */
function rau_cond_seis_todo() {
	$cl = rau_cond_seis(); $n = $cl ? $cl['n'] : 0; $ej = $cl ? $cl['ejemplos'] : array(); $psk = 0; $km = 0;
	if ( function_exists( 'rau_prop_datos' ) ) { $p = rau_prop_datos(); if ( ! empty( $p['bandas']['6 m'] ) ) { $psk = (int) $p['bandas']['6 m']['n']; $km = (int) $p['bandas']['6 m']['max']; } }
	$total = $n + $psk;
	if ( null === $cl && ! function_exists( 'rau_prop_datos' ) ) return null;
	$texto = $total ? ( $psk ? $psk . ' enlace' . ( 1 === $psk ? '' : 's' ) . ' en 6 m desde Colombia en la última hora (PSK Reporter' . ( $km ? ', hasta ' . number_format( $km, 0, ',', '.' ) . ' km' : '' ) . ')' : '' ) . ( $psk && $n ? ' y ' : '' ) . ( $n ? $n . ' reporte' . ( 1 === $n ? '' : 's' ) . ' en el cluster' : '' ) . '.' : 'Sin actividad en 6 m reportada ahora.';
	return array( 'n' => $total, 'psk' => $psk, 'cluster' => $n, 'km' => $km, 'ejemplos' => $ej, 'texto' => $texto );
}

/* Avisos de NOAA en español (los mensajes tienen formas fijas) */
function rau_cond_traducir( $t ) {
	$r = array(
		'/^(ALERT|WARNING|WATCH|SUMMARY|EXTENDED WARNING):\s*/i' => function( $m ) { $x = array( 'ALERT' => 'Alerta', 'WARNING' => 'Advertencia', 'WATCH' => 'Vigilancia', 'SUMMARY' => 'Resumen', 'EXTENDED WARNING' => 'Advertencia extendida' ); return ( $x[ strtoupper( $m[1] ) ] ?? $m[1] ) . ': '; },
	);
	$out = preg_replace_callback( array_keys( $r )[0], $r[ array_keys( $r )[0] ], trim( $t ) );
	$frases = array(
		'/Geomagnetic K-index of (\d) expected/i' => 'se espera un índice K geomagnético de $1',
		'/Geomagnetic K-index of (\d)/i' => 'índice K geomagnético de $1',
		'/Geomagnetic Storm Category (G\d) Predicted/i' => 'se pronostica una tormenta geomagnética $1',
		'/Geomagnetic Sudden Impulse expected/i' => 'se espera un impulso geomagnético repentino (llegada de una eyección solar)',
		'/Geomagnetic Sudden Impulse/i' => 'impulso geomagnético repentino (llegó una eyección solar)',
		'/X-ray Flux exceeded (M\d|X\d)/i' => 'el flujo de rayos X superó $1 (llamarada)',
		'/X-ray Event exceeded (M\d|X\d)/i' => 'evento de rayos X por encima de $1 (llamarada)',
		'/Type II Radio Emission/i' => 'emisión de radio tipo II (posible eyección de masa coronal)',
		'/Type IV Radio Emission/i' => 'emisión de radio tipo IV (llamarada fuerte con eyección)',
		'/10cm Radio Burst/i' => 'ráfaga de radio en 10 cm',
		'/Electron 2MeV Integral Flux exceeded 1000pfu/i' => 'flujo de electrones de 2 MeV por encima de 1000 pfu (afecta satélites)',
		'/Proton 10MeV Integral Flux above 10pfu expected/i' => 'se espera una tormenta de protones (más de 10 pfu a 10 MeV)',
		'/Proton (Event )?10MeV Integral Flux exceeded 10pfu/i' => 'tormenta de protones: más de 10 pfu a 10 MeV (afecta HF polar)',
	);
	$trad = preg_replace( array_keys( $frases ), array_values( $frases ), $out );
	return $trad;
}

/* Registro de eventos: lo que pasó en 24 h y lo que viene en 12 h, ordenado */
function rau_cond_eventos( $d ) {
	$e = array(); $t0 = time() - DAY_IN_SECONDS;
	foreach ( (array) ( $d['tend']['llamaradas'] ?? array() ) as $f ) if ( $f['t'] >= $t0 ) $e[] = array( 't' => $f['t'], 'tipo' => 'sol', 'nivel' => 'X' === $f['clase'][0] ? 2 : ( 'M' === $f['clase'][0] ? 1 : 0 ), 'texto' => 'Llamarada solar ' . $f['clase'] );
	foreach ( (array) ( $d['espacio']['alertas'] ?? array() ) as $a ) $e[] = array( 't' => strtotime( $a['hora'] ), 'tipo' => 'noaa', 'nivel' => 'ALERT' === $a['tipo'] ? 1 : 0, 'texto' => 'NOAA · ' . ( $a['es'] ?? $a['texto'] ) );
	$prev = null; foreach ( (array) ( $d['tend']['kp'] ?? array() ) as $k ) { if ( $k[0] >= $t0 && $k[1] >= 4 && ( null === $prev || $prev < 4 ) ) $e[] = array( 't' => $k[0], 'tipo' => 'geomag', 'nivel' => $k[1] >= 5 ? 2 : 1, 'texto' => 'Kp subió a ' . number_format( $k[1], 1, ',', '' ) ); $prev = $k[1]; }
	foreach ( (array) ( $d['sismos'] ?? array() ) as $s ) if ( $s['t'] >= $t0 ) $e[] = array( 't' => $s['t'], 'tipo' => 'sismo', 'nivel' => $s['mag'] >= 5 ? 2 : 1, 'texto' => 'Sismo M' . number_format( $s['mag'], 1, ',', '' ) . ' · ' . $s['lugar'] . ' (a ' . $s['km'] . ' km)' );
	if ( ! empty( $d['clima']['horas'] ) ) { $off = (int) $d['clima']['offset']; $ant = false;
		foreach ( array_slice( $d['clima']['horas'], 0, 12 ) as $h ) { $tor = in_array( (int) $h['cod'], array( 95, 96, 99 ), true );
			if ( $tor && ! $ant ) $e[] = array( 't' => strtotime( $h['t'] . ':00 UTC' ) - $off, 'tipo' => 'tormenta', 'nivel' => 2, 'texto' => 'Tormenta eléctrica prevista', 'futuro' => true );
			$ant = $tor; } }
	usort( $e, function( $a, $b ) { return $b['t'] - $a['t']; } );
	return array_slice( $e, 0, 20 );
}

/* Sitios RAU: tiempo, tormenta y viento en cada repetidora, en una sola tabla */
function rau_cond_sitios() {
	$c = get_transient( 'rau_cond_sitios' ); if ( is_array( $c ) ) return $c;
	$out = array();
	foreach ( function_exists( 'rau_get_repeaters_data' ) ? rau_get_repeaters_data() : array() as $r ) {
		list( $la, $lo ) = rau_cond_redondear( $r['lat'], $r['lng'] ); $cl = rau_cond_clima( $la, $lo ); $sem = rau_cond_semaforo( $cl, null, null );
		$out[] = array( 'n' => $r['name'], 'c' => $r['callsign'], 'f' => $r['freq'], 'lat' => $r['lat'], 'lon' => $r['lng'], 'estado' => $r['status'], 'url' => $r['url'],
			'temp' => $cl ? $cl['actual']['temp'] : null, 'cod' => $cl ? $cl['actual']['cod'] : null, 'lluvia' => $cl ? (int) max( array_map( function( $h ) { return (int) $h['pp']; }, array_slice( $cl['horas'], 0, 3 ) ) ) : null,
			'tormenta' => $sem['tormenta'] ?? null, 'viento' => $sem['viento'] ?? null );
	}
	set_transient( 'rau_cond_sitios', $out, 10 * MINUTE_IN_SECONDS );
	return $out;
}

/* Precarga cada 10 minutos: las ciudades más consultadas y los sitios, para que nadie espere a las fuentes */
function rau_cond_precargar() {
	foreach ( array( RAU_COND_PUNTO, array( 4.711, -74.0721 ), array( 3.4516, -76.532 ), array( 10.9685, -74.7813 ), array( 7.1193, -73.1227 ) ) as $p ) {
		list( $la, $lo ) = rau_cond_redondear( $p[0], $p[1] ); delete_transient( 'rau_cond_todo_' . md5( $la . ',' . $lo ) ); rau_condiciones( $la, $lo );
	}
	delete_transient( 'rau_cond_sitios' ); rau_cond_sitios();
	rau_cond_iono_grabar();
}
add_action( 'init', function() { if ( ! wp_next_scheduled( 'rau_cond_precarga' ) ) wp_schedule_event( time() + 300, 'rau_10min', 'rau_cond_precarga' ); } );
add_action( 'rau_cond_precarga', 'rau_cond_precargar' );

add_action( 'rest_api_init', function() {
	register_rest_route( 'rau/v1', '/condiciones/sitios', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function() { $r = new WP_REST_Response( rau_cond_sitios() ); $r->header( 'Cache-Control', 'public, max-age=300' ); return $r; } ) );
	register_rest_route( 'rau/v1', '/condiciones/salud', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function() { return rau_cond_salud(); } ) );
	register_rest_route( 'rau/v1', '/condiciones/avisos/mios', array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function( WP_REST_Request $r ) {
		$ep = (string) ( $r->get_json_params()['endpoint'] ?? '' ); $out = array();
		foreach ( (array) get_option( 'rau_cond_avisos', array() ) as $id => $a ) if ( $ep && hash_equals( $a['sub']['endpoint'], $ep ) ) $out[] = array( 'id' => $id, 'nombre' => $a['nombre'] ?? '', 'lat' => $a['lat'], 'lon' => $a['lon'], 'temas' => $a['temas'], 'umbral' => $a['umbral'] ?? 60 );
		return $out;
	} ) );
	register_rest_route( 'rau/v1', '/condiciones/avisos/prueba', array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function( WP_REST_Request $r ) {
		$ep = (string) ( $r->get_json_params()['endpoint'] ?? '' );
		if ( get_transient( 'rau_cond_prueba_' . md5( $ep ) ) ) return new WP_REST_Response( array( 'error' => 'Ya mandamos una prueba hace poco. Espera un minuto.' ), 429 );
		foreach ( (array) get_option( 'rau_cond_avisos', array() ) as $a ) if ( $ep && hash_equals( $a['sub']['endpoint'], $ep ) ) {
			set_transient( 'rau_cond_prueba_' . md5( $ep ), 1, MINUTE_IN_SECONDS );
			$ok = function_exists( 'rau_send_web_push' ) && rau_send_web_push( $a['sub'], '🔔 Prueba de avisos de Condiciones', 'Si ves esto, los avisos de ' . ( $a['nombre'] ?: 'tu punto' ) . ' funcionan.', home_url( '/condiciones/' ) );
			return array( 'ok' => (bool) $ok );
		}
		return new WP_REST_Response( array( 'error' => 'No hay avisos activos en este navegador.' ), 404 );
	} ) );
} );
