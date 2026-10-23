<?php
/**
 * Template Name: Alertas de Emergencia
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$alertas = new WP_Query( array(
	'post_type'      => 'alerta',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
) );
$activas = new WP_Query( array(
	'post_type'      => 'alerta',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'meta_query'     => array( array( 'key' => 'active', 'value' => '1' ) ),
) );
$hay_activas = $activas->have_posts();
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-accent">Red de Emergencia</p>
	<h1 class="mb-2 font-display text-3xl font-bold sm:text-4xl">Alertas de Emergencia</h1>
	<p class="mb-6 max-w-2xl text-muted-foreground">Comunicaciones de emergencia y avisos activos de la red HK4RAU. La malla de emergencia opera en <strong class="text-accent font-mono"><?php echo esc_html( rau_get_repetidor_principal()['frequency'] ); ?> MHz T100</strong>.</p>

	<a href="<?php echo esc_url( home_url( '/modo-emergencia/' ) ); ?>" class="mb-10 flex items-center justify-between rounded-xl border-2 border-accent bg-accent/10 px-6 py-4 transition-colors hover:bg-accent/20">
		<span>
			<span class="block font-display text-lg font-bold text-accent">🚨 Activar Modo Emergencia</span>
			<span class="block text-sm text-muted-foreground">Pantalla minimalista con lo esencial — frecuencias, checklist, contacto directo. Funciona con conexión limitada.</span>
		</span>
		<span class="shrink-0 font-mono text-2xl text-accent">→</span>
	</a>

	<!-- Banner de estado -->
	<div class="mb-10 flex items-center gap-4 rounded-xl border px-5 py-4 <?php echo $hay_activas ? 'border-accent/40 bg-accent/5' : 'border-border/70 bg-card'; ?>">
		<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full <?php echo $hay_activas ? 'bg-accent/10 ring-1 ring-accent/30' : 'bg-muted/50'; ?>">
			<span class="text-xl"><?php echo $hay_activas ? '🔴' : '🟢'; ?></span>
		</div>
		<div>
			<p class="font-semibold <?php echo $hay_activas ? 'text-accent' : 'text-foreground'; ?>">
				<?php echo $hay_activas ? 'Alerta activa en este momento' : 'Sin alertas activas'; ?>
			</p>
			<p class="text-sm text-muted-foreground">
				<?php echo $hay_activas ? 'Revisa los detalles abajo y sigue las instrucciones de la red.' : 'La red de emergencia está en modo de espera. No hay eventos activos.'; ?>
			</p>
		</div>
	</div>

	<?php if ( $alertas->have_posts() ) : ?>
	<div class="space-y-4">
		<?php while ( $alertas->have_posts() ) : $alertas->the_post();
			$active   = get_post_meta( get_the_ID(), 'active', true ) === '1';
			$nivel    = get_post_meta( get_the_ID(), 'nivel', true ) ?: 'Información';
			$zona     = get_post_meta( get_the_ID(), 'zona', true );
			$frecuencia = get_post_meta( get_the_ID(), 'frecuencia', true );
		?>
		<div class="rounded-2xl border <?php echo $active ? 'border-accent/40 bg-accent/5' : 'border-border/70 bg-card'; ?> p-6">
			<div class="flex flex-wrap items-start justify-between gap-3">
				<div class="flex items-center gap-3">
					<?php if ( $active ) : ?>
					<span class="relative flex h-3 w-3"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-accent opacity-75"></span><span class="relative inline-flex h-3 w-3 rounded-full bg-accent"></span></span>
					<?php endif; ?>
					<h2 class="font-display text-lg font-bold <?php echo $active ? 'text-accent' : ''; ?>"><?php the_title(); ?></h2>
				</div>
				<div class="flex flex-wrap gap-2">
					<span class="inline-flex rounded-full px-2.5 py-1 font-mono text-[11px] font-medium ring-1 <?php echo $active ? 'text-accent bg-accent/10 ring-accent/30' : 'text-muted-foreground bg-muted/30 ring-border/50'; ?>"><?php echo esc_html($nivel); ?></span>
					<?php if ( $zona ) : ?><span class="inline-flex rounded-full px-2.5 py-1 font-mono text-[11px] font-medium ring-1 text-muted-foreground bg-background ring-border/50"><?php echo esc_html($zona); ?></span><?php endif; ?>
				</div>
			</div>
			<?php if ( get_the_content() ) : ?>
			<div class="mt-3 text-sm text-muted-foreground"><?php echo wp_kses_post( get_the_content() ); ?></div>
			<?php endif; ?>
			<div class="mt-4 flex flex-wrap gap-4 text-xs text-muted-foreground">
				<?php if ( $frecuencia ) : ?><span class="font-mono">📡 <?php echo esc_html($frecuencia); ?></span><?php endif; ?>
				<span class="font-mono">🕐 <?php echo esc_html( get_the_date('d M Y · H:i') ); ?> UTC−5</span>
			</div>
		</div>
		<?php endwhile; wp_reset_postdata(); ?>
	</div>

	<?php else : ?>
	<!-- Estado vacío: próximamente -->
	<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/70 bg-card/50 px-6 py-20 text-center">
		<div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-accent/10 ring-1 ring-accent/20">
			<span class="text-4xl">📻</span>
		</div>
		<p class="mb-2 font-display text-xl font-bold">Red de Emergencia HK4RAU</p>
		<p class="max-w-sm text-sm text-muted-foreground">Esta sección publicará alertas y comunicados cuando la red de emergencia esté activa. Actualmente no hay eventos en curso.</p>
		<div class="mt-6 inline-flex items-center gap-2 rounded-full border border-accent/30 bg-accent/5 px-4 py-1.5">
			<span class="h-2 w-2 rounded-full bg-emerald-400"></span>
			<span class="font-mono text-xs text-accent">Red en espera · Sin alertas activas</span>
		</div>
		<div class="mt-10 grid gap-4 sm:grid-cols-3 max-w-2xl w-full text-left">
			<div class="rounded-xl border border-border/50 bg-background p-4">
				<p class="font-mono text-xs text-muted-foreground mb-1">Frecuencia principal</p>
				<p class="font-mono text-lg font-bold text-accent"><?php echo esc_html( rau_get_repetidor_principal()['frequency'] ); ?></p>
				<p class="font-mono text-xs text-muted-foreground">MHz · T100 · −600 kHz</p>
			</div>
			<div class="rounded-xl border border-border/50 bg-background p-4">
				<p class="font-mono text-xs text-muted-foreground mb-1">Canal simplex</p>
				<p class="font-mono text-lg font-bold text-foreground">146.520</p>
				<p class="font-mono text-xs text-muted-foreground">MHz · Llamada FM nacional</p>
			</div>
			<div class="rounded-xl border border-border/50 bg-background p-4">
				<p class="font-mono text-xs text-muted-foreground mb-1">Zello / DMR</p>
				<p class="font-mono text-lg font-bold text-foreground">TG 73241</p>
				<p class="font-mono text-xs text-muted-foreground">Canal HK4RAU</p>
			</div>
		</div>
	</div>
	<?php endif; ?>
</section>
<?php get_footer(); ?>
