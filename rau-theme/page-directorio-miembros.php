<?php
/**
 * Template Name: Directorio de Miembros
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$miembros = new WP_Query( array( 'post_type' => 'miembro', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish' ) );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Comunidad RAU</p>
	<h1 class="mb-2 max-w-2xl font-display text-3xl font-bold sm:text-4xl">Directorio de Miembros</h1>
	<p class="mb-8 max-w-2xl text-muted-foreground">Solo aparecen aquí los miembros que autorizaron publicar sus datos.</p>

	<input aria-label="Buscar por indicativo, nombre o ciudad" type="search" id="dir-buscar" placeholder="Buscar por indicativo, nombre o ciudad…" class="mb-4 w-full max-w-md rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">

	<div class="overflow-hidden rounded-2xl border border-border/70 bg-card">
		<div class="hidden grid-cols-12 gap-4 border-b border-border/70 px-6 py-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground md:grid">
			<div class="col-span-3">Indicativo</div>
			<div class="col-span-4">Nombre</div>
			<div class="col-span-3">Ciudad</div>
			<div class="col-span-2">Bandas / Modos</div>
		</div>
		<?php if ( $miembros->have_posts() ) : while ( $miembros->have_posts() ) : $miembros->the_post(); ?>
			<div class="dir-fila grid grid-cols-2 gap-2 border-b border-border/50 px-6 py-4 last:border-0 md:grid-cols-12 md:items-center md:gap-4">
				<div class="md:col-span-3 font-mono text-sm font-semibold text-primary"><?php echo esc_html( get_post_meta( get_the_ID(), 'indicativo', true ) ); ?></div>
				<div class="md:col-span-4"><?php the_title(); ?></div>
				<div class="md:col-span-3 text-sm text-muted-foreground"><?php echo esc_html( get_post_meta( get_the_ID(), 'ciudad', true ) ); ?></div>
				<div class="md:col-span-2 text-sm text-muted-foreground"><?php echo esc_html( get_post_meta( get_the_ID(), 'bandas', true ) ); ?></div>
			</div>
		<?php endwhile; wp_reset_postdata(); else : ?>
			<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/70 bg-card/50 px-6 py-24 text-center">
				<div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
					<span class="text-4xl">👥</span>
				</div>
				<p class="mb-2 font-display text-xl font-bold">Aún no hay miembros publicados</p>
				<p class="max-w-sm text-sm text-muted-foreground">Agrégalos en el escritorio → Directorio de Miembros → Agregar nuevo.</p>
			</div>
		<?php endif; ?>
	</div>

	<p class="mt-6 text-xs text-muted-foreground">¿Quieres aparecer aquí? Escríbenos a <a href="mailto:info@hk4rau.com" class="text-primary hover:underline">info@hk4rau.com</a> con tu indicativo, ciudad y bandas de interés.</p>
</section>
<script>
// Se comprueba que el campo exista antes de engancharlo. Si el directorio se renderiza vacío o
// alguien cambia el id, getElementById devuelve null y .addEventListener lanza un TypeError que
// corta el resto del script de la página.
(function(){
	var buscar = document.getElementById('dir-buscar');
	if(!buscar) return;
	buscar.addEventListener('input', function(){
		var q=this.value.toLowerCase();
		document.querySelectorAll('.dir-fila').forEach(function(fila){
			fila.style.display = fila.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
		});
	});
})();
</script>
<?php get_footer(); ?>
