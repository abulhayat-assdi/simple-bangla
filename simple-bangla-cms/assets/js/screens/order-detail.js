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
	Icon,
	Confirm,
	ErrorBox,
	toast,
} from '../ui.js';
import { api, apiList, money } from '../api.js';
import { href, onLinkClick, navigate } from '../router.js';
import {
	statusLabel,
	statusTone,
	statusOptions,
	customerName,
	addressLines,
	dateTime,
	refundedTotal,
	itemAttributes,
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
	const subtotal = items.reduce( ( sum, item ) => sum + Number( item.subtotal || 0 ), 0 );
	const shipping = ( order.shipping_lines || [] ).reduce( ( sum, line ) => sum + Number( line.total || 0 ), 0 );
	const refunded = refundedTotal( order );
	const outstanding = Number( order.total || 0 ) - refunded;
	const shipment = order.sb_courier;

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
						<div class="sb-items">
							${ items.map(
								( item ) => html`
									<div key=${ item.id } class="sb-item">
										<div class="sb-item__thumb">
											${ item.image && item.image.src
												? html`<img src=${ item.image.src } alt="" loading="lazy" />`
												: html`<span class="sb-thumb__empty"><${ Icon } name="image" size=${ 18 } /></span>` }
										</div>
										<div class="sb-item__body">
											<p class="sb-item__name">${ item.name }</p>
											<p class="sb-item__meta">
												${ money( item.price ) } × ${ item.quantity }
												${ item.sku ? ' · SKU ' + item.sku : '' }
											</p>
											${ itemAttributes( item ).length
												? html`<div class="sb-chips">
														${ itemAttributes( item ).map(
															( attr ) => html`
																<span key=${ attr.label } class="sb-chip">
																	<span class="sb-chip__key">${ attr.label }</span>
																	${ attr.value }
																</span>
															`
														) }
												  </div>`
												: null }
										</div>
										<div class="sb-item__total">${ money( item.total ) }</div>
									</div>
								`
							) }
						</div>

						<div class="sb-totals">
							<${ TotalRow } label="Subtotal" value=${ money( subtotal ) } />
							${ Number( order.discount_total ) > 0
								? html`<${ TotalRow } label="Discount" value=${ '− ' + money( order.discount_total ) } />`
								: null }
							${ shipping > 0 ? html`<${ TotalRow } label="Delivery" value=${ money( shipping ) } />` : null }
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

					<${ Card } title="Delivery information">
						<p class="sb-detail__name">${ customerName( order ) }</p>
						${ order.billing && order.billing.phone
							? html`<p><a href=${ 'tel:' + order.billing.phone }>${ order.billing.phone }</a></p>`
							: null }
						${ order.billing && order.billing.email
							? html`<p><a href=${ 'mailto:' + order.billing.email }>${ order.billing.email }</a></p>`
							: null }

						<address class="sb-address">
							${ addressLines( order.billing, true ).map( ( line, i ) => html`<span key=${ i }>${ line }</span>` ) }
						</address>

						${ addressLines( order.shipping, true ).length
							? html`
									<span class="sb-field__label">Shipping address</span>
									<address class="sb-address">
										${ addressLines( order.shipping, true ).map(
											( line, i ) => html`<span key=${ i }>${ line }</span>`
										) }
									</address>
							  `
							: null }

						${ order.customer_ip_address
							? html`
									<span class="sb-field__label">Client IP</span>
									<p class="sb-mono">
										${ order.customer_ip_address }
										<a
											class="sb-inline-link"
											href=${ href( '/blocked' ) }
											onClick=${ ( e ) => onLinkClick( e, '/blocked' ) }
										>
											Block this address
										</a>
									</p>
							  `
							: null }
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
						${ shipment
							? html`
									<div class="sb-shipment">
										<p class="sb-shipment__label">
											<${ Icon } name="truck" size=${ 16 } /> ${ shipment.provider_label }
										</p>
										<p class="sb-mono">${ shipment.consignment_id || '—' }</p>
										<p class="sb-hint">Sent ${ dateTime( new Date( shipment.sent_at * 1000 ) ) }</p>
									</div>
							  `
							: null }

						<button
							class="sb-btn sb-btn--primary sb-btn--block"
							disabled=${ sending }
							onClick=${ () => send( false ) }
						>
							<${ Icon } name="truck" size=${ 16 } />
							${ sending ? 'Sending…' : shipment ? 'Send to courier again' : 'Send to courier' }
						</button>
						<p class="sb-hint">
							Books the parcel with the courier set up under Settings and moves the order to
							Courier-এ আছে.
						</p>

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

/**
 * How this customer's parcels have gone before.
 *
 * Two halves, and they are not equally reliable, so the card says which is which. The **local**
 * figures are this shop's own orders — always available, never wrong. The **courier** figures come
 * from each courier's merchant portal, which has no documented API for them; a courier that cannot
 * be reached says so on its own line rather than being reported as zero, because "no record" and
 * "never delivered successfully" point in opposite directions.
 *
 * Loaded on demand rather than with the order. It is three sign-ins to three outside services and
 * it must not sit between the owner and an order they only wanted to read.
 */
function FraudReport( { id, phone } ) {
	const [ report, setReport ] = useState( null );
	const [ loading, setLoading ] = useState( false );
	const [ failed, setFailed ] = useState( null );

	const fetchReport = ( refresh = false ) => {
		setLoading( true );
		setFailed( null );

		api( '/orders/' + id + '/record', { params: refresh ? { refresh: 1 } : {} } )
			.then( setReport )
			.catch( setFailed )
			.finally( () => setLoading( false ) );
	};

	if ( ! phone ) {
		return null;
	}

	return html`
		<${ Card }
			title="Courier and fraud report"
			action=${ html`
				<button class="sb-btn sb-btn--ghost sb-btn--sm" disabled=${ loading } onClick=${ () => fetchReport( !! report ) }>
					${ loading ? 'Checking…' : report ? 'Check again' : 'Check this number' }
				</button>
			` }
		>
			${ ! report && ! loading && ! failed
				? html`<p class="sb-hint">
						Nothing is requested until you ask. Checking signs in to each courier's merchant panel
						to read what ${ phone } has ordered elsewhere.
				  </p>`
				: null }

			${ failed ? html`<${ ErrorBox } error=${ failed } onRetry=${ () => fetchReport( true ) } />` : null }

			${ report
				? html`
						<p class="sb-field__label">This shop's own history</p>
						<div class="sb-scoreboard">
							<${ Score } label="Orders" value=${ report.local.total } />
							<${ Score } label="Delivered" value=${ report.local.delivered } tone="ok" />
							<${ Score } label="Returned / cancelled" value=${ report.local.returned } tone="bad" />
							<${ Score } label="In progress" value=${ report.local.open } />
						</div>

						<p class="sb-field__label">Courier records</p>
						${ report.couriers.length
							? html`
									<div class="sb-courier-records">
										${ report.couriers.map(
											( record ) => html`
												<div key=${ record.provider } class="sb-courier-record">
													<span class="sb-courier-record__name">${ record.label }</span>
													${ record.error
														? html`<span class="sb-courier-record__error">${ record.error }</span>`
														: record.rate === null
														? html`<span class="sb-hint">No parcels on record.</span>`
														: html`
																<span class=${ 'sb-courier-record__rate' + ( record.rate < 60 ? ' is-bad' : '' ) }>
																	${ record.rate }%
																</span>
																<span class="sb-hint">
																	${ record.delivered } delivered · ${ record.returned } returned
																	${ record.cached ? ' · cached' : '' }
																</span>
														  ` }
												</div>
											`
										) }
									</div>
							  `
							: html`<p class="sb-hint">
									No courier is set up with a merchant panel login, so only this shop's own history
									is available. Add one under Settings → Courier.
							  </p>` }
				  `
				: null }
		<//>
	`;
}

function Score( { label, value, tone } ) {
	return html`
		<div class=${ 'sb-score' + ( tone ? ' sb-score--' + tone : '' ) }>
			<span class="sb-score__value">${ value }</span>
			<span class="sb-score__label">${ label }</span>
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
	// DOMParser rather than a detached div: a note can contain markup a plugin wrote, and a parsed
	// document never fetches an <img src> while its text is being read out of it.
	return new DOMParser().parseFromString( '<body>' + ( value || '' ) + '</body>', 'text/html' )
		.body.textContent.trim();
}
