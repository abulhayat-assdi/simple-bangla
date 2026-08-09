/**
 * Checkout: the quantity stepper on each order-summary line.
 *
 * The product page does not ask how many any more, so this is the one place a customer sets it.
 * The click writes to the real cart over AJAX and then asks WooCommerce to re-render the order
 * review, which is what keeps the subtotal, coupons and delivery charge honest — nothing here
 * recalculates money in the browser.
 *
 * The listener is delegated on document because that review table is replaced wholesale on
 * every recalculation; a listener bound to a button would not survive the first update.
 */
(function () {
	'use strict';

	var config = window.simpleBanglaCheckout || {};

	if (!config.ajaxUrl || !config.nonce) {
		return;
	}

	var i18n = config.i18n || {};
	var busy = false;

	function refreshReview() {
		// jQuery is WooCommerce's own dependency on this page. Without it there is no checkout
		// script either, so a reload is the only honest way to show the new totals.
		if (!window.jQuery) {
			window.location.reload();
			return;
		}

		window.jQuery(document.body).trigger('update_checkout');
		window.jQuery(document.body).trigger('wc_fragment_refresh');
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-sb-qty-step]');

		if (!button || button.disabled) {
			return;
		}

		var stepper = button.closest('[data-sb-qty]');
		var value = stepper && stepper.querySelector('.sb-qty__value');

		if (!stepper || !value) {
			return;
		}

		event.preventDefault();

		// One change at a time: two quick taps would otherwise race, and the slower response
		// would win and silently undo the faster one.
		if (busy) {
			return;
		}

		var next = parseInt(value.textContent, 10) + parseInt(button.dataset.sbQtyStep, 10);

		if (!next || next < 1) {
			return;
		}

		busy = true;
		stepper.classList.add('is-busy');

		var body = new URLSearchParams();

		body.set('action', 'simple_bangla_cart_qty');
		body.set('nonce', config.nonce);
		body.set('key', stepper.dataset.sbKey);
		body.set('quantity', String(next));

		fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (!payload || !payload.success) {
					var message = payload && payload.data && payload.data.message;

					window.alert(message || i18n.error || 'Could not change the quantity.');
					return;
				}

				// Paint the new number straight away; update_checkout replaces the whole row a
				// moment later, and the two agree because the server has already accepted it.
				value.textContent = String(payload.data.quantity);
				refreshReview();
			})
			.catch(function () {
				window.alert(i18n.error || 'Could not change the quantity.');
			})
			.then(function () {
				busy = false;
				stepper.classList.remove('is-busy');
			});
	});
})();
