<?php
/**
 * Template Name: Diplomas y Certificaciones
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$diplomas = new WP_Query( array(
	'post_type'      => 'diploma',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
) );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Reconocimientos RAU</p>
	<h1 class="mb-2 font-display text-3xl font-bold sm:text-4xl">Diplomas y Certificaciones</h1>
	<p class="mb-10 max-w-2xl text-muted-foreground">Diplomas oficiales emitidos por HK4RAU para sus miembros y operadores participantes en actividades especiales.</p>

	<?php if ( $diplomas->have_posts() ) : ?>
	<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
		<?php while ( $diplomas->have_posts() ) : $diplomas->the_post();
			$tipo     = get_post_meta( get_the_ID(), 'tipo', true );
			$fecha    = get_post_meta( get_the_ID(), 'fecha', true );
			$receptor = get_post_meta( get_the_ID(), 'receptor', true );
			$archivo  = get_post_meta( get_the_ID(), 'archivo_url', true );
			$numero   = get_post_meta( get_the_ID(), 'numero', true ); // usado por el Verificador de Diplomas en Herramientas
		?>
		<div class="rounded-2xl border border-border/70 bg-card p-6">
			<div class="mb-4 flex items-start justify-between gap-3">
				<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 ring-1 ring-primary/20">
					<span class="text-2xl">🏅</span>
				</div>
				<?php if ( $tipo ) : ?>
				<span class="inline-flex rounded-full px-2.5 py-1 font-mono text-[11px] font-medium ring-1 text-primary bg-primary/10 ring-primary/30"><?php echo esc_html( $tipo ); ?></span>
				<?php endif; ?>
			</div>
			<p class="font-display text-lg font-bold leading-snug"><?php the_title(); ?></p>
			<?php if ( $receptor ) : ?><p class="mt-1 text-sm text-primary"><?php echo esc_html( $receptor ); ?></p><?php endif; ?>
			<?php if ( $fecha ) : ?><p class="mt-1 font-mono text-xs text-muted-foreground"><?php echo esc_html( $fecha ); ?></p><?php endif; ?>
			<?php if ( get_the_content() ) : ?><p class="mt-3 text-sm text-muted-foreground"><?php echo wp_kses_post( get_the_excerpt() ); ?></p><?php endif; ?>
			<?php if ( $archivo ) : ?>
			<a href="<?php echo esc_url( $archivo ); ?>" target="_blank" rel="noopener"
				class="mt-4 inline-flex items-center gap-2 rounded-md border border-border/70 bg-background px-4 py-2 text-sm font-medium transition-colors hover:border-primary/50 hover:text-primary">
				📄 Descargar diploma
			</a>
			<?php endif; ?>
		</div>
		<?php endwhile; wp_reset_postdata(); ?>
	</div>

	<?php else : ?>
	<!-- Estado vacío: próximamente -->
	<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/70 bg-card/50 px-6 py-24 text-center">
		<div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
			<span class="text-4xl">🏅</span>
		</div>
		<p class="mb-2 font-display text-xl font-bold">Diplomas en preparación</p>
		<p class="max-w-sm text-sm text-muted-foreground">RAU emitirá diplomas para actividades especiales, participación en mallas y logros operativos. Esta sección se habilitará próximamente.</p>
		<div class="mt-6 inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/5 px-4 py-1.5">
			<span class="h-2 w-2 rounded-full bg-primary animate-pulse"></span>
			<span class="font-mono text-xs text-primary">Próximamente</span>
		</div>
		<div class="mt-8 grid gap-3 sm:grid-cols-3 max-w-lg w-full text-left">
			<?php
			$tipos = [
				['🎖️','Participación en mallas','Registro de asistencia a la malla semanal'],
				['📡','Activación POTA','Parques en el Aire desde Colombia'],
				['🇨🇴','Evento 5K4IC','Independencia de Colombia — julio 2026'],
			];
			foreach($tipos as $t): ?>
			<div class="rounded-xl border border-border/50 bg-background p-4 opacity-60">
				<span class="text-2xl"><?php echo esc_html($t[0]); ?></span>
				<p class="mt-2 text-sm font-semibold"><?php echo esc_html($t[1]); ?></p>
				<p class="mt-1 text-xs text-muted-foreground"><?php echo esc_html($t[2]); ?></p>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>
</section>
<?php get_footer(); ?>
