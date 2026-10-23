<?php
/**
 * Página 404
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-24 text-center">
	<p class="font-mono text-sm uppercase tracking-[0.3em] text-primary">Error 404</p>
	<h1 class="mt-4 font-display text-4xl font-bold sm:text-5xl">QRZ? No hay respuesta en esta frecuencia.</h1>
	<p class="mx-auto mt-4 max-w-md text-muted-foreground">La página que buscas no existe o cambió de lugar. Prueba desde el inicio o revisa el menú.</p>
	<div class="mt-8 flex flex-wrap justify-center gap-3">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90">Volver al inicio</a>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'repetidor' ) ); ?>" class="inline-flex h-10 items-center justify-center rounded-md border border-border bg-background px-8 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground">Ver repetidores</a>
	</div>
</section>
<?php get_footer(); ?>
