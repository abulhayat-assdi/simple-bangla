/*
 * Routing.
 *
 * Lifted out of app.js in phase 3, when screens started needing to navigate on their own — a
 * product row opens an editor, saving a new product replaces the URL with the real one. Passing
 * a navigate function down through every component would have coupled each screen to its parent
 * for no benefit.
 *
 * History API rather than hashes: the server serves the shell for every path under /manage, so a
 * deep link survives a refresh and reads like a real address.
 */

import { useState, useEffect } from './ui.js';
import { SB } from './api.js';

const BASE = SB.base || '/manage/';

const listeners = new Set();

/*
 * How many entries this app has pushed onto the browser's history.
 *
 * The back button needs to know whether history.back() would land on another CMS screen or leave
 * the site altogether — a deep link opened from a bookmark or a WhatsApp message has the store's
 * previous page, or nothing at all, behind it. The browser will not say, so the count is kept here:
 * a push adds one, a popstate spends one. When it reaches zero the button walks up the path instead.
 */
let depth = 0;

/** The route path for the current address, always leading-slashed. */
export function currentPath() {
	let path = window.location.pathname;

	if ( path.startsWith( BASE ) ) {
		path = path.slice( BASE.length );
	}

	return '/' + path.replace( /^\/+|\/+$/g, '' );
}

/** The browser address for a route path. */
export function href( path ) {
	return BASE + path.replace( /^\//, '' );
}

/**
 * Go to a route.
 *
 * @param {string}  path      Route path.
 * @param {boolean} [replace] Replace the current entry instead of pushing. Used after creating a
 *                            record, so the back button does not return to a "new" form that no
 *                            longer describes anything.
 */
export function navigate( path, replace = false ) {
	const url = href( path );

	if ( replace ) {
		window.history.replaceState( {}, '', url );
	} else {
		window.history.pushState( {}, '', url );
		depth += 1;
	}

	announce();
	window.scrollTo( 0, 0 );
}

/**
 * The route one level up from a path.
 *
 * Used only when there is nothing of ours to go back to. `/orders/57/invoice` climbs to
 * `/orders/57`, then `/orders`, then the dashboard — which is the same journey the back button
 * would have made, just reconstructed rather than replayed.
 *
 * @param {string} path Route path.
 * @return {string}
 */
export function parentPath( path ) {
	const parts = path.replace( /^\/+|\/+$/g, '' ).split( '/' ).filter( Boolean );

	parts.pop();

	return '/' + parts.join( '/' );
}

/**
 * Whether a back button has anywhere to go.
 *
 * True on the dashboard too, when the owner arrived there from another screen — "back" there means
 * the screen they came from, not "up", and refusing to move would be the surprising answer.
 *
 * @param {string} path Current route path.
 * @return {boolean}
 */
export function canGoBack( path ) {
	return depth > 0 || path !== '/';
}

/**
 * Go back one step.
 *
 * Prefers the browser's own history, so the scroll position and the screen's state come back with
 * it. Falls back to walking up the path for a deep link, where history.back() would leave the CMS.
 *
 * @param {string} path Current route path.
 */
export function goBack( path ) {
	if ( depth > 0 ) {
		window.history.back();
		return;
	}

	if ( path !== '/' ) {
		// replace, not push: a fallback that stacked entries would make the button need two presses
		// the second time it was used on the same screen.
		navigate( parentPath( path ), true );
	}
}

function announce() {
	const path = currentPath();
	listeners.forEach( ( fn ) => fn( path ) );
}

window.addEventListener( 'popstate', () => {
	depth = Math.max( 0, depth - 1 );
	announce();
} );

/** Subscribe a component to the current route. */
export function useRoute() {
	const [ path, setPath ] = useState( currentPath() );

	useEffect( () => {
		listeners.add( setPath );
		return () => listeners.delete( setPath );
	}, [] );

	return path;
}

/**
 * Handle a click on an internal link.
 *
 * Modified clicks are left alone so middle-click, ctrl-click and "open in new tab" keep working —
 * which is why nav links carry a real href rather than being buttons.
 *
 * @param {Event}  event Click event.
 * @param {string} path  Route path.
 */
export function onLinkClick( event, path ) {
	if ( event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0 ) {
		return;
	}

	event.preventDefault();
	navigate( path );
}
