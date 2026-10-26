<?php
/**
 * Validación de registros contra assets/emergencias/esquema.json (el mismo archivo que usa el
 * navegador). Un registro que no cumple el esquema, o que pide un permiso que el actor no tiene, se
 * rechaza con el motivo: nunca se guarda a medias ni se descarta en silencio.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function rau_em_esquema() {
	static $e = null;
	if ( null === $e ) $e = json_decode( (string) file_get_contents( get_template_directory() . '/assets/emergencias/esquema.json' ), true );
	return $e;
}

/** Valida un campo; devuelve array( ok, valor_limpio, error ). */
function rau_em_validar_campo( $spec, $v, $nombre ) {
	$esq  = rau_em_esquema();
	$tipo = $spec[0];
	$arg  = isset( $spec[1] ) ? $spec[1] : null;
	switch ( $tipo ) {
		case 's':
		case 't':
			if ( ! is_string( $v ) && ! is_int( $v ) && ! is_float( $v ) ) return array( false, null, "$nombre debe ser texto" );
			$v = 't' === $tipo ? sanitize_textarea_field( (string) $v ) : sanitize_text_field( (string) $v );
			if ( mb_strlen( $v ) > (int) $arg ) return array( false, null, "$nombre supera $arg caracteres" );
			return array( true, $v, '' );
		case 'n':
			if ( ! is_int( $v ) && ! is_float( $v ) && ! ( is_string( $v ) && is_numeric( $v ) ) ) return array( false, null, "$nombre debe ser un número" );
			$v = $v + 0;
			if ( ! is_finite( (float) $v ) || ( $arg && ( $v < $arg[0] || $v > $arg[1] ) ) ) return array( false, null, "$nombre fuera de rango" );
			return array( true, $v, '' );
		case 'b':
			if ( ! is_bool( $v ) ) return array( false, null, "$nombre debe ser sí o no" );
			return array( true, $v, '' );
		case 'e':
			if ( ! in_array( $v, $esq['enums'][ $arg ], true ) ) return array( false, null, "$nombre: valor no permitido" );
			return array( true, $v, '' );
		case 'u':
			if ( ! is_string( $v ) || ! preg_match( '/^[a-z0-9-]{8,36}$/', $v ) ) return array( false, null, "$nombre: referencia inválida" );
			return array( true, $v, '' );
		case 'ind':
			$v = rau_em_indicativo( $v );
			if ( strlen( $v ) < 3 ) return array( false, null, "$nombre: indicativo inválido" );
			return array( true, $v, '' );
		case 'a':
			if ( ! is_array( $v ) || count( $v ) > (int) $arg ) return array( false, null, "$nombre: lista inválida" );
			$out = array();
			foreach ( $v as $x ) {
				if ( ! is_string( $x ) ) return array( false, null, "$nombre: lista inválida" );
				$out[] = mb_substr( sanitize_text_field( $x ), 0, 40 );
			}
			return array( true, $out, '' );
	}
	return array( false, null, "$nombre: tipo desconocido" );
}

/**
 * Valida un registro completo para una operación y un actor.
 * Devuelve array( 'ok' => bool, 'datos' => array, 'error' => string ).
 */
function rau_em_validar_evento( $e, $op, $actor ) {
	$esq = rau_em_esquema();
	if ( ! is_array( $e ) ) return array( 'ok' => false, 'error' => 'Registro mal formado' );
	$t = isset( $e['t'] ) ? (string) $e['t'] : '';
	if ( ! isset( $esq['tipos'][ $t ] ) ) return array( 'ok' => false, 'error' => 'Tipo de registro desconocido' );
	$def = $esq['tipos'][ $t ];
	$d   = isset( $e['d'] ) && is_array( $e['d'] ) ? $e['d'] : array();
	$sim = 'simulacro' === $op['tipo'];
	if ( ! empty( $def['soloSim'] ) && ! $sim ) return array( 'ok' => false, 'error' => 'Los registros de simulación no se aceptan en una operación real' );
	if ( isset( $d['sim'] ) && ! $sim ) return array( 'ok' => false, 'error' => 'Los registros de simulación no se aceptan en una operación real' );
	// Permiso: el del tipo, o uno mayor según el valor de un campo
	$perm = $def['perm'];
	if ( isset( $def['permPor'] ) ) {
		$c = $def['permPor']['campo'];
		if ( isset( $d[ $c ] ) && is_string( $d[ $c ] ) && isset( $def['permPor']['valores'][ $d[ $c ] ] ) ) $perm = $def['permPor']['valores'][ $d[ $c ] ];
	}
	if ( ! rau_em_puede( $actor, $perm ) ) return array( 'ok' => false, 'error' => 'Tu rol no permite este registro (' . $perm . ')' );
	$campos = $def['campos'] + $esq['comunes'];
	$limpio = array();
	foreach ( $d as $k => $v ) {
		if ( ! isset( $campos[ $k ] ) ) return array( 'ok' => false, 'error' => "Campo no permitido: $k" );
		if ( null === $v || '' === $v ) continue;
		list( $ok, $val, $err ) = rau_em_validar_campo( $campos[ $k ], $v, $k );
		if ( ! $ok ) return array( 'ok' => false, 'error' => $err );
		$limpio[ $k ] = $val;
	}
	foreach ( $campos as $k => $spec ) {
		if ( ! empty( $spec[2] ) && ( ! isset( $limpio[ $k ] ) || '' === $limpio[ $k ] ) ) return array( 'ok' => false, 'error' => "Falta el campo obligatorio: $k" );
	}
	// Coordenadas: van en pareja
	if ( isset( $limpio['lat'] ) xor isset( $limpio['lng'] ) ) return array( 'ok' => false, 'error' => 'La ubicación necesita latitud y longitud' );
	return array( 'ok' => true, 'datos' => $limpio, 'perm' => $perm );
}

/**
 * Lo que un observador no debe ver: datos de personas (bienestar). El resto de la operación sí.
 */
function rau_em_redactar_para( $tipo, $datos, $actor ) {
	if ( rau_em_puede( $actor, 'operar' ) ) return $datos;
	if ( 'persona' === $tipo ) return array( 'nombre' => 'Persona registrada', 'estado' => $datos['estado'] ?? '', 'redactado' => true );
	if ( 'personaact' === $tipo ) return array( 'p' => $datos['p'] ?? '', 'estado' => $datos['estado'] ?? '', 'redactado' => true );
	return $datos;
}
