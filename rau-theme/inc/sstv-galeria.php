<?php
/**
 * Galería pública de imágenes SSTV recibidas con el receptor del club.
 *
 * Quien decodifica una imagen en el receptor y tiene sesión iniciada puede publicarla con un botón. Queda en
 * la biblioteca de medios con el modo, la frecuencia y la hora, y se ve en la página del receptor. Solo PNG
 * del tamaño que produce el propio decodificador (hasta 640×496). Se borran a los 30 días, salvo las que un
 * administrador marque como destacadas. Hasta 20 por persona por día.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_SSTV_META', '_rau_sstv' );

add_action( 'rest_api_init', function() {
	register_rest_route( 'rau/v1', '/sstv', array(
		array( 'methods' => 'POST', 'permission_callback' => function() { return is_user_logged_in(); }, 'callback' => 'rau_sstv_subir' ),
		array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function() { return rau_sstv_lista( 24 ); } ),
	) );
} );

function rau_sstv_subir( WP_REST_Request $req ) {
	$uid = get_current_user_id();
	$hoy = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'author' => $uid, 'meta_key' => RAU_SSTV_META, 'date_query' => array( array( 'after' => '1 day ago' ) ), 'fields' => 'ids', 'numberposts' => 25 ) );
	if ( count( $hoy ) >= 20 ) return new WP_REST_Response( array( 'error' => 'Llegaste al límite de 20 imágenes por día' ), 429 );
	$f = $req->get_file_params();
	if ( empty( $f['imagen']['tmp_name'] ) ) return new WP_REST_Response( array( 'error' => 'Falta la imagen' ), 400 );
	if ( $f['imagen']['size'] > 600 * 1024 ) return new WP_REST_Response( array( 'error' => 'Imagen demasiado grande' ), 413 );
	$info = @getimagesize( $f['imagen']['tmp_name'] );
	if ( ! $info || IMAGETYPE_PNG !== $info[2] ) return new WP_REST_Response( array( 'error' => 'Solo imágenes PNG del decodificador' ), 400 );
	if ( $info[0] < 160 || $info[0] > 640 || $info[1] < 120 || $info[1] > 496 ) return new WP_REST_Response( array( 'error' => 'Tamaño de imagen no válido para SSTV' ), 400 );
	$modo = substr( preg_replace( '/[^A-Za-z0-9 ]/', '', (string) $req->get_param( 'modo' ) ), 0, 20 );
	$freq = round( (float) $req->get_param( 'freq' ), 2 );
	$inicio = (int) $req->get_param( 'inicio' );
	if ( $freq <= 0 || $freq > 32000 ) return new WP_REST_Response( array( 'error' => 'Frecuencia no válida' ), 400 );
	if ( $inicio < ( time() - DAY_IN_SECONDS ) * 1000 || $inicio > ( time() + 60 ) * 1000 ) $inicio = time() * 1000;

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$cuando = gmdate( 'Y-m-d H:i', (int) ( $inicio / 1000 ) );
	$arch = array( 'name' => sprintf( 'HK4RAU-SSTV-%s-%.0fkHz-%s.png', str_replace( ' ', '', $modo ?: 'SSTV' ), $freq, gmdate( 'Ymd-Hi', (int) ( $inicio / 1000 ) ) ),
		'tmp_name' => $f['imagen']['tmp_name'], 'type' => 'image/png', 'error' => 0, 'size' => $f['imagen']['size'] );
	$id = media_handle_sideload( $arch, 0, sprintf( 'SSTV %s · %s kHz · %s UTC', $modo ?: '', number_format( $freq, 0, ',', '.' ), $cuando ), array( 'post_author' => $uid ) );
	if ( is_wp_error( $id ) ) return new WP_REST_Response( array( 'error' => $id->get_error_message() ), 500 );
	$u = wp_get_current_user();
	update_post_meta( $id, RAU_SSTV_META, array( 'modo' => $modo, 'freq' => $freq, 'inicio' => $inicio, 'por' => $u->display_name ?: $u->user_login ) );
	return rau_sstv_datos( $id );
}
function rau_sstv_datos( $id ) {
	$m = get_post_meta( $id, RAU_SSTV_META, true ); if ( ! $m ) return null;
	return array( 'id' => $id, 'url' => wp_get_attachment_url( $id ), 'modo' => $m['modo'], 'freq' => $m['freq'], 'inicio' => $m['inicio'], 'por' => $m['por'], 'titulo' => get_the_title( $id ) );
}
function rau_sstv_lista( $n = 12 ) {
	$ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => RAU_SSTV_META, 'orderby' => 'date', 'order' => 'DESC', 'fields' => 'ids', 'numberposts' => $n ) );
	return array_values( array_filter( array_map( 'rau_sstv_datos', $ids ) ) );
}

/* Limpieza diaria: más de 30 días y sin destacar */
add_action( 'init', function() { if ( ! wp_next_scheduled( 'rau_sstv_limpiar' ) ) wp_schedule_event( time() + 2 * HOUR_IN_SECONDS, 'daily', 'rau_sstv_limpiar' ); } );
add_action( 'rau_sstv_limpiar', 'rau_sstv_limpiar' );
function rau_sstv_limpiar() {
	$viejas = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => RAU_SSTV_META, 'date_query' => array( array( 'before' => '30 days ago' ) ), 'fields' => 'ids', 'numberposts' => 200 ) );
	$n = 0; foreach ( $viejas as $id ) { if ( get_post_meta( $id, '_rau_grabacion_destacada', true ) ) continue; wp_delete_attachment( $id, true ); $n++; }
	return $n;
}

/* Sección de la página del receptor: imágenes SSTV y lo que el club aporta a PSK Reporter */
function rau_receptor_aportes() {
	$imgs = rau_sstv_lista( 12 );
	$psk = function_exists( 'rau_psk_resumen' ) ? rau_psk_resumen( 30 ) : null;
	?>
	<section id="rx-aportes" class="mt-10 grid gap-6 lg:grid-cols-3" aria-label="Lo que se recibe con el receptor">
		<div class="rounded-2xl border border-border/70 bg-card p-5 lg:col-span-2">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Galería</p>
			<h2 class="font-display text-lg font-bold">Imágenes SSTV recibidas</h2>
			<?php if ( $imgs ) : ?>
				<p class="mt-1 text-sm text-muted-foreground">Decodificadas con este receptor y publicadas por quien las recibió. Se guardan 30 días.</p>
				<div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
					<?php foreach ( $imgs as $g ) : $cuando = wp_date( 'j M Y, H:i', (int) ( $g['inicio'] / 1000 ) ); ?>
					<figure class="overflow-hidden rounded-lg border border-border/70 bg-black">
						<a href="<?php echo esc_url( $g['url'] ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( $g['url'] ); ?>" alt="<?php echo esc_attr( 'Imagen SSTV ' . $g['modo'] . ' recibida el ' . $cuando ); ?>" loading="lazy" decoding="async" class="aspect-[5/4] w-full object-cover" style="image-rendering:pixelated"></a>
						<figcaption class="px-2 py-1.5 font-mono text-[10px] leading-tight text-muted-foreground"><?php echo esc_html( $g['modo'] . ' · ' . number_format( $g['freq'], 0, ',', '.' ) . ' kHz' ); ?><br><?php echo esc_html( $cuando . ' · ' . $g['por'] ); ?></figcaption>
					</figure>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="mt-1 text-sm text-muted-foreground">Todavía no hay imágenes publicadas. Elige el decodificador SSTV, espera una imagen (suelen enviarse en 14.230 y 7.171 kHz) y, con tu sesión iniciada, pulsa «Publicar en la galería».</p>
			<?php endif; ?>
		</div>
		<div class="rounded-2xl border border-border/70 bg-card p-5">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Red mundial</p>
			<h2 class="font-display text-lg font-bold">Aporte a PSK Reporter</h2>
			<p class="mt-1 text-sm text-muted-foreground">El receptor informa a <a class="text-primary underline" href="https://pskreporter.info/pskmap.html?preset&amp;callsign=<?php echo esc_attr( RAU_KIWI_INDICATIVO ); ?>&amp;txrx=rx" target="_blank" rel="noopener">PSK Reporter</a> las estaciones de FT8 que oye. Además, lo que se decodifica en esta página (FT4, JS8 y FT8 en otras bandas) se envía como oído por <?php echo esc_html( RAU_KIWI_INDICATIVO ); ?>.</p>
			<?php if ( $psk && $psk['n'] ) : ?>
				<p class="mt-3 font-display text-3xl font-bold"><?php echo esc_html( number_format( $psk['n'], 0, ',', '.' ) ); ?></p>
				<p class="text-xs text-muted-foreground">reportes enviados desde esta página en 30 días</p>
				<p class="mt-3 text-sm"><?php echo esc_html( implode( ' · ', array_map( function( $b, $k ) { return $b . ': ' . $k; }, array_keys( array_slice( $psk['bandas'], 0, 5, true ) ), array_slice( $psk['bandas'], 0, 5, true ) ) ) ); ?></p>
				<p class="text-sm text-muted-foreground"><?php echo esc_html( implode( ' · ', array_map( function( $m, $k ) { return $m . ' ' . $k; }, array_keys( $psk['modos'] ), $psk['modos'] ) ) ); ?></p>
				<?php if ( $psk['lejos'] ) : ?><p class="mt-2 text-sm">El más lejano: <b><?php echo esc_html( $psk['lejos']['c'] ); ?></b>, a <?php echo esc_html( number_format( $psk['lejos']['km'], 0, ',', '.' ) ); ?> km en <?php echo esc_html( $psk['lejos']['b'] . ' ' . $psk['lejos']['m'] ); ?>.</p><?php endif; ?>
			<?php else : ?>
				<p class="mt-3 text-sm text-muted-foreground">Aún no hay reportes enviados desde esta página en los últimos 30 días.</p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
