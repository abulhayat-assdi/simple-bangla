/*
 * The blocks an order is drawn from.
 *
 * There are two places an order is now shown: the pop-up card that opens from the Orders list, and
 * the full page behind `/orders/123` that keeps the notes, the refund and the invoice. They show the
 * same facts, so they draw them with the same components — the alternative was two copies of "what
 * is in the parcel" and "where is it going", which would have disagreed the first time either
 * changed, and it is the *card* the owner works from all morning.
 *
 * Everything here is presentational except the courier and fraud report, which fetches on demand and
 * says why. Actions are passed in: neither of these two screens should have to know how the other
 * saves a status.
 */

import { html, useState, Card, Icon, ErrorBox } from './ui.js';
import { api, money } from './api.js';
import { href, onLinkClick } from './router.js';
import {
	customerName,
	addressLines,
	dateTime,
	refundedTotal,
	itemAttributes,
	ORDER_OUTCOMES,
	isDispatched,
	offersOutcomes,
	collectsOnDelivery,
} from './order-utils.js';

/** What is in the parcel. */
export function OrderItems( { items } ) {
	return html`
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
	`;
}

/**
 * What will be collected.
 *
 * The subtotal is summed from the items rather than read off the order, because WooCommerce's own
 * `total` already has the delivery charge and any discount folded into it — printing that as a
 * subtotal would make the sum on screen not add up.
 */
export function OrderTotals( { order } ) {
	const items = order.line_items || [];
	const subtotal = items.reduce( ( sum, item ) => sum + Number( item.subtotal || 0 ), 0 );
	const shipping = ( order.shipping_lines || [] ).reduce( ( sum, line ) => sum + Number( line.total || 0 ), 0 );
	const refunded = refundedTotal( order );

	return html`
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
						<${ TotalRow } label="Net" value=${ money( Number( order.total || 0 ) - refunded ) } strong />
				  `
				: null }
		</div>
	`;
}

export function TotalRow( { label, value, strong, tone } ) {
	return html`
		<div class=${ 'sb-totals__row' + ( strong ? ' sb-totals__row--strong' : '' ) }>
			<span>${ label }</span>
			<span class=${ tone ? 'sb-totals__' + tone : '' }>${ value }</span>
		</div>
	`;
}

/**
 * Where the parcel is going.
 *
 * The phone number is a `tel:` link because confirming the order by phone is the next thing that
 * happens to almost every order this shop takes, and on a laptop that link is what hands the number
 * to the desk phone software or the owner's own mobile.
 *
 * @param {object}   props
 * @param {object}   props.order   Order record.
 * @param {Function} [props.onEdit] Shown as an "Edit info" button when given.
 */
export function DeliveryDetails( { order, onEdit } ) {
	const shippingLines = addressLines( order.shipping, true );

	return html`
		<div class="sb-detail">
			${ onEdit
				? html`<button class="sb-linkbtn sb-detail__edit" onClick=${ onEdit }>Edit info</button>`
				: null }

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

			${ shippingLines.length
				? html`
						<span class="sb-field__label">Shipping address</span>
						<address class="sb-address">
							${ shippingLines.map( ( line, i ) => html`<span key=${ i }>${ line }</span>` ) }
						</address>
				  `
				: null }

			${ order.customer_note
				? html`<p class="sb-note-customer"><strong>Customer note:</strong> ${ order.customer_note }</p>`
				: null }

			${ order.customer_ip_address
				? html`
						<span class="sb-field__label">Client IP</span>
						<p class="sb-mono">
							${ order.customer_ip_address }
							<a class="sb-inline-link" href=${ href( '/blocked' ) } onClick=${ ( e ) => onLinkClick( e, '/blocked' ) }>
								Block this address
							</a>
						</p>
				  `
				: null }
		</div>
	`;
}

/** How it will be paid for. */
export function PaymentSummary( { order } ) {
	return html`
		<div class="sb-payment">
			<p class="sb-payment__method">${ order.payment_method_title || 'Not recorded' }</p>
			<p class="sb-hint">
				${ collectsOnDelivery( order )
					? 'To collect on delivery: ' + money( order.total )
					: ( order.date_paid ? 'Paid ' + dateTime( order.date_paid ) : 'Not paid yet.' ) }
			</p>
			${ order.transaction_id ? html`<p class="sb-hint">Transaction ${ order.transaction_id }</p>` : null }
		</div>
	`;
}

/**
 * The one decision this order is waiting for.
 *
 * Before dispatch there is exactly one: hand it to the courier. After dispatch there are exactly
 * three — it arrived, it was cancelled, or it came back — and they are the same three the order list
 * offers, read from the same table so the card and the list can never differ about what is on offer.
 * Anything else, such as marking an order failed, is in the status list on the full order page: this
 * is the button a shop presses forty times on a Sunday morning, and a dropdown between the owner and
 * it would be forty extra taps.
 *
 * **Dispatch no longer changes the status** (owner's decision, 2026-08-12). The consignment number is
 * what records it, which is why the buttons below key off the shipment rather than off a stage.
 *
 * @param {object}   props
 * @param {object}   props.order    Order record, for its dispatch record and status.
 * @param {boolean}  props.busy     A status write is in flight.
 * @param {boolean}  props.sending  A dispatch is in flight.
 * @param {Function} props.onStatus Called with the new status.
 * @param {Function} props.onSend   Called to dispatch.
 */
export function StageActions( { order, busy, sending, onStatus, onSend } ) {
	const shipment = order.sb_courier;

	return html`
		${ shipment
			? html`
					<div class="sb-shipment">
						<p class="sb-shipment__label">
							<${ Icon } name="truck" size=${ 16 } /> ${ shipment.provider_label }
						</p>
						<p class="sb-mono">#${ shipment.consignment_id || '—' }</p>
						<p class="sb-hint">Sent ${ dateTime( new Date( shipment.sent_at * 1000 ) ) }</p>
					</div>
			  `
			: null }

		${ offersOutcomes( order )
			? html`
					${ ORDER_OUTCOMES.map(
						( outcome ) => html`
							<button
								key=${ outcome.status }
								class=${ 'sb-btn sb-btn--block sb-btn--' + outcome.tone +
									( outcome.tone === 'danger' ? ' sb-btn--outline' : '' ) }
								disabled=${ busy }
								onClick=${ () => onStatus( outcome.status ) }
							>
								${ outcome.label }
							</button>
						`
					) }
					<p class="sb-hint">Returned puts the items back into stock; completed and canceled do not.</p>
			  `
			: null }

		${ isDispatched( order )
			? html`
					<button class="sb-btn sb-btn--ghost sb-btn--block" disabled=${ sending } onClick=${ () => onSend( false ) }>
						<${ Icon } name="truck" size=${ 16 } />
						${ sending ? 'Sending…' : 'Send to courier again' }
					</button>
			  `
			: html`
					<button class="sb-btn sb-btn--primary sb-btn--block" disabled=${ sending } onClick=${ () => onSend( false ) }>
						<${ Icon } name="truck" size=${ 16 } />
						${ sending ? 'Sending…' : 'Send to courier' }
					</button>
					<p class="sb-hint">
						Books the parcel with the courier set up under Settings. The order stays in New Orders
						with its consignment number until you say how it ended.
					</p>
			  ` }
	`;
}

/**
 * How this customer's parcels have gone everywhere else.
 *
 * **This is not this shop's history.** It used to lead with one, and the owner asked for it removed
 * (2026-08-12): the shop's own orders from a number are one search away on the Orders screen, and
 * reading them inside a card headed "fraud" answered a question nobody was asking. The question this
 * card exists for is how the customer behaves with *other* shops, and only a courier can answer it.
 *
 * Steadfast is the report; Pathao and RedX are corroboration and are only listed when they are set
 * up. None of the three publishes a documented API for any of this — every figure here comes from
 * signing in to a merchant portal and calling what its own dashboard calls — so a courier that
 * cannot be reached says so on its own line rather than being reported as zero. "No record" and
 * "never delivered successfully" point in opposite directions and must never render the same.
 *
 * Loaded on demand rather than with the order. It is a sign-in to an outside service and it must not
 * sit between the owner and an order they only wanted to read.
 */
export function FraudReport( { id, phone } ) {
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

	const couriers = report ? report.couriers : [];
	const primary = couriers.find( ( record ) => record.primary );
	const others = couriers.filter( ( record ) => ! record.primary );

	return html`
		<${ Card }
			title="Customer delivery record"
			action=${ html`
				<button class="sb-btn sb-btn--ghost sb-btn--sm" disabled=${ loading } onClick=${ () => fetchReport( !! report ) }>
					${ loading ? 'Checking…' : report ? 'Check again' : 'Check this number' }
				</button>
			` }
		>
			${ ! report && ! loading && ! failed
				? html`<p class="sb-hint">
						Nothing is requested until you ask. Checking signs in to the courier's merchant panel to
						read how many parcels ${ phone } has taken delivery of — across every shop, not just this
						one — and whether any merchant has filed a report against the number.
				  </p>`
				: null }

			${ failed ? html`<${ ErrorBox } error=${ failed } onRetry=${ () => fetchReport( true ) } />` : null }

			${ report && ! couriers.length
				? html`<p class="sb-hint">
						This order has no usable phone number, so there is nothing to look up.
				  </p>`
				: null }

			${ primary ? html`<${ PrimaryRecord } record=${ primary } />` : null }

			${ others.length
				? html`
						<p class="sb-field__label">Other couriers</p>
						<div class="sb-courier-records">
							${ others.map(
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
				: null }
		<//>
	`;
}

/**
 * Steadfast's answer, drawn full size.
 *
 * The rate is the headline because it is the one number a decision is made on, but it is only shown
 * beside the counts it came from: 100% off a single parcel and 100% off ninety are the same
 * percentage and not remotely the same customer.
 */
function PrimaryRecord( { record } ) {
	if ( record.error ) {
		return html`
			<p class="sb-field__label">${ record.label }</p>
			<p class="sb-courier-record__error">${ record.error }</p>
			<p class="sb-hint">
				The delivery record needs the portal email and password you sign in to steadfast.com.bd
				with — the API key cannot read it. Add them under Settings → Courier.
			</p>
		`;
	}

	return html`
		<p class="sb-field__label">
			${ record.label } — everywhere this number has ordered${ record.cached ? ' · cached' : '' }
		</p>

		${ record.total === 0
			? html`<p class="sb-hint">
					No parcels on record. Either the customer is new to cash on delivery, or they order under
					a different number.
			  </p>`
			: html`
					<div class="sb-scoreboard">
						<${ Score } label="Total orders" value=${ record.total } />
						<${ Score } label="Received" value=${ record.delivered } tone="ok" />
						<${ Score } label="Refused / returned" value=${ record.returned } tone="bad" />
						<${ Score }
							label="Success rate"
							value=${ record.rate + '%' }
							tone=${ record.rate < 60 ? 'bad' : 'ok' }
						/>
					</div>
			  ` }

		${ record.reports_read
			? html`
					<p class="sb-field__label">Reports against this number</p>
					${ record.reports.length
						? html`
								<div class="sb-reports">
									${ record.reports.map(
										( item, index ) => html`
											<div key=${ index } class="sb-report">
												${ item.text ? html`<p class="sb-report__text">${ item.text }</p>` : null }
												${ item.source || item.date
													? html`<p class="sb-report__meta">
															${ [ item.source, item.date ].filter( Boolean ).join( ' · ' ) }
													  </p>`
													: null }
											</div>
										`
									) }
								</div>
						  `
						: html`<p class="sb-hint">None. No merchant has filed a report against this number.</p>` }
			  `
			: null }
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
