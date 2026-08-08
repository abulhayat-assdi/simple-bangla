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
	processing: { label: 'Processing', tone: 'ok' },
	'on-hold': { label: 'On hold', tone: 'warn' },
	completed: { label: 'Completed', tone: 'ok' },
	cancelled: { label: 'Cancelled', tone: 'muted' },
	refunded: { label: 'Refunded', tone: 'bad' },
	failed: { label: 'Failed', tone: 'bad' },
};

/** The statuses offered as a filter or a bulk change, in the order a COD store moves through them. */
export const STATUS_ORDER = [
	'pending',
	'processing',
	'on-hold',
	'completed',
	'cancelled',
	'refunded',
	'failed',
];

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

/** Options for a status <select>. */
export function statusOptions( includeAny = false ) {
	const options = STATUS_ORDER.map( ( value ) => ( { value, label: ORDER_STATUSES[ value ].label } ) );

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
