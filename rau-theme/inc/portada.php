<?php
/**
 * Datos de la portada (19.61.0). Aquí va lo que front-page.php necesita calcular, para que la
 * plantilla quede en solo HTML.
 *
 * - rau_portada_emergencia(): solo aparece si hay una operación REAL, abierta, pública, de una
 *   organización y con el tablero público publicado por quien coordina. Así nadie puede poner un
 *   aviso en la portada creando una operación de prueba.
 * - rau_portada_datos(): las cifras de «Qué es RAU».
 * - rau_portada_concursos(): los próximos concursos, con la misma regla de fechas que la
 *   herramienta Calendario de Concursos.
 * - rau_checkin_hoy: quiénes se reportaron hoy, con indicativo y ciudad (sin hora ni ubicación).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function rau_portada_url_plantilla( $plantilla, $respaldo ) {
	$p = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => $plantilla, 'number' => 1 ) );
	return $p ? get_permalink( $p[0] ) : home_url( $respaldo );
}

function rau_portada_emergencia() {
	$c = get_transient( 'rau_portada_emergencia' );
	if ( false !== $c ) return $c ?: null;
	$out = '';
	if ( function_exists( 'rau_em_tablas' ) && function_exists( 'rau_em_tablero_opcion' ) ) {
		global $wpdb;
		$t = rau_em_tablas();
		$ops = $wpdb->get_results( $wpdb->prepare( "SELECT codigo, nombre, zona FROM {$t['ops']} WHERE tipo = 'real' AND estado = 'abierto' AND publico = 1 AND org_id > 0 AND actividad > %d ORDER BY actividad DESC LIMIT 5", time() - 12 * HOUR_IN_SECONDS ), ARRAY_A );
		foreach ( (array) $ops as $o ) {
			$tab = get_option( rau_em_tablero_opcion( $o['codigo'] ) );
			if ( ! is_array( $tab ) || empty( $tab['token'] ) ) continue;
			$red = $tab['resumen']['red'] ?? null;
			$out = array( 'nombre' => $o['nombre'], 'zona' => $o['zona'], 'canal' => is_array( $red ) ? (string) ( $red['canal'] ?? '' ) : '', 'url' => home_url( '/tablero-emergencia/' . $tab['token'] . '/' ) );
			break;
		}
	}
	set_transient( 'rau_portada_emergencia', $out, 2 * MINUTE_IN_SECONDS );
	return $out ?: null;
}

function rau_portada_datos() {
	$d = get_transient( 'rau_portada_datos' );
	if ( is_array( $d ) ) return $d;
	$mallas = get_posts( array( 'post_type' => 'evento', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'recurrencia', 'value' => 'semanal' ) ) ) );
	$ids = get_posts( array( 'post_type' => 'checkin', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'date_query' => array( array( 'after' => '30 days ago' ) ) ) );
	$ind = array();
	foreach ( $ids as $id ) { $i = strtoupper( trim( (string) get_post_meta( $id, 'indicativo', true ) ) ); if ( $i ) $ind[ $i ] = 1; }
	$d = array( 'repetidoras' => (int) ( wp_count_posts( 'repetidor' )->publish ?? 0 ), 'mallas' => count( $mallas ), 'colegas' => count( $ind ) );
	set_transient( 'rau_portada_datos', $d, HOUR_IN_SECONDS );
	return $d;
}
foreach ( array( 'save_post_checkin', 'save_post_evento', 'save_post_repetidor' ) as $h ) add_action( $h, function() { delete_transient( 'rau_portada_datos' ); } );

/* Sábado del n-ésimo fin de semana completo del mes (n = -1: el último), en UTC */
function rau_portada_fin_de_semana( $anio, $mes0, $n ) {
	$dias = (int) gmdate( 't', gmmktime( 0, 0, 0, $mes0 + 1, 1, $anio ) );
	$sab = array();
	for ( $d = 1; $d < $dias; $d++ ) { $ts = gmmktime( 0, 0, 0, $mes0 + 1, $d, $anio ); if ( 6 === (int) gmdate( 'w', $ts ) ) $sab[] = $ts; }
	if ( ! $sab ) return 0;
	return $n < 0 ? $sab[ count( $sab ) + $n ] : ( $sab[ $n - 1 ] ?? 0 );
}
function rau_portada_concursos( $cuantos = 2, $ahora = null ) {
	$ahora = $ahora ?: time();
	$anio = (int) gmdate( 'Y', $ahora );
	$out = array();
	foreach ( rau_get_concursos() as $c ) {
		$ts = rau_portada_fin_de_semana( $anio, $c['mes'], $c['n_semana'] );
		if ( ! $ts || $ts + 2 * DAY_IN_SECONDS < $ahora ) $ts = rau_portada_fin_de_semana( $anio + 1, $c['mes'], $c['n_semana'] );
		if ( $ts ) $out[] = array( 'nombre' => $c['n'], 'ts' => $ts );
	}
	usort( $out, function( $a, $b ) { return $a['ts'] - $b['ts']; } );
	return array_slice( $out, 0, $cuantos );
}

/* Quiénes se reportaron hoy: lo pide la portada después de cargar, para que la caché de página no lo congele */
add_action( 'wp_ajax_rau_checkin_hoy', 'rau_checkin_hoy_endpoint' );
add_action( 'wp_ajax_nopriv_rau_checkin_hoy', 'rau_checkin_hoy_endpoint' );
function rau_checkin_hoy_endpoint() {
	$q = new WP_Query( array( 'post_type' => 'checkin', 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true,
		'meta_query' => array( array( 'key' => 'fecha', 'value' => current_time( 'Y-m-d' ) ) ) ) );
	$vistos = array(); $lista = array();
	foreach ( $q->posts as $p ) {
		$i = strtoupper( trim( (string) get_post_meta( $p->ID, 'indicativo', true ) ) );
		if ( ! $i || isset( $vistos[ $i ] ) ) continue;
		$vistos[ $i ] = 1;
		$lista[] = array( 'i' => $i, 'c' => (string) get_post_meta( $p->ID, 'ciudad', true ) );
	}
	wp_send_json_success( array( 'total' => count( $lista ), 'lista' => array_slice( $lista, 0, 24 ) ) );
}

/* Personalizador: el texto de «Qué es RAU» y hasta tres fotos del club */
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'rau_portada_quienes', array( 'title' => 'Portada: Qué es RAU', 'priority' => 31,
		'description' => 'Las fotos aparecen en una franja debajo del texto. Si no subes ninguna, la franja no se muestra.' ) );
	$wp_customize->add_setting( 'rau_home_quienes', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
	$wp_customize->add_control( 'rau_home_quienes', array( 'label' => 'Quiénes somos (2 o 3 frases)', 'section' => 'rau_portada_quienes', 'type' => 'textarea' ) );
	for ( $i = 1; $i <= 3; $i++ ) {
		$wp_customize->add_setting( 'rau_home_foto_' . $i, array( 'sanitize_callback' => 'absint' ) );
		$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'rau_home_foto_' . $i, array( 'label' => 'Foto ' . $i, 'section' => 'rau_portada_quienes', 'mime_type' => 'image' ) ) );
	}
}, 20 );
