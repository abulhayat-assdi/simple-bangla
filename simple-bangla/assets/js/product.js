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

/**
 * Reviews: the list of what other people said is the point of the tab, so the write-a-review
 * form starts folded behind a button instead of filling the panel.
 *
 * Progressive enhancement — with this script blocked the form is simply open, which is
 * WooCommerce's own behaviour and still perfectly usable.
 */
(function () {
	'use strict';

	var wrapper = document.getElementById('review_form_wrapper');

	if (!wrapper) {
		return;
	}

	var i18n = (window.simpleBanglaProduct || {}).i18n || {};

	// Someone who followed a #respond link, or whom WordPress bounced back here after a
	// validation error, is mid-review already — do not fold the form away from them.
	var startOpen = /^#(respond|review_form|comment)/.test(window.location.hash);

	var button = document.createElement('button');

	button.type = 'button';
	button.className = 'sb-btn sb-review-toggle';
	button.setAttribute('aria-controls', 'review_form_wrapper');

	function setOpen(open) {
		wrapper.hidden = !open;
		button.setAttribute('aria-expanded', open ? 'true' : 'false');
		button.textContent = open ? i18n.cancelReview || 'Cancel' : i18n.giveReview || 'Give Review';
	}

	wrapper.parentNode.insertBefore(button, wrapper);
	setOpen(startOpen);

	button.addEventListener('click', function () {
		var open = wrapper.hidden;

		setOpen(open);

		if (!open) {
			return;
		}

		var field = wrapper.querySelector('textarea, input:not([type="hidden"])');

		if (field) {
			field.focus();
		}
	});
})();

/**
 * Buy Now button: ensure sb_buy_now flag is active when clicking "অর্ডার করুন"
 * so both native POST and JS/AJAX form submissions carry sb_buy_now=1 to checkout.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var btn = event.target.closest('.sb-buy-now');
		if (!btn) {
			return;
		}

		var form = btn.closest('form.cart');
		if (!form) {
			return;
		}

		var hidden = form.querySelector('input[name="sb_buy_now"]');
		if (!hidden) {
			hidden = document.createElement('input');
			hidden.type = 'hidden';
			hidden.name = 'sb_buy_now';
			form.appendChild(hidden);
		}
		hidden.value = '1';
	}, true);

	if (typeof jQuery !== 'undefined') {
		jQuery(document.body).on('added_to_cart', function () {
			var form = document.querySelector('form.cart');
			if (form && form.querySelector('input[name="sb_buy_now"]') && form.querySelector('input[name="sb_buy_now"]').value === '1') {
				var checkoutUrl = (window.simpleBanglaProduct && window.simpleBanglaProduct.checkoutUrl) ? window.simpleBanglaProduct.checkoutUrl : '/checkout/';
				window.location.href = checkoutUrl;
			}
		});
	}
})();
