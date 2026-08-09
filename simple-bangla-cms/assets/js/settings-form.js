/*
 * The shared machinery behind every theme-settings screen.
 *
 * Five of the homepage screens are the same shape: fetch one group from /settings, edit a handful
 * of typed fields, write back the ones that changed. Writing that loop five times would have meant
 * five slightly different answers to "what counts as unsaved" and five chances to send an empty
 * batch, which the endpoint answers with a 400.
 *
 * Only changed keys are sent. The endpoint validates the whole batch before writing any of it, so
 * a smaller batch is also a smaller blast radius: an invalid colour in a field the owner never
 * touched cannot block a save of the field they did.
 */

import { html, useState, useEffect, useCallback, useRef, Field, Switch, ErrorBox, toast } from './ui.js';
import { api } from './api.js';
import { MediaField } from './media.js';

/**
 * Warn before leaving with unsaved changes.
 *
 * Settings screens are quick to edit and quick to navigate away from, which is exactly the
 * combination that loses work.
 *
 * @param {boolean} dirty Whether there is anything to lose.
 */
export function useUnsavedWarning( dirty ) {
	useEffect( () => {
		if ( ! dirty ) {
			return undefined;
		}

		const warn = ( event ) => {
			event.preventDefault();
			event.returnValue = '';
		};

		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ dirty ] );
}

/**
 * Load, edit and save one group of theme settings.
 *
 * @param {string} group Schema group: hero, hotdeals, circles, rows, banners, colors, store.
 * @return {object} Everything a settings screen needs to render itself.
 */
export function useSettings( group ) {
	const [ fields, setFields ] = useState( null );
	const [ values, setValues ] = useState( {} );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );

	// What the server last confirmed. Compared against `values` to decide what is dirty, so the
	// answer survives a save that only some fields took part in.
	const baseline = useRef( {} );

	const load = useCallback( () => {
		setError( null );
		setFields( null );

		api( '/settings', { params: { group } } )
			.then( ( data ) => {
				baseline.current = { ...data.values };
				setValues( { ...data.values } );
				setFields( data.fields );
			} )
			.catch( setError );
	}, [ group ] );

	useEffect( load, [ load ] );

	const set = useCallback( ( key, value ) => {
		setValues( ( current ) => ( { ...current, [ key ]: value } ) );
	}, [] );

	const changed = Object.keys( values ).filter( ( key ) => values[ key ] !== baseline.current[ key ] );

	const save = useCallback( async () => {
		if ( ! changed.length ) {
			return true;
		}

		setSaving( true );

		try {
			const payload = {};
			changed.forEach( ( key ) => {
				payload[ key ] = values[ key ];
			} );

			const result = await api( '/settings', { method: 'POST', body: { values: payload } } );

			// Trust the server's echo rather than the local copy: a sanitiser may have tidied a URL
			// or clamped a count, and the field should show what was actually stored.
			baseline.current = { ...baseline.current, ...result.values };
			setValues( ( current ) => ( { ...current, ...result.values } ) );

			toast( 'Saved' );
			return true;
		} catch ( e ) {
			toast( describeFailure( e, fields ), 'bad' );
			return false;
		} finally {
			setSaving( false );
		}
	}, [ changed, values, fields ] );

	return {
		fields,
		values,
		set,
		save,
		reload: load,
		saving,
		error,
		dirty: changed.length > 0,
		loading: ! fields && ! error,
	};
}

/**
 * Turn a rejected batch into one sentence naming a field the owner recognises.
 *
 * The endpoint answers with a map of theme-mod name to reason. A raw key like
 * `simple_bangla_hero_2_link` means nothing on screen, so it is swapped for the label the schema
 * already carries.
 *
 * @param {Error}  error  Thrown by api().
 * @param {object} fields Schema fields, for their labels.
 * @return {string}
 */
function describeFailure( error, fields ) {
	const rejected = error.data && error.data.rejected;

	if ( ! rejected ) {
		return error.message;
	}

	const [ key, reason ] = Object.entries( rejected )[ 0 ];
	const label = fields && fields[ key ] ? fields[ key ].label : key;

	return label + ': ' + reason;
}

/**
 * The frame every settings screen draws inside.
 *
 * `dirty`, `saving` and `onSave` can be passed explicitly by a screen that saves more than the one
 * settings group — the circles screen writes category images through WooCommerce as well — and
 * otherwise fall back to the hook's own state.
 */
export function SettingsPage( { title, lead, state, dirty, saving, onSave, children } ) {
	const isDirty = dirty === undefined ? state.dirty : dirty;
	const isSaving = saving === undefined ? state.saving : saving;
	const doSave = onSave || state.save;

	useUnsavedWarning( isDirty );

	if ( state.error ) {
		return html`
			<div class="sb-page">
				<h1 class="sb-page__title">${ title }</h1>
				<${ ErrorBox } error=${ state.error } onRetry=${ state.reload } />
			</div>
		`;
	}

	if ( state.loading ) {
		return html`
			<div class="sb-page">
				<h1 class="sb-page__title">${ title }</h1>
				<div class="sb-media-loading"><span class="sb-spinner"></span></div>
			</div>
		`;
	}

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">${ title }</h1>
					${ lead ? html`<p class="sb-page__lead">${ lead }</p>` : null }
				</div>
				<div class="sb-row sb-page__actions">
					<button
						class="sb-btn sb-btn--primary"
						onClick=${ doSave }
						disabled=${ isSaving || ! isDirty }
					>
						${ isSaving ? 'Saving…' : isDirty ? 'Save' : 'Saved' }
					</button>
				</div>
			</div>

			<div class="sb-form">${ children }</div>
		</div>
	`;
}

/**
 * The numbers in a group's field names, ascending.
 *
 * The schema is generated from the theme's own constants, so the number of hero slides, product
 * rows and banner pairs is whatever the theme says it is. Reading it back off the field names
 * means raising SIMPLE_BANGLA_HERO_SLIDES to 6 puts a sixth slide on this screen with no
 * front-end change — the same property the schema itself was built for.
 *
 * @param {object} fields  Schema fields.
 * @param {RegExp} pattern Must capture the number in group 1.
 * @return {number[]}
 */
export function indexesFrom( fields, pattern ) {
	const seen = new Set();

	Object.keys( fields || {} ).forEach( ( key ) => {
		const match = key.match( pattern );

		if ( match ) {
			seen.add( Number( match[ 1 ] ) );
		}
	} );

	return [ ...seen ].sort( ( a, b ) => a - b );
}

/**
 * A URL field.
 *
 * Deliberately `type="url"`-free: the browser's own validation rejects a bare "/shop/", which is
 * exactly what most of these links should be on a single-domain store.
 */
export function LinkInput( { id, value, onChange, placeholder = 'https://example.com/ or /shop/' } ) {
	return html`
		<input
			class="sb-input"
			id=${ id }
			type="text"
			inputmode="url"
			autocomplete="off"
			placeholder=${ placeholder }
			value=${ value || '' }
			onInput=${ ( e ) => onChange( e.target.value ) }
		/>
	`;
}

/**
 * The range the theme clamps every product count to.
 *
 * simple_bangla_sanitize_count() silently pulls anything outside 2–24 back inside it. The endpoint
 * would accept 40 and store 24, and because the form trusts the server's echo the field would jump
 * on save with no explanation. Better to refuse the number where it is typed.
 */
export const COUNT_MIN = 2;
export const COUNT_MAX = 24;

/**
 * A whole-number field with a sensible keyboard on a phone.
 */
export function NumberInput( { id, value, onChange, min = 0, max = 999 } ) {
	return html`
		<input
			class="sb-input sb-input--num"
			id=${ id }
			type="number"
			inputmode="numeric"
			min=${ min }
			max=${ max }
			value=${ value }
			onInput=${ ( e ) => onChange( e.target.value === '' ? '' : Number( e.target.value ) ) }
		/>
	`;
}

/**
 * A colour field: a swatch picker beside the hex, because both ways of choosing are useful.
 *
 * The text box is the one that matters. A brand colour usually arrives as a hex code from a
 * designer or an existing site and gets pasted; the native picker is for nudging it afterwards.
 *
 * The endpoint refuses a colour `sanitize_hex_color()` cannot read rather than storing the null it
 * answers with. Marking it invalid here as well means the owner sees the problem while looking at
 * the field, instead of in a toast after a save that changed nothing.
 */
export function ColorInput( { id, value, onChange } ) {
	const valid = /^#[0-9a-f]{6}$/i.test( value || '' );

	return html`
		<div class="sb-color">
			<input
				class="sb-color__swatch"
				type="color"
				aria-label="Colour picker"
				value=${ valid ? value : '#000000' }
				onInput=${ ( e ) => onChange( e.target.value ) }
			/>
			<input
				class=${ 'sb-input sb-color__hex' + ( valid ? '' : ' is-invalid' ) }
				id=${ id }
				type="text"
				spellcheck="false"
				autocomplete="off"
				placeholder="#000000"
				value=${ value || '' }
				onInput=${ ( e ) => onChange( e.target.value ) }
			/>
		</div>
		${ valid
			? null
			: html`<p class="sb-hint sb-hint--bad">Needs to be a six-digit hex colour, like #1a2b3c.</p>` }
	`;
}

/**
 * One field, rendered from what the schema says it is.
 *
 * The schema is generated from the theme's own registries, so a colour token or contact field
 * added to the theme should appear here without anyone editing the plugin. That only holds if the
 * control is picked from `spec.type` rather than from a list of keys written out by hand.
 *
 * @param {object}   props
 * @param {string}   props.name     Theme mod name.
 * @param {object}   props.spec     Schema entry: type, label, default.
 * @param {*}        props.value    Current value.
 * @param {Function} props.onChange Called with the new value.
 * @param {string}   [props.hint]   Extra guidance below the control.
 */
export function SchemaField( { name, spec, value, onChange, hint } ) {
	const id = 'sf-' + name;

	if ( spec.type === 'media' ) {
		return html`<${ MediaField } label=${ spec.label } value=${ value } onChange=${ onChange } hint=${ hint } />`;
	}

	if ( spec.type === 'bool' ) {
		return html`<${ Switch } checked=${ !! value } label=${ spec.label } hint=${ hint } onChange=${ onChange } />`;
	}

	const control =
		spec.type === 'color'
			? html`<${ ColorInput } id=${ id } value=${ value } onChange=${ onChange } />`
			: spec.type === 'int'
			? html`<${ NumberInput } id=${ id } value=${ value } onChange=${ onChange } />`
			: spec.type === 'url'
			? html`<${ LinkInput } id=${ id } value=${ value } onChange=${ onChange } />`
			: html`<input
					class="sb-input"
					id=${ id }
					type=${ spec.type === 'email' ? 'email' : 'text' }
					autocomplete="off"
					value=${ value === undefined || value === null ? '' : value }
					onInput=${ ( e ) => onChange( e.target.value ) }
			  />`;

	return html`<${ Field } label=${ spec.label } id=${ id } hint=${ hint }>${ control }<//>`;
}

/**
 * Split a group's fields into the cards a screen wants, keeping whatever it did not name.
 *
 * The trailing "Other" bucket is the point. A contact field added to the theme lands there instead
 * of vanishing from the CMS because nobody remembered to list it — the same promise the generated
 * schema makes, carried one layer further up.
 *
 * @param {object}   fields Schema fields for the group.
 * @param {object[]} cards  [{ title, lead, keys: [ themeModName… ], hints }]
 * @return {object[]} Cards with a resolved `fields` array; empty ones are dropped.
 */
export function layoutFields( fields, cards ) {
	// `fields` is null until the fetch lands, and every caller runs this during the same render
	// that SettingsPage spends showing a spinner. Reading `fields[ key ]` off null threw on the
	// first paint of both screens that use it, which took the whole app down rather than showing
	// the loading state it was already rendering.
	const safe = fields || {};

	const claimed = new Set();
	const out = [];

	( cards || [] ).forEach( ( card ) => {
		const resolved = ( card.keys || [] )
			.filter( ( key ) => safe[ key ] )
			.map( ( key ) => {
				claimed.add( key );
				return { name: key, spec: safe[ key ] };
			} );

		if ( resolved.length ) {
			out.push( { ...card, fields: resolved } );
		}
	} );

	const rest = Object.keys( safe )
		.filter( ( key ) => ! claimed.has( key ) )
		.map( ( key ) => ( { name: key, spec: safe[ key ] } ) );

	if ( rest.length ) {
		out.push( { title: 'Other', fields: rest } );
	}

	return out;
}
