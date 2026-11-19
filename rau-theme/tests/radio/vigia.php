<?php
/**
 * «Avísame si aparece» con la página cerrada (inc/vigia-receptor.php).
 *   php tests/radio/vigia.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php vigia.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
$prev = array( get_option( RAU_VIGIA_OPCION ), get_transient( 'rau_sdr_oido' ), get_option( 'rau_prop_co' ) );
$ah = time();
$sub = function( $id ) { return array( 'endpoint' => 'https://fcm.googleapis.com/fcm/send/' . $id, 'keys' => array( 'p256dh' => str_repeat( 'B', 87 ), 'auth' => str_repeat( 'a', 22 ) ) ); };

ok( array( '3Y0J', 'VK9', '10M' ) === rau_vigia_patrones( array( '3y0j', ' VK9 ', '10m', 'x', '11M', '3Y0J', '<b>' ) ), 'patrones: mayúsculas, sin repetidos, bandas válidas y al menos 2 letras' );
ok( 10 === count( rau_vigia_patrones( range( 10, 40 ) ) ) || 10 >= count( rau_vigia_patrones( array_map( function( $i ) { return 'HK' . $i; }, range( 1, 30 ) ) ) ), 'como mucho 10' );

// Alta por la API: valida la suscripción y guarda los patrones
delete_option( RAU_VIGIA_OPCION );
$r = new WP_REST_Request( 'POST', '/rau/v1/vigia' ); $r->set_header( 'Content-Type', 'application/json' );
$r->set_body( wp_json_encode( array( 'sub' => array( 'endpoint' => 'https://evil.example/x', 'keys' => $sub( 'x' )['keys'] ), 'patrones' => array( 'VK9' ) ) ) );
$res = rest_do_request( $r );
ok( 400 === $res->get_status() || 503 === $res->get_status(), 'rechaza un endpoint que no es de un servicio de push' );
delete_transient( 'rau_vigia_' . md5( '' . wp_salt( 'nonce' ) ) );
$r->set_body( wp_json_encode( array( 'sub' => $sub( 'uno' ), 'patrones' => array( 'vk9', '20m' ) ) ) );
$res = rest_do_request( $r );
if ( 503 === $res->get_status() ) { echo "(sin llaves VAPID en este sitio: se arma la lista a mano)\n"; update_option( RAU_VIGIA_OPCION, array( $sub( 'uno' ) + array( 'p' => array( 'VK9', '20M' ), 'creado' => $ah, 'av' => array() ) ) ); }
else ok( 200 === $res->get_status() && array( 'VK9', '20M' ) === $res->get_data()['patrones'], 'alta con sus patrones' );
$subs = rau_vigia_subs();
$subs[] = $sub( 'dos' ) + array( 'p' => array( 'EA' ), 'creado' => $ah, 'av' => array() );
$subs[] = $sub( 'vieja' ) + array( 'p' => array( 'VK9' ), 'creado' => $ah - 31 * DAY_IN_SECONDS, 'av' => array() );
$subs[] = $sub( 'muerta' ) + array( 'p' => array( 'VK9' ), 'creado' => $ah, 'av' => array() );
rau_vigia_guardar( $subs );

// Lo que se oye: el receptor (rau_sdr_oido) y los enlaces de Colombia (rau_prop_co)
$rep = function( $c, $b, $f ) use ( $ah ) { return array( 'c' => $c, 'b' => $b, 'f' => $f, 'm' => 'FT8', 'snr' => -12, 't' => $ah - 120 ); };
set_transient( 'rau_sdr_oido', array( 'reportes' => array( $rep( 'VK9XY', '20m', 14074.5 ), $rep( 'K1AA', '20m', 14075 ), $rep( 'W2BB', '20m', 14076 ), $rep( 'JA1CC', '15m', 21074 ) ), 'bandas' => array() ), 300 );
update_option( 'rau_prop_co', array( 'act' => $ah, 'r' => array( array( 'c' => 'HK4ABC', 'o' => 'EA7XYZ', 'b' => '40 m', 'm' => 'FT8', 'x' => 1, 't' => $ah - 300, 'cp' => array( 6, -75 ), 'op' => array( 37, -6 ) ) ) ), false );
$enviados = array();
$enviar = function( $s, $t, $c, $url ) use ( &$enviados ) { $enviados[] = array( basename( $s['endpoint'] ), $t, $c, $url ); return false !== strpos( $s['endpoint'], 'muerta' ) ? 410 : 201; };
$k = rau_vigia_revisar( $ah, $enviar );
$por = array(); foreach ( $enviados as $e ) $por[ $e[0] ] = $e;
ok( isset( $por['uno'] ) && false !== strpos( $por['uno'][1] . $por['uno'][2], 'VK9XY' ) && false !== strpos( $por['uno'][2], '20m abierta' ), 'avisa el prefijo oído y la banda con 3 estaciones o más' );
ok( false !== strpos( $por['uno'][3], '/receptor/?f=14074.5' ), 'el aviso abre el receptor en esa frecuencia' );
ok( isset( $por['dos'] ) && false !== strpos( $por['dos'][2], 'oyó a HK4ABC' ), 'también con los enlaces de Colombia de PSK Reporter' );
ok( ! isset( $por['vieja'] ), 'una suscripción de más de 30 días no recibe nada' );
$quedan = array_map( function( $s ) { return basename( $s['endpoint'] ); }, rau_vigia_subs() );
ok( ! in_array( 'vieja', $quedan, true ) && ! in_array( 'muerta', $quedan, true ) && in_array( 'uno', $quedan, true ), 'se borran la vencida y la que el servicio rechazó (410)' );
$enviados = array();
rau_vigia_revisar( $ah + 600, $enviar );
ok( ! $enviados, 'lo mismo no se avisa otra vez antes de 3 horas' );
rau_vigia_revisar( $ah + 4 * HOUR_IN_SECONDS, $enviar );
ok( ! $enviados, 'con datos viejos (más de 30 min) no se avisa' );

// Restaurar
if ( false === $prev[0] ) delete_option( RAU_VIGIA_OPCION ); else update_option( RAU_VIGIA_OPCION, $prev[0], false );
if ( false === $prev[1] ) delete_transient( 'rau_sdr_oido' ); else set_transient( 'rau_sdr_oido', $prev[1], 300 );
if ( false === $prev[2] ) delete_option( 'rau_prop_co' ); else update_option( 'rau_prop_co', $prev[2], false );
echo "\n$n comprobaciones, $fallos fallas\n"; exit( $fallos ? 1 : 0 );
