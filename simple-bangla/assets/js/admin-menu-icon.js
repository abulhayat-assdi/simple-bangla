/**
 * Menu icon picker for Appearance → Menus.
 *
 * Uses core's wp.media modal. Delegated from document because menu items are added,
 * removed and re-sorted by the nav-menus screen long after this file runs.
 */
(function () {
	'use strict';

	var strings = window.simpleBanglaMenuIcon || {};
	var frame = null;
	var activeField = null;

	function openPicker(field) {
		activeField = field;

		if (!frame) {
			frame = window.wp.media({
				title: strings.title || 'Choose a menu icon',
				button: { text: strings.button || 'Use this icon' },
				library: { type: 'image' },
				multiple: false,
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var preview = activeField.querySelector('.sb-menu-icon__preview');
				var value = activeField.querySelector('.sb-menu-icon__value');
				var remove = activeField.querySelector('.sb-menu-icon__remove');
				var thumb = (attachment.sizes && attachment.sizes.thumbnail) || attachment;

				value.value = attachment.id;
				preview.innerHTML = '';

				var img = document.createElement('img');
				img.src = thumb.url;
				img.width = 40;
				img.height = 40;
				img.alt = '';
				preview.appendChild(img);

				remove.hidden = false;

				// The screen only enables Save once it sees a change event from inside the item.
				value.dispatchEvent(new Event('change', { bubbles: true }));
			});
		}

		frame.open();
	}

	document.addEventListener('click', function (event) {
		var select = event.target.closest('.sb-menu-icon__select');

		if (select) {
			event.preventDefault();
			openPicker(select.closest('.sb-menu-icon'));
			return;
		}

		var remove = event.target.closest('.sb-menu-icon__remove');

		if (!remove) {
			return;
		}

		event.preventDefault();

		var field = remove.closest('.sb-menu-icon');
		var value = field.querySelector('.sb-menu-icon__value');

		value.value = '';
		field.querySelector('.sb-menu-icon__preview').innerHTML = '';
		remove.hidden = true;
		value.dispatchEvent(new Event('change', { bubbles: true }));
	});
})();
