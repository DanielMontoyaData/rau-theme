<?php
/**
 * Contenido real del Service Worker — servido vía la ruta /service-worker.js
 * definida en functions.php. No se accede a este archivo directamente.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$herramientas_url = home_url( '/herramientas/' );
$cache_version    = 'rau-cache-' . RAU_THEME_VERSION;
?>
const CACHE_NAME = '<?php echo esc_js( $cache_version ); ?>';
const APP_SHELL_URL = '<?php echo esc_js( $herramientas_url ); ?>';

/**
 * Instalación: guarda una copia del "shell" de Herramientas — la página
 * completa con las <?php echo count( rau_get_herramientas_tabs() ); ?> herramientas ya en el DOM. Las que son puramente
 * locales (Maidenhead, Código Q, CTCSS, RST, Fonético, Glosario, Código
 * de Colores) siguen funcionando sin internet porque no dependen de
 * ningún dato en vivo, solo de JS que ya está cargado.
 */
self.addEventListener('install', function(event){
	event.waitUntil(
		caches.open(CACHE_NAME).then(function(cache){
			return cache.addAll([APP_SHELL_URL]).catch(function(){ /* si falla, no bloquea la instalación */ });
		})
	);
	self.skipWaiting();
});

self.addEventListener('activate', function(event){
	event.waitUntil(
		caches.keys().then(function(nombres){
			return Promise.all(nombres.filter(function(n){ return n!==CACHE_NAME; }).map(function(n){ return caches.delete(n); }));
		})
	);
	self.clients.claim();
});

/**
 * Estrategia: red primero, caché de respaldo. Así, con internet siempre
 * se ve la versión más reciente (con datos en vivo); sin internet, cae
 * al shell guardado — las calculadoras locales igual funcionan.
 */
self.addEventListener('fetch', function(event){
	if(event.request.method !== 'GET') return;
	if(event.request.url.indexOf(APP_SHELL_URL) === -1) return; // solo cachea la página de Herramientas

	event.respondWith(
		fetch(event.request).then(function(respuesta){
			var copia = respuesta.clone();
			caches.open(CACHE_NAME).then(function(cache){ cache.put(event.request, copia); });
			return respuesta;
		}).catch(function(){
			return caches.match(event.request).then(function(cacheada){
				return cacheada || new Response('Sin conexión y sin copia guardada todavía. Visita Herramientas una vez con internet primero.', { status: 503 });
			});
		})
	);
});

/**
 * Notificaciones push reales — llegan aunque el sitio no esté abierto.
 */
self.addEventListener('push', function(event){
	var datos = {};
	try { datos = event.data ? event.data.json() : {}; } catch(e){ datos = { title:'RAU', body:event.data ? event.data.text() : '' }; }

	var titulo = datos.title || 'RAU · Radioaficionados Unidos';
	var opciones = {
		body: datos.body || '',
		icon: '<?php echo esc_js( get_template_directory_uri() . "/assets/images/icon-192.png" ); ?>',
		badge: '<?php echo esc_js( get_template_directory_uri() . "/assets/images/favicon-32x32.png" ); ?>',
		data: { url: datos.url || APP_SHELL_URL }
	};
	event.waitUntil(self.registration.showNotification(titulo, opciones));
});

self.addEventListener('notificationclick', function(event){
	event.notification.close();
	var url = (event.notification.data && event.notification.data.url) || APP_SHELL_URL;
	event.waitUntil(
		clients.matchAll({ type:'window' }).then(function(lista){
			for(var i=0;i<lista.length;i++){
				if(lista[i].url===url && 'focus' in lista[i]) return lista[i].focus();
			}
			if(clients.openWindow) return clients.openWindow(url);
		})
	);
});
