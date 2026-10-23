<?php
/**
 * Walker de navegación con submenús desplegables.
 * Usa display:none/flex via style inline para evitar conflictos con Tailwind !important.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class RAU_Nav_Walker extends Walker_Nav_Menu {

	function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( $depth === 0 ) {
			$output .= '<div class="rau-dropdown" style="display:none;position:absolute;left:0;top:100%;z-index:50;min-width:200px;flex-direction:column;border-radius:0.75rem;border:1px solid hsl(var(--border)/.7);background:hsl(var(--card)/.97);padding:6px 0;box-shadow:0 20px 25px -5px rgba(0,0,0,.3),0 8px 10px -6px rgba(0,0,0,.2);backdrop-filter:blur(16px);">';
		} else {
			$output .= '<div style="margin-left:0.5rem;margin-top:2px;display:flex;flex-direction:column;border-left:1px solid hsl(var(--border)/.5);padding-left:0.75rem;">';
		}
	}

	function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</div>';
	}

	function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$has_children = in_array( 'menu-item-has-children', $item->classes );
		$url          = esc_url( $item->url );
		$label        = esc_html( $item->title );

		if ( $depth === 0 ) {
			$output .= '<div class="rau-menu-item" style="position:relative;">';
			if ( $has_children ) {
				$output .= '<button type="button" aria-expanded="false" class="rau-trigger" style="display:inline-flex;align-items:center;gap:4px;border-radius:6px;padding:6px 12px;font-size:0.875rem;font-weight:500;color:hsl(var(--muted-foreground));background:none;border:none;cursor:pointer;transition:color .15s;"
					onmouseover="this.style.color=\'hsl(var(--primary))\'" onmouseout="if(!this.closest(\'.rau-menu-item\').querySelector(\'.rau-dropdown[style*=flex]\'))this.style.color=\'\'">'
					. $label
					. '<svg class="rau-chevron" style="width:14px;height:14px;transition:transform .2s;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>'
					. '</button>';
			} else {
				$output .= '<a href="' . $url . '" style="display:inline-flex;align-items:center;border-radius:6px;padding:6px 12px;font-size:0.875rem;font-weight:500;color:hsl(var(--muted-foreground));text-decoration:none;transition:color .15s;"
					onmouseover="this.style.color=\'hsl(var(--primary))\'" onmouseout="this.style.color=\'\'">' . $label . '</a>';
			}
		} elseif ( $depth === 1 ) {
			if ( $has_children ) {
				$output .= '<div>';
				$output .= '<span style="display:block;padding:4px 12px;font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:hsl(var(--muted-foreground)/.5);">' . $label . '</span>';
			} else {
				$output .= '<a href="' . $url . '" style="display:flex;align-items:center;border-radius:6px;padding:6px 12px;font-size:0.875rem;color:hsl(var(--muted-foreground));text-decoration:none;transition:background .15s,color .15s;"
					onmouseover="this.style.background=\'hsl(var(--primary)/.1)\';this.style.color=\'hsl(var(--primary))\'"
					onmouseout="this.style.background=\'\';this.style.color=\'\'">' . $label . '</a>';
			}
		} else {
			$output .= '<a href="' . $url . '" style="display:flex;align-items:center;border-radius:6px;padding:4px 8px;font-size:0.875rem;color:hsl(var(--muted-foreground));text-decoration:none;transition:color .15s;"
				onmouseover="this.style.color=\'hsl(var(--primary))\'" onmouseout="this.style.color=\'\'">' . $label . '</a>';
		}
	}

	function end_el( &$output, $item, $depth = 0, $args = null ) {
		$has_children = in_array( 'menu-item-has-children', $item->classes );
		if ( $depth === 0 ) {
			$output .= '</div>';
		} elseif ( $depth === 1 && $has_children ) {
			$output .= '</div>';
		}
	}
}

class RAU_Mobile_Nav_Walker extends Walker_Nav_Menu {

	function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '<div class="rau-mob-children" style="display:none;flex-direction:column;margin-left:0.75rem;border-left:1px solid hsl(var(--border)/.5);padding-left:0.75rem;margin-top:2px;">';
	}

	function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</div>';
	}

	function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$has_children = in_array( 'menu-item-has-children', $item->classes );
		$url          = esc_url( $item->url );
		$label        = esc_html( $item->title );

		if ( $has_children ) {
			$output .= '<div class="rau-mob-parent">';
			$output .= '<button type="button" class="rau-mob-trigger" style="display:flex;width:100%;align-items:center;justify-content:space-between;border-radius:6px;padding:8px 12px;font-size:0.875rem;font-weight:500;color:hsl(var(--muted-foreground));background:none;border:none;cursor:pointer;">'
				. '<span>' . $label . '</span>'
				. '<svg class="rau-chevron" style="width:14px;height:14px;transition:transform .2s;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>'
				. '</button>';
		} else {
			$output .= '<a href="' . $url . '" style="display:block;border-radius:6px;padding:8px 12px;font-size:0.875rem;color:hsl(var(--muted-foreground));text-decoration:none;">' . $label . '</a>';
		}
	}

	function end_el( &$output, $item, $depth = 0, $args = null ) {
		if ( in_array( 'menu-item-has-children', $item->classes ) ) {
			$output .= '</div>';
		}
	}
}

function rau_default_links() {
	return array(
		home_url( '/repetidores' ) => 'Repetidores',
		home_url( '/canales' )     => 'Canales',
		home_url( '/pulguero' )    => 'Pulguero',
		home_url( '/qsl' )         => 'Libro QSL',
	);
}

function rau_default_menu_fallback( $mobile = false ) {
	foreach ( rau_default_links() as $href => $label ) {
		echo '<a href="' . esc_url( $href ) . '" style="border-radius:6px;padding:6px 12px;font-size:0.875rem;color:hsl(var(--muted-foreground));text-decoration:none;">' . esc_html( $label ) . '</a>';
	}
}
