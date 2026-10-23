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

`php bin/verificar.php` — 16 comprobaciones, cada una nacida de un fallo que estuvo en producción.
Devuelve 0 o 1, así que sirve en un gancho de git. Probado reintroduciendo ocho fallos históricos:
los detecta todos. Ver `bin/README.md`.

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
