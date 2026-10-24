<?php
/**
 * Template Name: Herramientas de Radio
 * Calculadoras, utilidades y recursos técnicos para radioaficionados.
 * Todo corre en el navegador — sin dependencias externas de pago.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$tabs = rau_get_herramientas_tabs();
$repetidores_consola = rau_get_repeaters_data();
$tabs_by_cat_sin_orden = [];
foreach ( $tabs as $t ) {
	// Las agrupadas ('oculta') conservan su panel y su URL, pero no ocupan una entrada propia en
	// el catálogo: se muestran junto a la herramienta que las incluye.
	if ( ! empty( $t['oculta'] ) ) continue;
	$tabs_by_cat_sin_orden[ $t['cat'] ][] = $t;
}
// Orden intencional (RAU Tools 2.0, sección 13): Operar, Calcular, Localizar, Explorar, Escuchar, Aprender, Emergencias
$orden_categorias = [ 'Operar', 'Calcular', 'Localizar', 'Explorar', 'Escuchar', 'Aprender', 'Emergencias' ];
$tabs_by_cat = [];
foreach ( $orden_categorias as $cat ) {
	if ( isset( $tabs_by_cat_sin_orden[ $cat ] ) ) $tabs_by_cat[ $cat ] = $tabs_by_cat_sin_orden[ $cat ];
}
// Por si alguna categoría futura no está en la lista de orden, que igual aparezca al final en vez de desaparecer
foreach ( $tabs_by_cat_sin_orden as $cat => $lista ) {
	if ( ! isset( $tabs_by_cat[ $cat ] ) ) $tabs_by_cat[ $cat ] = $lista;
}
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<?php
	// En /herramientas/{id}/ el H1 es la herramienta: las 60 URLs ya tenían su propio <title> y su
	// descripción, pero compartían "Herramientas de Radio" como encabezado principal.
	$tool_h1 = get_query_var( 'rau_tool' ) ? rau_find_tool_by_id( sanitize_title( get_query_var( 'rau_tool' ) ) ) : null;
	?>
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary"><?php echo $tool_h1 ? 'Herramientas de Radio' : 'Utilidades técnicas'; ?></p>
	<h1 class="mb-2 font-display text-3xl font-bold sm:text-4xl"><?php echo esc_html( $tool_h1 ? $tool_h1['label'] : 'Herramientas de Radio' ); ?></h1>
	<p class="mb-10 max-w-2xl text-muted-foreground">Calculadoras y recursos operativos para radioaficionados. Todo funciona directamente en el navegador, sin necesidad de instalar nada.</p>

	<!-- ── TABS ─────────────────────────────────────────────── -->
	<div id="rau-tools">

	<div id="rau-tour-banner" class="rau-solo-consola mb-4 hidden items-start justify-between gap-4 rounded-xl border-2 border-primary/40 bg-primary/5 p-4">
			<div>
				<p class="font-display text-sm font-bold">👋 ¿Primera vez aquí?</p>
				<p class="mt-1 text-sm text-muted-foreground">Esto es un centro de mando: en el medio, el receptor del club en vivo; a la izquierda, cómo están las condiciones; a la derecha, tu bitácora. Cambia de banda en cualquier parte y todo se ajusta. Las <strong><?php echo count( $tabs ); ?> herramientas</strong> están en «Catálogo» y en el buscador.</p>
			</div>
			<button id="rau-tour-cerrar" type="button" class="shrink-0 rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium">Entendido</button>
		</div>

	<!-- CONSOLA RAU — CENTRO DE MANDO -->
	<div id="rau-consola" class="rau-consola mb-8 overflow-hidden rounded-2xl border-2 border-primary/40 bg-[#050914] shadow-[0_0_40px_-12px_hsl(var(--primary)/0.3)]">

		<!-- Franja superior: reloj + estado, siempre visible -->
		<div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-b border-primary/20 bg-black/40 px-4 py-2.5 sm:px-5">
			<div class="flex items-center gap-2 font-mono text-xs text-primary">
				<span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
				<span id="consola-utc" class="tabular-nums">--:--:--</span>
				<span class="text-muted-foreground">UTC</span>
				<span class="text-muted-foreground">·</span>
				<span id="consola-local" class="tabular-nums text-muted-foreground">--:--:--</span>
				<span class="text-muted-foreground">local</span>
			</div>
			<div class="flex flex-wrap items-center gap-x-4 gap-y-1 font-mono text-xs text-muted-foreground">
				<span>Mejor banda ahora: <strong id="consola-mejor-banda" class="text-primary">—</strong></span>
				<span id="consola-proximo-evento">Próximo: calculando…</span>
				<button type="button" id="consola-rx-chip" class="rounded-full border border-border/70 px-2 py-0.5 hover:border-primary/40 hover:text-primary">🎧 Receptor: …</button>
				<button type="button" id="consola-estacion-chip" class="rounded-full border border-border/70 px-2 py-0.5 hover:border-primary/40 hover:text-primary">⚙ Mi estación</button>
			</div>
		</div>

		<div id="consola-alerta-storm" role="alert" aria-live="assertive" class="hidden items-center gap-2 border-b border-rose-500/40 bg-rose-500/10 px-4 py-2 font-mono text-xs font-bold text-rose-400 sm:px-5">
			<span>⚠️</span>
			<span id="consola-alerta-storm-texto">Tormenta geomagnética en curso — HF puede estar muy afectado. Prioriza 40m/80m.</span>
		</div>

		<div class="rau-solo-consola border-b border-primary/20 bg-black/30 px-4 py-3 sm:px-5">
			<p class="mb-2 font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-primary/70">📻 Radio Now</p>
			<?php
			$rau_rn_rep = rau_get_repetidor_principal();
			$rau_rn_iconos = [ 'activo' => '🟢', 'intermitente' => '🟡', 'mantenimiento' => '🔵', 'fuera de servicio' => '🔴' ];
			$rau_rn_estado = strtolower( str_replace( '_', ' ', trim( $rau_rn_rep['status'] ?? 'activo' ) ) );
			$rau_rn_icono = $rau_rn_iconos[ $rau_rn_estado ] ?? '⚪';
			?>
			<div id="consola-radio-now" class="flex flex-wrap gap-x-5 gap-y-1.5 font-mono text-xs">
				<span id="rn-banda" class="text-muted-foreground">📡 Banda: calculando…</span>
				<span id="rn-red">📡 <?php echo esc_html( $rau_rn_rep['callsign'] ?? 'HK4RAU' ); ?> — <?php echo esc_html( $rau_rn_icono . ' ' . ucfirst( $rau_rn_estado ) ); ?></span>
				<span id="rn-satelite" class="text-muted-foreground">🛰️ calculando…</span>
				<span id="rn-dx" class="text-muted-foreground">🌎 DX: calculando…</span>
			</div>
			<div class="mt-2.5 flex flex-wrap gap-1.5" id="consola-que-hacer">
				<a href="<?php echo esc_url( home_url( '/herramientas/satpass/' ) ); ?>" class="rounded-full border border-border/70 bg-background px-2.5 py-1 font-mono text-[10px] text-muted-foreground hover:border-primary/40 hover:text-primary">🛰️ Ver próxima pasada</a>
				<a href="<?php echo esc_url( home_url( '/herramientas/dx/' ) ); ?>" class="rounded-full border border-border/70 bg-background px-2.5 py-1 font-mono text-[10px] text-muted-foreground hover:border-primary/40 hover:text-primary">🌎 Buscar DX</a>
				<button type="button" id="consola-mas-herramientas" class="rounded-full border border-dashed border-border/70 bg-transparent px-2.5 py-1 font-mono text-[10px] text-muted-foreground hover:border-primary/40 hover:text-primary">+ Más herramientas</button>
			</div>
		</div>

		<?php // Centro de mando (19.46.0): el receptor del club en el centro y todo lo demás conectado a
		      // su frecuencia. Izquierda: mirar (condiciones, clima espacial, satélites, redes digitales).
		      // Centro: escuchar (receptor, estaciones en la banda, mapa). Derecha: operar (mi estación,
		      // bitácora, frecuencias, notas, comunidad). En celular las tres columnas son pestañas. ?>
		<div id="consola-columnas" class="rau-solo-consola grid grid-cols-1 gap-4 p-3 sm:p-4 lg:grid-cols-12" data-pestana="escuchar">

			<!-- ══ CENTRO: ESCUCHAR (DOM primero = prioridad en móvil) ══ -->
			<div class="rau-col space-y-5 lg:order-2 lg:col-span-6" data-col="escuchar">
				<div>
					<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
						<p class="font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-primary/70">🎧 Receptor HK4RAU · en vivo desde Medellín</p>
						<a href="<?php echo esc_url( home_url( '/herramientas/receptor/' ) ); ?>" id="consola-rx-completo" class="font-mono text-[10px] text-muted-foreground hover:text-primary">Pantalla completa ↗</a>
					</div>
					<div id="consola-receptor-slot"></div>
				</div>

				<div id="consola-banda">
					<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
						<p class="font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-primary/70">📶 Estaciones en <span id="consola-banda-nombre">la banda</span></p>
						<div class="flex flex-wrap items-center gap-1.5 font-mono text-[10px]">
							<div class="inline-flex overflow-hidden rounded-md border border-border/70" role="group" aria-label="Qué estaciones mostrar">
								<button type="button" data-filtro="banda" class="rau-banda-filtro is-active px-2 py-1">Esta banda</button>
								<button type="button" data-filtro="todas" class="rau-banda-filtro border-l border-border/70 px-2 py-1">Todas</button>
							</div>
							<label class="flex items-center gap-1 text-muted-foreground"><input type="checkbox" id="consola-banda-dx" checked class="accent-primary"> <span class="text-amber-400">▼</span> Cluster DX</label>
							<label class="flex items-center gap-1 text-muted-foreground"><input type="checkbox" id="consola-banda-pota" checked class="accent-primary"> <span class="text-emerald-400">▼</span> POTA</label>
						</div>
					</div>
					<div class="rounded-xl border border-border/70 bg-card p-2.5">
						<div id="consola-banda-conteo" class="mb-2 grid grid-cols-8 gap-1" aria-label="Estaciones reportadas por banda"></div>
						<div id="consola-banda-lista" class="max-h-96 space-y-1 overflow-y-auto font-mono text-[11px] text-muted-foreground">Cargando estaciones…</div>
						<p class="mt-2 text-[10px] text-muted-foreground">🎧 la sintoniza en el receptor · 📝 la pasa a la bitácora · 📏 la ubica en el mapa. Las marcas ▼ también aparecen sobre la cascada.</p>
					</div>
				</div>

				<div id="consola-mapa-bloque">
					<p class="mb-2 font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-primary/70">📡 Mapa y propagación</p>
				<div id="consola-estado-bandas" class="mb-1 grid grid-cols-4 gap-1 sm:grid-cols-8">
					<div class="rau-banda-tile cursor-pointer rounded-md border border-border/70 bg-card px-1 py-1 text-center hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-ring" data-banda="80m" role="button" tabindex="0" aria-label="Ver por qué la banda 80m tiene este estado"><span class="block font-mono text-[9px] text-muted-foreground">80m</span><span class="block text-sm">⚪</span></div>
					<div class="rau-banda-tile cursor-pointer rounded-md border border-border/70 bg-card px-1 py-1 text-center hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-ring" data-banda="40m" role="button" tabindex="0" aria-label="Ver por qué la banda 40m tiene este estado"><span class="block font-mono text-[9px] text-muted-foreground">40m</span><span class="block text-sm">⚪</span></div>
					<div class="rau-banda-tile cursor-pointer rounded-md border border-border/70 bg-card px-1 py-1 text-center hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-ring" data-banda="30m" role="button" tabindex="0" aria-label="Ver por qué la banda 30m tiene este estado"><span class="block font-mono text-[9px] text-muted-foreground">30m</span><span class="block text-sm">⚪</span></div>
					<div class="rau-banda-tile cursor-pointer rounded-md border border-border/70 bg-card px-1 py-1 text-center hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-ring" data-banda="20m" role="button" tabindex="0" aria-label="Ver por qué la banda 20m tiene este estado"><span class="block font-mono text-[9px] text-muted-foreground">20m</span><span class="block text-sm">⚪</span></div>
					<div class="rau-banda-tile cursor-pointer rounded-md border border-border/70 bg-card px-1 py-1 text-center hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-ring" data-banda="17m" role="button" tabindex="0" aria-label="Ver por qué la banda 17m tiene este estado"><span class="block font-mono text-[9px] text-muted-foreground">17m</span><span class="block text-sm">⚪</span></div>
					<div class="rau-banda-tile cursor-pointer rounded-md border border-border/70 bg-card px-1 py-1 text-center hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-ring" data-banda="15m" role="button" tabindex="0" aria-label="Ver por qué la banda 15m tiene este estado"><span class="block font-mono text-[9px] text-muted-foreground">15m</span><span class="block text-sm">⚪</span></div>
					<div class="rau-banda-tile cursor-pointer rounded-md border border-border/70 bg-card px-1 py-1 text-center hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-ring" data-banda="12m" role="button" tabindex="0" aria-label="Ver por qué la banda 12m tiene este estado"><span class="block font-mono text-[9px] text-muted-foreground">12m</span><span class="block text-sm">⚪</span></div>
					<div class="rau-banda-tile cursor-pointer rounded-md border border-border/70 bg-card px-1 py-1 text-center hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-ring" data-banda="10m" role="button" tabindex="0" aria-label="Ver por qué la banda 10m tiene este estado"><span class="block font-mono text-[9px] text-muted-foreground">10m</span><span class="block text-sm">⚪</span></div>
				</div>
				<p id="consola-banda-explicacion" role="status" aria-live="polite" class="mb-2 hidden font-mono text-[10px] text-muted-foreground"></p>
				<div class="mb-2 flex flex-wrap items-center gap-1.5">
					<span class="font-mono text-[10px] uppercase text-muted-foreground">Modo:</span>
					<button type="button" class="rau-mapa-modo is-active rounded-md border border-primary/50 bg-primary/15 px-2.5 py-1 font-mono text-[11px] font-bold text-primary" data-modo-mapa="propagacion">🌐 Propagación</button>
					<button type="button" class="rau-mapa-modo rounded-md border border-border/70 bg-background px-2.5 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/40" data-modo-mapa="repetidores">📡 Repetidores</button>
					<button type="button" class="rau-mapa-modo rounded-md border border-border/70 bg-background px-2.5 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/40" data-modo-mapa="dx">🌎 DX</button>
				</div>
				<div class="mb-2 flex flex-wrap items-center gap-2">
					<label class="flex items-center gap-1.5 font-mono text-[11px] text-muted-foreground">
						<input type="checkbox" id="mapa-mostrar-comunidad" checked class="accent-primary">
						Actividad RAU
					</label>
					<label class="flex items-center gap-1.5 font-mono text-[11px] text-muted-foreground">
						<input type="checkbox" id="mapa-mostrar-satelite" checked class="accent-primary">
						🛰️ Satélite
					</label>
					<div class="flex items-center gap-1">
						<input aria-label="Grid DX" type="text" id="mapa-grid-manual" placeholder="Grid DX" maxlength="8" class="w-20 rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] uppercase focus:outline-none focus:ring-1 focus:ring-ring">
						<button type="button" id="mapa-grid-manual-btn" class="rounded-md border border-primary/40 bg-primary/10 px-2 py-1 font-mono text-[11px] font-bold text-primary hover:bg-primary/20">Ir</button>
					</div>
					<div class="flex items-center gap-1">
						<input aria-label="Indicativo DX" type="text" id="mapa-indicativo-manual" placeholder="Indicativo DX" maxlength="10" class="w-24 rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] uppercase focus:outline-none focus:ring-1 focus:ring-ring">
						<button type="button" id="mapa-indicativo-manual-btn" class="rounded-md border border-primary/40 bg-primary/10 px-2 py-1 font-mono text-[11px] font-bold text-primary hover:bg-primary/20">Ir</button>
					</div>
					<button type="button" id="mapa-unidad-toggle" data-unidad="km" class="rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/40">km</button>
					<button type="button" id="mi-qth-toggle" class="rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/40">📍 Mi QTH</button>
				</div>
				<div id="mi-qth-panel" class="mb-2 hidden flex-wrap items-center gap-1.5 rounded-md border border-border/70 bg-card p-2">
					<input aria-label="Grid (ej. FJ26)" type="text" id="mi-qth-grid" placeholder="Grid (ej. FJ26)" maxlength="8" class="w-28 rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] uppercase focus:outline-none focus:ring-1 focus:ring-ring">
					<button type="button" id="mi-qth-guardar" class="rounded-md border border-primary/40 bg-primary/10 px-2 py-1 font-mono text-[11px] font-bold text-primary hover:bg-primary/20">Guardar</button>
					<button type="button" id="mi-qth-geoloc" class="rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/40">Usar mi ubicación</button>
					<span id="mi-qth-estado" class="font-mono text-[10px] text-muted-foreground"></span>
				</div>
				<div class="mb-2 flex flex-wrap items-center gap-1.5">
					<span class="font-mono text-[10px] uppercase text-muted-foreground">Regiones rápidas:</span>
					<button type="button" class="rau-region-dx rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/40 hover:text-primary" data-lat="50" data-lng="10">🇪🇺 Europa</button>
					<button type="button" class="rau-region-dx rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/40 hover:text-primary" data-lat="39" data-lng="-98">🇺🇸 Norteamérica</button>
					<button type="button" class="rau-region-dx rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/40 hover:text-primary" data-lat="36" data-lng="138">🇯🇵 Asia</button>
					<button type="button" class="rau-region-dx rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/40 hover:text-primary" data-lat="-25" data-lng="135">🇦🇺 Oceanía</button>
					<button type="button" class="rau-region-dx rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/40 hover:text-primary" data-lat="0" data-lng="20">🌍 África</button>
				</div>
				<p class="mb-2 font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Haz clic en cualquier punto para estimar la propagación desde tu QTH</p>
				<?php // Más alto que las 24rem originales: el mapa terminaba mucho antes que las columnas
				      // laterales y dejaba un hueco central grande. ?>
				<div id="consola-mapa" class="h-72 w-full overflow-hidden rounded-xl sm:h-[26rem]"></div>
				<div id="mapa-clic-panel" class="mt-2 hidden rounded-xl border border-primary/30 bg-primary/5 p-3 font-mono text-xs"></div>
				<div id="mapa-prevision-24h" class="mt-2 hidden rounded-xl border border-border/70 bg-card p-3">
					<p class="mb-2 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Próximas 24 horas hacia este punto</p>
					<div id="mapa-prevision-barras" class="flex h-8 gap-px"></div>
					<div class="mt-1 flex justify-between font-mono text-[9px] text-muted-foreground">
						<span>ahora</span><span>+12h</span><span>+24h</span>
					</div>
					<p class="mt-2 font-mono text-[11px] text-primary" id="mapa-prevision-resumen"></p>
					<button type="button" id="mapa-compartir-texto" class="mt-2 rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[10px] text-muted-foreground hover:border-primary/40 hover:text-primary">💬 Copiar como texto</button>
				</div>
				<p id="mapa-clima-qth" class="mt-2 font-mono text-[11px] text-muted-foreground">Clima en tu QTH: cargando…</p>
				<p id="mapa-sol-qth-hoy" class="font-mono text-[11px] text-muted-foreground">☀ Hoy en tu QTH: calculando…</p>
				</div>
			</div>

			<!-- ══ IZQUIERDA: MIRAR ══ -->
			<div class="rau-col space-y-4 lg:order-1 lg:col-span-3" data-col="mirar">
				<div>
					<p class="mb-2 font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-primary/70">📊 Estado en vivo</p>
					<div class="grid grid-cols-5 gap-1 font-mono">
						<div class="rounded-lg border border-border/70 bg-card px-1 py-1.5 text-center"><span class="block text-[8px] uppercase text-muted-foreground">SFI</span><strong id="consola-sfi" class="block text-sm font-bold text-primary">—</strong></div>
						<div class="rounded-lg border border-border/70 bg-card px-1 py-1.5 text-center"><span class="block text-[8px] uppercase text-muted-foreground">SSN</span><strong id="consola-ssn" class="block text-sm font-bold text-primary">—</strong></div>
						<div class="rounded-lg border border-border/70 bg-card px-1 py-1.5 text-center"><span class="block text-[8px] uppercase text-muted-foreground">K</span><strong id="consola-k" class="block text-sm font-bold text-primary">—</strong></div>
						<div class="rounded-lg border border-border/70 bg-card px-1 py-1.5 text-center"><span class="block text-[8px] uppercase text-muted-foreground">A</span><strong id="consola-aindex" class="block text-sm font-bold text-primary">—</strong></div>
						<div class="rounded-lg border border-border/70 bg-card px-1 py-1.5 text-center"><span class="block text-[8px] uppercase text-muted-foreground">X</span><strong id="consola-xray" class="block text-sm font-bold text-primary">—</strong></div>
					</div>
					<div class="mt-2 flex flex-wrap gap-1">
						<a href="<?php echo esc_url( home_url( '/herramientas/sfihist/' ) ); ?>" class="rounded border border-border/70 bg-background px-1.5 py-0.5 font-mono text-[9px] text-muted-foreground hover:border-primary/40 hover:text-primary">Historial SFI/K</a>
						<a href="<?php echo esc_url( home_url( '/herramientas/ciclosolar/' ) ); ?>" class="rounded border border-border/70 bg-background px-1.5 py-0.5 font-mono text-[9px] text-muted-foreground hover:border-primary/40 hover:text-primary">Ciclo Solar</a>
					</div>
				</div>
				<div>
					<div class="mb-2 flex items-center justify-between">
						<p class="font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-primary/70">☀️ Clima espacial</p>
						<span id="consola-impacto-radio" class="font-mono text-[10px] text-muted-foreground">Calculando…</span>
					</div>
					<div class="space-y-2.5">
						<div class="rounded-xl border border-border/70 border-l-2 border-l-amber-400/60 bg-card p-2.5 shadow-sm">
							<div class="flex items-center gap-2.5">
								<img id="sol-vivo-img" src="" alt="Imagen del Sol en vivo (NASA SDO)" class="h-12 w-12 shrink-0 rounded-full border border-border object-cover" loading="lazy">
								<div class="min-w-0">
									<p class="font-mono text-[10px] font-semibold uppercase tracking-wide text-foreground/80">Sol en vivo</p>
									<p class="truncate text-[9px] text-muted-foreground">NASA SDO · cada 15 min</p>
								</div>
							</div>
							<div class="mt-1.5 flex gap-1">
								<button type="button" data-canal="hmiif" class="rau-sol-canal is-active rounded px-1.5 py-0.5 font-mono text-[9px] uppercase text-muted-foreground hover:text-primary">Visible</button>
								<button type="button" data-canal="0193" class="rau-sol-canal rounded px-1.5 py-0.5 font-mono text-[9px] uppercase text-muted-foreground hover:text-primary">AIA 193</button>
								<button type="button" data-canal="0304" class="rau-sol-canal rounded px-1.5 py-0.5 font-mono text-[9px] uppercase text-muted-foreground hover:text-primary">AIA 304</button>
							</div>
						</div>

						<div class="flex items-center gap-2.5 rounded-xl border border-border/70 border-l-2 border-l-violet-400/60 bg-card p-2.5 shadow-sm">
							<img id="aurora-img" src="" alt="Pronóstico de aurora (NOAA OVATION)" class="h-12 w-16 shrink-0 rounded-lg border border-border object-cover" loading="lazy">
							<div class="min-w-0">
								<p class="font-mono text-[10px] font-semibold uppercase tracking-wide text-foreground/80">Aurora</p>
								<p class="text-[9px] leading-tight text-muted-foreground">NOAA OVATION — actividad geomagnética</p>
							</div>
						</div>

						<div class="flex items-center gap-2.5 rounded-xl border border-border/70 border-l-2 border-l-slate-400/60 bg-card p-2.5 shadow-sm">
							<span id="consola-luna-icono" class="shrink-0 text-3xl">🌑</span>
							<div class="min-w-0">
								<p class="font-mono text-[10px] font-semibold uppercase tracking-wide text-foreground/80">Luna</p>
								<p class="truncate font-mono text-xs font-bold text-foreground"><span id="consola-luna-nombre">Calculando…</span> <span class="font-normal text-muted-foreground">· <span id="consola-luna-ilum">—</span>%</span></p>
								<p class="truncate text-[9px] text-muted-foreground" id="consola-luna-posicion">Calculando…</p>
								<a href="<?php echo esc_url( home_url( '/herramientas/luna/' ) ); ?>" class="text-[9px] text-primary hover:underline">Ver detalle EME →</a>
							</div>
						</div>

						<div class="rounded-xl border border-border/70 border-l-2 border-l-primary/60 bg-card p-2.5 shadow-sm">
							<p class="mb-1 font-mono text-[10px] font-semibold uppercase tracking-wide text-foreground/80">Tendencia SFI</p>
							<svg id="consola-tendencia-svg" viewBox="0 0 280 50" class="h-8 w-full"></svg>
							<p class="mt-0.5 truncate text-[9px] text-muted-foreground" id="consola-tendencia-texto">Cargando…</p>
						</div>
					</div>
				</div>

				<div class="rounded-xl border border-border/70 bg-card p-2.5">
					<div class="mb-1.5 flex items-center justify-between">
						<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">🛰 Satélites</p>
						<span id="sat-fuente-badge" class="hidden font-mono text-[9px] text-muted-foreground" title="De dónde vienen los datos orbitales (TLE) ahora mismo"></span>
					</div>
					<div class="mb-1.5 flex items-center gap-1.5">
						<select id="mapa-satelite-select" aria-label="Satélite a seguir en el mapa" class="w-full rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[11px] text-muted-foreground">
							<option value="">🛰 Sin satélite</option>
							<option value="ZARYA" selected>🛰 ISS</option>
							<option value="SO-50">🛰 SO-50</option>
							<option value="AO-91">🛰 AO-91</option>
							<option value="RS-44">🛰 RS-44</option>
							<option value="FO-29">🛰 FO-29</option>
							<option value="AO-7">🛰 AO-7</option>
						</select>
					</div>
					<p id="mapa-satelite-pasada" class="mb-1.5 font-mono text-[10px] text-muted-foreground"></p>
					<p id="mapa-satelite-detalle" class="mb-1.5 hidden font-mono text-[9px] text-muted-foreground"></p>
					<div id="mapa-satelite-vivo" class="mb-1.5 hidden rounded-md border border-border/70 bg-background p-2 transition-colors">
						<div class="flex items-center justify-between font-mono text-[10px]">
							<span id="mapa-satelite-vivo-estado" class="text-muted-foreground">—</span>
							<span id="mapa-satelite-vivo-azel" class="text-foreground"></span>
						</div>
						<div id="mapa-satelite-vivo-doppler" class="mt-1 font-mono text-[9px] text-muted-foreground"></div>
					</div>
					<div id="mapa-satelites-lista" class="space-y-1 font-mono text-[11px]">Cargando…</div>
					<div id="qo100-widget" class="mt-2 border-t border-border/50 pt-2 font-mono text-[10px] text-muted-foreground">
						🛰️ QO-100 (geoestacionario): calculando…
					</div>
					<div class="mt-2 flex flex-wrap gap-1">
						<a href="<?php echo esc_url( home_url( '/herramientas/satpass/' ) ); ?>" class="rounded border border-border/70 bg-background px-1.5 py-0.5 font-mono text-[9px] text-muted-foreground hover:border-primary/40 hover:text-primary">Pasadas detalladas</a>
						<a href="<?php echo esc_url( home_url( '/herramientas/satfreq/' ) ); ?>" class="rounded border border-border/70 bg-background px-1.5 py-0.5 font-mono text-[9px] text-muted-foreground hover:border-primary/40 hover:text-primary">Frecuencias</a>
						<a href="<?php echo esc_url( home_url( '/herramientas/doppler/' ) ); ?>" class="rounded border border-border/70 bg-background px-1.5 py-0.5 font-mono text-[9px] text-muted-foreground hover:border-primary/40 hover:text-primary">Doppler</a>
					</div>
					<div class="mt-2 rounded-md border border-border/70 bg-background p-2">
						<p class="mb-1.5 font-mono text-[9px] uppercase tracking-wider text-muted-foreground">🔥 Más activos ahora (AMSAT)</p>
						<div id="amsat-actividad-lista" class="space-y-1">
							<p class="font-mono text-[9px] text-muted-foreground">Cargando…</p>
						</div>
					</div>
					<div class="mt-2 rounded-md border border-border/70 bg-background p-2">
						<p class="mb-1.5 font-mono text-[9px] uppercase tracking-wider text-muted-foreground">📅 Próximas activaciones (hams.at)</p>
						<div id="hamsat-activaciones-lista" class="space-y-1">
							<p class="font-mono text-[9px] text-muted-foreground">Cargando…</p>
						</div>
					</div>
					<div class="mt-2 grid grid-cols-2 gap-1.5">
						<a href="https://hams.at/" target="_blank" rel="noopener" class="flex items-center gap-1.5 rounded-md border border-border/70 bg-background px-2 py-1.5 hover:border-primary/40">
							<span class="text-sm">🌐</span>
							<span class="flex flex-col leading-tight">
								<span class="font-mono text-[9px] font-bold text-foreground">hams.at</span>
								<span class="font-mono text-[8px] text-muted-foreground">Pasadas y activaciones</span>
							</span>
						</a>
						<a href="https://www.amsat.org/status/" target="_blank" rel="noopener" class="flex items-center gap-1.5 rounded-md border border-border/70 bg-background px-2 py-1.5 hover:border-primary/40">
							<span class="text-sm">📶</span>
							<span class="flex flex-col leading-tight">
								<span class="font-mono text-[9px] font-bold text-foreground">AMSAT status</span>
								<span class="font-mono text-[8px] text-muted-foreground">Actividad reportada</span>
							</span>
						</a>
					</div>
				</div>

				<div>
					<p class="mb-2 font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-primary/70">📻 DMR Colombia en vivo (BrandMeister)</p>
					<div class="rounded-xl border border-border/70 bg-card p-2.5">
						<div id="consola-bm-lastheard" class="space-y-1 font-mono text-[10px] text-muted-foreground">Conectando…</div>
					</div>
				</div>

				<div>
					<p class="mb-2 font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-primary/70">📡 WSPR desde Colombia (últimos 30 min)</p>
					<div class="rounded-xl border border-border/70 bg-card p-2.5">
						<div id="consola-wspr-lista" class="space-y-1 font-mono text-[10px] text-muted-foreground">Cargando…</div>
					</div>
				</div>

				<div>
					<p class="mb-2 font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-primary/70">🌐 D-STAR Last Heard (global)</p>
					<div class="rounded-xl border border-border/70 bg-card p-2.5">
						<div id="consola-dstar-lista" class="space-y-1 font-mono text-[10px] text-muted-foreground">Cargando…</div>
					</div>
				</div>
			</div>

			<!-- ══ DERECHA: OPERAR ══ -->
			<div class="rau-col space-y-4 lg:order-3 lg:col-span-3" data-col="operar">
				<div id="consola-estacion" class="rounded-xl border border-primary/30 bg-card p-2.5">
					<div class="mb-1.5 flex items-center justify-between gap-2">
						<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">⚙ Mi estación</p>
						<button type="button" id="estacion-editar" class="font-mono text-[10px] text-primary hover:underline" aria-expanded="false" aria-controls="estacion-form">Configurar</button>
					</div>
					<p id="estacion-resumen" class="font-mono text-[11px] text-muted-foreground">Configúrala una vez: tu indicativo y tu locator llenan la bitácora, la QSL, el certificado y las distancias a cada estación.</p>
					<div id="estacion-form" class="mt-2 hidden">
						<div class="grid grid-cols-2 gap-1">
							<input aria-label="Mi indicativo" type="text" id="estacion-indicativo" placeholder="Indicativo" maxlength="12" class="rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] uppercase focus:outline-none focus:ring-1 focus:ring-ring">
							<input aria-label="Mi locator (grid)" type="text" id="estacion-locator" placeholder="Locator FJ26" maxlength="8" class="rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] uppercase focus:outline-none focus:ring-1 focus:ring-ring">
							<input aria-label="Potencia en vatios" type="number" id="estacion-potencia" placeholder="Vatios" min="0" max="2000" class="rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] focus:outline-none focus:ring-1 focus:ring-ring">
							<input aria-label="Antena" type="text" id="estacion-antena" placeholder="Antena" maxlength="30" class="rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] focus:outline-none focus:ring-1 focus:ring-ring">
						</div>
						<button type="button" id="estacion-guardar" class="mt-1.5 w-full rounded-md border border-primary/40 bg-primary/10 py-1 font-mono text-[11px] font-bold text-primary hover:bg-primary/20">Guardar en este navegador</button>
						<p id="estacion-estado" role="status" aria-live="polite" class="mt-1 font-mono text-[10px] text-muted-foreground"></p>
					</div>
				</div>

					<div class="rounded-xl border border-border/70 bg-card p-2.5">
					<div class="mb-1.5 flex items-center justify-between">
						<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">📝 Bitácora rápida</p>
						<span class="font-mono text-[10px] text-primary">hoy: <strong id="bitacora-contador-hoy">0</strong></span>
					</div>
					<div class="grid grid-cols-2 gap-1">
						<input aria-label="Indicativo" type="text" id="bitacora-indicativo" placeholder="Indicativo" maxlength="12" class="rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] uppercase focus:outline-none focus:ring-1 focus:ring-ring">
						<input aria-label="MHz" type="text" id="bitacora-freq" placeholder="MHz" maxlength="10" class="rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] focus:outline-none focus:ring-1 focus:ring-ring">
						<select id="bitacora-modo" aria-label="Modo de la comunicación" class="rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] focus:outline-none focus:ring-1 focus:ring-ring">
							<option>SSB</option><option>CW</option><option>FM</option><option>FT8</option><option>FT4</option><option>RTTY</option><option>PSK31</option>
						</select>
						<input aria-label="RST" type="text" id="bitacora-rst" placeholder="RST" maxlength="6" class="rounded-md border border-input bg-background px-2 py-1 font-mono text-[11px] focus:outline-none focus:ring-1 focus:ring-ring">
					</div>
					<button type="button" id="bitacora-guardar" class="mt-1.5 w-full rounded-md border border-primary/40 bg-primary/10 py-1 font-mono text-[11px] font-bold text-primary hover:bg-primary/20">+ Registrar QSO</button>
					<div id="bitacora-confirmacion" role="status" aria-live="polite" class="hidden mt-1.5 rounded-md border border-emerald-400/40 bg-emerald-400/10 p-2 text-center font-mono text-[11px] text-emerald-400"></div>
					<div id="bitacora-lista" class="mt-2 max-h-32 space-y-1 overflow-y-auto font-mono text-[10px] text-muted-foreground"></div>
					<p class="mt-1.5 text-[9px] text-muted-foreground">Guardado solo en este navegador — para tu log oficial usa <a href="<?php echo esc_url( home_url( '/herramientas/log/' ) ); ?>" class="text-primary hover:underline">Log ADIF</a> <span id="consola-logadif-conteo"></span>.</p>
				</div>

				<div class="rounded-xl border border-border/70 bg-card p-2.5">
					<p class="mb-1.5 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">📻 Frecuencias rápidas <span class="normal-case text-muted-foreground">(clic para copiar)</span></p>
					<div class="space-y-1 font-mono text-[11px]">
						<div class="flex justify-between"><span class="text-muted-foreground">VHF FM llamada</span><button type="button" class="rau-freq-copiar text-primary hover:underline" data-freq="146.520">146.520</button></div>
						<?php $rau_rep_principal = rau_get_repetidor_principal(); ?>
						<div class="flex justify-between"><span class="text-muted-foreground">Repetidora RAU</span><button type="button" class="rau-freq-copiar text-primary hover:underline" data-freq="<?php echo esc_attr( $rau_rep_principal['frequency'] ); ?>"><?php echo esc_html( $rau_rep_principal['frequency'] ); ?></button></div>
						<div class="flex justify-between"><span class="text-muted-foreground">FT8 20m</span><button type="button" class="rau-freq-copiar text-primary hover:underline" data-freq="14.074">14.074</button></div>
						<div class="flex justify-between"><span class="text-muted-foreground">FT8 40m</span><button type="button" class="rau-freq-copiar text-primary hover:underline" data-freq="7.074">7.074</button></div>
						<div class="flex justify-between"><span class="text-muted-foreground">QRP CW 40m</span><button type="button" class="rau-freq-copiar text-primary hover:underline" data-freq="7.030">7.030</button></div>
					</div>
				</div>

				<?php $rau_rep_red = rau_get_repetidor_principal(); ?>
				<div class="rounded-xl border border-border/70 bg-card p-2.5">
					<p class="mb-1.5 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">🗒 Notas rápidas</p>
					<textarea aria-label="Recordatorios de esta sesión" id="consola-notas" rows="3" placeholder="Recordatorios de esta sesión…" class="w-full resize-none rounded-md border border-input bg-background px-2 py-1.5 font-mono text-[11px] focus:outline-none focus:ring-1 focus:ring-ring"></textarea>
					<p class="mt-1 text-[9px] text-muted-foreground">Se guarda solo en este navegador, se borra si limpias el caché.</p>
				</div>

					<div class="rounded-xl border border-border/70 bg-card p-2.5">
					<p class="mb-1.5 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">📡 Estado de la red</p>
					<?php
					$estado_iconos = [ 'activo' => '🟢', 'intermitente' => '🟡', 'mantenimiento' => '🔵', 'fuera de servicio' => '🔴' ];
					$estado_actual = $rau_rep_red['status'] ?? 'activo';
					$estado_normalizado = strtolower( str_replace( '_', ' ', trim( $estado_actual ) ) );
					$estado_icono = $estado_iconos[ $estado_normalizado ] ?? '⚪';
					?>
					<div class="flex items-center justify-between">
						<span class="font-mono text-xs font-bold"><?php echo esc_html( $rau_rep_red['callsign'] ?? 'HK4RAU' ); ?></span>
						<span class="font-mono text-[10px]"><?php echo esc_html( $estado_icono ); ?> <?php echo esc_html( ucfirst( str_replace( '_', ' ', $estado_actual ) ) ); ?></span>
					</div>
					<p class="mt-0.5 text-[9px] text-muted-foreground"><?php echo esc_html( $rau_rep_red['frequency'] ?? '—' ); ?> MHz · <?php echo esc_html( $rau_rep_red['location'] ?? '—' ); ?></p>
				</div>

				<div>
					<p class="mb-2 font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-primary/70">🏆 Mi Progreso</p>
					<div class="rounded-xl border border-border/70 bg-card p-2.5">
						<div id="consola-dxcc-was" class="space-y-2">
							<p class="font-mono text-[10px] text-muted-foreground">Cargando…</p>
						</div>
					</div>
				</div>

				<div>
					<p class="mb-2 font-mono text-[10px] font-bold uppercase tracking-[0.15em] text-amber-400/70">🤝 Comunidad RAU</p>
					<div class="space-y-2.5">
						<div class="rounded-xl border border-border/70 border-l-2 border-l-primary/60 bg-card p-2.5 shadow-sm">
							<p class="mb-1 font-mono text-[10px] font-semibold uppercase tracking-wide text-foreground/80">Concursos</p>
							<div id="consola-concursos-lista" class="space-y-0.5 font-mono text-[10px] text-muted-foreground">Cargando…</div>
							<a href="<?php echo esc_url( home_url( '/herramientas/concursos/' ) ); ?>" class="mt-1 block text-[9px] text-primary hover:underline">Ver calendario completo →</a>
						</div>

						<div class="rounded-xl border border-amber-400/30 border-l-2 border-l-amber-400 bg-amber-400/5 p-2.5 shadow-sm">
							<p class="mb-1 font-mono text-[10px] font-semibold uppercase tracking-wide text-amber-400">🔥 Racha</p>
							<div id="consola-racha-lista" class="space-y-0.5">
								<p class="text-[10px] text-muted-foreground">Cargando…</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<nav id="consola-pestanas" class="rau-solo-consola" aria-label="Secciones del centro de mando">
			<button type="button" data-ir="escuchar" class="is-active"><span aria-hidden="true">🎧</span>Escuchar</button>
			<button type="button" data-ir="mirar"><span aria-hidden="true">🌍</span>Mirar</button>
			<button type="button" data-ir="operar"><span aria-hidden="true">📝</span>Operar</button>
			<button type="button" data-ir="explorar"><span aria-hidden="true">🧰</span>Explorar</button>
		</nav>

		<!-- MI ESPACIO DE TRABAJO: pestañas fijadas por el usuario, siempre visibles -->
		<div id="consola-fijados-wrap" class="hidden flex-wrap items-center gap-1.5 border-b border-primary/20 bg-black/30 px-3 py-2 sm:px-4">
			<span class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">📌 Mi espacio:</span>
			<div id="consola-fijados-lista" class="flex flex-wrap items-center gap-1.5"></div>
		</div>

		<!-- Selector de modo: elegir uno REEMPLAZA el contenido, no se apila -->
		<div class="consola-modos flex gap-1.5 overflow-x-auto border-b border-primary/20 bg-black/20 px-3 py-2.5 sm:px-4" role="tablist">
			<button type="button" role="tab" aria-selected="true" tabindex="0" data-modo="antena" class="consola-modo-btn is-active shrink-0 rounded-lg px-3 py-2 font-mono text-xs font-bold uppercase tracking-wide">🔧 Construir antena</button>
			<button type="button" role="tab" aria-selected="false" tabindex="-1" data-modo="transmitir" class="consola-modo-btn shrink-0 rounded-lg px-3 py-2 font-mono text-xs font-bold uppercase tracking-wide">📡 Antes de transmitir</button>
				<button type="button" role="tab" aria-selected="false" tabindex="-1" data-modo="concurso" class="consola-modo-btn shrink-0 rounded-lg px-3 py-2 font-mono text-xs font-bold uppercase tracking-wide">🏆 En concurso</button>
				<button type="button" role="tab" aria-selected="false" tabindex="-1" data-modo="campo" class="consola-modo-btn shrink-0 rounded-lg px-3 py-2 font-mono text-xs font-bold uppercase tracking-wide">📻 En el campo</button>
			<span class="mx-1 hidden h-6 w-px shrink-0 self-center bg-primary/20 sm:block"></span>
			<?php // El número estaba escrito a mano y se quedó en 53 al agregar una herramienta nueva.
			      // Contarlo del mismo array que genera las pestañas evita que se vuelva a desfasar. ?>
			<button type="button" role="tab" aria-selected="false" tabindex="-1" data-modo="catalogo" class="consola-modo-btn shrink-0 rounded-lg px-3 py-2 font-mono text-xs font-bold uppercase tracking-wide">📂 Catálogo (<?php echo count( array_filter( rau_get_herramientas_tabs(), fn( $t ) => empty( $t['oculta'] ) ) ); ?>)</button>
		</div>

		<!-- Contenido del modo activo -->
		<div class="p-4 sm:p-5">

			<div data-modo-panel="antena" class="consola-panel">
				<div class="mb-3 flex items-center justify-between">
					<p class="text-xs text-muted-foreground">Estas 4 calculadoras comparten la misma frecuencia — cámbiala y las 4 se actualizan a la vez.</p>
					<div class="flex shrink-0 items-center gap-2">
						<button type="button" data-modo-fijar="antena" class="rau-fijar-modo text-lg text-muted-foreground hover:text-amber-400" title="Fijar en Mi Espacio">☆</button>
						<label for="banco-freq" class="font-mono text-[11px] uppercase text-muted-foreground">MHz</label>
						<input type="number" id="banco-freq" value="14.15" step="0.01" class="w-24 rounded-md border border-input bg-background px-2 py-1.5 font-mono text-sm text-center focus:outline-none focus:ring-2 focus:ring-ring">
					</div>
				</div>
				<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
					<div class="rounded-xl border border-border/70 bg-card p-4">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Dipolo λ/2</p>
						<p class="font-mono text-xl font-bold" id="banco-dipolo">—</p>
						<p class="text-[10px] text-muted-foreground">largo total (vf=0.95)</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-card p-4">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Yagi 2 el.</p>
						<p class="font-mono text-sm" id="banco-yagi">—</p>
						<p class="text-[10px] text-muted-foreground">driven / reflector</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-card p-4">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Radiales de tierra</p>
						<p class="font-mono text-xl font-bold" id="banco-radiales">—</p>
						<p class="text-[10px] text-muted-foreground">largo por radial (λ/4)</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-card p-4">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Smith Chart</p>
						<div class="mb-1 flex gap-1">
							<input aria-label="R" type="number" id="banco-r" value="75" placeholder="R" class="w-1/2 rounded border border-input bg-background px-1.5 py-1 font-mono text-xs">
							<input aria-label="X" type="number" id="banco-x" value="25" placeholder="X" class="w-1/2 rounded border border-input bg-background px-1.5 py-1 font-mono text-xs">
						</div>
						<p class="font-mono text-sm font-bold" id="banco-vswr">—</p>
						<p class="text-[10px] text-muted-foreground">ROE (Z₀=50Ω)</p>
					</div>
				</div>
				<div class="mt-3 flex flex-wrap items-center gap-2">
					<span class="font-mono text-[10px] uppercase text-muted-foreground">🧮 Calculadoras:</span>
					<a href="<?php echo esc_url( home_url( '/herramientas/calc/' ) ); ?>" class="rounded-lg border border-primary/30 bg-primary/5 px-3 py-1.5 font-mono text-[11px] text-primary hover:bg-primary/10">Más tipos de antena (loop, 5/8...)</a>
					<a href="<?php echo esc_url( home_url( '/herramientas/rfconv/' ) ); ?>" class="rounded-lg border border-primary/30 bg-primary/5 px-3 py-1.5 font-mono text-[11px] text-primary hover:bg-primary/10">Conversor RF</a>
				</div>
				<div class="mt-2 flex flex-wrap items-center gap-2">
					<span class="font-mono text-[10px] uppercase text-muted-foreground">📖 Referencia:</span>
					<a href="<?php echo esc_url( home_url( '/herramientas/colores/' ) ); ?>" class="rounded-lg border border-border/70 bg-background px-3 py-1.5 font-mono text-[11px] text-muted-foreground hover:border-primary/40 hover:text-primary">Código de Colores (resistores)</a>
				</div>
			</div>

			<div data-modo-panel="transmitir" class="consola-panel hidden">
				<div class="mb-3 flex items-center justify-between">
					<p class="text-xs text-muted-foreground">Todo lo que revisarías por separado antes de encender el equipo.</p>
					<div class="flex shrink-0 items-center gap-2">
						<button type="button" data-modo-fijar="transmitir" class="rau-fijar-modo text-lg text-muted-foreground hover:text-amber-400" title="Fijar en Mi Espacio">☆</button>
						<button class="rau-push-generic-btn rounded-md border border-primary/40 bg-primary/10 px-3 py-1.5 font-mono text-xs font-bold text-primary hover:bg-primary/20">🔔 Avisarme</button>
					</div>
				</div>
				<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
					<div class="rounded-xl border border-border/70 bg-card p-4">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Hora UTC</p>
						<p class="font-mono text-2xl font-bold" id="atx-utc">—:—:—</p>
						<p class="text-[10px] text-muted-foreground" id="atx-local">hora local —</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-card p-4">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">SFI / K / A</p>
						<p class="font-mono text-2xl font-bold" id="atx-sfi">—</p>
						<p class="text-[10px] text-muted-foreground" id="atx-ka">K=— · A=—</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-card p-4">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Rayos X</p>
						<p class="font-mono text-xl font-bold" id="atx-xray">—</p>
						<p class="text-[10px] text-muted-foreground" id="atx-apagon">—</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-card p-4">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Bandas abiertas ahora</p>
						<div id="atx-bandas" class="flex flex-wrap gap-1"><span class="text-[11px] text-muted-foreground">Cargando…</span></div>
					</div>
				</div>

				<div class="mt-3 overflow-hidden rounded-xl border border-border/70">
					<table class="w-full font-mono text-xs">
						<thead class="bg-black/20 text-muted-foreground">
							<tr><th class="px-3 py-1.5 text-left">Banda</th><th class="px-3 py-1.5 text-left">Día</th><th class="px-3 py-1.5 text-left">Noche</th></tr>
						</thead>
						<tbody id="atx-tabla-bandas"></tbody>
					</table>
				</div>
				<p class="mt-3 text-[11px] text-muted-foreground" id="atx-actualizado"></p>
				<div class="mt-3 flex flex-wrap items-center gap-2">
					<span class="flex items-center gap-1 font-mono text-[10px] uppercase text-muted-foreground"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>En vivo:</span>
					<a href="<?php echo esc_url( home_url( '/herramientas/apagon/' ) ); ?>" class="rounded-lg border border-emerald-400/30 bg-emerald-400/5 px-3 py-1.5 font-mono text-[11px] text-emerald-400 hover:bg-emerald-400/10">Predictor de Apagón HF</a>
					<a href="<?php echo esc_url( home_url( '/herramientas/bandalert/' ) ); ?>" class="rounded-lg border border-emerald-400/30 bg-emerald-400/5 px-3 py-1.5 font-mono text-[11px] text-emerald-400 hover:bg-emerald-400/10">Alertas de Banda</a>
				</div>
				<div class="mt-2 flex flex-wrap items-center gap-2">
					<span class="font-mono text-[10px] uppercase text-muted-foreground">📖 Referencia:</span>
					<a href="<?php echo esc_url( home_url( '/herramientas/bandas/' ) ); ?>" class="rounded-lg border border-border/70 bg-background px-3 py-1.5 font-mono text-[11px] text-muted-foreground hover:border-primary/40 hover:text-primary">Bandas Colombia (normativa)</a>
				</div>
			</div>

			<div data-modo-panel="campo" class="consola-panel hidden">
				<div class="mb-3 flex items-center justify-between">
					<p class="text-xs text-muted-foreground">Ubicación, repetidor más cercano, y batería — lo esencial para una activación o salida de campo.</p>
					<div class="flex shrink-0 items-center gap-2">
						<button type="button" data-modo-fijar="campo" class="rau-fijar-modo text-lg text-muted-foreground hover:text-amber-400" title="Fijar en Mi Espacio">☆</button>
						<button id="campo-gps" type="button" class="rounded-md border border-primary/40 bg-primary/10 px-3 py-1.5 font-mono text-xs font-bold text-primary hover:bg-primary/20">📍 Usar mi ubicación</button>
					</div>
				</div>
				<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
					<div class="rounded-xl border border-border/70 bg-card p-4">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Tu grid</p>
						<p class="font-mono text-2xl font-bold" id="campo-grid">—</p>
						<p class="text-[10px] text-muted-foreground">toca "Usar mi ubicación"</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-card p-4 sm:col-span-1 lg:col-span-2">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Repetidor más cercano</p>
						<p class="font-mono text-lg font-bold" id="campo-repetidor">—</p>
						<p class="text-[10px] text-muted-foreground" id="campo-repetidor-detalle"></p>
					</div>
					<div class="rounded-xl border border-border/70 bg-card p-4">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Batería rápida</p>
						<div class="mb-1 flex gap-1">
							<input aria-label="MAh" type="number" id="campo-bat-cap" value="10000" placeholder="mAh" class="w-1/2 rounded border border-input bg-background px-1.5 py-1 font-mono text-xs">
							<input aria-label="MA TX" type="number" id="campo-bat-tx" value="2000" placeholder="mA TX" class="w-1/2 rounded border border-input bg-background px-1.5 py-1 font-mono text-xs">
						</div>
						<p class="font-mono text-lg font-bold" id="campo-bat-horas">—</p>
						<p class="text-[10px] text-muted-foreground">autonomía estimada (duty 25%)</p>
					</div>
				</div>
				<?php $rau_rep_campo = rau_get_repetidor_principal(); $rau_tono_fmt = rtrim( rtrim( number_format( (float) $rau_rep_campo['tone'], 1 ), '0' ), '.' ); ?>
				<p class="mt-3 text-[11px] text-muted-foreground">🚨 Emergencia: <?php echo esc_html( $rau_rep_campo['frequency'] ); ?> MHz (T<?php echo esc_html( $rau_tono_fmt ); ?>) · Simplex: 146.520 MHz</p>
				<div class="mt-3 flex flex-wrap items-center gap-2">
					<span class="font-mono text-[10px] uppercase text-muted-foreground">📖 Referencia:</span>
					<a href="<?php echo esc_url( home_url( '/herramientas/tarjeta/' ) ); ?>" class="rounded-lg border border-border/70 bg-background px-3 py-1.5 font-mono text-[11px] text-muted-foreground hover:border-primary/40 hover:text-primary">Tarjeta de Campo (imprimir)</a>
					<a href="<?php echo esc_url( home_url( '/herramientas/fonetico/' ) ); ?>" class="rounded-lg border border-border/70 bg-background px-3 py-1.5 font-mono text-[11px] text-muted-foreground hover:border-primary/40 hover:text-primary">Alfabeto Fonético</a>
					<a href="<?php echo esc_url( home_url( '/herramientas/codigosq/' ) ); ?>" class="rounded-lg border border-border/70 bg-background px-3 py-1.5 font-mono text-[11px] text-muted-foreground hover:border-primary/40 hover:text-primary">Código Q</a>
					<a href="<?php echo esc_url( home_url( '/herramientas/pota/' ) ); ?>" class="rounded-lg border border-emerald-400/30 bg-emerald-400/5 px-3 py-1.5 font-mono text-[11px] text-emerald-400 hover:bg-emerald-400/10">🏞 POTA Colombia (activación)</a>
				</div>
			</div>

			<div data-modo-panel="concurso" class="consola-panel hidden">
				<div class="mb-3 flex items-center justify-between">
					<span></span>
					<button type="button" data-modo-fijar="concurso" class="rau-fijar-modo text-lg text-muted-foreground hover:text-amber-400" title="Fijar en Mi Espacio">☆</button>
				</div>
				<div class="mb-3 rounded-xl border border-primary/30 bg-primary/5 p-4">
					<p class="font-mono text-xs uppercase tracking-wider text-muted-foreground">Próximo concurso</p>
					<p class="font-display text-lg font-bold" id="cm-proximo-nombre">Calculando…</p>
					<p class="font-mono text-sm text-primary" id="cm-proximo-faltan"></p>
				</div>
				<div class="grid gap-3 sm:grid-cols-3">
					<div class="rounded-xl border border-border/70 bg-card p-4 text-center sm:col-span-1">
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Contador de QSO</p>
						<p class="font-mono text-3xl font-bold" id="cm-qso-total">0</p>
						<p class="mb-2 text-[10px] text-muted-foreground" id="cm-qso-rate">0 QSO/hora</p>
						<button id="cm-qso-add" class="w-full rounded-md bg-primary py-2 text-sm font-bold text-primary-foreground">+1 QSO</button>
					</div>
					<a href="<?php echo esc_url( home_url( '/herramientas/cabrillo/' ) ); ?>" class="flex items-center justify-between rounded-xl border border-border/70 bg-card p-4 hover:border-primary/40 sm:col-span-1">
						<span><span class="block font-mono text-xs font-bold uppercase text-primary">Validador Cabrillo</span><span class="text-xs text-muted-foreground">Revisa tu log antes de enviarlo</span></span>
						<span class="font-mono text-xl text-primary">→</span>
					</a>
					<a href="<?php echo esc_url( home_url( '/herramientas/radiofaros/' ) ); ?>" class="flex items-center justify-between rounded-xl border border-border/70 bg-card p-4 hover:border-primary/40 sm:col-span-1">
						<span><span class="block font-mono text-xs font-bold uppercase text-primary">Radiofaros NCDXF</span><span class="text-xs text-muted-foreground">Siente la propagación en vivo</span></span>
						<span class="font-mono text-xl text-primary">→</span>
					</a>
				</div>
				<div class="mt-3 flex flex-wrap items-center gap-2">
					<span class="flex items-center gap-1 font-mono text-[10px] uppercase text-muted-foreground"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>En vivo:</span>
					<a href="<?php echo esc_url( home_url( '/herramientas/dx/' ) ); ?>" class="rounded-lg border border-emerald-400/30 bg-emerald-400/5 px-3 py-1.5 font-mono text-[11px] text-emerald-400 hover:bg-emerald-400/10">DX Spots</a>
					<a href="<?php echo esc_url( home_url( '/herramientas/rbn/' ) ); ?>" class="rounded-lg border border-emerald-400/30 bg-emerald-400/5 px-3 py-1.5 font-mono text-[11px] text-emerald-400 hover:bg-emerald-400/10">Reverse Beacon Network</a>
				</div>
				<div class="mt-2 flex flex-wrap items-center gap-2">
					<span class="font-mono text-[10px] uppercase text-muted-foreground">🧮 Rastreador:</span>
					<a href="<?php echo esc_url( home_url( '/herramientas/dxcc/' ) ); ?>" class="rounded-lg border border-primary/30 bg-primary/5 px-3 py-1.5 font-mono text-[11px] text-primary hover:bg-primary/10">DXCC/WAS</a>
				</div>
			</div>

		</div>
	</div>
		<div id="rau-explorar" class="mb-8 rounded-2xl border border-border/60 bg-card/40 p-4 sm:p-5">
			<div class="relative mb-5">
				<svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
				<input aria-label="Buscar una herramienta" type="search" id="rau-tool-search" autocomplete="off" placeholder="Buscar una herramienta…"
					class="w-full rounded-xl border border-input bg-background py-3 pl-11 pr-4 text-base focus:outline-none focus:ring-2 focus:ring-ring">
				<div id="rau-search-results" class="hidden absolute z-20 mt-1 w-full overflow-hidden rounded-xl border border-border bg-card shadow-lg"></div>
			</div>

			<p class="mb-2 font-mono text-xs font-semibold uppercase tracking-wider text-primary">Accesos rápidos</p>
			<div class="flex flex-wrap gap-2.5">
				<?php foreach ( [ 'dashboard', 'qsl', 'indicativo', 'cobertura' ] as $qid ) :
					$qt = current( array_filter( $tabs, fn( $x ) => $x['id'] === $qid ) );
					if ( ! $qt ) continue;
				?>
				<button type="button" data-tab="<?php echo esc_attr( $qt['id'] ); ?>" data-cat="<?php echo esc_attr( $qt['cat'] ); ?>"
					class="rau-quick-chip inline-flex items-center rounded-xl border-2 px-5 py-3 text-sm font-bold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background border-primary/40 bg-primary/10 text-primary hover:bg-primary/20">
					<?php echo esc_html( $qt['label'] ); ?>
				</button>
				<?php endforeach; ?>
			</div>

			<!-- FAVORITOS (poblado por JS desde localStorage, sin cuenta) -->
			<div id="rau-favoritos" class="mt-5 hidden border-t border-border/50 pt-4">
				<p class="mb-2 font-mono text-xs font-semibold uppercase tracking-wider text-amber-400">★ Tus favoritas</p>
				<div id="rau-favoritos-lista" class="flex flex-wrap gap-2"></div>
			</div>
		</div>

		<?php
		$cat_icons = [
			'Operar'      => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
			'Calcular'    => '<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="12" x2="8" y2="12"/><line x1="12" y1="12" x2="12" y2="12"/><line x1="16" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="12" y1="16" x2="12" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/>',
			'Localizar'   => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
			'Explorar'    => '<circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 010 8.49M7.76 16.24a6 6 0 010-8.49M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/>',
			'Escuchar'    => '<path d="M3 14a9 9 0 0118 0"/><path d="M3 14h3v7H3z"/><path d="M18 14h3v7h-3z"/>',
			'Aprender'    => '<path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/>',
			'Emergencias' => '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
		];
		?>


	<!-- ACCESO SECUNDARIO: EL CATÁLOGO POR CATEGORÍA — controlado por el mismo selector de modo -->
	<div id="rau-cat-grid-wrap" class="hidden">

		<!-- GRID DE CATEGORÍAS (pantalla de entrada) -->
		<div id="rau-cat-grid" class="mt-6 space-y-5">
			<?php foreach ( $tabs_by_cat as $cat_name => $cat_tabs ) : ?>
			<div data-cat="<?php echo esc_attr( $cat_name ); ?>" data-label="<?php echo esc_attr( mb_strtolower( $cat_name . ' ' . implode( ' ', wp_list_pluck( $cat_tabs, 'label' ) ) ) ); ?>"
				class="rau-cat-card rounded-2xl border border-border/60 bg-card/40 p-4 sm:p-5">
				<div class="mb-3 flex items-center gap-2.5 text-muted-foreground">
					<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $cat_icons[ $cat_name ] ?? '<circle cx="12" cy="12" r="9"/>'; ?></svg>
					<span class="font-mono text-xs font-bold uppercase tracking-wider"><?php echo esc_html( $cat_name ); ?></span>
					<span class="font-mono text-[10px] text-muted-foreground">· <?php echo count( $cat_tabs ); ?></span>
				</div>
				<div class="flex flex-wrap gap-2">
					<?php foreach ( $cat_tabs as $t ) : ?>
					<span class="inline-flex items-stretch overflow-hidden rounded-lg border border-border/70 bg-background hover:border-primary/50">
						<button type="button" data-tab="<?php echo esc_attr( $t['id'] ); ?>" aria-label="Marcar como favorita"
							class="rau-fav-star flex items-center border-r border-border/70 px-2 text-sm text-muted-foreground hover:text-amber-400">☆</button>
						<button type="button" data-tab="<?php echo esc_attr( $t['id'] ); ?>"
							class="rau-tool-pill inline-flex items-center px-3.5 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-primary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-ring">
							<?php echo esc_html( $t['label'] ); ?>
						</button>
					</span>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endforeach; ?>
			<p id="rau-tool-noresults" class="hidden py-2 text-sm text-muted-foreground">No hay ninguna herramienta que coincida con esa búsqueda.</p>
		</div>

		<div id="rau-panel-storage" class="hidden" aria-hidden="true"></div>

		<!-- DASHBOARD DE LA CATEGORÍA ABIERTA -->
		<div id="rau-cat-dashboard" class="mt-6 hidden">
			<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
				<div class="flex flex-wrap items-center gap-3">
					<button type="button" id="rau-volver-consola" class="inline-flex items-center gap-1.5 rounded-lg border border-primary/40 bg-primary/10 px-3 py-1.5 font-mono text-sm font-bold text-primary hover:bg-primary/20">◉ Centro de mando</button>
					<button type="button" id="rau-cat-back" class="inline-flex items-center gap-1.5 font-mono text-sm font-bold text-primary hover:underline">
						← Todas las categorías
					</button>
				</div>
				<div id="rau-cat-jumps" class="flex flex-wrap gap-2"></div>
			</div>
			<div id="rau-cat-panels" class="space-y-8"></div>
		</div>

	</div><!-- /rau-cat-grid-wrap -->

		<!-- DASHBOARD EN VIVO -->
		<div id="panel-dashboard" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'dashboard' ) ); ?>" class="rau-panel" style="display:none">
			<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
				<div>
					<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Panel de instrumentos</p>
					<h2 class="font-display text-2xl font-bold">Dashboard en Vivo</h2>
					<p class="mt-1 text-sm text-muted-foreground">Todo a la vista, se actualiza solo — ideal para dejar abierto en una segunda pantalla mientras operas.</p>
				</div>
				<a id="dashYtLiveBadge" href="#" target="_blank" rel="noreferrer" class="hidden animate-pulse items-center gap-1.5 rounded-full bg-rose-500 px-3 py-1 font-mono text-[11px] font-bold text-white">
					<span class="h-1.5 w-1.5 rounded-full bg-white"></span> EN VIVO
				</a>
			</div>
			<button id="rau-push-generic-btn" data-contexto="dashboard" type="button" class="rau-push-generic-btn mb-4 inline-flex items-center gap-2 rounded-md border-2 border-primary/40 bg-primary/10 px-4 py-2 text-xs font-bold text-primary hover:bg-primary/20">
				🔔 Activar notificaciones de RAU
			</button>
			<p class="rau-push-generic-status mb-4 hidden text-xs text-muted-foreground"></p>

			<div class="grid grid-cols-1 gap-3 lg:grid-cols-12">

				<!-- RELOJ -->
				<div class="rau-tile lg:col-span-4">
					<p class="rau-tile-label">Hora</p>
					<div class="grid grid-cols-2 gap-3 text-center">
						<div>
							<p id="dash-utc-time" class="font-mono text-3xl font-bold tabular-nums text-primary sm:text-4xl">--:--:--</p>
							<p class="mt-0.5 font-mono text-[10px] uppercase tracking-wider text-muted-foreground">UTC</p>
						</div>
						<div>
							<p id="dash-local-time" class="font-mono text-3xl font-bold tabular-nums text-foreground sm:text-4xl">--:--:--</p>
							<p class="mt-0.5 font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Colombia</p>
						</div>
					</div>
					<p id="dash-date" class="mt-3 text-center font-mono text-xs text-muted-foreground">—</p>
				</div>

				<!-- PROPAGACIÓN -->
				<div class="rau-tile lg:col-span-4">
					<p class="rau-tile-label">Propagación solar</p>
					<div class="grid grid-cols-4 gap-2 text-center">
						<div><p id="dash-sfi" class="font-mono text-xl font-bold text-primary">—</p><p class="rau-tile-sublabel">SFI</p></div>
						<div><p id="dash-a" class="font-mono text-xl font-bold text-foreground">—</p><p class="rau-tile-sublabel">Índice A</p></div>
						<div><p id="dash-k" class="font-mono text-xl font-bold text-foreground">—</p><p class="rau-tile-sublabel">Índice K</p></div>
						<div><p id="dash-xray" class="font-mono text-xl font-bold text-foreground">—</p><p class="rau-tile-sublabel">X-Ray</p></div>
					</div>
					<p id="dash-solar-status" class="mt-3 rounded-lg border border-border/50 bg-background/50 px-3 py-2 text-center text-xs text-muted-foreground">Cargando…</p>
				</div>

				<!-- KIWISDR -->
				<div class="rau-tile lg:col-span-4">
					<div class="mb-2 flex items-center justify-between">
						<p class="rau-tile-label !mb-0">KiwiSDR HK4RAU</p>
						<span id="dash-sdr-badge" class="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-background px-2 py-0.5">
							<span class="h-1.5 w-1.5 rounded-full bg-muted animate-pulse"></span>
							<span class="font-mono text-[10px] text-muted-foreground">…</span>
						</span>
					</div>
					<div id="dash-sdr-info" class="rounded-lg border border-border/50 bg-background p-3 font-mono text-[11px] text-muted-foreground">
						<p>Receptor de 0 a 30 MHz en Medellín, antena dipolo.</p>
						<p id="dash-sdr-detalle" class="mt-1">Consultando canales…</p>
					</div>
					<a href="<?php echo esc_url( home_url( '/herramientas/receptor/' ) ); ?>" id="dash-sdr-ir" class="mt-2 inline-flex w-full items-center justify-center rounded-lg bg-primary py-2 font-mono text-xs font-bold text-primary-foreground hover:bg-primary/90">Escuchar en vivo →</a>
				</div>

				<!-- CONDICIONES POR BANDA -->
				<div class="rau-tile lg:col-span-5">
					<p class="rau-tile-label">Condiciones por banda</p>
					<div id="dash-bands" class="grid grid-cols-2 gap-2 sm:grid-cols-4">
						<p class="col-span-2 text-center text-xs text-muted-foreground sm:col-span-4">Cargando…</p>
					</div>
				</div>

				<!-- PRÓXIMA PASADA ISS -->
				<div class="rau-tile lg:col-span-3">
					<p class="rau-tile-label">Próxima pasada — ISS</p>
					<div id="dash-iss-content">
						<p class="text-center text-xs text-muted-foreground">Abre esta pestaña para calcular…</p>
					</div>
				</div>

				<!-- ALCANCE INTERNACIONAL -->
				<div class="rau-tile lg:col-span-4">
					<p class="rau-tile-label">Alcance internacional</p>
					<?php
					$rau_flags = rau_get_flag_counts();
					if ( $rau_flags ) : ?>
					<p class="mb-2 font-mono text-2xl font-bold text-primary"><?php echo esc_html( count( $rau_flags ) ); ?> <span class="text-sm font-normal text-muted-foreground">países</span></p>
					<div class="flex flex-wrap gap-1.5">
						<?php foreach ( array_slice( $rau_flags, 0, 8 ) as $f ) : ?>
						<span title="<?php echo esc_attr( $f['name'] ); ?>" class="rau-tile-sublabel rounded-full border border-border/50 bg-background/50 px-2 py-0.5"><?php echo esc_html( $f['flag'] . ' ' . $f['count'] ); ?></span>
						<?php endforeach; ?>
					</div>
					<?php else : ?>
					<p class="text-center text-xs text-muted-foreground">Todavía sin datos suficientes.</p>
					<?php endif; ?>
				</div>

				<!-- DX SPOTS -->
				<div class="rau-tile lg:col-span-12">
					<p class="rau-tile-label">DX Spots en vivo</p>
					<iframe data-src="https://dxheat.com/dxc/?spotted_filter=0&band_filter=ALL&limit=25&theme=dark&hide_header=1"
						class="w-full rounded-lg border border-border/50" style="height:260px;" loading="lazy" title="DX Spots en vivo"></iframe>
				</div>

			</div>
			<p class="mt-4 text-center font-mono text-[10px] text-muted-foreground">Propagación cada 30 min · SDR cada 2 min · Datos reutilizados de las demás herramientas de esta página</p>
		</div>

		<!-- UTC -->
		<div id="panel-utc" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'utc' ) ); ?>" class="rau-panel" style="display:none">
			<div class="grid gap-6 lg:grid-cols-2">
				<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
					<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Hora universal</p>
					<h2 class="mb-6 font-display text-2xl font-bold">Reloj UTC</h2>
					<div class="space-y-4">
						<div class="rounded-xl border border-primary/30 bg-primary/5 px-6 py-5 text-center">
							<p class="mb-1 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">UTC / GMT</p>
							<p id="clock-utc" class="font-mono text-5xl font-bold tabular-nums text-primary">--:--:--</p>
							<p id="date-utc" class="mt-1 font-mono text-sm text-muted-foreground">---</p>
						</div>
						<div class="rounded-xl border border-border/70 bg-background px-6 py-5 text-center">
							<p class="mb-1 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Hora local (Colombia — UTC−5)</p>
							<p id="clock-local" class="font-mono text-4xl font-bold tabular-nums text-foreground">--:--:--</p>
							<p id="date-local" class="mt-1 font-mono text-sm text-muted-foreground">---</p>
						</div>
					</div>
				</div>
				<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
					<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Referencia rápida</p>
					<h2 class="mb-4 font-display text-xl font-bold">Zonas horarias útiles</h2>
					<div class="space-y-2">
						<?php
						$zones = [
							['UTC−5','Colombia / Perú / Ecuador','COL'],
							['UTC−6','México (centro) / Guatemala','MEX'],
							['UTC−4','Venezuela / Bolivia','VEN'],
							['UTC−3','Brasil (Brasilia) / Argentina','BRA'],
							['UTC+0','UTC / Greenwich / Reino Unido','UTC'],
							['UTC+1','Europa Central (invierno)','EUR'],
						];
						foreach($zones as $z): ?>
						<div class="flex items-center justify-between rounded-lg border border-border/50 bg-background px-4 py-2.5">
							<div class="flex items-center gap-3">
								<span class="font-mono text-xs font-semibold text-primary"><?php echo esc_html($z[0]); ?></span>
								<span class="text-sm text-muted-foreground"><?php echo esc_html($z[1]); ?></span>
							</div>
							<span class="font-mono text-xs text-muted-foreground"><?php echo esc_html($z[2]); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
					<p class="mt-4 text-xs text-muted-foreground">En radioafición todos los horarios se expresan en UTC para evitar confusión entre zonas.</p>
				</div>
			</div>
		</div>

		<!-- PROPAGACIÓN -->
		<div id="panel-prop" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'prop' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Condiciones actuales</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Propagación Solar</h2>
				<p class="mb-4 text-xs text-muted-foreground">Un mismo aviso cubre bandas altas abiertas y fulguraciones solares (clase M o X) — útil para quien opera HF.</p>
				<button class="rau-push-generic-btn mb-6 inline-flex items-center gap-2 rounded-md border-2 border-primary/40 bg-primary/10 px-4 py-2 text-xs font-bold text-primary hover:bg-primary/20">🔔 Activar notificaciones de RAU</button>
				<p class="rau-push-generic-status mb-4 hidden text-xs text-muted-foreground"></p>
				<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
					<div class="rounded-xl border border-border/70 bg-background p-4 text-center">
						<p class="mb-2 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Flujo Solar (SFI)</p>
						<p id="prop-sfi" class="font-mono text-3xl font-bold tabular-nums text-primary">—</p>
						<p class="mt-1 text-xs text-muted-foreground">óptimo &gt; 150</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-background p-4 text-center">
						<p class="mb-2 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Índice A</p>
						<p id="prop-a" class="font-mono text-3xl font-bold tabular-nums text-foreground">—</p>
						<p class="mt-1 text-xs text-muted-foreground">bajo = mejor</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-background p-4 text-center">
						<p class="mb-2 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Índice K</p>
						<p id="prop-k" class="font-mono text-3xl font-bold tabular-nums text-foreground">—</p>
						<p class="mt-1 text-xs text-muted-foreground">0–1 excelente, &gt;4 tormenta</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-background p-4 text-center">
						<p class="mb-2 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Actividad Xray</p>
						<p id="prop-xray" class="font-mono text-3xl font-bold tabular-nums text-foreground">—</p>
						<p class="mt-1 text-xs text-muted-foreground">clase solar</p>
					</div>
				</div>
				<div id="prop-status" class="mt-4 rounded-xl border border-border/50 bg-background px-4 py-3 text-sm text-muted-foreground">Cargando datos de propagación…</div>
				<div class="mt-4 grid gap-4 sm:grid-cols-2">
					<div>
						<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Guía de interpretación del índice K</p>
						<div class="space-y-1.5">
							<?php
							$kguide = [
								['0–1','Excelente','text-emerald-400','bg-emerald-400/10','ring-emerald-400/30'],
								['2–3','Buena','text-primary','bg-primary/10','ring-primary/30'],
								['4','Perturbada','text-amber-400','bg-amber-400/10','ring-amber-400/30'],
								['5+','Tormenta geomagnética','text-rose-400','bg-rose-400/10','ring-rose-400/30'],
							];
							foreach($kguide as $k): ?>
							<div class="flex items-center gap-3 rounded-lg border border-border/50 bg-background px-3 py-2">
								<span class="inline-flex rounded-full px-2 py-0.5 font-mono text-xs font-medium ring-1 <?php echo esc_attr($k[2].' '.$k[3].' '.$k[4]); ?>"><?php echo esc_html($k[0]); ?></span>
								<span class="text-sm text-muted-foreground"><?php echo esc_html($k[1]); ?></span>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
					<div>
						<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Recursos externos</p>
						<div class="space-y-2">
							<a href="https://www.dxmaps.com/" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-lg border border-border/70 bg-background px-4 py-3 transition-colors hover:border-primary/50 group">
								<span class="text-sm font-medium group-hover:text-primary">DXmaps — mapa de propagación en tiempo real</span>
							</a>
							<a href="https://www.hamqsl.com/solar.html" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-lg border border-border/70 bg-background px-4 py-3 transition-colors hover:border-primary/50 group">
								<span class="text-sm font-medium group-hover:text-primary">HamQSL — panel solar completo</span>
							</a>
							<a href="https://openhamclock.com/" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-lg border border-border/70 bg-background px-4 py-3 transition-colors hover:border-primary/50 group">
								<span class="text-sm font-medium group-hover:text-primary">OpenHamClock — reloj y panel de propagación</span>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- ALERTAS DE BANDA ABIERTA -->
		<div id="panel-bandalert" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'bandalert' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Condiciones por banda</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Alertas de Banda Abierta</h2>
				<p class="mb-6 text-sm text-muted-foreground">Calculado por HamQSL a partir del índice K, la actividad solar y la hora del día. Se actualiza cada 30 minutos, igual que el panel de Propagación Solar.</p>

				<button id="bandalert-notif-btn" type="button" class="mb-6 inline-flex items-center gap-2 rounded-md border-2 border-primary/40 bg-primary/10 px-4 py-2.5 text-sm font-bold text-primary hover:bg-primary/20">
					🔔 Avisarme (aunque tenga el navegador cerrado) cuando abra una banda alta
				</button>
				<p id="bandalert-notif-status" class="mb-6 hidden text-xs text-muted-foreground"></p>

				<div id="bandalert-banner" class="hidden mb-6 rounded-xl border border-emerald-400/30 bg-emerald-400/5 px-4 py-3 text-sm font-semibold text-emerald-400">
					🔥 ¡Hay bandas altas abiertas ahora mismo!
				</div>

				<div id="bandalert-grid" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"></div>
				<p id="bandalert-status" class="mt-4 text-sm text-muted-foreground">Cargando condiciones…</p>

				<div class="mt-6 flex flex-wrap gap-4 text-xs text-muted-foreground">
					<span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span> Buena (Good)</span>
					<span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> Regular (Fair)</span>
					<span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span> Pobre / cerrada (Poor)</span>
				</div>
			</div>
		</div>

		<!-- ANTENAS -->
				<!-- Panel calculadoras unificado -->
		<div id="panel-calc" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'calc' ) ); ?>" class="rau-panel" style="display:none">
		<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Herramientas técnicas</p>
			<h2 class="mb-6 font-display text-2xl font-bold">Calculadoras de Radio</h2>

			<!-- Sub-tabs de calculadoras -->
			<div class="mb-6 flex flex-wrap gap-2" role="tablist">
				<?php
				$calcs = [
					['antenas',    'Antenas'],
					['linea',      'Línea de TX'],
					['enlace',     'Enlace Repetidor'],
					['potencia',   'Potencia / dB'],
					['efhw',       'EFHW / End-Fed'],
					['trafo',      'Transformador Z'],
					['condensador','Condensador'],
				];
				foreach($calcs as $i => $c): ?>
				<button type="button" role="tab" id="calctab-<?php echo esc_attr($c[0]); ?>"
					aria-controls="calc-<?php echo esc_attr($c[0]); ?>"
					aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
					tabindex="<?php echo $i === 0 ? '0' : '-1'; ?>"
					data-calc="<?php echo esc_attr($c[0]); ?>"
					class="calc-sub-tab inline-flex rounded-lg border px-3 py-1.5 font-mono text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring <?php echo $i===0 ? 'border-primary bg-primary/10 text-primary' : 'border-border/70 bg-card text-muted-foreground hover:border-primary/50'; ?>">
					<?php echo esc_html($c[1]); ?>
				</button>
				<?php endforeach; ?>
			</div>

			<!-- SUB-PANEL: Antenas -->
			<div id="calc-antenas" role="tabpanel" aria-labelledby="calctab-antenas" class="calc-sub-panel">
				<h3 class="mb-4 font-display text-lg font-semibold">Calculadora de Antenas</h3>
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<div><label for="ant-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia (MHz)</label>
						<input type="number" id="ant-freq" step="0.001" min="0.1" max="1300" placeholder="146.520" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						<div><label for="ant-type" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tipo de antena</label>
						<select id="ant-type" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
							<option value="dipole">Dipolo (λ/2)</option>
							<option value="vertical">Vertical (λ/4)</option>
							<option value="5_8">Vertical 5/8λ</option>
							<option value="loop">Loop onda completa</option>
							<option value="yagi2">Yagi 2 elementos</option>
							<option value="yagi3">Yagi 3 elementos</option>
						</select></div>
						<div><label for="ant-vf" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Factor de velocidad</label>
						<input type="number" id="ant-vf" step="0.01" min="0.5" max="1" value="0.95" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						<p class="mt-1 text-xs text-muted-foreground">Aire libre = 1.00 · Coax típico = 0.66–0.82 · Alambre = 0.92–0.98</p></div>
						<button id="ant-calc" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">Calcular</button>
					</div>
					<div id="ant-result" class="rounded-xl border border-border/70 bg-background p-5 text-sm text-muted-foreground">Ingresa frecuencia y tipo de antena.</div>
				</div>
			</div>

			<!-- SUB-PANEL: Línea de TX -->
			<div id="calc-linea" role="tabpanel" aria-labelledby="calctab-linea" class="calc-sub-panel" style="display:none">
				<h3 class="mb-4 font-display text-lg font-semibold">Pérdida en Línea de Transmisión</h3>
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<div><label for="lt-cable" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tipo de cable</label>
						<select id="lt-cable" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
							<!-- Atenuación en dB/m a 100 MHz — que es la referencia que espera la fórmula
							     (loss = base × √(f/100) × largo). Los valores anteriores correspondían a
							     ~450 MHz, así que la calculadora sobreestimaba la pérdida entre 2 y 2.5 veces:
							     con 20 m de RG-58 en 2m daba 10.5 dB (9 W de 100 W) cuando lo real ronda 4 dB.
							     Fuentes: hojas de datos Belden 8259/9258/8267, Times Microwave LMR y
							     CommScope LDF4/LDF5. Varían algo entre fabricantes. -->
							<option value="0.177">RG-58 A/U</option>
							<option value="0.121">RG-8X</option>
							<option value="0.066">RG-213</option>
							<option value="0.041">LMR-400</option>
							<option value="0.026">LMR-600</option>
							<option value="0.018">LMR-900</option>
							<option value="0.021">1/2" Heliax</option>
							<option value="0.012">7/8" Heliax</option>
						</select></div>
						<div class="grid grid-cols-2 gap-3">
							<div><label for="lt-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia (MHz)</label>
							<input type="number" id="lt-freq" step="0.1" min="1" max="1300" placeholder="146.5" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="lt-long" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Longitud (m)</label>
							<input type="number" id="lt-long" step="0.5" min="0.5" max="500" placeholder="20" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						</div>
						<div><label for="lt-pot" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Potencia entrada (W)</label>
						<input type="number" id="lt-pot" step="1" min="1" placeholder="100" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						<button id="lt-calc" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">Calcular pérdida</button>
					</div>
					<div id="lt-result" class="rounded-xl border border-border/70 bg-background p-5 text-sm text-muted-foreground">Ingresa los valores para calcular.</div>
				</div>
			</div>

			<!-- SUB-PANEL: Enlace Repetidor -->
			<div id="calc-enlace" role="tabpanel" aria-labelledby="calctab-enlace" class="calc-sub-panel" style="display:none">
				<h3 class="mb-4 font-display text-lg font-semibold">Calculadora de Enlace de Repetidor</h3>
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<h4 class="font-semibold text-sm text-muted-foreground">Estación móvil / portátil</h4>
						<div class="grid grid-cols-2 gap-3">
							<div><label for="el-ptx" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Potencia TX (W)</label>
							<input type="number" id="el-ptx" placeholder="5" min="0.1" step="0.1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="el-gtx" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Ganancia ant. TX (dBi)</label>
							<input type="number" id="el-gtx" placeholder="2.15" step="0.1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						</div>
						<div><label for="el-ltx" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Pérdida cable TX (dB)</label>
						<input type="number" id="el-ltx" placeholder="0.5" step="0.1" min="0" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						<h4 class="font-semibold text-sm text-muted-foreground pt-2">Repetidor (receptor)</h4>
						<div class="grid grid-cols-2 gap-3">
							<div><label for="el-grx" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Ganancia ant. RX (dBi)</label>
							<input type="number" id="el-grx" placeholder="7" step="0.1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="el-sens" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Sensibilidad RX (dBm)</label>
							<input type="number" id="el-sens" placeholder="-120" step="1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						</div>
						<div class="grid grid-cols-2 gap-3">
							<div><label for="el-freq2" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia (MHz)</label>
							<input type="number" id="el-freq2" placeholder="146.675" step="0.001" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="el-dist" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Distancia (km)</label>
							<input type="number" id="el-dist" placeholder="15" step="0.5" min="0.1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						</div>
						<button id="el-calc" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">Calcular enlace</button>
					</div>
					<div id="el-result" class="rounded-xl border border-border/70 bg-background p-5 text-sm text-muted-foreground">Completa los valores para verificar el enlace.</div>
				</div>
			</div>

			<!-- SUB-PANEL: Potencia / dB -->
			<div id="calc-potencia" role="tabpanel" aria-labelledby="calctab-potencia" class="calc-sub-panel" style="display:none">
				<h3 class="mb-4 font-display text-lg font-semibold">Potencia y Decibelios</h3>
				<div class="grid gap-8 lg:grid-cols-2">
					<div class="space-y-4">
						<h4 class="font-semibold text-sm">Vatios ↔ dBm ↔ dBW</h4>
						<div><label for="pw-watts" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Potencia (W)</label>
						<input type="number" id="pw-watts" step="any" min="0" placeholder="100" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						<div id="pw-result" class="space-y-2 rounded-xl border border-border/70 bg-background p-4 hidden">
							<div class="flex justify-between"><span class="font-mono text-xs text-muted-foreground">dBm</span><span id="pw-dbm" class="font-mono text-sm font-bold text-primary">—</span></div>
							<div class="flex justify-between"><span class="font-mono text-xs text-muted-foreground">dBW</span><span id="pw-dbw" class="font-mono text-sm font-bold text-foreground">—</span></div>
							<div class="flex justify-between"><span class="font-mono text-xs text-muted-foreground">mW</span><span id="pw-mw" class="font-mono text-sm font-bold text-foreground">—</span></div>
						</div>
						<h4 class="font-semibold text-sm pt-2">Ganancia dBi ↔ dBd</h4>
						<div class="grid grid-cols-2 gap-3">
							<div><label for="gain-dbi" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">dBi</label>
							<input type="number" id="gain-dbi" step="0.1" placeholder="2.15" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="gain-dbd" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">dBd</label>
							<input type="number" id="gain-dbd" step="0.1" placeholder="0" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						</div>
						<p class="text-xs text-muted-foreground">dBi = dBd + 2.15</p>
					</div>
					<div class="rounded-xl border border-border/50 bg-background p-4">
						<p class="mb-2 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Referencia rápida</p>
						<div class="space-y-1 text-xs font-mono text-muted-foreground">
							<div class="flex justify-between"><span>1 W</span><span>30 dBm = 0 dBW</span></div>
							<div class="flex justify-between"><span>5 W (QRP)</span><span>37 dBm</span></div>
							<div class="flex justify-between"><span>25 W</span><span>44 dBm</span></div>
							<div class="flex justify-between"><span>100 W</span><span>50 dBm</span></div>
							<div class="flex justify-between"><span>500 W</span><span>57 dBm</span></div>
							<div class="flex justify-between"><span>1500 W</span><span>61.8 dBm</span></div>
						</div>
					</div>
				</div>
			</div>

			<!-- SUB-PANEL: EFHW -->
			<div id="calc-efhw" role="tabpanel" aria-labelledby="calctab-efhw" class="calc-sub-panel" style="display:none">
				<h3 class="mb-4 font-display text-lg font-semibold">Antena EFHW (End-Fed Half Wave)</h3>
				<p class="mb-4 text-sm text-muted-foreground">La EFHW es ideal para POTA y operación portátil. Requiere un transformador 49:1 (unun) para adaptar la alta impedancia al coaxial de 50Ω.</p>
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<div><label for="efhw-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia fundamental (MHz)</label>
						<input type="number" id="efhw-freq" step="0.001" min="1" max="60" placeholder="7.150" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						<p class="mt-1 text-xs text-muted-foreground">La EFHW también funciona en armónicos (×2, ×3…)</p></div>
						<div><label for="efhw-vf" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Factor de velocidad del alambre</label>
						<input type="number" id="efhw-vf" step="0.01" min="0.9" max="1" value="0.97" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						<p class="mt-1 text-xs text-muted-foreground">Alambre desnudo ≈ 0.97 · Alambre aislado ≈ 0.94–0.96</p></div>
						<button id="efhw-calc" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">Calcular EFHW</button>
					</div>
					<div id="efhw-result" class="rounded-xl border border-border/70 bg-background p-5 text-sm text-muted-foreground">Ingresa la frecuencia para calcular.</div>
				</div>
			</div>

			<!-- SUB-PANEL: Transformador Z -->
			<div id="calc-trafo" role="tabpanel" aria-labelledby="calctab-trafo" class="calc-sub-panel" style="display:none">
				<h3 class="mb-4 font-display text-lg font-semibold">Transformador de Impedancias (Balun / Unun)</h3>
				<p class="mb-4 text-sm text-muted-foreground">Calcula la relación de espiras necesaria para adaptar dos impedancias distintas.</p>
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<div class="grid grid-cols-2 gap-3">
							<div><label for="trafo-z1" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Impedancia entrada (Ω)</label>
							<input type="number" id="trafo-z1" step="0.1" min="1" placeholder="50" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="trafo-z2" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Impedancia salida (Ω)</label>
							<input type="number" id="trafo-z2" step="0.1" min="1" placeholder="2450" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						</div>
						<button id="trafo-calc" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">Calcular relación de espiras</button>
						<div class="rounded-xl border border-border/50 bg-background p-4 text-xs text-muted-foreground">
							<p class="font-semibold text-foreground mb-2">Ejemplos comunes</p>
							<div class="space-y-1 font-mono">
								<div class="flex justify-between"><span>50Ω → 200Ω</span><span>1:4 (balun)</span></div>
								<div class="flex justify-between"><span>50Ω → 450Ω</span><span>1:9 (unun)</span></div>
								<div class="flex justify-between"><span>50Ω → 2450Ω</span><span>1:49 (EFHW)</span></div>
							</div>
						</div>
					</div>
					<div id="trafo-result" class="rounded-xl border border-border/70 bg-background p-5 text-sm text-muted-foreground">Ingresa las impedancias para calcular.</div>
				</div>
			</div>

			<!-- SUB-PANEL: Condensador -->
			<div id="calc-condensador" role="tabpanel" aria-labelledby="calctab-condensador" class="calc-sub-panel" style="display:none">
				<h3 class="mb-4 font-display text-lg font-semibold">Condensador de Sintonía (LC Tank)</h3>
				<p class="mb-4 text-sm text-muted-foreground">Calcula la capacitancia o inductancia necesaria para sintonizar un circuito resonante a una frecuencia dada.</p>
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<div><label for="lc-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia de resonancia (MHz)</label>
						<input type="number" id="lc-freq" step="0.001" min="0.1" max="500" placeholder="7.150" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						<div class="grid grid-cols-2 gap-3">
							<div><label for="lc-l" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Inductancia L (µH)</label>
							<input type="number" id="lc-l" step="0.01" min="0" placeholder="1.0" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="lc-c" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Capacitancia C (pF)</label>
							<input type="number" id="lc-c" step="0.1" min="0" placeholder="100" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						</div>
						<p class="text-xs text-muted-foreground">Deja vacío el valor que quieres calcular (L o C).</p>
						<button id="lc-calc" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">Calcular</button>
					</div>
					<div id="lc-result" class="rounded-xl border border-border/70 bg-background p-5 text-sm text-muted-foreground">
						<p>Ingresa la frecuencia y uno de los dos valores (L o C) para calcular el otro.</p>
						<p class="mt-3 font-mono text-xs text-muted-foreground">Fórmula: f = 1 / (2π√(LC))</p>
					</div>
				</div>
			</div>

		</div>
		</div>

		<!-- CONVERSOR RF -->
		<div id="panel-rfconv" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'rfconv' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Unidades de RF</p>
				<h2 class="mb-6 font-display text-2xl font-bold">Conversor RF</h2>
				<div class="grid gap-8 lg:grid-cols-2">

					<div>
						<h3 class="mb-4 font-display text-lg font-semibold">Potencia — dBm · W · mW · Vrms</h3>
						<p class="mb-4 text-xs text-muted-foreground">Escribe en cualquier campo, los demás se recalculan solos (referencia 50 Ω).</p>
						<div class="space-y-3">
							<div><label for="rf-dbm" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">dBm</label>
							<input type="text" id="rf-dbm" value="37" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="rf-w" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Vatios (W)</label>
							<input type="text" id="rf-w" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="rf-mw" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Miliwatios (mW)</label>
							<input type="text" id="rf-mw" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="rf-vrms" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Voltaje RMS (50 Ω)</label>
							<input type="text" id="rf-vrms" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						</div>
					</div>

					<div>
						<h3 class="mb-4 font-display text-lg font-semibold">ROE (VSWR) · Pérdida de Retorno</h3>
						<p class="mb-4 text-xs text-muted-foreground">Escribe la ROE o la pérdida de retorno, el otro campo se recalcula solo.</p>
						<div class="space-y-3">
							<div><label for="rf-vswr" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">ROE (VSWR)</label>
							<input type="text" id="rf-vswr" value="1.5" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
							<div><label for="rf-rl" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Pérdida de retorno (dB)</label>
							<input type="text" id="rf-rl" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
						</div>
						<div id="rf-vswr-result" class="mt-4 rounded-xl border border-border/70 bg-background p-4">
							<div class="grid grid-cols-2 gap-3 text-center">
								<div>
									<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Reflejada</p>
									<p id="rf-refl" class="font-mono text-xl font-bold text-rose-400">—</p>
								</div>
								<div>
									<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Transmitida</p>
									<p id="rf-trans" class="font-mono text-xl font-bold text-emerald-400">—</p>
								</div>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>

		<!-- HISTORIAL SFI/K -->
		<div id="panel-sfihist" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'sfihist' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Tendencia solar</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Historial de SFI / Índice K</h2>
				<p class="mb-6 text-sm text-muted-foreground">Un valor puntual dice poco — la tendencia de los últimos días importa más para planear cuándo operar. Se guarda automáticamente una vez al día, sin necesidad de cuenta.</p>
				<div id="sfihist-content">
					<p class="text-sm text-muted-foreground">Cargando historial…</p>
				</div>
			</div>
		</div>

		<!-- GRAYLINE -->
		<div id="panel-grayline" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'grayline' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Propagación por terminador</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Grayline</h2>
				<p class="mb-6 text-sm text-muted-foreground">La franja entre el día y la noche ("grayline") suele dar aperturas de banda excepcionales para DX de larga distancia — vale la pena estar en el aire justo antes/después de estas horas.</p>
				<div class="grid gap-4 sm:grid-cols-2">
					<div class="rounded-xl border border-border/70 bg-background p-5 text-center">
						<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Amanecer hoy (Medellín)</p>
						<p id="gl-amanecer" class="mt-1 font-mono text-3xl font-bold text-primary">—</p>
						<p class="mt-1 text-xs text-muted-foreground">Hora local (UTC-5)</p>
					</div>
					<div class="rounded-xl border border-border/70 bg-background p-5 text-center">
						<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Atardecer hoy (Medellín)</p>
						<p id="gl-atardecer" class="mt-1 font-mono text-3xl font-bold text-primary">—</p>
						<p class="mt-1 text-xs text-muted-foreground">Hora local (UTC-5)</p>
					</div>
				</div>
				<p class="mt-4 text-xs text-muted-foreground">La ventana útil suele ser ± 30-60 minutos alrededor de estas horas. Calculado para Medellín (6.24°N, 75.58°O) — variará algo según tu ubicación exacta.</p>
			</div>
		</div>

		<!-- FASE LUNAR (EME) -->
		<div id="panel-luna" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'luna' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Moonbounce</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Fase Lunar (EME)</h2>
				<p class="mb-6 text-sm text-muted-foreground">Referencia rápida para operación EME (Earth-Moon-Earth). No calcula horarios de salida/puesta de la luna — para eso, complementa con un efemérides completo antes de una sesión EME real.</p>
				<div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
					<div id="luna-icono" class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full border-2 border-border/70 bg-background text-4xl">🌕</div>
					<div class="text-center sm:text-left">
						<p id="luna-nombre" class="font-display text-2xl font-bold">—</p>
						<p id="luna-iluminacion" class="mt-1 font-mono text-sm text-muted-foreground">—</p>
						<p id="luna-edad" class="mt-1 font-mono text-xs text-muted-foreground">—</p>
					</div>
				</div>
			</div>
		</div>

		<!-- GLOSARIO -->
		<div id="panel-glosario" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'glosario' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Para quienes llegan a RAU</p>
				<h2 class="mb-6 font-display text-2xl font-bold">Glosario de Términos</h2>
				<div class="mb-4">
					<input aria-label="Buscar un término" type="search" id="glo-buscar" placeholder="Buscar un término…" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<div id="glo-lista" class="grid gap-3 sm:grid-cols-2"></div>
			</div>
		</div>

		<!-- CÓDIGO DE COLORES -->
		<div id="panel-colores" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'colores' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Componentes electrónicos</p>
				<h2 class="mb-6 font-display text-2xl font-bold">Código de Colores</h2>

				<div class="grid gap-8 lg:grid-cols-2">
					<div>
						<h3 class="mb-4 font-display text-lg font-semibold">Resistencias</h3>
						<div class="mb-4 flex gap-2">
							<button type="button" data-bands="4" class="col-bands-btn rounded-lg border-2 border-primary bg-primary/10 px-4 py-2 text-sm font-semibold text-primary">4 bandas</button>
							<button type="button" data-bands="5" class="col-bands-btn rounded-lg border-2 border-border bg-background px-4 py-2 text-sm font-semibold text-foreground">5 bandas</button>
						</div>
						<div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
							<div><label class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Dígito 1</label><select id="col-d1" aria-label="Primera banda de color del resistor" class="col-select w-full rounded-md border border-input bg-background px-2 py-2 text-sm"></select></div>
							<div><label class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Dígito 2</label><select id="col-d2" aria-label="Segunda banda de color del resistor" class="col-select w-full rounded-md border border-input bg-background px-2 py-2 text-sm"></select></div>
							<div id="col-d3-wrap" class="hidden"><label class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Dígito 3</label><select id="col-d3" aria-label="Tercera banda de color del resistor" class="col-select w-full rounded-md border border-input bg-background px-2 py-2 text-sm"></select></div>
							<div><label class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Multiplicador</label><select id="col-mult" aria-label="Banda multiplicadora del resistor" class="col-select w-full rounded-md border border-input bg-background px-2 py-2 text-sm"></select></div>
							<div><label class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Tolerancia</label><select id="col-tol" aria-label="Banda de tolerancia del resistor" class="col-select w-full rounded-md border border-input bg-background px-2 py-2 text-sm"></select></div>
						</div>
						<div class="mt-5 rounded-xl border border-border/70 bg-background p-5 text-center">
							<p id="col-result" class="font-mono text-3xl font-bold text-primary">—</p>
							<p id="col-result-tol" class="mt-1 text-sm text-muted-foreground">—</p>
						</div>
					</div>

					<div>
						<h3 class="mb-4 font-display text-lg font-semibold">Capacitores (código de 3 dígitos)</h3>
						<label for="cap-code" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Código impreso (ej. 104, 223, 475)</label>
						<input type="text" id="cap-code" maxlength="4" value="104" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						<div class="mt-4 grid grid-cols-3 gap-3 text-center">
							<div class="rounded-lg border border-border/50 bg-background px-2 py-3"><p id="cap-pf" class="font-mono text-lg font-bold text-foreground">—</p><p class="font-mono text-[10px] uppercase text-muted-foreground">pF</p></div>
							<div class="rounded-lg border border-border/50 bg-background px-2 py-3"><p id="cap-nf" class="font-mono text-lg font-bold text-foreground">—</p><p class="font-mono text-[10px] uppercase text-muted-foreground">nF</p></div>
							<div class="rounded-lg border border-border/50 bg-background px-2 py-3"><p id="cap-uf" class="font-mono text-lg font-bold text-primary">—</p><p class="font-mono text-[10px] uppercase text-muted-foreground">µF</p></div>
						</div>
						<p class="mt-4 text-xs text-muted-foreground">Los dos primeros dígitos son el valor, el tercero es el multiplicador (potencia de 10), siempre en pF.</p>
					</div>
				</div>
			</div>
		</div>

		<!-- AUTONOMÍA DE BATERÍA -->
		<div id="panel-bateria" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'bateria' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Operación portátil / POTA</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Autonomía de Batería</h2>
				<p class="mb-6 text-sm text-muted-foreground">Estimación de cuántas horas dura tu batería en una activación, según tu patrón real de transmisión/recepción.</p>
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<div>
							<label for="bat-cap" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Capacidad de la batería (mAh)</label>
							<input type="number" id="bat-cap" value="10000" min="1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						</div>
						<div>
							<label for="bat-tx" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Consumo en transmisión (mA)</label>
							<input type="number" id="bat-tx" value="2000" min="1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						</div>
						<div>
							<label for="bat-rx" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Consumo en recepción/espera (mA)</label>
							<input type="number" id="bat-rx" value="300" min="0" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						</div>
						<div>
							<label for="bat-duty" class="mb-1 flex items-center justify-between font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
								<span>% del tiempo transmitiendo</span>
								<span id="bat-duty-val" class="text-primary">25%</span>
							</label>
							<input type="range" id="bat-duty" min="5" max="80" step="5" value="25" class="w-full accent-primary">
							<p class="mt-1 text-xs text-muted-foreground">SSB/FM conversacional ronda 20-30% · CW/FT8 puede ser más parejo</p>
						</div>
					</div>
					<div class="rounded-xl border border-border/70 bg-background p-6 text-center">
						<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Autonomía estimada</p>
						<p id="bat-horas" class="mt-2 font-mono text-4xl font-bold text-primary">—</p>
						<p class="mt-4 flex justify-between border-t border-border/50 pt-3 text-xs text-muted-foreground"><span>Consumo promedio</span><span id="bat-promedio" class="font-mono font-semibold text-foreground">—</span></p>
						<p class="text-xs text-muted-foreground">Incluye 15% de margen por eficiencia real de la batería.</p>
					</div>
				</div>
			</div>
		</div>

		<!-- ÁNGULO DE RADIACIÓN -->
		<div id="panel-angulo" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'angulo' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Altura sobre el suelo</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Ángulo de Radiación</h2>
				<p class="mb-6 text-sm text-muted-foreground">La altura a la que cuelgas una antena horizontal decide hacia dónde sale tu señal: baja dispara hacia arriba (bueno para cubrir Colombia), alta aplana el lóbulo (bueno para DX). Cambia la altura y mira cómo se mueve el ángulo antes de subir la antena al árbol.</p>
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<div>
							<label for="ang-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia (MHz)</label>
							<input type="number" id="ang-freq" value="14.150" step="0.001" min="0.1" max="1300" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						</div>
						<div>
							<label for="ang-altura" class="mb-1 flex items-center justify-between font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
								<span>Altura sobre el suelo</span>
								<span id="ang-altura-val" class="text-primary">10.0 m</span>
							</label>
							<input type="range" id="ang-altura" min="1" max="40" step="0.5" value="10" class="w-full accent-primary">
							<p class="mt-1 text-xs text-muted-foreground">Mide desde el suelo hasta el alambre, no hasta el punto de anclaje.</p>
						</div>
						<div class="rounded-lg border border-border/60 bg-background/40 p-3">
							<p class="mb-2 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Referencia clásica</p>
							<div class="space-y-1 font-mono text-xs text-muted-foreground">
								<div class="flex justify-between"><span>λ/4 de altura</span><span class="text-foreground">90° — todo hacia arriba (NVIS)</span></div>
								<div class="flex justify-between"><span>λ/2 de altura</span><span class="text-foreground">30°</span></div>
								<div class="flex justify-between"><span>1λ de altura</span><span class="text-foreground">14°</span></div>
								<div class="flex justify-between"><span>2λ de altura</span><span class="text-foreground">7°</span></div>
							</div>
						</div>
					</div>
					<div class="space-y-4">
						<div class="rounded-xl border border-border/70 bg-background p-6 text-center">
							<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Lóbulo principal</p>
							<p id="ang-principal" class="mt-2 font-mono text-4xl font-bold text-primary">—</p>
							<p class="mt-1 text-xs text-muted-foreground">sobre el horizonte</p>
							<p class="mt-4 flex justify-between border-t border-border/50 pt-3 text-xs text-muted-foreground"><span>Longitud de onda</span><span id="ang-lambda" class="font-mono font-semibold text-foreground">—</span></p>
							<p class="flex justify-between text-xs text-muted-foreground"><span>Altura en λ</span><span id="ang-enlambdas" class="font-mono font-semibold text-foreground">—</span></p>
							<p class="flex justify-between text-xs text-muted-foreground"><span>Lóbulos adicionales</span><span id="ang-lobulos" class="font-mono font-semibold text-foreground">—</span></p>
						</div>
						<div id="ang-lectura" class="rounded-lg border px-4 py-3 text-sm"></div>
						<p class="text-xs text-muted-foreground">Modelo clásico de antena horizontal sobre suelo plano y reflectante (interferencia del rayo directo con el reflejado). El suelo real, la pendiente del terreno y lo que tengas alrededor mueven el resultado — sirve para decidir a qué altura subir, no como predicción exacta. Las verticales se rigen por otro modelo y no aplica esta cuenta.</p>
					</div>
				</div>
			</div>
		</div>

		<!-- AHORA MISMO, ¿QUÉ TRABAJO? -->
		<div id="panel-ahora" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'ahora' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Síntesis</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Ahora mismo, ¿qué trabajo?</h2>
				<p class="mb-6 text-sm text-muted-foreground">Los datos de propagación, grayline y actividad ya están en el sitio, pero repartidos en varios paneles. Esto los junta en una sola respuesta: qué banda, hacia dónde y cuánto dura la ventana.</p>

				<div id="ahora-cargando" class="rounded-xl border border-border/60 bg-background p-6 text-center font-mono text-sm text-muted-foreground">Leyendo condiciones…</div>

				<div id="ahora-contenido" class="hidden space-y-4">
					<div id="ahora-titular" class="rounded-xl border p-6">
						<p class="font-mono text-[11px] uppercase tracking-wider opacity-70">Mejor apuesta ahora</p>
						<p id="ahora-banda" class="mt-1 font-mono text-4xl font-bold">—</p>
						<p id="ahora-razon" class="mt-2 text-sm"></p>
						<button type="button" id="ahora-escuchar" class="rau-escuchar mt-3 hidden rounded-md border border-current px-3 py-2 font-mono text-xs hover:bg-white/10" data-modo="USB">🎧 Compruébalo en el receptor del club</button>
					</div>

					<div class="grid gap-3 sm:grid-cols-3">
						<div class="rounded-lg border border-border/60 bg-background p-4">
							<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Momento del día</p>
							<p id="ahora-momento" class="mt-1 font-mono text-lg font-bold text-foreground">—</p>
							<p id="ahora-cambio" class="text-xs text-muted-foreground"></p>
						</div>
						<div class="rounded-lg border border-border/60 bg-background p-4">
							<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Geomagnetismo</p>
							<p id="ahora-geo" class="mt-1 font-mono text-lg font-bold">—</p>
							<p id="ahora-geo-nota" class="text-xs text-muted-foreground"></p>
						</div>
						<div class="rounded-lg border border-border/60 bg-background p-4">
							<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Actividad comprobada</p>
							<p id="ahora-actividad" class="mt-1 font-mono text-lg font-bold text-foreground">—</p>
							<p id="ahora-actividad-nota" class="text-xs text-muted-foreground"></p>
						</div>
					</div>

					<div class="rounded-lg border border-border/60 bg-background p-4">
						<p class="mb-2 font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Todas las bandas ahora</p>
						<div id="ahora-bandas" class="grid grid-cols-2 gap-1.5 font-mono text-xs sm:grid-cols-4"></div>
					</div>

					<div id="ahora-grayline" class="rounded-lg border border-primary/30 bg-primary/5 p-4 text-sm"></div>

					<p class="text-xs text-muted-foreground">Esto no predice nada: resume lo que reportan HamQSL y el cluster en este momento, más la hora solar de tu QTH. La propagación real depende de tu antena, tu potencia y de quién esté escuchando al otro lado. <span id="ahora-sello"></span></p>
				</div>
			</div>
		</div>

		<!-- CONVERSOR DE COORDENADAS -->
		<div id="panel-coords" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'coords' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Localizar</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Conversor de Coordenadas</h2>
				<p class="mb-6 text-sm text-muted-foreground">El grid square solo lo entiende otro radioaficionado. Si estás coordinando con la Defensa Civil, los bomberos o el 123, ellos trabajan en grados decimales o en UTM. Esto convierte entre los cuatro formatos a la vez.</p>

				<div class="mb-4">
					<label for="coords-entrada" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Pega o escribe en cualquier formato</label>
					<input type="text" id="coords-entrada" value="6.2442, -75.5812" aria-label="Coordenadas de entrada en cualquier formato"
						class="w-full rounded-md border border-input bg-background px-3 py-2.5 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					<p class="mt-1.5 text-xs text-muted-foreground">Acepta: <span class="font-mono">6.2442, -75.5812</span> · <span class="font-mono">6°14'39"N 75°34'52"W</span> · <span class="font-mono">18N 435707 690235</span> · <span class="font-mono">FJ26ff</span></p>
				</div>

				<div class="mb-3 flex flex-wrap gap-2">
					<button type="button" id="coords-gps" class="rounded-lg border border-primary/40 bg-primary/10 px-3 py-1.5 font-mono text-xs font-semibold text-primary hover:bg-primary/20">📍 Usar mi ubicación</button>
					<button type="button" id="coords-qth" class="rounded-lg border border-border/70 bg-background px-3 py-1.5 font-mono text-xs text-muted-foreground hover:border-primary/50 hover:text-primary">Mi QTH guardado</button>
				</div>

				<div id="coords-error" class="mb-3 hidden rounded-lg border border-rose-400/30 bg-rose-400/5 px-4 py-2.5 text-sm text-rose-400"></div>

				<div id="coords-salida" class="space-y-2"></div>

				<p class="mt-4 text-xs text-muted-foreground">MAGNA-SIRGAS, el datum oficial de Colombia, usa el elipsoide GRS80. Para el uso práctico coincide con WGS84 (las diferencias quedan por debajo del metro), así que las coordenadas UTM de aquí sirven para reportar posición. Para levantamientos topográficos o catastro, usa el software oficial del IGAC.</p>
			</div>
		</div>

		<!-- DIAGNÓSTICO DE ROE -->
		<div id="panel-roe" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'roe' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Diagnóstico</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Diagnóstico de ROE</h2>
				<p class="mb-6 text-sm text-muted-foreground">Las otras calculadoras dan números. Esta interpreta un síntoma: cuéntale cómo se comporta tu ROE y te dice qué está pasando y qué revisar, en el orden en que conviene revisarlo.</p>

				<fieldset class="mb-5">
					<legend class="mb-2 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">¿Cómo se comporta a lo largo de la banda?</legend>
					<div id="roe-sintomas" class="space-y-2">
						<?php
						$roe_opciones = [
							['baja-alta',  'Baja abajo, sube hacia arriba de la banda'],
							['alta-baja',  'Alta abajo, baja hacia arriba de la banda'],
							['centro',     'Tiene mínimo en el centro, pero nunca baja de 2:1'],
							['plana-alta', 'Alta y casi igual en toda la banda'],
							['todas-baja', 'Muy baja (1:1) en todas las bandas, incluso donde la antena no debería funcionar'],
						];
						foreach ( $roe_opciones as $i => $o ) : ?>
						<label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-border/60 bg-background px-4 py-3 text-sm hover:border-primary/50">
							<input type="radio" name="roe-sintoma" value="<?php echo esc_attr( $o[0] ); ?>" <?php echo 0 === $i ? 'checked' : ''; ?> class="mt-0.5 accent-primary">
							<span><?php echo esc_html( $o[1] ); ?></span>
						</label>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<fieldset class="mb-5">
					<legend class="mb-2 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">¿Alguna de estas también? (opcional)</legend>
					<div class="space-y-2">
						<?php
						$roe_extras = [
							['lluvia',   'Cambia cuando llueve o hay humedad'],
							['potencia', 'Sube al aumentar la potencia'],
							['subito',   'Cambió de repente; antes estaba bien'],
							['tocar',    'Cambia si toco el equipo o el micrófono'],
						];
						foreach ( $roe_extras as $o ) : ?>
						<label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-border/60 bg-background px-4 py-2.5 text-sm hover:border-primary/50">
							<input type="checkbox" class="roe-extra mt-0.5 accent-primary" value="<?php echo esc_attr( $o[0] ); ?>">
							<span><?php echo esc_html( $o[1] ); ?></span>
						</label>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<div id="roe-resultado" class="space-y-3"></div>

				<div id="roe-ajuste" class="mt-5 hidden rounded-xl border border-primary/30 bg-primary/5 p-5">
					<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-primary">Cuánto cortar o alargar</p>
					<div class="grid gap-3 sm:grid-cols-3">
						<div>
							<label for="roe-fres" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Resuena en (MHz)</label>
							<input type="number" id="roe-fres" value="7.000" step="0.001" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm">
						</div>
						<div>
							<label for="roe-fobj" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">La quiero en (MHz)</label>
							<input type="number" id="roe-fobj" value="7.100" step="0.001" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm">
						</div>
						<div>
							<label for="roe-largo" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Largo total actual (m)</label>
							<input type="number" id="roe-largo" value="20.10" step="0.01" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm">
						</div>
					</div>
					<p id="roe-ajuste-salida" class="mt-3 font-mono text-lg font-bold text-primary">—</p>
					<p class="mt-1 text-xs text-muted-foreground">La frecuencia de resonancia es inversamente proporcional a la longitud. Corta de a poco y vuelve a medir: quitar alambre es fácil, devolverlo no.</p>
				</div>
			</div>
		</div>

		<!-- FRECUENCIAS DE EMERGENCIA -->
		<div id="panel-frecemerg" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'frecemerg' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-accent">Emergencias</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Frecuencias de Emergencia</h2>
				<p class="mb-6 text-sm text-muted-foreground">Lo primero que se busca cuando algo pasa, y hasta ahora no estaba en ninguna parte del sitio. Toca una frecuencia para copiarla.</p>

				<?php $fe_rep = rau_get_repetidor_principal(); ?>

				<div class="mb-5 rounded-xl border-2 border-accent/40 bg-accent/5 p-5">
					<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-accent">Red RAU — lo primero que debes intentar</p>
					<div class="space-y-1.5">
						<button type="button" class="fe-copiar flex w-full items-center justify-between rounded-lg border border-border/60 bg-background px-4 py-2.5 text-left hover:border-accent/50" data-freq="<?php echo esc_attr( $fe_rep['frequency'] ); ?>">
							<span><span class="font-mono text-lg font-bold text-accent"><?php echo esc_html( $fe_rep['frequency'] ); ?></span> <span class="font-mono text-xs text-muted-foreground">MHz</span>
							<span class="ml-2 font-mono text-xs text-muted-foreground">T<?php echo esc_html( rtrim( rtrim( $fe_rep['tone'], '0' ), '.' ) ); ?> · <?php echo esc_html( (float) $fe_rep['offset'] * 1000 ); ?> kHz</span></span>
							<span class="font-mono text-xs text-muted-foreground">Repetidora <?php echo esc_html( $fe_rep['callsign'] ); ?></span>
						</button>
						<button type="button" class="fe-copiar flex w-full items-center justify-between rounded-lg border border-border/60 bg-background px-4 py-2.5 text-left hover:border-accent/50" data-freq="146.520">
							<span><span class="font-mono text-lg font-bold text-accent">146.520</span> <span class="font-mono text-xs text-muted-foreground">MHz</span></span>
							<span class="font-mono text-xs text-muted-foreground">Simplex de llamada · si la repetidora cae</span>
						</button>
					</div>
				</div>

				<div class="mb-5">
					<h3 class="mb-2 font-display text-lg font-semibold">HF — Centros de Actividad de Emergencia</h3>
					<p class="mb-3 text-xs text-muted-foreground">Adoptadas por la IARU Región 2 (las Américas). No son frecuencias exclusivas sino puntos de reunión: en una emergencia se despejan y se escucha ahí.</p>
					<div class="space-y-1.5">
						<?php
						// Verificadas contra la publicación de la IARU y de la ARRL. Solo se listan las
						// que están formalmente adoptadas como Centro Global de Actividad en Región 2;
						// no se inventan las de 40m y 80m, que varían por país y no están formalizadas.
						$fe_hf = [
							['14.300', '20m', 'La más usada; suele haber alguien escuchando'],
							['18.160', '17m', 'Alternativa cuando 20m está saturada'],
							['21.360', '15m', 'Útil de día para distancias largas'],
						];
						foreach ( $fe_hf as $f ) : ?>
						<button type="button" class="fe-copiar flex w-full items-center justify-between rounded-lg border border-border/60 bg-background px-4 py-2.5 text-left hover:border-primary/50" data-freq="<?php echo esc_attr( $f[0] ); ?>">
							<span><span class="font-mono text-lg font-bold text-primary"><?php echo esc_html( $f[0] ); ?></span> <span class="font-mono text-xs text-muted-foreground">MHz · <?php echo esc_html( $f[1] ); ?></span></span>
							<span class="hidden font-mono text-xs text-muted-foreground sm:inline"><?php echo esc_html( $f[2] ); ?></span>
						</button>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="mb-5">
					<h3 class="mb-2 font-display text-lg font-semibold">Socorro internacional</h3>
					<p class="mb-3 text-xs text-muted-foreground">Fuera del servicio de radioaficionados. Escuchar está permitido; transmitir, solo en peligro real de vida.</p>
					<div class="space-y-1.5 font-mono text-sm">
						<div class="flex items-center justify-between rounded-lg border border-border/50 bg-background px-4 py-2"><span class="font-bold text-foreground">121.500 MHz</span><span class="text-xs text-muted-foreground">Socorro aeronáutico</span></div>
						<div class="flex items-center justify-between rounded-lg border border-border/50 bg-background px-4 py-2"><span class="font-bold text-foreground">156.800 MHz</span><span class="text-xs text-muted-foreground">Marítimo VHF, canal 16</span></div>
						<div class="flex items-center justify-between rounded-lg border border-border/50 bg-background px-4 py-2"><span class="font-bold text-foreground">2.182 kHz</span><span class="text-xs text-muted-foreground">Marítimo HF</span></div>
					</div>
				</div>

				<div class="rounded-xl border border-amber-400/30 bg-amber-400/5 p-5">
					<p class="mb-2 font-mono text-[11px] uppercase tracking-wider text-amber-400">Falta lo nacional — y a propósito</p>
					<p class="text-sm text-muted-foreground">Aquí deberían ir las frecuencias de la Defensa Civil, la Cruz Roja, los bomberos y la red nacional de emergencia. <strong class="text-foreground">No las incluyo porque no pude verificarlas contra una fuente oficial</strong>, y un número equivocado en esta pantalla es peor que un espacio en blanco: alguien llamaría a una frecuencia muerta creyendo que pide auxilio.</p>
					<p class="mt-2 text-sm text-muted-foreground">La junta debería confirmarlas con el MinTIC y con la UNGRD, y añadirlas aquí. Es un trabajo de una tarde y convierte esta herramienta en la que de verdad hace falta.</p>
				</div>

				<div id="fe-aviso" class="mt-4 hidden rounded-lg border border-emerald-400/30 bg-emerald-400/5 px-4 py-2 font-mono text-xs text-emerald-400"></div>
			</div>
		</div>

		<!-- MENSAJE FORMAL ICS-213 -->
		<div id="panel-ics213" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'ics213' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-accent">Emergencias</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Mensaje formal (ICS-213)</h2>
				<p class="mb-6 text-sm text-muted-foreground">En una emergencia el tráfico se pasa con formato, no conversando. Esto estructura el mensaje, cuenta las palabras para que quien recibe pueda verificar que no se perdió nada, y genera el texto listo para dictar o para enviar por modo digital.</p>

				<div class="grid gap-4 lg:grid-cols-2">
					<div class="space-y-3">
						<div class="grid grid-cols-2 gap-3">
							<div>
								<label for="ics-precedencia" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Precedencia</label>
								<select id="ics-precedencia" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
									<option value="RUTINA">Rutina</option>
									<option value="BIENESTAR">Bienestar</option>
									<option value="PRIORIDAD">Prioridad</option>
									<option value="EMERGENCIA">Emergencia</option>
								</select>
							</div>
							<div>
								<label for="ics-numero" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Número de mensaje</label>
								<input type="text" id="ics-numero" value="1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm">
							</div>
						</div>
						<div>
							<label for="ics-incidente" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Incidente</label>
							<input type="text" id="ics-incidente" placeholder="Ej: Deslizamiento vía Las Palmas" aria-label="Nombre del incidente" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
						</div>
						<div class="grid grid-cols-2 gap-3">
							<div>
								<label for="ics-para" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Para (nombre y cargo)</label>
								<input type="text" id="ics-para" placeholder="Coordinador DC" aria-label="Destinatario" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
							</div>
							<div>
								<label for="ics-de" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">De (nombre y cargo)</label>
								<input type="text" id="ics-de" placeholder="HK4XXX · Puesto 2" aria-label="Remitente" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
							</div>
						</div>
						<div>
							<label for="ics-asunto" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Asunto</label>
							<input type="text" id="ics-asunto" placeholder="Solicitud de apoyo médico" aria-label="Asunto" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
						</div>
						<div>
							<label for="ics-mensaje" class="mb-1 flex items-center justify-between font-mono text-[10px] uppercase text-muted-foreground">
								<span>Mensaje</span>
								<span id="ics-check" class="text-primary">0 palabras</span>
							</label>
							<textarea id="ics-mensaje" rows="6" placeholder="Texto claro y breve. Evita abreviaturas que el otro extremo pueda no conocer." aria-label="Texto del mensaje" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
							<p class="mt-1 text-xs text-muted-foreground">El conteo es el <em>check</em>: quien recibe lo compara con lo que anotó. Si no coincide, pide repetición antes de dar el mensaje por bueno.</p>
						</div>
						<div>
							<label for="ics-firma" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Aprobado por</label>
							<input type="text" id="ics-firma" placeholder="Nombre y cargo de quien autoriza" aria-label="Aprobado por" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
						</div>
						<div class="flex flex-wrap gap-2 pt-1">
							<button type="button" id="ics-copiar" class="rounded-lg border border-primary/40 bg-primary/10 px-4 py-2 font-mono text-xs font-semibold text-primary hover:bg-primary/20">Copiar texto</button>
							<button type="button" id="ics-imprimir" class="rounded-lg border border-border/70 bg-background px-4 py-2 font-mono text-xs text-muted-foreground hover:border-primary/50 hover:text-primary">Imprimir</button>
							<button type="button" id="ics-limpiar" class="rounded-lg border border-border/70 bg-background px-4 py-2 font-mono text-xs text-muted-foreground hover:border-rose-400/50 hover:text-rose-400">Limpiar</button>
						</div>
					</div>

					<div>
						<p class="mb-1 font-mono text-[10px] uppercase text-muted-foreground">Así se dicta por radio</p>
						<pre id="ics-salida" class="h-full min-h-[20rem] whitespace-pre-wrap rounded-lg border border-border/60 bg-background p-4 font-mono text-xs leading-relaxed text-foreground"></pre>
					</div>
				</div>

				<p class="mt-4 text-xs text-muted-foreground">Se guarda en este navegador mientras escribes, así que si se corta la luz o recargas la página no pierdes el mensaje a medias. No se envía a ningún servidor.</p>
			</div>
		</div>

		<!-- PERFIL DE TERRENO -->
		<div id="panel-terreno" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'terreno' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Localizar</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Perfil de Terreno</h2>
				<p class="mb-6 text-sm text-muted-foreground">El simulador de cobertura calcula el horizonte suponiendo tierra plana. En el valle de Aburrá eso no es una aproximación: te dice que alcanzas 40 km cuando tienes un cerro de por medio a tres. Esto dibuja el terreno real entre dos puntos y responde la pregunta que importa: <em>¿me tapa la montaña o no?</em></p>

				<div class="mb-4 grid gap-3 sm:grid-cols-2">
					<div>
						<label for="ter-origen" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Desde (grid o coordenadas)</label>
						<div class="flex gap-2">
							<input type="text" id="ter-origen" value="FJ26ff" aria-label="Punto de origen" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm">
							<button type="button" id="ter-gps" aria-label="Usar mi ubicación actual" class="shrink-0 rounded-md border border-input bg-background px-3 text-lg"><span aria-hidden="true">📍</span></button>
						</div>
					</div>
					<div>
						<label for="ter-destino" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Hasta (grid o coordenadas)</label>
						<input type="text" id="ter-destino" value="4.7292, -74.0417" aria-label="Punto de destino" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm">
					</div>
				</div>

				<div class="mb-4 grid gap-3 sm:grid-cols-3">
					<div>
						<label for="ter-h1" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Antena origen (m sobre el suelo)</label>
						<input type="number" id="ter-h1" value="10" min="0" step="1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm">
					</div>
					<div>
						<label for="ter-h2" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Antena destino (m)</label>
						<input type="number" id="ter-h2" value="20" min="0" step="1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm">
					</div>
					<div>
						<label for="ter-freq" class="mb-1 block font-mono text-[10px] uppercase text-muted-foreground">Frecuencia (MHz)</label>
						<input type="number" id="ter-freq" value="146.675" step="0.001" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm">
					</div>
				</div>

				<button type="button" id="ter-calcular" class="mb-4 rounded-lg bg-primary px-5 py-2.5 font-mono text-sm font-semibold text-primary-foreground hover:bg-primary/90">Trazar perfil</button>

				<div id="ter-estado" class="mb-4 hidden rounded-lg border px-4 py-3 text-sm"></div>
				<div id="ter-resultado" class="hidden space-y-4">
					<div id="ter-veredicto" class="rounded-xl border p-5"></div>
					<div class="overflow-hidden rounded-xl border border-border/60 bg-background p-3">
						<svg id="ter-grafico" viewBox="0 0 800 300" class="h-auto w-full" role="img" aria-label="Perfil de elevación del terreno entre los dos puntos"></svg>
					</div>
					<div id="ter-datos" class="grid gap-3 font-mono text-xs sm:grid-cols-4"></div>
				</div>

				<p class="mt-4 text-xs text-muted-foreground">Las elevaciones las consulta tu navegador a la API pública de Open-Meteo, que usa el modelo digital de terreno de Copernicus (resolución de unos 90 m). Ese modelo describe el suelo desnudo: no incluye edificios ni vegetación, así que en ciudad el despeje real puede ser peor que el que se ve aquí. El cálculo usa radio terrestre efectivo 4/3, que es la refracción atmosférica estándar en VHF y UHF.</p>
			</div>
		</div>

		<!-- CALENDARIO DE CONCURSOS -->
		<div id="panel-concursos" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'concursos' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">No te enteres tarde</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Calendario de Concursos</h2>
				<p class="mb-6 text-sm text-muted-foreground">Los concursos más importantes del calendario internacional, calculados según su patrón de fecha real (ej. "último fin de semana completo de octubre"). Verifica siempre las reglas oficiales antes de operar.</p>
				<div id="concursos-list" class="grid gap-3 sm:grid-cols-2"></div>
			</div>
		</div>

		<!-- VALIDADOR DE LOG CABRILLO -->
		<div id="panel-cabrillo" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'cabrillo' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Antes de enviar tu log</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Validador de Log Cabrillo</h2>
				<p class="mb-6 text-sm text-muted-foreground">Pega tu log en formato Cabrillo (v3.0 o v2.0) para revisar errores comunes antes de enviarlo al robot del concurso. Esto no reemplaza la validación oficial del organizador, solo detecta problemas obvios de formato.</p>

				<textarea aria-label="START-OF-LOG: 3.0
CALLSIGN: HK4RAU
CONTEST: CQ-WW-SSB
...
QSO: 14195 PH 2026-10-24 1200 HK4RAU 59 09 W1ABC 59 05
END-OF-LOG:" id="cab-input" rows="10" placeholder="START-OF-LOG: 3.0
CALLSIGN: HK4RAU
CONTEST: CQ-WW-SSB
...
QSO: 14195 PH 2026-10-24 1200 HK4RAU 59 09 W1ABC 59 05
END-OF-LOG:" class="w-full rounded-md border border-input bg-background p-3 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-ring"></textarea>

				<button id="cab-validar" class="mt-4 inline-flex items-center justify-center rounded-md bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary/90">Validar</button>

				<div id="cab-resultado" class="mt-6 hidden"></div>
			</div>
		</div>

		<!-- RADIOFAROS NCDXF/IARU -->
		<div id="panel-radiofaros" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'radiofaros' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Red internacional de radiofaros</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Radiofaros NCDXF/IARU</h2>
				<p class="mb-6 text-sm text-muted-foreground">18 radiofaros alrededor del mundo transmiten en secuencia cada 10 segundos, rotando por 5 bandas HF. Escuchándolos puedes saber en tiempo real qué banda está abierta hacia qué parte del mundo — sin depender de ningún número calculado, es la señal real.</p>

				<div class="mb-4 rounded-xl border border-primary/30 bg-primary/5 p-4 text-center">
					<p class="font-mono text-3xl font-bold tabular-nums text-primary" id="faro-reloj">--:--</p>
					<p class="font-mono text-[10px] uppercase text-muted-foreground">Posición en el ciclo de 3 minutos (UTC)</p>
				</div>

				<div class="grid gap-2 sm:grid-cols-5" id="faro-bandas"></div>

				<p class="mt-4 mb-2 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Próximos en 20m (14.100 MHz)</p>
				<div id="faro-proximos" class="space-y-1 font-mono text-sm"></div>
			</div>
		</div>

		<!-- CICLO SOLAR -->
		<div id="panel-ciclosolar" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'ciclosolar' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Contexto de 11 años</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Ciclo Solar 25</h2>
				<p class="mb-6 text-sm text-muted-foreground">Las condiciones de HF no solo cambian día a día — siguen un ciclo de ~11 años. Saber en qué punto del ciclo estamos ayuda a entender el panorama general detrás del número del día.</p>

				<div class="mb-4" id="ciclo-barra">
					<div class="h-3 w-full overflow-hidden rounded-full bg-background">
						<div id="ciclo-progreso" class="h-full rounded-full bg-gradient-to-r from-emerald-400 via-amber-400 to-primary" style="width:0%"></div>
					</div>
					<div class="mt-1 flex justify-between font-mono text-[10px] text-muted-foreground">
						<span>Dic 2019 (inicio)</span>
						<span>~2030 (próximo mínimo)</span>
					</div>
				</div>

				<div class="grid gap-3 sm:grid-cols-3">
					<div class="rounded-lg border border-border/50 p-3 text-center"><p class="font-mono text-2xl font-bold text-primary" id="ciclo-anos">—</p><p class="text-xs text-muted-foreground">años desde el inicio</p></div>
					<div class="rounded-lg border border-border/50 p-3 text-center"><p class="font-mono text-lg font-bold" id="ciclo-fase">—</p><p class="text-xs text-muted-foreground">fase actual</p></div>
					<div class="rounded-lg border border-border/50 p-3 text-center"><p class="font-mono text-2xl font-bold" id="ciclo-desde-max">—</p><p class="text-xs text-muted-foreground">desde el máximo declarado</p></div>
				</div>
				<p class="mt-4 text-xs text-muted-foreground">Máximo solar declarado oficialmente por NASA/NOAA: 15 de octubre de 2024. Próximo mínimo estimado: entre 2029 y 2032 (rango oficial del panel de predicción).</p>
			</div>
		</div>

		<!-- PREDICTOR DE APAGÓN HF -->
		<div id="panel-apagon" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'apagon' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">A partir del X-ray actual</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Predictor de Apagón HF</h2>
				<p class="mb-6 text-sm text-muted-foreground">Interpreta el valor de rayos X que ya se muestra en Propagación Solar — qué tan severo y cuánto puede durar un apagón de radio de onda corta (escala GOES estándar).</p>

				<div id="apagon-resultado" class="rounded-xl border border-border/70 bg-background p-5 text-center">
					<p class="text-sm text-muted-foreground">Cargando dato actual…</p>
				</div>

				<table class="mt-4 w-full text-left text-xs">
					<thead><tr class="border-b border-border/50 text-muted-foreground"><th class="py-1.5">Clase</th><th>Severidad</th><th>Duración típica del apagón</th></tr></thead>
					<tbody id="apagon-tabla" class="font-mono"></tbody>
				</table>
			</div>
		</div>
		<!-- RADIALES DE TIERRA -->
		<div id="panel-radiales" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'radiales' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Vertical / GP</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Radiales de Tierra</h2>
				<p class="mb-6 text-sm text-muted-foreground">Cuántos radiales usar y de qué longitud para una antena vertical — basado en los estudios clásicos de referencia (Brown, Lewis, Epstein), no una fórmula única.</p>

				<div class="mb-4">
					<label for="rad-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia (MHz)</label>
					<input type="number" id="rad-freq" value="14.15" step="0.01" class="w-full max-w-xs rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
				</div>

				<div id="rad-resultado" class="mb-4 rounded-xl border border-border/70 bg-background p-4 text-center">
					<p class="font-mono text-2xl font-bold text-primary" id="rad-longitud">—</p>
					<p class="text-xs text-muted-foreground">longitud recomendada por radial (λ/4)</p>
				</div>

				<table class="w-full text-left text-xs">
					<thead><tr class="border-b border-border/50 text-muted-foreground"><th class="py-1.5">Radiales</th><th>Resultado esperado</th></tr></thead>
					<tbody class="font-mono">
						<tr class="border-b border-border/30"><td class="py-1.5">4</td><td class="text-muted-foreground">Mínimo práctico — funciona, pérdidas notables</td></tr>
						<tr class="border-b border-border/30"><td class="py-1.5">16-32</td><td class="text-muted-foreground">Buena mejora — punto de equilibrio típico para la mayoría</td></tr>
						<tr class="border-b border-border/30"><td class="py-1.5">60-120</td><td class="text-emerald-400">Óptimo — estándar de las estaciones de referencia (broadcast AM)</td></tr>
						<tr><td class="py-1.5">120+</td><td class="text-muted-foreground">Rendimientos decrecientes — poca ganancia adicional</td></tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- AYUDANTE DE SPLIT -->
		<div id="panel-split" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'split' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Pileups de DX</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Ayudante de Split</h2>
				<p class="mb-6 text-sm text-muted-foreground">Cuando una estación DX rara opera "split" (escucha en una frecuencia distinta a la que transmite), calcula rápido dónde debes transmitir tú.</p>

				<div class="grid gap-4 sm:grid-cols-2">
					<div>
						<label for="split-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia del DX (kHz)</label>
						<input type="number" id="split-freq" value="14195" step="1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					</div>
					<div>
						<label for="split-offset" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">"Escucha arriba" (kHz)</label>
						<input type="number" id="split-offset" value="2" step="0.5" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					</div>
				</div>

				<div class="mt-4 grid grid-cols-2 gap-4">
					<div class="rounded-xl border border-border/70 bg-background p-4 text-center">
						<p class="font-mono text-[10px] uppercase text-muted-foreground">Escucha al DX en</p>
						<p class="font-mono text-2xl font-bold" id="split-rx">—</p>
					</div>
					<div class="rounded-xl border border-primary/40 bg-primary/5 p-4 text-center">
						<p class="font-mono text-[10px] uppercase text-primary">Tú transmites en</p>
						<p class="font-mono text-2xl font-bold text-primary" id="split-tx">—</p>
					</div>
				</div>
				<p class="mt-3 text-xs text-muted-foreground">"Escucha arriba" es lo más común (el DX está más solicitado y sube su ventana de escucha) — cambia a negativo si el DX dice que escucha abajo.</p>
			</div>
		</div>

		<!-- CONTADOR DE TASA DE QSO -->
		<div id="panel-qsorate" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'qsorate' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Concursos</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Contador de Tasa de QSO</h2>
				<p class="mb-6 text-sm text-muted-foreground">Cronómetro simple para saber tu ritmo real de contactos por hora mientras concursas.</p>

				<div class="mb-4 grid grid-cols-3 gap-3 text-center">
					<div class="rounded-xl border border-border/70 bg-background p-4"><p class="font-mono text-3xl font-bold text-primary" id="qso-total">0</p><p class="text-xs text-muted-foreground">QSOs</p></div>
					<div class="rounded-xl border border-border/70 bg-background p-4"><p class="font-mono text-3xl font-bold" id="qso-tiempo">00:00</p><p class="text-xs text-muted-foreground">Tiempo</p></div>
					<div class="rounded-xl border border-border/70 bg-background p-4"><p class="font-mono text-3xl font-bold" id="qso-rate">0</p><p class="text-xs text-muted-foreground">QSOs/hora</p></div>
				</div>

				<div class="flex gap-2">
					<button id="qso-add" class="flex-1 rounded-md bg-primary py-3 text-lg font-bold text-primary-foreground hover:bg-primary/90">+1 QSO</button>
					<button id="qso-start" class="rounded-md border border-border bg-background px-4 text-sm font-medium">Iniciar</button>
					<button id="qso-reset" class="rounded-md border border-border bg-background px-4 text-sm font-medium">Reiniciar</button>
				</div>
			</div>
		</div>

		<!-- RASTREADOR DXCC/WAS -->
		<div id="panel-dxcc" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'dxcc' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Progreso personal, guardado en tu navegador</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Rastreador DXCC / WAS</h2>
				<p class="mb-6 text-sm text-muted-foreground">Sin necesidad de cuenta — lleva tu conteo de entidades y estados trabajados/confirmados, guardado localmente.</p>

				<div class="grid gap-6 sm:grid-cols-2">
					<div>
						<p class="mb-2 font-mono text-xs font-semibold uppercase text-muted-foreground">DXCC (340 entidades vigentes · ARRL, ene. 2026)</p>
						<div class="mb-2 flex gap-2">
							<div class="flex-1"><label class="text-[10px] text-muted-foreground">Trabajadas</label><input type="number" id="dxcc-trabajadas" aria-label="Entidades DXCC trabajadas" min="0" max="340" value="0" class="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm"></div>
							<div class="flex-1"><label class="text-[10px] text-muted-foreground">Confirmadas</label><input type="number" id="dxcc-confirmadas" aria-label="Entidades DXCC confirmadas" min="0" max="340" value="0" class="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm"></div>
						</div>
						<div class="h-2 overflow-hidden rounded-full bg-background"><div id="dxcc-barra" class="h-full bg-primary" style="width:0%"></div></div>
						<p class="mt-1 text-xs text-muted-foreground" id="dxcc-texto">—</p>
					</div>
					<div>
						<p class="mb-2 font-mono text-xs font-semibold uppercase text-muted-foreground">WAS (50 estados)</p>
						<div class="mb-2 flex gap-2">
							<div class="flex-1"><label class="text-[10px] text-muted-foreground">Trabajados</label><input type="number" id="was-trabajados" aria-label="Estados WAS trabajados" min="0" max="50" value="0" class="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm"></div>
							<div class="flex-1"><label class="text-[10px] text-muted-foreground">Confirmados</label><input type="number" id="was-confirmados" aria-label="Estados WAS confirmados" min="0" max="50" value="0" class="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm"></div>
						</div>
						<div class="h-2 overflow-hidden rounded-full bg-background"><div id="was-barra" class="h-full bg-primary" style="width:0%"></div></div>
						<p class="mt-1 text-xs text-muted-foreground" id="was-texto">—</p>
					</div>
				</div>
			</div>
		</div>

		<!-- PSK REPORTER -->
		<div id="panel-pskreporter" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'pskreporter' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Red pública en tiempo real</p>
				<h2 class="mb-2 font-display text-2xl font-bold">PSK Reporter — ¿Quién me oye?</h2>
				<p class="mb-6 text-sm text-muted-foreground">Consulta quién ha reportado decodificar tu señal en FT8/FT4/PSK en los últimos 30 minutos — sin necesitar que trabajes esa estación.</p>

				<div class="flex gap-2">
					<input aria-label="Tu indicativo, ej. HK4RAU" type="text" id="psk-indicativo" placeholder="Tu indicativo, ej. HK4RAU" style="text-transform:uppercase" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
					<button id="psk-buscar" class="shrink-0 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Buscar</button>
				</div>
				<div id="psk-resultado" class="mt-4"></div>
			</div>
		</div>

		<!-- REVERSE BEACON NETWORK -->
		<div id="panel-rbn" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'rbn' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Para CW y RTTY</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Reverse Beacon Network</h2>
				<p class="mb-6 text-sm text-muted-foreground">Cientos de estaciones automáticas ("skimmers") alrededor del mundo decodifican CW y RTTY en vivo — llama CQ en CW y en segundos ve quién te está escuchando y con qué fuerza. Ideal para probar antenas antes de un concurso.</p>

				<div class="mb-4 flex gap-2">
					<input aria-label="Tu indicativo" type="text" id="rbn-indicativo" placeholder="Tu indicativo" style="text-transform:uppercase" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
					<a id="rbn-link" href="https://www.reversebeacon.net/main.php" target="_blank" rel="noopener" class="shrink-0 inline-flex items-center rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Abrir en RBN →</a>
				</div>
				<p class="text-xs text-muted-foreground">RBN no tiene una API pública estable para embeber resultados aquí — te llevamos directo a su buscador oficial con tu indicativo ya puesto.</p>
			</div>
		</div>

		<!-- APRS -->
		<div id="panel-aprs" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'aprs' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Estaciones y trackers en vivo</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Mapa APRS Colombia</h2>
				<p class="mb-6 text-sm text-muted-foreground">Ve en vivo las estaciones APRS activas cerca de Medellín y Colombia — vehículos, estaciones fijas, globos, todo lo que reporta posición por APRS.</p>

				<a href="https://aprs.fi/#!lat=6.24&lng=-75.58&z=8" target="_blank" rel="noopener" class="flex items-center justify-between rounded-xl border-2 border-primary/40 bg-primary/5 px-6 py-4 hover:bg-primary/10">
					<span>
						<span class="block font-display text-lg font-bold text-primary">Abrir mapa APRS en vivo</span>
						<span class="block text-sm text-muted-foreground">Centrado en Medellín — aprs.fi</span>
					</span>
					<span class="font-mono text-2xl text-primary">→</span>
				</a>
				<p class="mt-3 text-xs text-muted-foreground">aprs.fi requiere una clave de API gratuita para embeber datos directamente — por ahora te llevamos a su mapa oficial, ya centrado en la zona.</p>
			</div>
		</div>

		<!-- SMITH CHART -->
		<div id="panel-smith" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'smith' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Adaptación de impedancia</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Smith Chart</h2>
				<p class="mb-6 text-sm text-muted-foreground">Visualiza dónde cae tu impedancia dentro del Smith Chart clásico — entre más cerca del centro, mejor adaptada está tu carga.</p>

				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<div class="grid grid-cols-2 gap-3">
							<div><label for="smith-r" class="mb-1 block font-mono text-[11px] uppercase text-muted-foreground">R (Ω)</label><input type="number" id="smith-r" value="75" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm"></div>
							<div><label for="smith-x" class="mb-1 block font-mono text-[11px] uppercase text-muted-foreground">X (Ω, + inductivo)</label><input type="number" id="smith-x" value="25" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm"></div>
						</div>
						<div>
							<label for="smith-z0" class="mb-1 block font-mono text-[11px] uppercase text-muted-foreground">Z₀ de referencia (Ω)</label>
							<input type="number" id="smith-z0" value="50" class="w-full max-w-xs rounded-md border border-input bg-background px-3 py-2 font-mono text-sm">
						</div>
						<div id="smith-resultado" class="rounded-xl border border-border/70 bg-background p-4 text-sm space-y-1"></div>
					</div>
					<div>
						<svg id="smith-svg" viewBox="0 0 300 300" class="w-full rounded-xl border border-border/50 bg-background"></svg>
					</div>
				</div>
			</div>
		</div>

		<!-- TARJETA DE CAMPO -->
		<div id="panel-tarjeta" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'tarjeta' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Para llevar impresa</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Tarjeta de Campo</h2>
				<p class="mb-6 text-sm text-muted-foreground">Lo esencial en una sola hoja — frecuencias, alfabeto fonético y Código Q más usado. Ideal para POTA, simulacros, o guardar en la mochila de radio.</p>

				<button type="button" onclick="window.print()" class="rau-print-btn">🖨️ Imprimir Tarjeta de Campo</button>

				<div class="grid gap-4 rounded-xl border border-border/70 bg-background p-5 sm:grid-cols-2">
					<div>
						<p class="mb-2 font-mono text-xs font-bold uppercase text-accent">🚨 Emergencia</p>
						<?php $rau_em_ref = rau_get_repetidor_principal(); ?>
						<p class="font-mono text-lg font-bold"><?php echo esc_html( $rau_em_ref['frequency'] ); ?> MHz <span class="text-xs font-normal text-muted-foreground">T<?php echo esc_html( rtrim( rtrim( $rau_em_ref['tone'], '0' ), '.' ) ); ?> · <?php echo esc_html( $rau_em_ref['offset'] ); ?></span></p>
						<p class="font-mono text-sm text-muted-foreground">Simplex: 146.520 MHz</p>
					</div>
					<div>
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Alfabeto Fonético</p>
						<p class="text-xs leading-relaxed text-muted-foreground">Alfa · Bravo · Charlie · Delta · Echo · Foxtrot · Golf · Hotel · India · Juliett · Kilo · Lima · Mike · November · Oscar · Papa · Quebec · Romeo · Sierra · Tango · Uniform · Victor · Whiskey · X-ray · Yankee · Zulu</p>
					</div>
					<div>
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">Código Q esencial</p>
						<table class="w-full text-xs">
							<tbody class="font-mono text-muted-foreground">
								<tr><td class="py-0.5 pr-2 font-bold text-foreground">QRZ</td><td>¿Quién me llama?</td></tr>
								<tr><td class="py-0.5 pr-2 font-bold text-foreground">QTH</td><td>Ubicación</td></tr>
								<tr><td class="py-0.5 pr-2 font-bold text-foreground">QRM</td><td>Interferencia</td></tr>
								<tr><td class="py-0.5 pr-2 font-bold text-foreground">QSY</td><td>Cambiar de frecuencia</td></tr>
								<tr><td class="py-0.5 pr-2 font-bold text-foreground">QRT</td><td>Dejo de transmitir</td></tr>
							</tbody>
						</table>
					</div>
					<div>
						<p class="mb-2 font-mono text-xs font-bold uppercase text-primary">RST</p>
						<p class="text-xs text-muted-foreground">Legibilidad (1-5) · Fuerza (1-9) · Tono (1-9, solo CW)<br>Ej: "59" = perfecta legibilidad, señal excelente</p>
					</div>
				</div>
			</div>
		</div>

		<!-- BRANDMEISTER LAST HEARD -->
		<div id="panel-brandmeister" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'brandmeister' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Red DMR mundial</p>
				<h2 class="mb-2 font-display text-2xl font-bold">BrandMeister Last Heard</h2>
				<p class="mb-6 text-sm text-muted-foreground">Ve en vivo la actividad reciente de cualquier talkgroup o indicativo en la red DMR BrandMeister — la más grande del mundo.</p>

				<div class="flex gap-2">
					<input aria-label="Indicativo o talkgroup (ej. 214 para Colombia)" type="text" id="bm-buscar" placeholder="Indicativo o talkgroup (ej. 214 para Colombia)" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					<a id="bm-link" href="https://brandmeister.network/?page=lh" target="_blank" rel="noopener" class="shrink-0 inline-flex items-center rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Abrir →</a>
				</div>
				<p class="mt-3 text-xs text-muted-foreground">BrandMeister transmite su Last Heard por WebSocket en tiempo real, no por una simple consulta pública — te llevamos directo a su panel oficial, ya con tu búsqueda lista.</p>
			</div>
		</div>






		<!-- RECEPTOR KIWISDR -->
		<div id="panel-receptor" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'receptor' ) ); ?>" class="rau-panel" style="display:none">
			<div class="mb-4">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Receptor SDR · Medellín</p>
				<h2 class="font-display text-2xl font-bold">KiwiSDR HK4RAU</h2>
				<p class="mt-1 text-sm text-muted-foreground">Escucha de 0 a 30 MHz desde el receptor del club. Es el mismo receptor del centro de mando, en pantalla completa: si ya estabas escuchando, sigue sonando.</p>
			</div>
			<div id="panel-receptor-slot"><?php require_once get_template_directory() . '/inc/receptor-kiwi.php'; rau_receptor_kiwi(); ?></div>
		</div>

		<!-- RECORDATORIO DE LICENCIA -->
		<div id="panel-licencia" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'licencia' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Trámites RABCA</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Recordatorio de Licencia</h2>
				<p class="mb-6 text-sm text-muted-foreground">Guarda la fecha de vencimiento de tu licencia en este navegador (no se envía a ningún servidor, no necesitas cuenta) y descarga un recordatorio para tu calendario.</p>
				<div class="max-w-md space-y-4">
					<div>
						<label for="lic-fecha" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Fecha de vencimiento</label>
						<input type="date" id="lic-fecha" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					</div>
					<button id="lic-guardar" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Guardar en este navegador</button>
					<div id="lic-resultado" class="hidden rounded-xl border border-border/70 bg-background p-5 text-center">
						<p id="lic-estado" class="font-mono text-2xl font-bold">—</p>
						<button id="lic-ics" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">📅 Descargar recordatorio (.ics)</button>
					</div>
				</div>
			</div>
		</div>

		<!-- VERIFICADOR DE DIPLOMAS -->
		<div id="panel-diplomas" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'diplomas' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Verificación pública</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Verificador de Diplomas</h2>
				<p class="mb-6 text-sm text-muted-foreground">Escribe el número que aparece en tu diploma de RAU para confirmar que es auténtico.</p>
				<div class="max-w-md">
					<div class="flex gap-2">
						<input aria-label="Ej. RAU-2026-014" type="text" id="dip-numero" placeholder="Ej. RAU-2026-014" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						<button id="dip-buscar" class="shrink-0 inline-flex items-center justify-center rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Verificar</button>
					</div>
					<div id="dip-resultado" class="mt-4"></div>
				</div>
			</div>
		</div>

		<!-- MAIDENHEAD -->
		<div id="panel-maidenhead" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'maidenhead' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Localizador geográfico</p>
				<h2 class="mb-6 font-display text-2xl font-bold">Conversor Maidenhead</h2>
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<h3 class="font-display text-base font-semibold text-muted-foreground">Coordenadas → Grid Square</h3>
						<div class="grid grid-cols-2 gap-3">
							<div>
								<label for="mh-lat" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Latitud</label>
								<input type="number" id="mh-lat" step="0.0001" min="-90" max="90" placeholder="6.2442"
									class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
							</div>
							<div>
								<label for="mh-lng" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Longitud</label>
								<input type="number" id="mh-lng" step="0.0001" min="-180" max="180" placeholder="-75.5812"
									class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
							</div>
						</div>
						<button id="mh-calc" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">
							Calcular Grid Square
						</button>
						<button id="mh-gps" class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-border/70 bg-card px-5 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">
							📍 Usar mi ubicación actual
						</button>
						<div id="mh-result" class="hidden rounded-xl border border-primary/30 bg-primary/5 px-6 py-5 text-center">
							<p class="mb-1 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Grid Square (6 caracteres)</p>
							<p id="mh-grid" class="font-mono text-4xl font-bold tracking-widest text-primary">——</p>
						</div>
					</div>
					<div class="space-y-4">
						<h3 class="font-display text-base font-semibold text-muted-foreground">Grid Square → Coordenadas</h3>
						<div>
							<label for="mh-grid-in" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Grid Square (4 o 6 caracteres)</label>
							<input type="text" id="mh-grid-in" maxlength="6" placeholder="FJ26ff" style="text-transform:uppercase"
								class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
						</div>
						<button id="mh-decode" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">
							Decodificar
						</button>
						<div id="mh-decode-result" class="hidden space-y-2 rounded-xl border border-border/70 bg-background p-4">
							<div class="flex justify-between"><span class="font-mono text-xs text-muted-foreground">Latitud centro</span><span id="mh-dlat" class="font-mono text-sm font-bold text-primary">—</span></div>
							<div class="flex justify-between"><span class="font-mono text-xs text-muted-foreground">Longitud centro</span><span id="mh-dlng" class="font-mono text-sm font-bold text-primary">—</span></div>
						</div>
						<div class="rounded-xl border border-border/50 bg-background p-4 text-xs text-muted-foreground">
							<p class="mb-1 font-semibold text-foreground">¿Qué es el Maidenhead?</p>
							<p>Sistema de coordenadas simplificado para radioaficionados. Divide el planeta en cuadrículas de letras y números. Muy usado en POTA, concursos y modos digitales como FT8.</p>
							<p class="mt-2 font-mono">Medellín ≈ <strong class="text-primary">FJ26ff</strong></p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- PREFIJOS -->
		<div id="panel-prefijos" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'prefijos' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Identificación de indicativos</p>
				<h2 class="mb-6 font-display text-2xl font-bold">Buscador de Prefijos</h2>
				<div class="max-w-lg space-y-4">
					<div>
						<label for="pfx-input" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Indicativo o prefijo</label>
						<input type="text" id="pfx-input" maxlength="12" placeholder="HK4SSI"
							style="text-transform:uppercase"
							class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
					</div>
					<button id="pfx-search" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">
						Buscar
					</button>
					<div id="pfx-result" class="hidden rounded-xl border border-border/70 bg-background p-5">
						<div class="mb-3 flex items-center gap-3">
							<span id="pfx-flag" class="text-3xl"></span>
							<div>
								<p id="pfx-country" class="font-display text-lg font-bold">—</p>
								<p id="pfx-prefix" class="font-mono text-sm text-primary">—</p>
							</div>
						</div>
						<div class="space-y-1.5 text-sm">
							<div class="flex justify-between"><span class="text-muted-foreground">Continente</span><span id="pfx-cont" class="font-mono font-medium">—</span></div>
							<div class="flex justify-between"><span class="text-muted-foreground">Zona ITU</span><span id="pfx-itu" class="font-mono font-medium">—</span></div>
							<div class="flex justify-between"><span class="text-muted-foreground">Zona CQ</span><span id="pfx-cq" class="font-mono font-medium">—</span></div>
						</div>
					</div>
					<p id="pfx-notfound" class="hidden text-sm text-muted-foreground">No se encontró ese prefijo. Intenta con las primeras 2–3 letras del indicativo.</p>
				</div>
				<div class="mt-8">
					<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Prefijos colombianos y vecinos</p>
					<div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
						<?php
						$latam = [
							['HK','🇨🇴','Colombia','SA','9','11'],
							['5K','🇨🇴','Colombia (especial)','SA','9','11'],
							['YV','🇻🇪','Venezuela','SA','9','11'],
							['HJ','🇨🇴','Colombia (club)','SA','9','11'],
							['OA','🇵🇪','Perú','SA','10','11'],
							['HC','🇪🇨','Ecuador','SA','10','10'],
							['HP','🇵🇦','Panamá','NA','7','11'],
							['TI','🇨🇷','Costa Rica','NA','7','11'],
							['YN','🇳🇮','Nicaragua','NA','7','11'],
							['HQ','🇭🇳','Honduras','NA','7','11'],
							['TG','🇬🇹','Guatemala','NA','7','11'],
							['PY','🇧🇷','Brasil','SA','13','11'],
						];
						foreach($latam as $l): ?>
						<div class="flex items-center gap-3 rounded-lg border border-border/50 bg-background px-3 py-2.5">
							<span class="text-xl"><?php echo esc_html($l[1]); ?></span>
							<div>
								<p class="font-mono text-xs font-bold text-primary"><?php echo esc_html($l[0]); ?></p>
								<p class="text-xs text-muted-foreground"><?php echo esc_html($l[2]); ?></p>
							</div>
							<div class="ml-auto text-right">
								<p class="font-mono text-[10px] text-muted-foreground">ITU <?php echo esc_html($l[4]); ?></p>
								<p class="font-mono text-[10px] text-muted-foreground">CQ <?php echo esc_html($l[5]); ?></p>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- LOCALIZADOR DE INDICATIVO -->
		<div id="panel-indicativo" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'indicativo' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Identificación de indicativos</p>
				<h2 class="mb-2 font-display text-2xl font-bold">Localizador de Indicativo</h2>
				<p class="mb-6 text-sm text-muted-foreground">Escribe un indicativo DX y calcula automáticamente el país, zona y el azimut/distancia desde Medellín — sin tener que buscar el prefijo aparte y luego meterlo a mano en la calculadora.</p>
				<div class="max-w-lg space-y-4">
					<div>
						<label for="loc-input" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Indicativo DX</label>
						<input type="text" id="loc-input" maxlength="12" placeholder="W1AW" style="text-transform:uppercase"
							class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
					</div>
					<button id="loc-search" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">
						Localizar
					</button>
					<div id="loc-result" class="hidden rounded-xl border border-border/70 bg-background p-5">
						<div class="mb-4 flex items-center gap-3">
							<span id="loc-flag" class="text-3xl"></span>
							<div>
								<p id="loc-country" class="font-display text-lg font-bold">—</p>
								<p id="loc-prefix" class="font-mono text-sm text-primary">—</p>
							</div>
						</div>
						<div class="grid grid-cols-2 gap-3">
							<div class="rounded-lg border border-border/50 px-3 py-2.5">
								<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Distancia</p>
								<p id="loc-dist" class="font-mono text-2xl font-bold text-primary">—</p>
							</div>
							<div class="rounded-lg border border-border/50 px-3 py-2.5">
								<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Azimut (short path)</p>
								<p id="loc-az" class="font-mono text-2xl font-bold text-foreground">—</p>
							</div>
						</div>
						<div class="mt-3 space-y-1.5 text-sm">
							<div class="flex justify-between"><span class="text-muted-foreground">Dirección cardinal</span><span id="loc-dir" class="font-mono font-medium">—</span></div>
							<div class="flex justify-between"><span class="text-muted-foreground">Azimut long path</span><span id="loc-azlp" class="font-mono font-medium">—</span></div>
							<div class="flex justify-between"><span class="text-muted-foreground">Continente</span><span id="loc-cont" class="font-mono font-medium">—</span></div>
							<div class="flex justify-between"><span class="text-muted-foreground">Zona ITU</span><span id="loc-itu" class="font-mono font-medium">—</span></div>
							<div class="flex justify-between"><span class="text-muted-foreground">Zona CQ</span><span id="loc-cq" class="font-mono font-medium">—</span></div>
						</div>
					</div>
					<p id="loc-notfound" class="hidden text-sm text-muted-foreground">No se encontró ese prefijo. Intenta con las primeras 2–3 letras del indicativo.</p>
					<p id="loc-self" class="hidden text-sm text-muted-foreground">Ese indicativo es de Colombia — no hace falta calcular distancia con nosotros mismos. 🇨🇴</p>
				</div>
			</div>
		</div>

		<!-- CTCSS -->
		<div id="panel-ctcss" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'ctcss' ) ); ?>" class="rau-panel" style="display:none">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Repetidores y canales</p>
				<h2 class="mb-6 font-display text-2xl font-bold">Referencia CTCSS / DCS</h2>
				<button type="button" onclick="window.print()" class="rau-print-btn">🖨️ Imprimir / Guardar PDF</button>
				<div class="grid gap-8 lg:grid-cols-2">
					<div>
						<!-- La tabla genera 38 tonos (el juego estándar EIA/TIA-603). El "67" del título
						     anterior era el valor del PRIMER tono (67.0 Hz), no la cantidad. -->
						<h3 class="mb-4 font-display text-lg font-semibold">Tonos CTCSS (PL) — 38 tonos estándar EIA</h3>
						<div class="overflow-x-auto rounded-xl border border-border/70">
							<table class="min-w-[560px] w-full text-sm">
								<thead><tr class="border-b border-border/70 bg-muted/30">
									<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">No.</th>
									<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Hz</th>
									<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">No.</th>
									<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Hz</th>
									<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">No.</th>
									<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Hz</th>
								</tr></thead>
								<tbody id="ctcss-table"></tbody>
							</table>
						</div>
						<p class="mt-2 text-xs text-muted-foreground">Las repetidoras RAU usan tono <strong class="text-primary">T100 = 100.0 Hz</strong></p>
					</div>
					<div>
						<h3 class="mb-4 font-display text-lg font-semibold">Offsets de repetidor estándar</h3>
						<div class="space-y-2">
							<?php
							$offsets = [
								['29 MHz','−100 kHz'],
								['52 MHz','−1.0 MHz'],
								['146 MHz','±600 kHz'],
								['222 MHz','−1.6 MHz'],
								['440 MHz','±5 MHz'],
								['902 MHz','−25 MHz'],
								['1.2 GHz','−20 MHz'],
							];
							foreach($offsets as $o): ?>
							<div class="flex items-center justify-between rounded-lg border border-border/50 bg-background px-4 py-2.5">
								<span class="font-mono text-sm font-semibold text-primary"><?php echo esc_html($o[0]); ?></span>
								<span class="font-mono text-sm text-muted-foreground"><?php echo esc_html($o[1]); ?></span>
							</div>
							<?php endforeach; ?>
						</div>
						<div class="mt-6">
							<h3 class="mb-3 font-display text-base font-semibold">Repetidoras RAU</h3>
							<?php
							$reps = [
								['Medellín','146.675 MHz','−600 kHz','100.0 Hz'],
								['Bogotá','147.380 MHz','+600 kHz','100.0 Hz'],
								['Bucaramanga','147.925 MHz','−600 kHz','100.0 Hz'],
							];
							foreach($reps as $r): ?>
							<div class="mb-2 rounded-lg border border-primary/30 bg-primary/5 px-4 py-3">
								<div class="flex items-center justify-between">
									<span class="font-semibold text-sm"><?php echo esc_html($r[0]); ?></span>
									<span class="font-mono text-xs text-primary"><?php echo esc_html($r[1]); ?></span>
								</div>
								<div class="mt-1 flex gap-4 text-xs text-muted-foreground">
									<span>Offset: <?php echo esc_html($r[2]); ?></span>
									<span>CTCSS: <?php echo esc_html($r[3]); ?></span>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- CW -->
		<div id="panel-cw" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'cw' ) ); ?>" class="rau-panel" style="display:none">
		<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Telegrafía</p>
			<h2 class="mb-4 font-display text-2xl font-bold">Telegrafía CW — Práctica y Referencia</h2>

			<!-- Sub-tabs CW -->
			<div class="mb-6 flex flex-wrap gap-2">
				<button data-cwtab="reproductor" class="cw-sub-tab inline-flex rounded-lg border px-3 py-1.5 font-mono text-xs transition-colors border-primary bg-primary/10 text-primary focus:outline-none focus:ring-2 focus:ring-ring">▶ Reproductor</button>
				<button data-cwtab="tabla" class="cw-sub-tab inline-flex rounded-lg border px-3 py-1.5 font-mono text-xs transition-colors border-border/70 bg-card text-muted-foreground hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-ring">Tabla Morse</button>
				<button data-cwtab="abrev" class="cw-sub-tab inline-flex rounded-lg border px-3 py-1.5 font-mono text-xs transition-colors border-border/70 bg-card text-muted-foreground hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-ring">Abreviaciones</button>
				<button data-cwtab="entrenamiento" class="cw-sub-tab inline-flex rounded-lg border px-3 py-1.5 font-mono text-xs transition-colors border-border/70 bg-card text-muted-foreground hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-ring">Entrenamiento</button>
			</div>

			<!-- SUB: Reproductor -->
			<div id="cw-reproductor" class="cw-sub-panel">
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<div><label for="cw-text" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Texto a enviar en CW</label>
						<input type="text" id="cw-text" maxlength="200" placeholder="CQ CQ HK4RAU HK4RAU AR" style="text-transform:uppercase"
							class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring"></div>
						<div class="grid grid-cols-2 gap-3">
							<div><label for="cw-wpm" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Velocidad (WPM)</label>
							<input type="range" id="cw-wpm" min="5" max="40" value="15" class="w-full accent-primary">
							<p class="mt-1 text-center font-mono text-xs text-primary"><span id="cw-wpm-val">15</span> WPM</p></div>
							<div><label for="cw-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tono (Hz)</label>
							<input type="range" id="cw-freq" min="400" max="900" value="600" step="50" class="w-full accent-primary">
							<p class="mt-1 text-center font-mono text-xs text-primary"><span id="cw-freq-val">600</span> Hz</p></div>
						</div>
						<div class="flex gap-3">
							<button id="cw-play" class="inline-flex flex-1 items-center justify-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">▶ Reproducir</button>
							<button id="cw-stop" class="inline-flex items-center justify-center gap-2 rounded-md border border-border/70 bg-card px-5 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">■ Detener</button>
						</div>
						<div id="cw-display" class="rounded-xl border border-border/70 bg-background p-4 font-mono text-sm text-muted-foreground min-h-[60px]">El código Morse aparecerá aquí durante la reproducción…</div>
					</div>
					<div>
						<p class="mb-2 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Frases rápidas</p>
						<div class="grid grid-cols-2 gap-2 mb-4">
							<?php foreach(['CQ CQ HK4RAU AR','73 DE HK4RAU SK','QSL 5NN TU','QRZ? DE HK4RAU','QTH MEDELLIN HK'] as $frase): ?>
							<button onclick="document.getElementById('cw-text').value='<?php echo esc_js($frase); ?>'"
								class="rounded-lg border border-border/70 bg-background px-3 py-2 text-left font-mono text-xs text-muted-foreground hover:border-primary/50 hover:text-primary transition-colors">
								<?php echo esc_html($frase); ?>
							</button>
							<?php endforeach; ?>
						</div>
						<p class="mb-2 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Decodificador texto → Morse</p>
						<div id="cw-morse-visual" class="rounded-xl border border-border/70 bg-background p-3 font-mono text-xs text-primary leading-relaxed min-h-[60px] break-all"></div>
					</div>
				</div>
			</div>

			<!-- SUB: Tabla Morse -->
			<div id="cw-tabla" class="cw-sub-panel" style="display:none">
				<div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
					<?php
					$morse_chars = ['A'=>'.-','B'=>'-...','C'=>'-.-.','D'=>'-..','E'=>'.','F'=>'..-.','G'=>'--.','H'=>'....','I'=>'..','J'=>'.---','K'=>'-.-','L'=>'.-..','M'=>'--','N'=>'-.','O'=>'---','P'=>'.--.','Q'=>'--.-','R'=>'.-.','S'=>'...','T'=>'-','U'=>'..-','V'=>'...-','W'=>'.--','X'=>'-..-','Y'=>'-.--','Z'=>'--..',
					'0'=>'-----','1'=>'.----','2'=>'..---','3'=>'...--','4'=>'....-','5'=>'.....','6'=>'-....','7'=>'--...','8'=>'---..','9'=>'----.',
					'/'=>'-..-.','?'=>'..--..','='=>'-...-','AR'=>'.-.-.','.'=>'.-.-.-',','=>'--..--','SK'=>'...-.-','BK'=>'-...-.-'];
					foreach($morse_chars as $char => $code): ?>
					<div class="flex flex-col items-center rounded-xl border border-border/60 bg-background py-3 px-2">
						<span class="font-mono text-lg font-bold text-primary"><?php echo esc_html($char); ?></span>
						<span class="font-mono text-sm text-muted-foreground mt-1"><?php echo esc_html($code); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- SUB: Abreviaciones CW -->
			<div id="cw-abrev" class="cw-sub-panel" style="display:none">
				<div class="mb-3">
					<input aria-label="Buscar abreviación o significado" type="text" id="abrev-search" placeholder="Buscar abreviación o significado…"
						class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<div class="overflow-x-auto rounded-xl border border-border/70">
					<table class="min-w-[520px] w-full text-sm">
						<thead><tr class="border-b border-border/70 bg-muted/30">
							<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground w-20">Abrev.</th>
							<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Significado</th>
							<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Notas</th>
						</tr></thead>
						<tbody id="abrev-tbody"></tbody>
					</table>
				</div>
			</div>

			<!-- SUB: Entrenamiento -->
			<div id="cw-entrenamiento" class="cw-sub-panel" style="display:none">
				<div class="grid gap-6 lg:grid-cols-2">
					<div class="space-y-4">
						<p class="text-sm text-muted-foreground">Escucha el código Morse y escribe la letra o palabra que oíste. Perfecto para practicar la recepción.</p>
						<div class="flex gap-3">
							<div class="flex-1"><label for="train-wpm" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Velocidad (WPM)</label>
							<input type="range" id="train-wpm" min="5" max="30" value="10" class="w-full accent-primary">
							<p class="mt-1 text-center font-mono text-xs text-primary"><span id="train-wpm-val">10</span> WPM</p></div>
							<div class="flex-1"><label for="train-mode" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Modo</label>
							<select id="train-mode" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
								<option value="letters">Solo letras</option>
								<option value="numbers">Solo números</option>
								<option value="mixed">Letras y números</option>
								<option value="words">Palabras comunes</option>
							</select></div>
						</div>
						<button id="train-play" class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">▶ Escuchar siguiente</button>
						<div><label for="train-input" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tu respuesta</label>
						<input type="text" id="train-input" maxlength="20" placeholder="Escribe lo que escuchaste…" style="text-transform:uppercase"
							class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring"></div>
						<button id="train-check" class="inline-flex w-full items-center justify-center rounded-md border border-border/70 bg-card px-5 py-2.5 text-sm font-medium transition-colors hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">Verificar</button>
						<div id="train-feedback" class="hidden rounded-xl border px-4 py-3 text-sm font-medium"></div>
					</div>
					<div class="rounded-xl border border-border/70 bg-background p-4">
						<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Estadísticas de sesión</p>
						<div class="space-y-3">
							<div class="flex justify-between"><span class="text-sm text-muted-foreground">Correctas</span><span id="train-correct" class="font-mono font-bold text-emerald-400">0</span></div>
							<div class="flex justify-between"><span class="text-sm text-muted-foreground">Incorrectas</span><span id="train-wrong" class="font-mono font-bold text-rose-400">0</span></div>
							<div class="flex justify-between"><span class="text-sm text-muted-foreground">Racha actual</span><span id="train-racha" class="font-mono font-bold text-primary">0</span></div>
							<div class="flex justify-between"><span class="text-sm text-muted-foreground">Precisión</span><span id="train-pct" class="font-mono font-bold text-primary">—</span></div>
						</div>
						<button id="train-reset" class="mt-4 w-full rounded-md border border-border/70 px-3 py-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors">Reiniciar estadísticas</button>
					</div>
				</div>
			</div>

		</div>
		</div>
	</div>


<!-- ═══════════════════════════════════════════════════════
     PANEL: Línea de transmisión
═══════════════════════════════════════════════════════ -->

</div>

<!-- ═══════════════════════════════════════════════════════
     PANEL: Azimuth y distancia entre grid squares
═══════════════════════════════════════════════════════ -->
<div id="panel-azimuth" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'azimuth' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Antenas direccionales</p>
		<h2 class="mb-6 font-display text-2xl font-bold">Azimuth y Distancia entre Grid Squares</h2>
		<div class="grid gap-6 lg:grid-cols-2">
			<div class="space-y-4">
				<div>
					<label for="az-origen" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tu grid square (origen)</label>
					<input type="text" id="az-origen" maxlength="6" placeholder="FJ26ff" style="text-transform:uppercase"
						class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<div>
					<label for="az-destino" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Grid square destino</label>
					<input type="text" id="az-destino" maxlength="6" placeholder="FN31pr" style="text-transform:uppercase"
						class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<button id="az-calc" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">Calcular</button>
				<p class="text-xs text-muted-foreground">Medellín ≈ FJ26ff · Bogotá ≈ FJ24xr · Caracas ≈ FK60nl · Miami ≈ EL95vs</p>
			</div>
			<div id="az-result" class="rounded-xl border border-border/70 bg-background p-6">
				<p class="text-sm text-muted-foreground">Ingresa dos grid squares para calcular la distancia y el azimuth de apuntamiento.</p>
			</div>
		</div>
	</div>
</div>

<!-- COBERTURA DE REPETIDOR -->
<div id="panel-cobertura" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'cobertura' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Línea de vista VHF/UHF</p>
		<h2 class="mb-2 font-display text-2xl font-bold">Simulador de Cobertura de Repetidor</h2>
		<p class="mb-6 text-sm text-muted-foreground">Estimación geométrica de horizonte de radio según la curvatura terrestre. <strong class="text-foreground">No reemplaza una prueba real</strong>: montañas, edificios y vegetación entre tu QTH y el repetidor pueden bloquear la señal aunque estés dentro del rango teórico.</p>

		<div class="grid gap-6 lg:grid-cols-2">
			<div class="space-y-4">
				<div>
					<label for="cov-grid" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tu grid square (Maidenhead)</label>
					<div class="flex gap-2">
						<input type="text" id="cov-grid" maxlength="8" placeholder="FJ26ff" style="text-transform:uppercase"
							class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
						<button type="button" id="cov-gps" class="shrink-0 inline-flex items-center justify-center rounded-md border border-border bg-background px-3 text-xs font-medium hover:bg-accent">📍 GPS</button>
					</div>
				</div>
				<div>
					<label for="cov-altura" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Altura de tu antena sobre el suelo (m)</label>
					<input type="number" id="cov-altura" value="10" min="1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<div>
					<label for="cov-repetidor" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Repetidor</label>
					<select id="cov-repetidor" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						<option value="">Cargando repetidores…</option>
					</select>
				</div>
				<button id="cov-calc" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">
					Calcular cobertura
				</button>
				<p id="cov-empty" class="hidden text-sm text-muted-foreground">Aún no hay repetidores con coordenadas cargadas. Agrégalas en el Escritorio → Repetidores.</p>
			</div>

			<div>
				<div id="cov-result" class="hidden rounded-xl border border-border/70 bg-background p-5">
					<div class="mb-4 grid grid-cols-2 gap-3">
						<div class="rounded-lg border border-border/50 px-3 py-2.5 text-center">
							<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Distancia real</p>
							<p id="cov-dist" class="font-mono text-2xl font-bold text-foreground">—</p>
						</div>
						<div class="rounded-lg border border-border/50 px-3 py-2.5 text-center">
							<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Horizonte teórico</p>
							<p id="cov-horizonte" class="font-mono text-2xl font-bold text-primary">—</p>
						</div>
					</div>
					<div id="cov-veredicto" class="rounded-lg border px-4 py-3 text-sm font-semibold"></div>
					<div class="mt-4 space-y-1.5 text-xs text-muted-foreground">
						<div class="flex justify-between"><span>Tu horizonte</span><span id="cov-h1" class="font-mono">—</span></div>
						<div class="flex justify-between"><span>Horizonte del repetidor</span><span id="cov-h2" class="font-mono">—</span></div>
						<div class="flex justify-between"><span>Altura considerada del repetidor</span><span id="cov-altrep" class="font-mono">—</span></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- FRECUENCIAS DE SATÉLITES -->
<div id="panel-satfreq" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'satfreq' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Satélites de radioaficionados</p>
		<h2 class="mb-2 font-display text-2xl font-bold">Frecuencias de Satélites Activos</h2>
		<p class="mb-6 text-sm text-muted-foreground">Satélites FM operativos según AMSAT (actualizado periódicamente). Verifica siempre el <a href="https://www.amsat.org/status/" target="_blank" rel="noopener" class="text-primary hover:underline">estado en vivo de AMSAT</a> antes de una pasada — el estado de un satélite puede cambiar de un día para otro.</p>

		<div class="overflow-x-auto rounded-xl border border-border/70">
			<table class="min-w-[720px] w-full text-sm">
				<thead>
					<tr class="border-b border-border/70 bg-background/60 text-left font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
						<th class="px-4 py-3">Satélite</th>
						<th class="px-4 py-3">Subida (uplink)</th>
						<th class="px-4 py-3">Bajada (downlink)</th>
						<th class="px-4 py-3">Notas</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-border/50">
					<?php foreach ([
						['AO-91 (RadFxSat / Fox-1B)', '435.250 MHz (67.0 Hz)', '145.960 MHz', 'No usar en eclipse (batería débil).'],
						['SO-50 (SaudiSat-1C)', '145.850 MHz (67.0 Hz)', '436.795 MHz', 'Armar timer de 10 min con tono 74.4 Hz antes de usar.'],
						['ISS — Repetidor cruzado', '145.990 MHz (67.0 Hz)', '437.800 MHz', 'Verificar estado en ariss.org — a veces apagado por actividad de la tripulación.'],
						['PO-101 (Diwata-2)', '437.500 MHz (141.3 Hz)', '145.900 MHz', 'Activado por horario. Señales reportadas como débiles últimamente.'],
						['AO-123 (ASRTU-1)', '145.850 MHz (67.0 Hz)', '435.400 MHz', 'En comisionamiento.'],
						['CAS-3H (LilacSat-2)', '144.350 MHz', '437.200 MHz', 'Transpondedor FM sin horario fijo — a veces solo baliza de telemetría.'],
						['IO-86 (LAPAN-A2)', '145.880 MHz (88.5 Hz)', '435.880 MHz', 'Órbita ecuatorial — visible desde Colombia con buena frecuencia. Activado por horario.'],
						['RS95S (QMR-KWT-2)', '145.920 MHz (67.0 Hz)', '436.950 MHz', 'En pruebas — revisar AMSAT-BB por anuncios.'],
						['TEVEL2-1 a TEVEL2-9', '145.970 MHz', '436.400 MHz', 'Constelación educativa — baliza en 436.400 MHz (BPSK 9600).'],
					] as $sat) : ?>
					<tr class="hover:bg-muted/10">
						<td class="px-4 py-3 font-semibold"><?php echo esc_html( $sat[0] ); ?></td>
						<td class="px-4 py-3 font-mono text-primary"><?php echo esc_html( $sat[1] ); ?></td>
						<td class="px-4 py-3 font-mono text-primary"><?php echo esc_html( $sat[2] ); ?></td>
						<td class="px-4 py-3 text-xs text-muted-foreground"><?php echo esc_html( $sat[3] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="mt-3 text-xs text-muted-foreground">Fuente: <a href="https://www.amsat.org/live-fm-satellites/" target="_blank" rel="noopener" class="text-primary hover:underline">AMSAT — Live FM Satellites</a>. Los satélites de radioaficionados cambian de estado con frecuencia (baterías, eclipses, actividad programada) — esta tabla es una referencia, no un estado en vivo.</p>
	</div>
</div>

<!-- DOPPLER -->
<div id="panel-doppler" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'doppler' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Corrección de frecuencia</p>
		<h2 class="mb-2 font-display text-2xl font-bold">Calculadora de Doppler</h2>
		<p class="mb-6 text-sm text-muted-foreground">Durante una pasada de satélite LEO, la frecuencia percibida cambia porque el satélite se acerca y se aleja de ti. Este corrimiento es mayor en UHF que en VHF, y máximo en el horizonte (AOS/LOS), llegando a cero cuando el satélite está en su punto más alto (TCA).</p>

		<div class="grid gap-6 lg:grid-cols-2">
			<div class="space-y-4">
				<div>
					<label for="dop-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia nominal (MHz)</label>
					<input type="text" id="dop-freq" value="145.960" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<div>
					<label for="dop-vel" class="mb-1 flex items-center justify-between font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
						<span>Velocidad radial (km/s)</span>
						<span id="dop-vel-val" class="text-primary">7.5</span>
					</label>
					<input type="range" id="dop-vel" min="0" max="7.6" step="0.1" value="7.5" class="w-full accent-primary">
					<p class="mt-1 text-xs text-muted-foreground">0 = satélite en el cénit (TCA) · ~7.5 = máximo, justo en el horizonte (AOS/LOS)</p>
				</div>
			</div>

			<div class="rounded-xl border border-border/70 bg-background p-5">
				<div class="grid grid-cols-2 gap-4 text-center">
					<div>
						<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Acercándose (AOS)</p>
						<p id="dop-up" class="mt-1 font-mono text-xl font-bold text-emerald-400">—</p>
						<p class="mt-0.5 font-mono text-xs text-muted-foreground">sintoniza más alto</p>
					</div>
					<div>
						<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Alejándose (LOS)</p>
						<p id="dop-down" class="mt-1 font-mono text-xl font-bold text-rose-400">—</p>
						<p class="mt-0.5 font-mono text-xs text-muted-foreground">sintoniza más bajo</p>
					</div>
				</div>
				<div class="mt-4 border-t border-border/50 pt-3 text-center">
					<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Corrimiento máximo (Δf)</p>
					<p id="dop-delta" class="mt-1 font-mono text-lg font-bold text-foreground">—</p>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- PREDICCIÓN DE PASADAS -->
<div id="panel-satpass" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'satpass' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Mecánica orbital</p>
		<h2 class="mb-2 font-display text-2xl font-bold">Predicción de Pasadas</h2>
		<p class="mb-6 text-sm text-muted-foreground">Calculado con datos orbitales reales (TLE) de <a href="https://celestrak.org/" target="_blank" rel="noopener" class="text-primary hover:underline">CelesTrak</a> y propagación SGP4. <strong class="text-foreground">Esto es una estimación</strong> — la precisión del TLE se degrada con el tiempo; para operaciones críticas, cruza siempre con <a href="https://www.amsat.org/track/index.php" target="_blank" rel="noopener" class="text-primary hover:underline">AMSAT</a> o tu app de seguimiento habitual.</p>

		<div class="grid gap-4 sm:grid-cols-3">
			<div>
				<label for="sp-sat" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Satélite</label>
				<select id="sp-sat" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					<option value="">Cargando satélites…</option>
				</select>
			</div>
			<div>
				<label for="sp-grid" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tu grid square</label>
				<div class="flex gap-2">
					<input type="text" id="sp-grid" maxlength="8" placeholder="FJ26ff" style="text-transform:uppercase" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
					<button type="button" id="sp-gps" aria-label="Obtener mi posición desde el GPS del dispositivo" class="shrink-0 inline-flex items-center justify-center rounded-md border border-border bg-background px-3 text-xs font-medium hover:bg-accent">📍</button>
				</div>
			</div>
			<div class="flex items-end">
				<button id="sp-calc" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90">
					Calcular próximas pasadas
				</button>
			</div>
		</div>

		<p id="sp-status" class="mt-4 text-sm text-muted-foreground">Cargando datos orbitales de CelesTrak…</p>

		<div id="sp-results" class="mt-4 hidden overflow-x-auto rounded-xl border border-border/70">
			<table class="min-w-[760px] w-full text-sm">
				<thead>
					<tr class="border-b border-border/70 bg-background/60 text-left font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
						<th class="px-4 py-3">AOS (sube)</th>
						<th class="px-4 py-3">Az. AOS</th>
						<th class="px-4 py-3">Elev. máx.</th>
						<th class="px-4 py-3">LOS (baja)</th>
						<th class="px-4 py-3">Az. LOS</th>
						<th class="px-4 py-3">Duración</th>
					</tr>
				</thead>
				<tbody id="sp-tbody"></tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PANEL: Bandas Colombia
═══════════════════════════════════════════════════════ -->
<div id="panel-bandas" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'bandas' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Normativa MinTIC</p>
		<h2 class="mb-6 font-display text-2xl font-bold">Bandas de Radioaficionados en Colombia</h2>
		<p class="mb-4 text-sm text-muted-foreground">Atribución de bandas del artículo 29 y plan de frecuencias del artículo 30 del Decreto 963 de 2009, reglamento del servicio de radioaficionado. Categorías: <span class="font-mono text-primary">A</span> = Segunda o de Novicio · <span class="font-mono text-primary">B</span> = Primera o de Experto · <span class="font-mono text-primary">C</span> = Categoría Avanzada.</p>
		<div class="mb-4 rounded-lg border border-amber-400/30 bg-amber-400/5 px-4 py-3 text-xs text-muted-foreground">
			<strong class="text-amber-400">Nota legal.</strong> El normograma del MinTIC marca los artículos 29 (atribución de bandas) y 34 (bandas del Novicio) como <em>no compilados</em> en el Decreto Único 1078 de 2015, con remisión a su artículo 3.1.1. Siguen siendo la referencia técnica publicada, pero su vigencia formal es una cuestión jurídica que esta tabla no resuelve. Las potencias y los distintivos (artículos 35 y 38) sí quedaron compilados, en 2.2.5.7.1 y 2.2.5.7.4.
		</div>
		<div class="mb-4 rounded-lg border border-primary/30 bg-primary/5 px-4 py-3 text-xs text-muted-foreground">
			<strong class="text-primary">La potencia no depende de la banda, sino del grupo de frecuencias y de tu categoría.</strong>
			El decreto no fija un límite por banda: el artículo 2.2.5.7.4 lo hace por rango (MF/HF/VHF/UHF) y distingue estación fija de móvil. La tabla de potencias está justo debajo.
		</div>
		<div class="overflow-x-auto rounded-xl border border-border/70">
			<table class="min-w-[620px] w-full text-sm">
				<thead><tr class="border-b border-border/70 bg-muted/30">
					<th class="px-3 py-2.5 text-left font-mono text-xs text-muted-foreground">Banda</th>
					<th class="px-3 py-2.5 text-left font-mono text-xs text-muted-foreground">Rango (MHz)</th>
					<th class="px-3 py-2.5 text-left font-mono text-xs text-muted-foreground">Modos</th>
					<th class="px-3 py-2.5 text-left font-mono text-xs text-muted-foreground">Grupo / atribución</th>
					<th class="px-3 py-2.5 text-left font-mono text-xs text-muted-foreground">Licencia</th>
					<th class="px-3 py-2.5 text-left font-mono text-xs text-muted-foreground">Novicio (A)</th>
				</tr></thead>
				<tbody>
				<?php
				// [banda, rango MHz, modos, grupo de potencia, atribución UIT, categorías, detalle para Novicio]
				// El grupo (MF/HF/VHF/UHF) es lo que determina la potencia según el art. 2.2.5.7.4.
				// Las restricciones de Novicio salen textualmente del art. 2.2.5.6.5, que designa
				// banda por banda y tipo de emisión por tipo de emisión qué puede usar la Segunda categoría.
				$bandas = [
					['160m','1.800 – 2.000','Todos','MF','Primaria','A,B,C','banda completa, todos los modos'],
					['80m','3.500 – 4.000','Todos','HF','Primaria hasta 3.750','A,B,C','solo hasta 3.750 (fonía desde 3.525)'],
					['60m','5.3515 – 5.3665','USB / datos','HF','No figura en el decreto','B,C','no autorizada'],
					['40m','7.000 – 7.300','Todos','HF','Primaria','A,B,C','banda completa (fonía desde 7.040)'],
					['30m','10.100 – 10.150','CW / datos','HF','Secundaria','B,C','no autorizada'],
					['20m','14.000 – 14.350','Todos','HF','Primaria','B,C','no autorizada'],
					['17m','18.068 – 18.168','Todos','HF','Primaria','B,C','no autorizada'],
					['15m','21.000 – 21.450','Todos','HF','Primaria','A,B,C','solo CW y datos, sin fonía'],
					['12m','24.890 – 24.990','Todos','HF','Primaria','B,C','no autorizada'],
					['10m','28.000 – 29.700','Todos','HF','Primaria','A,B,C','solo hasta 29.5 (fonía desde 28.3)'],
					['6m','50.000 – 54.000','Todos','VHF','Primaria','A,B,C','solo CW y datos, sin fonía'],
					['2m','144.000 – 148.000','Todos','VHF','Primaria','A,B,C','banda completa, todos los modos'],
					['1.25m','220.000 – 225.000','Todos','VHF','Co-primaria','B,C','no autorizada'],
					['70cm','430.000 – 440.000','Todos','UHF','Co-primaria','A,B,C','solo CW y datos, sin fonía'],
					['33cm','902.000 – 928.000','Todos','UHF','Secundaria','B,C','no autorizada'],
					['23cm','1240.000 – 1300.000','Todos','UHF','Secundaria','B,C','no autorizada'],
				];
				foreach($bandas as $b):
					$lic = $b[5];
					$nota = $b[6];
					$nota_clase = ( $nota === 'no autorizada' ) ? 'text-muted-foreground' : ( strpos( $nota, 'completa' ) !== false ? 'text-emerald-400' : 'text-amber-400' );
				?>
				<tr class="border-b border-border/50 last:border-0 hover:bg-muted/20">
					<td class="px-3 py-2 font-mono text-xs font-bold text-primary"><?php echo esc_html($b[0]); ?></td>
					<td class="px-3 py-2 font-mono text-xs"><?php echo esc_html($b[1]); ?></td>
					<td class="px-3 py-2 text-xs text-muted-foreground"><?php echo esc_html($b[2]); ?></td>
					<td class="px-3 py-2 font-mono text-xs text-muted-foreground"><?php echo esc_html($b[3]); ?><span class="block text-[10px] opacity-70"><?php echo esc_html($b[4]); ?></span></td>
					<td class="px-3 py-2">
						<?php foreach(explode(',',$lic) as $l): ?>
						<span class="mr-1 inline-flex rounded-full px-1.5 py-0.5 font-mono text-[10px] font-bold ring-1 text-primary bg-primary/10 ring-primary/20"><?php echo esc_html(trim($l)); ?></span>
						<?php endforeach; ?>
					</td>
					<td class="px-3 py-2 text-xs <?php echo esc_attr($nota_clase); ?>"><?php echo esc_html($nota); ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<h3 class="mb-3 mt-8 font-display text-lg font-semibold">Potencias máximas autorizadas</h3>
		<p class="mb-4 text-sm text-muted-foreground">Artículo 2.2.5.7.4 del Decreto 1078 de 2015. Todos los valores en vatios PEP.</p>
		<div class="overflow-x-auto rounded-xl border border-border/70">
			<table class="min-w-[560px] w-full text-sm">
				<thead><tr class="border-b border-border/70 bg-muted/30">
					<th class="px-3 py-2.5 text-left font-mono text-xs text-muted-foreground">Rango</th>
					<th class="px-3 py-2.5 text-left font-mono text-xs text-muted-foreground">Avanzada (C)</th>
					<th class="px-3 py-2.5 text-left font-mono text-xs text-muted-foreground">Experto (B)</th>
					<th class="px-3 py-2.5 text-left font-mono text-xs text-muted-foreground">Novicio (A)</th>
				</tr></thead>
				<tbody>
				<?php
				// Artículo 38 del Decreto 963 de 2009, compilado en el 2.2.5.7.4 del Decreto 1078 de 2015.
				// En SHF y EHF el decreto no asigna nada a la Segunda categoría: esas bandas no le
				// están designadas, así que la celda va vacía a propósito, no es un dato faltante.
				$potencias = [
					['MF (160m)',                '2000 W',                      '600 W',                      '200 W'],
					['HF (80m – 10m)',           'fija 2000 W · móvil 200 W',   'fija 600 W · móvil 100 W',   'fija 100 W · móvil 50 W'],
					['VHF (6m, 2m, 1.25m)',      '100 W',                       '50 W',                       '25 W'],
					['UHF (70cm, 33cm, 23cm)',   '50 W',                        '50 W',                       '25 W'],
					['SHF (13cm y superiores)',  'fija 50 W · móvil 25 W',      '25 W',                       '—'],
					['EHF (6mm y superiores)',   '10 W',                        '10 W',                       '—'],
				];
				foreach($potencias as $p): ?>
				<tr class="border-b border-border/50 last:border-0 hover:bg-muted/20">
					<td class="px-3 py-2 font-mono text-xs font-bold text-primary"><?php echo esc_html($p[0]); ?></td>
					<td class="px-3 py-2 font-mono text-xs"><?php echo esc_html($p[1]); ?></td>
					<td class="px-3 py-2 font-mono text-xs"><?php echo esc_html($p[2]); ?></td>
					<td class="px-3 py-2 font-mono text-xs"><?php echo esc_html($p[3]); ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="mt-3 text-xs text-muted-foreground">El mismo artículo obliga a usar <strong>la mínima potencia necesaria</strong> para el contacto. Única excepción al alza: la categoría Avanzada puede llegar a 2000 W PEP en VHF y UHF exclusivamente para rebote lunar (EME).</p>
		<p class="mt-3 text-xs text-muted-foreground">* Tabla de referencia rápida. Los rangos y potencias pueden cambiar por actualizaciones normativas — confirma siempre contra el <a href="https://normograma.mintic.gov.co/mintic/compilacion/docs/decreto_1078_2015.htm" target="_blank" rel="noopener" class="text-primary hover:underline">Decreto 1078 de 2015</a> vigente (Título 5, Parte 2, Libro 2) antes de transmitir.</p>
	</div>
</div>

<!-- PLAN DE BANDAS VISUAL -->
<div id="panel-planbandas" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'planbandas' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Mismos datos, vista visual</p>
		<h2 class="mb-2 font-display text-2xl font-bold">Plan de Bandas (visual)</h2>
		<p class="mb-6 text-sm text-muted-foreground">Las mismas bandas de la tabla de Colombia, mostradas como una regla de frecuencias — más fácil de leer de un vistazo. Pasa el mouse sobre cada bloque para ver el detalle.</p>

		<p class="mb-2 font-mono text-xs font-semibold uppercase text-muted-foreground">HF (1.8 – 30 MHz)</p>
		<div id="pb-hf" class="mb-6 h-14 w-full"></div>

		<p class="mb-2 font-mono text-xs font-semibold uppercase text-muted-foreground">VHF / UHF (50 – 1300 MHz)</p>
		<div id="pb-vhf" class="h-14 w-full"></div>

		<div id="pb-tooltip" class="mt-4 hidden rounded-xl border border-border/70 bg-background p-4 text-sm"></div>
	</div>
</div>




<!-- ═══════════════════════════════════════════════════════
     PANEL: Reportes RST
═══════════════════════════════════════════════════════ -->
<div id="panel-rst" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'rst' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Reportes de señal</p>
		<h2 class="mb-6 font-display text-2xl font-bold">Sistema de Reportes RST</h2>
		<div class="grid gap-6 lg:grid-cols-3">
			<div>
				<h3 class="mb-3 font-display text-base font-semibold">R — Readability (Legibilidad)</h3>
				<div class="space-y-2">
					<?php foreach([['R1','Ininteligible'],['R2','Apenas inteligible'],['R3','Inteligible con dificultad'],['R4','Inteligible sin dificultad'],['R5','Perfectamente inteligible']] as $r): ?>
					<div class="flex gap-3 rounded-lg border border-border/50 bg-background px-3 py-2">
						<span class="shrink-0 font-mono text-sm font-bold text-primary"><?php echo esc_html( $r[0] ); ?></span>
						<span class="text-sm text-muted-foreground"><?php echo esc_html( $r[1] ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div>
				<h3 class="mb-3 font-display text-base font-semibold">S — Signal Strength (Intensidad)</h3>
				<div class="space-y-2">
					<?php foreach([['S1','Apenas perceptible'],['S2','Muy débil'],['S3','Débil'],['S4','Regular'],['S5','Más o menos buena'],['S6','Buena'],['S7','Moderadamente fuerte'],['S8','Fuerte'],['S9','Extremadamente fuerte']] as $s): ?>
					<div class="flex gap-3 rounded-lg border border-border/50 bg-background px-3 py-2">
						<span class="shrink-0 font-mono text-sm font-bold text-primary"><?php echo esc_html( $s[0] ); ?></span>
						<span class="text-sm text-muted-foreground"><?php echo esc_html( $s[1] ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div>
				<h3 class="mb-3 font-display text-base font-semibold">T — Tone (Tono CW)</h3>
				<div class="space-y-2">
					<?php foreach([['T1','Muy ronco, interferencia amplia'],['T2','Muy áspero y ronco'],['T3','Áspero, sin filtrar'],['T4','Áspero, filtrado insuficiente'],['T5','Ripple de CA moderado'],['T6','Filtrado, presencia de ripple'],['T7','Casi puro, rastro de ripple'],['T8','Casi perfecto'],['T9','Tono perfecto']] as $t): ?>
					<div class="flex gap-3 rounded-lg border border-border/50 bg-background px-3 py-2">
						<span class="shrink-0 font-mono text-sm font-bold text-primary"><?php echo esc_html( $t[0] ); ?></span>
						<span class="text-sm text-muted-foreground"><?php echo esc_html( $t[1] ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
				<div class="mt-4 rounded-xl border border-primary/30 bg-primary/5 p-3 text-xs text-muted-foreground">
					<p class="font-semibold text-foreground mb-1">Reportes comunes</p>
					<p><span class="font-mono text-primary">59</span> = fonía excelente · <span class="font-mono text-primary">599</span> = CW perfecto · <span class="font-mono text-primary">57</span> = buena pero no perfecta</p>
				</div>
			</div>
		</div>
		<!-- Calculadora interactiva -->
		<div class="mt-8 rounded-xl border border-border/70 bg-background p-5">
			<h3 class="mb-4 font-display text-base font-semibold">Generador de reporte</h3>
			<div class="flex flex-wrap gap-4 items-end">
				<div><label class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Legibilidad</label>
				<select id="rst-r" aria-label="Legibilidad (R) del reporte RST" class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					<?php for($i=1;$i<=5;$i++) echo "<option value='$i'>R$i</option>"; ?>
				</select></div>
				<div><label class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Intensidad</label>
				<select id="rst-s" aria-label="Intensidad (S) del reporte RST" class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					<?php for($i=1;$i<=9;$i++) echo "<option value='$i'" . ($i==9?" selected":"") . ">S$i</option>"; ?>
				</select></div>
				<div><label class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tono (solo CW)</label>
				<select id="rst-t" aria-label="Tono (T) del reporte RST" class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					<?php for($i=1;$i<=9;$i++) echo "<option value='$i'" . ($i==9?" selected":"") . ">T$i</option>"; ?>
				</select></div>
				<div class="rounded-xl border border-primary/40 bg-primary/5 px-6 py-3 text-center">
					<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground mb-1">Reporte fonía</p>
					<p id="rst-fonia" class="font-mono text-2xl font-bold text-primary">59</p>
				</div>
				<div class="rounded-xl border border-border/60 bg-background px-6 py-3 text-center">
					<p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground mb-1">Reporte CW</p>
					<p id="rst-cw" class="font-mono text-2xl font-bold text-foreground">599</p>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PANEL: Código Q
═══════════════════════════════════════════════════════ -->
<div id="panel-codigosq" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'codigosq' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Referencia operativa</p>
		<h2 class="mb-4 font-display text-2xl font-bold">Código Q — Referencia Completa</h2>
		<button type="button" onclick="window.print()" class="rau-print-btn">🖨️ Imprimir / Guardar PDF</button>
		<div class="mb-4">
			<input aria-label="Buscar por código o significado" type="text" id="cq-search" placeholder="Buscar por código o significado…"
				class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
		</div>
		<div class="overflow-x-auto rounded-xl border border-border/70">
			<table class="min-w-[640px] w-full text-sm" id="cq-table">
				<thead><tr class="border-b border-border/70 bg-muted/30">
					<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground w-16">Código</th>
					<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Pregunta</th>
					<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Respuesta / mensaje</th>
				</tr></thead>
				<tbody id="cq-tbody"></tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PANEL: POTA Colombia
═══════════════════════════════════════════════════════ -->

		<!-- Panel Alfabeto Fonético -->
		<div id="panel-fonetico" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'fonetico' ) ); ?>" class="rau-panel" style="display:none">
		<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">OTAN / ICAO</p>
			<h2 class="mb-2 font-display text-2xl font-bold">Alfabeto Fonético Internacional</h2>
			<p class="mb-6 text-sm text-muted-foreground">El alfabeto fonético oficial OTAN/ICAO adoptado por la UIT. Haz clic en cualquier letra para escuchar su pronunciación.</p>
			<button type="button" onclick="window.print()" class="rau-print-btn">🖨️ Imprimir / Guardar PDF</button>
			<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" id="fonetico-grid">
				<?php
				$alfabeto = [
					['A','Alfa','AL-fah'],
					['B','Bravo','BRAH-voh'],
					['C','Charlie','CHAR-lee'],
					['D','Delta','DELL-tah'],
					['E','Echo','EKK-oh'],
					['F','Foxtrot','FOKS-trot'],
					['G','Golf','GOLF'],
					['H','Hotel','hoh-TELL'],
					['I','India','IN-dee-ah'],
					['J','Juliett','JEW-lee-ett'],
					['K','Kilo','KEY-loh'],
					['L','Lima','LEE-mah'],
					['M','Mike','MIKE'],
					['N','November','no-VEM-ber'],
					['O','Oscar','OSS-car'],
					['P','Papa','pah-PAH'],
					['Q','Quebec','keh-BECK'],
					['R','Romeo','ROW-me-oh'],
					['S','Sierra','see-AIR-rah'],
					['T','Tango','TANG-go'],
					['U','Uniform','YOU-nee-form'],
					['V','Victor','VIK-tor'],
					['W','Whiskey','WISS-key'],
					['X','X-ray','ECKS-ray'],
					['Y','Yankee','YANG-key'],
					['Z','Zulu','ZOO-loo'],
					['0','Zero','ZE-ro'],
					['1','One','WUN'],
					['2','Two','TOO'],
					['3','Three','TREE'],
					['4','Four','FOW-er'],
					['5','Five','FIFE'],
					['6','Six','SIX'],
					['7','Seven','SEV-en'],
					['8','Eight','AIT'],
					['9','Nine','NIN-er'],
				];
				foreach($alfabeto as $a): ?>
				<button
					data-word="<?php echo esc_attr($a[1]); ?>"
					data-letter="<?php echo esc_attr($a[0]); ?>"
					class="fon-card group flex flex-col items-center gap-1 rounded-xl border border-border/70 bg-background p-3 text-center transition-all hover:border-primary/60 hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-ring active:scale-95"
					title="Clic para escuchar <?php echo esc_attr($a[1]); ?>">
					<span class="font-mono text-2xl font-bold text-primary"><?php echo esc_html($a[0]); ?></span>
					<span class="font-display text-sm font-semibold"><?php echo esc_html($a[1]); ?></span>
					<span class="font-mono text-[10px] text-muted-foreground"><?php echo esc_html($a[2]); ?></span>
					<span class="mt-1 text-[10px] text-primary/0 group-hover:text-primary/60 transition-colors">▶ escuchar</span>
				</button>
				<?php endforeach; ?>
			</div>
			<p class="mt-4 text-xs text-muted-foreground">▶ Haz clic en cualquier tarjeta para escuchar la pronunciación usando síntesis de voz del navegador (Web Speech API). La pronunciación varía según el idioma configurado en tu sistema.</p>
		</div>
		</div>
<!-- PANEL: Bandas WARC -->
<div id="panel-warc" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'warc' ) ); ?>" class="rau-panel" style="display:none">
<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
	<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Bandas especiales</p>
	<h2 class="mb-2 font-display text-2xl font-bold">Bandas WARC</h2>
	<p class="mb-6 text-sm text-muted-foreground">Las bandas WARC (30m, 17m y 12m) se asignaron a la radioafición en la Conferencia Administrativa Mundial de Radio de 1979. Su característica más importante: <strong>no se permiten concursos</strong>, lo que las hace más tranquilas y aptas para conversación y modos digitales.</p>
	<div class="grid gap-4 sm:grid-cols-3">
		<?php foreach([
			['30m','10.100 – 10.150 MHz','CW, datos','HF: fija 2000 W · móvil 200 W (Avanzada)','La más estrecha: solo 50 kHz. Excelente para FT8 (10.136) y Winlink. Propagación muy estable.'],
			['17m','18.068 – 18.168 MHz','Todos los modos','HF: fija 2000 W · móvil 200 W (Avanzada)','100 kHz de ancho. FT8 en 18.100. Muy popular en el máximo solar. Mezcla de DX y conversación.'],
			['12m','24.890 – 24.990 MHz','Todos los modos','HF: fija 2000 W · móvil 200 W (Avanzada)','100 kHz de ancho. FT8 en 24.915. Solo abre bien en el máximo solar. Poco congestionada.'],
		] as $b): ?>
		<div class="rounded-2xl border border-primary/30 bg-primary/5 p-5">
			<p class="font-mono text-3xl font-bold text-primary mb-1"><?php echo esc_html($b[0]); ?></p>
			<p class="font-mono text-sm text-foreground mb-3"><?php echo esc_html($b[1]); ?></p>
			<div class="space-y-2 text-xs">
				<div class="flex justify-between"><span class="text-muted-foreground">Modos</span><span class="font-medium"><?php echo esc_html($b[2]); ?></span></div>
				<div class="flex justify-between"><span class="text-muted-foreground">Potencia máx.</span><span class="font-medium"><?php echo esc_html($b[3]); ?></span></div>
			</div>
			<p class="mt-3 text-xs text-muted-foreground leading-relaxed"><?php echo esc_html($b[4]); ?></p>
		</div>
		<?php endforeach; ?>
	</div>
	<div class="mt-6 rounded-xl border border-amber-400/30 bg-amber-400/5 p-4 text-sm text-amber-400/90">
		<strong>Regla de oro de las WARC:</strong> Sin concursos, sin pile-ups agresivos. Son el espacio tranquilo del espectro. Respétalas.
	</div>
</div>
</div>

<!-- PANEL: Frecuencias de Emergencia -->
<div id="panel-emergencia" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'emergencia' ) ); ?>" class="rau-panel" style="display:none">
<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
	<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-accent">Red de emergencia</p>
	<h2 class="mb-2 font-display text-2xl font-bold">Frecuencias de Emergencia</h2>
	<p class="mb-6 text-sm text-muted-foreground">Frecuencias designadas para tráfico de emergencia y socorro. Memorízalas — en una emergencia no habrá tiempo de buscarlas.</p>
	<div class="space-y-6">
		<div>
			<h3 class="mb-3 font-display text-base font-bold text-accent">HK4RAU — Red de Emergencia</h3>
			<div class="grid gap-2 sm:grid-cols-2">
				<?php foreach([
					['146.675 MHz FM','Repetidora Medellín','T100 · −600 kHz','Principal'],
					['146.520 MHz FM','Simplex VHF nacional','Sin tono','Llamada'],
					['147.380 MHz FM','Repetidora Bogotá','T100 · +600 kHz','Enlace'],
					['147.925 MHz FM','Repetidora Bucaramanga','T100 · −600 kHz','Enlace'],
				] as $f): ?>
				<div class="rounded-xl border border-accent/30 bg-accent/5 px-4 py-3">
					<div class="flex items-start justify-between gap-2">
						<div>
							<p class="font-mono text-sm font-bold text-accent"><?php echo esc_html($f[0]); ?></p>
							<p class="text-xs text-muted-foreground"><?php echo esc_html($f[1]); ?></p>
							<p class="font-mono text-[10px] text-muted-foreground mt-0.5"><?php echo esc_html($f[2]); ?></p>
						</div>
						<span class="shrink-0 inline-flex rounded-full px-2 py-0.5 font-mono text-[10px] font-medium ring-1 text-accent bg-accent/10 ring-accent/30"><?php echo esc_html($f[3]); ?></span>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<div>
			<h3 class="mb-3 font-display text-base font-bold">IARU Región 2 — Internacional</h3>
			<div class="overflow-x-auto rounded-xl border border-border/70">
				<table class="min-w-[480px] w-full text-sm">
					<thead><tr class="border-b border-border/70 bg-muted/30">
						<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Frecuencia</th>
						<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Banda</th>
						<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Modo</th>
						<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Uso</th>
					</tr></thead>
					<tbody>
					<?php foreach([
						['3.985 MHz','80m','LSB','Emergencia y socorro IARU R2'],
						['7.060 MHz','40m','LSB','Emergencia IARU R2'],
						['14.300 MHz','20m','USB','Red Mundial de Socorro IARU'],
						['14.325 MHz','20m','USB','Red de Huracanes del Caribe'],
						['21.360 MHz','15m','USB','Emergencia IARU R2'],
						['146.520 MHz','2m','FM','Llamada simplex VHF nacional'],
						['156.800 MHz','Mar','FM','Canal 16 VHF marítimo (internacional)'],
					] as $r): ?>
					<tr class="border-b border-border/50 last:border-0 hover:bg-muted/20">
						<td class="px-3 py-2 font-mono text-sm font-bold text-primary"><?php echo esc_html($r[0]); ?></td>
						<td class="px-3 py-2 font-mono text-xs text-muted-foreground"><?php echo esc_html($r[1]); ?></td>
						<td class="px-3 py-2 font-mono text-xs"><?php echo esc_html($r[2]); ?></td>
						<td class="px-3 py-2 text-xs text-muted-foreground"><?php echo esc_html($r[3]); ?></td>
					</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
</div>

<!-- PANEL: Modos Digitales -->
<div id="panel-modos" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'modos' ) ); ?>" class="rau-panel" style="display:none">
<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
	<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Comunicaciones digitales</p>
	<h2 class="mb-4 font-display text-2xl font-bold">Guía de Modos Digitales</h2>
	<div class="space-y-3">
		<?php
		$modos = [
			['FT8','14.074 / 7.074 / 21.074 MHz','WSJT-X','~15s','Excelente para DX con señales muy débiles (hasta −24 dB bajo el ruido). Ideal para trabajar entidades nuevas con antenas pequeñas. No sirve para conversación.','🌍'],
			['FT4','14.080 / 7.0475 / 21.140 MHz','WSJT-X','~7.5s','Variante rápida de FT8 para concursos. Doble velocidad de QSOs. Mismo software.','⚡'],
			['JS8Call','14.078 / 7.078 MHz','JS8Call','Variable','Derivado de FT8 pero permite conversación real en texto. Muy popular para redes digitales de emergencia.','💬'],
			['Winlink','Varía','Winlink Express','Variable','Correo electrónico via radio HF/VHF. Muy usado en emergencias. Funciona sin internet.','📧'],
			['APRS','144.390 MHz (Américas)','Dire Wolf / APRS.fi','Continuo','Reporte automático de posición GPS y mensajes cortos. Estándar en actividades POTA y SOTA.','📍'],
			['PSK31','14.070 / 7.070 MHz','Fldigi','Continuo','Modo digital de teclado a teclado. Ancho de banda muy estrecho (31 Hz). Bueno para conversación con señales débiles.','⌨️'],
			['RTTY','14.080 / 7.080 MHz','Fldigi','Continuo','El modo digital más antiguo. Muy usado en concursos. Robusto y ampliamente soportado.','📡'],
			['SSTV','14.230 / 7.171 MHz','MMSSTV / Robot36','~30-120s','Transmisión de imágenes estáticas via radio. Popular en actividades especiales y satélites.','🖼️'],
		];
		foreach($modos as $m): ?>
		<div class="rounded-xl border border-border/70 bg-background p-4">
			<div class="flex flex-wrap items-start justify-between gap-3">
				<div class="flex items-center gap-3">
					<span class="text-2xl"><?php echo esc_html( $m[5] ); ?></span>
					<div>
						<p class="font-mono text-base font-bold text-primary"><?php echo esc_html($m[0]); ?></p>
						<p class="font-mono text-xs text-muted-foreground"><?php echo esc_html($m[1]); ?></p>
					</div>
				</div>
				<div class="flex gap-2 flex-wrap">
					<span class="inline-flex rounded-full px-2.5 py-1 font-mono text-[11px] font-medium ring-1 text-muted-foreground bg-background ring-border/60"><?php echo esc_html($m[2]); ?></span>
					<span class="inline-flex rounded-full px-2.5 py-1 font-mono text-[11px] font-medium ring-1 text-muted-foreground bg-background ring-border/60"><?php echo esc_html($m[3]); ?></span>
				</div>
			</div>
			<p class="mt-2 text-sm text-muted-foreground"><?php echo esc_html($m[4]); ?></p>
		</div>
		<?php endforeach; ?>
	</div>
</div>
</div>

<!-- PANEL: QSL Digital -->
<div id="panel-qsl" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'qsl' ) ); ?>" class="rau-panel" style="display:none">
<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
	<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Confirmación de contacto</p>
	<h2 class="mb-2 font-display text-2xl font-bold">Generador de QSL Digital</h2>
	<p class="mb-6 text-sm text-muted-foreground">Genera una tarjeta QSL digital para descargar o compartir. Completa los datos del contacto y haz clic en "Generar QSL".</p>
	<div class="grid gap-6 lg:grid-cols-2">
		<div class="space-y-4">
			<div class="grid grid-cols-2 gap-3">
				<div><label for="qsl-fondo" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Fondo</label>
				<select id="qsl-fondo" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					<option value="clasico">Clásico</option>
					<option value="cuadricula">Cuadrícula técnica</option>
					<option value="espacial">Espacial</option>
					<option value="colombia">Colombia</option>
					<option value="emblematico">Emblemático</option>
					<option value="retro">Retro colombiano</option>
					<option value="minimal">Minimalista</option>
				</select></div>
				<div><label for="qsl-forma" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Forma</label>
				<select id="qsl-forma" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					<option value="rect">Rectangular</option>
					<option value="redondeada">Bordes redondeados</option>
					<option value="sello">Sello postal</option>
				</select></div>
			</div>
			<div class="grid grid-cols-2 gap-3">
				<div><label for="qsl-de" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">De (tu indicativo)</label>
				<input type="text" id="qsl-de" maxlength="12" placeholder="HK4SSI" style="text-transform:uppercase"
					class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring"></div>
				<div><label for="qsl-to" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Para (indicativo DX)</label>
				<input type="text" id="qsl-to" maxlength="12" placeholder="W1AW" style="text-transform:uppercase"
					class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring"></div>
			</div>
			<div class="grid grid-cols-2 gap-3">
				<div><label for="qsl-fecha" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Fecha (UTC)</label>
				<input type="date" id="qsl-fecha" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
				<div><label for="qsl-hora" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Hora UTC</label>
				<input type="time" id="qsl-hora" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
			</div>
			<button type="button" id="qsl-now" class="-mt-2 inline-flex items-center gap-1.5 font-mono text-xs text-primary hover:underline">
				<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
				Usar fecha y hora actuales (UTC)
			</button>
			<div class="grid grid-cols-3 gap-3">
				<div><label for="qsl-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia</label>
				<input type="text" id="qsl-freq" maxlength="12" placeholder="14.250 MHz"
					class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
				<div><label for="qsl-modo" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Modo</label>
				<select id="qsl-modo" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
					<option>SSB</option><option>CW</option><option>FM</option><option>FT8</option><option>FT4</option><option>PSK31</option><option>RTTY</option><option>AM</option><option>SSTV</option>
				</select></div>
				<div><label for="qsl-rst" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">RST</label>
				<input type="text" id="qsl-rst" maxlength="3" placeholder="59"
					class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
			</div>
			<div><label for="qsl-nombre" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tu nombre</label>
			<input type="text" id="qsl-nombre" maxlength="40" placeholder="Alex"
				class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
			<div><label for="qsl-qthl" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tu QTH</label>
			<input type="text" id="qsl-qthl" maxlength="40" placeholder="Medellín, Colombia"
				class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
			<button id="qsl-gen" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">Generar QSL</button>
		</div>
		<div>
			<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Vista previa</p>
			<canvas id="qsl-canvas" width="1200" height="800" class="w-full rounded-xl border border-border/70" style="background:#0d1321;"></canvas>
			<div class="hidden mt-3 grid grid-cols-2 gap-2" id="qsl-actions">
				<button id="qsl-download" class="inline-flex items-center justify-center gap-2 rounded-md border border-border/70 bg-card px-4 py-2.5 text-sm font-medium transition-colors hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">
					⬇ Descargar PNG
				</button>
				<button id="qsl-share" class="hidden inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">
					↗ Compartir
				</button>
			</div>
		</div>
	</div>
</div>
</div>

<!-- CERTIFICADO DE PARTICIPACIÓN -->
<div id="panel-certificado" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'certificado' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Reconocimiento de actividades</p>
		<h2 class="mb-2 font-display text-2xl font-bold">Certificado de Participación</h2>
		<p class="mb-6 text-sm text-muted-foreground">Genera un certificado descargable por haber participado en una actividad de RAU — simulacros, mallas especiales, Field Day, etc.</p>

		<div class="grid gap-6 lg:grid-cols-2">
			<div class="space-y-4">
				<div>
					<label for="cert-nombre" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Nombre del participante</label>
					<input type="text" id="cert-nombre" placeholder="Tu nombre completo" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<div>
					<label for="cert-indicativo" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Indicativo (opcional)</label>
					<input type="text" id="cert-indicativo" placeholder="HK4ABC" style="text-transform:uppercase" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<div>
					<label for="cert-actividad" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Actividad</label>
					<input type="text" id="cert-actividad" placeholder="Ej. Simulacro Nacional de Emergencia" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<div>
					<label for="cert-fecha" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Fecha</label>
					<input type="date" id="cert-fecha" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<button id="cert-download" class="hidden inline-flex w-full items-center justify-center gap-2 rounded-md border border-border/70 bg-card px-5 py-2.5 text-sm font-medium transition-colors hover:border-primary/50">
					⬇ Descargar certificado (PNG)
				</button>
			</div>

			<div>
				<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Vista previa</p>
				<canvas id="cert-canvas" width="1200" height="848" class="w-full rounded-xl border border-border/70" style="background:#0d1321;"></canvas>
			</div>
		</div>
	</div>
</div>

<!-- PANEL: Log ADIF -->
<div id="panel-log" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'log' ) ); ?>" class="rau-panel" style="display:none">
<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
	<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Registro de QSOs</p>
	<h2 class="mb-2 font-display text-2xl font-bold">Log de Sesión ADIF</h2>
	<p class="mb-6 text-sm text-muted-foreground">Anota tus QSOs durante una sesión y expórtalos en formato ADIF estándar para importar en WSJT-X, Log4OM, QRZ, etc. Se guarda en este navegador — si cierras la pestaña, sigue ahí. También puedes importar un archivo ADIF que hayas exportado antes para continuar donde ibas.</p>
	<div class="mb-4">
		<button id="log-import-btn" class="inline-flex items-center gap-2 rounded-md border border-border/70 bg-card px-4 py-2 text-xs font-medium hover:border-primary/50">⬆ Importar archivo ADIF</button>
		<input type="file" id="log-import-file" aria-label="Archivo ADIF a importar" accept=".adi,.adif,.txt" class="hidden">
	</div>
	<div class="grid gap-4 lg:grid-cols-3 mb-4">
		<div><label for="log-call" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Indicativo DX</label>
		<input type="text" id="log-call" maxlength="12" placeholder="W1AW" style="text-transform:uppercase"
			class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring"></div>
		<div><label for="log-freq" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia (MHz)</label>
		<input type="number" id="log-freq" step="0.001" placeholder="14.250"
			class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
		<div><label for="log-modo2" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Modo</label>
		<select id="log-modo2" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
			<option>SSB</option><option>CW</option><option>FM</option><option>FT8</option><option>FT4</option><option>PSK31</option><option>RTTY</option><option>AM</option>
		</select></div>
		<div><label for="log-rst-s" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">RST enviado</label>
		<input type="text" id="log-rst-s" maxlength="3" placeholder="59"
			class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
		<div><label for="log-rst-r" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">RST recibido</label>
		<input type="text" id="log-rst-r" maxlength="3" placeholder="59"
			class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
		<div><label for="log-nombre2" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Nombre / QTH</label>
		<input type="text" id="log-nombre2" maxlength="40" placeholder="John / Miami"
			class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"></div>
	</div>
	<div class="flex gap-3 mb-6">
		<button id="log-add" class="inline-flex flex-1 items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">+ Agregar QSO</button>
		<button id="log-export" class="inline-flex items-center justify-center gap-2 rounded-md border border-border/70 bg-card px-5 py-2.5 text-sm font-medium transition-colors hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">⬇ Exportar ADIF</button>
		<button id="log-clear" class="inline-flex items-center justify-center gap-2 rounded-md border border-rose-400/30 bg-rose-400/5 px-4 py-2.5 text-sm font-medium text-rose-400 transition-colors hover:bg-rose-400/10 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">🗑 Limpiar</button>
	</div>
	<div class="overflow-x-auto rounded-xl border border-border/70">
		<table class="min-w-[560px] w-full text-sm">
			<thead><tr class="border-b border-border/70 bg-muted/30">
				<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Hora UTC</th>
				<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Indicativo</th>
				<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Freq.</th>
				<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Modo</th>
				<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">RST S/R</th>
				<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground">Nombre/QTH</th>
				<th class="px-3 py-2 text-left font-mono text-xs text-muted-foreground"></th>
			</tr></thead>
			<tbody id="log-tbody"><tr><td colspan="7" class="px-3 py-6 text-center text-sm text-muted-foreground">No hay QSOs registrados todavía.</td></tr></tbody>
		</table>
	</div>
	<p class="mt-3 text-xs text-muted-foreground">Los QSOs se guardan en memoria durante esta sesión. Exporta el ADIF antes de cerrar el navegador.</p>
</div>
</div>

<div id="panel-pota" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'pota' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Parks on the Air</p>
		<h2 class="mb-2 font-display text-2xl font-bold">Activaciones POTA en Colombia</h2>
		<p class="mb-6 text-sm text-muted-foreground">Activadores HK activos en este momento según api.pota.app · Se actualiza automáticamente.</p>
		<div id="pota-loading" class="flex items-center gap-3 text-sm text-muted-foreground">
			<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-dashoffset="12"/></svg>
			Consultando spots activos…
		</div>
		<div id="pota-empty" class="hidden rounded-xl border border-dashed border-border/70 py-12 text-center text-sm text-muted-foreground">
			<p class="text-2xl mb-2">🌿</p>
			<p>No hay activaciones HK activas en este momento.</p>
			<p class="mt-1 text-xs">Los spots duran ~15 minutos. Vuelve a revisar más tarde.</p>
		</div>
		<div id="pota-list" class="hidden space-y-3"></div>
		<div class="mt-6 rounded-xl border border-border/50 bg-background p-4 text-xs text-muted-foreground">
			<p class="font-semibold text-foreground mb-1">¿Qué es POTA?</p>
			<p>Parks on the Air es un programa de activaciones desde parques nacionales y naturales. En Colombia los parques tienen el prefijo <span class="font-mono text-primary">HK-</span>. Para participar se necesita hacer al menos 10 QSOs desde el parque.</p>
			<a href="https://pota.app" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-primary hover:underline">pota.app →</a>
		</div>
	</div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PANEL: DX Spots
═══════════════════════════════════════════════════════ -->
<div id="panel-dx" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'dx' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">DX Cluster</p>
		<h2 class="mb-2 font-display text-2xl font-bold">DX Spots en Vivo</h2>
		<p class="mb-5 text-sm text-muted-foreground">Spots en tiempo real del cluster DXheat.com — actualización automática cada 60 segundos.</p>

		<!-- Widget oficial DXheat (iframe HTTPS, sin restricciones CORS) -->
		<div class="overflow-hidden rounded-xl border border-border/70" style="height:520px;">
			<iframe
				data-src="https://dxheat.com/dxc/?spotted_filter=0&band_filter=ALL&limit=50&theme=dark&hide_header=1"
				width="100%"
				height="520"
				frameborder="0"
				scrolling="auto"
				title="DX Spots en vivo — DXheat"
				loading="lazy"
				style="display:block;width:100%;height:520px;border:none;background:#0d1321;">
			</iframe>
		</div>

		<div class="mt-4 flex flex-wrap items-center justify-between gap-3">
			<p class="text-xs text-muted-foreground">Fuente: <a href="https://dxheat.com" target="_blank" rel="noopener" class="text-primary hover:underline">DXheat.com</a> · Los spots son aportados por la comunidad de radioaficionados.</p>
			<div class="flex gap-2">
				<a href="https://dxheat.com" target="_blank" rel="noopener"
					class="inline-flex items-center gap-1.5 rounded-md border border-border/70 px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:border-primary/50 hover:text-primary">
					Ver en DXheat →
				</a>
				<a href="https://www.dxsummit.fi" target="_blank" rel="noopener"
					class="inline-flex items-center gap-1.5 rounded-md border border-border/70 px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:border-primary/50 hover:text-primary">
					DX Summit →
				</a>
			</div>
		</div>
	</div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PANEL: Examen RABCA
═══════════════════════════════════════════════════════ -->
<div id="panel-examen" role="region" aria-label="<?php echo esc_attr( rau_tool_label( 'examen' ) ); ?>" class="rau-panel" style="display:none">
	<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
		<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Licencia MinTIC</p>
		<h2 class="mb-2 font-display text-2xl font-bold">Simulador de Examen RABCA</h2>
		<p class="mb-6 text-sm text-muted-foreground">Practica con preguntas tipo examen del Registro de Aficionados de Colombia. Selecciona una respuesta y avanza. ¿Vas a presentar el examen real? Mira el <a href="<?php echo esc_url( home_url( '/como-sacar-la-licencia/' ) ); ?>" class="text-primary hover:underline">paso a paso de la licencia</a>: el examen del MinTIC incluye también preguntas de cultura general.</p>
		<div id="exam-start">
				<div class="grid gap-3 sm:grid-cols-3 mb-6">
				<?php foreach([['Segunda Categoría','Novicio · 10 preguntas','A'],['Primera Categoría','Experto · 15 preguntas','B'],['Categoría Avanzada','Avanzado · 20 preguntas','C']] as $cat): ?>
				<button data-cat="<?php echo esc_attr($cat[2]); ?>" class="exam-cat-btn rounded-xl border border-border/70 bg-card p-5 text-left transition-colors hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-ring">
					<p class="font-display text-lg font-bold"><?php echo esc_html($cat[0]); ?></p>
					<p class="mt-1 text-sm text-muted-foreground"><?php echo esc_html($cat[1]); ?></p>
					<p class="mt-2 font-mono text-[11px] text-muted-foreground" data-exam-stats="<?php echo esc_attr($cat[2]); ?>">Sin intentos todavía</p>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<div id="exam-container" class="hidden">
			<div class="mb-4 flex items-center justify-between">
				<p class="font-mono text-sm text-muted-foreground">Pregunta <span id="exam-num">1</span> de <span id="exam-total">10</span></p>
				<div class="flex items-center gap-3">
					<span class="font-mono text-xs text-emerald-400">✓ <span id="exam-correct">0</span></span>
					<span class="font-mono text-xs text-rose-400">✗ <span id="exam-wrong">0</span></span>
					<button id="exam-restart" class="rounded-md border border-border/70 px-3 py-1 text-xs text-muted-foreground hover:text-foreground">Reiniciar</button>
				</div>
			</div>
			<div class="mb-2 h-1.5 w-full rounded-full bg-muted overflow-hidden">
				<div id="exam-bar" class="h-full rounded-full bg-primary transition-all duration-300" style="width:0%"></div>
			</div>
			<div id="exam-question" class="mt-6 mb-5 rounded-xl border border-border/70 bg-background p-5">
				<p id="exam-q-text" class="font-display text-base font-semibold leading-snug"></p>
			</div>
			<div id="exam-options" class="space-y-2"></div>
			<div id="exam-explain" class="hidden mt-4 rounded-xl border border-border/60 bg-background p-4 text-sm text-muted-foreground"></div>
			<button id="exam-next" class="hidden mt-4 inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ring-offset-background">Siguiente pregunta →</button>
		</div>
		<div id="exam-result" class="hidden text-center py-8">
			<p class="font-display text-4xl font-bold" id="exam-score-text"></p>
			<p class="mt-2 text-muted-foreground" id="exam-score-sub"></p>
			<div id="exam-revision" class="mt-6 max-w-xl mx-auto text-left space-y-2"></div>
			<button id="exam-retry" class="mt-6 inline-flex items-center justify-center gap-2 rounded-md bg-primary px-6 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Intentar de nuevo</button>
		</div>
	</div>
</div>



	</div>
</section>

<script>
/* Vigilante de arranque.
   Si pasados unos segundos la marca RAU_JS_COMPLETO no existe, el JavaScript de Herramientas no
   llegó a ejecutarse entero y todos los paneles seguirán diciendo "Cargando…" indefinidamente.
   Ese mensaje es mentira en ese caso, y desde fuera no se distingue de una red lenta: hizo falta
   pedir capturas y abrir la consola dos veces para averiguarlo. Esto lo dice solo. */
(function(){
	setTimeout(function(){
		if(window.RAU_JS_COMPLETO) return;
		// Los paneles que siguen prometiendo carga pasan a decir la verdad
		document.querySelectorAll('.rau-panel, .consola-panel').forEach(function(p){
			p.querySelectorAll('*').forEach(function(el){
				if(el.children.length===0 && /^(Cargando|Conectando|Calculando)/.test((el.textContent||'').trim())){
					el.textContent='No se pudo cargar';
					el.style.opacity='0.6';
				}
			});
		});
		var aviso=document.createElement('div');
		aviso.style.cssText='position:fixed;bottom:12px;left:12px;z-index:99998;max-width:min(520px,92vw);'
			+'background:#2b0d12;color:#ffb4b4;border:1px solid #7f1d1d;border-radius:10px;padding:12px 14px;'
			+'font:12px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace;box-shadow:0 8px 30px rgba(0,0,0,.5)';
		aviso.textContent=<?php echo current_user_can( 'manage_options' )
			? "'El JavaScript de Herramientas no llegó a ejecutarse: ninguna herramienta va a funcionar. Revisa la consola del navegador y wp-config.php. (Solo lo ves tú, como administrador.)'"
			: "'Las herramientas no cargaron bien. Prueba a recargar la página.'"; ?>;
		document.body.appendChild(aviso);
	}, 8000);
})();
<?php if ( current_user_can( 'manage_options' ) ) : ?>
/* Captor de errores — solo visible para administradores.
   Un error de JavaScript no atendido detiene la ejecución del resto del script, y el síntoma que
   se ve en pantalla es que los módulos definidos más abajo nunca se inicializan: sus paneles se
   quedan con el texto "Cargando…" del HTML para siempre. Desde fuera es indistinguible de un
   problema de red, y sin abrir las herramientas de desarrollo no hay forma de saber cuál es.
   Esto muestra el error en la propia página, con archivo y línea, igual que el modo diagnóstico
   del proxy de DX Spots. No se carga para los visitantes. */
(function(){
	var caja=null;
	function mostrar(titulo, detalle){
		if(!caja){
			caja=document.createElement('div');
			caja.style.cssText='position:fixed;bottom:12px;left:12px;z-index:99999;max-width:min(560px,92vw);'
				+'max-height:40vh;overflow:auto;background:#2b0d12;color:#ffb4b4;border:1px solid #7f1d1d;'
				+'border-radius:10px;padding:12px 14px;font:12px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace;'
				+'box-shadow:0 8px 30px rgba(0,0,0,.5)';
			var cerrar=document.createElement('button');
			cerrar.textContent='✕';
			cerrar.style.cssText='position:absolute;top:6px;right:10px;background:none;border:none;color:#ffb4b4;cursor:pointer;font-size:14px';
			cerrar.onclick=function(){ caja.remove(); caja=null; };
			caja.appendChild(cerrar);
			var t=document.createElement('div');
			t.textContent='Errores de JavaScript en esta página (solo lo ves tú, como administrador)';
			t.style.cssText='font-weight:bold;margin-bottom:8px;padding-right:20px;color:#fca5a5';
			caja.appendChild(t);
			document.body.appendChild(caja);
		}
		var linea=document.createElement('div');
		linea.style.cssText='margin-top:6px;padding-top:6px;border-top:1px solid rgba(255,255,255,.08);white-space:pre-wrap;word-break:break-word';
		linea.textContent=titulo + (detalle ? '\n' + detalle : '');
		caja.appendChild(linea);
	}
	// La fase de CAPTURA (true) es necesaria: los fallos al cargar un recurso —un <script> que
	// devuelve 404, por ejemplo— no burbujean hasta window, así que sin esto pasaban en silencio.
	// Fue justo lo que ocurrió cuando /rau-herramientas.js no cargó: la página se veía entera,
	// nada funcionaba y no aparecía ningún aviso.
	window.addEventListener('error', function(e){
		if(e.target && e.target !== window && (e.target.src || e.target.href)){
			mostrar('No se pudo cargar un recurso', e.target.src || e.target.href);
			return;
		}
		mostrar(e.message || 'Error desconocido',
			(e.filename || '') + (e.lineno ? ':' + e.lineno + ':' + e.colno : ''));
	}, true);
	window.addEventListener('unhandledrejection', function(e){
		var r = e.reason;
		mostrar('Promesa rechazada sin atender: ' + (r && r.message ? r.message : String(r)),
			r && r.stack ? String(r.stack).split('\n').slice(0,3).join('\n') : '');
	});
})();
<?php endif; ?>
</script>
<?php
// El JavaScript va en línea a propósito. Se intentó servirlo como archivo aparte para que el
// navegador lo cacheara entre las 54 herramientas, y falló dos veces en este alojamiento: primero
// con regla de reescritura (404, página sin JavaScript) y después por parámetro de consulta
// (HTTP 500). El ahorro no compensa el riesgo de dejar la página inutilizable, así que se
// mantiene el modo que funciona con certeza en cualquier servidor.
?>
<script>
window.RAU_INIT = { tool: <?php echo wp_json_encode( sanitize_title( (string) get_query_var( 'rau_tool' ) ) ); ?> };
<?php include get_template_directory() . '/inc/herramientas-scripts.php'; ?>
</script>
<?php get_footer(); ?>
