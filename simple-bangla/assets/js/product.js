/**
 * Product gallery: swap the main image when a thumbnail is chosen.
 *
 * Each thumbnail is a real anchor to the full-size file, so with this script blocked the
 * gallery is still a working set of links to the images.
 */
(function () {
	'use strict';

	document.querySelectorAll('[data-sb-gallery]').forEach(function (gallery) {
		var stage = gallery.querySelector('[data-sb-stage]');
		var thumbs = gallery.querySelectorAll('.sb-gallery__thumb');

		if (!stage || !thumbs.length) {
			return;
		}

		thumbs.forEach(function (thumb) {
			thumb.addEventListener('click', function (event) {
				event.preventDefault();

				stage.src = thumb.dataset.sbFull;
				stage.srcset = thumb.dataset.sbSrcset || '';

				// The hero was marked high priority for the first paint; later swaps are
				// ordinary images and should not jump the queue.
				stage.removeAttribute('fetchpriority');

				thumbs.forEach(function (other) {
					other.classList.toggle('is-active', other === thumb);
					other.removeAttribute('aria-current');
				});

				thumb.setAttribute('aria-current', 'true');
			});
		});

		// Arrow keys move along the thumbnail row, which is what a listbox-like row implies.
		gallery.addEventListener('keydown', function (event) {
			if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
				return;
			}

			var list = Array.prototype.slice.call(thumbs);
			var index = list.indexOf(document.activeElement);

			if (index === -1) {
				return;
			}

			event.preventDefault();

			var next = list[event.key === 'ArrowRight' ? index + 1 : index - 1];

			if (next) {
				next.focus();
				next.click();
			}
		});
	});
})();
