<?php
/**
 * Capacidades de los miembros para emergencias: qué equipo y recursos tiene cada quien (HF, generador,
 * vehículo…) y en qué municipio está. Se editan en la ficha del miembro, en el escritorio.
 *
 * Solo las ve la coordinación del centro de mando (cuentas con un rol de emergencias) y solo de quienes
 * lo autorizaron con su propia casilla, aparte de la del directorio público: alguien puede no querer
 * figurar en el directorio pero sí estar disponible para una emergencia, y al revés.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function rau_capacidades_lista() {
	return array( 'hf' => 'HF (onda corta)', 'nvis' => 'Antena para NVIS', 'vhf' => 'VHF/UHF fija', 'portatil' => 'Portátil VHF/UHF', 'movil' => 'Estación móvil en vehículo',
		'digital' => 'Modos digitales (Winlink, JS8, FT8)', 'aprs' => 'APRS', 'satelite' => 'Satélite', 'generador' => 'Generador', 'solar' => 'Energía solar', 'bateria' => 'Batería de respaldo',
		'vehiculo' => 'Vehículo para desplazarse', 'todoterreno' => 'Vehículo 4×4', 'primeros' => 'Primeros auxilios', 'repetidora' => 'Repetidora portátil' );
}
add_action( 'add_meta_boxes_miembro', function() {
	add_meta_box( 'rau_miembro_capacidades', 'Capacidades para emergencias', function( $post ) {
		wp_nonce_field( 'rau_miembro_cap', 'rau_miembro_cap_nonce' );
		$tiene = (array) get_post_meta( $post->ID, 'capacidades', true );
		echo '<p><label><input type="checkbox" name="rau_cap_coord" value="1"' . checked( '1' === get_post_meta( $post->ID, 'cap_coord', true ), true, false ) . '> <strong>Autorizó</strong> que la coordinación de emergencias vea estas capacidades</label></p><div style="columns:2">';
		foreach ( rau_capacidades_lista() as $k => $n ) echo '<label style="display:block"><input type="checkbox" name="rau_cap[]" value="' . esc_attr( $k ) . '"' . checked( in_array( $k, $tiene, true ), true, false ) . '> ' . esc_html( $n ) . '</label>';
		echo '</div><p><label>Municipio donde suele estar<br><input type="text" name="rau_cap_municipio" class="widefat" value="' . esc_attr( get_post_meta( $post->ID, 'cap_municipio', true ) ) . '"></label></p>';
		echo '<p><label>Notas (autonomía, horarios, limitaciones)<br><input type="text" name="rau_cap_nota" class="widefat" maxlength="200" value="' . esc_attr( get_post_meta( $post->ID, 'cap_nota', true ) ) . '"></label></p>';
	}, 'miembro', 'normal' );
} );
add_action( 'save_post_miembro', function( $post_id ) {
	if ( ! isset( $_POST['rau_miembro_cap_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rau_miembro_cap_nonce'] ), 'rau_miembro_cap' ) ) return;
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) return;
	$validas = array_keys( rau_capacidades_lista() );
	update_post_meta( $post_id, 'capacidades', array_values( array_intersect( array_map( 'sanitize_key', (array) ( $_POST['rau_cap'] ?? array() ) ), $validas ) ) );
	update_post_meta( $post_id, 'cap_coord', empty( $_POST['rau_cap_coord'] ) ? '0' : '1' );
	update_post_meta( $post_id, 'cap_municipio', sanitize_text_field( wp_unslash( $_POST['rau_cap_municipio'] ?? '' ) ) );
	update_post_meta( $post_id, 'cap_nota', mb_substr( sanitize_text_field( wp_unslash( $_POST['rau_cap_nota'] ?? '' ) ), 0, 200 ) );
} );

function rau_capacidades_datos() {
	$ids = get_posts( array( 'post_type' => 'miembro', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'cap_coord', 'value' => '1' ) ) ) );
	$out = array();
	foreach ( $ids as $id ) {
		$c = array_values( array_intersect( (array) get_post_meta( $id, 'capacidades', true ), array_keys( rau_capacidades_lista() ) ) );
		if ( ! $c ) continue;
		$out[] = array( 'ind' => (string) get_post_meta( $id, 'indicativo', true ), 'nombre' => get_the_title( $id ), 'municipio' => (string) ( get_post_meta( $id, 'cap_municipio', true ) ?: get_post_meta( $id, 'ciudad', true ) ),
			'cap' => $c, 'nota' => (string) get_post_meta( $id, 'cap_nota', true ) );
	}
	usort( $out, function( $a, $b ) { return strcmp( $a['municipio'] . $a['ind'], $b['municipio'] . $b['ind'] ); } );
	return $out;
}
add_action( 'rest_api_init', function() {
	register_rest_route( 'rau-em/v1', '/capacidades', array( 'methods' => 'GET',
		'permission_callback' => function() { return function_exists( 'rau_em_rol_de_usuario' ) && in_array( rau_em_rol_de_usuario( wp_get_current_user() ), array( 'admin', 'coordinador', 'recursos' ), true ); },
		'callback' => function() { $r = rest_ensure_response( array( 'nombres' => rau_capacidades_lista(), 'miembros' => rau_capacidades_datos() ) ); $r->header( 'Cache-Control', 'private, no-store' ); return $r; } ) );
} );
