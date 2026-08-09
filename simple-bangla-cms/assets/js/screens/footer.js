/*
 * Footer and contact details.
 *
 * These are the `store` group of the theme's settings, and they reach further than the footer:
 * the phone number is also the mobile Call button, WhatsApp powers the floating button and the
 * "Order on WhatsApp" button on every product, and Messenger is the Chat button. The screen says
 * so per field rather than pretending each one is footer-only, because a store owner changing a
 * phone number is entitled to know what else moves.
 *
 * Nothing here is written out by hand. The cards name which settings they hold and the controls
 * come from the schema, so a contact field added to the theme appears without touching this file —
 * and if it is not named in a card, `layoutFields` puts it under "Other" instead of hiding it.
 */

import { html, Card, Fields } from '../ui.js';
import { useSettings, SettingsPage, SchemaField, layoutFields } from '../settings-form.js';

const CARDS = [
	{
		title: 'Contact',
		lead: 'Used across the footer, the menu drawer and the mobile bar.',
		keys: [
			'simple_bangla_contact_phone',
			'simple_bangla_contact_whatsapp',
			'simple_bangla_contact_email',
			'simple_bangla_contact_messenger',
			'simple_bangla_contact_address',
		],
	},
	{
		title: 'Social links',
		lead: 'The circular icons in the footer. Untick one to hide it without losing the address.',
		keys: [
			// URL then switch, so the pair reads as one decision rather than three addresses
			// followed by three unattached tickboxes.
			'simple_bangla_contact_facebook',
			'simple_bangla_contact_facebook_show',
			'simple_bangla_contact_instagram',
			'simple_bangla_contact_instagram_show',
			'simple_bangla_contact_youtube',
			'simple_bangla_contact_youtube_show',
		],
	},
	{
		title: 'Payment methods',
		lead: 'The strip of card and wallet logos along the bottom of the footer.',
		keys: [ 'simple_bangla_payment_strip' ],
	},
];

export function FooterSettings() {
	const state = useSettings( 'store' );
	const cards = layoutFields( state.fields, CARDS );

	return html`
		<${ SettingsPage }
			title="Footer"
			lead="Contact details, social links and the payment strip."
			state=${ state }
		>
			${ cards.map(
				( card ) => html`
					<${ Card } key=${ card.title } title=${ card.title } lead=${ card.lead }>
						<${ Fields }>
							${ card.fields.map(
								( field ) => html`
									<${ SchemaField }
										key=${ field.name }
										name=${ field.name }
										spec=${ field.spec }
										hint=${ field.spec.description }
										value=${ state.values[ field.name ] }
										onChange=${ ( value ) => state.set( field.name, value ) }
									/>
								`
							) }
						<//>
					<//>
				`
			) }
		<//>
	`;
}
