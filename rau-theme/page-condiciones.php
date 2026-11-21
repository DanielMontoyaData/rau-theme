<?php
/**
 * /condiciones/ — centro de condiciones para operar (19.62.0, rediseño de centro de mando en 19.63.0).
 * Los datos los arma inc/condiciones.php; esto es la estructura. assets/condiciones/condiciones.js la llena.
 * ?sala=1 abre directamente el modo sala (televisor o monitor de la sala de radio).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$prox = new WP_Query( array( 'post_type' => 'evento', 'posts_per_page' => 1, 'post_status' => 'publish', 'meta_key' => 'start', 'orderby' => 'meta_value', 'order' => 'ASC', 'no_found_rows' => true,
	'meta_query' => array( array( 'key' => 'start', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ) ) );
$malla = $prox->posts ? $prox->posts[0] : null;
/* Íconos propios (trazo de 2 px, 24×24), uno por concepto, para no depender de emojis que cambian con cada sistema */
$iconos = array(
	'tormenta' => '<path d="M7 16a4 4 0 0 1-.6-7.95A6 6 0 0 1 18 9a4 4 0 0 1 0 8"/><path d="M12.5 12 10 16.5h3.5L11 21"/>',
	'viento'   => '<path d="M3 8h11a3 3 0 1 0-3-3"/><path d="M3 12h16a3 3 0 1 1-3 3"/><path d="M3 16h7"/>',
	'hf'       => '<path d="M12 10v11"/><path d="m8 21 4-11 4 11"/><path d="M8.5 6.5a5 5 0 0 1 7 0"/><path d="M5.6 3.6a9 9 0 0 1 12.8 0"/><circle cx="12" cy="9" r="1"/>',
	'sol'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
	'geomag'   => '<path d="M6 3v9a6 6 0 0 0 12 0V3"/><path d="M6 7h4M14 7h4"/><path d="M10 3v9a2 2 0 0 0 4 0V3"/>',
	'seis'     => '<path d="M2 20h.01M7 20v-4M12 20v-8M17 20V8M22 4v16"/>',
	'sat'      => '<path d="m13 7 4-4 4 4-4 4z"/><path d="m3 17 4-4 4 4-4 4z"/><path d="m9 11 4 4"/><path d="m10 14 4-4"/><path d="M16 21a5 5 0 0 0 5-5"/>',
	'ubic'     => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
	'gps'      => '<circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>',
	'recargar' => '<path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 3v6h-6"/>',
	'compartir'=> '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
	'imprimir' => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/>',
	'sala'     => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
	'expandir' => '<path d="M3 9V3h6M21 9V3h-6M3 15v6h6M21 15v6h-6"/>',
	'capas'    => '<path d="m12 2 10 5-10 5L2 7z"/><path d="m2 12 10 5 10-5"/><path d="m2 17 10 5 10-5"/>',
	'mapa'     => '<path d="m9 3-6 3v15l6-3 6 3 6-3V3l-6 3z"/><path d="M9 3v15M15 6v15"/>',
	'nube'     => '<path d="M7 18a4 4 0 0 1-.6-7.95A6 6 0 0 1 18 11a4 4 0 0 1 0 7z"/>',
	'luna'     => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
	'sismo'    => '<path d="M2 12h4l2-6 4 12 3-9 2 3h5"/>',
	'radio'    => '<rect x="3" y="8" width="18" height="12" rx="2"/><path d="m7 8 9-5"/><circle cx="8" cy="14" r="2"/><path d="M14 12h4M14 16h4"/>',
	'campana'  => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
	'registro' => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
	'gota'     => '<path d="M12 2.7 6.3 8.4a8 8 0 1 0 11.4 0z"/>',
	'antena'   => '<path d="M4.9 19.1a10 10 0 0 1 0-14.2M7.8 16.2a6 6 0 0 1 0-8.4M16.2 7.8a6 6 0 0 1 0 8.4M19.1 4.9a10 10 0 0 1 0 14.2"/><circle cx="12" cy="12" r="2"/>',
	'tabla'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/>',
	'buscar'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
	'estrella' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
	'enlace'   => '<circle cx="5" cy="17" r="2"/><circle cx="19" cy="7" r="2"/><path d="M7 16c4-1 7-4 10-8"/>',
	'arbol'    => '<path d="M12 22v-6M7 16h10L12 3z"/>',
	'agua'     => '<path d="M2 15c2 0 2-2 4-2s2 2 4 2 2-2 4-2 2 2 4 2 2-2 4-2M2 20c2 0 2-2 4-2s2 2 4 2 2-2 4-2 2 2 4 2 2-2 4-2M12 3l-3 5a3 3 0 1 0 6 0z"/>',
	'archivo'  => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11h14V8M10 12h4"/>',
	'cme'      => '<circle cx="7" cy="12" r="4"/><path d="M13 7c3 1.5 5 3 8 5-3 2-5 3.5-8 5M12 12h9"/>',
);
if ( ! function_exists( 'rau_cnd_ic' ) ) { function rau_cnd_ic( $id, $clase = 'cnd-ic' ) { return '<svg class="' . esc_attr( $clase ) . '" aria-hidden="true" focusable="false"><use href="#cnd-i-' . esc_attr( $id ) . '"/></svg>'; } }
$caja = function( $id, $icono, $titulo, $clases = '' ) { ?>
	<section class="cnd-caja <?php echo esc_attr( $clases ); ?>" id="<?php echo esc_attr( $id ); ?>" aria-labelledby="<?php echo esc_attr( $id ); ?>-t">
		<div class="cnd-caja-cab"><h2 id="<?php echo esc_attr( $id ); ?>-t"><?php echo rau_cnd_ic( $icono ); ?><?php echo esc_html( $titulo ); ?></h2><span class="cnd-edad" data-edad></span></div>
		<div class="cnd-cuerpo"><p class="cnd-vacio">Consultando…</p></div>
	</section>
<?php };
?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs><?php foreach ( $iconos as $k => $d ) echo '<symbol id="cnd-i-' . esc_attr( $k ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</symbol>'; // phpcs:ignore -- trazos fijos del tema ?></defs></svg>
<main class="cnd" id="cnd">
	<div class="cnd-barra" role="region" aria-label="Barra de mando">
		<div class="cnd-marca-t"><small>Centro de condiciones RAU</small><h1>Condiciones</h1></div>
		<span class="cnd-estado" id="cnd-estado" data-n="-1" role="status" aria-live="polite">Consultando…</span>
		<div class="cnd-ubic">
			<span class="cnd-lugar" id="cnd-lugar"><?php echo rau_cnd_ic( 'ubic' ); ?><span id="cnd-lugar-txt">Medellín</span> <span class="cnd-mono cnd-mut cnd-mini" id="cnd-loc"></span></span>
			<button type="button" class="cnd-btn solo-ic cnd-sala-oculto" id="cnd-fav" title="Guardar en «Mis lugares»" aria-label="Guardar este lugar en Mis lugares" aria-pressed="false"><?php echo rau_cnd_ic( 'estrella' ); ?></button>
			<button type="button" class="cnd-btn solo-ic" id="cnd-gps" title="Usar mi ubicación" aria-label="Usar mi ubicación"><?php echo rau_cnd_ic( 'gps' ); ?></button>
			<button type="button" class="cnd-btn solo-ic cnd-solo-movil" id="cnd-buscar-btn" aria-expanded="false" aria-controls="cnd-buscar" title="Buscar un lugar" aria-label="Buscar un lugar"><?php echo rau_cnd_ic( 'buscar' ); ?></button>
			<div class="cnd-buscar cnd-sala-oculto">
				<input id="cnd-buscar" type="search" autocomplete="off" placeholder="Ciudad, municipio o locator (FJ26)" aria-label="Buscar ciudad, municipio o locator Maidenhead" aria-controls="cnd-sug" aria-autocomplete="list">
				<div class="cnd-sug" id="cnd-sug" role="listbox" hidden></div>
			</div>
		</div>
		<div class="cnd-relojes" aria-label="Relojes">
			<div class="cnd-reloj"><b id="cnd-reloj-local">--:--:--</b><span id="cnd-reloj-local-et">Hora local</span></div>
			<div class="cnd-reloj"><b id="cnd-reloj-utc">--:--:--</b><span>UTC</span></div>
		</div>
		<div class="cnd-acciones">
			<span class="cnd-fuentes" id="cnd-fuentes" role="img" aria-label="Estado de las fuentes de datos" title="Fuentes de datos"></span>
			<span class="cnd-cuenta" id="cnd-cuenta" aria-live="off"></span>
			<button type="button" class="cnd-btn solo-ic" id="cnd-recargar" title="Actualizar ahora" aria-label="Actualizar ahora"><?php echo rau_cnd_ic( 'recargar' ); ?></button>
			<button type="button" class="cnd-btn solo-ic cnd-sala-oculto" id="cnd-compartir" title="Compartir estas condiciones" aria-label="Compartir"><?php echo rau_cnd_ic( 'compartir' ); ?></button>
			<button type="button" class="cnd-btn solo-ic cnd-sala-oculto" id="cnd-imprimir" title="Imprimir o guardar en PDF la hoja de condiciones" aria-label="Imprimir o guardar en PDF"><?php echo rau_cnd_ic( 'imprimir' ); ?></button>
			<button type="button" class="cnd-btn" id="cnd-sala" aria-pressed="false" title="Modo sala: pantalla completa para la sala de radio"><?php echo rau_cnd_ic( 'sala' ); ?><span>Modo sala</span></button>
		</div>
	</div>

	<p class="cnd-solo-impresion" id="cnd-impresion"></p>
	<div class="cnd-lugares cnd-sala-oculto" id="cnd-lugares" hidden aria-label="Mis lugares"></div>
	<p class="cnd-offline" id="cnd-offline" role="status" hidden></p>

	<div class="cnd-sismo" id="cnd-sismo" role="alert" hidden></div>
	<section aria-label="Resumen para operar ahora" class="cnd-sem" id="cnd-sem">
		<?php foreach ( array( 'tormenta' => 'Tormenta', 'viento' => 'Viento', 'hf' => 'HF', 'sol' => 'Sol', 'geomag' => 'Campo magnético', 'seis' => '6 m / VHF', 'sat' => 'Satélites' ) as $id => $t ) : ?>
		<div class="cnd-luz" id="cnd-luz-<?php echo esc_attr( $id ); ?>" data-n="-1">
			<div class="cnd-luz-cab"><span><?php echo rau_cnd_ic( $id ); ?><?php echo esc_html( $t ); ?></span><b class="cnd-palabra">…</b></div>
			<p>Consultando…</p>
		</div>
		<?php endforeach; ?>
	</section>

	<div class="cnd-principal">
		<section class="cnd-mapa-caja" aria-label="Mapa en vivo">
			<div class="cnd-pestanas" role="toolbar" aria-label="Vista del mapa">
				<button type="button" class="cnd-btn" data-vista="radar" aria-pressed="true"><?php echo rau_cnd_ic( 'mapa' ); ?>Radar y capas</button>
				<button type="button" class="cnd-btn" data-vista="windy" aria-pressed="false"><?php echo rau_cnd_ic( 'viento' ); ?>Windy</button>
				<span class="sep"></span>
				<span id="cnd-windy-capas" class="cnd-windy-capas" hidden>
					<?php foreach ( array( 'wind' => 'Viento', 'rain' => 'Lluvia', 'thunder' => 'Rayos', 'clouds' => 'Nubes', 'temp' => 'Temperatura', 'cape' => 'Inestabilidad' ) as $k => $n ) : ?>
					<button type="button" class="cnd-btn" data-capa="<?php echo esc_attr( $k ); ?>" aria-pressed="<?php echo 'wind' === $k ? 'true' : 'false'; ?>"><?php echo esc_html( $n ); ?></button>
					<?php endforeach; ?>
				</span>
				<button type="button" class="cnd-btn" id="cnd-capas-btn" aria-expanded="false" aria-controls="cnd-capas"><?php echo rau_cnd_ic( 'capas' ); ?>Capas</button>
				<button type="button" class="cnd-btn solo-ic" id="cnd-full" title="Pantalla completa" aria-label="Pantalla completa"><?php echo rau_cnd_ic( 'expandir' ); ?></button>
			</div>
			<div id="cnd-mapa" role="application" aria-label="Mapa: toca un punto para ver sus condiciones"></div>
			<iframe id="cnd-windy" title="Mapa animado de Windy" hidden loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
			<div class="cnd-capas" id="cnd-capas" hidden>
				<strong class="cnd-chico">Fondo</strong>
				<label><input type="radio" name="cnd-fondo" value="oscuro" checked> Oscuro</label>
				<label><input type="radio" name="cnd-fondo" value="claro"> Claro</label>
				<label><input type="radio" name="cnd-fondo" value="satelite"> Satélite</label>
				<label><input type="radio" name="cnd-fondo" value="relieve"> Relieve</label>
				<hr>
				<strong class="cnd-chico">Capas</strong>
				<label><input type="checkbox" data-capa-mapa="radar" checked> Radar de lluvia (2 h)</label>
				<label><input type="checkbox" data-capa-mapa="noche" checked> Día, noche y línea gris</label>
				<label><input type="checkbox" data-capa-mapa="repetidoras" checked> Sitios RAU y su estado</label>
				<label><input type="checkbox" data-capa-mapa="cobertura"> Cobertura estimada de repetidoras</label>
				<label><input type="checkbox" data-capa-mapa="sismos"> Sismos (7 días)</label>
				<label><input type="checkbox" data-capa-mapa="iono"> Ionosondas (MUF)</label>
				<label><input type="checkbox" data-capa-mapa="drap" checked> Absorción de HF (D-RAP)</label>
				<label><input type="checkbox" data-capa-mapa="radio"> Radio de 50 km</label>
				<hr>
				<strong class="cnd-chico">Radio</strong>
				<label><input type="checkbox" data-capa-mapa="muf"> MUF en vivo (KC2G)</label>
				<label><input type="checkbox" data-capa-mapa="wspr"> Quién oye a Colombia (WSPR, 1 h)</label>
				<label><input type="checkbox" data-capa-mapa="pota"> Activaciones POTA</label>
				<label><input type="checkbox" data-capa-mapa="sats"> Satélites en vivo</label>
				<label><input type="checkbox" data-capa-mapa="balizas"> Balizas NCDXF</label>
				<label><input type="checkbox" data-capa-mapa="siata" checked> Quebradas y lluvia (SIATA)</label>
			</div>
			<div class="cnd-linea" id="cnd-linea" hidden>
				<button type="button" id="cnd-play" aria-label="Animar el radar">▶</button>
				<input type="range" id="cnd-cuadro" min="0" max="0" value="0" aria-label="Hora del radar">
				<span class="cnd-mono" id="cnd-cuadro-h" style="min-width:4.5rem">—</span>
				<span class="cnd-leyenda-r cnd-mut" title="Intensidad de la lluvia">débil <i></i> fuerte</span>
			</div>
		</section>
		<div class="cnd-lado">
			<?php $caja( 'cnd-ahora', 'nube', 'Ahora' ); ?>
			<?php $caja( 'cnd-reg', 'registro', 'Registro de eventos' ); ?>
			<div id="cnd-rota" aria-label="Paneles que rotan en el modo sala"></div>
		</div>
	</div>

	<div class="cnd-rej">
		<?php $caja( 'cnd-clima', 'nube', 'Pronóstico 48 horas', 'cnd-ancho2' ); ?>
		<?php $caja( 'cnd-sitios', 'tabla', 'Sitios RAU' ); ?>
		<?php $caja( 'cnd-siata', 'agua', 'Valle de Aburrá · SIATA', 'cnd-ancho' ); ?>
		<?php $caja( 'cnd-hf', 'hf', 'HF desde aquí', 'cnd-ancho2' ); ?>
		<?php $caja( 'cnd-enlace', 'enlace', '¿Llego a…? Predicción de enlace', 'cnd-ancho2' ); ?>
		<?php $caja( 'cnd-alcance', 'antena', 'Alcance real · WSPR última hora' ); ?>
		<?php $caja( 'cnd-esp', 'sol', 'Clima espacial' ); ?>
		<?php $caja( 'cnd-solar', 'cme', 'Tormentas solares', 'cnd-ancho' ); ?>
		<?php $caja( 'cnd-tend', 'sol', 'Tendencias del sol', 'cnd-ancho2' ); ?>
		<?php $caja( 'cnd-cielo', 'sat', 'Satélites, sol y luna' ); ?>
		<?php $caja( 'cnd-vhf', 'seis', '6 m, meteoros y VHF' ); ?>
		<?php $caja( 'cnd-pota', 'arbol', 'Activaciones POTA' ); ?>
		<?php $caja( 'cnd-semana', 'registro', 'Resumen de la semana', 'cnd-ancho2' ); ?>
		<?php $caja( 'cnd-archivo', 'archivo', 'Archivo de eventos' ); ?>
		<section class="cnd-caja" id="cnd-rau" aria-labelledby="cnd-rau-t">
			<div class="cnd-caja-cab"><h2 id="cnd-rau-t"><?php echo rau_cnd_ic( 'radio' ); ?>RAU cerca de ti</h2></div>
			<div id="cnd-rau-cont" class="cnd-vacio">Consultando…</div>
			<?php if ( $malla ) : $st = trim( (string) get_post_meta( $malla->ID, 'start', true ) ); $ts = strtotime( $st ); $con_hora = $ts && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $st ) && '00:00' !== date( 'H:i', $ts ); ?>
			<p class="cnd-chico" style="margin:0">Próxima actividad: <a href="<?php echo esc_url( get_permalink( $malla ) ); ?>"><?php echo esc_html( get_the_title( $malla ) ); ?></a> · <?php echo esc_html( date_i18n( $con_hora ? 'l j \d\e F, H:i' : 'l j \d\e F', $ts ) ); ?><?php $fq = get_post_meta( $malla->ID, 'frequency', true ); if ( $fq ) : ?> · <span class="cnd-mono"><?php echo esc_html( $fq ); ?></span><?php endif; ?></p>
			<?php endif; ?>
			<div>
				<p class="cnd-mini cnd-mut" style="margin:.2rem 0 .35rem">Para compartir y transmitir:</p>
				<div class="cnd-oficial" style="margin-bottom:.6rem">
					<button type="button" class="cnd-btn" id="cnd-tarjeta"><?php echo rau_cnd_ic( 'compartir' ); ?>Imagen para WhatsApp</button>
					<a class="cnd-btn" id="cnd-franja" href="<?php echo esc_url( home_url( '/condiciones/franja/' ) ); ?>" target="_blank" rel="noopener" title="Para OBS: fuente de navegador de 1280×90, con ?transparente para fondo transparente"><?php echo rau_cnd_ic( 'sala' ); ?>Franja para la transmisión</a>
				</div>
				<p class="cnd-mini cnd-mut" style="margin:.2rem 0 .35rem">Fuentes oficiales para alertas y emergencias:</p>
				<div class="cnd-oficial">
					<a class="cnd-btn" href="http://www.pronosticosyalertas.gov.co/" target="_blank" rel="noopener">IDEAM alertas</a>
					<a class="cnd-btn" href="https://fews.ideam.gov.co/visorfews/nacional" target="_blank" rel="noopener">IDEAM ríos</a>
					<a class="cnd-btn" href="https://portal.gestiondelriesgo.gov.co/" target="_blank" rel="noopener">UNGRD</a>
					<a class="cnd-btn" href="https://www.sgc.gov.co/sismos" target="_blank" rel="noopener">SGC sismos</a>
				</div>
			</div>
		</section>
		<section class="cnd-caja cnd-avisos cnd-sala-oculto" id="cnd-av" aria-labelledby="cnd-av-t">
			<div class="cnd-caja-cab"><h2 id="cnd-av-t"><?php echo rau_cnd_ic( 'campana' ); ?>Avisos al celular</h2></div>
			<p class="cnd-mut cnd-chico" style="margin:0">Llegan aunque tengas la página cerrada. Hasta 5 puntos por celular (casa, repetidora, finca…), como mucho un aviso por tema cada pocas horas.</p>
			<div style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
				<input type="text" id="cnd-av-nombre" placeholder="Nombre del punto (p. ej. Casa)" maxlength="60" aria-label="Nombre del punto" style="flex:1 1 10rem">
				<label style="padding:0" class="cnd-chico">Viento desde <select id="cnd-av-umbral" aria-label="Avisar viento desde"><option>40</option><option>50</option><option selected>60</option><option>70</option><option>80</option></select> km/h</label>
			</div>
			<div>
				<label><input type="checkbox" value="tormenta" checked> Tormenta eléctrica en la zona</label>
				<label><input type="checkbox" value="viento" checked> Viento fuerte</label>
				<label><input type="checkbox" value="sol"> Llamarada solar fuerte (apagón de HF)</label>
				<label><input type="checkbox" value="geomag"> Tormenta geomagnética</label>
				<label><input type="checkbox" value="seis"> 6 metros abierta</label>
				<label><input type="checkbox" value="llamarada"> Cada llamarada desde clase <select id="cnd-av-clase" aria-label="Clase mínima de llamarada"><option value="M">M</option><option value="X">X</option></select></label>
				<label><input type="checkbox" value="protones"> Tormenta de protones (S1 o más)</label>
				<label><input type="checkbox" value="hss"> Corriente rápida de viento solar</label>
				<label><input type="checkbox" value="sismo"> Sismo fuerte cerca (M4,5+ a 200 km o M6+ a 500 km)</label>
			</div>
			<div style="display:flex;gap:.4rem;flex-wrap:wrap">
				<button type="button" class="cnd-btn pri" id="cnd-av-on"><?php echo rau_cnd_ic( 'campana' ); ?>Avisarme en este punto</button>
				<button type="button" class="cnd-btn" id="cnd-av-prueba" hidden>Enviarme una prueba</button>
			</div>
			<p class="cnd-chico cnd-mut" id="cnd-av-est" aria-live="polite" style="margin:0"></p>
			<ul class="cnd-lista" id="cnd-av-lista"></ul>
		</section>
	</div>

	<p class="cnd-pie">
		Fuentes: tiempo de <a href="https://open-meteo.com/" rel="noopener" target="_blank">Open-Meteo</a> (respaldo: <a href="https://api.met.no/" rel="noopener" target="_blank">MET Norway</a>) · radar de <a href="https://www.rainviewer.com/" rel="noopener" target="_blank">RainViewer</a> · mapa animado de <a href="https://www.windy.com/" rel="noopener" target="_blank">Windy</a> · clima espacial, absorción D-RAP y pronósticos de <a href="https://www.swpc.noaa.gov/" rel="noopener" target="_blank">NOAA SWPC</a> · eyecciones de masa coronal de <a href="https://kauai.ccmc.gsfc.nasa.gov/DONKI/" rel="noopener" target="_blank">NASA DONKI</a> · imágenes del sol de <a href="https://sdo.gsfc.nasa.gov/" rel="noopener" target="_blank">NASA SDO</a> y de la corona de SOHO LASCO · ionosondas de la red GIRO vía <a href="https://prop.kc2g.com/" rel="noopener" target="_blank">KC2G</a> · bandas de <a href="https://www.hamqsl.com/" rel="noopener" target="_blank">HamQSL</a> · alcance de <a href="https://wspr.live/" rel="noopener" target="_blank">WSPR</a> · activaciones de <a href="https://pota.app/" rel="noopener" target="_blank">POTA</a> · quebradas, lluvia y estaciones del Valle de Aburrá: Fuente: <a href="https://siata.gov.co/" rel="noopener" target="_blank">SIATA – Área Metropolitana del Valle de Aburrá</a> · enlaces de <a href="https://pskreporter.info/" rel="noopener" target="_blank">PSK Reporter</a> · sismos del <a href="https://earthquake.usgs.gov/" rel="noopener" target="_blank">USGS</a> · satélites de <a href="https://celestrak.org/" rel="noopener" target="_blank">CelesTrak</a> · mapas © colaboradores de OpenStreetMap, Esri y OpenTopoMap.
		Tu ubicación se redondea a unos 11 km antes de consultar y no se guarda. No hay rayos en tiempo real: las redes que los publican no permiten usarlos en otros sitios; el radar y la capa «Rayos» de Windy (pronóstico) son la mejor guía. Para alertas oficiales sigue al IDEAM, la UNGRD y tu sistema local de alerta temprana.
	</p>
</main>
<?php get_footer(); ?>
