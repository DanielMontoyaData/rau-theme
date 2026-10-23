# Receptor KiwiSDR dentro de hk4rau.com

El KiwiSDR de RAU solo funciona por HTTP (`http://hk4rau.proxy.kiwisdr.com:8073`). Como hk4rau.com
es HTTPS, los navegadores no dejan escucharlo dentro de la página. Este Worker de Cloudflare le da
una dirección HTTPS gratuita y pasa el audio y la cascada tal cual. Con esa dirección, la página
`/receptor/` del sitio muestra el receptor con el diseño de RAU.

Probado el 23 de octubre de 2026 con `wrangler dev` y un KiwiSDR simulado: páginas, redirecciones,
WebSocket de audio y de cascada, y bloqueo de otros sitios (HTTP 403).

## 1. Crear el Worker (10 minutos, gratis)

1. Entra a <https://dash.cloudflare.com/sign-up> y crea una cuenta gratuita. No hace falta mover
   el dominio hk4rau.com a Cloudflare.
2. En el menú, **Compute (Workers) → Workers & Pages → Create → Create Worker**.
3. Ponle de nombre `rau-kiwi` y pulsa **Deploy**.
4. Pulsa **Edit code**, borra el ejemplo, pega todo el contenido de `kiwi-relay.js` y pulsa
   **Deploy**.
5. Copia la dirección que te muestra, algo como `https://rau-kiwi.TU-CUENTA.workers.dev`.
6. Compruébalo: abre `https://rau-kiwi.TU-CUENTA.workers.dev/status` en el navegador. Debe mostrar
   texto que empieza por `status=active`.

Un Worker nuevo ya trae una fecha de compatibilidad posterior al 2 de septiembre de 2024, que es
la que permite conectarse al puerto 8073. No hay que tocar nada más.

Quien prefiera la línea de comandos: `npx wrangler deploy` desde esta carpeta.

### Activarlo en el sitio

Pasa la dirección del paso 5 a quien administra el tema. Se guarda en la opción `rau_kiwi_relay`
de WordPress (por la API: `POST /wp-json/wp/v2/settings` con `{"rau_kiwi_relay": "https://…"}`).
Desde ese momento `/receptor/` muestra el receptor integrado. Si se borra la opción, vuelve a
mostrar el enlace a la interfaz original.

### Costo y límites

El plan gratuito de Workers permite 100.000 solicitudes al día. Cada sesión de escucha usa dos
(audio y cascada), así que alcanza de sobra. El tráfico de audio de una conexión WebSocket abierta
no cuenta como solicitudes adicionales.

Por el relevo, el KiwiSDR ve las conexiones llegar desde servidores de Cloudflare. El Worker le
pasa la IP real del oyente en `X-Real-IP` y `X-Forwarded-For`; si en la lista de usuarios del
KiwiSDR aparecen ubicaciones de Cloudflare en lugar de las reales, es por eso, y no afecta la
escucha.

## 2. Liberar canales en el KiwiSDR (lo hace quien lo administra)

El 23 de octubre de 2026 el receptor tenía **7 de 7 canales ocupados**, muy probablemente por los
decodificadores automáticos (FT8, WSPR). Mientras estén así, nadie puede escuchar, ni por el sitio
ni por la interfaz original.

Desde la versión 1.592 del KiwiSDR se puede hacer que esos decodificadores **cedan el canal**
cuando entra una persona y lo retomen cuando se va:

1. Abre `http://hk4rau.proxy.kiwisdr.com:8073/admin` e inicia sesión.
2. Ve a la pestaña **Extensions** y abre **FT8** (y después **WSPR**).
3. En la sección de autorun, cambia el menú **preemptable?** a **yes** en cada canal automático.
4. Guarda. El estado del sitio mostrará esos canales en «Ceden el canal».

Si la versión es anterior a la 1.592 (la página `/receptor/` muestra la versión), primero hay que
actualizar el KiwiSDR desde **Admin → Update**.

Recomendado además: en **Admin → Control**, el campo **Inactivity time limit (minutes)** (por ejemplo 30), para
que nadie deje un canal ocupado con la pestaña abierta.
