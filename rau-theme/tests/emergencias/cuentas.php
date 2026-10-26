<?php
/**
 * Pruebas de cuentas, organizaciones, conservación y directorio, dentro de WordPress.
 *   php tests/emergencias/cuentas.php /ruta/a/wordpress/wp-load.php
 * Crea datos de prueba con el prefijo «prueba-em-» y los borra al terminar.
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php cuentas.php /ruta/wp-load.php\n" ); exit( 2 ); }
$_SERVER['REMOTE_ADDR'] = '127.0.0.9';
require $argv[1];
global $wpdb;
$t = rau_em_tablas();
$fallos = 0; $n = 0;
function ok( $cond, $txt ) { global $fallos, $n; $n++; echo ( $cond ? "ok   " : "FALLA" ) . " $txt\n"; if ( ! $cond ) $fallos++; }

// Organizaciones y operaciones
$wpdb->insert( $t['org'], array( 'nombre' => 'prueba-em-A', 'sigla' => 'PA', 'creado' => time() ) ); $orgA = (int) $wpdb->insert_id;
$wpdb->insert( $t['org'], array( 'nombre' => 'prueba-em-B', 'sigla' => 'PB', 'creado' => time() ) ); $orgB = (int) $wpdb->insert_id;
$mk = function( $cod, $tipo, $org ) use ( $wpdb, $t ) {
	$wpdb->insert( $t['ops'], array( 'codigo' => $cod, 'nombre' => 'prueba-em-' . $cod, 'tipo' => $tipo, 'zona' => '', 'creador' => 'HK0TST', 'clave_hash' => wp_hash_password( 'x' ),
		'org_id' => $org, 'estado' => 'abierto', 'creado' => time(), 'actividad' => time() ) );
	return rau_em_operacion( $cod );
};
$opA = $mk( 'PRUEBAA1', 'real', $orgA ); $opB = $mk( 'PRUEBAB1', 'real', $orgB ); $simA = $mk( 'PRUEBAS1', 'simulacro', $orgA ); $sinOrg = $mk( 'PRUEBAN1', 'real', 0 );
$usuarios = array();
$mkU = function( $login, $rol, $org ) use ( &$usuarios ) {
	$id = wp_insert_user( array( 'user_login' => 'prueba-em-' . $login, 'user_pass' => wp_generate_password(), 'role' => $rol ) );
	update_user_meta( $id, 'rau_em_org', $org ); $usuarios[] = $id; return $id;
};
$coordA = $mkU( 'coordA', 'em_coordinador', $orgA );
$obsA   = $mkU( 'obsA', 'em_observador', $orgA );
$simU   = $mkU( 'simA', 'em_simulacion', $orgA );
$recA   = $mkU( 'recA', 'em_recursos', $orgA );
$nada   = $mkU( 'nada', 'subscriber', $orgA );
$admin  = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0];

wp_set_current_user( $coordA );
$a = rau_em_actor( $opA ); ok( $a && 'coordinador' === $a['rol'], 'coordinador ve la operación de su organización' );
ok( null === rau_em_actor( $opB ), 'coordinador no ve la de otra organización' );
ok( null === rau_em_actor( $sinOrg ), 'ni una operación sin organización (esas solo con clave)' );
wp_set_current_user( $obsA );
$a = rau_em_actor( $opA ); ok( $a && ! rau_em_puede( $a, 'operar' ) && rau_em_puede( $a, 'ver' ), 'observador: ver sí, operar no' );
$v = rau_em_validar_evento( array( 'u' => 'abcdef12-0000', 't' => 'nota', 'd' => array( 'texto' => 'x' ) ), $opA, $a ); ok( ! $v['ok'], 'el esquema rechaza a un observador que intenta registrar' );
wp_set_current_user( $simU );
ok( null === rau_em_actor( $opA ), 'usuario de simulación no entra a una operación real' );
$a = rau_em_actor( $simA ); ok( $a && rau_em_puede( $a, 'operar' ), 'pero sí opera en un simulacro de su organización' );
wp_set_current_user( $recA );
$a = rau_em_actor( $opA );
$v = rau_em_validar_evento( array( 'u' => 'abcdef12-0001', 't' => 'r_est', 'd' => array( 'r' => 'abcdef12-0002', 'e' => 'asignado' ) ), $opA, $a ); ok( $v['ok'], 'responsable de recursos asigna un recurso' );
$v = rau_em_validar_evento( array( 'u' => 'abcdef12-0003', 't' => 'i_fusion', 'd' => array( 'de' => 'abcdef12-0004', 'en' => 'abcdef12-0005' ) ), $opA, $a ); ok( ! $v['ok'], 'pero no fusiona incidentes (coordinar)' );
wp_set_current_user( $nada );
ok( null === rau_em_actor( $opA ), 'una cuenta sin rol del centro de mando no entra' );
wp_set_current_user( $admin );
ok( 'admin' === rau_em_actor( $opB )['rol'] && 'admin' === rau_em_actor( $sinOrg )['rol'], 'el administrador entra a todas' );

// Directorio: exige fuente; institucional exige verificación; todo queda auditado
$yo = rau_em_actor_cuenta( array( 'admin' ) );
ok( is_wp_error( rau_em_directorio_guardar( array( 'cat' => 'actividad_ra', 'f' => '7.100', 'fuente' => '' ), $yo ) ), 'el directorio rechaza una entrada sin fuente' );
ok( is_wp_error( rau_em_directorio_guardar( array( 'cat' => 'institucional', 'f' => '150.000', 'fuente' => 'Resolución X' ), $yo ) ), 'y una institucional sin fecha de verificación' );
$x = rau_em_directorio_guardar( array( 'cat' => 'pendiente', 'f' => '146.700', 'unidad' => 'MHz', 'banda' => '2 m', 'modo' => 'FM', 'uso' => 'prueba-em', 'fuente' => 'Prueba automática' ), $yo );
ok( ! is_wp_error( $x ) && $x['mod']['por'], 'una entrada válida se guarda con quién y cuándo' );
ok( (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['aud']} WHERE accion = 'directorio_alta' AND objeto = %s", $x['id'] ) ), 'y queda en la auditoría' );
rau_em_directorio_borrar( $x['id'], $yo );

// Conservación: los datos de personas se reducen al pasar el plazo desde el cierre
$wpdb->update( $t['ops'], array( 'estado' => 'cerrado', 'cerrado' => time() - 400 * DAY_IN_SECONDS ), array( 'codigo' => 'PRUEBAA1' ) );
$wpdb->insert( $t['ev'], array( 'inc' => 'PRUEBAA1', 'uuid' => 'prueba-em-per-1', 'tipo' => 'persona', 'autor' => 'HK0TST', 'ts_cli' => time() * 1000, 'ts_srv' => time(),
	'datos' => wp_json_encode( array( 'nombre' => 'María Pérez', 'estado' => 'bien', 'contacto' => '3000000000', 'msg' => 'Estoy bien' ) ) ) );
rau_em_aplicar_retencion();
$d = json_decode( $wpdb->get_var( "SELECT datos FROM {$t['ev']} WHERE uuid = 'prueba-em-per-1'" ), true );
ok( 'M.P.' === $d['nombre'] && ! isset( $d['contacto'] ) && ! isset( $d['msg'] ), 'conservación: queda «M.P.», sin contacto ni mensaje' );
ok( 1 === (int) $wpdb->get_var( "SELECT retencion FROM {$t['ops']} WHERE codigo = 'PRUEBAA1'" ), 'y la operación queda marcada' );

// Limpieza
foreach ( array( 'PRUEBAA1', 'PRUEBAB1', 'PRUEBAS1', 'PRUEBAN1' ) as $c ) { $wpdb->delete( $t['ev'], array( 'inc' => $c ) ); $wpdb->delete( $t['ops'], array( 'codigo' => $c ) ); }
$wpdb->delete( $t['org'], array( 'id' => $orgA ) ); $wpdb->delete( $t['org'], array( 'id' => $orgB ) );
require_once ABSPATH . 'wp-admin/includes/user.php';
foreach ( $usuarios as $u ) wp_delete_user( $u );
echo "\n$n comprobaciones, $fallos fallas\n";
exit( $fallos ? 1 : 0 );
