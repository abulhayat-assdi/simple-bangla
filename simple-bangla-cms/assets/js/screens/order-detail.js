/*
 * One order.
 *
 * Rebuilt on 2026-08-09 to the reference the owner supplied, which arranges the screen around the
 * decision being made rather than around WooCommerce's data model: what is in the parcel, where it
 * is going, what will be collected, whether this customer can be trusted with a cash-on-delivery
 * parcel — and then the one button that hands it to the courier.
 *
 * The notes panel matters more than it looks for a cash-on-delivery store: it is where the outcome
 * of the confirmation call ends up, and WooCommerce already stores and timestamps them. The courier
 * dispatch writes a note of its own for the same reason.
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
import { OrderItems, OrderTotals, DeliveryDetails, StageActions, FraudReport } from '../order-parts.js';
import { href, onLinkClick, navigate } from '../router.js';
import { statusLabel, statusTone, statusOptions, dateTime, refundedTotal } from '../order-utils.js';

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
	const [ sending, setSending ] = useState( false );
	const [ resending, setResending ] = useState( false );
	const [ erasing, setErasing ] = useState( false );

	const loadNotes = useCallback( async () => {
		const { items } = await apiList( 'wc/v3/orders/' + id + '/notes', { params: { per_page: 50 } } );
		setNotes( items );
	}, [ id ] );

	const load = useCallback( async () => {
		setError( null );

		try {
			const loaded = await api( 'wc/v3/orders/' + id );
			setOrder( loaded );
			setStatus( loaded.status );

			await loadNotes();
		} catch ( e ) {
			setError( e );
		}
	}, [ id, loadNotes ] );

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
			await loadNotes();
		} catch ( e ) {
			setStatus( order.status );
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	/**
	 * Hand the parcel to the configured courier.
	 *
	 * The endpoint refuses a second dispatch unless `force` is sent, because two consignments for
	 * one parcel means the shop pays twice. A 409 is therefore not an error to report and forget —
	 * it is the question "send it again?", so it opens the confirmation instead of a red toast.
	 */
	const send = async ( force = false ) => {
		setSending( true );

		try {
			const result = await api( '/orders/' + id + '/courier', {
				method: 'POST',
				body: { force },
			} );

			toast( 'Sent to ' + result.shipment.provider_label + ' · ' + ( result.shipment.consignment_id || 'booked' ) );
			setResending( false );
			await load();
		} catch ( e ) {
			if ( e.status === 409 && e.code === 'sb_cms_already_sent' && ! force ) {
				setResending( true );
			} else {
				toast( e.message, 'bad' );
			}
		} finally {
			setSending( false );
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

	/*
	 * `force` erases the order rather than trashing it, which is what the owner asked this button to
	 * do. A trashed order still counts in WooCommerce's own reports and still appears in a search,
	 * so a "deleted" order that keeps turning up would be the more confusing outcome — but it is
	 * permanent, and the confirmation says so in as many words.
	 */
	const erase = async () => {
		setBusy( true );

		try {
			await api( 'wc/v3/orders/' + id, { method: 'DELETE', params: { force: true } } );
			toast( 'Order deleted' );
			// replace, not push: going back to a screen for an order that no longer exists would
			// only produce a 404.
			navigate( '/orders', true );
		} catch ( e ) {
			toast( e.message, 'bad' );
			setBusy( false );
			setErasing( false );
		}
	};

	if ( error ) {
		return html`<div class="sb-page"><${ ErrorBox } error=${ error } onRetry=${ load } /></div>`;
	}

	if ( ! order ) {
		return html`<div class="sb-page"><div class="sb-media-loading"><span class="sb-spinner"></span></div></div>`;
	}

	const items = order.line_items || [];

	// Still needed here, unlike the subtotal and the delivery charge, because the refund dialog
	// offers the outstanding amount as its default and refuses to open once it reaches zero.
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
					<${ Card } title=${ 'Items ordered (' + items.length + ')' }>
						<${ OrderItems } items=${ items } />

						<${ OrderTotals } order=${ order } />

						${ order.customer_note
							? html`<p class="sb-note-customer"><strong>Customer note:</strong> ${ order.customer_note }</p>`
							: null }
					<//>

						<${ Card } title="Delivery information">
							<${ DeliveryDetails } order=${ order } />
						<//>

					<${ FraudReport } id=${ id } phone=${ ( order.billing && order.billing.phone ) || '' } />

					<${ Card } title="Notes">
						<${ Field } label="Add a note" id="o-note">
							<textarea
								class="sb-input sb-textarea"
								id="o-note"
								rows="3"
								placeholder="What the customer said on the phone…"
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
					<${ Card } title="Actions">
							<${ StageActions }
								order=${ order }
								status=${ status }
								busy=${ busy }
								sending=${ sending }
								onStatus=${ saveStatus }
								onSend=${ send }
							/>

						<button
							class="sb-btn sb-btn--danger sb-btn--block sb-btn--outline"
							disabled=${ busy }
							onClick=${ () => setErasing( true ) }
						>
							Permanently delete order
						</button>
					<//>

					<${ Card } title="Status">
						<${ Field } label="Order status" id="o-status">
							<${ Select } id="o-status" value=${ status } onChange=${ saveStatus } options=${ statusOptions() } />
						<//>
						<p class="sb-hint">Changing this saves immediately and records a note.</p>
					<//>

					<${ Card } title="Payment">
						<p><strong>${ order.payment_method_title || 'Not recorded' }</strong></p>
						<p class="sb-hint">
							${ order.date_paid ? 'Paid ' + dateTime( order.date_paid ) : 'To be collected on delivery.' }
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

			${ resending
				? html`<${ Confirm }
						title="Send this parcel again?"
						body="It has already been booked once. Sending it again creates a second consignment, which the courier will bill for."
						confirmLabel="Send again"
						busy=${ sending }
						onConfirm=${ () => send( true ) }
						onClose=${ () => setResending( false ) }
				  />`
				: null }

			${ erasing
				? html`<${ Confirm }
						title=${ 'Permanently delete order #' + order.number + '?' }
						body="It is erased rather than trashed: the order, its items and its notes are gone and cannot be recovered. Sales figures already reported will change."
						confirmLabel="Delete permanently"
						busy=${ busy }
						onConfirm=${ erase }
						onClose=${ () => setErasing( false ) }
				  />`
				: null }

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

/** WooCommerce stores notes as HTML; the list shows their text. */
function stripTags( value ) {
	// DOMParser rather than a detached div: a note can contain markup a plugin wrote, and a parsed
	// document never fetches an <img src> while its text is being read out of it.
	return new DOMParser().parseFromString( '<body>' + ( value || '' ) + '</body>', 'text/html' )
		.body.textContent.trim();
}
