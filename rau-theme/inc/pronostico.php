<?php
/**
 * Pronóstico de propagación para Colombia con mediciones, no con reglas:
 *  · foF2 y MUF(3000) de la ionosonda más cercana con datos recientes (red GIRO, vía la API pública de
 *    prop.kc2g.com). foF2 es la frecuencia más alta que vuelve al suelo en incidencia vertical: la de NVIS.
 *  · Flujo solar (10,7 cm) y Kp de NOAA SWPC (services.swpc.noaa.gov), que explican el resto.
 * Se guarda 15 minutos. Se muestra junto al mapa de propagación (lo que realmente se oye) y en el centro
 * de mando, en lugar de la regla fija de día y de noche cuando hay medición.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* Ionosondas: la más cercana a Medellín con medición de menos de 3 horas y confianza ≥ 50 */
/* $aqui: otro punto en vez de Medellín (Condiciones, 19.62.0); $d: la lista ya descargada, para no pedirla otra vez */
function rau_pron_ionosonda( $ahora = null, $aqui = null, $d = null ) {
	$ahora = $ahora ?: time();
	if ( null === $d ) {
		$r = rau_remote_get( 'https://prop.kc2g.com/api/stations.json', array( 'timeout' => 15, 'user-agent' => 'HK4RAU/1.0 (info@hk4rau.com)' ), 10 );
		if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) return null;
		$d = json_decode( wp_remote_retrieve_body( $r ), true );
	}
	if ( ! is_array( $d ) ) return null;
	$aqui = $aqui ?: array( 6.2442, -75.5812 ); $mejor = null;
	foreach ( $d as $s ) {
		if ( empty( $s['fof2'] ) || empty( $s['time'] ) || ! isset( $s['station']['latitude'] ) ) continue;
		$t = strtotime( $s['time'] . ' UTC' ); $cs = (float) ( $s['cs'] ?? 0 );
		if ( ! $t || $ahora - $t > 3 * HOUR_IN_SECONDS || $cs < 50 ) continue;
		$lat = (float) $s['station']['latitude']; $lng = (float) $s['station']['longitude']; if ( $lng > 180 ) $lng -= 360;
		$km = function_exists( 'rau_prop_km' ) ? rau_prop_km( $aqui, array( $lat, $lng ) ) : 0;
		if ( $km > 4000 ) continue;
		if ( ! $mejor || $km < $mejor['km'] ) $mejor = array( 'nombre' => $s['station']['name'], 'km' => $km, 'fof2' => round( (float) $s['fof2'], 2 ),
			'mufd' => isset( $s['mufd'] ) ? round( (float) $s['mufd'], 1 ) : null, 'hace' => (int) round( ( $ahora - $t ) / 60 ), 'confianza' => (int) $cs );
	}
	return $mejor;
}
function rau_pron_solar() {
	$out = array( 'sfi' => null, 'kp' => null );
	$r = rau_remote_get( 'https://services.swpc.noaa.gov/json/f107_cm_flux.json', array( 'timeout' => 15 ), 10 );
	if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
		$d = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( is_array( $d ) && isset( $d[0]['flux'] ) ) $out['sfi'] = (int) round( $d[0]['flux'] );
	}
	$r = rau_remote_get( 'https://services.swpc.noaa.gov/products/noaa-planetary-k-index.json', array( 'timeout' => 15 ), 10 );
	if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
		$d = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( is_array( $d ) && $d ) { $u = end( $d ); $out['kp'] = is_array( $u ) ? round( (float) ( $u['Kp'] ?? ( $u[1] ?? 0 ) ), 1 ) : null; }
	}
	return $out;
}
/* Bandas de radioaficionado en Colombia para cada uso (MHz, centro aproximado) */
function rau_pron_bandas() { return array( '160 m' => 1.85, '80 m' => 3.7, '40 m' => 7.1, '30 m' => 10.12, '20 m' => 14.2, '17 m' => 18.1, '15 m' => 21.2, '12 m' => 24.9, '10 m' => 28.5 ); }
/* Lo que se deduce: banda NVIS (≤ 0,9·foF2, con la absorción diurna de 80 y 160 m) y bandas para DX (≤ 0,85·MUF(3000)) */
function rau_pron_calcular( $iono, $solar, $hora_local ) {
	$res = array( 'iono' => $iono, 'sfi' => $solar['sfi'], 'kp' => $solar['kp'], 'nvis' => null, 'dx' => array(), 'notas' => array() );
	$dia = $hora_local >= 7 && $hora_local < 18;
	if ( $iono ) {
		$lim = 0.9 * $iono['fof2'];
		$nvis = null;
		foreach ( rau_pron_bandas() as $b => $f ) { if ( $f > 10.2 ) break; if ( $f <= $lim ) $nvis = $b; }
		// De día la capa D absorbe 160 y 80 m: si alcanza 40 m, se prefiere 40 m
		if ( $nvis ) {
			$txt = sprintf( 'foF2 %s MHz: las señales hasta unos %s MHz vuelven en vertical (0 a 400 km).', number_format( $iono['fof2'], 1, ',', '' ), number_format( $lim, 1, ',', '' ) );
			if ( $dia && in_array( $nvis, array( '160 m', '80 m' ), true ) ) $txt .= ' De día 80 m pierde señal por absorción: usa potencia y antena horizontal baja.';
			$res['nvis'] = array( 'banda' => $nvis, 'texto' => $txt );
		} else $res['nvis'] = array( 'banda' => null, 'texto' => 'foF2 muy baja (' . number_format( $iono['fof2'], 1, ',', '' ) . ' MHz): ni 160 m vuelve en vertical. Para contactos cercanos, VHF o repetidoras.' );
		if ( $iono['mufd'] ) foreach ( rau_pron_bandas() as $b => $f ) if ( $f <= 0.85 * $iono['mufd'] && $f >= 7 ) $res['dx'][] = $b;
	}
	if ( null !== $solar['kp'] && $solar['kp'] >= 5 ) $res['notas'][] = 'Tormenta geomagnética (Kp ' . number_format( $solar['kp'], 1, ',', '' ) . '): la propagación puede caer de golpe, sobre todo en las bandas altas.';
	elseif ( null !== $solar['kp'] && $solar['kp'] >= 4 ) $res['notas'][] = 'Campo geomagnético activo (Kp ' . number_format( $solar['kp'], 1, ',', '' ) . '): posibles altibajos.';
	return $res;
}
/* NVIS medido: enlaces cortos (hasta 600 km) reportados en la última hora en las bandas bajas, desde los
   datos de PSK Reporter que ya trae inc/propagacion.php. Lo que de verdad está pasando dentro del país,
   frente a la estimación con una ionosonda lejana. */
function rau_pron_nvis_medido( $reg = null, $ahora = null ) {
	$ahora = $ahora ?: time();
	if ( null === $reg ) { $p = get_option( 'rau_prop_co', array() ); $reg = is_array( $p ) && isset( $p['r'] ) ? $p['r'] : array(); }
	$bajas = array( '160 m', '80 m', '60 m', '40 m', '30 m' ); $por = array(); $ej = array(); $n = 0;
	foreach ( $reg as $x ) {
		if ( ! in_array( $x['b'], $bajas, true ) || $x['t'] < $ahora - HOUR_IN_SECONDS || empty( $x['cp'] ) || empty( $x['op'] ) ) continue;
		$km = function_exists( 'rau_prop_km' ) ? rau_prop_km( $x['cp'], $x['op'] ) : 9999;
		if ( $km > 600 || $km < 5 ) continue;
		$por[ $x['b'] ] = ( $por[ $x['b'] ] ?? 0 ) + 1; $n++;
		if ( ! isset( $ej[ $x['b'] ] ) || $km > $ej[ $x['b'] ]['km'] ) $ej[ $x['b'] ] = array( 'a' => $x['c'], 'b' => $x['o'], 'km' => $km );
	}
	if ( ! $n ) return array( 'n' => 0, 'banda' => null, 'por_banda' => array(), 'ejemplo' => null );
	arsort( $por ); $banda = key( $por );
	return array( 'n' => $n, 'banda' => $por[ $banda ] >= 2 ? $banda : null, 'por_banda' => $por, 'ejemplo' => $ej[ $banda ] );
}
function rau_pron_nvis_medido_texto( $m ) {
	if ( ! $m || ! $m['n'] ) return '';
	$partes = array(); foreach ( $m['por_banda'] as $b => $k ) $partes[] = $b . ' (' . $k . ')';
	$t = 'Medido en la última hora: ' . implode( ', ', $partes ) . ' enlace' . ( 1 === $m['n'] ? '' : 's' ) . ' de hasta 600 km reportados desde Colombia';
	if ( $m['ejemplo'] ) $t .= ', por ejemplo ' . $m['ejemplo']['a'] . ' ↔ ' . $m['ejemplo']['b'] . ' a ' . number_format( $m['ejemplo']['km'], 0, ',', '.' ) . ' km';
	return $t . '.';
}
function rau_pronostico( $forzar = false ) {
	if ( ! $forzar ) { $c = get_transient( 'rau_pronostico' ); if ( false !== $c ) return $c; }
	$iono = rau_pron_ionosonda(); $solar = rau_pron_solar();
	$hora = (int) gmdate( 'G', time() - 5 * HOUR_IN_SECONDS );
	$res = rau_pron_calcular( $iono, $solar, $hora );
	$res['medido'] = rau_pron_nvis_medido(); $res['medido']['texto'] = rau_pron_nvis_medido_texto( $res['medido'] );
	$res['actualizado'] = time();
	$res['fuente'] = 'Ionosonda ' . ( $iono ? $iono['nombre'] . ' (red GIRO, vía prop.kc2g.com)' : 'sin datos recientes' ) . ' · flujo solar y Kp de NOAA SWPC';
	set_transient( 'rau_pronostico', $res, ( $iono ? 15 : 5 ) * MINUTE_IN_SECONDS );
	return $res;
}
