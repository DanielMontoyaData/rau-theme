<?php
/**
 * Template Name: Check-in de Malla
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$hoy    = current_time( 'Y-m-d' );
$estado = isset( $_GET['checkin'] ) ? sanitize_key( $_GET['checkin'] ) : '';

$mensajes = array(
	'ok'         => array( 'ok',   '¡Check-in registrado! Ya apareces en la lista. 73 de RAU.' ),
	'duplicado'  => array( 'warn', 'Ese indicativo ya hizo check-in hoy. No hace falta repetirlo.' ),
	'indicativo' => array( 'err',  'El indicativo no parece válido. Revisa el formato (ej. HK4ABC o HK4ABC/M).' ),
	'nombre'     => array( 'err',  'Falta tu nombre.' ),
	'despacio'   => array( 'warn', 'Espera un par de minutos antes de enviar otro check-in.' ),
	'error'      => array( 'err',  'No se pudo registrar. Recarga la página e intenta de nuevo.' ),
);

$checkins_hoy = new WP_Query( array(
	'post_type'      => 'checkin',
	'posts_per_page' => 200,
	'orderby'        => 'date',
	'order'          => 'ASC',
	'post_status'    => 'publish',
	'meta_query'     => array( array( 'key' => 'fecha', 'value' => $hoy ) ),
) );
$total_hoy = (int) $checkins_hoy->found_posts;
?>

<div class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">

	<!-- Hero header -->
	<div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
		<div>
			<p class="mb-2 font-mono text-[11px] uppercase tracking-[0.25em] text-primary">Radioafición en Acción</p>
			<h1 class="font-display text-3xl font-bold sm:text-4xl">Check-in de Malla</h1>
			<p class="mt-2 max-w-xl text-muted-foreground">Malla semanal · Martes 7:00 p.m. · <span class="font-mono text-primary font-semibold"><?php echo esc_html( rau_get_repetidor_principal()['frequency'] ); ?> MHz</span> y canales digitales. Tu check-in aparece de inmediato.</p>
		</div>
		<!-- Contador -->
		<div class="flex shrink-0 flex-col items-center justify-center rounded-2xl border border-primary/30 bg-primary/5 px-8 py-4 text-center">
			<p class="font-mono text-4xl font-bold text-primary"><?php echo esc_html( $total_hoy ); ?></p>
			<p class="mt-1 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Reportados hoy</p>
		</div>
	</div>

	<!-- LIVE NET MONITOR -->
	<div id="net-monitor" class="mb-8 rounded-2xl border-2 p-5 border-border/70 bg-card">
		<div class="flex items-center justify-between">
			<div>
				<p class="mb-1 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">Live Net Monitor</p>
				<p id="net-estado" class="font-display text-lg font-bold">Calculando…</p>
			</div>
			<span id="net-badge" class="hidden items-center gap-1.5 rounded-full bg-rose-500 px-3 py-1 font-mono text-[11px] font-bold text-white animate-pulse">
				<span class="h-1.5 w-1.5 rounded-full bg-white"></span> EN VIVO
			</span>
		</div>
	</div>

	<!-- MAPA DE ACTIVIDAD DE HOY -->
	<div id="mapa-hoy-wrap" class="mb-8 hidden rounded-2xl border border-border/70 bg-card p-5">
		<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">🗺️ Quién estuvo hoy (por grid square)</p>
		<div id="mapa-hoy" class="h-64 w-full overflow-hidden rounded-xl"></div>
		<p class="mt-2 text-xs text-muted-foreground">Ubicación aproximada por grid square (~4-100 km, no exacta) — solo aparecen quienes registraron su grid al hacer check-in.</p>
	</div>

	<!-- LAST HEARD -->
	<div class="mb-8 rounded-2xl border border-border/70 bg-card p-5">
		<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">📡 Last Heard — actividad reciente</p>
		<div id="last-heard-list" class="space-y-1.5">
			<p class="text-sm text-muted-foreground">Cargando…</p>
		</div>
	</div>

	<?php $rachas = rau_checkin_rachas( 8 ); if ( $rachas ) : ?>
	<div class="mb-8 rounded-2xl border border-border/70 bg-card p-5">
		<p class="mb-3 font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">🔥 Racha de Participación (semanas seguidas)</p>
		<div class="flex flex-wrap gap-2">
			<?php foreach ( $rachas as $indicativo => $racha ) : ?>
			<span class="inline-flex items-center gap-1.5 rounded-full border border-border/70 bg-background px-3 py-1.5 font-mono text-sm">
				<strong class="text-primary"><?php echo esc_html( $indicativo ); ?></strong>
				<span class="text-muted-foreground">🔥 <?php echo esc_html( $racha ); ?></span>
			</span>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

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
				<!-- Header formulario -->
				<div class="border-b border-border/70 bg-muted/20 px-6 py-4">
					<h2 class="font-display text-lg font-bold">Reportarse</h2>
					<p class="mt-0.5 text-xs text-muted-foreground">Solo indicativo y nombre son obligatorios</p>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="p-6 space-y-5">
					<input type="hidden" name="action" value="rau_checkin">
					<?php wp_nonce_field( 'rau_checkin', 'rau_checkin_nonce' ); ?>
					<div class="hidden" aria-hidden="true">
						<label>Sitio web <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label>
					</div>

					<!-- Indicativo -->
					<div>
						<label for="ci-indicativo" class="mb-1.5 flex items-center gap-1.5 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
							Indicativo
							<span class="rounded-full bg-primary/20 px-1.5 py-0.5 text-[10px] font-bold text-primary">REQ</span>
						</label>
						<div class="relative">
							<input type="text" id="ci-indicativo" name="indicativo" required maxlength="13" placeholder="HK4ABC"
								class="w-full rounded-xl border border-input bg-background px-4 py-3 font-mono text-sm uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors"
								style="text-transform:uppercase" pattern="[A-Za-z0-9]{3,8}(/[A-Za-z0-9]{1,4})?">
							<div class="absolute right-3 top-1/2 -translate-y-1/2">
								<svg class="h-4 w-4 text-muted-foreground/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h2M4 12a8 8 0 0 1 8-8M12 4V2"/><circle cx="12" cy="12" r="3"/></svg>
							</div>
						</div>
					</div>

					<!-- Nombre -->
					<div>
						<label for="ci-nombre" class="mb-1.5 flex items-center gap-1.5 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
							Nombre
							<span class="rounded-full bg-primary/20 px-1.5 py-0.5 text-[10px] font-bold text-primary">REQ</span>
						</label>
						<input type="text" id="ci-nombre" name="nombre" required maxlength="60" placeholder="Tu nombre completo"
							class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
					</div>

					<div class="grid grid-cols-2 gap-3">
						<!-- Ciudad -->
						<div>
							<label for="ci-ciudad" class="mb-1.5 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Ciudad</label>
							<input type="text" id="ci-ciudad" name="ciudad" maxlength="60" placeholder="Medellín"
								class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
						</div>
						<!-- Grid Square (opcional, para el mapa de actividad) -->
						<div>
							<label for="ci-grid" class="mb-1.5 flex items-center justify-between font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
								<span>Grid (opcional)</span>
							</label>
							<div class="flex gap-1.5">
								<input type="text" id="ci-grid" name="grid" maxlength="8" placeholder="FJ26" style="text-transform:uppercase"
									class="w-full rounded-xl border border-input bg-background px-4 py-3 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
								<button type="button" id="ci-grid-gps" aria-label="Obtener mi grid square desde el GPS del dispositivo" class="shrink-0 rounded-xl border border-input bg-background px-3 text-lg"><span aria-hidden="true">📍</span></button>
							</div>
						</div>
					</div>

					<div class="grid grid-cols-2 gap-3">
						<div>
							<label for="ci-comentario" class="mb-1.5 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Comentario</label>
							<input type="text" id="ci-comentario" name="comentario" maxlength="200" placeholder="Señal, equipo…"
								class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
						</div>
					</div>

					<button type="submit"
						class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3.5 text-sm font-bold text-primary-foreground transition-all hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/20 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 ring-offset-background active:scale-[0.98]">
						<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
						Hacer check-in
					</button>

					<p class="text-center text-xs text-muted-foreground">Al reportarte confirmas tu asistencia a la malla de hoy</p>
				</form>
			</div>
		</div>

		<!-- ── LISTA (3/5) ── -->
		<div class="lg:col-span-3">
			<div class="rounded-2xl border border-border/70 bg-card overflow-hidden">
				<!-- Header lista -->
				<div class="flex items-center justify-between border-b border-border/70 bg-muted/20 px-6 py-4">
					<div>
						<h2 class="font-display text-lg font-bold">Reportados hoy</h2>
						<p class="mt-0.5 text-xs text-muted-foreground"><?php echo esc_html( current_time( 'd \d\e F, Y' ) ); ?></p>
					</div>
					<?php if ( $total_hoy > 0 ) : ?>
					<span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 font-mono text-xs font-semibold text-emerald-400">
						<span class="relative flex h-1.5 w-1.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span></span>
						En vivo
					</span>
					<?php endif; ?>
				</div>

				<?php if ( $checkins_hoy->have_posts() ) : ?>

				<!-- Tabla header -->
				<div class="hidden border-b border-border/50 bg-muted/10 px-6 py-2.5 sm:grid sm:grid-cols-12 sm:gap-3">
					<div class="col-span-1 font-mono text-[10px] uppercase tracking-wider text-muted-foreground">#</div>
					<div class="col-span-2 font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Hora</div>
					<div class="col-span-3 font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Indicativo</div>
					<div class="col-span-3 font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Nombre</div>
					<div class="col-span-3 font-mono text-[10px] uppercase tracking-wider text-muted-foreground">Ciudad</div>
				</div>

				<?php $num = 1; while ( $checkins_hoy->have_posts() ) : $checkins_hoy->the_post();
					$m = get_post_meta( get_the_ID() );
					$indicativo  = $m['indicativo'][0] ?? '';
					$nombre      = $m['nombre'][0] ?? '';
					$ciudad      = $m['ciudad'][0] ?? '';
					$hora        = $m['hora'][0] ?? '';
					$comentario  = $m['comentario'][0] ?? '';
					// Color avatar basado en inicial del indicativo
					$colores = ['hsl(193,85%,27%)','hsl(160,60%,30%)','hsl(262,60%,40%)','hsl(350,75%,45%)','hsl(30,80%,40%)'];
					$color_idx = ord( $indicativo[0] ?? 'A' ) % count($colores);
					$color_bg  = $colores[$color_idx];
					$inicial   = strtoupper( substr( $indicativo, 0, 2 ) );
				?>
				<div class="group flex flex-col border-b border-border/40 px-6 py-4 last:border-0 transition-colors hover:bg-muted/10 sm:grid sm:grid-cols-12 sm:items-center sm:gap-3">
					<!-- # -->
					<div class="hidden sm:block col-span-1 font-mono text-xs text-muted-foreground"><?php echo esc_html( $num ); ?></div>
					<!-- Hora -->
					<div class="col-span-2 font-mono text-xs text-muted-foreground"><?php echo esc_html( $hora ); ?></div>
					<!-- Indicativo con avatar -->
					<div class="col-span-3 flex items-center gap-2">
						<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg font-mono text-[10px] font-bold text-white" style="background:<?php echo esc_attr($color_bg); ?>">
							<?php echo esc_html( $inicial ); ?>
						</div>
						<span class="font-mono text-sm font-bold text-primary"><?php echo esc_html( $indicativo ); ?></span>
					</div>
					<!-- Nombre -->
					<div class="col-span-3 text-sm font-medium text-foreground truncate"><?php echo esc_html( $nombre ); ?></div>
					<!-- Ciudad -->
					<div class="col-span-3 text-sm text-muted-foreground">
						<?php if ( $ciudad ) : ?>
						<span class="flex items-center gap-1">
							<svg class="h-3 w-3 shrink-0 text-muted-foreground/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
							<?php echo esc_html( $ciudad ); ?>
						</span>
						<?php endif; ?>
					</div>
					<!-- Comentario (fila completa si existe) -->
					<?php if ( $comentario ) : ?>
					<div class="col-span-12 mt-1.5 flex items-start gap-2 sm:pl-11">
						<svg class="mt-0.5 h-3 w-3 shrink-0 text-primary/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
						<span class="text-xs italic text-muted-foreground"><?php echo esc_html( $comentario ); ?></span>
					</div>
					<?php endif; ?>
				</div>
				<?php $num++; endwhile; wp_reset_postdata(); ?>

				<?php else : ?>
				<div class="flex flex-col items-center justify-center px-6 py-20 text-center">
					<div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl border border-dashed border-border/70 bg-muted/20">
						<svg class="h-7 w-7 text-muted-foreground/30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h2M4 12a8 8 0 0 1 8-8M12 4V2M20 12h2M12 20v2"/><circle cx="12" cy="12" r="3"/></svg>
					</div>
					<p class="font-display text-base font-semibold">Nadie se ha reportado todavía</p>
					<p class="mt-1 text-sm text-muted-foreground">¡Sé el primero en hacer check-in hoy!</p>
				</div>
				<?php endif; ?>
			</div>
		</div>

	</div>
</div>

<script>
(function(){
	// Live Net Monitor: la malla es martes 7:00-9:00 p.m. Colombia (ventana de 2h)
	function estadoNet(){
		var ahora=new Date();
		var diaISO=ahora.getUTCDay()===0?7:ahora.getUTCDay(); // 1=lunes...7=domingo
		var horaUTC=ahora.getUTCHours();
		// Martes 19:00-21:00 Colombia = miércoles 00:00-02:00 UTC
		var enVivo = (diaISO===3 && horaUTC<2);

		var badge=document.getElementById('net-badge');
		var estado=document.getElementById('net-estado');
		if(enVivo){
			badge.classList.remove('hidden'); badge.classList.add('inline-flex');
			estado.textContent='Malla en curso — reportándose ahora';
			document.getElementById('net-monitor').className='mb-8 rounded-2xl border-2 p-5 border-rose-400/40 bg-rose-400/5';
		} else {
			badge.classList.add('hidden');
			// Próximo miércoles 00:00 UTC = próximo martes 19:00 Colombia
			var diasHasta=(3 - ahora.getUTCDay() + 7) % 7;
			var candidato=new Date(Date.UTC(ahora.getUTCFullYear(), ahora.getUTCMonth(), ahora.getUTCDate()+diasHasta, 0, 0, 0));
			if(candidato.getTime() <= ahora.getTime()) candidato=new Date(candidato.getTime()+7*86400000);
			var msFaltan=candidato-ahora;
			var horas=Math.floor(msFaltan/3600000), dias=Math.floor(horas/24);
			estado.textContent = dias>0 ? 'Próxima malla en '+dias+' día'+(dias===1?'':'s') : 'Próxima malla en '+horas+'h';
		}
	}
	estadoNet();
	setInterval(estadoNet, 60000);

	// Last Heard
	function cargarLastHeard(){
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_last_heard', {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				var el=document.getElementById('last-heard-list');
				if(!json.success || !json.data.length){ el.innerHTML='<p class="text-sm text-muted-foreground">Sin actividad reciente.</p>'; return; }
				el.innerHTML=json.data.map(function(h){
					return '<div class="flex items-center justify-between border-b border-border/30 py-1.5 last:border-0 text-sm">'
						+'<span><strong class="font-mono text-primary">'+h.indicativo+'</strong> <span class="text-muted-foreground">'+(h.ciudad||'')+'</span></span>'
						+'<span class="font-mono text-xs text-muted-foreground">'+h.hace+'</span>'
						+'</div>';
				}).join('');
			}).catch(function(){});
	}
	cargarLastHeard();
	setInterval(cargarLastHeard, 30000);

	/* GPS del campo Grid en el formulario de check-in */
	var gpsBtn=document.getElementById('ci-grid-gps');
	if(gpsBtn){
		gpsBtn.addEventListener('click', function(){
			if(!navigator.geolocation){ alert('Tu navegador no soporta geolocalización.'); return; }
			navigator.geolocation.getCurrentPosition(function(pos){
				document.getElementById('ci-grid').value = latLngAGrid(pos.coords.latitude, pos.coords.longitude);
			}, function(){ alert('No se pudo obtener tu ubicación.'); });
		});
	}
	function latLngAGrid(lat,lng){
		lat+=90; lng+=180;
		var A='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		var campo=A[Math.floor(lng/20)]+A[Math.floor(lat/10)];
		var cuadro=Math.floor((lng%20)/2)+''+Math.floor(lat%10);
		var sub=A[Math.floor((lng%2)*12)].toLowerCase()+A[Math.floor((lat%1)*24)].toLowerCase();
		return campo+cuadro+sub;
	}

	/* Mapa de "quién estuvo hoy" (Leaflet, cargado solo si hay datos) */
	function gridACoordenadas(grid){
		grid=grid.toUpperCase();
		var A='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		var lng=A.indexOf(grid[0])*20 + parseInt(grid[2])*2 - 180;
		var lat=A.indexOf(grid[1])*10 + parseInt(grid[3]) - 90;
		var lngSize=2, latSize=1;
		if(grid.length>=6){
			lng += A.indexOf(grid[4])*(2/24);
			lat += A.indexOf(grid[5])*(1/24);
			lngSize=2/24; latSize=1/24;
		}
		return { lat: lat+latSize/2, lng: lng+lngSize/2 };
	}

	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_checkin_hoy_mapa', {cache:'no-store'})
		.then(function(r){ return r.json(); })
		.then(function(json){
			if(!json.success || !json.data.length) return;
			document.getElementById('mapa-hoy-wrap').classList.remove('hidden');

			var s=document.createElement('link');
			s.rel='stylesheet'; s.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
			document.head.appendChild(s);
			var script=document.createElement('script');
			script.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
			script.onload=function(){
				var map=L.map('mapa-hoy', { scrollWheelZoom:false }).setView([6.24,-75.58], 6);
					L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'&copy; OpenStreetMap' }).addTo(map);
				var bounds=[];
				json.data.forEach(function(p){
					if(!p.grid) return;
					var coord=gridACoordenadas(p.grid);
					L.circleMarker([coord.lat, coord.lng], { radius:8, color:'#0b0f1a', weight:2, fillColor:'#1fd5f9', fillOpacity:1 })
						.addTo(map)
						.bindPopup('<strong>'+p.indicativo+'</strong><br>'+(p.ciudad||'')+'<br><span style="font-family:monospace;font-size:11px;">'+p.grid+'</span>');
					bounds.push([coord.lat, coord.lng]);
				});
				if(bounds.length>1) map.fitBounds(bounds, { padding:[30,30] });
			};
			document.head.appendChild(script);
		}).catch(function(){});
})();
</script>

<?php get_footer(); ?>
