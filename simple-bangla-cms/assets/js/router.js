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
	}

	announce();
	window.scrollTo( 0, 0 );
}

function announce() {
	const path = currentPath();
	listeners.forEach( ( fn ) => fn( path ) );
}

window.addEventListener( 'popstate', announce );

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
