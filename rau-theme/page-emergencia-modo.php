<?php
/**
 * Template Name: Modo Emergencia
 * Interfaz minimalista para una emergencia real — máxima legibilidad,
 * mínimo consumo, funciona con conectividad limitada (no depende de
 * datos en vivo, todo lo esencial está embebido directo en la página).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$rau_em_rep = rau_get_repetidor_principal();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<?php // Sin maximum-scale: bloquear el zoom impide ampliar el texto a quien tiene poca visión,
      // y esta es precisamente la página donde alguien puede estar leyendo con prisa, mala luz
      // o sin las gafas puestas. ?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Modo Emergencia — RAU</title>
<style>
	* { box-sizing: border-box; margin: 0; padding: 0; }
	body { background:#000; color:#fff; font-family: -apple-system, sans-serif; padding: 1.25rem; min-height: 100vh; }
	.rau-em-utc { text-align:center; font-family: monospace; font-size: 3rem; font-weight: 800; letter-spacing: 0.05em; margin-bottom: .25rem; }
	.rau-em-label { text-align:center; font-size: .75rem; text-transform:uppercase; letter-spacing:.2em; color:#aaa; margin-bottom: 1.5rem; }
	.rau-em-grid { text-align:center; font-family: monospace; font-size: 1.75rem; font-weight: 700; color:#fbbf24; margin-bottom: 2rem; }
	.rau-em-box { border: 3px solid #fff; border-radius: 12px; padding: 1rem; margin-bottom: 1rem; }
	.rau-em-box.rojo { border-color:#ff3b30; }
	.rau-em-box p.t { font-size: .7rem; text-transform:uppercase; letter-spacing:.15em; color:#aaa; margin-bottom:.35rem; }
	.rau-em-box p.v { font-family:monospace; font-size: 1.6rem; font-weight: 800; }
	.rau-em-check { display:flex; align-items:flex-start; gap:.75rem; padding: .85rem 0; border-bottom: 1px solid #333; font-size: 1.05rem; }
	.rau-em-check input { width: 1.5rem; height: 1.5rem; flex-shrink:0; margin-top: .15rem; }
	.rau-em-btn { display:block; width:100%; text-align:center; padding: 1rem; border-radius: 10px; font-weight: 800; font-size: 1.1rem; text-decoration:none; margin-top: .75rem; }
	.rau-em-btn.wa { background:#25D366; color:#000; }
	.rau-em-btn.volver { background:#222; color:#fff; border:1px solid #555; }
</style>
</head>
<body>

	<p class="rau-em-utc" id="rau-em-utc">--:--:--</p>
	<p class="rau-em-label">Hora UTC</p>

	<p class="rau-em-grid" id="rau-em-grid">📍 Obteniendo tu ubicación…</p>

	<div class="rau-em-box rojo">
		<p class="t">Frecuencia primaria</p>
		<p class="v"><?php echo esc_html( $rau_em_rep['frequency'] ); ?> MHz</p>
		<p class="t" style="margin-top:.5rem">Tono</p>
		<p class="v" style="font-size:1.1rem"><?php echo esc_html( $rau_em_rep['tone'] ); ?> Hz</p>
	</div>

	<div class="rau-em-box">
		<p class="t">Frecuencia secundaria (simplex)</p>
		<p class="v">146.520 MHz</p>
	</div>

	<div class="rau-em-box">
		<p class="t">Repetidor Medellín — HK4RAU</p>
		<p class="v" style="font-size:1.2rem"><?php echo esc_html( $rau_em_rep['frequency'] . ' · ' . ( (float) $rau_em_rep['offset'] * 1000 ) . ' kHz' ); ?></p>
	</div>

	<div style="margin: 1.5rem 0 .5rem;">
		<p class="t" style="font-size:.85rem; text-transform:uppercase; letter-spacing:.15em; color:#aaa; margin-bottom:.5rem;">Checklist</p>
		<label class="rau-em-check"><input type="checkbox"> Confirmar mi ubicación (grid o dirección)</label>
		<label class="rau-em-check"><input type="checkbox"> Reportarme en la frecuencia primaria</label>
		<label class="rau-em-check"><input type="checkbox"> Indicar tipo de emergencia y personas afectadas</label>
		<label class="rau-em-check"><input type="checkbox"> Confirmar si necesito relevo o apoyo adicional</label>
		<label class="rau-em-check"><input type="checkbox"> Mantener escucha en la frecuencia hasta nueva instrucción</label>
	</div>

	<a href="<?php echo esc_url( rau_whatsapp_url() ); ?>" class="rau-em-btn wa">📞 Contactar a RAU (WhatsApp)</a>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="rau-em-btn volver">← Salir del Modo Emergencia</a>

	<script>
		function pad(n){ return String(n).padStart(2,'0'); }
		function tick(){
			var d=new Date();
			document.getElementById('rau-em-utc').textContent = pad(d.getUTCHours())+':'+pad(d.getUTCMinutes())+':'+pad(d.getUTCSeconds());
		}
		tick(); setInterval(tick, 1000);

		// Conversión lat/lng -> Maidenhead, autocontenida (esta página no depende de otros scripts
		// del sitio a propósito: tiene que funcionar aunque todo lo demás falle).
		// OJO: los CAMPOS usan 18 letras (A–R) pero los SUBCUADROS usan 24 (a–x). La versión
		// anterior reutilizaba el alfabeto de 18 para ambos, así que en cuanto el índice del
		// subcuadro pasaba de 17 devolvía undefined: Bogotá salía como "FJ24undefinedr". Afectaba
		// al último cuarto de cada cuadro de 2°, o sea a una de cada cuatro posiciones del planeta.
		// Se usa fromCharCode, igual que la función principal del sitio, que sí estaba bien.
		function latLngAMaidenhead(lat, lng){
			lng+=180; lat+=90;
			var A='ABCDEFGHIJKLMNOPQR';
			var campo  = A[Math.floor(lng/20)] + A[Math.floor(lat/10)];
			var cuadro = Math.floor((lng%20)/2) + '' + Math.floor(lat%10);
			var sub    = String.fromCharCode(97+Math.floor((lng%2)*12))
			           + String.fromCharCode(97+Math.floor((lat%1)*24));
			return campo+cuadro+sub;
		}

		var gridEl=document.getElementById('rau-em-grid');
		if(navigator.geolocation){
			navigator.geolocation.getCurrentPosition(function(pos){
				var lat=pos.coords.latitude, lng=pos.coords.longitude;
				var grid=latLngAMaidenhead(lat, lng);
				// Además del locator se muestran los grados decimales. El grid square solo lo
				// entiende otro radioaficionado: si hay que dar la posición a la Defensa Civil,
				// a los bomberos o al 123, lo que piden son coordenadas. Tenerlas ya en pantalla
				// evita convertirlas de cabeza en mitad de una emergencia.
				gridEl.innerHTML='📍 Locator: '+grid
					+'<br><span style="font-size:1.1rem;color:#fff">'
					+ lat.toFixed(5)+', '+lng.toFixed(5)+'</span>'
					+'<br><span style="font-size:.8rem;color:#aaa">precisión ±'
					+ Math.round(pos.coords.accuracy) +' m</span>';
			}, function(){
				gridEl.textContent='⚠️ Ubicación no disponible — confírmala manualmente por radio';
			}, { timeout:8000, enableHighAccuracy:true });
		} else {
			gridEl.textContent='⚠️ Este dispositivo no soporta geolocalización — confírmala manualmente por radio';
		}
	</script>
</body>
</html>
