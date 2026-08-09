/*
 * Shared rendering pieces.
 *
 * Preact and htm are re-exported from here so no other file needs to know where the vendored
 * bundle lives — if it is ever swapped or moved, this is the only import to change.
 *
 * Icons are inline SVG paths rather than an icon font or sprite sheet: one fewer request, they
 * inherit `currentColor` so a stat tile's tone colours its own icon, and there is nothing to
 * keep in sync with a separate asset.
 */

export {
	html,
	render,
	Component,
	useState,
	useEffect,
	useMemo,
	useCallback,
	useRef,
	useContext,
	createContext,
} from '../vendor/preact-htm.module.js';

import { html, useState, useEffect, useRef } from '../vendor/preact-htm.module.js';

/** Icon name to the paths that draw it, on a 24×24 grid. */
const ICONS = {
	grid: [ 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z' ],
	bag: [ 'M6 8h12l-1 12H7L6 8z', 'M9.5 8V6.5a2.5 2.5 0 0 1 5 0V8' ],
	box: [ 'M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z', 'M4 7.5l8 4.5 8-4.5', 'M12 12v9' ],
	tag: [ 'M3 12V4h8l9 9-8 8-9-9z', 'M7.5 7.5h.01' ],
	image: [ 'M4 5h16v14H4z', 'M4 17l5-5 4 4 3-3 4 4', 'M9 9.5h.01' ],
	flame: [ 'M12 3s5 4 5 8a5 5 0 0 1-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3 1-5 1-7z' ],
	circle: [ 'M12 5a7 7 0 1 0 0 14 7 7 0 0 0 0-14z' ],
	rows: [ 'M4 5h16v5H4z', 'M4 14h16v5H4z' ],
	banner: [ 'M3 6h18v8H3z', 'M3 18h10' ],
	// Staggered rules, so the nav-menu icon is not the same three even lines as Settings.
	menu: [ 'M4 6h16', 'M4 12h11', 'M4 18h14' ],
	footer: [ 'M4 5h16v14H4z', 'M4 15h16' ],
	star: [ 'M12 4l2.4 5 5.6.8-4 3.9 1 5.5-5-2.7-5 2.7 1-5.5-4-3.9 5.6-.8L12 4z' ],
	// Sliders, not a cog: a ring with radiating ticks reads as a sun or a brightness control at
	// 18px, which is the wrong idea entirely for a Settings link.
	gear: [
		'M4 7h16M4 12h16M4 17h16',
		'M9 5.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z',
		'M15 10.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z',
		'M7 15.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z',
	],
	users: [ 'M9 4.8a3.2 3.2 0 1 0 0 6.4 3.2 3.2 0 0 0 0-6.4z', 'M3 20c0-3.3 2.7-5 6-5s6 1.7 6 5', 'M16 5.6a3.2 3.2 0 0 1 0 5.4', 'M17.5 15.2c2 .6 3.5 2.2 3.5 4.8' ],
	ban: [ 'M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16z', 'M6.4 6.4l11.2 11.2' ],
	shield: [ 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3z' ],
	money: [ 'M3 6h18v12H3z', 'M12 9.4a2.6 2.6 0 1 0 0 5.2 2.6 2.6 0 0 0 0-5.2z' ],
	cart: [ 'M3 5h2l2.2 10h10.3l1.8-7H6', 'M9 17.6a1.4 1.4 0 1 0 0 2.8 1.4 1.4 0 0 0 0-2.8z', 'M17 17.6a1.4 1.4 0 1 0 0 2.8 1.4 1.4 0 0 0 0-2.8z' ],
	clock: [ 'M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16z', 'M12 7.5V12l3 2' ],
	refresh: [ 'M20 12a8 8 0 1 1-2.4-5.7', 'M20 4v5h-5' ],
	layers: [ 'M12 3l9 5-9 5-9-5 9-5z', 'M3 13l9 5 9-5' ],
	chevron: [ 'M9 5l7 7-7 7' ],
	back: [ 'M15 5l-7 7 7 7' ],
	truck: [ 'M3 7h11v9H3z', 'M14 10h4l3 3v3h-7', 'M7 16a2 2 0 1 0 0 4 2 2 0 0 0 0-4z', 'M17.5 16a2 2 0 1 0 0 4 2 2 0 0 0 0-4z' ],
	trash: [ 'M4 7h16', 'M9 7V5h6v2', 'M6 7l1 13h10l1-13' ],
	print: [ 'M7 9V4h10v5', 'M5 9h14v7H5z', 'M7 14h10v6H7z' ],
	shield_check: [ 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3z', 'M9 11.5l2 2 4-4' ],
	alert: [ 'M12 4l9 16H3L12 4z', 'M12 10v4', 'M12 17h.01' ],
};

/**
 * An inline icon.
 */
export function Icon( { name, size = 18 } ) {
	const paths = ICONS[ name ] || ICONS.circle;

	return html`
		<svg
			width=${ size }
			height=${ size }
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			stroke-width="1.7"
			stroke-linecap="round"
			stroke-linejoin="round"
			aria-hidden="true"
			focusable="false"
		>
			${ paths.map( ( d ) => html`<path d=${ d } />` ) }
		</svg>
	`;
}

/**
 * A dashboard figure.
 */
export function Stat( { icon, label, value, note, tone, loading, wide } ) {
	return html`
		<div class=${ 'sb-stat' + ( tone ? ' sb-stat--' + tone : '' ) + ( wide ? ' sb-stat--wide' : '' ) }>
			<div class="sb-stat__head">
				<span class="sb-stat__icon"><${ Icon } name=${ icon } /></span>
				<span class="sb-stat__label">${ label }</span>
			</div>
			<div class=${ 'sb-stat__value' + ( loading ? ' sb-skeleton' : '' ) }>
				${ loading ? '0000' : value }
			</div>
			${ note && ! loading ? html`<div class="sb-stat__note">${ note }</div>` : null }
		</div>
	`;
}

/**
 * A screen that exists in the sidebar but is not built yet.
 *
 * Shipped deliberately rather than hiding the nav item: the owner asked to see the whole shape of
 * the CMS, and a menu that grows week by week hides what is still owed.
 */
export function Placeholder( { title, phase } ) {
	return html`
		<div class="sb-placeholder">
			<p class="sb-placeholder__title">${ title }</p>
			<p>This screen arrives in phase ${ phase }.</p>
		</div>
	`;
}

/**
 * A failed request, with a way out that is not "reload and hope".
 */
export function ErrorBox( { error, onRetry } ) {
	return html`
		<div class="sb-alert sb-alert--error" role="alert">
			${ error.message }
			${ onRetry
				? html` <button class="sb-btn sb-btn--ghost" onClick=${ onRetry }>Try again</button>`
				: null }
		</div>
	`;
}

/* ------------------------------------------------------------------ layout */

export function Card( { title, action, children, wide } ) {
	return html`
		<section class=${ 'sb-card' + ( wide ? ' sb-card--wide' : '' ) }>
			${ title
				? html`<header class="sb-card__head">
						<h2 class="sb-card__title">${ title }</h2>
						${ action || null }
					</header>`
				: null }
			<div class="sb-card__body">${ children }</div>
		</section>
	`;
}

export function EmptyState( { title, body, action } ) {
	return html`
		<div class="sb-empty">
			<p class="sb-empty__title">${ title }</p>
			${ body ? html`<p>${ body }</p>` : null }
			${ action || null }
		</div>
	`;
}

/* ------------------------------------------------------------------ form controls */

export function Field( { label, hint, children, id } ) {
	return html`
		<div class="sb-field">
			${ label ? html`<label class="sb-field__label" for=${ id }>${ label }</label>` : null }
			${ children }
			${ hint ? html`<p class="sb-hint">${ hint }</p>` : null }
		</div>
	`;
}

export function Select( { value, onChange, options, id, disabled } ) {
	return html`
		<select
			class="sb-input"
			id=${ id }
			disabled=${ disabled }
			value=${ value }
			onChange=${ ( e ) => onChange( e.target.value ) }
		>
			${ options.map(
				( o ) => html`<option key=${ o.value } value=${ o.value } selected=${ String( o.value ) === String( value ) }>${ o.label }</option>`
			) }
		</select>
	`;
}

/**
 * A checkbox that reads as a setting rather than as a form field.
 */
export function Switch( { checked, onChange, label, hint } ) {
	return html`
		<label class="sb-switch">
			<input type="checkbox" checked=${ !! checked } onChange=${ ( e ) => onChange( e.target.checked ) } />
			<span>
				<span class="sb-switch__label">${ label }</span>
				${ hint ? html`<span class="sb-hint">${ hint }</span>` : null }
			</span>
		</label>
	`;
}

export function Badge( { tone, children } ) {
	return html`<span class=${ 'sb-badge' + ( tone ? ' sb-badge--' + tone : '' ) }>${ children }</span>`;
}

/* ------------------------------------------------------------------ pagination */

export function Pagination( { page, pages, total, onPage, noun = 'items' } ) {
	if ( pages <= 1 ) {
		return total
			? html`<div class="sb-pager"><span class="sb-pager__info">${ total } ${ noun }</span></div>`
			: null;
	}

	return html`
		<div class="sb-pager">
			<span class="sb-pager__info">${ total } ${ noun } · page ${ page } of ${ pages }</span>
			<span class="sb-pager__buttons">
				<button class="sb-btn sb-btn--ghost" disabled=${ page <= 1 } onClick=${ () => onPage( page - 1 ) }>
					Previous
				</button>
				<button class="sb-btn sb-btn--ghost" disabled=${ page >= pages } onClick=${ () => onPage( page + 1 ) }>
					Next
				</button>
			</span>
		</div>
	`;
}

/* ------------------------------------------------------------------ modal */

/**
 * A dialog.
 *
 * Escape closes it and focus moves into the panel on open, because a modal that traps neither is
 * an obstacle for anyone not using a mouse.
 */
export function Modal( { title, onClose, children, footer, wide } ) {
	const panel = useRef( null );

	// The handler is kept in a ref so the mount effect below can depend on nothing at all. An
	// earlier version depended on [onClose], which callers recreate on every render — so every
	// keystroke in a dialog re-ran the effect and called focus() on the panel, pulling focus out
	// of the field being typed into and silently dropping characters.
	const closeRef = useRef( onClose );
	closeRef.current = onClose;

	useEffect( () => {
		const onKey = ( event ) => {
			if ( event.key === 'Escape' ) {
				closeRef.current();
			}
		};

		document.addEventListener( 'keydown', onKey );
		document.body.style.overflow = 'hidden';

		if ( panel.current ) {
			panel.current.focus();
		}

		return () => {
			document.removeEventListener( 'keydown', onKey );
			document.body.style.overflow = '';
		};
	}, [] );

	return html`
		<div class="sb-modal" role="dialog" aria-modal="true" aria-label=${ title }>
			<button class="sb-modal__scrim" aria-label="Close" onClick=${ onClose }></button>
			<div class=${ 'sb-modal__panel' + ( wide ? ' sb-modal__panel--wide' : '' ) } tabindex="-1" ref=${ panel }>
				<header class="sb-modal__head">
					<h2 class="sb-card__title">${ title }</h2>
					<button class="sb-icon-btn" aria-label="Close" onClick=${ onClose }>×</button>
				</header>
				<div class="sb-modal__body">${ children }</div>
				${ footer ? html`<footer class="sb-modal__foot">${ footer }</footer>` : null }
			</div>
		</div>
	`;
}

/**
 * A destructive action's second question.
 */
export function Confirm( { title, body, confirmLabel = 'Delete', onConfirm, onClose, busy } ) {
	return html`
		<${ Modal }
			title=${ title }
			onClose=${ onClose }
			footer=${ html`
				<button class="sb-btn sb-btn--ghost" onClick=${ onClose }>Cancel</button>
				<button class="sb-btn sb-btn--danger" onClick=${ onConfirm } disabled=${ busy }>
					${ busy ? 'Working…' : confirmLabel }
				</button>
			` }
		>
			<p>${ body }</p>
		<//>
	`;
}

/* ------------------------------------------------------------------ toasts */

let queue = [];
let nextId = 1;
const toastListeners = new Set();

/**
 * Announce the result of an action.
 *
 * @param {string} message Text.
 * @param {string} [tone]  "ok" or "bad".
 */
export function toast( message, tone = 'ok' ) {
	const entry = { id: nextId++, message, tone };

	queue = [ ...queue, entry ];
	toastListeners.forEach( ( fn ) => fn( queue ) );

	setTimeout( () => {
		queue = queue.filter( ( t ) => t.id !== entry.id );
		toastListeners.forEach( ( fn ) => fn( queue ) );
	}, tone === 'bad' ? 6000 : 3000 );
}

export function Toaster() {
	const [ items, setItems ] = useState( queue );

	useEffect( () => {
		toastListeners.add( setItems );
		return () => toastListeners.delete( setItems );
	}, [] );

	if ( ! items.length ) {
		return null;
	}

	return html`
		<div class="sb-toasts" role="status" aria-live="polite">
			${ items.map(
				( t ) => html`<div key=${ t.id } class=${ 'sb-toast sb-toast--' + t.tone }>${ t.message }</div>`
			) }
		</div>
	`;
}
