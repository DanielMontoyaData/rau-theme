<?php
/**
 * Conteo anónimo de uso de las herramientas (inc/uso-herramientas.php).
 *   php tests/sitio/uso.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php uso.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
$antes = get_option( RAU_USO_OPCION ); delete_option( RAU_USO_OPCION );
$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
$pedir = function( $h ) { $r = new WP_REST_Request( 'POST', '/rau/v1/uso' ); $r->set_param( 'h', $h ); return rest_do_request( $r )->get_status(); };
foreach ( array( 'smith', 'smith', 'calc', 'inventada' ) as $h ) delete_transient( 'rau_uso_' . md5( '203.0.113.7|' . $h . '|' . wp_salt( 'nonce' ) ) );
ok( 204 === $pedir( 'smith' ), 'responde sin contenido' );
$pedir( 'smith' ); $pedir( 'calc' ); $pedir( 'inventada' );
$u = get_option( RAU_USO_OPCION ); $m = gmdate( 'Y-m' );
ok( 1 === $u[ $m ]['smith'] && 1 === $u[ $m ]['calc'], 'una vez por visitante, herramienta y hora' );
ok( ! isset( $u[ $m ]['inventada'] ), 'solo herramientas que existen' );
ok( false === strpos( wp_json_encode( $u ), '203.0.113' ), 'no se guarda la IP' );
for ( $i = 0; $i < 15; $i++ ) rau_uso_registrar( 'utc', sprintf( '2025-%02d', ( $i % 12 ) + 1 ) . ( $i >= 12 ? '-x' : '' ) );
ok( count( get_option( RAU_USO_OPCION ) ) <= 13, 'se conservan 13 meses' );
$r = rau_uso_resumen( 1 );
ok( isset( $r['smith'] ) || isset( $r['utc'] ), 'resumen del mes' );
if ( false === $antes ) delete_option( RAU_USO_OPCION ); else update_option( RAU_USO_OPCION, $antes, false );
echo "\n$n comprobaciones, $fallos fallas\n"; exit( $fallos ? 1 : 0 );
