/*
 * Turning what an API sends into what a person should read.
 *
 * WordPress and WooCommerce hand back HTML in fields that look like plain text. `wp/v2/menu-items`
 * returns a title as `Airpod&#8217;s`, `wc/v3/settings` labels a currency
 * `Bangladeshi taka (&#2547;&nbsp;)`, and a product's `price_html` is a `<span>` with `<del>` and
 * `<ins>` inside it. Printed into a text node — which is every value in this interface, because
 * nothing here assigns `innerHTML` — those arrive on screen exactly as written above.
 *
 * There were three private copies of half of this before: `decodeEntities` in the Content Pages
 * screen and a `stripTags` in each of Products and Settings, two of which built a detached `<div>`
 * and set `innerHTML` on it. This module is the one copy, and it uses `DOMParser` throughout: a
 * parsed document is inert, while a detached div still starts `<img src>` loads in some browsers.
 * Several of these strings — a review body, a menu title, a product name — were typed by somebody
 * other than the shop owner, so inertness is the property that matters.
 */

/**
 * The text of a string that may carry HTML entities.
 *
 * @param {string} text Possibly entity-encoded text.
 * @return {string}
 */
export function decodeEntities( text ) {
	// The overwhelming majority of values have no entity in them at all, and parsing a document per
	// table cell to discover that would be the expensive way to do nothing.
	if ( ! text || text.indexOf( '&' ) === -1 ) {
		return text || '';
	}

	return parse( text );
}

/**
 * The words of a string that may carry markup, whitespace collapsed.
 *
 * Used where an API answers with a fragment of HTML for something the interface shows as one line:
 * WooCommerce's `price_html` and its settings descriptions, both of which arrive with tags.
 *
 * @param {string} value Possibly marked-up text.
 * @return {string}
 */
export function stripTags( value ) {
	if ( ! value ) {
		return '';
	}

	return parse( value ).replace( /\s+/g, ' ' ).trim();
}

/**
 * A record's title as text, whatever shape the API used for it.
 *
 * Core wraps a title as `{ raw, rendered }`, WooCommerce sends a bare string, and a menu item may
 * have either depending on the endpoint. `raw` is preferred where both exist: `rendered` has been
 * through the same filters that produce curly quotes and shortcodes, and this is a management
 * screen showing what is stored rather than a page rendering it.
 *
 * @param {object|string} title Title field.
 * @return {string}
 */
export function titleText( title ) {
	if ( ! title ) {
		return '';
	}

	const raw = typeof title === 'object' ? title.raw ?? title.rendered ?? '' : title;

	return decodeEntities( String( raw ) );
}

/**
 * Read a fragment as an inert document and take its text.
 *
 * @param {string} value Markup or entity-encoded text.
 * @return {string}
 */
function parse( value ) {
	const doc = new DOMParser().parseFromString( '<body>' + value + '</body>', 'text/html' );

	return doc.body.textContent || '';
}
