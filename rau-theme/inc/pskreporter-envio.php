<?php
/**
 * Reporte a PSK Reporter de lo que se decodifica en el navegador escuchando el receptor HK4RAU.
 *
 * El KiwiSDR de HK4RAU ya reporta por su cuenta FT8 en las frecuencias de su decodificador automático
 * (por defecto 7074, 14074, 21074 y 28074 kHz). Aquí entra lo demás: FT4, JS8 y FT8 en otras bandas,
 * cuando alguien con sesión iniciada lo decodifica en /receptor/. Así nada se reporta dos veces.
 *
 * Protocolo (https://pskreporter.info/pskdev.html): datagramas UDP en formato IPFIX a
 * report.pskreporter.info:4739, como mucho uno cada 5 minutos, cada indicativo una vez por hora y
 * banda, las plantillas en los tres primeros envíos y después una vez por hora, siempre el mismo
 * identificador aleatorio de la sesión.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_PSK_COLA', 'rau_psk_cola' );

/* Frecuencias (kHz de dial) que el propio Kiwi ya reporta en FT8 */
function rau_psk_kiwi_ft8() {
	$l = get_option( 'rau_psk_kiwi_ft8', '7074,14074,21074,28074' );
	return array_filter( array_map( 'floatval', explode( ',', (string) $l ) ) );
}
function rau_psk_banda( $hz ) { return function_exists( 'rau_prop_banda' ) ? rau_prop_banda( $hz ) : (string) floor( $hz / 1e6 ); }
/* Locator de 6 caracteres a partir de latitud y longitud */
function rau_psk_locator( $lat, $lng ) {
	$lng += 180; $lat += 90;
	return chr( 65 + floor( $lng / 20 ) ) . chr( 65 + floor( $lat / 10 ) ) . floor( fmod( $lng, 20 ) / 2 ) . floor( fmod( $lat, 10 ) )
		. chr( 97 + floor( fmod( $lng, 2 ) * 12 ) ) . chr( 97 + floor( fmod( $lat, 1 ) * 24 ) );
}
function rau_psk_indicativo_valido( $c ) {
	return (bool) preg_match( '#^([A-Z0-9]{1,4}/)?[A-Z0-9]{1,3}[0-9][A-Z0-9]{0,3}[A-Z](/[A-Z0-9]{1,4})?$#', $c ) && strlen( $c ) <= 12;
}

add_action( 'rest_api_init', function() {
	register_rest_route( 'rau/v1', '/spots', array(
		'methods'             => 'POST',
		'permission_callback' => function() { return is_user_logged_in(); },
		'callback'            => 'rau_psk_recibir',
	) );
} );
function rau_psk_recibir( WP_REST_Request $req ) {
	$uid = get_current_user_id();
	if ( get_transient( 'rau_psk_ult_' . $uid ) ) return new WP_REST_Response( array( 'error' => 'Demasiado seguido' ), 429 );
	set_transient( 'rau_psk_ult_' . $uid, 1, 3 );
	$p = $req->get_json_params();
	$modo = isset( $p['modo'] ) ? strtoupper( (string) $p['modo'] ) : '';
	if ( ! in_array( $modo, array( 'FT8', 'FT4', 'JS8' ), true ) ) return new WP_REST_Response( array( 'error' => 'Modo no admitido' ), 400 );
	$dial   = (int) ( $p['dial'] ?? 0 );
	$ranura = (int) floor( ( $p['ranura'] ?? 0 ) / 1000 );
	$ahora  = time();
	if ( $dial < 1800000 || $dial > 54000000 ) return new WP_REST_Response( array( 'error' => 'Frecuencia no válida' ), 400 );
	if ( $ranura < $ahora - 600 || $ranura > $ahora + 60 ) return new WP_REST_Response( array( 'error' => 'Ranura fuera de tiempo' ), 400 );
	// Lo que el Kiwi ya reporta no se repite
	if ( 'FT8' === $modo ) foreach ( rau_psk_kiwi_ft8() as $k ) if ( abs( $dial / 1000 - $k ) < 2 ) return array( 'ok' => true, 'nuevos' => 0, 'motivo' => 'kiwi' );

	$cola = get_option( RAU_PSK_COLA, array() ); if ( ! is_array( $cola ) ) $cola = array();
	$vistos = get_option( 'rau_psk_vistos', array() ); if ( ! is_array( $vistos ) ) $vistos = array();
	$n = 0;
	foreach ( array_slice( (array) ( $p['items'] ?? array() ), 0, 60 ) as $it ) {
		$c = strtoupper( trim( (string) ( $it['de'] ?? '' ) ) );
		if ( ! rau_psk_indicativo_valido( $c ) ) continue;
		$f = $dial + max( 0, min( 3500, (int) ( $it['f'] ?? 0 ) ) );
		$k = $c . '|' . rau_psk_banda( $f ) . '|' . $modo;
		if ( isset( $vistos[ $k ] ) && $vistos[ $k ] > $ahora - HOUR_IN_SECONDS ) continue;   // una vez por hora y banda
		$vistos[ $k ] = $ahora;
		$g = strtoupper( (string) ( $it['cuadricula'] ?? '' ) );
		$cola[] = array( 'c' => $c, 'f' => $f, 'm' => $modo, 's' => isset( $it['snr'] ) && is_numeric( $it['snr'] ) ? max( -50, min( 50, (int) $it['snr'] ) ) : 0,
			'g' => preg_match( '/^[A-R]{2}[0-9]{2}([A-X]{2})?$/', $g ) ? $g : '', 't' => $ranura );
		$n++;
	}
	foreach ( $vistos as $k => $t ) if ( $t < $ahora - 2 * HOUR_IN_SECONDS ) unset( $vistos[ $k ] );
	update_option( 'rau_psk_vistos', $vistos, false );
	if ( $n ) update_option( RAU_PSK_COLA, array_slice( $cola, -600 ), false );
	return array( 'ok' => true, 'nuevos' => $n );
}

/* ── Armado del datagrama IPFIX ── */
function rau_psk_cadena( $s ) { $s = substr( (string) $s, 0, 254 ); return chr( strlen( $s ) ) . $s; }
function rau_psk_relleno( $b ) { $r = strlen( $b ) % 4; return $r ? $b . str_repeat( "\0", 4 - $r ) : $b; }
/* Plantillas: receptor (indicativo, locator, programa) y emisor (indicativo, frecuencia, SNR, modo, locator, origen, hora) */
function rau_psk_plantillas() {
	$rx = "\x00\x03\x00\x24\x99\x92\x00\x03\x00\x01" . "\x80\x02\xFF\xFF\x00\x00\x76\x8F" . "\x80\x04\xFF\xFF\x00\x00\x76\x8F" . "\x80\x08\xFF\xFF\x00\x00\x76\x8F" . "\x00\x00";
	$tx = "\x99\x93\x00\x07"
		. "\x80\x01\xFF\xFF\x00\x00\x76\x8F"   // senderCallsign
		. "\x80\x05\x00\x04\x00\x00\x76\x8F"   // frequency (4)
		. "\x80\x06\x00\x01\x00\x00\x76\x8F"   // sNR (1)
		. "\x80\x0A\xFF\xFF\x00\x00\x76\x8F"   // mode
		. "\x80\x03\xFF\xFF\x00\x00\x76\x8F"   // senderLocator
		. "\x80\x0B\x00\x01\x00\x00\x76\x8F"   // informationSource (1)
		. "\x00\x96\x00\x04";                  // flowStartSeconds (4)
	return $rx . "\x00\x02" . pack( 'n', 4 + strlen( $tx ) ) . $tx;
}
function rau_psk_paquete( $spots, $con_plantillas, $secuencia, $id, $ahora = null ) {
	$ahora = $ahora ?: time();
	$l = get_option( 'rau_sdr_lugar' );
	$loc = is_array( $l ) && isset( $l['lat'] ) ? rau_psk_locator( (float) $l['lat'], (float) $l['lng'] ) : '';
	$rx = rau_psk_relleno( "\x99\x92\0\0" . rau_psk_cadena( RAU_KIWI_INDICATIVO ) . rau_psk_cadena( $loc ) . rau_psk_cadena( 'RAU receptor web ' . RAU_THEME_VERSION ) );
	$rx = substr_replace( $rx, pack( 'n', strlen( $rx ) ), 2, 2 );
	$tx = '';
	foreach ( $spots as $s ) {
		$tx .= rau_psk_cadena( $s['c'] ) . pack( 'N', $s['f'] ) . pack( 'c', $s['s'] ) . rau_psk_cadena( $s['m'] ) . rau_psk_cadena( $s['g'] ) . "\x01" . pack( 'N', $s['t'] );
	}
	$tx = rau_psk_relleno( "\x99\x93\0\0" . $tx );
	$tx = substr_replace( $tx, pack( 'n', strlen( $tx ) ), 2, 2 );
	$cuerpo = ( $con_plantillas ? rau_psk_plantillas() : '' ) . $rx . $tx;
	return pack( 'nnNNN', 0x000A, 16 + strlen( $cuerpo ), $ahora, $secuencia, $id ) . $cuerpo;
}

/* ── Envío cada 5 minutos (cron) ── */
add_action( 'rau_em_avisos_cron', 'rau_psk_enviar_cola', 20 ); // reutiliza el cron de 5 minutos del tema
function rau_psk_enviar_cola( $ahora = null ) {
	$ahora = $ahora ?: time();
	$cola = get_option( RAU_PSK_COLA, array() );
	if ( ! is_array( $cola ) || ! $cola ) return 0;
	$est = get_option( 'rau_psk_estado', array() ); if ( ! is_array( $est ) ) $est = array();
	if ( ! empty( $est['ultimo'] ) && $ahora - $est['ultimo'] < 290 ) return 0;
	$id  = isset( $est['id'] ) ? (int) $est['id'] : random_int( 1, 0x7FFFFFFF );
	$seq = (int) ( $est['seq'] ?? 0 );
	$n_env = (int) ( $est['enviados'] ?? 0 );
	$plant = $n_env < 3 || $ahora - (int) ( $est['plantillas'] ?? 0 ) > HOUR_IN_SECONDS;
	$paquetes = array(); $lote = array(); $total = 0;
	foreach ( $cola as $s ) {
		$lote[] = $s;
		if ( strlen( rau_psk_paquete( $lote, $plant && ! $paquetes, $seq, $id, $ahora ) ) > 1300 ) { array_pop( $lote ); $paquetes[] = $lote; $lote = array( $s ); }
	}
	if ( $lote ) $paquetes[] = $lote;
	$ok = true; $error = '';
	foreach ( $paquetes as $i => $lote ) {
		$dg = rau_psk_paquete( $lote, $plant && 0 === $i, $seq, $id, $ahora );
		$r = rau_psk_udp( $dg );
		if ( true !== $r ) { $ok = false; $error = $r; break; }
		$seq += count( $lote ); $total += count( $lote ); $n_env++;
	}
	$est = array( 'id' => $id, 'seq' => $seq, 'enviados' => $n_env, 'ultimo' => $ahora, 'plantillas' => $ok && $plant ? $ahora : (int) ( $est['plantillas'] ?? 0 ),
		'resultado' => $ok ? sprintf( '%d reportes enviados', $total ) : 'Error: ' . $error, 'cuando' => $ahora );
	update_option( 'rau_psk_estado', $est, false );
	if ( $ok ) update_option( RAU_PSK_COLA, array(), false );
	return $total;
}
/* UDP a report.pskreporter.info:4739, siempre desde el mismo puerto local si el servidor lo permite.
   El filtro rau_psk_udp permite interceptarlo (pruebas) o desactivarlo (devolviendo un texto de error). */
function rau_psk_udp( $datos ) {
	$pre = apply_filters( 'rau_psk_udp', null, $datos );
	if ( null !== $pre ) return $pre;
	$destino = 'udp://' . apply_filters( 'rau_psk_servidor', 'report.pskreporter.info:4739' );
	$ctx = stream_context_create( array( 'socket' => array( 'bindto' => '0.0.0.0:' . (int) get_option( 'rau_psk_puerto', 47390 ) ) ) );
	$s = @stream_socket_client( $destino, $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $ctx );
	if ( ! $s ) $s = @stream_socket_client( $destino, $errno, $errstr, 5 );
	if ( ! $s ) return $errstr ?: 'no se pudo abrir el socket UDP';
	$n = @fwrite( $s, $datos ); fclose( $s );
	return $n === strlen( $datos ) ? true : 'envío incompleto';
}
