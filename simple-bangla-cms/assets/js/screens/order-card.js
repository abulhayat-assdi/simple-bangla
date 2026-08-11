/*
 * One order, as a card over the list.
 *
 * Built to the reference the owner supplied (2026-08-10) and to the way the shop actually works:
 * open an order, read what is in it and where it is going, ring the customer, hand it to the
 * courier, close it, open the next one. Every one of those steps used to cost a page load away from
 * the list and a page load back, which on a morning's twenty orders is forty waits — and the list
 * lost its scroll position and its tab each time.
 *
 * **The card is not a second order screen.** It carries the four things that decision needs — the
 * items, the delivery details, the money, and the one action the order is waiting for — plus fixing
 * a wrong address and deleting a junk order. Notes, refunds and the printable invoice stay on the
 * full page behind `/orders/123`, which is one click away in the card's footer and is still what a
 * deep link or a bookmark opens.
 *
 * The palette is the shop's own black and cream, not the reference's dark blue: this is the same
 * product as the other nineteen screens and it should not look like a different one.
 */

import { html, useState, useEffect, useCallback, useRef, Icon, Badge, Field, Confirm, ErrorBox, toast } from '../ui.js';
import { api } from '../api.js';
import { href, onLinkClick } from '../router.js';
import { OrderItems, OrderTotals, DeliveryDetails, PaymentSummary, StageActions, FraudReport } from '../order-parts.js';
import { statusLabel, statusTone, dateTime } from '../order-utils.js';

/**
 * @param {object}   props
 * @param {number}   props.id       Order ID.
 * @param {Function} props.onClose  Called when the card is dismissed.
 * @param {Function} props.onChange Called after anything that changes the order, so the list behind
 *                                  the card can refresh its rows and its tab counts.
 * @param {Function} props.onDelete Called after the order is erased.
 */
export function OrderCard( { id, onClose, onChange, onDelete } ) {
	const [ order, setOrder ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ busy, setBusy ] = useState( false );
	const [ sending, setSending ] = useState( false );

	const [ editing, setEditing ] = useState( null );
	const [ resending, setResending ] = useState( false );
	const [ erasing, setErasing ] = useState( false );

	const load = useCallback( async () => {
		setError( null );

		try {
			setOrder( await api( 'wc/v3/orders/' + id ) );
		} catch ( e ) {
			setError( e );
		}
	}, [ id ] );

	useEffect( () => {
		load();
	}, [ load ] );

	/*
	 * Escape closes, and the page behind stops scrolling under the card.
	 *
	 * `onClose` is read through a ref so this effect can ignore a handler the parent rebuilds on
	 * every render — the same trap that made a dialog drop keystrokes in phase 4, where an effect
	 * depending on a parent's arrow function re-ran on every change and pulled focus out of the field
	 * being typed into. The three dialog flags are real dependencies: while one of them is open,
	 * Escape belongs to that dialog and must not close the card underneath it as well.
	 */
	const onCloseRef = useRef( onClose );
	onCloseRef.current = onClose;

	useEffect( () => {
		const onKey = ( event ) => {
			if ( 'Escape' === event.key && ! editing && ! resending && ! erasing ) {
				onCloseRef.current();
			}
		};

		document.addEventListener( 'keydown', onKey );
		document.body.style.overflow = 'hidden';

		return () => {
			document.removeEventListener( 'keydown', onKey );
			document.body.style.overflow = '';
		};
	}, [ editing, resending, erasing ] );

	const saveStatus = async ( next ) => {
		setBusy( true );

		try {
			const updated = await api( 'wc/v3/orders/' + id, { method: 'PUT', body: { status: next } } );
			setOrder( updated );
			toast( 'Status changed to ' + statusLabel( next ) );
			onChange && onChange();
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	/*
	 * The endpoint refuses a second dispatch unless `force` is sent, because two consignments for one
	 * parcel is a bill paid twice. A 409 is therefore a question — "send it again?" — rather than an
	 * error to report and forget.
	 */
	const send = async ( force = false ) => {
		setSending( true );

		try {
			const result = await api( '/orders/' + id + '/courier', { method: 'POST', body: { force } } );

			toast( 'Sent to ' + result.shipment.provider_label + ' · ' + ( result.shipment.consignment_id || 'booked' ) );
			setResending( false );
			await load();
			onChange && onChange();
		} catch ( e ) {
			if ( 409 === e.status && 'sb_cms_already_sent' === e.code && ! force ) {
				setResending( true );
			} else {
				toast( e.message, 'bad' );
			}
		} finally {
			setSending( false );
		}
	};

	const saveInfo = async () => {
		setBusy( true );

		try {
			const billing = {
				first_name: editing.first_name,
				last_name: editing.last_name,
				phone: editing.phone,
				address_1: editing.address_1,
				city: editing.city,
			};

			/*
			 * Shipping is written with the same values. The checkout only ever asks for one address —
			 * it is a single-country cash-on-delivery shop — so leaving shipping behind would put the
			 * corrected number on the invoice and the old one on the courier's label.
			 */
			const updated = await api( 'wc/v3/orders/' + id, {
				method: 'PUT',
				body: {
					billing,
					shipping: {
						first_name: editing.first_name,
						last_name: editing.last_name,
						address_1: editing.address_1,
						city: editing.city,
					},
				},
			} );

			setOrder( updated );
			setEditing( null );
			toast( 'Delivery details updated' );
			onChange && onChange();
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	/*
	 * `force` erases rather than trashes. A trashed order still counts in WooCommerce's own reports
	 * and still turns up in a search, so a "deleted" order that kept appearing would be the more
	 * confusing outcome — but it cannot be undone, and the confirmation says so in as many words.
	 */
	const erase = async () => {
		setBusy( true );

		try {
			await api( 'wc/v3/orders/' + id, { method: 'DELETE', params: { force: true } } );
			toast( 'Order deleted' );
			onDelete ? onDelete() : onClose();
		} catch ( e ) {
			toast( e.message, 'bad' );
			setBusy( false );
			setErasing( false );
		}
	};

	const fullPage = '/orders/' + id;

	return html`
		<div class="sb-ordermodal" role="dialog" aria-modal="true" aria-label=${ 'Order ' + id }>
			<button class="sb-modal__scrim" aria-label="Close" onClick=${ onClose }></button>

			<div class="sb-ordermodal__panel">
				<header class="sb-ordermodal__head">
					<div class="sb-ordermodal__title">
						<p class="sb-ordermodal__number">Order ${ order ? '#' + order.number : '' }</p>
						${ order
							? html`<p class="sb-ordermodal__date">Placed ${ dateTime( order.date_created ) }</p>`
							: null }
					</div>
					${ order
						? html`<${ Badge } tone=${ statusTone( order.status ) }>${ statusLabel( order.status ) }<//>`
						: null }
					<button class="sb-icon-btn sb-ordermodal__close" aria-label="Close" onClick=${ onClose }>×</button>
				</header>

				<div class="sb-ordermodal__body">
					${ error ? html`<${ ErrorBox } error=${ error } onRetry=${ load } />` : null }

					${ ! order && ! error
						? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
						: null }

					${ order
						? html`
								<div class="sb-ordermodal__grid">
									<div class="sb-ordermodal__col">
										<section class="sb-panel">
											<h3 class="sb-panel__title">Items ordered</h3>
											<${ OrderItems } items=${ order.line_items || [] } />
										</section>

										<section class="sb-panel">
											<h3 class="sb-panel__title">Delivery information</h3>
											<${ DeliveryDetails } order=${ order } onEdit=${ () => setEditing( toEdit( order ) ) } />
										</section>

										<${ FraudReport } id=${ id } phone=${ ( order.billing && order.billing.phone ) || '' } />
									</div>

									<div class="sb-ordermodal__col">
										<section class="sb-panel">
											<h3 class="sb-panel__title">Order summary</h3>
											<${ OrderTotals } order=${ order } />
										</section>

										<section class="sb-panel">
											<h3 class="sb-panel__title">Payment</h3>
											<${ PaymentSummary } order=${ order } />
										</section>

										<section class="sb-panel">
											<h3 class="sb-panel__title">Actions</h3>
											<${ StageActions }
												order=${ order }
												status=${ order.status }
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
										</section>
									</div>
								</div>
						  `
						: null }
				</div>

				${ order
					? html`
							<footer class="sb-ordermodal__foot">
								<a class="sb-btn sb-btn--ghost" href=${ href( fullPage ) } onClick=${ ( e ) => onLinkClick( e, fullPage ) }>
									<${ Icon } name="file" size=${ 16 } />
									Open full page — notes, refunds, invoice
								</a>
								<button class="sb-btn sb-btn--ghost" onClick=${ onClose }>Close</button>
							</footer>
					  `
					: null }
			</div>

			${ editing
				? html`<${ Confirm }
						title="Edit delivery details"
						confirmLabel="Save details"
						busy=${ busy }
						onConfirm=${ saveInfo }
						onClose=${ () => setEditing( null ) }
						body=${ html`
							<${ Field } label="First name" id="oe-first">
								<input
									class="sb-input"
									id="oe-first"
									value=${ editing.first_name }
									onInput=${ ( e ) => setEditing( { ...editing, first_name: e.target.value } ) }
								/>
							<//>
							<${ Field } label="Last name" id="oe-last">
								<input
									class="sb-input"
									id="oe-last"
									value=${ editing.last_name }
									onInput=${ ( e ) => setEditing( { ...editing, last_name: e.target.value } ) }
								/>
							<//>
							<${ Field } label="Phone" id="oe-phone" hint="The number the courier will ring.">
								<input
									class="sb-input"
									id="oe-phone"
									inputmode="tel"
									value=${ editing.phone }
									onInput=${ ( e ) => setEditing( { ...editing, phone: e.target.value } ) }
								/>
							<//>
							<${ Field } label="Address" id="oe-address">
								<input
									class="sb-input"
									id="oe-address"
									value=${ editing.address_1 }
									onInput=${ ( e ) => setEditing( { ...editing, address_1: e.target.value } ) }
								/>
							<//>
							<${ Field } label="City" id="oe-city">
								<input
									class="sb-input"
									id="oe-city"
									value=${ editing.city }
									onInput=${ ( e ) => setEditing( { ...editing, city: e.target.value } ) }
								/>
							<//>
							<p class="sb-hint">
								This changes the order, not the customer's saved account. Correct it before sending
								the parcel to the courier — after that the courier has the old address.
							</p>
						` }
				  />`
				: null }

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
						title=${ 'Permanently delete order #' + ( order ? order.number : id ) + '?' }
						body="It is erased rather than trashed: the order, its items and its notes are gone and cannot be recovered. Sales figures already reported will change."
						confirmLabel="Delete permanently"
						busy=${ busy }
						onConfirm=${ erase }
						onClose=${ () => setErasing( false ) }
				  />`
				: null }
		</div>
	`;
}

/** The editable half of an order's addresses. */
function toEdit( order ) {
	const billing = order.billing || {};

	return {
		first_name: billing.first_name || '',
		last_name: billing.last_name || '',
		phone: billing.phone || '',
		address_1: billing.address_1 || '',
		city: billing.city || '',
	};
}
