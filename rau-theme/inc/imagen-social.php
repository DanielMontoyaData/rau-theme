<?php
/**
 * Imagen para compartir de cada página (1200×630): el título de la página sobre el diseño del sitio,
 * con el escudo. La ven WhatsApp, Facebook, Telegram, X y LinkedIn en la vista previa del enlace.
 * Se genera una sola vez por título (GD con FreeType y la fuente Space Grotesk del tema, licencia OFL)
 * y se guarda en wp-content/uploads/rau-og/. Si el servidor no tiene GD, se usa el escudo como antes.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function rau_og_dir() { $u = wp_upload_dir(); return array( trailingslashit( $u['basedir'] ) . 'rau-og', trailingslashit( $u['baseurl'] ) . 'rau-og' ); }
/* Parte el título en líneas que quepan en el ancho, con la fuente y tamaño dados */
function rau_og_lineas( $texto, $fuente, $tam, $ancho, $max ) {
	$pal = preg_split( '/\s+/u', trim( $texto ) ); $lin = array(); $act = '';
	foreach ( $pal as $p ) {
		$prueba = '' === $act ? $p : $act . ' ' . $p;
		$c = imagettfbbox( $tam, 0, $fuente, $prueba );
		if ( $c[2] - $c[0] > $ancho && '' !== $act ) { $lin[] = $act; $act = $p; } else $act = $prueba;
	}
	if ( '' !== $act ) $lin[] = $act;
	if ( count( $lin ) > $max ) { $lin = array_slice( $lin, 0, $max ); $lin[ $max - 1 ] = rtrim( $lin[ $max - 1 ], ' .,;:' ) . '…'; }
	return $lin;
}
/* URL de la imagen (la genera si no existe). Devuelve null si no se puede generar. */
function rau_og_imagen_url( $titulo = null, $seccion = '' ) {
	if ( ! function_exists( 'imagettftext' ) ) return null;
	$titulo = trim( wp_strip_all_tags( html_entity_decode( (string) ( $titulo ?? get_bloginfo( 'name' ) ), ENT_QUOTES, 'UTF-8' ) ) );
	$titulo = trim( preg_replace( '/\s+[—–|-]\s+(' . preg_quote( get_bloginfo( 'name' ), '/' ) . '|Radioaficionados Unidos|Herramientas RAU)(\s+[—–|-]\s+.*)?$/u', '', $titulo ) );
	if ( '' === $titulo ) $titulo = 'Radioaficionados Unidos';
	list( $dir, $url ) = rau_og_dir();
	$nombre = substr( md5( $titulo . '|' . $seccion . '|4' ), 0, 16 ) . '.png';
	if ( file_exists( "$dir/$nombre" ) ) return "$url/$nombre";
	if ( ! wp_mkdir_p( $dir ) ) return null;
	$f_b = get_template_directory() . '/assets/fonts/SpaceGrotesk-Bold.ttf'; $f_m = get_template_directory() . '/assets/fonts/SpaceGrotesk-Medium.ttf';
	$W = 1200; $H = 630; $im = imagecreatetruecolor( $W, $H );
	// Fondo: azul noche con un degradado leve y la cuadrícula del sitio
	for ( $y = 0; $y < $H; $y++ ) { $k = $y / $H; imageline( $im, 0, $y, $W, $y, imagecolorallocate( $im, (int) ( 8 + 6 * $k ), (int) ( 12 + 8 * $k ), (int) ( 22 + 14 * $k ) ) ); }
	$rej = imagecolorallocatealpha( $im, 56, 189, 248, 120 );
	for ( $x = 0; $x < $W; $x += 40 ) imageline( $im, $x, 0, $x, $H, $rej );
	for ( $y = 0; $y < $H; $y += 40 ) imageline( $im, 0, $y, $W, $y, $rej );
	$cian = imagecolorallocate( $im, 56, 189, 248 ); $ambar = imagecolorallocate( $im, 255, 181, 71 ); $blanco = imagecolorallocate( $im, 241, 245, 249 ); $gris = imagecolorallocate( $im, 148, 163, 184 );
	imagefilledrectangle( $im, 0, 0, $W, 8, $cian );
	// Escudo a la derecha
	// El escudo en WebP (el PNG trae un perfil de color que libpng advierte en cada carga)
	$escudo = get_template_directory() . '/assets/images/rau-badge.webp';
	$e = function_exists( 'imagecreatefromwebp' ) && file_exists( $escudo ) ? @imagecreatefromwebp( $escudo ) : @imagecreatefrompng( get_template_directory() . '/assets/images/rau-badge.png' );
	if ( $e ) {
		$ew = imagesx( $e ); $eh = imagesy( $e ); $h2 = 300; $w2 = (int) round( $ew * $h2 / $eh );
		imagealphablending( $im, true ); imagecopyresampled( $im, $e, $W - $w2 - 70, (int) ( ( $H - $h2 ) / 2 ), 0, 0, $w2, $h2, $ew, $eh ); imagedestroy( $e );
	}
	$ancho = $W - 80 - 420;
	imagettftext( $im, 22, 0, 80, 110, $cian, $f_m, mb_strtoupper( 'RAU · Radioaficionados Unidos' . ( $seccion ? ' · ' . $seccion : '' ) ) );
	// Título: el tamaño más grande con el que cabe en 4 líneas
	foreach ( array( 64, 56, 48, 42 ) as $tam ) { $lin = rau_og_lineas( $titulo, $f_b, $tam, $ancho, 4 ); $c = imagettfbbox( $tam, 0, $f_b, implode( ' ', $lin ) ); if ( count( $lin ) <= 3 || 42 === $tam ) break; }
	$y = 200 + $tam;
	foreach ( $lin as $l ) { imagettftext( $im, $tam, 0, 80, $y, $blanco, $f_b, $l ); $y += (int) ( $tam * 1.25 ); }
	imagefilledrectangle( $im, 80, $H - 92, 140, $H - 88, $ambar );
	imagettftext( $im, 24, 0, 80, $H - 50, $gris, $f_m, preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( home_url() ) ) );
	$ok = imagepng( $im, "$dir/$nombre", 6 ); imagedestroy( $im );
	return $ok ? "$url/$nombre" : null;
}
/* Limpieza: las que no se usan hace 90 días */
add_action( 'rau_grab_limpiar', function() { list( $dir ) = rau_og_dir(); foreach ( (array) glob( "$dir/*.png" ) as $f ) if ( filemtime( $f ) < time() - 90 * DAY_IN_SECONDS ) @unlink( $f ); } );
