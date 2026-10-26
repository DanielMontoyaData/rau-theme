<?php
/**
 * Centro de Mando de Emergencias — /emergencias/ (la sirve inc/emergencias.php, sin página de WordPress).
 *
 * Página propia, sin el encabezado ni los estilos del sitio: tiene que cargar rápido con señal mala y
 * abrir sin internet (el service worker guarda la página y sus archivos). Lo esencial para los primeros
 * minutos (frecuencias por categoría y teléfonos) está escrito en el HTML: se ve aunque el JavaScript falle.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$em_uri = get_template_directory_uri() . '/assets/emergencias/';
$em_ver = RAU_THEME_VERSION;
$em_dir = array( 'categorias' => rau_em_directorio_categorias(), 'entradas' => rau_em_directorio() );
$em_rep = array();
foreach ( rau_get_repeaters_data() as $r ) {
	$em_rep[] = array( 'n' => $r['name'], 'c' => $r['callsign'], 'f' => $r['freq'], 'o' => $r['offset'], 't' => $r['tone'], 'l' => $r['location'], 'lat' => $r['lat'], 'lng' => $r['lng'] );
}
$em_cfg = array(
	'rest'       => esc_url_raw( rest_url( 'rau-em/v1/' ) ),
	// Solo con sesión iniciada en el sitio: la API usa la cookie más este nonce. Sin sesión, tokens de dispositivo.
	'nonce'      => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
	'sitio'      => home_url( '/' ),
	'login'      => wp_login_url( home_url( '/emergencias/' ) ),
	'admin'      => admin_url( 'tools.php?page=rau-em' ),
	'version'    => $em_ver,
	'esquema'    => rau_em_esquema(),
	'roles'      => rau_em_roles_nombres(),
	'directorio' => $em_dir,
	'repes'      => $em_rep,
	'turnos'     => home_url( '/turnos-de-guardia/' ),
	'checkin'    => home_url( '/check-in-de-malla/' ),
	'contorno'   => $em_uri . 'colombia.geojson?ver=' . $em_ver,
	'leaflet'    => array( 'js' => get_template_directory_uri() . '/assets/vendor/leaflet/leaflet.js?ver=1.9.4', 'css' => get_template_directory_uri() . '/assets/vendor/leaflet/leaflet.css?ver=1.9.4' ),
	'qr'         => get_template_directory_uri() . '/assets/vendor/qrcode/qrcode.js?ver=1.4.4',
);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b0f17">
<meta name="robots" content="noindex">
<meta name="description" content="Centro de mando de emergencias de HK4RAU: incidentes, mensajes con trazabilidad, estaciones, recursos, plan de comunicaciones e informes para radioaficionados y organizaciones de respuesta en Colombia. Funciona sin internet.">
<title>Centro de Mando de Emergencias · HK4RAU</title>
<link rel="manifest" href="<?php echo esc_url( get_template_directory_uri() . '/manifest.json' ); ?>">
<link rel="icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/images/favicon-32x32.png' ); ?>">
<link rel="stylesheet" href="<?php echo esc_url( $em_uri . 'em.css?ver=' . $em_ver ); ?>">
</head>
<body>
<div id="em-app">
	<header class="em-cab">
		<a class="em-marca" href="<?php echo esc_url( home_url( '/' ) ); ?>">RAU</a>
		<div class="em-titulo"><strong>Centro de Mando de Emergencias</strong><span>HK4RAU · radioaficionados de Colombia</span></div>
	</header>
	<main class="em-sinjs">
		<noscript><p class="em-aviso">Este navegador no ejecuta JavaScript: abajo están las frecuencias y los teléfonos. Para registrar incidentes y mensajes, ábrelo en un navegador con JavaScript.</p></noscript>
		<section class="em-tarjeta">
			<h2>Teléfonos</h2>
			<p class="em-tel"><strong>123</strong> Línea única de emergencias · <strong>132</strong> Cruz Roja · <strong>144</strong> Defensa Civil · <strong>119</strong> Bomberos</p>
		</section>
		<?php foreach ( array( 'actividad_ra', 'llamada_ra', 'otro_servicio' ) as $cat ) : ?>
		<section class="em-tarjeta">
			<h2><?php echo esc_html( $em_dir['categorias'][ $cat ] ); ?></h2>
			<table class="em-tabla"><tbody>
			<?php foreach ( $em_dir['entradas'] as $f ) : if ( $f['cat'] !== $cat ) continue; ?>
				<tr><td class="em-f"><?php echo esc_html( $f['f'] . ' ' . $f['unidad'] ); ?></td><td><?php echo esc_html( $f['banda'] . ' · ' . $f['modo'] ); ?></td><td><?php echo esc_html( $f['uso'] ); ?><br><small><?php echo esc_html( $f['condiciones'] ); ?></small></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		</section>
		<?php endforeach; ?>
		<p class="em-nota">Fuentes: reportes de la coordinación de emergencias de la IARU Región 2 sobre el sismo de agosto de 2026 (ARRL, IARU R1); Reglamento de Radiocomunicaciones de la UIT. Los centros de actividad son puntos de reunión, no canales exclusivos.</p>
	</main>
</div>
<script>window.RAU_EM=<?php echo wp_json_encode( $em_cfg ); ?>;</script>
<?php foreach ( array( 'em-core', 'em-db', 'em', 'em-vistas', 'em-sala', 'em-herr', 'em-mapa', 'em-sim' ) as $js ) : ?>
<script src="<?php echo esc_url( $em_uri . $js . '.js?ver=' . $em_ver ); ?>" defer></script>
<?php endforeach; ?>
<script>
if('serviceWorker' in navigator) window.addEventListener('load', function(){ navigator.serviceWorker.register('/service-worker.js').catch(function(){}); });
</script>
</body>
</html>
