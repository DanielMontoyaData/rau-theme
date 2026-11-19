<?php
/**
 * Pronóstico (inc/pronostico.php) con respuestas fijas de las fuentes: sin internet.
 *   php tests/radio/pronostico.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php pronostico.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
$hace = function( $min ) { return gmdate( 'Y-m-d\TH:i:s', time() - 60 * $min ); };
$est = function( $nombre, $lat, $lng, $fof2, $mufd, $min, $cs ) use ( $hace ) { return array( 'fof2' => $fof2, 'mufd' => $mufd, 'cs' => $cs, 'time' => $hace( $min ), 'station' => array( 'name' => $nombre, 'latitude' => (string) $lat, 'longitude' => (string) $lng ) ); };
$estaciones = array(
	$est( 'Ramey, Puerto Rico', 18.5, 292.9, 12.5, 40.9, 60 * 24 * 900, 0 ),   // vieja: no sirve
	$est( 'Boa Vista, Brazil', 2.8, 299.3, 8.6, 27.0, 20, 75 ),
	$est( 'Austin, TX, USA', 30.4, 262.3, 9.0, 30.0, 10, 100 ),                 // más lejos
	$est( 'Jicamarca, Peru', -12, 283.2, null, null, 10, 90 ),                   // sin foF2
);
add_filter( 'pre_http_request', function( $pre, $args, $url ) use ( &$estaciones ) {
	$r = function( $b ) { return array( 'headers' => array(), 'body' => wp_json_encode( $b ), 'response' => array( 'code' => 200, 'message' => '' ), 'cookies' => array() ); };
	if ( false !== strpos( $url, 'prop.kc2g.com' ) ) return $r( $estaciones );
	if ( false !== strpos( $url, 'f107_cm_flux' ) ) return $r( array( array( 'flux' => 142.0 ) ) );
	if ( false !== strpos( $url, 'planetary-k-index' ) ) return $r( array( array( 'time_tag' => 'x', 'Kp' => 2.0 ), array( 'time_tag' => 'y', 'Kp' => 5.33 ) ) );
	return $pre;
}, 10, 3 );
global $wpdb; $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%rau_caida_%' OR option_name LIKE '_transient%rau_pronostico%'" );

$i = rau_pron_ionosonda();
ok( $i && 'Boa Vista, Brazil' === $i['nombre'] && 8.6 === $i['fof2'] && abs( $i['km'] - 1690 ) < 30, 'la ionosonda más cercana con datos recientes: Boa Vista, a ' . ( $i['km'] ?? '?' ) . ' km' );
$s = rau_pron_solar();
ok( 142 === $s['sfi'] && 5.3 === $s['kp'], 'flujo solar y el último Kp de NOAA' );
$p = rau_pron_calcular( $i, $s, 10 );
ok( '40 m' === $p['nvis']['banda'], 'foF2 8,6 MHz de día: NVIS en 40 m' );
ok( array( '40 m', '30 m', '20 m', '17 m', '15 m' ) === $p['dx'], 'MUF(3000) 27 MHz: de 40 a 15 m abiertas para DX (hasta 0,85·MUF)' );
ok( 1 === count( $p['notas'] ) && false !== strpos( $p['notas'][0], 'Tormenta' ), 'Kp 5,3: aviso de tormenta geomagnética' );
$p2 = rau_pron_calcular( array_merge( $i, array( 'fof2' => 4.4 ) ), array( 'sfi' => 90, 'kp' => 1 ), 13 );
ok( '80 m' === $p2['nvis']['banda'] && false !== strpos( $p2['nvis']['texto'], 'absorción' ), 'foF2 4,4 de día: 80 m, con el aviso de absorción' );
$p3 = rau_pron_calcular( array_merge( $i, array( 'fof2' => 1.7 ) ), array( 'sfi' => 70, 'kp' => 1 ), 3 );
ok( null === $p3['nvis']['banda'] && false !== strpos( $p3['nvis']['texto'], 'VHF' ), 'foF2 muy baja: ninguna banda de HF vuelve en vertical' );
$estaciones = array( $est( 'Boa Vista, Brazil', 2.8, 299.3, 8.6, 27.0, 400, 75 ) );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%rau_caida_%'" );
ok( null === rau_pron_ionosonda(), 'una medición de hace más de 3 horas no se usa' );
$pr = rau_pronostico( true );
ok( null === $pr['iono'] && null === $pr['nvis'] && 142 === $pr['sfi'], 'sin ionosonda: el pronóstico no inventa banda, pero trae el flujo solar' );
// NVIS medido con enlaces cortos de PSK Reporter
$ah = time();
$reg = array(
	array( 'c' => 'HK4AAA', 'cp' => array( 6.25, -75.58 ), 'o' => 'HK3BBB', 'op' => array( 4.6, -74.08 ), 'b' => '40 m', 't' => $ah - 300 ),   // Medellín–Bogotá, 245 km
	array( 'c' => 'HK4AAA', 'cp' => array( 6.25, -75.58 ), 'o' => 'HK6CCC', 'op' => array( 3.45, -76.53 ), 'b' => '40 m', 't' => $ah - 900 ),   // Medellín–Cali, 328 km
	array( 'c' => 'HK1DDD', 'cp' => array( 10.4, -75.5 ), 'o' => 'HK4EEE', 'op' => array( 6.25, -75.58 ), 'b' => '80 m', 't' => $ah - 600 ),    // Cartagena–Medellín, 461 km
	array( 'c' => 'HK4AAA', 'cp' => array( 6.25, -75.58 ), 'o' => 'W1AW', 'op' => array( 41.7, -72.7 ), 'b' => '40 m', 't' => $ah - 60 ),      // DX: no cuenta
	array( 'c' => 'HK4AAA', 'cp' => array( 6.25, -75.58 ), 'o' => 'HK3FFF', 'op' => array( 4.6, -74.08 ), 'b' => '20 m', 't' => $ah - 60 ),    // 20 m: no es NVIS
	array( 'c' => 'HK4AAA', 'cp' => array( 6.25, -75.58 ), 'o' => 'HK3GGG', 'op' => array( 4.6, -74.08 ), 'b' => '40 m', 't' => $ah - 7200 ),  // vieja
);
$m = rau_pron_nvis_medido( $reg, $ah );
ok( 3 === $m['n'] && '40 m' === $m['banda'] && 2 === $m['por_banda']['40 m'] && 1 === $m['por_banda']['80 m'], 'NVIS medido: cuenta solo enlaces cortos, recientes y en bandas bajas' );
ok( 'HK6CCC' === $m['ejemplo']['b'] && $m['ejemplo']['km'] > 300 && $m['ejemplo']['km'] < 360, 'trae el enlace más largo de la banda como ejemplo' );
$tx = rau_pron_nvis_medido_texto( $m );
ok( false !== strpos( $tx, '40 m (2), 80 m (1)' ) && false !== strpos( $tx, 'HK4AAA ↔ HK6CCC' ), 'texto: ' . $tx );
$m1 = rau_pron_nvis_medido( array_slice( $reg, 2, 1 ), $ah );
ok( 1 === $m1['n'] && null === $m1['banda'], 'con un solo enlace no se declara banda' );
ok( '' === rau_pron_nvis_medido_texto( rau_pron_nvis_medido( array(), $ah ) ), 'sin enlaces, sin texto' );
delete_transient( 'rau_pronostico' );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%rau_caida_%'" );
echo "\n$n comprobaciones, $fallos fallas\n";
exit( $fallos ? 1 : 0 );
