<?php
/**
 * Template Name: Novedades
 * Registro público de lo que se agrega al sitio — para que la comunidad
 * no dependa de que alguien les cuente uno por uno qué hay de nuevo.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$novedades = array(
	array( 'fecha' => '2026-08', 'titulo' => 'Notificaciones push reales', 'texto' => 'Alertas de banda, Dashboard y RAU Field ahora pueden avisarte aunque tengas el navegador cerrado — usando el mismo estándar (Web Push) que usan las apps nativas, sin depender de ningún servicio de pago.' ),
	array( 'fecha' => '2026-08', 'titulo' => 'Modo sin conexión (PWA)', 'texto' => 'Las calculadoras que no dependen de datos en vivo (Maidenhead, Código Q, CTCSS, RST, Fonético, Glosario, Código de Colores) ahora funcionan incluso sin internet, una vez que visitaste la página con conexión.' ),
	array( 'fecha' => '2026-08', 'titulo' => 'Manos libres en RAU Field', 'texto' => 'Reconocimiento de voz para consultar hora, batería, repetidor más cercano, próxima pasada de satélite y frecuencias de emergencia sin tocar la pantalla.' ),
	array( 'fecha' => '2026-08', 'titulo' => 'URL propia por herramienta', 'texto' => 'Cada una de las 38 herramientas ahora tiene su propio link (ej. hk4rau.com/herramientas/doppler/) — se puede compartir directo, y Google puede indexarlas por separado.' ),
	array( 'fecha' => '2026-08', 'titulo' => 'API pública de solo lectura', 'texto' => 'Repetidores y eventos ahora se pueden consultar en formato de datos abierto, para quien quiera construir algo sobre la información de RAU.' ),
	array( 'fecha' => '2026-08', 'titulo' => 'Mapa de Repetidores', 'texto' => 'El directorio de repetidores ahora incluye un mapa con el estado real de cada uno y su cobertura estimada.' ),
	array( 'fecha' => '2026-08', 'titulo' => 'RAU Field', 'texto' => 'Vista simplificada para operación portátil y activaciones POTA — reloj, ubicación, repetidor más cercano, batería, todo en una sola pantalla.' ),
	array( 'fecha' => '2026-08', 'titulo' => 'Dashboard en Vivo y Estado RAU', 'texto' => 'Dos vistas nuevas para tener de un vistazo todo lo operativo — propagación, KiwiSDR, próxima malla, satélites y más.' ),
	array( 'fecha' => '2026-07', 'titulo' => 'Predicción de Pasadas de Satélites', 'texto' => 'Cálculo real de mecánica orbital (SGP4) para saber cuándo y por dónde pasa cada satélite sobre tu ubicación.' ),
	array( 'fecha' => '2026-07', 'titulo' => 'Correo diario a socios', 'texto' => 'Reporte automático de propagación y próximas actividades, directo al correo de quien lo active.' ),
	array( 'fecha' => '2026-07', 'titulo' => 'Bot de preguntas frecuentes', 'texto' => 'Un asistente en el sitio que responde dudas comunes sobre RAU al instante, sin necesidad de esperar respuesta por WhatsApp.' ),
	array( 'fecha' => '2026-07', 'titulo' => 'Libro de Visitas y Certificados', 'texto' => 'Un espacio permanente para que cualquiera deje su huella, y certificados descargables de participación en actividades.' ),
	array( 'fecha' => '2026-06', 'titulo' => 'Más de 30 herramientas técnicas', 'texto' => 'Desde calculadoras de antenas y RF hasta código de colores, autonomía de batería, calendario de concursos y verificador de diplomas — todo gratis, sin necesitar cuenta.' ),
);
?>

<div class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
	<p class="mb-3 font-mono text-[11px] uppercase tracking-[0.25em] text-primary">Registro de cambios</p>
	<h1 class="mb-2 font-display text-3xl font-bold sm:text-4xl">Novedades</h1>
	<p class="mb-10 text-muted-foreground">Qué se ha ido agregando a hk4rau.com, en orden — sin necesidad de que te lo contemos uno por uno.</p>

	<div class="space-y-8 border-l-2 border-border/70 pl-6">
		<?php foreach ( $novedades as $n ) : ?>
		<div class="relative">
			<span class="absolute -left-[29px] top-1.5 h-3 w-3 rounded-full border-2 border-primary bg-background"></span>
			<p class="mb-1 font-mono text-xs uppercase tracking-wider text-primary"><?php echo esc_html( $n['fecha'] ); ?></p>
			<h2 class="mb-1 font-display text-lg font-bold"><?php echo esc_html( $n['titulo'] ); ?></h2>
			<p class="text-sm text-muted-foreground"><?php echo esc_html( $n['texto'] ); ?></p>
		</div>
		<?php endforeach; ?>
	</div>

	<p class="mt-10 text-center text-xs text-muted-foreground">¿Falta algo o encontraste un problema? <a href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>" class="text-primary hover:underline">Escríbenos</a>.</p>
</div>

<?php get_footer(); ?>
