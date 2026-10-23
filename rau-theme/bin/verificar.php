<?php
/**
 * Verificación del tema RAU.
 *
 * Uso:   php bin/verificar.php
 * Salida: 0 si todo pasa, 1 si hay algún fallo.
 *
 * Cada comprobación de aquí corresponde a un fallo REAL que estuvo en producción sin que nadie
 * lo notara. No son buenas prácticas genéricas: son las trampas concretas en las que ya cayó
 * este tema, escritas para que no vuelvan a entrar sin avisar.
 *
 * Los cuatro patrones que produjeron casi todos los errores encontrados:
 *   1. Datos o fórmulas duplicados que con el tiempo divergen (grid squares, Haversine, Morse,
 *      prefijos, concursos, fase lunar, potencias del decreto).
 *   2. Ausencia de dato tratada como dato: parseInt(x)||0 convertía "sin lectura" en cero, y el
 *      panel anunciaba "condiciones excelentes" cuando la fuente no había dicho nada.
 *   3. Símbolos compartidos declarados con const a media altura del archivo, después de que
 *      algún consumidor los use. Aborta el script entero y deja media página sin inicializar.
 *   4. Números escritos a mano que se desfasan del dato real (el catálogo se quedó en 53).
 */

$raiz = dirname( __DIR__ );
$fallos = 0;
$avisos = 0;

function titulo( $t ) { echo "\n\033[1m" . $t . "\033[0m\n" . str_repeat( '-', 72 ) . "\n"; }
function ok( $m )    { echo "  \033[32mOK\033[0m    " . $m . "\n"; }
function mal( $m )   { global $fallos; $fallos++; echo "  \033[31mFALLA\033[0m " . $m . "\n"; }
function aviso( $m ) { global $avisos; $avisos++; echo "  \033[33mAVISO\033[0m " . $m . "\n"; }

$php_files = array_merge( glob( "$raiz/*.php" ), glob( "$raiz/inc/*.php" ) );
$js   = file_get_contents( "$raiz/inc/herramientas-scripts.php" );
// Versión sin comentarios: si no, los ejemplos escritos DENTRO de un comentario se detectan
// como código real. Le pasó a este mismo guion en su primera ejecución.
$js_sin_comentarios = preg_replace( array( '#/\*.*?\*/#s', '#(?<!:)//[^\n]*#' ), '', $js );
$html = file_get_contents( "$raiz/page-herramientas.php" );
$fn   = file_get_contents( "$raiz/functions.php" );

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '1. Sintaxis PHP' );
$malos = array();
foreach ( $php_files as $f ) {
	exec( 'php -l ' . escapeshellarg( $f ) . ' 2>&1', $out, $rc );
	if ( 0 !== $rc ) $malos[] = basename( $f );
	$out = array();
}
$malos ? mal( 'con errores de sintaxis: ' . implode( ', ', $malos ) )
       : ok( count( $php_files ) . ' archivos PHP validan' );

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '2. Versión coherente entre functions.php y style.css' );
preg_match( "/RAU_THEME_VERSION', '([^']+)'/", $fn, $v1 );
preg_match( '/^Version:\s*(.+)$/m', file_get_contents( "$raiz/style.css" ), $v2 );
$a = $v1[1] ?? '?'; $b = trim( $v2[1] ?? '?' );
$a === $b ? ok( "ambas en $a" ) : mal( "functions.php dice $a y style.css dice $b" );

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '3. Herramientas registradas ↔ paneles del HTML' );
preg_match( '/function rau_get_herramientas_tabs\(\) \{(.*?)\n\}/s', $fn, $m );
preg_match_all( "/'id'=>'([a-z0-9]+)'/", $m[1] ?? '', $ids );
$tabs = $ids[1] ?? array();
preg_match_all( '/id="panel-([a-z0-9]+)"/', $html, $p );
$paneles = array_unique( $p[1] );
$sin_panel = array_diff( $tabs, $paneles );
$sin_tab   = array_diff( $paneles, $tabs );
if ( $sin_panel ) mal( 'herramientas sin panel: ' . implode( ', ', $sin_panel ) );
if ( $sin_tab )   mal( 'paneles sin herramienta registrada: ' . implode( ', ', $sin_tab ) );
if ( ! $sin_panel && ! $sin_tab ) ok( count( $tabs ) . ' herramientas, todas con su panel' );

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '3b. Integridad de los grupos de herramientas' );
// Una herramienta puede 'incluir' a otras para no ocupar entradas separadas en el catálogo. Si un
// id incluido no existe, o existe pero no está marcado 'oculta', la herramienta desaparece del
// catálogo sin aparecer en ningún grupo: inalcanzable salvo por su URL directa.
// Se recorre línea a línea: cada herramienta ocupa una. Un regex que pare en el primer ']' se
// corta DENTRO del array 'incluye' y no ve ninguna inclusión — le pasó a este mismo guion.
$entradas = array();
foreach ( explode( "\n", $m[1] ?? '' ) as $linea ) {
	if ( preg_match( "/'id'=>'([a-z0-9]+)'/", $linea, $id ) ) $entradas[] = array( $linea, $id[1], $linea );
}
$todas = array(); $ocultas = array(); $incluidas = array();
foreach ( $entradas as $e ) {
	$todas[ $e[1] ] = true;
	if ( strpos( $e[2], "'oculta'=>true" ) !== false ) $ocultas[ $e[1] ] = true;
	if ( preg_match( "/'incluye'=>\[([^\]]*)\]/", $e[2], $inc ) ) {
		foreach ( preg_split( "/\s*,\s*/", trim( $inc[1] ) ) as $x ) {
			$x = trim( $x, "' " );
			if ( '' !== $x ) $incluidas[ $x ] = $e[1];
		}
	}
}
$errores = array();
foreach ( $incluidas as $sub => $lider ) {
	if ( ! isset( $todas[ $sub ] ) )   $errores[] = "$lider incluye '$sub', que no existe";
	elseif ( ! isset( $ocultas[ $sub ] ) ) $errores[] = "'$sub' está en un grupo pero no marcada 'oculta' (saldría dos veces)";
}
foreach ( array_keys( $ocultas ) as $o ) {
	if ( ! isset( $incluidas[ $o ] ) ) $errores[] = "'$o' está oculta pero ningún grupo la incluye: quedaría inalcanzable";
}
$errores ? mal( implode( '; ', $errores ) )
         : ok( count( $incluidas ) . ' herramientas agrupadas, todas alcanzables desde su grupo' );

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '4. Contadores calculados, no escritos a mano' );
// El botón del catálogo se quedó en "53" al añadir una herramienta.
if ( preg_match( '/Catálogo \((\d+)\)/u', $html, $c ) ) {
	mal( "el contador del catálogo está fijo en {$c[1]}; debe salir de count( rau_get_herramientas_tabs() )" );
} else {
	ok( 'el contador del catálogo se calcula' );
}

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '5. Símbolos compartidos declarados antes de usarse' );
// Un const usado antes de su línea lanza ReferenceError y aborta TODO el script: los módulos
// definidos más abajo nunca se inicializan y sus paneles quedan en "Cargando…" para siempre.
foreach ( array( 'MORSE', 'PFX_GEO', 'PFX' ) as $sym ) {
	if ( ! preg_match( '/^const ' . $sym . '\s*=/m', $js, $d, PREG_OFFSET_CAPTURE ) ) {
		aviso( "no encuentro la declaración de $sym" );
		continue;
	}
	$pos_decl = $d[0][1];
	$primer_uso = null;
	foreach ( array( '/\b' . $sym . '\s*\[/', '/=\s*' . $sym . '\s*[;,)]/' ) as $pat ) {
		if ( preg_match( $pat, $js, $u, PREG_OFFSET_CAPTURE ) ) {
			if ( null === $primer_uso || $u[0][1] < $primer_uso ) $primer_uso = $u[0][1];
		}
	}
	if ( null !== $primer_uso && $primer_uso < $pos_decl ) {
		$ln = substr_count( substr( $js, 0, $primer_uso ), "\n" ) + 1;
		mal( "$sym se usa en la línea $ln, antes de declararse" );
	} else {
		ok( "$sym se declara antes de cualquier uso" );
	}
}

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '6. Acceso al DOM sin protección a nivel superior' );
// document.getElementById('x').addEventListener(...) suelto: si el id no existe, TypeError y
// muere el resto del script. Debe usarse rauOn(), que avisa por consola y sigue.
$n = preg_match_all( '/^document\.getElementById\(/m', $js_sin_comentarios );
$n ? mal( "$n llamada(s) sueltas; usa rauOn( id, evento, fn )" )
   : ok( 'ninguna; todas pasan por rauOn()' );

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '7. Ids que el JS busca y no existen en el HTML' );
preg_match_all( "/getElementById\('([^']+)'\)/", $js_sin_comentarios, $g );
preg_match_all( '/id="([^"<>]+)"/', $html, $h );
$en_html = array_flip( $h[1] );
$huerfanos = array();
foreach ( array_unique( $g[1] ) as $id ) {
	if ( isset( $en_html[ $id ] ) ) continue;
	// Encadenar propiedades sobre un id inexistente es lo peligroso; guardarlo en variable no.
	if ( preg_match( "/getElementById\('" . preg_quote( $id, '/' ) . "'\)\s*\./", $js_sin_comentarios ) ) {
		$huerfanos[] = $id;
	}
}
$huerfanos ? mal( 'acceso encadenado a ids inexistentes: ' . implode( ', ', $huerfanos ) )
           : ok( 'ningún acceso encadenado a un id que no exista' );

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '8. Integridad de la tabla de prefijos' );
if ( preg_match( '/const PFX_GEO=\{(.*?)\n\};/s', $js, $t ) ) {
	preg_match_all( "/'([A-Z0-9]+)':'([^']*)'/", $t[1], $e, PREG_SET_ORDER );
	$claves = array(); $mal_formato = array(); $mal_coord = array(); $dup = array();
	foreach ( $e as $x ) {
		if ( isset( $claves[ $x[1] ] ) ) $dup[] = $x[1];
		$claves[ $x[1] ] = true;
		$campos = explode( '|', $x[2] );
		if ( 6 !== count( $campos ) ) { $mal_formato[] = $x[1]; continue; }
		$lat = (float) $campos[4]; $lng = (float) $campos[5];
		if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) $mal_coord[] = $x[1];
	}
	if ( $dup )         mal( 'claves duplicadas: ' . implode( ', ', array_unique( $dup ) ) );
	if ( $mal_formato ) mal( 'sin los 6 campos: ' . implode( ', ', $mal_formato ) );
	if ( $mal_coord )   mal( 'coordenadas fuera de rango: ' . implode( ', ', $mal_coord ) );
	if ( ! $dup && ! $mal_formato && ! $mal_coord ) ok( count( $e ) . ' prefijos, sin duplicados y con coordenadas válidas' );
} else {
	aviso( 'no encuentro PFX_GEO' );
}

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '9. Tabla Morse contra ITU-R M.1677-1' );
$ITU = array(
	'A'=>'.-','B'=>'-...','C'=>'-.-.','D'=>'-..','E'=>'.','F'=>'..-.','G'=>'--.','H'=>'....',
	'I'=>'..','J'=>'.---','K'=>'-.-','L'=>'.-..','M'=>'--','N'=>'-.','O'=>'---','P'=>'.--.',
	'Q'=>'--.-','R'=>'.-.','S'=>'...','T'=>'-','U'=>'..-','V'=>'...-','W'=>'.--','X'=>'-..-',
	'Y'=>'-.--','Z'=>'--..','0'=>'-----','1'=>'.----','2'=>'..---','3'=>'...--','4'=>'....-',
	'5'=>'.....','6'=>'-....','7'=>'--...','8'=>'---..','9'=>'----.','/'=>'-..-.',
);
if ( preg_match( '/const MORSE=\{(.*?)\n\};/s', $js, $t ) ) {
	preg_match_all( "/'?([A-Z0-9\/])'?\s*:\s*'([^']*)'/", $t[1], $e, PREG_SET_ORDER );
	$tabla = array(); foreach ( $e as $x ) $tabla[ $x[1] ] = $x[2];
	$errores = array(); $faltan = array();
	foreach ( $ITU as $k => $v ) {
		if ( ! isset( $tabla[ $k ] ) ) { $faltan[] = $k; continue; }
		if ( $tabla[ $k ] !== $v ) $errores[] = "$k es '{$tabla[$k]}' y debería ser '$v'";
	}
	if ( $errores ) mal( implode( '; ', $errores ) );
	// La barra importa: aparece en todo indicativo portátil (HK4RAU/P)
	if ( $faltan ) mal( 'caracteres ausentes: ' . implode( ' ', $faltan ) );
	if ( ! $errores && ! $faltan ) ok( 'las 37 letras, cifras y la barra coinciden con la norma' );
} else {
	aviso( 'no encuentro la tabla MORSE' );
}

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '10. Fórmulas y datos duplicados' );
// Bloques de 4+ líneas repetidos textualmente. Así divergieron los grid squares (124 km de
// error), la fórmula de Yagi y la tabla Morse, sin que nadie lo notara.
$lineas = array();
foreach ( array( 'functions.php', 'front-page.php', 'page-herramientas.php', 'inc/herramientas-scripts.php' ) as $rel ) {
	$n = 0;
	foreach ( explode( "\n", file_get_contents( "$raiz/$rel" ) ) as $l ) {
		$n++;
		$t = trim( preg_replace( '/\s+/', ' ', $l ) );
		if ( strlen( $t ) < 30 ) continue;
		if ( preg_match( '#^(//|/\*|\*|<!--)#', $t ) ) continue;
		$lineas[] = array( $rel, $n, $t );
	}
}
$bloques = array();
for ( $i = 0; $i + 3 < count( $lineas ); $i++ ) {
	if ( $lineas[$i][0] !== $lineas[$i+3][0] ) continue;
	if ( $lineas[$i+3][1] - $lineas[$i][1] !== 3 ) continue;
	$clave = $lineas[$i][2] . "\n" . $lineas[$i+1][2] . "\n" . $lineas[$i+2][2] . "\n" . $lineas[$i+3][2];
	$bloques[ md5( $clave ) ][] = $lineas[$i][0] . ':' . $lineas[$i][1];
}
$reps = array();
foreach ( $bloques as $ub ) if ( count( $ub ) > 1 ) $reps[] = implode( ' y ', $ub );
if ( count( $reps ) > 8 ) {
	aviso( count( $reps ) . ' bloques de 4+ líneas repetidos — revisa si alguno es un dato o una fórmula' );
	foreach ( array_slice( $reps, 0, 5 ) as $r ) echo "          $r\n";
} else {
	ok( count( $reps ) . ' bloques repetidos (solapamientos triviales, por debajo del umbral)' );
}

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '11. Ausencia de dato tratada como dato' );
// parseInt(x)||0 convierte "la fuente no reportó nada" en cero. En el panel de propagación eso
// hacía que un índice K ausente se anunciara como "condiciones excelentes para HF".
$n = preg_match_all( '/parse(?:Int|Float)\([^)]*\)\s*\|\|\s*0\b/', $js, $mm );
if ( $n ) {
	aviso( "$n uso(s) de parseInt/parseFloat(...)||0 — comprueba que el cero sea un valor legítimo y no un dato ausente" );
	foreach ( array_slice( $mm[0], 0, 4 ) as $x ) echo "          $x\n";
} else {
	ok( 'ninguno' );
}

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '12. Endpoints que escriben en la base de datos' );
// Todo endpoint público que escribe debe tener límite por IP; si no, cualquiera puede inundarlo.
preg_match_all( '/function (rau_[a-z_0-9]+)\s*\(\s*\)\s*\{(.*?)\n\}/s', $fn, $fns, PREG_SET_ORDER );
$sin_limite = array();
foreach ( $fns as $f ) {
	$cuerpo = $f[2];
	$escribe = preg_match( '/\b(wp_insert_post|update_option|update_post_meta|wp_insert_comment)\s*\(/', $cuerpo );
	if ( ! $escribe ) continue;
	// Solo interesan los que están expuestos a visitantes anónimos. Las tareas de cron y las
	// funciones internas escriben también, pero no las puede invocar cualquiera desde fuera.
	if ( ! preg_match( "/add_action\(\s*'wp_ajax_nopriv_[a-z_0-9]+',\s*'" . preg_quote( $f[1], '/' ) . "'\s*\)/", $fn ) ) continue;
	$protegido = strpos( $cuerpo, 'get_transient' ) !== false
	          || strpos( $cuerpo, 'wp_verify_nonce' ) !== false
	          || strpos( $cuerpo, 'current_user_can' ) !== false;
	if ( ! $protegido ) $sin_limite[] = $f[1];
}
$sin_limite ? mal( 'escriben sin nonce, capability ni límite por IP: ' . implode( ', ', $sin_limite ) )
            : ok( 'todos los que escriben tienen nonce, capability o límite por IP' );

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '13. Accesibilidad' );
$sin_etiqueta = 0; $sin_nombre = 0;
foreach ( $php_files as $f ) {
	if ( strpos( $f, 'scripts' ) !== false ) continue;
	$s = file_get_contents( $f );
	preg_match_all( '/<label\b.*?<\/label>/s', $s, $labels, PREG_OFFSET_CAPTURE );
	preg_match_all( '/<(input|select|textarea)\b((?:[^>]|<\?php.*?\?>)*?)>/s', $s, $ii, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
	foreach ( $ii as $x ) {
		$attrs = $x[2][0];
		if ( preg_match( '/type\s*=\s*"(hidden|submit|button)"/', $attrs ) ) continue;
		if ( preg_match( '/\b(aria-label|aria-labelledby)\s*=/', $attrs ) ) continue;
		if ( preg_match( '/id="([^"]+)"/', $attrs, $idm ) && strpos( $s, 'for="' . $idm[1] . '"' ) !== false ) continue;
		$dentro = false;
		foreach ( $labels[0] as $lb ) {
			if ( $x[0][1] >= $lb[1] && $x[0][1] < $lb[1] + strlen( $lb[0] ) ) { $dentro = true; break; }
		}
		if ( ! $dentro ) $sin_etiqueta++;
	}
	preg_match_all( '/<button\b((?:[^>]|<\?php.*?\?>)*?)>(.*?)<\/button>/s', $s, $bb, PREG_SET_ORDER );
	foreach ( $bb as $x ) {
		if ( preg_match( '/\b(aria-label|title)\s*=/', $x[1] ) ) continue;
		$txt = preg_replace( '/<[^>]+>/', '', preg_replace( '/<\?php.*?\?>/s', 'X', $x[2] ) );
		if ( '' === preg_replace( '/[^\w]/u', '', $txt ) ) $sin_nombre++;
	}
}
$sin_etiqueta ? mal( "$sin_etiqueta campo(s) de formulario sin etiqueta accesible" )
              : ok( 'todos los campos tienen etiqueta o aria-label' );
$sin_nombre ? mal( "$sin_nombre botón(es) sin nombre accesible" )
            : ok( 'todos los botones tienen nombre accesible' );

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '14. SEO de las URLs de herramientas' );
// Las 54 herramientas viven en direcciones creadas por una regla de reescritura. Sin canonical
// propio, el de WordPress las declara todas copias de /herramientas/ y Google indexa solo una.
if ( strpos( $fn, "remove_action( 'wp_head', 'rel_canonical' )" ) === false ) {
	mal( 'no se sustituye el canonical del core: las 54 herramientas apuntarán a /herramientas/' );
} else {
	ok( 'el canonical del core está sustituido' );
}
if ( strpos( $fn, 'rau_url_canonica()' ) === false || strpos( $fn, 'og:url' ) === false ) {
	aviso( 'revisa que og:url use rau_url_canonica()' );
} elseif ( preg_match( '/og:url.*?rau_url_canonica\(\)/s', $fn ) ) {
	ok( 'og:url usa la URL canónica de cada herramienta' );
} else {
	mal( 'og:url no usa rau_url_canonica(): todas las herramientas se comparten con la misma URL' );
}
if ( strpos( $fn, 'wp_register_sitemap_provider' ) === false ) {
	mal( 'las herramientas no están registradas en el sitemap' );
} else {
	ok( 'las herramientas se registran en el sitemap' );
}

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '15. Trampas conocidas de Maidenhead (en TODOS los archivos)' );
// Tres errores distintos que aparecieron en copias repartidas por el tema. La comprobación 10
// solo mira cuatro archivos; esta recorre los 36, que es como se escapó la copia de single.php.
$problemas = array();
foreach ( $php_files as $f ) {
	$s = file_get_contents( $f );
	$b = basename( $f );
	// a) 0.0833333 / 0.0416667 son 1/12 y 1/24 truncados: al dividir, una coordenada justo en el
	//    límite del subcuadro cae en la letra anterior. Hay que multiplicar por 12 y 24.
	if ( preg_match( '#/\s*0\.0(?:833333|416667)#', $s ) ) {
		$problemas[] = "$b divide por 1/12 o 1/24 truncado en vez de multiplicar";
	}
	// b) Sumar el centro del cuadro (+1 / +0.5) Y ADEMÁS el del subcuadro: hasta 124 km de error.
	if ( preg_match( '/-\s*180\s*\+\s*1\b/', $s ) && preg_match( '/charCodeAt\(\s*4\s*\)/', $s ) ) {
		$problemas[] = "$b suma el centro del cuadro y el del subcuadro a la vez (error de ~124 km)";
	}
	// c) El alfabeto de CAMPOS tiene 18 letras (A–R) pero los SUBCUADROS usan 24 (a–x).
	//    Reutilizar el de 18 devuelve undefined en una de cada cuatro posiciones del planeta.
	if ( preg_match( "/'ABCDEFGHIJKLMNOPQR'/", $s ) && preg_match( '/toLowerCase\(\)\s*\[/', $s ) ) {
		$problemas[] = "$b usa el alfabeto de 18 letras para los subcuadros (devuelve undefined)";
	}
}
$problemas ? mal( implode( '; ', $problemas ) )
           : ok( 'ninguna de las tres trampas conocidas en los ' . count( $php_files ) . ' archivos' );

/* ─────────────────────────────────────────────────────────────────────── */
titulo( '16. El JavaScript de Herramientas se sirve en línea' );
// Se intentó servirlo como archivo aparte y falló dos veces en producción: con regla de
// reescritura devolvía 404 (página entera sin JavaScript) y por parámetro de consulta, HTTP 500.
// Esta comprobación existe para que nadie lo reintente sin darse cuenta de que ya se probó.
if ( strpos( $html, "include get_template_directory() . '/inc/herramientas-scripts.php'" ) === false ) {
	mal( 'la página ya no incluye el JS: si se movió a un archivo aparte, verifica el Content-Type antes de dar por bueno el cambio' );
} else {
	ok( 'se incluye en línea, que es lo que funciona en este alojamiento' );
}
if ( strpos( $js, 'window.RAU_JS_COMPLETO' ) === false || strpos( $html, 'RAU_JS_COMPLETO' ) === false ) {
	mal( 'falta el vigilante de arranque: si el JS muere, los paneles se quedarán diciendo "Cargando…" sin avisar' );
} else {
	ok( 'el vigilante avisa si el JavaScript no llega a ejecutarse' );
}
if ( strpos( $html, 'window.RAU_INIT' ) === false ) {
	mal( 'falta window.RAU_INIT: la página no le dice al script qué herramienta abrir' );
} else {
	ok( 'window.RAU_INIT se publica antes del script' );
}

/* ─────────────────────────────────────────────────────────────────────── */
echo "\n" . str_repeat( '=', 72 ) . "\n";
if ( $fallos ) {
	echo "\033[31m$fallos comprobación(es) fallan\033[0m";
	if ( $avisos ) echo " · $avisos aviso(s)";
	echo "\n";
	exit( 1 );
}
echo "\033[32mTodo pasa\033[0m" . ( $avisos ? " · $avisos aviso(s) para revisar a mano" : '' ) . "\n";
exit( 0 );
