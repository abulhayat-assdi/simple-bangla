/*
 * Settings: the palette, the store's address, and how prices are printed.
 *
 * Two stores of truth on one screen. The colours are theme mods and go through `/settings`; the
 * address and currency are WooCommerce's and go through `wc/v3/settings/general`. They are
 * together because they answer the same question — "what is this shop and what does it look
 * like?" — and apart in every other way, including who is allowed to change them. Each card
 * checks its own ability rather than trusting the sidebar to have filtered correctly.
 *
 * As with the circles screen, the save is not atomic across the two APIs. WooCommerce is written
 * first as one batch, then the theme mods; a failure stops there and says so, with whatever
 * already committed left committed.
 *
 * Payment gateways and shipping are deliberately absent. bKash, SSLCommerz and Nagad are plugin
 * screens with no REST API at all, and a half-populated payments card that silently omitted the
 * gateway the shop actually uses would be worse than sending the owner to wp-admin for it.
 */

import { html, useState, useEffect, Card, Field, Fields, Select, ErrorBox, EmptyState, toast } from '../ui.js';
import { api, apiList, can, SB } from '../api.js';
import { useSettings, SettingsPage, SchemaField, layoutFields } from '../settings-form.js';
import { decodeEntities, stripTags } from '../text.js';
import { CourierCard } from './courier.js';

/** WooCommerce general settings this screen edits, in the order they are shown. */
const ADDRESS_KEYS = [
	'woocommerce_store_address',
	'woocommerce_store_address_2',
	'woocommerce_store_city',
	'woocommerce_store_postcode',
	'woocommerce_default_country',
];

const CURRENCY_KEYS = [
	'woocommerce_currency',
	'woocommerce_currency_pos',
	'woocommerce_price_thousand_sep',
	'woocommerce_price_decimal_sep',
	'woocommerce_price_num_decimals',
];

/*
 * The logo sits first because it is the one thing on this screen a shop changes on day one, and
 * it is the same `custom_logo` mod WordPress's Site Identity panel writes — so the header, the
 * footer and this CMS's own sign-in page all follow it. Empty means the site name in text.
 *
 * The tab icon is beside it because they are one decision — "what picture is this shop?" — asked
 * twice at two sizes. Leaving the icon empty is a real answer: the theme falls back to the logo.
 */
const LOGO_CARD = 'Logo and tab icon';

const APPEARANCE_CARDS = [
	{
		title: LOGO_CARD,
		keys: [ 'custom_logo', 'site_icon' ],
	},
	{
		title: 'Brand',
		lead: 'Buttons, links and the states that need to stand out.',
		keys: [ 'simple_bangla_color_brand', 'simple_bangla_color_brand_dark' ],
	},
	{
		title: 'Text',
		keys: [ 'simple_bangla_color_ink', 'simple_bangla_color_muted', 'simple_bangla_color_sale' ],
	},
	{
		title: 'Surfaces',
		lead: 'The backgrounds everything else sits on.',
		keys: [
			'simple_bangla_color_page',
			'simple_bangla_color_bg',
			'simple_bangla_color_bg_alt',
			'simple_bangla_color_line',
			'simple_bangla_color_footer_bg',
		],
	},
];

export function Settings() {
	const canColors = can( 'appearance.manage' );
	const canStore = can( 'store.manage' );

	// Two schema groups, one request: the logo and the palette are both "how the shop looks".
	const state = useSettings( 'brand,colors' );

	const [ woo, setWoo ] = useState( null );
	const [ wooBase, setWooBase ] = useState( {} );
	const [ wooError, setWooError ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	const loadWoo = () => {
		if ( ! canStore ) {
			setWoo( [] );
			return;
		}

		setWooError( null );

		apiList( 'wc/v3/settings/general' )
			.then( ( result ) => {
				const wanted = [ ...ADDRESS_KEYS, ...CURRENCY_KEYS ];
				const rows = result.items.filter( ( s ) => wanted.includes( s.id ) );

				setWoo( rows );

				const base = {};
				rows.forEach( ( s ) => {
					base[ s.id ] = s.value;
				} );
				setWooBase( base );
			} )
			.catch( setWooError );
	};

	useEffect( loadWoo, [ canStore ] );

	const setWooValue = ( id, value ) => {
		setWoo( ( current ) => current.map( ( s ) => ( s.id === id ? { ...s, value } : s ) ) );
	};

	const wooChanged = ( woo || [] ).filter( ( s ) => s.value !== wooBase[ s.id ] );
	const dirty = state.dirty || wooChanged.length > 0;

	const save = async () => {
		setSaving( true );

		try {
			if ( wooChanged.length ) {
				// One batch, not a request per field: WooCommerce provides it, and five sequential
				// writes on a slow host is five chances to half-apply an address.
				const result = await api( 'wc/v3/settings/general/batch', {
					method: 'POST',
					body: { update: wooChanged.map( ( s ) => ( { id: s.id, value: s.value } ) ) },
				} );

				// A batch answers 200 and reports per-item failures inside the body, so the result
				// is inspected rather than trusted.
				const failed = ( result.update || [] ).filter( ( r ) => r.error );

				if ( failed.length ) {
					throw new Error( failed[ 0 ].error.message || 'A store setting could not be saved.' );
				}

				const applied = {};
				( result.update || [] ).forEach( ( r ) => {
					applied[ r.id ] = r.value;
				} );
				setWooBase( ( current ) => ( { ...current, ...applied } ) );
				setWoo( ( current ) => current.map( ( s ) => ( s.id in applied ? { ...s, value: applied[ s.id ] } : s ) ) );
			}

			if ( state.dirty ) {
				const ok = await state.save();

				if ( ! ok ) {
					return;
				}
			} else if ( wooChanged.length ) {
				toast( 'Saved' );
			}
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setSaving( false );
		}
	};

	const appearanceCards = canColors ? layoutFields( state.fields, APPEARANCE_CARDS ) : [];

	// The logo is lifted out of the run so it can sit at the top of the page. The colours stay
	// below the store's own details, where they already were.
	const logoCard = appearanceCards.find( ( card ) => card.title === LOGO_CARD );
	const colorCards = appearanceCards.filter( ( card ) => card !== logoCard );

	return html`
		<${ SettingsPage }
			title="Settings"
			lead="How the shop looks, where it is, and how it prints prices."
			state=${ state }
			dirty=${ dirty }
			saving=${ saving || state.saving }
			onSave=${ save }
		>
			${ logoCard ? html`<${ SchemaCard } card=${ logoCard } state=${ state } />` : null }

			${ canStore
				? html`
						<${ WooCard }
							title="Store address"
							lead="Printed on invoices and used to work out tax and shipping."
							ids=${ ADDRESS_KEYS }
							woo=${ woo }
							error=${ wooError }
							onRetry=${ loadWoo }
							onChange=${ setWooValue }
						/>

						<${ WooCard }
							title="Currency"
							lead=${ `The storefront is built around ${ ( SB.store && SB.store.currency_symbol ) || '৳' } with no decimals. Changing these changes every price on the site.` }
							ids=${ CURRENCY_KEYS }
							woo=${ woo }
							error=${ wooError }
							onRetry=${ loadWoo }
							onChange=${ setWooValue }
						/>

						<${ CourierCard } />
				  `
				: null }

			${ colorCards.map(
				( card ) => html`<${ SchemaCard } key=${ card.title } card=${ card } state=${ state } />`
			) }

			${ ! canColors && ! canStore
				? html`<${ EmptyState }
						title="Nothing to change here"
						body="Your account cannot edit the palette or the store's details."
				  />`
				: null }
		<//>
	`;
}

/**
 * A card of theme settings, each control chosen from what the schema says the field is.
 *
 * @param {object} props
 * @param {object} props.card  One entry from layoutFields(): title, optional lead, fields.
 * @param {object} props.state The useSettings() state the fields read and write.
 */
function SchemaCard( { card, state } ) {
	return html`
		<${ Card } title=${ card.title } lead=${ card.lead }>
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
	`;
}

/**
 * A card of WooCommerce settings, rendered from what WooCommerce says each one is.
 *
 * Their labels, help text and select options all come back with the values, so nothing about
 * currencies or countries is restated here — the list of 200-odd countries in particular is
 * WooCommerce's to keep current.
 */
function WooCard( { title, lead, ids, woo, error, onRetry, onChange } ) {
	if ( error ) {
		return html`<${ Card } title=${ title }><${ ErrorBox } error=${ error } onRetry=${ onRetry } /><//>`;
	}

	if ( ! woo ) {
		return html`<${ Card } title=${ title }
			><div class="sb-media-loading"><span class="sb-spinner"></span></div
		><//>`;
	}

	const rows = ids.map( ( id ) => woo.find( ( s ) => s.id === id ) ).filter( Boolean );

	if ( ! rows.length ) {
		return null;
	}

	return html`
		<${ Card } title=${ title } lead=${ lead }>
			<${ Fields }>
			${ rows.map( ( setting ) => {
				const id = 'woo-' + setting.id;
				const options = setting.options
					? Object.entries( setting.options ).map( ( [ value, label ] ) => ( {
							value,
							// Country groups arrive as nested objects; flattening them is not worth
							// it when the flat entries are the ones a Bangladeshi store needs.
							//
							// Decoded, because WooCommerce writes the currency symbol into the label as
							// an entity: the taka option arrives as "Bangladeshi taka (&#2547;&nbsp;)"
							// and an <option> is a text node, so it read exactly like that on screen.
							label: typeof label === 'string' ? decodeEntities( label ) : value,
					  } ) )
					: null;

				return html`
					<${ Field }
						key=${ setting.id }
						label=${ decodeEntities( setting.label ) }
						id=${ id }
						hint=${ stripTags( setting.description ) }
					>
						${ options
							? html`<${ Select }
									id=${ id }
									value=${ setting.value }
									options=${ options }
									onChange=${ ( value ) => onChange( setting.id, value ) }
							  />`
							: html`<input
									class="sb-input"
									id=${ id }
									type=${ setting.type === 'number' ? 'number' : 'text' }
									autocomplete="off"
									value=${ setting.value === null || setting.value === undefined ? '' : setting.value }
									onInput=${ ( e ) => onChange( setting.id, e.target.value ) }
							  />` }
					<//>
				`;
			} ) }
			<//>
		<//>
	`;
}

