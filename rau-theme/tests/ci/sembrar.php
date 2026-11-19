<?php
/**
 * Datos mínimos para las pruebas de integración: instala WordPress, activa el tema y crea las páginas,
 * repetidoras y ajustes que usan las pruebas. Se puede correr varias veces.
 *   php tests/ci/sembrar.php /ruta/a/wordpress [http://localhost:8099]
 */
$base = $argv[2] ?? 'http://localhost:8099';
define( 'WP_INSTALLING', true );
$_SERVER['HTTP_HOST'] = parse_url( $base, PHP_URL_HOST ) . ':' . parse_url( $base, PHP_URL_PORT ); $_SERVER['REQUEST_URI'] = '/';
require rtrim( $argv[1], '/' ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
if ( ! is_blog_installed() ) wp_install( 'RAU', 'admin', 'admin@example.com', true, '', wp_generate_password( 24 ) );
update_option( 'siteurl', $base ); update_option( 'home', $base );
update_option( 'permalink_structure', '/%postname%/' ); update_option( 'timezone_string', 'America/Bogota' );
switch_theme( 'rau-theme' );
foreach ( array( 'herramientas' => array( 'Herramientas de Radio', 'page-herramientas.php' ), 'receptor' => array( 'Receptor KiwiSDR', 'page-receptor.php' ) ) as $slug => $p ) {
	if ( get_page_by_path( $slug ) ) continue;
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $p[0], 'post_name' => $slug ) );
	update_post_meta( $id, '_wp_page_template', $p[1] );
}
update_option( 'rau_kiwi_relay', 'https://relevo.prueba' );
update_option( 'rau_receptores', array( array( 'id' => 'prueba', 'nombre' => 'Receptor de prueba', 'lugar' => 'Pereira, Risaralda', 'lat' => 4.81, 'lng' => -75.69 ) ) );
if ( ! get_posts( array( 'post_type' => 'repetidor', 'numberposts' => 1 ) ) ) {
	$reps = array(
		array( 'Repetidor Lira — Ibagué', 'PENDIENTE', '146.760 MHz', '-600 kHz', '85.4 Hz', 'Ibagué, Tolima', 4.44, -75.23 ),
		array( 'Repetidora Bogotá — Cerro Quiba', 'HK4-3', '147.380 MHz', '+600 kHz', '100.0 Hz', 'Cerro Quiba, Bogotá D.C.', 4.55, -74.17 ),
		array( 'Repetidora Medellín — Monte Alvernia', 'HK4RAU', '146.675 MHz', '-600 kHz', '100.0 Hz', 'Monte Alvernia, Medellín, Antioquia', 6.3, -75.6 ),
		array( 'Repetidora Montería — Cerro La Mula', 'HK4-2', '146.675 MHz', '-600 kHz', '100.0 Hz', 'Cerro La Mula, Montería, Córdoba', 8.75, -75.88 ),
	);
	foreach ( $reps as $r ) {
		$id = wp_insert_post( array( 'post_type' => 'repetidor', 'post_status' => 'publish', 'post_title' => $r[0] ) );
		foreach ( array( 'callsign' => $r[1], 'frequency' => $r[2], 'offset' => $r[3], 'tone' => $r[4], 'location' => $r[5], 'mode' => 'FM', 'band' => '2m', 'status' => 'activo', 'lat' => $r[6], 'lng' => $r[7], 'fecha_verificacion' => gmdate( 'Y-m-d' ) ) as $k => $v ) update_post_meta( $id, $k, $v );
	}
	$e = wp_insert_post( array( 'post_type' => 'evento', 'post_status' => 'publish', 'post_title' => 'Radioafición en Acción — Martes 7:00 p.m.' ) );
	update_post_meta( $e, 'start', gmdate( 'Y-m-d', time() + 6 * DAY_IN_SECONDS ) ); update_post_meta( $e, 'type', 'rueda' ); update_post_meta( $e, 'recurrencia', 'semanal' );
	$l = wp_insert_post( array( 'post_type' => 'libro_visitas', 'post_status' => 'publish', 'post_title' => 'Firma', 'post_content' => 'Más que un grupo es una familia.' ) );
	update_post_meta( $l, 'nombre', 'Persona de prueba' ); update_post_meta( $l, 'indicativo', 'HK4PRB' );
}
flush_rewrite_rules();
echo "Sembrado en $base\n";
