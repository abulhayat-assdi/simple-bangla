/*
 * The order list.
 *
 * Built around what a cash-on-delivery store actually does all day: a pile of new orders arrives,
 * each is confirmed by phone, and then a batch of them moves to the next status together. Hence
 * the checkboxes and the bulk status change — clicking into 40 orders one at a time to mark them
 * processing is the difference between this screen being used and being abandoned.
 */

import {
	html,
	useState,
	useEffect,
	useCallback,
	Badge,
	Select,
	Pagination,
	EmptyState,
	ErrorBox,
	toast,
} from '../ui.js';
import { api, apiList, money } from '../api.js';
import { href, onLinkClick } from '../router.js';
import { statusLabel, statusTone, statusOptions, customerName, dateTime, itemCount } from '../order-utils.js';

const PER_PAGE = 20;

export function Orders() {
	const [ rows, setRows ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ total, setTotal ] = useState( 0 );
	const [ pages, setPages ] = useState( 1 );
	const [ busy, setBusy ] = useState( true );
	const [ error, setError ] = useState( null );

	const [ search, setSearch ] = useState( '' );
	const [ status, setStatus ] = useState( '' );

	const [ selected, setSelected ] = useState( [] );
	const [ bulkStatus, setBulkStatus ] = useState( 'processing' );
	const [ applying, setApplying ] = useState( false );

	const load = useCallback( async () => {
		setBusy( true );
		setError( null );

		try {
			const result = await apiList( 'wc/v3/orders', {
				params: {
					page,
					per_page: PER_PAGE,
					search,
					status,
					orderby: 'date',
					order: 'desc',
				},
			} );

			setRows( result.items );
			setTotal( result.total );
			setPages( result.pages );
			// A selection that survived a filter change would apply a bulk action to orders the
			// owner can no longer see.
			setSelected( [] );
		} catch ( e ) {
			setError( e );
		} finally {
			setBusy( false );
		}
	}, [ page, search, status ] );

	useEffect( () => {
		const timer = setTimeout( load, search ? 300 : 0 );
		return () => clearTimeout( timer );
	}, [ load, search ] );

	const changeFilter = ( setter ) => ( value ) => {
		setter( value );
		setPage( 1 );
	};

	const toggle = ( id ) =>
		setSelected( ( current ) =>
			current.includes( id ) ? current.filter( ( x ) => x !== id ) : [ ...current, id ]
		);

	const toggleAll = () =>
		setSelected( ( current ) => ( current.length === rows.length ? [] : rows.map( ( r ) => r.id ) ) );

	const applyBulk = async () => {
		setApplying( true );

		try {
			const result = await api( 'wc/v3/orders/batch', {
				method: 'POST',
				body: { update: selected.map( ( id ) => ( { id, status: bulkStatus } ) ) },
			} );

			// WooCommerce reports per-item failures inside a 200 response rather than failing the
			// whole batch, so a silent partial success is entirely possible without this check.
			const failed = ( result.update || [] ).filter( ( entry ) => entry.error );

			if ( failed.length ) {
				toast( failed.length + ' of ' + selected.length + ' could not be updated', 'bad' );
			} else {
				toast( selected.length + ' orders moved to ' + statusLabel( bulkStatus ) );
			}

			await load();
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setApplying( false );
		}
	};

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Orders</h1>
					<p class="sb-page__lead">${ total } orders.</p>
				</div>
			</div>

			<div class="sb-toolbar">
				<input
					class="sb-input sb-toolbar__search"
					type="search"
					placeholder="Search by order number, name or phone"
					value=${ search }
					onInput=${ ( e ) => changeFilter( setSearch )( e.target.value ) }
				/>
				<${ Select } value=${ status } onChange=${ changeFilter( setStatus ) } options=${ statusOptions( true ) } />
			</div>

			${ selected.length
				? html`
						<div class="sb-bulkbar">
							<span>${ selected.length } selected</span>
							<span class="sb-row">
								<${ Select }
									value=${ bulkStatus }
									onChange=${ setBulkStatus }
									options=${ statusOptions() }
								/>
								<button class="sb-btn sb-btn--primary" disabled=${ applying } onClick=${ applyBulk }>
									${ applying ? 'Applying…' : 'Change status' }
								</button>
								<button class="sb-btn sb-btn--ghost" onClick=${ () => setSelected( [] ) }>Clear</button>
							</span>
						</div>
				  `
				: null }

			${ error ? html`<${ ErrorBox } error=${ error } onRetry=${ load } />` : null }

			${ busy && ! rows.length
				? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
				: rows.length
				? html`
						<div class=${ 'sb-table-wrap' + ( busy ? ' is-busy' : '' ) }>
							<table class="sb-table">
								<thead>
									<tr>
										<th class="sb-table__check-col">
											<input
												type="checkbox"
												aria-label="Select all orders on this page"
												checked=${ selected.length > 0 && selected.length === rows.length }
												onChange=${ toggleAll }
											/>
										</th>
										<th>Order</th>
										<th>Date</th>
										<th>Customer</th>
										<th>Items</th>
										<th>Total</th>
										<th>Payment</th>
										<th>Status</th>
									</tr>
								</thead>
								<tbody>
									${ rows.map(
										( order ) => html`
											<tr key=${ order.id } class=${ selected.includes( order.id ) ? 'is-selected' : '' }>
												<td>
													<input
														type="checkbox"
														aria-label=${ 'Select order ' + order.number }
														checked=${ selected.includes( order.id ) }
														onChange=${ () => toggle( order.id ) }
													/>
												</td>
												<td>
													<a
														class="sb-table__name"
														href=${ href( '/orders/' + order.id ) }
														onClick=${ ( e ) => onLinkClick( e, '/orders/' + order.id ) }
													>
														#${ order.number }
													</a>
												</td>
												<td class="sb-table__sub">${ dateTime( order.date_created ) }</td>
												<td>
													${ customerName( order ) }
													<div class="sb-table__sub">${ ( order.billing && order.billing.phone ) || '—' }</div>
												</td>
												<td class="sb-table__sub">${ itemCount( order ) }</td>
												<td><strong>${ money( order.total ) }</strong></td>
												<td class="sb-table__sub">${ order.payment_method_title || '—' }</td>
												<td><${ Badge } tone=${ statusTone( order.status ) }>${ statusLabel( order.status ) }<//></td>
											</tr>
										`
									) }
								</tbody>
							</table>
						</div>
				  `
				: html`<${ EmptyState }
						title="No orders match"
						body=${ search || status ? 'Try clearing the filters.' : 'Orders will appear here as they come in.' }
				  />` }

			<${ Pagination } page=${ page } pages=${ pages } total=${ total } noun="orders" onPage=${ setPage } />
		</div>
	`;
}
