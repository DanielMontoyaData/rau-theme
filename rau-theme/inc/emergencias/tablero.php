<?php
/**
 * Tablero público de solo lectura de una operación, para autoridades y organismos de socorro.
 *
 * Quien coordina lo publica desde «Informes»: su dispositivo arma el resumen (em-core.js tableroPublico:
 * incidentes vigentes, prioridades, estado de la red y de las estaciones, sin nombres, mensajes, notas ni
 * coordenadas exactas) y lo envía aquí; mientras tenga la operación abierta lo actualiza solo. El enlace
 * /tablero-emergencia/<token>/ no necesita cuenta. Se deja de publicar con un botón, y el enlace muere.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function rau_em_tablero_opcion( $cod ) { return 'rau_em_tablero_' . strtoupper( $cod ); }
function rau_em_tablero_por_token( $token ) {
	if ( ! preg_match( '/^[a-f0-9]{24}$/', (string) $token ) ) return null;
	$cod = get_option( 'rau_em_tablero_t_' . $token );
	if ( ! $cod ) return null;
	$t = get_option( rau_em_tablero_opcion( $cod ) );
	return is_array( $t ) && hash_equals( $t['token'], $token ) ? $t : null;
}
/* Lo que llega del navegador se reconstruye campo por campo: nada que no esté en esta lista pasa */
function rau_em_tablero_limpiar( $r ) {
	$txt = function( $v, $n ) { return mb_substr( sanitize_text_field( (string) $v ), 0, $n ); };
	$num = function( $v ) { return is_numeric( $v ) ? 0 + $v : 0; };
	$enum = function( $v, $ok ) { return in_array( $v, $ok, true ) ? $v : ''; };
	$prios = array( 'critica', 'alta', 'media', 'baja' ); $estados = array( 'abierto', 'en_atencion', 'resuelto', 'cerrado' ); $verifs = array( 'reportado', 'en_verificacion', 'verificado', 'descartado' );
	$cuenta = function( $a, $claves ) use ( $num ) { $o = array(); foreach ( $claves as $k ) $o[ $k ] = max( 0, (int) $num( $a[ $k ] ?? 0 ) ); return $o; };
	$out = array(
		'op' => array( 'nombre' => $txt( $r['op']['nombre'] ?? '', 120 ), 'zona' => $txt( $r['op']['zona'] ?? '', 120 ), 'tipo' => 'simulacro' === ( $r['op']['tipo'] ?? '' ) ? 'simulacro' : 'real', 'estado' => 'cerrado' === ( $r['op']['estado'] ?? '' ) ? 'cerrado' : 'abierto' ),
		'porPrio' => $cuenta( (array) ( $r['porPrio'] ?? array() ), $prios ), 'porEstado' => $cuenta( (array) ( $r['porEstado'] ?? array() ), $estados ), 'porVerif' => $cuenta( (array) ( $r['porVerif'] ?? array() ), $verifs ),
		'estaciones' => array( 'total' => max( 0, (int) ( $r['estaciones']['total'] ?? 0 ) ), 'activas' => max( 0, (int) ( $r['estaciones']['activas'] ?? 0 ) ) ),
		'red' => ! empty( $r['red'] ) ? array( 'e' => $enum( $r['red']['e'] ?? '', array( 'abierta', 'dirigida', 'cerrada', 'libre' ) ), 'canal' => $txt( $r['red']['canal'] ?? '', 80 ) ) : null,
		'vigentes' => array(),
	);
	foreach ( array_slice( (array) ( $r['vigentes'] ?? array() ), 0, 60 ) as $x ) {
		$i = array( 'tipo' => $txt( $x['tipo'] ?? '', 40 ), 'titulo' => $txt( $x['titulo'] ?? '', 140 ), 'lugar' => $txt( $x['lugar'] ?? '', 120 ), 'prio' => $enum( $x['prio'] ?? '', $prios ),
			'verif' => $enum( $x['verif'] ?? '', $verifs ), 'estado' => $enum( $x['estado'] ?? '', $estados ), 'recibido' => (int) $num( $x['recibido'] ?? 0 ), 'act' => (int) $num( $x['act'] ?? 0 ) );
		if ( isset( $x['lat'], $x['lng'] ) && is_numeric( $x['lat'] ) && is_numeric( $x['lng'] ) ) { $i['lat'] = round( max( -90, min( 90, (float) $x['lat'] ) ), 2 ); $i['lng'] = round( max( -180, min( 180, (float) $x['lng'] ) ), 2 ); }
		if ( '' !== $i['titulo'] ) $out['vigentes'][] = $i;
	}
	return $out;
}

add_action( 'rest_api_init', function() {
	$cod = '(?P<cod>[A-Za-z0-9]{4,12})';
	register_rest_route( 'rau-em/v1', "/operaciones/$cod/tablero", array(
		array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function( WP_REST_Request $req ) {
			$c = rau_em_contexto( $req, 'coordinar' ); if ( $c instanceof WP_REST_Response ) return $c;
			list( $op, $a ) = $c;
			if ( rau_em_limite( 'tab' . $op['codigo'], 120, HOUR_IN_SECONDS ) ) return rau_em_error( 'Demasiadas actualizaciones del tablero', 429 );
			$p = $req->get_json_params();
			$prev = get_option( rau_em_tablero_opcion( $op['codigo'] ) );
			$token = is_array( $prev ) && ! empty( $prev['token'] ) ? $prev['token'] : bin2hex( random_bytes( 12 ) );
			$t = array( 'token' => $token, 'cod' => $op['codigo'], 'ts' => time(), 'por' => $a['nombre'], 'resumen' => rau_em_tablero_limpiar( (array) ( $p['resumen'] ?? array() ) ) );
			update_option( rau_em_tablero_opcion( $op['codigo'] ), $t, false );
			update_option( 'rau_em_tablero_t_' . $token, $op['codigo'], false );
			if ( ! $prev ) rau_em_auditar( $op['codigo'], $a, 'tablero_publicado', '', array() );
			return array( 'ok' => true, 'url' => home_url( '/tablero-emergencia/' . $token . '/' ) );
		} ),
		array( 'methods' => 'DELETE', 'permission_callback' => '__return_true', 'callback' => function( WP_REST_Request $req ) {
			$c = rau_em_contexto( $req, 'coordinar' ); if ( $c instanceof WP_REST_Response ) return $c;
			list( $op, $a ) = $c;
			$prev = get_option( rau_em_tablero_opcion( $op['codigo'] ) );
			if ( is_array( $prev ) ) { delete_option( 'rau_em_tablero_t_' . $prev['token'] ); delete_option( rau_em_tablero_opcion( $op['codigo'] ) ); rau_em_auditar( $op['codigo'], $a, 'tablero_retirado', '', array() ); }
			return array( 'ok' => true );
		} ),
	) );
	register_rest_route( 'rau-em/v1', '/tablero/(?P<t>[a-f0-9]{24})', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function( WP_REST_Request $req ) {
		$t = rau_em_tablero_por_token( $req['t'] );
		if ( ! $t ) return new WP_REST_Response( array( 'error' => 'Este tablero no existe o ya no se publica' ), 404 );
		$r = rest_ensure_response( array( 'ts' => $t['ts'], 'resumen' => $t['resumen'] ) ); $r->header( 'Cache-Control', 'no-store' ); $r->header( 'X-Robots-Tag', 'noindex' );
		return $r;
	} ) );
} );

add_action( 'init', function() { add_rewrite_rule( '^tablero-emergencia/([a-f0-9]{24})/?$', 'index.php?rau_tablero=$matches[1]', 'top' ); } );
add_filter( 'query_vars', function( $v ) { $v[] = 'rau_tablero'; return $v; } );
add_action( 'template_redirect', function() {
	$token = get_query_var( 'rau_tablero' ); if ( ! $token ) return;
	$t = rau_em_tablero_por_token( $token );
	nocache_headers(); header( 'X-Robots-Tag: noindex' );
	if ( ! $t ) status_header( 404 );
	$api = rest_url( 'rau-em/v1/tablero/' . $token );
	?><!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?php echo esc_html( $t ? 'Tablero · ' . $t['resumen']['op']['nombre'] : 'Tablero no disponible' ); ?> — RAU</title>
<style>
:root{--f:#0b0f17;--c:#121a26;--b:#243044;--t:#e6edf5;--m:#93a3b8;--p:#22d3ee;--r:#f87171;--a:#fbbf24;--v:#4ade80}
@media (prefers-color-scheme: light){:root{--f:#f5f7fa;--c:#fff;--b:#d6dde6;--t:#111827;--m:#526077;--p:#0e7490;--r:#b91c1c;--a:#a16207;--v:#15803d}}
*{box-sizing:border-box}body{margin:0;background:var(--f);color:var(--t);font:15px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
main{max-width:1100px;margin:0 auto;padding:20px 16px 40px}h1{margin:.2em 0;font-size:1.6rem}.m{color:var(--m)}.sim{background:var(--a);color:#000;font-weight:700;padding:6px 12px;border-radius:8px;display:inline-block;margin-bottom:8px}
.g{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin:16px 0}.k{background:var(--c);border:1px solid var(--b);border-radius:12px;padding:12px}.k b{display:block;font-size:1.8rem}
table{width:100%;border-collapse:collapse;background:var(--c);border:1px solid var(--b);border-radius:12px;overflow:hidden}th,td{padding:8px 10px;border-bottom:1px solid var(--b);text-align:left;vertical-align:top}th{font-size:.8rem;color:var(--m);text-transform:uppercase}
.p-critica{color:var(--r);font-weight:700}.p-alta{color:var(--a);font-weight:700}.viejo{color:var(--a)}@media (max-width:640px){td:nth-child(5),th:nth-child(5){display:none}}
</style></head><body><main>
<p class="m" style="margin:0">RAU · Radioaficionados Unidos — Centro de mando de emergencias</p>
<?php if ( ! $t ) : ?><h1>Este tablero no existe o ya no se publica</h1><p class="m">Pide el enlace actualizado a quien coordina la operación.</p>
<?php else : ?>
<div id="tab" data-api="<?php echo esc_url( $api ); ?>"><p class="m">Cargando…</p></div>
<p class="m" style="font-size:.85rem;margin-top:20px">Tablero de solo lectura publicado por la coordinación de la operación. Muestra los incidentes que registró la red de radioaficionados, con su estado de verificación; no reemplaza la información oficial de la autoridad. Ubicaciones aproximadas (~1 km). Se actualiza solo cada minuto.</p>
<script>
(function(){
	var el=document.getElementById('tab'), api=el.dataset.api;
	var P={critica:'Crítica',alta:'Alta',media:'Media',baja:'Baja'}, V={reportado:'Reportado',en_verificacion:'En verificación',verificado:'Verificado',descartado:'Descartado'}, S={abierto:'Abierto',en_atencion:'En atención',resuelto:'Resuelto',cerrado:'Cerrado'};
	function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
	function hora(ms){ return ms?new Date(ms).toLocaleString('es-CO',{day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'}):''; }
	function pintar(j){
		var r=j.resumen, min=Math.round((Date.now()/1000-j.ts)/60);
		var h=(r.op.tipo==='simulacro'?'<div class="sim">SIMULACRO — no es una emergencia real</div>':'')
			+'<h1>'+esc(r.op.nombre)+'</h1><p class="m">'+esc(r.op.zona)+(r.op.estado==='cerrado'?' · Operación cerrada':'')+' · <span class="'+(min>30?'viejo':'')+'">actualizado '+(min<1?'hace menos de 1 min':'hace '+min+' min')+(min>30?' (puede estar desactualizado)':'')+'</span></p>'
			+'<div class="g"><div class="k"><span class="m">Incidentes vigentes</span><b>'+r.vigentes.length+'</b></div><div class="k"><span class="m">Críticos</span><b class="p-critica">'+r.porPrio.critica+'</b></div><div class="k"><span class="m">Prioridad alta</span><b class="p-alta">'+r.porPrio.alta+'</b></div><div class="k"><span class="m">Verificados</span><b>'+r.porVerif.verificado+'</b></div><div class="k"><span class="m">Estaciones activas</span><b>'+r.estaciones.activas+' / '+r.estaciones.total+'</b></div>'
			+(r.red?'<div class="k"><span class="m">Red</span><b style="font-size:1.1rem">'+esc(r.red.e)+'</b><span class="m">'+esc(r.red.canal)+'</span></div>':'')+'</div>'
			+(r.vigentes.length?'<table><thead><tr><th>Prioridad</th><th>Incidente</th><th>Estado</th><th>Verificación</th><th>Actualizado</th></tr></thead><tbody>'+r.vigentes.map(function(x){
				return '<tr><td class="p-'+x.prio+'">'+esc(P[x.prio]||x.prio)+'</td><td><b>'+esc(x.titulo)+'</b><br><span class="m">'+esc(x.tipo.replace(/_/g,' '))+(x.lugar?' · '+esc(x.lugar):'')+(x.lat!=null?' · '+x.lat+', '+x.lng:'')+'</span></td><td>'+esc(S[x.estado]||x.estado)+'</td><td>'+esc(V[x.verif]||x.verif)+'</td><td>'+hora(x.act)+'</td></tr>';
			}).join('')+'</tbody></table>':'<p class="m">Sin incidentes vigentes.</p>');
		el.innerHTML=h;
	}
	function cargar(){ fetch(api,{cache:'no-store'}).then(function(r){ return r.json().then(function(j){ if(!r.ok) throw j; return j; }); }).then(pintar).catch(function(e){ el.innerHTML='<h1>Este tablero ya no se publica</h1><p class="m">'+esc(e&&e.error||'No se pudo cargar.')+'</p>'; }); }
	cargar(); setInterval(cargar, 60000);
})();
</script>
<?php endif; ?>
</main></body></html><?php
	exit;
} );
