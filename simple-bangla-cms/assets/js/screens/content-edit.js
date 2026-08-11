/*
 * Writing a page.
 *
 * Title, body, address and whether it is published — the four things a shop owner actually decides
 * about an About Us page. Everything else core's page editor offers (templates, featured images,
 * parents, comment settings) is left out: this theme renders every page the same way, so those
 * controls would be more questions with one answer each.
 *
 * **Creating and deleting are gone** (owner's decision, 2026-08-10), along with the "show in the
 * footer" tick. The list this editor opens from is now derived from the footer rather than from the
 * page table, so a page created here could never have appeared in it, a page deleted here would
 * have taken a footer link with it, and unticking a page would have removed it from the only screen
 * that could tick it back. Which pages are in the footer is the Menu screen's question; this screen
 * answers what they say. The theme still honours a tick set before this change.
 *
 * The body is the plugin's own rich-text control, the same one product descriptions use, and what
 * is stored is plain HTML in `post_content` — so a page written here opens correctly in wp-admin
 * and vice versa, and nothing about the storefront's rendering changes.
 */

import { html, useState, useEffect, useCallback, Card, Field, Select, ErrorBox, toast } from '../ui.js';
import { api, SB } from '../api.js';
import { href, onLinkClick } from '../router.js';
import { RichText } from '../editor.js';
import { isFrontPage, isStorePage } from './content.js';
import { decodeEntities } from '../text.js';

export function PageEdit( { id } ) {
	const [ form, setForm ] = useState( null );
	const [ dirty, setDirty ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );

	/* -- load -- */

	useEffect( () => {
		setError( null );

		api( 'wp/v2/pages/' + id, { params: { context: 'edit' } } )
			.then( ( loaded ) => {
				setForm( toForm( loaded ) );
				setDirty( false );
			} )
			.catch( setError );
	}, [ id ] );

	// A page is a lot of typing to lose to a mistaken back gesture.
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

	const set = useCallback( ( patch ) => {
		setForm( ( current ) => ( { ...current, ...patch } ) );
		setDirty( true );
	}, [] );

	/* -- save -- */

	const save = async () => {
		if ( ! form.title.trim() ) {
			toast( 'Give the page a title before saving.', 'bad' );
			return;
		}

		setSaving( true );

		try {
			const payload = {
				title: form.title,
				status: form.status,
				content: form.content,
			};

			/*
			 * Omitted rather than sent empty when it was never typed. On an update an empty string
			 * asks WordPress to regenerate the slug — which silently changes the address of a page
			 * that was only being renamed, and breaks every link and every search result pointing at
			 * it, the footer's own link included.
			 */
			if ( form.slug.trim() ) {
				payload.slug = form.slug.trim();
			}

			const saved = await api( 'wp/v2/pages/' + id, { method: 'POST', body: payload } );

			// The server's echo, not the local values: WordPress resolves the slug (deduplicating it
			// against every other page) and may sanitise the body, and the form should show what was
			// actually stored rather than what was asked for.
			setForm( toForm( saved ) );
			setDirty( false );
			toast( 'Page saved' );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setSaving( false );
		}
	};

	/* -- render -- */

	if ( error ) {
		return html`<div class="sb-page"><${ ErrorBox } error=${ error } /></div>`;
	}

	if ( ! form ) {
		return html`<div class="sb-page"><div class="sb-media-loading"><span class="sb-spinner"></span></div></div>`;
	}

	const address = ( SB.store && SB.store.url ? SB.store.url.replace( /\/+$/, '' ) : '' ) + '/' + ( form.slug || '…' ) + '/';

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<p class="sb-crumb">
						<a href=${ href( '/content' ) } onClick=${ ( e ) => onLinkClick( e, '/content' ) }>Content Pages</a>
					</p>
					<h1 class="sb-page__title">${ form.title || 'Untitled' }</h1>
					<p class="sb-page__lead">
						<a href=${ form.link } target="_blank" rel="noopener">View on site</a>
					</p>
				</div>
				<div class="sb-row sb-page__actions">
					<button class="sb-btn sb-btn--primary" onClick=${ save } disabled=${ saving }>
						${ saving ? 'Saving…' : dirty ? 'Save' : 'Saved' }
					</button>
				</div>
			</div>

			<div class="sb-editor">
				<div class="sb-editor__main">
					<${ Card } title="Page">
						${ form.frontPage
							? html`<p class="sb-hint sb-hint--bad">
									This page is the homepage, and the theme builds the homepage from the Hero Slider,
									Hot Deals, Category Circles, Product Rows and Banners screens. Anything written
									here is stored but never shown.
							  </p>`
							: null }

						${ form.storePage
							? html`<p class="sb-hint sb-hint--bad">
									This is a store page — its body is a WooCommerce shortcode that renders the cart,
									the checkout or an account area. It opens in the HTML view for that reason. Edit
									the text around the shortcode if you need to, but leave the shortcode itself
									alone or the page stops working.
							  </p>`
							: null }

						<${ Field } label="Title" id="pg-title">
							<input
								class="sb-input"
								id="pg-title"
								value=${ form.title }
								onInput=${ ( e ) => set( { title: e.target.value } ) }
							/>
						<//>

						<${ Field } label="Content" id="pg-content">
							<${ RichText }
								id="pg-content"
								rows=${ 16 }
								startInHtml=${ form.storePage }
								value=${ form.content }
								onChange=${ ( content ) => set( { content } ) }
							/>
						<//>
					<//>
				</div>

				<div class="sb-editor__side">
					<${ Card } title="Visibility">
						<${ Field } label="Status" id="pg-status">
							<${ Select }
								id="pg-status"
								value=${ form.status }
								onChange=${ ( status ) => set( { status } ) }
								options=${ [
									{ value: 'publish', label: 'Published' },
									{ value: 'draft', label: 'Draft — not visible to customers' },
									{ value: 'private', label: 'Private — visible to you only' },
								] }
							/>
						<//>

						<p class="sb-hint">
							This page is linked from your footer. Only published pages appear there, so
							Draft or Private takes the link away until it is published again.
						</p>
					<//>

					<${ Card } title="Address">
						<${ Field }
							label="Slug"
							id="pg-slug"
							hint=${ address + ' — changing it breaks existing links to this page.' }
						>
							<input
								class="sb-input"
								id="pg-slug"
								spellcheck="false"
								autocomplete="off"
								value=${ form.slug }
								onInput=${ ( e ) => set( { slug: e.target.value } ) }
							/>
						<//>
					<//>
				</div>
			</div>

		</div>
	`;
}

/**
 * A page record as the form holds it.
 *
 * The title is decoded on the way in and therefore saved decoded — the theme's own default pages
 * are stored with an `&amp;` in them, and an editor that showed that to the owner would be asking
 * them to understand HTML entities to fix a typo.
 *
 * @param {object} page Page record from the REST API, in edit context.
 * @return {object}
 */
function toForm( page ) {
	return {
		title: decodeEntities( ( page.title && page.title.raw ) || '' ),
		slug: page.slug || '',
		status: page.status || 'publish',
		content: ( page.content && page.content.raw ) || '',
		link: page.link || '',
		frontPage: isFrontPage( page ),
		storePage: isStorePage( page ),
	};
}
