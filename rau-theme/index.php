<?php
/**
 * Plantilla de respaldo (requerida por WordPress)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<section class="mx-auto max-w-[80rem] px-4 sm:px-6 py-16">
	<?php if ( have_posts() ) : ?>
		<h1 class="mb-8 font-display text-3xl font-bold sm:text-4xl"><?php bloginfo( 'name' ); ?></h1>
		<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
			<?php while ( have_posts() ) : the_post(); ?>
				<article class="rounded-xl border border-border/70 bg-card p-6">
					<h3 class="font-display text-lg font-semibold"><a href="<?php the_permalink(); ?>" class="hover:text-primary"><?php the_title(); ?></a></h3>
					<p class="mt-2 text-sm text-muted-foreground"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
					<p class="mt-4 font-mono text-[11px] uppercase tracking-wider text-muted-foreground"><?php echo esc_html( get_the_date() ); ?></p>
				</article>
			<?php endwhile; ?>
		</div>
		<div class="mt-8"><?php the_posts_pagination(); ?></div>
	<?php else : ?>
		<h1 class="font-display text-3xl font-bold">No se encontró contenido</h1>
		<p class="mt-4 text-muted-foreground">Vuelve al <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-primary hover:underline">inicio</a>.</p>
	<?php endif; ?>
</section>
<?php get_footer(); ?>
