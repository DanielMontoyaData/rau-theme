<?php
/**
 * Template Name: Junta Directiva
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$board = new WP_Query( array(
	'post_type'      => 'directivo',
	'posts_per_page' => -1,
	'orderby'        => 'meta_value_num',
	'meta_key'       => 'order',
	'order'          => 'ASC',
	'post_status'    => 'publish',
) );
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Institución</p>
	<h1 class="mb-2 max-w-2xl font-display text-3xl font-bold sm:text-4xl">Junta Directiva</h1>
	<p class="mb-10 max-w-2xl text-muted-foreground">Quienes coordinan la RAU y sus áreas de trabajo.</p>

	<?php if ( $board->have_posts() ) : ?>
	<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
		<?php while ( $board->have_posts() ) : $board->the_post();
			$role     = get_post_meta( get_the_ID(), 'role', true );
			$callsign = get_post_meta( get_the_ID(), 'callsign', true );
		?>
		<div class="flex items-center gap-4 rounded-xl border border-border/70 bg-card p-5">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="h-14 w-14 shrink-0 overflow-hidden rounded-full ring-1 ring-primary/20">
					<?php the_post_thumbnail( 'thumbnail', array( 'class' => 'h-full w-full object-cover' ) ); ?>
				</div>
			<?php else : ?>
				<div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/10 font-display text-lg font-bold text-primary ring-1 ring-primary/20"><?php echo esc_html( rau_initials( get_the_title() ) ); ?></div>
			<?php endif; ?>
			<div>
				<p class="font-semibold"><?php the_title(); ?></p>
				<?php if ( $role ) : ?><p class="text-sm text-primary"><?php echo esc_html( $role ); ?></p><?php endif; ?>
				<?php if ( $callsign ) : ?><p class="font-mono text-xs text-muted-foreground"><?php echo esc_html( $callsign ); ?></p><?php endif; ?>
			</div>
		</div>
		<?php endwhile; wp_reset_postdata(); ?>
	</div>

	<?php else : ?>
	<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/70 bg-card/50 px-6 py-24 text-center">
		<div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
			<?php rau_icon( 'users', 'h-8 w-8 text-primary' ); ?>
		</div>
		<p class="mb-2 font-display text-xl font-bold">Aún no hay directivos publicados</p>
		<p class="max-w-sm text-sm text-muted-foreground">Agrégalos en el escritorio → Junta Directiva → Agregar nuevo.</p>
	</div>
	<?php endif; ?>
</section>
<?php get_footer(); ?>
