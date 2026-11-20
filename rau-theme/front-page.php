<?php
/**
 * Página de inicio.
 *
 * 19.61.0 — orden nuevo, pensado para quien llega por primera vez sin estorbar a quien ya es de RAU:
 *   1. Portada: título, dos botones y la próxima actividad (que se vuelve «En vivo ahora» durante la
 *      malla o cuando el canal de YouTube transmite).
 *   2. Qué es RAU: quiénes somos, tres cifras reales, «empieza aquí» en tres pasos y fotos del club.
 *   3. En el aire ahora: propagación por bandas y el receptor del club.
 *   4. Repetidoras.  5. Próximas actividades (con los concursos).  6. Novedades (si hay entradas).
 *   7. Comunidad: quiénes se reportaron hoy, Libro de Visitas, cómo asociarse y Código de Ética.
 * Lo que solo aparece cuando hace falta: la franja de emergencia (operación real con tablero público).
 * Se quitaron: Radio RAU (un dial de juguete), la tarjeta repetida del Libro de Visitas en la portada
 * y la franja suelta del próximo concurso.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

// Solo eventos desde hoy (antes salían los más antiguos del catálogo); los semanales se corren solos.
$events    = new WP_Query( array(
	'post_type'      => 'evento',
	'posts_per_page' => 6,
	'post_status'    => 'publish',
	'meta_key'       => 'start',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_query'     => array( array( 'key' => 'start', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ),
) );
// Todas las repetidoras, la principal (HK4RAU) primero y el resto por nombre.
$repetidores_home = new WP_Query( array( 'post_type' => 'repetidor', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
$repetidores_home->posts = rau_ordenar_repetidoras( $repetidores_home->posts );
$alerts    = new WP_Query( array( 'post_type' => 'alerta', 'posts_per_page' => -1, 'meta_query' => array( array( 'key' => 'active', 'value' => '1' ) ), 'post_status' => 'publish' ) );
$historias = new WP_Query( array( 'post_type' => 'historia', 'posts_per_page' => 4, 'post_status' => 'publish' ) );
$libro_preview = new WP_Query( array( 'post_type' => 'libro_visitas', 'posts_per_page' => 2, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) );
$libro_total   = (int) ( wp_count_posts( 'libro_visitas' )->publish ?? 0 );
// Novedades: solo entradas de los últimos 60 días; si no hay, la sección no aparece.
$novedades = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 3, 'post_status' => 'publish', 'ignore_sticky_posts' => true, 'no_found_rows' => true, 'date_query' => array( array( 'after' => '60 days ago' ) ) ) );

$como_asociarse_id = rau_content_by_section( 'como_asociarse' );
$codigo_etica_id   = rau_content_by_section( 'codigo_etica' );
$url_checkin       = rau_portada_url_plantilla( 'page-checkin.php', '/check-in-de-malla/' );
$url_repetidoras   = get_post_type_archive_link( 'repetidor' );
$emergencia        = rau_portada_emergencia();
$datos             = rau_portada_datos();
$proximo           = $events->posts ? $events->posts[0] : null;
?>

<?php if ( $emergencia ) : ?>
<div class="border-b border-rose-500/50 bg-rose-500/15" role="alert">
	<div class="mx-auto flex max-w-[96rem] flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3 sm:px-6">
		<p class="flex items-center gap-2 text-sm font-semibold text-foreground">
			<span class="relative flex h-2.5 w-2.5 shrink-0"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-500 opacity-75"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-rose-500"></span></span>
			<span>Red de emergencia activa: <?php echo esc_html( $emergencia['nombre'] ); ?><?php if ( $emergencia['zona'] ) : ?> · <?php echo esc_html( $emergencia['zona'] ); ?><?php endif; ?><?php if ( $emergencia['canal'] ) : ?> · <span class="font-mono"><?php echo esc_html( $emergencia['canal'] ); ?></span><?php endif; ?></span>
		</p>
		<a href="<?php echo esc_url( $emergencia['url'] ); ?>" class="inline-flex items-center rounded-md bg-rose-500 px-4 py-2 text-sm font-bold text-white hover:bg-rose-600">Ver tablero público →</a>
	</div>
</div>
<?php endif; ?>

<?php if ( $alerts->have_posts() ) :
	// Antes iba en rojo (el color de alerta) aunque solo anuncie el programa y la frecuencia de contacto.
	// El rojo queda para la franja de emergencia de arriba, que solo aparece cuando de verdad hay una. ?>
<div class="overflow-hidden border-b border-primary/25 bg-primary/5">
	<div class="flex whitespace-nowrap py-2">
		<div class="marquee-track flex shrink-0">
			<?php for ( $rep = 0; $rep < 4; $rep++ ) : $alerts->rewind_posts(); while ( $alerts->have_posts() ) : $alerts->the_post(); ?>
				<span class="mx-8 flex items-center gap-2 font-mono text-xs uppercase tracking-wider text-muted-foreground"<?php echo $rep ? ' aria-hidden="true"' : ''; ?>>
					<svg class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5M19.1 4.9C23 8.8 23 15.1 19.1 19"></path><circle cx="12" cy="12" r="2"></circle></svg>
					<?php the_title(); ?> — <span class="text-foreground"><?php echo esc_html( get_post_meta( get_the_ID(), 'frequency', true ) ); ?></span>
				</span>
			<?php endwhile; endfor; wp_reset_postdata(); ?>
		</div>
	</div>
</div>
<?php endif; ?>

<div class="relative overflow-hidden">
	<div class="absolute inset-0 starfield opacity-60"></div>
	<?php if ( has_post_thumbnail( get_option( 'page_on_front' ) ) ) : ?>
		<div class="absolute inset-0 h-full w-full object-cover opacity-30">
			<?php echo get_the_post_thumbnail( get_option( 'page_on_front' ), 'rau-hero', array( 'class' => 'h-full w-full object-cover', 'fetchpriority' => 'high', 'decoding' => 'async', 'alt' => '' ) ); ?>
		</div>
	<?php else : $img = get_template_directory_uri() . '/assets/images/'; // servida por el propio sitio (antes desde Pexels) ?>
		<img src="<?php echo esc_url( $img . 'hero-torre-1280.webp' ); ?>" srcset="<?php echo esc_url( $img . 'hero-torre-768.webp' ); ?> 768w, <?php echo esc_url( $img . 'hero-torre-1280.webp' ); ?> 1280w" sizes="100vw" width="1280" height="1920" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30" fetchpriority="high" decoding="async">
	<?php endif; ?>
	<div class="absolute inset-0 bg-gradient-to-b from-background/70 via-background/80 to-background"></div>

	<section class="rau-portada relative mx-auto max-w-[96rem] px-4 sm:px-6 flex flex-col justify-center">
		<div class="mb-6 inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-4 py-1.5 w-fit">
			<span class="relative flex h-2 w-2">
				<span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"></span>
				<span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
			</span>
			<span class="font-mono text-xs uppercase tracking-widest text-primary"><?php echo esc_html( get_theme_mod( 'rau_hero_badge', 'Comunidad en el aire · 73' ) ); ?></span>
		</div>
		<h1 class="max-w-4xl font-display text-3xl font-bold leading-[1.05] tracking-tight break-words sm:text-5xl lg:text-7xl">
			<?php echo esc_html( get_theme_mod( 'rau_hero_title_a', 'Radioaficionados' ) ); ?> <span class="text-primary"><?php echo esc_html( get_theme_mod( 'rau_hero_title_b', 'Unidos' ) ); ?></span> <?php echo esc_html( get_theme_mod( 'rau_hero_title_c', 'sin fronteras...' ) ); ?>
		</h1>
		<p class="mt-6 max-w-2xl text-lg text-muted-foreground">
			<?php echo esc_html( get_theme_mod( 'rau_hero_subtitle', 'El hogar digital de la asociación RAU: repetidores, nodos, eventos, recursos técnicos, malla de emergencia y una comunidad que mantiene vivas las ondas.' ) ); ?>
		</p>

		<div class="mt-8 flex flex-wrap gap-3">
			<a href="<?php echo esc_url( home_url( '/herramientas/' ) ); ?>" class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-6 text-sm font-bold text-primary-foreground shadow transition-colors hover:bg-primary/90">Explorar Herramientas →</a>
			<a href="<?php echo esc_url( rau_whatsapp_url() ); ?>" target="_blank" rel="noreferrer" class="inline-flex h-11 items-center justify-center rounded-md border border-border bg-background/60 px-6 text-sm font-medium backdrop-blur transition-colors hover:bg-accent">Únete a la comunidad</a>
		</div>

		<?php
		// Dos tarjetas: la próxima actividad y la licencia. La del Libro de Visitas se quitó porque
		// el libro ya tiene su lugar más abajo. La de la actividad se vuelve «En vivo ahora» sola (ver script).
		$p_start = $proximo ? get_post_meta( $proximo->ID, 'start', true ) : '';
		$p_ts    = $p_start ? strtotime( get_gmt_from_date( $p_start ) . ' UTC' ) : 0;
		?>
		<div class="mt-10 grid max-w-3xl gap-3 sm:grid-cols-2">
			<?php if ( $proximo ) : ?>
			<a id="home-proxima" href="<?php echo esc_url( get_permalink( $proximo ) ); ?>" data-inicio="<?php echo esc_attr( $p_ts ); ?>" data-frec="<?php echo esc_attr( get_post_meta( $proximo->ID, 'frequency', true ) ); ?>" class="rounded-xl border border-border/70 bg-card p-4 transition-colors hover:border-primary/50">
				<p class="home-prox-eti font-mono text-[11px] uppercase tracking-[0.2em] text-primary">Próxima actividad</p>
				<p class="mt-1 text-sm font-semibold"><?php echo esc_html( ucfirst( date_i18n( 'l j \d\e F', strtotime( $p_start ) ) ) ); ?></p>
				<p class="text-sm text-muted-foreground"><?php echo esc_html( get_the_title( $proximo ) ); ?></p>
			</a>
			<?php else : ?>
			<a id="home-proxima" href="<?php echo esc_url( get_post_type_archive_link( 'evento' ) ); ?>" data-inicio="0" class="rounded-xl border border-border/70 bg-card p-4 transition-colors hover:border-primary/50">
				<p class="home-prox-eti font-mono text-[11px] uppercase tracking-[0.2em] text-primary">Calendario</p>
				<p class="mt-1 text-sm text-muted-foreground">Mallas, ruedas y concursos de la red</p>
			</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/como-sacar-la-licencia/' ) ); ?>" class="rounded-xl border border-border/70 bg-card p-4 transition-colors hover:border-primary/50">
				<p class="font-mono text-[11px] uppercase tracking-[0.2em] text-primary">¿Quieres tu licencia?</p>
				<p class="mt-1 text-sm font-semibold">El trámite paso a paso</p>
				<p class="text-sm text-muted-foreground">Examen, pago y costos <?php echo esc_html( current_time( 'Y' ) ); ?></p>
			</a>
		</div>
		<div id="home-envivo" class="mt-3 hidden max-w-3xl flex-wrap items-center gap-2 rounded-xl border border-rose-500/40 bg-rose-500/10 p-3">
			<span class="flex items-center gap-2 pr-2 text-sm font-semibold"><span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-500 opacity-75"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-rose-500"></span></span><span id="home-envivo-txt">En vivo ahora</span></span>
			<a id="home-envivo-yt" href="https://www.youtube.com/@radioaficionadosunidos/live" target="_blank" rel="noopener" class="hidden items-center rounded-md bg-rose-500 px-3 py-1.5 text-sm font-bold text-white hover:bg-rose-600">▶ Ver en YouTube</a>
			<a id="home-envivo-rx" href="<?php echo esc_url( $url_repetidoras ); ?>" class="hidden items-center rounded-md border border-border bg-background/60 px-3 py-1.5 text-sm font-medium hover:bg-accent"></a>
		</div>

		<p class="mt-8 flex items-center gap-3 text-muted-foreground" aria-label="RAU en código Morse">
			<span class="font-mono text-lg tracking-[0.35em] text-primary/80" aria-hidden="true">.-. .- ..-</span>
			<span class="font-mono text-[11px] uppercase tracking-[0.2em]">RAU en Morse</span>
		</p>
	</section>
</div>

<script>
/* «En vivo ahora»: durante la actividad (desde su hora de inicio y por 90 minutos) o cuando el canal
   de YouTube transmite. Se decide en el navegador para que la caché de la página no lo congele. */
(function(){
	var tarjeta=document.getElementById('home-proxima'), caja=document.getElementById('home-envivo'); if(!tarjeta||!caja) return;
	var inicio=(+tarjeta.dataset.inicio||0)*1000, frec=tarjeta.dataset.frec||'', ahora=Date.now();
	function mostrar(tarjetaTambien){ caja.classList.remove('hidden'); caja.classList.add('flex'); if(!tarjetaTambien) return; var e=tarjeta.querySelector('.home-prox-eti'); if(e){ e.textContent='🔴 En el aire ahora'; e.classList.remove('text-primary'); e.classList.add('text-rose-400'); } tarjeta.classList.add('border-rose-500/50'); }
	if(inicio && ahora>=inicio && ahora<inicio+90*60000){
		mostrar(true);
		if(frec){ var rx=document.getElementById('home-envivo-rx'); rx.textContent='📻 Sintoniza '+frec; rx.classList.remove('hidden'); rx.classList.add('inline-flex'); }
	}
	fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>?action=rau_yt_live').then(function(r){ return r.json(); }).then(function(j){
		if(!j||!j.success||!j.data||!j.data.live) return;
		var yt=document.getElementById('home-envivo-yt'); if(j.data.url) yt.href=j.data.url; yt.classList.remove('hidden'); yt.classList.add('inline-flex');
		document.getElementById('home-envivo-txt').textContent='Transmitiendo en vivo ahora';
		mostrar();
	}).catch(function(){});
})();
</script>

<?php
$quienes = trim( (string) get_theme_mod( 'rau_home_quienes', '' ) ) ?: 'RAU, Radioaficionados Unidos, es una asociación de radioaficionados de Colombia. Mantenemos una red de repetidoras, mallas semanales abiertas a cualquier colega con licencia y una red de apoyo para emergencias. Aquí aprendes, sales al aire y ayudas cuando hace falta.';
$fotos = array();
for ( $i = 1; $i <= 3; $i++ ) { $f = (int) get_theme_mod( 'rau_home_foto_' . $i ); if ( $f && wp_attachment_is_image( $f ) ) $fotos[] = $f; }
?>
<section id="que-es-rau" class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12 scroll-mt-20">
	<div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
		<div>
			<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Quiénes somos</p>
			<h2 class="font-display text-3xl font-bold sm:text-4xl">Qué es RAU</h2>
			<p class="mt-4 max-w-xl text-muted-foreground"><?php echo nl2br( esc_html( $quienes ) ); ?></p>
			<dl class="mt-6 grid max-w-xl grid-cols-3 gap-3">
				<div class="rounded-xl border border-border/70 bg-card p-4"><dt class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Repetidoras</dt><dd class="mt-1 font-display text-3xl font-bold text-primary"><?php echo (int) $datos['repetidoras']; ?></dd></div>
				<div class="rounded-xl border border-border/70 bg-card p-4"><dt class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Mallas por semana</dt><dd class="mt-1 font-display text-3xl font-bold text-primary"><?php echo (int) $datos['mallas']; ?></dd></div>
				<div class="rounded-xl border border-border/70 bg-card p-4"><dt class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Colegas reportados en 30 días</dt><dd class="mt-1 font-display text-3xl font-bold text-primary"><?php echo (int) $datos['colegas']; ?></dd></div>
			</dl>
		</div>
		<div class="rounded-2xl border border-primary/30 bg-card p-6 sm:p-8">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">¿Eres nuevo?</p>
			<h3 class="font-display text-2xl font-bold">Empieza aquí</h3>
			<ol class="mt-5 space-y-5">
				<li class="flex gap-4">
					<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/15 font-mono font-bold text-primary ring-1 ring-primary/30">1</span>
					<div><p class="font-semibold">Saca tu licencia</p><p class="text-sm text-muted-foreground"><a href="<?php echo esc_url( home_url( '/como-sacar-la-licencia/' ) ); ?>" class="text-primary hover:underline">El trámite paso a paso</a> y el <a href="<?php echo esc_url( home_url( '/herramientas/examen/' ) ); ?>" class="text-primary hover:underline">simulador del examen</a>.</p></div>
				</li>
				<li class="flex gap-4">
					<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/15 font-mono font-bold text-primary ring-1 ring-primary/30">2</span>
					<div><p class="font-semibold">Programa tu radio</p><p class="text-sm text-muted-foreground">Frecuencia, desplazamiento y tono de <a href="<?php echo esc_url( $url_repetidoras ); ?>" class="text-primary hover:underline">cada repetidora</a>.</p></div>
				</li>
				<li class="flex gap-4">
					<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/15 font-mono font-bold text-primary ring-1 ring-primary/30">3</span>
					<div><p class="font-semibold">Sal al aire</p><p class="text-sm text-muted-foreground"><?php if ( $proximo ) : ?>Entra a <a href="<?php echo esc_url( get_permalink( $proximo ) ); ?>" class="text-primary hover:underline"><?php echo esc_html( get_the_title( $proximo ) ); ?></a> y <?php else : ?>Entra a una malla y <?php endif; ?><a href="<?php echo esc_url( $url_checkin ); ?>" class="text-primary hover:underline">repórtate en el check-in</a>.</p></div>
				</li>
			</ol>
		</div>
	</div>
	<?php if ( $fotos ) : ?>
	<div class="mt-8 grid gap-3 <?php echo count( $fotos ) > 1 ? 'sm:grid-cols-' . count( $fotos ) : ''; ?>">
		<?php foreach ( $fotos as $f ) : ?>
		<figure class="overflow-hidden rounded-xl border border-border/70 bg-card">
			<?php echo wp_get_attachment_image( $f, 'large', false, array( 'class' => 'aspect-[4/3] h-full w-full object-cover', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(min-width: 640px) 33vw, 100vw' ) ); ?>
			<?php $cap = wp_get_attachment_caption( $f ); if ( $cap ) : ?><figcaption class="px-3 py-2 text-xs text-muted-foreground"><?php echo esc_html( $cap ); ?></figcaption><?php endif; ?>
		</figure>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</section>

<section id="en-el-aire" class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">En el aire ahora</p>
	<div class="grid gap-4 lg:grid-cols-2">
		<!-- Gauge de propagación solar — medidor tipo reloj -->
		<div class="rounded-2xl border border-border/70 bg-card">
			<div class="flex h-full flex-col p-6 sm:p-8">
				<div class="mb-4 flex items-center gap-2">
					<svg class="h-4 w-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
					<p class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Clima Espacial · En vivo</p>
				</div>
				<h2 class="mb-5 font-display text-2xl font-bold">Propagación HF</h2>

				<!-- Gauge SVG + métricas -->
				<div class="flex flex-col items-center">
					<!-- SVG Gauge (índice K, 0-9) -->
					<div class="relative w-full max-w-[240px]">
						<svg viewBox="0 0 240 140" xmlns="http://www.w3.org/2000/svg" class="w-full">
							<!-- Arco de fondo -->
							<path d="M 20 120 A 100 100 0 0 1 220 120" fill="none" stroke="hsl(var(--border)/.4)" stroke-width="18" stroke-linecap="round"/>
							<!-- Zonas de color — opacidad adaptada por JS según tema -->
							<path id="gz-green" d="M 20 120 A 100 100 0 0 1 120 20" fill="none" stroke="#16a34a" stroke-width="18" stroke-linecap="round" opacity="0.22"/>
							<path id="gz-amber" d="M 120 20 A 100 100 0 0 1 183 37" fill="none" stroke="#d97706" stroke-width="18" stroke-linecap="round" opacity="0.22"/>
							<path id="gz-red"   d="M 183 37 A 100 100 0 0 1 220 120" fill="none" stroke="#dc2626" stroke-width="18" stroke-linecap="round" opacity="0.22"/>

							<!-- Arco activo (se actualiza por JS) -->
							<path id="gauge-arc" d="M 20 120 A 100 100 0 0 1 20 120" fill="none" stroke="#16a34a" stroke-width="18" stroke-linecap="round" opacity="0.95"/>

							<!-- Aguja -->
							<line id="gauge-needle" x1="120" y1="120" x2="20" y2="120" stroke="#1e3a5f" stroke-width="3" stroke-linecap="round"
								style="transform-origin:120px 120px;transform:rotate(0deg);transition:transform 1s cubic-bezier(.34,1.56,.64,1);"/>
							<circle cx="120" cy="120" r="7" fill="hsl(var(--card))" stroke="#1e3a5f" stroke-width="2.5"/>

							<!-- Etiquetas K -->
							<text x="16"  y="136" font-size="9" fill="#64748b" font-family="monospace" text-anchor="middle">0</text>
							<text x="48"  y="52"  font-size="9" fill="#64748b" font-family="monospace" text-anchor="middle">2</text>
							<text x="120" y="18"  font-size="9" fill="#64748b" font-family="monospace" text-anchor="middle">4</text>
							<text x="192" y="52"  font-size="9" fill="#64748b" font-family="monospace" text-anchor="middle">6</text>
							<text x="224" y="136" font-size="9" fill="#64748b" font-family="monospace" text-anchor="middle">9</text>

							<!-- Valor K central -->
							<text id="gauge-k-val" x="120" y="98" font-size="28" font-weight="700" fill="#1e3a5f" font-family="'Space Grotesk',sans-serif" text-anchor="middle">—</text>
							<text x="120" y="113" font-size="9" fill="#64748b" font-family="monospace" text-anchor="middle">ÍNDICE K</text>
						</svg>

						<!-- Etiqueta de estado bajo el gauge -->
						<p id="gauge-estado" class="mt-1 text-center font-display text-lg font-bold text-muted-foreground">Cargando…</p>
						<p id="gauge-desc"   class="mt-0.5 text-center text-xs text-muted-foreground">—</p>
						<ul id="gauge-bandas" class="mt-3 flex flex-wrap justify-center gap-1.5" aria-label="Estado de las bandas"></ul>
					</div>

					<!-- Métricas SFI y A -->
					<div class="mt-5 grid w-full grid-cols-2 gap-3">
						<div class="flex flex-col items-center rounded-xl border border-border/60 bg-background/40 py-3">
							<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Flujo Solar SFI</p>
							<p id="hp-sfi" class="mt-1 font-mono text-2xl font-bold text-amber-400">—</p>
							<p class="font-mono text-[10px] text-muted-foreground">óptimo &gt; 150</p>
						</div>
						<div class="flex flex-col items-center rounded-xl border border-border/60 bg-background/40 py-3">
							<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Índice A</p>
							<p id="hp-a" class="mt-1 font-mono text-2xl font-bold text-foreground">—</p>
							<p class="font-mono text-[10px] text-muted-foreground">bajo = mejor</p>
						</div>
					</div>
				</div>

				<a href="<?php echo esc_url( home_url('/herramientas/') ); ?>" class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
					Panel completo
					<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
				</a>
			</div>
		</div>

		<!-- Widget SDR HK4RAU -->
		<div class="rounded-2xl border border-border/70 bg-card">
			<div class="flex h-full flex-col p-6 sm:p-8">

				<!-- Encabezado -->
				<div class="mb-5 flex items-start justify-between gap-4">
					<div>
						<div class="mb-2 flex items-center gap-2">
							<svg class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h2M4 12a8 8 0 0 1 8-8M12 4V2M20 12h2M12 20v2M4.93 4.93l1.41 1.41M17.66 6.34l1.41-1.41M6.34 17.66l-1.41 1.41M17.66 17.66l1.41 1.41M12 12a4 4 0 0 1 0-8M8 12a4 4 0 0 0 8 0"/><circle cx="12" cy="12" r="2"/></svg>
							<p class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Receptor SDR · <?php echo esc_html( rau_sdr_lugar() ); ?></p>
						</div>
						<h2 class="font-display text-2xl font-bold">KiwiSDR HK4RAU</h2>
						<p class="mt-1 text-sm text-muted-foreground"><?php echo esc_html( rau_sdr_lugar() ); ?>, Colombia · 0–30 MHz</p>
					</div>
					<!-- Indicador de estado -->
					<div id="sdr-status-badge" class="shrink-0 inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-background px-3 py-1.5">
						<span class="h-2 w-2 rounded-full bg-muted animate-pulse"></span>
						<span class="font-mono text-xs text-muted-foreground">Verificando…</span>
					</div>
				</div>

				<!-- Ocupación real por hora (antes había aquí un espectro dibujado a mano, "estético") -->
				<div class="mb-5 rounded-xl border border-border/60 bg-background p-3">
					<div class="mb-2 flex items-center justify-between font-mono text-[10px] text-muted-foreground"><span>CANALES LIBRES POR HORA</span><span>hora de Colombia</span></div>
					<div id="sdr-horas" class="flex h-16 items-end gap-0.5" aria-label="Canales libres por hora"></div>
					<p id="sdr-horas-nota" class="mt-2 font-mono text-[10px] text-muted-foreground">Recolectando datos…</p>
				</div>

				<!-- Info del receptor -->
				<div class="mb-5 grid grid-cols-3 gap-2 text-center">
					<div class="rounded-lg border border-border/50 bg-background/50 py-2">
						<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Cobertura</p>
						<p class="mt-0.5 font-mono text-sm font-bold text-primary">0–30 MHz</p>
					</div>
					<div class="rounded-lg border border-border/50 bg-background/50 py-2">
						<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Modos</p>
						<p class="mt-0.5 font-mono text-sm font-bold text-foreground">AM SSB CW</p>
					</div>
					<div class="rounded-lg border border-border/50 bg-background/50 py-2">
						<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Libres</p>
						<p id="sdr-users" class="mt-0.5 font-mono text-sm font-bold text-foreground">—</p>
					</div>
				</div>

				<p class="mb-4 flex flex-wrap items-center gap-1.5 font-mono text-[11px] text-muted-foreground">
					<span class="uppercase tracking-wider text-muted-foreground">Decodifica:</span>
					<?php foreach ( array( 'FT8/FT4', 'CW', 'JS8', 'RTTY', 'PSK31', 'SSTV' ) as $modo_dec ) : ?><span class="rounded-full border border-border/60 bg-background/50 px-2 py-0.5 text-primary"><?php echo esc_html( $modo_dec ); ?></span><?php endforeach; ?>
				</p>

				<a id="sdr-abrir" href="<?php echo esc_url( home_url( '/receptor/' ) ); ?>"
					class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90">
					Abrir el receptor
				</a>

				<script>
				(function(){
					var AJAX='<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
					var badge=document.getElementById('sdr-status-badge'), users=document.getElementById('sdr-users'), abrir=document.getElementById('sdr-abrir');
					/* Si está lleno, el botón lo dice en vez de prometer un receptor que no va a abrir (19.61.0) */
					var lleno=false, mejorHora=null;
					function boton(){ if(!lleno) return; abrir.textContent='Lleno ahora'+(mejorHora!=null?' · suele haber cupo hacia las '+mejorHora+' h':' · inténtalo en un rato'); abrir.classList.remove('bg-primary','text-primary-foreground','hover:bg-primary/90'); abrir.classList.add('border','border-amber-500/50','bg-amber-500/10','text-amber-300'); }
					function pinta(color, texto){ badge.innerHTML='<span style="width:8px;height:8px;border-radius:50%;background:'+color+';display:inline-block;"></span><span style="font-family:monospace;font-size:0.75rem;color:'+color+';">'+texto+'</span>'; }
					fetch(AJAX+'?action=rau_sdr_status', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
						if(!j.success){ pinta('#f43f5e','Fuera de línea'); users.textContent='—'; return; }
						var d=j.data;
						users.textContent=d.libres==null?'—':(d.libres+' de '+d.max);
						if(d.libres===0){ pinta('#f59e0b','Lleno'); lleno=true; boton(); } else pinta('#22c55e','Canal libre');
					}).catch(function(e){ rauErr(e); pinta('#f59e0b','Sin datos'); users.textContent='—'; });
					fetch(AJAX+'?action=rau_sdr_historial', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
						if(!j.success) return;
						var hs=j.data.horas, max=Math.max(1, Math.max.apply(null, hs.map(function(h){ return h.libres||0; })));
						var ahora=new Date().toLocaleString('en-US',{timeZone:'America/Bogota',hour:'numeric',hour12:false})|0;
						document.getElementById('sdr-horas').innerHTML=hs.map(function(h){
							return '<div title="'+h.hora+' h" class="flex-1 rounded-t '+(h.hora===ahora?'bg-accent':'bg-primary/60')+'" style="height:'+(h.libres==null?0:Math.max(6,Math.round(100*h.libres/max)))+'%"></div>';
						}).join('');
						var nota=document.getElementById('sdr-horas-nota');
						if(j.data.muestras<36){ nota.textContent='Recolectando datos ('+j.data.muestras+' mediciones).'; return; }
						var mejor=hs.filter(function(h){ return h.libres!=null; }).sort(function(a,b){ return b.libres-a.libres; })[0];
						nota.textContent=mejor ? 'Más fácil conseguir canal hacia las '+mejor.hora+' h.' : ''; if(mejor){ mejorHora=mejor.hora; boton(); }
					}).catch(function(e){ rauErr(e); });
				})();
				</script>
			</div>
		</div>
	</div>
		<script>
		(function(){
			/* Convierte índice K (0-9) en ángulo del gauge (0° = izquierda = K0, 180° = derecha = K9) */
			function kToAngle(k){ return Math.min(k,9) * 20; } /* 0-9 → 0-180° */

			/* Calcula el punto final del arco SVG dado un ángulo en grados (0 = izq, 180 = der) */
			function arcPoint(deg){
				var rad = (deg - 180) * Math.PI / 180;
				return { x: 120 + 100 * Math.cos(rad), y: 120 + 100 * Math.sin(rad) };
			}

			function updateGauge(k, sfi, aindex){
				var angle = kToAngle(k);
				var p = arcPoint(angle);
				var largeArc = angle > 90 ? 1 : 0;
				var isLight = document.documentElement.getAttribute('data-theme') === 'light';

				/* Colores según tema */
				var cGreen = isLight ? '#15803d' : '#22c55e';
				var cAmber = isLight ? '#b45309' : '#f59e0b';
				var cRed   = isLight ? '#b91c1c' : '#ef4444';
				var cNeedle= isLight ? '#1e3a5f' : '#e2e8f0';

				/* Opacidad de zonas de fondo según tema */
				var zOp = isLight ? '0.35' : '0.22';
				var gz = document.getElementById('gz-green'); if(gz) gz.setAttribute('opacity', zOp);
				var ga = document.getElementById('gz-amber'); if(ga) ga.setAttribute('opacity', zOp);
				var gr = document.getElementById('gz-red');   if(gr) gr.setAttribute('opacity', zOp);

				/* Arco activo */
				var arc = document.getElementById('gauge-arc');
				if(arc){
					if(angle < 1){
						arc.setAttribute('d','M 20 120 A 100 100 0 0 1 20 120');
					} else {
						arc.setAttribute('d','M 20 120 A 100 100 0 '+largeArc+' 1 '+p.x.toFixed(1)+' '+p.y.toFixed(1));
					}
					var arcColor = k<=3 ? cGreen : k===4 ? cAmber : cRed;
					arc.setAttribute('stroke', arcColor);
					arc.setAttribute('opacity', isLight ? '1' : '0.95');
				}

				/* Aguja */
				var needle = document.getElementById('gauge-needle');
				if(needle){ needle.style.transform='rotate('+(angle)+'deg)'; needle.setAttribute('stroke', cNeedle); }
				var hub = needle ? needle.nextElementSibling : null;
				if(hub){ hub.setAttribute('stroke', cNeedle); }

				/* Valor K */
				var kval = document.getElementById('gauge-k-val');
				if(kval){
					kval.textContent = k;
					kval.setAttribute('fill', k<=3 ? cGreen : k===4 ? cAmber : cRed);
				}

				/* Estado (19.61.0): el índice K solo describe el campo magnético. Antes decía «Excelente ·
				   Condiciones óptimas» con 10 m cerrada; ahora la etiqueta habla del campo y las bandas
				   abiertas y cerradas se listan aparte (pintarBandas). */
				var estado = document.getElementById('gauge-estado');
				var desc   = document.getElementById('gauge-desc');
				if(estado && desc){
					if(k<=2)       { estado.textContent='Campo tranquilo'; estado.style.color=cGreen; desc.textContent='Bueno para HF · SFI '+sfi; }
					else if(k===3) { estado.textContent='Campo inestable'; estado.style.color=isLight?'#0e7490':'hsl(190 95% 55%)'; desc.textContent='HF normal, algo de ruido · SFI '+sfi; }
					else if(k===4) { estado.textContent='Perturbado';      estado.style.color=cAmber; desc.textContent='HF degradado · tormenta posible'; }
					else           { estado.textContent='Tormenta';        estado.style.color=cRed;   desc.textContent='HF muy degradado · K '+k; }
				}

				/* Métricas */
				var sfiEl = document.getElementById('hp-sfi'); if(sfiEl) sfiEl.textContent = sfi||'—';
				var aEl   = document.getElementById('hp-a');   if(aEl)   aEl.textContent   = aindex||'—';
			}

			/* Bandas abiertas y cerradas según HamQSL, de día o de noche según la hora de Colombia */
			function pintarBandas(bandas){
				var ul=document.getElementById('gauge-bandas'); if(!ul || !Array.isArray(bandas)) return;
				var h=new Date().toLocaleString('en-US',{timeZone:'America/Bogota',hour:'numeric',hour12:false})|0, dia=h>=6 && h<18;
				var TXT={Good:['abierta','border-emerald-500/40 bg-emerald-500/10 text-emerald-400'],Fair:['regular','border-amber-500/40 bg-amber-500/10 text-amber-400'],Poor:['cerrada','border-rose-500/40 bg-rose-500/10 text-rose-400']};
				ul.innerHTML=bandas.map(function(b){ var e=TXT[dia?b.day:b.night]; if(!e) return '';
					return '<li class="rounded-full border px-2.5 py-1 font-mono text-[11px] '+e[1]+'">'+String(b.name).replace('m-','–')+' '+e[0]+'</li>'; }).join('');
							}

			fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_solar',{cache:'no-store'})
			.then(function(r){return r.json();})
			.then(function(json){
				if(!json.success) throw new Error();
				var d=json.data;
				// parseInt(...)||0 convertía "HamQSL no reportó" en K=0 y SFI=0, y con eso el
				// medidor anunciaba "Buena · Propagación normal · SFI=0": afirmaba condiciones
				// buenas a partir de un dato que nunca llegó. Sin lectura no hay veredicto.
				var k=parseInt(d.kindex,10), sfi=parseInt(d.sfi,10);
				if(isNaN(k)||isNaN(sfi)) throw new Error('sin lectura solar');
				updateGauge(k, sfi, d.aindex); pintarBandas(d.bands);
			})
			.catch(function(e){ rauErr(e);
				var e=document.getElementById('gauge-estado'); if(e) e.textContent='Sin datos';
				var d2=document.getElementById('gauge-desc'); if(d2) d2.textContent='No se pudo cargar la información solar';
			});
		})();
		</script>
</section>

<section id="repetidores" class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Red de Repetidores</p>
	<div class="mb-8 flex flex-wrap items-end justify-between gap-4"><h2 class="max-w-2xl font-display text-3xl font-bold sm:text-4xl">Repetidores</h2><a href="<?php echo esc_url( get_post_type_archive_link( 'repetidor' ) ); ?>" class="text-sm font-medium text-primary hover:underline">Ver directorio completo →</a></div>
	<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
		<?php if ( ! $repetidores_home->have_posts() ) : ?>
			<p class="text-sm text-muted-foreground"><?php echo current_user_can( 'edit_posts' ) ? 'Aún no hay repetidores publicados. Agrégalos en el escritorio → Repetidores.' : 'Pronto publicaremos aquí los repetidores de la red.'; ?></p>
		<?php else : $rep_idx = 0; while ( $repetidores_home->have_posts() ) : $repetidores_home->the_post(); $rid = get_the_ID();
			$status = get_post_meta( $rid, 'status', true ) ?: 'activo';
		?>
		<div class="rau-animar rounded-xl border border-border/70 bg-card p-5" style="transition-delay:<?php echo esc_attr( $rep_idx++ * 80 ); ?>ms">
			<div class="mb-2 flex items-start justify-between gap-2">
				<h3 class="font-semibold"><?php the_title(); ?></h3>
				<span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium capitalize ring-1 <?php echo esc_attr( rau_status_classes( $status ) ); ?>"><?php echo esc_html( $status ); ?></span>
			</div>
			<?php $callsign = get_post_meta( $rid, 'callsign', true ); $tono = get_post_meta( $rid, 'tone', true ); ?>
			<p class="font-mono text-xs <?php echo rau_indicativo_confirmado( $callsign ) ? 'text-primary' : 'text-muted-foreground'; ?>"><?php echo esc_html( rau_indicativo_confirmado( $callsign ) ? $callsign : 'Indicativo por confirmar' ); ?> · <?php echo esc_html( get_post_meta( $rid, 'band', true ) ); ?></p>
			<p class="mt-2 font-mono text-sm"><?php echo esc_html( get_post_meta( $rid, 'frequency', true ) ); ?> <span class="text-xs text-muted-foreground"><?php echo esc_html( get_post_meta( $rid, 'offset', true ) ); ?><?php if ( $tono ) : ?> · tono <?php echo esc_html( $tono ); ?><?php endif; ?></span></p>
			<p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"><svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1116 0z"></path></svg><?php echo esc_html( get_post_meta( $rid, 'location', true ) ); ?></p>
		</div>
		<?php endwhile; wp_reset_postdata(); endif; ?>
	</div>
</section>


<?php
// Próximas actividades: los eventos y los dos concursos que vienen, por fecha. Antes el concurso iba
// en una franja suelta y, con un solo evento publicado, la sección se veía casi vacía.
$actividades = array();
foreach ( $events->posts as $ev ) {
	$st = get_post_meta( $ev->ID, 'start', true );
	$actividades[] = array( 'ev' => $ev, 'ts' => $st ? strtotime( $st ) : 0 );
}
foreach ( rau_portada_concursos( 2 ) as $c ) $actividades[] = array( 'concurso' => $c['nombre'], 'ts' => $c['ts'] );
usort( $actividades, function( $a, $b ) { return $a['ts'] - $b['ts']; } );
$actividades = array_slice( $actividades, 0, 6 );
$ics       = home_url( '/calendario.ics' );
$ics_gcal  = 'https://calendar.google.com/calendar/r?cid=' . rawurlencode( preg_replace( '#^https?://#', 'webcal://', $ics ) );
?>
<section id="eventos" class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Calendario · Mallas · Concursos</p>
	<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
		<h2 class="max-w-2xl font-display text-3xl font-bold sm:text-4xl">Próximas actividades</h2>
		<div class="flex flex-wrap items-center gap-x-4 gap-y-2">
			<a href="<?php echo esc_url( $ics_gcal ); ?>" target="_blank" rel="noopener" class="inline-flex h-10 items-center gap-2 rounded-md border border-primary/40 bg-primary/10 px-4 text-sm font-medium text-primary hover:bg-primary/20">
				<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5" width="17" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3.5 10h17M12 13v5M9.5 15.5h5"></path></svg>
				Agregar a mi calendario
			</a>
			<a href="<?php echo esc_url( preg_replace( '#^https?://#', 'webcal://', $ics ), array( 'webcal', 'https', 'http' ) ); ?>" class="text-xs text-muted-foreground hover:text-primary hover:underline" title="Para el calendario del iPhone, Mac u Outlook">iPhone / Outlook</a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'evento' ) ); ?>" class="text-sm font-medium text-primary hover:underline">Ver todas →</a>
		</div>
	</div>
	<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
		<?php if ( ! $actividades ) : ?>
			<p class="text-sm text-muted-foreground">No hay actividades programadas por ahora. Síguenos en el grupo de WhatsApp para enterarte de la próxima.</p>
		<?php endif; ?>
		<?php foreach ( $actividades as $idx => $act ) : $ts = $act['ts']; ?>
		<?php if ( isset( $act['concurso'] ) ) : ?>
		<a href="<?php echo esc_url( home_url( '/herramientas/concursos/' ) ); ?>" class="rau-animar flex gap-4 rounded-xl border border-border/70 bg-card p-5 transition-colors hover:border-primary/50" style="transition-delay:<?php echo esc_attr( $idx * 80 ); ?>ms">
			<div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 ring-1 ring-amber-500/20">
				<span class="font-display text-2xl font-bold leading-none"><?php echo esc_html( gmdate( 'j', $ts ) ); ?></span>
				<span class="font-mono text-[10px] uppercase"><?php echo esc_html( date_i18n( 'M', $ts ) ); ?></span>
			</div>
			<div class="min-w-0 flex-1">
				<span class="font-mono text-[10px] uppercase tracking-wider text-amber-400">Concurso · fin de semana</span>
				<h3 class="font-semibold"><?php echo esc_html( $act['concurso'] ); ?></h3>
				<p class="home-faltan font-mono text-xs text-muted-foreground" data-ts="<?php echo esc_attr( $ts ); ?>"></p>
			</div>
		</a>
		<?php else : $eid = $act['ev']->ID; $start = get_post_meta( $eid, 'start', true ); ?>
		<div class="rau-animar flex gap-4 rounded-xl border border-border/70 bg-card p-5" style="transition-delay:<?php echo esc_attr( $idx * 80 ); ?>ms">
			<div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-lg bg-primary/10 text-primary ring-1 ring-primary/20">
				<span class="font-display text-2xl font-bold leading-none"><?php echo $ts ? esc_html( date_i18n( 'j', $ts ) ) : '—'; ?></span>
				<span class="font-mono text-[10px] uppercase"><?php echo $ts ? esc_html( date_i18n( 'M', $ts ) ) : ''; ?></span>
			</div>
			<div class="min-w-0 flex-1">
				<span class="font-mono text-[10px] uppercase tracking-wider text-primary"><?php echo esc_html( get_post_meta( $eid, 'type', true ) ); ?><?php echo 'semanal' === get_post_meta( $eid, 'recurrencia', true ) ? ' · cada semana' : ''; ?></span>
				<h3 class="font-semibold"><a href="<?php echo esc_url( get_permalink( $eid ) ); ?>" class="hover:text-primary"><?php echo esc_html( get_the_title( $eid ) ); ?></a></h3>
				<?php $freq = get_post_meta( $eid, 'frequency', true ); if ( $freq ) : ?><p class="font-mono text-xs text-muted-foreground"><?php echo esc_html( $freq ); ?></p><?php endif; ?>
				<?php $loc = get_post_meta( $eid, 'location', true ); if ( $loc ) : ?><p class="flex items-center gap-1 text-xs text-muted-foreground"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1116 0z"></path></svg><?php echo esc_html( $loc ); ?></p><?php endif; ?>
				<?php if ( $start ) : ?>
					<a href="<?php echo esc_url( rau_google_calendar_link( $eid ) ); ?>" target="_blank" rel="noreferrer" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">+ Agregar este</a>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<script>
	(function(){ document.querySelectorAll('.home-faltan').forEach(function(p){ var d=Math.ceil((p.dataset.ts*1000-Date.now())/86400000); p.textContent=d<=0?'¡Es este fin de semana!':d===1?'Empieza mañana':'Faltan '+d+' días'; }); })();
	</script>
</section>

<?php if ( $novedades->have_posts() ) : ?>
<section id="novedades" class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Del club</p>
	<div class="mb-6 flex flex-wrap items-end justify-between gap-4"><h2 class="font-display text-3xl font-bold sm:text-4xl">Novedades</h2><?php $blog = get_option( 'page_for_posts' ); if ( $blog ) : ?><a href="<?php echo esc_url( get_permalink( $blog ) ); ?>" class="text-sm font-medium text-primary hover:underline">Ver todas →</a><?php endif; ?></div>
	<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
		<?php while ( $novedades->have_posts() ) : $novedades->the_post(); ?>
		<a href="<?php the_permalink(); ?>" class="group overflow-hidden rounded-xl border border-border/70 bg-card transition-colors hover:border-primary/50">
			<?php if ( has_post_thumbnail() ) : ?><?php the_post_thumbnail( 'medium_large', array( 'class' => 'aspect-[16/9] w-full object-cover', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); ?><?php endif; ?>
			<div class="p-5">
				<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground"><?php echo esc_html( get_the_date() ); ?></p>
				<h3 class="mt-1 font-semibold group-hover:text-primary"><?php the_title(); ?></h3>
				<p class="mt-2 line-clamp-3 text-sm text-muted-foreground"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
			</div>
		</a>
		<?php endwhile; wp_reset_postdata(); ?>
	</div>
</section>
<?php endif; ?>

<div style="background: hsl(var(--card) / 0.35);">
<section id="comunidad" class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Comunidad</p>
	<h2 class="mb-6 font-display text-3xl font-bold sm:text-4xl">La gente de RAU</h2>

	<?php // Quiénes se reportaron hoy: se pide al cargar (la caché de la página lo dejaría viejo) ?>
	<div id="home-hoy" class="mb-6 hidden flex-wrap items-center gap-x-3 gap-y-2 rounded-xl border border-border/70 bg-card px-5 py-4">
		<p class="text-sm"><strong id="home-hoy-n" class="text-primary"></strong></p>
		<ul id="home-hoy-lista" class="flex flex-wrap gap-1.5"></ul>
		<a href="<?php echo esc_url( $url_checkin ); ?>" class="ml-auto text-sm font-medium text-primary hover:underline">Repórtate tú también →</a>
	</div>
	<script>
	(function(){
		var caja=document.getElementById('home-hoy'); if(!caja) return;
		fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>?action=rau_checkin_hoy').then(function(r){ return r.json(); }).then(function(j){
			if(!j||!j.success) return; var d=j.data;
			function esc(t){ return String(t||'').replace(/[&<>"]/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
			document.getElementById('home-hoy-n').textContent = d.total ? (d.total===1?'Hoy se reportó 1 colega:':'Hoy se reportaron '+d.total+' colegas:') : 'Nadie se ha reportado hoy todavía. ¿Eres el primero?';
			document.getElementById('home-hoy-lista').innerHTML=d.lista.map(function(x){ return '<li class="rounded-full border border-border/60 bg-background/60 px-2.5 py-1 font-mono text-xs"'+(x.c?' title="'+esc(x.c)+'"':'')+'>'+esc(x.i)+'</li>'; }).join('')+(d.total>d.lista.length?'<li class="px-1 py-1 font-mono text-xs text-muted-foreground">+'+(d.total-d.lista.length)+'</li>':'');
			caja.classList.remove('hidden'); caja.classList.add('flex');
		}).catch(function(){});
	})();
	</script>

	<?php // Con una sola historia la sección parecía abandonada; vuelve a aparecer con dos o más.
	if ( $historias->post_count >= 2 ) : ?>
	<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
		<?php $his_idx = 0; while ( $historias->have_posts() ) : $historias->the_post(); $autor = get_post_meta( get_the_ID(), 'autor', true ) ?: get_the_title(); ?>
		<div class="rau-animar rounded-xl border border-border/70 bg-card p-5" style="transition-delay:<?php echo esc_attr( $his_idx++ * 80 ); ?>ms">
			<p class="text-sm italic text-muted-foreground">&ldquo;<?php echo wp_kses_post( get_the_content() ); ?>&rdquo;</p>
			<div class="mt-4 flex items-center gap-3 border-t border-border/50 pt-3">
				<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 font-mono text-xs font-bold text-primary ring-1 ring-primary/20"><?php echo esc_html( rau_initials( preg_replace( '/,.*/', '', $autor ) ) ); ?></div>
				<div><p class="text-sm font-semibold"><?php echo esc_html( $autor ); ?></p><p class="text-xs text-muted-foreground"><?php echo esc_html( get_post_meta( get_the_ID(), 'rol', true ) ); ?></p></div>
			</div>
		</div>
		<?php endwhile; wp_reset_postdata(); ?>
	</div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-2">
		<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
			<div class="mb-5 flex flex-wrap items-end justify-between gap-4">
				<div>
					<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Deja tu huella</p>
					<h3 class="font-display text-2xl font-bold">Libro de Visitas</h3>
					<p class="mt-1 text-sm text-muted-foreground"><?php echo esc_html( $libro_total ); ?> mensaje<?php echo 1 === $libro_total ? '' : 's'; ?> de la comunidad.</p>
				</div>
				<a href="<?php echo esc_url( home_url( '/libro-de-visitas/' ) ); ?>" class="inline-flex h-10 shrink-0 items-center justify-center rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">✍️ Firmar el libro</a>
			</div>
			<?php if ( $libro_preview->have_posts() ) : ?>
			<div class="space-y-3">
				<?php while ( $libro_preview->have_posts() ) : $libro_preview->the_post();
					$lv_indicativo = get_post_meta( get_the_ID(), 'indicativo', true );
					$lv_nombre     = get_post_meta( get_the_ID(), 'nombre', true ) ?: get_the_title();
				?>
				<div class="rounded-xl border border-border/70 bg-background p-4">
					<p class="text-sm italic text-muted-foreground">&ldquo;<?php echo esc_html( wp_trim_words( get_the_content(), 22 ) ); ?>&rdquo;</p>
					<div class="mt-3 flex items-center gap-3">
						<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 font-mono text-[11px] font-bold text-primary ring-1 ring-primary/20"><?php echo esc_html( rau_initials( $lv_indicativo ?: $lv_nombre ) ); ?></div>
						<p class="text-sm"><span class="font-semibold"><?php echo esc_html( $lv_nombre ); ?></span><?php if ( $lv_indicativo ) : ?> <span class="font-mono text-xs text-primary"><?php echo esc_html( $lv_indicativo ); ?></span><?php endif; ?> <span class="text-xs text-muted-foreground">· hace <?php echo esc_html( human_time_diff( get_post_time( 'U', true ), time() ) ); ?></span></p>
					</div>
				</div>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
			<?php else : ?>
				<p class="text-sm text-muted-foreground">Todavía nadie ha firmado. ¡Sé el primero!</p>
			<?php endif; ?>
		</div>

		<div class="overflow-hidden rounded-2xl border border-primary/30 bg-gradient-to-br from-primary/15 via-card to-card p-6 sm:p-8">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Cómo asociarse</p>
			<h3 class="font-display text-2xl font-bold">Únete a RAU</h3>
			<?php if ( $como_asociarse_id ) : ?><div class="mt-3 text-sm text-muted-foreground"><?php echo apply_filters( 'the_content', get_post_field( 'post_content', $como_asociarse_id ) ); ?></div><?php endif; ?>
			<p class="mt-4 text-sm font-medium">¿Tienes licencia activa, la tienes vencida o quieres sacarla por primera vez?</p>
			<ul class="mt-3 space-y-2 text-sm">
				<li><a href="<?php echo esc_url( home_url( '/como-sacar-la-licencia/' ) ); ?>" class="text-primary hover:underline">🪪 Cómo sacar la licencia, paso a paso</a></li>
				<li><a href="<?php echo esc_url( home_url( '/herramientas/examen/' ) ); ?>" class="text-primary hover:underline">📝 Practica con el simulador de examen</a></li>
				<li><a href="<?php echo esc_url( home_url( '/herramientas/licencia/' ) ); ?>" class="text-primary hover:underline">📅 Recordatorio de vencimiento de la licencia</a></li>
				<?php if ( $codigo_etica_id ) : ?><li><a href="<?php echo esc_url( get_permalink( $codigo_etica_id ) ); ?>" class="text-primary hover:underline">📜 <?php echo esc_html( get_the_title( $codigo_etica_id ) ); ?></a></li><?php endif; ?>
			</ul>
			<a href="<?php echo esc_url( rau_whatsapp_url() ); ?>" target="_blank" rel="noreferrer" class="mt-6 inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-6 text-sm font-bold text-primary-foreground shadow transition-colors hover:bg-primary/90">
				<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"></path></svg> Escríbenos por WhatsApp
			</a>
		</div>
	</div>
</section>
</div>

<?php get_footer(); ?>
