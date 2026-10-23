<?php
/**
 * RAU Theme functions and definitions
 * Traducido 1:1 desde el proyecto React + PocketBase original.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_THEME_VERSION', '19.37.0' );

require get_template_directory() . '/inc/nav-walker.php';
require get_template_directory() . '/inc/default-logo.php';

/**
 * Configuración básica del tema
 */
function rau_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'rau-hero', 1600, 900, true ); // recorte 16:9, usado como fondo del hero de portada
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 80,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'script', 'style' ) );
	add_theme_support( 'responsive-embeds' );
	register_nav_menus( array(
		'primary' => __( 'Menú principal', 'rau-theme' ),
	) );
}
add_action( 'after_setup_theme', 'rau_theme_setup' );

/**
 * Al activar el tema, asegura que la URL /calendario.ics y los slugs de los
 * tipos de contenido queden funcionando sin tener que ir manualmente a
 * Ajustes → Enlaces permanentes → Guardar (aunque no está de más hacerlo también).
 */
function rau_flush_rewrites_on_activation() {
	rau_register_cpts();
	rau_ics_rewrite_rule();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'rau_flush_rewrites_on_activation' );

/**
 * Refresca las reglas de reescritura cuando cambia la versión del tema.
 *
 * 'after_switch_theme' solo se dispara al ACTIVAR otro tema, no al actualizar el mismo. Así que
 * al subir una versión que añade o cambia una regla, esta no queda registrada hasta que alguien
 * entra a Ajustes → Enlaces permanentes y guarda. Es un paso fácil de olvidar y con consecuencias
 * grandes: cuando se movió el JavaScript de Herramientas a /rau-herramientas.js, olvidarlo dejaba
 * esa dirección devolviendo 404 y la página entera sin JavaScript.
 *
 * Comparando la versión guardada con la actual, el refresco ocurre solo una vez tras cada
 * actualización y sin intervención manual.
 */
add_action( 'init', function() {
	if ( get_option( 'rau_rewrite_version' ) === RAU_THEME_VERSION ) return;
	flush_rewrite_rules();
	update_option( 'rau_rewrite_version', RAU_THEME_VERSION, false );
}, 99 );

/**
 * Estilos y tipografías — misma tríada del proyecto original
 */
function rau_theme_assets() {
	wp_enqueue_style(
		'rau-google-fonts',
		'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'rau-theme-style', get_stylesheet_uri(), array(), RAU_THEME_VERSION );

	wp_enqueue_script( 'rau-theme-main', get_template_directory_uri() . '/assets/main.js', array(), RAU_THEME_VERSION, true );
	wp_localize_script( 'rau-theme-main', 'RAU_AJAX', array( 'url' => admin_url( 'admin-ajax.php' ) ) );
}
add_action( 'wp_enqueue_scripts', 'rau_theme_assets' );

/**
 * Feed de calendario (.ics) generado en vivo desde los Eventos.
 * URL: tudominio.com/calendario.ics — en Google Calendar: "Otros calendarios" →
 * "Desde URL" → pega esa dirección. Se actualiza sola cada vez que Google la revisa
 * (normalmente cada 8-24 horas), sin que tengas que volver a exportar nada.
 */
function rau_ics_rewrite_rule() {
	add_rewrite_rule( '^calendario\.ics$', 'index.php?rau_ics=1', 'top' );
}
add_action( 'init', 'rau_ics_rewrite_rule' );

function rau_ics_query_vars( $vars ) {
	$vars[] = 'rau_ics';
	return $vars;
}
add_filter( 'query_vars', 'rau_ics_query_vars' );

function rau_ics_maybe_output() {
	if ( ! get_query_var( 'rau_ics' ) ) return;

	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: inline; filename="calendario-rau.ics"' );

	$eventos = new WP_Query( array( 'post_type' => 'evento', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'meta_value', 'meta_key' => 'start', 'order' => 'ASC' ) );

	echo "BEGIN:VCALENDAR\r\n";
	echo "VERSION:2.0\r\n";
	echo "PRODID:-//RAU//Calendario de Eventos//ES\r\n";
	echo "CALSCALE:GREGORIAN\r\n";
	echo "METHOD:PUBLISH\r\n";
	echo "X-WR-CALNAME:" . rau_ics_escape( get_bloginfo( 'name' ) . ' — Eventos' ) . "\r\n";
	echo "X-WR-TIMEZONE:America/Bogota\r\n";

	if ( $eventos->have_posts() ) {
		while ( $eventos->have_posts() ) {
			$eventos->the_post();
			$id    = get_the_ID();
			$start = get_post_meta( $id, 'start', true );
			if ( ! $start ) continue;
			$dt = str_replace( '-', '', $start );

			$type = get_post_meta( $id, 'type', true );
			$freq = get_post_meta( $id, 'frequency', true );
			$loc  = get_post_meta( $id, 'location', true );

			$desc = array();
			if ( $type ) $desc[] = 'Tipo: ' . $type;
			if ( $freq ) $desc[] = 'Frecuencia: ' . $freq;
			if ( get_the_content() ) $desc[] = wp_strip_all_tags( get_the_content() );

			echo "BEGIN:VEVENT\r\n";
			echo "UID:evento-{$id}@" . parse_url( home_url(), PHP_URL_HOST ) . "\r\n";
			echo "DTSTAMP:" . gmdate( 'Ymd\THis\Z' ) . "\r\n";
			echo "DTSTART;VALUE=DATE:{$dt}\r\n";
			echo "SUMMARY:" . rau_ics_escape( get_the_title() ) . "\r\n";
			if ( $desc ) echo "DESCRIPTION:" . rau_ics_escape( implode( '\n', $desc ) ) . "\r\n";
			if ( $loc ) echo "LOCATION:" . rau_ics_escape( $loc ) . "\r\n";
			echo "URL:" . esc_url_raw( get_permalink() ) . "\r\n";
			echo "END:VEVENT\r\n";
		}
		wp_reset_postdata();
	}

	echo "END:VCALENDAR\r\n";
	exit;
}
add_action( 'template_redirect', 'rau_ics_maybe_output' );

function rau_ics_escape( $text ) {
	$text = str_replace( array( '\\', ';', ',' ), array( '\\\\', '\\;', '\\,' ), $text );
	$text = str_replace( array( "\r\n", "\r", "\n" ), '\\n', $text );
	return $text;
}

/**
 * Enlace "Añadir a Google Calendar" para un evento individual (agrega solo ese
 * evento al calendario personal del visitante, al instante, sin esperar).
 */
function rau_google_calendar_link( $event_id ) {
	$start = get_post_meta( $event_id, 'start', true );
	if ( ! $start ) return '';
	$dt      = str_replace( '-', '', $start );
	try {
		$next = new DateTime( $start );
		$next->modify( '+1 day' );
		$dt_next = $next->format( 'Ymd' );
	} catch ( Exception $e ) {
		$dt_next = $dt;
	}
	$type    = get_post_meta( $event_id, 'type', true );
	$freq    = get_post_meta( $event_id, 'frequency', true );
	$loc     = get_post_meta( $event_id, 'location', true );
	$details = trim( ( $type ? 'Tipo: ' . $type . '. ' : '' ) . ( $freq ? 'Frecuencia: ' . $freq : '' ) );

	return add_query_arg( array(
		'action'   => 'TEMPLATE',
		'text'     => rawurlencode( get_the_title( $event_id ) ),
		'dates'    => $dt . '/' . $dt_next,
		'details'  => rawurlencode( $details ),
		'location' => rawurlencode( $loc ),
	), 'https://calendar.google.com/calendar/render' );
}

/**
 * Puntos de repetidores con lat/lng para el mapa (campos personalizados opcionales: lat, lng)
 * Se guarda en caché (transient) 1 hora para no consultar la base de datos en cada
 * visita — se invalida solo apenas guardes/edites un Repetidor.
 */
/**
 * Fuente única de datos de repetidores con coordenadas — todo lo demás
 * (mapa, simulador de cobertura, etc.) consulta esta función en vez de
 * repetir la misma consulta con campos ligeramente distintos.
 */
function rau_get_repeaters_data() {
	$cached = get_transient( 'rau_repeaters_data' );
	if ( false !== $cached ) return $cached;

	$points = array();
	$q = new WP_Query( array( 'post_type' => 'repetidor', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
	while ( $q->have_posts() ) {
		$q->the_post();
		$id  = get_the_ID();
		$lat = get_post_meta( $id, 'lat', true );
		$lng = get_post_meta( $id, 'lng', true );
		if ( $lat && $lng ) {
			$altura = get_post_meta( $id, 'altura_antena', true );
			$points[] = array(
				'name'     => get_the_title(),
				'callsign' => get_post_meta( $id, 'callsign', true ),
				'freq'     => get_post_meta( $id, 'frequency', true ),
				'offset'   => get_post_meta( $id, 'offset', true ),
				'tone'     => get_post_meta( $id, 'tone', true ),
				'mode'     => get_post_meta( $id, 'mode', true ),
				'location' => get_post_meta( $id, 'location', true ),
				'status'   => get_post_meta( $id, 'status', true ) ?: 'activo',
				'lat'      => (float) $lat,
				'lng'      => (float) $lng,
				'altura'   => $altura ? (float) $altura : 30,
				'url'      => get_permalink( $id ),
			);
		}
	}
	wp_reset_postdata();
	set_transient( 'rau_repeaters_data', $points, HOUR_IN_SECONDS );
	return $points;
}
add_action( 'save_post_repetidor', function() { delete_transient( 'rau_repeaters_data' ); delete_transient( 'rau_repetidor_principal' ); } );

/**
 * RAU DATA — Fase 6: eventos y alertas con coordenadas, para la capa correspondiente en RAU Map.
 * Mismo patrón exacto que rau_get_repeaters_data() — solo incluye los que sí tienen lat/lng cargados.
 */
function rau_get_eventos_map_data() {
	$cached = get_transient( 'rau_eventos_map_data' );
	if ( false !== $cached ) return $cached;

	$points = array();
	$q = new WP_Query( array( 'post_type' => 'evento', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
	while ( $q->have_posts() ) {
		$q->the_post();
		$id  = get_the_ID();
		$lat = get_post_meta( $id, 'lat', true );
		$lng = get_post_meta( $id, 'lng', true );
		if ( $lat && $lng ) {
			$points[] = array(
				'name'     => get_the_title(),
				'type'     => get_post_meta( $id, 'type', true ),
				'start'    => get_post_meta( $id, 'start', true ),
				'location' => get_post_meta( $id, 'location', true ),
				'lat'      => (float) $lat,
				'lng'      => (float) $lng,
				'url'      => get_permalink( $id ),
			);
		}
	}
	wp_reset_postdata();
	set_transient( 'rau_eventos_map_data', $points, HOUR_IN_SECONDS );
	return $points;
}
add_action( 'save_post_evento', function() { delete_transient( 'rau_eventos_map_data' ); } );

function rau_get_alertas_map_data() {
	$cached = get_transient( 'rau_alertas_map_data' );
	if ( false !== $cached ) return $cached;

	$points = array();
	$q = new WP_Query( array( 'post_type' => 'alerta', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
	while ( $q->have_posts() ) {
		$q->the_post();
		$id  = get_the_ID();
		$lat = get_post_meta( $id, 'lat', true );
		$lng = get_post_meta( $id, 'lng', true );
		// Solo alertas activas — una alerta resuelta no debería seguir marcada en el mapa
		$activa = get_post_meta( $id, 'active', true ) === '1';
		if ( $lat && $lng && $activa ) {
			$points[] = array(
				'name'  => get_the_title(),
				'nivel' => get_post_meta( $id, 'nivel', true ) ?: 'Información',
				'zona'  => get_post_meta( $id, 'zona', true ),
				'lat'   => (float) $lat,
				'lng'   => (float) $lng,
			);
		}
	}
	wp_reset_postdata();
	set_transient( 'rau_alertas_map_data', $points, HOUR_IN_SECONDS );
	return $points;
}
add_action( 'save_post_alerta', function() { delete_transient( 'rau_alertas_map_data' ); } );

/* Envoltorios: mantienen exactamente la misma forma que ya consumía cada
 * herramienta, para no tener que tocar ese código — pero ya no repiten
 * la consulta a la base de datos. */
function rau_get_repeater_map_points() {
	return array_map( function( $r ) {
		return array( 'name' => $r['name'], 'callsign' => $r['callsign'], 'freq' => $r['freq'], 'location' => $r['location'], 'lat' => $r['lat'], 'lng' => $r['lng'] );
	}, rau_get_repeaters_data() );
}
function rau_get_repeaters_for_coverage() {
	return array_map( function( $r ) {
		return array( 'name' => $r['name'], 'callsign' => $r['callsign'], 'lat' => $r['lat'], 'lng' => $r['lng'], 'altura' => $r['altura'] );
	}, rau_get_repeaters_data() );
}

/**
 * Tipo de contenido: Repetidores (colección "repeaters")
 * Campos personalizados: callsign, band, frequency, offset, tone, mode, location, status
 * status: activo | mantenimiento | inactivo
 */

/**
 * WordPress llama a los sanitize_callback de register_post_meta con 4 argumentos
 * (valor, meta_key, tipo de objeto, subtipo) — floatval() nativo de PHP solo acepta 1
 * y lanza ArgumentCountError en PHP 8+. Este wrapper acepta los extra y los ignora.
 */
function rau_sanitize_float( $valor ) { return floatval( $valor ); }

function rau_register_cpts() {

	register_post_type( 'repetidor', array(
		'labels' => array(
			'name'          => __( 'Repetidores', 'rau-theme' ),
			'singular_name' => __( 'Repetidor', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar repetidor', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => true, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-microphone',
		'supports' => array( 'title', 'custom-fields' ),
		'rewrite' => array( 'slug' => 'repetidores' ),
	) );

	// RAU DATA — Fase 2: declarar los campos del repetidor con tipo correcto.
	// Los primeros 8 ya existían como custom fields genéricos (sin declarar) — se mantienen
	// exactamente con el mismo nombre para no romper archive-repetidor.php ni datos ya cargados.
	// Los últimos 6 son nuevos, para cubrir la sección 10 del documento HK4RAU 3.0.
	$campos_repetidor = array(
		'callsign' => 'string', 'frequency' => 'string', 'offset' => 'string', 'tone' => 'string',
		'mode' => 'string', 'band' => 'string', 'location' => 'string', 'status' => 'string',
		'lat' => 'number', 'lng' => 'number',
		// Nuevos (Fase 2):
		'dcs' => 'string', 'grid' => 'string', 'pais' => 'string',
		'fuente' => 'string', 'fecha_verificacion' => 'string', 'responsable' => 'string',
	);
	foreach ( $campos_repetidor as $campo => $tipo ) {
		register_post_meta( 'repetidor', $campo, array(
			'type' => $tipo, 'single' => true, 'show_in_rest' => true,
			'sanitize_callback' => ( 'number' === $tipo ) ? 'rau_sanitize_float' : 'sanitize_text_field',
		) );
	}

	// RAU DATA — Fase 6: coordenadas para eventos y alertas, necesarias para mostrarlos como capa en RAU Map.
	// Ninguno de los dos tenía coordenadas antes — solo texto libre de ubicación/zona.
	foreach ( array( 'evento', 'alerta' ) as $cpt_con_mapa ) {
		register_post_meta( $cpt_con_mapa, 'lat', array( 'type' => 'number', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'rau_sanitize_float' ) );
		register_post_meta( $cpt_con_mapa, 'lng', array( 'type' => 'number', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'rau_sanitize_float' ) );
	}

	// Eventos (colección "events"): start, type, frequency, location
	register_post_type( 'evento', array(
		'labels' => array(
			'name'          => __( 'Eventos', 'rau-theme' ),
			'singular_name' => __( 'Evento', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar evento', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => true, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-calendar-alt',
		'supports' => array( 'title', 'custom-fields' ),
		'rewrite' => array( 'slug' => 'eventos' ),
	) );

	// Recursos técnicos (colección "resources"): category, description(editor), meta, link
	register_post_type( 'recurso', array(
		'labels' => array(
			'name'          => __( 'Recursos Técnicos', 'rau-theme' ),
			'singular_name' => __( 'Recurso', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar recurso', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => true, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-admin-tools',
		'supports' => array( 'title', 'editor', 'custom-fields' ),
		'rewrite' => array( 'slug' => 'recursos-tecnicos' ),
	) );

	// Junta Directiva (colección "board"): role, callsign, order
	register_post_type( 'directivo', array(
		'labels' => array(
			'name'          => __( 'Junta Directiva', 'rau-theme' ),
			'singular_name' => __( 'Directivo', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar directivo', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => false, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-groups',
		'supports' => array( 'title', 'custom-fields' ),
		// Sin ficha propia: se presentan en la página Junta Directiva. La ficha suelta solo
		// mostraba el nombre.
		'publicly_queryable' => false, 'exclude_from_search' => true, 'query_var' => false, 'rewrite' => false,
	) );

	// Secciones de contenido institucional (colección "content"): section(slug), summary, body=editor
	register_post_type( 'contenido', array(
		'labels' => array(
			'name'          => __( 'Contenido Institucional', 'rau-theme' ),
			'singular_name' => __( 'Sección', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar sección', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => false, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-admin-page',
		'supports' => array( 'title', 'editor', 'custom-fields' ),
		'rewrite' => array( 'slug' => 'info' ),
	) );

	// Alertas de emergencia (colección "alerts"): frequency, message, active
	register_post_type( 'alerta', array(
		'labels' => array(
			'name'          => __( 'Alertas de Emergencia', 'rau-theme' ),
			'singular_name' => __( 'Alerta', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar alerta', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => false, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-warning',
		'supports' => array( 'title', 'custom-fields' ),
	) );

	// Historias de la comunidad: autor, rol — contenido = la cita
	register_post_type( 'historia', array(
		'labels' => array(
			'name'          => __( 'Historias de la Comunidad', 'rau-theme' ),
			'singular_name' => __( 'Historia', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar historia', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => true, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-format-quote',
		'supports' => array( 'title', 'editor', 'custom-fields' ),
		'rewrite' => array( 'slug' => 'historias' ),
	) );

	// Galería de actividades — usa la imagen destacada
	register_post_type( 'galeria_item', array(
		'labels' => array(
			'name'          => __( 'Galería de Actividades', 'rau-theme' ),
			'singular_name' => __( 'Foto', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar foto', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => true, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-camera',
		'supports' => array( 'title', 'thumbnail' ),
		'rewrite' => array( 'slug' => 'galeria-fotos' ),
	) );

	// Diplomas y certificaciones
	register_post_type( 'diploma', array(
		'labels' => array(
			'name'          => __( 'Diplomas y Certificaciones', 'rau-theme' ),
			'singular_name' => __( 'Diploma', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar diploma', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => true, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-awards',
		'supports' => array( 'title', 'editor', 'custom-fields' ),
		'rewrite' => array( 'slug' => 'diplomas' ),
	) );

	// Pulguero (clasificados): precio, contacto, estado
	register_post_type( 'anuncio', array(
		'labels' => array(
			'name'          => __( 'Pulguero (Clasificados)', 'rau-theme' ),
			'singular_name' => __( 'Anuncio', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar anuncio', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => false, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-tag',
		'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'rewrite' => array( 'slug' => 'pulguero-item' ),
	) );

	// Libro de Guardia / QSL: fecha, indicativo, banda, modo, confirmado
	register_post_type( 'qso', array(
		'labels' => array(
			'name'          => __( 'Libro de Guardia (QSL)', 'rau-theme' ),
			'singular_name' => __( 'Contacto QSO', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar contacto', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => false, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-book',
		'supports' => array( 'title', 'custom-fields' ),
		// Sin ficha propia: single.php no tiene vista para este tipo y la página salía vacía.
		'publicly_queryable' => false, 'exclude_from_search' => true, 'query_var' => false, 'rewrite' => false,
	) );

	// Directorio de Miembros: indicativo, ciudad, bandas, visible (si acepta aparecer publicamente)
	register_post_type( 'miembro', array(
		'labels' => array(
			'name'          => __( 'Directorio de Miembros', 'rau-theme' ),
			'singular_name' => __( 'Miembro', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar miembro', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => false, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-id',
		'supports' => array( 'title', 'custom-fields' ),
		// Sin ficha propia: el directorio los lista y solo con autorización (campo "visible").
		// Antes cada miembro tenía /miembro/{nombre}/, indexada y en el sitemap, autorizara o no.
		'publicly_queryable' => false, 'exclude_from_search' => true, 'query_var' => false, 'rewrite' => false,
	) );

	// Biblioteca de Documentos: archivo_url, categoria
	register_post_type( 'documento', array(
		'labels' => array(
			'name'          => __( 'Biblioteca de Documentos', 'rau-theme' ),
			'singular_name' => __( 'Documento', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar documento', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => false, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-media-document',
		'supports' => array( 'title', 'editor', 'custom-fields' ),
		'rewrite' => array( 'slug' => 'documento' ),
	) );

	// Roster de Turnos de Guardia (Red de Emergencia): miembro, fecha_inicio, fecha_fin, notas
	register_post_type( 'turno', array(
		'labels' => array(
			'name'          => __( 'Turnos de Guardia', 'rau-theme' ),
			'singular_name' => __( 'Turno', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar turno', 'rau-theme' ),
		),
		'public' => true, 'has_archive' => false, 'show_in_rest' => true,
		'menu_icon' => 'dashicons-clock',
		'supports' => array( 'title', 'custom-fields' ),
		// Sin ficha propia: single.php no tiene vista para este tipo y la página salía vacía.
		'publicly_queryable' => false, 'exclude_from_search' => true, 'query_var' => false, 'rewrite' => false,
	) );

	// Fase 2: check-in de asistencia a mallas. No es público (no genera páginas
	// propias en el sitio); solo existe como registro en el escritorio y se
	// lista desde la plantilla "Check-in de Malla".
	register_post_type( 'checkin', array(
		'labels' => array(
			'name'          => __( 'Check-ins de Malla', 'rau-theme' ),
			'singular_name' => __( 'Check-in', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar check-in', 'rau-theme' ),
		),
		'public' => false, 'show_ui' => true, 'has_archive' => false,
		'show_in_rest' => false, 'exclude_from_search' => true,
		'menu_icon' => 'dashicons-yes-alt',
		'supports' => array( 'title', 'custom-fields' ),
	) );
	// Libro de Visitas: mensajes públicos y permanentes de la comunidad.
	// No genera páginas propias; se lista desde la plantilla "Libro de Visitas".
	register_post_type( 'libro_visitas', array(
		'labels' => array(
			'name'          => __( 'Libro de Visitas', 'rau-theme' ),
			'singular_name' => __( 'Entrada del Libro', 'rau-theme' ),
			'add_new_item'  => __( 'Agregar entrada', 'rau-theme' ),
		),
		'public' => false, 'show_ui' => true, 'has_archive' => false,
		'show_in_rest' => false, 'exclude_from_search' => true,
		'menu_icon' => 'dashicons-book-alt',
		'supports' => array( 'title', 'editor', 'custom-fields' ),
	) );
}
add_action( 'init', 'rau_register_cpts' );

/**
 * RAU DATA — Fase 2: fuente única de verdad para el repetidor principal de RAU.
 * Busca en el CPT 'repetidor' por indicativo HK4RAU. Si todavía no existe cargado
 * en la base de datos (por ejemplo, recién after desplegar esta versión), cae en
 * los valores ya verificados manualmente esta sesión — nunca deja el dato vacío.
 * Cacheado con transient de 1 hora para no consultar la base en cada carga.
 */
/**
 * Número de WhatsApp del club, en un solo sitio.
 *
 * Estaba escrito a mano en 9 lugares repartidos por 5 archivos, incluido el botón de la página
 * de Modo Emergencia. Cambiarlo obligaba a encontrarlos todos, y uno que se quedara atrás en la
 * página de emergencia significa que alguien marca un número muerto justo cuando más importa.
 * Se puede sobrescribir desde el Personalizador sin tocar código.
 */
function rau_whatsapp_numero() {
	return preg_replace( '/\D/', '', (string) get_theme_mod( 'rau_whatsapp', '573104154317' ) );
}
function rau_whatsapp_url() {
	return 'https://wa.me/' . rau_whatsapp_numero();
}

function rau_get_repetidor_principal() {
	$cache = get_transient( 'rau_repetidor_principal' );
	if ( false !== $cache ) return $cache;

	$datos = array(
		'callsign' => 'HK4RAU', 'frequency' => '146.675', 'offset' => '-0.600',
		'tone' => '100.0', 'mode' => 'FM', 'band' => '2m',
		'location' => 'Medellín, Antioquia', 'lat' => 6.2442, 'lng' => -75.5812,
		'grid' => 'FJ26ff', 'status' => 'activo',
		'fuente' => 'valores_por_defecto_verificados',
	);

	$posts = get_posts( array(
		'post_type' => 'repetidor', 'posts_per_page' => 1, 'post_status' => 'publish',
		'meta_query' => array( array( 'key' => 'callsign', 'value' => 'HK4RAU' ) ),
	) );
	if ( $posts ) {
		$id = $posts[0]->ID;
		foreach ( array_keys( $datos ) as $campo ) {
			$valor = get_post_meta( $id, $campo, true );
			if ( '' !== $valor && null !== $valor ) $datos[ $campo ] = $valor;
		}
		$datos['fuente'] = 'base_de_datos';
		$datos['post_id'] = $id;
	}

	// Normalizar frequency: quitar "MHz" si ya viene incluido en el dato guardado —
	// los lugares que consumen este valor agregan "MHz" ellos mismos, y duplicarían la unidad si no se limpia aquí.
	$datos['frequency'] = trim( preg_replace( '/\s*mhz\s*$/i', '', $datos['frequency'] ) );
	// Mismo criterio para tone (quitar "Hz" si viene incluido)
	$datos['tone'] = trim( preg_replace( '/\s*hz\s*$/i', '', $datos['tone'] ) );

	set_transient( 'rau_repetidor_principal', $datos, HOUR_IN_SECONDS );
	return $datos;
}

/**
 * Fuente única de las herramientas de Herramientas de Radio — usada por
 * la página misma y por el SEO (título/descripción por herramienta).
 * Si se agrega o renombra una herramienta, se hace UNA vez aquí.
 */
/**
 * Calendario de concursos internacionales — fuente única.
 *
 * La lista estaba escrita dos veces, en front-page.php y en inc/herramientas-scripts.php, y el
 * comentario original ya admitía que "una sola fuente sería mejor a futuro". Aún no habían
 * divergido, pero es el mismo patrón que sí hizo divergir los grid squares, la tabla Morse y las
 * potencias del decreto. Un concurso mal fechado manda a alguien al aire el fin de semana
 * equivocado, así que mejor un solo sitio donde corregirlo.
 *
 * 'mes' va indexado desde 0 como en JavaScript (0 = enero), porque es ahí donde se consume.
 * 'n_semana' es el número de fin de semana completo dentro del mes; -1 significa el último.
 * Fechas contrastadas con los reglamentos de ARRL y CQ.
 */
function rau_get_concursos() {
	return array(
		array( 'n' => 'ARRL International DX (Phone)',  'mes' => 2,  'n_semana' => 1 ),   // 1º de marzo
		array( 'n' => 'CQ WPX Contest (SSB)',           'mes' => 2,  'n_semana' => -1 ),  // último de marzo
		array( 'n' => 'CQ WPX Contest (CW)',            'mes' => 4,  'n_semana' => -1 ),  // último de mayo
		array( 'n' => 'ARRL Field Day',                 'mes' => 5,  'n_semana' => 4 ),   // 4º de junio
		array( 'n' => 'IARU HF World Championship',     'mes' => 6,  'n_semana' => 2 ),   // 2º de julio
		array( 'n' => 'CQ WW DX Contest (SSB)',         'mes' => 9,  'n_semana' => -1 ),  // último de octubre
		array( 'n' => 'ARRL November Sweepstakes (CW)', 'mes' => 10, 'n_semana' => 1 ),   // 1º de noviembre
		array( 'n' => 'CQ WW DX Contest (CW)',          'mes' => 10, 'n_semana' => -1 ),  // último de noviembre
	);
}

/**
 * Catálogo de herramientas.
 *
 * Campos opcionales para agrupar:
 *   'incluye' — ids que se muestran JUNTO a esta herramienta cuando se abre. Sirve para reunir
 *               varias vistas de un mismo asunto en una sola entrada del catálogo.
 *   'oculta'  — no aparece como entrada propia en el catálogo, pero conserva su panel y su URL
 *               (/herramientas/{id}/), así que los enlaces compartidos y el sitemap siguen valiendo.
 *
 * Por qué: el catálogo tenía 54 entradas pero cinco leían el mismo feed de HamQSL, tres eran
 * vistas del mismo satélite, tres contestaban "quién me oye" y tres hacían variantes de "dónde
 * está y a qué distancia". Quince entradas para cuatro preguntas. Agrupándolas, el catálogo baja
 * a 42 y cada entrada responde algo completo, sin perder ninguna función ni romper ningún enlace.
 */
function rau_get_herramientas_tabs() {
	return [
		['id'=>'ahora',      'label'=>'Ahora mismo, ¿qué trabajo?', 'cat'=>'Operar'],
		['id'=>'dashboard',  'label'=>'Dashboard en Vivo',         'cat'=>'Operar'],
		['id'=>'utc',        'label'=>'Reloj UTC',                 'cat'=>'Explorar'],
		['id'=>'prop',       'label'=>'Condiciones HF',         'cat'=>'Explorar', 'incluye'=>['bandalert','sfihist','ciclosolar','apagon']],
		['id'=>'bandalert',  'label'=>'Alertas de Banda',          'cat'=>'Explorar', 'oculta'=>true],
		['id'=>'sfihist',    'label'=>'Historial SFI/K',           'cat'=>'Explorar', 'oculta'=>true],
		['id'=>'grayline',   'label'=>'Grayline',                  'cat'=>'Explorar'],
		['id'=>'luna',       'label'=>'Fase Lunar (EME)',          'cat'=>'Explorar'],
		['id'=>'calc',       'label'=>'Calculadoras',              'cat'=>'Calcular'],
		['id'=>'rfconv',     'label'=>'Conversor RF',              'cat'=>'Calcular'],
		['id'=>'colores',    'label'=>'Código de Colores',         'cat'=>'Calcular'],
		['id'=>'roe',        'label'=>'Diagnóstico de ROE',        'cat'=>'Calcular'],
		['id'=>'bateria',    'label'=>'Autonomía de Batería',      'cat'=>'Calcular'],
		['id'=>'angulo',     'label'=>'Ángulo de Radiación',       'cat'=>'Calcular'],
		['id'=>'maidenhead', 'label'=>'Dónde y a qué distancia',                'cat'=>'Localizar', 'incluye'=>['azimuth','indicativo']],
		['id'=>'azimuth',    'label'=>'Azimuth / km',              'cat'=>'Localizar', 'oculta'=>true],
		['id'=>'terreno',    'label'=>'Perfil de Terreno',        'cat'=>'Localizar'],
		['id'=>'cobertura',  'label'=>'Cobertura de Repetidor',    'cat'=>'Localizar'],
		['id'=>'satfreq',    'label'=>'Frecuencias de Satélites',  'cat'=>'Explorar', 'oculta'=>true],
		['id'=>'doppler',    'label'=>'Doppler',                   'cat'=>'Explorar', 'oculta'=>true],
		['id'=>'satpass',    'label'=>'Satélites',     'cat'=>'Explorar', 'incluye'=>['satfreq','doppler']],
		['id'=>'coords',     'label'=>'Conversor de Coordenadas',  'cat'=>'Localizar'],
		['id'=>'prefijos',   'label'=>'Prefijos',                  'cat'=>'Localizar'],
		['id'=>'indicativo', 'label'=>'Localizador DX',            'cat'=>'Localizar', 'oculta'=>true],
		['id'=>'bandas',     'label'=>'Bandas Colombia',           'cat'=>'Aprender'],
		['id'=>'warc',       'label'=>'Bandas WARC',               'cat'=>'Aprender'],
		['id'=>'ics213',     'label'=>'Mensaje formal (ICS-213)',  'cat'=>'Emergencias'],
		['id'=>'frecemerg',  'label'=>'Frecuencias de Emergencia', 'cat'=>'Emergencias'],
		['id'=>'emergencia', 'label'=>'Emergencia',                'cat'=>'Emergencias'],
		['id'=>'ctcss',      'label'=>'CTCSS / DCS',               'cat'=>'Aprender'],
		['id'=>'rst',        'label'=>'Reportes RST',              'cat'=>'Aprender'],
		['id'=>'codigosq',   'label'=>'Código Q',                  'cat'=>'Aprender'],
		['id'=>'fonetico',   'label'=>'Alfabeto Fonético',         'cat'=>'Aprender'],
		['id'=>'glosario',   'label'=>'Glosario de Términos',      'cat'=>'Aprender'],
		['id'=>'cw',         'label'=>'Telegrafía CW',             'cat'=>'Aprender'],
		['id'=>'modos',      'label'=>'Modos Digitales',           'cat'=>'Aprender'],
		['id'=>'qsl',        'label'=>'QSL Digital',               'cat'=>'Operar'],
		['id'=>'certificado', 'label'=>'Certificado de Participación', 'cat'=>'Operar'],
		['id'=>'log',        'label'=>'Log ADIF',                  'cat'=>'Operar'],
		['id'=>'pota',       'label'=>'POTA Colombia',             'cat'=>'Operar'],
		['id'=>'dx',         'label'=>'Actividad en las bandas',                  'cat'=>'Explorar', 'incluye'=>['pskreporter','rbn']],
		['id'=>'concursos',  'label'=>'Calendario de Concursos',   'cat'=>'Operar'],
		['id'=>'cabrillo',   'label'=>'Validador de Log Cabrillo', 'cat'=>'Operar'],
		['id'=>'radiofaros', 'label'=>'Radiofaros NCDXF/IARU',     'cat'=>'Explorar'],
		['id'=>'ciclosolar', 'label'=>'Ciclo Solar',               'cat'=>'Explorar', 'oculta'=>true],
		['id'=>'apagon',     'label'=>'Predictor de Apagón HF',    'cat'=>'Explorar', 'oculta'=>true],
		['id'=>'radiales',   'label'=>'Radiales de Tierra',        'cat'=>'Calcular'],
		['id'=>'split',      'label'=>'Ayudante de Split (DX)',    'cat'=>'Operar'],
		['id'=>'qsorate',    'label'=>'Contador de Tasa de QSO',   'cat'=>'Operar'],
		['id'=>'dxcc',       'label'=>'Rastreador DXCC/WAS',       'cat'=>'Explorar'],
		['id'=>'pskreporter','label'=>'PSK Reporter — ¿quién me oye?', 'cat'=>'Explorar', 'oculta'=>true],
		['id'=>'rbn',        'label'=>'Reverse Beacon Network',    'cat'=>'Explorar', 'oculta'=>true],
		['id'=>'aprs',       'label'=>'Mapa APRS Colombia',        'cat'=>'Localizar'],
		['id'=>'smith',      'label'=>'Smith Chart',               'cat'=>'Calcular'],
		['id'=>'tarjeta',    'label'=>'Tarjeta de Campo (imprimir)', 'cat'=>'Aprender'],
		['id'=>'brandmeister','label'=>'BrandMeister Last Heard',  'cat'=>'Escuchar'],
		['id'=>'planbandas',  'label'=>'Plan de Bandas (visual)',  'cat'=>'Aprender'],
		['id'=>'examen',     'label'=>'Examen RABCA',              'cat'=>'Aprender'],
		['id'=>'licencia',   'label'=>'Recordatorio de Licencia',  'cat'=>'Aprender'],
		['id'=>'diplomas',   'label'=>'Verificador de Diplomas',   'cat'=>'Aprender'],
	];
}
/**
 * Nombre legible de una herramienta a partir de su id, para etiquetar su panel.
 * Se usa como aria-label: sin él, un lector de pantalla anuncia 54 regiones sin nombre.
 */
function rau_tool_label( $id ) {
	$t = rau_find_tool_by_id( $id );
	return $t ? $t['label'] : ucfirst( str_replace( '-', ' ', $id ) );
}

function rau_find_tool_by_id( $id ) {
	foreach ( rau_get_herramientas_tabs() as $t ) {
		if ( $t['id'] === $id ) return $t;
	}
	return null;
}

/**
 * El <title> real de la pestaña lo genera WordPress solo (add_theme_support
 * 'title-tag'), independiente de rau_seo_meta_tags() — que solo controla
 * Open Graph/Twitter. Hay que engancharse aquí también para que cada
 * herramienta tenga su propio título de pestaña, no el genérico de la página.
 */
add_filter( 'document_title_parts', function( $parts ) {
	if ( is_page_template( 'page-herramientas.php' ) && get_query_var( 'rau_tool' ) ) {
		$tool = rau_find_tool_by_id( sanitize_title( get_query_var( 'rau_tool' ) ) );
		if ( $tool ) $parts['title'] = $tool['label'];
	}
	return $parts;
} );

/**
 * URLs propias por herramienta: /herramientas/{id}/ en vez de solo
 * /herramientas/#id. La página sigue siendo la misma (page-herramientas.php),
 * pero ahora es una ruta real de WordPress — indexable, compartible sin
 * depender de JavaScript, y con su propio title/meta description.
 */
add_action( 'init', function() {
	add_rewrite_rule( '^herramientas/([^/]+)/?$', 'index.php?pagename=herramientas&rau_tool=$matches[1]', 'top' );
} );
add_filter( 'query_vars', function( $vars ) {
	$vars[] = 'rau_tool';
	return $vars;
} );
// Las reglas de reescritura solo se generan una vez — si el slug real de
// la página de Herramientas no es "herramientas", ajusta la línea de
// arriba y visita Ajustes → Enlaces permanentes → Guardar para regenerarlas.
add_action( 'after_switch_theme', function() { flush_rewrite_rules(); } );

/**
 * Sirve /service-worker.js desde la raíz del dominio, aunque el archivo
 * real viva dentro de la carpeta del tema. Un Service Worker solo puede
 * controlar páginas dentro de su misma carpeta o inferiores por defecto
 * — sirviéndolo "desde la raíz" (vía esta ruta) le da alcance a todo
 * hk4rau.com, necesario tanto para caché offline como para push.
 */
add_action( 'init', function() {
	add_rewrite_rule( '^service-worker\.js$', 'index.php?rau_sw=1', 'top' );
} );
add_filter( 'query_vars', function( $vars ) {
	$vars[] = 'rau_sw';
	return $vars;
} );
// Sin esto, la redirección canónica de WordPress le agrega una barra al
// final ("/service-worker.js/"), lo cual hace fallar el registro del
// Service Worker en el navegador (pide la ruta exacta, sin barra).
add_filter( 'redirect_canonical', function( $redirect_url ) {
	if ( get_query_var( 'rau_sw' ) ) return false;
	return $redirect_url;
} );
add_action( 'template_redirect', function() {
	if ( ! get_query_var( 'rau_sw' ) ) return;
	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'Service-Worker-Allowed: /' );
	header( 'Cache-Control: no-cache' );
	include get_template_directory() . '/service-worker-src.php';
	exit;
} );

/**
 * Helper: iniciales para el círculo de un directivo (máx. 2 letras)
 */
function rau_initials( $name ) {
	$parts = preg_split( '/\s+/', trim( $name ) );
	if ( count( $parts ) === 1 ) {
		return mb_strtoupper( mb_substr( $parts[0], 0, 2 ) );
	}
	$initials = '';
	foreach ( array_slice( $parts, 0, 2 ) as $p ) {
		$initials .= mb_substr( $p, 0, 1 );
	}
	return mb_strtoupper( $initials );
}

/**
 * Logo de la asociación: usa el que se suba en Personalizar > Identidad del sitio;
 * si no hay ninguno, muestra el escudo real de RAU incrustado (no un ícono genérico).
 */
function rau_logo_html( $class = 'h-9 w-9' ) {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	printf(
		'<img src="data:image/webp;base64,%1$s" alt="Escudo RAU" class="%2$s rounded-full object-contain">',
		RAU_DEFAULT_LOGO_B64,
		esc_attr( $class )
	);
}

/**
 * Helper: clases de color del badge de estado de un repetidor
 * (igual al objeto STATUS_COLOR de HomePage.jsx)
 */
function rau_status_classes( $status ) {
	$status = $status ?: 'activo';
	$map = array(
		'activo'        => 'text-emerald-400 bg-emerald-400/10 ring-emerald-400/30',
		'mantenimiento' => 'text-amber-400 bg-amber-400/10 ring-amber-400/30',
		'inactivo'      => 'text-rose-400 bg-rose-400/10 ring-rose-400/30',
	);
	return isset( $map[ $status ] ) ? $map[ $status ] : $map['activo'];
}

/**
 * Helper: icono (dashicon simple) según la categoría de un recurso
 */
function rau_resource_icon( $category ) {
	$map = array(
		'manual' => 'book', 'software' => 'cpu', 'software_programacion' => 'cpu',
		'diy' => 'wrench', 'nodo_digital' => 'signal', 'frecuencia_encuentro' => 'waves',
	);
	return isset( $map[ $category ] ) ? $map[ $category ] : 'book';
}

/**
 * Contenido institucional por sección (equivalente a contentBy() en React)
 * También en caché — se invalida al guardar cualquier "Sección" de Contenido Institucional.
 */
function rau_content_by_section( $section ) {
	$cache_key = 'rau_content_' . $section;
	$cached = get_transient( $cache_key );
	if ( false !== $cached ) return ( 0 === $cached ) ? null : $cached;

	$q = new WP_Query( array(
		'post_type' => 'contenido', 'posts_per_page' => 1, 'post_status' => 'publish',
		'meta_query' => array( array( 'key' => 'section', 'value' => $section ) ),
	) );
	$id = null;
	if ( $q->have_posts() ) { $q->the_post(); $id = get_the_ID(); wp_reset_postdata(); }
	set_transient( $cache_key, $id ? $id : 0, HOUR_IN_SECONDS );
	return $id;
}
add_action( 'save_post_contenido', function( $post_id ) {
	$section = get_post_meta( $post_id, 'section', true );
	if ( $section ) delete_transient( 'rau_content_' . $section );
} );

/**
 * Iconos en línea (equivalentes a los de lucide-react usados en el proyecto original)
 */
function rau_icon( $name, $class = 'h-5 w-5' ) {
	$paths = array(
		'antenna'  => '<path d="M12 2v7M12 9a3 3 0 100 6 3 3 0 000-6zM7 6a7 7 0 000 10M17 6a7 7 0 010 10"/><path d="M9 21h6l-1.5-6h-3z"/>',
		'signal'   => '<path d="M2 20h.01M7 20v-4M12 20v-8M17 20V4M22 20v-2"/>',
		'waves'    => '<path d="M2 12c1.5-2 3.5-2 5 0s3.5 2 5 0 3.5-2 5 0 3.5 2 5 0M2 18c1.5-2 3.5-2 5 0s3.5 2 5 0 3.5-2 5 0 3.5 2 5 0"/>',
		'book'     => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20V4H6.5A2.5 2.5 0 004 6.5v13z"/><path d="M4 19.5A2.5 2.5 0 006.5 22H20v-3"/>',
		'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17"/>',
		'award'    => '<circle cx="12" cy="8" r="6"/><path d="M8.5 13.5L7 22l5-3 5 3-1.5-8.5"/>',
		'wrench'   => '<path d="M14.7 6.3a4 4 0 10-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 005.4-5.4l-2.8 2.8-2-2z"/>',
		'users'    => '<path d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
		'cpu'      => '<rect x="6" y="6" width="12" height="12" rx="1"/><path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"/>',
	);
	$p = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['users'];
	printf(
		'<svg xmlns="http://www.w3.org/2000/svg" class="%1$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">%2$s</svg>',
		esc_attr( $class ), $p
	);
}

/**
 * Personalizador: textos del hero y correo de contacto
 */
function rau_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'rau_hero_section', array(
		'title'    => __( 'Textos del hero', 'rau-theme' ),
		'priority' => 30,
	) );
	$fields = array(
		'rau_hero_badge'    => 'Insignia (arriba del título)',
		'rau_hero_title_a'  => 'Título — parte 1',
		'rau_hero_title_b'  => 'Título — parte 2 (resaltada en cian)',
		'rau_hero_title_c'  => 'Título — parte 3',
		'rau_hero_subtitle' => 'Subtítulo',
	);
	foreach ( $fields as $id => $label ) {
		$wp_customize->add_setting( $id, array( 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control( $id, array(
			'label'   => __( $label, 'rau-theme' ),
			'section' => 'rau_hero_section',
			'type'    => 'text',
		) );
	}

	$wp_customize->add_setting( 'rau_community_image', array( 'sanitize_callback' => 'esc_url_raw' ) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'rau_community_image', array(
		'label'   => __( 'Foto de la sección Comunidad', 'rau-theme' ),
		'section' => 'rau_hero_section',
	) ) );
}
/**
 * SEO básico: meta descripción + Open Graph + Twitter Card
 */
/**
 * URL canónica de la vista actual.
 *
 * Cada herramienta vive en su propia dirección (/herramientas/satpass/) generada por una regla de
 * reescritura sobre la página /herramientas/. WordPress no lo sabe: su rel_canonical por defecto
 * usa get_permalink(), que devuelve /herramientas/ para las 54. El resultado es que Google
 * considera las 54 direcciones copias de una sola y solo indexa esa — desperdiciando el título y
 * la descripción propios que rau_seo_meta_tags() ya genera para cada herramienta.
 */
/**
 * Añade las 54 herramientas al sitemap de WordPress.
 *
 * El sitemap del core recorre entradas y taxonomías reales. Las herramientas no son ninguna de
 * las dos: son direcciones generadas por una regla de reescritura sobre una única página, así
 * que nunca aparecían en wp-sitemap.xml. Sin canonical propio y sin estar en el sitemap, para un
 * buscador simplemente no existían.
 */
add_action( 'init', function() {
	if ( ! class_exists( 'WP_Sitemaps_Provider' ) || ! function_exists( 'wp_register_sitemap_provider' ) ) {
		return;
	}
	class RAU_Sitemap_Herramientas extends WP_Sitemaps_Provider {
		public function __construct() {
			$this->name        = 'herramientas';
			$this->object_type = 'herramienta';
		}
		public function get_url_list( $page_num, $object_subtype = '' ) {
			$urls = array();
			foreach ( rau_get_herramientas_tabs() as $t ) {
				$urls[] = array( 'loc' => home_url( '/herramientas/' . $t['id'] . '/' ) );
			}
			return $urls;
		}
		public function get_max_num_pages( $object_subtype = '' ) {
			return 1; // 54 URLs caben de sobra en una sola página de sitemap
		}
	}
	wp_register_sitemap_provider( 'herramientas', new RAU_Sitemap_Herramientas() );
} );

function rau_url_canonica() {
	if ( is_page_template( 'page-herramientas.php' ) && get_query_var( 'rau_tool' ) ) {
		$id = sanitize_title( get_query_var( 'rau_tool' ) );
		if ( rau_find_tool_by_id( $id ) ) {
			return home_url( '/herramientas/' . $id . '/' );
		}
	}
	if ( is_singular() ) return wp_get_canonical_url();
	// Antes todo lo que no era singular ni archivo de un tipo devolvía la portada: categorías,
	// etiquetas, autores y las páginas 2, 3… de cualquier listado le decían a Google que eran
	// copias de la portada. Búsquedas y 404 no llevan canonical.
	if ( is_search() || is_404() ) return '';
	$url = '';
	if ( is_front_page() )            $url = home_url( '/' );
	elseif ( is_home() )              $url = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/' );
	elseif ( is_post_type_archive() ) $url = get_post_type_archive_link( get_query_var( 'post_type' ) );
	elseif ( is_category() || is_tag() || is_tax() ) $url = get_term_link( get_queried_object() );
	elseif ( is_author() )            $url = get_author_posts_url( get_queried_object_id() );
	if ( ! $url || is_wp_error( $url ) ) return '';
	$pagina = (int) get_query_var( 'paged' );
	if ( $pagina > 1 ) $url = trailingslashit( $url ) . 'page/' . $pagina . '/';
	return $url;
}

// Se sustituye el canonical del core por el nuestro, que sí distingue cada herramienta.
remove_action( 'wp_head', 'rel_canonical' );
add_action( 'wp_head', function() {
	$url = rau_url_canonica();
	if ( $url ) echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
}, 1 );

/**
 * Descripción para una página singular sin extracto ni contenido. Sale de los datos de la propia
 * página (campos del repetidor o del evento, introducción de la plantilla), no de un texto genérico.
 */
function rau_descripcion_de_respaldo() {
	$id   = get_the_ID();
	$meta = function ( $k ) use ( $id ) { return trim( (string) get_post_meta( $id, $k, true ) ); };
	switch ( get_post_type() ) {
		case 'repetidor':
			$partes = array_filter( array(
				$meta( 'callsign' ),
				$meta( 'frequency' ) ? $meta( 'frequency' ) . ' MHz' : '',
				$meta( 'mode' ),
				$meta( 'tone' ) ? 'tono ' . $meta( 'tone' ) : '',
				$meta( 'location' ),
			) );
			return get_the_title() . ( $partes ? ': ' . implode( ' · ', $partes ) : '' ) . '. Datos del repetidor y simulador de cobertura.';
		case 'evento':
			$partes = array_filter( array( $meta( 'type' ), $meta( 'start' ), $meta( 'frequency' ), $meta( 'location' ) ) );
			return get_the_title() . ( $partes ? ' — ' . implode( ' · ', $partes ) : '' ) . '. Actividad de Radioaficionados Unidos.';
		case 'alerta':
			return 'Aviso de la red de Radioaficionados Unidos: ' . get_the_title() . '.';
	}
	$por_plantilla = array(
		'page-alertas.php'             => 'Comunicaciones de emergencia y avisos activos de la red HK4RAU.',
		'page-canales.php'             => 'Además de las repetidoras, así es como la comunidad de RAU se mantiene en contacto todos los días.',
		'page-checkin.php'             => 'Check-in de la malla semanal de Radioaficionados Unidos: reporta tu asistencia con tu indicativo.',
		'page-contacto.php'            => '¿Consulta, propuesta de alianza, prensa, o simplemente quieres saludar? Escríbenos.',
		'page-diplomas.php'            => 'Diplomas oficiales emitidos por HK4RAU para sus miembros y operadores participantes en actividades especiales.',
		'page-directorio-miembros.php' => 'Directorio de miembros de Radioaficionados Unidos que autorizaron publicar sus datos.',
		'page-documentos.php'          => 'Actas, estatutos y resoluciones de la asociación en un solo lugar.',
		'page-elmer.php'               => 'Programa Elmer: conectamos a quienes recién obtuvieron su indicativo con miembros de RAU dispuestos a acompañarlos.',
		'page-emergencia-modo.php'     => 'Modo Emergencia de RAU: frecuencias, hora UTC y datos esenciales en una página ligera, pensada para conectividad limitada.',
		'page-estado.php'              => 'Estado de RAU: todo lo operativo de la asociación en un solo lugar.',
		'page-field.php'               => 'RAU Field: vista simplificada para operación portátil y POTA, con botones grandes y pensada para usarse con una mano.',
		'page-galeria.php'             => 'Registro fotográfico de mallas, eventos especiales, activaciones POTA y encuentros de la asociación.',
		'page-herramientas.php'        => 'Calculadoras y recursos operativos para radioaficionados. Todo funciona directamente en el navegador, sin necesidad de instalar nada.',
		'page-junta-directiva.php'     => 'Quienes coordinan Radioaficionados Unidos y sus áreas de trabajo.',
		'page-libro-visitas.php'       => 'Libro de visitas de Radioaficionados Unidos: deja un mensaje corto y tu indicativo si tienes uno.',
		'page-novedades.php'           => 'Qué se ha ido agregando a hk4rau.com, en orden.',
		'page-pulguero.php'            => 'Pulguero de RAU: equipos, antenas y accesorios entre miembros de la asociación.',
		'page-qsl.php'                 => 'Bitácora de contactos (QSO) de la asociación y su confirmación vía QSL.',
		'page-turnos.php'              => 'Quién está de turno para monitorear la Red de Emergencia cada semana.',
	);
	$plantilla = get_page_template_slug();
	if ( isset( $por_plantilla[ $plantilla ] ) ) return $por_plantilla[ $plantilla ];
	$tipo = get_post_type_object( get_post_type() );
	return get_the_title() . ( $tipo ? ' — ' . $tipo->labels->singular_name : '' ) . ' de Radioaficionados Unidos.';
}

function rau_seo_meta_tags() {
	if ( is_front_page() ) {
		$title = get_bloginfo( 'name' ) . ' — Radioaficionados Unidos';
		$desc  = get_theme_mod( 'rau_hero_subtitle', 'El hogar digital de la asociación RAU: repetidores, nodos, eventos, recursos técnicos, malla de emergencia y una comunidad que mantiene vivas las ondas.' );
	} elseif ( is_page_template( 'page-herramientas.php' ) && get_query_var( 'rau_tool' ) ) {
		$tool = rau_find_tool_by_id( sanitize_title( get_query_var( 'rau_tool' ) ) );
		if ( $tool ) {
			$title = $tool['label'] . ' — Herramientas RAU';
			$desc  = 'Usa gratis la herramienta "' . $tool['label'] . '" de Radioaficionados Unidos (categoría: ' . $tool['cat'] . '). Sin necesidad de cuenta ni instalar nada.';
		} else {
			$title = wp_get_document_title();
			$desc  = get_bloginfo( 'description' );
		}
	} elseif ( is_singular() ) {
		$title = get_the_title() . ' — ' . get_bloginfo( 'name' );
		$desc  = has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 30 );
		// 41 páginas salían con content="": las que se construyen desde su plantilla o desde
		// campos personalizados no tienen texto en el editor.
		if ( '' === trim( wp_strip_all_tags( $desc ) ) ) $desc = rau_descripcion_de_respaldo();
	} elseif ( is_post_type_archive() ) {
		$title = wp_get_document_title();
		$archive_desc = array(
			'repetidor'      => 'Directorio completo de repetidores de radioaficionados en Colombia — frecuencias, tonos y ubicaciones.',
			'evento'         => 'Calendario de mallas, concursos y actividades de Radioaficionados Unidos.',
			'diploma'        => 'Diplomas y certificaciones otorgados por Radioaficionados Unidos.',
			'historia'       => 'Historias y testimonios de la comunidad de Radioaficionados Unidos.',
			'galeria_item'   => 'Galería de actividades de Radioaficionados Unidos.',
			'directivo'      => 'Junta Directiva de Radioaficionados Unidos.',
		);
		$desc = $archive_desc[ get_post_type() ] ?? get_bloginfo( 'description' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$title = wp_get_document_title();
		$desc  = term_description() ?: 'Publicaciones de Radioaficionados Unidos en «' . single_term_title( '', false ) . '».';
	} else {
		$title = wp_get_document_title();
		$desc  = get_bloginfo( 'description' );
	}
	$desc = wp_strip_all_tags( $desc );
	$logo_id = has_custom_logo() ? get_theme_mod( 'custom_logo' ) : 0;
	if ( $logo_id ) {
		$logo    = wp_get_attachment_image_url( $logo_id, 'full' );
		$logo_wh = wp_get_attachment_image_src( $logo_id, 'full' );
		$logo_w  = $logo_wh ? $logo_wh[1] : 640;
		$logo_h  = $logo_wh ? $logo_wh[2] : 591;
	} else {
		// Sin logo subido en el Personalizador: usamos el badge del tema como respaldo
		// — así los links compartidos en WhatsApp/Facebook/Twitter siempre muestran imagen.
		$logo   = get_template_directory_uri() . '/assets/images/rau-badge.png';
		$logo_w = 640;
		$logo_h = 591;
	}
	?>
	<meta name="description" content="<?php echo esc_attr( $desc ); ?>">
	<meta property="og:type" content="<?php echo is_singular() ? 'article' : 'website'; ?>">
	<meta property="og:locale" content="es_CO">
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $desc ); ?>">
	<?php if ( rau_url_canonica() ) : ?><meta property="og:url" content="<?php echo esc_url( rau_url_canonica() ); ?>"><?php endif; ?>
	<meta property="og:site_name" content="<?php bloginfo( 'name' ); ?>">
	<?php if ( is_singular() ) : ?>
	<meta property="article:published_time" content="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
	<meta property="article:modified_time" content="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
	<?php endif; ?>
	<meta property="og:image" content="<?php echo esc_url( $logo ); ?>">
	<meta property="og:image:width" content="<?php echo esc_attr( $logo_w ); ?>">
	<meta property="og:image:height" content="<?php echo esc_attr( $logo_h ); ?>">
	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
	<meta name="twitter:description" content="<?php echo esc_attr( $desc ); ?>">
	<meta name="twitter:image" content="<?php echo esc_url( $logo ); ?>">
	<meta name="theme-color" content="#080c16">
	<?php
}
add_action( 'wp_head', 'rau_seo_meta_tags', 1 );

/**
 * Datos estructurados Schema.org — ayuda a que Google muestre la asociación
 * y los eventos de forma más completa en los resultados de búsqueda.
 */
function rau_structured_data() {
	$logo_id = has_custom_logo() ? get_theme_mod( 'custom_logo' ) : 0;
	$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : get_template_directory_uri() . '/assets/images/rau-badge.png';

	// SoftwareApplication por herramienta individual — le dice a Google
	// explícitamente "esto es una calculadora gratuita", no solo texto.
	if ( is_page_template( 'page-herramientas.php' ) && get_query_var( 'rau_tool' ) ) {
		$tool = rau_find_tool_by_id( sanitize_title( get_query_var( 'rau_tool' ) ) );
		if ( $tool ) {
			$app = array(
				'@context'      => 'https://schema.org',
				'@type'         => 'SoftwareApplication',
				'name'          => $tool['label'] . ' — RAU',
				'applicationCategory' => 'UtilitiesApplication',
				'operatingSystem' => 'Any (navegador web)',
				'url'           => home_url( '/herramientas/' . $tool['id'] . '/' ),
				'offers'        => array( '@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD' ),
				'provider'      => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ),
			);
			echo '<script type="application/ld+json">' . wp_json_encode( $app ) . '</script>' . "\n";
		}
	}

	$same_as = array_filter( array(
		'https://www.instagram.com/hk4rau',
		'https://www.facebook.com/share/1Cahc9bBYr/',
		'https://www.youtube.com/@radioaficionadosunidos',
		'https://www.qrz.com/db/HK4RAU',
	) );

	$org = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'name'     => get_bloginfo( 'name' ) . ' — Radioaficionados Unidos',
		'url'      => home_url( '/' ),
		'sameAs'   => array_values( $same_as ),
	);
	if ( $logo ) $org['logo'] = $logo;

	echo '<script type="application/ld+json">' . wp_json_encode( $org ) . '</script>' . "\n";

	if ( is_singular( 'evento' ) ) {
		$id    = get_the_ID();
		$start = get_post_meta( $id, 'start', true );
		$loc   = get_post_meta( $id, 'location', true );
		if ( $start ) {
			$event = array(
				'@context'    => 'https://schema.org',
				'@type'       => 'Event',
				'name'        => get_the_title( $id ),
				'startDate'   => $start,
				'eventStatus' => 'https://schema.org/EventScheduled',
				'description' => wp_strip_all_tags( get_the_content( null, false, $id ) ),
				'url'         => get_permalink( $id ),
				'location'    => array(
					'@type' => 'Place',
					'name'  => $loc ? $loc : get_bloginfo( 'name' ),
				),
				'organizer'   => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
					'url'   => home_url( '/' ),
				),
			);
			echo '<script type="application/ld+json">' . wp_json_encode( $event ) . '</script>' . "\n";
		}
	}

	// BreadcrumbList — ayuda a que Google muestre "Inicio > Categoría > Título"
	// en vez de la URL cruda en los resultados de búsqueda.
	if ( is_singular() && ! is_front_page() ) {
		$crumbs = array(
			array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => home_url( '/' ) ),
		);
		$archive_link = get_post_type_archive_link( get_post_type() );
		if ( $archive_link ) {
			$crumbs[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => get_post_type_object( get_post_type() )->labels->name,
				'item'     => $archive_link,
			);
		}
		$crumbs[] = array(
			'@type'    => 'ListItem',
			'position' => count( $crumbs ) + 1,
			'name'     => get_the_title(),
			'item'     => get_permalink(),
		);
		$breadcrumb = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $crumbs,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb ) . '</script>' . "\n";
	}

	// WebSite + SearchAction — habilita que Google muestre una caja de
	// búsqueda directamente bajo el resultado del sitio (sitelinks search box).
	if ( is_front_page() ) {
		$website = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'WebSite',
			'name'          => get_bloginfo( 'name' ),
			'url'           => home_url( '/' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $website ) . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'rau_structured_data', 2 );

/**
 * Limpieza automática de datos de ejemplo, ahora reemplazados por datos reales.
 * Corre UNA sola vez (queda marcado en wp_options) apenas entres a cualquier
 * página del escritorio después de subir esta versión del tema. No necesitas
 * hacer nada — no borra nada más aparte de lo que se lista aquí, y busca por
 * el slug exacto de cada entrada de ejemplo, así que es seguro.
 */
function rau_cleanup_datos_ejemplo() {
	if ( get_option( 'rau_cleanup_v1_done' ) ) return;

	// Junta Directiva de ejemplo -> ya reemplazada por los 7 miembros reales
	$slugs_directivo = array( 'carlos-ramirez', 'ana-gomez', 'luis-torres' );
	foreach ( $slugs_directivo as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'directivo' );
		if ( $post ) wp_delete_post( $post->ID, true );
	}

	// Contenido institucional de ejemplo -> ya reemplazado por el real
	$slugs_contenido = array( 'guia-para-la-licencia', 'normativa-vigente' );
	foreach ( $slugs_contenido as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'contenido' );
		if ( $post ) wp_delete_post( $post->ID, true );
	}

	// Repetidor de Bucaramanga: corrige la ubicación de "Santander" (departamento) a la ciudad real
	$rep = get_page_by_path( 'repetidora-santander', OBJECT, 'repetidor' );
	if ( $rep ) update_post_meta( $rep->ID, 'location', 'Bucaramanga' );

	update_option( 'rau_cleanup_v1_done', 1 );
}
add_action( 'admin_init', 'rau_cleanup_datos_ejemplo' );

/**
 * Endurecimiento básico de seguridad (recomendado para cualquier sitio en producción)
 */

// Oculta la versión exacta de WordPress (en el <head>, RSS y JS/CSS del core) —
// no cambia nada visualmente, solo evita facilitarle a un atacante saber qué
// vulnerabilidades conocidas buscar para tu versión instalada.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
add_filter( 'style_loader_src', 'rau_remove_wp_version_strings', 9999 );
add_filter( 'script_loader_src', 'rau_remove_wp_version_strings', 9999 );
function rau_remove_wp_version_strings( $src ) {
	global $wp_version;
	if ( strpos( $src, 'ver=' . $wp_version ) !== false ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}

// Mensaje de error genérico al iniciar sesión — por defecto WordPress dice
// "usuario no existe" o "contraseña incorrecta" por separado, lo que le permite
// a alguien adivinar qué usuarios sí existen probando uno por uno.
add_filter( 'login_errors', function() {
	return __( 'Usuario o contraseña incorrectos.', 'rau-theme' );
} );

// Desactiva específicamente los métodos de pingback de XML-RPC — es el vector
// que más se usa para ataques de fuerza bruta y de amplificación DDoS contra
// WordPress. Deja el resto de XML-RPC intacto por si algún plugin lo necesita
// (ej. publicar desde la app oficial de WordPress).
add_filter( 'xmlrpc_methods', function( $methods ) {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
	return $methods;
} );
remove_action( 'wp_head', 'rsd_link' );
add_filter( 'wp_headers', function( $headers ) {
	unset( $headers['X-Pingback'] );
	// Cabeceras de seguridad que el alojamiento no envía. HSTS sin includeSubDomains ni preload:
	// lo conservador, que no compromete a subdominios que quizá no tengan HTTPS.
	// Sin Permissions-Policy a propósito: varias herramientas piden la ubicación (GPS).
	$headers['Strict-Transport-Security'] = 'max-age=31536000';
	$headers['X-Content-Type-Options']    = 'nosniff';
	$headers['X-Frame-Options']           = 'SAMEORIGIN';
	$headers['Referrer-Policy']           = 'strict-origin-when-cross-origin';
	return $headers;
} );
/* Autorización para aparecer en el Directorio de Miembros (campo "visible"). La plantilla
 * prometía "solo aparecen los miembros que autorizaron" pero listaba a todos; el campo existía
 * solo en un comentario. Sin la casilla marcada, el miembro no se publica. */
add_action( 'add_meta_boxes_miembro', function() {
	add_meta_box( 'rau_miembro_visible', 'Directorio público', function( $post ) {
		wp_nonce_field( 'rau_miembro_visible', 'rau_miembro_visible_nonce' );
		$marcado = get_post_meta( $post->ID, 'visible', true ) === '1';
		echo '<label><input type="checkbox" name="rau_visible" value="1"' . checked( $marcado, true, false ) . '> ';
		echo 'Autorizó aparecer en el Directorio de Miembros</label>';
		echo '<p class="description">Sin esta casilla el miembro no se muestra en la página pública.</p>';
	}, 'miembro', 'side' );
} );
add_action( 'save_post_miembro', function( $post_id ) {
	if ( ! isset( $_POST['rau_miembro_visible_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rau_miembro_visible_nonce'] ), 'rau_miembro_visible' ) ) return;
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) return;
	update_post_meta( $post_id, 'visible', empty( $_POST['rau_visible'] ) ? '0' : '1' );
} );

// El servidor anunciaba la versión exacta de PHP en cada respuesta.
add_action( 'send_headers', function() { header_remove( 'X-Powered-By' ); } );

/* Enumeración de usuarios: /wp-json/wp/v2/users, /?author=1 y el sitemap de usuarios publicaban
 * el usuario administrador. Es la mitad de lo que hace falta para adivinar la contraseña. */
add_filter( 'rest_endpoints', function( $endpoints ) {
	if ( is_user_logged_in() ) return $endpoints; // el editor de bloques los necesita
	foreach ( array_keys( $endpoints ) as $ruta ) {
		// Usuarios, y los tipos sin ficha pública: la API listaba el nombre de todos los
		// miembros, también de los que no autorizaron aparecer en el directorio.
		if ( preg_match( '#^/wp/v2/(users|miembro|turno|qso)(/|$)#', $ruta ) ) unset( $endpoints[ $ruta ] );
	}
	return $endpoints;
} );
// Prioridad 1: antes que redirect_canonical, que respondía a /?author=1 con una redirección
// cuya cabecera Location ya revelaba el usuario.
add_action( 'template_redirect', function() {
	if ( is_author() && ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 1 );
add_filter( 'wp_sitemaps_add_provider', function( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

// El script de emojis de WordPress (hasta 73 KB) se cargaba en todas las páginas; los navegadores
// actuales dibujan los emojis por sí mismos.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
add_filter( 'emoji_svg_url', '__return_false' );

/* =========================================================================
 * Fase 2 — Check-in de asistencia a mallas
 * -------------------------------------------------------------------------
 * Formulario público (plantilla "Check-in de Malla") donde cada operador
 * reporta su asistencia a la malla con indicativo + nombre. Cada envío se
 * guarda como un post del tipo `checkin` con la fecha del día, y la misma
 * página lista quiénes ya se reportaron hoy.
 *
 * Protecciones sin plugins ni servicios externos:
 *  - Nonce de WordPress (el formulario caduca y no se puede reenviar de fuera)
 *  - Campo honeypot invisible (los bots lo llenan, los humanos no)
 *  - Límite de 1 envío por IP cada 2 minutos (transient)
 *  - Rechazo de indicativo duplicado el mismo día
 * ========================================================================= */

/**
 * Valida un indicativo de radioaficionado (formato flexible):
 * letras/números, opcionalmente con sufijo /M, /P, /QRP, etc.
 * Ej.: HK4SSI, HK4ABC/M, 5K4IC, HJ4XYZ/P
 */
function rau_checkin_validar_indicativo( $raw ) {
	$ind = strtoupper( trim( (string) $raw ) );
	if ( ! preg_match( '/^[A-Z0-9]{3,8}(\/[A-Z0-9]{1,4})?$/', $ind ) ) {
		return false;
	}
	// Debe contener al menos una letra y al menos un número (todo indicativo real los tiene)
	if ( ! preg_match( '/[A-Z]/', $ind ) || ! preg_match( '/[0-9]/', $ind ) ) {
		return false;
	}
	return $ind;
}

/**
 * Procesa el envío del formulario de check-in (usuarios logueados y visitantes).
 */
function rau_checkin_handle() {
	$volver = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$volver = remove_query_arg( array( 'checkin' ), $volver );

	// 1. Nonce
	if ( ! isset( $_POST['rau_checkin_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rau_checkin_nonce'] ), 'rau_checkin' ) ) {
		wp_safe_redirect( add_query_arg( 'checkin', 'error', $volver ) ); exit;
	}

	// 2. Honeypot: si el campo oculto "sitio_web" trae algo, es un bot. Fingimos éxito.
	if ( ! empty( $_POST['sitio_web'] ) ) {
		wp_safe_redirect( add_query_arg( 'checkin', 'ok', $volver ) ); exit;
	}

	// 3. Límite por IP: 1 envío cada 2 minutos
	$ip_hash = md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) );
	if ( get_transient( 'rau_ci_' . $ip_hash ) ) {
		wp_safe_redirect( add_query_arg( 'checkin', 'despacio', $volver ) ); exit;
	}

	// 4. Campos
	$indicativo = rau_checkin_validar_indicativo( $_POST['indicativo'] ?? '' );
	$nombre     = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
	$ciudad     = sanitize_text_field( wp_unslash( $_POST['ciudad'] ?? '' ) );
	$comentario = sanitize_text_field( wp_unslash( $_POST['comentario'] ?? '' ) );
	$grid       = strtoupper( sanitize_text_field( wp_unslash( $_POST['grid'] ?? '' ) ) );
	$nombre     = mb_substr( $nombre, 0, 60 );
	$ciudad     = mb_substr( $ciudad, 0, 60 );
	$comentario = mb_substr( $comentario, 0, 200 );
	// Grid opcional — si no tiene formato Maidenhead válido (4 o 6 caracteres), se descarta en silencio, no bloquea el check-in
	if ( ! preg_match( '/^[A-R]{2}[0-9]{2}([A-X]{2})?$/i', $grid ) ) $grid = '';

	if ( ! $indicativo ) {
		wp_safe_redirect( add_query_arg( 'checkin', 'indicativo', $volver ) ); exit;
	}
	if ( '' === $nombre ) {
		wp_safe_redirect( add_query_arg( 'checkin', 'nombre', $volver ) ); exit;
	}

	// 5. ¿Ya hizo check-in hoy este indicativo?
	$hoy = current_time( 'Y-m-d' );
	$dup = get_posts( array(
		'post_type'      => 'checkin',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => 'indicativo', 'value' => $indicativo ),
			array( 'key' => 'fecha', 'value' => $hoy ),
		),
	) );
	if ( $dup ) {
		wp_safe_redirect( add_query_arg( 'checkin', 'duplicado', $volver ) ); exit;
	}

	// 6. Guardar
	$post_id = wp_insert_post( array(
		'post_type'   => 'checkin',
		'post_status' => 'publish',
		'post_title'  => $indicativo . ' — ' . $nombre,
		'meta_input'  => array(
			'indicativo' => $indicativo,
			'nombre'     => $nombre,
			'ciudad'     => $ciudad,
			'comentario' => $comentario,
			'grid'       => $grid,
			'fecha'      => $hoy,
			'hora'       => current_time( 'H:i' ),
		),
	), false );

	if ( ! $post_id ) {
		wp_safe_redirect( add_query_arg( 'checkin', 'error', $volver ) ); exit;
	}

	set_transient( 'rau_ci_' . $ip_hash, 1, 2 * MINUTE_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'checkin', 'ok', $volver ) ); exit;
}
add_action( 'admin_post_rau_checkin', 'rau_checkin_handle' );

/**
 * Procesa el envío del Libro de Visitas (público, permanente).
 */
function rau_guestbook_handle() {
	$volver = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$volver = remove_query_arg( array( 'libro' ), $volver );

	// 1. Nonce
	if ( ! isset( $_POST['rau_guestbook_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rau_guestbook_nonce'] ), 'rau_guestbook' ) ) {
		wp_safe_redirect( add_query_arg( 'libro', 'error', $volver ) ); exit;
	}

	// 2. Honeypot
	if ( ! empty( $_POST['sitio_web'] ) ) {
		wp_safe_redirect( add_query_arg( 'libro', 'ok', $volver ) ); exit;
	}

	// 3. Límite por IP: 1 envío cada 3 minutos
	$ip_hash = md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) );
	if ( get_transient( 'rau_lv_' . $ip_hash ) ) {
		wp_safe_redirect( add_query_arg( 'libro', 'despacio', $volver ) ); exit;
	}

	// 4. Campos
	$indicativo = sanitize_text_field( wp_unslash( $_POST['indicativo'] ?? '' ) );
	$indicativo = strtoupper( preg_replace( '/[^A-Za-z0-9\/]/', '', $indicativo ) );
	$nombre     = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
	$ciudad     = sanitize_text_field( wp_unslash( $_POST['ciudad'] ?? '' ) );
	$mensaje    = sanitize_textarea_field( wp_unslash( $_POST['mensaje'] ?? '' ) );
	$nombre     = mb_substr( $nombre, 0, 60 );
	$ciudad     = mb_substr( $ciudad, 0, 60 );
	$mensaje    = mb_substr( $mensaje, 0, 400 );

	if ( '' === $nombre ) {
		wp_safe_redirect( add_query_arg( 'libro', 'nombre', $volver ) ); exit;
	}
	if ( '' === $mensaje ) {
		wp_safe_redirect( add_query_arg( 'libro', 'mensaje', $volver ) ); exit;
	}

	// 5. Guardar (queda visible de inmediato, igual que el check-in)
	$post_id = wp_insert_post( array(
		'post_type'    => 'libro_visitas',
		'post_status'  => 'publish',
		'post_title'   => $indicativo ? $indicativo . ' — ' . $nombre : $nombre,
		'post_content' => $mensaje,
		'meta_input'   => array(
			'indicativo' => $indicativo,
			'nombre'     => $nombre,
			'ciudad'     => $ciudad,
		),
	), false );

	if ( ! $post_id ) {
		wp_safe_redirect( add_query_arg( 'libro', 'error', $volver ) ); exit;
	}

	set_transient( 'rau_lv_' . $ip_hash, 1, 3 * MINUTE_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'libro', 'ok', $volver ) ); exit;
}
add_action( 'admin_post_rau_guestbook', 'rau_guestbook_handle' );

/**
 * Procesa el Formulario de Contacto (recuperado). No se guarda en base de
 * datos — solo se envía por correo a info@hk4rau.com, con Reply-To
 * apuntando directo al remitente.
 */
function rau_contacto_handle() {
	$volver = wp_get_referer() ? wp_get_referer() : home_url( '/contacto/' );
	$volver = remove_query_arg( array( 'contacto' ), $volver );

	if ( ! isset( $_POST['rau_contacto_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rau_contacto_nonce'] ), 'rau_contacto' ) ) {
		wp_safe_redirect( add_query_arg( 'contacto', 'error', $volver ) ); exit;
	}
	if ( ! empty( $_POST['sitio_web'] ) ) {
		wp_safe_redirect( add_query_arg( 'contacto', 'ok', $volver ) ); exit;
	}
	$ip_hash = md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) );
	if ( get_transient( 'rau_contacto_' . $ip_hash ) ) {
		wp_safe_redirect( add_query_arg( 'contacto', 'despacio', $volver ) ); exit;
	}

	$nombre  = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
	$correo  = sanitize_email( wp_unslash( $_POST['correo'] ?? '' ) );
	$motivo  = sanitize_text_field( wp_unslash( $_POST['motivo'] ?? '' ) );
	$mensaje = sanitize_textarea_field( wp_unslash( $_POST['mensaje'] ?? '' ) );

	if ( '' === $nombre || ! is_email( $correo ) || '' === $mensaje ) {
		wp_safe_redirect( add_query_arg( 'contacto', 'invalido', $volver ) ); exit;
	}

	$motivos_validos = array( 'general' => 'Consulta general', 'afiliacion' => 'Afiliación', 'prensa' => 'Prensa / medios', 'alianza' => 'Propuesta de alianza', 'otro' => 'Otro' );
	$motivo_label    = $motivos_validos[ $motivo ] ?? 'Consulta general';

	$asunto = '[Contacto RAU] ' . $motivo_label . ' — ' . $nombre;
	$cuerpo = "Nombre: {$nombre}\nCorreo: {$correo}\nMotivo: {$motivo_label}\n\nMensaje:\n{$mensaje}";
	$headers = array( 'Reply-To: ' . $nombre . ' <' . $correo . '>' );

	$enviado = wp_mail( 'info@hk4rau.com', $asunto, $cuerpo, $headers );

	set_transient( 'rau_contacto_' . $ip_hash, 1, 2 * MINUTE_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'contacto', $enviado ? 'ok' : 'error', $volver ) ); exit;
}
add_action( 'admin_post_rau_contacto', 'rau_contacto_handle' );
add_action( 'admin_post_nopriv_rau_contacto', 'rau_contacto_handle' );

/**
 * El Libro de Visitas es permanente por diseño: el formulario siempre crea
 * las entradas como 'publish' directo, nada las manda a la papelera solo.
 * El único riesgo real es que WordPress vacía la papelera automáticamente
 * a los 30 días — si alguna vez alguien manda una entrada ahí sin querer,
 * esto evita que el cron de 30 días la borre para siempre en silencio.
 * El borrado manual e intencional (para quitar spam) sigue funcionando igual.
 */
add_filter( 'pre_delete_post', function( $check, $post, $force_delete ) {
	if ( $force_delete && 'libro_visitas' === get_post_type( $post ) && doing_action( 'wp_scheduled_delete' ) ) {
		return false;
	}
	return $check;
}, 10, 3 );
add_action( 'admin_post_nopriv_rau_guestbook', 'rau_guestbook_handle' );
add_action( 'admin_post_nopriv_rau_checkin', 'rau_checkin_handle' );

/**
 * Columnas útiles en el listado del escritorio (Check-ins de Malla).
 */
add_filter( 'manage_checkin_posts_columns', function( $cols ) {
	return array(
		'cb'         => $cols['cb'],
		'title'      => __( 'Check-in', 'rau-theme' ),
		'indicativo' => __( 'Indicativo', 'rau-theme' ),
		'ciudad'     => __( 'Ciudad', 'rau-theme' ),
		'fecha_ci'   => __( 'Fecha de la malla', 'rau-theme' ),
		'hora_ci'    => __( 'Hora', 'rau-theme' ),
	);
} );
add_action( 'manage_checkin_posts_custom_column', function( $col, $post_id ) {
	if ( 'indicativo' === $col ) echo '<code>' . esc_html( get_post_meta( $post_id, 'indicativo', true ) ) . '</code>';
	if ( 'ciudad'     === $col ) echo esc_html( get_post_meta( $post_id, 'ciudad', true ) );
	if ( 'fecha_ci'   === $col ) echo esc_html( get_post_meta( $post_id, 'fecha', true ) );
	if ( 'hora_ci'    === $col ) echo esc_html( get_post_meta( $post_id, 'hora', true ) );
}, 10, 2 );

/**
 * Exportar todos los check-ins a CSV (botón arriba del listado en el escritorio).
 * Útil para llevar la estadística de asistencia de las mallas en Excel.
 */
add_action( 'admin_post_rau_checkin_export', function() {
	if ( ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ?? '' ), 'rau_checkin_export' ) ) {
		wp_die( esc_html__( 'Sin permiso.', 'rau-theme' ) );
	}
	$rows = get_posts( array( 'post_type' => 'checkin', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'ASC' ) );
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="checkins-malla-rau.csv"' );
	$out = fopen( 'php://output', 'w' );
	fputs( $out, "\xEF\xBB\xBF" ); // BOM para que Excel abra bien las tildes
	fputcsv( $out, array( 'Fecha', 'Hora', 'Indicativo', 'Nombre', 'Ciudad', 'Comentario' ), ';' );
	foreach ( $rows as $p ) {
		fputcsv( $out, array(
			get_post_meta( $p->ID, 'fecha', true ),
			get_post_meta( $p->ID, 'hora', true ),
			get_post_meta( $p->ID, 'indicativo', true ),
			get_post_meta( $p->ID, 'nombre', true ),
			get_post_meta( $p->ID, 'ciudad', true ),
			get_post_meta( $p->ID, 'comentario', true ),
		), ';' );
	}
	fclose( $out ); exit;
} );
add_action( 'restrict_manage_posts', function( $post_type ) {
	if ( 'checkin' !== $post_type ) return;
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=rau_checkin_export' ), 'rau_checkin_export' );
	echo '<a href="' . esc_url( $url ) . '" class="button">' . esc_html__( 'Exportar todo a CSV', 'rau-theme' ) . '</a>';
} );

/* =========================================================================
 * Propagación solar — proxy server-side con caché de 30 minutos
 * Evita el bloqueo CORS que impide hacer fetch desde el navegador.
 * ========================================================================= */
/**
 * wp_remote_get con memoria corta de fallos.
 *
 * Los proxies del tema no guardaban nada en caché cuando la fuente externa fallaba, así que
 * mientras un servicio estuviera caído CADA visita volvía a intentarlo y se quedaba esperando
 * el timeout completo (4 a 10 s según el proxy) antes de responder. Con varios paneles en la
 * misma página eso se acumula y bloquea admin-ajax.
 *
 * Al marcar la URL como caída durante unos minutos, el siguiente visitante recibe el fallo al
 * instante en vez de esperar. Devuelve un WP_Error, que es exactamente lo que ya comprueban
 * todos los llamadores, así que no cambia el flujo de ninguno.
 *
 * $ignorar_marca sirve para el modo diagnóstico: sin él, el diagnóstico se topaba con la marca
 * que había dejado cualquier visita anterior y reportaba su propia caché en vez del error real
 * de la fuente, que es justo el dato que se quiere ver. En ese modo tampoco escribe la marca,
 * para no dejar las fuentes bloqueadas por haber diagnosticado.
 */
function rau_remote_get( $url, $args = array(), $minutos_backoff = 2, $ignorar_marca = false ) {
	$clave = 'rau_caida_' . md5( $url );
	if ( ! $ignorar_marca && get_transient( $clave ) ) {
		return new WP_Error( 'rau_backoff', 'Fuente marcada como caída hace poco; no se reintenta todavía.' );
	}
	$respuesta = wp_remote_get( $url, $args );
	if ( ! $ignorar_marca && ( is_wp_error( $respuesta ) || 200 !== (int) wp_remote_retrieve_response_code( $respuesta ) ) ) {
		set_transient( $clave, 1, $minutos_backoff * MINUTE_IN_SECONDS );
	}
	return $respuesta;
}

add_action( 'wp_ajax_rau_solar', 'rau_solar_proxy' );
add_action( 'wp_ajax_nopriv_rau_solar', 'rau_solar_proxy' );
function rau_solar_proxy() {
	// Intentar desde caché primero
	$cached = get_transient( 'rau_solar_data' );
	if ( $cached ) {
		wp_send_json_success( $cached );
		return;
	}

	$response = rau_remote_get( 'https://www.hamqsl.com/solarxml.php', array(
		'timeout'    => 8,
		'user-agent' => 'Mozilla/5.0 (compatible; HK4RAU/1.0)',
	) );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		wp_send_json_error( 'fetch_failed' );
		return;
	}

	$xml_str = wp_remote_retrieve_body( $response );
	libxml_use_internal_errors( true ); // evita que un XML malformado del servicio externo emita warnings crudos de PHP
	$xml = simplexml_load_string( $xml_str );
	libxml_clear_errors();
	if ( ! $xml ) {
		wp_send_json_error( 'parse_failed' );
		return;
	}

	$bands = array();
	if ( isset( $xml->solardata->calculatedconditions->band ) ) {
		foreach ( $xml->solardata->calculatedconditions->band as $band ) {
			$name = (string) $band['name'];
			$time = (string) $band['time'];
			if ( ! isset( $bands[ $name ] ) ) {
				$bands[ $name ] = array( 'name' => $name, 'day' => '—', 'night' => '—' );
			}
			$bands[ $name ][ $time ] = (string) $band;
		}
	}

	$data = array(
		'sfi'      => (string) ( $xml->solardata->solarflux ?? '—' ),
		'ssn'      => (string) ( $xml->solardata->sunspots ?? '—' ),
		'aindex'   => (string) ( $xml->solardata->aindex ?? '—' ),
		'kindex'   => (string) ( $xml->solardata->kindex ?? '—' ),
		'xray'     => (string) ( $xml->solardata->xray ?? '—' ),
		'updated'  => (string) ( $xml->solardata->updated ?? '' ),
		'bands'    => array_values( $bands ),
	);

	set_transient( 'rau_solar_data', $data, 30 * MINUTE_IN_SECONDS );
	wp_send_json_success( $data );
}

/* =========================================================================
 * Estado 'en vivo' del canal de YouTube — sin API key.
 * YouTube redirige /@canal/live a /watch?v=... solo cuando hay transmisión
 * activa; si no, se queda en la página del canal. Revisamos el header
 * Location de una petición sin seguir redirecciones (barato, no descarga
 * el HTML completo). Caché de 5 min para no golpear YouTube en cada visita.
 * ========================================================================= */
add_action( 'wp_ajax_rau_yt_live', 'rau_youtube_live_proxy' );
add_action( 'wp_ajax_nopriv_rau_yt_live', 'rau_youtube_live_proxy' );
function rau_youtube_live_proxy() {
	$cached = get_transient( 'rau_yt_live' );
	if ( false !== $cached ) {
		wp_send_json_success( $cached );
		return;
	}

	$data = array( 'live' => false, 'url' => '' );

	$response = wp_remote_get( 'https://www.youtube.com/@radioaficionadosunidos/live', array(
		'timeout'     => 8,
		'redirection' => 0, // no seguir el redirect, solo leer el header
		'user-agent'  => 'Mozilla/5.0 (compatible; HK4RAU/1.0)',
	) );

	if ( ! is_wp_error( $response ) ) {
		$location = wp_remote_retrieve_header( $response, 'location' );
		if ( $location && false !== strpos( $location, 'watch?v=' ) ) {
			$data['live'] = true;
			$data['url']  = $location;
		}
	}

	set_transient( 'rau_yt_live', $data, 5 * MINUTE_IN_SECONDS );
	wp_send_json_success( $data );
}

/* =========================================================================
 * Contador de Banderas propio (estilo flagcounter.com)
 * Geolocaliza al visitante por IP (ip-api.com, gratis, sin API key), cuenta
 * como máximo 1 visita por IP por día (deduplicado vía cookie de sesión),
 * y suma el total a wp_options — nunca se borra, no se guarda la IP en sí,
 * solo el conteo agregado por país. Filtra bots básicos por user-agent.
 * ========================================================================= */
/**
 * IP del visitante para el contador de banderas.
 *
 * OJO con la confianza: HTTP_CF_CONNECTING_IP y HTTP_X_FORWARDED_FOR las envía el CLIENTE. Solo
 * son fiables si delante hay un proxy (Cloudflare, balanceador) que las reescriba en cada
 * petición. Si el sitio se sirve directo, cualquiera puede mandar la cabecera que quiera y
 * falsear su país en el contador tantas veces como quiera, saltándose el límite diario por IP.
 *
 * Se mantienen porque el sitio va detrás de Cloudflare, pero ahora se descartan los rangos
 * privados y reservados, que es lo que se usaría para sondear la red interna a través de la
 * consulta de geolocalización. Si algún día se quita Cloudflare, hay que dejar solo REMOTE_ADDR.
 *
 * Nota: los límites por IP de los formularios y de los endpoints que escriben NO usan esta
 * función — usan $_SERVER['REMOTE_ADDR'] directamente, que no se puede falsear. Es lo correcto y
 * conviene que siga así.
 */
function rau_flag_counter_ip() {
	$candidatos = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
	foreach ( $candidatos as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$ip = trim( explode( ',', $_SERVER[ $key ] )[0] );
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return $ip;
			}
		}
	}
	return '';
}

function rau_flag_counter_track() {
	if ( is_admin() || is_user_logged_in() ) return;

	$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
	if ( preg_match( '/bot|crawl|spider|slurp|facebookexternalhit|preview/i', $ua ) ) return;

	$ip = rau_flag_counter_ip();
	if ( ! $ip ) return;

	$ip_hash = md5( $ip . wp_salt( 'nonce' ) );
	if ( get_transient( 'rau_fc_seen_' . $ip_hash ) ) return; // ya contado hoy
	set_transient( 'rau_fc_seen_' . $ip_hash, 1, DAY_IN_SECONDS );

	// Geolocalización con su propia caché de 24h por IP, para no golpear la API de más
	$cache_key = 'rau_fc_geo_' . $ip_hash;
	$geo       = get_transient( $cache_key );
	if ( false === $geo ) {
		$response = wp_remote_get( 'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=status,countryCode,country', array( 'timeout' => 3 ) );
		if ( is_wp_error( $response ) ) return;
		$geo = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $geo ) || ( $geo['status'] ?? '' ) !== 'success' ) return;
		set_transient( $cache_key, $geo, DAY_IN_SECONDS );
	}

	$code = strtoupper( $geo['countryCode'] ?? '' );
	$name = $geo['country'] ?? '';
	if ( ! $code ) return;

	$counts = get_option( 'rau_flag_counts', array() );
	if ( ! isset( $counts[ $code ] ) ) {
		$counts[ $code ] = array( 'name' => $name, 'count' => 0 );
	}
	$counts[ $code ]['count']++;
	update_option( 'rau_flag_counts', $counts, false );
}
add_action( 'wp_footer', 'rau_flag_counter_track' );

/**
 * Devuelve los conteos ordenados de mayor a menor, con emoji de bandera
 * calculado desde el código ISO (sin necesitar tabla de imágenes).
 */
function rau_get_flag_counts() {
	$counts = get_option( 'rau_flag_counts', array() );
	uasort( $counts, function( $a, $b ) { return $b['count'] <=> $a['count']; } );
	$out = array();
	foreach ( $counts as $code => $data ) {
		$flag = '';
		foreach ( str_split( strtoupper( $code ) ) as $letra ) {
			$flag .= mb_chr( 127397 + ord( $letra ), 'UTF-8' );
		}
		$out[] = array( 'code' => $code, 'name' => $data['name'], 'count' => $data['count'], 'flag' => $flag );
	}
	return $out;
}

/* =========================================================================
 * Proxy de TLE (Two-Line Elements) para satélites de radioaficionados.
 * Fuente: CelesTrak, grupo "amateur" — actualizado por ellos varias veces
 * al día. Cacheamos 6h de nuestro lado para no golpear su servidor de más;
 * los TLE no cambian tan rápido como para necesitar más frecuencia que eso.
 * ========================================================================= */
add_action( 'wp_ajax_rau_tle', 'rau_tle_proxy' );
add_action( 'wp_ajax_nopriv_rau_tle', 'rau_tle_proxy' );
function rau_parsear_tle( $body ) {
	$lines = preg_split( '/\r\n|\r|\n/', trim( $body ) );
	$sats  = array();
	for ( $i = 0; $i < count( $lines ); $i += 3 ) {
		if ( ! isset( $lines[ $i ], $lines[ $i + 1 ], $lines[ $i + 2 ] ) ) break;
		// Saltar líneas que no encajan en el formato de 3 líneas (ej. el pie de página de PU4ELT)
		if ( '1 ' !== substr( trim( $lines[ $i + 1 ] ), 0, 2 ) || '2 ' !== substr( trim( $lines[ $i + 2 ] ), 0, 2 ) ) continue;
		$sats[] = array(
			'name'  => trim( $lines[ $i ] ),
			'line1' => trim( $lines[ $i + 1 ] ),
			'line2' => trim( $lines[ $i + 2 ] ),
		);
	}
	return $sats;
}

function rau_tle_proxy() {
	$clave_datos = 'rau_tle_data_' . RAU_THEME_VERSION;
	$clave_fuente = 'rau_tle_fuente_' . RAU_THEME_VERSION;
	$cached = get_transient( $clave_datos );
	if ( false !== $cached ) {
		// Si la caché de datos existe pero la de fuente no (ej. quedó de antes de agregar esta función),
		// autorreparar asumiendo celestrak — es la fuente normal y casi siempre la correcta.
		if ( false === get_transient( $clave_fuente ) ) {
			set_transient( $clave_fuente, 'celestrak', HOUR_IN_SECONDS );
		}
		wp_send_json_success( $cached );
		return;
	}

	$sats = array();
	$fuente = '';

	// Fuente principal: CelesTrak
	$response = rau_remote_get( 'https://celestrak.org/NORAD/elements/gp.php?GROUP=amateur&FORMAT=tle', array( 'timeout' => 10 ) );
	if ( ! is_wp_error( $response ) ) {
		$sats = rau_parsear_tle( wp_remote_retrieve_body( $response ) );
		if ( $sats ) $fuente = 'celestrak';
	}

	// Respaldo: PU4ELT — mismo formato de 3 líneas, catálogo más amplio, actualizado por un radioaficionado (PU4ELT) manualmente
	if ( ! $sats ) {
		$response_pu4elt = rau_remote_get( 'https://www.qsl.net/pu4elt/TLE/PU4ELT_tle.txt', array( 'timeout' => 10 ) );
		if ( ! is_wp_error( $response_pu4elt ) ) {
			$sats = rau_parsear_tle( wp_remote_retrieve_body( $response_pu4elt ) );
			if ( $sats ) $fuente = 'pu4elt_respaldo';
		}
	}

	if ( ! $sats ) {
		wp_send_json_error( 'No se pudo obtener TLE de ninguna fuente (CelesTrak ni el respaldo).' );
		return;
	}

	set_transient( $clave_datos, $sats, 6 * HOUR_IN_SECONDS );
	set_transient( $clave_fuente, $fuente, 6 * HOUR_IN_SECONDS );
	wp_send_json_success( $sats );
}

add_action( 'wp_ajax_rau_tle_fuente',        'rau_tle_fuente_actual' );
add_action( 'wp_ajax_nopriv_rau_tle_fuente', 'rau_tle_fuente_actual' );
function rau_tle_fuente_actual() {
	$fuente = get_transient( 'rau_tle_fuente_' . RAU_THEME_VERSION );
	wp_send_json_success( array( 'fuente' => $fuente ?: 'celestrak' ) );
}

/**
 * Analiza la página de estado de AMSAT y devuelve qué satélites tienen más reportes recientes.
 * Agnóstico a si la marca real es <table> o <ul> — sigue el patrón: enlace real con nombre
 * terminado en "[Modo]", seguido de varios enlaces javascript:void(0) con el número de reportes
 * de cada día. Los enlaces de "Reports From" (indicativos) no tienen ese patrón, así que no contaminan.
 */
function rau_parsear_amsat_status( $html ) {
	if ( ! $html ) return array();
	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
	libxml_clear_errors();
	$xpath   = new DOMXPath( $dom );
	$enlaces = $xpath->query( '//a' );

	$resultados = array();
	$satActual  = null;

	foreach ( $enlaces as $a ) {
		$href  = $a->getAttribute( 'href' );
		$texto = trim( $a->textContent );

		if ( false !== strpos( $href, 'javascript:void' ) ) {
			if ( null !== $satActual && ctype_digit( $texto ) ) {
				$resultados[ $satActual ] += (int) $texto;
			}
		} elseif ( preg_match( '/\[[\w\/\.\s]+\]\s*$/', $texto ) && strlen( $texto ) < 40 ) {
			$satActual = $texto;
			if ( ! isset( $resultados[ $satActual ] ) ) $resultados[ $satActual ] = 0;
		}
	}

	arsort( $resultados );
	return $resultados;
}

add_action( 'wp_ajax_rau_amsat_actividad',        'rau_amsat_actividad_proxy' );
add_action( 'wp_ajax_nopriv_rau_amsat_actividad', 'rau_amsat_actividad_proxy' );
function rau_amsat_actividad_proxy() {
	$clave_cache = 'rau_amsat_actividad_' . RAU_THEME_VERSION;
	$cached = get_transient( $clave_cache );
	if ( false !== $cached ) { wp_send_json_success( $cached ); return; }

	$response = rau_remote_get( 'https://www.amsat.org/status/', array(
		'timeout' => 10,
		'headers' => array( 'User-Agent' => 'Mozilla/5.0 (compatible; HK4RAU/1.0)' ),
	) );
	if ( is_wp_error( $response ) ) {
		wp_send_json_error( 'No se pudo contactar a AMSAT.' );
		return;
	}
	$codigo_http = wp_remote_retrieve_response_code( $response );
	if ( 200 !== (int) $codigo_http ) {
		wp_send_json_error( 'AMSAT respondió con un error (HTTP ' . $codigo_http . ').' );
		return;
	}

	$resultados = rau_parsear_amsat_status( wp_remote_retrieve_body( $response ) );

	// Salvaguarda: si AMSAT cambia su marcado y el parser deja de reconocer el patrón,
	// esto dará muy pocos resultados — mejor fallar honestamente que mostrar datos parciales o rotos.
	if ( count( $resultados ) < 10 ) {
		wp_send_json_error( 'No se pudo interpretar la página de AMSAT (puede haber cambiado su formato).' );
		return;
	}

	$top10 = array_slice( $resultados, 0, 10, true );
	$salida = array();
	foreach ( $top10 as $nombre => $conteo ) {
		$salida[] = array( 'nombre' => $nombre, 'conteo' => $conteo );
	}

	set_transient( $clave_cache, $salida, 3 * HOUR_IN_SECONDS );
	wp_send_json_success( $salida );
}

/**
 * hams.at muestra activaciones próximas en tarjetas con varios campos (satélite, operador,
 * grid, horario, frecuencia) — una estructura más compleja que AMSAT, y no pude verificar
 * su HTML real desde este entorno. En vez de intentar extraer cada campo por separado
 * (arriesgando romperse en un campo que no vi bien), busco solo los nombres de
 * satélites que ya rastreamos, y devuelvo el texto cercano como contexto — más simple,
 * más robusto, honesto sobre lo que realmente puede confirmar.
 */
add_action( 'wp_ajax_rau_hamsat_activaciones',        'rau_hamsat_activaciones_proxy' );
add_action( 'wp_ajax_nopriv_rau_hamsat_activaciones', 'rau_hamsat_activaciones_proxy' );
function rau_hamsat_activaciones_proxy() {
	$clave_cache = 'rau_hamsat_activaciones_' . RAU_THEME_VERSION;
	$cached = get_transient( $clave_cache );
	if ( false !== $cached ) { wp_send_json_success( $cached ); return; }

	$response = rau_remote_get( 'https://hams.at/', array(
		'timeout' => 10,
		'headers' => array( 'User-Agent' => 'Mozilla/5.0 (compatible; HK4RAU/1.0)' ),
	) );
	if ( is_wp_error( $response ) ) {
		wp_send_json_error( 'No se pudo contactar a hams.at.' );
		return;
	}
	$codigo_http = wp_remote_retrieve_response_code( $response );
	if ( 200 !== (int) $codigo_http ) {
		// No es un WP_Error (la conexión se completó), pero tampoco es una respuesta válida —
		// sin este chequeo, un 403/bloqueo se malinterpretaría como "0 activaciones" en vez de un fallo real.
		wp_send_json_error( 'hams.at respondió con un error (HTTP ' . $codigo_http . ').' );
		return;
	}

	$html = wp_remote_retrieve_body( $response );
	$texto = wp_strip_all_tags( $html );
	$texto = preg_replace( '/\s+/', ' ', $texto );

	// Nuestros satélites rastreados — mismos nombres que usa el selector de la Consola.
	// AO-7 puede aparecer como "AO-07" (con cero) en algunas fuentes — se acepta cualquiera de las 2 formas.
	$satelites = array(
		'ISS'   => '/\bISS\b/i',
		'SO-50' => '/\bSO-50\b/i',
		'AO-91' => '/\bAO-91\b/i',
		'RS-44' => '/\bRS-44\b/i',
		'FO-29' => '/\bFO-29\b/i',
		'AO-7'  => '/\bAO-0?7\b/i',
	);
	$encontrados = array();

	foreach ( $satelites as $nombre => $patron_sat ) {
		// Orden real confirmado en hams.at: "in Xd HH:MM" (tiempo relativo) aparece ANTES del nombre del satélite
		$patron_sat_sin_delim = trim( $patron_sat, '/i' );
		// Se capturan días, horas y minutos por separado (no como un solo texto) porque hacen falta
		// como número para poder ordenar la lista por cercanía en el tiempo más abajo.
		$patron = '/\bin\s+(?:(\d+)d\s+)?(\d{1,2}):(\d{2})\b(.{0,10}?)' . $patron_sat_sin_delim . '/i';
		if ( preg_match_all( $patron, $texto, $m ) ) {
			$vistos_este_sat = array();
			foreach ( $m[0] as $i => $coincidencia ) {
				$limpio = trim( $coincidencia );
				// Deduplicar: hams.at a veces repite la misma activación en más de un lugar de la página
				if ( in_array( $limpio, $vistos_este_sat, true ) ) continue;
				$vistos_este_sat[] = $limpio;
				// Limitar a 2 por satélite, para que uno solo no acapare el espacio y deje sin lugar a los demás
				if ( count( $vistos_este_sat ) > 2 ) break;
				$minutos = ( (int) $m[1][ $i ] * 1440 ) + ( (int) $m[2][ $i ] * 60 ) + (int) $m[3][ $i ];
				$encontrados[] = array( 'satelite' => $nombre, 'contexto' => $limpio, 'minutos' => $minutos );
			}
		}
	}

	// Se recorre satélite por satélite, así que lo encontrado queda agrupado por satélite y no por
	// cercanía: una activación dentro de 8 horas terminaba listada debajo de otra dentro de 7 días.
	// Un panel titulado "Próximas activaciones" tiene que ir en orden cronológico.
	usort( $encontrados, function( $a, $b ) {
		return $a['minutos'] <=> $b['minutos'];
	} );

	// Si no encontramos ninguno, puede ser honestamente que no haya activaciones próximas
	// para estos satélites específicos — no es necesariamente un fallo del parser.
	set_transient( $clave_cache, $encontrados, 2 * HOUR_IN_SECONDS );
	wp_send_json_success( $encontrados );
}

/**
 * D-STAR Last Heard (dstarusers.org) — tabla HTML simple: Indicativo | Hora | Nodo | Ubicación.
 * Actividad global reciente, no filtrada a Colombia — revisé 307 indicativos de una muestra real
 * y ninguno era colombiano, así que filtrar habría dejado el widget vacío casi siempre.
 */
function rau_parsear_dstar_lastheard( $html ) {
	if ( ! $html ) return array();
	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
	libxml_clear_errors();
	$xpath = new DOMXPath( $dom );
	$filas = $xpath->query( '//tr' );

	$resultados = array();
	foreach ( $filas as $fila ) {
		$celdas = $xpath->query( './td', $fila );
		if ( $celdas->length < 4 ) continue;
		$indicativo = trim( $celdas->item( 0 )->textContent );
		$hora       = trim( $celdas->item( 1 )->textContent );
		$nodo       = trim( $celdas->item( 2 )->textContent );
		$ubicacion  = trim( $celdas->item( 3 )->textContent );
		// Validar que la primera celda realmente parezca un indicativo (letras/números, longitud razonable)
		// Exige al menos un dígito — todo indicativo real lo tiene (formato UIT), pero encabezados
		// de tabla como "Callsign" son solo letras y se colaban antes por casualidad de longitud.
		if ( ! preg_match( '/^(?=.*\d)[A-Z0-9]{3,10}(\s[A-Z])?$/i', $indicativo ) ) continue;
		$resultados[] = array( 'indicativo' => $indicativo, 'hora' => $hora, 'nodo' => $nodo, 'ubicacion' => $ubicacion );
	}
	return $resultados;
}

add_action( 'wp_ajax_rau_dstar_lastheard',        'rau_dstar_lastheard_proxy' );
add_action( 'wp_ajax_nopriv_rau_dstar_lastheard', 'rau_dstar_lastheard_proxy' );
function rau_dstar_lastheard_proxy() {
	$clave_cache = 'rau_dstar_lastheard_' . RAU_THEME_VERSION;
	$cached = get_transient( $clave_cache );
	if ( false !== $cached ) { wp_send_json_success( $cached ); return; }

	$response = rau_remote_get( 'https://dstarusers.org/lastheard.php', array(
		'timeout' => 10,
		'headers' => array( 'User-Agent' => 'Mozilla/5.0 (compatible; HK4RAU/1.0)' ),
	) );
	if ( is_wp_error( $response ) ) {
		wp_send_json_error( 'No se pudo contactar a dstarusers.org.' );
		return;
	}
	$codigo_http = wp_remote_retrieve_response_code( $response );
	if ( 200 !== (int) $codigo_http ) {
		wp_send_json_error( 'dstarusers.org respondió con un error (HTTP ' . $codigo_http . ').' );
		return;
	}

	$resultados = rau_parsear_dstar_lastheard( wp_remote_retrieve_body( $response ) );

	// Salvaguarda: si dstarusers.org cambia su marcado, esto dará muy pocos resultados —
	// mejor fallar honestamente que mostrar datos parciales o rotos.
	if ( count( $resultados ) < 10 ) {
		wp_send_json_error( 'No se pudo interpretar la página de dstarusers.org (puede haber cambiado su formato).' );
		return;
	}

	$top10 = array_slice( $resultados, 0, 8 );
	set_transient( $clave_cache, $top10, HOUR_IN_SECONDS );
	wp_send_json_success( $top10 );
}

/* =========================================================================
 * Verificador público de Diplomas — busca por número exacto.
 * Estructura definida: meta 'numero' (código del diploma), 'nombre'
 * (destinatario), 'evento' (actividad asociada), 'fecha' (emisión).
 * ========================================================================= */
add_action( 'wp_ajax_rau_diploma_lookup', 'rau_diploma_lookup' );
add_action( 'wp_ajax_nopriv_rau_diploma_lookup', 'rau_diploma_lookup' );
function rau_diploma_lookup() {
	$numero = isset( $_GET['numero'] ) ? sanitize_text_field( wp_unslash( $_GET['numero'] ) ) : '';
	if ( '' === $numero ) {
		wp_send_json_error( 'Escribe un número de diploma.' );
		return;
	}
	$q = new WP_Query( array(
		'post_type' => 'diploma', 'posts_per_page' => 1, 'post_status' => 'publish',
		'meta_query' => array( array( 'key' => 'numero', 'value' => $numero ) ),
	) );
	if ( ! $q->have_posts() ) {
		wp_send_json_error( 'No se encontró ningún diploma con ese número.' );
		return;
	}
	$q->the_post();
	$data = array(
		'titulo'  => get_the_title(),
		'nombre'  => get_post_meta( get_the_ID(), 'nombre', true ),
		'evento'  => get_post_meta( get_the_ID(), 'evento', true ),
		'fecha'   => get_post_meta( get_the_ID(), 'fecha', true ),
	);
	wp_reset_postdata();
	wp_send_json_success( $data );
}

/* =========================================================================
 * Historial de SFI / Índice K — snapshot diario vía WP-Cron.
 * Guarda hasta 60 días en wp_options (sin base de datos aparte, sin cuentas
 * de usuario). Reutiliza el mismo XML de HamQSL que ya consulta el proxy
 * solar principal.
 * ========================================================================= */
if ( ! wp_next_scheduled( 'rau_solar_snapshot_event' ) ) {
	wp_schedule_event( time(), 'daily', 'rau_solar_snapshot_event' );
}
add_action( 'rau_solar_snapshot_event', 'rau_solar_snapshot' );
/**
 * Descarga y parsea el XML de HamQSL — compartido entre el snapshot
 * diario (historial SFI/K/A) y la revisión frecuente de bandas abiertas.
 */
/**
 * Proxy de PSK Reporter — endpoint real documentado en pskreporter.info/pskdev.html.
 * Consulta quién ha reportado escuchar (o decodificar) un indicativo en los
 * últimos 30 minutos. Cacheado 2 minutos para no saturar el servicio.
 */
add_action( 'wp_ajax_rau_pskreporter', 'rau_pskreporter_proxy' );
add_action( 'wp_ajax_nopriv_rau_pskreporter', 'rau_pskreporter_proxy' );
function rau_pskreporter_proxy() {
	$indicativo = isset( $_GET['indicativo'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['indicativo'] ) ) ) : '';
	if ( '' === $indicativo || ! preg_match( '/^[A-Z0-9\/]{3,15}$/', $indicativo ) ) {
		wp_send_json_error( 'Indicativo inválido.' ); return;
	}

	$cache_key = 'rau_psk_' . md5( $indicativo );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) { wp_send_json_success( $cached ); return; }

	$url = 'https://retrieve.pskreporter.info/query?senderCallsign=' . rawurlencode( $indicativo ) . '&flowStartSeconds=-1800';
	$response = rau_remote_get( $url, array( 'timeout' => 12 ) );
	if ( is_wp_error( $response ) ) { wp_send_json_error( 'No se pudo conectar con PSK Reporter.' ); return; }

	libxml_use_internal_errors( true );
	$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );
	libxml_clear_errors();
	if ( ! $xml ) { wp_send_json_error( 'Respuesta inválida de PSK Reporter.' ); return; }

	$reportes = array();
	if ( isset( $xml->receptionReport ) ) {
		foreach ( $xml->receptionReport as $r ) {
			$reportes[] = array(
				'receptor'  => (string) $r['receiverCallsign'],
				'grid'      => (string) $r['receiverLocator'],
				'freq'      => round( (float) $r['frequency'] / 1000000, 4 ), // Hz -> MHz
				'modo'      => (string) $r['mode'],
				'snr'       => (string) $r['sNR'],
				'hace_seg'  => time() - (int) $r['flowStartSeconds'],
			);
		}
	}
	// Más recientes primero, máximo 20
	usort( $reportes, fn( $a, $b ) => $a['hace_seg'] <=> $b['hace_seg'] );
	$reportes = array_slice( $reportes, 0, 20 );

	set_transient( $cache_key, $reportes, 2 * MINUTE_IN_SECONDS );
	wp_send_json_success( $reportes );
}

function rau_fetch_solar_xml() {
	// Misma URL que rau_solar_proxy, así que comparten la marca de caída: si hamqsl.com no
	// responde, ninguno de los dos la vuelve a intentar durante unos minutos.
	$response = rau_remote_get( 'https://www.hamqsl.com/solarxml.php', array( 'timeout' => 10 ) );
	if ( is_wp_error( $response ) ) return null;
	libxml_use_internal_errors( true ); // evita que un XML malformado del servicio externo emita warnings crudos de PHP
	$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );
	libxml_clear_errors();
	return $xml ?: null;
}

function rau_solar_snapshot() {
	$xml = rau_fetch_solar_xml();
	if ( ! $xml ) return;

	$history = get_option( 'rau_solar_history', array() );
	$today   = current_time( 'Y-m-d' );
	$history[ $today ] = array(
		'sfi' => (string) ( $xml->solardata->solarflux ?? '' ),
		'k'   => (string) ( $xml->solardata->kindex ?? '' ),
		'a'   => (string) ( $xml->solardata->aindex ?? '' ),
	);
	if ( count( $history ) > 60 ) {
		$history = array_slice( $history, -60, null, true );
	}
	update_option( 'rau_solar_history', $history, false );
}
add_action( 'wp_ajax_rau_solar_history', 'rau_solar_history_endpoint' );
add_action( 'wp_ajax_nopriv_rau_solar_history', 'rau_solar_history_endpoint' );
function rau_solar_history_endpoint() {
	wp_send_json_success( get_option( 'rau_solar_history', array() ) );
}
add_action( 'switch_theme', function() { wp_clear_scheduled_hook( 'rau_solar_snapshot_event' ); wp_clear_scheduled_hook( 'rau_bandalert_check_event' ); } );

/* Revisión de bandas abiertas cada 15 minutos, para no perderse aperturas cortas */
add_filter( 'cron_schedules', function( $schedules ) {
	$schedules['rau_quarter_hour'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Cada 15 minutos (RAU)' );
	return $schedules;
} );
if ( ! wp_next_scheduled( 'rau_bandalert_check_event' ) ) {
	wp_schedule_event( time(), 'rau_quarter_hour', 'rau_bandalert_check_event' );
}
add_action( 'rau_bandalert_check_event', function() {
	$xml = rau_fetch_solar_xml();
	if ( ! $xml ) return;

	if ( isset( $xml->solardata->calculatedconditions->band ) ) {
		$bands = array();
		foreach ( $xml->solardata->calculatedconditions->band as $band ) {
			$name = (string) $band['name']; $time = (string) $band['time'];
			if ( ! isset( $bands[ $name ] ) ) $bands[ $name ] = array( 'name' => $name, 'day' => '—', 'night' => '—' );
			$bands[ $name ][ $time ] = (string) $band;
		}
		rau_push_check_and_notify_bands( array_values( $bands ) );
	}

	$xray_raw = (string) ( $xml->solardata->xray ?? '' );
	rau_push_check_and_notify_flare( $xray_raw );
} );

/**
 * Fulguración solar (X-ray, escala GOES): A/B = fondo normal, C = menor,
 * M = moderada (puede degradar HF en la cara diurna de la Tierra),
 * X = fuerte (puede causar apagón de HF). Avisamos solo en M o X.
 */
function rau_push_check_and_notify_flare( $xray_raw ) {
	if ( ! preg_match( '/^([ABCMX])([\d.]+)/i', trim( $xray_raw ), $m ) ) return;
	$clase = strtoupper( $m[1] );
	if ( ! in_array( $clase, array( 'M', 'X' ), true ) ) return;

	// No repetir el aviso más de una vez cada 3 horas para la misma clase
	if ( get_transient( 'rau_push_flare_sent_' . $clase ) ) return;
	set_transient( 'rau_push_flare_sent_' . $clase, 1, 3 * HOUR_IN_SECONDS );

	$subs = get_option( 'rau_push_subscriptions', array() );
	$titulo = 'X' === $clase ? '⚠️ Fulguración solar fuerte (clase X)' : '☀️ Fulguración solar moderada (clase M)';
	$cuerpo = 'RAU detectó rayos X clase ' . $xray_raw . '. ' . ( 'X' === $clase ? 'Posible apagón de HF en la cara diurna de la Tierra.' : 'Puede degradar HF en la cara diurna.' );
	foreach ( $subs as $sub ) {
		rau_send_web_push( $sub, $titulo, $cuerpo, home_url( '/herramientas/prop/' ) );
	}
}

/* =========================================================================
 * Correo diario a socios — propagación + próximas actividades.
 *
 * Estructura de datos: en cada post del CPT 'miembro', agrega el campo
 * personalizado 'email' (Escritorio → Directorio de Miembros → editar →
 * Campos personalizados) para que ese socio reciba el correo. Sin ese
 * campo, no se le envía nada — nunca se expone en la página pública.
 *
 * IMPORTANTE: wp_mail() usa por defecto la función mail() de PHP, poco
 * confiable en hosting compartido para envíos masivos. Se recomienda
 * instalar el plugin gratuito "WP Mail SMTP" y conectarlo a la cuenta de
 * Google Workspace del dominio — así los correos salen por Gmail/Google,
 * mucho más confiable. Este código no necesita cambios si hacen eso.
 * ========================================================================= */

function rau_digest_html_body() {
	$solar = get_transient( 'rau_solar_data' );
	$sfi = $solar['sfi'] ?? '—'; $k = $solar['kindex'] ?? '—'; $a = $solar['aindex'] ?? '—'; $xray = $solar['xray'] ?? '—';
	$bands = $solar['bands'] ?? array();

	ob_start();
	?>
	<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0d1321;color:#e2e8f0;padding:24px;border-radius:16px;">
		<p style="font-family:monospace;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#1fd5f9;margin:0 0 4px;">RAU · Radioaficionados Unidos</p>
		<h1 style="font-size:22px;margin:0 0 20px;">Reporte diario — <?php echo esc_html( date_i18n( 'j \d\e F' ) ); ?></h1>

		<h2 style="font-size:15px;color:#1fd5f9;margin:0 0 10px;">☀ Propagación Solar</h2>
		<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
			<tr>
				<td style="padding:8px;background:#111827;border-radius:8px 0 0 8px;text-align:center;"><div style="font-size:20px;font-weight:bold;color:#1fd5f9;"><?php echo esc_html( $sfi ); ?></div><div style="font-size:10px;color:#94a3b8;">SFI</div></td>
				<td style="padding:8px;background:#111827;text-align:center;"><div style="font-size:20px;font-weight:bold;"><?php echo esc_html( $a ); ?></div><div style="font-size:10px;color:#94a3b8;">Índice A</div></td>
				<td style="padding:8px;background:#111827;text-align:center;"><div style="font-size:20px;font-weight:bold;"><?php echo esc_html( $k ); ?></div><div style="font-size:10px;color:#94a3b8;">Índice K</div></td>
				<td style="padding:8px;background:#111827;border-radius:0 8px 8px 0;text-align:center;"><div style="font-size:20px;font-weight:bold;"><?php echo esc_html( $xray ); ?></div><div style="font-size:10px;color:#94a3b8;">X-Ray</div></td>
			</tr>
		</table>

		<?php if ( $bands ) : ?>
		<h2 style="font-size:15px;color:#1fd5f9;margin:0 0 10px;">📡 Condiciones por banda</h2>
		<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
			<?php foreach ( $bands as $b ) : ?>
			<tr>
				<td style="padding:6px 8px;border-bottom:1px solid #1f2937;font-family:monospace;font-size:13px;"><?php echo esc_html( $b['name'] ); ?></td>
				<td style="padding:6px 8px;border-bottom:1px solid #1f2937;font-size:12px;color:#94a3b8;text-align:right;">Día: <?php echo esc_html( $b['day'] ); ?> · Noche: <?php echo esc_html( $b['night'] ); ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php endif; ?>

		<?php
		$eventos = new WP_Query( array(
			'post_type' => 'evento', 'posts_per_page' => 3, 'post_status' => 'publish',
			'meta_key' => 'start', 'orderby' => 'meta_value', 'order' => 'ASC',
			'meta_query' => array( array( 'key' => 'start', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ),
		) );
		if ( $eventos->have_posts() ) : ?>
		<h2 style="font-size:15px;color:#1fd5f9;margin:0 0 10px;">📅 Próximas actividades</h2>
		<?php while ( $eventos->have_posts() ) : $eventos->the_post();
			$start = get_post_meta( get_the_ID(), 'start', true );
		?>
			<p style="margin:0 0 8px;font-size:13px;"><strong><?php the_title(); ?></strong><br><span style="color:#94a3b8;"><?php echo esc_html( $start ); ?></span></p>
		<?php endwhile; wp_reset_postdata(); endif; ?>

		<p style="margin-top:24px;"><a href="<?php echo esc_url( home_url( '/herramientas/dashboard/' ) ); ?>" style="color:#1fd5f9;">Ver el Dashboard en Vivo →</a></p>
		<p style="margin-top:20px;font-size:11px;color:#64748b;">Recibes esto porque tu correo está registrado como socio de RAU. <a href="{{unsub_link}}" style="color:#64748b;">Darme de baja de este correo</a>.</p>
	</div>
	<?php
	return ob_get_clean();
}

function rau_digest_unsub_token( $member_id, $email ) {
	return substr( md5( $member_id . $email . wp_salt() ), 0, 16 );
}

add_action( 'init', function() {
	if ( ! isset( $_GET['rau_unsub'], $_GET['id'], $_GET['t'] ) ) return;
	$member_id = absint( $_GET['id'] );
	$email     = get_post_meta( $member_id, 'email', true );
	if ( $email && hash_equals( rau_digest_unsub_token( $member_id, $email ), sanitize_text_field( $_GET['t'] ) ) ) {
		update_post_meta( $member_id, 'digest_optout', '1' );
		wp_die( 'Listo, ya no recibirás el correo diario de RAU. Si fue un error, contáctanos en info@hk4rau.com para reactivarlo.', 'Baja confirmada', array( 'response' => 200 ) );
	}
} );

function rau_send_digest() {
	$body_template = rau_digest_html_body();

	$q = new WP_Query( array( 'post_type' => 'miembro', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
	$destinatarios = array();
	while ( $q->have_posts() ) {
		$q->the_post();
		$id    = get_the_ID();
		$email = get_post_meta( $id, 'email', true );
		if ( $email && is_email( $email ) && '1' !== get_post_meta( $id, 'digest_optout', true ) ) {
			$unsub = add_query_arg( array( 'rau_unsub' => 1, 'id' => $id, 't' => rau_digest_unsub_token( $id, $email ) ), home_url( '/' ) );
			$destinatarios[] = array( 'email' => $email, 'unsub' => $unsub );
		}
	}
	wp_reset_postdata();
	if ( ! $destinatarios ) return;

	add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );
	foreach ( $destinatarios as $d ) {
		$body = str_replace( '{{unsub_link}}', esc_url( $d['unsub'] ), $body_template );
		wp_mail( $d['email'], 'RAU · Reporte diario de propagación', $body );
	}
}
add_action( 'rau_daily_digest_event', 'rau_send_digest' );

if ( ! wp_next_scheduled( 'rau_daily_digest_event' ) ) {
	// Primer envío mañana a las 06:00 hora Colombia (11:00 UTC), luego cada 24h.
	$manana_6am_utc = strtotime( 'tomorrow 11:00 UTC' );
	wp_schedule_event( $manana_6am_utc, 'daily', 'rau_daily_digest_event' );
}
add_action( 'switch_theme', function() { wp_clear_scheduled_hook( 'rau_daily_digest_event' ); } );

// Envío de prueba manual: visita /wp-admin/admin-post.php?action=rau_digest_test estando logueado como admin.
add_action( 'admin_post_rau_digest_test', function() {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado.' );
	$user = wp_get_current_user();
	add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );
	$body = str_replace( '{{unsub_link}}', esc_url( home_url( '/' ) ), rau_digest_html_body() );
	wp_mail( $user->user_email, 'RAU · Reporte diario de propagación (PRUEBA)', $body );
	wp_safe_redirect( wp_get_referer() ?: admin_url() );
	exit;
} );

/**
 * Interpreta un valor de frecuencia/offset guardado en cualquiera de los
 * dos formatos que existen hoy en los datos ("146.675 MHz", "146.760",
 * "-600 kHz", "-0.600") y lo normaliza siempre a MHz como número.
 * Devuelve null si no se pudo interpretar nada.
 */
function rau_parse_freq_mhz( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) return null;
	if ( ! preg_match( '/-?[\d.]+/', $raw, $m ) ) return null;
	$num = (float) $m[0];
	if ( false !== stripos( $raw, 'khz' ) ) {
		$num = $num / 1000; // kHz → MHz
	}
	return $num;
}

/**
 * Exporta el directorio de repetidores en formato CHIRP CSV real
 * (verificado contra la documentación oficial de CHIRP, no inventado).
 */
add_action( 'admin_post_rau_export_chirp', 'rau_export_chirp' );
add_action( 'admin_post_nopriv_rau_export_chirp', 'rau_export_chirp' );
function rau_export_chirp() {
	$reps = rau_get_repeaters_data();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="repetidores-rau-chirp.csv"' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Location', 'Name', 'Frequency', 'Duplex', 'Offset', 'Tone', 'rToneFreq', 'cToneFreq', 'DtcsCode', 'DtcsPolarity', 'Mode', 'TStep', 'Skip', 'Comment', 'URCALL', 'RPT1CALL', 'RPT2CALL', 'DVCODE' ) );

	$i = 1;
	foreach ( $reps as $r ) {
		$freq = rau_parse_freq_mhz( $r['freq'] );
		if ( null === $freq ) continue; // sin frecuencia válida, no se puede programar

		$offset_raw = rau_parse_freq_mhz( $r['offset'] );
		$offset_abs = null !== $offset_raw ? abs( $offset_raw ) : 0;
		$duplex     = ( $offset_raw && 0 != $offset_raw ) ? ( $offset_raw < 0 ? '-' : '+' ) : '';

		$tone_hz   = preg_replace( '/[^\d.]/', '', (string) $r['tone'] );
		$tone_mode = '' !== $tone_hz ? 'Tone' : '';

		$nombre = trim( $r['name'] . ( $r['callsign'] ? ' ' . $r['callsign'] : '' ) );

		fputcsv( $out, array(
			$i++,
			mb_substr( $nombre, 0, 16 ), // la mayoría de radios solo muestran 16 caracteres
			number_format( $freq, 6, '.', '' ),
			$duplex,
			number_format( $offset_abs, 6, '.', '' ),
			$tone_mode,
			'' !== $tone_hz ? $tone_hz : '88.5',
			'' !== $tone_hz ? $tone_hz : '88.5',
			'023',
			'NN',
			$r['mode'] ?: 'FM',
			'5.00',
			'',
			$r['location'],
			'', '', '', '',
		) );
	}
	fclose( $out );
	exit;
}

/* =========================================================================
 * API de solo lectura — /wp-json/rau/v1/
 * Pública, sin autenticación (los mismos datos ya son visibles en el
 * sitio). Reutiliza rau_get_repeaters_data(), la fuente única — la API
 * nunca inventa ni duplica datos que ya existen en otro lugar.
 * ========================================================================= */
add_action( 'rest_api_init', function() {

	register_rest_route( 'rau/v1', '/repetidores', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function() {
			$reps = rau_get_repeaters_data();
			$out = array_map( function( $r ) {
				return array(
					'nombre'     => $r['name'],
					'indicativo' => $r['callsign'],
					'frecuencia' => $r['freq'],
					'offset'     => $r['offset'],
					'tono'       => $r['tone'],
					'modo'       => $r['mode'],
					'ubicacion'  => $r['location'],
					'estado'     => $r['status'],
					'lat'        => $r['lat'],
					'lng'        => $r['lng'],
					'url'        => $r['url'],
				);
			}, $reps );
			return new WP_REST_Response( array(
				'fuente'      => 'HK4RAU — Radioaficionados Unidos',
				'actualizado' => current_time( 'c' ),
				'total'       => count( $out ),
				'repetidores' => $out,
			), 200 );
		},
	) );

	register_rest_route( 'rau/v1', '/eventos', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function( $request ) {
			// Mismo fallo que tenía la portada: ordenado por 'start' ascendente y sin filtro de
			// fecha, un endpoint llamado /eventos devolvía los 50 eventos MÁS ANTIGUOS. Ahora
			// entrega los próximos por defecto, que es lo que espera cualquiera que lo consuma.
			// Con ?todos=1 se recupera el comportamiento anterior, por si alguien lo usaba
			// para construir un histórico.
			$args = array(
				'post_type' => 'evento', 'posts_per_page' => 50, 'post_status' => 'publish',
				'meta_key' => 'start', 'orderby' => 'meta_value', 'order' => 'ASC',
			);
			if ( ! $request->get_param( 'todos' ) ) {
				$args['meta_query'] = array( array( 'key' => 'start', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) );
			}
			$q = new WP_Query( $args );
			$out = array();
			while ( $q->have_posts() ) {
				$q->the_post();
				$out[] = array(
					'nombre'    => get_the_title(),
					'inicio'    => get_post_meta( get_the_ID(), 'start', true ),
					'ubicacion' => get_post_meta( get_the_ID(), 'location', true ),
					'url'       => get_permalink(),
				);
			}
			wp_reset_postdata();
			return new WP_REST_Response( array(
				'fuente'      => 'HK4RAU — Radioaficionados Unidos',
				'actualizado' => current_time( 'c' ),
				'total'       => count( $out ),
				'eventos'     => $out,
			), 200 );
		},
	) );
} );

/* =========================================================================
 * NOTIFICACIONES PUSH REALES (Web Push, RFC 8291) — llegan aunque el
 * sitio no esté abierto en ninguna pestaña. Sin servicio de terceros de
 * pago: se habla directo con el endpoint de push de cada navegador.
 *
 * ⚠️ Esta es la pieza de todo el proyecto que NO se pudo probar contra
 * un navegador real desde este entorno — la criptografía (ECDH + HKDF +
 * AES-128-GCM) se verificó con una prueba de round-trip (cifrar y
 * descifrar con una implementación de referencia del estándar, ver abajo
 * de este bloque), pero eso no reemplaza una prueba real. Se recomienda
 * activar una suscripción de prueba y confirmar que llega la notificación
 * antes de depender de esto para algo importante.
 * ========================================================================= */

function rau_b64url_encode( $data ) {
	return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}
function rau_b64url_decode( $data ) {
	$data = strtr( $data, '-_', '+/' );
	$pad  = strlen( $data ) % 4;
	if ( $pad ) $data .= str_repeat( '=', 4 - $pad );
	return base64_decode( $data );
}

/**
 * Genera (una sola vez) el par de claves VAPID que identifican a este
 * servidor ante los servicios de push. Se guardan en wp_options.
 */
function rau_get_vapid_keys() {
	$keys = get_option( 'rau_vapid_keys' );
	if ( $keys && ! empty( $keys['public'] ) && ! empty( $keys['private_pem'] ) ) return $keys;

	$res = openssl_pkey_new( array( 'curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC ) );
	if ( ! $res ) return null;
	$details = openssl_pkey_get_details( $res );
	$x = $details['ec']['x']; $y = $details['ec']['y'];
	$public_raw = "\x04" . str_pad( $x, 32, "\x00", STR_PAD_LEFT ) . str_pad( $y, 32, "\x00", STR_PAD_LEFT );

	openssl_pkey_export( $res, $private_pem );

	$keys = array(
		'public'      => rau_b64url_encode( $public_raw ),
		'private_pem' => $private_pem,
	);
	update_option( 'rau_vapid_keys', $keys, false );
	return $keys;
}

/**
 * Construye una clave pública EC en formato PEM a partir de los 65 bytes
 * crudos (0x04 || X || Y) que entrega el navegador — necesario porque
 * OpenSSL no acepta el punto crudo directamente, solo estructuras DER.
 */
function rau_ec_raw_to_pem( $raw_point ) {
	// Cabecera DER fija para "EC PUBLIC KEY, curva prime256v1" — es constante,
	// solo cambian los 65 bytes del punto que se agregan al final.
	$der_header = hex2bin( '3059301306072a8648ce3d020106082a8648ce3d030107034200' );
	$der = $der_header . $raw_point;
	return "-----BEGIN PUBLIC KEY-----\n" . chunk_split( base64_encode( $der ), 64, "\n" ) . "-----END PUBLIC KEY-----\n";
}

/**
 * HKDF manual (RFC 5869), como lo pide RFC 8291 paso a paso — no se usa
 * hash_hkdf() de PHP porque necesitamos los pasos de Extract y Expand
 * por separado, con "info" distinto en cada etapa.
 */
function rau_hkdf_extract( $salt, $ikm ) {
	return hash_hmac( 'sha256', $ikm, $salt, true );
}
function rau_hkdf_expand( $prk, $info, $length ) {
	return substr( hash_hmac( 'sha256', $info . chr( 1 ), $prk, true ), 0, $length );
}

/**
 * Extrae (r, s) de una firma ECDSA en formato DER y las concatena en
 * crudo de 32+32 bytes — formato que exige JWS ES256, distinto del que
 * entrega openssl_sign() por defecto.
 */
function rau_der_signature_to_raw( $der ) {
	$offset = 2; // salta SEQUENCE tag + length
	$parts  = array();
	for ( $i = 0; $i < 2; $i++ ) {
		$offset++; // salta INTEGER tag (0x02)
		$len = ord( $der[ $offset ] ); $offset++;
		$val = substr( $der, $offset, $len ); $offset += $len;
		$val = ltrim( $val, "\x00" ); // quita el 0x00 de padding si el bit alto estaba prendido
		$parts[] = str_pad( $val, 32, "\x00", STR_PAD_LEFT );
	}
	return $parts[0] . $parts[1];
}

/**
 * Genera el JWT de autenticación VAPID para un endpoint de push específico.
 */
function rau_build_vapid_jwt( $endpoint, $vapid_private_pem ) {
	$parsed = wp_parse_url( $endpoint );
	$aud    = $parsed['scheme'] . '://' . $parsed['host'];

	$header = rau_b64url_encode( wp_json_encode( array( 'typ' => 'JWT', 'alg' => 'ES256' ) ) );
	$claims = rau_b64url_encode( wp_json_encode( array(
		'aud' => $aud,
		'exp' => time() + 12 * HOUR_IN_SECONDS,
		'sub' => 'mailto:info@hk4rau.com',
	) ) );
	$signing_input = $header . '.' . $claims;

	$pkey = openssl_pkey_get_private( $vapid_private_pem );
	openssl_sign( $signing_input, $der_signature, $pkey, OPENSSL_ALGO_SHA256 );
	$raw_signature = rau_der_signature_to_raw( $der_signature );

	return $signing_input . '.' . rau_b64url_encode( $raw_signature );
}

/**
 * Cifra el payload según RFC 8291 (aes128gcm) para un suscriptor específico.
 * Devuelve el cuerpo binario completo listo para enviar (cabecera + cifrado).
 */
function rau_encrypt_push_payload( $payload, $p256dh_b64url, $auth_b64url ) {
	$ua_public_raw = rau_b64url_decode( $p256dh_b64url );
	$auth_secret   = rau_b64url_decode( $auth_b64url );

	// Par de claves efímero del servidor, uno nuevo por cada mensaje.
	$as_res = openssl_pkey_new( array( 'curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC ) );
	$as_details = openssl_pkey_get_details( $as_res );
	$as_public_raw = "\x04" . str_pad( $as_details['ec']['x'], 32, "\x00", STR_PAD_LEFT ) . str_pad( $as_details['ec']['y'], 32, "\x00", STR_PAD_LEFT );

	$ua_public_pem = rau_ec_raw_to_pem( $ua_public_raw );
	$ecdh_secret   = openssl_pkey_derive( openssl_pkey_get_public( $ua_public_pem ), $as_res, 32 );

	// Paso 1 (RFC 8291): combinar ECDH + auth_secret
	$prk_key = rau_hkdf_extract( $auth_secret, $ecdh_secret );
	$key_info = "WebPush: info\x00" . $ua_public_raw . $as_public_raw;
	$ikm = rau_hkdf_expand( $prk_key, $key_info, 32 );

	// Paso 2 (RFC 8188): derivar CEK y nonce a partir de un salt aleatorio
	$salt = random_bytes( 16 );
	$prk  = rau_hkdf_extract( $salt, $ikm );
	$cek   = rau_hkdf_expand( $prk, "Content-Encoding: aes128gcm\x00", 16 );
	$nonce = rau_hkdf_expand( $prk, "Content-Encoding: nonce\x00", 12 );

	// Un solo registro (obligatorio por spec): payload + byte delimitador 0x02
	$plaintext = $payload . "\x02";
	$ciphertext = openssl_encrypt( $plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag );
	$ciphertext_con_tag = $ciphertext . $tag;

	// Cabecera del formato aes128gcm (RFC 8188): salt(16) + rs(4) + idlen(1) + keyid(65)
	$header = $salt . pack( 'N', 4096 ) . chr( strlen( $as_public_raw ) ) . $as_public_raw;

	return $header . $ciphertext_con_tag;
}

/**
 * Envía una notificación push real a una suscripción guardada.
 * $subscription = ['endpoint'=>..., 'keys'=>['p256dh'=>..., 'auth'=>...]]
 */
function rau_send_web_push( $subscription, $title, $body, $url = '' ) {
	$vapid = rau_get_vapid_keys();
	if ( ! $vapid ) return false;

	$payload = wp_json_encode( array( 'title' => $title, 'body' => $body, 'url' => $url ?: home_url( '/' ) ) );
	$cuerpo_cifrado = rau_encrypt_push_payload( $payload, $subscription['keys']['p256dh'], $subscription['keys']['auth'] );
	$jwt = rau_build_vapid_jwt( $subscription['endpoint'], $vapid['private_pem'] );

	$response = wp_remote_post( $subscription['endpoint'], array(
		'timeout' => 10,
		'headers' => array(
			'Content-Type'     => 'application/octet-stream',
			'Content-Encoding' => 'aes128gcm',
			'TTL'              => '86400',
			'Authorization'    => 'vapid t=' . $jwt . ', k=' . $vapid['public'],
		),
		'body' => $cuerpo_cifrado,
	) );

	return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 300;
}

/** Guarda una suscripción nueva (llamado desde el navegador vía AJAX). */
add_action( 'wp_ajax_rau_push_subscribe', 'rau_push_subscribe' );
add_action( 'wp_ajax_nopriv_rau_push_subscribe', 'rau_push_subscribe' );
function rau_push_subscribe() {
	// Límite por IP: 1 intento cada 30 segundos — mismo patrón ya probado en checkin/libro_visitas
	$ip_hash = md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) );
	if ( get_transient( 'rau_push_' . $ip_hash ) ) { wp_send_json_error( 'Espera un momento.' ); return; }
	set_transient( 'rau_push_' . $ip_hash, 1, 30 );

	$body = json_decode( file_get_contents( 'php://input' ), true );
	if ( empty( $body['endpoint'] ) || empty( $body['keys']['p256dh'] ) || empty( $body['keys']['auth'] ) ) {
		wp_send_json_error( 'Suscripción inválida.' );
	}

	// El endpoint se guarda y MÁS TARDE el servidor le envía peticiones desde rau_push_send().
	// Guardarlo tal cual llega significa que cualquiera puede hacer que el servidor consulte la
	// URL que quiera, incluidas direcciones de la red interna del hosting: un SSRF de manual.
	// Solo se aceptan URLs https de los servicios push reales de los navegadores.
	$endpoint = esc_url_raw( trim( (string) $body['endpoint'] ) );
	$host     = wp_parse_url( $endpoint, PHP_URL_HOST );
	$esquema  = wp_parse_url( $endpoint, PHP_URL_SCHEME );
	$permitidos = array(
		'fcm.googleapis.com',            // Chrome, Edge, Opera
		'updates.push.services.mozilla.com', // Firefox
		'push.services.mozilla.com',
		'wns2-*.notify.windows.com',     // Windows (subdominio variable)
		'web.push.apple.com',            // Safari
	);
	$host_ok = false;
	foreach ( $permitidos as $patron ) {
		if ( fnmatch( $patron, (string) $host ) ) { $host_ok = true; break; }
	}
	if ( 'https' !== $esquema || ! $host_ok || strlen( $endpoint ) > 500 ) {
		wp_send_json_error( 'Endpoint de suscripción no reconocido.' );
		return;
	}

	// Las claves son base64url de longitud conocida; cualquier otra cosa se rechaza en vez de
	// almacenarse. p256dh son 65 bytes y auth 16, que en base64url dan 87 y 22 caracteres.
	$p256dh = trim( (string) $body['keys']['p256dh'] );
	$auth   = trim( (string) $body['keys']['auth'] );
	if ( ! preg_match( '/^[A-Za-z0-9_-]{80,100}$/', $p256dh ) || ! preg_match( '/^[A-Za-z0-9_-]{16,32}$/', $auth ) ) {
		wp_send_json_error( 'Claves de suscripción inválidas.' );
		return;
	}

	$subs = get_option( 'rau_push_subscriptions', array() );
	// Tope máximo — evita que un abuso haga crecer la opción sin límite
	if ( count( $subs ) >= 5000 ) { wp_send_json_error( 'Límite alcanzado.' ); return; }
	// Evita duplicados por el mismo endpoint. El isset protege de entradas antiguas malformadas,
	// que con el operador directo lanzaban un aviso de PHP.
	$subs = array_filter( $subs, function( $s ) use ( $endpoint ) {
		return ! isset( $s['endpoint'] ) || $s['endpoint'] !== $endpoint;
	} );
	$subs[] = array( 'endpoint' => $endpoint, 'keys' => array( 'p256dh' => $p256dh, 'auth' => $auth ) );
	update_option( 'rau_push_subscriptions', array_values( $subs ), false );
	wp_send_json_success();
}

/** Expone la clave pública VAPID al navegador (necesaria para suscribirse). */
add_action( 'wp_ajax_rau_vapid_public_key', 'rau_vapid_public_key_endpoint' );
add_action( 'wp_ajax_nopriv_rau_vapid_public_key', 'rau_vapid_public_key_endpoint' );
function rau_vapid_public_key_endpoint() {
	$vapid = rau_get_vapid_keys();
	wp_send_json_success( array( 'key' => $vapid ? $vapid['public'] : '' ) );
}

/**
 * Envía la notificación real de "banda abierta" a todos los suscritos.
 * Se llama desde el mismo snapshot diario de propagación — reutiliza el
 * dato de bandas que ya se descarga ahí, no hace una consulta aparte.
 */
function rau_push_check_and_notify_bands( $bands ) {
	$hay_alerta = false;
	foreach ( $bands as $b ) {
		if ( preg_match( '/good/i', $b['day'] ?? '' ) && preg_match( '/12m-10m|17m-15m/', $b['name'] ?? '' ) ) {
			$hay_alerta = true; break;
		}
	}
	if ( ! $hay_alerta ) return;

	// No repetir el aviso más de una vez cada 3 horas, aunque el cron corra seguido
	if ( get_transient( 'rau_push_bandalert_sent' ) ) return;
	set_transient( 'rau_push_bandalert_sent', 1, 3 * HOUR_IN_SECONDS );

	$subs = get_option( 'rau_push_subscriptions', array() );
	foreach ( $subs as $sub ) {
		rau_send_web_push( $sub, '¡Banda alta abierta! 📡', 'RAU detectó buena condición en 15-10m ahora mismo.', home_url( '/herramientas/bandalert/' ) );
	}
}

/**
 * Envía una notificación de PRUEBA a todos los dispositivos suscritos —
 * para confirmar que el sistema de push realmente entrega, sin tener
 * que esperar a una apertura de banda real. Solo accesible para admins.
 * Visita: /wp-admin/admin-post.php?action=rau_push_test
 */
add_action( 'admin_post_rau_push_test', function() {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado.' );

	$subs = get_option( 'rau_push_subscriptions', array() );
	if ( ! $subs ) {
		wp_die( 'Todavía no hay ninguna suscripción activa. Ve a Herramientas → Alertas de Banda → "Avisarme…" primero, en el navegador donde quieres recibir la prueba.', 'Sin suscripciones', array( 'response' => 200 ) );
	}

	$exitos = 0; $fallos = 0;
	foreach ( $subs as $sub ) {
		$ok = rau_send_web_push( $sub, '🔔 Notificación de prueba — RAU', 'Si ves esto, el sistema de notificaciones push funciona correctamente.', home_url( '/' ) );
		if ( $ok ) $exitos++; else $fallos++;
	}

	wp_die(
		"Enviado a {$exitos} dispositivo(s) con éxito, {$fallos} con error.<br><br>Si no te llegó nada en los próximos segundos (revisa también que las notificaciones del sistema operativo estén permitidas para el navegador), avísame para revisar el detalle.",
		'Prueba de notificación enviada',
		array( 'response' => 200 )
	);
} );

/**
 * Registro de preguntas del bot de FAQ — para poder ver qué pregunta la
 * gente de verdad y mejorar las respuestas con datos reales, no adivinando.
 * Guarda solo la pregunta y si se encontró coincidencia, nada de datos
 * personales ni IP. Se queda con las últimas 200 para no crecer sin límite.
 */
add_action( 'wp_ajax_rau_bot_log', 'rau_bot_log_endpoint' );
add_action( 'wp_ajax_nopriv_rau_bot_log', 'rau_bot_log_endpoint' );
function rau_bot_log_endpoint() {
	// Límite por IP — mismo patrón que checkin, libro de visitas, contacto y push. Este era el
	// único endpoint que escribe en la base sin ninguno: al ser público y sin sesión, permitía
	// escrituras ilimitadas y, como el registro solo guarda las últimas 200 entradas, bastaba con
	// repetirlo para desplazar todas las preguntas reales de los socios.
	$ip_hash = md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) );
	if ( get_transient( 'rau_botlog_' . $ip_hash ) ) { wp_send_json_error( 'Espera un momento.' ); return; }

	$pregunta   = sanitize_text_field( wp_unslash( $_POST['pregunta'] ?? '' ) );
	$encontrada = ! empty( $_POST['encontrada'] );
	if ( '' === $pregunta ) { wp_send_json_error(); return; }

	set_transient( 'rau_botlog_' . $ip_hash, 1, 10 );

	$log = get_option( 'rau_bot_log', array() );
	$log[] = array( 'pregunta' => mb_substr( $pregunta, 0, 200 ), 'encontrada' => $encontrada, 'fecha' => current_time( 'Y-m-d H:i' ) );
	if ( count( $log ) > 200 ) $log = array_slice( $log, -200 );
	update_option( 'rau_bot_log', $log, false );
	wp_send_json_success();
}

/** Ver el registro: /wp-admin/admin-post.php?action=rau_bot_log_ver (solo admins) */
add_action( 'admin_post_rau_bot_log_ver', function() {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado.' );
	$log = array_reverse( get_option( 'rau_bot_log', array() ) );
	$sin_respuesta = array_filter( $log, fn( $e ) => ! $e['encontrada'] );

	echo '<h1>Registro del Bot de FAQ (' . count( $log ) . ' preguntas)</h1>';
	echo '<h2>Sin respuesta encontrada (' . count( $sin_respuesta ) . ') — prioriza agregar estas al FAQ</h2><ul>';
	foreach ( $sin_respuesta as $e ) echo '<li>[' . esc_html( $e['fecha'] ) . '] ' . esc_html( $e['pregunta'] ) . '</li>';
	echo '</ul><h2>Todas</h2><ul>';
	foreach ( $log as $e ) echo '<li>[' . esc_html( $e['fecha'] ) . '] ' . ( $e['encontrada'] ? '✅' : '❌' ) . ' ' . esc_html( $e['pregunta'] ) . '</li>';
	echo '</ul>';
	exit;
} );

/* =========================================================================
 * COMPARTIR QTH EN VIVO — PRIVACY FIRST
 *
 * Principios de privacidad aplicados:
 * - Nada se comparte hasta que la persona toca "activar" explícitamente
 * - Se expone el grid square (precisión ~4km), NUNCA la coordenada exacta
 * - Expira solo a las 4 horas, sin que nadie tenga que acordarse de apagarlo
 * - Quien lo activó puede detenerlo en cualquier momento desde su navegador
 * - El link es un token aleatorio no adivinable, no un ID secuencial
 * ========================================================================= */

add_action( 'init', function() {
	add_rewrite_rule( '^mi-qth/([a-z0-9]+)/?$', 'index.php?rau_qth_token=$matches[1]', 'top' );
} );
add_filter( 'query_vars', function( $vars ) { $vars[] = 'rau_qth_token'; return $vars; } );

add_action( 'template_redirect', function() {
	$token = get_query_var( 'rau_qth_token' );
	if ( ! $token ) return;

	$datos = get_transient( 'rau_qth_' . $token );
	get_header();
	?>
	<div class="mx-auto max-w-md px-4 py-16 text-center sm:px-6">
		<p class="mb-2 font-mono text-[11px] uppercase tracking-[0.25em] text-primary">Compartir QTH en vivo</p>
		<?php if ( ! $datos ) : ?>
			<h1 class="mb-3 font-display text-2xl font-bold">Este enlace ya no está activo</h1>
			<p class="text-muted-foreground">Quien lo compartió lo desactivó, o expiró (los enlaces duran máximo 4 horas).</p>
		<?php else : ?>
			<h1 class="mb-1 font-display text-3xl font-bold" id="qth-grid"><?php echo esc_html( $datos['grid'] ); ?></h1>
			<p class="mb-6 text-sm text-muted-foreground" id="qth-actualizado">Actualizado hace <span id="qth-hace"><?php echo esc_html( human_time_diff( $datos['ts'] ) ); ?></span></p>
			<div class="rounded-2xl border border-border/70 bg-card p-6">
				<p class="text-sm text-muted-foreground">Ubicación aproximada compartida en vivo — precisión de grid square (~4 km), no la posición exacta.</p>
			</div>
			<script>
			(function(){
				var token='<?php echo esc_js( $token ); ?>';
				function actualizar(){
					fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_qth_get&token='+token, {cache:'no-store'})
						.then(function(r){ return r.json(); })
						.then(function(json){
							if(!json.success){ document.body.innerHTML='<p style="text-align:center;padding:4rem 1rem;">Este enlace ya expiró.</p>'; return; }
							document.getElementById('qth-grid').textContent=json.data.grid;
							var seg=Math.floor((Date.now()/1000)-json.data.ts);
							var texto = seg<60 ? 'hace unos segundos' : Math.floor(seg/60)+' min';
							document.getElementById('qth-hace').textContent=texto;
						}).catch(function(){});
				}
				setInterval(actualizar, 30000);
			})();
			</script>
		<?php endif; ?>
	</div>
	<?php
	get_footer();
	exit;
} );

function rau_latlng_to_grid( $lat, $lng ) {
	$lat += 90; $lng += 180;
	$A = range( 'A', 'Z' );
	$campo    = $A[ (int) floor( $lng / 20 ) ] . $A[ (int) floor( $lat / 10 ) ];
	$cuadro   = (int) floor( fmod( $lng, 20 ) / 2 ) . (int) floor( fmod( $lat, 10 ) );
	$sub_lng  = fmod( $lng, 2 ) * 12;
	$sub_lat  = fmod( $lat, 1 ) * 24;
	$subcuadro = strtolower( $A[ (int) floor( $sub_lng ) ] . $A[ (int) floor( $sub_lat ) ] );
	return $campo . $cuadro . $subcuadro;
}

/** Inicia o actualiza el compartir — el navegador que comparte llama esto cada vez que se mueve */
add_action( 'wp_ajax_rau_qth_share', 'rau_qth_share_endpoint' );
add_action( 'wp_ajax_nopriv_rau_qth_share', 'rau_qth_share_endpoint' );
function rau_qth_share_endpoint() {
	$lat = isset( $_POST['lat'] ) ? (float) $_POST['lat'] : null;
	$lng = isset( $_POST['lng'] ) ? (float) $_POST['lng'] : null;
	$token = isset( $_POST['token'] ) ? preg_replace( '/[^a-z0-9]/', '', $_POST['token'] ) : '';

	if ( null === $lat || null === $lng ) { wp_send_json_error(); return; }
	if ( '' === $token ) $token = substr( md5( uniqid( '', true ) ), 0, 12 );

	$datos = array( 'grid' => rau_latlng_to_grid( $lat, $lng ), 'ts' => time() );
	set_transient( 'rau_qth_' . $token, $datos, 4 * HOUR_IN_SECONDS );

	wp_send_json_success( array( 'token' => $token, 'url' => home_url( '/mi-qth/' . $token . '/' ) ) );
}

/** Detener: borra el token de inmediato, sin esperar la expiración */
add_action( 'wp_ajax_rau_qth_stop', function() {
	$token = isset( $_POST['token'] ) ? preg_replace( '/[^a-z0-9]/', '', $_POST['token'] ) : '';
	if ( $token ) delete_transient( 'rau_qth_' . $token );
	wp_send_json_success();
} );
add_action( 'wp_ajax_nopriv_rau_qth_stop', function() {
	$token = isset( $_POST['token'] ) ? preg_replace( '/[^a-z0-9]/', '', $_POST['token'] ) : '';
	if ( $token ) delete_transient( 'rau_qth_' . $token );
	wp_send_json_success();
} );

/** Consultar (público, lo usa la página del visor) */
add_action( 'wp_ajax_rau_qth_get', 'rau_qth_get_endpoint' );
add_action( 'wp_ajax_nopriv_rau_qth_get', 'rau_qth_get_endpoint' );
function rau_qth_get_endpoint() {
	$token = isset( $_GET['token'] ) ? preg_replace( '/[^a-z0-9]/', '', $_GET['token'] ) : '';
	$datos = $token ? get_transient( 'rau_qth_' . $token ) : false;
	if ( ! $datos ) { wp_send_json_error(); return; }
	wp_send_json_success( $datos );
}

/**
 * Calcula la racha de participación en la malla semanal por indicativo —
 * cuántas semanas SEGUIDAS ha hecho check-in, contando hacia atrás desde
 * la semana actual (o la anterior, si la malla de esta semana aún no
 * ha ocurrido — es martes). Reutiliza los datos que ya existen, no
 * agrega ningún campo nuevo.
 */
/**
 * Last Heard RAU — últimos check-ins reales de toda la red, no solo de
 * la malla actual. Reutiliza la misma fuente que la Racha, sin duplicar
 * ninguna consulta nueva a la base de datos.
 */
/**
 * Estadísticas de participación — check-ins por semana en los últimos 3
 * meses. Reutiliza los mismos datos de rau_checkin_rachas, sin duplicar
 * consultas.
 */
function rau_checkin_stats_semanales( $semanas = 12 ) {
	$ids = get_posts( array( 'post_type' => 'checkin', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids' ) );
	$por_semana = array();
	foreach ( $ids as $id ) {
		$fecha_str = get_post_meta( $id, 'fecha', true );
		if ( ! $fecha_str ) continue;
		$ts = strtotime( $fecha_str );
		if ( ! $ts ) continue;
		$semana = date( 'o-W', $ts );
		$por_semana[ $semana ] = ( $por_semana[ $semana ] ?? 0 ) + 1;
	}
	// Últimas N semanas, en orden cronológico, incluyendo semanas en 0
	$resultado = array();
	for ( $i = $semanas - 1; $i >= 0; $i-- ) {
		$ts_semana = strtotime( "-{$i} week" );
		$clave     = date( 'o-W', $ts_semana );
		$resultado[] = array( 'semana' => $clave, 'total' => $por_semana[ $clave ] ?? 0 );
	}
	return $resultado;
}
add_action( 'wp_ajax_rau_checkin_stats', 'rau_checkin_stats_endpoint' );
add_action( 'wp_ajax_nopriv_rau_checkin_stats', 'rau_checkin_stats_endpoint' );
function rau_checkin_stats_endpoint() {
	wp_send_json_success( rau_checkin_stats_semanales( 12 ) );
}

function rau_last_heard( $limite = 15 ) {
	$q = new WP_Query( array(
		'post_type' => 'checkin', 'posts_per_page' => $limite,
		'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish',
	) );
	$out = array();
	while ( $q->have_posts() ) {
		$q->the_post();
		$out[] = array(
			'indicativo' => get_post_meta( get_the_ID(), 'indicativo', true ),
			'nombre'     => get_post_meta( get_the_ID(), 'nombre', true ),
			'ciudad'     => get_post_meta( get_the_ID(), 'ciudad', true ),
			'hace'       => human_time_diff( get_the_time( 'U' ) ) . ' antes',
			'fecha'      => get_the_date( 'Y-m-d H:i' ),
		);
	}
	wp_reset_postdata();
	return $out;
}
add_action( 'wp_ajax_rau_last_heard', 'rau_last_heard_endpoint' );
add_action( 'wp_ajax_nopriv_rau_last_heard', 'rau_last_heard_endpoint' );
function rau_last_heard_endpoint() {
	wp_send_json_success( rau_last_heard( 15 ) );
}

/**
 * "Quién estuvo hoy" — check-ins de HOY que sí registraron grid square.
 * Los que no tienen grid simplemente no aparecen en el mapa (no se inventa
 * una ubicación que nadie dio).
 */
add_action( 'wp_ajax_rau_checkin_hoy_mapa', 'rau_checkin_hoy_mapa_endpoint' );
add_action( 'wp_ajax_nopriv_rau_checkin_hoy_mapa', 'rau_checkin_hoy_mapa_endpoint' );
function rau_checkin_hoy_mapa_endpoint() {
	$hoy = current_time( 'Y-m-d' );
	$q = new WP_Query( array(
		'post_type' => 'checkin', 'posts_per_page' => -1, 'post_status' => 'publish',
		'meta_query' => array(
			array( 'key' => 'fecha', 'value' => $hoy ),
			array( 'key' => 'grid', 'value' => '', 'compare' => '!=' ),
		),
	) );
	$out = array();
	while ( $q->have_posts() ) {
		$q->the_post();
		$out[] = array(
			'indicativo' => get_post_meta( get_the_ID(), 'indicativo', true ),
			'grid'       => get_post_meta( get_the_ID(), 'grid', true ),
			'ciudad'     => get_post_meta( get_the_ID(), 'ciudad', true ),
			'hora'       => get_post_meta( get_the_ID(), 'hora', true ),
		);
	}
	wp_reset_postdata();
	wp_send_json_success( $out );
}

function rau_checkin_rachas( $limite = 10 ) {
	$ids = get_posts( array( 'post_type' => 'checkin', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids' ) );

	$por_indicativo = array();
	foreach ( $ids as $id ) {
		$indicativo = get_post_meta( $id, 'indicativo', true );
		$fecha_str  = get_post_meta( $id, 'fecha', true ); // misma fuente que usa el resto del sistema de check-in
		if ( ! $indicativo || ! $fecha_str ) continue;
		$ts = strtotime( $fecha_str );
		if ( ! $ts ) continue;
		$semana = date( 'o-W', $ts ); // año-semana ISO, ej. "2026-35"
		$por_indicativo[ $indicativo ][ $semana ] = true;
	}

	$anio_actual   = (int) current_time( 'o' );
	$semana_actual = (int) current_time( 'W' );
	$dia_iso       = (int) current_time( 'N' ); // 1=lunes ... 7=domingo
	// La malla es martes — si hoy es lunes, la de esta semana todavía no pasó.
	if ( $dia_iso < 2 ) {
		$semana_actual--;
		if ( $semana_actual < 1 ) { $semana_actual = 52; $anio_actual--; }
	}

	$rachas = array();
	foreach ( $por_indicativo as $indicativo => $semanas ) {
		$racha = 0;
		$cursor_anio = $anio_actual; $cursor_semana = $semana_actual;
		while ( true ) {
			$clave = sprintf( '%04d-%02d', $cursor_anio, $cursor_semana );
			if ( empty( $semanas[ $clave ] ) ) break;
			$racha++;
			$cursor_semana--;
			if ( $cursor_semana < 1 ) { $cursor_semana = 52; $cursor_anio--; }
		}
		if ( $racha > 0 ) $rachas[ $indicativo ] = $racha;
	}
	arsort( $rachas );
	return array_slice( $rachas, 0, $limite, true );
}

/** Endpoint AJAX para la consola — reutiliza rau_checkin_rachas() ya existente, sin duplicar lógica */
add_action( 'wp_ajax_rau_racha_top', 'rau_racha_top_endpoint' );
add_action( 'wp_ajax_nopriv_rau_racha_top', 'rau_racha_top_endpoint' );
function rau_racha_top_endpoint() {
	$rachas = rau_checkin_rachas( 5 );
	$out    = array();
	foreach ( $rachas as $indicativo => $racha ) {
		$out[] = array( 'indicativo' => $indicativo, 'racha' => $racha );
	}
	wp_send_json_success( $out );
}

/* ── Estado del KiwiSDR HK4RAU (caché 2 min) ── */
add_action( 'wp_ajax_rau_sdr_status',        'rau_sdr_status_proxy' );
add_action( 'wp_ajax_nopriv_rau_sdr_status', 'rau_sdr_status_proxy' );
function rau_sdr_status_proxy() {
	$cached = get_transient( 'rau_sdr_status' );
	if ( $cached !== false ) { wp_send_json_success( $cached ); return; }

	$response = rau_remote_get( 'http://hk4rau.proxy.kiwisdr.com:8073/status', array(
		'timeout'    => 5,
		'user-agent' => 'Mozilla/5.0 (compatible; HK4RAU/1.0)',
	) );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		wp_send_json_error( 'offline' ); return;
	}

	$body = wp_remote_retrieve_body( $response );
	// El endpoint /status de KiwiSDR devuelve líneas "clave=valor"
	$data = array();
	foreach ( explode( "\n", $body ) as $line ) {
		$parts = explode( '=', trim( $line ), 2 );
		if ( count( $parts ) === 2 ) $data[ trim($parts[0]) ] = trim($parts[1]);
	}
	// KiwiSDR /status usa distintos nombres según versión del firmware
	$users = null;
	foreach ( array( 'users_audio', 'users', 'num_users', 'rx_users', 'nr_users' ) as $key ) {
		if ( isset( $data[$key] ) && is_numeric( $data[$key] ) ) { $users = (int) $data[$key]; break; }
	}
	$max = null;
	foreach ( array( 'users_max', 'max_users', 'rx_users_max' ) as $key ) {
		if ( isset( $data[$key] ) && is_numeric( $data[$key] ) ) { $max = (int) $data[$key]; break; }
	}
	$result = array(
		'users'    => $users,
		'max'      => $max ?? 4,
		'location' => $data['rx_location'] ?? $data['location'] ?? 'Medellín, Colombia',
	);
	set_transient( 'rau_sdr_status', $result, 2 * MINUTE_IN_SECONDS );
	wp_send_json_success( $result );
}

/* ── Proxy DX Spots — múltiples fuentes con fallback — caché 3 min ── */
add_action( 'wp_ajax_rau_dx_spots',        'rau_dx_spots_proxy' );
add_action( 'wp_ajax_nopriv_rau_dx_spots', 'rau_dx_spots_proxy' );

/**
 * Busca una clave en un elemento sin importar mayúsculas/minúsculas.
 * Las APIs de cluster cambian la capitalización de sus campos entre versiones
 * (dx_call / DXCall / DxCall) y con acceso exacto eso se traduce en cero spots
 * en silencio, indistinguible de "no hay actividad".
 */
function rau_dx_campo( $item, $claves, $defecto = '' ) {
	if ( ! is_array( $item ) ) return $defecto;
	$normalizado = array();
	foreach ( $item as $k => $v ) {
		$normalizado[ strtolower( str_replace( array( '_', '-' ), '', (string) $k ) ) ] = $v;
	}
	foreach ( (array) $claves as $clave ) {
		$buscar = strtolower( str_replace( array( '_', '-' ), '', $clave ) );
		if ( isset( $normalizado[ $buscar ] ) && $normalizado[ $buscar ] !== '' ) {
			return $normalizado[ $buscar ];
		}
	}
	return $defecto;
}

function rau_dx_spots_proxy() {
	// Diagnóstico solo para administradores: devuelve qué respondió cada fuente en vez de los spots.
	// Sin esto, un fallo se ve igual que "no hay spots" y no hay forma de saber cuál de las
	// tres fuentes falló ni por qué.
	$diagnostico = isset( $_GET['diagnostico'] ) && current_user_can( 'manage_options' );

	$clave_cache = 'rau_dx_spots_' . RAU_THEME_VERSION;
	if ( ! $diagnostico ) {
		$cached = get_transient( $clave_cache );
		if ( $cached !== false ) {
			if ( $cached === 'sin_datos' ) { wp_send_json_error( 'all_sources_failed' ); return; }
			wp_send_json_success( $cached );
			return;
		}
	}

	// Fuentes en orden de prioridad.
	// dxheat.com/dxc/spots/feed/ salió de aquí: el diagnóstico confirmó HTTP 404 — el endpoint
	// ya no existe. Nunca fue una API pública documentada (se llamaba con Referer falsificado),
	// así que no se reemplaza por otra ruta adivinada del mismo sitio.
	//
	// DXSummit por HTTP va primero porque es la única que el diagnóstico confirmó respondiendo
	// (50 elementos, 50 spots reconocidos). Su variante HTTPS y cluster.dl9gtb.de dieron timeout
	// incluso con 10 s, así que quedan detrás: si fueran antes, cada refresco de caché gastaría
	// 10 segundos esperando algo que no contesta antes de llegar a la que sí.
	//
	// Sobre el HTTP en texto plano: es una petición de servidor a servidor, no del navegador del
	// visitante, así que no genera contenido mixto ni expone nada del usuario. Lo que viaja sin
	// cifrar son spots públicos de un cluster. Se deja el HTTPS de segundo por si arreglan su TLS.
	$sources = array(
		'http://www.dxsummit.fi/api/v1/spots',
		'https://www.dxsummit.fi/api/v1/spots',
		'https://cluster.dl9gtb.de/spots?limit=50&format=json',
	);

	$args = array(
		// Antes eran 4 s y el diagnóstico devolvió cURL 28 a los 4002 ms en dos de las tres
		// fuentes: el timeout era el que cortaba, no las fuentes. Desde Colombia a un servidor
		// europeo, con handshake TLS incluido, 4 s se queda corto. Con la marca de caída ya en
		// funcionamiento, una espera larga ocurre como mucho una vez cada dos minutos.
		'timeout'    => 10,
		'headers'    => array(
			'User-Agent' => 'Mozilla/5.0 (compatible; HK4RAU/1.0; +https://hk4rau.com)',
			'Accept'     => 'application/json, text/plain, */*',
		),
	);

	$informe = array();

	foreach ( $sources as $url ) {
		// Se incluye el esquema porque hay dos entradas del mismo host (https y http) y con
		// solo el nombre la segunda pisaría el informe de la primera.
		$host = wp_parse_url( $url, PHP_URL_SCHEME ) . '://' . wp_parse_url( $url, PHP_URL_HOST );
		$response = rau_remote_get( $url, $args, 2, $diagnostico );
		if ( is_wp_error( $response ) ) {
			$informe[ $host ] = 'error de conexión: ' . $response->get_error_message();
			continue;
		}
		$codigo = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $codigo ) {
			// Un fragmento del cuerpo ayuda a distinguir un endpoint retirado de un bloqueo
			// de Cloudflare o una página de error del propio hosting.
			$pista = trim( wp_strip_all_tags( substr( wp_remote_retrieve_body( $response ), 0, 160 ) ) );
			$informe[ $host ] = 'HTTP ' . $codigo . ( $pista ? ' — ' . preg_replace( '/\s+/', ' ', $pista ) : '' );
			continue;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			$informe[ $host ] = 'la respuesta no es JSON válido (' . strlen( $body ) . ' bytes)';
			continue;
		}

		// json_decode(..., true) devuelve un array TANTO para listas como para objetos JSON, así que
		// el is_array() que había aquí antes siempre daba verdadero y la rama que buscaba $data['spots']
		// era código muerto: si una fuente envolvía los spots en un objeto, se recorría el objeto
		// exterior y no coincidía ningún campo, dando cero spots sin ningún aviso.
		$items = $data;
		if ( ! isset( $data[0] ) ) {
			foreach ( array( 'spots', 'Spots', 'data', 'results', 'items' ) as $envoltorio ) {
				if ( isset( $data[ $envoltorio ] ) && is_array( $data[ $envoltorio ] ) ) {
					$items = $data[ $envoltorio ];
					break;
				}
			}
		}

		$spots = array();
		foreach ( $items as $s ) {
			if ( ! is_array( $s ) ) continue;
			$indicativo = rau_dx_campo( $s, array( 'dx_call', 'dx_call_sign', 'dxcall', 'dx' ) );
			if ( '' === $indicativo ) continue;

			$frecuencia = (float) rau_dx_campo( $s, array( 'frequency', 'freq', 'qrg' ), 0 );
			// DXsummit entrega kHz; el resto, MHz. Se detecta por magnitud en vez de por fuente,
			// que es lo que aguanta un cambio de proveedor.
			if ( $frecuencia > 1000 ) $frecuencia = $frecuencia / 1000;

			$hora = (string) rau_dx_campo( $s, array( 'time', 'utc', 'date_time', 't' ) );
			// Puede venir como "14:32" o como fecha ISO completa
			if ( preg_match( '/(\d{2}:\d{2})/', $hora, $m_hora ) ) $hora = $m_hora[1];

			$spots[] = array(
				// Tres decimales: el frontend muestra este valor y lo copia al portapapeles para
				// sintonizar. Con un decimal, un spot en 14.024 se copiaba como "14.0" y no servía
				// para encontrar a nadie. Sin separador de miles, o 23cm saldría como "1,240.000".
				'dx'   => $indicativo,
				'freq' => number_format( $frecuencia, 3, '.', '' ),
				'f'    => $frecuencia,
				'sp'   => rau_dx_campo( $s, array( 'spotter', 'de_call_sign', 'de_call', 'sp', 'de' ) ),
				't'    => $hora,
				'info' => rau_dx_campo( $s, array( 'comment', 'info', 'remarks' ) ),
			);
		}

		$informe[ $host ] = count( $items ) . ' elementos recibidos, ' . count( $spots ) . ' spots reconocidos';
		if ( ! empty( $items ) && empty( $spots ) ) {
			// Muestra las claves del primer elemento para ver qué cambió del otro lado
			$primero = reset( $items );
			if ( is_array( $primero ) ) {
				$informe[ $host ] .= ' — campos: ' . implode( ', ', array_slice( array_keys( $primero ), 0, 12 ) );
			}
		}

		// El recorte se hace aquí porque a DXSummit se le pide sin parámetros de cantidad
		$spots = array_slice( $spots, 0, 50 );

		if ( ! empty( $spots ) && ! $diagnostico ) {
			set_transient( $clave_cache, $spots, 3 * MINUTE_IN_SECONDS );
			wp_send_json_success( $spots );
			return;
		}
	}

	if ( $diagnostico ) {
		wp_send_json_success( array( 'diagnostico' => $informe ) );
		return;
	}

	// Caché negativa: sin esto, cada visita que cargaba la página volvía a golpear las tres fuentes
	// con 4 s de espera cada una — hasta 12 s de admin-ajax bloqueado por visitante, una y otra vez.
	set_transient( $clave_cache, 'sin_datos', 2 * MINUTE_IN_SECONDS );
	wp_send_json_error( 'all_sources_failed' );
}

/* ── Proxy POTA activaciones HK — caché 5 min ── */
add_action( 'wp_ajax_rau_pota_spots',        'rau_pota_spots_proxy' );
add_action( 'wp_ajax_nopriv_rau_pota_spots', 'rau_pota_spots_proxy' );
function rau_pota_spots_proxy() {
	$cached = get_transient( 'rau_pota_spots' );
	if ( $cached !== false ) { wp_send_json_success( $cached ); return; }

	$response = rau_remote_get( 'https://api.pota.app/spot/activator', array(
		'timeout'    => 8,
		'user-agent' => 'Mozilla/5.0 (compatible; HK4RAU/1.0)',
	) );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		wp_send_json_error( 'fetch_failed' ); return;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! $data ) { wp_send_json_error( 'parse_failed' ); return; }

	set_transient( 'rau_pota_spots', $data, 5 * MINUTE_IN_SECONDS );
	wp_send_json_success( $data );
}
