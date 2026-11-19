<?php
/**
 * Verificación en dos pasos (inc/dos-pasos.php), dentro de WordPress y con el formulario de entrada real.
 *   php tests/sitio/dos-pasos.php /ruta/a/wordpress/wp-load.php [http://localhost:8099]
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php dos-pasos.php /ruta/wp-load.php [base]\n" ); exit( 2 ); }
require $argv[1];
$base = $argv[2] ?? 'http://localhost:8099';
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }

// RFC 6238, apéndice B (SHA1): secreto ASCII «12345678901234567890»
$s = rau_2fa_b32_codificar( '12345678901234567890' );
ok( 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ' === $s, 'base32 del secreto de la RFC' );
ok( '12345678901234567890' === rau_2fa_b32_decodificar( $s ), 'base32 de ida y vuelta' );
foreach ( array( 59 => '94287082', 1111111109 => '07081804', 1234567890 => '89005924', 2000000000 => '69279037' ) as $t => $c8 ) {
	ok( $c8 === rau_2fa_codigo( $s, (int) floor( $t / 30 ), 8 ), "vector RFC 6238 en T=$t" );
}
$t = 1234567890; $c = rau_2fa_codigo( $s, (int) floor( $t / 30 ) );
ok( false !== rau_2fa_verificar( $s, $c, $t ), 'acepta el código de ahora' );
ok( false !== rau_2fa_verificar( $s, $c, $t + 30 ), 'acepta el del paso anterior (reloj del celular atrasado)' );
ok( false === rau_2fa_verificar( $s, $c, $t + 90 ), 'no acepta uno de hace 90 s' );
ok( false === rau_2fa_verificar( $s, $c, $t, (int) floor( $t / 30 ) ), 'no acepta reutilizar un código ya usado' );
ok( false === rau_2fa_verificar( $s, '000000', $t ) || '000000' === $c, 'rechaza un código cualquiera' );

// Entrada real por wp-login.php
$login = 'prueba-2fa-' . wp_generate_password( 6, false ); $clave = wp_generate_password( 20, false );
$uid = wp_insert_user( array( 'user_login' => $login, 'user_pass' => $clave, 'role' => 'editor' ) );
function entrar( $base, $login, $clave, $codigo = null ) {
	$campos = array( 'log' => $login, 'pwd' => $clave, 'wp-submit' => 'Acceder', 'testcookie' => '1' );
	if ( null !== $codigo ) $campos['rau_2fa'] = $codigo;
	$ch = curl_init( $base . '/wp-login.php' );
	curl_setopt_array( $ch, array( CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query( $campos ), CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIE => 'wordpress_test_cookie=WP%20Cookie%20check', CURLOPT_PROXY => '', CURLOPT_NOPROXY => '*' ) );
	$r = curl_exec( $ch ); $st = curl_getinfo( $ch, CURLINFO_HTTP_CODE ); curl_close( $ch );
	return array( $st, (bool) preg_match( '/Set-Cookie: wordpress_logged_in_/i', $r ), $r );
}
list( , $dentro, $html ) = entrar( $base, $login, $clave );
ok( $dentro, 'sin los dos pasos activados, entra solo con la contraseña' );
ok( false !== strpos( $html, '' ) && false !== strpos( file_get_contents( $base . '/wp-login.php', false, stream_context_create( array( 'http' => array( 'proxy' => '' ) ) ) ), 'name="rau_2fa"' ), 'el formulario de entrada tiene el campo del código' );

$sec = rau_2fa_nuevo_secreto(); update_user_meta( $uid, RAU_2FA_SECRETO, $sec ); $resp = rau_2fa_nuevos_respaldos( $uid );
list( , $dentro, $html ) = entrar( $base, $login, $clave );
preg_match( '/<div id="login_error".*?<\/div>/s', $html, $m ); ok( ! $dentro && false !== strpos( $html, 'Falta el código de verificación' ), 'activada: sin código no entra y dice por qué' ) || print( ( $m[0] ?? substr( $html, 0, 300 ) ) . "\n" );
list( , $dentro, $html ) = entrar( $base, $login, $clave, '123456' === rau_2fa_codigo( $sec, (int) floor( time() / 30 ) ) ? '654321' : '123456' );
ok( ! $dentro && false !== strpos( $html, 'incorrecto' ), 'con un código equivocado no entra' );
list( , $dentro ) = entrar( $base, $login, $clave, rau_2fa_codigo( $sec, (int) floor( time() / 30 ) ) );
ok( $dentro, 'con el código de la app entra' );
list( , $dentro ) = entrar( $base, $login, $clave, rau_2fa_codigo( $sec, (int) floor( time() / 30 ) ) );
ok( ! $dentro, 'el mismo código no sirve dos veces' );
list( , $dentro ) = entrar( $base, $login, $clave, strtoupper( str_replace( '-', '', $resp[0] ) ) );
ok( $dentro, 'un código de respaldo sirve (con o sin guion, en mayúsculas)' );
list( , $dentro ) = entrar( $base, $login, $clave, $resp[0] );
ok( ! $dentro, 'y solo una vez' );
ok( 7 === count( get_user_meta( $uid, RAU_2FA_RESPALDO, true ) ), 'quedan 7 respaldos' );
list( , $dentro ) = entrar( $base, $login, 'otra-' . $clave, rau_2fa_codigo( $sec, (int) floor( time() / 30 ) + 1 ) );
ok( ! $dentro, 'el código no reemplaza la contraseña' );

// Contraseña de aplicación por la API: no pasa por el formulario, sigue funcionando
$ap = WP_Application_Passwords::create_new_application_password( $uid, array( 'name' => 'prueba' ) );
$ch = curl_init( $base . '/wp-json/wp/v2/users/me' );
curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_USERPWD => $login . ':' . $ap[0], CURLOPT_PROXY => '', CURLOPT_NOPROXY => '*' ) );
$r = json_decode( curl_exec( $ch ), true ); curl_close( $ch );
ok( isset( $r['id'] ) && $uid === (int) $r['id'], 'las contraseñas de aplicación de la API siguen funcionando' );

// XML-RPC con usuario y contraseña, apagado
$x = '<?xml version="1.0"?><methodCall><methodName>wp.getUsersBlogs</methodName><params><param><value>' . $login . '</value></param><param><value>' . $clave . '</value></param></params></methodCall>';
$ch = curl_init( $base . '/xmlrpc.php' );
curl_setopt_array( $ch, array( CURLOPT_POST => true, CURLOPT_POSTFIELDS => $x, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => array( 'Content-Type: text/xml' ), CURLOPT_PROXY => '', CURLOPT_NOPROXY => '*' ) );
$r = curl_exec( $ch ); curl_close( $ch );
ok( false !== strpos( $r, '<name>faultCode</name>' ) && false === strpos( $r, 'blogName' ), 'XML-RPC no acepta usuario y contraseña' );

require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $uid );
echo "\n$n comprobaciones, $fallos fallas\n"; exit( $fallos ? 1 : 0 );
