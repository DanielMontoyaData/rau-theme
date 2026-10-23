<?php
/**
 * Archivo completo de Eventos (con paginación)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<section class="mx-auto max-w-[80rem] px-4 sm:px-6 py-16">
	<div class="mb-2 flex flex-wrap items-end justify-between gap-4"><h1 class="max-w-2xl font-display text-3xl font-bold sm:text-4xl">Todos los Eventos</h1></div>
	<p class="mb-8 text-sm text-muted-foreground">
		<a href="<?php echo esc_url( home_url( '/calendario.ics' ) ); ?>" class="inline-flex items-center gap-1 font-medium text-primary hover:underline">
			<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3.5 10h17"></path></svg>
			Suscribirse a todos los eventos en Google Calendar
		</a>
	</p>

	<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); $eid = get_the_ID();
			$start = get_post_meta( $eid, 'start', true );
			$ts = $start ? strtotime( $start ) : false;
		?>
		<div class="flex gap-4 rounded-xl border border-border/70 bg-card p-5 transition-colors hover:border-primary/50">
			<div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-lg bg-primary/10 text-primary ring-1 ring-primary/20">
				<span class="font-display text-2xl font-bold leading-none"><?php echo $ts ? esc_html( date_i18n( 'j', $ts ) ) : '—'; ?></span>
				<span class="font-mono text-[10px] uppercase"><?php echo $ts ? esc_html( date_i18n( 'M', $ts ) ) : ''; ?></span>
			</div>
			<div class="min-w-0 flex-1">
				<span class="font-mono text-[10px] uppercase tracking-wider text-accent"><?php echo esc_html( get_post_meta( $eid, 'type', true ) ); ?></span>
				<h3 class="truncate font-semibold"><a href="<?php the_permalink(); ?>" class="hover:text-primary"><?php the_title(); ?></a></h3>
				<?php $freq = get_post_meta( $eid, 'frequency', true ); if ( $freq ) : ?><p class="font-mono text-xs text-muted-foreground"><?php echo esc_html( $freq ); ?></p><?php endif; ?>
				<?php $loc = get_post_meta( $eid, 'location', true ); if ( $loc ) : ?><p class="text-xs text-muted-foreground"><?php echo esc_html( $loc ); ?></p><?php endif; ?>
				<?php if ( $start ) : ?>
					<a href="<?php echo esc_url( rau_google_calendar_link( $eid ) ); ?>" target="_blank" rel="noreferrer" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">+ Google Calendar</a>
				<?php endif; ?>
			</div>
		</div>
		<?php endwhile; else : ?>
			<p class="text-sm text-muted-foreground">Aún no hay eventos publicados.</p>
		<?php endif; ?>
	</div>

	<div class="mt-8"><?php the_posts_pagination( array(
		'prev_text' => '← Anterior',
		'next_text' => 'Siguiente →',
	) ); ?></div>
</section>
<?php get_footer(); ?>
