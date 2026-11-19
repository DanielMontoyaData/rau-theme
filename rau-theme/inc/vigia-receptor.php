<?php
/**
 * «Avísame si aparece» con la página cerrada: avisos al celular cuando el receptor del club oye un
 * indicativo o prefijo, o cuando una banda empieza a oírse.
 *
 * Desde Herramientas, quien vigila algo puede activar los avisos al celular (Web Push con las llaves VAPID
 * del tema). El servidor guarda su lista (hasta 10 entradas) y, cada 10 minutos, la compara con:
 *  · lo que decodifica el receptor HK4RAU (sus reportes a PSK Reporter, rau_sdr_oido());
 *  · los enlaces desde y hacia Colombia de la última media hora (inc/propagacion.php).
 * Cada coincidencia se avisa una vez cada 3 horas por persona. Las suscripciones vencen a los 30 días
 * (al volver a la página se renuevan solas) y las que el servicio de push rechaza se borran.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_VIGIA_OPCION', 'rau_vigias' );

function rau_vigia_subs() { $s = get_option( RAU_VIGIA_OPCION, array() ); return is_array( $s ) ? $s : array(); }
function rau_vigia_guardar( $s ) { update_option( RAU_VIGIA_OPCION, array_values( $s ), false ); }
function rau_vigia_patrones( $lista ) {
	$out = array();
	foreach ( (array) $lista as $p ) {
		$p = strtoupper( preg_replace( '/[^A-Za-z0-9\/]/', '', (string) $p ) );
		if ( '' === $p || strlen( $p ) > 12 ) continue;
		if ( preg_match( '/^(\d+)M$/', $p, $m ) ) { if ( in_array( (int) $m[1], array( 160, 80, 60, 40, 30, 20, 17, 15, 12, 10, 6 ), true ) ) $out[] = $p; continue; }
		if ( strlen( $p ) >= 2 ) $out[] = $p;
	}
	return array_slice( array_values( array_unique( $out ) ), 0, 10 );
}

add_action( 'rest_api_init', function() {
	register_rest_route( 'rau/v1', '/vigia', array(
		array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => 'rau_vigia_alta' ),
		array( 'methods' => 'DELETE', 'permission_callback' => '__return_true', 'callback' => function( WP_REST_Request $r ) {
			$ep = (string) ( $r->get_json_params()['endpoint'] ?? $r->get_param( 'endpoint' ) );
			rau_vigia_guardar( array_filter( rau_vigia_subs(), function( $s ) use ( $ep ) { return $s['endpoint'] !== $ep; } ) );
			return array( 'ok' => true );
		} ),
	) );
} );
function rau_vigia_alta( WP_REST_Request $req ) {
	$ip = md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) );
	if ( get_transient( 'rau_vigia_' . $ip ) ) return new WP_REST_Response( array( 'error' => 'Espera unos segundos' ), 429 );
	set_transient( 'rau_vigia_' . $ip, 1, 5 );
	if ( ! function_exists( 'rau_push_validar_suscripcion' ) || ! rau_get_vapid_keys() ) return new WP_REST_Response( array( 'error' => 'Los avisos no están disponibles en este sitio' ), 503 );
	$p = $req->get_json_params();
	$sub = rau_push_validar_suscripcion( $p['sub'] ?? null );
	if ( is_string( $sub ) ) return new WP_REST_Response( array( 'error' => $sub ), 400 );
	$pat = rau_vigia_patrones( $p['patrones'] ?? array() );
	$subs = array_filter( rau_vigia_subs(), function( $s ) use ( $sub ) { return $s['endpoint'] !== $sub['endpoint']; } );
	if ( ! $pat ) { rau_vigia_guardar( $subs ); return array( 'ok' => true, 'patrones' => array() ); }
	if ( count( $subs ) >= 2000 ) return new WP_REST_Response( array( 'error' => 'Se alcanzó el máximo de suscripciones' ), 429 );
	$previa = current( array_filter( rau_vigia_subs(), function( $s ) use ( $sub ) { return $s['endpoint'] === $sub['endpoint']; } ) );
	$subs[] = array( 'endpoint' => $sub['endpoint'], 'keys' => $sub['keys'], 'p' => $pat, 'creado' => time(), 'av' => $previa ? (array) $previa['av'] : array() );
	rau_vigia_guardar( $subs );
	return array( 'ok' => true, 'patrones' => $pat );
}

/* Lo que se oye ahora: [indicativo, banda (p. ej. «20m»), detalle, kHz o 0] */
function rau_vigia_oido( $ahora = null ) {
	$ahora = $ahora ?: time(); $l = array(); $bandas = array();
	$o = function_exists( 'rau_sdr_oido' ) ? rau_sdr_oido() : null;
	if ( $o && ! empty( $o['reportes'] ) ) foreach ( $o['reportes'] as $x ) {
		if ( $x['t'] < $ahora - 1800 ) continue;
		$l[] = array( strtoupper( $x['c'] ), $x['b'], 'oído en ' . RAU_KIWI_INDICATIVO . ' en ' . number_format( $x['f'], 1, ',', '.' ) . ' kHz ' . $x['m'] . ', ' . $x['snr'] . ' dB', $x['f'] );
		$bandas[ $x['b'] ] = ( $bandas[ $x['b'] ] ?? 0 ) + 1;
	}
	$p = get_option( 'rau_prop_co', array() );
	if ( is_array( $p ) && ! empty( $p['r'] ) ) foreach ( $p['r'] as $x ) {
		if ( $x['t'] < $ahora - 1800 ) continue;
		$b = str_replace( ' ', '', $x['b'] );
		$l[] = array( strtoupper( $x['o'] ), $b, ( $x['x'] ? 'oyó a ' : 'oído por ' ) . $x['c'] . ' en ' . $x['b'] . ' ' . $x['m'], 0 );
	}
	return array( $l, $bandas );
}
/* Envía y devuelve el código HTTP del servicio de push (404 o 410: la suscripción ya no existe) */
function rau_vigia_enviar( $sub, $titulo, $cuerpo, $url ) {
	return function_exists( 'rau_em_aviso_enviar' ) ? rau_em_aviso_enviar( $sub, $titulo, $cuerpo, $url, true ) : ( rau_send_web_push( $sub, $titulo, $cuerpo, $url ) ? 201 : 0 );
}
function rau_vigia_revisar( $ahora = null, $enviar = 'rau_vigia_enviar' ) {
	$ahora = $ahora ?: time(); $subs = rau_vigia_subs(); if ( ! $subs ) return 0;
	list( $oido, $bandas ) = rau_vigia_oido( $ahora );
	$n = 0; $cambio = false;
	foreach ( $subs as $i => &$s ) {
		if ( $s['creado'] < $ahora - 30 * DAY_IN_SECONDS ) { unset( $subs[ $i ] ); $cambio = true; continue; }
		$s['av'] = array_filter( (array) $s['av'], function( $t ) use ( $ahora ) { return $t > $ahora - 3 * HOUR_IN_SECONDS; } );
		$avisos = array();
		foreach ( $s['p'] as $pat ) {
			if ( preg_match( '/^(\d+)M$/', $pat, $m ) ) {
				$b = $m[1] . 'm'; $k = 'b|' . $b;
				if ( ( $bandas[ $b ] ?? 0 ) >= 3 && empty( $s['av'][ $k ] ) ) { $s['av'][ $k ] = $ahora; $avisos[] = array( $b . ' abierta', 'El receptor del club oye ' . $bandas[ $b ] . ' estaciones en ' . $b . '.', 0 ); }
				continue;
			}
			foreach ( $oido as $x ) {
				if ( 0 !== strpos( $x[0], $pat ) ) continue;
				$k = 'c|' . $x[0] . '|' . $x[1];
				if ( ! empty( $s['av'][ $k ] ) ) continue;
				$s['av'][ $k ] = $ahora; $avisos[] = array( $x[0] . ' en ' . $x[1], $x[2] . '.', $x[3] );
			}
		}
		if ( ! $avisos ) continue;
		$cambio = true;
		$t = 1 === count( $avisos ) ? 'RAU · ' . $avisos[0][0] : 'RAU · ' . count( $avisos ) . ' avisos';
		$c = implode( "\n", array_map( function( $a ) { return $a[0] . ': ' . $a[1]; }, array_slice( $avisos, 0, 4 ) ) );
		$url = $avisos[0][2] ? add_query_arg( array( 'f' => $avisos[0][2] ), home_url( '/receptor/' ) ) : home_url( '/herramientas/' );
		$cod = (int) call_user_func( $enviar, $s, $t, $c, $url );
		if ( 404 === $cod || 410 === $cod ) unset( $subs[ $i ] );
		$n++;
	}
	unset( $s );
	if ( $cambio ) rau_vigia_guardar( $subs );
	return $n;
}
add_action( 'rau_prop_cron', function() { rau_vigia_revisar(); }, 20 );
