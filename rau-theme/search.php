<?php
/**
 * Resultados de búsqueda
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Búsqueda</p>
	<h1 class="mb-8 font-display text-3xl font-bold sm:text-4xl">
		<?php if ( have_posts() ) : ?>
			Resultados para: <span class="text-primary">"<?php echo esc_html( get_search_query() ); ?>"</span>
		<?php else : ?>
			Sin resultados para: "<?php echo esc_html( get_search_query() ); ?>"
		<?php endif; ?>
	</h1>

	<?php
	$q = mb_strtolower( trim( get_search_query() ) );
	$tool_matches = array();
	if ( '' !== $q ) {
		foreach ( rau_get_herramientas_tabs() as $t ) {
			if ( false !== mb_strpos( mb_strtolower( $t['label'] . ' ' . $t['cat'] ), $q ) ) {
				$tool_matches[] = $t;
			}
		}
	}
	if ( $tool_matches ) :
	?>
	<div class="mb-10">
		<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Herramientas (<?php echo count( $tool_matches ); ?>)</p>
		<div class="flex flex-wrap gap-2">
			<?php foreach ( $tool_matches as $t ) : ?>
			<a href="<?php echo esc_url( home_url( '/herramientas/' . $t['id'] . '/' ) ); ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-2 text-sm font-medium hover:border-primary/50 hover:text-primary">
				<?php echo esc_html( $t['label'] ); ?> <span class="font-mono text-[10px] text-muted-foreground">· <?php echo esc_html( $t['cat'] ); ?></span>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<?php if ( $tool_matches ) : ?><p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Contenido</p><?php endif; ?>
		<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
			<?php while ( have_posts() ) : the_post(); ?>
				<article class="rounded-xl border border-border/70 bg-card p-6">
					<span class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground"><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ); ?></span>
					<h3 class="mt-1 font-display text-lg font-semibold"><a href="<?php the_permalink(); ?>" class="hover:text-primary"><?php the_title(); ?></a></h3>
					<p class="mt-2 text-sm text-muted-foreground"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
				</article>
			<?php endwhile; ?>
		</div>
		<div class="mt-8"><?php the_posts_pagination( array( 'prev_text' => '← Anterior', 'next_text' => 'Siguiente →' ) ); ?></div>
	<?php elseif ( ! $tool_matches ) : ?>
		<p class="max-w-md text-muted-foreground">Prueba con otra palabra, o revisa el <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-primary hover:underline">inicio</a> y el menú de navegación.</p>
	<?php endif; ?>
</section>
<?php get_footer(); ?>
