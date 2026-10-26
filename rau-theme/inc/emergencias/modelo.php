<?php
/**
 * Centro de Mando de Emergencias — modelo de datos, roles, organizaciones, sesiones y auditoría.
 *
 * Una OPERACIÓN es una activación (o un simulacro): el contenedor que comparte un grupo de operadores.
 * Dentro de ella todo se guarda como EVENTOS que solo se agregan (incidentes, mensajes, estaciones,
 * recursos, plan…), con un identificador único creado en el dispositivo. El estado se calcula
 * recorriendo los eventos; nada se sobrescribe, así que la trazabilidad es completa.
 *
 * Acceso (todo se comprueba en el servidor, nada en el navegador):
 *  - Cuentas de WordPress con un rol del centro de mando y una organización: ven y operan las
 *    operaciones de su organización según su rol. El administrador del sitio ve todas.
 *  - Sesiones por dispositivo: quien recibe por radio el código y una clave de la operación abre una
 *    sesión con el rol que esa clave otorga (operador o coordinador; las invitaciones pueden dar
 *    observador o recursos). Cada sesión tiene su token aleatorio, guardado solo como hash, y se
 *    puede revocar una por una. Las claves se guardan con el hash de contraseñas de WordPress.
 *
 * La tabla de operaciones conserva el nombre de la versión 19.52.0 (rau_em_incidentes) para no migrar
 * datos: allí cada fila ya era una activación.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_EM_DB_VERSION', '2' );

/** Permisos por rol. «simulacion» opera, pero solo en simulacros. */
function rau_em_permisos_de_rol( $rol ) {
	$p = array(
		'observador'  => array( 'ver' ),
		'simulacion'  => array( 'ver', 'operar' ),
		'operador'    => array( 'ver', 'operar' ),
		'recursos'    => array( 'ver', 'operar', 'recursos' ),
		'coordinador' => array( 'ver', 'operar', 'recursos', 'coordinar' ),
		'admin'       => array( 'ver', 'operar', 'recursos', 'coordinar', 'admin' ),
	);
	return isset( $p[ $rol ] ) ? $p[ $rol ] : array();
}
function rau_em_roles_nombres() {
	return array( 'coordinador' => 'Coordinador', 'operador' => 'Operador de comunicaciones', 'recursos' => 'Responsable de recursos',
		'observador' => 'Observador', 'simulacion' => 'Usuario de simulación', 'admin' => 'Administrador' );
}

function rau_em_tablas() {
	global $wpdb;
	$p = $wpdb->prefix . 'rau_em_';
	return array( 'ops' => $p . 'incidentes', 'ev' => $p . 'eventos', 'ses' => $p . 'sesiones', 'aud' => $p . 'auditoria', 'org' => $p . 'orgs', 'inv' => $p . 'invitaciones' );
}

/* ── Migraciones ─────────────────────────────────────────────────────────── */
add_action( 'init', function() {
	if ( get_option( 'rau_em_db' ) === RAU_EM_DB_VERSION ) return;
	global $wpdb;
	$t  = rau_em_tablas();
	$cs = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( "CREATE TABLE {$t['ops']} (
		codigo varchar(12) NOT NULL,
		nombre varchar(120) NOT NULL,
		tipo varchar(10) NOT NULL DEFAULT 'real',
		zona varchar(120) NOT NULL DEFAULT '',
		creador varchar(20) NOT NULL DEFAULT '',
		clave_hash varchar(255) NOT NULL,
		clave_coord_hash varchar(255) NOT NULL DEFAULT '',
		org_id bigint(20) NOT NULL DEFAULT 0,
		publico tinyint(1) NOT NULL DEFAULT 1,
		estado varchar(10) NOT NULL DEFAULT 'abierto',
		creado bigint(20) NOT NULL,
		actividad bigint(20) NOT NULL,
		cerrado bigint(20) NOT NULL DEFAULT 0,
		retencion tinyint(1) NOT NULL DEFAULT 0,
		PRIMARY KEY  (codigo)
	) $cs;" );
	dbDelta( "CREATE TABLE {$t['ev']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		inc varchar(12) NOT NULL,
		uuid varchar(36) NOT NULL,
		tipo varchar(12) NOT NULL,
		autor varchar(40) NOT NULL DEFAULT '',
		rol varchar(12) NOT NULL DEFAULT '',
		sesion bigint(20) NOT NULL DEFAULT 0,
		usuario bigint(20) NOT NULL DEFAULT 0,
		ts_cli bigint(20) NOT NULL,
		ts_srv bigint(20) NOT NULL,
		datos text NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY uuid (uuid),
		KEY inc_id (inc,id)
	) $cs;" );
	dbDelta( "CREATE TABLE {$t['ses']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		op varchar(12) NOT NULL,
		token_hash char(64) NOT NULL,
		rol varchar(12) NOT NULL,
		indicativo varchar(20) NOT NULL DEFAULT '',
		nombre varchar(80) NOT NULL DEFAULT '',
		origen varchar(20) NOT NULL DEFAULT '',
		creado bigint(20) NOT NULL,
		ultimo bigint(20) NOT NULL,
		expira bigint(20) NOT NULL,
		revocado bigint(20) NOT NULL DEFAULT 0,
		ip_hash varchar(16) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		UNIQUE KEY token_hash (token_hash),
		KEY op (op)
	) $cs;" );
	dbDelta( "CREATE TABLE {$t['inv']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		op varchar(12) NOT NULL,
		rol varchar(12) NOT NULL,
		clave_hash varchar(255) NOT NULL,
		expira bigint(20) NOT NULL,
		usos_max int(11) NOT NULL DEFAULT 1,
		usos int(11) NOT NULL DEFAULT 0,
		creado bigint(20) NOT NULL,
		creado_por varchar(60) NOT NULL DEFAULT '',
		revocado bigint(20) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY op (op)
	) $cs;" );
	dbDelta( "CREATE TABLE {$t['aud']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		ts bigint(20) NOT NULL,
		op varchar(12) NOT NULL DEFAULT '',
		actor varchar(80) NOT NULL DEFAULT '',
		rol varchar(12) NOT NULL DEFAULT '',
		accion varchar(40) NOT NULL,
		objeto varchar(80) NOT NULL DEFAULT '',
		detalle text NOT NULL,
		ip_hash varchar(16) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY op_ts (op,ts)
	) $cs;" );
	dbDelta( "CREATE TABLE {$t['org']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		nombre varchar(120) NOT NULL,
		sigla varchar(20) NOT NULL DEFAULT '',
		creado bigint(20) NOT NULL,
		PRIMARY KEY  (id)
	) $cs;" );
	// Las operaciones de la 19.52.0 se crearon sin clave de coordinación: solo una cuenta las coordina
	update_option( 'rau_em_db', RAU_EM_DB_VERSION, false );
}, 5 );

/* ── Roles de WordPress ──────────────────────────────────────────────────── */
add_action( 'init', function() {
	if ( get_option( 'rau_em_roles' ) === RAU_EM_DB_VERSION ) return;
	foreach ( array( 'coordinador', 'operador', 'recursos', 'observador', 'simulacion' ) as $r ) {
		$n = rau_em_roles_nombres();
		if ( ! get_role( 'em_' . $r ) ) add_role( 'em_' . $r, 'Emergencias: ' . $n[ $r ], array( 'read' => true, 'rau_em_' . $r => true ) );
	}
	update_option( 'rau_em_roles', RAU_EM_DB_VERSION, false );
}, 6 );

/** Rol del centro de mando de un usuario de WordPress (el de más permisos si tiene varios). */
function rau_em_rol_de_usuario( $user ) {
	if ( ! $user || ! $user->exists() ) return '';
	if ( user_can( $user, 'manage_options' ) ) return 'admin';
	foreach ( array( 'coordinador', 'recursos', 'operador', 'simulacion', 'observador' ) as $r ) {
		if ( user_can( $user, 'rau_em_' . $r ) ) return $r;
	}
	return '';
}
function rau_em_org_de_usuario( $user_id ) { return (int) get_user_meta( $user_id, 'rau_em_org', true ); }

/* ── Utilidades ──────────────────────────────────────────────────────────── */
function rau_em_ip_hash() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return substr( hash( 'sha256', $ip . wp_salt( 'nonce' ) ), 0, 16 );
}
/** Cuenta intentos en una ventana; true si ya se pasó del límite. */
function rau_em_limite( $clave, $max, $segundos ) {
	// Solo en un entorno de pruebas (constante en wp-config.php local): las pruebas crean muchas
	// operaciones desde la misma IP. Nunca definirla en producción. No afecta el bloqueo por claves.
	if ( defined( 'RAU_EM_PRUEBAS' ) && RAU_EM_PRUEBAS ) return false;
	$k = 'rau_em_lim_' . md5( $clave );
	$n = (int) get_transient( $k );
	if ( $n >= $max ) return true;
	set_transient( $k, $n + 1, $segundos );
	return false;
}
function rau_em_indicativo( $s ) { return substr( strtoupper( preg_replace( '/[^A-Za-z0-9\/]/', '', (string) $s ) ), 0, 20 ); }
function rau_em_codigo( $s ) { return substr( strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $s ) ), 0, 12 ); }
function rau_em_operacion( $codigo ) {
	global $wpdb;
	$t = rau_em_tablas();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['ops']} WHERE codigo = %s", $codigo ), ARRAY_A );
}
function rau_em_org( $id ) {
	global $wpdb;
	if ( ! $id ) return null;
	$t = rau_em_tablas();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['org']} WHERE id = %d", $id ), ARRAY_A );
}
/** Lo que se puede mostrar de una operación a cualquiera: sin claves ni ubicaciones exactas. */
function rau_em_operacion_publica( $o ) {
	$org = rau_em_org( (int) $o['org_id'] );
	return array( 'codigo' => $o['codigo'], 'nombre' => $o['nombre'], 'tipo' => $o['tipo'], 'zona' => $o['zona'], 'creador' => $o['creador'],
		'estado' => $o['estado'], 'creado' => (int) $o['creado'], 'actividad' => (int) $o['actividad'],
		'org' => $org ? array( 'id' => (int) $org['id'], 'nombre' => $org['nombre'], 'sigla' => $org['sigla'] ) : null );
}
/** Código corto para dictar por radio: sin letras que se confunden (O/0, I/1). */
function rau_em_codigo_nuevo( $largo = 6 ) {
	$abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	$c   = '';
	for ( $i = 0; $i < $largo; $i++ ) $c .= $abc[ random_int( 0, strlen( $abc ) - 1 ) ];
	return $c;
}

/* ── Auditoría ───────────────────────────────────────────────────────────── */
function rau_em_auditar( $op, $actor, $accion, $objeto = '', $detalle = array() ) {
	global $wpdb;
	$t = rau_em_tablas();
	$wpdb->insert( $t['aud'], array( 'ts' => time(), 'op' => (string) $op, 'actor' => substr( (string) ( $actor['nombre'] ?? '' ), 0, 80 ), 'rol' => (string) ( $actor['rol'] ?? '' ),
		'accion' => substr( $accion, 0, 40 ), 'objeto' => substr( (string) $objeto, 0, 80 ), 'detalle' => wp_json_encode( $detalle ), 'ip_hash' => rau_em_ip_hash() ) );
}

/* ── Sesiones por dispositivo ────────────────────────────────────────────── */
function rau_em_crear_sesion( $op, $rol, $indicativo, $nombre, $origen ) {
	global $wpdb;
	$t     = rau_em_tablas();
	$token = bin2hex( random_bytes( 32 ) );
	$ahora = time();
	$wpdb->insert( $t['ses'], array( 'op' => $op, 'token_hash' => hash( 'sha256', $token ), 'rol' => $rol, 'indicativo' => $indicativo, 'nombre' => mb_substr( $nombre, 0, 80 ),
		'origen' => $origen, 'creado' => $ahora, 'ultimo' => $ahora, 'expira' => $ahora + 30 * DAY_IN_SECONDS, 'ip_hash' => rau_em_ip_hash() ) );
	return array( 'token' => $token, 'id' => (int) $wpdb->insert_id );
}
function rau_em_sesion_por_token( $token ) {
	global $wpdb;
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) return null;
	$t = rau_em_tablas();
	$s = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['ses']} WHERE token_hash = %s", hash( 'sha256', $token ) ), ARRAY_A );
	if ( ! $s || (int) $s['revocado'] || (int) $s['expira'] < time() ) return null;
	if ( time() - (int) $s['ultimo'] > 300 ) $wpdb->update( $t['ses'], array( 'ultimo' => time() ), array( 'id' => $s['id'] ) );
	return $s;
}

/**
 * Quién hace la petición y qué puede hacer en una operación. Devuelve null si no tiene acceso.
 * [ 'rol', 'permisos', 'nombre' (para la bitácora), 'sesion', 'usuario' ]
 */
function rau_em_actor( $op, WP_REST_Request $req = null ) {
	if ( ! $op ) return null;
	$sim = 'simulacro' === $op['tipo'];
	// 1. Sesión del dispositivo para esta operación
	$token = $req ? (string) $req->get_header( 'x_rau_em_token' ) : '';
	if ( $token ) {
		$s = rau_em_sesion_por_token( $token );
		if ( $s && $s['op'] === $op['codigo'] ) {
			return array( 'rol' => $s['rol'], 'permisos' => rau_em_permisos_de_rol( $s['rol'] ), 'nombre' => $s['indicativo'] ?: $s['nombre'], 'sesion' => (int) $s['id'], 'usuario' => 0 );
		}
	}
	// 2. Cuenta de WordPress con rol del centro de mando
	$u = wp_get_current_user();
	$rol = rau_em_rol_de_usuario( $u );
	if ( ! $rol ) return null;
	if ( 'simulacion' === $rol && ! $sim ) return null;
	if ( 'admin' !== $rol && ( ! (int) $op['org_id'] || rau_em_org_de_usuario( $u->ID ) !== (int) $op['org_id'] ) ) return null;
	$ind = get_user_meta( $u->ID, 'rau_em_indicativo', true );
	return array( 'rol' => $rol, 'permisos' => rau_em_permisos_de_rol( $rol ), 'nombre' => $ind ?: $u->user_login, 'sesion' => 0, 'usuario' => (int) $u->ID );
}
function rau_em_puede( $actor, $permiso ) { return $actor && in_array( $permiso, $actor['permisos'], true ); }
