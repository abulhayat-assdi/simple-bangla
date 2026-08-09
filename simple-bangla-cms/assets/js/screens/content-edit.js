/*
 * Writing a page.
 *
 * Title, body, address, whether it is published and whether it is linked in the footer — the five
 * things a shop owner actually decides about an About Us page. Everything else core's page editor
 * offers (templates, featured images, parents, comment settings) is left out: this theme renders
 * every page the same way, so those controls would be five more questions with one answer each.
 *
 * The body is the plugin's own rich-text control, the same one product descriptions use, and what
 * is stored is plain HTML in `post_content` — so a page written here opens correctly in wp-admin
 * and vice versa, and nothing about the storefront's rendering changes.
 */

import {
	html,
	useState,
	useEffect,
	useCallback,
	Card,
	Field,
	Select,
	Switch,
	Confirm,
	ErrorBox,
	toast,
} from '../ui.js';
import { api, SB } from '../api.js';
import { navigate, href, onLinkClick } from '../router.js';
import { RichText } from '../editor.js';
import { FOOTER_META, decodeEntities, isFrontPage, isStorePage } from './content.js';

/** What a brand-new page starts as. Published, because a page nobody can read is rarely the goal. */
const BLANK = {
	title: '',
	slug: '',
	status: 'publish',
	content: '',
	footer: false,
	link: '',
	frontPage: false,
	storePage: false,
};

export function PageEdit( { id } ) {
	const isNew = id === 'new';

	const [ form, setForm ] = useState( isNew ? { ...BLANK } : null );
	const [ dirty, setDirty ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ confirming, setConfirming ] = useState( false );

	/* -- load -- */

	useEffect( () => {
		if ( isNew ) {
			setForm( { ...BLANK } );
			setDirty( false );
			return;
		}

		setError( null );

		api( 'wp/v2/pages/' + id, { params: { context: 'edit' } } )
			.then( ( loaded ) => {
				setForm( toForm( loaded ) );
				setDirty( false );
			} )
			.catch( setError );
	}, [ id, isNew ] );

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
				meta: { [ FOOTER_META ]: !! form.footer },
			};

			/*
			 * Omitted rather than sent empty when it was never typed. WordPress makes one from the
			 * title on create; on an update an empty string asks it to regenerate — which silently
			 * changes the address of a page that was only being renamed, and breaks every link and
			 * every search result pointing at it.
			 */
			if ( form.slug.trim() ) {
				payload.slug = form.slug.trim();
			}

			const saved = isNew
				? await api( 'wp/v2/pages', { method: 'POST', body: payload } )
				: await api( 'wp/v2/pages/' + id, { method: 'POST', body: payload } );

			// The server's echo, not the local values: WordPress resolves the slug (deduplicating it
			// against every other page) and may sanitise the body, and the form should show what was
			// actually stored rather than what was asked for.
			setForm( toForm( saved ) );
			setDirty( false );
			toast( isNew ? 'Page created' : 'Page saved' );

			if ( isNew ) {
				navigate( '/content/' + saved.id, true );
			}
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setSaving( false );
		}
	};

	const remove = async () => {
		setSaving( true );

		try {
			// No `force`, so it goes to the trash and can be restored from WordPress. Unlike a
			// coupon, a page is often the only copy of writing nobody wants to do twice.
			await api( 'wp/v2/pages/' + id, { method: 'DELETE' } );
			setDirty( false );
			toast( 'Page moved to trash' );
			navigate( '/content', true );
		} catch ( e ) {
			toast( e.message, 'bad' );
			setSaving( false );
			setConfirming( false );
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
					<h1 class="sb-page__title">${ isNew ? 'New page' : form.title || 'Untitled' }</h1>
					<p class="sb-page__lead">
						${ isNew
							? 'It will be reachable at its own address as soon as it is published.'
							: html`<a href=${ form.link } target="_blank" rel="noopener">View on site</a>` }
					</p>
				</div>
				<div class="sb-row sb-page__actions">
					${ ! isNew && ! form.frontPage
						? html`<button class="sb-btn sb-btn--ghost" onClick=${ () => setConfirming( true ) }>Delete</button>`
						: null }
					<button class="sb-btn sb-btn--primary" onClick=${ save } disabled=${ saving }>
						${ saving ? 'Saving…' : dirty || isNew ? 'Save' : 'Saved' }
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

						<${ Switch }
							label="Show in the footer"
							hint=${ form.status === 'publish'
								? 'Lists this page in the first footer column, under the shop name. Ticked pages are the whole of that column.'
								: 'Only published pages appear in the footer, so this will do nothing until the status above is Published.' }
							checked=${ form.footer }
							onChange=${ ( footer ) => set( { footer } ) }
						/>
					<//>

					<${ Card } title="Address">
						<${ Field }
							label="Slug"
							id="pg-slug"
							hint=${ isNew && ! form.slug.trim()
								? 'Leave it empty and one is made from the title.'
								: address + ' — changing it breaks existing links to this page.' }
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

			${ confirming
				? html`<${ Confirm }
						title=${ 'Delete "' + ( form.title || 'this page' ) + '"?' }
						body=${ ( form.storePage
							? 'This is a store page. Deleting it breaks the part of the shop it renders until WooCommerce is pointed at a new one. '
							: '' ) +
						'It moves to the trash and can be restored from WordPress. Any menu or footer link to it will stop working.' }
						confirmLabel="Move to trash"
						busy=${ saving }
						onConfirm=${ remove }
						onClose=${ () => setConfirming( false ) }
				  />`
				: null }
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
		footer: !! ( page.meta && page.meta[ FOOTER_META ] ),
		link: page.link || '',
		frontPage: isFrontPage( page ),
		storePage: isStorePage( page ),
	};
}
