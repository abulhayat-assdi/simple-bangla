/**
 * Header behaviour: stuck state, mobile drawer, submenu toggles, live product search.
 *
 * Progressive enhancement throughout. With this file blocked the header is still a working
 * navigation: the menu renders as plain nested lists, and the search box is a normal GET form.
 */
(function () {
	'use strict';

	var config = window.simpleBanglaHeader || {};
	var strings = config.i18n || {};

	/* -------------------------------------------------------------- *
	 * Stuck header
	 * -------------------------------------------------------------- */

	var header = document.getElementById('masthead');

	if (header) {
		// A zero-height sentinel above the header turns "have we scrolled past it" into an
		// intersection test, which the browser answers off the main thread.
		var sentinel = document.createElement('div');
		sentinel.setAttribute('aria-hidden', 'true');
		sentinel.style.cssText = 'position:absolute;top:0;height:1px;width:1px;';
		header.parentNode.insertBefore(sentinel, header);

		new IntersectionObserver(
			function (entries) {
				header.classList.toggle('is-stuck', !entries[0].isIntersecting);
			},
			{ threshold: 0 }
		).observe(sentinel);
	}

	/* -------------------------------------------------------------- *
	 * Mobile drawer
	 * -------------------------------------------------------------- */

	var drawer = document.getElementById('sb-drawer');
	var burger = document.querySelector('.sb-header__burger');

	function focusable(root) {
		return Array.prototype.filter.call(
			root.querySelectorAll('a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])'),
			function (el) {
				return el.offsetParent !== null;
			}
		);
	}

	function openDrawer() {
		drawer.hidden = false;

		// Read a layout property so the browser paints the closed state before the class
		// change starts the slide; without this the panel simply appears.
		void drawer.offsetWidth;

		drawer.classList.add('is-open');
		document.body.classList.add('sb-drawer-open');
		burger.setAttribute('aria-expanded', 'true');

		var first = focusable(drawer)[0];
		if (first) {
			first.focus();
		}
	}

	function closeDrawer() {
		if (drawer.hidden) {
			return;
		}

		drawer.classList.remove('is-open');
		document.body.classList.remove('sb-drawer-open');
		burger.setAttribute('aria-expanded', 'false');

		window.setTimeout(function () {
			drawer.hidden = true;
		}, 200);

		burger.focus();
	}

	if (drawer && burger) {
		burger.addEventListener('click', function () {
			if (drawer.hidden) {
				openDrawer();
			} else {
				closeDrawer();
			}
		});

		drawer.addEventListener('click', function (event) {
			if (event.target.closest('[data-sb-drawer-close]')) {
				closeDrawer();
			}
		});

		drawer.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				closeDrawer();
				return;
			}

			if (event.key !== 'Tab') {
				return;
			}

			var items = focusable(drawer.querySelector('.sb-drawer__panel'));

			if (!items.length) {
				return;
			}

			var first = items[0];
			var last = items[items.length - 1];

			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		});

		// A drawer left open across a resize into desktop would trap focus in a hidden panel.
		window.addEventListener('resize', function () {
			if (window.innerWidth >= 1024) {
				closeDrawer();
			}
		});
	}

	/* -------------------------------------------------------------- *
	 * Submenu toggles
	 * -------------------------------------------------------------- */

	document.addEventListener('click', function (event) {
		var toggle = event.target.closest('.sb-nav__toggle');

		if (!toggle) {
			// A click anywhere else closes any open desktop panel.
			document.querySelectorAll('.sb-nav .sb-nav__toggle[aria-expanded="true"]').forEach(function (open) {
				open.setAttribute('aria-expanded', 'false');
			});
			return;
		}

		event.preventDefault();

		var expanded = toggle.getAttribute('aria-expanded') === 'true';
		var siblings = toggle.closest('ul').querySelectorAll(':scope > li > .sb-nav__toggle');

		// Only one branch of a level open at a time, or the drawer becomes a wall of links.
		siblings.forEach(function (other) {
			if (other !== toggle) {
				other.setAttribute('aria-expanded', 'false');
			}
		});

		toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
	});

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape') {
			return;
		}

		document.querySelectorAll('.sb-nav .sb-nav__toggle[aria-expanded="true"]').forEach(function (open) {
			open.setAttribute('aria-expanded', 'false');
			open.focus();
		});
	});

	/* -------------------------------------------------------------- *
	 * Live search
	 * -------------------------------------------------------------- */

	if (!config.ajaxUrl || !config.nonce) {
		return;
	}

	document.querySelectorAll('[data-sb-search]').forEach(function (wrap) {
		var field = wrap.querySelector('.sb-search__field');
		var panel = wrap.querySelector('.sb-search__results');

		if (!field || !panel) {
			return;
		}

		var timer = null;
		var controller = null;
		var lastTerm = '';

		function hide() {
			panel.hidden = true;
			panel.innerHTML = '';
			field.setAttribute('aria-expanded', 'false');
		}

		function render(data) {
			panel.innerHTML = '';

			if (!data.results.length) {
				var empty = document.createElement('p');
				empty.className = 'sb-search__empty';
				empty.textContent = strings.noResults || 'No products matched.';
				panel.appendChild(empty);
			} else {
				data.results.forEach(function (item) {
					var link = document.createElement('a');
					link.className = 'sb-search__result';
					link.href = item.url;
					link.setAttribute('role', 'option');

					if (item.image) {
						var img = document.createElement('img');
						img.src = item.image;
						img.alt = '';
						img.width = 44;
						img.height = 44;
						img.loading = 'lazy';
						link.appendChild(img);
					}

					var text = document.createElement('span');

					var title = document.createElement('span');
					title.className = 'sb-search__result-title';
					title.textContent = item.title;
					text.appendChild(title);

					if (item.price) {
						var price = document.createElement('span');
						price.className = 'sb-search__result-price';
						price.textContent = item.price;
						text.appendChild(price);
					}

					link.appendChild(text);
					panel.appendChild(link);
				});

				var all = document.createElement('a');
				all.className = 'sb-search__all';
				all.href = data.allUrl;
				all.textContent = strings.viewAll || 'See all results';
				panel.appendChild(all);
			}

			panel.hidden = false;
			field.setAttribute('aria-expanded', 'true');
		}

		function search(term) {
			if (controller) {
				controller.abort();
			}

			controller = new AbortController();

			var body = new URLSearchParams();
			body.set('action', 'simple_bangla_search');
			body.set('nonce', config.nonce);
			body.set('term', term);

			fetch(config.ajaxUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				signal: controller.signal,
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					if (payload && payload.success) {
						render(payload.data);
					}
				})
				.catch(function () {
					// An aborted or failed suggestion request is not worth showing an error for;
					// the form underneath still submits normally.
				});
		}

		field.addEventListener('input', function () {
			var term = field.value.trim();

			window.clearTimeout(timer);

			if (term.length < 2) {
				hide();
				return;
			}

			if (term === lastTerm) {
				return;
			}

			lastTerm = term;
			timer = window.setTimeout(function () {
				search(term);
			}, 250);
		});

		field.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				hide();
				return;
			}

			if (panel.hidden || (event.key !== 'ArrowDown' && event.key !== 'ArrowUp')) {
				return;
			}

			event.preventDefault();

			var items = Array.prototype.slice.call(panel.querySelectorAll('a'));
			var index = items.indexOf(document.activeElement);
			var next = event.key === 'ArrowDown' ? index + 1 : index - 1;

			if (next < 0) {
				field.focus();
				return;
			}

			if (items[next]) {
				items[next].focus();
			}
		});

		document.addEventListener('click', function (event) {
			if (!wrap.contains(event.target)) {
				hide();
			}
		});

		wrap.addEventListener('focusout', function (event) {
			if (!wrap.contains(event.relatedTarget)) {
				hide();
			}
		});
	});
})();
