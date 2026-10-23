<?php
/**
 * Template Name: Pulguero
 * Asigna esta plantilla a una página (ej. título "Pulguero") en el editor de WordPress.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$anuncios = new WP_Query( array( 'post_type' => 'anuncio', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish' ) );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Compra · Venta · Intercambio</p>
	<h1 class="mb-2 max-w-2xl font-display text-3xl font-bold sm:text-4xl">Pulguero RAU</h1>
	<p class="mb-8 max-w-2xl text-muted-foreground">Equipos, antenas y accesorios entre miembros de la asociación.</p>

	<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
		<?php if ( $anuncios->have_posts() ) : while ( $anuncios->have_posts() ) : $anuncios->the_post(); ?>
			<div class="flex flex-col overflow-hidden rounded-xl border border-border/70 bg-card">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="aspect-[4/3] w-full overflow-hidden"><?php the_post_thumbnail( 'medium', array( 'class' => 'h-full w-full object-cover' ) ); ?></div>
				<?php endif; ?>
				<div class="flex flex-1 flex-col p-5">
					<h3 class="font-display text-lg font-semibold"><?php the_title(); ?></h3>
					<div class="mt-2 flex-1 text-sm text-muted-foreground"><?php the_content(); ?></div>
					<div class="mt-4 flex items-center justify-between border-t border-border/50 pt-3">
						<span class="font-display text-lg font-bold text-primary"><?php echo esc_html( get_post_meta( get_the_ID(), 'precio', true ) ?: 'A convenir' ); ?></span>
						<span class="font-mono text-xs text-muted-foreground"><?php echo esc_html( get_post_meta( get_the_ID(), 'contacto', true ) ); ?></span>
					</div>
				</div>
			</div>
		<?php endwhile; wp_reset_postdata(); else : ?>
			<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/70 bg-card/50 px-6 py-24 text-center">
				<div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
					<span class="text-4xl">🏷️</span>
				</div>
				<p class="mb-2 font-display text-xl font-bold">Aún no hay anuncios publicados</p>
				<p class="max-w-sm text-sm text-muted-foreground"><?php echo current_user_can( 'edit_posts' ) ? 'Agrégalos en el escritorio → Pulguero (Clasificados) → Agregar nuevo.' : 'No hay clasificados publicados por ahora.'; ?></p>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
