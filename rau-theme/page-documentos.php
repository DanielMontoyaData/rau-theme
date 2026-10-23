<?php
/**
 * Template Name: Biblioteca de Documentos
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$documentos = new WP_Query( array( 'post_type' => 'documento', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish' ) );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Documentos oficiales</p>
	<h1 class="mb-2 max-w-2xl font-display text-3xl font-bold sm:text-4xl">Biblioteca de Documentos</h1>
	<p class="mb-8 max-w-2xl text-muted-foreground">Actas, estatutos y resoluciones de la asociación en un solo lugar.</p>

	<div class="overflow-hidden rounded-2xl border border-border/70 bg-card">
		<?php if ( $documentos->have_posts() ) : while ( $documentos->have_posts() ) : $documentos->the_post();
			$archivo = get_post_meta( get_the_ID(), 'archivo_url', true );
			$categoria = get_post_meta( get_the_ID(), 'categoria', true );
		?>
			<div class="flex items-center justify-between gap-4 border-b border-border/50 px-6 py-4 last:border-0">
				<div class="min-w-0">
					<p class="font-semibold"><?php the_title(); ?></p>
					<?php if ( $categoria ) : ?><p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground"><?php echo esc_html( $categoria ); ?></p><?php endif; ?>
					<?php if ( get_the_content() ) : ?><div class="mt-1 text-sm text-muted-foreground"><?php the_content(); ?></div><?php endif; ?>
				</div>
				<?php if ( $archivo ) : ?>
					<a href="<?php echo esc_url( $archivo ); ?>" target="_blank" rel="noreferrer" class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-md border border-border bg-background px-4 text-xs font-medium shadow-sm hover:bg-accent hover:text-accent-foreground">
						<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"></path></svg> Descargar
					</a>
				<?php endif; ?>
			</div>
		<?php endwhile; wp_reset_postdata(); else : ?>
			<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/70 bg-card/50 px-6 py-24 text-center">
				<div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
					<span class="text-4xl">📄</span>
				</div>
				<p class="mb-2 font-display text-xl font-bold">Aún no hay documentos publicados</p>
				<p class="max-w-sm text-sm text-muted-foreground"><?php echo current_user_can( 'edit_posts' ) ? 'Agrégalos en el escritorio → Biblioteca de Documentos → Agregar nuevo.' : 'Pronto publicaremos documentos aquí.'; ?></p>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
