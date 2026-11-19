<?php
/**
 * Verificación en dos pasos para entrar al sitio (opcional, por persona).
 *
 * Quien la activa en su perfil escanea un código QR con una app de autenticación (Google Authenticator,
 * Aegis, 1Password, Authy…) y desde entonces, además de la contraseña, el formulario de entrada le pide
 * el código de 6 dígitos de la app. Recibe 8 códigos de respaldo de un solo uso por si pierde el celular.
 *
 * Solo afecta el formulario de entrada (wp-login.php). Las contraseñas de aplicación de la API no cambian:
 * son otra puerta, ya con su propia clave larga. Un administrador puede desactivarla a otra persona
 * desde su perfil si quedó por fuera.
 *
 * TOTP según RFC 6238 (HMAC-SHA1, 30 s, 6 dígitos), con un paso de tolerancia y sin reutilizar un código.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RAU_2FA_SECRETO', 'rau_2fa_secreto' );
define( 'RAU_2FA_PENDIENTE', 'rau_2fa_pendiente' );
define( 'RAU_2FA_RESPALDO', 'rau_2fa_respaldo' );
define( 'RAU_2FA_ULTIMO', 'rau_2fa_ultimo_paso' );

/* ── TOTP ── */
function rau_2fa_b32_decodificar( $s ) {
	$alf = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $s = strtoupper( preg_replace( '/[^A-Za-z2-7]/', '', $s ) );
	$bits = ''; $out = '';
	for ( $i = 0; $i < strlen( $s ); $i++ ) $bits .= str_pad( decbin( strpos( $alf, $s[ $i ] ) ), 5, '0', STR_PAD_LEFT );
	for ( $i = 0; $i + 8 <= strlen( $bits ); $i += 8 ) $out .= chr( bindec( substr( $bits, $i, 8 ) ) );
	return $out;
}
function rau_2fa_b32_codificar( $bin ) {
	$alf = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits = ''; $out = '';
	for ( $i = 0; $i < strlen( $bin ); $i++ ) $bits .= str_pad( decbin( ord( $bin[ $i ] ) ), 8, '0', STR_PAD_LEFT );
	for ( $i = 0; $i < strlen( $bits ); $i += 5 ) $out .= $alf[ bindec( str_pad( substr( $bits, $i, 5 ), 5, '0' ) ) ];
	return $out;
}
function rau_2fa_codigo( $secreto, $paso, $digitos = 6 ) {
	$clave = rau_2fa_b32_decodificar( $secreto );
	$h = hash_hmac( 'sha1', pack( 'N2', 0, $paso ), $clave, true );
	$o = ord( $h[19] ) & 0xf;
	$n = ( ( ord( $h[ $o ] ) & 0x7f ) << 24 ) | ( ord( $h[ $o + 1 ] ) << 16 ) | ( ord( $h[ $o + 2 ] ) << 8 ) | ord( $h[ $o + 3 ] );
	return str_pad( (string) ( $n % ( 10 ** $digitos ) ), $digitos, '0', STR_PAD_LEFT );
}
/* Devuelve el paso de tiempo que coincide (para no aceptar el mismo código dos veces) o false */
function rau_2fa_verificar( $secreto, $codigo, $ahora = null, $ultimo = 0 ) {
	$codigo = preg_replace( '/\D/', '', (string) $codigo );
	if ( 6 !== strlen( $codigo ) || ! $secreto ) return false;
	$t = (int) floor( ( $ahora ?: time() ) / 30 );
	foreach ( array( 0, -1, 1 ) as $d ) {
		$p = $t + $d;
		if ( $p > $ultimo && hash_equals( rau_2fa_codigo( $secreto, $p ), $codigo ) ) return $p;
	}
	return false;
}
function rau_2fa_activa( $uid ) { return (bool) get_user_meta( $uid, RAU_2FA_SECRETO, true ); }
function rau_2fa_nuevo_secreto() { return rau_2fa_b32_codificar( random_bytes( 20 ) ); }
function rau_2fa_nuevos_respaldos( $uid ) {
	$claros = array(); $hash = array();
	for ( $i = 0; $i < 8; $i++ ) { $c = strtolower( substr( rau_2fa_b32_codificar( random_bytes( 6 ) ), 0, 8 ) ); $c = substr( $c, 0, 4 ) . '-' . substr( $c, 4 ); $claros[] = $c; $hash[] = wp_hash_password( $c ); }
	update_user_meta( $uid, RAU_2FA_RESPALDO, $hash );
	return $claros;
}
function rau_2fa_usar_respaldo( $uid, $codigo ) {
	$codigo = strtolower( trim( (string) $codigo ) ); if ( ! preg_match( '/^[a-z2-7]{4}-?[a-z2-7]{4}$/', $codigo ) ) return false;
	if ( false === strpos( $codigo, '-' ) ) $codigo = substr( $codigo, 0, 4 ) . '-' . substr( $codigo, 4 );
	$lista = (array) get_user_meta( $uid, RAU_2FA_RESPALDO, true );
	foreach ( $lista as $i => $h ) {
		if ( $h && wp_check_password( $codigo, $h ) ) { unset( $lista[ $i ] ); update_user_meta( $uid, RAU_2FA_RESPALDO, array_values( $lista ) ); return true; }
	}
	return false;
}
/* Verificación de quien entra: código de la app o uno de respaldo */
function rau_2fa_comprobar_usuario( $uid, $codigo ) {
	$sec = get_user_meta( $uid, RAU_2FA_SECRETO, true );
	$p = rau_2fa_verificar( $sec, $codigo, null, (int) get_user_meta( $uid, RAU_2FA_ULTIMO, true ) );
	if ( false !== $p ) { update_user_meta( $uid, RAU_2FA_ULTIMO, $p ); return true; }
	return rau_2fa_usar_respaldo( $uid, $codigo );
}

/* ── Formulario de entrada ── */
add_action( 'login_form', function() {
	echo '<p><label for="rau_2fa">Código de verificación <small>(solo si activaste los dos pasos)</small></label>'
		. '<input type="text" name="rau_2fa" id="rau_2fa" class="input" inputmode="numeric" autocomplete="one-time-code" size="20" placeholder="123456 o código de respaldo"></p>';
} );
add_filter( 'authenticate', function( $user ) {
	// Solo el formulario de entrada: las contraseñas de aplicación y las cookies no pasan por aquí con «log»
	if ( ! ( $user instanceof WP_User ) || ! isset( $_POST['log'] ) || ! rau_2fa_activa( $user->ID ) ) return $user;
	$codigo = isset( $_POST['rau_2fa'] ) ? sanitize_text_field( wp_unslash( $_POST['rau_2fa'] ) ) : '';
	// Pocos intentos por persona: 6 cada 15 minutos
	$clave = 'rau_2fa_int_' . $user->ID; $n = (int) get_transient( $clave );
	if ( $n >= 6 ) return new WP_Error( 'rau_2fa_bloqueo', 'Demasiados códigos equivocados. Espera 15 minutos.' );
	if ( '' === $codigo ) return new WP_Error( 'rau_2fa_falta', '<strong>Falta el código de verificación.</strong> Tu cuenta tiene activados los dos pasos: escribe el código de 6 dígitos de tu app o uno de respaldo.' );
	if ( ! rau_2fa_comprobar_usuario( $user->ID, $codigo ) ) { set_transient( $clave, $n + 1, 15 * MINUTE_IN_SECONDS ); return new WP_Error( 'rau_2fa_mal', '<strong>Código de verificación incorrecto.</strong> Revisa que la hora del celular esté al día.' ); }
	delete_transient( $clave );
	return $user;
}, 40 );

/* ── Perfil: activar, ver respaldos, desactivar ── */
function rau_2fa_perfil( $u ) {
	$propio = get_current_user_id() === $u->ID; $activa = rau_2fa_activa( $u->ID );
	echo '<h2 id="rau-2fa">Verificación en dos pasos</h2><table class="form-table" role="presentation"><tr><th>Estado</th><td>';
	wp_nonce_field( 'rau_2fa_' . $u->ID, 'rau_2fa_nonce' );
	$nuevos = $propio ? get_transient( 'rau_2fa_mostrar_' . $u->ID ) : false;
	if ( $nuevos ) {
		delete_transient( 'rau_2fa_mostrar_' . $u->ID );
		echo '<div class="notice notice-success inline"><p><strong>Activada.</strong> Guarda estos códigos de respaldo en un lugar seguro: cada uno sirve una sola vez si pierdes el celular. No se vuelven a mostrar.</p><p><code style="font-size:1.1em;line-height:2">' . esc_html( implode( '   ', $nuevos ) ) . '</code></p></div>';
	}
	if ( $activa ) {
		$quedan = count( (array) get_user_meta( $u->ID, RAU_2FA_RESPALDO, true ) );
		echo '<p><strong>Activada.</strong> Quedan ' . (int) $quedan . ' códigos de respaldo.</p>';
		if ( $propio ) {
			echo '<p><label>Para desactivarla o generar respaldos nuevos, escribe un código de tu app: <input type="text" name="rau_2fa_codigo" inputmode="numeric" autocomplete="one-time-code" size="10" aria-label="Código de la app"></label></p>';
			echo '<p><label><input type="radio" name="rau_2fa_accion" value="respaldos"> Generar códigos de respaldo nuevos</label><br><label><input type="radio" name="rau_2fa_accion" value="desactivar"> Desactivar la verificación en dos pasos</label></p>';
		} elseif ( current_user_can( 'edit_users' ) ) {
			echo '<p><label><input type="checkbox" name="rau_2fa_quitar" value="1"> Desactivarla para esta persona (por ejemplo, si perdió el celular y los respaldos)</label></p>';
		}
	} elseif ( $propio ) {
		$sec = get_user_meta( $u->ID, RAU_2FA_PENDIENTE, true );
		if ( ! $sec ) { $sec = rau_2fa_nuevo_secreto(); update_user_meta( $u->ID, RAU_2FA_PENDIENTE, $sec ); }
		$uri = 'otpauth://totp/' . rawurlencode( 'RAU:' . $u->user_login ) . '?secret=' . $sec . '&issuer=RAU&digits=6&period=30';
		echo '<p>Desactivada. Actívala para que, además de la contraseña, se pida un código del celular al entrar.</p>';
		echo '<ol><li>Escanea este código con tu app de autenticación (Google Authenticator, Aegis, 1Password, Authy…):<div id="rau-2fa-qr" data-uri="' . esc_attr( $uri ) . '" style="margin:.6em 0;background:#fff;display:inline-block;padding:8px"></div><br><small>¿No puedes escanear? Escribe esta clave en la app: <code>' . esc_html( trim( chunk_split( $sec, 4, ' ' ) ) ) . '</code></small></li>';
		echo '<li><label>Escribe el código de 6 dígitos que muestra la app: <input type="text" name="rau_2fa_codigo" inputmode="numeric" autocomplete="one-time-code" size="10" aria-label="Código de la app"></label></li>';
		echo '<li>Pulsa «Actualizar perfil».</li></ol><input type="hidden" name="rau_2fa_accion" value="activar">';
		echo '<script src="' . esc_url( get_template_directory_uri() . '/assets/vendor/qrcode/qrcode.js?ver=1.4.4' ) . '"></script>';
		echo '<script>(function(){ var e=document.getElementById("rau-2fa-qr"); if(!e||!window.qrcode) return; var q=qrcode(0,"M"); q.addData(e.dataset.uri); q.make(); e.innerHTML=q.createSvgTag(4,0); })();</script>';
	} else {
		echo '<p>Desactivada.</p>';
	}
	echo '</td></tr></table>';
}
add_action( 'show_user_profile', 'rau_2fa_perfil' );
add_action( 'edit_user_profile', 'rau_2fa_perfil' );

function rau_2fa_guardar( $uid ) {
	if ( ! isset( $_POST['rau_2fa_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rau_2fa_nonce'] ) ), 'rau_2fa_' . $uid ) ) return;
	if ( get_current_user_id() !== $uid ) {
		if ( ! empty( $_POST['rau_2fa_quitar'] ) && current_user_can( 'edit_users' ) ) { delete_user_meta( $uid, RAU_2FA_SECRETO ); delete_user_meta( $uid, RAU_2FA_RESPALDO ); }
		return;
	}
	$accion = isset( $_POST['rau_2fa_accion'] ) ? sanitize_key( $_POST['rau_2fa_accion'] ) : '';
	$codigo = isset( $_POST['rau_2fa_codigo'] ) ? sanitize_text_field( wp_unslash( $_POST['rau_2fa_codigo'] ) ) : '';
	if ( ! $accion || '' === $codigo ) return;
	if ( 'activar' === $accion ) {
		$sec = get_user_meta( $uid, RAU_2FA_PENDIENTE, true );
		$p = rau_2fa_verificar( $sec, $codigo );
		if ( false === $p ) { add_action( 'user_profile_update_errors', function( $e ) { $e->add( 'rau_2fa', 'El código de verificación no coincide: la verificación en dos pasos sigue desactivada. Revisa la hora del celular e inténtalo de nuevo.' ); } ); return; }
		update_user_meta( $uid, RAU_2FA_SECRETO, $sec ); update_user_meta( $uid, RAU_2FA_ULTIMO, $p ); delete_user_meta( $uid, RAU_2FA_PENDIENTE );
		set_transient( 'rau_2fa_mostrar_' . $uid, rau_2fa_nuevos_respaldos( $uid ), 10 * MINUTE_IN_SECONDS );
		return;
	}
	if ( ! rau_2fa_activa( $uid ) ) return;
	if ( ! rau_2fa_comprobar_usuario( $uid, $codigo ) ) { add_action( 'user_profile_update_errors', function( $e ) { $e->add( 'rau_2fa', 'El código de verificación no coincide: no se cambió nada.' ); } ); return; }
	if ( 'desactivar' === $accion ) { delete_user_meta( $uid, RAU_2FA_SECRETO ); delete_user_meta( $uid, RAU_2FA_RESPALDO ); }
	elseif ( 'respaldos' === $accion ) set_transient( 'rau_2fa_mostrar_' . $uid, rau_2fa_nuevos_respaldos( $uid ), 10 * MINUTE_IN_SECONDS );
}
add_action( 'personal_options_update', 'rau_2fa_guardar' );
add_action( 'edit_user_profile_update', 'rau_2fa_guardar' );

/* Recordatorio en el escritorio para quien puede publicar y aún no la activa */
add_action( 'admin_notices', function() {
	$uid = get_current_user_id();
	if ( ! $uid || ! current_user_can( 'edit_others_posts' ) || rau_2fa_activa( $uid ) || get_user_meta( $uid, 'rau_2fa_aviso_visto', true ) ) return;
	$s = get_current_screen(); if ( $s && 'profile' === $s->id ) { update_user_meta( $uid, 'rau_2fa_aviso_visto', 1 ); return; }
	echo '<div class="notice notice-info"><p>Protege tu cuenta: activa la <a href="' . esc_url( admin_url( 'profile.php#rau-2fa' ) ) . '">verificación en dos pasos</a>. Toma un minuto con una app de autenticación en el celular.</p></div>';
} );
