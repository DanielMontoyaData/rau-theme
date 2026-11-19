<?php
/**
 * Grabaciones compartidas del receptor (inc/grabaciones.php), dentro de WordPress.
 *   php tests/radio/grabaciones.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php grabaciones.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
require_once ABSPATH . 'wp-admin/includes/file.php';
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
function wav( $seg, $tasa = 12000, $bits = 16 ) {
	$n = (int) ( $seg * $tasa ); $datos = ''; for ( $i = 0; $i < $n; $i++ ) $datos .= pack( 'v', (int) ( 8000 * sin( 2 * M_PI * 700 * $i / $tasa ) ) & 0xFFFF );
	$h = 'RIFF' . pack( 'V', 36 + strlen( $datos ) ) . 'WAVEfmt ' . pack( 'VvvVVvv', 16, 1, 1, $tasa, $tasa * 2, 2, $bits ) . 'data' . pack( 'V', strlen( $datos ) );
	$f = wp_tempnam( 'grab' ); file_put_contents( $f, $h . $datos ); return $f;
}
function subir( $archivo, $campos ) {
	$r = new WP_REST_Request( 'POST', '/rau/v1/grabaciones' );
	foreach ( $campos as $k => $v ) $r->set_param( $k, $v );
	$r->set_file_params( array( 'audio' => array( 'name' => 'grabacion.wav', 'type' => 'audio/wav', 'tmp_name' => $archivo, 'error' => 0, 'size' => filesize( $archivo ) ) ) );
	$res = rest_do_request( $r ); return array( $res->get_status(), $res->get_data() );
}
$c = array( 'freq' => 7074.0, 'modo' => 'usb', 'inicio' => ( time() - 60 ) * 1000 );
wp_set_current_user( 0 );
list( $st ) = subir( wav( 3 ), $c );
ok( 401 === $st || 403 === $st, 'sin sesión no se sube nada' );
$uid = wp_insert_user( array( 'user_login' => 'prueba-grab-' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password( 20 ), 'display_name' => 'HK4PRB', 'role' => 'subscriber' ) );
wp_set_current_user( $uid );
list( $st, $d ) = subir( wav( 3 ), $c );
ok( 200 === $st && ! empty( $d['id'] ) && 3.0 == $d['dur'] && 'HK4PRB' === $d['por'], 'se sube con su frecuencia, duración y autor' );
ok( false !== strpos( $d['enlace'], '/receptor/?grab=' . $d['id'] ) && preg_match( '/\.wav$/', $d['url'] ), 'enlace para compartir y archivo WAV en la biblioteca' );
$id = $d['id'];
$r = rest_do_request( new WP_REST_Request( 'GET', '/rau/v1/grabaciones/' . $id ) );
ok( 200 === $r->get_status() && 7074.0 == $r->get_data()['freq'], 'la grabación se consulta sin sesión' );
list( $st, $d ) = subir( wav( 2, 12000, 8 ), $c );
ok( 400 === $st, 'otro formato se rechaza (' . ( $d['error'] ?? '' ) . ')' );
$falso = wp_tempnam( 'x' ); file_put_contents( $falso, '<?php echo 1;' . str_repeat( ' ', 100 ) );
list( $st ) = subir( $falso, $c );
ok( 400 === $st, 'un archivo que no es WAV se rechaza' );
list( $st, $d ) = subir( wav( 0.4 ), $c );
ok( 400 === $st, 'demasiado corta se rechaza' );
// Vista previa al compartir
$_GET['grab'] = $id;
ok( false !== strpos( apply_filters( 'rau_seo_titulo', 'x' ), '7.074,00 kHz USB' ), 'el título para compartir dice la frecuencia y el modo' );
ok( false !== strpos( apply_filters( 'rau_seo_descripcion', 'x' ), 'HK4PRB' ), 'la descripción dice quién la compartió' );
unset( $_GET['grab'] );
// Limpieza a los 30 días
wp_update_post( array( 'ID' => $id, 'post_date' => gmdate( 'Y-m-d H:i:s', time() - 40 * DAY_IN_SECONDS ), 'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - 40 * DAY_IN_SECONDS ) ) );
$ruta = get_attached_file( $id );
ok( 1 === rau_grab_limpiar() && ! get_post( $id ) && ! file_exists( $ruta ), 'a los 30 días se borra, con su archivo' );
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $uid );
echo "\n$n comprobaciones, $fallos fallas\n";
exit( $fallos ? 1 : 0 );
