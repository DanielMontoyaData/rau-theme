<?php
/**
 * Datos de la portada (inc/portada.php): concursos, franja de emergencia, cifras y check-ins de hoy.
 *   php tests/sitio/portada.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php portada.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }

/* Concursos: mismas fechas que publican ARRL y CQ */
$c = rau_portada_concursos( 2, gmmktime( 12, 0, 0, 11, 20, 2026 ) );
ok( 'CQ WW DX Contest (CW)' === $c[0]['nombre'] && '2026-11-28' === gmdate( 'Y-m-d', $c[0]['ts'] ), 'CQ WW CW 2026: sábado 28 de noviembre' );
ok( 'ARRL International DX (Phone)' === $c[1]['nombre'] && '2027-03-06' === gmdate( 'Y-m-d', $c[1]['ts'] ), 'luego ARRL DX Phone, 6 de marzo de 2027' );
$c = rau_portada_concursos( 1, gmmktime( 12, 0, 0, 11, 29, 2026 ) );
ok( '2026-11-28' === gmdate( 'Y-m-d', $c[0]['ts'] ), 'mientras dura el fin de semana sigue siendo «el próximo»' );
ok( '2026-10-24' === gmdate( 'Y-m-d', rau_portada_fin_de_semana( 2026, 9, -1 ) ), 'último fin de semana completo de octubre 2026' );
ok( '2026-05-30' === gmdate( 'Y-m-d', rau_portada_fin_de_semana( 2026, 4, -1 ) ), 'mayo 2026: el 30 y 31 cuentan como fin de semana completo' );

/* Franja de emergencia: solo operación real, abierta, pública, de una organización y con tablero */
global $wpdb; $t = rau_em_tablas();
$cod = 'PRT' . strtoupper( wp_generate_password( 5, false ) );
$fila = array( 'codigo' => $cod, 'nombre' => 'Inundación de prueba', 'tipo' => 'real', 'zona' => 'Bello', 'creador' => 'HK4PRB', 'clave_hash' => 'x', 'clave_coord_hash' => '',
	'org_id' => 1, 'publico' => 1, 'estado' => 'abierto', 'creado' => time(), 'actividad' => time() );
$wpdb->insert( $t['ops'], $fila );
$ver = function() { delete_transient( 'rau_portada_emergencia' ); return rau_portada_emergencia(); };
ok( null === $ver(), 'sin tablero público no hay franja' );
update_option( rau_em_tablero_opcion( $cod ), array( 'token' => str_repeat( 'a1', 12 ), 'cod' => $cod, 'ts' => time(), 'resumen' => array( 'red' => array( 'e' => 'dirigida', 'canal' => '146.520 MHz' ) ) ), false );
$e = $ver();
ok( $e && 'Inundación de prueba' === $e['nombre'] && '146.520 MHz' === $e['canal'] && false !== strpos( $e['url'], '/tablero-emergencia/' ), 'con tablero: franja con nombre, canal y enlace' );
$wpdb->update( $t['ops'], array( 'tipo' => 'simulacro' ), array( 'codigo' => $cod ) );
ok( null === $ver(), 'un simulacro no pone franja' );
$wpdb->update( $t['ops'], array( 'tipo' => 'real', 'org_id' => 0 ), array( 'codigo' => $cod ) );
ok( null === $ver(), 'sin organización no hay franja' );
$wpdb->update( $t['ops'], array( 'org_id' => 1, 'estado' => 'cerrado' ), array( 'codigo' => $cod ) );
ok( null === $ver(), 'cerrada no hay franja' );
$wpdb->update( $t['ops'], array( 'estado' => 'abierto', 'actividad' => time() - 13 * HOUR_IN_SECONDS ), array( 'codigo' => $cod ) );
ok( null === $ver(), 'sin actividad en 12 horas no hay franja' );
$wpdb->delete( $t['ops'], array( 'codigo' => $cod ) ); delete_option( rau_em_tablero_opcion( $cod ) ); delete_transient( 'rau_portada_emergencia' );

/* Check-ins de hoy: un indicativo una vez, sin hora ni ubicación */
$ids = array();
foreach ( array( 'HK4PRA', 'hk4pra', 'HK3PRB' ) as $i ) {
	$ids[] = $id = wp_insert_post( array( 'post_type' => 'checkin', 'post_status' => 'publish', 'post_title' => $i ) );
	update_post_meta( $id, 'indicativo', $i ); update_post_meta( $id, 'fecha', current_time( 'Y-m-d' ) ); update_post_meta( $id, 'ciudad', 'Medellín' ); update_post_meta( $id, 'grid', 'FJ26' ); update_post_meta( $id, 'hora', '19:05' );
}
add_filter( 'wp_doing_ajax', '__return_true' );
add_filter( 'wp_die_ajax_handler', function() { return function() { throw new Exception( 'fin' ); }; } );
ob_start(); try { rau_checkin_hoy_endpoint(); } catch ( Exception $x ) {} $j = json_decode( ob_get_clean(), true );
$lista = wp_list_pluck( $j['data']['lista'], 'i' );
ok( in_array( 'HK4PRA', $lista, true ) && in_array( 'HK3PRB', $lista, true ) && 1 === count( array_keys( $lista, 'HK4PRA', true ) ), 'cada indicativo una sola vez' );
ok( false === strpos( wp_json_encode( $j ), 'FJ26' ) && false === strpos( wp_json_encode( $j ), '19:05' ), 'no expone ubicación ni hora' );
delete_transient( 'rau_portada_datos' );
ok( rau_portada_datos()['colegas'] >= 2, 'cifra de colegas reportados en 30 días' );
foreach ( $ids as $id ) wp_delete_post( $id, true );

echo "\n$n comprobaciones, $fallos fallas\n"; exit( $fallos ? 1 : 0 );
