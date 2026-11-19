<?php
/**
 * Libro de Guardia: ADIF y diploma automático (inc/libro-adif.php).
 *   php tests/sitio/libro.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php libro.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
$adif = "Exportado por WSJT-X\n<adif_ver:5>3.1.4 <programid:6>WSJT-X <EOH>\n"
	. "<call:6>K1ABCD <gridsquare:4>FN42 <mode:3>FT8 <rst_sent:3>-10 <rst_rcvd:3>-12 <qso_date:8>20261115 <time_on:6>134500 <band:3>20m <freq:9>14.075123 <qsl_rcvd:1>Y <eor>\n"
	. "<CALL:5>EA7XY <QSO_DATE:8>20261116 <TIME_ON:4>0210 <BAND:3>40M <MODE:3>SSB <EOR>\n"
	. "<call:2>X1 <qso_date:4>2026 <eor>\n";
$r = rau_libro_importar( $adif );
ok( 2 === $r['nuevos'] && 1 === $r['malos'], 'importa 2 contactos y descarta el inválido' );
$r2 = rau_libro_importar( $adif );
ok( 0 === $r2['nuevos'] && 2 === $r2['repetidos'], 'importar otra vez no duplica' );
$ids = get_posts( array( 'post_type' => 'qso', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => 'adif_clave' ) );
$k1 = current( array_filter( $ids, function( $i ) { return 'K1ABCD' === get_the_title( $i ); } ) );
ok( '2026-11-15' === get_post_meta( $k1, 'fecha', true ) && '13:45' === get_post_meta( $k1, 'hora', true ) && '20m' === get_post_meta( $k1, 'banda', true ) && 'FT8' === get_post_meta( $k1, 'modo', true ), 'fecha, hora, banda y modo' );
// Diploma automático: el confirmado ya tiene el suyo
$dip = (int) get_post_meta( $k1, 'diploma_id', true );
$num = $dip ? get_post_meta( $dip, 'numero', true ) : '';
ok( $dip && preg_match( '/^RAU-QSO-\d{4}-[0-9A-F]{8}$/', $num ) && 'K1ABCD' === get_post_meta( $dip, 'nombre', true ), 'el contacto confirmado emite su diploma con número' );
$ea = current( array_filter( $ids, function( $i ) { return 'EA7XY' === get_the_title( $i ); } ) );
ok( ! get_post_meta( $ea, 'diploma_id', true ), 'el pendiente todavía no' );
update_post_meta( $ea, 'confirmado', '1' );
$d2 = (int) get_post_meta( $ea, 'diploma_id', true );
ok( $d2 > 0, 'al confirmarlo se emite' );
update_post_meta( $ea, 'confirmado', '1' ); wp_update_post( array( 'ID' => $ea ) );
ok( $d2 === (int) get_post_meta( $ea, 'diploma_id', true ), 'y una sola vez' );
$_GET['numero'] = $num;
add_filter( 'wp_die_ajax_handler', function() { return function() {}; } );
ob_start(); add_filter( 'wp_doing_ajax', '__return_true' ); try { rau_diploma_lookup(); } catch ( \Throwable $e ) {} $j = json_decode( ob_get_clean(), true );
ok( ! empty( $j['success'] ) && $j['data']['auto'] && $num === $j['data']['numero'], 'el verificador lo encuentra' );
// Exportar: ADIF válido que vuelve a leerse igual
$x = rau_libro_adif( array( $k1, $ea ) );
$l = rau_adif_leer( $x );
ok( 2 === count( $l ) && 'K1ABCD' === $l[0]['call'] && '20261115' === $l[0]['qso_date'] && '1345' === $l[0]['time_on'] && '20M' === $l[0]['band'] && 'Y' === $l[0]['qsl_rcvd'] && 'HK4RAU' === $l[0]['station_callsign'], 'el ADIF exportado tiene los campos de LoTW/QRZ' );
foreach ( $ids as $i ) { $d = (int) get_post_meta( $i, 'diploma_id', true ); if ( $d ) wp_delete_post( $d, true ); wp_delete_post( $i, true ); }
echo "\n$n comprobaciones, $fallos fallas\n"; exit( $fallos ? 1 : 0 );
