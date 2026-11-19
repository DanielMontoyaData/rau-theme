<?php
/**
 * Reporte a PSK Reporter (inc/pskreporter-envio.php), dentro de WordPress, sin salir a internet:
 * el datagrama va a un socket UDP local y se decodifica aquí.
 *   php tests/radio/pskreporter.php /ruta/a/wordpress/wp-load.php
 */
if ( empty( $argv[1] ) || ! file_exists( $argv[1] ) ) { fwrite( STDERR, "Uso: php pskreporter.php /ruta/wp-load.php\n" ); exit( 2 ); }
require $argv[1];
$fallos = 0; $n = 0;
function ok( $c, $t ) { global $fallos, $n; $n++; echo ( $c ? "ok   " : "FALLA" ) . " $t\n"; if ( ! $c ) $fallos++; }
function enviar( $cuerpo ) { delete_transient( 'rau_psk_ult_' . get_current_user_id() ); $r = new WP_REST_Request( 'POST', '/rau/v1/spots' ); $r->set_header( 'Content-Type', 'application/json' ); $r->set_body( wp_json_encode( $cuerpo ) ); $res = rest_do_request( $r ); return array( $res->get_status(), $res->get_data() ); }
$guardar = array(); foreach ( array( 'rau_psk_cola', 'rau_psk_vistos', 'rau_psk_estado', 'rau_sdr_lugar' ) as $o ) { $guardar[ $o ] = get_option( $o ); delete_option( $o ); }
update_option( 'rau_sdr_lugar', array( 'lat' => 6.2442, 'lng' => -75.5812 ) ); // Medellín: FJ26
$S = ( time() - 20 ) * 1000;
$items = array( array( 'de' => 'K1ABC', 'cuadricula' => 'FN42', 'f' => 1234, 'snr' => -12 ), array( 'de' => 'HK4XYZ', 'f' => 800, 'snr' => 3 ), array( 'de' => '<...>', 'f' => 900 ), array( 'de' => 'HOLA', 'f' => 900 ) );

wp_set_current_user( 0 );
list( $st ) = enviar( array( 'modo' => 'FT4', 'ranura' => $S, 'dial' => 14080000, 'items' => $items ) );
ok( 401 === $st || 403 === $st, 'sin sesión no se acepta' );
$uid = wp_insert_user( array( 'user_login' => 'prueba-psk-' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password( 20 ), 'role' => 'subscriber' ) );
wp_set_current_user( $uid );
list( $st, $d ) = enviar( array( 'modo' => 'FT4', 'ranura' => $S, 'dial' => 14080000, 'items' => $items ) );
ok( 200 === $st && 2 === $d['nuevos'], 'FT4: entran los dos indicativos válidos (no el hash ni el texto)' );
list( , $d ) = enviar( array( 'modo' => 'FT4', 'ranura' => $S, 'dial' => 14080000, 'items' => $items ) );
ok( 0 === $d['nuevos'], 'el mismo indicativo en la misma banda no se repite en la hora' );
list( , $d ) = enviar( array( 'modo' => 'FT8', 'ranura' => $S, 'dial' => 14074000, 'items' => $items ) );
ok( 'kiwi' === ( $d['motivo'] ?? '' ), 'FT8 en 14074 no se reporta: ya lo reporta el Kiwi' );
list( , $d ) = enviar( array( 'modo' => 'FT8', 'ranura' => $S, 'dial' => 10136000, 'items' => $items ) );
ok( 2 === $d['nuevos'], 'FT8 en 30 m (que el Kiwi no cubre) sí' );
list( , $d ) = enviar( array( 'modo' => 'JS8', 'ranura' => $S, 'dial' => 7078000, 'items' => array( array( 'de' => 'KN4CRD', 'cuadricula' => 'EM73', 'f' => 1500, 'snr' => -8 ) ) ) );
ok( 1 === $d['nuevos'], 'JS8 también' );

// Envío a un socket UDP local
$srv = stream_socket_server( 'udp://127.0.0.1:0', $e1, $e2, STREAM_SERVER_BIND );
$puerto = (int) substr( strrchr( stream_socket_get_name( $srv, false ), ':' ), 1 );
add_filter( 'rau_psk_servidor', function() use ( $puerto ) { return '127.0.0.1:' . $puerto; } );
update_option( 'rau_psk_puerto', 0 );
$total = rau_psk_enviar_cola();
ok( 5 === $total, 'se envían los 5 reportes en la cola' );
$dg = stream_socket_recvfrom( $srv, 65535 );
ok( strlen( $dg ) > 50, 'el datagrama llegó al socket' );
$h = unpack( 'nver/nlargo/Nhora/Nseq/Nid', substr( $dg, 0, 16 ) );
ok( 10 === $h['ver'] && strlen( $dg ) === $h['largo'] && abs( $h['hora'] - time() ) < 5 && 0 === $h['seq'], 'encabezado IPFIX: versión 10, largo, hora y secuencia' );
// Conjuntos
$o = 16; $sets = array();
while ( $o + 4 <= strlen( $dg ) ) { $s = unpack( 'nid/nl', substr( $dg, $o, 4 ) ); $sets[ $s['id'] ] = substr( $dg, $o + 4, $s['l'] - 4 ); $o += $s['l']; if ( $s['l'] < 4 ) break; }
ok( isset( $sets[2], $sets[3], $sets[0x9992], $sets[0x9993] ), 'trae las dos plantillas (es el primer envío), el receptor y los emisores' );
ok( 0 === strlen( $dg ) % 4 || $o === strlen( $dg ), 'los conjuntos cubren todo el datagrama' );
$r = $sets[0x9992]; $p = 0; $campos = array(); for ( $i = 0; $i < 3; $i++ ) { $l = ord( $r[ $p ] ); $campos[] = substr( $r, $p + 1, $l ); $p += 1 + $l; }
ok( 'HK4RAU' === $campos[0] && 'FJ26ff' === $campos[1] && 0 === strpos( $campos[2], 'RAU receptor web' ), 'receptor: HK4RAU, FJ26ff (Medellín) y el programa (' . $campos[1] . ')' );
$t = $sets[0x9993]; $p = 0; $emisores = array();
while ( $p < strlen( $t ) && ord( $t[ $p ] ) > 0 ) {
	$l = ord( $t[ $p ] ); $c = substr( $t, $p + 1, $l ); $p += 1 + $l;
	$f = unpack( 'N', substr( $t, $p, 4 ) )[1]; $p += 4; $snr = unpack( 'c', $t[ $p ] )[1]; $p++;
	$l = ord( $t[ $p ] ); $m = substr( $t, $p + 1, $l ); $p += 1 + $l; $l = ord( $t[ $p ] ); $g = substr( $t, $p + 1, $l ); $p += 1 + $l;
	$src = ord( $t[ $p ] ); $p++; $ts = unpack( 'N', substr( $t, $p, 4 ) )[1]; $p += 4;
	$emisores[] = compact( 'c', 'f', 'snr', 'm', 'g', 'src', 'ts' );
}
ok( 5 === count( $emisores ), 'cinco registros de emisor decodificados' );
$k = $emisores[0];
ok( 'K1ABC' === $k['c'] && 14081234 === $k['f'] && -12 === $k['snr'] && 'FT4' === $k['m'] && 'FN42' === $k['g'] && 1 === $k['src'] && abs( $k['ts'] - $S / 1000 ) < 1, 'K1ABC: frecuencia real, SNR, modo, locator, «automático» y hora' );
ok( 'KN4CRD' === $emisores[4]['c'] && 'JS8' === $emisores[4]['m'], 'el de JS8 va con modo JS8' );
ok( 0 === rau_psk_enviar_cola(), 'con la cola vacía no se envía nada' );
// Antes de 5 minutos no se vuelve a enviar
enviar( array( 'modo' => 'FT4', 'ranura' => $S, 'dial' => 21140000, 'items' => array( array( 'de' => 'W1AW', 'f' => 1000, 'snr' => 0 ) ) ) );
ok( 0 === rau_psk_enviar_cola(), 'no más de un envío cada 5 minutos' );
ok( 1 === rau_psk_enviar_cola( time() + 300 ), 'a los 5 minutos sale el siguiente' );
$dg2 = stream_socket_recvfrom( $srv, 65535 ); $h2 = unpack( 'nver/nlargo/Nhora/Nseq/Nid', substr( $dg2, 0, 16 ) );
ok( 5 === $h2['seq'] && $h2['id'] === $h['id'], 'la secuencia cuenta reportes y el identificador se mantiene' );
ok( 0x9992 === unpack( 'n', substr( $dg2, 16, 2 ) )[1] || false !== strpos( $dg2, "\x00\x02\x00\x3C\x99\x93" ), 'el segundo envío también lleva plantillas (las tres primeras veces)' );

require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $uid );
delete_option( 'rau_psk_puerto' );
foreach ( $guardar as $o => $v ) { if ( false === $v ) delete_option( $o ); else update_option( $o, $v, false ); }
echo "\n$n comprobaciones, $fallos fallas\n";
exit( $fallos ? 1 : 0 );
