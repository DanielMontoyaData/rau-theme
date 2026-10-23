<?php
/**
 * Template Name: Galería de Actividades
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$items = new WP_Query( array(
	'post_type'      => 'galeria_item',
	'posts_per_page' => 40,
	'post_status'    => 'publish',
) );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Comunidad RAU</p>
	<h1 class="mb-2 font-display text-3xl font-bold sm:text-4xl">Galería de Actividades</h1>
	<p class="mb-10 max-w-2xl text-muted-foreground">Registro fotográfico de mallas, eventos especiales, activaciones POTA y encuentros de la asociación.</p>

	<?php if ( $items->have_posts() ) : ?>
	<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
		<?php while ( $items->have_posts() ) : $items->the_post();
			$img   = get_post_meta( get_the_ID(), 'image_url', true );
			$fecha = get_post_meta( get_the_ID(), 'fecha', true );
		?>
		<div class="group overflow-hidden rounded-2xl border border-border/70 bg-card">
			<?php if ( $img ) : ?>
			<div class="aspect-[4/3] overflow-hidden">
				<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>"
					loading="lazy" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
			</div>
			<?php else : ?>
			<div class="flex aspect-[4/3] items-center justify-center bg-muted/30">
				<span class="font-mono text-4xl text-muted-foreground/30">📷</span>
			</div>
			<?php endif; ?>
			<div class="p-4">
				<p class="font-semibold text-sm"><?php the_title(); ?></p>
				<?php if ( $fecha ) : ?><p class="mt-1 font-mono text-xs text-muted-foreground"><?php echo esc_html( $fecha ); ?></p><?php endif; ?>
			</div>
		</div>
		<?php endwhile; wp_reset_postdata(); ?>
	</div>

	<?php else : ?>
	<!-- Estado vacío: próximamente -->
	<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/70 bg-card/50 px-6 py-24 text-center">
		<div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
			<span class="text-4xl">📷</span>
		</div>
		<p class="mb-2 font-display text-xl font-bold">Galería en construcción</p>
		<p class="max-w-sm text-sm text-muted-foreground">Aquí irán las fotos de mallas, activaciones POTA, encuentros y eventos especiales de HK4RAU. Vuelve pronto.</p>
		<div class="mt-6 inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/5 px-4 py-1.5">
			<span class="h-2 w-2 rounded-full bg-primary animate-pulse"></span>
			<span class="font-mono text-xs text-primary">Próximamente</span>
		</div>
	</div>
	<?php endif; ?>
</section>
<?php get_footer(); ?>
