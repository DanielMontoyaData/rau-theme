<?php
/**
 * JS8 oído en el receptor HK4RAU, para el centro de mando.
 *
 * El decodificador JS8 corre en el navegador de quien escucha el receptor. Con sesión iniciada en el sitio,
 * lo decodificado (sin los latidos automáticos) se envía aquí y el centro de mando lo muestra en «Ahora
 * mismo», marcado como oído en el receptor, con quién lo reportó. Sin sesión no se acepta nada: así cada
 * entrada tiene responsable y nadie anónimo puede inventar tráfico de emergencia.
 *
 * Se guardan las últimas 6 horas (hasta 400 entradas). Lo mismo oído por dos personas cuenta una vez.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_JS8_OPCION', 'rau_js8_oido' );

add_action( 'rest_api_init', function() {
	register_rest_route( 'rau/v1', '/js8', array(
		'methods'             => 'POST',
		'permission_callback' => function() { return is_user_logged_in(); },
		'callback'            => 'rau_js8_recibir',
	) );
} );

function rau_js8_recibir( WP_REST_Request $req ) {
	$uid = get_current_user_id();
	// Una ranura dura 15 s: más de un envío cada 10 s por persona no tiene sentido
	$clave = 'rau_js8_ult_' . $uid;
	if ( get_transient( $clave ) ) return new WP_REST_Response( array( 'error' => 'Demasiado seguido' ), 429 );
	set_transient( $clave, 1, 10 );

	$p      = $req->get_json_params();
	$ranura = isset( $p['ranura'] ) ? (int) floor( $p['ranura'] / 1000 ) : 0;
	$ahora  = time();
	if ( $ranura < $ahora - 600 || $ranura > $ahora + 60 ) return new WP_REST_Response( array( 'error' => 'Ranura fuera de tiempo' ), 400 );
	$dial = isset( $p['dial'] ) ? (int) $p['dial'] : 0;
	if ( $dial < 1800000 || $dial > 54000000 ) return new WP_REST_Response( array( 'error' => 'Frecuencia no válida' ), 400 );
	$items = isset( $p['items'] ) && is_array( $p['items'] ) ? array_slice( $p['items'], 0, 30 ) : array();

	$u    = wp_get_current_user();
	$quien = $u->display_name ?: $u->user_login;
	$lista = rau_js8_lista();
	$hay   = array();
	foreach ( $lista as $x ) $hay[ $x['k'] ] = true;
	$n = 0;
	foreach ( $items as $it ) {
		if ( ! is_array( $it ) ) continue;
		// Solo el alfabeto de JS8 (mayúsculas, números, signos) y un largo razonable
		$texto = strtoupper( trim( (string) ( $it['texto'] ?? '' ) ) );
		// Sin < ni >: el indicativo incompleto «<....>» de JS8 queda como «....»
		$texto = preg_replace( '/[^A-Z0-9 @\/\-+?.,:!\'"()=&#]/u', '', $texto );
		$texto = trim( preg_replace( '/\s+/', ' ', $texto ) );
		if ( '' === $texto || strlen( $texto ) > 300 ) continue;
		$tipo = in_array( $it['tipo'] ?? '', array( 'dirigido', 'datos', 'compuesto', 'cq', 'mensaje' ), true ) ? $it['tipo'] : 'otro';
		$f    = max( 0, min( 3500, (int) ( $it['f'] ?? 0 ) ) );
		$k    = md5( $ranura . '|' . $texto );
		if ( isset( $hay[ $k ] ) ) continue;
		$hay[ $k ] = true;
		$ind = function( $v ) { $v = strtoupper( (string) $v ); return preg_match( '/^@?[A-Z0-9\/]{2,12}$/', $v ) ? $v : ''; };
		$lista[] = array( 'k' => $k, 't' => $ranura, 'mhz' => round( ( $dial + $f ) / 1e6, 6 ), 'texto' => $texto, 'tipo' => $tipo,
			'de' => $ind( $it['de'] ?? '' ), 'para' => $ind( $it['para'] ?? '' ), 'grupo' => $ind( $it['grupo'] ?? '' ),
			'snr' => isset( $it['snr'] ) && is_numeric( $it['snr'] ) ? max( -40, min( 40, (int) $it['snr'] ) ) : null, 'por' => $quien );
		$n++;
	}
	if ( $n ) {
		$lista = array_values( array_filter( $lista, function( $x ) use ( $ahora ) { return $x['t'] > $ahora - 6 * HOUR_IN_SECONDS; } ) );
		if ( count( $lista ) > 400 ) $lista = array_slice( $lista, -400 );
		update_option( RAU_JS8_OPCION, $lista, false );
	}
	return array( 'ok' => true, 'nuevos' => $n );
}
function rau_js8_lista() { $l = get_option( RAU_JS8_OPCION, array() ); return is_array( $l ) ? $l : array(); }

/* Para el centro de mando: lo de las últimas 2 horas, primero lo dirigido a grupos de emergencia */
function rau_js8_para_sala( $horas = 2 ) {
	$desde = time() - $horas * HOUR_IN_SECONDS;
	$l = array_values( array_filter( rau_js8_lista(), function( $x ) use ( $desde ) { return $x['t'] > $desde; } ) );
	$emerg = array( '@EMCOMM', '@ARES', '@RACES', '@RAYNET', '@AMRRON', '@MARS', '@SKYWARN', '@ALLCALL', '@NTS', '@NET' );
	foreach ( $l as &$x ) { $x['emergencia'] = in_array( $x['grupo'], $emerg, true ) || in_array( $x['para'], $emerg, true ); unset( $x['k'] ); }
	unset( $x );
	usort( $l, function( $a, $b ) { return ( $b['emergencia'] <=> $a['emergencia'] ) ?: ( $b['t'] - $a['t'] ); } );
	return array_slice( $l, 0, 15 );
}
