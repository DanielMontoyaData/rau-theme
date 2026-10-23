<?php
/**
 * Template Name: Canales de Comunicación
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<section class="mx-auto max-w-[96rem] px-4 sm:px-6 py-16">
	<p class="mb-3 font-mono text-xs uppercase tracking-[0.25em] text-primary">Conéctate con RAU</p>
	<h1 class="mb-2 max-w-2xl font-display text-3xl font-bold sm:text-4xl">Canales de Comunicación</h1>
	<p class="mb-10 max-w-2xl text-muted-foreground">Además de las repetidoras, así es como la comunidad de RAU se mantiene en contacto todos los días.</p>

	<div class="grid gap-4 md:grid-cols-2">

		<div class="rounded-xl border border-border/70 bg-card p-6">
			<div class="flex items-center gap-3">
				<svg class="h-6 w-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12c1.5-2 3.5-2 5 0s3.5 2 5 0 3.5-2 5 0 3.5 2 5 0"></path></svg>
				<h3 class="font-display text-lg font-semibold">Zello — Canal Oficial RAU.</h3>
			</div>
			<p class="mt-3 text-sm text-muted-foreground">Mantener tu información actualizada en Zello es clave para una comunicación clara, ordenada y efectiva.</p>
			<a href="https://on.zello.com/b7z3p2" target="_blank" rel="noreferrer" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">Unirme al canal <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"></path></svg></a>

			<div class="mt-5 rounded-lg border border-border/50 bg-background/40 p-4">
				<p class="font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Configuración recomendada</p>
				<ul class="mt-2 space-y-1.5 text-sm text-muted-foreground">
					<li><span class="text-foreground">Usuario:</span> tu indicativo</li>
					<li><span class="text-foreground">User Name:</span> tu nombre</li>
					<li><span class="text-foreground">Acerca de mí:</span> "MIEMBRO RADIOAFICIONADOS UNIDOS."</li>
					<li><span class="text-foreground">Ubicación:</span> grid + ciudad + departamento + país</li>
					<li><span class="text-foreground">Sitio web:</span> tu enlace de QRZ</li>
				</ul>
			</div>
		</div>

		<!-- Nodo Multiprotocolo RAU -->
		<div class="relative overflow-hidden rounded-2xl border border-primary/40 bg-gradient-to-br from-primary/10 via-card to-card p-6">
			<div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-primary/5 blur-2xl"></div>
			<div class="absolute -bottom-8 -left-4 h-24 w-24 rounded-full bg-primary/5 blur-2xl"></div>
			<div class="relative">
				<div class="mb-1 flex items-center gap-2">
					<span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span></span>
					<p class="font-mono text-[10px] uppercase tracking-[0.2em] text-primary">Voz IP · En línea</p>
				</div>
				<h3 class="mb-1 font-display text-xl font-bold">Nodo Multiprotocolo RAU</h3>
				<p class="mb-5 text-sm text-muted-foreground">Conéctate desde cualquier parte del mundo con radio o solo con la app — todos los protocolos enlazados.</p>

				<div class="space-y-2">

					<!-- DMR -->
					<div class="flex items-center gap-3 rounded-xl border border-border/60 bg-background/50 px-4 py-3">
						<!-- Badge DMR: rojo oscuro, letras blancas bold -->
						<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" style="background:linear-gradient(135deg,#b91c1c,#7f1d1d);box-shadow:0 0 12px rgba(185,28,28,0.4);">
							<svg viewBox="0 0 44 44" width="44" height="44" xmlns="http://www.w3.org/2000/svg">
								<!-- Onda circular DMR -->
								<circle cx="22" cy="22" r="8" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="1.5"/>
								<circle cx="22" cy="22" r="13" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="1"/>
								<text x="22" y="26" font-family="monospace" font-size="10" font-weight="900" fill="white" text-anchor="middle" letter-spacing="0.5">DMR</text>
							</svg>
						</div>
						<div class="flex-1 min-w-0">
							<p class="text-sm font-semibold">DMR</p>
							<p class="font-mono text-xs text-muted-foreground">Talkgroup 73241</p>
						</div>
						<span class="font-mono text-base font-bold" style="color:#ef4444;">TG 73241</span>
					</div>

					<!-- System Fusion C4FM -->
					<div class="flex items-center gap-3 rounded-xl border border-border/60 bg-background/50 px-4 py-3">
						<!-- Badge C4FM: azul Yaesu -->
						<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" style="background:linear-gradient(135deg,#1d4ed8,#1e3a8a);box-shadow:0 0 12px rgba(29,78,216,0.4);">
							<svg viewBox="0 0 44 44" width="44" height="44" xmlns="http://www.w3.org/2000/svg">
								<!-- Símbolo Y estilizado Yaesu -->
								<path d="M14 12 L22 22 L30 12" stroke="rgba(255,255,255,0.5)" stroke-width="2.5" fill="none" stroke-linecap="round"/>
								<line x1="22" y1="22" x2="22" y2="32" stroke="rgba(255,255,255,0.5)" stroke-width="2.5" stroke-linecap="round"/>
								<text x="22" y="27" font-family="monospace" font-size="8.5" font-weight="900" fill="white" text-anchor="middle" letter-spacing="0.3">C4FM</text>
							</svg>
						</div>
						<div class="flex-1 min-w-0">
							<p class="text-sm font-semibold">System Fusion C4FM</p>
							<p class="font-mono text-xs text-muted-foreground">YSF 73241 · CO-HK4RAU</p>
						</div>
						<span class="font-mono text-base font-bold" style="color:#60a5fa;">YSF</span>
					</div>

					<!-- AllStar Link -->
					<div class="flex items-center gap-3 rounded-xl border border-border/60 bg-background/50 px-4 py-3">
						<!-- Badge AllStar: naranja con estrella -->
						<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" style="background:linear-gradient(135deg,#c2410c,#7c2d12);box-shadow:0 0 12px rgba(194,65,12,0.4);">
							<svg viewBox="0 0 44 44" width="44" height="44" xmlns="http://www.w3.org/2000/svg">
								<!-- Estrella AllStar -->
								<polygon points="22,10 24.9,18.1 33.5,18.1 26.8,23.2 29.3,31.6 22,26.8 14.7,31.6 17.2,23.2 10.5,18.1 19.1,18.1" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.6)" stroke-width="1"/>
								<text x="22" y="27" font-family="monospace" font-size="7" font-weight="900" fill="white" text-anchor="middle" letter-spacing="0.2">ASL</text>
							</svg>
						</div>
						<div class="flex-1 min-w-0">
							<p class="text-sm font-semibold">AllStar Link</p>
							<p class="font-mono text-xs text-muted-foreground">Nodo 634510</p>
						</div>
						<span class="font-mono text-base font-bold" style="color:#fb923c;">634510</span>
					</div>

					<!-- EchoLink -->
					<div class="flex items-center gap-3 rounded-xl border border-border/60 bg-background/50 px-4 py-3">
						<!-- Badge EchoLink: verde con ondas -->
						<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" style="background:linear-gradient(135deg,#15803d,#14532d);box-shadow:0 0 12px rgba(21,128,61,0.4);">
							<svg viewBox="0 0 44 44" width="44" height="44" xmlns="http://www.w3.org/2000/svg">
								<!-- Ondas EchoLink -->
								<path d="M13 22 Q16 17 19 22 Q22 27 25 22 Q28 17 31 22" stroke="rgba(255,255,255,0.5)" stroke-width="2" fill="none" stroke-linecap="round"/>
								<path d="M10 26 Q14 19 18 26 Q22 33 26 26 Q30 19 34 26" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" fill="none" stroke-linecap="round"/>
								<text x="22" y="20" font-family="monospace" font-size="7.5" font-weight="900" fill="white" text-anchor="middle" letter-spacing="0.3">ECO</text>
							</svg>
						</div>
						<div class="flex-1 min-w-0">
							<p class="text-sm font-semibold">EchoLink</p>
							<p class="font-mono text-xs text-muted-foreground">Conferencia HK4RAU</p>
						</div>
						<span class="font-mono text-base font-bold" style="color:#4ade80;">CONF</span>
					</div>

				</div>

				<!-- Programa semanal -->
				<div class="mt-4 flex items-center gap-3 rounded-xl border border-primary/30 bg-primary/5 px-4 py-3">
					<svg class="h-5 w-5 shrink-0 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><circle cx="12" cy="16" r="1" fill="currentColor"/></svg>
					<div>
						<p class="text-xs font-semibold text-foreground">Programa semanal</p>
						<p class="font-mono text-xs text-muted-foreground">Martes 7:00 p.m. hora Colombia · 00:00 UTC</p>
					</div>
				</div>
			</div>
		</div>




	</div>

	<div class="mt-4 overflow-hidden rounded-xl border border-primary/30 bg-gradient-to-br from-primary/15 via-card to-card p-8 sm:p-10">
		<div class="flex flex-col items-center gap-5 text-center sm:flex-row sm:items-center sm:gap-8 sm:text-left">
			<div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-primary/15 ring-1 ring-primary/30">
				<svg class="h-9 w-9 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
					<path d="M2 12c1.5-2.5 3.5-2.5 5 0s3.5 2.5 5 0 3.5-2.5 5 0 3.5 2.5 5 0"></path>
					<path d="M2 17c1.5-2.5 3.5-2.5 5 0s3.5 2.5 5 0 3.5-2.5 5 0 3.5 2.5 5 0"></path>
				</svg>
			</div>
			<div class="flex-1">
				<span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 font-mono text-[11px] uppercase tracking-wider text-primary">
					<span class="relative flex h-1.5 w-1.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"></span><span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-primary"></span></span>
					En vivo
				</span>
				<h3 class="mt-2 font-display text-xl font-bold sm:text-2xl">Escucha HF en vivo — Receptor KiwiSDR de RAU</h3>
				<p class="mt-2 max-w-xl text-sm text-muted-foreground">¿Aún no tienes radio de HF? Sintoniza las bandas en tiempo real desde el receptor web de la asociación, sin instalar nada. Se abre en una pestaña aparte porque el receptor es un equipo físico, no una página de este sitio.</p>
			</div>
			<a href="<?php echo esc_url( home_url( '/receptor/' ) ); ?>" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-md bg-primary px-6 text-sm font-semibold text-primary-foreground shadow hover:bg-primary/90">
				Escuchar ahora
				<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"></path></svg>
			</a>
		</div>
	</div>

	<div class="mt-12 grid gap-4 md:grid-cols-2">
		<div class="rounded-xl border border-border/70 bg-card p-6 md:col-span-2">
			<div class="flex items-center gap-3">
				<svg class="h-6 w-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.93-6.63-.82-8.94 0-2.58.92-5.01 2.86-7.44 6.32"></path></svg>
				<h3 class="font-display text-lg font-semibold">Redes Sociales</h3>
			</div>
			<div class="mt-4 grid gap-3 sm:grid-cols-3">
				<a href="https://www.instagram.com/hk4rau" target="_blank" rel="noreferrer"
					class="flex items-center gap-3 rounded-lg border border-border/50 bg-background/40 px-4 py-2.5 text-sm transition-colors hover:border-primary/50 hover:text-primary">
					<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"></circle></svg>
					<span class="font-medium">Instagram</span>
					<span class="ml-auto font-mono text-xs text-muted-foreground">@hk4rau</span>
				</a>
				<a href="https://www.facebook.com/share/1Cahc9bBYr/" target="_blank" rel="noreferrer"
					class="flex items-center gap-3 rounded-lg border border-border/50 bg-background/40 px-4 py-2.5 text-sm transition-colors hover:border-primary/50 hover:text-primary">
					<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path></svg>
					<span class="font-medium">Facebook</span>
					<span class="ml-auto font-mono text-xs text-muted-foreground">RAU</span>
				</a>
				<a href="https://youtube.com/@radioaficionadosunidos" target="_blank" rel="noreferrer"
					class="flex items-center gap-3 rounded-lg border border-border/50 bg-background/40 px-4 py-2.5 text-sm transition-colors hover:border-primary/50 hover:text-primary">
					<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="3"></rect><path d="M11 10l4 2-4 2z" fill="currentColor" stroke="none"></path></svg>
					<span class="font-medium">YouTube</span>
					<span class="ml-auto font-mono text-xs text-muted-foreground">@radioaficionadosunidos</span>
				</a>
			</div>
		</div>

		<div class="rounded-xl border border-border/70 bg-card p-6">
			<h3 class="font-display text-lg font-semibold">WhatsApp de la Asociación</h3>
			<p class="mt-2 text-sm text-muted-foreground">Escríbenos directo para dudas, inscripción o soporte.</p>
			<a href="<?php echo esc_url( rau_whatsapp_url() ); ?>" target="_blank" rel="noreferrer" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">Abrir chat <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"></path></svg></a>
		</div>
		<div class="rounded-xl border border-border/70 bg-card p-6">
			<h3 class="font-display text-lg font-semibold">Perfil QRZ — HK4RAU</h3>
			<p class="mt-2 text-sm text-muted-foreground">Ficha oficial de la asociación en la base de datos de radioaficionados más usada del mundo.</p>
			<a href="https://www.qrz.com/db/HK4RAU" target="_blank" rel="noreferrer" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">Ver perfil QRZ <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"></path></svg></a>
		</div>
	</div>
</section>
<?php get_footer(); ?>
