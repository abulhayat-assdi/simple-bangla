/*
 * Shared order vocabulary.
 *
 * The list, the detail screen and the invoice all need the same status labels, tones and
 * formatting. Defining them once means a status can never read "Processing" on one screen and
 * "processing" on the next.
 */

import { SB } from './api.js';

/**
 * WooCommerce's core order statuses.
 *
 * `checkout-draft` is deliberately absent: it is an internal placeholder WooCommerce creates for
 * a block checkout in progress, and showing it would put half-abandoned baskets in the owner's
 * order list as if they were real.
 */
export const ORDER_STATUSES = {
	pending: { label: 'Pending payment', tone: 'warn' },
	processing: { label: 'New — to confirm', tone: 'warn' },
	'on-hold': { label: 'On hold', tone: 'warn' },
	// Registered by the theme, in inc/order-status.php. Nothing sets `sb-courier` any more — see
	// ORDER_VIEWS — but orders dispatched under the old system still carry it and must still be named.
	'sb-courier': { label: 'Courier-এ আছে', tone: 'ok' },
	completed: { label: 'Completed', tone: 'ok' },
	'sb-returned': { label: 'Returned', tone: 'bad' },
	cancelled: { label: 'Cancelled', tone: 'muted' },
	refunded: { label: 'Refunded', tone: 'bad' },
	failed: { label: 'Failed', tone: 'bad' },
};

/**
 * The statuses offered as a bulk change, in the order a COD store moves through them.
 *
 * `sb-courier` is deliberately absent since 2026-08-12. Handing a parcel to the courier no longer
 * changes an order's status — the consignment number records it and the order stays in New Orders —
 * so offering the status here would let the owner park orders in a stage nothing else now sets, and
 * whose only remaining purpose is to name the ones dispatched under the old system.
 */
export const STATUS_ORDER = [
	'pending',
	'processing',
	'on-hold',
	'completed',
	'sb-returned',
	'cancelled',
	'refunded',
	'failed',
];

/**
 * The four stages the shop actually thinks in, and the WooCommerce statuses behind each.
 *
 * The order list is filtered by these rather than by raw status (owner's decision, 2026-08-09), and
 * this table is the whole of that mapping — nothing else in the CMS decides what "New Orders" means.
 *
 * **There is no longer a Courier stage** (owner's decision, 2026-08-12). Handing a parcel over is
 * not a place an order waits: it is something that happened *to* an order that is still the shop's
 * outstanding work. So a dispatched order keeps its status, wears its consignment number in the list,
 * and stays under New Orders until someone says how it ended. That removed a tab the owner had to
 * check twice a day to find parcels they had already dealt with.
 *
 * `sb-courier` therefore sits under New too. Nothing sets it any more, but orders dispatched under
 * the previous system are in it, and they belong beside the rest of the un-finished work rather than
 * only in All.
 *
 * The rule that has to hold: **every status appears in exactly one view.** These four are the only
 * filters, so a status missing from all of them would be an order the owner can never find.
 */
export const ORDER_VIEWS = [
	{ key: 'new', label: 'New Orders', statuses: [ 'pending', 'processing', 'on-hold', 'sb-courier' ] },
	{ key: 'completed', label: 'Completed Orders', statuses: [ 'completed' ] },
	{ key: 'cancelled', label: 'Cancel Orders', statuses: [ 'cancelled' ] },
	{ key: 'failed', label: 'Failed / Cancelled', statuses: [ 'sb-returned', 'refunded', 'failed' ] },
];

/**
 * The statuses an order can still be worked on from — the ones the New Orders tab holds.
 *
 * Used to decide whether the three outcome buttons are offered on a dispatched order. A parcel
 * already marked delivered must not show them again, or one stray tap turns a completed sale into a
 * return.
 */
export const OPEN_STATUSES = [ 'pending', 'processing', 'on-hold', 'sb-courier' ];

/**
 * The three ways an order ends, as buttons, in the order the owner asked for them.
 *
 * They appear on a dispatched order in place of the status badge, and each one is a whole decision:
 * the parcel arrived, the order was killed, or the parcel came back. Returned is kept apart from
 * cancelled deliberately — a parcel that came back cost the shop a courier fee and a cancelled order
 * did not, and the two tabs are how that is counted.
 *
 * `sb-returned` is the theme's own status (see `simple-bangla/inc/order-status.php`); it restocks,
 * which is the other half of why it cannot just be `cancelled`.
 */
export const ORDER_OUTCOMES = [
	{ status: 'completed', label: 'Completed', short: 'Completed', tone: 'primary' },
	{ status: 'cancelled', label: 'Canceled', short: 'Canceled', tone: 'ghost' },
	{ status: 'sb-returned', label: 'Returned', short: 'Returned', tone: 'danger' },
];

/** Whether an order has been handed to a courier, which is a fact about its record, not its status. */
export function isDispatched( order ) {
	return !! ( order && order.sb_courier );
}

/** Whether the three outcome buttons belong on this order. */
export function offersOutcomes( order ) {
	return isDispatched( order ) && OPEN_STATUSES.includes( order.status );
}

/** The view the order screen opens on: what arrived and still needs doing. */
export const DEFAULT_VIEW = 'new';

/**
 * The statuses behind a view, as WooCommerce's `status` parameter wants them.
 *
 * A comma-separated string rather than an array, because the query builder writes each parameter
 * once and WordPress splits a string into an array for an array-typed parameter anyway.
 *
 * @param {string} key View key.
 * @return {string}
 */
export function viewStatuses( key ) {
	const view = ORDER_VIEWS.find( ( v ) => v.key === key );

	return view ? view.statuses.join( ',' ) : '';
}

export function statusLabel( status ) {
	return ORDER_STATUSES[ status ] ? ORDER_STATUSES[ status ].label : status;
}

/**
 * The same statuses in Bangla, for the invoice.
 *
 * The CMS is English and the invoice is not; printing "Completed" inside an otherwise Bangla
 * document is the kind of seam a customer notices.
 */
const STATUS_BN = {
	pending: 'পেমেন্ট বাকি',
	processing: 'প্রক্রিয়াধীন',
	'on-hold': 'স্থগিত',
	completed: 'সম্পন্ন',
	cancelled: 'বাতিল',
	refunded: 'ফেরত দেওয়া হয়েছে',
	failed: 'ব্যর্থ',
};

export function statusLabelBn( status ) {
	return STATUS_BN[ status ] || statusLabel( status );
}

export function statusTone( status ) {
	return ORDER_STATUSES[ status ] ? ORDER_STATUSES[ status ].tone : 'muted';
}

/**
 * Options for a status `<select>`.
 *
 * `current` is what keeps the control honest. A select whose value matches none of its options
 * displays the *first* one instead, so an order sitting in a status this list no longer offers —
 * `sb-courier` since 2026-08-12, or anything a plugin registered — would read as "Pending payment",
 * and saving the form the owner never touched would move a real order. Appending it means the
 * control always shows what the order actually is.
 *
 * @param {boolean} [includeAny] Prepend an "Any status" option, for filtering rather than setting.
 * @param {string}  [current]    The value the control is showing, appended if it is not on the list.
 * @return {Array<{value:string,label:string}>}
 */
export function statusOptions( includeAny = false, current = '' ) {
	const slugs = current && ! STATUS_ORDER.includes( current ) ? [ ...STATUS_ORDER, current ] : STATUS_ORDER;
	const options = slugs.map( ( value ) => ( { value, label: statusLabel( value ) } ) );

	return includeAny ? [ { value: '', label: 'Any status' }, ...options ] : options;
}

/**
 * The customer's name, falling back through what an order actually carries.
 *
 * A guest COD order often has no account and no company; what it always has is a billing name.
 */
export function customerName( order ) {
	const billing = order.billing || {};
	const name = [ billing.first_name, billing.last_name ].filter( Boolean ).join( ' ' ).trim();

	return name || billing.company || 'Guest';
}

/**
 * A single-line address, skipping the parts that are empty.
 *
 * @param {object}  address        WooCommerce billing or shipping object.
 * @param {boolean} [hideHomeland] Drop the country when it is Bangladesh. The store sells only
 *                                 there, so on a printed invoice "BD" is noise on every parcel.
 * @return {string[]}
 */
export function addressLines( address, hideHomeland = false ) {
	if ( ! address ) {
		return [];
	}

	const name = [ address.first_name, address.last_name ].filter( Boolean ).join( ' ' );
	const cityLine = [ address.city, address.state, address.postcode ].filter( Boolean ).join( ', ' );
	const country = hideHomeland && address.country === 'BD' ? '' : address.country;

	return [ name, address.company, address.address_1, address.address_2, cityLine, country ]
		.map( ( line ) => ( line || '' ).trim() )
		.filter( Boolean );
}

/**
 * Date and time in the site's locale.
 *
 * WooCommerce sends `date_created` as local store time with no zone marker. Appending "Z" would
 * claim it is UTC and shift every order by the store's offset — six hours, for Dhaka. It is
 * parsed as-is and shown as-is.
 *
 * A `Date` is accepted as well as a string, for the few values the plugin stores as real unix
 * timestamps and therefore does know the zone of.
 */
export function dateTime( value ) {
	if ( ! value ) {
		return '—';
	}

	const date = new Date( value );

	return Number.isNaN( date.getTime() )
		? value
		: date.toLocaleString( undefined, {
				day: 'numeric',
				month: 'short',
				year: 'numeric',
				hour: 'numeric',
				minute: '2-digit',
		  } );
}

export function dateOnly( value ) {
	if ( ! value ) {
		return '—';
	}

	const date = new Date( value );

	return Number.isNaN( date.getTime() )
		? value
		: date.toLocaleDateString( undefined, { day: 'numeric', month: 'short', year: 'numeric' } );
}

/** How many things are in the order, counting quantities rather than lines. */
export function itemCount( order ) {
	return ( order.line_items || [] ).reduce( ( sum, item ) => sum + Number( item.quantity || 0 ), 0 );
}

/** Total already refunded against an order, as a positive number. */
export function refundedTotal( order ) {
	return ( order.refunds || [] ).reduce( ( sum, refund ) => sum + Math.abs( Number( refund.total || 0 ) ), 0 );
}

/** The store's own details, for the invoice header. Filled in by the caller from /settings. */
export function storeName() {
	return ( SB.store && SB.store.name ) || '';
}

/**
 * The variation a line item was bought in — size, colour and anything else the product defines.
 *
 * WooCommerce puts these in the item's own `meta_data` alongside private bookkeeping keys, which are
 * the ones prefixed with an underscore. `display_key` and `display_value` are the labels WooCommerce
 * has already resolved for the customer, so they are preferred over the raw slugs.
 *
 * @param {object} item Line item.
 * @return {Array<{label:string,value:string}>}
 */
export function itemAttributes( item ) {
	return ( ( item && item.meta_data ) || [] )
		.filter( ( meta ) => meta.key && ! String( meta.key ).startsWith( '_' ) )
		.map( ( meta ) => ( {
			label: String( meta.display_key || meta.key ),
			// A value can be an object for a plugin-added field; only a scalar is worth printing.
			value: typeof meta.display_value === 'object' ? '' : String( meta.display_value ?? meta.value ?? '' ),
		} ) )
		.filter( ( meta ) => meta.value.trim() );
}
