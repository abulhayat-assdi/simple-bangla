/*
 * A small rich-text editor.
 *
 * Product descriptions were plain textareas holding raw HTML, and the owner asked not to have to
 * write tags. The two no-build alternatives were WordPress's own TinyMCE — jQuery, ~300 KB, and
 * wp-admin's stylesheets arriving in a page built specifically to avoid them — and this: a
 * contenteditable box with a toolbar, in the same vanilla style as the rest of the plugin.
 *
 * `document.execCommand` is formally deprecated and has no replacement. Every browser still ships
 * it, and the alternative is hand-writing selection and range surgery for bold, lists and links,
 * which is a far larger surface to get wrong than the one deprecation warning it costs. If it ever
 * stops working, the fallback is already here: the HTML view is a plain textarea over the same
 * value, and nothing about the stored data changes.
 *
 * What is stored is HTML, unchanged in shape from what the textarea stored before — so a product
 * written in the old screen opens here correctly, and WooCommerce, the storefront template and the
 * wp-admin editor all keep reading the same field.
 */

import { html, useRef, useEffect, useState, useCallback, Icon } from './ui.js';
import { MediaPicker } from './media.js';

/**
 * The toolbar.
 *
 * Deliberately short. A product description is a paragraph, a list of specifications and the
 * occasional bold line; a font-size dropdown and a colour picker would invite a description that
 * fights the storefront's own typography, which the theme then has to defend against.
 */
const TOOLS = [
	{ cmd: 'bold', label: 'Bold', text: 'B', style: 'font-weight:800' },
	{ cmd: 'italic', label: 'Italic', text: 'I', style: 'font-style:italic' },
	{ cmd: 'formatBlock', value: 'h2', label: 'Heading', text: 'H2' },
	{ cmd: 'formatBlock', value: 'h3', label: 'Sub-heading', text: 'H3' },
	{ cmd: 'formatBlock', value: 'p', label: 'Normal text', text: '¶' },
	{ cmd: 'insertUnorderedList', label: 'Bulleted list', text: '••' },
	{ cmd: 'insertOrderedList', label: 'Numbered list', text: '1.' },
];

/**
 * Strip everything a pasted fragment carries that this editor does not offer.
 *
 * Pasting from Word, Google Docs or another shop's product page brings inline font stacks, colours,
 * class names and sometimes a `<style>` block. Left alone they end up stored in the description and
 * printed on the storefront, where a 14px Calibri paragraph in the middle of the page is the result.
 * The allowed list is exactly what the toolbar can produce, plus links and images.
 *
 * This is a tidiness measure, not a security boundary — the real one is WordPress's own kses on the
 * way in, which is what actually decides what a product description may contain.
 *
 * @param {string} dirty Pasted HTML.
 * @return {string} Clean HTML.
 */
export function cleanPastedHtml( dirty ) {
	const allowed = new Set( [
		'P', 'BR', 'B', 'STRONG', 'I', 'EM', 'U', 'H2', 'H3', 'H4',
		'UL', 'OL', 'LI', 'A', 'IMG', 'BLOCKQUOTE', 'DIV', 'SPAN',
	] );

	// DOMParser rather than innerHTML on a detached div: a parsed document is inert, so a pasted
	// <img src=x onerror=…> never fires while it is being cleaned.
	const doc = new DOMParser().parseFromString( '<body>' + dirty + '</body>', 'text/html' );

	doc.body.querySelectorAll( 'style, script, meta, link' ).forEach( ( node ) => node.remove() );

	doc.body.querySelectorAll( '*' ).forEach( ( node ) => {
		if ( ! allowed.has( node.tagName ) ) {
			node.replaceWith( ...node.childNodes );
			return;
		}

		[ ...node.attributes ].forEach( ( attr ) => {
			const name = attr.name.toLowerCase();
			const keep =
				( 'A' === node.tagName && ( 'href' === name || 'target' === name || 'rel' === name ) ) ||
				( 'IMG' === node.tagName && ( 'src' === name || 'alt' === name ) );

			if ( ! keep ) {
				node.removeAttribute( attr.name );
			}
		} );

		// A span with nothing left on it was only ever carrying formatting.
		if ( 'SPAN' === node.tagName || 'DIV' === node.tagName ) {
			if ( ! node.attributes.length && 'SPAN' === node.tagName ) {
				node.replaceWith( ...node.childNodes );
			}
		}
	} );

	return doc.body.innerHTML;
}

/**
 * A rich-text field.
 *
 * @param {object}   props
 * @param {string}   props.value    Current HTML.
 * @param {Function} props.onChange Called with the new HTML.
 * @param {string}   [props.id]     For the label to point at.
 * @param {number}   [props.rows]   Rough starting height, in lines.
 */
export function RichText( { value, onChange, id, rows = 10 } ) {
	const box = useRef( null );
	const [ source, setSource ] = useState( false );
	const [ picking, setPicking ] = useState( false );

	/*
	 * The editable box owns its own DOM while it has focus — writing `value` back into it on every
	 * render would destroy the caret on every keystroke. So the value is pushed in only when it
	 * differs from what is already there, which happens when the record loads, when the HTML view
	 * is edited, and when a different product is opened.
	 */
	useEffect( () => {
		if ( ! source && box.current && box.current.innerHTML !== ( value || '' ) ) {
			box.current.innerHTML = value || '';
		}
	}, [ value, source ] );

	const emit = useCallback( () => {
		if ( box.current ) {
			onChange( box.current.innerHTML );
		}
	}, [ onChange ] );

	const run = ( tool ) => {
		// Without this the button steals focus from the box and execCommand has no selection to
		// act on, so the first click of every session appears to do nothing.
		if ( box.current ) {
			box.current.focus();
		}

		document.execCommand( tool.cmd, false, tool.value || null );
		emit();
	};

	const addLink = () => {
		const url = window.prompt( 'Link address', 'https://' );

		if ( ! url ) {
			return;
		}

		box.current.focus();
		document.execCommand( 'createLink', false, url );
		emit();
	};

	const addImage = ( chosen ) => {
		const attachment = ( chosen || [] )[ 0 ];

		if ( ! attachment ) {
			return;
		}

		box.current.focus();
		// The full-size URL, not a thumbnail: the storefront's own stylesheet caps the width, and a
		// 150px rendition stretched to fill it is the one mistake this control can make that only
		// shows up on the live product page.
		document.execCommand( 'insertImage', false, attachment.source_url );
		emit();
	};

	return html`
		<div class="sb-rte">
			<div class="sb-rte__bar" role="toolbar" aria-label="Formatting">
				${ TOOLS.map(
					( tool ) => html`
						<button
							key=${ tool.cmd + ( tool.value || '' ) }
							class="sb-rte__tool"
							type="button"
							title=${ tool.label }
							aria-label=${ tool.label }
							disabled=${ source }
							style=${ tool.style || null }
							onMouseDown=${ ( e ) => e.preventDefault() }
							onClick=${ () => run( tool ) }
						>
							${ tool.text }
						</button>
					`
				) }

				<button
					class="sb-rte__tool"
					type="button"
					title="Link"
					aria-label="Link"
					disabled=${ source }
					onMouseDown=${ ( e ) => e.preventDefault() }
					onClick=${ addLink }
				>
					🔗
				</button>

				<button
					class="sb-rte__tool"
					type="button"
					title="Image"
					aria-label="Image"
					disabled=${ source }
					onMouseDown=${ ( e ) => e.preventDefault() }
					onClick=${ () => setPicking( true ) }
				>
					<${ Icon } name="image" size=${ 15 } />
				</button>

				<span class="sb-rte__spacer"></span>

				<button
					class=${ 'sb-rte__tool sb-rte__tool--text' + ( source ? ' is-on' : '' ) }
					type="button"
					title="Edit the HTML directly"
					onMouseDown=${ ( e ) => e.preventDefault() }
					onClick=${ () => setSource( ! source ) }
				>
					HTML
				</button>
			</div>

			${ source
				? html`
						<textarea
							class="sb-input sb-textarea sb-rte__source"
							id=${ id }
							rows=${ rows }
							value=${ value || '' }
							onInput=${ ( e ) => onChange( e.target.value ) }
						></textarea>
				  `
				: html`
						<div
							class="sb-rte__box"
							id=${ id }
							ref=${ box }
							contenteditable="true"
							role="textbox"
							aria-multiline="true"
							style=${ 'min-height:' + rows * 1.7 + 'em' }
							onInput=${ emit }
							onBlur=${ emit }
							onPaste=${ ( event ) => {
								event.preventDefault();

								const clip = event.clipboardData;
								const asHtml = clip.getData( 'text/html' );

								// insertHTML rather than assigning innerHTML, so the paste lands at the
								// caret and stays on the undo stack.
								document.execCommand(
									'insertHTML',
									false,
									asHtml ? cleanPastedHtml( asHtml ) : escapeText( clip.getData( 'text/plain' ) )
								);

								emit();
							} }
						></div>
				  ` }

			${ picking
				? html`<${ MediaPicker } onSelect=${ addImage } onClose=${ () => setPicking( false ) } />`
				: null }
		</div>
	`;
}

/**
 * Plain text, safe to hand to insertHTML.
 *
 * A pasted plain-text block containing "<" would otherwise be parsed as markup, which is how a
 * pasted specification list quietly loses half its lines.
 *
 * @param {string} text Plain text.
 * @return {string}
 */
function escapeText( text ) {
	return ( text || '' )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /\n/g, '<br>' );
}
