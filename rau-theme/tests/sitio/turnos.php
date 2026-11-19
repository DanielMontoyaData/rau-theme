<?php
/**
 * De turno ahora (inc/turnos.php).
 *   php tests/sitio/turnos.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php turnos.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
ok( 'HK4ABC' === rau_indicativo_en( 'Turno de hk4abc — Juan' ) && '5K4XY' === rau_indicativo_en( '5K4XY' ) && '' === rau_indicativo_en( 'Juan Pérez' ), 'saca el indicativo del título' );
$ah = time(); $ids = array();
$mk = function( $tit, $ini, $fin ) use ( &$ids ) { $id = wp_insert_post( array( 'post_type' => 'turno', 'post_status' => 'publish', 'post_title' => $tit ) ); update_post_meta( $id, 'fecha_inicio', $ini ); update_post_meta( $id, 'fecha_fin', $fin ); $ids[] = $id; return $id; };
$mk( 'HK4PRV — semana pasada', wp_date( 'Y-m-d', $ah - 9 * DAY_IN_SECONDS ), wp_date( 'Y-m-d', $ah - 3 * DAY_IN_SECONDS ) );
ok( null === rau_turno_actual( $ah ), 'sin turno vigente, nada' );
$mk( 'HK4TUR — Turno de prueba', wp_date( 'Y-m-d', $ah - DAY_IN_SECONDS ), wp_date( 'Y-m-d', $ah + 5 * DAY_IN_SECONDS ) );
$t = rau_turno_actual( $ah );
ok( 'HK4TUR' === $t['indicativo'] && null === $t['ultimo'] && $t['alerta'], 'de turno y sin ningún check-in: alerta' );
$c = wp_insert_post( array( 'post_type' => 'checkin', 'post_status' => 'publish', 'post_title' => 'HK4TUR', 'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $ah - 3 * HOUR_IN_SECONDS ), 'post_date' => wp_date( 'Y-m-d H:i:s', $ah - 3 * HOUR_IN_SECONDS ) ) ); update_post_meta( $c, 'indicativo', 'HK4TUR' ); $ids[] = $c;
$t = rau_turno_actual( $ah );
ok( $t['ultimo'] && abs( $t['horas'] - 3 ) < 0.2 && ! $t['alerta'], 'check-in hace 3 horas: sin alerta' );
$t = rau_turno_actual( $ah + 26 * HOUR_IN_SECONDS );
ok( $t['alerta'] && $t['horas'] > 24, 'más de 24 horas sin check-in: alerta' );
ob_start(); rau_turno_tarjeta(); $h = ob_get_clean();
ok( false !== strpos( $h, 'De turno ahora' ) && false !== strpos( $h, 'HK4TUR' ), 'la página de Turnos lo muestra' );
foreach ( $ids as $id ) wp_delete_post( $id, true );
echo "\n$n comprobaciones, $fallos fallas\n"; exit( $fallos ? 1 : 0 );
