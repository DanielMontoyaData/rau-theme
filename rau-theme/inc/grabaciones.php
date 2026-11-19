<?php
/**
 * Grabaciones del receptor que se comparten con un enlace.
 *
 * Quien tiene sesión iniciada puede subir lo que grabó en el receptor (WAV de hasta 3 minutos, el formato
 * que genera el propio receptor). Queda en la biblioteca de medios con la frecuencia, el modo y la hora, y
 * se comparte como /receptor/?grab=<id>: la página muestra un reproductor y un botón para escuchar esa
 * frecuencia en vivo. Las grabaciones se borran a los 30 días, salvo las que un administrador marque
 * como destacadas. Hasta 10 por persona por día.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_GRAB_META', '_rau_grabacion' );
define( 'RAU_GRAB_MAX_SEG', 180 );

add_action( 'rest_api_init', function() {
	register_rest_route( 'rau/v1', '/grabaciones', array(
		'methods' => 'POST', 'permission_callback' => function() { return is_user_logged_in(); }, 'callback' => 'rau_grab_subir',
	) );
	register_rest_route( 'rau/v1', '/grabaciones/(?P<id>\d+)', array(
		'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function( WP_REST_Request $r ) {
			$g = rau_grab_datos( (int) $r['id'] );
			return $g ? $g : new WP_REST_Response( array( 'error' => 'No existe' ), 404 );
		},
	) );
} );

/* Cabecera WAV: PCM 16 bits, un canal, de 8 a 48 kHz; devuelve la duración en segundos o un error */
function rau_grab_validar_wav( $ruta ) {
	$f = @fopen( $ruta, 'rb' ); if ( ! $f ) return 'No se pudo leer el archivo';
	$c = fread( $f, 44 ); fclose( $f );
	if ( strlen( $c ) < 44 || 'RIFF' !== substr( $c, 0, 4 ) || 'WAVE' !== substr( $c, 8, 4 ) || 'fmt ' !== substr( $c, 12, 4 ) ) return 'No es un WAV';
	$h = unpack( 'vformato/vcanales/Vtasa/Vbps/vblock/vbits', substr( $c, 20, 16 ) );
	if ( 1 !== $h['formato'] || 1 !== $h['canales'] || 16 !== $h['bits'] || $h['tasa'] < 8000 || $h['tasa'] > 48000 ) return 'Formato no admitido (se espera el WAV del receptor: PCM 16 bits, un canal)';
	$datos = filesize( $ruta ) - 44; $seg = $datos / ( 2 * $h['tasa'] );
	if ( $seg > RAU_GRAB_MAX_SEG + 1 ) return 'Supera los ' . ( RAU_GRAB_MAX_SEG / 60 ) . ' minutos';
	if ( $seg < 1 ) return 'Es demasiado corta';
	return round( $seg, 1 );
}
function rau_grab_subir( WP_REST_Request $req ) {
	$uid = get_current_user_id();
	$hoy = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'author' => $uid, 'meta_key' => RAU_GRAB_META, 'date_query' => array( array( 'after' => '1 day ago' ) ), 'fields' => 'ids', 'numberposts' => 20 ) );
	if ( count( $hoy ) >= 10 ) return new WP_REST_Response( array( 'error' => 'Llegaste al límite de 10 grabaciones por día' ), 429 );
	$archivos = $req->get_file_params();
	if ( empty( $archivos['audio']['tmp_name'] ) ) return new WP_REST_Response( array( 'error' => 'Falta el audio' ), 400 );
	if ( $archivos['audio']['size'] > ( RAU_GRAB_MAX_SEG + 2 ) * 2 * 48000 + 44 ) return new WP_REST_Response( array( 'error' => 'Archivo demasiado grande' ), 413 );
	$dur = rau_grab_validar_wav( $archivos['audio']['tmp_name'] );
	if ( is_string( $dur ) ) return new WP_REST_Response( array( 'error' => $dur ), 400 );
	$freq = round( (float) $req->get_param( 'freq' ), 2 );
	$modo = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $req->get_param( 'modo' ) ) );
	$inicio = (int) $req->get_param( 'inicio' );
	if ( $freq <= 0 || $freq > 32000 ) return new WP_REST_Response( array( 'error' => 'Frecuencia no válida' ), 400 );
	if ( $inicio < ( time() - DAY_IN_SECONDS ) * 1000 || $inicio > ( time() + 60 ) * 1000 ) $inicio = time() * 1000;

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$nombre = sprintf( 'HK4RAU-%.2fkHz-%s-%s.wav', $freq, $modo ?: 'am', gmdate( 'Ymd-Hi', (int) ( $inicio / 1000 ) ) );
	$arch = array( 'name' => $nombre, 'tmp_name' => $archivos['audio']['tmp_name'], 'type' => 'audio/wav', 'error' => 0, 'size' => $archivos['audio']['size'] );
	$titulo = sprintf( 'Receptor HK4RAU · %s kHz %s · %s UTC', number_format( $freq, 2, ',', '.' ), strtoupper( $modo ), gmdate( 'Y-m-d H:i', (int) ( $inicio / 1000 ) ) );
	$id = media_handle_sideload( $arch, 0, $titulo, array( 'post_author' => $uid ) );
	if ( is_wp_error( $id ) ) return new WP_REST_Response( array( 'error' => $id->get_error_message() ), 500 );
	$u = wp_get_current_user();
	update_post_meta( $id, RAU_GRAB_META, array( 'freq' => $freq, 'modo' => $modo, 'inicio' => $inicio, 'dur' => $dur, 'por' => $u->display_name ?: $u->user_login ) );
	return rau_grab_datos( $id );
}
function rau_grab_datos( $id ) {
	$m = get_post_meta( $id, RAU_GRAB_META, true );
	if ( ! $m || 'attachment' !== get_post_type( $id ) ) return null;
	return array( 'id' => $id, 'url' => wp_get_attachment_url( $id ), 'freq' => $m['freq'], 'modo' => $m['modo'], 'inicio' => $m['inicio'], 'dur' => $m['dur'], 'por' => $m['por'],
		'titulo' => get_the_title( $id ), 'enlace' => add_query_arg( 'grab', $id, home_url( '/receptor/' ) ) );
}

/* Limpieza diaria: más de 30 días y sin destacar */
add_action( 'init', function() { if ( ! wp_next_scheduled( 'rau_grab_limpiar' ) ) wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'rau_grab_limpiar' ); } );
add_action( 'rau_grab_limpiar', 'rau_grab_limpiar' );
function rau_grab_limpiar() {
	$viejas = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => RAU_GRAB_META, 'date_query' => array( array( 'before' => '30 days ago' ) ), 'fields' => 'ids', 'numberposts' => 200 ) );
	$n = 0; foreach ( $viejas as $id ) { if ( get_post_meta( $id, '_rau_grabacion_destacada', true ) ) continue; wp_delete_attachment( $id, true ); $n++; }
	return $n;
}

/* Vista previa al compartir /receptor/?grab=<id> */
function rau_grab_compartida() {
	$id = isset( $_GET['grab'] ) ? absint( $_GET['grab'] ) : 0;
	return $id ? rau_grab_datos( $id ) : null;
}
add_filter( 'rau_seo_titulo', function( $t ) { $g = rau_grab_compartida(); return $g ? $g['titulo'] : $t; } );
add_filter( 'rau_seo_descripcion', function( $d ) { $g = rau_grab_compartida(); return $g ? sprintf( 'Grabación de %s s en el KiwiSDR de RAU, compartida por %s. Escúchala o sintoniza esa frecuencia en vivo.', number_format( $g['dur'], 0 ), $g['por'] ) : $d; } );
