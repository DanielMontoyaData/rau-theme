<?php
/**
 * Template Name: Libro de Guardia (QSL)
 * Asigna esta plantilla a una página (ej. título "Libro QSL") en el editor de WordPress.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$qsos = new WP_Query( array( 'post_type' => 'qso', 'posts_per_page' => 50, 'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish' ) );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Registro de Contactos</p>
	<h1 class="mb-2 max-w-2xl font-display text-3xl font-bold sm:text-4xl">Libro de Guardia Digital</h1>
	<p class="mb-8 max-w-2xl text-muted-foreground">Bitácora de contactos (QSO) de la asociación y su confirmación vía QSL.</p>

	<div class="overflow-hidden rounded-2xl border border-border/70 bg-card">
		<div class="hidden grid-cols-12 gap-4 border-b border-border/70 px-6 py-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground md:grid">
			<div class="col-span-3">Fecha</div>
			<div class="col-span-3">Indicativo</div>
			<div class="col-span-3">Banda / Modo</div>
			<div class="col-span-3">QSL</div>
		</div>
		<?php if ( $qsos->have_posts() ) : while ( $qsos->have_posts() ) : $qsos->the_post();
			$confirmado = get_post_meta( get_the_ID(), 'confirmado', true );
		?>
		<div class="grid grid-cols-2 gap-2 border-b border-border/50 px-6 py-4 last:border-0 md:grid-cols-12 md:items-center md:gap-4">
			<div class="md:col-span-3 font-mono text-sm text-muted-foreground"><?php echo esc_html( get_post_meta( get_the_ID(), 'fecha', true ) ); ?></div>
			<div class="md:col-span-3 font-mono text-sm font-semibold text-primary"><?php the_title(); ?></div>
			<div class="md:col-span-3 text-sm"><?php echo esc_html( get_post_meta( get_the_ID(), 'banda', true ) ); ?> · <?php echo esc_html( get_post_meta( get_the_ID(), 'modo', true ) ); ?></div>
			<div class="md:col-span-3">
				<?php if ( $confirmado ) : ?>
					<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 text-emerald-400 bg-emerald-400/10 ring-emerald-400/30">Confirmado</span>
				<?php else : ?>
					<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 text-amber-400 bg-amber-400/10 ring-amber-400/30">Pendiente</span>
				<?php endif; ?>
			</div>
		</div>
		<?php endwhile; wp_reset_postdata(); else : ?>
			<div class="px-6 py-10 text-center text-sm text-muted-foreground">Aún no hay contactos registrados. Agrégalos en el escritorio → Libro de Guardia (QSL).</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
