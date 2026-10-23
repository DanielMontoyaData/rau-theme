# Historial de cambios — tema RAU

De la versión **19.1.0** a la **19.22.0**. Revisión completa del tema: las 54 herramientas, la
portada, las 36 plantillas, la capa de proxies, la tabla normativa, seguridad, accesibilidad y SEO.

Cada corrección se verificó contra una fuente externa o con una prueba numérica antes de aplicarla.
Donde no fue posible verificar, está dicho.

---

## Errores de cálculo

**Yagi: el reflector medía lo mismo que el radiador.** `10.06m / 10.06m`. El reflector tiene que ir
~5% más largo; con la misma medida no hay desfase de corriente entre elementos y la antena no
tiene directividad — es un dipolo caro. Estaba en el banco rápido y en la calculadora completa, y
el comentario del código decía "misma fórmula ya verificada en la herramienta completa", así que la
verificación cruzada propagó el error en vez de detectarlo. A 14.15 MHz ahora da radiador 10.06 m
(0.475λ), reflector 10.54 m (0.497λ) y director 9.36 m (0.442λ), contra la referencia clásica
0.473 / 0.495 / 0.440λ.

**Grid squares: 124 km de error, en cinco copias.** La conversión de cuadrícula a coordenadas
estaba duplicada en Maidenhead, Azimuth, Cobertura de Repetidor, Predicción de Pasadas y la página
de cada repetidor. Todas sumaban el desplazamiento al centro del cuadro de 2°×1° (+1 / +0.5) y
**encima** el del subcuadro. Una de ellas dividía además el ancho del subcuadro entre 12 sin razón.
El error medido es de 124 km — más que el alcance de cualquier repetidor de 2m, así que el
simulador de cobertura daba veredictos sin sentido. Comprobado contra los cuatro puntos que la
propia página cita: FJ26ff → 6.2292 N, 75.5417 W.

**Grayline: dos horas corrido.** Signo de la longitud invertido y el del ángulo horario cruzado.
Para Medellín daba amanecer 08:03 y atardecer 19:50 cuando lo real es 05:55 y 18:07. Justo en la
herramienta que sirve para cronometrar la ventana de propagación del grayline.

**Satélites: se medía la distancia sobre el suelo.** La lista mostraba la distancia al punto
subsatelital, ignorando la altura de la órbita: la ISS aparecía a 9.411 km cuando su alcance máximo
real ronda los 2.300 km. Además marcaba con estrella como "más cercano" un satélite que estaba al
otro lado del planeta. Ahora usa el slant range real y la elevación, y los que están bajo el
horizonte lo dicen en vez de mostrar un número que invita a apuntar la antena.

**Maidenhead: `undefined` en una de cada cuatro posiciones.** La página de Modo Emergencia
reutilizaba el alfabeto de 18 letras de los *campos* (A–R) para los *subcuadros*, que usan 24
(a–x). Bogotá salía como `FJ24undefinedr`. Medellín cae en el índice 5, así que quien lo probó
desde la sede lo vio funcionar. Verificado con un barrido de 120.000 posiciones.

**Maidenhead: redondeo en los límites.** La función principal dividía por `0.0416667`, que es 1/24
truncado. Con una coordenada justo en el límite de un subcuadro (`lat%1 = 0.5`) daba 11,99999 en
lugar de 12 y devolvía la letra anterior. Ahora multiplica, que es exacto. Ambas implementaciones
coinciden en 443.700 posiciones comparadas.

---

## Datos incorrectos

**Potencias del decreto: mal en las quince filas.** El artículo 38 del Decreto 963 de 2009 no fija
potencia por banda, sino por rango de frecuencias, categoría de licencia y tipo de estación. La
tabla se había inventado un número por banda. En VHF decía 500 W cuando el máximo legal es 100 W
para la categoría más alta — cinco veces por encima, en la banda que más se usa y donde está la
repetidora del club. Reemplazada por la tabla real del articulado, con las tres categorías y la
distinción entre estación fija y móvil.

**Bandas del Novicio.** El artículo 34 designa banda por banda y tipo de emisión qué puede usar la
Segunda Categoría. La tabla negaba 6m al Novicio, que sí la tiene autorizada, y daba por completas
80m y 10m, que están recortadas. En 15m, 6m y 70cm el Novicio solo tiene CW y datos, no fonía.

**Pérdidas de coaxial: sobreestimadas 2,5 veces.** La fórmula usa 100 MHz de referencia pero las
cifras del selector eran las de ~450 MHz. Con 20 m de RG-58 en 2m decía 10,5 dB —9 W de 100—
cuando lo real ronda 4 dB. Corregidas las ocho según hojas de datos de Belden, Times Microwave y
CommScope.

**FT4 en 40 metros.** Decía 7.090, que es una frecuencia real pero de **concurso** (FT Roundup) y
para FT8. La estándar de FT4 es 7.0475, según la documentación de WSJT-X.

**HK4 no es el Eje Cafetero.** El artículo 35 asigna la zona 4 a Antioquia y Chocó; el Eje
Cafetero es la zona 6. Único error factual del banco de preguntas del examen, y justo en la zona
donde está el club.

**Tonos CTCSS.** El título decía "67 tonos estándar". La tabla tiene 38 — el 67 era el valor del
primer tono, 67.0 Hz, confundido con la cantidad.

**RST.** S9 decía "Muy fuerte" cuando S8 ya es "Fuerte"; en la escala estándar S9 es un grado por
encima, extremadamente fuerte.

**Tabla de prefijos: de 57 a 329 entradas.** Tenía 22 de Sudamérica, 17 de Norteamérica, 10 de
Europa, 5 de Asia, 3 de Oceanía y **ninguna de África**. Los países usan bloques enteros asignados
por la UIT, no un prefijo suelto: faltaban Japón entero salvo JA, España salvo EA, Polonia salvo
SP. Se añadieron los bloques completos y 67 entidades DXCC ausentes. Las zonas ITU/CQ son las de
la capital: para países que abarcan varias son aproximadas, y está anotado.

---

## Datos ausentes presentados como datos

Un patrón que se repitió cuatro veces: `||0` y `typeof x === 'undefined'` convierten "la fuente no
respondió" en un valor concreto, y el sitio afirma algo que nadie dijo.

- **Índice K, en la portada y en la herramienta de propagación.** `parseInt(d.kindex)||0` hacía que
  un dato ausente se anunciara como **"condiciones excelentes para HF"**, con la aguja en verde.
- **Alertas de banda.** Cualquier condición que no fuera *good* ni *fair* se pintaba roja como
  "Pobre", incluidos los huecos que el proxy rellena con un guion.
- **Historial SFI/K.** Un día sin registro se dibujaba como cero y la línea caía al fondo, que se
  lee como un desplome solar en vez de como un hueco en el archivo.

---

## Fallos que tumbaban la página

**`MORSE is not defined`.** Al unificar las tres copias de la tabla Morse la declaré con `const` a
media altura del archivo. Un `const` no existe hasta que su línea se ejecuta, así que el
`ReferenceError` abortaba el resto del script y los cuatro paneles del final —DX Spots, DMR, WSPR,
D-STAR— se quedaban en "Cargando…" indefinidamente. **Este error lo introduje yo** al corregir otro.
Las tablas compartidas están ahora al principio del archivo.

**20 enganches de eventos sin protección.** `document.getElementById('x').addEventListener(...)` a
nivel superior: si el id no existe, `TypeError` y muere todo lo que venga después. Ahora pasan por
`rauOn()`, que avisa por consola y sigue.

**Módulo huérfano.** La herramienta de DX se rehízo con un iframe pero el JavaScript viejo quedó
ejecutándose: lanzaba una petición inútil a `admin-ajax.php` en cada carga y reventaba al pintar
sobre elementos que ya no existían.

---

## DX Spots: cuatro fallos apilados

Todos producían el mismo síntoma, "Sin spots disponibles ahora mismo":

1. `is_array($data)` siempre daba verdadero, así que la rama que manejaba respuestas envueltas en
   objeto era código muerto.
2. El campo del spotter se buscaba como `de_call_sign`; en DXSummit se llama `de_call`.
3. El endpoint de dxheat.com devolvía HTTP 404 — retirado.
4. El timeout de 4 segundos no alcanzaba para cruzar el Atlántico.

Los dos primeros se encontraron leyendo el código; los otros dos solo aparecieron con el modo
diagnóstico añadido para el caso. La frecuencia además se mostraba con un decimal: un spot en
14.024 se copiaba al portapapeles como "14.0", y 28.450 se redondeaba a "28.5", al otro lado de la
banda.

---

## Duplicaciones eliminadas

Ocho, y en cinco las copias ya habían divergido:

| Duplicado | Copias | ¿Habían divergido? |
|---|---|---|
| Grid square → coordenadas | 5 | sí, todas mal |
| Haversine y rumbo | 5 | no |
| Fórmula de Yagi | 2 | no, ambas mal |
| Tabla de prefijos | 2 | no |
| Tabla Morse | 3 | sí, dos sin la barra `/` |
| Calendario de concursos | 2 | no |
| Fase lunar | 2 | no |
| Potencias del decreto | 2 | sí, la segunda quedó sin corregir |

Sigue triplicada la conversión de Maidenhead entre check-in, RAU Field y herramientas, pero las
tres son correctas y unificarlas obligaría a cargar 290 KB de scripts para usar veinte líneas. La
comprobación 15 del guion vigila que no diverjan.

---

## Seguridad

**SSRF en la suscripción de notificaciones.** El endpoint guardaba la URL tal como llegaba y el
servidor **luego le enviaba peticiones**. Cualquier visitante anónimo podía registrar una dirección
de la red interna del hosting o el servicio de metadatos de la nube. Ahora solo se aceptan URLs
https de los servicios push reales de los navegadores; probado contra diez vectores, incluido un
dominio que imita al de Google.

**Escrituras ilimitadas.** El registro del bot era el único endpoint que escribe en la base sin
límite por IP. Como solo conserva las últimas 200 entradas, bastaba con repetir la llamada para
desplazar todas las preguntas reales.

**Cabeceras de IP falseables.** El contador de banderas confiaba en `HTTP_X_FORWARDED_FOR` sin
condiciones. Ahora rechaza rangos privados y reservados. Los límites por IP de los formularios usan
`REMOTE_ADDR` directamente, que no se puede falsear — eso ya estaba bien y conviene que siga así.

---

## Rendimiento

**Diez proxies sin caché de fallos.** Ninguno guardaba nada cuando la fuente externa fallaba, así
que mientras un servicio estuviera caído cada visitante volvía a intentarlo y esperaba el timeout
completo. Con varios paneles en la misma página eso se acumula. El envoltorio `rau_remote_get()`
marca la URL como caída dos minutos: de 40 segundos de espera acumulada a 4, en la simulación con
diez visitantes.

---

## Accesibilidad

- **42 campos de formulario sin etiqueta accesible.** Casi todos tenían `placeholder`, que no
  cumple esa función y además desaparece al escribir.
- **5 botones que solo contenían un icono**, anunciados como "botón" a secas.
- **Patrón de pestañas a medias**: 54 `role="tabpanel"` y ningún `role="tab"`, que es peor que no
  poner nada. Los 54 paneles de herramienta no son pestañas —son navegación con URL propia— y
  pasaron a `role="region"` con nombre. Los dos grupos que sí lo son recibieron el patrón completo
  con navegación por flechas.
- **Contraste**: el texto atenuado a opacidad parcial daba 4,19:1 y 3,44:1, por debajo del mínimo
  AA, en 35 usos de tipografía pequeña. El borde de los campos daba 1,61:1 cuando la norma pide
  3:1 para lo que identifica un control.

Queda pendiente lo que necesita un navegador: orden de tabulación real, trampas de foco y si
conviene anunciar con `aria-live` los paneles que se refrescan solos.

---

## SEO

**Las 54 herramientas eran invisibles.** Cada una tiene URL propia generada por una regla de
reescritura. WordPress no lo sabía: su canonical las declaraba a todas copias de `/herramientas/`,
que le dice a Google explícitamente que indexe solo una. Y ninguna estaba en el sitemap, porque el
del core recorre entradas reales. Ahora hay canonical propio, `og:url` correcto y un proveedor de
sitemap que las registra.

Pendiente, y es trabajo de diseño: la imagen de Open Graph es el badge de 640×591, pero la tarjeta
se declara `summary_large_image`, que espera algo cercano a 1200×630.

---

## Herramienta nueva

**Ángulo de Radiación** (`/herramientas/angulo/`). Frecuencia y altura de la antena → ángulo del
lóbulo principal, lóbulos secundarios y una lectura en lenguaje llano. Detecta el caso NVIS por
debajo de λ/4, que en Colombia es lo que se busca para cobertura nacional en 40m y 80m. Contrastada
contra la tabla clásica: λ/2 → 30°, 1λ → 14,5°, 2λ → 7,2°.

---

## Simulador RABCA

Además del error de HK4: una pregunta contenía la respuesta literal en el enunciado, y el barajado
era `sort(() => Math.random() - 0.5)`, un algoritmo sesgado. Medido sobre 200.000 tiradas, la
respuesta correcta caía en la 2ª o 3ª posición el **76%** de las veces. Como en el banco la
correcta suele ser la segunda opción del array, se podía aprobar apostando siempre por las de en
medio sin saber nada de radio. Reemplazado por Fisher-Yates. Se añadieron tres preguntas sobre
normativa colombiana verificada.

---

## Rendimiento: un intento fallido, documentado

Los 289 KB de JavaScript van en línea dentro del HTML. Como las 54 herramientas tienen URL propia,
navegar de una a otra vuelve a descargarlos enteros: recorrer cinco transfiere 1,4 MB de código
idéntico. Servirlo como archivo aparte lo arreglaría.

**Se intentó dos veces y falló las dos en este alojamiento.** Primero por regla de reescritura: la
dirección devolvía 404 y la página cargaba entera sin una sola línea de JavaScript. Después por
parámetro de consulta con el manejador en `parse_request`: HTTP 500.

Se revirtió por completo. El ahorro es real pero no compensa el riesgo de dejar la página
inutilizable, y cada intento costó un despliegue con el sitio caído. Queda documentado para que
nadie lo reintente sin saber que ya se probó; la comprobación 16 del guion lo vigila.

Si algún día se retoma, la lección es que el veredicto no se puede juzgar a ojo: lo que decide si
el navegador ejecuta el archivo es la cabecera `Content-Type`, y un archivo servido como
`text/html` se ve idéntico a uno servido bien.

---

## Verificación automática

`php bin/verificar.php` — 16 comprobaciones en la 19.34.0 (18 desde la 19.36.0, contando la 3b), cada una nacida de un fallo que estuvo en producción.
Devuelve 0 o 1, así que sirve en un gancho de git. Probado reintroduciendo ocho fallos históricos:
los detecta todos. Ver `bin/README.md`.

---

## 19.41.0 — el chat explica la licencia paso a paso

Sacar la licencia es la duda más común de quien llega, y el chat solo respondía "practica con el
simulador". La "Guía para la Licencia" del sitio era una línea con un enlace.

**Nueva página /como-sacar-la-licencia/** (contenido de WordPress, no del tema): el trámite completo
ante el MinTIC en seis pasos, con dónde y cómo hacer cada uno, los valores de 2026, los datos de
pago, el indicativo por zona, renovación, ascenso a Primera y preguntas frecuentes. Verificada el
23 de octubre de 2026 contra las páginas RABCA del MinTIC (Novicio, actualizada el 27-jun-2025;
Primera, 2-feb-2026), la Circular 009 de 2026 de costos, el temario oficial y el Decreto 1078 de
2015 (arts. 2.2.5.2.3, 2.2.5.2.4, 2.2.5.6.5 y 2.2.5.7.1).

**El chat recorre el trámite.** Nueve respuestas sobre la licencia (cómo sacarla, examen, costo,
pago, documentos, demora, indicativo, renovación y ascenso) en lugar de una. Después de cada
respuesta ofrece las preguntas que suelen venir a continuación, y los botones iniciales empiezan
por la licencia. Las específicas van antes que la general en la lista, porque a igual puntaje gana
la primera: "¿cuánto cuesta la licencia?" responde el costo. Probado con 11 preguntas escritas como
las haría alguien; "se me venció la licencia" caía en el resumen general y se corrigió.

**Respuestas que no eran ciertas.** "Asociarse" pedía registrarse en el sitio, que no tiene
registro; "herramientas" decía "más de 35" (son 60, ahora contadas del registro); "Elmer" mandaba
a un menú y ahora enlaza la página.

**Viñetas en las páginas.** El preflight de Tailwind quitaba viñetas y números de las listas del
editor: los pasos de una guía se leían como párrafos sueltos.

**Enlaces a la guía** en el pie del sitio y en el simulador de examen, que avisa además de que el
examen real incluye cultura general.

---

## 19.40.0 — los errores avisan; el celular estorba menos

**Aviso por correo de errores de JavaScript.** "Ahora mismo" estuvo rota desde su publicación sin
que nadie lo supiera, porque su `.catch()` mostraba un aviso de red. Un script en `wp_head`
escucha los errores sin atrapar y las promesas rechazadas, y `rauErr(e)` se agregó a los 48
`.catch()` del tema (todos menos el registro del service worker). Solo se avisan errores de
programación (ReferenceError, TypeError, SyntaxError, RangeError) que no sean cortes de red, y solo
de scripts del propio sitio. Llegan a `admin_email` con el mensaje, la línea, la página, la versión
del tema y el navegador. Límites: cada error distinto una vez al día, 3 avisos por visitante cada
10 minutos, 10 correos al día en total. Probado contra el sitio en vivo con el fallo de "Ahora
mismo" todavía publicado: un solo aviso, el correcto, y ninguno por errores de red.

**El botón del chat tapaba texto en el celular.** Baja de 56 a 48 px en pantallas pequeñas y se
aparta al desplazarse hacia abajo; vuelve al subir. Con el chat abierto no se mueve.

**Zonas táctiles de 12–17 px.** Spots DX, frecuencias para copiar, satélites y canales solares
eran botones de texto demasiado bajos para un dedo. En pantallas táctiles miden ahora 24 px, el
mínimo de WCAG 2.2; los enlaces del pie y las casillas del mapa también crecen.

**/herramientas/dxcc/ abría bajo el borde de la pantalla.** Las herramientas de arriba terminaban
de cargar después del salto y empujaban el panel. Se vuelve a alinear al terminar la carga, salvo
que la persona ya haya empezado a moverse.

---

## 19.39.0 — revisión de las 60 herramientas en producción

Se abrieron las 60 URLs /herramientas/{id}/ en un móvil simulado, registrando también los errores
que el código atrapa sin mostrar.

**"Ahora mismo, ¿qué trabajo?" nunca funcionó en producción.** Mostraba "No se pudieron leer las
condiciones" aunque los datos solares y los spots llegaban bien. Leía `RAU_QTH_MAPA`, una variable
declarada dentro del bloque del mapa y por tanto invisible desde el suyo: `ReferenceError`, que el
`.catch()` convertía en un aviso de red. Como la página carga todas las herramientas, el error se
repetía en las 60 URLs. El botón "Mi QTH" del conversor de coordenadas tenía el mismo fallo. Ahora
las dos usan `rauQth()`, una sola función global para el QTH guardado o Medellín.

**Comprobación 19, nueva.** Busca constantes en mayúsculas declaradas dentro de un bloque y leídas
desde otro. Probada reintroduciendo el fallo: lo detecta y da los bloques; sin él, pasa.

**El nombre de la herramienta quedaba bajo la cabecera.** Al abrir /herramientas/{id}/ la página
bajaba hasta la herramienta y la cabecera fija (4rem) tapaba su título. `scroll-padding-top` en
`html` lo corrige para todo salto del sitio.

**Un solo H1 para 60 URLs.** Cada herramienta tenía su propio `<title>` y descripción, pero todas
compartían "Herramientas de Radio" como encabezado principal. Ahora el H1 es el nombre de la
herramienta.

**Calendario .ics: `\n` literal en la descripción.** `implode('\n')` con comillas simples dejaba
una barra que `rau_ics_escape()` volvía a escapar: Google Calendar mostraba "rueda\nFrecuencia".

---

## 19.38.0 — lo que ve quien llega por primera vez

**El splash tapaba la portada 5,5 segundos.** En la primera visita de cada sesión el escudo cubría
la página entera y no se podía hacer nada hasta que terminara la animación. Medido con un móvil
simulado: 5.563 ms. Ahora dura 1,6 s con la misma animación, y se salta con un toque o con Esc.

**Instrucciones del escritorio a la vista del público.** Cuando una sección estaba vacía, cualquier
visitante leía "Agrégalos en el escritorio → …". Tras retirar los datos de ejemplo eso pasaba en el
Libro QSL, Turnos y Próximas actividades. Ahora esa indicación solo la ve quien puede editar; el
público lee un mensaje neutro. Portada (repetidores, eventos, historias), Directorio, Pulguero,
Libro QSL, Documentos, Junta Directiva y Turnos.

**"1 repetidores".** El contador de la portada concuerda en número.

**Eventos semanales que caducaban.** La portada y el calendario solo muestran eventos de hoy en
adelante, y un evento tiene una sola fecha: la malla de los martes desaparecía a los siete días.
Campo nuevo `recurrencia` (`semanal`): cuando la fecha pasa, se corre sola al mismo día de la
semana siguiente. En el calendario .ics sale con `RRULE:FREQ=WEEKLY`.

**Campos del evento en la API.** `start`, `type`, `frequency`, `location` y `recurrencia` quedan
declarados con `register_post_meta`. Antes la API REST los ignoraba sin avisar, así que un evento
creado por esa vía no tenía fecha y no aparecía en ningún lado.

**Iconos sociales sin nombre.** WhatsApp, Instagram, Facebook y YouTube del pie eran enlaces con
solo un SVG: un lector de pantalla los anunciaba como "enlace". Llevan `aria-label` y el SVG se
oculta a la accesibilidad.

---

## 19.37.0 — análisis del sitio en producción

Se recorrieron desde fuera las 134 URLs del sitemap de hk4rau.com con un navegador real
(escritorio y móvil), 230 enlaces, axe-core en 12 páginas y el peso de cada página. Los cambios se
probaron después en un WordPress 7.1.3 local con el tema, contenido de prueba y el mismo navegador.

**Ninguna URL de herramienta mostraba su herramienta.** `/herramientas/qsl/` abría la página y
enseñaba el tablero "Radio Now": el panel de la QSL se activaba, pero dentro del catálogo, que vive
en el modo "Catálogo" de la consola y estaba oculto. Medido en producción: 0 de 60. Con la
corrección: 60 de 60, en pantalla y sin errores. `activateCategory()` pasa ahora a ese modo con el
mismo clic que usa el botón "+ Más herramientas", y quien llega por la URL de una herramienta ve su
panel. Ninguna comprobación lo detectaba: todas miraban el código, ninguna lo que se ve. La 18 lo
vigila.

**Seguridad.**
- `bin/verificar.php` se ejecutaba desde la web: pedir su URL lo ponía a correr en el servidor, sin
  límite. Ahora solo funciona desde la línea de comandos y responde 404 al resto.
- El usuario administrador se publicaba en `/wp-json/wp/v2/users`, en `/?author=1` (la cabecera
  `Location` de la redirección ya lo revelaba) y en el sitemap de usuarios. Las tres vías cerradas
  para visitantes anónimos; el escritorio y las contraseñas de aplicación siguen viéndolo.
- Cabeceras que el alojamiento no enviaba: HSTS (un año, sin subdominios), `nosniff`,
  `X-Frame-Options: SAMEORIGIN` y `Referrer-Policy`. Sin `Permissions-Policy`: varias herramientas
  piden la ubicación. `X-Powered-By` ya no anuncia la versión de PHP.

**Privacidad del directorio.** La página decía "solo aparecen los miembros que autorizaron
publicar sus datos" y listaba a todos: el campo `visible` solo existía en un comentario. Ahora hay
una casilla "Directorio público" en la ficha de cada miembro y sin ella no aparece. Además, la API
REST publicaba el nombre de todos los miembros y cada uno tenía su página `/miembro/{nombre}/`
indexada. **Después de desplegar, el directorio queda vacío hasta marcar la casilla de quienes
autorizaron.**

**Fichas vacías.** Miembros, turnos, QSO y directivos tenían página propia que solo mostraba el
título (`single.php` no tiene vista para esos tipos): unas 15 páginas indexadas sin contenido. Ya no
son consultables por URL; siguen en el escritorio y en las páginas que los listan.

**SEO.**
- Canonical: categorías, etiquetas, autores y las páginas 2, 3… de cualquier listado declaraban la
  portada como su URL canónica. Ahora cada una la suya; búsquedas y 404 no llevan.
- 41 páginas salían con `<meta name="description" content="">`. Ahora la descripción sale de los
  datos de la página: los campos del repetidor o del evento, o la introducción de su plantilla.
- RAU Field no tenía `<h1>`.

**Rendimiento.** Las páginas de herramientas pesaban 2–4,2 MB contra 700 KB de la portada:
- El widget de DXheat (hasta 1,8 MB, con su propio contador Piwik) se cargaba en las 60 URLs aunque
  nadie abriera el panel: `loading="lazy"` no aplaza un iframe oculto. Ahora usa `data-src` y se
  carga al abrir su panel. De ahí salían los avisos de cookies `_pk_` en la consola.
- El generador de QSL descargaba sus tres fotos de fondo (~1 MB) en cada herramienta, y el fondo
  por defecto no usa ninguna. Ahora cada foto se pide al elegir su fondo.
- Fuera el script de emojis de WordPress (hasta 73 KB por página).

**Accesibilidad y móvil.**
- Textos con opacidad 50 % daban contraste 2,8:1 (calculado y coincidente con axe: 2,81). Pasan a
  opacidad completa: 7,1:1 en oscuro, 5,6:1 en claro. Los iconos de 30–40 % son decorativos.
- Los enlaces `text-primary hover:underline` dentro de un párrafo solo se distinguían por el color
  (1,32:1 con el texto). Ahora van subrayados.
- El marcador de la ISS en el mapa no tenía nombre para lectores de pantalla.
- Los archivos de categoría y autor mostraban el título con las etiquetas escapadas
  ("Categoría: &lt;span&gt;…") y esa cadena, sin espacios, desbordaba en móvil. Una URL larga en
  la guía de licencia también.

**Comprobación 18, nueva**: que `activateCategory()` pase al modo Catálogo y que ningún iframe de
terceros en Herramientas lleve `src`. Probada reintroduciendo cada fallo por separado.

**Queda fuera del tema** (panel de WordPress / Hostinger): despublicar el contenido de ejemplo
(alerta "Malla de Emergencia activada", QSO HK3ZZZ y HK4WWW, miembros y turnos de prueba,
"historia-1" a "historia-3", "Hello world!"), cambiar el nombre público del administrador, que hoy
es su correo, y borrar `readme.html` y `license.txt`. Tras desplegar, purgar la caché de LiteSpeed:
guarda también las cabeceras.

---

## 19.36.0 — campos vacíos y JavaScript validado

**No existe una 19.35.0 publicada.** En una sesión de trabajo apareció una copia marcada 19.35.0
con una herramienta `cobreal` (Cobertura Real) cuyo origen nadie pudo explicar, y no se integró.
Se salta el número para que no se confunda con aquella copia. Esta versión parte de la 19.34.0
desplegada, que desde ahora vive en git.

**Campos vacíos que daban un resultado.** La comprobación 11 contaba 13 usos de `parseFloat(x)||0`
y en realidad había 31: su expresión regular no veía el paréntesis anidado de
`getElementById('x').value`. Revisados uno a uno, cinco herramientas convertían un campo borrado
en un número falso:

- **Autonomía de batería** (y la versión corta del Centro de mando): un consumo en RX o TX borrado
  contaba como 0 mA y la autonomía salía inflada. Es la cifra con la que se planea una emergencia.
- **Carta de Smith** y su versión del banco rápido: R vacía daba ROE ∞:1, como una antena en corto.
- **Field Day**: capacidad vacía daba "0h 0m".
- **Split**: frecuencia vacía daba "0.0 kHz".
- **Perfil de terreno**: una altura de antena vacía se calculaba como 0 m sin avisar. Ahora lo pide
  antes de consultar las elevaciones.

Ahora muestran "—" o piden el dato. En los 16 usos restantes el cero sí es un valor real (contadores
DXCC/WAS vacíos, índices de `<select>`, datos que nunca se guardaron, X = 0 en una carga resistiva,
split sin desplazamiento) y cada uno lo declara en la misma línea con `/* 0 legítimo: motivo */`.

**Comprobación 11, de aviso a fallo.** Ya no se conforma con contar: un `||0` sin esa marca hace
fallar el guion. Recorre todos los archivos, no solo el de Herramientas. Probada reintroduciendo
el fallo de la batería (con paréntesis anidado) y el de Field Day: detecta los dos, y no salta con
`||0.95` ni con los comentarios que citan el patrón.

**Comprobación 17, nueva: sintaxis JavaScript.** `php -l` no mira el JavaScript que vive dentro de
los `.php`. La comprobación sustituye cada bloque PHP por un `0`, conservando los saltos de línea, y
pasa cada `<script>` por `node --check` por separado, como lo trata el navegador: 21 bloques. Probada
con tres errores en tres archivos distintos; los detecta los tres y da la línea exacta. Sin `node`
instalado, avisa en vez de fallar.

---

## Pendiente

**Consulta jurídica.** El normograma del MinTIC marca los artículos 29 (atribución de bandas) y 34
(bandas del Novicio) del Decreto 963 de 2009 como **no compilados** en el Decreto Único 1078 de
2015, con remisión a su artículo 3.1.1. Los artículos 35 y 38 —distintivos y potencias— sí quedaron
compilados. O sea que la parte que dice a un Novicio en qué bandas puede operar quedó fuera de la
compilación oficial, mientras que la que dice con cuánta potencia sí. La tabla lleva una nota que
explica la situación sin pronunciarse. Merece que alguien con criterio jurídico lo mire, o una
consulta directa al MinTIC: una tabla que un socio consulta antes de transmitir no debería quedarse
en "probablemente sigue vigente".
