<?php
/**
 * Archivo genérico: Recursos Técnicos, Historias, Diplomas, Galería
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$type = get_post_type();
$titles = array(
	'recurso'      => 'Todos los Recursos Técnicos',
	'historia'     => 'Todas las Historias de la Comunidad',
	'diploma'      => 'Todos los Diplomas y Certificaciones',
	'galeria_item' => 'Galería Completa',
);
// get_the_archive_title() trae HTML ("Categoría: <span>…</span>"); con esc_html se veían las etiquetas.
$heading = isset( $titles[ $type ] ) ? $titles[ $type ] : wp_strip_all_tags( get_the_archive_title() );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<h1 class="mb-8 max-w-2xl font-display text-3xl font-bold sm:text-4xl"><?php echo esc_html( $heading ); ?></h1>

	<?php if ( 'galeria_item' === $type ) : ?>
		<div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
				<div class="aspect-[4/3] overflow-hidden rounded-xl border border-border/70">
					<?php the_post_thumbnail( 'medium', array( 'class' => 'h-full w-full object-cover' ) ); ?>
				</div>
			<?php endwhile; else : ?>
				<p class="text-sm text-muted-foreground">Aún no hay fotos publicadas.</p>
			<?php endif; ?>
		</div>

	<?php elseif ( 'historia' === $type ) : ?>
		<div class="grid gap-4 sm:grid-cols-3">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post();
				$autor = get_post_meta( get_the_ID(), 'autor', true ) ?: get_the_title();
			?>
				<div class="rounded-xl border border-border/70 bg-card p-5">
					<p class="text-sm italic text-muted-foreground">&ldquo;<?php echo wp_kses_post( get_the_content() ); ?>&rdquo;</p>
					<div class="mt-4 flex items-center gap-3 border-t border-border/50 pt-3">
						<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 font-mono text-xs font-bold text-primary ring-1 ring-primary/20"><?php echo esc_html( rau_initials( preg_replace( '/,.*/', '', $autor ) ) ); ?></div>
						<div><p class="text-sm font-semibold"><?php echo esc_html( $autor ); ?></p><p class="text-xs text-muted-foreground"><?php echo esc_html( get_post_meta( get_the_ID(), 'rol', true ) ); ?></p></div>
					</div>
				</div>
			<?php endwhile; else : ?>
				<p class="text-sm text-muted-foreground">Aún no hay historias publicadas.</p>
			<?php endif; ?>
		</div>

	<?php elseif ( 'recurso' === $type ) : ?>
		<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); $cat = get_post_meta( get_the_ID(), 'category', true ); ?>
				<div class="flex flex-col rounded-xl border border-border/70 bg-card p-6">
					<?php rau_icon( rau_resource_icon( $cat ), 'mb-4 h-6 w-6 text-primary' ); ?>
					<h3 class="font-display text-lg font-semibold"><?php the_title(); ?></h3>
					<div class="mt-2 flex-1 text-sm text-muted-foreground"><?php the_content(); ?></div>
					<div class="mt-4 flex items-center justify-between">
						<span class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground"><?php echo esc_html( get_post_meta( get_the_ID(), 'meta', true ) ); ?></span>
						<?php $link = get_post_meta( get_the_ID(), 'link', true ); if ( $link ) : ?>
							<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noreferrer" class="flex items-center gap-1 text-sm font-medium text-primary hover:underline">Abrir <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"></path></svg></a>
						<?php endif; ?>
					</div>
				</div>
			<?php endwhile; else : ?>
				<p class="text-sm text-muted-foreground">Aún no hay recursos publicados.</p>
			<?php endif; ?>
		</div>

	<?php else : /* diploma y cualquier otro tipo */ ?>
		<div class="grid gap-4 sm:grid-cols-3">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
				<div class="flex flex-col rounded-xl border border-border/70 bg-card p-5">
					<?php rau_icon( 'award', 'mb-3 h-6 w-6 text-primary' ); ?>
					<h3 class="font-display text-base font-semibold"><?php the_title(); ?></h3>
					<div class="mt-1 text-sm text-muted-foreground"><?php the_content(); ?></div>
				</div>
			<?php endwhile; else : ?>
				<p class="text-sm text-muted-foreground">Aún no hay contenido publicado.</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="mt-8"><?php the_posts_pagination( array( 'prev_text' => '← Anterior', 'next_text' => 'Siguiente →' ) ); ?></div>
</section>
<?php get_footer(); ?>
