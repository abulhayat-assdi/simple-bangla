/*
 * The order list.
 *
 * Built around what a cash-on-delivery store actually does all day: a pile of new orders arrives,
 * each is confirmed by phone, and then a batch of them moves to the next status together. Hence
 * the checkboxes and the bulk status change — clicking into 40 orders one at a time to mark them
 * processing is the difference between this screen being used and being abandoned.
 *
 * **Filtering is by stage, not by status** (owner's decision, 2026-08-09). The five tabs are the
 * ones the shop thinks in — New, with the courier, completed, returned, failed — and the mapping
 * onto WooCommerce's statuses lives in `order-utils.js` so this screen never decides it. The screen
 * opens on New Orders, because the only reason to open it is that something has come in.
 *
 * **Two layouts, one data source.** Below 900px each order is a card, matching the reference the
 * owner supplied; above it the table stays, because a desk is where forty orders get worked through
 * and a table compares them a card cannot. Both are rendered and CSS chooses, which costs a little
 * markup and avoids a resize listener deciding what the owner can see.
 */

import {
	html,
	useState,
	useEffect,
	useCallback,
	Icon,
	Badge,
	Select,
	Pagination,
	EmptyState,
	ErrorBox,
	toast,
} from '../ui.js';
import { api, apiList, money } from '../api.js';
// `href` only: an order opens in the card over this list rather than by navigating, so the links
// that remain are there to carry a real address for middle-click, not to be followed on a plain one.
import { href } from '../router.js';
import { OrderCard } from './order-card.js';
import {
	statusLabel,
	statusTone,
	statusOptions,
	customerName,
	dateTime,
	itemCount,
	itemAttributes,
	ORDER_VIEWS,
	DEFAULT_VIEW,
	viewStatuses,
	STATUS_WITH_COURIER,
	COURIER_OUTCOMES,
} from '../order-utils.js';

const PER_PAGE = 20;

export function Orders() {
	const [ rows, setRows ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ total, setTotal ] = useState( 0 );
	const [ pages, setPages ] = useState( 1 );
	const [ busy, setBusy ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ counts, setCounts ] = useState( null );

	/*
	 * Seeded from ?search= so a link can hand this screen a phone number. It is how one customer's
	 * whole history is looked up — on a cash-on-delivery store that history *is* their orders, most
	 * of them placed as a guest under the same number, which is why there is no Customers screen.
	 *
	 * Read once, on mount. Routing is by path, so arriving here from elsewhere always mounts this
	 * screen fresh; re-reading it on every render would fight with what the owner then types.
	 */
	const [ search, setSearch ] = useState(
		() => new URLSearchParams( window.location.search ).get( 'search' ) || ''
	);

	/*
	 * A search that arrived from another screen opens on every stage rather than on New Orders. The
	 * point of following that link is to see one customer's whole history, and defaulting to New
	 * would answer it with an empty screen for any customer whose orders have all been delivered.
	 */
	const [ view, setView ] = useState( () =>
		new URLSearchParams( window.location.search ).get( 'search' ) ? '' : DEFAULT_VIEW
	);

	const [ selected, setSelected ] = useState( [] );
	const [ bulkStatus, setBulkStatus ] = useState( STATUS_WITH_COURIER );
	const [ applying, setApplying ] = useState( false );

	// The id of the order whose card action is in flight, so only that card's buttons go quiet.
	const [ acting, setActing ] = useState( 0 );

	/** The order whose card is open, or 0. Not a route: closing it must not cost a navigation. */
	const [ openOrder, setOpenOrder ] = useState( 0 );

	const load = useCallback( async () => {
		setBusy( true );
		setError( null );

		try {
			const result = await apiList( 'wc/v3/orders', {
				params: {
					page,
					per_page: PER_PAGE,
					search,
					status: viewStatuses( view ),
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
	}, [ page, search, view ] );

	useEffect( () => {
		const timer = setTimeout( load, search ? 300 : 0 );
		return () => clearTimeout( timer );
	}, [ load, search ] );

	/*
	 * The number on each tab. WooCommerce keeps a per-status count for its own admin screens, so
	 * this is one cheap request rather than five list calls made only to read their totals.
	 * Silently ignored on failure: a missing number on a tab is a smaller problem than an error
	 * banner over a list that loaded perfectly well.
	 */
	const loadCounts = useCallback( () => {
		api( 'wc/v3/reports/orders/totals' )
			.then( ( totals ) => {
				const byStatus = {};

				( totals || [] ).forEach( ( entry ) => {
					byStatus[ String( entry.slug ).replace( /^wc-/, '' ) ] = Number( entry.total || 0 );
				} );

				setCounts( byStatus );
			} )
			.catch( () => setCounts( null ) );
	}, [] );

	useEffect( loadCounts, [ loadCounts ] );

	const changeView = ( next ) => {
		setView( next );
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
			loadCounts();
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setApplying( false );
		}
	};

	/*
	 * The reference the owner supplied puts the next action on the card itself, and it is right to:
	 * working through a morning's orders on a phone is a list of one-tap decisions, and making each
	 * one cost an open-and-go-back is what turns the screen into something nobody uses.
	 *
	 * The row is removed from the list afterwards rather than updated in place — it no longer belongs
	 * to the view being looked at, and leaving it there under its old label would invite a second tap
	 * on an order that has already moved.
	 */
	const dispatch = async ( order ) => {
		setActing( order.id );

		try {
			const result = await api( '/orders/' + order.id + '/courier', { method: 'POST', body: {} } );

			toast( 'Sent to ' + result.shipment.provider_label + ' · ' + ( result.shipment.consignment_id || 'booked' ) );
			await load();
			loadCounts();
		} catch ( e ) {
			// A second dispatch needs the confirmation that only the order screen offers, so this
			// says where to go rather than silently forcing it.
			toast(
				e.code === 'sb_cms_already_sent' ? e.message + ' Open the order to send it again.' : e.message,
				'bad'
			);
		} finally {
			setActing( 0 );
		}
	};

	const setStatus = async ( order, status ) => {
		setActing( order.id );

		try {
			await api( 'wc/v3/orders/' + order.id, { method: 'PUT', body: { status } } );
			toast( '#' + order.number + ' moved to ' + statusLabel( status ) );
			await load();
			loadCounts();
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setActing( 0 );
		}
	};

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Orders</h1>
					<p class="sb-page__lead">${ total } ${ total === 1 ? 'order' : 'orders' } in this view.</p>
				</div>
			</div>

			<input
				class="sb-input sb-orders__search"
				type="search"
				placeholder="Search by order number, name or phone"
				value=${ search }
				onInput=${ ( e ) => {
					setSearch( e.target.value );
					setPage( 1 );
				} }
			/>

			<div class="sb-tabs" role="tablist" aria-label="Order stage">
				<${ Tab }
					label="All"
					active=${ view === '' }
					count=${ null }
					onClick=${ () => changeView( '' ) }
				/>
				${ ORDER_VIEWS.map(
					( item ) => html`
						<${ Tab }
							key=${ item.key }
							label=${ item.label }
							active=${ view === item.key }
							count=${ counts
								? item.statuses.reduce( ( sum, status ) => sum + ( counts[ status ] || 0 ), 0 )
								: null }
							onClick=${ () => changeView( item.key ) }
						/>
					`
				) }
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
						<div class=${ busy ? 'is-busy' : '' }>
							<${ OrderCards }
								rows=${ rows }
								selected=${ selected }
								onToggle=${ toggle }
								acting=${ acting }
								onDispatch=${ dispatch }
								onStatus=${ setStatus }
								onOpen=${ setOpenOrder }
							/>
							<${ OrderTable }
								rows=${ rows }
								selected=${ selected }
								onToggle=${ toggle }
								onToggleAll=${ toggleAll }
								onOpen=${ setOpenOrder }
							/>
						</div>
				  `
				: html`<${ EmptyState }
						title="Nothing here"
						body=${ search
							? 'No order matches that search in this view. Try the All tab.'
							: 'Orders will appear here as they reach this stage.' }
				  />` }

			<${ Pagination } page=${ page } pages=${ pages } total=${ total } noun="orders" onPage=${ setPage } />

			${ /*
			 * The order opens over the list rather than replacing it. Anything done in the card that
			 * changes the order — a status, a dispatch, a corrected address — refetches these rows and
			 * the tab counts, so the row behind the card is never left describing the order as it was
			 * before it was worked on.
			 */
			openOrder
				? html`<${ OrderCard }
						id=${ openOrder }
						onClose=${ () => setOpenOrder( 0 ) }
						onChange=${ load }
						onDelete=${ () => {
							setOpenOrder( 0 );
							load();
						} }
				  />`
				: null }
		</div>
	`;
}

function Tab( { label, count, active, onClick } ) {
	return html`
		<button
			class=${ 'sb-tab' + ( active ? ' is-active' : '' ) }
			role="tab"
			aria-selected=${ active }
			onClick=${ onClick }
		>
			<span>${ label }</span>
			${ count === null || count === undefined
				? null
				: html`<span class="sb-tab__count">${ count }</span>` }
		</button>
	`;
}

/**
 * The phone layout: one card per order, in the shape of the reference the owner supplied.
 *
 * Every row is a label above a value, so the card reads top to bottom under a thumb and nothing
 * depends on a column header that scrolled off the side. The whole card is a link; the checkbox is
 * the one thing inside it that is not, because tapping it must select rather than navigate.
 */
function OrderCards( { rows, selected, onToggle, acting, onDispatch, onStatus, onOpen } ) {
	return html`
		<div class="sb-ordercards">
			${ rows.map( ( order ) => {
				const path = '/orders/' + order.id;
				const first = ( order.line_items || [] )[ 0 ];
				const shipment = order.sb_courier;

				return html`
					<article
						key=${ order.id }
						class=${ 'sb-ordercard' + ( selected.includes( order.id ) ? ' is-selected' : '' ) }
					>
						<header class="sb-ordercard__top">
							<label class="sb-ordercard__pick">
								<input
									type="checkbox"
									aria-label=${ 'Select order ' + order.number }
									checked=${ selected.includes( order.id ) }
									onChange=${ () => onToggle( order.id ) }
								/>
							</label>
							<a
								class="sb-ordercard__no"
								href=${ href( path ) }
								onClick=${ ( e ) => {
									if ( e.metaKey || e.ctrlKey || e.shiftKey || 0 !== e.button ) {
										return;
									}

									e.preventDefault();
									onOpen( order.id );
								} }
							>
								#${ order.number }
							</a>
							<${ Badge } tone=${ statusTone( order.status ) }>${ statusLabel( order.status ) }<//>
						</header>

						<${ CardRow } label="Customer">
							<span class="sb-ordercard__name">${ customerName( order ) }</span>
							${ order.billing && order.billing.phone
								? html`<a class="sb-ordercard__phone" href=${ 'tel:' + order.billing.phone }>
										${ order.billing.phone }
								  </a>`
								: null }
						<//>

						<${ CardRow } label="Order date">${ dateTime( order.date_created ) }<//>

						<${ CardRow } label="Product">
							<div class="sb-ordercard__product">
								${ first && first.image && first.image.src
									? html`<img src=${ first.image.src } alt="" loading="lazy" />`
									: html`<span class="sb-thumb__empty"><${ Icon } name="image" size=${ 16 } /></span>` }
								<span>
									${ first ? first.name : '—' }
									${ first && first.quantity > 1 ? html`<span class="sb-ordercard__more"> × ${ first.quantity }</span>` : null }
									${ ( order.line_items || [] ).length > 1
										? html`<span class="sb-ordercard__more">
												+ ${ order.line_items.length - 1 } more
										  </span>`
										: null }
								</span>
							</div>
						<//>

						${ first && itemAttributes( first ).length
							? html`
									<${ CardRow } label="Options">
										<span class="sb-chips">
											${ itemAttributes( first ).map(
												( attr ) => html`<span key=${ attr.label } class="sb-chip">${ attr.value }</span>`
											) }
										</span>
									<//>
							  `
							: null }

						<${ CardRow } label="Address">
							<span class="sb-ordercard__address">${ shortAddress( order ) }</span>
						<//>

						<${ CardRow } label="Total"><strong>${ money( order.total ) }</strong><//>

						${ shipment
							? html`<${ CardRow } label="Courier">
									${ shipment.provider_label } · ${ shipment.consignment_id || '—' }
							  <//>`
							: null }

						<footer class="sb-ordercard__foot">
							${ order.status === STATUS_WITH_COURIER
								? COURIER_OUTCOMES.map(
										( outcome ) => html`
											<button
												key=${ outcome.status }
												class=${ 'sb-btn sb-btn--block sb-btn--' + outcome.tone +
													( outcome.tone === 'danger' ? ' sb-btn--outline' : '' ) }
												disabled=${ acting === order.id }
												onClick=${ () => onStatus( order, outcome.status ) }
											>
												${ acting === order.id ? 'Working…' : outcome.label }
											</button>
										`
								  )
								: html`<button
										class="sb-btn sb-btn--primary sb-btn--block"
										disabled=${ acting === order.id }
										onClick=${ () => onDispatch( order ) }
								  >
										<${ Icon } name="truck" size=${ 16 } />
										${ acting === order.id ? 'Sending…' : 'Send to courier' }
								  </button>` }

							<button class="sb-btn sb-btn--ghost sb-btn--block" onClick=${ () => onOpen( order.id ) }>
								Open order
							</button>
						</footer>
					</article>
				`;
			} ) }
		</div>
	`;
}

function CardRow( { label, children } ) {
	return html`
		<div class="sb-ordercard__row">
			<span class="sb-ordercard__label">${ label }</span>
			<span class="sb-ordercard__value">${ children }</span>
		</div>
	`;
}

/** The address on one line, which is all a card has room for. */
function shortAddress( order ) {
	const billing = order.billing || {};

	return (
		[ billing.address_1, billing.address_2, billing.city, billing.state ]
			.map( ( part ) => ( part || '' ).trim() )
			.filter( Boolean )
			.join( ', ' ) || '—'
	);
}

/**
 * The desktop layout.
 *
 * **The whole row opens the order**, not only the number in the first column. Clicking a row and
 * having nothing happen was the single most confusing thing about this screen: the row is plainly a
 * record of one order, and everything on it — the customer, the total, the status — reads as part of
 * the same clickable thing. The checkbox column is the exception, because tapping it must select.
 *
 * The order number stays a real link inside the row, so middle-click and "open in new tab" still
 * reach the full page.
 */
function OrderTable( { rows, selected, onToggle, onToggleAll, onOpen } ) {
	return html`
		<div class="sb-table-wrap sb-orders__table">
			<table class="sb-table">
				<thead>
					<tr>
						<th class="sb-table__check-col">
							<input
								type="checkbox"
								aria-label="Select all orders on this page"
								checked=${ selected.length > 0 && selected.length === rows.length }
								onChange=${ onToggleAll }
							/>
						</th>
						<th>Order</th>
						<th>Date</th>
						<th>Customer</th>
						<th>Items</th>
						<th>Total</th>
						<th>Courier</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					${ rows.map( ( order ) => {
						const shipment = order.sb_courier;

						return html`
							<tr
								key=${ order.id }
								class=${ 'sb-table__row--open' + ( selected.includes( order.id ) ? ' is-selected' : '' ) }
								onClick=${ ( e ) => {
									// A click on the checkbox, or a modified click on the number, is not a
									// request to open the card.
									if ( e.target.closest( 'input, a, button' ) ) {
										return;
									}

									onOpen( order.id );
								} }
							>
								<td>
									<input
										type="checkbox"
										aria-label=${ 'Select order ' + order.number }
										checked=${ selected.includes( order.id ) }
										onChange=${ () => onToggle( order.id ) }
									/>
								</td>
								<td>
									<a
										class="sb-table__name"
										href=${ href( '/orders/' + order.id ) }
										onClick=${ ( e ) => {
											if ( e.metaKey || e.ctrlKey || e.shiftKey || 0 !== e.button ) {
												return;
											}

											e.preventDefault();
											onOpen( order.id );
										} }
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
								<td class="sb-table__sub">
									${ shipment ? shipment.provider_label : '—' }
									${ shipment && shipment.consignment_id
										? html`<div class="sb-table__sub">${ shipment.consignment_id }</div>`
										: null }
								</td>
								<td><${ Badge } tone=${ statusTone( order.status ) }>${ statusLabel( order.status ) }<//></td>
							</tr>
						`;
					} ) }
				</tbody>
			</table>
		</div>
	`;
}
