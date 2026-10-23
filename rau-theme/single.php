<?php
/**
 * Plantilla para entradas individuales (repetidor, evento, entradas de blog)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
while ( have_posts() ) : the_post();
	$type = get_post_type();
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<div class="max-w-3xl">
		<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary"><?php echo esc_html( get_post_type_object( $type )->labels->singular_name ); ?></p>
		<div class="mb-3 flex flex-wrap items-start justify-between gap-4">
			<h1 class="font-display text-3xl font-bold sm:text-4xl"><?php the_title(); ?></h1>
			<a href="https://wa.me/?text=<?php echo rawurlencode( get_the_title() . ' — ' . get_permalink() ); ?>" target="_blank" rel="noreferrer"
				class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-border bg-background px-3 py-2 text-xs font-medium hover:bg-accent hover:text-accent-foreground">
				<svg class="h-4 w-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"></path></svg>
				Compartir
			</a>
		</div>
	</div>

	<?php if ( 'repetidor' === $type ) : ?>
		<div class="mt-8 grid max-w-4xl gap-6 lg:grid-cols-2 lg:items-start">
		<div>
		<div class="overflow-hidden rounded-2xl border border-border/70 bg-card">
			<?php
			$estado_iconos_rep = [ 'activo' => '🟢', 'intermitente' => '🟡', 'mantenimiento' => '🔵', 'fuera de servicio' => '🔴' ];
			$estado_rep = get_post_meta( get_the_ID(), 'status', true ) ?: 'activo';
			$estado_rep_norm = strtolower( str_replace( '_', ' ', trim( $estado_rep ) ) );
			$estado_rep_icono = $estado_iconos_rep[ $estado_rep_norm ] ?? '⚪';
			$fecha_verif = get_post_meta( get_the_ID(), 'fecha_verificacion', true );
			$rows = array(
				'Indicativo' => rau_indicativo_confirmado( get_post_meta( get_the_ID(), 'callsign', true ) ) ? get_post_meta( get_the_ID(), 'callsign', true ) : 'Por confirmar',
				'Estado'     => $estado_rep_icono . ' ' . ucfirst( $estado_rep_norm ),
				'Banda'      => get_post_meta( get_the_ID(), 'band', true ),
				'Frecuencia' => get_post_meta( get_the_ID(), 'frequency', true ),
				'Desplazamiento' => get_post_meta( get_the_ID(), 'offset', true ) ?: '—',
				'Tono CTCSS' => get_post_meta( get_the_ID(), 'tone', true ) ?: '—',
				'DCS'        => get_post_meta( get_the_ID(), 'dcs', true ) ?: '—',
				'Modo'       => get_post_meta( get_the_ID(), 'mode', true ),
				'Ubicación'  => get_post_meta( get_the_ID(), 'location', true ),
				'Grid'       => get_post_meta( get_the_ID(), 'grid', true ) ?: '—',
			);
			foreach ( $rows as $label => $value ) : ?>
				<div class="flex justify-between border-b border-border/50 px-5 py-3 text-sm last:border-0">
					<span class="text-muted-foreground"><?php echo esc_html( $label ); ?></span>
					<span class="font-mono"><?php echo esc_html( $value ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="mt-2 font-mono text-[10px] text-muted-foreground">
			<?php if ( $fecha_verif ) : ?>
				Última verificación: <?php echo esc_html( $fecha_verif ); ?>
			<?php else : ?>
				⚪ Fecha de verificación no registrada — dato sin confirmar recientemente
			<?php endif; ?>
		</p>

		<?php
		$rep_lat = get_post_meta( get_the_ID(), 'lat', true );
		$rep_lng = get_post_meta( get_the_ID(), 'lng', true );
		if ( $rep_lat && $rep_lng ) :
			$rep_altura = get_post_meta( get_the_ID(), 'altura_antena', true ) ?: 30;
		?>
		<div class="mt-4 max-w-md rounded-2xl border border-border/70 bg-card p-5">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Estimación</p>
			<h2 class="mb-1 font-display text-lg font-bold">Cobertura desde tu QTH</h2>
			<p class="mb-4 text-xs text-muted-foreground">Horizonte de radio geométrico — el terreno real puede bloquear la señal aunque estés dentro del rango teórico.</p>

			<div class="space-y-3">
				<div>
					<label for="cov1-grid" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Tu grid square</label>
					<div class="flex gap-2">
						<input type="text" id="cov1-grid" maxlength="8" placeholder="FJ26ff" style="text-transform:uppercase" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring">
						<button type="button" id="cov1-gps" aria-label="Obtener mi posición desde el GPS del dispositivo" class="shrink-0 inline-flex items-center justify-center rounded-md border border-border bg-background px-3 text-xs font-medium hover:bg-accent">📍</button>
					</div>
				</div>
				<div>
					<label for="cov1-altura" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Altura de tu antena (m)</label>
					<input type="number" id="cov1-altura" value="10" min="1" class="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring">
				</div>
				<button id="cov1-calc" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Calcular</button>
			</div>

			<div id="cov1-result" class="mt-4 hidden">
				<div class="grid grid-cols-2 gap-3">
					<div class="rounded-lg border border-border/50 px-3 py-2 text-center"><p class="font-mono text-[10px] uppercase text-muted-foreground">Distancia</p><p id="cov1-dist" class="font-mono text-lg font-bold">—</p></div>
					<div class="rounded-lg border border-border/50 px-3 py-2 text-center"><p class="font-mono text-[10px] uppercase text-muted-foreground">Horizonte</p><p id="cov1-horiz" class="font-mono text-lg font-bold text-primary">—</p></div>
				</div>
				<div id="cov1-veredicto" class="mt-3 rounded-lg border px-4 py-2.5 text-sm font-semibold"></div>
			</div>
		</div>

		<script>
		(function(){
			var calcBtn=document.getElementById('cov1-calc');
			if(!calcBtn) return;
			var REP={lat:<?php echo esc_js( $rep_lat ); ?>, lng:<?php echo esc_js( $rep_lng ); ?>, altura:<?php echo esc_js( $rep_altura ); ?>};

			// Centro de la cuadrícula. Esta página no carga los scripts de Herramientas, así que
			// lleva su propia copia — pero la que había arrastraba el mismo fallo que ya se corrigió
			// allí: sumaba el desplazamiento al centro del cuadro de 2°×1° (+1 / +0.5) y ENCIMA el
			// del subcuadro, quedando hasta 124 km corrida. El cálculo de cobertura de este
			// repetidor partía de un punto que no era el del usuario.
			// Comprobado: FJ26ff -> 6.2292 N, 75.5417 W (centro real de la cuadrícula de Medellín).
			function gridCenter(g){
				g=(g||'').toUpperCase().trim();
				if(g.length<4) return null;
				var cuadLng=parseInt(g[2],10), cuadLat=parseInt(g[3],10);
				if(isNaN(cuadLng)||isNaN(cuadLat)) return null;
				var lng=-180+(g.charCodeAt(0)-65)*20+cuadLng*2;
				var lat=-90 +(g.charCodeAt(1)-65)*10+cuadLat;
				if(g.length>=6){
					// El subcuadro mide 2/24° de ancho y 1/24° de alto; se suma su mitad para el centro
					lng+=(g.charCodeAt(4)-65)*(2/24)+(1/24);
					lat+=(g.charCodeAt(5)-65)*(1/24)+(0.5/24);
				} else {
					lng+=1; lat+=0.5;
				}
				return {lat:lat,lng:lng};
			}
			function distanciaKm(o,d){
				var R=6371;
				var lat1=o.lat*Math.PI/180,lat2=d.lat*Math.PI/180;
				var dLat=(d.lat-o.lat)*Math.PI/180,dLng=(d.lng-o.lng)*Math.PI/180;
				var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(lat1)*Math.cos(lat2)*Math.sin(dLng/2)*Math.sin(dLng/2);
				return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
			}
			function horizonteKm(alturaM){ return 4.12*Math.sqrt(Math.max(alturaM,0)); } // horizonte de RADIO (modelo Tierra 4/3, ITU-R P.452) — no confundir con el óptico (3.57)

			document.getElementById('cov1-gps').addEventListener('click', function(){
				if(!navigator.geolocation){ alert('Tu navegador no soporta geolocalización.'); return; }
				navigator.geolocation.getCurrentPosition(function(pos){
					if(typeof latLngToMH==='function'){
						document.getElementById('cov1-grid').value=latLngToMH(pos.coords.latitude,pos.coords.longitude,6);
					} else {
						document.getElementById('cov1-grid').value=pos.coords.latitude.toFixed(4)+','+pos.coords.longitude.toFixed(4);
					}
				}, function(){ alert('No se pudo obtener la ubicación.'); });
			});
			document.getElementById('cov1-grid').addEventListener('input', function(){ this.value=this.value.toUpperCase(); });

			calcBtn.addEventListener('click', function(){
				var raw=document.getElementById('cov1-grid').value.trim();
				var origen = raw.indexOf(',')>-1
					? {lat:parseFloat(raw.split(',')[0]), lng:parseFloat(raw.split(',')[1])}
					: gridCenter(raw);
				if(!origen || isNaN(origen.lat)){ alert('Ingresa un grid square válido (ej. FJ26ff) o usa el botón GPS.'); return; }

				var altura=parseFloat(document.getElementById('cov1-altura').value)||10;
				var dist=distanciaKm(origen, REP);
				var h1=horizonteKm(altura), h2=horizonteKm(REP.altura);
				var horizonte=h1+h2;
				var ok=dist<=horizonte;

				document.getElementById('cov1-dist').textContent=dist.toFixed(1)+' km';
				document.getElementById('cov1-horiz').textContent=horizonte.toFixed(1)+' km';
				var v=document.getElementById('cov1-veredicto');
				if(ok){
					v.className='mt-3 rounded-lg border px-4 py-2.5 text-sm font-semibold border-emerald-400/30 bg-emerald-400/5 text-emerald-400';
					v.textContent='✅ Estás dentro del horizonte teórico.';
				} else {
					v.className='mt-3 rounded-lg border px-4 py-2.5 text-sm font-semibold border-rose-400/30 bg-rose-400/5 text-rose-400';
					v.textContent='⚠️ Fuera del horizonte teórico ('+(dist-horizonte).toFixed(0)+' km de más).';
				}
				document.getElementById('cov1-result').classList.remove('hidden');
			});
		})();
		</script>
		<?php endif; ?>
		</div>

		<?php
		// Lo que hay que poner en el radio. La ficha daba frecuencia y tono, pero no el desplazamiento,
		// y nadie decía la frecuencia de transmisión: es la cuenta que más se equivoca quien empieza.
		$prog = rau_programacion_repetidor( get_the_ID() );
		if ( $prog ) : ?>
		<div class="rounded-2xl border border-primary/30 bg-card p-5 sm:p-6">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Programa tu radio</p>
			<h2 class="mb-4 font-display text-lg font-bold">Así la configuras</h2>
			<div class="grid grid-cols-2 gap-3">
				<div class="rounded-xl bg-background p-3"><p class="text-xs text-muted-foreground">Recibes en (RX)</p><p class="font-mono text-lg font-bold text-primary"><?php echo esc_html( $prog['rx'] ); ?></p></div>
				<div class="rounded-xl bg-background p-3"><p class="text-xs text-muted-foreground">Transmites en (TX)</p><p class="font-mono text-lg font-bold"><?php echo esc_html( $prog['tx'] ); ?></p></div>
				<div class="rounded-xl bg-background p-3"><p class="text-xs text-muted-foreground">Desplazamiento</p><p class="font-mono font-semibold"><?php echo esc_html( $prog['shift'] ); ?></p></div>
				<div class="rounded-xl bg-background p-3"><p class="text-xs text-muted-foreground">Tono de subida</p><p class="font-mono font-semibold"><?php echo esc_html( $prog['tono'] ?: 'Sin tono' ); ?></p></div>
			</div>
			<ol class="mt-4 list-decimal space-y-1 pl-5 text-sm text-muted-foreground">
				<li>Sintoniza <span class="font-mono text-foreground"><?php echo esc_html( $prog['rx'] ); ?></span> en modo <?php echo esc_html( $prog['modo'] ); ?>.</li>
				<li>Activa el desplazamiento <?php echo esc_html( $prog['signo_texto'] ); ?> de <span class="font-mono text-foreground"><?php echo esc_html( $prog['shift_abs'] ); ?></span>.</li>
				<?php if ( $prog['tono'] ) : ?><li>Activa el tono (TONE o ENC) en <span class="font-mono text-foreground"><?php echo esc_html( $prog['tono'] ); ?></span>.</li><?php endif; ?>
				<li>Escucha primero; si el canal está libre, identifícate con tu indicativo.</li>
			</ol>
			<p class="mt-4 text-xs text-muted-foreground">¿Muchas repetidoras? Cárgalas todas de una vez con el <a href="<?php echo esc_url( admin_url( 'admin-post.php?action=rau_export_chirp' ) ); ?>" class="text-primary hover:underline">archivo para CHIRP</a>.</p>
		</div>
		<?php endif; ?>
		</div>

		<p class="mt-4 flex flex-wrap gap-3 text-sm">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'repetidor' ) ); ?>" class="text-primary hover:underline">← Ver todos los repetidores</a>
			<span class="text-muted-foreground">·</span>
			<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=rau_export_chirp' ) ); ?>" class="text-primary hover:underline">Exportar todos para CHIRP</a>
		</p>
	<?php elseif ( 'diploma' === $type ) : ?>
		<?php
		$dip_numero  = get_post_meta( get_the_ID(), 'numero', true );
		$dip_nombre  = get_post_meta( get_the_ID(), 'nombre', true );
		$dip_evento  = get_post_meta( get_the_ID(), 'evento', true );
		$dip_fecha   = get_post_meta( get_the_ID(), 'fecha', true );
		$dip_url     = get_permalink();
		?>
		<div class="mt-8 max-w-md overflow-hidden rounded-2xl border border-border/70 bg-card">
			<?php
			$rows = array(
				'Número'    => $dip_numero,
				'Nombre'    => $dip_nombre,
				'Actividad' => $dip_evento,
				'Fecha'     => $dip_fecha,
			);
			foreach ( $rows as $label => $value ) : ?>
				<div class="flex justify-between border-b border-border/50 px-5 py-3 text-sm last:border-0">
					<span class="text-muted-foreground"><?php echo esc_html( $label ); ?></span>
					<span class="font-mono"><?php echo esc_html( $value ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="mt-4 flex max-w-md flex-col items-center gap-2 rounded-2xl border border-border/70 bg-card p-5">
			<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Verificación pública — escanea para confirmar</p>
			<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo rawurlencode( $dip_url ); ?>" width="200" height="200" alt="Código QR de verificación de este diploma" loading="lazy" class="rounded-lg border border-border/50">
			<p class="break-all text-center font-mono text-[10px] text-muted-foreground"><?php echo esc_html( $dip_url ); ?></p>
		</div>
	<?php elseif ( 'evento' === $type ) : ?>
		<div class="mt-8 max-w-md overflow-hidden rounded-2xl border border-border/70 bg-card">
			<?php
			$rows = array(
				'Fecha'      => get_post_meta( get_the_ID(), 'start', true ),
				'Tipo'       => get_post_meta( get_the_ID(), 'type', true ),
				'Frecuencia' => get_post_meta( get_the_ID(), 'frequency', true ),
				'Ubicación'  => get_post_meta( get_the_ID(), 'location', true ),
			);
			foreach ( $rows as $label => $value ) : ?>
				<div class="flex justify-between border-b border-border/50 px-5 py-3 text-sm last:border-0">
					<span class="text-muted-foreground"><?php echo esc_html( $label ); ?></span>
					<span class="font-mono"><?php echo esc_html( $value ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( get_post_meta( get_the_ID(), 'start', true ) ) : ?>
			<a href="<?php echo esc_url( rau_google_calendar_link( get_the_ID() ) ); ?>" target="_blank" rel="noreferrer" class="mt-4 inline-flex h-10 items-center justify-center gap-2 rounded-md border border-border bg-background px-6 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground">
				<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3.5 10h17"></path></svg>
				Añadir a Google Calendar
			</a>
		<?php endif; ?>
	<?php endif; ?>

	<div class="entry-content mt-8 max-w-3xl text-muted-foreground">
		<?php the_content(); ?>
	</div>
</section>
<?php endwhile; get_footer(); ?>
