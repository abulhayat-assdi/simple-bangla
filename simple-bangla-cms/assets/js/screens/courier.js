/*
 * Courier credentials.
 *
 * It sits on the Settings screen but saves itself, rather than joining that screen's shared Save
 * button. Two reasons, and both are about the secrets: a blank secret field here means "leave what
 * is stored alone" rather than "clear it", which is the opposite of what every other field on that
 * screen means; and the endpoint is the plugin's own rather than `/settings` or `wc/v3`, so folding
 * it into that screen's already two-way save would have made a third partial-failure case to
 * explain. A card that owns its own button has none of that.
 *
 * The whole registry — which couriers exist, which fields each needs, which of them are secret and
 * which are only for reading a delivery record — comes from the server, so adding a courier in
 * `inc/courier.php` puts it on this screen with no change here.
 */

import { html, useState, useEffect, Card, Field, Fields, Select, Badge, ErrorBox, toast } from '../ui.js';
import { api } from '../api.js';

export function CourierCard() {
	const [ data, setData ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	// Only what has been typed this session. A field the owner never touched is not sent at all, so
	// the server keeps what it has — which is the only way a secret it never revealed can survive.
	const [ edits, setEdits ] = useState( {} );
	const [ active, setActive ] = useState( null );

	const load = () => {
		setError( null );
		setData( null );

		api( '/courier' )
			.then( ( result ) => {
				setData( result );
				setActive( result.active );
				setEdits( {} );
			} )
			.catch( setError );
	};

	useEffect( load, [] );

	const setField = ( provider, field, value ) =>
		setEdits( ( current ) => ( { ...current, [ provider + '.' + field ]: value } ) );

	const valueOf = ( provider, field ) => {
		const key = provider + '.' + field;

		if ( key in edits ) {
			return edits[ key ];
		}

		return data.providers[ provider ].values[ field ] || '';
	};

	const dirty = Object.keys( edits ).length > 0 || ( data && active !== data.active );

	const save = async () => {
		setSaving( true );

		try {
			const providers = {};

			Object.entries( edits ).forEach( ( [ key, value ] ) => {
				const [ provider, field ] = key.split( '.' );

				providers[ provider ] = { ...( providers[ provider ] || {} ), [ field ]: value };
			} );

			const result = await api( '/courier', { method: 'POST', body: { active, providers } } );

			setData( result );
			setActive( result.active );
			setEdits( {} );
			toast( 'Courier settings saved' );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setSaving( false );
		}
	};

	if ( error ) {
		return html`<${ Card } title="Courier"><${ ErrorBox } error=${ error } onRetry=${ load } /><//>`;
	}

	if ( ! data ) {
		return html`<${ Card } title="Courier"
			><div class="sb-media-loading"><span class="sb-spinner"></span></div
		><//>`;
	}

	const providers = Object.entries( data.providers );

	return html`
		<${ Card }
			title="Courier"
			action=${ html`
				<button class="sb-btn sb-btn--primary sb-btn--sm" disabled=${ saving || ! dirty } onClick=${ save }>
					${ saving ? 'Saving…' : dirty ? 'Save courier' : 'Saved' }
				</button>
			` }
			lead="Which courier the Send to Courier button on an order books with. Each one needs two different things: the API key it publishes for creating parcels, and — separately — the email and password you sign in to its own merchant panel with, which is the only way to read what a customer has ordered elsewhere. You can fill in one without the other."
		>
			<${ Field } label="Courier to send parcels with" id="cr-active">
				<${ Select }
					id="cr-active"
					value=${ active || '' }
					onChange=${ setActive }
					options=${ [
						{ value: '', label: 'None — the button is off' },
						...providers.map( ( [ key, provider ] ) => ( { value: key, label: provider.label } ) ),
					] }
				/>
			<//>

			${ /*
			 * Folded, with the chosen courier open.
			 *
			 * Three couriers at eleven fields between them made this card longer than the rest of the
			 * Settings screen put together, and a shop uses one of them. `<details>` rather than state
			 * of our own: it opens on the browser's own find-in-page, it is one element, and binding
			 * `open` to the active courier means changing the select opens the fields that choice just
			 * made relevant instead of leaving the owner to hunt for them.
			 */
			providers.map(
				( [ key, provider ] ) => html`
					<details key=${ key } class="sb-courier-block" open=${ key === active }>
						<summary class="sb-courier-block__head">
							<span class="sb-courier-block__name">${ provider.label }</span>
							${ provider.ready
								? html`<${ Badge } tone="ok">Can send parcels<//>`
								: html`<${ Badge }>Not set up<//>` }
							${ provider.can_check ? html`<${ Badge } tone="ok">Can check a number<//>` : null }
							<span class="sb-courier-block__toggle" aria-hidden="true"></span>
						</summary>

						<${ Fields }>
							${ Object.entries( provider.fields ).map( ( [ field, spec ] ) => {
								const id = 'cr-' + key + '-' + field;
								const stored = data.providers[ key ].has_secret[ field ];

								return html`
									<${ Field }
										key=${ field }
										label=${ spec.label }
										id=${ id }
										hint=${ spec.secret && stored
											? ( spec.description ? spec.description + ' ' : '' ) +
											  'Already saved. Leave blank to keep it, or type a new one to replace it.'
											: spec.description }
									>
										<input
											class="sb-input"
											id=${ id }
											type="text"
											autocomplete="off"
											spellcheck="false"
											placeholder=${ spec.secret && stored ? '••••••••' : '' }
											value=${ valueOf( key, field ) }
											onInput=${ ( e ) => setField( key, field, e.target.value ) }
										/>
									<//>
								`;
							} ) }
						<//>
					</details>
				`
			) }

			<p class="sb-hint">
				A note on the merchant-panel logins: no courier publishes an API for a customer's
				delivery record, so this signs in to their own panel the way their dashboard does. It
				works today and it is not a supported interface, so treat a courier that suddenly reports
				"could not be read" as their end changing rather than as an answer about the customer.
			</p>
		<//>
	`;
}
