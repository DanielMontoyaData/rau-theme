<?php
/**
 * Libro de Guardia del club (CPT «qso»): exportar e importar ADIF, y diploma automático.
 *
 *  · Exportar: un archivo ADIF con todos los contactos, listo para firmarlo con TQSL y subirlo a LoTW
 *    (LoTW solo acepta archivos firmados con el certificado de la estación) o importarlo en QRZ, Log4OM…
 *  · Importar: un ADIF de WSJT-X, Log4OM, el «Log ADIF» de Herramientas, etc. Los repetidos (mismo
 *    indicativo, fecha, hora, banda y modo) no se duplican.
 *  · Diploma: cuando un contacto queda confirmado, se emite el diploma «Contacto con HK4RAU» con un
 *    número que se puede comprobar en Herramientas → Verificador de Diplomas.
 * Metas del QSO: fecha (AAAA-MM-DD), hora (HH:MM, UTC), banda, modo, frecuencia (MHz), rst_env, rst_rec,
 * confirmado. El título es el indicativo.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function rau_adif_campo( $n, $v ) { $v = (string) $v; return '' === $v ? '' : '<' . $n . ':' . strlen( $v ) . '>' . $v . ' '; }
function rau_adif_banda( $b ) { $b = strtolower( str_replace( ' ', '', (string) $b ) ); return preg_match( '/^\d+(\.\d+)?c?m$/', $b ) ? strtoupper( $b ) : ''; }
function rau_libro_adif( $ids = null ) {
	$ids = null === $ids ? get_posts( array( 'post_type' => 'qso', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'ASC' ) ) : $ids;
	$out = "Libro de Guardia de " . RAU_KIWI_INDICATIVO . " exportado desde hk4rau.com\n" . rau_adif_campo( 'ADIF_VER', '3.1.4' ) . rau_adif_campo( 'PROGRAMID', 'RAU' ) . rau_adif_campo( 'PROGRAMVERSION', RAU_THEME_VERSION ) . "<EOH>\n";
	foreach ( $ids as $id ) {
		$m = function( $k ) use ( $id ) { return (string) get_post_meta( $id, $k, true ); };
		$fecha = preg_replace( '/\D/', '', $m( 'fecha' ) ); $hora = preg_replace( '/\D/', '', $m( 'hora' ) );
		$out .= rau_adif_campo( 'CALL', strtoupper( get_the_title( $id ) ) ) . rau_adif_campo( 'QSO_DATE', 8 === strlen( $fecha ) ? $fecha : '' ) . rau_adif_campo( 'TIME_ON', $hora ? str_pad( substr( $hora, 0, 4 ), 4, '0' ) : '' )
			. rau_adif_campo( 'BAND', rau_adif_banda( $m( 'banda' ) ) ) . rau_adif_campo( 'MODE', strtoupper( $m( 'modo' ) ) ) . rau_adif_campo( 'FREQ', $m( 'frecuencia' ) )
			. rau_adif_campo( 'RST_SENT', $m( 'rst_env' ) ) . rau_adif_campo( 'RST_RCVD', $m( 'rst_rec' ) ) . rau_adif_campo( 'STATION_CALLSIGN', RAU_KIWI_INDICATIVO )
			. rau_adif_campo( 'QSL_RCVD', $m( 'confirmado' ) ? 'Y' : 'N' ) . "<EOR>\n";
	}
	return $out;
}
/* Lee registros ADIF: devuelve una lista de arreglos con los campos en minúsculas */
function rau_adif_leer( $txt ) {
	$p = stripos( $txt, '<eoh>' ); if ( false !== $p ) $txt = substr( $txt, $p + 5 );
	$regs = array(); $act = array(); $i = 0; $n = strlen( $txt );
	while ( $i < $n ) {
		$a = strpos( $txt, '<', $i ); if ( false === $a ) break;
		$c = strpos( $txt, '>', $a ); if ( false === $c ) break;
		$et = substr( $txt, $a + 1, $c - $a - 1 ); $i = $c + 1;
		if ( 0 === strcasecmp( $et, 'eor' ) ) { if ( $act ) $regs[] = $act; $act = array(); continue; }
		$partes = explode( ':', $et );
		if ( count( $partes ) < 2 || ! ctype_digit( $partes[1] ) ) continue;
		$act[ strtolower( $partes[0] ) ] = substr( $txt, $i, (int) $partes[1] ); $i += (int) $partes[1];
	}
	if ( $act ) $regs[] = $act; // algunos programas no cierran el último registro
	return $regs;
}
function rau_libro_importar( $txt ) {
	$nuevos = 0; $repetidos = 0; $malos = 0;
	foreach ( rau_adif_leer( $txt ) as $r ) {
		$call = strtoupper( trim( $r['call'] ?? '' ) );
		$fecha = preg_replace( '/\D/', '', $r['qso_date'] ?? '' );
		if ( ! preg_match( '/^[A-Z0-9\/]{3,15}$/', $call ) || 8 !== strlen( $fecha ) ) { $malos++; continue; }
		$fecha = substr( $fecha, 0, 4 ) . '-' . substr( $fecha, 4, 2 ) . '-' . substr( $fecha, 6, 2 );
		$hora = preg_replace( '/\D/', '', $r['time_on'] ?? '' ); $hora = $hora ? substr( $hora, 0, 2 ) . ':' . substr( $hora, 2, 2 ) : '';
		$banda = strtolower( sanitize_text_field( $r['band'] ?? '' ) ); $modo = strtoupper( sanitize_text_field( $r['mode'] ?? '' ) );
		$clave = md5( implode( '|', array( $call, $fecha, $hora, $banda, $modo ) ) );
		if ( get_posts( array( 'post_type' => 'qso', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => 'adif_clave', 'meta_value' => $clave ) ) ) { $repetidos++; continue; }
		$id = wp_insert_post( array( 'post_type' => 'qso', 'post_status' => 'publish', 'post_title' => $call ) );
		if ( ! $id || is_wp_error( $id ) ) { $malos++; continue; }
		foreach ( array( 'fecha' => $fecha, 'hora' => $hora, 'banda' => $banda, 'modo' => $modo, 'frecuencia' => sanitize_text_field( $r['freq'] ?? '' ),
			'rst_env' => sanitize_text_field( $r['rst_sent'] ?? '' ), 'rst_rec' => sanitize_text_field( $r['rst_rcvd'] ?? '' ), 'adif_clave' => $clave ) as $k => $v ) if ( '' !== $v ) update_post_meta( $id, $k, $v );
		if ( 'Y' === strtoupper( $r['qsl_rcvd'] ?? '' ) || 'Y' === strtoupper( $r['lotw_qsl_rcvd'] ?? '' ) ) update_post_meta( $id, 'confirmado', '1' );
		$nuevos++;
	}
	return array( 'nuevos' => $nuevos, 'repetidos' => $repetidos, 'malos' => $malos );
}

/* Acciones del escritorio: exportar e importar (solo quien puede editar el libro) */
add_action( 'admin_post_rau_qso_adif', function() {
	if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'No autorizado.' );
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . RAU_KIWI_INDICATIVO . '-libro-' . gmdate( 'Ymd' ) . '.adi"' );
	echo rau_libro_adif(); exit;
} );
add_action( 'admin_post_rau_qso_importar', function() {
	if ( ! current_user_can( 'edit_posts' ) || ! check_admin_referer( 'rau_qso_importar' ) ) wp_die( 'No autorizado.' );
	$f = $_FILES['adif']['tmp_name'] ?? '';
	if ( ! $f || ! is_uploaded_file( $f ) || filesize( $f ) > 5 * MB_IN_BYTES ) wp_die( 'Archivo no válido (máximo 5 MB).' );
	$r = rau_libro_importar( (string) file_get_contents( $f ) );
	wp_safe_redirect( add_query_arg( array( 'rau_adif' => $r['nuevos'] . '-' . $r['repetidos'] . '-' . $r['malos'] ), wp_get_referer() ?: home_url( '/' ) ) ); exit;
} );
function rau_libro_herramientas() {
	if ( ! current_user_can( 'edit_posts' ) ) return;
	$res = isset( $_GET['rau_adif'] ) ? array_map( 'intval', explode( '-', sanitize_text_field( wp_unslash( $_GET['rau_adif'] ) ) ) ) : null;
	?>
	<div class="mb-6 rounded-2xl border border-border/70 bg-card p-5">
		<p class="font-mono text-xs uppercase tracking-[0.2em] text-primary">Solo para quien administra el libro</p>
		<?php if ( $res && 3 === count( $res ) ) : ?><p class="mt-2 text-sm text-emerald-400">Importados <?php echo (int) $res[0]; ?> contactos nuevos · <?php echo (int) $res[1]; ?> ya estaban<?php echo $res[2] ? ' · ' . (int) $res[2] . ' sin indicativo o fecha válidos' : ''; ?>.</p><?php endif; ?>
		<div class="mt-3 flex flex-wrap items-end gap-3">
			<a class="rounded-lg border border-primary/40 bg-primary/10 px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/20" href="<?php echo esc_url( admin_url( 'admin-post.php?action=rau_qso_adif' ) ); ?>">⬇ Exportar ADIF</a>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap items-center gap-2">
				<input type="hidden" name="action" value="rau_qso_importar"><?php wp_nonce_field( 'rau_qso_importar' ); ?>
				<label class="text-sm">Importar ADIF <input type="file" name="adif" accept=".adi,.adif,.txt" required class="text-sm"></label>
				<button class="rounded-lg border border-border px-3 py-2 text-sm">Importar</button>
			</form>
		</div>
		<p class="mt-3 text-xs text-muted-foreground">Para LoTW: exporta el ADIF, ábrelo con TQSL (el programa de la ARRL) con el certificado de <?php echo esc_html( RAU_KIWI_INDICATIVO ); ?>, firma y sube. LoTW no acepta archivos sin esa firma. QRZ y Log4OM importan el ADIF directamente.</p>
	</div>
	<?php
}

/* Diploma automático al confirmar un contacto */
function rau_qso_diploma( $id ) {
	if ( 'qso' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) || ! get_post_meta( $id, 'confirmado', true ) ) return 0;
	if ( get_post_meta( $id, 'diploma_id', true ) ) return (int) get_post_meta( $id, 'diploma_id', true );
	$call = strtoupper( get_the_title( $id ) ); $fecha = (string) get_post_meta( $id, 'fecha', true );
	$num = 'RAU-QSO-' . gmdate( 'Y' ) . '-' . strtoupper( substr( hash_hmac( 'sha256', $id . '|' . $call . '|' . $fecha, wp_salt( 'auth' ) ), 0, 8 ) );
	$d = wp_insert_post( array( 'post_type' => 'diploma', 'post_status' => 'publish', 'post_title' => 'Contacto con ' . RAU_KIWI_INDICATIVO . ' — ' . $call ) );
	if ( ! $d || is_wp_error( $d ) ) return 0;
	foreach ( array( 'numero' => $num, 'nombre' => $call, 'receptor' => $call, 'evento' => 'Contacto confirmado con ' . RAU_KIWI_INDICATIVO . ' el ' . $fecha . ' en ' . get_post_meta( $id, 'banda', true ) . ' ' . get_post_meta( $id, 'modo', true ),
		'fecha' => current_time( 'Y-m-d' ), 'tipo' => 'Contacto con ' . RAU_KIWI_INDICATIVO, 'qso_id' => $id ) as $k => $v ) update_post_meta( $d, $k, $v );
	update_post_meta( $id, 'diploma_id', $d );
	return $d;
}
add_action( 'updated_post_meta', function( $mid, $id, $clave, $valor ) { if ( 'confirmado' === $clave && $valor ) rau_qso_diploma( $id ); }, 10, 4 );
add_action( 'added_post_meta', function( $mid, $id, $clave, $valor ) { if ( 'confirmado' === $clave && $valor ) rau_qso_diploma( $id ); }, 10, 4 );
add_action( 'save_post_qso', function( $id ) { if ( ! wp_is_post_revision( $id ) ) rau_qso_diploma( $id ); }, 20 );
