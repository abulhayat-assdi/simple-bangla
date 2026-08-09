/*
 * Customers.
 *
 * WooCommerce's `wc/v3/customers`, read-only on purpose. Editing a customer's address here would
 * change it for future orders and not for the one being delivered, which is the opposite of what
 * anyone clicking "edit" on this screen would expect. What the shop actually needs from a customer
 * record is the phone number and what they have ordered before — so the row opens their order
 * history rather than a form.
 *
 * Registered accounts only, which is most of the list and none of the picture on a store like this
 * one: cash-on-delivery orders are usually placed as a guest, so the same phone number can have ten
 * orders and no account at all. The Orders screen searches by phone and is the honest way to find
 * someone; this screen says so rather than letting an empty result read as "no such customer".
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
} from '../ui.js';
import { apiList, money } from '../api.js';
import { href, onLinkClick } from '../router.js';

const PER_PAGE = 20;

export function Customers() {
	const [ rows, setRows ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ total, setTotal ] = useState( 0 );
	const [ pages, setPages ] = useState( 1 );
	const [ search, setSearch ] = useState( '' );
	const [ busy, setBusy ] = useState( true );
	const [ error, setError ] = useState( null );

	const load = useCallback( async () => {
		setBusy( true );
		setError( null );

		try {
			const result = await apiList( 'wc/v3/customers', {
				params: { page, per_page: PER_PAGE, search, orderby: 'registered_date', order: 'desc' },
			} );

			setRows( result.items );
			setTotal( result.total );
			setPages( result.pages );
		} catch ( e ) {
			setError( e );
		} finally {
			setBusy( false );
		}
	}, [ page, search ] );

	useEffect( () => {
		const timer = setTimeout( load, search ? 300 : 0 );
		return () => clearTimeout( timer );
	}, [ load, search ] );

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Customers</h1>
					<p class="sb-page__lead">${ total } registered account${ total === 1 ? '' : 's' }.</p>
				</div>
			</div>

			<p class="sb-hint">
				Only customers who created an account appear here. Most cash-on-delivery orders are
				placed as a guest — to find one of those, search by phone number on the
				${ ' ' }
				<a href=${ href( '/orders' ) } onClick=${ ( e ) => onLinkClick( e, '/orders' ) }>Orders</a>
				${ ' ' }screen.
			</p>

			<div class="sb-toolbar">
				<input
					class="sb-input sb-toolbar__search"
					type="search"
					placeholder="Search by name or email"
					value=${ search }
					onInput=${ ( e ) => {
						setSearch( e.target.value );
						setPage( 1 );
					} }
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
										<th>Customer</th>
										<th>Phone</th>
										<th>Orders</th>
										<th>Spent</th>
										<th>Joined</th>
									</tr>
								</thead>
								<tbody>
									${ rows.map( ( customer ) => html`<${ Row } key=${ customer.id } customer=${ customer } />` ) }
								</tbody>
							</table>
						</div>
				  `
				: html`<${ EmptyState }
						title="No customers found"
						body=${ search
							? 'Nothing matches that search. Guest orders are found on the Orders screen instead.'
							: 'Nobody has created an account yet.' }
				  />` }

			<${ Pagination } page=${ page } pages=${ pages } total=${ total } noun="customers" onPage=${ setPage } />
		</div>
	`;
}

function Row( { customer } ) {
	const name = [ customer.first_name, customer.last_name ].filter( Boolean ).join( ' ' ) || customer.username;
	const phone = ( customer.billing && customer.billing.phone ) || '';

	// The order list already knows how to search; sending the phone to it is more useful than a
	// customer page that would only repeat what is on this row.
	const path = '/orders?search=' + encodeURIComponent( phone || customer.email || '' );

	return html`
		<tr>
			<td>
				<span class="sb-table__name">${ name }</span>
				<div class="sb-table__sub">${ customer.email }</div>
			</td>
			<td>
				${ phone
					? html`<a href=${ href( path ) } onClick=${ ( e ) => onLinkClick( e, path ) }>${ phone }</a>`
					: html`<span class="sb-table__sub">—</span>` }
			</td>
			<td>
				${ customer.orders_count
					? html`<${ Badge } tone="ok">${ customer.orders_count }<//>`
					: html`<span class="sb-table__sub">0</span>` }
			</td>
			<td><span class="sb-price">${ money( customer.total_spent ) }</span></td>
			<td class="sb-table__sub">${ String( customer.date_created || '' ).slice( 0, 10 ) }</td>
		</tr>
	`;
}
