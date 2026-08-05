/**
 * Arrow controls for the homepage product strips.
 *
 * The strips are CSS scroll-snap tracks and are already usable by swipe, trackpad, scrollbar
 * and keyboard without this file. All it adds is a pair of buttons for mouse users, injected
 * from JS so they never appear as dead controls when scripting is off.
 */
(function () {
	'use strict';

	var ARROWS = {
		prev: 'm15 6-6 6 6 6',
		next: 'm9 6 6 6-6 6',
	};

	var strings = (window.simpleBanglaSlider && window.simpleBanglaSlider.i18n) || {};

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
		var track = slider.querySelector('.sb-products--slider');

		if (!track) {
			return;
		}

		var prev = makeButton('prev', strings.prev || 'Previous products');
		var next = makeButton('next', strings.next || 'Next products');

		slider.appendChild(prev);
		slider.appendChild(next);

		function step() {
			var card = track.querySelector('.sb-card');

			if (!card) {
				return track.clientWidth;
			}

			// Scroll by whole cards so the snap points always line up with the gutter.
			var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
			var perView = Math.max(1, Math.floor(track.clientWidth / (card.offsetWidth + gap)));

			return (card.offsetWidth + gap) * perView;
		}

		function update() {
			// A sub-pixel rounding slack, or the last card leaves "next" permanently enabled.
			var max = track.scrollWidth - track.clientWidth - 2;

			prev.disabled = track.scrollLeft <= 2;
			next.disabled = track.scrollLeft >= max;
		}

		prev.addEventListener('click', function () {
			track.scrollBy({ left: -step(), behavior: 'smooth' });
		});

		next.addEventListener('click', function () {
			track.scrollBy({ left: step(), behavior: 'smooth' });
		});

		track.addEventListener('scroll', update, { passive: true });
		window.addEventListener('resize', update);

		update();
	});
})();
