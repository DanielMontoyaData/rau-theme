<?php
/**
 * JS8 oído → centro de mando (inc/js8-oido.php), dentro de WordPress.
 *   php tests/radio/js8-oido.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php js8-oido.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
function enviar( $cuerpo ) { $r = new WP_REST_Request( 'POST', '/rau/v1/js8' ); $r->set_header( 'Content-Type', 'application/json' ); $r->set_body( wp_json_encode( $cuerpo ) ); $res = rest_do_request( $r ); return array( $res->get_status(), $res->get_data() ); }
$antes = get_option( RAU_JS8_OPCION ); delete_option( RAU_JS8_OPCION );
$S = ( time() - 20 ) * 1000;
$cuerpo = array( 'ranura' => $S, 'dial' => 7078000, 'items' => array(
	array( 'texto' => 'HK4ABC: @EMCOMM SNR -12', 'de' => 'HK4ABC', 'para' => '@EMCOMM', 'grupo' => '@EMCOMM', 'tipo' => 'dirigido', 'f' => 1300, 'snr' => -12 ),
	array( 'texto' => 'HK6XYZ: HK4ABC RR', 'de' => 'HK6XYZ', 'para' => 'HK4ABC', 'tipo' => 'dirigido', 'f' => 900, 'snr' => 3 ),
	array( 'texto' => '<script>alert(1)</script>', 'tipo' => 'datos', 'f' => 1000 ),
) );
wp_set_current_user( 0 );
list( $st ) = enviar( $cuerpo );
ok( 401 === $st || 403 === $st, 'sin sesión no se acepta nada' );

$uid = wp_insert_user( array( 'user_login' => 'prueba-js8-' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password( 20 ), 'display_name' => 'HK4PRB', 'role' => 'subscriber' ) );
wp_set_current_user( $uid );
list( $st, $d ) = enviar( $cuerpo );
ok( 200 === $st && 3 === $d['nuevos'], 'con sesión se guardan los mensajes' );
list( $st ) = enviar( $cuerpo );
ok( 429 === $st, 'más de un envío cada 10 s se rechaza' );
delete_transient( 'rau_js8_ult_' . $uid );
list( $st, $d ) = enviar( $cuerpo );
ok( 200 === $st && 0 === $d['nuevos'], 'lo mismo de la misma ranura no se repite' );
delete_transient( 'rau_js8_ult_' . $uid );
list( $st ) = enviar( array( 'ranura' => ( time() - 3600 ) * 1000, 'dial' => 7078000, 'items' => $cuerpo['items'] ) );
ok( 400 === $st, 'una ranura vieja se rechaza' );

$sala = rau_js8_para_sala();
ok( 3 === count( $sala ), 'el centro de mando recibe lo de las últimas 2 horas' );
ok( '@EMCOMM' === $sala[0]['grupo'] && $sala[0]['emergencia'], 'primero lo dirigido a grupos de emergencia' );
ok( 7.0793 === $sala[0]['mhz'] && 'HK4PRB' === $sala[0]['por'], 'con la frecuencia real y quién lo reportó' );
$x = array_values( array_filter( $sala, function( $s ) { return 'datos' === $s['tipo']; } ) )[0];
ok( 'SCRIPTALERT(1)/SCRIPT' === $x['texto'], 'el texto se limpia: solo el alfabeto de JS8, sin < ni >' );
$al = rau_em_alertas_publicas();
ok( isset( $al['js8'] ) && 3 === count( $al['js8'] ), 'llega a las alertas del centro de mando' );

require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $uid );
if ( false === $antes ) delete_option( RAU_JS8_OPCION ); else update_option( RAU_JS8_OPCION, $antes, false );
echo "\n$n comprobaciones, $fallos fallas\n";
exit( $fallos ? 1 : 0 );
