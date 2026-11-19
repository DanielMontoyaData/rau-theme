<?php
/**
 * Avisos al celular del centro de mando (inc/emergencias/avisos.php), dentro de WordPress.
 *   php tests/emergencias/avisos.php /ruta/a/wordpress/wp-load.php
 * No sale nada a internet: los envíos al servicio push se interceptan. Borra lo que crea.
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php avisos.php /ruta/wp-load.php\n" ); exit( 2 ); }
$_SERVER['REMOTE_ADDR'] = '127.0.0.8';
require $argv[1];
global $wpdb;
$t = rau_em_tablas();
$fallos = 0; $n = 0;
function ok( $cond, $txt ) { global $fallos, $n; $n++; echo ( $cond ? "ok   " : "FALLA" ) . " $txt\n"; if ( ! $cond ) $fallos++; }
function b64u( $b ) { return rtrim( strtr( base64_encode( $b ), '+/', '-_' ), '=' ); }

// Envíos interceptados; el código de respuesta se puede cambiar para simular una suscripción vencida
$enviados = array(); $respuesta = 201;
add_filter( 'pre_http_request', function( $pre, $args, $url ) use ( &$enviados, &$respuesta ) {
	if ( false === strpos( $url, 'fcm.googleapis.com' ) ) return $pre;
	$enviados[] = array( 'url' => $url, 'args' => $args );
	return array( 'headers' => array(), 'body' => '', 'response' => array( 'code' => $respuesta, 'message' => '' ), 'cookies' => array() );
}, 10, 3 );

// Una suscripción con claves reales del navegador (P-256), para que el cifrado del mensaje funcione
$k = openssl_pkey_new( array( 'curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC ) );
$det = openssl_pkey_get_details( $k );
$p256dh = b64u( "\x04" . str_pad( $det['ec']['x'], 32, "\0", STR_PAD_LEFT ) . str_pad( $det['ec']['y'], 32, "\0", STR_PAD_LEFT ) );
$sub = array( 'endpoint' => 'https://fcm.googleapis.com/fcm/send/prueba-em-' . wp_generate_password( 12, false ), 'keys' => array( 'p256dh' => $p256dh, 'auth' => b64u( random_bytes( 16 ) ) ) );
ok( is_array( rau_push_validar_suscripcion( $sub ) ), 'la suscripción de prueba es válida' );
ok( is_string( rau_push_validar_suscripcion( array( 'endpoint' => 'https://interno.local/x', 'keys' => $sub['keys'] ) ) ), 'un endpoint que no es de un servicio push se rechaza' );
ok( (bool) rau_get_vapid_keys(), 'el sitio tiene llaves VAPID' );

$antes = get_option( RAU_EM_AVISOS_OPCION, array() );
function api( $metodo, $ruta, $cuerpo = null, $token = '' ) {
	$r = new WP_REST_Request( $metodo, '/rau-em/v1' . $ruta );
	if ( $token ) $r->set_header( 'X-RAU-EM-Token', $token );
	if ( $cuerpo !== null ) { $r->set_header( 'Content-Type', 'application/json' ); $r->set_body( wp_json_encode( $cuerpo ) ); }
	$res = rest_do_request( $r );
	return array( $res->get_status(), $res->get_data() );
}
// Operación con una persona que coordina (suscrita) y otra que opera
list( $st, $op ) = api( 'POST', '/operaciones', array( 'nombre' => 'prueba-em-avisos', 'zona' => 'Pereira', 'tipo' => 'real', 'indicativo' => 'HK6COO', 'clave' => 'OPER-avisos1', 'clave_coord' => 'COORD-avisos99' ) );
ok( 200 === $st, 'se crea la operación' );
$cod = $op['operacion']['codigo']; $tokCoord = $op['token'];
list( , $so ) = api( 'POST', "/operaciones/$cod/sesion", array( 'clave' => 'OPER-avisos1', 'indicativo' => 'HK6OPR' ) );
$tokOper = $so['token'];

list( $st ) = api( 'POST', "/operaciones/$cod/avisos", $sub );
ok( 401 === $st, 'sin sesión no hay suscripción' );
list( $st ) = api( 'POST', "/operaciones/$cod/avisos", $sub, $tokCoord );
ok( 200 === $st, 'quien coordina suscribe su celular' );

$ev = function( $tipo, $d, $ts = null ) { return array( 'u' => wp_generate_uuid4(), 't' => $tipo, 'ts' => $ts ?: (int) ( microtime( true ) * 1000 ), 'd' => $d ); };
// El operador registra un mensaje de emergencia: se avisa a la coordinación
$enviados = array();
api( 'POST', "/operaciones/$cod/eventos", array( 'eventos' => array( $ev( 'msg', array( 'prec' => 'E', 'para' => 'CMGRD', 'texto' => 'Se necesitan dos ambulancias', 'num' => '7' ) ) ) ), $tokOper );
ok( 1 === count( $enviados ), 'mensaje de EMERGENCIA: un aviso' );
if ( $enviados ) {
	$h = $enviados[0]['args']['headers'];
	ok( 'aes128gcm' === $h['Content-Encoding'] && 0 === strpos( $h['Authorization'], 'vapid t=' ), 'el aviso va cifrado y firmado (VAPID)' );
	ok( false === $enviados[0]['args']['blocking'], 'no demora el registro: no espera respuesta' );
}
// Un mensaje de rutina no avisa; lo que registra quien está suscrito tampoco se le avisa a sí mismo
$enviados = array();
api( 'POST', "/operaciones/$cod/eventos", array( 'eventos' => array( $ev( 'msg', array( 'prec' => 'R', 'para' => 'X', 'texto' => 'rutina' ) ) ) ), $tokOper );
api( 'POST', "/operaciones/$cod/eventos", array( 'eventos' => array( $ev( 'contacto', array( 'ind' => 'HK4AAA', 'estado' => 'urgente', 'nota' => 'techo caído' ) ) ) ), $tokCoord );
ok( 0 === count( $enviados ), 'rutina no avisa, y a quien registra no se le avisa lo suyo' );
api( 'POST', "/operaciones/$cod/eventos", array( 'eventos' => array( $ev( 'contacto', array( 'ind' => 'HK4BBB', 'estado' => 'urgente', 'nota' => 'sin energía' ) ) ) ), $tokOper );
ok( 1 === count( $enviados ), 'URGENCIA de una estación registrada por otro: avisa' );

// Por tiempo: incidente crítico sin responsable a los 15 minutos, una sola vez
$enviados = array(); $hace20 = (int) ( ( time() - 20 * 60 ) * 1000 );
api( 'POST', "/operaciones/$cod/eventos", array( 'eventos' => array( $ev( 'i_nuevo', array( 'tipo' => 'deslizamiento', 'titulo' => 'Deslizamiento vía La Florida', 'precision' => 'aproximada', 'fuente' => 'HK4AAA', 'fuente_tipo' => 'operador_radio', 'prio' => 'critica' ), $hace20 ) ) ), $tokOper );
$enviados = array();
rau_em_avisos_revisar();
ok( 1 === count( $enviados ), 'el cron avisa del crítico sin responsable a los 15 min (el mensaje de emergencia todavía no cumple 10)' );
$enviados = array();
rau_em_avisos_revisar( time() + 3600 );
ok( 1 === count( $enviados ), 'una hora después: avisa del mensaje de emergencia sin entregar, y no repite el del incidente' );
$enviados = array();
rau_em_avisos_revisar( time() + 7200 );
ok( 0 === count( $enviados ), 'cada aviso sale una sola vez' );

// Suscripción vencida: el servicio responde 410 y se borra
$respuesta = 410; $enviados = array();
api( 'POST', "/operaciones/$cod/eventos", array( 'eventos' => array( $ev( 'i_nuevo', array( 'tipo' => 'sismo', 'titulo' => 'Colapso', 'precision' => 'aproximada', 'fuente' => 'X', 'fuente_tipo' => 'operador_radio', 'prio' => 'critica' ), $hace20 ) ) ), $tokOper );
rau_em_avisos_revisar();
$quedan = array_filter( rau_em_avisos_subs(), function( $s ) use ( $cod ) { return $s['op'] === $cod; } );
ok( 0 === count( $quedan ), 'una suscripción vencida (410) se borra' );

// Limpieza
update_option( RAU_EM_AVISOS_OPCION, $antes, false );
$wpdb->delete( $t['ev'], array( 'inc' => $cod ) ); $wpdb->delete( $t['ses'], array( 'op' => $cod ) ); $wpdb->delete( $t['ops'], array( 'codigo' => $cod ) );
echo "\n$n comprobaciones, $fallos fallas\n";
exit( $fallos ? 1 : 0 );
