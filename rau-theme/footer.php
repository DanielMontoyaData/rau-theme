<?php
/**
 * Footer — traducción literal del <footer> en HomePage.jsx
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
</main>

<footer class="border-t border-border/60 pt-6 pb-10">
	<section class="mx-auto max-w-[96rem] px-4 sm:px-6">
		<div class="mb-4 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 border-b border-border/50 pb-4 font-mono text-xs text-muted-foreground sm:justify-start">
			<a href="<?php echo esc_url( home_url( '/estado-rau/' ) ); ?>" class="hover:text-primary">Estado RAU</a>
			<a href="<?php echo esc_url( home_url( '/rau-field/' ) ); ?>" class="hover:text-primary">RAU Field</a>
			<a href="<?php echo esc_url( home_url( '/modo-emergencia/' ) ); ?>" class="hover:text-accent">🚨 Modo Emergencia</a>
			<a href="<?php echo esc_url( home_url( '/herramientas/' ) ); ?>" class="hover:text-primary">Herramientas</a>
			<a href="<?php echo esc_url( home_url( '/novedades/' ) ); ?>" class="hover:text-primary">Novedades</a>
			<a href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>" class="hover:text-primary">Contacto</a>
		</div>
		<div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
			<div class="flex items-center gap-2">
				<?php rau_logo_html( 'h-6 w-6' ); ?>
				<span class="font-display font-bold"><?php bloginfo( 'name' ); ?> · Radioaficionados Unidos</span>
			</div>
			<div class="flex items-center gap-3">
				<a href="<?php echo esc_url( rau_whatsapp_url() ); ?>" target="_blank" rel="noreferrer" title="WhatsApp" aria-label="WhatsApp de RAU (abre en otra pestaña)" class="transition-opacity hover:opacity-75">
					<svg aria-hidden="true" focusable="false" class="h-6 w-6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
						<path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.534 5.845L0 24l6.345-1.508A11.934 11.934 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z" fill="#25D366"/>
						<path d="M12 2.182c-5.423 0-9.818 4.395-9.818 9.818 0 1.878.535 3.63 1.46 5.114L2.727 21.27l4.29-1.375A9.773 9.773 0 0 0 12 21.636c5.423 0 9.818-4.395 9.818-9.818S17.423 2.182 12 2.182z" fill="#FEFEFE"/>
						<path d="M17.507 14.398c-.29-.145-1.718-.847-1.984-.944-.265-.097-.458-.145-.651.145-.193.29-.748.944-.917 1.137-.169.193-.338.217-.627.072-.29-.145-1.223-.451-2.329-1.437-.861-.768-1.442-1.717-1.611-2.007-.169-.29-.018-.447.127-.591.13-.13.29-.338.435-.507.145-.169.193-.29.29-.483.096-.193.048-.362-.024-.507-.073-.145-.651-1.57-.892-2.149-.235-.564-.474-.487-.651-.496l-.554-.01c-.193 0-.507.072-.772.362-.265.29-1.013.99-1.013 2.414s1.037 2.8 1.182 2.993c.145.193 2.04 3.116 4.942 4.37.691.298 1.23.476 1.65.609.694.22 1.325.189 1.824.114.556-.083 1.718-.702 1.96-1.38.242-.677.242-1.257.169-1.38-.072-.12-.265-.193-.555-.338z" fill="#25D366"/>
					</svg>
				</a>
				<a href="https://www.instagram.com/hk4rau" target="_blank" rel="noreferrer" title="Instagram" aria-label="Instagram de RAU (abre en otra pestaña)" class="text-muted-foreground transition-colors hover:text-primary">
					<svg aria-hidden="true" focusable="false" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.2" cy="6.8" r="1"></circle></svg>
				</a>
				<a href="https://www.facebook.com/share/1Cahc9bBYr/" target="_blank" rel="noreferrer" title="Facebook" aria-label="Facebook de RAU (abre en otra pestaña)" class="text-muted-foreground transition-colors hover:text-primary">
					<svg aria-hidden="true" focusable="false" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M14 8.5h2.5V5H14c-2 0-3.5 1.5-3.5 3.5V11H8v3h2.5v6h3v-6H16l.5-3h-3V9c0-.3.2-.5.5-.5z"></path></svg>
				</a>
				<a href="https://youtube.com/@radioaficionadosunidos" target="_blank" rel="noreferrer" title="YouTube" aria-label="Canal de YouTube de RAU (abre en otra pestaña)" class="text-muted-foreground transition-colors hover:text-primary">
					<svg aria-hidden="true" focusable="false" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="6" width="18" height="12" rx="3"></rect><path d="M11 10l4 2-4 2z" fill="currentColor" stroke="none"></path></svg>
				</a>
				<a href="https://www.qrz.com/db/HK4RAU" target="_blank" rel="noreferrer" title="Perfil QRZ — HK4RAU" class="font-mono text-xs text-muted-foreground transition-colors hover:text-primary">QRZ↗</a>
			</div>
			<p class="font-mono text-xs text-muted-foreground">73 · <span title="Calculado, verificar con la junta directiva">Locator FJ26*</span> · © <?php echo esc_html( current_time( 'Y' ) ); ?> · info@hk4rau.com</p>
		</div>

		<?php
		$rau_flags = rau_get_flag_counts();
		if ( $rau_flags ) :
			$rau_flags_top   = array_slice( $rau_flags, 0, 10 );
			$rau_flags_total = array_sum( wp_list_pluck( $rau_flags, 'count' ) );
		?>
		<div class="mt-6 rounded-xl border border-border/60 bg-background/40 px-4 py-3">
			<div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-2 sm:justify-between">
				<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">
					🌍 Alcance internacional · <span class="text-primary font-semibold"><?php echo esc_html( count( $rau_flags ) ); ?></span> países · <span class="text-primary font-semibold"><?php echo esc_html( number_format_i18n( $rau_flags_total ) ); ?></span> visitas
				</p>
				<div class="flex flex-wrap items-center justify-center gap-2">
					<?php foreach ( $rau_flags_top as $f ) : ?>
						<span title="<?php echo esc_attr( $f['name'] . ': ' . $f['count'] ); ?>" class="inline-flex items-center gap-1 rounded-full border border-border/50 bg-card px-2 py-0.5 font-mono text-[11px] text-muted-foreground">
							<span><?php echo esc_html( $f['flag'] ); ?></span><span><?php echo esc_html( $f['count'] ); ?></span>
						</span>
					<?php endforeach; ?>
					<?php if ( count( $rau_flags ) > 10 ) : ?>
						<span class="font-mono text-[11px] text-muted-foreground">+<?php echo esc_html( count( $rau_flags ) - 10 ); ?> más</span>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<p class="mt-4 text-center font-mono text-[11px] text-muted-foreground sm:text-right">Sitio desarrollado por HK4SSI &amp; HJ4ENV</p>
	</section>
</footer>

<script>
if('serviceWorker' in navigator){
	window.addEventListener('load', function(){
		navigator.serviceWorker.register('/service-worker.js').catch(function(){ /* falla silenciosa, el sitio sigue funcionando igual sin él */ });
	});
}

/* Suscripción push genérica — cualquier botón con esta clase, en cualquier página */
(function(){
	function urlBase64ToUint8Array(base64String){
		var padding='='.repeat((4 - base64String.length % 4) % 4);
		var base64=(base64String+padding).replace(/-/g,'+').replace(/_/g,'/');
		var rawData=window.atob(base64);
		var outputArray=new Uint8Array(rawData.length);
		for(var i=0;i<rawData.length;++i){ outputArray[i]=rawData.charCodeAt(i); }
		return outputArray;
	}
	document.querySelectorAll('.rau-push-generic-btn').forEach(function(btn){
		var status = btn.parentElement ? btn.parentElement.querySelector('.rau-push-generic-status') : null;
		btn.addEventListener('click', function(){
			if(!('serviceWorker' in navigator) || !('PushManager' in window)){
				btn.disabled=true; btn.textContent='Tu navegador no soporta notificaciones push';
				return;
			}
			btn.disabled=true; btn.textContent='Activando…';
			navigator.serviceWorker.ready.then(function(registration){
				return fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_vapid_public_key')
					.then(function(r){ return r.json(); })
					.then(function(json){
						if(!json.success || !json.data.key) throw new Error('sin clave VAPID');
						return registration.pushManager.subscribe({ userVisibleOnly:true, applicationServerKey: urlBase64ToUint8Array(json.data.key) });
					});
			}).then(function(subscription){
				return fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_push_subscribe', { method:'POST', body: JSON.stringify(subscription) });
			}).then(function(){
				btn.textContent='🔔 Notificaciones activadas';
				if(status){ status.classList.remove('hidden'); status.textContent='Listo. Recibirás avisos de RAU aunque tengas el navegador cerrado.'; }
			}).catch(function(){
				btn.disabled=false; btn.textContent='🔔 Activar notificaciones de RAU';
				if(status){ status.classList.remove('hidden'); status.textContent='No se pudo activar. Intenta de nuevo.'; }
			});
		});
	});
})();
</script>

<!-- BOT DE PREGUNTAS FRECUENTES -->
<div id="rau-bot-widget">
	<button id="rau-bot-toggle" type="button" aria-label="Abrir chat de ayuda" class="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg hover:bg-primary/90">
		<svg id="rau-bot-icon-chat" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"></path></svg>
		<svg id="rau-bot-icon-close" class="hidden h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
	</button>

	<div id="rau-bot-panel" class="hidden fixed bottom-24 right-5 z-40 flex h-[70vh] max-h-[520px] w-[90vw] max-w-sm flex-col overflow-hidden rounded-2xl border-2 border-border bg-card shadow-2xl">
		<div class="flex items-center gap-2 border-b border-border/70 bg-background/60 px-4 py-3">
			<?php rau_logo_html( 'h-7 w-7' ); ?>
			<div>
				<p class="font-display text-sm font-bold leading-none">Asistente RAU</p>
				<p class="mt-0.5 font-mono text-[10px] text-muted-foreground">Respuestas automáticas</p>
			</div>
		</div>

		<div id="rau-bot-messages" class="flex-1 space-y-3 overflow-y-auto p-4"></div>

		<div id="rau-bot-quick" class="flex flex-wrap gap-1.5 border-t border-border/50 p-3"></div>

		<form id="rau-bot-form" class="flex gap-2 border-t border-border/70 p-3">
			<input aria-label="Escribe tu pregunta" type="text" id="rau-bot-input" autocomplete="off" placeholder="Escribe tu pregunta…" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
			<button type="submit" aria-label="Enviar pregunta" class="shrink-0 inline-flex items-center justify-center rounded-md bg-primary px-3 text-primary-foreground hover:bg-primary/90">
				<svg class="h-4 w-4" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
			</button>
		</form>
	</div>
</div>

<script>
/* ── BOT DE PREGUNTAS FRECUENTES (respuestas fijas, sin API, sin costo) ── */
(function(){
	var toggleBtn=document.getElementById('rau-bot-toggle');
	if(!toggleBtn) return;

	var panel=document.getElementById('rau-bot-panel');
	var iconChat=document.getElementById('rau-bot-icon-chat');
	var iconClose=document.getElementById('rau-bot-icon-close');
	var messagesEl=document.getElementById('rau-bot-messages');
	var quickEl=document.getElementById('rau-bot-quick');
	var form=document.getElementById('rau-bot-form');
	var input=document.getElementById('rau-bot-input');

	var FAQ=[
		{ id:'asociarse', pregunta:'¿Cómo me asocio a RAU?',
			palabras:['asociar','asociarme','inscrib','ingresar','membres','socio','unirme','afiliar'],
			respuesta:'Para asociarte: regístrate en el sitio con tu indicativo, completa el formulario de afiliación y realiza el aporte anual. Escríbenos por WhatsApp y te guiamos — <a href="<?php echo esc_url( rau_whatsapp_url() ); ?>" target="_blank" rel="noreferrer" class="text-primary underline">abrir chat</a>.' },
		{ id:'que-es', pregunta:'¿Qué es RAU?',
			palabras:['que es rau','quienes son','quien es rau','sobre rau','asociacion'],
			respuesta:'RAU (Radioaficionados Unidos) es una asociación colombiana de radioaficionados con sede en Medellín, indicativo HK4RAU. Somos una comunidad que mantiene vivas las ondas — repetidores, malla de emergencia, eventos y más.' },
		{ id:'malla', pregunta:'¿Cuándo es la malla semanal?',
			palabras:['malla','net','check-in','checkin','martes','horario malla'],
			respuesta:'La malla semanal es los martes a las 7:00 p.m. en <?php echo esc_html( rau_get_repetidor_principal()['frequency'] ); ?> MHz y canales digitales. Puedes reportarte en <a href="'+'<?php echo esc_url( home_url( "/check-in-de-malla/" ) ); ?>'+'" class="text-primary underline">Check-in de Malla</a>.' },
		{ id:'repetidores', pregunta:'¿Dónde veo los repetidores?',
			palabras:['repetidor','repetidora','frecuencias repetidor','tono','ctcss'],
			respuesta:'Todos nuestros repetidores (y los de aliados) están en el <a href="'+'<?php echo esc_url( get_post_type_archive_link( "repetidor" ) ); ?>'+'" class="text-primary underline">Directorio de Repetidores</a>, con frecuencia, tono y ubicación.' },
		{ id:'kiwisdr', pregunta:'¿Cómo escucho el receptor en línea?',
			palabras:['kiwisdr','sdr','escuchar en linea','receptor','radio en linea'],
			respuesta:'Nuestro KiwiSDR está en línea 24/7. <a href="http://hk4rau.proxy.kiwisdr.com:8073/" target="_blank" rel="noreferrer" class="text-primary underline">Escuchar en vivo</a> (nota: el receptor opera en HTTP, se abre en pestaña nueva).' },
		{ id:'herramientas', pregunta:'¿Qué herramientas tienen?',
			palabras:['herramienta','calculadora','utilidad','conversor'],
			respuesta:'Tenemos más de 35 herramientas gratuitas: calculadoras de antenas, propagación solar, predicción de pasadas de satélites, generador de QSL y muchas más. Todo en <a href="'+'<?php echo esc_url( home_url( "/herramientas/" ) ); ?>'+'" class="text-primary underline">Herramientas de Radio</a>.' },
		{ id:'licencia', pregunta:'¿Cómo saco mi licencia RABCA?',
			palabras:['licencia','rabca','examen','certificado operador'],
			respuesta:'Puedes practicar con nuestro <a href="'+'<?php echo esc_url( home_url( "/herramientas/examen/" ) ); ?>'+'" class="text-primary underline">Simulador de Examen RABCA</a>. Una vez tengas tu licencia, usa el Recordatorio de Vencimiento en Herramientas para no olvidar renovarla.' },
		{ id:'contacto', pregunta:'¿Cómo los contacto?',
			palabras:['contacto','correo','email','escribir','telefono','whatsapp'],
			respuesta:'Escríbenos a <a href="mailto:info@hk4rau.com" class="text-primary underline">info@hk4rau.com</a> o por <a href="<?php echo esc_url( rau_whatsapp_url() ); ?>" target="_blank" rel="noreferrer" class="text-primary underline">WhatsApp</a>.' },
		{ id:'ubicacion', pregunta:'¿Dónde están ubicados?',
			palabras:['ubicacion','donde estan','ciudad','medellin','locator','qth'],
			respuesta:'Estamos en Medellín, Colombia — Locator FJ26.' },
		{ id:'eventos', pregunta:'¿Qué eventos tienen próximamente?',
			palabras:['evento','actividad','concurso','proxima','calendario'],
			respuesta:'Revisa el calendario de <a href="'+'<?php echo esc_url( get_post_type_archive_link( "evento" ) ); ?>'+'" class="text-primary underline">Próximas Actividades</a> en el home, o el Calendario de Concursos en Herramientas.' },
		{ id:'pota', pregunta:'¿Qué es POTA?',
			palabras:['pota','parks on the air','activacion parque'],
			respuesta:'POTA (Parks on the Air) es el programa de activación de parques. Tenemos una pestaña dedicada de POTA Colombia en Herramientas con spots y referencias.' },
		{ id:'elmer', pregunta:'¿Qué es el Programa Elmer?',
			palabras:['elmer','mentor','guia nuevo','ayuda principiante'],
			respuesta:'Elmer es nuestro programa de mentoría: radioaficionados experimentados que guían a quienes están empezando. Lo encuentras en el menú Comunidad → Programa Elmer.' },
		{ id:'diplomas', pregunta:'¿Cómo verifico un diploma?',
			palabras:['diploma','certificado','verificar diploma'],
			respuesta:'Puedes verificar la autenticidad de un diploma con el número que aparece impreso, en la pestaña Verificador de Diplomas dentro de Herramientas.' },
		{ id:'libro', pregunta:'¿Qué es el Libro de Visitas?',
			palabras:['libro de visitas','firmar','mensaje comunidad'],
			respuesta:'Es un espacio donde cualquiera puede dejar un mensaje permanente sobre lo que significa RAU para ellos. <a href="'+'<?php echo esc_url( home_url( "/libro-de-visitas/" ) ); ?>'+'" class="text-primary underline">Firmar el libro</a>.' },
		{ id:'redes', pregunta:'¿Tienen redes sociales?',
			palabras:['instagram','facebook','youtube','redes sociales'],
			respuesta:'Sí — Instagram @hk4rau, Facebook RAU, y YouTube @radioaficionadosunidos. Los links están en el pie de página.' },
	];

	var historial=[];

	function agregarMensaje(texto, esBot){
		var div=document.createElement('div');
		div.className = esBot
			? 'max-w-[85%] rounded-xl rounded-tl-sm bg-background px-3 py-2 text-sm'
			: 'ml-auto max-w-[85%] rounded-xl rounded-tr-sm bg-primary/15 px-3 py-2 text-sm text-right';
		div.innerHTML=texto;
		messagesEl.appendChild(div);
		messagesEl.scrollTop=messagesEl.scrollHeight;
	}

	function normalizar(s){
		return s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
	}

	function encontrarMejorFAQ(pregunta){
		var q=normalizar(pregunta);
		var mejor=null, mejorPuntaje=0;
		FAQ.forEach(function(item){
			var puntaje=0;
			item.palabras.forEach(function(p){
				if(q.indexOf(normalizar(p))>-1) puntaje++;
			});
			if(puntaje>mejorPuntaje){ mejorPuntaje=puntaje; mejor=item; }
		});
		return mejor;
	}
	function buscarRespuesta(pregunta){
		var mejor=encontrarMejorFAQ(pregunta);
		if(mejor) return mejor.respuesta;
		return 'No tengo una respuesta exacta para eso todavía. Prueba con una de las preguntas rápidas de abajo, o escríbenos directo por <a href="<?php echo esc_url( rau_whatsapp_url() ); ?>" target="_blank" rel="noreferrer" class="text-primary underline">WhatsApp</a> — un humano de RAU te responde con gusto. 🙂';
	}

	function renderQuickButtons(){
		quickEl.innerHTML=FAQ.slice(0,6).map(function(item){
			return '<button type="button" data-id="'+item.id+'" class="rau-bot-quick-btn rounded-full border border-border/70 bg-background px-2.5 py-1 font-mono text-[11px] text-muted-foreground hover:border-primary/50 hover:text-primary">'+item.pregunta+'</button>';
		}).join('');
		quickEl.querySelectorAll('.rau-bot-quick-btn').forEach(function(btn){
			btn.addEventListener('click', function(){
				var item=FAQ.find(function(f){ return f.id===btn.dataset.id; });
				if(!item) return;
				agregarMensaje(item.pregunta, false);
				agregarMensaje(item.respuesta, true);
			});
		});
	}

	var abierto=false;
	function togglePanel(){
		abierto=!abierto;
		panel.classList.toggle('hidden', !abierto);
		iconChat.classList.toggle('hidden', abierto);
		iconClose.classList.toggle('hidden', !abierto);
		if(abierto && !messagesEl.children.length){
			agregarMensaje('¡Hola! 👋 Soy el asistente de RAU. Puedo responder preguntas frecuentes sobre la asociación — escribe tu pregunta o elige una de abajo.', true);
			renderQuickButtons();
		}
	}
	toggleBtn.addEventListener('click', togglePanel);

	form.addEventListener('submit', function(e){
		e.preventDefault();
		var texto=input.value.trim();
		if(!texto) return;
		agregarMensaje(texto, false);
		input.value='';
		var encontrada = !!encontrarMejorFAQ(texto);
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_bot_log', {
			method:'POST',
			headers:{'Content-Type':'application/x-www-form-urlencoded'},
			body:'pregunta='+encodeURIComponent(texto)+'&encontrada='+(encontrada?'1':'')
		}).catch(function(){});
		setTimeout(function(){
			agregarMensaje(buscarRespuesta(texto), true);
		}, 300);
	});
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
