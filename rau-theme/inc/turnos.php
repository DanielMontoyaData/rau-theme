<?php
/**
 * Quién está de turno ahora y si se ha reportado.
 *
 * Cruza los Turnos de Guardia (fecha_inicio y fecha_fin; el título lleva el indicativo del responsable)
 * con los check-in de malla. Si quien está de turno no hace check-in en 24 horas, la página de Turnos y
 * el escritorio lo muestran en rojo.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_TURNO_HORAS_ALERTA', 24 );

function rau_indicativo_en( $texto ) {
	return preg_match( '/\b((?:[A-Z]{1,2}|[0-9][A-Z])[0-9][A-Z]{1,4})\b/', strtoupper( (string) $texto ), $m ) ? $m[1] : '';
}
function rau_turno_actual( $ahora = null ) {
	$ahora = $ahora ?: time(); $hoy = wp_date( 'Y-m-d', $ahora );
	$q = get_posts( array( 'post_type' => 'turno', 'post_status' => 'publish', 'numberposts' => 5, 'meta_query' => array( 'relation' => 'AND',
		array( 'key' => 'fecha_inicio', 'value' => $hoy, 'compare' => '<=', 'type' => 'DATE' ),
		array( 'key' => 'fecha_fin', 'value' => $hoy, 'compare' => '>=', 'type' => 'DATE' ) ) ) );
	if ( ! $q ) return null;
	$t = $q[0]; $ind = rau_indicativo_en( $t->post_title );
	$ult = null;
	if ( $ind ) {
		$c = get_posts( array( 'post_type' => 'checkin', 'post_status' => 'publish', 'numberposts' => 1, 'orderby' => 'date', 'order' => 'DESC',
			'meta_query' => array( array( 'key' => 'indicativo', 'value' => $ind, 'compare' => '=' ) ) ) );
		if ( $c ) $ult = (int) get_post_time( 'U', true, $c[0] );
	}
	$horas = $ult ? ( $ahora - $ult ) / HOUR_IN_SECONDS : null;
	return array( 'titulo' => $t->post_title, 'indicativo' => $ind, 'inicio' => get_post_meta( $t->ID, 'fecha_inicio', true ), 'fin' => get_post_meta( $t->ID, 'fecha_fin', true ),
		'ultimo' => $ult, 'horas' => null === $horas ? null : round( $horas, 1 ),
		'alerta' => $ind && ( null === $horas || $horas > RAU_TURNO_HORAS_ALERTA ) );
}
function rau_turno_tarjeta() {
	$t = rau_turno_actual(); if ( ! $t ) return;
	$color = $t['alerta'] ? 'border-rose-500/50 bg-rose-500/10' : 'border-emerald-400/40 bg-emerald-400/5';
	?>
	<div id="turno-ahora" class="mb-6 rounded-2xl border-2 <?php echo esc_attr( $color ); ?> p-5" role="status">
		<p class="font-mono text-xs uppercase tracking-[0.2em] <?php echo $t['alerta'] ? 'text-rose-400' : 'text-emerald-400'; ?>">De turno ahora</p>
		<p class="mt-1 font-display text-2xl font-bold"><?php echo esc_html( $t['titulo'] ); ?></p>
		<p class="mt-1 text-sm text-muted-foreground">Del <?php echo esc_html( $t['inicio'] ); ?> al <?php echo esc_html( $t['fin'] ); ?>.
			<?php if ( ! $t['indicativo'] ) : ?>Escribe el indicativo en el título del turno para cruzarlo con los check-in.
			<?php elseif ( null === $t['ultimo'] ) : ?><strong class="text-rose-400"><?php echo esc_html( $t['indicativo'] ); ?> no tiene ningún check-in registrado.</strong>
			<?php else : ?>Último check-in de <?php echo esc_html( $t['indicativo'] ); ?>: <strong class="<?php echo $t['alerta'] ? 'text-rose-400' : ''; ?>">hace <?php echo esc_html( human_time_diff( $t['ultimo'] ) ); ?></strong><?php echo $t['alerta'] ? ' — más de ' . RAU_TURNO_HORAS_ALERTA . ' horas sin reportarse.' : '.'; ?>
			<?php endif; ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', function() {
	if ( ! current_user_can( 'edit_posts' ) ) return;
	$t = rau_turno_actual(); if ( ! $t || ! $t['alerta'] ) return;
	echo '<div class="notice notice-error"><p><strong>Guardia sin reportarse:</strong> ' . esc_html( $t['titulo'] ) . ' está de turno y ' . ( null === $t['ultimo'] ? 'no tiene check-in registrado' : 'su último check-in fue hace ' . esc_html( human_time_diff( $t['ultimo'] ) ) ) . '.</p></div>';
} );
