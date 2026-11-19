<?php
/**
 * Cuántas veces se abre cada herramienta, por mes. Sirve para decidir con datos cuáles fusionar o retirar.
 *
 * Solo se guarda un contador por herramienta y mes: nada de IP, cuenta, navegador ni hora. Para que un
 * mismo visitante no infle la cuenta, el navegador avisa una vez por sesión y herramienta, y el servidor
 * acepta como mucho un aviso por dirección IP, herramienta y hora (la IP solo se usa, cifrada, para ese
 * límite temporal y se descarta). Se conservan 13 meses. Se ve en el Escritorio → «Herramientas más usadas».
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_USO_OPCION', 'rau_herr_uso' );

function rau_uso_registrar( $id, $mes = null ) {
	$valido = false; foreach ( rau_get_herramientas_tabs() as $t ) if ( $t['id'] === $id ) { $valido = true; break; }
	if ( ! $valido ) return false;
	$mes = $mes ?: gmdate( 'Y-m' );
	$u = get_option( RAU_USO_OPCION, array() ); if ( ! is_array( $u ) ) $u = array();
	$u[ $mes ][ $id ] = ( $u[ $mes ][ $id ] ?? 0 ) + 1;
	ksort( $u ); $u = array_slice( $u, -13, null, true );
	update_option( RAU_USO_OPCION, $u, false );
	return true;
}
add_action( 'rest_api_init', function() {
	register_rest_route( 'rau/v1', '/uso', array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function( WP_REST_Request $r ) {
		$id = sanitize_key( (string) ( $r->get_param( 'h' ) ?? '' ) );
		if ( '' === $id ) { $b = json_decode( $r->get_body(), true ); $id = sanitize_key( (string) ( $b['h'] ?? '' ) ); }
		$clave = 'rau_uso_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . '|' . $id . '|' . wp_salt( 'nonce' ) );
		if ( get_transient( $clave ) ) return new WP_REST_Response( null, 204 );
		set_transient( $clave, 1, HOUR_IN_SECONDS );
		rau_uso_registrar( $id );
		return new WP_REST_Response( null, 204 );
	} ) );
} );

/* Resumen: suma de los últimos N meses, ordenado */
function rau_uso_resumen( $meses = 1 ) {
	$u = get_option( RAU_USO_OPCION, array() ); if ( ! is_array( $u ) ) return array();
	$tot = array();
	foreach ( array_slice( $u, -$meses, null, true ) as $m ) foreach ( $m as $id => $n ) $tot[ $id ] = ( $tot[ $id ] ?? 0 ) + $n;
	arsort( $tot ); return $tot;
}
add_action( 'wp_dashboard_setup', function() {
	if ( ! current_user_can( 'edit_posts' ) ) return;
	wp_add_dashboard_widget( 'rau_uso_herramientas', 'Herramientas más usadas', function() {
		$mes = rau_uso_resumen( 1 ); $tres = rau_uso_resumen( 3 );
		$nombres = array(); foreach ( rau_get_herramientas_tabs() as $t ) if ( empty( $t['oculta'] ) ) $nombres[ $t['id'] ] = $t['label'];
		if ( ! $tres ) { echo '<p>Todavía no hay datos. Se empiezan a contar desde la versión 19.60.0.</p>'; return; }
		echo '<p>Aperturas de cada herramienta (un aviso por visitante, herramienta y hora). Sin datos personales.</p><table class="widefat striped"><thead><tr><th>Herramienta</th><th>Este mes</th><th>3 meses</th></tr></thead><tbody>';
		foreach ( $tres as $id => $n ) echo '<tr><td>' . esc_html( $nombres[ $id ] ?? $id ) . '</td><td>' . (int) ( $mes[ $id ] ?? 0 ) . '</td><td>' . (int) $n . '</td></tr>';
		echo '</tbody></table>';
		$nunca = array_diff_key( $nombres, $tres );
		if ( $nunca ) echo '<p><strong>Sin aperturas en 3 meses:</strong> ' . esc_html( implode( ', ', $nunca ) ) . '.</p>';
	} );
} );
