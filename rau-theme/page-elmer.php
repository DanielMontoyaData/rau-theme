<?php
/**
 * Template Name: Programa Elmer (Mentores)
 * Usa el mismo tipo de contenido "Miembro" — marca a alguien como mentor
 * agregando el campo personalizado `mentor` con valor `1` y opcionalmente
 * `especialidad` (ej. "HF y antenas", "Modos digitales", "Emergencias").
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$mentores = new WP_Query( array(
	'post_type' => 'miembro', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC',
	'post_status' => 'publish',
	'meta_query' => array( array( 'key' => 'mentor', 'value' => '1' ) ),
) );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Aprende de quien ya recorrió el camino</p>
	<h1 class="mb-2 max-w-2xl font-display text-3xl font-bold sm:text-4xl">Programa Elmer</h1>
	<p class="mb-8 max-w-2xl text-muted-foreground">
		"Elmer" es como la comunidad de radioaficionados llama, desde hace décadas, a quien guía
		a un operador nuevo. Aquí conectamos a quienes recién obtuvieron su indicativo con
		miembros de RAU dispuestos a acompañarlos — dudas técnicas, primeros contactos, o
		simplemente compañía en la frecuencia.
	</p>

	<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
		<?php if ( $mentores->have_posts() ) : while ( $mentores->have_posts() ) : $mentores->the_post();
			$indicativo   = get_post_meta( get_the_ID(), 'indicativo', true );
			$especialidad = get_post_meta( get_the_ID(), 'especialidad', true );
			$ciudad       = get_post_meta( get_the_ID(), 'ciudad', true );
			$asunto       = rawurlencode( 'Programa Elmer — quiero un mentor' );
		?>
			<div class="flex flex-col rounded-xl border border-border/70 bg-card p-6">
				<div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 font-display text-base font-bold text-primary ring-1 ring-primary/20"><?php echo esc_html( rau_initials( get_the_title() ) ); ?></div>
				<h3 class="mt-4 font-display text-lg font-semibold"><?php the_title(); ?></h3>
				<p class="font-mono text-xs text-primary"><?php echo esc_html( $indicativo ); ?><?php echo $ciudad ? ' · ' . esc_html( $ciudad ) : ''; ?></p>
				<?php if ( $especialidad ) : ?><p class="mt-2 text-sm text-muted-foreground">Se especializa en: <?php echo esc_html( $especialidad ); ?></p><?php endif; ?>
				<a href="mailto:info@hk4rau.com?subject=<?php echo esc_attr( $asunto ); ?>" class="mt-4 inline-flex h-9 items-center justify-center gap-1.5 rounded-md border border-border bg-background px-4 text-xs font-medium shadow-sm hover:bg-accent hover:text-accent-foreground">
					<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"></path><path d="M4 6l8 7 8-7"></path></svg> Pedir contacto
				</a>
			</div>
		<?php endwhile; wp_reset_postdata(); else : ?>
			<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/70 bg-card/50 px-6 py-24 text-center">
				<div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
					<span class="text-4xl">🎓</span>
				</div>
				<p class="mb-2 font-display text-xl font-bold">Todavía no hay mentores registrados</p>
				<p class="max-w-sm text-sm text-muted-foreground">Marca a un miembro con el campo <code>mentor</code> = 1 en Directorio de Miembros.</p>
			</div>
		<?php endif; ?>
	</div>

	<div class="mt-12 rounded-2xl border border-primary/30 bg-gradient-to-br from-primary/15 via-card to-card p-8">
		<h3 class="font-display text-xl font-bold">¿Quieres ser Elmer?</h3>
		<p class="mt-2 max-w-xl text-sm text-muted-foreground">Si llevas tiempo en el aire y quieres acompañar a alguien nuevo, escríbenos y te agregamos al programa.</p>
		<a href="mailto:info@hk4rau.com?subject=<?php echo esc_attr( rawurlencode( 'Quiero ser Elmer' ) ); ?>" class="mt-4 inline-flex h-10 items-center justify-center rounded-md bg-primary px-6 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90">Escribir a RAU</a>
	</div>
</section>
<?php get_footer(); ?>
