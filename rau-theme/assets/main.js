document.addEventListener('DOMContentLoaded', function () {

	// Cambio de tema claro / oscuro — se registra primero, no depende de nada más
	function rauCurrentTheme() {
		return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
	}
	function rauApplyTheme(theme) {
		if (theme === 'light') {
			document.documentElement.setAttribute('data-theme', 'light');
		} else {
			document.documentElement.removeAttribute('data-theme');
		}
		try { localStorage.setItem('rau-theme', theme); } catch (e) {}
		if (window.rauMap && window.rauTileLayer) {
			window.rauMap.removeLayer(window.rauTileLayer);
			window.rauTileLayer = L.tileLayer(rauTileUrlFor(theme), { attribution: '&copy; OpenStreetMap &copy; CARTO', maxZoom: 18 }).addTo(window.rauMap);
		}
	}
	['rauThemeToggle', 'rauThemeToggleMobile'].forEach(function (id) {
		var btn = document.getElementById(id);
		if (btn) {
			btn.addEventListener('click', function () {
				rauApplyTheme(rauCurrentTheme() === 'light' ? 'dark' : 'light');
			});
		}
	});

	// Caja de búsqueda desplegable
	var searchToggle = document.getElementById('rauSearchToggle');
	var searchForm = document.getElementById('rauSearchForm');
	if (searchToggle && searchForm) {
		searchToggle.setAttribute('aria-expanded', 'false');
		searchToggle.addEventListener('click', function () {
			var isOpen = searchForm.classList.toggle('hidden') === false;
			searchToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			if (isOpen) {
				var input = searchForm.querySelector('input[name="s"]');
				if (input) input.focus();
			}
		});
	}

	var toggle = document.getElementById('rauMenuToggle');
	var nav = document.getElementById('rauMobileNav');
	var iconMenu = document.getElementById('rauIconMenu');
	var iconClose = document.getElementById('rauIconClose');

	function rauCloseMobileNav() {
		if (!nav) return;
		nav.classList.add('hidden');
		if (iconMenu) iconMenu.classList.remove('hidden');
		if (iconClose) iconClose.classList.add('hidden');
		if (toggle) toggle.setAttribute('aria-expanded', 'false');
	}

	if (toggle && nav) {
		toggle.setAttribute('aria-expanded', 'false');
		toggle.addEventListener('click', function () {
			var isOpen = !nav.classList.contains('hidden');
			nav.classList.toggle('hidden');
			iconMenu.classList.toggle('hidden');
			iconClose.classList.toggle('hidden');
			toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
		});

		nav.querySelectorAll('a').forEach(function (link) {
			link.addEventListener('click', rauCloseMobileNav);
		});
	}

	// Tecla Escape cierra el menú móvil y la búsqueda
	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') return;
		if (nav && !nav.classList.contains('hidden')) {
			rauCloseMobileNav();
			if (toggle) toggle.focus();
		}
		if (searchForm && !searchForm.classList.contains('hidden')) {
			searchForm.classList.add('hidden');
			if (searchToggle) { searchToggle.setAttribute('aria-expanded', 'false'); searchToggle.focus(); }
		}
	});

	// Clic afuera cierra la búsqueda desplegable
	document.addEventListener('click', function (e) {
		if (searchForm && !searchForm.classList.contains('hidden')) {
			if (!searchForm.contains(e.target) && e.target !== searchToggle && !searchToggle.contains(e.target)) {
				searchForm.classList.add('hidden');
				searchToggle.setAttribute('aria-expanded', 'false');
			}
		}
	});

	function rauTileUrlFor(theme) {
		return theme === 'light'
			? 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png'
			: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
	}
	var mapEl = document.getElementById('repeaterMap');
	if (mapEl && typeof L !== 'undefined' && window.RAU_MAP_POINTS && RAU_MAP_POINTS.length) {
		mapEl.classList.remove('hidden');
		window.rauMap = L.map('repeaterMap', { scrollWheelZoom: false }).setView([RAU_MAP_POINTS[0].lat, RAU_MAP_POINTS[0].lng], 10);
		window.rauTileLayer = L.tileLayer(rauTileUrlFor(rauCurrentTheme()), {
			attribution: '&copy; OpenStreetMap &copy; CARTO',
			maxZoom: 18
		}).addTo(window.rauMap);
		RAU_MAP_POINTS.forEach(function (r) {
			L.circleMarker([r.lat, r.lng], { radius: 8, color: '#22d9f4', fillColor: '#22d9f4', fillOpacity: 0.85, weight: 2 })
				.addTo(window.rauMap)
				.bindPopup('<strong>' + r.name + '</strong><br>' + (r.callsign || '') + '<br>' + (r.freq || '') + '<br>' + (r.location || ''));
		});
	}
});

/* ── Dropdowns de navegación (desktop hover + click, móvil click) ── */
document.addEventListener('DOMContentLoaded', function () {

	/* Desktop */
	document.querySelectorAll('.rau-trigger').forEach(function (btn) {
		var item  = btn.closest('.rau-menu-item');
		var panel = item ? item.querySelector('.rau-dropdown') : null;
		if (!panel) return;

		function openPanel()  { panel.classList.add('open');    btn.setAttribute('aria-expanded', 'true');  }
		function closePanel() { panel.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); }

		item.addEventListener('mouseenter', openPanel);
		item.addEventListener('mouseleave', closePanel);

		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			panel.classList.contains('open') ? closePanel() : openPanel();
		});

		btn.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { closePanel(); btn.focus(); }
		});
	});

	/* Cerrar al click fuera */
	document.addEventListener('click', function () {
		document.querySelectorAll('.rau-dropdown.open').forEach(function (p) {
			p.classList.remove('open');
			var b = p.closest('.rau-menu-item') ? p.closest('.rau-menu-item').querySelector('.rau-trigger') : null;
			if (b) b.setAttribute('aria-expanded', 'false');
		});
	});

	/* Móvil */
	document.querySelectorAll('.rau-mob-trigger').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var parent = btn.closest('.rau-mob-parent');
			if (!parent) return;
			var isOpen = parent.classList.contains('open');
			var siblings = parent.parentElement ? parent.parentElement.querySelectorAll('.rau-mob-parent.open') : [];
			siblings.forEach(function (s) {
				s.classList.remove('open');
				var sb = s.querySelector('.rau-mob-trigger');
				if (sb) sb.setAttribute('aria-expanded', 'false');
			});
			if (!isOpen) {
				parent.classList.add('open');
				btn.setAttribute('aria-expanded', 'true');
			}
		});
	});

});

/* ── Badge de YouTube en vivo (revisa cada 5 min, sin API key) ── */
(function () {
	var badge = document.getElementById('rauYtLiveBadge');
	if (!badge || typeof RAU_AJAX === 'undefined') return;

	function checkLive() {
		fetch(RAU_AJAX.url + '?action=rau_yt_live', { cache: 'no-store' })
			.then(function (r) { return r.json(); })
			.then(function (json) {
				if (json.success && json.data && json.data.live) {
					badge.href = json.data.url;
					badge.classList.remove('hidden');
				} else {
					badge.classList.add('hidden');
				}
			})
			.catch(function () {});
	}
	checkLive();
	setInterval(checkLive, 5 * 60 * 1000);
})();

/* ── Animación de entrada al hacer scroll (cualquier elemento con .rau-animar) ── */
(function () {
	if (!('IntersectionObserver' in window)) return;
	var obs = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (entry.isIntersecting) {
				entry.target.classList.add('rau-visible');
				obs.unobserve(entry.target);
			}
		});
	}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
	document.querySelectorAll('.rau-animar').forEach(function (el) { obs.observe(el); });
})();
