/**
 * Small site-wide behaviours: the back-to-top button.
 *
 * The button ships with the `hidden` attribute set, so it only ever appears once this file has
 * run and there is actually something to scroll back from.
 */
(function () {
	'use strict';

	var button = document.querySelector('[data-sb-to-top]');

	if (!button) {
		return;
	}

	// A sentinel one viewport down. Cheaper than a scroll handler and it never runs on the
	// main thread while the page is moving.
	var sentinel = document.createElement('div');
	sentinel.setAttribute('aria-hidden', 'true');
	sentinel.style.cssText = 'position:absolute;top:100vh;height:1px;width:1px;';
	document.body.appendChild(sentinel);

	new IntersectionObserver(function (entries) {
		button.hidden = entries[0].isIntersecting;
	}).observe(sentinel);

	button.addEventListener('click', function () {
		var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });

		// Send focus somewhere meaningful, or a keyboard user is left at the bottom of the
		// document with the page scrolled to the top.
		var skip = document.querySelector('.skip-link') || document.body;
		skip.focus({ preventScroll: true });
	});
})();
