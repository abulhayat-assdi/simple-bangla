/**
 * One-line product carousels.
 *
 * Each row shows a fixed number of cards — two on a phone, three on a desktop — and advances
 * one page every five seconds. Everything this file adds is an enhancement: the track is a CSS
 * scroll-snap row, so with JavaScript blocked a visitor can still swipe or drag through every
 * product in the category. The arrows, the dots and the autoplay are what get added on top.
 *
 * Autoplay stops whenever a person is engaging with the row — hovering, focusing, touching or
 * scrolling it — and while the row is off-screen or the tab is in the background.
 */
(function () {
	'use strict';

	var INTERVAL = 5000;

	var ARROWS = {
		prev: 'm15 6-6 6 6 6',
		next: 'm9 6 6 6-6 6',
	};

	var strings = (window.simpleBanglaSlider && window.simpleBanglaSlider.i18n) || {};
	var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

	function makeButton(direction, label) {
		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'sb-slider__nav sb-slider__nav--' + direction;
		button.innerHTML =
			'<span class="screen-reader-text">' +
			label +
			'</span>' +
			'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
			'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" ' +
			'focusable="false"><path d="' +
			ARROWS[direction] +
			'"/></svg>';
		return button;
	}

	document.querySelectorAll('[data-sb-slider]').forEach(function (slider) {
		var track = slider.querySelector('[data-sb-track]');

		if (!track) {
			return;
		}

		// Direct children, whatever they are — product cards in a row, image slides in the
		// hero. Both shapes get the same arrows, dots and autoplay.
		var cards = Array.prototype.filter.call(track.children, function (el) {
			return el.nodeType === 1;
		});

		if (!cards.length) {
			return;
		}

		var prev = makeButton('prev', strings.prev || 'Previous products');
		var next = makeButton('next', strings.next || 'Next products');
		var dots = document.createElement('div');

		dots.className = 'sb-slider__dots';
		dots.setAttribute('role', 'tablist');
		dots.setAttribute('aria-label', strings.pages || 'Product pages');

		slider.appendChild(prev);
		slider.appendChild(next);
		slider.appendChild(dots);

		var timer = null;
		var paused = false;
		var onScreen = true;

		/** Width of one card plus the gap that follows it. */
		function stride() {
			var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
			return cards[0].getBoundingClientRect().width + gap;
		}

		/** How many whole cards are visible at once. */
		function perView() {
			return Math.max(1, Math.round(track.clientWidth / stride()));
		}

		function pageCount() {
			return Math.max(1, Math.ceil(cards.length / perView()));
		}

		function currentPage() {
			return Math.round(track.scrollLeft / (stride() * perView()));
		}

		function goTo(page, smooth) {
			var pages = pageCount();
			var target = ((page % pages) + pages) % pages; // wrap in both directions

			track.scrollTo({
				left: target * stride() * perView(),
				behavior: smooth && !reducedMotion.matches ? 'smooth' : 'auto',
			});
		}

		function renderDots() {
			var pages = pageCount();

			// A single page is not a carousel; drop the whole chrome rather than show one dot.
			if (pages < 2) {
				dots.innerHTML = '';
				slider.classList.add('sb-slider--static');
				return;
			}

			slider.classList.remove('sb-slider--static');

			if (dots.children.length !== pages) {
				dots.innerHTML = '';

				for (var i = 0; i < pages; i++) {
					var dot = document.createElement('button');
					dot.type = 'button';
					dot.className = 'sb-slider__dot';
					dot.dataset.page = i;
					dot.innerHTML =
						'<span class="screen-reader-text">' +
						(strings.page || 'Page') +
						' ' +
						(i + 1) +
						'</span>';
					dots.appendChild(dot);
				}
			}

			var active = currentPage();

			Array.prototype.forEach.call(dots.children, function (dot, index) {
				dot.classList.toggle('is-active', index === active);
				dot.setAttribute('aria-current', index === active ? 'true' : 'false');
			});
		}

		function update() {
			// Sub-pixel slack, or the last card leaves "next" permanently enabled.
			var max = track.scrollWidth - track.clientWidth - 2;

			prev.disabled = track.scrollLeft <= 2;
			next.disabled = track.scrollLeft >= max;

			renderDots();
		}

		/* -- Autoplay -- */

		function stop() {
			window.clearInterval(timer);
			timer = null;
		}

		function start() {
			stop();

			if (paused || !onScreen || reducedMotion.matches || pageCount() < 2) {
				return;
			}

			timer = window.setInterval(function () {
				goTo(currentPage() + 1, true);
			}, INTERVAL);
		}

		function pause() {
			paused = true;
			stop();
		}

		function resume() {
			paused = false;
			start();
		}

		['pointerenter', 'focusin', 'touchstart'].forEach(function (event) {
			slider.addEventListener(event, pause, { passive: true });
		});

		['pointerleave', 'focusout', 'touchend', 'touchcancel'].forEach(function (event) {
			slider.addEventListener(event, resume, { passive: true });
		});

		// A row nobody can see should not be animating.
		new IntersectionObserver(
			function (entries) {
				onScreen = entries[0].isIntersecting;
				if (onScreen) {
					start();
				} else {
					stop();
				}
			},
			{ threshold: 0.2 }
		).observe(slider);

		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				stop();
			} else {
				start();
			}
		});

		/* -- Manual controls -- */

		prev.addEventListener('click', function () {
			goTo(currentPage() - 1, true);
		});

		next.addEventListener('click', function () {
			goTo(currentPage() + 1, true);
		});

		dots.addEventListener('click', function (event) {
			var dot = event.target.closest('.sb-slider__dot');

			if (dot) {
				goTo(parseInt(dot.dataset.page, 10), true);
			}
		});

		var scrollTimer = null;

		track.addEventListener(
			'scroll',
			function () {
				window.clearTimeout(scrollTimer);
				scrollTimer = window.setTimeout(update, 80);
			},
			{ passive: true }
		);

		window.addEventListener('resize', function () {
			update();
			start();
		});

		reducedMotion.addEventListener('change', start);

		update();
		start();
	});
})();
