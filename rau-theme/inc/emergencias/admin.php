<?php
/**
 * Administración del centro de mando en el escritorio de WordPress (Herramientas → Centro de mando),
 * datos del usuario (organización e indicativo) y política de conservación.
 *
 * Conservación: al pasar N días desde el cierre de una operación (90 por defecto), los datos de
 * personas (bienestar) se reducen a lo mínimo: se borran contacto, mensaje y ubicación, y el nombre
 * queda en iniciales. El resto del registro operativo se conserva para el informe y las lecciones.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Organización e indicativo en el perfil del usuario ─────────────────── */
function rau_em_campos_usuario( $user ) {
	global $wpdb;
	$t = rau_em_tablas();
	$orgs = $wpdb->get_results( "SELECT id, nombre, sigla FROM {$t['org']} ORDER BY nombre", ARRAY_A ) ?: array();
	$mi = rau_em_org_de_usuario( $user->ID );
	echo '<h2>Centro de Mando de Emergencias</h2><table class="form-table"><tr><th><label for="rau_em_indicativo">Indicativo</label></th><td>';
	echo '<input type="text" name="rau_em_indicativo" id="rau_em_indicativo" value="' . esc_attr( get_user_meta( $user->ID, 'rau_em_indicativo', true ) ) . '" class="regular-text"></td></tr>';
	if ( current_user_can( 'manage_options' ) ) {
		echo '<tr><th><label for="rau_em_org">Organización</label></th><td><select name="rau_em_org" id="rau_em_org"><option value="0">— Ninguna —</option>';
		foreach ( $orgs as $o ) echo '<option value="' . (int) $o['id'] . '"' . selected( $mi, (int) $o['id'], false ) . '>' . esc_html( $o['nombre'] . ( $o['sigla'] ? ' (' . $o['sigla'] . ')' : '' ) ) . '</option>';
		echo '</select><p class="description">El rol del centro de mando se asigna arriba, en «Perfil» (roles «Emergencias: …»). Sin organización, la cuenta no ve operaciones (salvo el administrador).</p></td></tr>';
	}
	echo '</table>';
	wp_nonce_field( 'rau_em_usuario', 'rau_em_usuario_nonce' );
}
add_action( 'show_user_profile', 'rau_em_campos_usuario' );
add_action( 'edit_user_profile', 'rau_em_campos_usuario' );
function rau_em_guardar_usuario( $id ) {
	if ( ! isset( $_POST['rau_em_usuario_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rau_em_usuario_nonce'] ), 'rau_em_usuario' ) ) return;
	if ( ! current_user_can( 'edit_user', $id ) ) return;
	if ( isset( $_POST['rau_em_indicativo'] ) ) update_user_meta( $id, 'rau_em_indicativo', rau_em_indicativo( wp_unslash( $_POST['rau_em_indicativo'] ) ) );
	if ( current_user_can( 'manage_options' ) && isset( $_POST['rau_em_org'] ) ) {
		$antes = rau_em_org_de_usuario( $id );
		$nueva = (int) $_POST['rau_em_org'];
		update_user_meta( $id, 'rau_em_org', $nueva );
		if ( $antes !== $nueva ) rau_em_auditar( '', array( 'nombre' => wp_get_current_user()->user_login, 'rol' => 'admin' ), 'usuario_org', 'user:' . $id, array( 'antes' => $antes, 'despues' => $nueva ) );
	}
}
add_action( 'personal_options_update', 'rau_em_guardar_usuario' );
add_action( 'edit_user_profile_update', 'rau_em_guardar_usuario' );
// Cambiar el rol de alguien es una acción sensible: queda en la auditoría
add_action( 'set_user_role', function( $id, $rol, $antes ) {
	if ( 0 === strpos( $rol, 'em_' ) || array_filter( (array) $antes, function( $r ) { return 0 === strpos( $r, 'em_' ); } ) ) {
		$u = wp_get_current_user();
		rau_em_auditar( '', array( 'nombre' => $u->exists() ? $u->user_login : 'sistema', 'rol' => 'admin' ), 'usuario_rol', 'user:' . $id, array( 'antes' => $antes, 'despues' => $rol ) );
	}
}, 10, 3 );

/* ── Conservación de datos ───────────────────────────────────────────────── */
add_action( 'init', function() { if ( ! wp_next_scheduled( 'rau_em_retencion' ) ) wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'rau_em_retencion' ); } );
add_action( 'rau_em_retencion', 'rau_em_aplicar_retencion' );
function rau_em_aplicar_retencion() {
	global $wpdb;
	$t = rau_em_tablas();
	$dias = max( 7, (int) get_option( 'rau_em_retencion_dias', 90 ) );
	$ops = $wpdb->get_col( $wpdb->prepare( "SELECT codigo FROM {$t['ops']} WHERE estado = 'cerrado' AND cerrado > 0 AND cerrado < %d AND retencion = 0", time() - $dias * DAY_IN_SECONDS ) );
	foreach ( $ops ?: array() as $cod ) {
		$filas = $wpdb->get_results( $wpdb->prepare( "SELECT id, tipo, datos FROM {$t['ev']} WHERE inc = %s AND tipo IN ('persona','personaact')", $cod ), ARRAY_A );
		foreach ( $filas ?: array() as $f ) {
			$d = json_decode( $f['datos'], true ) ?: array();
			if ( 'persona' === $f['tipo'] ) {
				$ini = implode( '', array_map( function( $p ) { return mb_substr( $p, 0, 1 ) . '.'; }, array_filter( explode( ' ', (string) ( $d['nombre'] ?? '' ) ) ) ) );
				$d = array( 'nombre' => $ini ?: '—', 'estado' => $d['estado'] ?? '', 'redactado' => true );
			} else {
				$d = array( 'p' => $d['p'] ?? '', 'estado' => $d['estado'] ?? '', 'redactado' => true );
			}
			$wpdb->update( $t['ev'], array( 'datos' => wp_json_encode( $d ) ), array( 'id' => $f['id'] ) );
		}
		$wpdb->update( $t['ops'], array( 'retencion' => 1 ), array( 'codigo' => $cod ) );
		// Las sesiones de una operación cerrada ya no sirven
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['ses']} SET revocado = %d WHERE op = %s AND revocado = 0", time(), $cod ) );
		rau_em_auditar( $cod, array( 'nombre' => 'sistema', 'rol' => 'admin' ), 'retencion_aplicada', $cod, array( 'personas' => count( $filas ?: array() ), 'dias' => $dias ) );
	}
}

/* ── Página de administración ────────────────────────────────────────────── */
add_action( 'admin_menu', function() {
	add_management_page( 'Centro de mando de emergencias', 'Centro de mando', 'manage_options', 'rau-em', 'rau_em_admin' );
} );
function rau_em_admin() {
	global $wpdb;
	$t = rau_em_tablas();
	$yo = array( 'nombre' => wp_get_current_user()->user_login, 'rol' => 'admin' );
	$msg = '';
	if ( isset( $_POST['rau_em_accion'] ) && check_admin_referer( 'rau_em_admin' ) ) {
		$acc = sanitize_key( $_POST['rau_em_accion'] );
		$cod = isset( $_POST['codigo'] ) ? rau_em_codigo( wp_unslash( $_POST['codigo'] ) ) : '';
		if ( 'org_nueva' === $acc && ! empty( $_POST['nombre'] ) ) {
			$wpdb->insert( $t['org'], array( 'nombre' => mb_substr( sanitize_text_field( wp_unslash( $_POST['nombre'] ) ), 0, 120 ), 'sigla' => mb_substr( sanitize_text_field( wp_unslash( $_POST['sigla'] ?? '' ) ), 0, 20 ), 'creado' => time() ) );
			rau_em_auditar( '', $yo, 'org_creada', 'org:' . $wpdb->insert_id ); $msg = 'Organización creada.';
		} elseif ( 'op_org' === $acc && $cod ) {
			$wpdb->update( $t['ops'], array( 'org_id' => (int) $_POST['org_id'], 'publico' => empty( $_POST['publico'] ) ? 0 : 1 ), array( 'codigo' => $cod ) );
			rau_em_auditar( $cod, $yo, 'op_org', $cod, array( 'org' => (int) $_POST['org_id'], 'publico' => ! empty( $_POST['publico'] ) ) ); $msg = 'Operación actualizada.';
		} elseif ( 'op_borrar' === $acc && $cod ) {
			$wpdb->delete( $t['ev'], array( 'inc' => $cod ) );
			$wpdb->delete( $t['ses'], array( 'op' => $cod ) );
			$wpdb->delete( $t['inv'], array( 'op' => $cod ) );
			$wpdb->delete( $t['ops'], array( 'codigo' => $cod ) );
			rau_em_auditar( $cod, $yo, 'op_borrada', $cod ); $msg = 'Operación ' . $cod . ' borrada con todos sus registros.';
		} elseif ( 'retencion' === $acc ) {
			update_option( 'rau_em_retencion_dias', max( 7, (int) $_POST['dias'] ), false );
			rau_em_auditar( '', $yo, 'retencion_cambiada', '', array( 'dias' => (int) $_POST['dias'] ) ); $msg = 'Conservación actualizada.';
		} elseif ( 'restaurar' === $acc && ! empty( $_FILES['respaldo']['tmp_name'] ) ) {
			$msg = rau_em_restaurar( (string) file_get_contents( $_FILES['respaldo']['tmp_name'] ), $yo );
		}
	}
	$orgs = $wpdb->get_results( "SELECT o.*, (SELECT COUNT(*) FROM {$t['ops']} p WHERE p.org_id = o.id) AS n FROM {$t['org']} o ORDER BY nombre", ARRAY_A ) ?: array();
	$ops = $wpdb->get_results( "SELECT i.*, (SELECT COUNT(*) FROM {$t['ev']} e WHERE e.inc = i.codigo) AS n, (SELECT COUNT(*) FROM {$t['ses']} s WHERE s.op = i.codigo AND s.revocado = 0) AS ses FROM {$t['ops']} i ORDER BY actividad DESC LIMIT 200", ARRAY_A ) ?: array();
	$aud = $wpdb->get_results( "SELECT * FROM {$t['aud']} ORDER BY id DESC LIMIT 60", ARRAY_A ) ?: array();
	$f = function( $acc ) { wp_nonce_field( 'rau_em_admin' ); echo '<input type="hidden" name="rau_em_accion" value="' . esc_attr( $acc ) . '">'; };
	echo '<div class="wrap"><h1>Centro de mando de emergencias</h1>';
	if ( $msg ) echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
	echo '<p>La aplicación está en <a href="' . esc_url( home_url( '/emergencias/' ) ) . '">/emergencias/</a>. Documentación: <code>EMERGENCIAS.md</code> en la carpeta del tema.</p>';

	echo '<h2>Organizaciones</h2><p>Las cuentas con rol «Emergencias: …» ven las operaciones de su organización. Una operación solo aparece en la lista pública si tiene organización.</p><table class="widefat striped" style="max-width:720px"><thead><tr><th>Nombre</th><th>Sigla</th><th>Operaciones</th></tr></thead><tbody>';
	foreach ( $orgs as $o ) echo '<tr><td>' . esc_html( $o['nombre'] ) . '</td><td>' . esc_html( $o['sigla'] ) . '</td><td>' . (int) $o['n'] . '</td></tr>';
	echo '</tbody></table><form method="post" style="margin:.75rem 0 2rem">'; $f( 'org_nueva' );
	echo '<input name="nombre" placeholder="Nombre" required> <input name="sigla" placeholder="Sigla" size="8"> <button class="button">Agregar organización</button></form>';

	echo '<h2>Operaciones</h2><table class="widefat striped"><thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Estado</th><th>Registros</th><th>Sesiones activas</th><th>Actividad</th><th>Organización y lista pública</th><th></th></tr></thead><tbody>';
	foreach ( $ops as $o ) {
		echo '<tr><td><code>' . esc_html( $o['codigo'] ) . '</code></td><td>' . esc_html( $o['nombre'] ) . '<br><small>' . esc_html( $o['zona'] . ' · creó ' . $o['creador'] ) . '</small></td><td>' . esc_html( $o['tipo'] ) . '</td><td>' . esc_html( $o['estado'] ) . ( (int) $o['retencion'] ? ' · conservación aplicada' : '' ) . '</td><td>' . (int) $o['n'] . '</td><td>' . (int) $o['ses'] . '</td><td>' . esc_html( wp_date( 'Y-m-d H:i', (int) $o['actividad'] ) ) . '</td><td>';
		echo '<form method="post">'; $f( 'op_org' ); echo '<input type="hidden" name="codigo" value="' . esc_attr( $o['codigo'] ) . '"><select name="org_id"><option value="0">Sin organización</option>';
		foreach ( $orgs as $g ) echo '<option value="' . (int) $g['id'] . '"' . selected( (int) $o['org_id'], (int) $g['id'], false ) . '>' . esc_html( $g['sigla'] ?: $g['nombre'] ) . '</option>';
		echo '</select> <label><input type="checkbox" name="publico" value="1"' . checked( (int) $o['publico'], 1, false ) . '> pública</label> <button class="button button-small">Guardar</button></form></td><td>';
		echo '<form method="post" onsubmit="return confirm(\'¿Borrar la operación y todos sus registros? No se puede deshacer. Descarga antes el respaldo desde la aplicación.\')">'; $f( 'op_borrar' );
		echo '<input type="hidden" name="codigo" value="' . esc_attr( $o['codigo'] ) . '"><button class="button button-link-delete">Borrar</button></form></td></tr>';
	}
	echo '</tbody></table>';

	echo '<h2>Conservación de datos</h2><form method="post">'; $f( 'retencion' );
	echo '<p>Días tras el cierre de una operación para reducir los datos de personas (bienestar): <input type="number" name="dias" min="7" value="' . (int) get_option( 'rau_em_retencion_dias', 90 ) . '" style="width:6rem"> <button class="button">Guardar</button></p></form>';

	echo '<h2>Restaurar un respaldo</h2><form method="post" enctype="multipart/form-data">'; $f( 'restaurar' );
	echo '<p>Archivo JSON descargado con «Respaldo» en la aplicación. Si la operación ya existe, se agregan solo los registros que falten; si no existe, se crea sin claves (entra un coordinador con cuenta y define claves nuevas).</p><input type="file" name="respaldo" accept=".json,application/json" required> <button class="button">Restaurar</button></form>';

	echo '<h2>Auditoría reciente</h2><table class="widefat striped"><thead><tr><th>Hora</th><th>Operación</th><th>Quién</th><th>Rol</th><th>Acción</th><th>Objeto</th></tr></thead><tbody>';
	foreach ( $aud as $x ) echo '<tr><td>' . esc_html( wp_date( 'Y-m-d H:i:s', (int) $x['ts'] ) ) . '</td><td>' . esc_html( $x['op'] ) . '</td><td>' . esc_html( $x['actor'] ) . '</td><td>' . esc_html( $x['rol'] ) . '</td><td>' . esc_html( $x['accion'] ) . '</td><td>' . esc_html( $x['objeto'] ) . '</td></tr>';
	echo '</tbody></table></div>';
}

/** Restaura un respaldo descargado de la aplicación. Devuelve un mensaje para el administrador. */
function rau_em_restaurar( $json, $yo ) {
	global $wpdb;
	$t = rau_em_tablas();
	$j = json_decode( $json, true );
	if ( ! is_array( $j ) || empty( $j['operacion']['codigo'] ) || ! isset( $j['eventos'] ) || ! is_array( $j['eventos'] ) ) return 'El archivo no es un respaldo del centro de mando.';
	$cod = rau_em_codigo( $j['operacion']['codigo'] );
	$op  = rau_em_operacion( $cod );
	if ( ! $op ) {
		$wpdb->insert( $t['ops'], array( 'codigo' => $cod, 'nombre' => mb_substr( sanitize_text_field( $j['operacion']['nombre'] ?? 'Restaurada' ), 0, 120 ), 'tipo' => 'simulacro' === ( $j['operacion']['tipo'] ?? '' ) ? 'simulacro' : 'real',
			'zona' => mb_substr( sanitize_text_field( $j['operacion']['zona'] ?? '' ), 0, 120 ), 'creador' => rau_em_indicativo( $j['operacion']['creador'] ?? '' ),
			'clave_hash' => wp_hash_password( wp_generate_password( 32 ) ), 'clave_coord_hash' => '', 'org_id' => 0, 'publico' => 0, 'estado' => 'cerrado', 'creado' => time(), 'actividad' => time(), 'cerrado' => time() ) );
		$op = rau_em_operacion( $cod );
	}
	$n = 0; $r = 0;
	$admin = array( 'rol' => 'admin', 'permisos' => rau_em_permisos_de_rol( 'admin' ), 'nombre' => $yo['nombre'], 'sesion' => 0, 'usuario' => get_current_user_id() );
	foreach ( $j['eventos'] as $e ) {
		$v = rau_em_validar_evento( $e, $op, $admin );
		if ( ! $v['ok'] || ! preg_match( '/^[a-z0-9-]{8,36}$/', (string) ( $e['u'] ?? '' ) ) ) { $r++; continue; }
		$ok = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$t['ev']} (inc, uuid, tipo, autor, rol, sesion, usuario, ts_cli, ts_srv, datos) VALUES (%s, %s, %s, %s, %s, 0, 0, %d, %d, %s)",
			$cod, $e['u'], $e['t'], mb_substr( sanitize_text_field( $e['a'] ?? '' ), 0, 40 ), sanitize_key( $e['rol'] ?? '' ), (int) $e['ts'], (int) ( ( $e['srv'] ?? $e['ts'] ) / 1000 ), wp_json_encode( $v['datos'] ) ) );
		if ( $ok ) $n++;
	}
	rau_em_auditar( $cod, $yo, 'respaldo_restaurado', $cod, array( 'agregados' => $n, 'rechazados' => $r ) );
	return "Respaldo de $cod: $n registros agregados, $r rechazados por no cumplir el esquema.";
}
