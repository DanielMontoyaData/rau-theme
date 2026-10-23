<?php
/**
 * Template Name: Estado RAU
 * Un solo lugar con el estado de todo lo operativo — repetidores, KiwiSDR,
 * próxima malla. Reutiliza la misma fuente de datos que el resto del
 * sitio, no agrega ningún dato nuevo.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$repetidores = rau_get_repeaters_data();
?>

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">

	<div class="mb-8">
		<p class="mb-2 font-mono text-[11px] uppercase tracking-[0.25em] text-primary">De un vistazo</p>
		<h1 class="font-display text-3xl font-bold sm:text-4xl">Estado RAU</h1>
		<p class="mt-2 text-muted-foreground">Todo lo operativo de la asociación, en un solo lugar.</p>
	</div>

	<!-- MALLA + KIWISDR -->
	<div class="mb-6 grid gap-4 sm:grid-cols-2">
		<div class="rounded-2xl border border-border/70 bg-card p-5">
			<p class="mb-1 font-mono text-xs uppercase tracking-wider text-muted-foreground">Próxima malla</p>
			<p id="estado-malla" class="font-display text-lg font-bold">Calculando…</p>
			<p class="mt-1 font-mono text-xs text-primary"><?php echo esc_html( rau_get_repetidor_principal()['frequency'] ); ?> MHz · Martes 7:00 p.m.</p>
		</div>
		<div class="rounded-2xl border border-border/70 bg-card p-5">
			<div class="mb-1 flex items-center justify-between">
				<p class="font-mono text-xs uppercase tracking-wider text-muted-foreground">KiwiSDR HK4RAU</p>
				<span id="estado-sdr-badge" class="inline-flex items-center gap-1.5">
					<span class="h-2 w-2 rounded-full bg-muted animate-pulse"></span>
				</span>
			</div>
			<p id="estado-sdr-texto" class="font-display text-lg font-bold">Consultando…</p>
		</div>
	</div>

	<!-- ESTADÍSTICAS DE PARTICIPACIÓN -->
	<div class="mb-6 rounded-2xl border border-border/70 bg-card p-5">
		<p class="mb-4 font-mono text-xs uppercase tracking-wider text-muted-foreground">Check-ins por semana (últimas 12)</p>
		<div id="stats-chart">
			<p class="text-sm text-muted-foreground">Cargando…</p>
		</div>
	</div>

	<!-- REPETIDORES -->
	<div class="rounded-2xl border border-border/70 bg-card p-5">
		<p class="mb-4 font-mono text-xs uppercase tracking-wider text-muted-foreground">Repetidores (<?php echo count( $repetidores ); ?>)</p>
		<div class="space-y-2">
			<?php foreach ( $repetidores as $r ) :
				$colores = array( 'activo' => 'bg-emerald-400', 'mantenimiento' => 'bg-amber-400', 'inactivo' => 'bg-rose-400' );
				$color   = $colores[ $r['status'] ] ?? $colores['activo'];
			?>
			<a href="<?php echo esc_url( $r['url'] ); ?>" class="flex items-center justify-between rounded-lg border border-border/50 px-4 py-2.5 hover:border-primary/50">
				<div class="flex items-center gap-2.5">
					<span class="h-2.5 w-2.5 shrink-0 rounded-full <?php echo esc_attr( $color ); ?>"></span>
					<span class="font-medium"><?php echo esc_html( $r['name'] ); ?></span>
					<span class="font-mono text-xs text-primary"><?php echo esc_html( $r['callsign'] ); ?></span>
				</div>
				<span class="font-mono text-xs text-muted-foreground"><?php echo esc_html( $r['freq'] ); ?></span>
			</a>
			<?php endforeach; ?>
			<?php if ( ! $repetidores ) : ?>
			<p class="text-sm text-muted-foreground">Sin repetidores publicados todavía.</p>
			<?php endif; ?>
		</div>
	</div>

	<p class="mt-6 text-center font-mono text-[10px] text-muted-foreground">Se actualiza solo · KiwiSDR cada 2 min</p>
</div>

<script>
(function(){
	// Próxima malla: siguiente miércoles 00:00 UTC = martes 19:00 Colombia (UTC-5)
	function proximaMalla(){
		var ahora=new Date();
		var diasHastaObjetivo=(3 - ahora.getUTCDay() + 7) % 7; // 3 = miércoles en getUTCDay()
		var candidato=new Date(Date.UTC(ahora.getUTCFullYear(), ahora.getUTCMonth(), ahora.getUTCDate()+diasHastaObjetivo, 0, 0, 0));
		if(candidato.getTime() <= ahora.getTime()){
			candidato=new Date(candidato.getTime() + 7*86400000);
		}
		var msFaltan=candidato-ahora;
		var dias=Math.floor(msFaltan/86400000);
		var horas=Math.floor((msFaltan%86400000)/3600000);
		var texto = dias===0 ? '¡Hoy en '+horas+'h!' : (dias===1 ? 'Mañana' : 'En '+dias+' días');
		document.getElementById('estado-malla').textContent=texto;
	}
	proximaMalla();

	function checkSDR(){
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_sdr_status', {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				var badge=document.getElementById('estado-sdr-badge');
				var texto=document.getElementById('estado-sdr-texto');
				if(json.success){
					badge.innerHTML='<span class="h-2 w-2 rounded-full bg-emerald-400"></span>';
					texto.textContent='En línea'+(json.data.users_audio!==undefined?' · '+json.data.users_audio+' usuarios':'');
				} else {
					badge.innerHTML='<span class="h-2 w-2 rounded-full bg-rose-400"></span>';
					texto.textContent='Sin conexión';
				}
			}).catch(function(){
				document.getElementById('estado-sdr-badge').innerHTML='<span class="h-2 w-2 rounded-full bg-rose-400"></span>';
				document.getElementById('estado-sdr-texto').textContent='Sin conexión';
			});
	}
	checkSDR(); setInterval(checkSDR, 2*60*1000);

	// Estadísticas de participación
	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_checkin_stats', {cache:'no-store'})
		.then(function(r){ return r.json(); })
		.then(function(json){
			var el=document.getElementById('stats-chart');
			if(!json.success || !json.data.length){ el.innerHTML='<p class="text-sm text-muted-foreground">Sin datos todavía.</p>'; return; }
			var datos=json.data;
			var max=Math.max.apply(null, datos.map(function(d){ return d.total; }).concat([1]));
			var W=600, H=100, barW=W/datos.length;
			var barras=datos.map(function(d,i){
				var h=(d.total/max)*(H-10);
				var x=i*barW+2, y=H-h;
				var color = d.total>0 ? 'hsl(var(--primary))' : 'hsl(var(--border))';
				return '<rect x="'+x+'" y="'+y+'" width="'+(barW-4)+'" height="'+h+'" fill="'+color+'" rx="1"></rect>';
			}).join('');
			el.innerHTML='<svg viewBox="0 0 '+W+' '+H+'" class="w-full h-24">'+barras+'</svg>'
				+'<p class="mt-2 text-xs text-muted-foreground">Total: '+datos.reduce(function(a,d){return a+d.total;},0)+' check-ins en las últimas 12 semanas</p>';
		}).catch(function(){});
})();
</script>

<?php get_footer(); ?>
