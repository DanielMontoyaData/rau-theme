<?php
/**
 * Header — traducción literal de components/Navbar.jsx
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.pexels.com">
<link rel="icon" href="<?php echo esc_url( get_template_directory_uri() . '/favicon.ico' ); ?>" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( get_template_directory_uri() . '/assets/images/favicon-32x32.png' ); ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url( get_template_directory_uri() . '/assets/images/favicon-16x16.png' ); ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( get_template_directory_uri() . '/assets/images/apple-touch-icon.png' ); ?>">
<link rel="manifest" href="<?php echo esc_url( get_template_directory_uri() . '/manifest.json' ); ?>">
<?php wp_head(); ?>
<script>
(function(){
  try {
    var saved = localStorage.getItem('rau-theme');
    if (saved === 'light') document.documentElement.setAttribute('data-theme', 'light');
  } catch(e) {}
  try {
    if (sessionStorage.getItem('rau-splash-seen')) {
      document.documentElement.classList.add('rau-splash-skip');
    }
  } catch(e) {}
})();
</script>
</head>
<body <?php body_class( 'min-h-screen bg-background text-foreground' ); ?>>
<?php wp_body_open(); ?>

<?php if ( is_front_page() ) : ?>
<div id="rauSplash" class="rau-splash" aria-hidden="true">
	<div class="rau-splash-inner">
		<picture>
			<source srcset="<?php echo esc_url( get_template_directory_uri() . '/assets/images/rau-badge.webp' ); ?>" type="image/webp">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/rau-badge.png' ); ?>" alt="" width="640" height="591" class="rau-splash-badge" fetchpriority="high" decoding="async">
		</picture>
		<p class="rau-splash-text">Comunidad en el aire · 73</p>
	</div>
</div>
<script>
(function(){
	var splash = document.getElementById('rauSplash');
	if (!splash) return;
	if (document.documentElement.classList.contains('rau-splash-skip')) {
		splash.remove();
		return;
	}
	document.documentElement.classList.add('rau-splash-active');
	function finish(){
		try { sessionStorage.setItem('rau-splash-seen', '1'); } catch(e) {}
		document.documentElement.classList.remove('rau-splash-active');
		if (splash.parentNode) splash.parentNode.removeChild(splash);
	}
	splash.addEventListener('click', finish);
	document.addEventListener('keydown', function(e){ if (e.key === 'Escape') finish(); }, { once: true });
	window.setTimeout(finish, 1600);
})();
</script>
<?php endif; ?>

<a href="#main-content" class="skip-link">Saltar al contenido</a>

<header class="sticky top-0 z-50 border-b border-border/60 bg-background/80 backdrop-blur-xl">
	<div class="mx-auto flex h-16 max-w-[96rem] items-center justify-between px-4 sm:px-6">

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2.5">
			<span class="flex h-9 w-9 shrink-0 items-center justify-center">
				<?php rau_logo_html( 'h-9 w-9' ); ?>
			</span>
			<span class="font-display text-lg font-bold tracking-tight text-foreground"><?php bloginfo( 'name' ); ?></span>
			<span class="hidden font-mono text-[10px] uppercase tracking-widest text-muted-foreground sm:inline">Radioaficionados Unidos</span>
		</a>

		<nav class="hidden items-center gap-1 lg:flex">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'walker'         => new RAU_Nav_Walker(),
				) );
			} else {
				rau_default_menu_fallback();
			}
			?>
		</nav>

		<div class="hidden items-center gap-2 lg:flex">
			<?php if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) : ?>
				<a href="<?php echo esc_url( admin_url() ); ?>" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md border border-primary/40 px-3 text-xs font-medium text-primary shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
					<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"></path></svg> Panel
				</a>
			<?php endif; ?>

			<?php if ( is_user_logged_in() ) : $u = wp_get_current_user(); ?>
				<span class="font-mono text-xs text-muted-foreground"><?php echo esc_html( $u->display_name ); ?></span>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md px-3 text-xs font-medium transition-colors hover:bg-accent hover:text-accent-foreground">
					<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"></path></svg> Salir
				</a>
			<?php endif; ?>

			<button type="button" class="theme-toggle" id="rauSearchToggle" aria-label="Buscar" aria-expanded="false" aria-controls="rauSearchForm" title="Buscar">
				<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
			</button>

			<button type="button" class="theme-toggle" id="rauThemeToggle" aria-label="Cambiar a modo claro u oscuro" title="Cambiar tema">
				<svg class="icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"></path></svg>
				<svg class="icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
			</button>
		</div>

		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" id="rauSearchForm" class="absolute inset-x-0 top-full hidden border-t border-border/60 bg-background/95 px-4 py-3 backdrop-blur-xl sm:px-6">
			<div class="mx-auto flex max-w-[96rem] items-center gap-2">
				<input type="search" name="s" aria-label="Buscar en el sitio" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Buscar en el sitio…" class="h-10 flex-1 rounded-md border border-border bg-card px-3 text-sm outline-none focus-visible:border-primary" autocomplete="off">
				<button type="submit" class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">Buscar</button>
			</div>
		</form>

		<a id="rauYtLiveBadge" href="#" target="_blank" rel="noreferrer" class="hidden -mr-1 h-8 animate-pulse inline-flex items-center justify-center gap-1.5 rounded-full bg-rose-500 px-3 text-xs font-bold text-white shadow-sm">
			<span class="h-1.5 w-1.5 rounded-full bg-white"></span> EN VIVO
		</a>

		<button id="rauMenuToggle" class="-mr-1.5 flex h-11 w-11 items-center justify-center rounded-md text-foreground lg:hidden" aria-label="Menu" aria-expanded="false" aria-controls="rauMobileNav">
			<svg id="rauIconMenu" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
			<svg id="rauIconClose" class="hidden h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"></path></svg>
		</button>
	</div>

	<div id="rauMobileNav" class="hidden border-t border-border/60 bg-background lg:hidden">
		<div class="flex flex-col gap-1 px-4 py-3">
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="mb-2 flex items-center gap-2">
				<input type="search" name="s" aria-label="Buscar en el sitio" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Buscar en el sitio…" class="h-10 flex-1 rounded-md border border-border bg-card px-3 text-sm outline-none focus-visible:border-primary" autocomplete="off">
				<button type="submit" class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground">Ir</button>
			</form>
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'walker'         => new RAU_Mobile_Nav_Walker(),
				) );
			} else {
				rau_default_menu_fallback( true );
			}
			?>
			<div class="mt-2 flex items-center gap-2 border-t border-border/60 pt-3">
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="inline-flex h-8 items-center justify-center rounded-md px-3 text-xs font-medium hover:bg-accent">Salir</a>
				<?php endif; ?>
				<button type="button" class="theme-toggle" id="rauThemeToggleMobile" aria-label="Cambiar a modo claro u oscuro" title="Cambiar tema">
					<svg class="icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"></path></svg>
					<svg class="icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
				</button>
			</div>
		</div>
	</div>
</header>
<main id="main-content">
