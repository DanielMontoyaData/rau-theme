<?php
/**
 * Template Name: Libro de Visitas
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$estado = isset( $_GET['libro'] ) ? sanitize_key( $_GET['libro'] ) : '';

$mensajes = array(
	'ok'       => array( 'ok',   '¡Gracias por firmar el Libro de Visitas! Ya apareces en la lista.' ),
	'nombre'   => array( 'err',  'Falta tu nombre.' ),
	'mensaje'  => array( 'err',  'Escribe un mensaje antes de enviar.' ),
	'despacio' => array( 'warn', 'Espera un par de minutos antes de firmar otra vez.' ),
	'error'    => array( 'err',  'No se pudo guardar. Recarga la página e intenta de nuevo.' ),
);

$paged = max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( isset( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1 ) );

$total_q = new WP_Query( array( 'post_type' => 'libro_visitas', 'posts_per_page' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) );
$total   = (int) $total_q->found_posts;

$entradas = new WP_Query( array(
	'post_type'      => 'libro_visitas',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
) );
?>

<div class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">

	<!-- Hero header -->
	<div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
		<div>
			<p class="mb-2 font-mono text-[11px] uppercase tracking-[0.25em] text-primary">Comunidad RAU</p>
			<h1 class="font-display text-3xl font-bold sm:text-4xl">Libro de Visitas</h1>
			<p class="mt-2 max-w-xl text-muted-foreground">Deja tu huella. Un mensaje corto, tu indicativo si tienes uno, y quedas para siempre en la historia de la asociación.</p>
		</div>
		<div class="flex shrink-0 flex-col items-center justify-center rounded-2xl border border-primary/30 bg-primary/5 px-8 py-4 text-center">
			<p class="font-mono text-4xl font-bold text-primary"><?php echo esc_html( $total ); ?></p>
			<p class="mt-1 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Mensajes en total</p>
		</div>
	</div>

	<?php if ( $estado && isset( $mensajes[ $estado ] ) ) :
		[$tipo, $texto] = $mensajes[ $estado ];
		$cls = [ 'ok' => 'border-emerald-400/40 bg-emerald-400/10 text-emerald-400', 'warn' => 'border-amber-400/40 bg-amber-400/10 text-amber-400', 'err' => 'border-rose-400/40 bg-rose-400/10 text-rose-400' ];
	?>
	<div class="mb-8 flex items-center gap-3 rounded-xl border px-5 py-3.5 text-sm font-medium <?php echo esc_attr( $cls[$tipo] ); ?>" role="status">
		<?php if ( $tipo === 'ok' ) : ?>
		<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
		<?php elseif ( $tipo === 'warn' ) : ?>
		<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
		<?php else : ?>
		<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
		<?php endif; ?>
		<?php echo esc_html( $texto ); ?>
	</div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-5">

		<!-- ── FORMULARIO (2/5) ── -->
		<div class="lg:col-span-2">
			<div class="sticky top-24 rounded-2xl border border-border/70 bg-card overflow-hidden">
				<div class="border-b border-border/70 bg-muted/20 px-6 py-4">
					<h2 class="font-display text-lg font-bold">Firmar el libro</h2>
					<p class="mt-0.5 text-xs text-muted-foreground">Solo nombre y mensaje son obligatorios</p>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="p-6 space-y-5">
					<input type="hidden" name="action" value="rau_guestbook">
					<?php wp_nonce_field( 'rau_guestbook', 'rau_guestbook_nonce' ); ?>
					<div class="hidden" aria-hidden="true">
						<label>Sitio web <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label>
					</div>

					<div>
						<label for="lv-nombre" class="mb-1.5 flex items-center gap-1.5 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
							Nombre
							<span class="rounded-full bg-primary/20 px-1.5 py-0.5 text-[10px] font-bold text-primary">REQ</span>
						</label>
						<input type="text" id="lv-nombre" name="nombre" required maxlength="60" placeholder="Tu nombre"
							class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
					</div>

					<div class="grid grid-cols-2 gap-3">
						<div>
							<label for="lv-indicativo" class="mb-1.5 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Indicativo</label>
							<input type="text" id="lv-indicativo" name="indicativo" maxlength="13" placeholder="HK4ABC"
								class="w-full rounded-xl border border-input bg-background px-4 py-3 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors" style="text-transform:uppercase">
						</div>
						<div>
							<label for="lv-ciudad" class="mb-1.5 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Ciudad</label>
							<input type="text" id="lv-ciudad" name="ciudad" maxlength="60" placeholder="Medellín"
								class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
						</div>
					</div>

					<div>
						<label for="lv-mensaje" class="mb-1.5 flex items-center gap-1.5 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
							Mensaje
							<span class="rounded-full bg-primary/20 px-1.5 py-0.5 text-[10px] font-bold text-primary">REQ</span>
						</label>
						<textarea id="lv-mensaje" name="mensaje" required maxlength="400" rows="4" placeholder="¿Qué significa RAU para ti?"
							class="w-full resize-none rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors"></textarea>
					</div>

					<button type="submit"
						class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3.5 text-sm font-bold text-primary-foreground transition-all hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/20 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 ring-offset-background active:scale-[0.98]">
						<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
						Firmar el libro
					</button>

					<p class="text-center text-xs text-muted-foreground">Tu mensaje queda publicado de inmediato y permanece para siempre.</p>
				</form>
			</div>
		</div>

		<!-- ── MENSAJES (3/5) ── -->
		<div class="lg:col-span-3">
			<?php if ( $entradas->have_posts() ) : ?>
			<div class="grid gap-4 sm:grid-cols-2">
				<?php while ( $entradas->have_posts() ) : $entradas->the_post();
					$m          = get_post_meta( get_the_ID() );
					$indicativo = $m['indicativo'][0] ?? '';
					$nombre     = $m['nombre'][0] ?? get_the_title();
					$ciudad     = $m['ciudad'][0] ?? '';
					$colores    = ['hsl(193,85%,27%)','hsl(160,60%,30%)','hsl(262,60%,40%)','hsl(350,75%,45%)','hsl(30,80%,40%)'];
					$color_idx  = ord( ( $indicativo ?: $nombre )[0] ?? 'A' ) % count( $colores );
					$color_bg   = $colores[ $color_idx ];
					$inicial    = strtoupper( substr( $indicativo ?: $nombre, 0, 2 ) );
				?>
				<div class="flex flex-col rounded-2xl border border-border/70 bg-card p-5">
					<p class="mb-4 flex-1 text-sm italic text-muted-foreground">&ldquo;<?php echo esc_html( get_the_content() ); ?>&rdquo;</p>
					<div class="flex items-center gap-3 border-t border-border/50 pt-3">
						<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full font-mono text-xs font-bold text-white" style="background:<?php echo esc_attr( $color_bg ); ?>">
							<?php echo esc_html( $inicial ); ?>
						</div>
						<div class="min-w-0">
							<p class="truncate text-sm font-bold text-foreground"><?php echo esc_html( $nombre ); ?></p>
							<p class="truncate font-mono text-xs text-primary">
								<?php echo esc_html( $indicativo ); ?><?php if ( $indicativo && $ciudad ) echo ' · '; ?><?php echo esc_html( $ciudad ); ?>
							</p>
						</div>
					</div>
				</div>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>

			<?php if ( $entradas->max_num_pages > 1 ) : ?>
			<div class="mt-8 flex items-center justify-center gap-2">
				<?php echo paginate_links( array(
					'base'      => esc_url( get_permalink() ) . '%_%',
					'format'    => '?pg=%#%',
					'total'     => $entradas->max_num_pages,
					'current'   => $paged,
					'prev_text' => '←',
					'next_text' => '→',
					'type'      => 'plain',
				) ); ?>
			</div>
			<?php endif; ?>

			<?php else : ?>
			<div class="flex flex-col items-center justify-center rounded-2xl border border-border/70 bg-card px-6 py-20 text-center">
				<div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl border border-dashed border-border/70 bg-muted/20">
					<svg class="h-7 w-7 text-muted-foreground/30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
				</div>
				<p class="font-display text-base font-semibold">Todavía nadie ha firmado</p>
				<p class="mt-1 text-sm text-muted-foreground">¡Sé el primero en dejar tu mensaje!</p>
			</div>
			<?php endif; ?>
		</div>

	</div>
</div>

<?php get_footer(); ?>
