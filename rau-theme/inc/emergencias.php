<?php
/**
 * Centro de Mando de Emergencias (/emergencias/) — servidor.
 *
 * La aplicación vive en el navegador y funciona sin internet: cada operador guarda en su teléfono
 * todo lo que registra. Cuando hay conexión, sincroniza con este servidor para que la estación de
 * control y los demás operadores del mismo incidente vean lo mismo.
 *
 * Diseño, a partir de lo que pasó en el sismo de magnitud 7,4 del 10 de agosto de 2026 (Chocó y Eje
 * Cafetero; la red de la Liga operó 124 horas y la red celular cayó en minutos):
 *  - Incidentes con clave de red: quien la tenga (la da el coordinador por radio) registra. Sin
 *    cuentas: en plena emergencia nadie va a recuperar una contraseña. Abierto a cualquier club del país.
 *  - Registros que solo se agregan (eventos con un identificador único creado en el teléfono): los de
 *    varios operadores se combinan sin conflictos, y un teléfono que estuvo horas sin señal entrega lo
 *    suyo al volver sin pisar lo de nadie.
 *  - La clave nunca se guarda: solo su hash. El teléfono guarda un token derivado de ella, que deja
 *    de servir si el incidente se cierra con otra clave.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_EM_DB_VERSION', '1' );
// Tipos de registro que acepta el servidor; cualquier otro se descarta
define( 'RAU_EM_TIPOS', 'checkin,contacto,sinresp,msg,msgest,nota,plan,planx,persona,personaact,recurso,recursox,repe,dir,dirx,inc' );

/* ── Tablas ─────────────────────────────────────────────────────────────── */
function rau_em_tablas() {
	global $wpdb;
	return array( $wpdb->prefix . 'rau_em_incidentes', $wpdb->prefix . 'rau_em_eventos' );
}
add_action( 'init', function() {
	if ( get_option( 'rau_em_db' ) === RAU_EM_DB_VERSION ) return;
	global $wpdb;
	list( $ti, $te ) = rau_em_tablas();
	$cs = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( "CREATE TABLE $ti (
		codigo varchar(12) NOT NULL,
		nombre varchar(120) NOT NULL,
		tipo varchar(10) NOT NULL DEFAULT 'real',
		zona varchar(120) NOT NULL DEFAULT '',
		creador varchar(20) NOT NULL DEFAULT '',
		clave_hash varchar(255) NOT NULL,
		estado varchar(10) NOT NULL DEFAULT 'abierto',
		creado bigint(20) NOT NULL,
		actividad bigint(20) NOT NULL,
		PRIMARY KEY  (codigo)
	) $cs;" );
	dbDelta( "CREATE TABLE $te (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		inc varchar(12) NOT NULL,
		uuid varchar(36) NOT NULL,
		tipo varchar(12) NOT NULL,
		autor varchar(20) NOT NULL DEFAULT '',
		ts_cli bigint(20) NOT NULL,
		ts_srv bigint(20) NOT NULL,
		datos text NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY uuid (uuid),
		KEY inc_id (inc,id)
	) $cs;" );
	update_option( 'rau_em_db', RAU_EM_DB_VERSION, false );
}, 5 );

/* ── Ruta /emergencias/ ─────────────────────────────────────────────────── */
add_action( 'init', function() {
	add_rewrite_rule( '^emergencias/?$', 'index.php?rau_em=1', 'top' );
} );
add_filter( 'query_vars', function( $v ) { $v[] = 'rau_em'; return $v; } );
add_action( 'template_redirect', function() {
	if ( ! get_query_var( 'rau_em' ) ) return;
	status_header( 200 );
	nocache_headers();
	include get_template_directory() . '/page-emergencias.php';
	exit;
} );

/* ── Utilidades ─────────────────────────────────────────────────────────── */
function rau_em_ip_hash() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return substr( md5( $ip . wp_salt( 'nonce' ) ), 0, 16 );
}
/** Cuenta intentos en una ventana; true si ya se pasó del límite. */
function rau_em_limite( $clave, $max, $segundos ) {
	$k = 'rau_em_lim_' . md5( $clave );
	$n = (int) get_transient( $k );
	if ( $n >= $max ) return true;
	set_transient( $k, $n + 1, $segundos );
	return false;
}
function rau_em_token( $codigo, $clave_hash ) {
	return hash_hmac( 'sha256', $codigo . '|' . $clave_hash, wp_salt( 'auth' ) );
}
function rau_em_indicativo( $s ) {
	$s = strtoupper( preg_replace( '/[^A-Za-z0-9\/]/', '', (string) $s ) );
	return substr( $s, 0, 20 );
}
function rau_em_incidente( $codigo ) {
	global $wpdb;
	list( $ti ) = rau_em_tablas();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $ti WHERE codigo = %s", $codigo ), ARRAY_A );
}
function rau_em_publico( $i ) {
	return array( 'codigo' => $i['codigo'], 'nombre' => $i['nombre'], 'tipo' => $i['tipo'], 'zona' => $i['zona'],
		'creador' => $i['creador'], 'estado' => $i['estado'], 'creado' => (int) $i['creado'], 'actividad' => (int) $i['actividad'] );
}
/** Limpia los datos de un registro: solo texto plano, números y booleanos, con tope de largo. */
function rau_em_limpiar( $d, $prof = 0 ) {
	if ( $prof > 2 || ! is_array( $d ) ) return array();
	$out = array();
	foreach ( array_slice( $d, 0, 40, true ) as $k => $v ) {
		$k = substr( preg_replace( '/[^a-z0-9_]/i', '', (string) $k ), 0, 20 );
		if ( '' === $k ) continue;
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) $out[ $k ] = $v;
		elseif ( is_string( $v ) ) $out[ $k ] = mb_substr( sanitize_textarea_field( $v ), 0, 2000 );
		elseif ( is_array( $v ) ) $out[ $k ] = rau_em_limpiar( $v, $prof + 1 );
	}
	return $out;
}
function rau_em_post( $k ) {
	return isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
}

/* ── Incidentes abiertos (solo nombre y zona: los registros piden clave) ── */
add_action( 'wp_ajax_rau_em_lista', 'rau_em_lista' );
add_action( 'wp_ajax_nopriv_rau_em_lista', 'rau_em_lista' );
function rau_em_lista() {
	global $wpdb;
	list( $ti ) = rau_em_tablas();
	$desde = time() - 14 * DAY_IN_SECONDS;
	$filas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $ti WHERE estado = 'abierto' AND actividad > %d ORDER BY actividad DESC LIMIT 30", $desde ), ARRAY_A );
	wp_send_json_success( array_map( 'rau_em_publico', $filas ?: array() ) );
}

/* ── Crear un incidente o simulacro ─────────────────────────────────────── */
add_action( 'wp_ajax_rau_em_crear', 'rau_em_crear' );
add_action( 'wp_ajax_nopriv_rau_em_crear', 'rau_em_crear' );
function rau_em_crear() {
	if ( rau_em_limite( 'crear' . rau_em_ip_hash(), 6, DAY_IN_SECONDS ) ) wp_send_json_error( 'Demasiados incidentes creados desde esta conexión hoy.' );
	$nombre = mb_substr( sanitize_text_field( rau_em_post( 'nombre' ) ), 0, 120 );
	$zona   = mb_substr( sanitize_text_field( rau_em_post( 'zona' ) ), 0, 120 );
	$tipo   = 'simulacro' === rau_em_post( 'tipo' ) ? 'simulacro' : 'real';
	$ind    = rau_em_indicativo( rau_em_post( 'indicativo' ) );
	$clave  = (string) rau_em_post( 'clave' );
	if ( mb_strlen( $nombre ) < 3 ) wp_send_json_error( 'Ponle un nombre al incidente.' );
	if ( strlen( $ind ) < 3 ) wp_send_json_error( 'Falta tu indicativo.' );
	if ( mb_strlen( $clave ) < 4 || mb_strlen( $clave ) > 64 ) wp_send_json_error( 'La clave de red debe tener entre 4 y 64 caracteres.' );
	global $wpdb;
	list( $ti ) = rau_em_tablas();
	// Código corto, fácil de dictar por radio: sin letras que se confunden (O/0, I/1)
	$abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	do {
		$codigo = '';
		for ( $i = 0; $i < 6; $i++ ) $codigo .= $abc[ random_int( 0, strlen( $abc ) - 1 ) ];
	} while ( rau_em_incidente( $codigo ) );
	$hash = wp_hash_password( $clave );
	$ahora = time();
	$ok = $wpdb->insert( $ti, array( 'codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo, 'zona' => $zona, 'creador' => $ind,
		'clave_hash' => $hash, 'estado' => 'abierto', 'creado' => $ahora, 'actividad' => $ahora ) );
	if ( ! $ok ) wp_send_json_error( 'No se pudo crear el incidente. Intenta de nuevo.' );
	$i = rau_em_incidente( $codigo );
	wp_send_json_success( array( 'incidente' => rau_em_publico( $i ), 'token' => rau_em_token( $codigo, $hash ) ) );
}

/* ── Unirse con la clave de red ─────────────────────────────────────────── */
add_action( 'wp_ajax_rau_em_entrar', 'rau_em_entrar' );
add_action( 'wp_ajax_nopriv_rau_em_entrar', 'rau_em_entrar' );
function rau_em_entrar() {
	$codigo = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) rau_em_post( 'codigo' ) ) );
	if ( rau_em_limite( 'entrar' . rau_em_ip_hash(), 20, HOUR_IN_SECONDS ) ) wp_send_json_error( 'Demasiados intentos. Espera unos minutos.' );
	$i = rau_em_incidente( $codigo );
	if ( ! $i ) wp_send_json_error( 'No existe un incidente con ese código.' );
	if ( ! wp_check_password( (string) rau_em_post( 'clave' ), $i['clave_hash'] ) ) wp_send_json_error( 'La clave de red no coincide.' );
	wp_send_json_success( array( 'incidente' => rau_em_publico( $i ), 'token' => rau_em_token( $i['codigo'], $i['clave_hash'] ) ) );
}

/* ── Sincronizar: entrega lo pendiente del teléfono y trae lo nuevo de los demás ── */
add_action( 'wp_ajax_rau_em_sync', 'rau_em_sync' );
add_action( 'wp_ajax_nopriv_rau_em_sync', 'rau_em_sync' );
function rau_em_sync() {
	global $wpdb;
	list( $ti, $te ) = rau_em_tablas();
	$codigo = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) rau_em_post( 'codigo' ) ) );
	$i = rau_em_incidente( $codigo );
	if ( ! $i || ! hash_equals( rau_em_token( $i['codigo'], $i['clave_hash'] ), (string) rau_em_post( 'token' ) ) ) {
		wp_send_json_error( array( 'auth' => true, 'msg' => 'Sin acceso a este incidente. Vuelve a entrar con la clave de red.' ) );
	}
	$tipos = explode( ',', RAU_EM_TIPOS );
	$nuevos = json_decode( (string) rau_em_post( 'eventos' ), true );
	$guardados = array();
	$cerrar = false;
	if ( is_array( $nuevos ) && $nuevos ) {
		if ( rau_em_limite( 'sync' . rau_em_ip_hash(), 3000, HOUR_IN_SECONDS ) ) wp_send_json_error( array( 'msg' => 'Demasiados registros desde esta conexión.' ) );
		foreach ( array_slice( $nuevos, 0, 200 ) as $e ) {
			if ( ! is_array( $e ) ) continue;
			$uuid = isset( $e['u'] ) ? (string) $e['u'] : '';
			$tipo = isset( $e['t'] ) ? (string) $e['t'] : '';
			if ( ! preg_match( '/^[a-z0-9-]{8,36}$/', $uuid ) || ! in_array( $tipo, $tipos, true ) ) continue;
			$datos = wp_json_encode( rau_em_limpiar( isset( $e['d'] ) ? $e['d'] : array() ) );
			if ( strlen( $datos ) > 8000 ) continue;
			$ts = isset( $e['ts'] ) ? (int) $e['ts'] : 0;
			// Hora del teléfono, salvo que sea absurda (reloj sin configurar tras quedarse sin batería)
			if ( $ts < ( time() - 30 * DAY_IN_SECONDS ) * 1000 || $ts > ( time() + DAY_IN_SECONDS ) * 1000 ) $ts = time() * 1000;
			$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $te (inc, uuid, tipo, autor, ts_cli, ts_srv, datos) VALUES (%s, %s, %s, %s, %d, %d, %s)",
				$i['codigo'], $uuid, $tipo, rau_em_indicativo( isset( $e['a'] ) ? $e['a'] : '' ), $ts, time(), $datos ) );
			$guardados[] = $uuid;
			if ( 'inc' === $tipo && isset( $e['d']['estado'] ) && 'cerrado' === $e['d']['estado'] ) $cerrar = true;
		}
		$wpdb->update( $ti, $cerrar ? array( 'actividad' => time(), 'estado' => 'cerrado' ) : array( 'actividad' => time() ), array( 'codigo' => $i['codigo'] ) );
	}
	$desde = max( 0, (int) rau_em_post( 'desde' ) );
	$filas = $wpdb->get_results( $wpdb->prepare( "SELECT id, uuid, tipo, autor, ts_cli, datos FROM $te WHERE inc = %s AND id > %d ORDER BY id ASC LIMIT 500", $i['codigo'], $desde ), ARRAY_A );
	$out = array();
	foreach ( $filas ?: array() as $f ) {
		$out[] = array( 's' => (int) $f['id'], 'u' => $f['uuid'], 't' => $f['tipo'], 'a' => $f['autor'], 'ts' => (int) $f['ts_cli'], 'd' => json_decode( $f['datos'], true ) ?: new stdClass() );
	}
	$i = rau_em_incidente( $i['codigo'] );
	wp_send_json_success( array( 'incidente' => rau_em_publico( $i ), 'guardados' => $guardados, 'eventos' => $out, 'mas' => count( $out ) >= 500, 'hora' => time() ) );
}

/* ── Alertas: sismos (USGS) y desastres (GDACS) en Colombia y alrededores ── */
function rau_em_alertas() {
	$c = get_transient( 'rau_em_alertas' );
	if ( false !== $c ) return $c;
	$out = array( 'sismos' => null, 'gdacs' => null, 'actualizado' => time() );
	// El feed del Servicio Geológico Colombiano no es público (responde acceso denegado); el USGS cubre
	// los sismos de Colombia con magnitud 2,5 o más
	$r = rau_remote_get( add_query_arg( array( 'format' => 'geojson', 'minlatitude' => -4.5, 'maxlatitude' => 13.5, 'minlongitude' => -82, 'maxlongitude' => -66,
		'minmagnitude' => 3.5, 'starttime' => gmdate( 'Y-m-d', time() - 7 * DAY_IN_SECONDS ), 'orderby' => 'time', 'limit' => 40 ), 'https://earthquake.usgs.gov/fdsnws/event/1/query' ), array( 'timeout' => 15 ), 5 );
	if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
		$j = json_decode( wp_remote_retrieve_body( $r ), true );
		$out['sismos'] = array();
		foreach ( isset( $j['features'] ) ? $j['features'] : array() as $f ) {
			$p = $f['properties']; $g = $f['geometry']['coordinates'];
			$out['sismos'][] = array( 'm' => round( (float) $p['mag'], 1 ), 'lugar' => sanitize_text_field( $p['place'] ), 't' => (int) ( $p['time'] / 1000 ),
				'lat' => round( (float) $g[1], 3 ), 'lng' => round( (float) $g[0], 3 ), 'prof' => round( (float) $g[2] ), 'url' => esc_url_raw( $p['url'] ) );
		}
	}
	$r = rau_remote_get( 'https://www.gdacs.org/gdacsapi/api/events/geteventlist/SEARCH?country=COL', array( 'timeout' => 15 ), 10 );
	if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
		$j = json_decode( wp_remote_retrieve_body( $r ), true );
		$out['gdacs'] = array();
		$tipos = array( 'EQ' => 'Sismo', 'TC' => 'Ciclón tropical', 'FL' => 'Inundación', 'VO' => 'Volcán', 'DR' => 'Sequía', 'WF' => 'Incendio forestal', 'TS' => 'Tsunami' );
		foreach ( isset( $j['features'] ) ? $j['features'] : array() as $f ) {
			$p = $f['properties'];
			$fin = isset( $p['todate'] ) ? strtotime( $p['todate'] ) : 0;
			if ( $fin && $fin < time() - 60 * DAY_IN_SECONDS ) continue;
			$out['gdacs'][] = array( 'tipo' => isset( $tipos[ $p['eventtype'] ] ) ? $tipos[ $p['eventtype'] ] : sanitize_text_field( $p['eventtype'] ),
				'nivel' => sanitize_key( $p['alertlevel'] ), 'nombre' => sanitize_text_field( $p['name'] ), 'desde' => isset( $p['fromdate'] ) ? strtotime( $p['fromdate'] ) : 0,
				'url' => isset( $p['url']['report'] ) ? esc_url_raw( $p['url']['report'] ) : '' );
		}
	}
	if ( null === $out['sismos'] && null === $out['gdacs'] ) {
		$u = get_option( 'rau_em_alertas_ultimo' );
		if ( $u ) { $u['viejo'] = true; set_transient( 'rau_em_alertas', $u, 2 * MINUTE_IN_SECONDS ); return $u; }
		return null;
	}
	update_option( 'rau_em_alertas_ultimo', $out, false );
	set_transient( 'rau_em_alertas', $out, 5 * MINUTE_IN_SECONDS );
	return $out;
}
add_action( 'wp_ajax_rau_em_alertas', 'rau_em_alertas_endpoint' );
add_action( 'wp_ajax_nopriv_rau_em_alertas', 'rau_em_alertas_endpoint' );
function rau_em_alertas_endpoint() {
	$a = rau_em_alertas();
	$o = rau_sdr_oido();
	$bandas = array();
	if ( $o ) foreach ( $o['bandas'] as $b => $x ) $bandas[ $b ] = array( 'n' => $x['n'], 'lejos' => $x['lejos'] ? $x['lejos']['km'] : 0 );
	wp_send_json_success( array( 'alertas' => $a, 'oido' => $o ? array( 'bandas' => $bandas, 'vigiladas' => $o['vigiladas'], 'actualizado' => $o['actualizado'], 'lugar' => $o['rx']['lugar'] ) : null ) );
}

/* ── Administración: ver y borrar incidentes (por si alguien abusa) ─────── */
add_action( 'admin_menu', function() {
	add_management_page( 'Incidentes de emergencia', 'Incidentes de emergencia', 'manage_options', 'rau-em', 'rau_em_admin' );
} );
function rau_em_admin() {
	global $wpdb;
	list( $ti, $te ) = rau_em_tablas();
	if ( isset( $_POST['rau_em_borrar'] ) && check_admin_referer( 'rau_em_borrar' ) ) {
		$c = sanitize_text_field( wp_unslash( $_POST['rau_em_borrar'] ) );
		$wpdb->delete( $te, array( 'inc' => $c ) );
		$wpdb->delete( $ti, array( 'codigo' => $c ) );
		echo '<div class="notice notice-success"><p>Incidente ' . esc_html( $c ) . ' borrado con todos sus registros.</p></div>';
	}
	$filas = $wpdb->get_results( "SELECT i.*, (SELECT COUNT(*) FROM $te e WHERE e.inc = i.codigo) AS n FROM $ti i ORDER BY actividad DESC LIMIT 200", ARRAY_A );
	echo '<div class="wrap"><h1>Incidentes de emergencia</h1><p>Creados desde <a href="' . esc_url( home_url( '/emergencias/' ) ) . '">/emergencias/</a>. Los registros solo los ve quien tiene la clave de red.</p>';
	echo '<table class="widefat striped"><thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Zona</th><th>Creó</th><th>Estado</th><th>Registros</th><th>Última actividad</th><th></th></tr></thead><tbody>';
	foreach ( $filas ?: array() as $f ) {
		echo '<tr><td><code>' . esc_html( $f['codigo'] ) . '</code></td><td>' . esc_html( $f['nombre'] ) . '</td><td>' . esc_html( $f['tipo'] ) . '</td><td>' . esc_html( $f['zona'] ) . '</td><td>' . esc_html( $f['creador'] ) . '</td><td>' . esc_html( $f['estado'] ) . '</td><td>' . (int) $f['n'] . '</td><td>' . esc_html( wp_date( 'Y-m-d H:i', (int) $f['actividad'] ) ) . '</td><td>';
		echo '<form method="post" onsubmit="return confirm(\'¿Borrar el incidente y todos sus registros? No se puede deshacer.\')">';
		wp_nonce_field( 'rau_em_borrar' );
		echo '<button class="button button-link-delete" name="rau_em_borrar" value="' . esc_attr( $f['codigo'] ) . '">Borrar</button></form></td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * Frecuencias nacionales de emergencia. Son las que la Liga Colombiana de Radioaficionados activó y
 * liberó en el sismo del 10 de agosto de 2026, según los reportes de la coordinación de emergencias
 * de la IARU Región 2 (CO2JC) publicados por la ARRL y la IARU Región 1. Son centros de actividad:
 * el tráfico puede estar hasta ±20 kHz alrededor. Ojo: en 80 m es 3985 kHz, no 3750.
 */
function rau_em_frecuencias() {
	return array(
		array( 'f' => '7.060',   'b' => '40 m', 'm' => 'LSB', 'uso' => 'Red nacional, la principal. De día cubre Colombia por onda de cielo (NVIS).' ),
		array( 'f' => '7.240',   'b' => '40 m', 'm' => 'LSB', 'uso' => 'Red nacional, alterna.' ),
		array( 'f' => '7.275',   'b' => '40 m', 'm' => 'LSB', 'uso' => 'Red nacional, alterna.' ),
		array( 'f' => '3.985',   'b' => '80 m', 'm' => 'LSB', 'uso' => 'Red nacional de noche, cuando 40 m se va lejos y deja de oírse cerca.' ),
		array( 'f' => '14.300',  'b' => '20 m', 'm' => 'USB', 'uso' => 'Enlace internacional (IARU Región 2).' ),
		array( 'f' => '18.160',  'b' => '17 m', 'm' => 'USB', 'uso' => 'Enlace internacional, alterna de 20 m.' ),
		array( 'f' => '21.360',  'b' => '15 m', 'm' => 'USB', 'uso' => 'Enlace internacional de día.' ),
		array( 'f' => '146.520', 'b' => '2 m',  'm' => 'FM',  'uso' => 'Símplex de llamada: cuando no hay repetidora.' ),
	);
}
/** Repetidoras que se usaron en el Eje Cafetero en agosto de 2026 (tono y desplazamiento: confirmar en la zona). */
function rau_em_repetidoras_reportadas() {
	return array(
		array( 'f' => '147.120', 'n' => 'Cerro El Castillo (Armenia – Pereira)' ),
		array( 'f' => '146.910', 'n' => 'Las Cruces, Risaralda' ),
		array( 'f' => '146.930', 'n' => 'Las Cruces, Risaralda' ),
		array( 'f' => '146.880', 'n' => 'Eje Cafetero (repetidora nueva, 2026)' ),
	);
}
