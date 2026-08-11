/*
 * The pages the shop's footer links to — About Us, Privacy Policy, Refund and Returns, and whatever
 * else is in those two columns.
 *
 * **This screen used to list every page WordPress contained** and the owner asked for it not to
 * (2026-08-10). That list was fourteen rows on their shop, of which four were writing and the rest
 * were Cart, Checkout, My account, Sample Page and the homepage — things whose bodies are a
 * WooCommerce shortcode or are never printed at all. Offering them for editing was an invitation to
 * break the checkout while looking for the About Us page.
 *
 * So the list is now derived from the footer rather than from the page table, and the theme is what
 * derives it: `/footer-pages` asks `simple_bangla_footer_pages()`, which follows the same tick →
 * assigned menu → theme defaults order the footer itself renders with. Change the footer menu and
 * this screen changes with it.
 *
 * There is deliberately no "Add page" here, and no delete. This screen answers one question — what
 * do these pages say — and *which* pages appear is the Menu screen's question. A new page created
 * here could not have shown up in this list anyway, and a page deleted here would have taken a
 * footer link with it.
 */

import { html, useState, useEffect, useCallback, Badge, EmptyState, ErrorBox } from '../ui.js';
import { api, apiList, SB } from '../api.js';
import { titleText } from '../text.js';
import { href, onLinkClick } from '../router.js';

/** Enough to draw a row and to sniff a store page; `content` is why the request is in edit context. */
const FIELDS = 'id,title,slug,status,link,meta,content';

export function Pages() {
	const [ rows, setRows ] = useState( null );
	const [ sources, setSources ] = useState( {} );
	const [ error, setError ] = useState( null );

	const load = useCallback( async () => {
		setError( null );

		try {
			const footer = await api( '/footer-pages' );

			if ( ! footer.pages.length ) {
				setSources( footer.sources || {} );
				setRows( [] );
				return;
			}

			const result = await apiList( 'wp/v2/pages', {
				params: {
					include: footer.pages.join( ',' ),
					// The footer's own order, not WordPress's. `include` alone sorts by date, which
					// would list the pages in the order they were created rather than the order the
					// customer sees them in.
					orderby: 'include',
					per_page: 100,
					// A page linked from the footer while it is a draft is exactly the case worth
					// seeing on this screen, and core returns drafts only when asked for by name.
					status: 'publish,draft,pending,private',
					context: 'edit',
					_fields: FIELDS,
				},
			} );

			setSources( footer.sources || {} );
			setRows( result.items );
		} catch ( e ) {
			setError( e );
			setRows( [] );
		}
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Content Pages</h1>
					<p class="sb-page__lead">
						The pages your footer links to. This is where the words on them are written.
					</p>
				</div>
			</div>

			<p class="sb-hint">${ whereFrom( sources ) }</p>

			${ error ? html`<${ ErrorBox } error=${ error } onRetry=${ load } />` : null }

			${ null === rows
				? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
				: rows.length
				? html`
						<div class="sb-table-wrap">
							<table class="sb-table">
								<thead>
									<tr>
										<th>Page</th>
										<th>Address</th>
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
													${ isStorePage( row ) ? html` <${ Badge } tone="warn">Store page<//>` : null }
												</td>
												<td class="sb-table__sub">/${ row.slug }/</td>
												<td>
													${ row.status === 'publish'
														? html`<${ Badge } tone="ok">Published<//>`
														: html`<${ Badge } tone="warn">${ row.status }<//>` }
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
				  `
				: html`<${ EmptyState }
						title="The footer has no page links"
						body="Both footer columns are empty, or everything in them points somewhere other than a page. Add page links to the footer menus on the Menu screen and they will appear here."
				  />` }
		</div>
	`;
}

/**
 * One sentence saying where this list comes from, so the owner knows where to change it.
 *
 * Both columns are usually the same kind of source, and saying it twice would be noise; they are
 * only distinguished when they genuinely differ.
 *
 * @param {object} sources Location to `ticked` | `menu` | `fallback`.
 * @return {string}
 */
function whereFrom( sources ) {
	const kinds = [ ...new Set( Object.values( sources ) ) ];

	if ( ! kinds.length ) {
		return '';
	}

	const said = {
		menu: 'come from the menus assigned to the footer — change which pages appear on the Menu screen',
		fallback: 'are the theme\'s defaults, because no menu is assigned to that footer column yet',
		ticked: 'are the ones ticked for the footer',
	};

	if ( 1 === kinds.length ) {
		return 'These ' + said[ kinds[ 0 ] ] + '.';
	}

	return (
		'The two footer columns are filled differently: one ' +
		said[ kinds[ 0 ] ] +
		', the other ' +
		said[ kinds[ 1 ] ] +
		'.'
	);
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

/**
 * Whether this page is the site's front page.
 *
 * Compared by address rather than by an id from the boot payload: the front page is the one whose
 * permalink *is* the site, which is true however it was configured and needs nothing added to
 * /session to find out.
 *
 * It is unlikely to appear in this list now — a shop does not usually link its homepage from its own
 * footer — but if it is linked there, the editor still has to say that nothing typed into it is
 * ever printed.
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
