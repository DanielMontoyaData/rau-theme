# Centro de Mando de Emergencias HK4RAU (`/emergencias/`)

Plataforma de coordinación de comunicaciones de emergencia para radioaficionados, radiooperadores y
organizaciones de respuesta en Colombia. Vive dentro del tema de WordPress de HK4RAU, en la ruta
`/emergencias/`, y funciona con conectividad intermitente.

**Qué es y qué no es.** Apoya la coordinación entre operadores: incidentes, mensajes con trazabilidad,
estaciones, recursos, plan de comunicaciones e informes. **No** reemplaza los sistemas oficiales de
atención ni de despacho, **no** transmite por radio y **no** emite alertas públicas.

---

## 1. Arquitectura

| Capa | Archivos | Qué hace |
|---|---|---|
| Ruta | `inc/emergencias.php` | Registra `/emergencias/` (regla de reescritura, sin página de WordPress) y carga los módulos del servidor. |
| Modelo | `inc/emergencias/modelo.php` | Tablas, migraciones, roles, organizaciones, sesiones por dispositivo y auditoría. |
| Esquema | `assets/emergencias/esquema.json` + `inc/emergencias/esquema.php` | Esquema único de los registros: lo aplican el servidor y el navegador. |
| API | `inc/emergencias/api.php` | API REST `/wp-json/rau-em/v1/`. |
| Directorio | `inc/emergencias/directorio.php` | Frecuencias por categoría, con fuente, jurisdicción, condiciones y fecha de verificación. |
| Alertas | `inc/emergencias/alertas.php` | Sismos (USGS), desastres (GDACS) y bandas que oye el receptor HK4RAU. |
| Administración | `inc/emergencias/admin.php` | Herramientas → Centro de mando, organización e indicativo del usuario, política de conservación. |
| Página | `page-emergencias.php` | HTML propio (sin el encabezado del sitio) con respaldo sin JavaScript. |
| Núcleo | `assets/emergencias/em-core.js` | Lógica pura: estado, validación, duplicados, vigencia, SITREP, GeoJSON, CSV, CAP. Se prueba en Node. |
| Almacenamiento | `assets/emergencias/em-db.js` | IndexedDB (operaciones, registros, ajustes) y migración desde la 19.52.0. |
| Interfaz | `em.js`, `em-vistas.js`, `em-mapa.js`, `em-sim.js` | Aplicación, vistas, mapa (Leaflet local) y simulador. |
| Sin conexión | `service-worker-src.php` | Guarda la página, sus archivos, Leaflet, el contorno de Colombia y los mosaicos ya vistos. |

### Modelo de datos

- **Operación**: activación real o simulacro, con un código de 6 caracteres sin O/0 ni I/1. Es el
  contenedor que comparten los operadores. Se guarda en la tabla `{prefijo}rau_em_incidentes`; ese nombre
  se conserva de la 19.52.0 para no tener que migrar datos.
- **Registros (eventos)**: todo lo que pasa dentro de una operación es un evento que solo se agrega
  (`{prefijo}rau_em_eventos`).
  - Cada evento lleva un identificador único creado en el dispositivo, un tipo, el autor que pone el
    servidor, el rol, la sesión, la hora del dispositivo, la hora del servidor y los datos.
  - Nada se sobrescribe ni se borra: las correcciones son registros nuevos.
  - El estado de incidentes, mensajes, estaciones y demás se **calcula** recorriendo los eventos
    (`em-core.js → reducir`).
- **Sesiones** (`rau_em_sesiones`): una por dispositivo. El token es aleatorio (256 bits) y se guarda
  solo como hash SHA-256. Las sesiones vencen a los 30 días y se pueden revocar.
- **Invitaciones** (`rau_em_invitaciones`): claves de un rol (observador, operador o recursos), con
  vencimiento y número de usos.
- **Auditoría** (`rau_em_auditoria`): acciones sensibles. Ver la sección 5.
- **Organizaciones** (`rau_em_orgs`).

### Tipos de registro (resumen; el detalle está en `esquema.json`)

| Módulo | Tipos |
|---|---|
| Operación | `op` (abrir o cerrar), `leccion`, `informe`, `resuelve` (conflictos), `sim` (solo simulacros) |
| Incidentes | `i_nuevo`, `i_act`, `i_verif`, `i_estado`, `i_prio`, `i_desp`, `i_resp`, `i_vinc`, `i_fusion`, `i_nodup`, `decision`, `accion`, `accion_ok` |
| Mensajes | `msg`, `m_est` (entrega), `m_int` (intentos y errores) |
| Estaciones y enlaces | `e_reg`, `e_est` (estado reportado), `e_verif` (verificación), `enlace` (prueba), `contacto`, `sinresp`, `repe` |
| Recursos | `r_reg`, `r_sol`, `r_sol_est`, `r_est` |
| Plan | `p_ver` (versión), `p_est` (borrador, propuesto, aprobado, retirado), `plan` y `planx` (canales asignados) |
| Mapa | `pt`, `pt_act` |
| Bienestar y contactos | `persona`, `personaact`, `dir`, `dirx`, `nota` |
| Heredados de la 19.52.0 | `checkin`, `msgest`, `recurso`, `recursox`, `inc`: se siguen leyendo |

### Distinciones que el sistema mantiene

- **Prioridad del reporte** (`i_prio`) frente a **prioridad de despacho** (`i_desp`).
  - La primera la da quien registra.
  - La segunda solo se registra cuando la decide la autoridad competente, con su nombre.
- **Verificación de un incidente**: reportado → en verificación → verificado o descartado.
  - Solo coordinación verifica o descarta.
  - Un reporte nunca se vuelve «oficial» solo.
- **Estados de un mensaje**: creado → en cola → enviado → recibido por nodo → entregado al destinatario → confirmado.
  - También existen «fallido» y «anulado».
  - Solo se registran los estados que alguien puede demostrar: el nodo que lo recibió, la evidencia de entrega, el acuse.
  - Los intentos y errores se guardan aparte.
- **Estaciones**: disponible (verificada), reportada disponible, no verificada, degradada, fuera de servicio y desconocida.
  - Un reporte o una verificación valen por una ventana configurable (60 min por defecto).
  - Pasada la ventana, el estado es **desconocido**: no se infiere disponibilidad de datos viejos.
- **Recursos**: disponible, reservado, asignado, en desplazamiento, desplegado, liberado y fuera de servicio.
  - Se muestra la **última ubicación conocida** con su antigüedad.
  - No hay seguimiento en tiempo real.
- **Duplicados**: se sugieren por distancia, hora, tipo y similitud del texto.
  - Fusionar exige una persona con rol de coordinación.
  - «No es duplicado» queda registrado.
- **Conflictos**: si dos personas cambian el estado de lo mismo sin ver el cambio del otro, el sistema
  lo detecta (campo `base`) y pide una resolución explícita (`resuelve`).

---

## 2. Seguridad y acceso

Todo se comprueba **en el servidor**: la interfaz solo oculta los botones que el rol no puede usar.

### Roles

| Rol | Permisos | Cómo se obtiene |
|---|---|---|
| Administrador | Todo, en todas las operaciones; organizaciones, directorio, conservación, borrado | Administrador de WordPress |
| Coordinador | Ver, operar, recursos, coordinar (verificar, despacho, fusionar, cerrar, aprobar el plan, invitar, revocar, cambiar claves, auditoría, respaldo) | Cuenta con rol «Emergencias: Coordinador», o la **clave de coordinación** de la operación |
| Responsable de recursos | Ver, operar, aprobar y asignar recursos | Cuenta con ese rol, o una invitación |
| Operador de comunicaciones | Ver y operar (incidentes reportados, mensajes, estaciones, enlaces, notas, mapa, bienestar, solicitudes) | Cuenta con ese rol, o la **clave de operadores** |
| Observador | Ver, sin datos de personas (bienestar) | Cuenta con ese rol, o una invitación |
| Usuario de simulación | Operar **solo en simulacros** | Cuenta con ese rol |

- Las cuentas ven las operaciones de **su organización**, que asigna el administrador en el perfil del
  usuario. El administrador del sitio ve todas.
- Quien no tiene cuenta entra con el código y una clave **dictados por radio**. Las claves se guardan
  con el hash de contraseñas de WordPress; nunca en el navegador ni en el código.
- Una operación sin organización funciona, pero **no aparece en la lista pública**: solo se entra con su
  código. Así se evita que cualquiera publique una «emergencia» visible para todo el país.

### Controles

- **Validación en el servidor**: cada registro se valida contra `esquema.json`.
  - Se revisan el tipo, los campos permitidos, los obligatorios, las listas cerradas, los rangos, los
    largos, las coordenadas en pareja y el permiso por rol (que puede subir según el valor; por ejemplo,
    `i_verif` con `verificado` exige coordinar).
  - Un registro inválido se **rechaza con el motivo**, que se muestra en el dispositivo; no se descarta en silencio.
- **Texto limpio**: todo el texto se guarda con `sanitize_text_field` o `sanitize_textarea_field`, y la
  interfaz escapa todo lo que muestra (`esc`). No hay inyección SQL: todas las consultas usan `$wpdb->prepare`.
- **Límites** por conexión:
  - 6 operaciones al día sin cuenta;
  - 30 intentos de sesión por hora;
  - 8 claves equivocadas por operación y conexión, y luego una hora de bloqueo;
  - 3000 registros por hora y sesión;
  - 100 registros por envío.
- **Revocación**:
  - por sesión (Administración → Accesos);
  - todas a la vez, al cambiar las claves con «revocar»;
  - por cuenta, quitando el rol en WordPress;
  - automática, al aplicar la conservación de una operación cerrada.
- **Sin caché**: las respuestas de la API llevan `Cache-Control: no-store` y `X-LiteSpeed-Cache-Control: no-cache`.
- **Privacidad**:
  - la lista pública solo muestra nombre, tipo, zona, organización y actividad (sin coordenadas ni datos);
  - los observadores no reciben datos de personas;
  - el GeoJSON y el CAP excluyen el bienestar.
- **Operación cerrada**: solo admite reabrirla, lecciones aprendidas, informes y resolución de conflictos.

### Conservación de datos

- Al pasar **90 días** (configurable) desde el cierre de una operación, una tarea diaria reduce los datos
  de personas.
  - El nombre queda en iniciales y se borran contacto, mensaje y ubicación.
  - Además se revocan las sesiones de esa operación.
- El resto del registro operativo se conserva para informes y lecciones.
- Un administrador puede borrar una operación completa en Herramientas → Centro de mando.

---

## 3. API REST (`/wp-json/rau-em/v1/`)

Autenticación: sesión de dispositivo (cabecera `X-RAU-EM-Token`) o cuenta de WordPress (cookie más
`X-WP-Nonce`). Las respuestas de error son `{ "error": "motivo" }` con el código HTTP correspondiente.

| Método y ruta | Permiso | Descripción |
|---|---|---|
| `GET /yo` | — | Cuenta actual (rol, organización) o `null`. |
| `GET /alertas` | — | Sismos (USGS) y GDACS para Colombia, más lo que oye el receptor HK4RAU. |
| `GET /directorio` | — | Directorio de frecuencias con categorías. |
| `POST /directorio` | cuenta de coordinador o administrador | Crea o cambia una entrada (exige `fuente`). Queda auditado. |
| `DELETE /directorio/{id}` | cuenta de administrador | Borra una entrada. Queda auditado. |
| `GET /operaciones` | — | Operaciones abiertas públicas, más las de la organización de la cuenta. |
| `POST /operaciones` | — (con límite) | `nombre, zona, tipo (real/simulacro), indicativo, clave (≥6), clave_coord (≥8)`. Devuelve la operación y una sesión de coordinador. |
| `GET /operaciones/{cod}` | ver | Operación y permisos de quien pregunta. |
| `POST /operaciones/{cod}/sesion` | — (con límite) | `clave, indicativo, nombre`. Devuelve `token` y `actor`. |
| `POST /operaciones/{cod}/salir` | sesión | Revoca la sesión propia. |
| `GET /operaciones/{cod}/eventos?desde=N` | ver | Registros con id > N (máximo 500; `mas` indica si hay más). |
| `POST /operaciones/{cod}/eventos` | operar | `{eventos:[{u,t,ts,d}]}`, máximo 100. Responde `{resultados:[{u, ok, s, error, repetido}]}`. |
| `GET /operaciones/{cod}/sesiones` | coordinar | Sesiones, sin tokens. |
| `POST /operaciones/{cod}/sesiones/{id}/revocar` | coordinar | Revoca una sesión. |
| `POST /operaciones/{cod}/claves` | coordinar | `clave, clave_coord, revocar_sesiones`. |
| `POST /operaciones/{cod}/invitaciones` | coordinar | `rol (observador/operador/recursos), horas (1–168), usos (1–50)`. Devuelve la clave una sola vez. |
| `GET /operaciones/{cod}/auditoria` | coordinar | Acciones sensibles de la operación. |
| `GET /operaciones/{cod}/respaldo` | coordinar | Todos los registros, sin hashes ni tokens. |

---

## 4. Sin conexión

- **Almacenamiento**: IndexedDB (`rau-emergencias`), con los almacenes `ops`, `eventos` y `kv`.
  - Si el navegador no lo permite (por ejemplo, en navegación privada), la aplicación funciona en
    memoria y lo avisa.
  - Los datos de la 19.52.0, que estaban en localStorage, se migran una sola vez.
- **Cola**: cada registro está *sin enviar*, *guardado en el servidor* o *rechazado*.
  - El estado se ve en la cabecera, en la bitácora y en Campo.
  - Nada se presenta como transmitido mientras solo esté en el dispositivo.
- **Sincronización**: cada 15 s, al volver la red y después de cada registro.
  - Si falla, reintenta a los 5, 10, 20… segundos, hasta un máximo de 2 min.
  - Si la sesión fue revocada, avisa y conserva lo pendiente para volver a entrar.
- **Service worker**:
  - guarda `/emergencias/`, los seis JS, el CSS, `colombia.geojson` y Leaflet;
  - guarda hasta 1500 mosaicos de OpenStreetMap **ya vistos**, sin descargas masivas, como pide la
    política de uso de OSM.
- **Mapa sin internet**: contorno de Colombia, sus departamentos y los países vecinos (Natural Earth, dominio público, 96 KB).
- **Respaldo entre dispositivos**: Informes → Exportar → «Descargar respaldo», y en el otro dispositivo «Importar respaldo».
  - Se combina sin duplicar.
  - No se mezclan simulacros con operaciones reales.

**Límites** (probados y documentados):
- La primera visita necesita internet.
- Sin internet no se puede crear una operación en el servidor ni unirse a una. Se puede trabajar en una
  operación local de un solo dispositivo, que no tiene control de acceso.
- El mapa base detallado necesita internet.
- Los datos del dispositivo **no son un respaldo suficiente**: hay que exportar con frecuencia.

---

## 5. Auditoría

Se registran con fecha, actor, rol, objeto, detalle y hash de IP:
- creación de operaciones y apertura o cierre de sesiones;
- claves equivocadas;
- revocaciones, cambios de clave e invitaciones;
- descarga de respaldos;
- todo registro que exige coordinar o recursos;
- cambios del directorio (con el antes y el después);
- cambios de organización o rol de usuarios;
- borrado de operaciones, restauraciones y aplicación de la conservación.

---

## 6. Simulador

Solo corre dentro de operaciones de tipo **simulacro**. Además, el servidor rechaza registros de
simulación en operaciones reales, y el rol «usuario de simulación» no entra a operaciones reales.

**Nueve escenarios**:
- deslizamiento;
- incendio forestal;
- inundación;
- terremoto, con colapsos, reportes duplicados y una repetidora caída;
- comunidad aislada;
- pérdida de conectividad;
- caída de un nodo;
- reportes duplicados;
- recuperación de comunicaciones.

**Cómo funciona**:
- Los escenarios inyectan mensajes «que llegan por radio» a 1×, 5× o 10×.
- Los datos ficticios usan indicativos `SIM…` y llevan la marca de simulación.
- Se mide el tiempo hasta registrar el incidente, entregar o confirmar el mensaje, asignar el recurso,
  generar el informe y resolver los duplicados. Las métricas se exportan en CSV.
- No se envían alertas reales.

---

## 7. Integraciones

| Integración | Estado |
|---|---|
| USGS (sismos) | Activa, de solo lectura (`earthquake.usgs.gov`). |
| GDACS (desastres) | Activa, de solo lectura. |
| Receptor HK4RAU (PSK Reporter y WSPR) | Activa: la usa el tablero para la propagación medida. |
| OpenStreetMap: mosaicos | Activa. Nominatim (búsqueda de lugares): solo cuando la persona lo pide. |
| Servicio Geológico Colombiano | **No disponible**: su feed responde «acceso denegado». No se usa. |
| Voz y CW | Transporte **manual**: el operador transmite y registra. |
| Winlink, APRS, EchoLink | **No configurados**. El modelo de mensaje es independiente del medio. Para Winlink, se exporta el texto o el ICS-213 y se adjunta en su cliente. |
| CAP 1.2 | Exportación por incidente en estado `Draft` (o `Exercise` en simulacro), con alcance restringido. **No** es una alerta pública. |
| Sistemas de gobierno, satélites, radios | **Sin integración**. No se simulan. |

---

## 8. Instalación y despliegue

1. **Respaldo** de la base de datos y del tema actual (Hostinger → Copias de seguridad, o un plugin).
2. Subir el zip del tema en Apariencia → Temas → Añadir nuevo → Subir → **Reemplazar el actual**.
3. Al primer uso, el tema aplica solo:
   - las migraciones de las tablas: `rau_em_db` pasa de `1` a `2`, se agregan columnas a la tabla de
     operaciones y se crean las de sesiones, invitaciones, auditoría y organizaciones;
   - los roles «Emergencias: …»;
   - las reglas de reescritura, porque cambia la versión del tema;
   - la tarea diaria de conservación.
4. Purgar la caché de LiteSpeed (LiteSpeed Cache → Purgar todo).
5. En **Herramientas → Centro de mando**:
   - crear las organizaciones (por ejemplo, RAU y Liga Radio Quindío);
   - asignar a cada operación su organización si debe aparecer en la lista pública.
6. En **Usuarios**:
   - dar a cada coordinador el rol «Emergencias: Coordinador»;
   - asignarle su organización y su indicativo.
7. Verificar:
   - `https://hk4rau.com/emergencias/` carga;
   - `https://hk4rau.com/wp-json/rau-em/v1/directorio` responde JSON;
   - un simulacro de prueba funciona de punta a punta.

**Variables de entorno**: ninguna, y no hay secretos que configurar. Las claves de las operaciones las
crea cada coordinador y se guardan como hash. Opciones de WordPress que se usan:

| Opción | Uso |
|---|---|
| `rau_em_db`, `rau_em_roles` | Versión de las migraciones. |
| `rau_em_directorio` | Directorio de frecuencias editable. |
| `rau_em_retencion_dias` | Días de conservación (90 por defecto). |
| `rau_em_alertas_ultimo` | Últimas alertas buenas. |

`RAU_EM_PRUEBAS` es **solo para pruebas**: se define en el `wp-config.php` local para relajar los límites
de volumen. **Nunca** debe definirse en producción.

### Reversión

- Volver a subir el zip anterior (19.52.0) restaura el código.
- Las tablas nuevas no estorban a la versión anterior, que solo usa `rau_em_incidentes` y `rau_em_eventos`.
- En git, la etiqueta local `emergencias-antes-v2` marca el commit `3fd3662`.

### Recuperación

- **Respaldo de una operación**: Administración → «Descargar respaldo del servidor» (coordinación), o
  Informes → Exportar (cualquier operador, con lo que tiene su dispositivo).
- **Restaurar**: Herramientas → Centro de mando → Restaurar un respaldo.
  - Si la operación existe, agrega lo que falte.
  - Si no existe, la crea cerrada y sin claves; un coordinador con cuenta define claves nuevas.
  - Cada registro se valida de nuevo.

---

## 9. Pruebas

Requieren un WordPress con el tema y `RAU_EM_PRUEBAS` definido en ese `wp-config.php`.

| Prueba | Comando |
|---|---|
| Núcleo (lógica, sin navegador) | `node --test tests/emergencias/core.test.mjs` |
| API REST | `RAU_EM_BASE=http://localhost:8099 node --test tests/emergencias/api.test.mjs` |
| Cuentas, organizaciones, conservación y directorio | `php tests/emergencias/cuentas.php /ruta/a/wordpress/wp-load.php` |
| De punta a punta (Playwright) | `RAU_EM_BASE=http://localhost:8099 node --test --test-concurrency=1 tests/emergencias/e2e.test.mjs` |
| Verificador general del tema | `php bin/verificar.php` |

La carpeta `tests/` no se incluye en el zip que se sube al sitio.

---

## 10. Limitaciones conocidas

- **Formularios**: no son formularios oficiales validados por la UNGRD.
  - El ICS-213, el SCI-205 y el SCI-214 son adaptaciones y lo dicen en su texto.
- **PDF**: se genera con «Imprimir → Guardar como PDF» del navegador, sin una biblioteca de PDF.
- **Frecuencias institucionales**: la categoría está vacía a propósito.
  - No se publican frecuencias de organismos sin una fuente oficial verificable.
- **Repetidoras reportadas en agosto de 2026** (Eje Cafetero): figuran como «pendiente de validación»
  porque no hay tono ni desplazamiento confirmados.
- **Hora**: la de cada registro es la del dispositivo, salvo que sea absurda (con más de 30 días de
  atraso o 1 día de adelanto); la del servidor se guarda aparte.
- **Duplicados**: la detección es una ayuda. Puede no ver duplicados sin coordenadas y con textos
  distintos, y puede sugerir falsos duplicados cercanos.
- **IA**: no se usa. La detección de duplicados y de vacíos de información es por reglas explícitas.
