<?php
/**
 * Alertas para el tablero: sismos (USGS) y desastres (GDACS) en Colombia y alrededores, y las bandas
 * que oye el receptor HK4RAU. Son datos públicos de terceros: se muestran con su fuente y su hora, y
 * nunca se convierten en incidentes por sí solos.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function rau_em_alertas() {
	$c = get_transient( 'rau_em_alertas' );
	if ( false !== $c ) return $c;
	$out = array( 'sismos' => null, 'gdacs' => null, 'actualizado' => time() );
	// El feed del Servicio Geológico Colombiano no es público (responde acceso denegado); el USGS cubre
	// los sismos de Colombia con magnitud 2,5 o más
	$r = rau_remote_get( add_query_arg( array( 'format' => 'geojson', 'minlatitude' => -4.5, 'maxlatitude' => 13.5, 'minlongitude' => -82, 'maxlongitude' => -66,
		'minmagnitude' => 3.5, 'starttime' => gmdate( 'Y-m-d', time() - 7 * DAY_IN_SECONDS ), 'orderby' => 'time', 'limit' => 40 ), 'https://earthquake.usgs.gov/fdsnws/event/1/query' ), array( 'timeout' => 15 ), 5 );
	if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
		$j = json_decode( wp_remote_retrieve_body( $r ), true );
		$out['sismos'] = array();
		foreach ( isset( $j['features'] ) ? $j['features'] : array() as $f ) {
			$p = $f['properties']; $g = $f['geometry']['coordinates'];
			$out['sismos'][] = array( 'm' => round( (float) $p['mag'], 1 ), 'lugar' => sanitize_text_field( $p['place'] ), 't' => (int) ( $p['time'] / 1000 ),
				'lat' => round( (float) $g[1], 3 ), 'lng' => round( (float) $g[0], 3 ), 'prof' => round( (float) $g[2] ), 'url' => esc_url_raw( $p['url'] ) );
		}
	}
	$r = rau_remote_get( 'https://www.gdacs.org/gdacsapi/api/events/geteventlist/SEARCH?country=COL', array( 'timeout' => 15 ), 10 );
	if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
		$j = json_decode( wp_remote_retrieve_body( $r ), true );
		$out['gdacs'] = array();
		$tipos = array( 'EQ' => 'Sismo', 'TC' => 'Ciclón tropical', 'FL' => 'Inundación', 'VO' => 'Volcán', 'DR' => 'Sequía', 'WF' => 'Incendio forestal', 'TS' => 'Tsunami' );
		foreach ( isset( $j['features'] ) ? $j['features'] : array() as $f ) {
			$p = $f['properties'];
			$fin = isset( $p['todate'] ) ? strtotime( $p['todate'] ) : 0;
			if ( $fin && $fin < time() - 60 * DAY_IN_SECONDS ) continue;
			$out['gdacs'][] = array( 'tipo' => isset( $tipos[ $p['eventtype'] ] ) ? $tipos[ $p['eventtype'] ] : sanitize_text_field( $p['eventtype'] ),
				'nivel' => sanitize_key( $p['alertlevel'] ), 'nombre' => sanitize_text_field( $p['name'] ), 'desde' => isset( $p['fromdate'] ) ? strtotime( $p['fromdate'] ) : 0,
				'url' => isset( $p['url']['report'] ) ? esc_url_raw( $p['url']['report'] ) : '' );
		}
	}
	if ( null === $out['sismos'] && null === $out['gdacs'] ) {
		$u = get_option( 'rau_em_alertas_ultimo' );
		if ( $u ) { $u['viejo'] = true; set_transient( 'rau_em_alertas', $u, 2 * MINUTE_IN_SECONDS ); return $u; }
		return null;
	}
	update_option( 'rau_em_alertas_ultimo', $out, false );
	set_transient( 'rau_em_alertas', $out, 5 * MINUTE_IN_SECONDS );
	return $out;
}
/** Lo que se entrega al navegador: alertas más el resumen de lo que oye el receptor. */
function rau_em_alertas_publicas() {
	$a = rau_em_alertas();
	$o = function_exists( 'rau_sdr_oido' ) ? rau_sdr_oido() : null;
	$bandas = array();
	if ( $o ) foreach ( $o['bandas'] as $b => $x ) $bandas[ $b ] = array( 'n' => $x['n'], 'lejos' => $x['lejos'] ? $x['lejos']['km'] : 0 );
	return array( 'alertas' => $a, 'oido' => $o ? array( 'bandas' => $bandas, 'vigiladas' => $o['vigiladas'], 'actualizado' => $o['actualizado'], 'lugar' => $o['rx']['lugar'] ) : null );
}
