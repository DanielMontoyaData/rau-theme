<?php
/**
 * Template Name: Receptor KiwiSDR
 *
 * El KiwiSDR de RAU escuchado desde la propia página, con el diseño del sitio.
 *
 * El receptor solo habla HTTP; una página HTTPS no puede abrirle un WebSocket (contenido mixto).
 * Con un relevo HTTPS configurado (opción rau_kiwi_relay, ver cloudflare-worker/ en el repositorio)
 * el navegador se conecta a wss://relevo/ws/kiwi/<marca>/SND y /W/F, el mismo protocolo que usa la
 * interfaz oficial (web/openwebrx/openwebrx.js y kiwiclient/kiwi/client.py del proyecto KiwiSDR).
 * Sin relevo, la página muestra el estado y la ocupación por hora, y enlaza la interfaz original.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
require_once get_template_directory() . '/inc/receptor-kiwi.php';
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Receptor SDR · Medellín</p>
	<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
		<div>
			<h1 class="font-display text-3xl font-bold sm:text-4xl">KiwiSDR HK4RAU</h1>
			<p class="mt-2 max-w-2xl text-muted-foreground">Un receptor de 0 a 30 MHz en Medellín que cualquiera puede escuchar desde el navegador: bandas de radioaficionado, onda corta, señales horarias. Hasta 7 personas a la vez.</p>
		</div>
	</div>

	<?php rau_receptor_kiwi(); ?>
</section>

<?php get_footer(); ?>
