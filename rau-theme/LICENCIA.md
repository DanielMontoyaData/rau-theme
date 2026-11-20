# Licencia del tema RAU

El tema se distribuye bajo la **Licencia Pública General de GNU, versión 3 o posterior (GPLv3+)**.
El texto completo de la licencia está en [`assets/radio/LICENSE-JS8Call-GPLv3.txt`](assets/radio/LICENSE-JS8Call-GPLv3.txt).

## Por qué GPLv3

Hasta la versión 19.56 el tema era GPLv2 o posterior. Desde la 19.57, el decodificador de JS8
(`assets/radio/js8.js`) adapta código de JS8Call, que es GPLv3. Una obra que incluye código GPLv3 solo
puede distribuirse completa bajo GPLv3, así que el tema pasa a GPLv3 o posterior. Como el código propio
ya era «v2 o posterior», el cambio es compatible.

## Partes de terceros

| Archivo | Origen | Licencia |
|---|---|---|
| `assets/radio/js8.js`, `assets/radio/js8-jsc.json.gz` | JS8Call (Jordan Sherer, KN4CRD) | GPLv3 |
| `assets/radio/ftx.js` | ft8_lib (Kārlis Goba) | MIT, ver `assets/radio/LICENSE-ft8_lib.txt` |
| `assets/vendor/leaflet/` | Leaflet (Volodymyr Agafonkin) | BSD-2-Clause, ver su `LICENSE` |
| `assets/vendor/qrcode/` | QR Code Generator (Kazuhiko Arase) | MIT, ver su `LICENSE` |
| `assets/fonts/SpaceGrotesk-*` | Space Grotesk | SIL OFL 1.1, ver `SpaceGrotesk-OFL.txt` |
| `assets/fonts/DSEG7Classic-*` | DSEG | SIL OFL 1.1, ver `DSEG-LICENSE.txt` |
| `assets/images/hero-torre-*.webp` | Foto 15491339 de Pexels | Licencia de Pexels (uso libre, sin atribución obligatoria) |

Quien redistribuya el tema (o una versión modificada) debe entregar también su código fuente bajo GPLv3.
