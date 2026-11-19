<?php
/**
 * Avisos del centro de mando al celular, con la página cerrada (Web Push con las llaves VAPID del tema).
 *
 * Quien está en una operación suscribe su navegador desde «Alarmas». El servidor avisa:
 *  · en el momento: mensaje de EMERGENCIA, URGENCIA reportada por una estación, incidente crítico;
 *  · por tiempo (cron cada 5 min): mensaje de emergencia sin entregar a los 10 min, incidente crítico
 *    sin responsable a los 15 min.
 * Cada aviso sale una sola vez. A quien registró el evento no se le avisa de lo suyo. En un simulacro el
 * aviso lo dice. Las suscripciones vencidas (el servicio push responde 404 o 410) se borran.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_EM_AVISOS_OPCION', 'rau_em_avisos' );

function rau_em_avisos_subs() { $s = get_option( RAU_EM_AVISOS_OPCION, array() ); return is_array( $s ) ? $s : array(); }
function rau_em_avisos_guardar( $subs ) { update_option( RAU_EM_AVISOS_OPCION, array_values( $subs ), false ); }

add_action( 'rest_api_init', function() {
	$cod = '(?P<cod>[A-Za-z0-9]{4,12})';
	register_rest_route( 'rau-em/v1', '/operaciones/' . $cod . '/avisos', array(
		array( 'methods' => 'POST', 'callback' => 'rau_em_api_avisos_alta', 'permission_callback' => '__return_true' ),
		array( 'methods' => 'DELETE', 'callback' => 'rau_em_api_avisos_baja', 'permission_callback' => '__return_true' ),
	) );
} );

function rau_em_api_avisos_alta( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req, 'ver' );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op, $a ) = $c;
	if ( ! function_exists( 'rau_push_validar_suscripcion' ) || ! rau_get_vapid_keys() ) return rau_em_error( 'Los avisos no están disponibles en este sitio', 503 );
	$sub = rau_push_validar_suscripcion( $req->get_json_params() );
	if ( is_string( $sub ) ) return rau_em_error( $sub );
	$subs = array_filter( rau_em_avisos_subs(), function( $s ) use ( $sub, $op ) { return ! ( $s['endpoint'] === $sub['endpoint'] && $s['op'] === $op['codigo'] ); } );
	if ( count( $subs ) >= 3000 ) return rau_em_error( 'Se alcanzó el máximo de suscripciones', 429 );
	$subs[] = array( 'op' => $op['codigo'], 'endpoint' => $sub['endpoint'], 'keys' => $sub['keys'], 'ind' => $a['nombre'], 'rol' => $a['rol'], 'creado' => time() );
	rau_em_avisos_guardar( $subs );
	return array( 'ok' => true );
}
function rau_em_api_avisos_baja( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req, 'ver' );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op ) = $c;
	$ep = (string) $req->get_param( 'endpoint' );
	rau_em_avisos_guardar( array_filter( rau_em_avisos_subs(), function( $s ) use ( $ep, $op ) { return ! ( $s['endpoint'] === $ep && $s['op'] === $op['codigo'] ); } ) );
	return array( 'ok' => true );
}

/* Envío a un suscriptor. $esperar=false no espera la respuesta (avisos en el momento, sin demorar el registro). */
function rau_em_aviso_enviar( $sub, $titulo, $cuerpo, $url, $esperar ) {
	$vapid = rau_get_vapid_keys();
	if ( ! $vapid ) return 0;
	$datos = wp_json_encode( array( 'title' => $titulo, 'body' => $cuerpo, 'url' => $url ) );
	$r = wp_remote_post( $sub['endpoint'], array(
		'timeout'  => 8,
		'blocking' => $esperar,
		'headers'  => array( 'Content-Type' => 'application/octet-stream', 'Content-Encoding' => 'aes128gcm', 'TTL' => '3600', 'Urgency' => 'high',
			'Authorization' => 'vapid t=' . rau_build_vapid_jwt( $sub['endpoint'], $vapid['private_pem'] ) . ', k=' . $vapid['public'] ),
		'body'     => rau_encrypt_push_payload( $datos, $sub['keys']['p256dh'], $sub['keys']['auth'] ),
	) );
	return $esperar && ! is_wp_error( $r ) ? (int) wp_remote_retrieve_response_code( $r ) : 0;
}
/* Avisa a los suscritos de la operación, una sola vez por clave, salvo a quien causó el aviso */
function rau_em_avisar( $op, $clave, $texto, $excepto = '', $esperar = false ) {
	$hechos = get_option( 'rau_em_avisos_hechos', array() );
	if ( ! is_array( $hechos ) ) $hechos = array();
	$k = $op['codigo'] . ':' . $clave;
	if ( isset( $hechos[ $k ] ) ) return 0;
	$hechos[ $k ] = time();
	// Se conservan los últimos 3 días
	foreach ( $hechos as $h => $t ) if ( $t < time() - 3 * DAY_IN_SECONDS ) unset( $hechos[ $h ] );
	update_option( 'rau_em_avisos_hechos', $hechos, false );
	$titulo = ( 'simulacro' === $op['tipo'] ? '[SIMULACRO] ' : '' ) . 'Centro de mando · ' . $op['nombre'];
	$url    = add_query_arg( 'op', $op['codigo'], home_url( '/emergencias/' ) );
	$subs   = rau_em_avisos_subs(); $quedan = array(); $n = 0;
	foreach ( $subs as $s ) {
		if ( $s['op'] !== $op['codigo'] || ( $excepto && $s['ind'] === $excepto ) ) { $quedan[] = $s; continue; }
		$codigo = rau_em_aviso_enviar( $s, $titulo, $texto, $url, $esperar );
		$n++;
		if ( 404 === $codigo || 410 === $codigo ) continue; // suscripción vencida: se borra
		$quedan[] = $s;
	}
	if ( count( $quedan ) !== count( $subs ) ) rau_em_avisos_guardar( $quedan );
	return $n;
}

/* En el momento: lo que llega por la API */
add_action( 'rau_em_eventos_guardados', function( $op, $eventos, $actor ) {
	foreach ( $eventos as $e ) {
		$d = $e['d'];
		if ( 'msg' === $e['t'] && 'E' === ( $d['prec'] ?? '' ) && empty( $d['prueba'] ) ) {
			rau_em_avisar( $op, 'msgE:' . $e['u'], 'Mensaje de EMERGENCIA n.º ' . ( $d['num'] ?? 's/n' ) . ' para ' . ( $d['para'] ?? '' ) . ': ' . wp_trim_words( $d['texto'] ?? '', 18 ), $actor['nombre'] );
		} elseif ( 'contacto' === $e['t'] && 'urgente' === ( $d['estado'] ?? '' ) ) {
			rau_em_avisar( $op, 'urg:' . $e['u'], ( $d['ind'] ?? '' ) . ' reporta URGENCIA' . ( ! empty( $d['nota'] ) ? ': ' . $d['nota'] : '' ), $actor['nombre'] );
		} elseif ( 'i_nuevo' === $e['t'] && 'critica' === ( $d['prio'] ?? '' ) ) {
			rau_em_avisar( $op, 'inc:' . $e['u'], 'Incidente CRÍTICO: ' . ( $d['titulo'] ?? '' ) . ( ! empty( $d['lugar'] ) ? ' · ' . $d['lugar'] : '' ), $actor['nombre'] );
		}
	}
}, 10, 3 );

/* Por tiempo: cada 5 minutos se revisan las operaciones abiertas que tienen suscritos */
add_filter( 'cron_schedules', function( $s ) { $s['rau_5min'] = array( 'interval' => 300, 'display' => 'Cada 5 minutos' ); return $s; } );
add_action( 'init', function() { if ( ! wp_next_scheduled( 'rau_em_avisos_cron' ) ) wp_schedule_event( time() + 60, 'rau_5min', 'rau_em_avisos_cron' ); } );
add_action( 'rau_em_avisos_cron', 'rau_em_avisos_revisar' );
function rau_em_avisos_revisar( $ahora = null ) {
	global $wpdb;
	$ahora = $ahora ?: time();
	$t = rau_em_tablas();
	$ops = array_unique( array_map( function( $s ) { return $s['op']; }, rau_em_avisos_subs() ) );
	foreach ( $ops as $cod ) {
		$op = rau_em_operacion( $cod );
		if ( ! $op || 'cerrado' === $op['estado'] ) continue;
		$filas = $wpdb->get_results( $wpdb->prepare( "SELECT uuid, tipo, ts_cli, datos FROM {$t['ev']} WHERE inc = %s AND tipo IN ('msg','m_est','i_nuevo','i_resp','i_estado','i_verif','i_fusion') AND ts_cli > %d ORDER BY id", $cod, ( $ahora - 2 * DAY_IN_SECONDS ) * 1000 ), ARRAY_A );
		$msg = array(); $inc = array();
		foreach ( $filas as $f ) {
			$d = json_decode( $f['datos'], true ) ?: array();
			switch ( $f['tipo'] ) {
				case 'msg': if ( 'E' === ( $d['prec'] ?? '' ) && empty( $d['prueba'] ) ) $msg[ $f['uuid'] ] = array( 'ts' => (int) $f['ts_cli'], 'd' => $d, 'fin' => false ); break;
				case 'm_est': if ( isset( $msg[ $d['m'] ?? '' ] ) && in_array( $d['e'] ?? '', array( 'entregado', 'confirmado', 'anulado' ), true ) ) $msg[ $d['m'] ]['fin'] = true; break;
				case 'i_nuevo': if ( 'critica' === ( $d['prio'] ?? '' ) ) $inc[ $f['uuid'] ] = array( 'ts' => (int) $f['ts_cli'], 'd' => $d, 'resp' => false, 'fin' => false ); break;
				case 'i_resp': if ( isset( $inc[ $d['id'] ?? '' ] ) ) $inc[ $d['id'] ]['resp'] = true; break;
				case 'i_estado': if ( isset( $inc[ $d['id'] ?? '' ] ) && in_array( $d['e'] ?? '', array( 'resuelto', 'cerrado' ), true ) ) $inc[ $d['id'] ]['fin'] = true; break;
				case 'i_verif': if ( isset( $inc[ $d['id'] ?? '' ] ) && 'descartado' === ( $d['v'] ?? '' ) ) $inc[ $d['id'] ]['fin'] = true; break;
				case 'i_fusion': if ( isset( $inc[ $d['de'] ?? '' ] ) ) $inc[ $d['de'] ]['fin'] = true; break;
			}
		}
		foreach ( $msg as $u => $m ) {
			$min = ( $ahora * 1000 - $m['ts'] ) / 60000;
			if ( ! $m['fin'] && $min >= 10 ) rau_em_avisar( $op, 'msgE10:' . $u, 'Mensaje de EMERGENCIA n.º ' . ( $m['d']['num'] ?? 's/n' ) . ' para ' . ( $m['d']['para'] ?? '' ) . ' lleva ' . floor( $min ) . ' min sin entregarse', '', true );
		}
		foreach ( $inc as $u => $x ) {
			$min = ( $ahora * 1000 - $x['ts'] ) / 60000;
			if ( ! $x['resp'] && ! $x['fin'] && $min >= 15 ) rau_em_avisar( $op, 'resp15:' . $u, 'Incidente CRÍTICO sin responsable hace ' . floor( $min ) . ' min: ' . ( $x['d']['titulo'] ?? '' ), '', true );
		}
	}
}
