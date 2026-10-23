<?php
/**
 * Template Name: Turnos de Guardia
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$turnos = new WP_Query( array( 'post_type' => 'turno', 'posts_per_page' => -1, 'orderby' => 'meta_value', 'meta_key' => 'fecha_inicio', 'order' => 'ASC', 'post_status' => 'publish' ) );
$hoy = current_time( 'Y-m-d' );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Red de Emergencia</p>
	<h1 class="mb-2 max-w-2xl font-display text-3xl font-bold sm:text-4xl">Turnos de Guardia</h1>
	<p class="mb-8 max-w-2xl text-muted-foreground">Quién está de turno para monitorear la Red de Emergencia cada semana.</p>

	<div class="overflow-hidden rounded-2xl border border-border/70 bg-card">
		<div class="hidden grid-cols-12 gap-4 border-b border-border/70 px-6 py-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground md:grid">
			<div class="col-span-3">Del</div>
			<div class="col-span-3">Al</div>
			<div class="col-span-4">Responsable</div>
			<div class="col-span-2">Estado</div>
		</div>
		<?php if ( $turnos->have_posts() ) : while ( $turnos->have_posts() ) : $turnos->the_post();
			$inicio = get_post_meta( get_the_ID(), 'fecha_inicio', true );
			$fin    = get_post_meta( get_the_ID(), 'fecha_fin', true );
			$actual = ( $inicio && $fin && $hoy >= $inicio && $hoy <= $fin );
		?>
			<div class="grid grid-cols-2 gap-2 border-b border-border/50 px-6 py-4 last:border-0 md:grid-cols-12 md:items-center md:gap-4">
				<div class="md:col-span-3 font-mono text-sm text-muted-foreground"><?php echo esc_html( $inicio ); ?></div>
				<div class="md:col-span-3 font-mono text-sm text-muted-foreground"><?php echo esc_html( $fin ); ?></div>
				<div class="md:col-span-4 font-semibold"><?php the_title(); ?></div>
				<div class="md:col-span-2">
					<?php if ( $actual ) : ?>
						<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 text-emerald-400 bg-emerald-400/10 ring-emerald-400/30">En turno</span>
					<?php else : ?>
						<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 text-muted-foreground bg-muted ring-border">Programado</span>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( get_post_meta( get_the_ID(), 'notas', true ) ) : ?>
				<div class="border-b border-border/50 px-6 pb-4 text-sm text-muted-foreground last:border-0"><?php echo esc_html( get_post_meta( get_the_ID(), 'notas', true ) ); ?></div>
			<?php endif; ?>
		<?php endwhile; wp_reset_postdata(); else : ?>
			<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/70 bg-card/50 px-6 py-24 text-center">
				<div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
					<span class="text-4xl">🕐</span>
				</div>
				<p class="mb-2 font-display text-xl font-bold">Aún no hay turnos programados</p>
				<p class="max-w-sm text-sm text-muted-foreground"><?php echo current_user_can( 'edit_posts' ) ? 'Agrégalos en el escritorio → Turnos de Guardia → Agregar nuevo.' : 'No hay turnos de guardia publicados por ahora.'; ?></p>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
