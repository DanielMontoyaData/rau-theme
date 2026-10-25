<?php
/**
 * Centro de Mando de Emergencias — /emergencias/ (la sirve inc/emergencias.php, sin página de WordPress).
 *
 * Página propia, sin el encabezado ni los estilos del sitio: tiene que cargar rápido con señal mala y
 * abrir sin internet (el service worker la guarda con sus dos archivos). Todo lo esencial para los
 * primeros minutos (frecuencias y teléfonos) está escrito en el HTML: se ve aunque el JavaScript falle.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$em_uri  = get_template_directory_uri() . '/assets/emergencias/';
$em_frec = rau_em_frecuencias();
$em_rep  = array();
foreach ( rau_get_repeaters_data() as $r ) {
	$em_rep[] = array( 'n' => $r['name'], 'c' => $r['callsign'], 'f' => $r['freq'], 'o' => $r['offset'], 't' => $r['tone'], 'l' => $r['location'], 'lat' => $r['lat'], 'lng' => $r['lng'] );
}
$em_cfg = array(
	'ajax'     => admin_url( 'admin-ajax.php' ),
	'sitio'    => home_url( '/' ),
	'version'  => RAU_THEME_VERSION,
	'frec'     => $em_frec,
	'repes'    => $em_rep,
	'reportadas' => rau_em_repetidoras_reportadas(),
	'turnos'   => home_url( '/turnos-de-guardia/' ),
	'checkin'  => home_url( '/check-in-de-malla/' ),
);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b0f17">
<meta name="description" content="Centro de mando de emergencias para radioaficionados de Colombia: red de estaciones, mensajes formales, plan de frecuencias y bienestar. Funciona sin internet.">
<title>Centro de Mando de Emergencias · RAU</title>
<link rel="manifest" href="<?php echo esc_url( get_template_directory_uri() . '/manifest.json' ); ?>">
<link rel="icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/images/favicon-32x32.png' ); ?>">
<link rel="stylesheet" href="<?php echo esc_url( $em_uri . 'em.css?ver=' . RAU_THEME_VERSION ); ?>">
</head>
<body>
<div id="em-app">
	<header class="em-cab">
		<a class="em-marca" href="<?php echo esc_url( home_url( '/' ) ); ?>">RAU</a>
		<div class="em-titulo"><strong>Centro de Mando de Emergencias</strong><span>Radioaficionados de Colombia</span></div>
	</header>
	<main class="em-sinjs">
		<noscript><p class="em-aviso">Este navegador no ejecuta JavaScript: abajo están las frecuencias y teléfonos. Para registrar estaciones y mensajes, ábrelo en un navegador con JavaScript.</p></noscript>
		<section class="em-tarjeta">
			<h2>Frecuencias nacionales de emergencia</h2>
			<table class="em-tabla"><tbody>
			<?php foreach ( $em_frec as $f ) : ?>
				<tr><td class="em-f"><?php echo esc_html( $f['f'] ); ?></td><td><?php echo esc_html( $f['b'] . ' · ' . $f['m'] ); ?></td><td><?php echo esc_html( $f['uso'] ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
			<p class="em-nota">Centros de actividad de la IARU Región 2 que activó la Liga Colombiana de Radioaficionados en el sismo de agosto de 2026: el tráfico puede estar hasta ±20 kHz alrededor.</p>
		</section>
		<section class="em-tarjeta">
			<h2>Teléfonos</h2>
			<p class="em-tel"><strong>123</strong> Línea única de emergencias · <strong>132</strong> Cruz Roja · <strong>144</strong> Defensa Civil · <strong>119</strong> Bomberos</p>
		</section>
	</main>
</div>
<script>window.RAU_EM=<?php echo wp_json_encode( $em_cfg ); ?>;</script>
<script src="<?php echo esc_url( $em_uri . 'em.js?ver=' . RAU_THEME_VERSION ); ?>" defer></script>
<script>
if('serviceWorker' in navigator) window.addEventListener('load', function(){ navigator.serviceWorker.register('/service-worker.js').catch(function(){}); });
</script>
</body>
</html>
