<?php
/**
 * Capacidades de los miembros para emergencias (inc/capacidades.php).
 *   php tests/sitio/capacidades.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php capacidades.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
$ids = array();
$m = function( $ind, $cap, $autoriza ) use ( &$ids ) { $id = wp_insert_post( array( 'post_type' => 'miembro', 'post_status' => 'publish', 'post_title' => 'Miembro ' . $ind ) ); update_post_meta( $id, 'indicativo', $ind ); update_post_meta( $id, 'capacidades', $cap ); update_post_meta( $id, 'cap_coord', $autoriza ? '1' : '0' ); update_post_meta( $id, 'cap_municipio', 'Pereira' ); $ids[] = $id; };
$m( 'HK6CAP', array( 'hf', 'generador', 'inventada' ), true );
$m( 'HK6NOA', array( 'hf' ), false );
$m( 'HK6VAC', array(), true );
$d = rau_capacidades_datos();
$por = array(); foreach ( $d as $x ) $por[ $x['ind'] ] = $x;
ok( isset( $por['HK6CAP'] ) && array( 'hf', 'generador' ) === $por['HK6CAP']['cap'], 'solo capacidades válidas' );
ok( ! isset( $por['HK6NOA'] ), 'sin autorización no aparece' );
ok( ! isset( $por['HK6VAC'] ), 'sin capacidades no aparece' );
$pedir = function() { $r = rest_do_request( new WP_REST_Request( 'GET', '/rau-em/v1/capacidades' ) ); return $r->get_status(); };
wp_set_current_user( 0 ); ok( in_array( $pedir(), array( 401, 403 ), true ), 'sin cuenta, no' );
$u = wp_insert_user( array( 'user_login' => 'prueba-cap-' . wp_generate_password( 5, false ), 'user_pass' => wp_generate_password( 20 ), 'role' => 'subscriber' ) );
wp_set_current_user( $u ); ok( in_array( $pedir(), array( 401, 403 ), true ), 'una cuenta sin rol de emergencias, no' );
( new WP_User( $u ) )->add_cap( 'rau_em_coordinador' ); wp_set_current_user( 0 ); clean_user_cache( $u ); wp_set_current_user( $u );
ok( 200 === $pedir(), 'coordinación de emergencias, sí' );
foreach ( $ids as $id ) wp_delete_post( $id, true );
require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $u );
echo "\n$n comprobaciones, $fallos fallas\n"; exit( $fallos ? 1 : 0 );
