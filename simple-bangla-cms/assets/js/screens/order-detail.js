/*
 * One order.
 *
 * Laid out in the order the questions actually get asked: what is in it, who is it going to,
 * what was paid, and what has been said about it. The notes panel matters more than it looks for
 * a cash-on-delivery store — it is where the courier's tracking number and the outcome of the
 * confirmation call end up, and WooCommerce already stores and timestamps them.
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
	Badge,
	Confirm,
	ErrorBox,
	toast,
} from '../ui.js';
import { api, apiList, money } from '../api.js';
import { href, onLinkClick } from '../router.js';
import {
	statusLabel,
	statusTone,
	statusOptions,
	customerName,
	addressLines,
	dateTime,
	refundedTotal,
} from '../order-utils.js';

export function OrderDetail( { id } ) {
	const [ order, setOrder ] = useState( null );
	const [ notes, setNotes ] = useState( [] );
	const [ error, setError ] = useState( null );
	const [ busy, setBusy ] = useState( false );

	const [ status, setStatus ] = useState( '' );
	const [ noteText, setNoteText ] = useState( '' );
	const [ noteToCustomer, setNoteToCustomer ] = useState( false );
	const [ refunding, setRefunding ] = useState( false );
	const [ refundAmount, setRefundAmount ] = useState( '' );
	const [ refundReason, setRefundReason ] = useState( '' );

	const load = useCallback( async () => {
		setError( null );

		try {
			const loaded = await api( 'wc/v3/orders/' + id );
			setOrder( loaded );
			setStatus( loaded.status );

			const { items } = await apiList( 'wc/v3/orders/' + id + '/notes', { params: { per_page: 50 } } );
			setNotes( items );
		} catch ( e ) {
			setError( e );
		}
	}, [ id ] );

	useEffect( () => {
		load();
	}, [ load ] );

	const saveStatus = async ( next ) => {
		setStatus( next );
		setBusy( true );

		try {
			const updated = await api( 'wc/v3/orders/' + id, { method: 'PUT', body: { status: next } } );
			setOrder( updated );
			toast( 'Status changed to ' + statusLabel( next ) );
			// A status change writes its own note, so the trail is only right if it is refetched.
			const { items } = await apiList( 'wc/v3/orders/' + id + '/notes', { params: { per_page: 50 } } );
			setNotes( items );
		} catch ( e ) {
			setStatus( order.status );
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	const addNote = async () => {
		if ( ! noteText.trim() ) {
			return;
		}

		setBusy( true );

		try {
			const created = await api( 'wc/v3/orders/' + id + '/notes', {
				method: 'POST',
				body: { note: noteText, customer_note: noteToCustomer },
			} );

			setNotes( ( current ) => [ created, ...current ] );
			setNoteText( '' );
			toast( noteToCustomer ? 'Note added and emailed to the customer' : 'Note added' );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	const refund = async () => {
		setBusy( true );

		try {
			await api( 'wc/v3/orders/' + id + '/refunds', {
				method: 'POST',
				body: {
					amount: String( refundAmount ),
					reason: refundReason,
					// Cash on delivery has no gateway to call. Asking WooCommerce to refund through
					// the API would fail on the only payment method this store uses.
					api_refund: false,
				},
			} );

			toast( 'Refund of ' + money( refundAmount ) + ' recorded' );
			setRefunding( false );
			setRefundAmount( '' );
			setRefundReason( '' );
			await load();
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	if ( error ) {
		return html`<div class="sb-page"><${ ErrorBox } error=${ error } onRetry=${ load } /></div>`;
	}

	if ( ! order ) {
		return html`<div class="sb-page"><div class="sb-media-loading"><span class="sb-spinner"></span></div></div>`;
	}

	const items = order.line_items || [];
	const subtotal = items.reduce( ( sum, item ) => sum + Number( item.subtotal || 0 ), 0 );
	const refunded = refundedTotal( order );
	const outstanding = Number( order.total || 0 ) - refunded;

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<p class="sb-crumb">
						<a href=${ href( '/orders' ) } onClick=${ ( e ) => onLinkClick( e, '/orders' ) }>Orders</a>
					</p>
					<h1 class="sb-page__title">Order #${ order.number }</h1>
					<p class="sb-page__lead">
						${ dateTime( order.date_created ) } ·
						<${ Badge } tone=${ statusTone( order.status ) }>${ statusLabel( order.status ) }<//>
					</p>
				</div>
				<div class="sb-row sb-page__actions">
					<a
						class="sb-btn sb-btn--ghost"
						href=${ href( '/orders/' + id + '/invoice' ) }
						onClick=${ ( e ) => onLinkClick( e, '/orders/' + id + '/invoice' ) }
					>
						Invoice
					</a>
				</div>
			</div>

			<div class="sb-editor">
				<div class="sb-editor__main">
					<${ Card } title=${ 'Items (' + items.length + ')' }>
						<div class="sb-table-wrap">
							<table class="sb-table sb-table--compact">
								<thead>
									<tr>
										<th class="sb-table__thumb-col"><span class="sb-sr">Image</span></th>
										<th>Product</th>
										<th>Price</th>
										<th>Qty</th>
										<th>Total</th>
									</tr>
								</thead>
								<tbody>
									${ items.map(
										( item ) => html`
											<tr key=${ item.id }>
												<td>
													<div class="sb-table__thumb">
														${ item.image && item.image.src
															? html`<img src=${ item.image.src } alt="" loading="lazy" />`
															: null }
													</div>
												</td>
												<td>
													<span class="sb-table__name">${ item.name }</span>
													<div class="sb-table__sub">${ item.sku ? 'SKU ' + item.sku : '' }</div>
												</td>
												<td class="sb-table__sub">${ money( item.price ) }</td>
												<td class="sb-table__sub">× ${ item.quantity }</td>
												<td><strong>${ money( item.total ) }</strong></td>
											</tr>
										`
									) }
								</tbody>
							</table>
						</div>

						<div class="sb-totals">
							<${ TotalRow } label="Subtotal" value=${ money( subtotal ) } />
							${ Number( order.discount_total ) > 0
								? html`<${ TotalRow } label="Discount" value=${ '− ' + money( order.discount_total ) } />`
								: null }
							${ ( order.shipping_lines || [] ).map(
								( line ) => html`<${ TotalRow } key=${ line.id } label=${ line.method_title || 'Delivery' } value=${ money( line.total ) } />`
							) }
							${ Number( order.total_tax ) > 0
								? html`<${ TotalRow } label="Tax" value=${ money( order.total_tax ) } />`
								: null }
							<${ TotalRow } label="Total" value=${ money( order.total ) } strong />
							${ refunded > 0
								? html`
										<${ TotalRow } label="Refunded" value=${ '− ' + money( refunded ) } tone="bad" />
										<${ TotalRow } label="Net" value=${ money( outstanding ) } strong />
								  `
								: null }
						</div>

						${ order.customer_note
							? html`<p class="sb-note-customer"><strong>Customer note:</strong> ${ order.customer_note }</p>`
							: null }
					<//>

					<${ Card } title="Notes">
						<${ Field } label="Add a note" id="o-note">
							<textarea
								class="sb-input sb-textarea"
								id="o-note"
								rows="3"
								placeholder="Courier tracking number, what the customer said on the phone…"
								value=${ noteText }
								onInput=${ ( e ) => setNoteText( e.target.value ) }
							></textarea>
						<//>
						<${ Switch }
							label="Send this to the customer"
							hint="Customer notes are emailed. Leave off for internal notes."
							checked=${ noteToCustomer }
							onChange=${ setNoteToCustomer }
						/>
						<button class="sb-btn sb-btn--primary" disabled=${ busy || ! noteText.trim() } onClick=${ addNote }>
							Add note
						</button>

						<div class="sb-notes">
							${ notes.length
								? notes.map(
										( note ) => html`
											<article
												key=${ note.id }
												class=${ 'sb-note' + ( note.customer_note ? ' sb-note--customer' : '' ) }
											>
												<p class="sb-note__body">${ stripTags( note.note ) }</p>
												<p class="sb-note__meta">
													${ dateTime( note.date_created ) }
													${ note.customer_note ? ' · sent to customer' : '' }
												</p>
											</article>
										`
								  )
								: html`<p class="sb-hint">No notes yet.</p>` }
						</div>
					<//>
				</div>

				<div class="sb-editor__side">
					<${ Card } title="Status">
						<${ Field } label="Order status" id="o-status">
							<${ Select } id="o-status" value=${ status } onChange=${ saveStatus } options=${ statusOptions() } />
						<//>
						<p class="sb-hint">Changing this saves immediately and records a note.</p>
					<//>

					<${ Card } title="Customer">
						<p class="sb-detail__name">${ customerName( order ) }</p>
						${ order.billing && order.billing.phone
							? html`<p><a href=${ 'tel:' + order.billing.phone }>${ order.billing.phone }</a></p>`
							: null }
						${ order.billing && order.billing.email
							? html`<p><a href=${ 'mailto:' + order.billing.email }>${ order.billing.email }</a></p>`
							: null }

						<span class="sb-field__label">Billing address</span>
						<address class="sb-address">
							${ addressLines( order.billing ).map( ( line, i ) => html`<span key=${ i }>${ line }</span>` ) }
						</address>

						${ addressLines( order.shipping ).length
							? html`
									<span class="sb-field__label">Shipping address</span>
									<address class="sb-address">
										${ addressLines( order.shipping ).map( ( line, i ) => html`<span key=${ i }>${ line }</span>` ) }
									</address>
							  `
							: null }
					<//>

					<${ Card } title="Payment">
						<p><strong>${ order.payment_method_title || 'Not recorded' }</strong></p>
						<p class="sb-hint">
							${ order.date_paid ? 'Paid ' + dateTime( order.date_paid ) : 'Not marked as paid.' }
						</p>
						${ order.transaction_id
							? html`<p class="sb-hint">Transaction ${ order.transaction_id }</p>`
							: null }

						<button
							class="sb-btn sb-btn--ghost"
							onClick=${ () => {
								setRefundAmount( String( outstanding ) );
								setRefunding( true );
							} }
							disabled=${ outstanding <= 0 }
						>
							Record a refund
						</button>
						${ refunded > 0 ? html`<p class="sb-hint">${ money( refunded ) } already refunded.</p>` : null }
					<//>
				</div>
			</div>

			${ refunding
				? html`<${ Confirm }
						title="Record a refund"
						confirmLabel="Record refund"
						busy=${ busy }
						onConfirm=${ refund }
						onClose=${ () => setRefunding( false ) }
						body=${ html`
							<${ Field } label="Amount" id="o-refund">
								<input
									class="sb-input"
									id="o-refund"
									inputmode="decimal"
									value=${ refundAmount }
									onInput=${ ( e ) => setRefundAmount( e.target.value ) }
								/>
							<//>
							<${ Field } label="Reason" id="o-reason">
								<input
									class="sb-input"
									id="o-reason"
									value=${ refundReason }
									onInput=${ ( e ) => setRefundReason( e.target.value ) }
								/>
							<//>
							<p class="sb-hint">
								This records the refund against the order. Cash on delivery has no gateway to
								return money through, so paying the customer back is done separately.
							</p>
						` }
				  />`
				: null }
		</div>
	`;
}

function TotalRow( { label, value, strong, tone } ) {
	return html`
		<div class=${ 'sb-totals__row' + ( strong ? ' sb-totals__row--strong' : '' ) }>
			<span>${ label }</span>
			<span class=${ tone ? 'sb-totals__' + tone : '' }>${ value }</span>
		</div>
	`;
}

/** WooCommerce stores notes as HTML; the list shows their text. */
function stripTags( value ) {
	const el = document.createElement( 'div' );
	el.innerHTML = value || '';

	return el.textContent.trim();
}
