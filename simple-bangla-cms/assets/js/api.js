/*
 * Talking to WordPress.
 *
 * Authentication is the login cookie the browser already holds, proven with the `wp_rest` nonce.
 * There is no token here on purpose: nothing to keep in localStorage, nothing for a cross-site
 * script to read, nothing to refresh. That is what serving the CMS from the store's own domain
 * buys, and it is the main reason it is served from there.
 */

const bootEl = document.getElementById( 'sb-cms-boot' );

/** Everything the server put in the page. */
export const SB = bootEl ? JSON.parse( bootEl.textContent ) : {};

/** Abilities as a set, so screens can gate themselves cheaply. */
const abilities = new Set( SB.abilities || [] );

export function can( ability ) {
	return abilities.has( ability );
}

/**
 * Whether the user holds any one of several abilities.
 *
 * The Settings screen is the reason this exists: it carries the colour palette, which is
 * `appearance.manage`, alongside the store address and currency, which are `store.manage`. Gating
 * the sidebar link on either one would hide half a working screen from someone entitled to it, so
 * the link asks for either and each card checks for itself.
 *
 * @param {string|string[]} ability One ability or a list of alternatives.
 * @return {boolean}
 */
export function canAny( ability ) {
	return Array.isArray( ability ) ? ability.some( can ) : can( ability );
}

/**
 * A cookie session can end while a tab sits open overnight. Rather than trying to renew a nonce
 * with a request that would itself be rejected, hand the problem back to the server: reloading
 * re-renders either a signed-in shell with a fresh nonce or the login screen.
 */
function sessionExpired() {
	window.location.reload();
}

/**
 * Call an endpoint.
 *
 * @param {string} path   Route below the plugin namespace, e.g. "/dashboard". Pass a full
 *                        namespace like "wc/v3/products" to reach WooCommerce's own API.
 * @param {object} [opts] method, body, params.
 * @return {Promise<any>}
 */
export async function api( path, opts = {} ) {
	const response = await request( path, opts );
	const payload = await readBody( response );

	if ( ! response.ok ) {
		throw toError( response, payload );
	}

	return payload;
}

/**
 * Issue the request. A path with no leading slash is treated as a full namespace, which is how
 * WooCommerce's own API is reached: api( 'wc/v3/products' ).
 */
function request( path, opts = {} ) {
	const { method = 'GET', body, params } = opts;

	const route = path.startsWith( '/' ) ? SB.namespace + path : path;
	const url = new URL( SB.restRoot.replace( /\/$/, '' ) + '/' + route, window.location.origin );

	if ( params ) {
		Object.entries( params ).forEach( ( [ key, value ] ) => {
			if ( value !== undefined && value !== null && value !== '' ) {
				url.searchParams.set( key, value );
			}
		} );
	}

	const init = {
		method,
		// Without this the login cookie is not sent and every call is anonymous.
		credentials: 'same-origin',
		headers: { 'X-WP-Nonce': SB.nonce },
	};

	if ( body !== undefined ) {
		init.headers[ 'Content-Type' ] = 'application/json';
		init.body = JSON.stringify( body );
	}

	return fetch( url, init ).then( ( response ) => {
		if ( response.status === 401 ) {
			sessionExpired();
			throw new Error( 'Session expired.' );
		}

		return response;
	} );
}

/** A DELETE returns a body, a 204 does not; either way the caller should not have to care. */
async function readBody( response ) {
	try {
		return await response.json();
	} catch ( e ) {
		return null;
	}
}

/** Turn a failed response into an Error carrying what the interface can show. */
function toError( response, payload ) {
	const error = new Error(
		( payload && payload.message ) || 'Something went wrong. Please try again.'
	);

	error.status = response.status;
	error.code = payload && payload.code;
	error.data = payload && payload.data;

	return error;
}

/**
 * Call a listing endpoint and keep the pagination that comes back in the headers.
 *
 * WordPress reports totals in `X-WP-Total` and `X-WP-TotalPages` rather than in the body, so a
 * plain api() call returns the rows and silently loses the count the pager needs.
 *
 * @param {string} path   Route.
 * @param {object} [opts] Same options as api().
 * @return {Promise<{items: any[], total: number, pages: number}>}
 */
export async function apiList( path, opts = {} ) {
	const response = await request( path, opts );
	const items = await readBody( response );

	if ( ! response.ok ) {
		throw toError( response, items );
	}

	return {
		items: Array.isArray( items ) ? items : [],
		total: Number( response.headers.get( 'X-WP-Total' ) || 0 ),
		pages: Number( response.headers.get( 'X-WP-TotalPages' ) || 1 ),
	};
}

/**
 * Upload a file to the media library.
 *
 * Sent as a raw body with Content-Disposition rather than as multipart form data: WordPress
 * accepts both, and the raw form avoids having to build a FormData whose field name has to match
 * what the REST controller expects.
 *
 * @param {File}   file       The file from an <input type="file">.
 * @param {object} [meta]     Optional title and alt_text.
 * @return {Promise<object>}  The created attachment.
 */
export async function apiUpload( file, meta = {} ) {
	const url = new URL(
		SB.restRoot.replace( /\/$/, '' ) + '/wp/v2/media',
		window.location.origin
	);

	const response = await fetch( url, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'X-WP-Nonce': SB.nonce,
			'Content-Type': file.type || 'application/octet-stream',
			'Content-Disposition': 'attachment; filename="' + encodeURIComponent( file.name ) + '"',
		},
		body: file,
	} );

	const body = await readBody( response );

	if ( response.status === 401 ) {
		sessionExpired();
		throw new Error( 'Session expired.' );
	}

	if ( ! response.ok ) {
		throw toError( response, body );
	}

	if ( meta.alt_text || meta.title ) {
		return api( 'wp/v2/media/' + body.id, { method: 'POST', body: meta } );
	}

	return body;
}

/**
 * Format an amount in the store's currency.
 *
 * The store runs at zero decimals, so a hard-coded two would print "৳ 1,999.00" on every screen.
 * The decimal count comes from WooCommerce's own setting in the boot payload.
 *
 * @param {number} amount Amount.
 * @return {string}
 */
export function money( amount ) {
	const decimals = SB.store ? SB.store.decimals : 0;
	const symbol = ( SB.store && SB.store.currency_symbol ) || '';
	const value = Number( amount || 0 ).toLocaleString( undefined, {
		minimumFractionDigits: decimals,
		maximumFractionDigits: decimals,
	} );

	return symbol ? symbol + value : value;
}

/**
 * Format a plain count.
 *
 * @param {number} value Number.
 * @return {string}
 */
export function count( value ) {
	return Number( value || 0 ).toLocaleString();
}
