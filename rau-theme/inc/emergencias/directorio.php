<?php
/**
 * Directorio de frecuencias del centro de mando.
 *
 * Separa lo que en un incidente no se debe confundir:
 *  - actividad_ra   Centros de actividad de radioaficionados (puntos de reunión, NO canales exclusivos).
 *  - llamada_ra     Frecuencias de llamada de radioaficionados (tampoco son de emergencia).
 *  - repetidora_ra  Repetidoras de radioaficionados (las de RAU salen del registro de repetidoras del sitio).
 *  - institucional  Frecuencias de organismos. Vacío a propósito: no se publican sin fuente oficial.
 *  - otro_servicio  Canales de servicios distintos al de radioaficionados (aeronáutico, marítimo).
 *  - pendiente      Información histórica o por validar (p. ej. repetidoras reportadas en 2026 sin tono).
 *
 * Cada entrada lleva fuente, jurisdicción, condiciones y fecha de verificación. Los cambios los hacen
 * coordinadores o administradores con cuenta, exigen fuente y quedan en la auditoría con el antes y el
 * después.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function rau_em_directorio_categorias() {
	return array(
		'actividad_ra'  => 'Centros de actividad de radioaficionados',
		'llamada_ra'    => 'Frecuencias de llamada de radioaficionados',
		'repetidora_ra' => 'Repetidoras de radioaficionados',
		'institucional' => 'Frecuencias institucionales verificadas',
		'otro_servicio' => 'Canales de otros servicios',
		'pendiente'     => 'Pendiente de validación o histórica',
	);
}

function rau_em_directorio_inicial() {
	$iaru = 'Reportes de la coordinación de emergencias de la IARU Región 2 (CO2JC) sobre el sismo del 10 de agosto de 2026, publicados por la ARRL y la IARU Región 1';
	$ra   = 'Servicio de radioaficionados · IARU Región 2 · Colombia';
	$cond = 'No es un canal exclusivo: punto de reunión de la red de emergencia de la Liga Colombiana de Radioaficionados. El tráfico puede estar hasta ±20 kHz. Requiere licencia de radioaficionado.';
	$uit  = 'Reglamento de Radiocomunicaciones de la UIT, apéndice 15 (frecuencias de socorro y seguridad)';
	$f = function( $id, $cat, $frec, $unidad, $banda, $modo, $uso, $fuente, $jur, $cond, $verif, $extra = array() ) {
		return array_merge( array( 'id' => $id, 'cat' => $cat, 'f' => $frec, 'unidad' => $unidad, 'banda' => $banda, 'modo' => $modo, 'uso' => $uso,
			'fuente' => $fuente, 'jurisdiccion' => $jur, 'condiciones' => $cond, 'verificado' => $verif, 'tono' => '', 'desplazamiento' => '' ), $extra );
	};
	return array(
		$f( 'ra-7060', 'actividad_ra', '7.060', 'MHz', '40 m', 'LSB', 'Red nacional de emergencia, la principal. De día cubre Colombia por onda de cielo casi vertical (NVIS).', $iaru, $ra, $cond, '2026-10-25' ),
		$f( 'ra-7240', 'actividad_ra', '7.240', 'MHz', '40 m', 'LSB', 'Red nacional, alterna.', $iaru, $ra, $cond, '2026-10-25' ),
		$f( 'ra-7275', 'actividad_ra', '7.275', 'MHz', '40 m', 'LSB', 'Red nacional, alterna.', $iaru, $ra, $cond, '2026-10-25' ),
		$f( 'ra-3985', 'actividad_ra', '3.985', 'MHz', '80 m', 'LSB', 'Red nacional de noche, cuando 40 m salta lejos y deja de oírse cerca. En Colombia es 3.985, no 3.750.', $iaru, $ra, $cond, '2026-10-25' ),
		$f( 'ra-14300', 'actividad_ra', '14.300', 'MHz', '20 m', 'USB', 'Enlace internacional (centro global de actividad de emergencia).', $iaru, $ra, $cond, '2026-10-25' ),
		$f( 'ra-18160', 'actividad_ra', '18.160', 'MHz', '17 m', 'USB', 'Enlace internacional, alterna de 20 m.', $iaru, $ra, $cond, '2026-10-25' ),
		$f( 'ra-21360', 'actividad_ra', '21.360', 'MHz', '15 m', 'USB', 'Enlace internacional de día.', $iaru, $ra, $cond, '2026-10-25' ),
		$f( 'ra-146520', 'llamada_ra', '146.520', 'MHz', '2 m', 'FM', 'Símplex de llamada: para encontrarse cuando no hay repetidora. Se pasa a otra frecuencia para conversar. La Liga pidió mantenerla despejada en agosto de 2026.', $iaru . '; plan de banda de 2 m de la IARU Región 2', $ra, 'Frecuencia de llamada, no exclusiva de emergencia. Requiere licencia de radioaficionado.', '2026-10-25' ),
		$f( 'os-121500', 'otro_servicio', '121.500', 'MHz', 'VHF aeronáutica', 'AM', 'Socorro aeronáutico.', $uit, 'Servicio móvil aeronáutico', 'Escuchar está permitido. Transmitir requiere la licencia de ese servicio.', '2026-10-26' ),
		$f( 'os-156800', 'otro_servicio', '156.800', 'MHz', 'VHF marítima, canal 16', 'FM', 'Socorro, seguridad y llamada marítima.', $uit, 'Servicio móvil marítimo', 'Escuchar está permitido. Transmitir requiere la licencia de ese servicio.', '2026-10-26' ),
		$f( 'os-2182', 'otro_servicio', '2.182', 'MHz', 'MF marítima', 'USB', 'Socorro y llamada marítima en onda media.', $uit, 'Servicio móvil marítimo', 'Escuchar está permitido. Transmitir requiere la licencia de ese servicio.', '2026-10-26' ),
		$f( 'pd-147120', 'pendiente', '147.120', 'MHz', '2 m', 'FM', 'Repetidora Cerro El Castillo (Armenia – Pereira), usada en el sismo de agosto de 2026.', 'Reportes de ARRL y UBA sobre el sismo de agosto de 2026', $ra, 'Tono y desplazamiento sin confirmar: verificar con la asociación local antes de usarla.', '' ),
		$f( 'pd-146910', 'pendiente', '146.910', 'MHz', '2 m', 'FM', 'Repetidora Las Cruces (Risaralda), enlazada por Radio Club de Antioquia en agosto de 2026.', 'Reportes de ARRL y UBA sobre el sismo de agosto de 2026', $ra, 'Tono y desplazamiento sin confirmar.', '' ),
		$f( 'pd-146930', 'pendiente', '146.930', 'MHz', '2 m', 'FM', 'Repetidora Las Cruces (Risaralda).', 'Reportes de ARRL y UBA sobre el sismo de agosto de 2026', $ra, 'Tono y desplazamiento sin confirmar.', '' ),
		$f( 'pd-146880', 'pendiente', '146.880', 'MHz', '2 m', 'FM', 'Repetidora nueva del Eje Cafetero (2026).', 'Reporte de ARRL sobre el sismo de agosto de 2026', $ra, 'Ubicación, tono y desplazamiento sin confirmar.', '' ),
	);
}

/** Directorio completo: lo guardado más las repetidoras de RAU (que se editan en Repetidoras). */
function rau_em_directorio() {
	$d = get_option( 'rau_em_directorio' );
	if ( ! is_array( $d ) ) { $d = rau_em_directorio_inicial(); update_option( 'rau_em_directorio', $d, false ); }
	foreach ( rau_get_repeaters_data() as $r ) {
		$d[] = array( 'id' => 'rau-' . sanitize_key( $r['callsign'] . $r['freq'] ), 'cat' => 'repetidora_ra', 'f' => (string) $r['freq'], 'unidad' => 'MHz', 'banda' => '2 m', 'modo' => $r['mode'] ?: 'FM',
			'uso' => $r['name'] . ( $r['location'] ? ' · ' . $r['location'] : '' ), 'fuente' => 'Registro de repetidoras de RAU (hk4rau.com)', 'jurisdiccion' => 'Servicio de radioaficionados · Colombia',
			'condiciones' => 'Estado: ' . $r['status'] . '. Que figure aquí no garantiza que esté en el aire: confirma con un reporte reciente.', 'verificado' => '',
			'tono' => (string) $r['tone'], 'desplazamiento' => (string) $r['offset'], 'lat' => $r['lat'], 'lng' => $r['lng'], 'fijo' => true );
	}
	return $d;
}

/** Lista simple que usa el panel de Herramientas: centros de actividad y llamada de radioaficionados. */
function rau_em_frecuencias() {
	$out = array();
	foreach ( rau_em_directorio() as $x ) {
		if ( in_array( $x['cat'], array( 'actividad_ra', 'llamada_ra' ), true ) ) $out[] = array( 'f' => $x['f'], 'b' => $x['banda'], 'm' => $x['modo'], 'uso' => $x['uso'], 'cat' => $x['cat'] );
	}
	return $out;
}

/** Crea o cambia una entrada. Exige fuente. Devuelve la entrada o un WP_Error. */
function rau_em_directorio_guardar( $e, $actor ) {
	$cats = rau_em_directorio_categorias();
	$x = array();
	foreach ( array( 'id', 'cat', 'f', 'unidad', 'banda', 'modo', 'uso', 'fuente', 'jurisdiccion', 'condiciones', 'verificado', 'tono', 'desplazamiento' ) as $k ) {
		$x[ $k ] = isset( $e[ $k ] ) ? mb_substr( sanitize_text_field( (string) $e[ $k ] ), 0, 'uso' === $k || 'condiciones' === $k || 'fuente' === $k ? 400 : 60 ) : '';
	}
	if ( ! isset( $cats[ $x['cat'] ] ) || 'repetidora_ra' === $x['cat'] && 0 === strpos( $x['id'], 'rau-' ) ) return new WP_Error( 'cat', 'Categoría no válida' );
	if ( ! preg_match( '/^\d{1,5}(\.\d{1,4})?$/', $x['f'] ) ) return new WP_Error( 'f', 'Frecuencia no válida' );
	if ( ! in_array( $x['unidad'], array( 'kHz', 'MHz' ), true ) ) $x['unidad'] = 'MHz';
	if ( mb_strlen( $x['fuente'] ) < 5 ) return new WP_Error( 'fuente', 'Toda entrada necesita su fuente' );
	if ( 'institucional' === $x['cat'] && ! $x['verificado'] ) return new WP_Error( 'verificado', 'Una frecuencia institucional necesita fecha de verificación contra la fuente oficial' );
	if ( $x['verificado'] && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $x['verificado'] ) ) return new WP_Error( 'verificado', 'Fecha de verificación en formato AAAA-MM-DD' );
	$lista = get_option( 'rau_em_directorio' ) ?: rau_em_directorio_inicial();
	$antes = null;
	if ( $x['id'] ) {
		foreach ( $lista as $i => $y ) if ( $y['id'] === $x['id'] ) { $antes = $y; $lista[ $i ] = $x; }
		if ( ! $antes ) return new WP_Error( 'id', 'No existe esa entrada' );
	} else {
		$x['id'] = 'u-' . strtolower( rau_em_codigo_nuevo( 8 ) );
		$lista[] = $x;
	}
	$x['mod'] = array( 'por' => $actor['nombre'], 'ts' => time() );
	foreach ( $lista as $i => $y ) if ( $y['id'] === $x['id'] ) $lista[ $i ] = $x;
	update_option( 'rau_em_directorio', $lista, false );
	rau_em_auditar( '', $actor, $antes ? 'directorio_cambio' : 'directorio_alta', $x['id'], array( 'antes' => $antes, 'despues' => $x ) );
	return $x;
}
function rau_em_directorio_borrar( $id, $actor ) {
	$lista = get_option( 'rau_em_directorio' ) ?: rau_em_directorio_inicial();
	$antes = null;
	$lista = array_values( array_filter( $lista, function( $y ) use ( $id, &$antes ) { if ( $y['id'] === $id ) { $antes = $y; return false; } return true; } ) );
	if ( ! $antes ) return new WP_Error( 'id', 'No existe esa entrada' );
	update_option( 'rau_em_directorio', $lista, false );
	rau_em_auditar( '', $actor, 'directorio_baja', $id, array( 'antes' => $antes ) );
	return true;
}
