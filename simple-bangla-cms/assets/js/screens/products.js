/*
 * The product list.
 *
 * Reads WooCommerce's own `wc/v3/products` rather than a bespoke endpoint. Filtering, searching
 * and paging are all query parameters WooCommerce already implements and keeps correct across
 * its own upgrades; re-implementing them here would have been a second thing to maintain and a
 * second place for the two to disagree.
 */

import {
	html,
	useState,
	useEffect,
	useCallback,
	Icon,
	Badge,
	Pagination,
	EmptyState,
	ErrorBox,
	Select,
} from '../ui.js';
import { apiList, money } from '../api.js';
import { stripTags, titleText } from '../text.js';
import { navigate, href, onLinkClick } from '../router.js';

const PER_PAGE = 20;

export function Products() {
	const [ rows, setRows ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ total, setTotal ] = useState( 0 );
	const [ pages, setPages ] = useState( 1 );
	const [ busy, setBusy ] = useState( true );
	const [ error, setError ] = useState( null );

	const [ search, setSearch ] = useState( '' );
	const [ category, setCategory ] = useState( '' );
	const [ status, setStatus ] = useState( '' );
	const [ stock, setStock ] = useState( '' );

	const [ categories, setCategories ] = useState( [] );

	useEffect( () => {
		apiList( 'wc/v3/products/categories', { params: { per_page: 100, orderby: 'name' } } )
			.then( ( r ) => setCategories( r.items ) )
			.catch( () => setCategories( [] ) );
	}, [] );

	const load = useCallback( async () => {
		setBusy( true );
		setError( null );

		try {
			const result = await apiList( 'wc/v3/products', {
				params: {
					page,
					per_page: PER_PAGE,
					search,
					category,
					status,
					stock_status: stock,
					orderby: 'date',
					order: 'desc',
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
	}, [ page, search, category, status, stock ] );

	// Debounced, or every keystroke in the search box is a request.
	useEffect( () => {
		const timer = setTimeout( load, search ? 300 : 0 );
		return () => clearTimeout( timer );
	}, [ load, search ] );

	// Any filter change invalidates the page number: page 4 of the old result set is meaningless.
	const changeFilter = ( setter ) => ( value ) => {
		setter( value );
		setPage( 1 );
	};

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Products</h1>
					<p class="sb-page__lead">${ total } in the catalogue.</p>
				</div>
				<button class="sb-btn sb-btn--primary" onClick=${ () => navigate( '/products/new' ) }>
					Add product
				</button>
			</div>

			<div class="sb-toolbar">
				<input
					class="sb-input sb-toolbar__search"
					type="search"
					placeholder="Search by name or SKU"
					value=${ search }
					onInput=${ ( e ) => changeFilter( setSearch )( e.target.value ) }
				/>
				<${ Select }
					value=${ category }
					onChange=${ changeFilter( setCategory ) }
					options=${ [
						{ value: '', label: 'All categories' },
						...categories.map( ( c ) => ( { value: c.id, label: c.name + ' (' + c.count + ')' } ) ),
					] }
				/>
				<${ Select }
					value=${ status }
					onChange=${ changeFilter( setStatus ) }
					options=${ [
						{ value: '', label: 'Any status' },
						{ value: 'publish', label: 'Published' },
						{ value: 'draft', label: 'Draft' },
						{ value: 'pending', label: 'Pending' },
						{ value: 'private', label: 'Private' },
					] }
				/>
				<${ Select }
					value=${ stock }
					onChange=${ changeFilter( setStock ) }
					options=${ [
						{ value: '', label: 'Any stock' },
						{ value: 'instock', label: 'In stock' },
						{ value: 'outofstock', label: 'Out of stock' },
						{ value: 'onbackorder', label: 'On backorder' },
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
										<th class="sb-table__thumb-col"><span class="sb-sr">Image</span></th>
										<th>Product</th>
										<th>Price</th>
										<th>Stock</th>
										<th>Categories</th>
										<th>Status</th>
									</tr>
								</thead>
								<tbody>
									${ rows.map( ( product ) => html`<${ Row } key=${ product.id } product=${ product } />` ) }
								</tbody>
							</table>
						</div>
				  `
				: html`<${ EmptyState }
						title="No products match"
						body=${ search || category || status || stock
							? 'Try clearing the filters.'
							: 'Add your first product to get started.' }
				  />` }

			<${ Pagination } page=${ page } pages=${ pages } total=${ total } noun="products" onPage=${ setPage } />
		</div>
	`;
}

function Row( { product } ) {
	const image = product.images && product.images[ 0 ];
	const path = '/products/' + product.id;

	return html`
		<tr>
			<td>
				<div class="sb-table__thumb">
					${ image
						? html`<img src=${ image.src } alt="" loading="lazy" />`
						: html`<span class="sb-thumb__empty"><${ Icon } name="image" size=${ 18 } /></span>` }
				</div>
			</td>
			<td>
				<a class="sb-table__name" href=${ href( path ) } onClick=${ ( e ) => onLinkClick( e, path ) }>
					${ titleText( product.name ) }
				</a>
				<div class="sb-table__sub">
					${ product.sku ? 'SKU ' + product.sku : 'No SKU' }
					${ product.type !== 'simple' ? html` · <${ Badge } tone="muted">${ product.type }<//>` : null }
				</div>
			</td>
			<td><${ Price } product=${ product } /></td>
			<td><${ Stock } product=${ product } /></td>
			<td class="sb-table__sub">
				${ product.categories && product.categories.length
					? product.categories.map( ( c ) => c.name ).join( ', ' )
					: '—' }
			</td>
			<td><${ Status } status=${ product.status } /></td>
		</tr>
	`;
}

function Price( { product } ) {
	// A variable product reports a range in price_html; its own regular_price is empty, so
	// printing that field would show nothing at all for every variable product in the list.
	if ( product.type === 'variable' ) {
		return html`<span class="sb-price">${ stripTags( product.price_html ) || '—' }</span>`;
	}

	const onSale = product.sale_price && product.sale_price !== product.regular_price;

	return html`
		<span class="sb-price">
			${ onSale
				? html`<del>${ money( product.regular_price ) }</del> <ins>${ money( product.sale_price ) }</ins>`
				: product.price
				? money( product.price )
				: '—' }
		</span>
	`;
}

function Stock( { product } ) {
	if ( product.manage_stock ) {
		const qty = Number( product.stock_quantity || 0 );

		return html`<${ Badge } tone=${ qty <= 0 ? 'bad' : qty <= 2 ? 'warn' : 'ok' }>${ qty } in stock<//>`;
	}

	const labels = {
		instock: [ 'ok', 'In stock' ],
		outofstock: [ 'bad', 'Out of stock' ],
		onbackorder: [ 'warn', 'On backorder' ],
	};
	const [ tone, label ] = labels[ product.stock_status ] || [ 'muted', product.stock_status ];

	return html`<${ Badge } tone=${ tone }>${ label }<//>`;
}

function Status( { status } ) {
	const tones = { publish: 'ok', draft: 'muted', pending: 'warn', private: 'muted' };
	const labels = { publish: 'Published', draft: 'Draft', pending: 'Pending', private: 'Private' };

	return html`<${ Badge } tone=${ tones[ status ] || 'muted' }>${ labels[ status ] || status }<//>`;
}

