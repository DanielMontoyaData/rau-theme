<?php
/**
 * Página de inicio — traducción literal de pages/HomePage.jsx
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

// La sección se titula "Próximas actividades", pero la consulta no tenía filtro de fecha: como
// ordena por 'start' ascendente, lo que pintaba eran los eventos MÁS ANTIGUOS del catálogo. En
// septiembre la portada seguía anunciando actividades de julio. Además, con posts_per_page => -1
// la rejilla crecía sin límite con cada evento publicado.
// Se usa el mismo patrón de meta_query que ya emplea el boletín en functions.php, para no tener
// dos formas distintas de preguntar lo mismo. La comparación es contra el día de hoy, no contra
// el instante actual, para que un evento de esta misma tarde siga apareciendo por la mañana.
$events    = new WP_Query( array(
	'post_type'      => 'evento',
	'posts_per_page' => 6,
	'post_status'    => 'publish',
	'meta_key'       => 'start',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_query'     => array( array( 'key' => 'start', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ),
) );
$repetidores_home = new WP_Query( array( 'post_type' => 'repetidor', 'posts_per_page' => 4, 'post_status' => 'publish' ) );
$alerts    = new WP_Query( array( 'post_type' => 'alerta', 'posts_per_page' => -1, 'meta_query' => array( array( 'key' => 'active', 'value' => '1' ) ), 'post_status' => 'publish' ) );
$historias = new WP_Query( array( 'post_type' => 'historia', 'posts_per_page' => 4, 'post_status' => 'publish' ) );
$libro_preview = new WP_Query( array( 'post_type' => 'libro_visitas', 'posts_per_page' => 3, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) );
$libro_total_q = new WP_Query( array( 'post_type' => 'libro_visitas', 'posts_per_page' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) );
$libro_total   = (int) $libro_total_q->found_posts;

$como_asociarse_id = rau_content_by_section( 'como_asociarse' );
?>

<?php if ( $alerts->have_posts() ) : ?>
<div class="overflow-hidden border-b border-accent/40 bg-accent/10">
	<div class="flex whitespace-nowrap py-2">
		<div class="marquee-track flex shrink-0">
			<?php for ( $rep = 0; $rep < 4; $rep++ ) : $alerts->rewind_posts(); while ( $alerts->have_posts() ) : $alerts->the_post(); ?>
				<span class="mx-8 flex items-center gap-2 font-mono text-xs uppercase tracking-wider text-accent-foreground/90">
					<svg class="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 01-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 011-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 011.52 0C14.51 3.81 17 5 19 5a1 1 0 011 1z"></path></svg>
					<?php the_title(); ?> — <?php echo esc_html( get_post_meta( get_the_ID(), 'frequency', true ) ); ?>
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
	<?php else : ?>
		<img src="https://images.pexels.com/photos/15491339/pexels-photo-15491339.jpeg?auto=compress&cs=tinysrgb&w=1280&fm=webp" alt="Torre de antena de radioaficionado" class="absolute inset-0 h-full w-full object-cover opacity-30" fetchpriority="high" decoding="async">
	<?php endif; ?>
	<div class="absolute inset-0 bg-gradient-to-b from-background/70 via-background/80 to-background"></div>

	<section class="relative mx-auto max-w-[96rem] px-4 sm:px-6 flex min-h-[92dvh] flex-col justify-center py-24">
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
		$hero_flags = rau_get_flag_counts();
		$hero_reps  = wp_count_posts( 'repetidor' )->publish ?? 0;
		?>
		<div class="mt-10 flex flex-wrap gap-x-8 gap-y-3 font-mono text-sm">
			<div><span class="text-2xl font-bold text-primary"><?php echo esc_html( count( rau_get_herramientas_tabs() ) ); ?></span> <span class="text-muted-foreground">herramientas gratis</span></div>
			<div><span class="text-2xl font-bold text-primary"><?php echo esc_html( $hero_reps ); ?></span> <span class="text-muted-foreground">repetidores</span></div>
			<?php if ( $hero_flags ) : ?>
			<div><span class="text-2xl font-bold text-primary"><?php echo esc_html( count( $hero_flags ) ); ?></span> <span class="text-muted-foreground">países alcanzados</span></div>
			<?php endif; ?>
		</div>

		<div class="mt-8 flex items-center gap-4">
			<span class="font-mono text-lg sm:text-xl tracking-[0.35em] text-primary/80" title="RAU en código Morse">.-. .- ..-</span>
			<svg viewBox="0 0 200 30" class="hidden h-6 w-32 text-primary/40 sm:block" aria-hidden="true">
				<polyline points="0,15 15,15 20,5 25,25 30,15 45,15 50,10 55,20 60,15 75,15 80,3 85,27 90,15 105,15 110,12 115,18 120,15 200,15" fill="none" stroke="currentColor" stroke-width="1.5">
					<animate attributeName="points"
						values="0,15 15,15 20,5 25,25 30,15 45,15 50,10 55,20 60,15 75,15 80,3 85,27 90,15 105,15 110,12 115,18 120,15 200,15;
						        0,15 15,15 20,22 25,8 30,15 45,15 50,20 55,5 60,15 75,15 80,25 85,3 90,15 105,15 110,6 115,24 120,15 200,15;
						        0,15 15,15 20,5 25,25 30,15 45,15 50,10 55,20 60,15 75,15 80,3 85,27 90,15 105,15 110,12 115,18 120,15 200,15"
						dur="2.4s" repeatCount="indefinite" />
				</polyline>
			</svg>
		</div>
	</section>
</div>

<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-6">
	<div class="rounded-2xl border-2 border-primary/30 bg-card p-5 sm:p-6">
		<div class="mb-4 flex items-center justify-between">
			<div>
				<p class="font-mono text-[11px] uppercase tracking-[0.25em] text-primary">Solo por diversión</p>
				<h2 class="font-display text-lg font-bold sm:text-xl">🎛 Radio RAU</h2>
			</div>
			<p class="hidden font-mono text-xs text-muted-foreground sm:block">Gira el dial y busca una estación…</p>
		</div>

		<div class="grid gap-5 sm:grid-cols-[1fr_auto] sm:items-center">
			<div>
				<div class="mb-3 flex items-end justify-between">
					<div>
						<p id="radio-freq" class="font-mono text-3xl font-bold tabular-nums text-primary sm:text-4xl">14.250</p>
						<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">MHz</p>
					</div>
					<button id="radio-power" type="button" aria-pressed="false" class="inline-flex items-center gap-1.5 rounded-full border-2 border-border bg-background px-3 py-1.5 font-mono text-xs font-bold text-muted-foreground">
						<span id="radio-power-dot" class="h-2 w-2 rounded-full bg-muted-foreground"></span> OFF
					</button>
				</div>

				<input type="range" id="radio-dial" min="0" max="1000" value="500" class="w-full accent-primary" aria-label="Sintonizar frecuencia">

				<div class="mt-3 flex items-center gap-3">
					<span class="font-mono text-[10px] uppercase text-muted-foreground">Señal</span>
					<div class="h-2 flex-1 overflow-hidden rounded-full bg-background">
						<div id="radio-smeter" class="h-full w-0 rounded-full bg-gradient-to-r from-emerald-400 via-amber-400 to-rose-400 transition-all duration-150"></div>
					</div>
					<span class="font-mono text-[10px] uppercase text-muted-foreground">Volumen</span>
					<input type="range" id="radio-vol" aria-label="Volumen del reproductor" min="0" max="100" value="40" class="w-20 accent-primary">
				</div>
				<p id="radio-status" class="mt-2 font-mono text-xs text-muted-foreground">Apagada — dale al botón para encenderla.</p>
			</div>

			<div class="hidden h-20 w-20 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-border/60 sm:flex">
				<svg id="radio-icon" class="h-9 w-9 text-muted-foreground transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l3 3M20 4l-3 3"></path><circle cx="12" cy="14" r="7"></circle><path d="M12 14v-4"></path><path d="M9 21h6"></path></svg>
			</div>
		</div>
	</div>
</section>

<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">
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
					</div>

					<!-- Métricas SFI y A -->
					<div class="mt-5 grid w-full grid-cols-2 gap-3">
						<div class="flex flex-col items-center rounded-xl border border-border/60 bg-background/40 py-3">
							<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Flujo Solar SFI</p>
							<p id="hp-sfi" class="mt-1 font-mono text-2xl font-bold text-amber-400">—</p>
							<p class="font-mono text-[10px] text-muted-foreground/50">óptimo &gt; 150</p>
						</div>
						<div class="flex flex-col items-center rounded-xl border border-border/60 bg-background/40 py-3">
							<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Índice A</p>
							<p id="hp-a" class="mt-1 font-mono text-2xl font-bold text-foreground">—</p>
							<p class="font-mono text-[10px] text-muted-foreground/50">bajo = mejor</p>
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
							<p class="font-mono text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Receptor SDR · En línea</p>
						</div>
						<h2 class="font-display text-2xl font-bold">KiwiSDR HK4RAU</h2>
						<p class="mt-1 text-sm text-muted-foreground">Medellín, Colombia · 0–30 MHz</p>
					</div>
					<!-- Indicador de estado -->
					<div id="sdr-status-badge" class="shrink-0 inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-background px-3 py-1.5">
						<span class="h-2 w-2 rounded-full bg-muted animate-pulse"></span>
						<span class="font-mono text-xs text-muted-foreground">Verificando…</span>
					</div>
				</div>

				<!-- Visualización de espectro simulada (estética) -->
				<div class="mb-5 overflow-hidden rounded-xl border border-border/60 bg-background">
					<div class="flex items-center justify-between border-b border-border/50 px-3 py-1.5">
						<span class="font-mono text-[10px] text-muted-foreground">ESPECTRO HF — 0 a 30 MHz</span>
						<span class="font-mono text-[10px] text-primary">HK4RAU</span>
					</div>
					<svg viewBox="0 0 400 80" xmlns="http://www.w3.org/2000/svg" class="w-full" preserveAspectRatio="none">
						<!-- Fondo de cuadrícula -->
						<line x1="0" y1="20" x2="400" y2="20" stroke="hsl(var(--border)/.3)" stroke-width="0.5"/>
						<line x1="0" y1="40" x2="400" y2="40" stroke="hsl(var(--border)/.3)" stroke-width="0.5"/>
						<line x1="0" y1="60" x2="400" y2="60" stroke="hsl(var(--border)/.3)" stroke-width="0.5"/>
						<line x1="80"  y1="0" x2="80"  y2="80" stroke="hsl(var(--border)/.2)" stroke-width="0.5"/>
						<line x1="160" y1="0" x2="160" y2="80" stroke="hsl(var(--border)/.2)" stroke-width="0.5"/>
						<line x1="240" y1="0" x2="240" y2="80" stroke="hsl(var(--border)/.2)" stroke-width="0.5"/>
						<line x1="320" y1="0" x2="320" y2="80" stroke="hsl(var(--border)/.2)" stroke-width="0.5"/>
						<!-- Espectro decorativo representativo de HF -->
						<polyline points="0,72 10,70 20,68 30,65 35,60 40,55 44,58 48,62 55,70 60,68 65,55 70,45 75,50 80,65 90,70 100,68 110,55 115,40 120,45 125,52 130,65 140,70 150,68 155,60 160,48 165,55 170,62 175,58 180,65 190,70 200,68 210,58 215,42 220,38 225,42 230,55 235,62 240,68 250,70 260,65 265,50 270,42 275,48 280,55 290,68 300,70 310,65 315,52 320,45 325,50 330,60 340,68 350,70 355,62 360,55 365,58 370,65 380,70 390,72 400,72"
							fill="none" stroke="hsl(var(--primary)/.6)" stroke-width="1.5" stroke-linejoin="round"/>
						<!-- Relleno bajo la curva -->
						<polyline points="0,80 0,72 10,70 20,68 30,65 35,60 40,55 44,58 48,62 55,70 60,68 65,55 70,45 75,50 80,65 90,70 100,68 110,55 115,40 120,45 125,52 130,65 140,70 150,68 155,60 160,48 165,55 170,62 175,58 180,65 190,70 200,68 210,58 215,42 220,38 225,42 230,55 235,62 240,68 250,70 260,65 265,50 270,42 275,48 280,55 290,68 300,70 310,65 315,52 320,45 325,50 330,60 340,68 350,70 355,62 360,55 365,58 370,65 380,70 390,72 400,72 400,80"
							fill="hsl(var(--primary)/.08)"/>
						<!-- Marcas de frecuencia decorativas -->
						<line x1="80"  y1="45" x2="80"  y2="80" stroke="hsl(var(--primary)/.3)" stroke-width="1" stroke-dasharray="2,2"/>
						<line x1="160" y1="48" x2="160" y2="80" stroke="hsl(var(--primary)/.3)" stroke-width="1" stroke-dasharray="2,2"/>
						<line x1="220" y1="38" x2="220" y2="80" stroke="#fbbf24" stroke-width="1.5" stroke-dasharray="2,2" opacity=".5"/>
						<line x1="320" y1="45" x2="320" y2="80" stroke="hsl(var(--primary)/.3)" stroke-width="1" stroke-dasharray="2,2"/>
						<!-- Labels de frecuencia -->
						<text x="80"  y="78" font-size="7" fill="hsl(var(--muted-foreground)/.5)" font-family="monospace" text-anchor="middle">7 MHz</text>
						<text x="160" y="78" font-size="7" fill="hsl(var(--muted-foreground)/.5)" font-family="monospace" text-anchor="middle">14 MHz</text>
						<text x="220" y="78" font-size="7" fill="#fbbf2499" font-family="monospace" text-anchor="middle">20m</text>
						<text x="320" y="78" font-size="7" fill="hsl(var(--muted-foreground)/.5)" font-family="monospace" text-anchor="middle">27 MHz</text>
					</svg>
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
						<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Usuarios</p>
						<p id="sdr-users" class="mt-0.5 font-mono text-sm font-bold text-foreground">—</p>
					</div>
				</div>

				<p class="mb-4 flex flex-wrap items-center gap-1.5 font-mono text-[11px] text-muted-foreground">
					<span class="uppercase tracking-wider text-muted-foreground">Decodifica:</span>
					<span class="rounded-full border border-border/60 bg-background/50 px-2 py-0.5 text-primary">FT8/FT4</span>
					<span class="rounded-full border border-border/60 bg-background/50 px-2 py-0.5 text-primary">SSTV</span>
					<span class="rounded-full border border-border/60 bg-background/50 px-2 py-0.5 text-primary">WSPR</span>
				</p>

				<!-- Aviso HTTP + botón -->
				<div class="mb-4 rounded-lg border border-amber-400/20 bg-amber-400/5 px-3 py-2 text-xs text-amber-400/80">
					⚠ El receptor opera en HTTP. Se abre en pestaña nueva para evitar bloqueo de contenido mixto.
				</div>

				<a href="http://hk4rau.proxy.kiwisdr.com:8073/" target="_blank" rel="noopener"
					class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">
					<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h2M4 12a8 8 0 0 1 8-8M12 4V2M20 12h2M12 20v2"/><circle cx="12" cy="12" r="3"/></svg>
					Escuchar en vivo — KiwiSDR HK4RAU
				</a>

				<script>
				(function(){
					/* Intenta obtener el número de usuarios del KiwiSDR via proxy PHP */
					var badge = document.getElementById('sdr-status-badge');
					var users = document.getElementById('sdr-users');
					fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_sdr_status', {cache:'no-store'})
					.then(function(r){return r.json();})
					.then(function(json){
						if(json.success && json.data){
							var n = json.data.users;
							var m = json.data.max || 4;
							if(users) users.textContent = (n !== null && n !== undefined) ? n+'/'+m : '—';
							badge.innerHTML = '<span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;"></span><span style="font-family:monospace;font-size:0.75rem;color:#22c55e;">En línea</span>';
						} else { throw new Error(); }
					})
					.catch(function(){
						badge.innerHTML = '<span style="width:8px;height:8px;border-radius:50%;background:#f59e0b;display:inline-block;"></span><span style="font-family:monospace;font-size:0.75rem;color:hsl(var(--muted-foreground));">Sin confirmar</span>';
						if(users) users.textContent = '—';
					});
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

				/* Estado */
				var estado = document.getElementById('gauge-estado');
				var desc   = document.getElementById('gauge-desc');
				if(estado && desc){
					if(k<=1 && sfi>=100){ estado.textContent='Excelente'; estado.style.color=cGreen; desc.textContent='Condiciones óptimas · SFI='+sfi; }
					else if(k<=3)       { estado.textContent='Buena';      estado.style.color=isLight?'#0e7490':'hsl(190 95% 55%)'; desc.textContent='Propagación normal · SFI='+sfi; }
					else if(k===4)      { estado.textContent='Perturbada'; estado.style.color=cAmber; desc.textContent='HF degradado · Tormenta posible'; }
					else                { estado.textContent='Tormenta';   estado.style.color=cRed;   desc.textContent='HF muy degradado · K='+k; }
				}

				/* Métricas */
				var sfiEl = document.getElementById('hp-sfi'); if(sfiEl) sfiEl.textContent = sfi||'—';
				var aEl   = document.getElementById('hp-a');   if(aEl)   aEl.textContent   = aindex||'—';
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
				updateGauge(k, sfi, d.aindex);
			})
			.catch(function(){
				var e=document.getElementById('gauge-estado'); if(e) e.textContent='Sin datos';
				var d2=document.getElementById('gauge-desc'); if(d2) d2.textContent='No se pudo cargar la información solar';
			});
		})();
		</script>
</section>

<section id="repetidores" class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Red de Repetidores</p>
	<div class="mb-8 flex flex-wrap items-end justify-between gap-4"><h2 class="max-w-2xl font-display text-3xl font-bold sm:text-4xl">Repetidores</h2><a href="<?php echo esc_url( get_post_type_archive_link( 'repetidor' ) ); ?>" class="text-sm font-medium text-primary hover:underline">Ver directorio completo →</a></div>
	<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
		<?php if ( ! $repetidores_home->have_posts() ) : ?>
			<p class="text-sm text-muted-foreground">Aún no hay repetidores publicados. Agrégalos en el escritorio → Repetidores.</p>
		<?php else : $rep_idx = 0; while ( $repetidores_home->have_posts() ) : $repetidores_home->the_post(); $rid = get_the_ID();
			$status = get_post_meta( $rid, 'status', true ) ?: 'activo';
		?>
		<div class="rau-animar rounded-xl border border-border/70 bg-card p-5" style="transition-delay:<?php echo esc_attr( $rep_idx++ * 80 ); ?>ms">
			<div class="mb-2 flex items-start justify-between gap-2">
				<h3 class="font-semibold"><?php the_title(); ?></h3>
				<span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium capitalize ring-1 <?php echo esc_attr( rau_status_classes( $status ) ); ?>"><?php echo esc_html( $status ); ?></span>
			</div>
			<p class="font-mono text-xs text-primary"><?php echo esc_html( get_post_meta( $rid, 'callsign', true ) ); ?> · <?php echo esc_html( get_post_meta( $rid, 'band', true ) ); ?></p>
			<p class="mt-2 font-mono text-sm"><?php echo esc_html( get_post_meta( $rid, 'frequency', true ) ); ?> <span class="text-xs text-muted-foreground"><?php echo esc_html( get_post_meta( $rid, 'offset', true ) ); ?></span></p>
			<p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"><svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1116 0z"></path></svg><?php echo esc_html( get_post_meta( $rid, 'location', true ) ); ?></p>
		</div>
		<?php endwhile; wp_reset_postdata(); endif; ?>
	</div>
</section>

<div class="mx-auto max-w-[96rem] px-4 sm:px-6">
	<div id="home-concurso-widget" class="hidden mb-8 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-primary/30 bg-primary/5 px-5 py-3">
		<p class="font-mono text-sm"><span class="text-muted-foreground">Próximo concurso:</span> <strong id="home-concurso-nombre" class="text-foreground">—</strong></p>
		<p class="font-mono text-sm font-bold text-primary" id="home-concurso-dias">—</p>
	</div>
</div>

<section id="eventos" class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Calendario · Mallas · Concursos · Diplomas</p>
	<div class="mb-2 flex flex-wrap items-end justify-between gap-4"><h2 class="max-w-2xl font-display text-3xl font-bold sm:text-4xl">Próximas actividades</h2><a href="<?php echo esc_url( get_post_type_archive_link( 'evento' ) ); ?>" class="text-sm font-medium text-primary hover:underline">Ver todas →</a></div>
	<p class="mb-8 text-sm text-muted-foreground">
		<a href="<?php echo esc_url( home_url( '/calendario.ics' ) ); ?>" class="inline-flex items-center gap-1 font-medium text-primary hover:underline">
			<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3.5 10h17"></path></svg>
			Suscribirse a todos los eventos en Google Calendar
		</a> — pega ese enlace en Google Calendar → Otros calendarios → Desde URL.
	</p>
	<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
		<?php if ( ! $events->have_posts() ) :
			// Con el filtro de fecha, que no haya resultados ya no significa que no haya eventos:
			// puede que todos hayan pasado. Se distingue para no mandar a publicar lo que ya existe.
			$hay_eventos = (int) wp_count_posts( 'evento' )->publish;
		?>
			<?php if ( $hay_eventos ) : ?>
				<p class="text-sm text-muted-foreground">No hay actividades programadas por ahora. Las anteriores siguen disponibles en <a href="<?php echo esc_url( get_post_type_archive_link( 'evento' ) ); ?>" class="text-primary hover:underline">el calendario completo</a>.</p>
			<?php else : ?>
				<p class="text-sm text-muted-foreground">Aún no hay eventos publicados. Agrégalos en el escritorio → Eventos.</p>
			<?php endif; ?>
		<?php else : $evt_idx = 0; while ( $events->have_posts() ) : $events->the_post(); $eid = get_the_ID();
			$start = get_post_meta( $eid, 'start', true );
			$ts = $start ? strtotime( $start ) : false;
		?>
		<div class="rau-animar flex gap-4 rounded-xl border border-border/70 bg-card p-5" style="transition-delay:<?php echo esc_attr( $evt_idx++ * 80 ); ?>ms">
			<div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-lg bg-primary/10 text-primary ring-1 ring-primary/20">
				<span class="font-display text-2xl font-bold leading-none"><?php echo $ts ? esc_html( date_i18n( 'j', $ts ) ) : '—'; ?></span>
				<span class="font-mono text-[10px] uppercase"><?php echo $ts ? esc_html( date_i18n( 'M', $ts ) ) : ''; ?></span>
			</div>
			<div class="min-w-0 flex-1">
				<span class="font-mono text-[10px] uppercase tracking-wider text-accent"><?php echo esc_html( get_post_meta( $eid, 'type', true ) ); ?></span>
				<h3 class="truncate font-semibold"><?php the_title(); ?></h3>
				<?php $freq = get_post_meta( $eid, 'frequency', true ); if ( $freq ) : ?><p class="font-mono text-xs text-muted-foreground"><?php echo esc_html( $freq ); ?></p><?php endif; ?>
				<?php $loc = get_post_meta( $eid, 'location', true ); if ( $loc ) : ?><p class="flex items-center gap-1 text-xs text-muted-foreground"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1116 0z"></path></svg><?php echo esc_html( $loc ); ?></p><?php endif; ?>
				<?php if ( $start ) : ?>
					<a href="<?php echo esc_url( rau_google_calendar_link( $eid ) ); ?>" target="_blank" rel="noreferrer" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">+ Google Calendar</a>
				<?php endif; ?>
			</div>
		</div>
		<?php endwhile; wp_reset_postdata(); endif; ?>
	</div>
</section>


<div style="background: hsl(var(--card) / 0.35);">
<section id="comunidad" class="mx-auto max-w-[96rem] px-4 sm:px-6 pt-12 pb-6">
	<?php $codigo_etica_id = rau_content_by_section( 'codigo_etica' ); if ( $codigo_etica_id ) : ?>
	<a href="<?php echo esc_url( get_permalink( $codigo_etica_id ) ); ?>" class="group flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-border/70 bg-card p-6 transition-colors hover:border-primary/50">
		<div>
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Para quienes llegan a RAU</p>
			<h2 class="font-display text-2xl font-bold group-hover:text-primary sm:text-3xl"><?php echo esc_html( get_the_title( $codigo_etica_id ) ); ?></h2>
			<?php $ce_summary = get_post_meta( $codigo_etica_id, 'summary', true ); if ( $ce_summary ) : ?><p class="mt-2 max-w-2xl text-muted-foreground"><?php echo esc_html( $ce_summary ); ?></p><?php endif; ?>
		</div>
		<span class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-md border border-border bg-background px-6 text-sm font-medium shadow-sm transition-colors group-hover:bg-accent group-hover:text-accent-foreground">Leer <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></span>
	</a>
	<?php endif; ?>

	<div class="mt-12">
		<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Historias de la Comunidad</p>
		<div class="mb-6 flex flex-wrap items-end justify-between gap-4"><h3 class="font-display text-2xl font-bold">Lo que dicen quienes ya son parte de RAU</h3><a href="<?php echo esc_url( get_post_type_archive_link( 'historia' ) ); ?>" class="text-sm font-medium text-primary hover:underline">Ver todas →</a></div>
		<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
			<?php if ( $historias->have_posts() ) : $his_idx = 0; while ( $historias->have_posts() ) : $historias->the_post();
				$autor = get_post_meta( get_the_ID(), 'autor', true ) ?: get_the_title();
			?>
			<div class="rau-animar rounded-xl border border-border/70 bg-card p-5" style="transition-delay:<?php echo esc_attr( $his_idx++ * 80 ); ?>ms">
				<p class="text-sm italic text-muted-foreground">&ldquo;<?php echo wp_kses_post( get_the_content() ); ?>&rdquo;</p>
				<div class="mt-4 flex items-center gap-3 border-t border-border/50 pt-3">
					<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 font-mono text-xs font-bold text-primary ring-1 ring-primary/20"><?php echo esc_html( rau_initials( preg_replace('/,.*/', '', $autor) ) ); ?></div>
					<div><p class="text-sm font-semibold"><?php echo esc_html( $autor ); ?></p><p class="text-xs text-muted-foreground"><?php echo esc_html( get_post_meta( get_the_ID(), 'rol', true ) ); ?></p></div>
				</div>
			</div>
			<?php endwhile; wp_reset_postdata(); else : ?>
				<p class="text-sm text-muted-foreground">Aún no hay historias publicadas. Agrégalas en el escritorio → Historias de la Comunidad.</p>
			<?php endif; ?>
		</div>
	</div>

	<div class="mt-12 rounded-2xl border border-primary/30 bg-card p-6 sm:p-8">
		<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
			<div>
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Deja tu huella</p>
				<h3 class="font-display text-2xl font-bold">Libro de Visitas</h3>
				<p class="mt-1 text-sm text-muted-foreground"><?php echo esc_html( $libro_total ); ?> mensaje<?php echo $libro_total === 1 ? '' : 's'; ?> de la comunidad — permanentes, para siempre.</p>
			</div>
			<a href="<?php echo esc_url( home_url( '/libro-de-visitas/' ) ); ?>" class="inline-flex h-10 shrink-0 items-center justify-center rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
				✍️ Firmar el libro
			</a>
		</div>

		<?php if ( $libro_preview->have_posts() ) : ?>
		<div class="grid gap-4 sm:grid-cols-3">
			<?php while ( $libro_preview->have_posts() ) : $libro_preview->the_post();
				$m_lv          = get_post_meta( get_the_ID() );
				$lv_indicativo = $m_lv['indicativo'][0] ?? '';
				$lv_nombre     = $m_lv['nombre'][0] ?? get_the_title();
			?>
			<div class="rounded-xl border border-border/70 bg-background p-5">
				<p class="text-sm italic text-muted-foreground">&ldquo;<?php echo esc_html( wp_trim_words( get_the_content(), 18 ) ); ?>&rdquo;</p>
				<div class="mt-4 flex items-center gap-3 border-t border-border/50 pt-3">
					<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 font-mono text-xs font-bold text-primary ring-1 ring-primary/20"><?php echo esc_html( rau_initials( $lv_indicativo ?: $lv_nombre ) ); ?></div>
					<div><p class="text-sm font-semibold"><?php echo esc_html( $lv_nombre ); ?></p><?php if ( $lv_indicativo ) : ?><p class="font-mono text-xs text-primary"><?php echo esc_html( $lv_indicativo ); ?></p><?php endif; ?></div>
				</div>
			</div>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
		<?php else : ?>
			<p class="text-sm text-muted-foreground">Todavía nadie ha firmado. ¡Sé el primero!</p>
		<?php endif; ?>
	</div>

	<div class="mt-12 overflow-hidden rounded-2xl border border-primary/30 bg-gradient-to-br from-primary/15 via-card to-card p-8 sm:p-12">
		<div class="max-w-2xl">
			<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Cómo Asociarse</p>
			<h3 class="font-display text-3xl font-bold"><?php echo $como_asociarse_id ? esc_html( get_the_title( $como_asociarse_id ) ) : 'Únete a la RAU'; ?></h3>
			<?php if ( $como_asociarse_id ) : ?><div class="mt-3 text-muted-foreground"><?php echo apply_filters( 'the_content', get_post_field( 'post_content', $como_asociarse_id ) ); ?></div><?php endif; ?>
			<div class="mt-6">
				<p class="mb-3 text-sm font-medium text-foreground">¿Tienes licencia activa, la tienes vencida, o quieres sacarla por primera vez?</p>
				<div class="mb-4 flex flex-wrap gap-3 text-sm">
					<a href="<?php echo esc_url( home_url( '/herramientas/examen/' ) ); ?>" class="text-primary hover:underline">📝 Practica con el Simulador de Examen RABCA</a>
					<span class="text-muted-foreground">·</span>
					<a href="<?php echo esc_url( home_url( '/herramientas/licencia/' ) ); ?>" class="text-primary hover:underline">📅 Configura el Recordatorio de Vencimiento</a>
				</div>
				<a href="<?php echo esc_url( rau_whatsapp_url() ); ?>" target="_blank" rel="noreferrer" class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
					<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"></path></svg> Escríbenos por WhatsApp
				</a>
			</div>
		</div>
	</div>
</section>
</div>

<script>
(function(){
	var powerBtn=document.getElementById('radio-power');
	if(!powerBtn) return;

	var powerDot=document.getElementById('radio-power-dot');
	var dial=document.getElementById('radio-dial');
	var volSlider=document.getElementById('radio-vol');
	var freqEl=document.getElementById('radio-freq');
	var smeter=document.getElementById('radio-smeter');
	var statusEl=document.getElementById('radio-status');
	var iconEl=document.getElementById('radio-icon');

	var ctx=null, noiseSource=null, noiseGain=null, filterNode=null, encendida=false;
	var morseTimer=null, morseOsc=null, morseGain=null;

	// "CQ CQ DE HK4RAU" en Morse — el huequito escondido del dial
	var MORSE={'C':'-.-.','Q':'--.-',' ':' ','D':'-..','E':'.','H':'....','K':'-.-','4':'....-','R':'.-.','A':'.-','U':'..-'};
	var MENSAJE='CQ CQ DE HK4RAU';
	var FREQ_SECRETA=145; // punto exacto del dial (0-1000) donde aparece la señal

	function freqFromDial(v){
		// Mapea 0-1000 del slider a un rango de frecuencia HF "de mentiras" (3.5 a 29.7 MHz)
		return (3.5 + (v/1000)*(29.7-3.5)).toFixed(3);
	}

	function crearRuidoBlanco(){
		var bufferSize=2*ctx.sampleRate;
		var buffer=ctx.createBuffer(1, bufferSize, ctx.sampleRate);
		var data=buffer.getChannelData(0);
		for(var i=0;i<bufferSize;i++){ data[i]=Math.random()*2-1; }
		var src=ctx.createBufferSource();
		src.buffer=buffer;
		src.loop=true;
		return src;
	}

	function encender(){
		if(!ctx) ctx=new (window.AudioContext||window.webkitAudioContext)();
		noiseSource=crearRuidoBlanco();
		filterNode=ctx.createBiquadFilter();
		filterNode.type='bandpass';
		filterNode.frequency.value=1200;
		noiseGain=ctx.createGain();
		noiseGain.gain.value=(volSlider.value/100)*0.15;
		noiseSource.connect(filterNode).connect(noiseGain).connect(ctx.destination);
		noiseSource.start();
		encendida=true;
		powerBtn.setAttribute('aria-pressed','true');
		powerBtn.innerHTML='<span class="h-2 w-2 rounded-full bg-emerald-400"></span> ON';
		powerBtn.classList.add('border-emerald-400/50','text-emerald-400');
		iconEl.classList.remove('text-muted-foreground');
		iconEl.classList.add('text-primary');
		actualizarDial();
	}

	function apagar(){
		if(morseTimer){ clearTimeout(morseTimer); morseTimer=null; }
		if(morseOsc){ try{morseOsc.stop();}catch(e){} morseOsc=null; }
		if(noiseSource){ try{noiseSource.stop();}catch(e){} noiseSource=null; }
		encendida=false;
		powerBtn.setAttribute('aria-pressed','false');
		powerBtn.innerHTML='<span id="radio-power-dot" class="h-2 w-2 rounded-full bg-muted-foreground"></span> OFF';
		powerBtn.classList.remove('border-emerald-400/50','text-emerald-400');
		iconEl.classList.add('text-muted-foreground');
		iconEl.classList.remove('text-primary');
		smeter.style.width='0%';
		statusEl.textContent='Apagada — dale al botón para encenderla.';
	}

	function pitarMorse(){
		if(!encendida) return;
		if(morseOsc){ try{morseOsc.stop();}catch(e){} morseOsc=null; }
		var punto=90, letras=MENSAJE.split(''), i=0;
		function siguiente(){
			if(!encendida) return;
			if(i>=letras.length){ morseTimer=setTimeout(siguiente, punto*6); i=0; return; }
			var letra=letras[i++];
			if(letra===' '){ morseTimer=setTimeout(siguiente, punto*4); return; }
			var codigo=MORSE[letra]||'';
			var j=0;
			function pip(){
				if(j>=codigo.length){ morseTimer=setTimeout(siguiente, punto*2); return; }
				var simbolo=codigo[j++];
				var dur = simbolo==='-' ? punto*3 : punto;
				morseOsc=ctx.createOscillator();
				var g=ctx.createGain();
				g.gain.value=(volSlider.value/100)*0.2;
				morseOsc.frequency.value=600;
				morseOsc.connect(g).connect(ctx.destination);
				morseOsc.start();
				morseOsc.stop(ctx.currentTime+dur/1000);
				morseTimer=setTimeout(pip, dur+punto);
			}
			pip();
		}
		siguiente();
	}

	function actualizarDial(){
		var v=parseInt(dial.value);
		freqEl.textContent=freqFromDial(v);
		var cercaDeSecreta=Math.abs(v-FREQ_SECRETA)<15;

		if(!encendida) return;

		if(cercaDeSecreta){
			if(noiseGain) noiseGain.gain.value=(volSlider.value/100)*0.02;
			smeter.style.width='95%';
			statusEl.textContent='📡 ¡Señal fuerte! CQ CQ DE HK4RAU…';
			if(!morseTimer) pitarMorse();
		} else {
			if(morseTimer){ clearTimeout(morseTimer); morseTimer=null; }
			if(morseOsc){ try{morseOsc.stop();}catch(e){} morseOsc=null; }
			if(noiseGain) noiseGain.gain.value=(volSlider.value/100)*0.15;
			if(filterNode) filterNode.frequency.value=400+Math.random()*2000;
			var senal=Math.round(10+Math.random()*20);
			smeter.style.width=senal+'%';
			statusEl.textContent='Solo estática por aquí… sigue buscando.';
		}
	}

	powerBtn.addEventListener('click', function(){ encendida ? apagar() : encender(); });
	dial.addEventListener('input', actualizarDial);
	volSlider.addEventListener('input', function(){
		if(noiseGain) noiseGain.gain.value=(volSlider.value/100)*0.15;
	});
})();
</script>

<script>
(function(){
	var widget=document.getElementById('home-concurso-widget');
	if(!widget) return;

	function finesDeSemanaCompletos(year, month){
		var lastDay=new Date(Date.UTC(year, month+1, 0)).getUTCDate();
		var out=[];
		for(var d=1; d<=lastDay; d++){
			var dt=new Date(Date.UTC(year, month, d));
			if(dt.getUTCDay()===6 && d+1<=lastDay) out.push(dt);
		}
		return out;
	}
	function nEsimoFinDeSemana(year, month, n){
		var ws=finesDeSemanaCompletos(year, month);
		return n<0 ? ws[ws.length+n] : ws[n-1];
	}
	function proximo(month, n){
		var ahora=new Date();
		var candidato=nEsimoFinDeSemana(ahora.getUTCFullYear(), month, n);
		if(!candidato || (candidato.getTime()+2*86400000) < ahora.getTime()){
			candidato=nEsimoFinDeSemana(ahora.getUTCFullYear()+1, month, n);
		}
		return candidato;
	}
	function diasFaltantes(dt){
		return Math.ceil((dt.getTime()-new Date().getTime())/86400000);
	}

	// Lista compartida con Herramientas → Calendario de Concursos (rau_get_concursos()
	// en functions.php). Antes estaba escrita por duplicado en los dos sitios.
	var CONCURSOS=<?php echo wp_json_encode( rau_get_concursos() ); ?>;

	var proximos = CONCURSOS.map(function(c){
		var dt=proximo(c.mes, c.n_semana);
		return {nombre:c.n, fecha:dt, faltan:diasFaltantes(dt)};
	}).sort(function(a,b){ return a.fecha-b.fecha; });

	var siguiente = proximos[0];
	if(siguiente){
		document.getElementById('home-concurso-nombre').textContent = siguiente.nombre;
		document.getElementById('home-concurso-dias').textContent = siguiente.faltan<=0 ? '¡Es este fin de semana!' : 'Faltan '+siguiente.faltan+' días';
		widget.classList.remove('hidden');
	}
})();
</script>

<?php get_footer(); ?>
