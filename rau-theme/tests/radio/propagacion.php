<?php
/**
 * Propagación de Colombia (inc/propagacion.php), dentro de WordPress, sin salir a internet.
 *   php tests/radio/propagacion.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php propagacion.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
$t = time();
$rep = function( $s, $sl, $sd, $r, $rl, $rd, $f, $snr = -10, $hace = 60 ) use ( $t ) {
	return sprintf( '<receptionReport receiverCallsign="%s" receiverLocator="%s" senderCallsign="%s" senderLocator="%s" frequency="%d" flowStartSeconds="%d" mode="FT8" sNR="%d" senderDXCC="%s" receiverDXCC="%s" />', $r, $rl, $s, $sl, $f, $t - $hace, $snr, $sd, $rd );
};
$xml = '<?xml version="1.0"?><receptionReports>'
	. $rep( 'HK4XYZ', 'FJ26', 'Colombia', 'EA4ABC', 'IN80do', 'Spain', 14074500, -12 )      // oído en Europa
	. $rep( 'K1ABC', 'FN42', 'United States', 'HK3QQ', 'FJ24', 'Colombia', 14075100, -3 )   // oído aquí
	. $rep( 'HK4XYZ', 'FJ26', 'Colombia', 'HK6AA', 'FJ14', 'Colombia', 7074000, 5 )          // dentro del país
	. $rep( 'K1ABC', 'FN42', 'United States', 'VE3X', 'FN03', 'Canada', 14074000 )           // nada de Colombia
	. $rep( 'HK0A', 'EK92', 'San Andres & Providencia', 'W4Z', 'EM73', 'United States', 21074000 )
	. $rep( 'HK4OLD', 'FJ26', 'Colombia', 'G4A', 'IO91', 'England', 14074000, 0, 7200 )      // vieja: fuera de la hora
	. $rep( 'HK4XYZ', 'FJ26', 'Colombia', 'EA4ABC', 'IN80do', 'Spain', 9999000 )             // fuera de banda
	. '</receptionReports>';
$urls = array(); $caida = false;
add_filter( 'pre_http_request', function( $pre, $args, $url ) use ( $xml, &$urls, &$caida ) {
	if ( false === strpos( $url, 'pskreporter.info' ) ) return $pre;
	if ( $caida ) return new WP_Error( 'x', 'caída' );
	$urls[] = $url;
	return array( 'headers' => array(), 'body' => false !== strpos( $url, 'callsign=FJ' ) ? $xml : '<receptionReports/>', 'response' => array( 'code' => 200, 'message' => '' ), 'cookies' => array() );
}, 10, 3 );
$antes = get_option( 'rau_prop_co' ); delete_option( 'rau_prop_co' );
global $wpdb; $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%rau_caida_%'" );

rau_prop_actualizar();
ok( 4 === count( rau_prop_datos()['enlaces'] ), 'se guardan solo los enlaces con un lado en Colombia, en banda y de la última hora' );
ok( 2 === count( $urls ) && false !== strpos( $urls[0], 'appcontact=' ) && false !== strpos( $urls[1], 'callsign=FK' ), 'dos consultas (FJ y FK) identificando la aplicación' );
$d = rau_prop_datos();
ok( array( '20 m', '40 m', '15 m' ) == array_keys( $d['bandas'] ) || 3 === count( $d['bandas'] ), 'tres bandas: 20, 40 y 15 m' );
ok( 1 === $d['bandas']['20 m']['tx'] && 1 === $d['bandas']['20 m']['rx'], '20 m: uno oído afuera y uno oído aquí' );
ok( isset( $d['bandas']['20 m']['reg']['Europa'] ) && isset( $d['bandas']['20 m']['reg']['Norteamérica'] ), 'regiones: Europa y Norteamérica' );
ok( isset( $d['bandas']['40 m']['reg']['Colombia'] ), 'un enlace entre dos colombianos cuenta como nacional' );
ok( isset( $d['bandas']['15 m'] ), 'San Andrés y Providencia cuenta como Colombia' );
$e = array_values( array_filter( $d['enlaces'], function( $x ) { return 'EA4ABC' === $x['o']; } ) )[0];
ok( $e['km'] > 7000 && $e['km'] < 8500 && -12 === $e['s'], 'distancia Medellín–Madrid y SNR correctos (' . $e['km'] . ' km)' );
// Una segunda consulta con la fuente caída conserva lo anterior
$caida = true;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%rau_caida_%'" );
rau_prop_actualizar(); $d2 = rau_prop_datos();
ok( 4 === count( $d2['enlaces'] ) && '' !== $d2['error'], 'si la fuente cae se conserva la última hora y se informa el error' );
$r = rest_do_request( new WP_REST_Request( 'GET', '/rau/v1/propagacion' ) );
ok( 200 === $r->get_status() && false !== strpos( $r->get_headers()['Cache-Control'] ?? '', 'max-age=120' ), 'endpoint público con caché de 2 minutos' );
$al = rau_em_alertas_publicas();
ok( isset( $al['prop']['bandas']['20 m'] ) && ! isset( $al['prop']['enlaces'] ), 'el centro de mando recibe el resumen por banda, sin la lista de enlaces' );

if ( false === $antes ) delete_option( 'rau_prop_co' ); else update_option( 'rau_prop_co', $antes, false );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%rau_caida_%'" );
echo "\n$n comprobaciones, $fallos fallas\n";
exit( $fallos ? 1 : 0 );
