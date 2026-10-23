<?php
/**
 * Archivo completo de Repetidores (con paginación)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<section class="mx-auto max-w-[80rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Directorio Completo</p>
	<div class="mb-2 flex flex-wrap items-end justify-between gap-3">
		<h1 class="max-w-2xl font-display text-3xl font-bold sm:text-4xl">Todos los Repetidores</h1>
		<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=rau_export_chirp' ) ); ?>" class="inline-flex items-center gap-1.5 rounded-md border border-border/70 bg-card px-4 py-2 text-sm font-medium hover:border-primary/50">
			⬇ Exportar para CHIRP (CSV)
		</a>
	</div>
	<p class="mb-6 flex flex-wrap gap-4 text-xs text-muted-foreground">
		<span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span> Activo</span>
		<span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> Mantenimiento</span>
		<span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span> Fuera de servicio</span>
	</p>

	<?php
	$rau_map_data = rau_get_repeaters_data();
	$rau_eventos_map = rau_get_eventos_map_data();
	$rau_alertas_map = rau_get_alertas_map_data();
	?>
	<?php if ( $rau_map_data || $rau_eventos_map || $rau_alertas_map ) : ?>
	<div class="mb-3 flex flex-wrap items-center gap-3 font-mono text-xs">
		<span class="text-muted-foreground uppercase tracking-wider">Capas:</span>
		<label class="flex items-center gap-1.5"><input type="checkbox" id="rau-capa-repetidores" checked class="accent-primary"> 📡 Repetidores (<?php echo count( $rau_map_data ); ?>)</label>
		<label class="flex items-center gap-1.5"><input type="checkbox" id="rau-capa-eventos" checked class="accent-primary"> 📅 Eventos (<?php echo count( $rau_eventos_map ); ?>)</label>
		<label class="flex items-center gap-1.5"><input type="checkbox" id="rau-capa-alertas" checked class="accent-primary"> 🚨 Alertas activas (<?php echo count( $rau_alertas_map ); ?>)</label>
	</div>
	<div id="rau-repeaters-map" class="mb-8 h-[380px] w-full rounded-2xl border border-border/70 bg-card sm:h-[460px]"></div>
	<p class="mb-8 -mt-4 text-xs text-muted-foreground">Los círculos son un horizonte de radio <strong class="text-foreground">estimado</strong> (asumiendo una estación típica de 10 m de altura) — el terreno real puede reducir el alcance mostrado.</p>
	<?php endif; ?>

	<div class="overflow-hidden rounded-2xl border border-border/70 bg-card">
		<div class="hidden grid-cols-12 gap-4 border-b border-border/70 px-6 py-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground md:grid">
			<div class="col-span-3">Repetidora</div>
			<div class="col-span-2">Frecuencia</div>
			<div class="col-span-1">Tono</div>
			<div class="col-span-2">Modo</div>
			<div class="col-span-2">Ubicación</div>
			<div class="col-span-2">Estado</div>
		</div>
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); $rid = get_the_ID();
			$status = get_post_meta( $rid, 'status', true ) ?: 'activo';
		?>
		<div class="grid grid-cols-2 gap-2 border-b border-border/50 px-6 py-4 last:border-0 md:grid-cols-12 md:items-center md:gap-4">
			<div class="col-span-2 md:col-span-3">
				<p class="font-semibold"><a href="<?php the_permalink(); ?>" class="hover:text-primary"><?php the_title(); ?></a></p>
				<p class="font-mono text-xs text-primary"><?php echo esc_html( get_post_meta( $rid, 'callsign', true ) ); ?> · <?php echo esc_html( get_post_meta( $rid, 'band', true ) ); ?></p>
			</div>
			<div class="md:col-span-2 font-mono text-sm"><?php echo esc_html( get_post_meta( $rid, 'frequency', true ) ); ?><span class="block text-xs text-muted-foreground"><?php echo esc_html( get_post_meta( $rid, 'offset', true ) ); ?></span></div>
			<div class="md:col-span-1 font-mono text-sm text-muted-foreground"><?php echo esc_html( get_post_meta( $rid, 'tone', true ) ?: '—' ); ?></div>
			<div class="md:col-span-2 text-sm"><?php echo esc_html( get_post_meta( $rid, 'mode', true ) ); ?></div>
			<div class="md:col-span-2 flex items-center gap-1 text-sm text-muted-foreground">
				<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1116 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
				<?php echo esc_html( get_post_meta( $rid, 'location', true ) ); ?>
			</div>
			<div class="md:col-span-2">
				<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 <?php echo esc_attr( rau_status_classes( $status ) ); ?>"><?php echo esc_html( $status ); ?></span>
			</div>
		</div>
		<?php endwhile; else : ?>
			<div class="px-6 py-10 text-center text-sm text-muted-foreground">Aún no hay repetidores publicados.</div>
		<?php endif; ?>
	</div>

	<div class="mt-8"><?php the_posts_pagination( array(
		'prev_text' => '← Anterior',
		'next_text' => 'Siguiente →',
	) ); ?></div>
</section>

<?php if ( $rau_map_data || $rau_eventos_map || $rau_alertas_map ) : ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function(){
	var REPS = <?php echo wp_json_encode( $rau_map_data ); ?>;
	var EVENTOS = <?php echo wp_json_encode( $rau_eventos_map ); ?>;
	var ALERTAS = <?php echo wp_json_encode( $rau_alertas_map ); ?>;
	var container = document.getElementById('rau-repeaters-map');
	if(!container || (!REPS.length && !EVENTOS.length && !ALERTAS.length)) return;

	var COLORES = { activo:'#34d399', mantenimiento:'#fbbf24', inactivo:'#fb7185' };
	function horizonteKm(alturaM){ return 4.12*Math.sqrt(Math.max(alturaM,0)); } // horizonte de RADIO (modelo Tierra 4/3, ITU-R P.452) — no confundir con el óptico (3.57)

	// Centro inicial: el primer punto de cualquier capa que tenga datos (repetidores primero, si existen)
	var primerPunto = REPS[0] || EVENTOS[0] || ALERTAS[0];
	var map=L.map(container, { scrollWheelZoom:false }).setView([primerPunto.lat, primerPunto.lng], 7);
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution:'&copy; OpenStreetMap', maxZoom:18
	}).addTo(map);

	var bounds=[];
	var capaRepetidores=L.layerGroup().addTo(map);
	var capaEventos=L.layerGroup().addTo(map);
	var capaAlertas=L.layerGroup().addTo(map);

	REPS.forEach(function(r){
		var color=COLORES[r.status]||COLORES.activo;
		var radioM=(horizonteKm(10)+horizonteKm(r.altura))*1000;
		L.circle([r.lat, r.lng], { radius:radioM, color:color, weight:1, fillColor:color, fillOpacity:0.06 }).addTo(capaRepetidores);
		var marker=L.circleMarker([r.lat, r.lng], { radius:8, color:'#0b0f1a', weight:2, fillColor:color, fillOpacity:1 }).addTo(capaRepetidores);
		marker.bindPopup(
			'<div style="font-family:monospace;min-width:180px;">'
			+'<strong>'+r.name+'</strong><br>'
			+(r.callsign?r.callsign+' · ':'')+(r.freq||'')+' '+(r.offset||'')+'<br>'
			+(r.tone?'Tono '+r.tone+' · ':'')+(r.mode||'')+'<br>'
			+'<span style="color:#94a3b8;">'+(r.location||'')+'</span><br>'
			+'<a href="'+r.url+'" style="color:'+color+';">Ver ficha completa →</a>'
			+'</div>'
		);
		bounds.push([r.lat, r.lng]);
	});

	EVENTOS.forEach(function(e){
		var marker=L.marker([e.lat, e.lng], {
			icon: L.divIcon({ html:'<div style="font-size:20px;">📅</div>', className:'', iconSize:[24,24], iconAnchor:[12,12] })
		}).addTo(capaEventos);
		marker.bindPopup(
			'<div style="font-family:monospace;min-width:180px;">'
			+'<strong>'+e.name+'</strong><br>'
			+(e.type?e.type+'<br>':'')
			+(e.start?'<span style="color:#94a3b8;">'+e.start+'</span><br>':'')
			+(e.location?'<span style="color:#94a3b8;">'+e.location+'</span><br>':'')
			+(e.url?'<a href="'+e.url+'" style="color:#38bdf8;">Ver evento →</a>':'')
			+'</div>'
		);
		bounds.push([e.lat, e.lng]);
	});

	ALERTAS.forEach(function(a){
		var marker=L.marker([a.lat, a.lng], {
			icon: L.divIcon({ html:'<div style="font-size:20px;">🚨</div>', className:'', iconSize:[24,24], iconAnchor:[12,12] })
		}).addTo(capaAlertas);
		marker.bindPopup(
			'<div style="font-family:monospace;min-width:180px;">'
			+'<strong>'+a.name+'</strong><br>'
			+'Nivel: '+(a.nivel||'—')+'<br>'
			+(a.zona?'<span style="color:#94a3b8;">'+a.zona+'</span>':'')
			+'</div>'
		);
		bounds.push([a.lat, a.lng]);
	});

	if(bounds.length>1) map.fitBounds(bounds, { padding:[30,30] });

	// Checkboxes de capas — reutilizan el patrón simple ya establecido en el resto del sitio
	var chkRep=document.getElementById('rau-capa-repetidores');
	var chkEvt=document.getElementById('rau-capa-eventos');
	var chkAlert=document.getElementById('rau-capa-alertas');
	if(chkRep) chkRep.addEventListener('change', function(){ map[this.checked?'addLayer':'removeLayer'](capaRepetidores); });
	if(chkEvt) chkEvt.addEventListener('change', function(){ map[this.checked?'addLayer':'removeLayer'](capaEventos); });
	if(chkAlert) chkAlert.addEventListener('change', function(){ map[this.checked?'addLayer':'removeLayer'](capaAlertas); });
})();
</script>
<?php endif; ?>
<?php get_footer(); ?>
