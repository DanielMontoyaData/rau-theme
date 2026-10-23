# Verificación del tema RAU

```bash
php bin/verificar.php
```

Devuelve `0` si todo pasa y `1` si algo falla, así que sirve tal cual en un gancho de git o
en integración continua:

```bash
# .git/hooks/pre-commit
php bin/verificar.php || exit 1
```

## Qué comprueba y por qué

Cada comprobación corresponde a un fallo que **estuvo en producción** en hk4rau.com sin que
nadie lo notara. No son buenas prácticas genéricas.

| # | Comprueba | El fallo que lo motivó |
|---|---|---|
| 1 | Sintaxis PHP | — |
| 2 | Versión igual en `functions.php` y `style.css` | cachés servidas con versión equivocada |
| 3 | Herramientas registradas ↔ paneles del HTML | — |
| 4 | Contadores calculados, no fijos | el catálogo se quedó en 53 al añadir la 54ª |
| 5 | Símbolos compartidos declarados antes de usarse | `MORSE is not defined` tumbó media página |
| 6 | Acceso al DOM sin proteger a nivel superior | un id ausente aborta el script entero |
| 7 | Ids que el JS busca y no existen | módulo huérfano tras rehacer el panel de DX |
| 8 | Integridad de la tabla de prefijos | 57 entradas, cero de África |
| 9 | Tabla Morse contra ITU-R M.1677-1 | tres copias; dos sin la barra `/` |
| 10 | Fórmulas y datos duplicados | grid squares con 124 km de error en 4 copias |
| 11 | `parseInt(x)\|\|0` (aviso) | un índice K ausente se anunciaba como "excelente" |
| 12 | Endpoints públicos que escriben en la base | escrituras ilimitadas sin límite por IP |
| 13 | Accesibilidad de campos y botones | 42 campos sin etiqueta, 5 botones sin nombre |
| 14 | Canonical y sitemap de las herramientas | las 54 URLs colapsaban en `/herramientas/` |
| 15 | Trampas de Maidenhead en los 36 archivos | 5ª copia con 124 km de error; `undefined` en 1 de cada 4 posiciones |
| 16 | El JS se sirve en línea | servirlo aparte falló dos veces: 404 y HTTP 500 |

## Los cuatro patrones de fondo

1. **Datos o fórmulas duplicados que divergen.** Se encontraron siete duplicaciones; en cuatro
   las copias ya habían divergido. Comprobaciones 8, 9 y 10.
2. **Ausencia de dato tratada como dato.** `||0` y `typeof x === 'undefined'` convierten "no
   hay lectura" en un valor concreto, y el sitio afirma algo que la fuente nunca dijo.
   Comprobación 11.
3. **Símbolos compartidos declarados a media altura.** Un `const` usado antes de su línea
   aborta todo el script. Comprobaciones 5 y 6.
4. **Números escritos a mano.** Comprobación 4.

## Qué NO comprueba

Nada que necesite un navegador: contraste real, orden de tabulación, trampas de foco,
si los paneles que se refrescan solos se anuncian a un lector de pantalla. Tampoco valida
que los datos externos (frecuencias, normativa) sigan vigentes: eso hay que contrastarlo
contra la fuente a mano.

Probado reintroduciendo cinco fallos históricos: los detecta los cinco.
