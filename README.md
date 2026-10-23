# RAU Theme — Sitio institucional de Radioaficionados Unidos

Tema de WordPress 100% personalizado (sin page builders) desarrollado para **RAU (Radioaficionados Unidos)**, una asociación colombiana de radioaficionados.

## Stack técnico

- WordPress con tema a medida (PHP + Tailwind CSS)
- 16 tipos de contenido personalizados (CPTs): alerta, anuncio, checkin, contenido, diploma, directivo, documento, evento, galeria_item, historia, libro_visitas, miembro, qso, recurso, repetidor, turno
- Base de datos MySQL
- Integraciones con servicios externos en tiempo real (propagación solar, DX spots, SDR remoto)
- Progressive Web App (manifest.json, service worker)

## Funcionalidades principales

- **Más de 50 herramientas interactivas** especializadas en radioafición: calculadoras de radiofrecuencia (antenas, línea de transmisión, enlaces de repetidor), predicción orbital de satélites, conversor de coordenadas Maidenhead, generador de tarjetas QSL digitales, entrenador de telegrafía Morse, banco de frecuencias y normativa colombiana, entre otras.
- Check-in de malla en vivo, turnos de guardia, directorio de miembros
- Modo de emergencia, alertas, libro de visitas
- Panel de verificación técnica interna (`bin/verificar.php`) para pruebas cruzadas de cálculo antes de cada release

## Historial de desarrollo

Ver [`CHANGELOG.md`](rau-theme/CHANGELOG.md) para el historial detallado de versiones, incluyendo correcciones de cálculo verificadas contra fuentes externas.

## Instrucciones de instalación

Ver [`INSTRUCCIONES.md`](rau-theme/INSTRUCCIONES.md).

## Nota de seguridad

Este repositorio contiene únicamente el código del tema. Credenciales de base de datos, claves de API y configuración de servidor se gestionan por fuera del control de versiones (variables de entorno / `wp-config.php`, no incluido).

## Autor

Daniel Montoya Escobar — desarrollo y mantenimiento independiente.
