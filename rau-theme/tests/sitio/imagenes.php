<?php
/**
 * Imágenes: los tamaños que genera WordPress salen en WebP; la imagen para compartir se genera una vez.
 *   php tests/sitio/imagenes.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php imagenes.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
$im = imagecreatetruecolor( 1600, 1067 ); for ( $y = 0; $y < 1067; $y += 4 ) imagefilledrectangle( $im, 0, $y, 1600, $y + 3, imagecolorallocate( $im, $y % 255, 120, 200 ) );
$f = wp_tempnam( 'foto' ) . '.jpg'; imagejpeg( $im, $f, 90 );
$id = media_handle_sideload( array( 'name' => 'prueba-webp.jpg', 'tmp_name' => $f ), 0, 'Prueba WebP' );
ok( ! is_wp_error( $id ), 'se sube una foto JPEG' );
$m = wp_get_attachment_metadata( $id );
$mimes = array_unique( wp_list_pluck( $m['sizes'], 'mime-type' ) );
ok( array( 'image/webp' ) === array_values( $mimes ), 'todos los tamaños generados son WebP (' . implode( ',', $mimes ) . ')' );
ok( 'image/jpeg' === get_post_mime_type( $id ), 'el original se conserva en JPEG' );
$jpeg = filesize( get_attached_file( $id ) ); $web = filesize( dirname( get_attached_file( $id ) ) . '/' . $m['sizes']['large']['file'] );
ok( $web < $jpeg, 'el tamaño grande pesa menos que el original (' . round( $web / 1024 ) . ' KB contra ' . round( $jpeg / 1024 ) . ' KB)' );
$html = wp_get_attachment_image( $id, 'medium_large' );
ok( false !== strpos( $html, 'srcset=' ) && false !== strpos( $html, '.webp' ), 'la imagen sale con srcset y en WebP' );
wp_delete_attachment( $id, true );
// Imagen para compartir
$u1 = rau_og_imagen_url( 'Una página de prueba con un título bastante largo para que ocupe varias líneas', 'Prueba' );
$u2 = rau_og_imagen_url( 'Una página de prueba con un título bastante largo para que ocupe varias líneas', 'Prueba' );
ok( $u1 && $u1 === $u2, 'la imagen para compartir se genera una sola vez por título' );
list( $dir ) = rau_og_dir(); $arch = $dir . '/' . basename( $u1 ); $tam = getimagesize( $arch );
ok( 1200 === $tam[0] && 630 === $tam[1] && 'image/png' === $tam['mime'], 'PNG de 1200×630' );
ok( rau_og_imagen_url( 'Receptor KiwiSDR — RAU' ) === rau_og_imagen_url( 'Receptor KiwiSDR' ), 'el nombre del sitio al final del título no se repite en la imagen' );
@unlink( $arch );
echo "\n$n comprobaciones, $fallos fallas\n";
exit( $fallos ? 1 : 0 );
