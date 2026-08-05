/**
 * Shop archive: filtering without a reload, incremental loading, and the mobile filter panel.
 *
 * Every path here has a working non-JS equivalent already in the markup — the filter panel is
 * a GET form, "load more" is a link to page 2, and the numbered pagination is still rendered.
 * This file only replaces the navigation with a fetch and a swap.
 */
(function () {
	'use strict';

	var config = window.simpleBanglaShop || {};
	var strings = config.i18n || {};

	var results = document.querySelector('[data-sb-results]');

	if (!results) {
		return;
	}

	// Announce that the enhanced behaviour is live. shop.css keys the collapsible filter panel
	// and the hidden numbered pagination off this, so neither is applied when JS never runs.
	document.documentElement.classList.add('js-sb-shop');

	/* -------------------------------------------------------------- *
	 * Mobile filter panel
	 * -------------------------------------------------------------- */

	var toggle = document.querySelector('.sb-shop__filter-toggle');
	var sidebar = document.getElementById('sb-shop-filters');

	if (toggle && sidebar) {
		toggle.addEventListener('click', function () {
			var open = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
			sidebar.classList.toggle('is-open', !open);
		});
	}

	/* -------------------------------------------------------------- *
	 * Fetching
	 * -------------------------------------------------------------- */

	var pending = null;

	function fragmentUrl(url) {
		var target = new URL(url, window.location.origin);
		target.searchParams.set('sb_ajax', '1');
		return target.toString();
	}

	function displayUrl(url) {
		var target = new URL(url, window.location.origin);
		target.searchParams.delete('sb_ajax');
		return target.toString();
	}

	function fetchFragment(url) {
		if (pending) {
			pending.abort();
		}

		pending = new AbortController();

		return fetch(fragmentUrl(url), {
			credentials: 'same-origin',
			signal: pending.signal,
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('HTTP ' + response.status);
			}
			return response.text();
		});
	}

	function parse(html) {
		var doc = document.implementation.createHTMLDocument('');
		doc.body.innerHTML = html;
		return doc.body;
	}

	function showError() {
		var note = results.querySelector('.sb-shop__error') || document.createElement('p');
		note.className = 'sb-shop__error';
		note.setAttribute('role', 'status');
		note.textContent = strings.error || 'Something went wrong. Please try again.';
		results.appendChild(note);
	}

	/* -------------------------------------------------------------- *
	 * Filtering
	 * -------------------------------------------------------------- */

	function replaceResults(url) {
		results.setAttribute('aria-busy', 'true');

		fetchFragment(url)
			.then(function (html) {
				results.innerHTML = html;
				results.removeAttribute('aria-busy');
				window.history.pushState({}, '', displayUrl(url));

				// The grid has just moved under the toolbar; put the reader back at its top.
				results.scrollIntoView({ block: 'start', behavior: 'smooth' });
			})
			.catch(function (error) {
				if (error.name === 'AbortError') {
					return;
				}
				results.removeAttribute('aria-busy');
				showError();
			});
	}

	var form = document.querySelector('[data-sb-filters]');

	if (form) {
		form.addEventListener('submit', function (event) {
			event.preventDefault();

			var data = new FormData(form);
			var target = new URL(form.action, window.location.origin);

			data.forEach(function (value, key) {
				if (String(value).trim() !== '') {
					target.searchParams.set(key, value);
				}
			});

			replaceResults(target.toString());
		});
	}

	// WooCommerce's ordering dropdown submits its own form; catch it at the document level
	// so this keeps working if the markup around it changes.
	document.addEventListener('submit', function (event) {
		if (event.target.classList.contains('woocommerce-ordering')) {
			event.preventDefault();

			var data = new FormData(event.target);
			var target = new URL(window.location.href);

			data.forEach(function (value, key) {
				target.searchParams.set(key, value);
			});

			target.searchParams.delete('paged');
			replaceResults(target.toString());
		}
	});

	/* -------------------------------------------------------------- *
	 * Load more
	 * -------------------------------------------------------------- */

	document.addEventListener('click', function (event) {
		var more = event.target.closest('[data-sb-next]');

		if (!more || !results.contains(more)) {
			return;
		}

		event.preventDefault();

		var url = more.href;
		var original = more.textContent;

		more.classList.add('is-busy');
		more.textContent = strings.loading || 'Loading…';

		fetchFragment(url)
			.then(function (html) {
				var body = parse(html);
				var grid = results.querySelector('.sb-products');
				var incoming = body.querySelector('.sb-products');

				if (grid && incoming) {
					while (incoming.firstElementChild) {
						grid.appendChild(incoming.firstElementChild);
					}
				}

				var nextLink = body.querySelector('[data-sb-next]');
				var wrapper = more.closest('.sb-shop__more');

				if (nextLink && wrapper) {
					more.href = nextLink.href;
					more.textContent = original;
					more.classList.remove('is-busy');
				} else if (wrapper) {
					// Nothing left to fetch: say so once, then take the control away.
					wrapper.textContent = strings.noMore || 'That is everything.';
					wrapper.classList.add('sb-shop__more--done');
				}

				// Deep-link the last page the visitor actually pulled in.
				window.history.replaceState({}, '', displayUrl(url));
			})
			.catch(function (error) {
				more.classList.remove('is-busy');
				more.textContent = original;

				if (error.name !== 'AbortError') {
					showError();
				}
			});
	});

	/* -------------------------------------------------------------- *
	 * Back button
	 * -------------------------------------------------------------- */

	window.addEventListener('popstate', function () {
		// The URL has already changed; re-fetch whatever it now points at.
		fetchFragment(window.location.href)
			.then(function (html) {
				results.innerHTML = html;
			})
			.catch(function () {
				// If the fragment cannot be fetched, a full reload is the honest fallback.
				window.location.reload();
			});
	});
})();
