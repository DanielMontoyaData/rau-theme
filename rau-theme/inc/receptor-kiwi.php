<?php
/**
 * Receptor KiwiSDR de RAU: componente compartido por la página /receptor/ y la herramienta
 * "Receptor KiwiSDR" de Herramientas. Va en línea (HTML + JS) por la misma razón que el resto del
 * JavaScript de Herramientas: servido como archivo aparte falló en este alojamiento (verificar.php, 16).
 *
 * Protocolo del KiwiSDR (web/openwebrx/openwebrx.js, audio.js y kiwiclient del proyecto oficial):
 *  - Dos WebSocket con la MISMA marca de tiempo: <relevo>/ws/kiwi/<marca>/SND (audio) y /W/F
 *    (cascada). Con marcas distintas el receptor no los empareja y la cascada llega vacía.
 *  - Cada uno empieza con "SET auth t=kiwi p=". El servidor manda texto "MSG clave=valor ..." y
 *    binarios con etiqueta de 3 letras ("SND", "W/F").
 *  - Audio: audio_rate → "SET AR OK in=<tasa> out=<navegador>"; sample_rate → modo, AGC y compresión.
 *    Trama SND: etiqueta(3) flags(1) secuencia(4 LE) S-metro(2 BE; dBm = 0.1·v − 127) y muestras
 *    IMA-ADPCM si flags & 0x10 (el estado sigue entre tramas) o PCM 16 bits big-endian.
 *  - Cascada con "SET wf_comp=0" y "SET send_dB=1": etiqueta(3) relleno(1) x_bin(4) zoom(4)
 *    secuencia(4) y 1024 bytes, dBm = byte − 255. Borde izquierdo = x_bin / (fft · 2^zoom_max) · ancho.
 *  - "SET keepalive" periódico en las dos conexiones.
 *
 * Expone window.RauRx (sintonizar, encender) y window.rauEscuchar(kHz, pista), que usan otras
 * herramientas para abrir el receptor en una frecuencia ("Escuchar" en los spots de DX y POTA).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Plan de banda que se dibuja sobre la cascada. Bandas de radioaficionado de la Región 2 de la
 * IARU, que adopta el Decreto 1078 de 2015 (art. 2.2.5.6.1), y los segmentos de la licencia de
 * Novicio del art. 2.2.5.6.5. Frecuencias de FT8 y de las señales horarias WWV para orientarse.
 */
function rau_receptor_plan() {
	return array(
		'ham'     => array( array( 1800, 2000, '160 m' ), array( 3500, 4000, '80 m' ), array( 7000, 7300, '40 m' ), array( 10100, 10150, '30 m' ), array( 14000, 14350, '20 m' ), array( 18068, 18168, '17 m' ), array( 21000, 21450, '15 m' ), array( 24890, 24990, '12 m' ), array( 28000, 29700, '10 m' ) ),
		'novicio' => array( array( 1800, 2000, 'CW y fonía' ), array( 3500, 3525, 'CW' ), array( 3525, 3750, 'CW y fonía' ), array( 7000, 7040, 'CW' ), array( 7040, 7300, 'CW y fonía' ), array( 21000, 21450, 'CW' ), array( 28000, 28300, 'CW' ), array( 28300, 29500, 'CW y fonía' ) ),
		'marcas'  => array( array( 1840, 'FT8' ), array( 3573, 'FT8' ), array( 7074, 'FT8' ), array( 10136, 'FT8' ), array( 14074, 'FT8' ), array( 18100, 'FT8' ), array( 21074, 'FT8' ), array( 24915, 'FT8' ), array( 28074, 'FT8' ), array( 2500, 'WWV' ), array( 5000, 'WWV' ), array( 10000, 'WWV' ), array( 15000, 'WWV' ), array( 20000, 'WWV' ), array( 25000, 'WWV' ) ),
	);
}

function rau_receptor_kiwi( $args = array() ) {
	$args  = wp_parse_args( $args, array( 'estado' => true ) );
	$relay = get_option( 'rau_kiwi_relay', '' );
	?>
	<div id="rx-componente">
	<?php if ( $relay ) : ?>
	<div id="rx-app" tabindex="0" class="rx-radio outline-none" aria-label="Receptor KiwiSDR: usa las flechas para sintonizar">
		<div class="rx-placa">
			<span class="rx-placa-marca" id="rx-placa-marca">HK4RAU</span>
			<span class="rx-placa-modelo">KiwiSDR 2 · receptor de comunicaciones · 10 kHz – 30 MHz</span>
		</div>
		<div class="rx-frente">
			<div class="rx-lcd" aria-live="off">
				<div class="rx-lcd-fila">
					<span id="rx-ind-rx" class="rx-ind">RX</span>
					<span class="rx-ind is-on">VFO A</span>
					<span id="rx-modo-txt" class="rx-ind is-on">LSB</span>
					<span id="rx-ind-filtro" class="rx-ind is-on">2.7k</span>
					<span id="rx-ind-agc" class="rx-ind is-on">AGC</span>
					<span id="rx-ind-rec" class="rx-ind rx-ind-rojo">REC</span>
					<span id="rx-ind-banda" class="rx-lcd-banda">40 m</span>
				</div>
				<div class="rx-lcd-freq">
					<span class="rx-fantasma" aria-hidden="true">88.888.88</span>
					<span id="rx-freq" class="rx-digitos" role="group" aria-label="Frecuencia: toca la parte de arriba o de abajo de un dígito para subirlo o bajarlo">7.150.00</span>
					<span class="rx-lcd-unidad">MHz</span>
				</div>
				<div class="rx-smetro">
					<div class="rx-smetro-escala" aria-hidden="true"><span>S</span><span>1</span><span>3</span><span>5</span><span>7</span><span>9</span><span>+20</span><span>+40</span><span>+60</span></div>
					<div class="rx-smetro-barra"><div id="rx-smeter" class="rx-smetro-relleno"></div><div id="rx-pico" class="rx-smetro-pico"></div><div id="rx-sq-marca" class="absolute inset-y-0 hidden w-0.5 bg-accent"></div></div>
					<div class="rx-lcd-fila rx-lcd-pie"><span id="rx-s">—</span><span id="rx-libres"></span></div>
				</div>
			</div>
			<div class="rx-mandos">
				<div class="rx-perilla-caja">
					<div id="rx-perilla" class="rx-perilla" role="slider" tabindex="0" aria-label="Perilla de sintonía: gírala o usa las flechas" aria-valuetext="7150.00 kHz"><span class="rx-perilla-hoyo"></span></div>
					<span class="rx-perilla-txt">VFO · <span class="rx-paso-txt">1 kHz</span></span>
				</div>
				<div class="rx-teclas-grandes">
					<button id="rx-power" type="button" class="rx-tecla rx-tecla-power" aria-pressed="false">▶ Escuchar</button>
					<div class="rx-teclas-fila">
						<button id="rx-mute" type="button" class="rx-tecla" aria-pressed="false" title="Silenciar">🔊</button>
						<button id="rx-rec" type="button" class="rx-tecla" aria-pressed="false" title="Grabar lo que suena (WAV)">● Grabar</button>
						<button id="rx-share" type="button" class="rx-tecla" title="Compartir esta frecuencia">Compartir</button>
					</div>
				</div>
			</div>
		</div>
		<p class="rx-ayuda-digitos"><span class="rx-solo-raton">Haz clic arriba o abajo de un dígito para subirlo o bajarlo, gira la perilla o usa la rueda sobre ellos.</span><span class="rx-solo-tactil">Toca ▲ o ▼ sobre un dígito para cambiarlo, o gira la perilla con el dedo.</span></p>

		<div class="rx-estado-linea">
			<span class="rx-estado-led" aria-hidden="true"></span>
			<p id="rx-msg" role="status" aria-live="polite">Pulsa «Escuchar» para conectarte. Usarás uno de los canales del receptor mientras escuchas.</p>
			<button type="button" id="rx-avisar" class="rx-tecla hidden">🔔 Avísame cuando haya canal</button>
			<button type="button" id="rx-grab-subir" class="rx-tecla hidden">🔗 Compartir esta grabación</button>
			<?php $grab = function_exists( 'rau_grab_compartida' ) ? rau_grab_compartida() : null; if ( $grab ) : ?>
			<div id="rx-grab" class="rx-grab">
				<p class="rx-serigrafia">Grabación compartida · <?php echo esc_html( number_format( $grab['freq'], 2, ',', '.' ) . ' kHz ' . strtoupper( $grab['modo'] ) ); ?> · <?php echo esc_html( gmdate( 'Y-m-d H:i', (int) ( $grab['inicio'] / 1000 ) ) ); ?> UTC · <?php echo esc_html( $grab['por'] ); ?></p>
				<audio controls preload="none" src="<?php echo esc_url( $grab['url'] ); ?>"></audio>
				<button type="button" class="rx-tecla" id="rx-grab-ir" data-f="<?php echo esc_attr( $grab['freq'] ); ?>" data-m="<?php echo esc_attr( $grab['modo'] ); ?>">Escuchar esa frecuencia en vivo</button>
			</div>
			<?php endif; ?>
		</div>

		<div class="rx-pan">
			<div class="rx-pan-cab">
				<span class="rx-serigrafia">Panorámico</span>
				<span id="rx-donde" class="rx-pan-donde" aria-live="polite"></span>
				<div class="rx-span" role="group" aria-label="Ancho visible de la cascada">
					<span class="rx-serigrafia">Span</span>
					<button type="button" id="rx-zoom-menos" class="rx-tecla rx-tecla-mini" aria-label="Ver más banda (alejar)">−</button>
					<span class="rx-span-v"><span id="rx-ancho">234 kHz</span><span id="rx-zoom" class="hidden">7</span></span>
					<button type="button" id="rx-zoom-mas" class="rx-tecla rx-tecla-mini" aria-label="Ver menos banda (acercar)">+</button>
				</div>
			</div>
			<div id="rx-pantalla" class="relative touch-none select-none overflow-hidden" style="cursor:crosshair">
				<canvas id="rx-plan" class="block h-[20px] w-full" aria-hidden="true"></canvas>
				<canvas id="rx-spots" class="block h-[18px] w-full" aria-label="Estaciones reportadas en esta parte de la banda: toca una para sintonizarla"></canvas>
				<canvas id="rx-esp" class="block h-[80px] w-full" aria-hidden="true"></canvas>
				<canvas id="rx-wf" width="1024" height="200" class="block h-[200px] w-full" aria-label="Cascada de frecuencias: toca para sintonizar, arrastra para recorrer la banda, usa la rueda para ajustar"></canvas>
				<canvas id="rx-esc" class="block h-[22px] w-full" aria-hidden="true"></canvas>
				<div id="rx-filtro" class="pointer-events-none absolute bg-white/10" style="top:38px;height:280px;left:50%;width:0"></div>
				<div id="rx-marca" class="pointer-events-none absolute w-px bg-accent" style="top:38px;height:280px;left:50%"></div>
				<p id="rx-ayuda" class="pointer-events-none absolute inset-x-0 top-[150px] text-center font-mono text-xs text-muted-foreground">Pulsa «Escuchar». Luego toca una señal en la cascada para sintonizarla.</p>
			</div>
		</div>

		<div id="rx-cw" class="rx-cw hidden">
			<div class="rx-cw-cab">
				<span class="rx-serigrafia">Decodificador CW</span><span id="rx-cw-info">Esperando una señal…</span>
				<label class="rx-ft-filtro" title="Lee a la vez todas las estaciones de CW que caben en el filtro ancho"><input type="checkbox" id="rx-cw-skim"> todas las señales</label>
				<button type="button" id="rx-cw-borrar" class="rx-tecla rx-tecla-mini">Borrar</button>
			</div>
			<p id="rx-cw-texto" class="rx-cw-texto" aria-live="polite"></p>
			<div id="rx-cw-canales" class="rx-cw-canales hidden" role="log" aria-live="polite" aria-label="Estaciones de CW decodificadas"></div>
			<p class="rx-cw-nota">Funciona mejor con una sola señal clara: tócala en la cascada para centrarla en el filtro y usa el filtro «Angosto». Las letras tenues son dudosas.</p>
		</div>

		<div id="rx-dig" class="rx-cw hidden">
			<div class="rx-cw-cab">
				<span class="rx-serigrafia" id="rx-dig-titulo">Decodificador</span><span id="rx-dig-info">Esperando una señal…</span>
				<button type="button" id="rx-dig-borrar" class="rx-tecla rx-tecla-mini">Borrar</button>
			</div>
			<p id="rx-dig-texto" class="rx-cw-texto rx-dig-texto" aria-live="polite"></p>
			<p class="rx-cw-nota" id="rx-dig-nota"></p>
		</div>

		<div id="rx-sstv" class="rx-cw hidden">
			<div class="rx-cw-cab">
				<span class="rx-serigrafia">Decodificador SSTV</span><span id="rx-sstv-info">Esperando el tono VIS de comienzo de imagen…</span>
				<button type="button" id="rx-sstv-guardar" class="rx-tecla rx-tecla-mini" disabled>Guardar imagen</button>
			</div>
			<canvas id="rx-sstv-cv" class="rx-sstv-cv" width="320" height="256" aria-label="Imagen SSTV recibida"></canvas>
			<div id="rx-sstv-galeria" class="rx-sstv-galeria" aria-label="Imágenes anteriores"></div>
			<p class="rx-cw-nota">Arranca solo con el tono VIS al empezar cada imagen: Martin 1 y 2, Scottie 1 y 2, Robot 36. Frecuencias habituales: 14.230 USB, 7.171 LSB, 21.340 USB, 28.680 USB.</p>
		</div>

		<div id="rx-ft" class="rx-cw rx-ft hidden">
			<div class="rx-cw-cab">
				<span class="rx-serigrafia" id="rx-ft-titulo">Decodificador FT8</span><select id="rx-js8-sub" class="rx-ft-sel hidden" aria-label="Submodo de JS8"><option value="normal">Normal · 15 s</option><option value="rapido">Rápido · 10 s</option><option value="turbo">Turbo · 6 s</option><option value="lento">Lento · 30 s</option></select><span id="rx-ft-info">Esperando la próxima ranura…</span>
				<label class="rx-ft-filtro"><input type="checkbox" id="rx-ft-cq"> solo CQ</label>
				<button type="button" id="rx-ft-mapa-b" class="rx-tecla rx-tecla-mini" aria-pressed="false">Mapa</button>
				<button type="button" id="rx-ft-borrar" class="rx-tecla rx-tecla-mini">Borrar</button>
			</div>
			<p id="rx-ft-reloj" class="rx-ft-aviso hidden" role="status"></p>
			<div id="rx-ft-mapa" class="rx-ft-mapa hidden" role="region" aria-label="Mapa de las estaciones oídas"></div>
			<div class="rx-ft-tabla" role="log" aria-live="polite" aria-label="Mensajes decodificados">
				<table><thead><tr><th>UTC</th><th>dB</th><th>DT</th><th>Hz</th><th>Mensaje</th></tr></thead><tbody id="rx-ft-filas"></tbody></table>
			</div>
			<p class="rx-cw-nota">Decodifica cada ranura de tiempo en este navegador, con el control de ganancia fijo y el audio sin compresión mientras tanto. La SNR es aproximada. Resaltados: CQ y estaciones colombianas. El mapa ubica a quien transmite su locator.</p>
		</div>

		<div class="rx-mesa">
			<div class="rx-grupo rx-grupo-banda" role="group" aria-label="Banda">
				<span class="rx-serigrafia">Banda</span>
				<div id="rx-bandas" class="rx-fila rx-teclas"></div>
				<div id="rx-segmentos" class="rx-teclas rx-segmentos"></div>
			</div>
			<div class="rx-grupo" role="group" aria-label="Modo">
				<span class="rx-serigrafia">Modo</span>
				<div id="rx-modos" class="rx-teclas"></div>
			</div>
			<?php $otros = (array) get_option( 'rau_receptores', array() ); if ( $otros ) : ?>
			<div class="rx-grupo" role="group" aria-label="Receptor">
				<span class="rx-serigrafia">Receptor</span>
				<div class="rx-teclas">
					<select id="rx-receptor" class="rx-ft-sel" aria-label="Receptor"><option value="">HK4RAU · <?php echo esc_html( rau_sdr_lugar() ); ?></option><?php foreach ( $otros as $o ) echo '<option value="' . esc_attr( $o['id'] ) . '">' . esc_html( $o['nombre'] . ' · ' . $o['lugar'] ) . '</option>'; ?></select>
					<select id="rx-comparar" class="rx-ft-sel" aria-label="Comparar con otro receptor"><option value="">Comparar con…</option><option value="-">HK4RAU</option><?php foreach ( $otros as $o ) echo '<option value="' . esc_attr( $o['id'] ) . '">' . esc_html( $o['nombre'] ) . '</option>'; ?></select>
					<span id="rx-comp" class="rx-comp" aria-live="polite"></span>
				</div>
			</div>
			<?php endif; ?>
			<div class="rx-grupo" role="group" aria-label="Decodificador">
				<span class="rx-serigrafia">Decodificador</span>
				<div id="rx-decs" class="rx-teclas">
					<button type="button" data-d="" class="rx-dec rx-tecla" aria-pressed="true">Ninguno</button><button type="button" data-d="cw" class="rx-dec rx-tecla" aria-pressed="false">CW</button><button type="button" data-d="FT8" class="rx-dec rx-tecla" aria-pressed="false">FT8</button><button type="button" data-d="FT4" class="rx-dec rx-tecla" aria-pressed="false">FT4</button><button type="button" data-d="JS8" class="rx-dec rx-tecla" aria-pressed="false" title="JS8Call">JS8</button><button type="button" data-d="rtty" class="rx-dec rx-tecla" aria-pressed="false" title="RTTY 45,45 baudios, 170 Hz">RTTY</button><button type="button" data-d="psk31" class="rx-dec rx-tecla" aria-pressed="false">PSK31</button><button type="button" data-d="sstv" class="rx-dec rx-tecla" aria-pressed="false" title="Imágenes de televisión de barrido lento">SSTV</button>
				</div>
			</div>
			<div class="rx-grupo" role="group" aria-label="Filtro">
				<span class="rx-serigrafia">Filtro</span>
				<div id="rx-filtros" class="rx-teclas rx-teclas-iguales">
					<button type="button" data-v="0" class="rx-filtro-op rx-tecla rx-tecla-doble">Angosto <span class="rx-tecla-sub"></span></button><button type="button" data-v="1" class="rx-filtro-op rx-tecla rx-tecla-doble">Normal <span class="rx-tecla-sub"></span></button><button type="button" data-v="2" class="rx-filtro-op rx-tecla rx-tecla-doble">Ancho <span class="rx-tecla-sub"></span></button>
				</div>
			</div>
			<div class="rx-grupo rx-grupo-ancho" role="group" aria-label="Sintonía">
				<span class="rx-serigrafia">Sintonía · paso</span>
				<div id="rx-pasos" class="rx-teclas" role="group" aria-label="Paso de sintonía">
					<button type="button" data-v="0.01" class="rx-paso-op rx-tecla">10 Hz</button><button type="button" data-v="0.1" class="rx-paso-op rx-tecla">100 Hz</button><button type="button" data-v="1" class="rx-paso-op rx-tecla">1 kHz</button><button type="button" data-v="5" class="rx-paso-op rx-tecla">5 kHz</button>
				</div>
				<div class="rx-teclas rx-ir">
					<button type="button" data-pasos="-1" class="rx-paso rx-tecla rx-tecla-sin-led" aria-label="Bajar un paso">◀</button>
					<label class="sr-only" for="rx-input">Frecuencia en kHz o MHz</label>
					<input id="rx-input" type="text" inputmode="decimal" class="rx-entrada" placeholder="kHz o MHz" title="Escribe 7074 (kHz) o 7.074 (MHz) y pulsa Enter">
					<button type="button" data-pasos="1" class="rx-paso rx-tecla rx-tecla-sin-led" aria-label="Subir un paso">▶</button>
				</div>
			</div>
			<div class="rx-grupo rx-grupo-ancho" role="group" aria-label="Audio">
				<span class="rx-serigrafia">Audio</span>
				<label class="rx-fader"><span class="rx-fader-n">Vol</span><input id="rx-vol" type="range" min="0" max="100" value="60" aria-label="Volumen"><span id="rx-vol-v" class="rx-fader-v">60</span></label>
				<label class="rx-fader"><span class="rx-fader-n" title="Silenciador: corta el ruido entre transmisiones">SQL</span><input id="rx-sq" type="range" min="0" max="9" value="0" aria-label="Silenciador"><span id="rx-sq-v" class="rx-fader-v">off</span></label>
				<button type="button" id="rx-agc" class="rx-tecla" aria-pressed="true" title="Control automático de ganancia: iguala el volumen de señales fuertes y débiles">AGC automático</button>
			</div>
		</div>

		<button type="button" id="rx-ajustes" class="rx-ajustes-btn rx-tecla" aria-expanded="false" aria-controls="rx-avanzado">☰ Menú: cascada y memorias</button>
		<div id="rx-avanzado" class="rx-avanzado rx-mesa">
			<div class="rx-grupo" role="group" aria-label="Cascada">
				<span class="rx-serigrafia">Cascada</span>
				<label class="rx-fader"><span class="rx-fader-n">Color</span>
					<select id="rx-paleta" class="rx-entrada">
						<option value="ambar">Ámbar</option><option value="rau">Cian RAU</option><option value="clasica">Clásica KiwiSDR</option><option value="turbo">Turbo</option><option value="fosforo">Fósforo verde</option><option value="grises">Grises</option>
					</select>
				</label>
				<label class="rx-fader"><span class="rx-fader-n">Contr.</span><input id="rx-contraste" type="range" min="15" max="60" value="30" aria-label="Contraste de la cascada"><span id="rx-contraste-v" class="rx-fader-v">30 dB</span></label>
			</div>
			<div class="rx-grupo rx-grupo-mem" role="group" aria-label="Memorias">
				<span class="rx-serigrafia">Memorias</span>
				<div id="rx-favs" class="rx-teclas"></div>
				<button type="button" id="rx-fav-add" class="rx-tecla">M+ Guardar esta frecuencia</button>
			</div>
		</div>
	</div>
	<div id="rx-mini" style="bottom:calc(1rem + env(safe-area-inset-bottom));left:1rem" class="rx-mini fixed z-50 hidden" role="region" aria-label="Receptor sonando">
		<span class="rx-mini-led" aria-hidden="true"></span><span id="rx-mini-f" class="rx-mini-lcd">—</span>
		<button type="button" id="rx-mini-ver" class="rx-mini-btn">Ver</button>
		<button type="button" id="rx-mini-stop" class="rx-mini-btn rx-mini-stop" aria-label="Detener el receptor">■</button>
	</div>
	<p class="rx-ayuda-teclado mt-3 text-xs text-muted-foreground">En computador: flechas ← → para sintonizar un paso, ↑ ↓ para diez. ¿Necesitas decodificadores o «acampar» cuando está lleno? Usa la <a href="<?php echo esc_url( $relay . '/' ); ?>" target="_blank" rel="noopener" class="text-primary hover:underline">interfaz completa del KiwiSDR</a>.</p>
	<?php else : ?>
	<div class="rounded-2xl border border-border/70 bg-card p-6">
		<h2 class="font-display text-xl font-bold">Escúchalo en la interfaz del KiwiSDR</h2>
		<p class="mt-2 max-w-2xl text-sm text-muted-foreground">El receptor todavía no tiene dirección segura (HTTPS), así que los navegadores no permiten escucharlo dentro de esta página. Mientras tanto se abre en su propia pestaña.</p>
		<a href="<?php echo esc_url( RAU_KIWI_HTTP . '/' ); ?>" target="_blank" rel="noopener" class="mt-4 inline-flex h-11 items-center justify-center rounded-md bg-primary px-6 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Abrir el KiwiSDR en otra pestaña</a>
	</div>
	<?php endif; ?>

	<?php if ( $args['estado'] ) : ?>
	<div class="rx-estado-bloque mt-8 grid gap-4 lg:grid-cols-[2fr_1fr]">
		<div class="rounded-2xl border border-border/70 bg-card p-5 sm:p-6">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Cuándo entrar</p>
			<h2 class="font-display text-lg font-bold">Canales libres por hora</h2>
			<p class="mt-1 text-sm text-muted-foreground">Promedio de canales libres a cada hora del día (hora de Colombia), medido cada 10 minutos.</p>
			<div id="rx-horas" class="mt-4 flex h-32 items-end gap-1" aria-label="Canales libres por hora"></div>
			<div class="mt-1 flex justify-between font-mono text-[10px] text-muted-foreground"><span>0 h</span><span>6 h</span><span>12 h</span><span>18 h</span><span>23 h</span></div>
			<p id="rx-horas-nota" class="mt-3 text-xs text-muted-foreground">Recolectando datos…</p>
		</div>
		<div class="rounded-2xl border border-border/70 bg-card p-5 sm:p-6">
			<p class="mb-1 font-mono text-xs uppercase tracking-[0.25em] text-primary">Ahora</p>
			<dl class="space-y-2 text-sm">
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Estado</dt><dd id="rx-estado" class="text-right font-mono text-xs">Consultando…</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Canales en uso</dt><dd id="rx-d-users" class="font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Ceden el canal</dt><dd id="rx-d-pre" class="font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Antena</dt><dd id="rx-d-ant" class="text-right font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Encendido hace</dt><dd id="rx-d-up" class="font-mono">—</dd></div>
				<div class="flex justify-between gap-3"><dt class="text-muted-foreground">Versión</dt><dd id="rx-d-ver" class="font-mono">—</dd></div>
			</dl>
			<p class="mt-4 text-xs text-muted-foreground">El receptor topa la cuenta en su máximo: «7 de 7» no siempre significa lleno. Prueba igual. «Ceden el canal» son decodificadores automáticos que se apartan cuando entra una persona.</p>
		</div>
	</div>
	<script>
	(function(){
		var AJAX=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		var mejoresHoras='';
		function uptime(s){ if(s==null) return '—'; var d=Math.floor(s/86400), h=Math.floor(s%86400/3600); return d ? d+' d '+h+' h' : h+' h '+Math.floor(s%3600/60)+' min'; }
		function estado(){
			fetch(AJAX+'?action=rau_sdr_status', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
				var el=document.getElementById('rx-estado');
				if(!j.success){ el.innerHTML='<span class="text-rose-400">Fuera de línea</span>'; var lib0=document.getElementById('rx-libres'); if(lib0) lib0.textContent='El receptor no responde en este momento.'; document.dispatchEvent(new CustomEvent('rau:sdr-estado', {detail:null})); return; }
				var d=j.data;
				window.rauRxEstado=d; document.dispatchEvent(new CustomEvent('rau:sdr-estado', {detail:d}));
				var lib=document.getElementById('rx-libres');
				if(lib && window.RauRx && window.RauRx.encendido()) lib.textContent='Conectado: usas uno de los '+d.max+' canales';
				else if(lib) lib.textContent=(d.libres===0 ? 'Muy ocupado ahora: prueba igual' : d.libres+' de '+d.max+' canales libres')+(mejoresHoras ? ' · mejores horas: '+mejoresHoras : '');
				el.innerHTML=d.libres===0 ? '<span class="text-amber-400">Muy ocupado: prueba igual</span>' : '<span class="text-emerald-400">'+d.libres+' de '+d.max+' libres</span>';
				document.getElementById('rx-d-users').textContent=d.users==null?'—':d.users+' de '+d.max;
				document.getElementById('rx-d-pre').textContent=d.preempt;
				document.getElementById('rx-d-ant').textContent=d.antenna||'—';
				document.getElementById('rx-d-up').textContent=uptime(d.uptime);
				document.getElementById('rx-d-ver').textContent=d.version||'—';
			}).catch(function(e){ rauErr(e); });
		}
		function horas(){
			fetch(AJAX+'?action=rau_sdr_historial', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
				if(!j.success) return;
				var cont=document.getElementById('rx-horas'), nota=document.getElementById('rx-horas-nota');
				var hs=j.data.horas, max=Math.max(1, Math.max.apply(null, hs.map(function(h){ return h.libres||0; })));
				var ahora=new Date().toLocaleString('en-US',{timeZone:'America/Bogota',hour:'numeric',hour12:false})|0;
				cont.innerHTML=hs.map(function(h){
					var alto=h.libres==null?0:Math.max(4, Math.round(100*h.libres/max));
					var titulo=h.libres==null?(h.hora+' h: sin datos'):(h.hora+' h: '+h.libres+' libres en promedio, '+h.con_canal+' % del tiempo con canal');
					return '<div title="'+titulo+'" class="flex-1 rounded-t '+(h.hora===ahora?'bg-accent':'bg-primary/60')+'" style="height:'+alto+'%"></div>';
				}).join('');
				if(j.data.muestras<36){ nota.textContent='Recolectando datos: '+j.data.muestras+' mediciones hasta ahora. En un par de días la gráfica será confiable.'; return; }
				if(hs.every(function(h){ return !h.libres; })){ nota.textContent='Estos días el receptor ha estado casi siempre en su máximo: los decodificadores automáticos ocupan los canales. Aun así a veces se consigue uno.'; return; }
				var mejores=hs.filter(function(h){ return h.libres!=null; }).sort(function(a,b){ return b.libres-a.libres; }).slice(0,3).map(function(h){ return h.hora+' h'; });
				nota.textContent='Mejores horas para conseguir canal: '+mejores.join(', ')+'. La barra resaltada es la hora actual.';
				mejoresHoras=mejores.join(', '); estado();
			}).catch(function(e){ rauErr(e); });
		}
		horas(); estado(); setInterval(estado, 60000);
	})();
	</script>
	<?php rau_mapa_propagacion(); ?>
	<?php endif; ?>
	</div>

	<?php if ( $relay ) : ?>
	<?php // Los decodificadores se usan recién cuando llega audio: se cargan sin bloquear la página ?>
	<script defer src="<?php echo esc_url( get_template_directory_uri() . '/assets/radio/cw.js?ver=' . RAU_THEME_VERSION ); ?>"></script>
	<script defer src="<?php echo esc_url( get_template_directory_uri() . '/assets/radio/digi.js?ver=' . RAU_THEME_VERSION ); ?>"></script>
	<script>
	(function(){
		var FT_WORKER=<?php echo wp_json_encode( get_template_directory_uri() . '/assets/radio/ftx-worker.js?ver=' . RAU_THEME_VERSION ); ?>;
		var LEAFLET=<?php echo wp_json_encode( array( 'js' => get_template_directory_uri() . '/assets/vendor/leaflet/leaflet.js?ver=1.9.4', 'css' => get_template_directory_uri() . '/assets/vendor/leaflet/leaflet.css?ver=1.9.4' ) ); ?>;
		var SDR_POS=<?php $l = get_option( 'rau_sdr_lugar' ); echo wp_json_encode( is_array( $l ) && isset( $l['lat'] ) ? array( 'lat' => (float) $l['lat'], 'lng' => (float) $l['lng'], 'n' => rau_sdr_lugar() ) : null ); ?>;
		var RELAY=<?php echo wp_json_encode( preg_replace( '#^http(s?)://#', 'ws$1://', $relay ) ); ?>;
		var RECEPTORES=<?php echo wp_json_encode( array_values( (array) get_option( 'rau_receptores', array() ) ) ); ?>, SDR_HK4RAU=null;
		var AJAX_RX=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		// Con sesión iniciada, lo que se decodifica en JS8 se comparte con el centro de mando (ver inc/js8-oido.php)
		var JS8_API=<?php echo wp_json_encode( is_user_logged_in() ? array( 'url' => rest_url( 'rau/v1/js8' ), 'spots' => rest_url( 'rau/v1/spots' ), 'grab' => rest_url( 'rau/v1/grabaciones' ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) : null ); ?>;
		var PAGINA=<?php echo wp_json_encode( home_url( '/receptor/' ) ); ?>;
		var PLAN=<?php echo wp_json_encode( rau_receptor_plan() ); ?>;
		/* Bandas de radioaficionado con sus segmentos de uso (plan IARU Región 2) y otras escuchas.
		   Elegir la banda lleva a fonía (a CW en 30 m, que no tiene fonía); el segmento afina. */
		var BANDAS=[
			{n:'160 m', r:[1800,2000],   s:[['CW',1830,'cw'],['FT8',1840,'usb'],['Fonía',1900,'lsb']]},
			{n:'80 m',  r:[3500,4000],   s:[['CW',3530,'cw'],['FT8',3573,'usb'],['Fonía',3750,'lsb']]},
			{n:'40 m',  r:[7000,7300],   s:[['CW',7030,'cw'],['FT8',7074,'usb'],['Fonía',7150,'lsb']]},
			{n:'30 m',  r:[10100,10150], s:[['CW',10120,'cw'],['FT8',10136,'usb']]},
			{n:'20 m',  r:[14000,14350], s:[['CW',14030,'cw'],['FT8',14074,'usb'],['Fonía',14200,'usb']]},
			{n:'17 m',  r:[18068,18168], s:[['CW',18080,'cw'],['FT8',18100,'usb'],['Fonía',18130,'usb']]},
			{n:'15 m',  r:[21000,21450], s:[['CW',21030,'cw'],['FT8',21074,'usb'],['Fonía',21250,'usb']]},
			{n:'12 m',  r:[24890,24990], s:[['CW',24900,'cw'],['FT8',24915,'usb'],['Fonía',24950,'usb']]},
			{n:'10 m',  r:[28000,29700], s:[['CW',28030,'cw'],['FT8',28074,'usb'],['Fonía',28400,'usb']]}
		];
		var OTRAS=[['Onda corta 49 m',6000,'am',[5900,6200]],['Onda corta 31 m',9650,'am',[9400,9900]],['Onda corta 25 m',11800,'am',[11600,12100]],['Onda corta 19 m',15400,'am',[15100,15800]],['Onda corta 16 m',17700,'am',[17480,17900]],
			['Hora WWV 5 MHz',5000,'am',[4998,5002]],['Hora WWV 10 MHz',10000,'am',[9998,10002]],['Hora WWV 15 MHz',15000,'am',[14998,15002]]];
		function bandaDe(f){ for(var i=0;i<BANDAS.length;i++) if(f>=BANDAS[i].r[0] && f<=BANDAS[i].r[1]) return BANDAS[i]; return null; }
		function segmentoDe(b, f){
			if(!b) return null;
			var ft8=b.s.filter(function(x){ return x[0]==='FT8'; })[0], fonia=b.s.filter(function(x){ return x[0]==='Fonía'; })[0];
			if(ft8 && f>=ft8[1]-1 && f<=ft8[1]+4) return 'FT8';
			if(fonia && f>=(b.n==='40 m'?7040:b.n==='80 m'?3600:b.n==='160 m'?1843:fonia[1]-80)) return 'Fonía';
			return f<(ft8?ft8[1]:b.r[1]) ? 'CW' : 'Fonía';
		}
		// Bordes del filtro en Hz por modo: angosto, normal, ancho
		var MODOS={
			am:  {n:'AM',  f:[[-2500,2500],[-4900,4900],[-5500,5500]]},
			lsb: {n:'LSB', f:[[-2400,-300],[-2700,-300],[-3300,-200]]},
			usb: {n:'USB', f:[[300,2400],[300,2700],[200,3300]]},
			cw:  {n:'CW',  f:[[450,550],[300,700],[200,1000]]},
			nbfm:{n:'FM',  f:[[-6000,6000],[-6000,6000],[-6000,6000]]}
		};
		var PALETAS={
			ambar:  [[0,[6,8,16]],[0.3,[40,20,8]],[0.55,[180,83,9]],[0.78,[250,204,21]],[1,[255,251,220]]],
			rau:    null,
			clasica:[[0,[0,0,0]],[0.2,[0,0,150]],[0.4,[0,180,255]],[0.6,[0,255,0]],[0.8,[255,255,0]],[1,[255,0,0]]],
			turbo:  [[0,[48,18,59]],[0.15,[70,107,227]],[0.35,[27,207,212]],[0.5,[97,252,108]],[0.65,[210,233,53]],[0.8,[254,155,45]],[1,[122,4,3]]],
			fosforo:[[0,[2,8,4]],[0.6,[34,197,94]],[1,[220,252,231]]],
			grises: [[0,[0,0,0]],[1,[255,255,255]]]
		};
		var STEP=[7,8,9,10,11,12,13,14,16,17,19,21,23,25,28,31,34,37,41,45,50,55,60,66,73,80,88,97,107,118,130,143,157,173,190,209,230,253,279,307,337,371,408,449,494,544,598,658,724,796,876,963,1060,1166,1282,1411,1552,1707,1878,2066,2272,2499,2749,3024,3327,3660,4026,4428,4871,5358,5894,6484,7132,7845,8630,9493,10442,11487,12635,13899,15289,16818,18500,20350,22385,24623,27086,29794,32767];
		var ADJ=[-1,-1,-1,-1,2,4,6,8,-1,-1,-1,-1,2,4,6,8];

		var $=function(id){ return document.getElementById(id); };
		var st={freq:7150, modo:'lsb', filtro:1, paso:1, zoom:7, zoomMax:14, fft:1024, bw:30000000, on:false, snd:null, wf:null, ctx:null, gain:null,
			t:0, dec:{i:0,p:0}, rate:12000, rpos:0, rprev:0, ka:null, ts:0, paleta:'ambar', contraste:30, sq:0, agc:true, mute:false, sqAbierto:true, sqHasta:0,
			grab:null, esp:[], wfStart:0, wfSpan:0, piso:null, lut:null, ultXbin:-1, ultZoom:-1, spots:[], golpes:[], vigia:null, pico:0, perilla:0};
		var g=null;
		try {
			g=JSON.parse(localStorage.getItem('rau_rx')||'null');
			if(g){ if(g.freq) st.freq=g.freq; if(MODOS[g.modo]) st.modo=g.modo; if(g.zoom!=null) st.zoom=g.zoom|0; if(PALETAS.hasOwnProperty(g.paleta)) st.paleta=g.paleta;
				if(g.contraste) st.contraste=g.contraste; if(g.paso) st.paso=g.paso; if(g.filtro!=null) st.filtro=g.filtro; }
		} catch(e){}
		/* Primera visita (nada guardado ni frecuencia en el enlace): al conectar, el receptor busca en la
		   cascada la emisora de onda corta más fuerte de ese momento y la sintoniza, para que quien llega
		   oiga de inmediato para qué sirve. Las bandas van según la hora: de día (11 a 22 UTC, 6 a. m. a
		   5 p. m. en Colombia) propagan las altas; de noche, las bajas. */
		var hUtc=new Date().getUTCHours(), DIA=hUtc>=11 && hUtc<22;
		var OC_ARRANQUE = DIA ? [[11600,12100,'25 m'],[17480,17900,'16 m'],[15100,15800,'19 m'],[9400,9900,'31 m']]
			: [[5900,6200,'49 m'],[9400,9900,'31 m'],[11600,12100,'25 m'],[15100,15800,'19 m']];
		var EMISORAS=[[5000,'WWV, hora oficial de EE. UU.'],[10000,'WWV, hora oficial de EE. UU.'],[15000,'WWV, hora oficial de EE. UU.']];
		if(!g){ st.freq=(OC_ARRANQUE[0][0]+OC_ARRANQUE[0][1])/2; st.modo='am'; st.zoom=6; st.primera=true; }
		st.decod = st.modo==='cw' ? 'cw' : '';
		var q=new URLSearchParams(location.search);
		if(q.get('f') && +q.get('f')>0){ st.primera=false; st.freq=+q.get('f'); st.modo=MODOS[q.get('m')]?q.get('m'):modoPara(st.freq,''); }

		function guardar(){ try { localStorage.setItem('rau_rx', JSON.stringify({freq:st.freq, modo:st.modo, zoom:st.zoom, paleta:st.paleta, contraste:st.contraste, paso:st.paso, filtro:st.filtro})); } catch(e){} }
		function msg(t){ $('rx-msg').textContent=t; }
		function send(ws, t){ if(ws && ws.readyState===1) ws.send(t); }

		/* Modo probable para una frecuencia: la pista (comentario del spot) manda; si no, AM en
		   radiodifusión y señales horarias, CW en los segmentos de telegrafía, y LSB/USB según la
		   convención de radioaficionado (por debajo / encima de 10 MHz). */
		function modoPara(f, pista){
			pista=(pista||'').toUpperCase();
			if(/\bCW\b/.test(pista)) return 'cw';
			if(/FT8|FT4|JS8|PSK|RTTY|DIGI|SSTV|USB/.test(pista)) return 'usb';
			if(/\bLSB\b/.test(pista)) return 'lsb';
			if(/\bAM\b/.test(pista)) return 'am';
			if(PLAN.marcas.some(function(m){ return m[1]==='WWV' && Math.abs(m[0]-f)<2; })) return 'am';
			var ham=PLAN.ham.some(function(b){ return f>=b[0] && f<=b[1]; });
			if(!ham) return 'am';
			if((f>=7000&&f<7040)||(f>=3500&&f<3570)||(f>=14000&&f<14070)||(f>=21000&&f<21070)||(f>=28000&&f<28070)||(f>=10100&&f<10130)||(f>=18068&&f<18095)||(f>=24890&&f<24915)) return 'cw';
			return f<10000 ? 'lsb' : 'usb';
		}

		/* ── Paletas y dibujo ─────────────────────────────────────────────── */
		function rgbDeVar(v, def){
			var p=(getComputedStyle(document.documentElement).getPropertyValue(v)||'').trim().split(/\s+/).map(parseFloat);
			if(p.length!==3||p.some(isNaN)) return def;
			var h=p[0], s=p[1]/100, l=p[2]/100, a=s*Math.min(l,1-l), k=function(n){ return (n+h/30)%12; };
			return [0,8,4].map(function(n){ return 255*(l-a*Math.max(-1,Math.min(k(n)-3,Math.min(9-k(n),1)))); });
		}
		function construirLut(){
			var stops=PALETAS[st.paleta];
			if(!stops) stops=[[0,rgbDeVar('--background',[8,12,24])],[0.7,rgbDeVar('--primary',[31,213,249])],[1,[255,255,255]]];
			st.lut=[];
			for(var i=0;i<256;i++){ var t=i/255, j=1; while(j<stops.length-1 && t>stops[j][0]) j++;
				var a=stops[j-1], b=stops[j], u=Math.max(0,Math.min(1,(t-a[0])/(b[0]-a[0])));
				st.lut.push([a[1][0]+(b[1][0]-a[1][0])*u, a[1][1]+(b[1][1]-a[1][1])*u, a[1][2]+(b[1][2]-a[1][2])*u]); }
			var c=st.lut[205]; st.trazo='rgb('+c.map(Math.round).join(',')+')'; st.relleno='rgba('+c.map(Math.round).join(',')+',.15)';
		}
		function ajustar(cv){ var r=cv.getBoundingClientRect(), d=window.devicePixelRatio||1; var w=Math.max(1,Math.round(r.width*d)), h=Math.max(1,Math.round(r.height*d)); if(cv.width!==w||cv.height!==h){ cv.width=w; cv.height=h; } return d; }
		function xDe(fk, w){ return (fk*1000-st.wfStart)/st.wfSpan*w; }

		function dibujarPlan(){
			var cv=$('rx-plan'), d=ajustar(cv), c=cv.getContext('2d'), w=cv.width, h=cv.height;
			c.clearRect(0,0,w,h); if(!st.wfSpan) return;
			c.font=(10*d)+'px ui-monospace,monospace'; c.textBaseline='middle';
			PLAN.ham.forEach(function(b){ var x0=xDe(b[0],w), x1=xDe(b[1],w); if(x1<0||x0>w) return;
				c.fillStyle='rgba(148,163,184,.12)'; c.fillRect(x0,0,x1-x0,h); });
			PLAN.novicio.forEach(function(s){ var x0=xDe(s[0],w), x1=xDe(s[1],w); if(x1<0||x0>w) return;
				var cw=s[2]==='CW'; c.fillStyle=cw?'rgba(59,130,246,.28)':'rgba(16,185,129,.25)'; c.fillRect(x0,0,x1-x0,h);
				c.fillStyle=cw?'#93c5fd':'#6ee7b7'; var tx='Novicio: '+s[2], xi=Math.max(x0,0)+4*d, tw=c.measureText(tx).width;
				// Si una marca (FT8, WWV) cae encima del rótulo, el rótulo se corre a su derecha
				PLAN.marcas.forEach(function(m){ var xm=xDe(m[0],w); if(xm>=xi-2*d && xm<=xi+tw) xi=xm+c.measureText(m[1]).width+10*d; });
				if(Math.min(x1,w)-xi>tw) c.fillText(tx, xi, h/2); });
			// Bandas sin segmento de novicio (30 m, 20 m, 17 m…): el rótulo va sobre la propia banda
			PLAN.ham.forEach(function(b){ var x0=xDe(b[0],w), x1=xDe(b[1],w); if(x1<0||x0>w) return;
				if(PLAN.novicio.some(function(s){ return s[0]<b[1] && s[1]>b[0]; })) return;
				c.fillStyle='#94a3b8'; var xi=Math.max(x0,0)+4*d, libre=Math.min(x1,w)-xi;
				[b[2]+' · Primera y Avanzada', b[2]].some(function(tx){ if(libre>c.measureText(tx).width){ c.fillText(tx, xi, h/2); return true; } }); });
			PLAN.marcas.forEach(function(m){ var x=xDe(m[0],w); if(x<0||x>w) return; var tw=c.measureText(m[1]).width;
				c.fillStyle='rgba(8,12,24,.9)'; c.fillRect(x+d, 2*d, tw+4*d, h-4*d); c.fillStyle='#fbbf24'; c.fillRect(x-d/2,0,d,h); c.fillText(m[1], x+3*d, h/2); });
		}
		function dibujarEscala(){
			var cv=$('rx-esc'), d=ajustar(cv), c=cv.getContext('2d'), w=cv.width, h=cv.height;
			c.clearRect(0,0,w,h); if(!st.wfSpan) return;
			var spanK=st.wfSpan/1000, pasos=[0.1,0.2,0.5,1,2,5,10,20,50,100,200,500,1000,2000,5000], ideal=spanK/(w/d/90), p=pasos.find(function(x){ return x>=ideal; })||5000;
			c.font=(10*d)+'px ui-monospace,monospace'; c.fillStyle='#94a3b8'; c.strokeStyle='#334155'; c.textAlign='center';
			for(var f=Math.ceil(st.wfStart/1000/p)*p; f<(st.wfStart+st.wfSpan)/1000; f+=p){ var x=xDe(f,w);
				c.beginPath(); c.moveTo(x,0); c.lineTo(x,5*d); c.stroke(); c.fillText(p<1?f.toFixed(1):Math.round(f), x, 15*d); }
			for(var f2=Math.ceil(st.wfStart/1000/(p/5))*(p/5); f2<(st.wfStart+st.wfSpan)/1000; f2+=p/5){ var x2=xDe(f2,w); c.beginPath(); c.moveTo(x2,0); c.lineTo(x2,2*d); c.stroke(); }
		}
		/* La señal más fuerte a menos de 12 px de donde se tocó, si sobresale del ruido. No en el
		   segmento FT8, donde hay decenas de señales juntas y la frecuencia de la radio no se mueve. */
		function picoCerca(x, ancho){
			if(!st.on || !st.esp.length || segmentoDe(bandaDe(st.freq), st.freq)==='FT8') return null;
			var n=st.esp[0].length, prom=new Float32Array(n);
			st.esp.forEach(function(l){ for(var i=0;i<n;i++) prom[i]+=l[i]/st.esp.length; });
			var c=Math.round(x/ancho*n), rad=Math.max(2, Math.round(12/ancho*n)), mejor=-1, valor=-999;
			for(var i=Math.max(0,c-rad); i<=Math.min(n-1,c+rad); i++) if(prom[i]>valor){ valor=prom[i]; mejor=i; }
			var orden=Array.prototype.slice.call(prom).sort(function(a,b){ return a-b; });
			if(mejor<0 || valor<orden[n>>1]+10) return null;
			return (st.wfStart+(mejor+0.5)/n*st.wfSpan)/1000;
		}
		function dibujarEspectro(){
			var cv=$('rx-esp'), d=ajustar(cv), c=cv.getContext('2d'), w=cv.width, h=cv.height;
			c.clearRect(0,0,w,h); if(!st.esp.length) return;
			var n=st.esp[0].length, prom=new Float32Array(n);
			st.esp.forEach(function(l){ for(var i=0;i<n;i++) prom[i]+=l[i]/st.esp.length; });
			var suave=new Float32Array(n); for(var i=0;i<n;i++){ var a=0,k=0; for(var j=Math.max(0,i-2);j<=Math.min(n-1,i+2);j++){ a+=prom[j]; k++; } suave[i]=a/k; }
			var ord=Array.prototype.slice.call(suave).sort(function(a,b){ return a-b; }), mn=ord[Math.floor(n*0.05)]-2, mx=Math.max(ord[n-2]+3, mn+20);
			var y=function(v){ return Math.max(1,Math.min(h-1, h-2-(v-mn)/(mx-mn)*(h-6))); };
			c.beginPath(); c.moveTo(0,h); for(var x=0;x<n;x++) c.lineTo(x/n*w, y(suave[x])); c.lineTo(w,h); c.closePath(); c.fillStyle=st.relleno; c.fill();
			c.beginPath(); for(var x2=0;x2<n;x2++){ var X=x2/n*w, Y=y(suave[x2]); x2?c.lineTo(X,Y):c.moveTo(X,Y); } c.strokeStyle=st.trazo; c.lineWidth=1.2*d; c.stroke();
		}
		var wf=$('rx-wf'), w2d=wf.getContext('2d');
		function limpiarCascada(){ var c=st.lut?st.lut[0]:[0,0,0]; w2d.fillStyle='rgb('+c.map(Math.round).join(',')+')'; w2d.fillRect(0,0,wf.width,wf.height); }
		function lineaCascada(datos){
			var w=wf.width, h=wf.height, n=datos.length;
			// Dos píxeles por línea: la cascada se llena el doble de rápido y se ve viva desde el principio
			w2d.drawImage(wf, 0, 0, w, h-2, 0, 2, w, h-2);
			var orden=Array.prototype.slice.call(datos).sort(function(a,b){ return a-b; });
			var med=orden[n>>1]-255;                               // mediana de la línea = ruido de fondo
			st.piso = st.piso===null ? med : st.piso*0.9+med*0.1;   // la primera línea fija el piso: sin esto, las primeras salían saturadas
			var img=w2d.createImageData(w,1);
			for(var x=0;x<w;x++){
				var dbm=datos[Math.floor(x*n/w)]-255, t=Math.max(0,Math.min(1,(dbm-st.piso-2)/st.contraste)), col=st.lut[Math.round(Math.pow(t,0.8)*255)];
				img.data[4*x]=col[0]; img.data[4*x+1]=col[1]; img.data[4*x+2]=col[2]; img.data[4*x+3]=255;
			}
			w2d.putImageData(img,0,0); w2d.putImageData(img,0,1);
		}
		function marcas(){
			var pant=$('rx-pantalla').getBoundingClientRect().width;
			if(!st.wfSpan){ $('rx-marca').style.left='50%'; $('rx-filtro').style.width='0'; return; }
			var f=MODOS[st.modo].f[st.filtro], x=(st.freq*1000-st.wfStart)/st.wfSpan*pant;
			var a=((st.freq*1000+f[0])-st.wfStart)/st.wfSpan*pant, b=((st.freq*1000+f[1])-st.wfStart)/st.wfSpan*pant;
			$('rx-marca').style.left=Math.max(0,Math.min(pant,x))+'px';
			$('rx-filtro').style.left=Math.max(0,a)+'px'; $('rx-filtro').style.width=Math.max(1,Math.min(pant,b)-Math.max(0,a))+'px';
		}
		/* Estaciones reportadas (cluster DX, POTA) sobre la cascada. Las pone la consola de
		   Herramientas con RauRx.ponerSpots(); tocar una la sintoniza. */
		function dibujarSpots(){
			var cv=$('rx-spots'), d=ajustar(cv), c=cv.getContext('2d'), w=cv.width, h=cv.height;
			// Lo decodificado en el navegador (FT8, FT4, JS8) se ve 3 minutos encima de su señal
			var ahora=Date.now(); st.decSpots=(st.decSpots||[]).filter(function(s){ return ahora-s.t<180000; });
			var todos=st.spots.concat(st.decSpots);
			c.clearRect(0,0,w,h); st.golpes=[]; if(!st.wfSpan || !todos.length) return;
			c.font='bold '+(10*d)+'px ui-monospace,monospace'; c.textBaseline='middle';
			var fin=-1, vis=todos.filter(function(s){ var x=xDe(s.khz,w); return x>=0 && x<=w; }).sort(function(a,b){ return a.khz-b.khz; });
			vis.forEach(function(s){
				var x=xDe(s.khz,w), col=s.fuente==='pota'?'#34d399':s.fuente==='oido'?'#38bdf8':s.fuente==='dec'?'#f472b6':'#fbbf24';
				c.fillStyle=col; c.beginPath(); c.moveTo(x-3*d,0); c.lineTo(x+3*d,0); c.lineTo(x,5*d); c.closePath(); c.fill();
				var tx=s.dx||'', ancho=c.measureText(tx).width;
				if(x+2*d>fin){ c.fillText(tx, x+2*d, h*0.62); fin=x+2*d+ancho+6*d; }
				if(s.fuente!=='dec') st.golpes.push({x:x/d, s:s}); // tocar uno decodificado movería el dial y cortaría la decodificación
			});
		}
		/* Sin conexión (o sin cascada) la pantalla igual muestra la banda: plan, escala y spots
		   alrededor de la frecuencia, con el mismo ancho que tendría la cascada a este zoom. */
		function vistaLocal(forzar){
			if(st.on && st.wf && st.ultXbin>=0) return;
			var span=st.bw/Math.pow(2,st.zoom), f=st.freq*1000;
			if(!forzar && st.wfSpan===span && f>st.wfStart+span*0.1 && f<st.wfStart+span*0.9) return;
			st.wfSpan=span; st.wfStart=Math.max(0, Math.min(st.bw-span, f-span/2));
			dibujarPlan(); dibujarEscala(); dibujarSpots(); marcas();
		}
		function redibujarFijos(){ dibujarPlan(); dibujarEscala(); dibujarEspectro(); dibujarSpots(); marcas(); }

		/* ── Sintonía ─────────────────────────────────────────────────────── */
		/* La frecuencia grande es un sintonizador, en el formato de un radio: MHz.kHz.decenas de Hz
		   (7.150.00). Cada dígito sube o baja su propio valor. Detrás va «88.888.88» casi apagado,
		   como los segmentos sin encender de un LCD; el dígito de 10 MHz, si es cero, queda apagado. */
		var VALORES=[10000,1000,null,100,10,1,null,0.1,0.01];
		function pintarDigitos(){
			var t=st.freq.toFixed(2).replace('.',''); while(t.length<7) t='0'+t;
			var txt=t.slice(0,2)+'.'+t.slice(2,5)+'.'+t.slice(5,7);
			$('rx-freq').innerHTML=txt.split('').map(function(ch, i){
				var v=VALORES[i];
				if(v===null) return '<span class="rx-punto">.</span>';
				var apagado = i===0 && ch==='0';
				var nombre=v>=1000 ? v/1000+' MHz' : v>=1 ? v+' kHz' : Math.round(v*1000)+' Hz';
				return '<span class="rx-dig'+(apagado?' rx-dig-vacio':'')+'" data-v="'+v+'" role="button" tabindex="-1" aria-label="Dígito de '+nombre+': '+ch+'">'+(apagado?'!':ch)+'</span>';
			}).join('');
			$('rx-perilla').setAttribute('aria-valuetext', st.freq.toFixed(2)+' kHz');
		}
		function anchoTxt(){ var k=st.bw/Math.pow(2,st.zoom)/1000; return k>=1000 ? (k/1000).toFixed(k>=10000?0:1)+' MHz' : Math.round(k)+' kHz'; }
		function pasoTxt(v){ return v>=1 ? v+' kHz' : Math.round(v*1000)+' Hz'; }
		function emisoraDe(f){ var e=EMISORAS.filter(function(x){ return Math.abs(x[0]-f)<=2; })[0]; return e ? e[1] : ''; }
		function pintarBanda(){
			var b=bandaDe(st.freq), seg=segmentoDe(b, st.freq), otra=OTRAS.filter(function(o){ return st.freq>=o[3][0] && st.freq<=o[3][1]; })[0];
			$('rx-ind-banda').textContent = b ? b.n : otra ? otra[0].replace('Onda corta ','OC ').replace('Hora ','') : 'GEN';
			document.querySelectorAll('.rx-banda').forEach(function(x){ var on=(b && x.dataset.b===b.n) || (otra && x.dataset.o===otra[0]); x.setAttribute('aria-pressed', !!on);
				// En celular la fila se desliza: que la banda activa quede a la vista sin mover la página
				if(on && $('rx-bandas').scrollWidth>$('rx-bandas').clientWidth) $('rx-bandas').scrollLeft=Math.max(0, x.offsetLeft-$('rx-bandas').offsetLeft-24); });
			$('rx-segmentos').innerHTML = b ? '<span class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">'+b.n+':</span>'+b.s.map(function(x){
				var on=seg===x[0];
				return '<button type="button" data-f="'+x[1]+'" data-m="'+x[2]+'" class="rx-seg rx-tecla" aria-pressed="'+on+'">'+x[0]+' <span class="rx-tecla-sub">'+(x[1]/1000).toFixed(3)+'</span></button>';
			}).join('') : '';
			var nov=PLAN.novicio.filter(function(x){ return st.freq>=x[0] && st.freq<x[1]; })[0];
			$('rx-donde').textContent = b ? b.n+' · '+(seg==='FT8'?'segmento FT8 (digital)':seg==='CW'?'segmento de telegrafía':'segmento de fonía')+' · '+(nov?'Novicio: '+nov[2]:'solo Primera y Avanzada')
				: emisoraDe(st.freq) ? (otra ? otra[0].replace('Onda corta ','OC ')+' · ' : '')+emisoraDe(st.freq) : otra ? otra[0] : 'Fuera de las bandas de radioaficionado';
		}
		function pintarFreq(){
			pintarDigitos(); if(document.activeElement!==$('rx-input')) $('rx-input').value=st.freq.toFixed(2);
			$('rx-zoom').textContent=st.zoom; $('rx-ancho').textContent=anchoTxt(); $('rx-modo-txt').textContent=MODOS[st.modo].n;
			document.querySelectorAll('.rx-paso-txt').forEach(function(x){ x.textContent=pasoTxt(st.paso); });
			document.querySelectorAll('.rx-paso-op').forEach(function(x){ x.setAttribute('aria-pressed', +x.dataset.v===st.paso); });
			var anchoF=function(i){ var x=MODOS[st.modo].f[i]; return ((x[1]-x[0])/1000).toFixed(1)+'k'; };
			$('rx-ind-filtro').textContent=anchoF(st.filtro);
			document.querySelectorAll('.rx-filtro-op').forEach(function(x){ x.setAttribute('aria-pressed', +x.dataset.v===st.filtro); x.querySelector('.rx-tecla-sub').textContent=anchoF(+x.dataset.v); });
			$('rx-ind-agc').classList.toggle('is-on', st.agc); $('rx-agc').setAttribute('aria-pressed', st.agc);
			document.querySelectorAll('.rx-fav-ir').forEach(function(x){ x.setAttribute('aria-pressed', Math.abs(+x.dataset.f-st.freq)<0.005 && x.dataset.m===st.modo); });
			pintarBanda();
			if(st.decod==='cw' && st.modo!=='cw') st.decod='';
			$('rx-cw').classList.toggle('hidden', st.decod!=='cw');
			$('rx-ft').classList.toggle('hidden', !st.ft);
			$('rx-dig').classList.toggle('hidden', st.decod!=='rtty' && st.decod!=='psk31');
			$('rx-sstv').classList.toggle('hidden', st.decod!=='sstv');
			$('rx-js8-sub').classList.toggle('hidden', !st.ft || st.ft.modo!=='JS8');
			document.querySelectorAll('.rx-dec').forEach(function(b){ b.setAttribute('aria-pressed', b.dataset.d===(st.ft?st.ft.modo:st.decod)); });
			document.querySelectorAll('.rx-modo').forEach(function(b){ b.setAttribute('aria-pressed', b.dataset.m===st.modo); });
		}
		function enviarModo(){ var f=MODOS[st.modo].f[st.filtro], t='SET mod='+st.modo+' low_cut='+f[0]+' high_cut='+f[1]+' freq='+st.freq.toFixed(3); send(st.snd, t); if(st.comp) send(st.comp.ws, t); }
		/* Otro receptor: se cambia la conexión (el audio y la cascada pasan a ser los de ese receptor) */
		function elegirReceptor(id){
			var r=RECEPTORES.filter(function(x){ return x.id===id; })[0];
			if(SDR_HK4RAU===null) SDR_HK4RAU=SDR_POS;
			SDR_POS=r ? {lat:r.lat, lng:r.lng, n:r.lugar} : SDR_HK4RAU;
			compararCon('');
			var estaba=st.on; if(estaba) apagar(true);
			st.rx=id||''; $('rx-placa-marca').textContent=r ? r.nombre : 'HK4RAU';
			if(estaba) encender(); else msg('Pulsa «Escuchar» para conectarte a '+(r?r.nombre:'HK4RAU')+'.');
		}
		/* Comparar: el mismo dial en otro receptor, sin sonido, solo su S-meter junto al de este */
		function compararCon(id){
			if(st.comp){ try{ st.comp.ws.onclose=null; st.comp.ws.close(); }catch(e){} st.comp=null; }
			var e=$('rx-comp'); if(e) e.textContent='';
			if(!id || !st.on) { if(id && e) e.textContent='Primero pulsa «Escuchar».'; return; }
			var rid=id==='-'?'':id, nombre=rid?(RECEPTORES.filter(function(x){ return x.id===rid; })[0]||{}).nombre:'HK4RAU';
			if(rid===st.rx){ e.textContent='Es el mismo receptor.'; return; }
			var ws=new WebSocket(base(rid)+'/ws/kiwi/'+Date.now()+'/SND'); ws.binaryType='arraybuffer';
			st.comp={ws:ws, n:nombre, dbm:null};
			ws.onopen=function(){ ws.send('SET auth t=kiwi p='); ws.send('SERVER DE CLIENT hk4rau.com SND'); };
			ws.onmessage=function(ev){
				if(typeof ev.data==='string' || !st.comp || st.comp.ws!==ws) return;
				var u8=new Uint8Array(ev.data), tag=String.fromCharCode(u8[0],u8[1],u8[2]);
				if(tag==='MSG'){ var o=pares(new TextDecoder().decode(u8.subarray(4)));
					if('too_busy' in o){ e.textContent=nombre+': sin canales libres.'; compararCon(''); return; }
					if(o.audio_rate) ws.send('SET AR OK in='+o.audio_rate+' out=12000');
					if(o.sample_rate){ ws.send('SET squelch=0 max=0'); ws.send('SET agc=1 hang=0 thresh=-100 slope=6 decay=1000 manGain=50'); ws.send('SET compression=1'); ws.send('SET ident_user=hk4rau.com'); enviarModo(); }
					return; }
				if(tag!=='SND') return;
				var dbm=0.1*new DataView(ev.data).getUint16(8, false)-127; st.comp.dbm=st.comp.dbm===null?dbm:st.comp.dbm*0.9+dbm*0.1;
				var a=st.dbm, b=st.comp.dbm, sm=function(d){ return d<=-73 ? 'S'+Math.max(0,Math.round((d+127)/6)) : 'S9+'+Math.round(d+73); };
				e.textContent=nombre+': '+sm(b)+' ('+Math.round(b)+' dBm)'+(a!=null?' · aquí: '+sm(a)+' · diferencia '+(a-b>=0?'+':'')+Math.round(a-b)+' dB':'');
			};
			ws.onclose=function(){ if(st.comp && st.comp.ws===ws){ st.comp=null; e.textContent=nombre+': conexión cerrada.'; } };
		}
		function sintonizar(f, modo, recentrar){
			// Otra estación: el decodificador de CW vuelve a aprender tono y velocidad
			if(st.cw && Math.abs(f-st.freq)>0.3) st.cw.reiniciar();
			if(st.skim && Math.abs(f-st.freq)>0.05) st.skim.reiniciar();
			if(st.dig && st.decod!=='sstv' && Math.abs(f-st.freq)>0.3) st.dig.reiniciar();
			st.freq=Math.min(30000, Math.max(10, Math.round(f*100)/100));
			if(modo && MODOS[modo]) st.modo=modo;
			enviarModo();
			if(recentrar!==false) zoomA(st.zoom); else vistaLocal();
			pintarFreq(); guardar(); marcas();
			// La consola de Herramientas sigue la frecuencia: bitácora, lista de spots, banda del mapa
			document.dispatchEvent(new CustomEvent('rau:frecuencia', {detail:{khz:st.freq, modo:st.modo}}));
		}
		function zoomA(z){ st.zoom=Math.max(0, Math.min(st.zoomMax, z)); send(st.wf, 'SET zoom='+st.zoom+' cf='+st.freq.toFixed(3)); $('rx-zoom').textContent=st.zoom; $('rx-ancho').textContent=anchoTxt(); vistaLocal(true); }
		function redondear(f){ return Math.round(f/st.paso)*st.paso; }

		/* ── Decodificador de CW (assets/radio/cw.js) ─────────────────────────
		   Tono exacto por espectro, detector coherente con filtro a la medida de la velocidad, niveles
		   adaptativos y lectura de cada letra como la secuencia válida de Morse más probable. */
		// Letras con confianza menor a 0,2 se descartan (ruido); entre 0,2 y 0,5 se muestran tenues
		var cwLetras=[];
		function cwTexto(ch, conf){
			if(conf!==undefined && conf<0.2) return;
			if(ch===' ' && (!cwLetras.length || cwLetras[cwLetras.length-1].c===' ')) return;
			cwLetras.push({c:ch, d:conf!==undefined && conf<0.5}); if(cwLetras.length>400) cwLetras=cwLetras.slice(-400);
			$('rx-cw-texto').innerHTML=cwLetras.map(function(x){ var t=x.c.replace(/&/g,'&amp;').replace(/</g,'&lt;'); return x.d ? '<span class="rx-cw-duda">'+t+'</span>' : t; }).join('');
		}
		/* Skimmer: una línea por estación; tocarla sintoniza esa estación (en CW el tono de 500 Hz es la portadora) */
		var skimPend=false;
		function pintarSkim(){
			if(skimPend) return; skimPend=true;
			requestAnimationFrame(function(){
				skimPend=false; if(!st.skim) return;
				// Solo las estaciones que ya dijeron algo: un pico sin texto todavía no es una estación
				var l=st.skim.canales().filter(function(c){ return c.texto.trim(); });
				$('rx-cw-canales').innerHTML=l.length ? l.map(function(c){
					var f=st.freq+(c.tono-500)/1000, t=c.texto.slice(-60).replace(/&/g,'&amp;').replace(/</g,'&lt;').trim();
					return '<button type="button" class="rx-cw-canal" data-f="'+f.toFixed(2)+'"><span class="rx-cw-f">'+f.toFixed(2)+'</span><span class="rx-cw-v">'+(c.info&&c.info.senal?c.info.ppm+' PPM':'…')+'</span><span class="rx-cw-t">'+(t||'escuchando…')+'</span></button>';
				}).join('') : '<p class="rx-cw-nota">Buscando señales de CW en el filtro…</p>';
				$('rx-cw-info').textContent=l.length+(l.length===1?' estación':' estaciones')+' en el filtro de '+(MODOS.cw.f[st.filtro][1]-MODOS.cw.f[st.filtro][0])+' Hz';
			});
		}
		var cwInfoT=0;
		function cwInfo(o){
			var ahora=Date.now(); if(ahora-cwInfoT<400) return; cwInfoT=ahora;
			$('rx-cw-info').textContent = o.senal ? o.ppm+' PPM · tono '+o.tono+' Hz · '+o.snr+' dB sobre el ruido · banda '+o.banda+' Hz' : 'Sin señal clara en el filtro';
		}

		/* ── FT8 y FT4 (assets/radio/ftx.js, en un worker) ────────────────────
		   El audio se guarda como una sola secuencia de muestras con un reloj lineal: la hora de cada
		   muestra sale de los paquetes que llegaron con menos demora (los demás traen la variación de
		   la red), menos una corrección que se ajusta sola con el DT de lo decodificado. Al terminar
		   cada ranura UTC se le pasa al worker su audio completo. */
		var FT4F={'80 m':3575,'40 m':7047.5,'30 m':10140,'20 m':14080,'17 m':18104,'15 m':21140,'12 m':24919,'10 m':28180};
		// Frecuencias de llamada de JS8Call (kHz, dial USB)
		var JS8F={'160 m':1842,'80 m':3578,'40 m':7078,'30 m':10130,'20 m':14078,'17 m':18104,'15 m':21078,'12 m':24922,'10 m':28078,'6 m':50318};
		var ftFilas=[], ftWorker=null, ftId=0, ftT=null;
		function frecFT(modo){
			var b=bandaDe(st.freq)||BANDAS.filter(function(x){ return x.n===(DIA?'20 m':'40 m'); })[0];
			if(modo==='FT4') return FT4F[b.n]||14080;
			if(modo==='JS8') return JS8F[b.n]||14078;
			var s=b.s.filter(function(x){ return x[0]==='FT8'; })[0]; return s?s[1]:14074;
		}
		/* Diferencia del reloj del equipo con el servidor (ms, positiva = adelantado): de cinco consultas
		   vale la de menor ida y vuelta, que es la que menos error trae */
		function medirReloj(){
			var muestras=[], n=0;
			return new Promise(function(fin){
				(function una(){
					var t0=Date.now();
					fetch(AJAX_RX+'?action=rau_hora', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
						var t1=Date.now(); muestras.push({rtt:t1-t0, off:(t0+t1)/2-j.t});
					}).catch(function(){}).then(function(){ if(++n<5) setTimeout(una, 150); else { muestras.sort(function(a,b){ return a.rtt-b.rtt; }); fin(muestras.length ? muestras[0] : null); } });
				})();
			});
		}
		function audioDatos(si){
			// Para decodificar: ganancia fija (el AGC sube y baja con cada señal fuerte) y audio sin compresión ADPCM
			if(si){ send(st.snd, 'SET agc=0 hang=0 thresh=-100 slope=6 decay=1000 manGain=50'); send(st.snd, 'SET compression=0'); }
			else { agc(); send(st.snd, 'SET compression=1'); }
		}
		function elegirDecodificador(d){
			// Los modos digitales piden ganancia fija y audio sin compresión; al salir de todos se restaura
			var DATOS=['FT8','FT4','JS8','rtty','psk31','sstv'], habia=!!st.datos, ahora=DATOS.indexOf(d)>=0;
			st.ft=null; clearInterval(ftT);
			if(habia && !ahora) audioDatos(false);
			if(!habia && ahora) audioDatos(true);
			st.datos=ahora;
			st.decod = ['cw','rtty','psk31','sstv'].indexOf(d)>=0 ? d : '';
			st.dig=null;
			if(d==='cw'){ if(st.modo!=='cw') sintonizar(st.freq, 'cw', false); }
			else if(d==='rtty' || d==='psk31' || d==='sstv') elegirDigital(d);
			else if(d==='FT8' || d==='FT4' || d==='JS8'){
				var corr=0.6; try { corr=+localStorage.getItem('rau_rx_ftcorr')||0.6; } catch(e){}
				var sub=$('rx-js8-sub').value;
				st.ft={modo:d, sub:sub, ranura:d==='FT4'?7.5:d==='JS8'?JS8_RANURA[sub]:15, buf:null, n:0, marcas:[], hechas:{}, corr:corr, rapido:false};
				medirReloj().then(function(m){
					var ft=st.ft, av=$('rx-ft-reloj'); if(!ft || !m) return;
					// La demora de la red queda en la corrección; el reloj corrido se suma de entrada
					ft.corr=0.6+m.off/1000; st.relojOff=m.off;
					var s=Math.abs(m.off)/1000;
					av.classList.toggle('hidden', s<1);
					av.textContent='Tu reloj está '+s.toFixed(1).replace('.', ',')+' s '+(m.off>0?'adelantado':'atrasado')+' respecto a la hora UTC del servidor. Se compensa solo, pero conviene sincronizarlo (Windows: Configuración → Hora e idioma → Sincronizar ahora; celular: hora automática).';
				});
				st.filtro=2; sintonizar(frecFT(d), 'usb');
				if(!ftWorker){ try { ftWorker=new Worker(FT_WORKER); ftWorker.onmessage=ftResultado; } catch(e){ ftWorker=null; } }
				$('rx-ft-titulo').textContent='Decodificador '+d;
				$('rx-ft-info').textContent=ftWorker ? (st.on ? 'Esperando la próxima ranura…' : 'Pulsa «Escuchar» para empezar.') : 'Este navegador no puede decodificar en segundo plano.';
				if(d==='JS8' && ftWorker) $('rx-ft-info').textContent+=JS8_API ? ' Lo que decodifiques (salvo los latidos) se comparte con el centro de mando de emergencias, con tu nombre.' : ' Inicia sesión en el sitio para compartir lo que oigas con el centro de mando de emergencias.';
				ftT=setInterval(ftRevisar, 250);
			}
			pintarFreq();
		}
		/* ── RTTY, PSK31 y SSTV (assets/radio/digi.js) ─────────────────────── */
		// Ranuras de los submodos de JS8 (todos usan la misma frecuencia de llamada)
		var JS8_RANURA={normal:15, rapido:10, turbo:6, lento:30};
		var DIGI_F={rtty:{'80 m':3590,'40 m':7040,'20 m':14085,'15 m':21085,'10 m':28085}, psk31:{'80 m':3580,'40 m':7070,'30 m':10142,'20 m':14070,'17 m':18100,'15 m':21070,'10 m':28120},
			sstv:{'80 m':3845,'40 m':7171,'20 m':14230,'15 m':21340,'10 m':28680}};
		var DIGI_RANGO={rtty:[[3580,3600],[7030,7050],[14075,14100],[21075,21100],[28075,28100]], psk31:[[3575,3590],[7065,7075],[10138,10146],[14068,14075],[18098,18104],[21068,21075],[28118,28124]],
			sstv:[[3840,3850],[7165,7176],[14225,14235],[21335,21345],[28675,28685]]};
		var sstvImgs=[];
		function elegirDigital(d){
			var enRango=DIGI_RANGO[d].some(function(r){ return st.freq>=r[0] && st.freq<=r[1]; });
			var f=st.freq; if(!enRango){ var b=bandaDe(st.freq)||BANDAS.filter(function(x){ return x.n===(DIA?'20 m':'40 m'); })[0]; f=DIGI_F[d][b.n]||DIGI_F[d][DIA?'20 m':'40 m']; }
			// SSTV en 40 y 80 m va en LSB; RTTY y PSK31, en USB (el RTTY detecta solo la polaridad)
			st.filtro=2; sintonizar(f, d==='sstv' && f<10000 ? 'lsb' : 'usb');
			if(!window.RauDigi) return;
			if(d==='sstv'){
				st.dig=RauDigi.sstv(st.rate, sstvLinea, function(t){ $('rx-sstv-info').textContent=t; });
			} else {
				$('rx-dig-titulo').textContent='Decodificador '+(d==='rtty'?'RTTY':'PSK31');
				$('rx-dig-nota').textContent=d==='rtty' ? 'RTTY de radioaficionado: 45,45 baudios y 170 Hz de desplazamiento. Se engancha solo al par de tonos y detecta la polaridad. Con filtro ancho, toca la señal en la cascada para centrarla.' : 'PSK31: se engancha a la señal más fuerte del filtro y la sigue. Con varias señales, toca la que quieras en la cascada y usa el filtro «Angosto».';
				$('rx-dig-texto').textContent='';
				st.dig=RauDigi[d](st.rate, function(t){ var e=$('rx-dig-texto'); e.textContent=t.slice(-1500); }, function(t){ $('rx-dig-info').textContent=t; });
			}
		}
		function sstvLinea(o){
			var cv=$('rx-sstv-cv');
			if(cv.width!==o.ancho || cv.height!==o.alto){ cv.width=o.ancho; cv.height=o.alto; }
			if(o.linea%4 && o.linea<o.alto) return;
			cv.getContext('2d').putImageData(new ImageData(new Uint8ClampedArray(o.img), o.ancho, o.alto), 0, 0);
			if(o.linea>=o.alto){
				var url=cv.toDataURL('image/png'), nombre='HK4RAU-SSTV-'+o.modo.replace(/ /g,'')+'-'+new Date().toISOString().slice(0,16).replace(/[:T]/g,'')+'.png';
				sstvImgs.unshift({url:url, nombre:nombre}); if(sstvImgs.length>6) sstvImgs.pop();
				$('rx-sstv-guardar').disabled=false;
				$('rx-sstv-galeria').innerHTML=sstvImgs.map(function(x, i){ return '<a href="'+x.url+'" download="'+x.nombre+'" title="Descargar"><img src="'+x.url+'" alt="Imagen SSTV '+(i+1)+'" width="80" height="64"></a>'; }).join('');
			}
		}
		function ftGuardar(m){
			var ft=st.ft, tasa=st.rate;
			if(!ft.buf || ft.tasa!==tasa){ ft.tasa=tasa; ft.buf=new Float32Array(Math.round(45*tasa)); ft.n=0; ft.marcas=[]; }
			var L=ft.buf.length;
			for(var i=0;i<m.length;i++) ft.buf[(ft.n+i)%L]=m[i];
			ft.n+=m.length;
			var ahora=Date.now(); ft.marcas.push([ahora, ft.n]);
			while(ft.marcas.length && ft.marcas[0][0]<ahora-30000) ft.marcas.shift();
		}
		/* Hora (ms) de la muestra 0 según los paquetes menos demorados de los últimos 30 s */
		function ftAncla(){ var ft=st.ft, a=Infinity; ft.marcas.forEach(function(x){ a=Math.min(a, x[0]-x[1]/ft.tasa*1000); }); return a; }
		function ftRevisar(){
			var ft=st.ft; if(!ft || !st.on || !ft.buf || !ftWorker) return;
			var R=ft.ranura*1000, ahora=Date.now()-ft.corr*1000, S=Math.floor(ahora/R)*R-R;
			if(ft.hechas[S] || ahora<S+R+300) return;
			ft.hechas[S]=1;
			var A=ftAncla(), k0=Math.round((S+ft.corr*1000-A)/1000*ft.tasa), n=Math.round(ft.ranura*ft.tasa), L=ft.buf.length;
			// Hace falta la ranura entera dentro de lo guardado
			if(k0<ft.n-L || k0+n>ft.n || k0<0){ $('rx-ft-info').textContent='Juntando audio de una ranura completa…'; return; }
			var x=new Float32Array(n); for(var i=0;i<n;i++) x[i]=ft.buf[(k0+i)%L];
			ftWorker.postMessage({id:++ftId, modo:ft.modo, submodo:ft.sub, muestras:x, tasa:Math.abs(ft.tasa-12000)<2?12000:ft.tasa, profundo:!ft.rapido});
			ft.pend={id:ftId, S:S, dial:st.freq, modo:ft.modo};
			$('rx-ft-info').textContent='Decodificando la ranura de las '+horaUtc(S)+'…';
		}
		function horaUtc(ms){ var d=new Date(ms); return [d.getUTCHours(), d.getUTCMinutes(), d.getUTCSeconds()].map(function(v){ return (v<10?'0':'')+v; }).join(''); }
		function ftResultado(e){
			var ft=st.ft, d=e.data; if(!ft || !ft.pend || d.id!==ft.pend.id) return;
			var p=ft.pend; ft.pend=null;
			if(d.error){ $('rx-ft-info').textContent='Error al decodificar: '+d.error; return; }
			// Equipo lento: si decodificar toma más de media ranura, se pasa a la etapa rápida (decodifica menos, pero a tiempo)
			if(!ft.rapido && d.ms>ft.ranura*500) ft.rapido=true;
			var res=d.res.filter(function(r){ return r.texto; });
			res.forEach(function(r){ ftFilas.unshift({utc:horaUtc(p.S), snr:r.snr, dt:r.dt, f:r.freq, t:r.texto.trim(), nuevo:true}); r.t=r.texto; });
			// JS8: los mensajes de varios marcos, ya armados, van como una fila más
			(d.mensajes||[]).forEach(function(m){ if(m.partes>1) ftFilas.unshift({utc:horaUtc(p.S), snr:'', dt:null, f:m.freq, t:'✉ '+m.texto, nuevo:true}); });
			if(p.modo==='JS8') js8Compartir(p, res, d.mensajes||[]);
			pskReportar(p, res);
			res.forEach(function(r){ var x=remitente(r); if(x) st.decSpots=(st.decSpots||[]).filter(function(s){ return s.dx!==x.de; }).concat([{khz:p.dial+r.freq/1000, dx:x.de, fuente:'dec', t:Date.now()}]); });
			dibujarSpots();
			if(ftFilas.length>300) ftFilas.length=300;
			// Corrección de tiempo: lleva la mediana del DT hacia 0
			if(res.length>=2){ var dts=res.map(function(r){ return r.dt; }).sort(function(a,b){ return a-b; }), med=dts[dts.length>>1];
				if(Math.abs(med)>0.15){ ft.corr=Math.max(-2, Math.min(4, ft.corr+0.7*med)); try { localStorage.setItem('rau_rx_ftcorr', ft.corr.toFixed(2)); } catch(e2){} } }
			$('rx-ft-info').textContent=horaUtc(p.S)+' UTC · '+res.length+(res.length===1?' mensaje':' mensajes')+' en '+(d.ms/1000).toFixed(1)+' s'+(ft.rapido?' (modo rápido: este equipo no alcanza la decodificación completa)':'')+' · dial '+(p.dial/1000).toFixed(4)+' MHz USB · corrección de hora '+(ft.corr>=0?'+':'')+ft.corr.toFixed(1)+' s';
			res.forEach(function(r){ ftEstacion(r, p.S); });
			pintarFt(); pintarMapa();
		}
		/* JS8 al centro de mando: con sesión iniciada se envía lo decodificado (sin latidos) para que la sala
		   lo vea como «oído en el receptor HK4RAU». Sin sesión, nada sale del navegador. */
		/* PSK Reporter: con sesión iniciada, quien transmitió lo que se decodificó (indicativo, locator, SNR) se
		   reporta como oído por HK4RAU. El servidor descarta lo que el Kiwi ya reporta y junta los envíos. */
		function remitente(r){
			if(r.de) return {de:r.de, g:r.cuadricula||''};
			var c=r.campos; if(!c || !c[1] || /^</.test(c[1])) return null;
			return {de:c[1], g:/^[A-R]{2}[0-9]{2}$/.test(c[2]||'')?c[2]:''};
		}
		function pskReportar(p, res){
			if(!JS8_API || st.rx) return; // solo lo oído en el HK4RAU se reporta como del HK4RAU
			var items=[]; res.forEach(function(r){ var x=remitente(r); if(x) items.push({de:x.de, cuadricula:x.g, f:r.freq, snr:r.snr}); });
			if(!items.length) return;
			fetch(JS8_API.spots, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json', 'X-WP-Nonce':JS8_API.nonce}, body:JSON.stringify({modo:p.modo, ranura:p.S, dial:Math.round(p.dial*1000), items:items})}).catch(function(){});
		}
		function js8Compartir(p, res, mensajes){
			if(!JS8_API || st.rx) return; // solo lo oído en el HK4RAU se reporta como del HK4RAU
			var items=res.filter(function(r){ return r.tipo!=='latido' && r.texto; }).map(function(r){ return {texto:r.texto.trim(), de:r.de||'', para:r.para||'', grupo:r.grupo||'', tipo:r.tipo, f:r.freq, snr:r.snr}; })
				.concat(mensajes.filter(function(m){ return m.partes>1; }).map(function(m){ return {texto:m.texto, tipo:'mensaje', f:m.freq, snr:null}; }));
			if(!items.length) return;
			fetch(JS8_API.url, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json', 'X-WP-Nonce':JS8_API.nonce}, body:JSON.stringify({ranura:p.S, dial:Math.round(p.dial*1000), items:items.slice(0, 30)})}).catch(function(){});
		}
		/* ── Mapa de lo oído: quien envía su locator (CQ o respuesta con cuadrícula) ── */
		var ftEst={}, ftMapa=null;
		function cuadricula(g){ var a=g.charCodeAt(0)-65, b=g.charCodeAt(1)-65; return {lat:b*10-90+(+g[3])+0.5, lng:a*20-180+(+g[2])*2+1}; }
		function km(a, b){ var r=Math.PI/180, x=Math.sin((b.lat-a.lat)*r/2), y=Math.sin((b.lng-a.lng)*r/2), h=x*x+Math.cos(a.lat*r)*Math.cos(b.lat*r)*y*y; return Math.round(12742*Math.asin(Math.sqrt(h))); }
		function ftEstacion(r, S){
			if(r.cuadricula && r.de){ if(/^[A-R]{2}[0-9]{2}$/.test(r.cuadricula)) ftEst[r.de]={c:r.de, g:r.cuadricula, p:cuadricula(r.cuadricula), snr:r.snr, t:S, msg:r.texto}; return; }
			if(!r.campos) return; var g=String(r.campos[2]||'').replace(/^R /,''), c=r.campos[1];
			if(!/^[A-R]{2}[0-9]{2}$/.test(g) || !c || /^</.test(c)) return;
			ftEst[c]={c:c, g:g, p:cuadricula(g), snr:r.snr, t:S, msg:r.t||r.texto};
		}
		function cargarLeaflet(){
			if(window.L) return Promise.resolve();
			return new Promise(function(ok, mal){ var l=document.createElement('link'); l.rel='stylesheet'; l.href=LEAFLET.css; document.head.appendChild(l); var s=document.createElement('script'); s.src=LEAFLET.js; s.onload=ok; s.onerror=mal; document.head.appendChild(s); });
		}
		function pintarMapa(){
			var cont=$('rx-ft-mapa'); if(cont.classList.contains('hidden')) return;
			cargarLeaflet().then(function(){
				if(!ftMapa){ ftMapa={m:L.map(cont, {worldCopyJump:true}).setView([10, -60], 2), capa:null};
					L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:8, attribution:'© colaboradores de OpenStreetMap'}).addTo(ftMapa.m); }
				if(ftMapa.capa) ftMapa.capa.remove();
				var capa=L.layerGroup(), ahora=Date.now(), pts=[];
				if(SDR_POS){ L.circleMarker([SDR_POS.lat, SDR_POS.lng], {radius:7, color:'#0b0f17', weight:2, fillColor:'#ffb547', fillOpacity:1}).bindTooltip('Receptor HK4RAU · '+SDR_POS.n).addTo(capa); pts.push([SDR_POS.lat, SDR_POS.lng]); }
				Object.keys(ftEst).forEach(function(k){
					var e=ftEst[k], min=(ahora-e.t)/60000; if(min>30){ delete ftEst[k]; return; }
					var co=/^(HK|HJ|5J|5K)[0-9]/.test(e.c), color=co?'#67e8f9':min<2?'#86efac':'#94a3b8';
					if(SDR_POS) L.polyline([[SDR_POS.lat, SDR_POS.lng], [e.p.lat, e.p.lng]], {color:color, weight:1, opacity:0.35}).addTo(capa);
					L.circleMarker([e.p.lat, e.p.lng], {radius:5, color:'#0b0f17', weight:1, fillColor:color, fillOpacity:0.9})
						.bindTooltip(e.c+' · '+e.g+' · '+(e.snr>0?'+':'')+e.snr+' dB'+(SDR_POS?' · '+km(SDR_POS, e.p).toLocaleString('es-CO')+' km':'')+' · hace '+Math.round(min)+' min').addTo(capa);
					pts.push([e.p.lat, e.p.lng]);
				});
				capa.addTo(ftMapa.m); ftMapa.capa=capa; ftMapa.m.invalidateSize();
				if(pts.length>1 && !ftMapa.ajustado){ ftMapa.m.fitBounds(pts, {padding:[20,20], maxZoom:5}); ftMapa.ajustado=true; }
			}).catch(function(){ cont.textContent='No se pudo cargar el mapa.'; });
		}
		var CO=/\b(HK|HJ|5J|5K)[0-9][A-Z0-9]{1,4}\b/g;
		function pintarFt(){
			var solo=$('rx-ft-cq').checked, ultima=ftFilas.length?ftFilas[0].utc:'';
			$('rx-ft-filas').innerHTML=ftFilas.filter(function(r){ return !solo || /^CQ\b/.test(r.t); }).slice(0, 150).map(function(r){
				var t=r.t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(CO, '<b class="rx-ft-co">$&</b>');
				return '<tr class="'+(/^CQ\b/.test(r.t)?'rx-ft-cqf ':'')+(r.utc===ultima?'rx-ft-ult':'')+'"><td>'+r.utc+'</td><td>'+(r.snr===''||r.snr===null?'':(r.snr>0?'+':'')+r.snr)+'</td><td>'+(r.dt===null?'':r.dt.toFixed(1))+'</td><td>'+r.f+'</td><td>'+t+'</td></tr>';
			}).join('') || '<tr><td colspan="5">Todavía nada decodificado.</td></tr>';
		}

		/* ── Audio ────────────────────────────────────────────────────────── */
		function adpcm(bytes, dec){
			var out=new Float32Array(bytes.length*2), k=0;
			function uno(code){ var step=STEP[dec.i]; dec.i=Math.max(0,Math.min(88,dec.i+ADJ[code]));
				var diff=step>>3; if(code&1) diff+=step>>2; if(code&2) diff+=step>>1; if(code&4) diff+=step; if(code&8) diff=-diff;
				dec.p=Math.max(-32768,Math.min(32767,dec.p+diff)); return dec.p; }
			for(var i=0;i<bytes.length;i++){ var b=bytes[i]; out[k++]=uno(b&15)/32768; out[k++]=uno(b>>4)/32768; }
			return out;
		}
		// Remuestreo lineal continuo de ~12 kHz a la tasa del navegador (Safari antiguo no acepta buffers de 12 kHz)
		function remuestrear(inp){
			var r=st.rate/st.ctx.sampleRate, out=[], pos=st.rpos;
			while(pos<inp.length-1){ var i=Math.floor(pos), f=pos-i, a=i<0?st.rprev:inp[i]; out.push(a+(inp[i+1]-a)*f); pos+=r; }
			st.rpos=pos-inp.length; st.rprev=inp[inp.length-1];
			return Float32Array.from(out);
		}
		function reproducir(m){
			var ctx=st.ctx; if(!ctx || !m.length) return;
			if(st.grab) grabar(m);
			if(st.modo==='cw' && st.decod==='cw' && window.RauCW){
				if(st.skimOn){ if(!st.skim) st.skim=RauCW.crearSkimmer(st.rate, pintarSkim, {fmin:200, fmax:1100}); st.skim.procesar(m); }
				else { if(!st.cw) st.cw=RauCW.crear(st.rate, cwTexto, cwInfo); st.cw.procesar(m); }
			}
			if(st.ft) ftGuardar(m);
			if(st.dig) st.dig.procesar(m);
			m=remuestrear(m); if(!m.length) return;
			var buf=ctx.createBuffer(1, m.length, ctx.sampleRate); buf.getChannelData(0).set(m);
			var src=ctx.createBufferSource(); src.buffer=buf; src.connect(st.gain);
			var ahora=ctx.currentTime;
			if(st.t<ahora+0.05) st.t=ahora+0.25;          // buffer vacío: margen para la variación de la red
			if(st.t>ahora+1.2) return;                    // demasiado atrasado: se descarta para no acumular eco
			src.start(st.t); st.t+=buf.duration;
		}
		function volumen(){
			if(!st.gain) return;
			var v=st.mute||!st.sqAbierto ? 0 : Math.pow($('rx-vol').value/100,2)*2;
			st.gain.gain.setTargetAtTime(v, st.ctx.currentTime, 0.03);
		}
		function smeter(raw){
			var dbm=0.1*raw-127; st.dbm = st.dbm==null ? dbm : st.dbm*0.9+dbm*0.1; var s=dbm<=-73 ? Math.max(0,Math.round((dbm+127)/6)) : 9;
			$('rx-s').textContent=(dbm<=-73 ? 'S'+s : 'S9+'+Math.round(dbm+73)+' dB')+' · '+Math.round(dbm)+' dBm';
			var pct=Math.max(0,Math.min(100,(dbm+127)/114*100));
			$('rx-smeter').style.width=pct+'%';
			st.pico = pct>st.pico ? pct : Math.max(pct, st.pico-0.8);
			$('rx-pico').style.left=st.pico+'%';
			// Silenciador del lado del navegador: abre por encima del umbral y espera 0,8 s antes de cerrar
			if(st.sq>0){ var umbral=-127+6*st.sq, ahora=Date.now();
				if(dbm>=umbral){ st.sqHasta=ahora+800; if(!st.sqAbierto){ st.sqAbierto=true; volumen(); } }
				else if(st.sqAbierto && ahora>st.sqHasta){ st.sqAbierto=false; volumen(); } }
		}

		/* Grabación: muestras a la tasa del receptor, WAV PCM 16 bits mono. Máximo 10 minutos. */
		function grabar(m){
			var g=st.grab; if(g.n+m.length>st.rate*600){ pararGrabacion(); msg('La grabación llegó a 10 minutos y se guardó.'); return; }
			var i16=new Int16Array(m.length); for(var i=0;i<m.length;i++) i16[i]=Math.max(-32768,Math.min(32767,m[i]*32768));
			g.partes.push(i16); g.n+=m.length;
		}
		function pararGrabacion(){
			var g=st.grab; st.grab=null; $('rx-rec').textContent='● Grabar'; $('rx-ind-rec').classList.remove('is-on'); $('rx-rec').setAttribute('aria-pressed','false'); $('rx-rec').classList.remove('text-rose-400');
			if(!g || !g.n) return;
			var tasa=Math.round(st.rate), dv=new DataView(new ArrayBuffer(44)), s=function(o,t){ for(var i=0;i<t.length;i++) dv.setUint8(o+i,t.charCodeAt(i)); };
			s(0,'RIFF'); dv.setUint32(4,36+g.n*2,true); s(8,'WAVE'); s(12,'fmt '); dv.setUint32(16,16,true); dv.setUint16(20,1,true); dv.setUint16(22,1,true);
			dv.setUint32(24,tasa,true); dv.setUint32(28,tasa*2,true); dv.setUint16(32,2,true); dv.setUint16(34,16,true); s(36,'data'); dv.setUint32(40,g.n*2,true);
			var blob=new Blob([dv.buffer].concat(g.partes), {type:'audio/wav'}), url=URL.createObjectURL(blob), a=document.createElement('a');
			// Con sesión iniciada y hasta 3 minutos, se puede subir para compartirla con un enlace
			st.ultGrab={blob:blob, freq:g.freq, modo:g.modo, inicio:g.inicio}; $('rx-grab-subir').classList.toggle('hidden', !JS8_API || g.n/tasa>180);
			a.href=url; a.download='HK4RAU-'+g.freq.toFixed(2)+'kHz-'+g.modo+'-'+new Date(g.inicio).toISOString().slice(0,16).replace(/[:T]/g,'')+'.wav';
			document.body.appendChild(a); a.click(); a.remove(); setTimeout(function(){ URL.revokeObjectURL(url); }, 5000);
		}

		/* ── Conexión ─────────────────────────────────────────────────────── */
		function pares(t){ var o={}; t.split(' ').forEach(function(p){ var i=p.indexOf('='); if(i>0){ var v=p.slice(i+1); try { v=decodeURIComponent(v); } catch(e){} o[p.slice(0,i)]=v; } else if(p) o[p]=''; }); return o; }
		function avisoMsg(o, stream){
			// El HK4RAU tiene 8 canales de audio y solo 3 con cascada: si esos están ocupados se sigue escuchando.
			if('too_busy' in o && stream==='W/F'){ $('rx-ayuda').textContent='Cascada no disponible: sus canales están ocupados. El audio sigue.'; $('rx-ayuda').classList.remove('hidden'); try{ st.wf.onclose=null; st.wf.close(); }catch(e){} st.wf=null; return true; }
			if('too_busy' in o){ msg('Los '+o.too_busy+' canales del receptor están ocupados en este momento.'); apagar(true); $('rx-avisar').classList.remove('hidden'); return true; }
			if('badp' in o && o.badp!=='0'){ msg('El receptor no aceptó la conexión: todos los canales libres requieren clave.'); apagar(true); return true; }
			if('down' in o){ msg('El receptor está en mantenimiento. Intenta más tarde.'); apagar(true); return true; }
			if('inactivity_timeout' in o || 'tlimit_exp' in o){ msg('El receptor cerró la sesión por tiempo. Pulsa «Escuchar» para volver.'); apagar(true); return true; }
			return false;
		}
		function base(id){ return RELAY+(id?'/r/'+id:''); }
		function abrir(stream, onmsg, onbin){
			var ws=new WebSocket(base(st.rx)+'/ws/kiwi/'+st.ts+'/'+stream);
			ws.binaryType='arraybuffer';
			ws.onopen=function(){ ws.send('SET auth t=kiwi p='); ws.send('SERVER DE CLIENT hk4rau.com '+stream); };
			ws.onmessage=function(ev){
				if(typeof ev.data==='string') return;
				var u8=new Uint8Array(ev.data), tag=String.fromCharCode(u8[0],u8[1],u8[2]);
				if(tag==='MSG'){ var o=pares(new TextDecoder().decode(u8.subarray(4))); if(!avisoMsg(o, stream)) onmsg(o); }
				else onbin(tag, ev.data, u8);
			};
			ws.onerror=function(){ if(st.on) msg('No fue posible conectar con el receptor. Revisa tu conexión o intenta en unos minutos.'); };
			ws.onclose=function(){ if(st.on && stream==='SND'){ msg('Se cerró la conexión con el receptor.'); apagar(true); } };
			return ws;
		}
		function agc(){ send(st.snd, st.agc ? 'SET agc=1 hang=0 thresh=-100 slope=6 decay=1000 manGain=50' : 'SET agc=0 hang=0 thresh=-100 slope=6 decay=1000 manGain=60'); }
		function encender(){
			if(st.on) return;
			st.ctx=st.ctx||new (window.AudioContext||window.webkitAudioContext)(); st.ctx.resume();
			if(!st.gain){ st.gain=st.ctx.createGain(); st.gain.connect(st.ctx.destination); }
			st.on=true; st.t=0; st.dec={i:0,p:0}; st.rpos=0; st.rprev=0; st.ts=Date.now(); st.esp=[]; st.sqAbierto=st.sq===0; volumen();
			$('rx-libres').textContent='Conectando…';
			$('rx-power').textContent='■ Detener'; $('rx-power').setAttribute('aria-pressed','true'); $('rx-ind-rx').classList.add('is-on'); $('rx-ayuda').classList.add('hidden'); $('rx-avisar').classList.add('hidden');
			msg('Conectando con el receptor…'); document.dispatchEvent(new CustomEvent('rau:rx-encendido', {detail:{on:true}}));
			st.snd=abrir('SND', function(o){
				if(o.audio_rate) send(st.snd, 'SET AR OK in='+o.audio_rate+' out='+Math.round(st.ctx.sampleRate));
				if(o.sample_rate){
					st.rate=parseFloat(o.sample_rate)||12000;
					send(st.snd, 'SET squelch=0 max=0'); send(st.snd, 'SET genattn=0'); send(st.snd, 'SET gen=0 mix=-1');
					agc(); send(st.snd, 'SET compression=1'); send(st.snd, 'SET ident_user=hk4rau.com');
					sintonizar(st.freq, st.modo);
					if(st.datos) audioDatos(true);
					msg('Escuchando. Toca una señal en la cascada para sintonizarla o arrástrala para recorrer la banda.');
					$('rx-libres').textContent='Conectado: usas uno de los canales del receptor';
					if(st.primera){ st.primera=false; buscarEmisora(0); }
				}
				if(o.audio_adpcm_state){ var v=o.audio_adpcm_state.split(','); st.dec={i:+v[0]|0,p:+v[1]|0}; }
			}, function(tag, buf, u8){
				if(tag!=='SND') return;
				var dv=new DataView(buf); smeter(dv.getUint16(8, false));
				var datos=u8.subarray(10), m;
				if(u8[3]&0x10) m=adpcm(datos, st.dec);
				else { m=new Float32Array(datos.length>>1); for(var i=0;i<m.length;i++) m[i]=dv.getInt16(10+2*i,false)/32768; }
				reproducir(m);
			});
			// La cascada se abre un poco después del audio, con la misma marca, como la interfaz oficial
			setTimeout(function(){ if(!st.on) return; st.wf=abrir('W/F', function(o){
				if(o.wf_fft_size) st.fft=+o.wf_fft_size;
				if(o.zoom_max) st.zoomMax=+o.zoom_max;
				if(o.bandwidth) st.bw=+o.bandwidth;
				if('wf_setup' in o){ send(st.wf,'SET send_dB=1'); send(st.wf,'SET wf_comp=0'); send(st.wf,'SET maxdb=-20 mindb=-120'); send(st.wf,'SET wf_speed=4'); zoomA(st.zoom); }
			}, function(tag, buf){
				if(tag!=='W/F') return;
				var dv=new DataView(buf), xbin=dv.getUint32(4,true), z=dv.getUint32(8,true)&0xffff, datos=new Uint8Array(buf,16);
				st.wfStart=xbin/(st.fft*Math.pow(2,st.zoomMax))*st.bw; st.wfSpan=st.bw/Math.pow(2,z);
				if(xbin!==st.ultXbin || z!==st.ultZoom){ st.ultXbin=xbin; st.ultZoom=z; limpiarCascada(); st.esp=[]; st.piso=null; if(st.busq) st.busq={n:0}; dibujarPlan(); dibujarEscala(); dibujarSpots(); marcas(); }
				var lin=new Float32Array(datos.length); for(var i=0;i<datos.length;i++) lin[i]=datos[i]-255;
				st.esp.push(lin); if(st.esp.length>10) st.esp.shift();
				if(st.busq){ if(!st.busq.s) st.busq.s=new Float32Array(lin.length); for(i=0;i<lin.length;i++) st.busq.s[i]+=lin[i]; st.busq.n++; }
				lineaCascada(datos);
				if(!st.dibujoPend){ st.dibujoPend=true; requestAnimationFrame(function(){ st.dibujoPend=false; dibujarEspectro(); }); }
			}); }, 300);
			clearInterval(st.ka);
			st.ka=setInterval(function(){ send(st.snd,'SET keepalive'); send(st.wf,'SET keepalive'); }, 3000);
			clearInterval(st.miniT); st.miniT=setInterval(mini, 700);
		}
		/* La portadora más alta del rango (en kHz) sobre la mediana de la cascada, promediando todas
		   las líneas desde que se centró la banda (unos 3 s: con menos, ganaba la emisora que estuviera
		   en un pico de desvanecimiento); null si nada sobresale 15 dB. La onda corta va cada 5 kHz. */
		function portadoraMasFuerte(r){
			var bq=st.busq; st.busq=null; if(!bq || bq.n<5) return null;
			var n=bq.s.length, prom=bq.s, i, mejor=null;
			var med=Array.from(prom).sort(function(a,b){ return a-b; })[n>>1];
			for(i=2;i<n-2;i++){
				var f=(st.wfStart+i/n*st.wfSpan)/1000, d=(prom[i]-med)/bq.n;
				if(f>=r[0] && f<=r[1] && d>15 && (!mejor || d>mejor.d)) mejor={f:Math.round(f/5)*5, d:d};
			}
			return mejor;
		}
		function buscarEmisora(i){
			var b=OC_ARRANQUE[i]; if(!b) return msg('La onda corta está tranquila en este momento. Prueba 40 m o 20 m en BANDA, o toca cualquier señal de la cascada.');
			var centro=(b[0]+b[1])/2;
			st.busq={n:0}; sintonizar(centro, 'am'); msg('Buscando la emisora más fuerte de la banda de '+b[2]+'…');
			setTimeout(function(){
				// Si la persona ya movió el dial, se respeta
				if(!st.on || Math.abs(st.freq-centro)>0.01){ st.busq=null; return; }
				var e=portadoraMasFuerte(b); if(!e) return buscarEmisora(i+1);
				sintonizar(e.f, 'am');
				msg('Suena la emisora de onda corta más fuerte de este momento: '+e.f+' kHz AM, banda de '+b[2]+'. Para oír radioaficionados elige 40 m o 20 m en BANDA.');
			}, 3500);
		}
		/* Mini barra flotante: en Herramientas el panel se guarda al cambiar de herramienta y el audio
		   sigue; sin esto quedaba sonando sin forma visible de pararlo. */
		function mini(){
			var app=$('rx-app'), r=app.getBoundingClientRect();
			var aLaVista=app.offsetParent!==null && r.bottom>0 && r.top<innerHeight;
			var el=$('rx-mini'), ver=st.on && !aLaVista;
			el.classList.toggle('hidden', !ver); el.classList.toggle('flex', ver);
			if(ver) $('rx-mini-f').textContent=st.freq.toFixed(2)+' kHz '+MODOS[st.modo].n+($('rx-s').textContent!=='—' ? ' · '+$('rx-s').textContent.split(' ·')[0] : '');
		}
		function apagar(silencioso){
			if(st.grab) pararGrabacion();
			st.on=false; clearInterval(st.ka); clearInterval(st.miniT); mini();
			[st.snd, st.wf].forEach(function(ws){ if(ws){ ws.onclose=null; try{ ws.close(); }catch(e){} } });
			st.snd=st.wf=null; st.ultXbin=-1; compararCon('');
			$('rx-power').textContent='▶ Escuchar'; $('rx-power').setAttribute('aria-pressed','false'); $('rx-ind-rx').classList.remove('is-on'); $('rx-s').textContent='—'; $('rx-smeter').style.width='0'; st.pico=0; $('rx-pico').style.left='0';
			if(!silencioso) msg('Desconectado. El canal quedó libre para otra persona.');
			document.dispatchEvent(new CustomEvent('rau:rx-encendido', {detail:{on:false}}));
			limpiarCascada(); st.esp=[]; dibujarEspectro(); vistaLocal(true);
		}
		/* «Avísame cuando haya canal»: consulta el estado cada 30 s y avisa con un tono, una
		   notificación del navegador si la persona la permitió, y el título de la pestaña. */
		function vigilarCanal(){
			if(st.vigia) return;
			$('rx-avisar').textContent='⏳ Te aviso apenas haya canal…'; $('rx-avisar').disabled=true;
			if(window.Notification && Notification.permission==='default') Notification.requestPermission().catch(function(){});
			var titulo=document.title;
			function revisar(){
				fetch(AJAX_RX+'?action=rau_sdr_status', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
					if(!j.success || !(j.data.libres>0)) return;
					clearInterval(st.vigia); st.vigia=null;
					$('rx-avisar').classList.add('hidden'); $('rx-avisar').disabled=false; $('rx-avisar').textContent='🔔 Avísame cuando haya canal';
					msg('¡Hay canal libre! Pulsa «Escuchar».');
					try { var ctx=st.ctx||new (window.AudioContext||window.webkitAudioContext)(); st.ctx=ctx; var o=ctx.createOscillator(), g=ctx.createGain(); o.frequency.value=880; g.gain.value=0.15; o.connect(g); g.connect(ctx.destination); o.start(); o.stop(ctx.currentTime+0.35); } catch(e){}
					if(window.Notification && Notification.permission==='granted'){ try { new Notification('KiwiSDR HK4RAU', {body:'Hay un canal libre. Vuelve a la pestaña y pulsa Escuchar.'}); } catch(e){} }
					document.title='🔔 Canal libre · '+titulo; setTimeout(function(){ document.title=titulo; }, 60000);
				}).catch(function(e){ rauErr(e); });
			}
			st.vigia=setInterval(revisar, 30000); revisar();
		}

		/* ── Controles ────────────────────────────────────────────────────── */
		var chip='rx-banda rx-tecla';
		$('rx-bandas').innerHTML=BANDAS.map(function(b){ return '<button type="button" data-b="'+b.n+'" class="'+chip+'">'+b.n+'</button>'; }).join('')
			+'<span class="rx-separador" aria-hidden="true"></span>'
			+OTRAS.map(function(o){ return '<button type="button" data-o="'+o[0]+'" class="'+chip+'">'+o[0].replace('Onda corta ','OC ').replace('Hora WWV ','WWV ')+'</button>'; }).join('');
		$('rx-modos').innerHTML=Object.keys(MODOS).map(function(k){ return '<button type="button" data-m="'+k+'" class="rx-modo rx-tecla">'+MODOS[k].n+'</button>'; }).join('');
		$('rx-bandas').addEventListener('click', function(e){
			var x=e.target.closest('.rx-banda'); if(!x) return;
			if(x.dataset.b){ var b=BANDAS.filter(function(y){ return y.n===x.dataset.b; })[0], s=b.s.filter(function(y){ return y[0]==='Fonía'; })[0]||b.s[0]; sintonizar(s[1], s[2]); }
			else { var o=OTRAS.filter(function(y){ return y[0]===x.dataset.o; })[0]; sintonizar(o[1], o[2]); }
		});
		$('rx-segmentos').addEventListener('click', function(e){ var x=e.target.closest('.rx-seg'); if(x){ if(x.dataset.m==='cw') st.decod='cw'; sintonizar(+x.dataset.f, x.dataset.m); } });

		/* Perilla: girarla sintoniza un paso cada 12° (30 pasos por vuelta), como un VFO con
		   «clics». Funciona con ratón y dedo (ángulo alrededor del centro), con la rueda y con flechas. */
		var giro=null, perilla=$('rx-perilla');
		function anguloDe(e){ var r=perilla.getBoundingClientRect(); return Math.atan2(e.clientY-(r.top+r.height/2), e.clientX-(r.left+r.width/2))*180/Math.PI; }
		function girar(pasos){ st.perilla+=pasos*12; perilla.style.setProperty('--giro', st.perilla+'deg'); sintonizar(Math.round((redondear(st.freq)+pasos*st.paso)*100)/100, null, false); }
		perilla.addEventListener('pointerdown', function(e){ e.preventDefault(); giro={a:anguloDe(e), resto:0}; try { perilla.setPointerCapture(e.pointerId); } catch(err){} perilla.classList.add('is-girando'); });
		perilla.addEventListener('pointermove', function(e){
			if(!giro) return; var a=anguloDe(e), d=a-giro.a; if(d>180) d-=360; if(d<-180) d+=360; giro.a=a; giro.resto+=d;
			var n=Math.trunc(giro.resto/12); if(n){ giro.resto-=n*12; girar(n); }
		});
		['pointerup','pointercancel'].forEach(function(ev){ perilla.addEventListener(ev, function(){ giro=null; perilla.classList.remove('is-girando'); }); });
		perilla.addEventListener('wheel', function(e){ e.preventDefault(); girar(e.deltaY<0?1:-1); }, {passive:false});
		perilla.addEventListener('keydown', function(e){ var k={ArrowRight:1, ArrowUp:1, ArrowLeft:-1, ArrowDown:-1, PageUp:10, PageDown:-10}[e.key]; if(!k) return; e.preventDefault(); e.stopPropagation(); girar(k); });

		/* Dígitos: tocar la mitad de arriba sube, la de abajo baja; rueda encima; en pantalla táctil,
		   deslizar el dedo hacia arriba o abajo sobre el dígito. */
		var dig=null;
		function cambiarDigito(v, signo){ sintonizar(Math.round((st.freq+signo*v)*100)/100, null, false); }
		$('rx-freq').addEventListener('pointerdown', function(e){
			var d=e.target.closest('.rx-dig'); if(!d) return;
			e.preventDefault(); dig={el:d, v:+d.dataset.v, y:e.clientY, paso:0};
			try { $('rx-freq').setPointerCapture(e.pointerId); } catch(err){}
		});
		$('rx-freq').addEventListener('pointermove', function(e){
			if(!dig) return; var n=Math.trunc((dig.y-e.clientY)/18);
			if(n!==dig.paso){ cambiarDigito(dig.v, n-dig.paso); dig.paso=n; }
		});
		$('rx-freq').addEventListener('pointerup', function(e){
			if(!dig) return; var d=dig; dig=null;
			if(d.paso!==0) return;
			var r=d.el.getBoundingClientRect(); cambiarDigito(d.v, e.clientY<r.top+r.height/2 ? 1 : -1);
		});
		$('rx-freq').addEventListener('wheel', function(e){ var d=e.target.closest('.rx-dig'); if(!d) return; e.preventDefault(); cambiarDigito(+d.dataset.v, e.deltaY<0?1:-1); }, {passive:false});
		document.querySelectorAll('.rx-modo').forEach(function(b){ b.addEventListener('click', function(){ if(b.dataset.m==='cw') elegirDecodificador('cw'); else if(st.ft || st.dig) elegirDecodificador(''); sintonizar(st.freq, b.dataset.m, false); }); });
		document.querySelectorAll('.rx-paso').forEach(function(b){ b.addEventListener('click', function(){ sintonizar(redondear(st.freq)+st.paso*(+b.dataset.pasos), null, false); }); });
		// Acepta kHz (7074) o MHz (7.074 o 7,074): por debajo de 31 se entiende MHz
		$('rx-input').addEventListener('change', function(){ var f=parseFloat(String(this.value).replace(',', '.')); if(!(f>0)) return; if(f<31) f*=1000; sintonizar(f, modoPara(f,'')); });
		document.body.appendChild($('rx-mini')); // fuera del panel, que Herramientas oculta al cambiar de herramienta
		$('rx-mini-stop').addEventListener('click', function(){ apagar(); });
		$('rx-mini-ver').addEventListener('click', function(){
			// En la consola el receptor solo quedó fuera de la vista: basta con desplazarse
			if(typeof window.rauIrPestana==='function') window.rauIrPestana('escuchar');
			if($('rx-app').offsetParent===null && typeof window.rauAbrirHerramienta==='function') window.rauAbrirHerramienta('receptor');
			$('rx-app').scrollIntoView({block:'start', behavior:'smooth'}); mini();
		});
		$('rx-zoom-mas').addEventListener('click', function(){ zoomA(st.zoom+1); guardar(); });
		$('rx-zoom-menos').addEventListener('click', function(){ zoomA(st.zoom-1); guardar(); });
		$('rx-filtros').addEventListener('click', function(e){ var x=e.target.closest('.rx-filtro-op'); if(!x) return; st.filtro=+x.dataset.v; enviarModo(); marcas(); guardar(); pintarFreq(); });
		$('rx-pasos').addEventListener('click', function(e){ var x=e.target.closest('.rx-paso-op'); if(!x) return; st.paso=+x.dataset.v; guardar(); pintarFreq(); });
		$('rx-paleta').value=st.paleta; $('rx-paleta').addEventListener('change', function(){ st.paleta=this.value; construirLut(); limpiarCascada(); dibujarEspectro(); guardar(); });
		$('rx-contraste').value=st.contraste; $('rx-contraste-v').textContent=st.contraste+' dB';
		$('rx-contraste').addEventListener('input', function(){ st.contraste=+this.value; $('rx-contraste-v').textContent=st.contraste+' dB'; guardar(); });
		$('rx-sq').addEventListener('input', function(){
			st.sq=+this.value; $('rx-sq-v').textContent=st.sq?'S'+st.sq:'off';
			$('rx-sq-marca').classList.toggle('hidden', !st.sq); $('rx-sq-marca').style.left=(6*st.sq/114*100)+'%';
			if(!st.sq){ st.sqAbierto=true; volumen(); }
		});
		$('rx-vol').addEventListener('input', function(){ $('rx-vol-v').textContent=this.value; volumen(); });
		$('rx-agc').addEventListener('click', function(){ st.agc=!st.agc; agc(); pintarFreq(); });
		$('rx-mute').addEventListener('click', function(){ st.mute=!st.mute; this.textContent=st.mute?'🔇':'🔊'; this.setAttribute('aria-pressed', st.mute); volumen(); });
		$('rx-rec').addEventListener('click', function(){
			if(st.grab){ pararGrabacion(); msg('Grabación guardada en tus descargas.'); return; }
			if(!st.on){ msg('Primero pulsa «Escuchar»; luego graba.'); return; }
			st.grab={partes:[], n:0, freq:st.freq, modo:st.modo, inicio:Date.now()};
			this.textContent='■ Parar grabación'; this.setAttribute('aria-pressed','true'); this.classList.add('text-rose-400'); $('rx-ind-rec').classList.add('is-on');
			msg('Grabando '+st.freq.toFixed(2)+' kHz. Pulsa «Parar grabación» para descargar el WAV.');
		});
		$('rx-grab-subir').addEventListener('click', function(){
			var g=st.ultGrab, b=this; if(!g) return;
			var fd=new FormData(); fd.append('audio', g.blob, 'grabacion.wav'); fd.append('freq', g.freq); fd.append('modo', g.modo); fd.append('inicio', g.inicio);
			b.disabled=true; msg('Subiendo la grabación…');
			fetch(JS8_API.grab, {method:'POST', credentials:'same-origin', headers:{'X-WP-Nonce':JS8_API.nonce}, body:fd}).then(function(r){ return r.json(); }).then(function(j){
				b.disabled=false; if(!j.enlace){ msg('No se pudo compartir: '+(j.error||'error')); return; }
				b.classList.add('hidden'); msg('Listo. Enlace de la grabación: '+j.enlace);
				if(navigator.share) navigator.share({title:j.titulo, url:j.enlace}).catch(function(){}); else if(navigator.clipboard) navigator.clipboard.writeText(j.enlace).catch(function(){});
			}).catch(function(){ b.disabled=false; msg('No se pudo subir la grabación.'); });
		});
		if($('rx-grab-ir')) $('rx-grab-ir').addEventListener('click', function(){ var f=+this.dataset.f, m=this.dataset.m; if(!st.on){ st.freq=f; if(MODOS[m]) st.modo=m; $('rx-power').click(); } else sintonizar(f, MODOS[m]?m:st.modo); });
		$('rx-share').addEventListener('click', function(){
			var url=PAGINA+'?f='+st.freq.toFixed(2)+'&m='+st.modo, texto='Escucha '+st.freq.toFixed(2)+' kHz '+MODOS[st.modo].n+' en el KiwiSDR de RAU';
			if(navigator.share){ navigator.share({title:'KiwiSDR HK4RAU', text:texto, url:url}).catch(function(){}); return; }
			(navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(function(){ msg('Enlace copiado: '+url); }, function(){ prompt('Copia este enlace:', url); });
		});
		$('rx-power').addEventListener('click', function(){ st.on ? apagar() : encender(); });
		$('rx-avisar').addEventListener('click', vigilarCanal);
		$('rx-cw-borrar').addEventListener('click', function(){ cwLetras=[]; $('rx-cw-texto').textContent=''; if(st.cw) st.cw.reiniciar(); if(st.skim) st.skim.reiniciar(); });
		$('rx-cw-skim').addEventListener('change', function(){
			st.skimOn=this.checked; st.skim=null;
			$('rx-cw-canales').classList.toggle('hidden', !st.skimOn); $('rx-cw-texto').classList.toggle('hidden', st.skimOn);
			// El skimmer necesita ver varias señales: filtro ancho
			if(st.skimOn && st.filtro!==2){ st.filtro=2; enviarModo(); pintarFreq(); }
			if(st.skimOn) pintarSkim(); else $('rx-cw-info').textContent='Esperando una señal…';
		});
		$('rx-cw-canales').addEventListener('click', function(e){ var b=e.target.closest('.rx-cw-canal'); if(b) sintonizar(+b.dataset.f, 'cw', false); });
		$('rx-decs').addEventListener('click', function(e){ var b=e.target.closest('.rx-dec'); if(b) elegirDecodificador(b.dataset.d); });
		$('rx-js8-sub').addEventListener('change', function(){ if(st.ft && st.ft.modo==='JS8') elegirDecodificador('JS8'); });
		if($('rx-receptor')) $('rx-receptor').addEventListener('change', function(){ elegirReceptor(this.value); });
		if($('rx-comparar')) $('rx-comparar').addEventListener('change', function(){ compararCon(this.value); });
		$('rx-dig-borrar').addEventListener('click', function(){ if(st.dig) st.dig.reiniciar(); $('rx-dig-texto').textContent=''; });
		$('rx-sstv-guardar').addEventListener('click', function(){ var x=sstvImgs[0]; if(!x) return; var a=document.createElement('a'); a.href=x.url; a.download=x.nombre; document.body.appendChild(a); a.click(); a.remove(); });
		$('rx-ft-borrar').addEventListener('click', function(){ ftFilas=[]; pintarFt(); });
		$('rx-ft-cq').addEventListener('change', pintarFt);
		$('rx-ft-mapa-b').addEventListener('click', function(){ var on=$('rx-ft-mapa').classList.toggle('hidden')===false; this.setAttribute('aria-pressed', on); if(on) pintarMapa(); });
		// En la consola los ajustes avanzados van plegados para que la lista de estaciones quede cerca
		$('rx-ajustes').addEventListener('click', function(){ var ab=$('rx-avanzado').classList.toggle('abierto'); this.setAttribute('aria-expanded', ab); });

		/* Pantalla: tocar sintoniza, arrastrar recorre la banda, la rueda mueve un paso */
		var pant=$('rx-pantalla'), arr=null;
		function freqEn(clientX){ var r=pant.getBoundingClientRect(); return (st.wfStart+(clientX-r.left)/r.width*st.wfSpan)/1000; }
		pant.addEventListener('pointerdown', function(e){ if(!st.wfSpan) return; arr={x:e.clientX, f:st.freq, movido:false}; pant.setPointerCapture(e.pointerId); });
		pant.addEventListener('pointermove', function(e){
			if(!arr) return; var dx=e.clientX-arr.x; if(Math.abs(dx)>6) arr.movido=true;
			if(arr.movido){ var r=pant.getBoundingClientRect(); st.freq=Math.max(10,arr.f-dx/r.width*st.wfSpan/1000); pintarFreq(); marcas(); }
		});
		pant.addEventListener('pointerup', function(e){
			if(!arr) return; var a=arr; arr=null;
			if(a.movido){ sintonizar(redondear(st.freq)); return; }
			var cerca=null, r=pant.getBoundingClientRect(), xr=e.clientX-r.left;
			// Con setPointerCapture el destino del evento es la pantalla entera: se mira la altura
			var rs=$('rx-spots').getBoundingClientRect();
			if(e.clientY>=rs.top-4 && e.clientY<=rs.bottom+4) st.golpes.forEach(function(g){ var dd=Math.abs(g.x-xr); if(dd<14 && (!cerca || dd<cerca.d)) cerca={d:dd, s:g.s}; });
			if(cerca){ sintonizar(cerca.s.khz, modoPara(cerca.s.khz, cerca.s.modo)); msg('Sintonizado '+cerca.s.dx+' en '+cerca.s.khz.toFixed(2)+' kHz'+(st.on?'.':'. Pulsa «Escuchar» para oírlo.')); return; }
			var f=freqEn(e.clientX), pico=picoCerca(e.clientX-r.left, r.width);
			if(pico){
				// La señal queda dentro del filtro: en USB la voz va ~0,3–2,7 kHz encima de la portadora
				var ajuste={usb:-1.2, lsb:1.2, cw:-0.5}[st.modo]||0;
				sintonizar(Math.round((pico+ajuste)*100)/100, null, false); msg('Sintonizado en la señal más cercana ('+pico.toFixed(2)+' kHz).');
				return;
			}
			sintonizar(st.modo==='am'||st.modo==='nbfm' ? Math.round(f) : redondear(f), null, false);
		});
		pant.addEventListener('wheel', function(e){ if(!st.on) return; e.preventDefault(); sintonizar(redondear(st.freq)+(e.deltaY<0?1:-1)*st.paso, null, false); }, {passive:false});
		$('rx-app').addEventListener('keydown', function(e){
			if(/INPUT|SELECT|TEXTAREA/.test(e.target.tagName)) return;
			var k={ArrowRight:1, ArrowLeft:-1, ArrowUp:10, ArrowDown:-10}[e.key]; if(!k) return;
			e.preventDefault(); sintonizar(redondear(st.freq)+k*st.paso, null, Math.abs(k)>1);
		});

		/* Favoritos */
		function favs(){ try { return JSON.parse(localStorage.getItem('rau_rx_favs')||'[]'); } catch(e){ return []; } }
		function pintarFavs(){
			var l=favs(), el=$('rx-favs');
			if(!l.length){ el.innerHTML='<p class="rx-mem-vacio">Guarda aquí las frecuencias que más escuchas (quedan en este navegador).</p>'; return; }
			el.innerHTML=l.map(function(x,i){ var n=x.n?x.n.replace(/[<>&"]/g,''):'';
				return '<span class="rx-mem"><button type="button" data-i="'+i+'" data-f="'+x.f+'" data-m="'+x.m+'" class="rx-fav-ir rx-tecla" title="'+n+'"><b>M'+(i+1)+'</b> '+(n?n+' <span class="rx-tecla-sub">':'<span class="rx-tecla-sub">')+x.f.toFixed(2)+' '+MODOS[x.m].n+'</span></button><button type="button" data-i="'+i+'" class="rx-fav-x" aria-label="Borrar la memoria M'+(i+1)+'">×</button></span>'; }).join('');
			el.querySelectorAll('.rx-fav-ir').forEach(function(b){ b.addEventListener('click', function(){ var x=favs()[b.dataset.i]; sintonizar(x.f, x.m); }); });
			el.querySelectorAll('.rx-fav-x').forEach(function(b){ b.addEventListener('click', function(){ var l=favs(); l.splice(b.dataset.i,1); try{ localStorage.setItem('rau_rx_favs', JSON.stringify(l)); }catch(e){} pintarFavs(); }); });
			pintarFreq();
		}
		$('rx-fav-add').addEventListener('click', function(){
			var n=prompt('Nombre para '+st.freq.toFixed(2)+' kHz (opcional):', '');
			if(n===null) return;
			var l=favs().filter(function(x){ return Math.abs(x.f-st.freq)>0.005 || x.m!==st.modo; }); l.unshift({f:st.freq, m:st.modo, n:n.slice(0,30)}); l=l.slice(0,20);
			try{ localStorage.setItem('rau_rx_favs', JSON.stringify(l)); }catch(e){} pintarFavs();
		});

		/* También al mostrarse el panel en Herramientas (antes medía 0 px de ancho) */
		if(window.ResizeObserver) new ResizeObserver(function(){ redibujarFijos(); }).observe($('rx-pantalla')); else window.addEventListener('resize', function(){ redibujarFijos(); });
		window.addEventListener('pagehide', function(){ if(st.on) apagar(true); });
		construirLut(); limpiarCascada(); pintarFreq(); pintarFavs(); vistaLocal(true); redibujarFijos();
		if(st.primera) msg('Pulsa «Escuchar»: el receptor busca la emisora de onda corta más fuerte de este momento para que la oigas de inmediato.');

		/* API para otras herramientas: abrir el receptor en una frecuencia */
		window.RauRx={ sintonizar:function(f,m){ sintonizar(f, m||modoPara(f,'')); }, encender:encender, modoPara:modoPara, encendido:function(){ return st.on; },
			estado:function(){ return {khz:st.freq, modo:st.modo, on:st.on}; },
			ponerSpots:function(l){ st.spots=(l||[]).filter(function(s){ return s.khz>0 && s.khz<30000; }); dibujarSpots(); },
			decodificados:function(){ return (st.decSpots||[]).slice(); } };
		window.rauEscuchar=function(fk, pista){
			fk=+fk; if(!(fk>0)) return;
			var m=modoPara(fk, pista);
			// En la consola el receptor ya está a la vista: se sintoniza ahí mismo, sin cambiar de página
			if(typeof window.rauIrPestana==='function') window.rauIrPestana('escuchar');
			if($('rx-app').offsetParent===null && typeof window.rauAbrirHerramienta==='function') window.rauAbrirHerramienta('receptor');
			sintonizar(fk, m); if(!st.on) encender();
			setTimeout(function(){ $('rx-app').scrollIntoView({block:'nearest', behavior:'smooth'}); }, 50);
		};
	})();
	</script>
	<?php endif;
}
