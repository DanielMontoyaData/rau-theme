<?php
/**
 * Galería SSTV (inc/sstv-galeria.php) y estadísticas de PSK Reporter (inc/pskreporter-envio.php).
 *   php tests/radio/sstv.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php sstv.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
require_once ABSPATH . 'wp-admin/includes/file.php';
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
function png( $w, $h ) { $im = imagecreatetruecolor( $w, $h ); imagefilledrectangle( $im, 0, 0, $w / 2, $h, imagecolorallocate( $im, 220, 120, 40 ) ); $f = wp_tempnam( 'sstv' ); imagepng( $im, $f ); imagedestroy( $im ); return $f; }
function subir( $f, $campos, $tipo = 'image/png' ) {
	$r = new WP_REST_Request( 'POST', '/rau/v1/sstv' ); foreach ( $campos as $k => $v ) $r->set_param( $k, $v );
	$r->set_file_params( array( 'imagen' => array( 'name' => 'x.png', 'type' => $tipo, 'tmp_name' => $f, 'error' => 0, 'size' => filesize( $f ) ) ) );
	$res = rest_do_request( $r ); return array( $res->get_status(), $res->get_data() );
}
$c = array( 'modo' => 'Robot 36', 'freq' => 14230, 'inicio' => ( time() - 120 ) * 1000 );
wp_set_current_user( 0 );
list( $st ) = subir( png( 320, 240 ), $c );
ok( 401 === $st || 403 === $st, 'sin sesión no se publica' );
$uid = wp_insert_user( array( 'user_login' => 'prueba-sstv-' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password( 20 ), 'display_name' => 'HK4SST', 'role' => 'subscriber' ) );
wp_set_current_user( $uid );
list( $st, $d ) = subir( png( 320, 240 ), $c );
ok( 200 === $st && 'Robot 36' === $d['modo'] && 'HK4SST' === $d['por'] && preg_match( '/\.(png|webp)$/', $d['url'] ), 'se publica con modo, frecuencia y autor' );
list( $st ) = subir( png( 1200, 900 ), $c );
ok( 400 === $st, 'una imagen de otro tamaño no es SSTV' );
$txt = wp_tempnam( 'x' ); file_put_contents( $txt, 'no es imagen' );
list( $st ) = subir( $txt, $c );
ok( 400 === $st, 'un archivo que no es PNG se rechaza' );
$j = imagecreatetruecolor( 320, 240 ); $fj = wp_tempnam( 'j' ); imagejpeg( $j, $fj );
list( $st ) = subir( $fj, $c, 'image/jpeg' );
ok( 400 === $st, 'solo PNG (lo que genera el decodificador)' );
$l = rest_do_request( new WP_REST_Request( 'GET', '/rau/v1/sstv' ) )->get_data();
ok( count( $l ) >= 1 && $l[0]['id'] === $d['id'], 'la lista pública trae la última primero' );
ob_start(); rau_receptor_aportes(); $h = ob_get_clean();
ok( false !== strpos( $h, 'Imágenes SSTV recibidas' ) && false !== strpos( $h, esc_url( $d['url'] ) ) && false !== strpos( $h, 'loading="lazy"' ), 'la página del receptor muestra la galería' );
wp_update_post( array( 'ID' => $d['id'], 'post_date' => gmdate( 'Y-m-d H:i:s', time() - 40 * DAY_IN_SECONDS ), 'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - 40 * DAY_IN_SECONDS ) ) );
ok( rau_sstv_limpiar() >= 1 && ! get_post( $d['id'] ), 'a los 30 días se borra' );

// Estadísticas de PSK Reporter
delete_option( 'rau_psk_stats' );
update_option( 'rau_sdr_lugar', array( 'lat' => 4.53, 'lng' => -75.68, 'nombre' => 'Quindío' ) );
$ah = time();
rau_psk_contar( array(
	array( 'c' => 'K1ABC', 'f' => 14080500, 'm' => 'FT4', 's' => -5, 'g' => 'FN31', 't' => $ah - 60 ),
	array( 'c' => 'EA7XYZ', 'f' => 7078900, 'm' => 'JS8', 's' => -12, 'g' => 'IM76', 't' => $ah - 60 ),
	array( 'c' => 'HK3AA', 'f' => 7078500, 'm' => 'JS8', 's' => 3, 'g' => '', 't' => $ah - 30 ),
), $ah );
rau_psk_contar( array( array( 'c' => 'VK2AB', 'f' => 14080200, 'm' => 'FT4', 's' => -18, 'g' => 'QF56', 't' => $ah - 3 * DAY_IN_SECONDS ) ), $ah );
$r = rau_psk_resumen( 30, $ah );
ok( 4 === $r['n'] && 2 === $r['bandas']['20 m'] && 2 === $r['bandas']['40 m'] && 2 === $r['modos']['JS8'], 'resumen de 30 días por banda y modo' );
ok( 'VK2AB' === $r['lejos']['c'] && $r['lejos']['km'] > 14000, 'el más lejano, con la distancia desde el receptor' );
ok( 3 === rau_psk_resumen( 2, $ah )['n'], 'el período se respeta' );
$res = rest_do_request( new WP_REST_Request( 'GET', '/rau/v1/spots/resumen' ) );
ok( 200 === $res->get_status() && 4 === $res->get_data()['n'], 'resumen público en la API' );
delete_option( 'rau_psk_stats' );
require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $uid );
echo "\n$n comprobaciones, $fallos fallas\n"; exit( $fallos ? 1 : 0 );
