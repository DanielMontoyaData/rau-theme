# Tema RAU para WordPress — v2 (calcado del proyecto React original)

Este tema no es una reinterpretación: usa el **CSS compilado real** de tu proyecto
(Tailwind + tus tokens de color) y las **mismas clases** que tienen tus componentes
`HomePage.jsx` y `Navbar.jsx`. Fondo oscuro, cian como color principal, carmesí solo
para la Red de Emergencia.

## 0. Atajo: cargar todo el contenido de ejemplo de un clic
Junto al tema te dejé un archivo aparte, **`rau-datos-de-ejemplo.xml`**. Después de
instalar y activar el tema (pasos 1 y 2 abajo), ve a **Herramientas → Importar →
WordPress** (instala el importador si te lo pide), sube ese archivo y dale
"Importar". Esto crea automáticamente los 3 repetidores, 3 eventos, 3 recursos,
3 directivos, 1 alerta, las 6 secciones de contenido institucional, 3 historias,
3 diplomas, 2 anuncios de Pulguero, 2 contactos QSO, **y las páginas "Pulguero" y
"Libro QSL" ya con su plantilla asignada** — todo lo que antes había que cargar
a mano, campo por campo.

**Fotos:** el tema ya trae fotos reales de bancos gratuitos (Pexels, sin atribución
requerida) en el hero, Clima Espacial, Malla de Emergencia y Comunidad, para que no
se vea vacío desde el primer momento. Reemplázalas por las tuyas cuando quieras:
- Hero: pon una "Imagen destacada" a tu página de portada (Páginas → Ajustes de lectura).
- Comunidad: Apariencia → Personalizar → Textos del hero → "Foto de la sección Comunidad".
- Clima Espacial / Malla de Emergencia: por ahora son fijas en `front-page.php` (búscalas por la URL `images.pexels.com` y cámbialas por tu propia foto).

## 1. Instalar
**Apariencia → Temas → Añadir nuevo → Subir tema** → sube `rau-theme.zip` → **Instalar** → **Activar**.

## 2. Identidad del sitio
**Apariencia → Personalizar → Identidad del sitio** → sube tu escudo real como "Logo del sitio".

**Apariencia → Personalizar** también tiene un panel para el texto del hero (insignia,
título en 3 partes, subtítulo) si quieres cambiar la frase por defecto sin tocar código.

**Portada como foto de fondo del hero (opcional):** en **Páginas → Ajustes de lectura**,
si usas una página estática como portada, ponle una "Imagen destacada" — se usará
como fondo del hero con superposición oscura, igual que en el proyecto original.

## 3. Cargar contenido real

### Repetidores → Agregar nuevo
Activa el panel **Campos personalizados** (Opciones de pantalla, arriba a la derecha) y agrega:
`callsign`, `band`, `frequency`, `offset`, `tone`, `mode`, `location`, `status` (activo / mantenimiento / inactivo)

### Eventos → Agregar nuevo
`start` (AAAA-MM-DD), `type` (malla / rueda / concurso / campo), `frequency`, `location`

### Recursos Técnicos → Agregar nuevo
Título + contenido del editor (descripción). Campos personalizados: `category` (manual / software / diy / nodo_digital / frecuencia_encuentro), `meta` (texto corto), `link` (URL externa opcional)

### Junta Directiva → Agregar nuevo
Título = nombre completo. Campos: `role`, `callsign`, `order` (número para el orden)

### Alertas de Emergencia → Agregar nuevo
Título = texto de la alerta. Campos: `frequency`, `message`, `active` (escribe `1` para que aparezca en el ticker y en la Malla de Emergencia)

### Contenido Institucional → Agregar nuevo
Para Misión y Visión, Guía de Licencia, Normativa, Código de Ética, Plan de Bandas y Cómo Asociarse.
Título = título visible. Contenido del editor = el cuerpo largo. Campos: `section` (usa exactamente:
`mision_vision`, `guia_licencia`, `normativa`, `codigo_etica`, `plan_bandas` o `como_asociarse`), `summary` (resumen corto, solo se usa en las 4 tarjetas pequeñas)

### Historias de la Comunidad → Agregar nuevo
Contenido del editor = la cita/testimonio. Campos: `autor`, `rol`

### Galería de Actividades → Agregar nuevo
Solo sube una "Imagen destacada" por cada entrada — no necesita más campos.

### Diplomas y Certificaciones → Agregar nuevo
Título + contenido del editor (descripción breve).

### Directorio de Repetidores → mapa interactivo (opcional)
Si además de `callsign`, `frequency`, etc. le agregas a un repetidor los campos
`lat` y `lng` (ej. `6.2442` y `-75.5812`), aparece automáticamente un mapa
interactivo arriba de la tabla, con un marcador por cada repetidor georreferenciado.
Si ningún repetidor tiene coordenadas, el mapa simplemente no se muestra — no rompe nada.

### Pulguero (clasificados)
1. Crea una **Página** nueva (título sugerido: "Pulguero"), y en **Atributos de página**
   elige la plantilla **"Pulguero"**. Publícala.
2. Ve a **Pulguero (Clasificados) → Agregar nuevo** por cada anuncio: título, foto
   destacada, descripción en el editor, y campos personalizados `precio` y `contacto`.

### Libro de Guardia (QSL)
1. Crea una **Página** nueva (título sugerido: "Libro QSL"), plantilla **"Libro de Guardia (QSL)"**.
2. Ve a **Libro de Guardia (QSL) → Agregar nuevo** por cada contacto: título = indicativo
   contactado. Campos: `fecha`, `banda`, `modo`, `confirmado` (escribe `1` si ya llegó la QSL).

### Clima Espacial en vivo
Ya no es un enlace — la sección "Clima Espacial y Propagación" del inicio ahora muestra el
panel real de datos solares de HamQSL (N0NBH), que se actualiza solo cada 3 horas. No necesita
configuración.

### Directorio de Miembros
1. Crea una **Página** nueva (ej. "Directorio de Miembros"), plantilla **"Directorio de Miembros"**.
2. Ve a **Directorio de Miembros → Agregar nuevo** por cada persona que autorice aparecer:
   título = nombre. Campos: `indicativo`, `ciudad`, `bandas` (ej. "HF, VHF, DMR").

### Biblioteca de Documentos
1. Crea una **Página** nueva (ej. "Documentos"), plantilla **"Biblioteca de Documentos"**.
2. Sube primero el PDF/archivo en **Medios → Añadir nuevo**, copia su URL.
3. Ve a **Biblioteca de Documentos → Agregar nuevo**: título, descripción opcional en el
   editor, y campos `archivo_url` (pega la URL del paso anterior) y `categoria` (ej. "Acta", "Estatuto").

### Turnos de Guardia (Red de Emergencia)
1. Crea una **Página** nueva (ej. "Turnos de Guardia"), plantilla **"Turnos de Guardia"**.
2. Ve a **Turnos de Guardia → Agregar nuevo**: título = nombre del responsable. Campos:
   `fecha_inicio`, `fecha_fin` (ambas en formato AAAA-MM-DD), `notas` (opcional). El turno que
   incluya la fecha de hoy se marca automáticamente como "En turno".

### Programa Elmer (mentores)
Usa el mismo Directorio de Miembros: a quien quieras marcar como mentor, agrégale los campos
`mentor` = `1` y `especialidad` (ej. "HF y antenas"). Luego crea una **Página** (ej. "Programa
Elmer"), plantilla **"Programa Elmer (Mentores)"** — listará automáticamente a todos los que
tengan `mentor=1`. El botón "Pedir contacto" abre un correo pre-escrito a `contacto@rau.org`
(cámbialo por el correo real en `page-elmer.php` si es distinto).

### SEO y qué se ve al compartir en redes
El tema ya genera automáticamente meta descripción y etiquetas Open Graph (para que se vea
bien al compartir en WhatsApp/Facebook) usando tu logo y el subtítulo del hero. No necesitas
configurar nada — si quieres, personaliza el subtítulo en Personalizar → Textos del hero.

### Ver todo el contenido, no solo lo que cabe en el inicio
Repetidores, Recursos, Eventos, Historias y Galería ahora tienen su propia página de "ver
todos" con paginación (12 por página, configurable en Ajustes → Lectura → "Las páginas de
blog muestran como máximo"). Los enlaces "Ver todos →" ya aparecen automáticamente junto a
cada sección del inicio.

### Canales de Comunicación (Zello, Echolink, DMR, KiwiSDR, YouTube)
Ya viene con toda tu información real cargada (canal de Zello, sala de Echolink, talkgroup DMR,
directorio externo, lista POTA). Crea una **Página** (ej. "Canales"), plantilla **"Canales de
Comunicación"**. Si algo cambia (nuevo enlace de Echolink, etc.), edita directamente
`page-canales.php`.

### Datos reales ya cargados en esta versión
Al importar `rau-datos-de-ejemplo.xml` esta vez obtienes: tus 3 repetidoras reales
(Medellín 146.675, Bogotá 147.380, Santander 147.925), los canales digitales reales como
Recursos Técnicos, y las redes sociales reales (Instagram, Facebook, YouTube) ya enlazadas
en el pie de página — no necesitas cambiarlas a menos que quieras.

### Google Calendar (2 formas de conectar tus eventos)

**1. Suscripción completa (recomendada) — se actualiza sola:**
Tu sitio genera automáticamente un feed en `tudominio.com/calendario.ics` con todos
los eventos publicados. Para conectarlo:
1. Entra a Google Calendar (en computador) → a la izquierda, junto a "Otros calendarios", clic en **+** → **Desde URL**
2. Pega: `https://tudominio.com/calendario.ics`
3. Clic en **Añadir calendario**

Desde ese momento, cualquier evento que agregues en **Eventos → Agregar nuevo** en WordPress
aparecerá en ese Google Calendar automáticamente — Google revisa el feed cada tantas horas,
así que no es instantáneo, pero no tienes que exportar ni copiar nada nunca más.
Ya viene también enlazado con un botón "Suscribirse" arriba de la sección de eventos del inicio.

**2. Botón "+ Google Calendar" por evento:**
Cada evento (en el inicio, en "Ver todos" y en su propia página) tiene un botón que abre
Google Calendar con ese evento específico ya listo para que un visitante lo guarde en
su propio calendario personal, con un clic — no requiere configuración de tu parte.

Si más adelante quieres lo inverso (mostrar en tu web un Google Calendar que ya tengas armado),
dime el ID de ese calendario y te agrego el bloque para incrustarlo.

## 4. Menú
**Apariencia → Menús** → crea uno con enlaces a `/#repetidores`, `/#recursos`, `/#eventos`,
`/#comunidad`, `/#galeria` (o a tus páginas reales) → asígnalo a **"Menú principal"**.
Si no creas ninguno, el tema ya muestra estos mismos enlaces por defecto.

## 5. Cuentas de usuario
Este tema usa el sistema de usuarios de WordPress en vez de una cuenta aparte:
"Ingresar" lleva al login de WordPress y "Panel" solo lo ven los administradores.

---
Todo lo que dejes vacío simplemente no se muestra — no rompe el diseño.

## Nuevo en v3.11 — Check-in de Malla (Fase 2)

1. Crea una página nueva (ej. título **"Check-in"**) y en Atributos de página →
   Plantilla elige **"Check-in de Malla"**. Publícala y agrégala al menú si quieres.
2. Listo: cualquier visitante puede reportar su asistencia con indicativo + nombre
   (ciudad y comentario opcionales). La lista de reportados **de hoy** se muestra
   en la misma página, con contador.
3. Protecciones incluidas (sin plugins): nonce, campo honeypot anti-bots, límite
   de 1 envío por IP cada 2 minutos, y rechazo de indicativo repetido el mismo día.
4. En el escritorio aparece el menú **Check-ins de Malla** con columnas de
   indicativo, ciudad, fecha y hora, y un botón **"Exportar todo a CSV"** (abre
   perfecto en Excel, con tildes) para llevar la estadística de asistencia.
5. Si alguien se equivoca, borra su check-in desde el escritorio y podrá
   reportarse de nuevo.
