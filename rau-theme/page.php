<?php
/**
 * Plantilla genérica para páginas (Quiénes Somos, Normativa, etc.)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<?php while ( have_posts() ) : the_post(); ?>
		<div class="max-w-3xl">
			<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary"><?php echo esc_html( get_the_date() ); ?></p>
			<h1 class="font-display text-3xl font-bold sm:text-4xl"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="mt-4 text-lg text-muted-foreground"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="mt-8 overflow-hidden rounded-2xl border border-border/70">
				<?php the_post_thumbnail( 'large', array( 'class' => 'w-full object-cover' ) ); ?>
			</div>
		<?php endif; ?>

		<div class="entry-content mt-8 max-w-3xl text-muted-foreground">
			<?php the_content(); ?>
		</div>
	<?php endwhile; ?>
</section>
<?php get_footer(); ?>
