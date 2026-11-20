<?php
/**
 * /condiciones/ — todo lo que afecta salir al aire, para tu ubicación (19.62.0).
 * Los datos los arma inc/condiciones.php; esto es solo la estructura. assets/condiciones/condiciones.js la llena.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$prox = new WP_Query( array( 'post_type' => 'evento', 'posts_per_page' => 1, 'post_status' => 'publish', 'meta_key' => 'start', 'orderby' => 'meta_value', 'order' => 'ASC', 'no_found_rows' => true,
	'meta_query' => array( array( 'key' => 'start', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ) ) );
$malla = $prox->posts ? $prox->posts[0] : null;
?>
<main class="cnd" id="cnd">
	<div class="cnd-cab">
		<div>
			<p class="cnd-eti">En vivo · para tu ubicación</p>
			<h1>Condiciones</h1>
			<p class="cnd-mut" style="max-width:46rem;margin:0">Todo lo que afecta salir al aire, en un solo lugar: tormentas y viento, el sol y el campo magnético, la ionosfera y las bandas, satélites, sismos y lo de RAU cerca de ti.</p>
			<div class="cnd-ubic">
				<span class="cnd-lugar" id="cnd-lugar">📍 <span id="cnd-lugar-txt">Medellín</span> <span class="cnd-mono cnd-mut cnd-chico" id="cnd-loc"></span></span>
				<button type="button" class="cnd-btn" id="cnd-gps">🎯 Usar mi ubicación</button>
				<div class="cnd-buscar">
					<input id="cnd-buscar" type="search" autocomplete="off" placeholder="Ciudad, municipio o locator (FJ26)" aria-label="Buscar ciudad, municipio o locator Maidenhead" aria-controls="cnd-sug" aria-autocomplete="list">
					<div class="cnd-sug" id="cnd-sug" role="listbox" hidden></div>
				</div>
			</div>
		</div>
		<div style="text-align:right">
			<p class="cnd-mut cnd-chico" id="cnd-act" aria-live="polite">Cargando…</p>
			<div style="display:flex;gap:.4rem;justify-content:flex-end;flex-wrap:wrap">
				<button type="button" class="cnd-btn" id="cnd-recargar" title="Actualizar ahora">↻ Actualizar</button>
				<button type="button" class="cnd-btn" id="cnd-compartir" title="Copiar el enlace a estas condiciones">🔗 Compartir</button>
			</div>
		</div>
	</div>

	<section aria-label="Resumen para operar ahora" class="cnd-sem" id="cnd-sem">
		<?php foreach ( array( 'tormenta' => array( '⛈️', 'Tormenta eléctrica' ), 'viento' => array( '💨', 'Viento' ), 'hf' => array( '📡', 'HF' ), 'sol' => array( '☀️', 'Sol' ), 'geomag' => array( '🧲', 'Campo magnético' ), 'seis' => array( '📶', '6 m y VHF' ), 'sat' => array( '🛰️', 'Satélites' ) ) as $id => $x ) : ?>
		<div class="cnd-luz" id="cnd-luz-<?php echo esc_attr( $id ); ?>" data-n="-1">
			<span class="ic" aria-hidden="true"><?php echo esc_html( $x[0] ); ?></span>
			<div><h3><span class="cnd-punto"></span><?php echo esc_html( $x[1] ); ?></h3><p>Consultando…</p></div>
		</div>
		<?php endforeach; ?>
	</section>

	<section class="cnd-mapa-caja" aria-label="Mapa en vivo">
		<div class="cnd-pestanas" role="toolbar" aria-label="Vista del mapa">
			<button type="button" class="cnd-btn" data-vista="radar" aria-pressed="true">🗺️ Radar y capas</button>
			<button type="button" class="cnd-btn" data-vista="windy" aria-pressed="false">🌬️ Viento y tormentas (Windy)</button>
			<span class="sep"></span>
			<span id="cnd-windy-capas" class="cnd-windy-capas" hidden>
				<?php foreach ( array( 'wind' => 'Viento', 'rain' => 'Lluvia', 'thunder' => 'Rayos', 'clouds' => 'Nubes', 'temp' => 'Temperatura', 'cape' => 'Inestabilidad' ) as $k => $n ) : ?>
				<button type="button" class="cnd-btn" data-capa="<?php echo esc_attr( $k ); ?>" aria-pressed="<?php echo 'wind' === $k ? 'true' : 'false'; ?>"><?php echo esc_html( $n ); ?></button>
				<?php endforeach; ?>
			</span>
			<button type="button" class="cnd-btn" id="cnd-capas-btn" aria-expanded="false" aria-controls="cnd-capas">☰ Capas</button>
			<button type="button" class="cnd-btn" id="cnd-full" title="Pantalla completa" aria-label="Pantalla completa">⛶</button>
		</div>
		<div id="cnd-mapa" role="application" aria-label="Mapa: toca un punto para ver sus condiciones"></div>
		<iframe id="cnd-windy" title="Mapa de Windy" hidden loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
		<div class="cnd-capas" id="cnd-capas" hidden>
			<strong class="cnd-chico">Fondo</strong>
			<label><input type="radio" name="cnd-fondo" value="oscuro" checked> Oscuro</label>
			<label><input type="radio" name="cnd-fondo" value="claro"> Claro</label>
			<label><input type="radio" name="cnd-fondo" value="satelite"> Satélite</label>
			<label><input type="radio" name="cnd-fondo" value="relieve"> Relieve</label>
			<hr>
			<strong class="cnd-chico">Capas</strong>
			<label><input type="checkbox" data-capa-mapa="radar" checked> 🌧️ Radar de lluvia</label>
			<label><input type="checkbox" data-capa-mapa="noche" checked> 🌗 Día, noche y línea gris</label>
			<label><input type="checkbox" data-capa-mapa="repetidoras" checked> 📻 Repetidoras RAU</label>
			<label><input type="checkbox" data-capa-mapa="sismos" checked> 🌋 Sismos (7 días)</label>
			<label><input type="checkbox" data-capa-mapa="iono"> 📈 Ionosondas (MUF)</label>
			<label><input type="checkbox" data-capa-mapa="radio"> ⭕ Radio de 50 km</label>
		</div>
		<div class="cnd-linea" id="cnd-linea" hidden>
			<button type="button" id="cnd-play" aria-label="Animar el radar">▶</button>
			<input type="range" id="cnd-cuadro" min="0" max="0" value="0" aria-label="Hora del radar">
			<span class="cnd-mono" id="cnd-cuadro-h" style="min-width:3.2rem">—</span>
			<span class="cnd-leyenda cnd-mut" title="Intensidad de la lluvia">débil <i></i> fuerte</span>
		</div>
	</section>

	<div class="cnd-rej">
		<section class="cnd-caja" aria-labelledby="t-clima" id="cnd-clima">
			<p class="cnd-eti">Tiempo</p><h2 id="t-clima">El tiempo aquí</h2>
			<div class="cnd-vacio">Consultando…</div>
		</section>
		<section class="cnd-caja" aria-labelledby="t-hf" id="cnd-hf">
			<p class="cnd-eti">Ionosfera</p><h2 id="t-hf">HF desde aquí</h2>
			<div class="cnd-vacio">Consultando…</div>
		</section>
		<section class="cnd-caja" aria-labelledby="t-esp" id="cnd-esp">
			<p class="cnd-eti">Clima espacial</p><h2 id="t-esp">El sol y el campo magnético</h2>
			<div class="cnd-vacio">Consultando…</div>
		</section>
		<section class="cnd-caja" aria-labelledby="t-cielo" id="cnd-cielo">
			<p class="cnd-eti">Cielo</p><h2 id="t-cielo">Sol, luna y satélites</h2>
			<div class="cnd-vacio">Calculando…</div>
		</section>
		<section class="cnd-caja" aria-labelledby="t-vhf" id="cnd-vhf">
			<p class="cnd-eti">VHF y UHF</p><h2 id="t-vhf">6 m, meteoros y bandas altas</h2>
			<div class="cnd-vacio">Consultando…</div>
		</section>
		<section class="cnd-caja" aria-labelledby="t-sis" id="cnd-sis">
			<p class="cnd-eti">Sismos</p><h2 id="t-sis">Sismos cercanos</h2>
			<div class="cnd-vacio">Consultando…</div>
		</section>
		<section class="cnd-caja" aria-labelledby="t-rau" id="cnd-rau">
			<p class="cnd-eti">RAU</p><h2 id="t-rau">RAU cerca de ti</h2>
			<div id="cnd-rau-cont" class="cnd-vacio">Consultando…</div>
			<?php if ( $malla ) : $st = get_post_meta( $malla->ID, 'start', true ); ?>
			<p class="cnd-chico" style="margin:.75rem 0 0">🗓️ Próxima actividad: <a href="<?php echo esc_url( get_permalink( $malla ) ); ?>"><?php echo esc_html( get_the_title( $malla ) ); ?></a> · <?php echo esc_html( date_i18n( 'l j \d\e F, g:i a', strtotime( $st ) ) ); ?><?php $fq = get_post_meta( $malla->ID, 'frequency', true ); if ( $fq ) : ?> · <span class="cnd-mono"><?php echo esc_html( $fq ); ?></span><?php endif; ?></p>
			<?php endif; ?>
		</section>
		<section class="cnd-caja cnd-avisos" aria-labelledby="t-av" id="cnd-av">
			<p class="cnd-eti">Avisos</p><h2 id="t-av">Avísame al celular</h2>
			<p class="cnd-mut cnd-chico" style="margin-top:0">Llegan aunque tengas la página cerrada, para el punto que estás viendo. Como mucho uno por tema cada pocas horas.</p>
			<label><input type="checkbox" value="tormenta" checked> ⛈️ Tormenta eléctrica prevista en mi zona</label>
			<label><input type="checkbox" value="viento" checked> 💨 Ráfagas de 60 km/h o más</label>
			<label><input type="checkbox" value="sol"> ☀️ Llamarada solar fuerte (apagón de HF)</label>
			<label><input type="checkbox" value="geomag"> 🧲 Tormenta geomagnética</label>
			<label><input type="checkbox" value="seis"> 📶 6 metros abierta</label>
			<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.6rem">
				<button type="button" class="cnd-btn pri" id="cnd-av-on">🔔 Activar avisos aquí</button>
				<button type="button" class="cnd-btn" id="cnd-av-off" hidden>Quitar avisos</button>
			</div>
			<p class="cnd-chico cnd-mut" id="cnd-av-est" aria-live="polite" style="margin:.5rem 0 0"></p>
		</section>
	</div>

	<p class="cnd-pie">
		Fuentes: tiempo de <a href="https://open-meteo.com/" rel="noopener" target="_blank">Open-Meteo</a> (respaldo: <a href="https://api.met.no/" rel="noopener" target="_blank">MET Norway</a>) · radar de <a href="https://www.rainviewer.com/" rel="noopener" target="_blank">RainViewer</a> · mapa animado de <a href="https://www.windy.com/" rel="noopener" target="_blank">Windy</a> · clima espacial y absorción D-RAP de <a href="https://www.swpc.noaa.gov/" rel="noopener" target="_blank">NOAA SWPC</a> · ionosondas de la red GIRO vía <a href="https://prop.kc2g.com/" rel="noopener" target="_blank">KC2G</a> · bandas de <a href="https://www.hamqsl.com/" rel="noopener" target="_blank">HamQSL</a> · sismos del <a href="https://earthquake.usgs.gov/" rel="noopener" target="_blank">USGS</a> · satélites de <a href="https://celestrak.org/" rel="noopener" target="_blank">CelesTrak</a> · mapas © colaboradores de OpenStreetMap, Esri y OpenTopoMap.
		Tu ubicación se redondea a unos 11 km antes de consultar y no se guarda. Para alertas oficiales de riesgo, sigue a la UNGRD, al IDEAM y a tu sistema local de alerta temprana.
	</p>
</main>
<?php get_footer(); ?>
