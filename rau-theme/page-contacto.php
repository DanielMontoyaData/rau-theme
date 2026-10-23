<?php
/**
 * Template Name: Contacto
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$estado = isset( $_GET['contacto'] ) ? sanitize_key( $_GET['contacto'] ) : '';
$mensajes = array(
	'ok'       => array( 'ok',   '¡Gracias por escribirnos! Te responderemos pronto a tu correo.' ),
	'invalido' => array( 'err',  'Revisa los campos — nombre, correo válido y mensaje son obligatorios.' ),
	'despacio' => array( 'warn', 'Espera un par de minutos antes de enviar otro mensaje.' ),
	'error'    => array( 'err',  'No se pudo enviar el mensaje. Intenta de nuevo o escríbenos por WhatsApp.' ),
);
?>

<div class="mx-auto max-w-[96rem] px-4 sm:px-6 py-12">

	<div class="mb-10">
		<p class="mb-2 font-mono text-[11px] uppercase tracking-[0.25em] text-primary">Hablemos</p>
		<h1 class="font-display text-3xl font-bold sm:text-4xl">Contacto</h1>
		<p class="mt-2 max-w-xl text-muted-foreground">¿Consulta, propuesta de alianza, prensa, o simplemente quieres saludar? Escríbenos.</p>
	</div>

	<?php if ( $estado && isset( $mensajes[ $estado ] ) ) :
		list( $tipo, $texto ) = $mensajes[ $estado ];
		$cls = array( 'ok' => 'border-emerald-400/40 bg-emerald-400/10 text-emerald-400', 'warn' => 'border-amber-400/40 bg-amber-400/10 text-amber-400', 'err' => 'border-rose-400/40 bg-rose-400/10 text-rose-400' );
	?>
	<div class="mb-8 rounded-xl border px-5 py-3.5 text-sm font-medium <?php echo esc_attr( $cls[ $tipo ] ); ?>" role="status"><?php echo esc_html( $texto ); ?></div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-5">
		<div class="lg:col-span-3">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-4">
					<input type="hidden" name="action" value="rau_contacto">
					<?php wp_nonce_field( 'rau_contacto', 'rau_contacto_nonce' ); ?>
					<div class="hidden" aria-hidden="true"><label>Sitio web <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>

					<div class="grid gap-4 sm:grid-cols-2">
						<div>
							<label for="ct-nombre" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Nombre</label>
							<input type="text" id="ct-nombre" name="nombre" required maxlength="80" class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						</div>
						<div>
							<label for="ct-correo" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Correo</label>
							<input type="email" id="ct-correo" name="correo" required class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
						</div>
					</div>

					<div>
						<label for="ct-motivo" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Motivo</label>
						<select id="ct-motivo" name="motivo" class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
							<option value="general">Consulta general</option>
							<option value="afiliacion">Afiliación</option>
							<option value="prensa">Prensa / medios</option>
							<option value="alianza">Propuesta de alianza</option>
							<option value="otro">Otro</option>
						</select>
					</div>

					<div>
						<label for="ct-mensaje" class="mb-1 block font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Mensaje</label>
						<textarea id="ct-mensaje" name="mensaje" required rows="5" maxlength="1000" class="w-full resize-none rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"></textarea>
					</div>

					<button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-5 py-3 text-sm font-bold text-primary-foreground hover:bg-primary/90">Enviar mensaje</button>
				</form>
			</div>
		</div>

		<div class="lg:col-span-2">
			<div class="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
				<h2 class="mb-4 font-display text-lg font-bold">Otras formas de contactarnos</h2>
				<div class="space-y-3 text-sm">
					<a href="mailto:info@hk4rau.com" class="flex items-center gap-2 text-muted-foreground hover:text-primary">✉️ info@hk4rau.com</a>
					<a href="<?php echo esc_url( rau_whatsapp_url() ); ?>" target="_blank" rel="noreferrer" class="flex items-center gap-2 text-muted-foreground hover:text-primary">💬 WhatsApp</a>
					<p class="flex items-center gap-2 text-muted-foreground">📍 Medellín, Colombia · Locator FJ26</p>
				</div>
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>
