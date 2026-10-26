<?php
/**
 * Centro de Mando de Emergencias (/emergencias/) — cargador.
 *
 * Servidor:   inc/emergencias/modelo.php      tablas, migraciones, roles, sesiones, auditoría
 *             inc/emergencias/esquema.php     validación de registros (assets/emergencias/esquema.json)
 *             inc/emergencias/api.php         API REST /wp-json/rau-em/v1/
 *             inc/emergencias/directorio.php  directorio de frecuencias con fuentes
 *             inc/emergencias/alertas.php     sismos (USGS), desastres (GDACS), bandas del receptor
 *             inc/emergencias/admin.php       Herramientas → Centro de mando, perfil de usuario, conservación
 * Navegador:  page-emergencias.php y assets/emergencias/ (em-core, em-db, em-mapa, em-sim, em)
 * Documentación: EMERGENCIAS.md
 */
if ( ! defined( 'ABSPATH' ) ) exit;

foreach ( array( 'modelo', 'esquema', 'directorio', 'alertas', 'api', 'admin' ) as $m ) require __DIR__ . '/emergencias/' . $m . '.php';

/* ── Ruta /emergencias/ (sin página de WordPress) ───────────────────────── */
add_action( 'init', function() {
	add_rewrite_rule( '^emergencias/?$', 'index.php?rau_em=1', 'top' );
} );
add_filter( 'query_vars', function( $v ) { $v[] = 'rau_em'; return $v; } );
add_action( 'template_redirect', function() {
	if ( ! get_query_var( 'rau_em' ) ) return;
	status_header( 200 );
	nocache_headers();
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	include get_template_directory() . '/page-emergencias.php';
	exit;
} );

// Clientes de la 19.52.0 que sigan abiertos: las alertas responden igual; lo demás pasó a la API REST
add_action( 'wp_ajax_rau_em_alertas', 'rau_em_alertas_ajax' );
add_action( 'wp_ajax_nopriv_rau_em_alertas', 'rau_em_alertas_ajax' );
function rau_em_alertas_ajax() { wp_send_json_success( rau_em_alertas_publicas() ); }
