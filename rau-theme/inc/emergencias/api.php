<?php
/**
 * API REST del centro de mando: /wp-json/rau-em/v1/…  (documentada en EMERGENCIAS.md)
 *
 * Autenticación: cuenta de WordPress (cookie + cabecera X-WP-Nonce) o sesión de dispositivo
 * (cabecera X-RAU-EM-Token). Todas las respuestas llevan «no-store»: ni el navegador ni LiteSpeed
 * deben guardar datos de una operación.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function() {
	$ns  = 'rau-em/v1';
	$pub = '__return_true'; // el permiso real se comprueba dentro, por operación
	$cod = '(?P<cod>[A-Za-z0-9]{4,12})';
	register_rest_route( $ns, '/yo', array( 'methods' => 'GET', 'callback' => 'rau_em_api_yo', 'permission_callback' => $pub ) );
	register_rest_route( $ns, '/alertas', array( 'methods' => 'GET', 'callback' => function() { return rau_em_alertas_publicas(); }, 'permission_callback' => $pub ) );
	register_rest_route( $ns, '/directorio', array(
		array( 'methods' => 'GET', 'callback' => 'rau_em_api_directorio', 'permission_callback' => $pub ),
		array( 'methods' => 'POST', 'callback' => 'rau_em_api_directorio_guardar', 'permission_callback' => $pub ),
	) );
	register_rest_route( $ns, '/directorio/(?P<id>[a-z0-9-]{3,40})', array( 'methods' => 'DELETE', 'callback' => 'rau_em_api_directorio_borrar', 'permission_callback' => $pub ) );
	register_rest_route( $ns, '/operaciones', array(
		array( 'methods' => 'GET', 'callback' => 'rau_em_api_lista', 'permission_callback' => $pub ),
		array( 'methods' => 'POST', 'callback' => 'rau_em_api_crear', 'permission_callback' => $pub ),
	) );
	register_rest_route( $ns, "/operaciones/$cod", array( 'methods' => 'GET', 'callback' => 'rau_em_api_operacion', 'permission_callback' => $pub ) );
	register_rest_route( $ns, "/operaciones/$cod/sesion", array( 'methods' => 'POST', 'callback' => 'rau_em_api_sesion', 'permission_callback' => $pub ) );
	register_rest_route( $ns, "/operaciones/$cod/salir", array( 'methods' => 'POST', 'callback' => 'rau_em_api_salir', 'permission_callback' => $pub ) );
	register_rest_route( $ns, "/operaciones/$cod/eventos", array(
		array( 'methods' => 'GET', 'callback' => 'rau_em_api_eventos', 'permission_callback' => $pub ),
		array( 'methods' => 'POST', 'callback' => 'rau_em_api_registrar', 'permission_callback' => $pub ),
	) );
	register_rest_route( $ns, "/operaciones/$cod/sesiones", array( 'methods' => 'GET', 'callback' => 'rau_em_api_sesiones', 'permission_callback' => $pub ) );
	register_rest_route( $ns, "/operaciones/$cod/sesiones/(?P<id>\d+)/revocar", array( 'methods' => 'POST', 'callback' => 'rau_em_api_revocar', 'permission_callback' => $pub ) );
	register_rest_route( $ns, "/operaciones/$cod/claves", array( 'methods' => 'POST', 'callback' => 'rau_em_api_claves', 'permission_callback' => $pub ) );
	register_rest_route( $ns, "/operaciones/$cod/invitaciones", array( 'methods' => 'POST', 'callback' => 'rau_em_api_invitar', 'permission_callback' => $pub ) );
	register_rest_route( $ns, "/operaciones/$cod/auditoria", array( 'methods' => 'GET', 'callback' => 'rau_em_api_auditoria', 'permission_callback' => $pub ) );
	register_rest_route( $ns, "/operaciones/$cod/respaldo", array( 'methods' => 'GET', 'callback' => 'rau_em_api_respaldo', 'permission_callback' => $pub ) );
} );

// Sin caché para todo lo del centro de mando
add_filter( 'rest_post_dispatch', function( $res, $server, $req ) {
	if ( 0 === strpos( $req->get_route(), '/rau-em/' ) ) {
		$res->header( 'Cache-Control', 'no-store, private' );
		$res->header( 'X-LiteSpeed-Cache-Control', 'no-cache' );
		$res->header( 'X-Content-Type-Options', 'nosniff' );
	}
	return $res;
}, 10, 3 );

function rau_em_error( $msg, $estado = 400, $extra = array() ) {
	return new WP_REST_Response( array_merge( array( 'error' => $msg ), $extra ), $estado );
}
/** Operación y actor de la petición, o la respuesta de error que corresponda. */
function rau_em_contexto( WP_REST_Request $req, $permiso = 'ver' ) {
	$op = rau_em_operacion( rau_em_codigo( $req['cod'] ) );
	if ( ! $op ) return rau_em_error( 'No existe esa operación', 404 );
	$a = rau_em_actor( $op, $req );
	if ( ! $a ) return rau_em_error( 'Sin acceso a esta operación. Entra con el código y una clave, o con una cuenta autorizada.', 401, array( 'auth' => true ) );
	if ( ! rau_em_puede( $a, $permiso ) ) return rau_em_error( 'Tu rol no permite esta acción', 403 );
	return array( $op, $a );
}
function rau_em_meta_actor( $a ) {
	return array( 'rol' => $a['rol'], 'permisos' => $a['permisos'], 'nombre' => $a['nombre'], 'cuenta' => (bool) $a['usuario'] );
}

/* ── Identidad ───────────────────────────────────────────────────────────── */
function rau_em_api_yo() {
	$u = wp_get_current_user();
	$rol = rau_em_rol_de_usuario( $u );
	$org = $rol ? rau_em_org( rau_em_org_de_usuario( $u->ID ) ) : null;
	return array( 'usuario' => $rol ? array( 'login' => $u->user_login, 'rol' => $rol, 'indicativo' => get_user_meta( $u->ID, 'rau_em_indicativo', true ), 'org' => $org ? array( 'id' => (int) $org['id'], 'nombre' => $org['nombre'], 'sigla' => $org['sigla'] ) : null ) : null,
		'roles' => rau_em_roles_nombres() );
}

/* ── Operaciones ─────────────────────────────────────────────────────────── */
function rau_em_api_lista() {
	global $wpdb;
	$t = rau_em_tablas();
	$desde = time() - 14 * DAY_IN_SECONDS;
	// Lista pública: solo operaciones abiertas de una organización verificada por el administrador.
	// Una operación sin organización existe y funciona, pero solo se entra con su código.
	$filas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['ops']} WHERE estado = 'abierto' AND publico = 1 AND org_id > 0 AND actividad > %d ORDER BY actividad DESC LIMIT 40", $desde ), ARRAY_A );
	$out = array_map( 'rau_em_operacion_publica', $filas ?: array() );
	// Las de la organización de quien tiene cuenta, aunque no sean públicas
	$u = wp_get_current_user();
	$rol = rau_em_rol_de_usuario( $u );
	if ( $rol ) {
		$q = 'admin' === $rol ? "SELECT * FROM {$t['ops']} ORDER BY actividad DESC LIMIT 100"
			: $wpdb->prepare( "SELECT * FROM {$t['ops']} WHERE org_id = %d ORDER BY actividad DESC LIMIT 100", rau_em_org_de_usuario( $u->ID ) );
		$ya = wp_list_pluck( $out, 'codigo' );
		foreach ( $wpdb->get_results( $q, ARRAY_A ) ?: array() as $f ) {
			if ( 'simulacion' === $rol && 'simulacro' !== $f['tipo'] ) continue;
			if ( in_array( $f['codigo'], $ya, true ) ) continue;
			$x = rau_em_operacion_publica( $f ); $x['mia'] = true; $out[] = $x;
		}
	}
	return $out;
}

function rau_em_api_crear( WP_REST_Request $req ) {
	$u   = wp_get_current_user();
	$rol = rau_em_rol_de_usuario( $u );
	$tipo = 'simulacro' === $req['tipo'] ? 'simulacro' : 'real';
	if ( 'simulacion' === $rol && 'real' === $tipo ) return rau_em_error( 'Un usuario de simulación solo puede crear simulacros', 403 );
	if ( ! in_array( $rol, array( 'admin', 'coordinador' ), true ) && rau_em_limite( 'crear' . rau_em_ip_hash(), 6, DAY_IN_SECONDS ) ) return rau_em_error( 'Demasiadas operaciones creadas desde esta conexión hoy', 429 );
	$nombre = mb_substr( sanitize_text_field( (string) $req['nombre'] ), 0, 120 );
	$zona   = mb_substr( sanitize_text_field( (string) $req['zona'] ), 0, 120 );
	$ind    = rau_em_indicativo( $req['indicativo'] );
	$clave  = (string) $req['clave'];
	$ccoord = (string) $req['clave_coord'];
	if ( mb_strlen( $nombre ) < 3 ) return rau_em_error( 'Ponle un nombre a la operación' );
	if ( strlen( $ind ) < 3 ) return rau_em_error( 'Falta tu indicativo' );
	if ( mb_strlen( $clave ) < 6 || mb_strlen( $clave ) > 64 ) return rau_em_error( 'La clave de operadores debe tener entre 6 y 64 caracteres' );
	if ( mb_strlen( $ccoord ) < 8 || mb_strlen( $ccoord ) > 64 ) return rau_em_error( 'La clave de coordinación debe tener entre 8 y 64 caracteres' );
	if ( $clave === $ccoord ) return rau_em_error( 'La clave de coordinación debe ser distinta de la de operadores' );
	$org = 0;
	if ( 'admin' === $rol ) $org = (int) $req['org_id'];
	elseif ( $rol ) $org = rau_em_org_de_usuario( $u->ID );
	global $wpdb;
	$t = rau_em_tablas();
	do { $codigo = rau_em_codigo_nuevo(); } while ( rau_em_operacion( $codigo ) );
	$ahora = time();
	$ok = $wpdb->insert( $t['ops'], array( 'codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo, 'zona' => $zona, 'creador' => $ind,
		'clave_hash' => wp_hash_password( $clave ), 'clave_coord_hash' => wp_hash_password( $ccoord ), 'org_id' => $org, 'publico' => 1,
		'estado' => 'abierto', 'creado' => $ahora, 'actividad' => $ahora ) );
	if ( ! $ok ) return rau_em_error( 'No se pudo crear la operación', 500 );
	$ses = rau_em_crear_sesion( $codigo, 'coordinador', $ind, '', 'creacion' );
	$actor = array( 'nombre' => $ind, 'rol' => 'coordinador' );
	rau_em_auditar( $codigo, $actor, 'operacion_creada', $codigo, array( 'tipo' => $tipo, 'org' => $org, 'cuenta' => $u->exists() ? $u->user_login : '' ) );
	$op = rau_em_operacion( $codigo );
	return array( 'operacion' => rau_em_operacion_publica( $op ), 'token' => $ses['token'], 'actor' => array( 'rol' => 'coordinador', 'permisos' => rau_em_permisos_de_rol( 'coordinador' ), 'nombre' => $ind, 'cuenta' => false ) );
}

function rau_em_api_operacion( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op, $a ) = $c;
	return array( 'operacion' => rau_em_operacion_publica( $op ), 'actor' => rau_em_meta_actor( $a ) );
}

function rau_em_api_sesion( WP_REST_Request $req ) {
	global $wpdb;
	$t  = rau_em_tablas();
	$op = rau_em_operacion( rau_em_codigo( $req['cod'] ) );
	$ip = rau_em_ip_hash();
	if ( rau_em_limite( 'ses' . $ip, 30, HOUR_IN_SECONDS ) ) return rau_em_error( 'Demasiados intentos desde esta conexión. Espera una hora.', 429 );
	if ( ! $op ) return rau_em_error( 'No existe una operación con ese código', 404 );
	if ( (int) get_transient( 'rau_em_fallos_' . $op['codigo'] . $ip ) >= 8 ) return rau_em_error( 'Demasiadas claves equivocadas para esta operación. Espera una hora.', 429 );
	$clave = (string) $req['clave'];
	$ind   = rau_em_indicativo( $req['indicativo'] );
	if ( strlen( $ind ) < 3 ) return rau_em_error( 'Falta tu indicativo' );
	$rol = ''; $origen = '';
	if ( $op['clave_coord_hash'] && wp_check_password( $clave, $op['clave_coord_hash'] ) ) { $rol = 'coordinador'; $origen = 'clave_coordinacion'; }
	elseif ( wp_check_password( $clave, $op['clave_hash'] ) ) { $rol = 'operador'; $origen = 'clave_operadores'; }
	else {
		$invs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['inv']} WHERE op = %s AND revocado = 0 AND expira > %d AND usos < usos_max", $op['codigo'], time() ), ARRAY_A );
		foreach ( $invs ?: array() as $inv ) {
			if ( wp_check_password( $clave, $inv['clave_hash'] ) ) {
				$rol = $inv['rol']; $origen = 'invitacion:' . $inv['id'];
				$wpdb->query( $wpdb->prepare( "UPDATE {$t['inv']} SET usos = usos + 1 WHERE id = %d", $inv['id'] ) );
				break;
			}
		}
	}
	if ( ! $rol ) {
		$k = 'rau_em_fallos_' . $op['codigo'] . $ip;
		set_transient( $k, (int) get_transient( $k ) + 1, HOUR_IN_SECONDS );
		rau_em_auditar( $op['codigo'], array( 'nombre' => $ind, 'rol' => '' ), 'clave_fallida', $ind );
		return rau_em_error( 'La clave no coincide', 403 );
	}
	$ses = rau_em_crear_sesion( $op['codigo'], $rol, $ind, mb_substr( sanitize_text_field( (string) $req['nombre'] ), 0, 80 ), $origen );
	rau_em_auditar( $op['codigo'], array( 'nombre' => $ind, 'rol' => $rol ), 'sesion_abierta', 'ses:' . $ses['id'], array( 'origen' => $origen ) );
	return array( 'operacion' => rau_em_operacion_publica( $op ), 'token' => $ses['token'], 'actor' => array( 'rol' => $rol, 'permisos' => rau_em_permisos_de_rol( $rol ), 'nombre' => $ind, 'cuenta' => false ) );
}

function rau_em_api_salir( WP_REST_Request $req ) {
	global $wpdb;
	$t = rau_em_tablas();
	$s = rau_em_sesion_por_token( (string) $req->get_header( 'x_rau_em_token' ) );
	if ( $s ) {
		$wpdb->update( $t['ses'], array( 'revocado' => time() ), array( 'id' => $s['id'] ) );
		rau_em_auditar( $s['op'], array( 'nombre' => $s['indicativo'], 'rol' => $s['rol'] ), 'sesion_cerrada', 'ses:' . $s['id'] );
	}
	return array( 'ok' => true );
}

/* ── Registros ───────────────────────────────────────────────────────────── */
function rau_em_api_eventos( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op, $a ) = $c;
	global $wpdb;
	$t = rau_em_tablas();
	$desde = max( 0, (int) $req['desde'] );
	$filas = $wpdb->get_results( $wpdb->prepare( "SELECT id, uuid, tipo, autor, rol, ts_cli, ts_srv, datos FROM {$t['ev']} WHERE inc = %s AND id > %d ORDER BY id ASC LIMIT 500", $op['codigo'], $desde ), ARRAY_A );
	$out = array();
	foreach ( $filas ?: array() as $f ) {
		$d = json_decode( $f['datos'], true ) ?: array();
		$out[] = array( 's' => (int) $f['id'], 'u' => $f['uuid'], 't' => $f['tipo'], 'a' => $f['autor'], 'rol' => $f['rol'], 'ts' => (int) $f['ts_cli'], 'srv' => (int) $f['ts_srv'] * 1000,
			'd' => rau_em_redactar_para( $f['tipo'], $d, $a ) ?: new stdClass() );
	}
	return array( 'operacion' => rau_em_operacion_publica( $op ), 'actor' => rau_em_meta_actor( $a ), 'eventos' => $out, 'mas' => count( $out ) >= 500, 'hora' => time() * 1000 );
}

function rau_em_api_registrar( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req, 'operar' );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op, $a ) = $c;
	global $wpdb;
	$t = rau_em_tablas();
	$lista = $req->get_param( 'eventos' );
	if ( ! is_array( $lista ) ) return rau_em_error( 'Faltan los registros' );
	if ( count( $lista ) > 100 ) return rau_em_error( 'Máximo 100 registros por envío' );
	if ( rau_em_limite( 'reg' . ( $a['sesion'] ?: 'u' . $a['usuario'] ), 3000, HOUR_IN_SECONDS ) ) return rau_em_error( 'Demasiados registros en una hora', 429 );
	$cerrada = 'cerrado' === $op['estado'];
	$res = array();
	$estado_op = null;
	foreach ( $lista as $e ) {
		$u = is_array( $e ) && isset( $e['u'] ) ? (string) $e['u'] : '';
		if ( ! preg_match( '/^[a-z0-9-]{8,36}$/', $u ) ) { $res[] = array( 'u' => $u, 'ok' => false, 'error' => 'Identificador inválido' ); continue; }
		$ya = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['ev']} WHERE uuid = %s AND inc = %s", $u, $op['codigo'] ) );
		if ( $ya ) { $res[] = array( 'u' => $u, 'ok' => true, 's' => (int) $ya, 'repetido' => true ); continue; }
		$v = rau_em_validar_evento( $e, $op, $a );
		if ( ! $v['ok'] ) { $res[] = array( 'u' => $u, 'ok' => false, 'error' => $v['error'] ); continue; }
		$tipo = (string) $e['t'];
		if ( $cerrada && ! in_array( $tipo, array( 'op', 'inc', 'leccion', 'informe', 'resuelve' ), true ) ) { $res[] = array( 'u' => $u, 'ok' => false, 'error' => 'La operación está cerrada: solo se admiten lecciones, informes o reabrirla' ); continue; }
		$ts = isset( $e['ts'] ) ? (int) $e['ts'] : 0;
		// Hora del dispositivo, salvo que sea absurda (reloj sin configurar tras quedarse sin batería)
		if ( $ts < ( time() - 30 * DAY_IN_SECONDS ) * 1000 || $ts > ( time() + DAY_IN_SECONDS ) * 1000 ) $ts = time() * 1000;
		$ok = $wpdb->insert( $t['ev'], array( 'inc' => $op['codigo'], 'uuid' => $u, 'tipo' => $tipo, 'autor' => $a['nombre'], 'rol' => $a['rol'], 'sesion' => $a['sesion'],
			'usuario' => $a['usuario'], 'ts_cli' => $ts, 'ts_srv' => time(), 'datos' => wp_json_encode( $v['datos'] ) ) );
		if ( ! $ok ) {
			// Mismo identificador en otra operación: no se pisa
			$res[] = array( 'u' => $u, 'ok' => false, 'error' => 'No se pudo guardar (identificador en uso)' ); continue;
		}
		$res[] = array( 'u' => $u, 'ok' => true, 's' => (int) $wpdb->insert_id );
		if ( in_array( $tipo, array( 'op', 'inc' ), true ) ) $estado_op = $v['datos']['estado'];
		if ( in_array( $v['perm'], array( 'coordinar', 'recursos' ), true ) ) rau_em_auditar( $op['codigo'], $a, 'registro_' . $tipo, $u, $v['datos'] );
	}
	$cambio = array( 'actividad' => time() );
	if ( $estado_op ) { $cambio['estado'] = $estado_op; $cambio['cerrado'] = 'cerrado' === $estado_op ? time() : 0; }
	$wpdb->update( $t['ops'], $cambio, array( 'codigo' => $op['codigo'] ) );
	return array( 'resultados' => $res );
}

/* ── Administración de la operación: sesiones, claves, invitaciones, auditoría, respaldo ── */
function rau_em_api_sesiones( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req, 'coordinar' );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op ) = $c;
	global $wpdb;
	$t = rau_em_tablas();
	$filas = $wpdb->get_results( $wpdb->prepare( "SELECT id, rol, indicativo, nombre, origen, creado, ultimo, expira, revocado FROM {$t['ses']} WHERE op = %s ORDER BY ultimo DESC LIMIT 300", $op['codigo'] ), ARRAY_A );
	return array_map( function( $s ) { foreach ( array( 'id', 'creado', 'ultimo', 'expira', 'revocado' ) as $k ) $s[ $k ] = (int) $s[ $k ]; return $s; }, $filas ?: array() );
}
function rau_em_api_revocar( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req, 'coordinar' );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op, $a ) = $c;
	global $wpdb;
	$t = rau_em_tablas();
	$n = $wpdb->query( $wpdb->prepare( "UPDATE {$t['ses']} SET revocado = %d WHERE id = %d AND op = %s AND revocado = 0", time(), (int) $req['id'], $op['codigo'] ) );
	rau_em_auditar( $op['codigo'], $a, 'sesion_revocada', 'ses:' . (int) $req['id'] );
	return array( 'revocadas' => (int) $n );
}
function rau_em_api_claves( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req, 'coordinar' );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op, $a ) = $c;
	global $wpdb;
	$t = rau_em_tablas();
	$cambio = array();
	$clave  = (string) $req['clave'];
	$ccoord = (string) $req['clave_coord'];
	if ( '' !== $clave ) { if ( mb_strlen( $clave ) < 6 ) return rau_em_error( 'La clave de operadores necesita al menos 6 caracteres' ); $cambio['clave_hash'] = wp_hash_password( $clave ); }
	if ( '' !== $ccoord ) { if ( mb_strlen( $ccoord ) < 8 ) return rau_em_error( 'La clave de coordinación necesita al menos 8 caracteres' ); $cambio['clave_coord_hash'] = wp_hash_password( $ccoord ); }
	if ( '' !== $clave && $clave === $ccoord ) return rau_em_error( 'Las dos claves deben ser distintas' );
	if ( $cambio ) $wpdb->update( $t['ops'], $cambio, array( 'codigo' => $op['codigo'] ) );
	$rev = 0;
	if ( $req['revocar_sesiones'] ) {
		// Revoca todas menos la de quien hace el cambio
		$rev = $wpdb->query( $wpdb->prepare( "UPDATE {$t['ses']} SET revocado = %d WHERE op = %s AND revocado = 0 AND id <> %d", time(), $op['codigo'], $a['sesion'] ) );
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['inv']} SET revocado = %d WHERE op = %s AND revocado = 0", time(), $op['codigo'] ) );
	}
	rau_em_auditar( $op['codigo'], $a, 'claves_cambiadas', $op['codigo'], array( 'operadores' => '' !== $clave, 'coordinacion' => '' !== $ccoord, 'sesiones_revocadas' => (int) $rev ) );
	return array( 'ok' => true, 'sesiones_revocadas' => (int) $rev );
}
function rau_em_api_invitar( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req, 'coordinar' );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op, $a ) = $c;
	$rol = (string) $req['rol'];
	if ( ! in_array( $rol, array( 'observador', 'operador', 'recursos' ), true ) ) return rau_em_error( 'Rol no válido para una invitación (la coordinación se da con su clave o con una cuenta)' );
	$horas = max( 1, min( 168, (int) $req['horas'] ?: 24 ) );
	$usos  = max( 1, min( 50, (int) $req['usos'] ?: 1 ) );
	$clave = rau_em_codigo_nuevo( 4 ) . '-' . rau_em_codigo_nuevo( 4 );
	global $wpdb;
	$t = rau_em_tablas();
	$wpdb->insert( $t['inv'], array( 'op' => $op['codigo'], 'rol' => $rol, 'clave_hash' => wp_hash_password( $clave ), 'expira' => time() + $horas * HOUR_IN_SECONDS,
		'usos_max' => $usos, 'creado' => time(), 'creado_por' => $a['nombre'] ) );
	rau_em_auditar( $op['codigo'], $a, 'invitacion_creada', 'inv:' . $wpdb->insert_id, array( 'rol' => $rol, 'horas' => $horas, 'usos' => $usos ) );
	return array( 'clave' => $clave, 'rol' => $rol, 'expira' => time() + $horas * HOUR_IN_SECONDS, 'usos' => $usos );
}
function rau_em_api_auditoria( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req, 'coordinar' );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op ) = $c;
	global $wpdb;
	$t = rau_em_tablas();
	$filas = $wpdb->get_results( $wpdb->prepare( "SELECT ts, actor, rol, accion, objeto, detalle FROM {$t['aud']} WHERE op = %s ORDER BY id DESC LIMIT 500", $op['codigo'] ), ARRAY_A );
	return array_map( function( $f ) { $f['ts'] = (int) $f['ts']; $f['detalle'] = json_decode( $f['detalle'], true ); return $f; }, $filas ?: array() );
}
/** Respaldo completo (sin hashes de claves ni tokens), para guardar fuera del servidor o restaurar. */
function rau_em_api_respaldo( WP_REST_Request $req ) {
	$c = rau_em_contexto( $req, 'coordinar' );
	if ( $c instanceof WP_REST_Response ) return $c;
	list( $op, $a ) = $c;
	global $wpdb;
	$t = rau_em_tablas();
	$filas = $wpdb->get_results( $wpdb->prepare( "SELECT uuid, tipo, autor, rol, ts_cli, ts_srv, datos FROM {$t['ev']} WHERE inc = %s ORDER BY id ASC", $op['codigo'] ), ARRAY_A );
	rau_em_auditar( $op['codigo'], $a, 'respaldo_descargado', $op['codigo'], array( 'registros' => count( $filas ?: array() ) ) );
	return array( 'rau_em' => 2, 'generado' => time() * 1000, 'operacion' => rau_em_operacion_publica( $op ),
		'eventos' => array_map( function( $f ) { return array( 'u' => $f['uuid'], 't' => $f['tipo'], 'a' => $f['autor'], 'rol' => $f['rol'], 'ts' => (int) $f['ts_cli'], 'srv' => (int) $f['ts_srv'] * 1000, 'd' => json_decode( $f['datos'], true ) ); }, $filas ?: array() ) );
}

/* ── Directorio de frecuencias ───────────────────────────────────────────── */
function rau_em_api_directorio() {
	return array( 'categorias' => rau_em_directorio_categorias(), 'entradas' => rau_em_directorio() );
}
/** Solo cuentas de coordinador o administrador: el directorio es de todas las operaciones. */
function rau_em_actor_cuenta( $roles ) {
	$u = wp_get_current_user();
	$rol = rau_em_rol_de_usuario( $u );
	if ( ! in_array( $rol, $roles, true ) ) return null;
	return array( 'rol' => $rol, 'permisos' => rau_em_permisos_de_rol( $rol ), 'nombre' => get_user_meta( $u->ID, 'rau_em_indicativo', true ) ?: $u->user_login, 'sesion' => 0, 'usuario' => $u->ID );
}
function rau_em_api_directorio_guardar( WP_REST_Request $req ) {
	$a = rau_em_actor_cuenta( array( 'admin', 'coordinador' ) );
	if ( ! $a ) return rau_em_error( 'Solo un coordinador o administrador con cuenta puede cambiar el directorio', 403 );
	$r = rau_em_directorio_guardar( (array) $req->get_json_params(), $a );
	return is_wp_error( $r ) ? rau_em_error( $r->get_error_message() ) : $r;
}
function rau_em_api_directorio_borrar( WP_REST_Request $req ) {
	$a = rau_em_actor_cuenta( array( 'admin' ) );
	if ( ! $a ) return rau_em_error( 'Solo un administrador puede borrar entradas del directorio', 403 );
	$r = rau_em_directorio_borrar( (string) $req['id'], $a );
	return is_wp_error( $r ) ? rau_em_error( $r->get_error_message() ) : array( 'ok' => true );
}
