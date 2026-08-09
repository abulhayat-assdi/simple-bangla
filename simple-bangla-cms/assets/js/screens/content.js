/*
 * Site pages — About Us, Privacy Policy, Delivery & Return, and anything else the owner writes.
 *
 * Built on core's own `wp/v2/pages`, which has covered pages since WordPress 4.7: listing, search,
 * statuses, slugs, revisions and the trash. The plugin adds one thing to it — the theme's
 * "show in the footer" tick, registered for REST in inc/rest-pages.php — and nothing else.
 *
 * Two kinds of page turn up in this list and neither is prose:
 *
 * - The **homepage**, which the theme builds from the Homepage screens. Whatever is typed into its
 *   body is never printed, so the row says so rather than letting the owner write a page nobody
 *   will ever see.
 * - **Store pages** — Cart, Checkout, My Account — whose entire body is one WooCommerce shortcode.
 *   Rewriting one of those in a rich-text box is how a shop loses its checkout, so they are marked
 *   here and open in the HTML view with a warning.
 */

import {
	html,
	useState,
	useEffect,
	useCallback,
	Badge,
	Pagination,
	EmptyState,
	ErrorBox,
	Select,
} from '../ui.js';
import { apiList, SB } from '../api.js';
import { titleText } from '../text.js';
import { navigate, href, onLinkClick } from '../router.js';

const PER_PAGE = 20;

/** The meta key the theme reads for footer links. Defined in simple-bangla/inc/pages.php. */
export const FOOTER_META = '_simple_bangla_footer_link';

/** Only these fields are asked for; a page body is not needed twenty at a time except to sniff it. */
const FIELDS = 'id,title,slug,status,link,meta,content';

export function Pages() {
	const [ rows, setRows ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ total, setTotal ] = useState( 0 );
	const [ pages, setPages ] = useState( 1 );
	const [ busy, setBusy ] = useState( true );
	const [ error, setError ] = useState( null );

	const [ search, setSearch ] = useState( '' );
	const [ status, setStatus ] = useState( '' );

	const load = useCallback( async () => {
		setBusy( true );
		setError( null );

		try {
			const result = await apiList( 'wp/v2/pages', {
				params: {
					page,
					per_page: PER_PAGE,
					search,
					// Drafts are the point of the filter, and core only returns them when they are
					// asked for by name — the default is published pages only.
					status: status || 'publish,draft,pending,private',
					context: 'edit',
					orderby: 'title',
					order: 'asc',
					_fields: FIELDS,
				},
			} );

			setRows( result.items );
			setTotal( result.total );
			setPages( result.pages );
		} catch ( e ) {
			setError( e );
		} finally {
			setBusy( false );
		}
	}, [ page, search, status ] );

	// Debounced, or every keystroke in the search box is a request.
	useEffect( () => {
		const timer = setTimeout( load, search ? 300 : 0 );
		return () => clearTimeout( timer );
	}, [ load, search ] );

	const inFooter = rows.filter( isInFooter ).length;

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Content Pages</h1>
					<p class="sb-page__lead">
						${ total } pages. This is where About Us, Privacy Policy and Delivery & Return are written.
					</p>
				</div>
				<button class="sb-btn sb-btn--primary" onClick=${ () => navigate( '/content/new' ) }>
					Add page
				</button>
			</div>

			<p class="sb-hint">
				Tick <strong>Show in the footer</strong> on a page to list it in the first footer column,
				under the shop name.
				${ inFooter
					? ' Ticked pages are the whole of that column — untick them all to go back to the menu assigned there.'
					: ' While no page is ticked, the footer keeps showing exactly what it shows now.' }
			</p>

			<div class="sb-toolbar">
				<input
					class="sb-input sb-toolbar__search"
					type="search"
					placeholder="Search pages"
					value=${ search }
					onInput=${ ( e ) => {
						setSearch( e.target.value );
						setPage( 1 );
					} }
				/>
				<${ Select }
					value=${ status }
					onChange=${ ( value ) => {
						setStatus( value );
						setPage( 1 );
					} }
					options=${ [
						{ value: '', label: 'Any status' },
						{ value: 'publish', label: 'Published' },
						{ value: 'draft', label: 'Draft' },
						{ value: 'private', label: 'Private' },
					] }
				/>
			</div>

			${ error ? html`<${ ErrorBox } error=${ error } onRetry=${ load } />` : null }

			${ busy && ! rows.length
				? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
				: rows.length
				? html`
						<div class=${ 'sb-table-wrap' + ( busy ? ' is-busy' : '' ) }>
							<table class="sb-table">
								<thead>
									<tr>
										<th>Page</th>
										<th>Address</th>
										<th>Footer</th>
										<th>Status</th>
										<th class="sb-table__actions-col"><span class="sb-sr">Actions</span></th>
									</tr>
								</thead>
								<tbody>
									${ rows.map( ( row ) => {
										const to = '/content/' + row.id;

										return html`
											<tr key=${ row.id }>
												<td>
													<a class="sb-table__name" href=${ href( to ) } onClick=${ ( e ) => onLinkClick( e, to ) }>
														${ pageTitle( row ) }
													</a>
													${ isFrontPage( row )
														? html` <${ Badge } tone="warn">Homepage<//>`
														: isStorePage( row )
														? html` <${ Badge } tone="warn">Store page<//>`
														: null }
												</td>
												<td class="sb-table__sub">/${ row.slug }/</td>
												<td>
													${ isInFooter( row ) ? html`<${ Badge } tone="ok">In footer<//>` : '—' }
												</td>
												<td>
													${ row.status === 'publish'
														? html`<${ Badge } tone="ok">Published<//>`
														: html`<${ Badge }>${ row.status }<//>` }
												</td>
												<td>
													<div class="sb-row">
														<a class="sb-btn sb-btn--ghost sb-btn--sm" href=${ href( to ) } onClick=${ ( e ) => onLinkClick( e, to ) }>
															Edit
														</a>
														<a class="sb-btn sb-btn--ghost sb-btn--sm" href=${ row.link } target="_blank" rel="noopener">
															View
														</a>
													</div>
												</td>
											</tr>
										`;
									} ) }
								</tbody>
							</table>
						</div>

						<${ Pagination } page=${ page } pages=${ pages } total=${ total } noun="pages" onPage=${ setPage } />
				  `
				: html`<${ EmptyState }
						title="No pages found"
						body=${ search ? 'Nothing matches that search.' : 'Add one to start writing.' }
				  />` }
		</div>
	`;
}

/* ------------------------------------------------------------------ shared with the editor */

/**
 * A page's title, as text.
 *
 * The theme's own default page is stored as "Delivery &amp; Return Policy" and a text node prints
 * that entity literally, so it goes through the shared decoder in text.js.
 *
 * @param {object} page Page record.
 * @return {string}
 */
export function pageTitle( page ) {
	return titleText( page.title ) || '(no title)';
}

/** Whether a page is ticked for the footer. */
export function isInFooter( page ) {
	return !! ( page.meta && page.meta[ FOOTER_META ] );
}

/**
 * Whether this page is the site's front page.
 *
 * Compared by address rather than by an id from the boot payload: the front page is the one whose
 * permalink *is* the site, which is true however it was configured and needs nothing added to
 * /session to find out.
 *
 * @param {object} page Page record.
 * @return {boolean}
 */
export function isFrontPage( page ) {
	const home = ( SB.store && SB.store.url ) || '';

	return !! home && !! page.link && trimSlash( page.link ) === trimSlash( home );
}

/**
 * Whether the body is a store page rather than prose.
 *
 * Sniffed from the content instead of asked of WooCommerce, which would have cost a second request
 * on every visit — and one needing `manage_woocommerce`, a capability this screen does not
 * otherwise require. A false positive costs a warning on a page that did not need it; a false
 * negative costs nothing that the HTML view cannot undo.
 *
 * @param {object} page Page record.
 * @return {boolean}
 */
export function isStorePage( page ) {
	const body = ( page.content && ( page.content.raw ?? '' ) ) || '';

	return /\[(woocommerce_|products|shop_messages|order_tracking)/.test( body ) || body.includes( 'wp:woocommerce/' );
}

function trimSlash( url ) {
	return String( url ).replace( /\/+$/, '' );
}
