/*
 * The printable invoice.
 *
 * This is the one screen in the CMS whose text is Bangla. It follows the rule the project already
 * set for the checkout and the thank-you page: anything a customer reads is Bangla, everything
 * that is only ever seen by the store is English. This sheet goes in the parcel.
 *
 * The store's own details come from the same `theme_mod` values the storefront footer prints, so
 * changing the shop phone number in one place changes it on the invoice too.
 */

import { html, useState, useEffect, Icon, ErrorBox } from '../ui.js';
import { api, money, SB } from '../api.js';
import { href, onLinkClick } from '../router.js';
import { customerName, addressLines, dateOnly, statusLabelBn } from '../order-utils.js';

export function Invoice( { id } ) {
	const [ order, setOrder ] = useState( null );
	const [ store, setStore ] = useState( {} );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		api( 'wc/v3/orders/' + id )
			.then( setOrder )
			.catch( setError );

		// The storefront's own contact details, not a second copy kept in the CMS.
		api( '/settings', { params: { group: 'store' } } )
			.then( ( result ) => setStore( result.values || {} ) )
			.catch( () => setStore( {} ) );
	}, [ id ] );

	if ( error ) {
		return html`<div class="sb-page"><${ ErrorBox } error=${ error } /></div>`;
	}

	if ( ! order ) {
		return html`<div class="sb-page"><div class="sb-media-loading"><span class="sb-spinner"></span></div></div>`;
	}

	const items = order.line_items || [];
	const subtotal = items.reduce( ( sum, item ) => sum + Number( item.subtotal || 0 ), 0 );
	const shipping = ( order.shipping_lines || [] ).reduce( ( sum, line ) => sum + Number( line.total || 0 ), 0 );
	const address = addressLines( order.shipping ).length ? order.shipping : order.billing;

	return html`
		<div class="sb-page sb-invoice-page">
			<div class="sb-page__header sb-noprint">
				<div>
					<p class="sb-crumb">
						<a
							href=${ href( '/orders/' + id ) }
							onClick=${ ( e ) => onLinkClick( e, '/orders/' + id ) }
						>
							Order #${ order.number }
						</a>
					</p>
					<h1 class="sb-page__title">Invoice</h1>
					<p class="sb-page__lead">Printed in Bangla — this sheet goes to the customer.</p>
				</div>
				<div class="sb-row">
					<button class="sb-btn sb-btn--primary" onClick=${ () => window.print() }>
						<${ Icon } name="box" /> Print
					</button>
				</div>
			</div>

			<article class="sb-invoice" lang="bn">
				<header class="sb-invoice__head">
					<div>
						${ SB.brandLogo
							? html`<img class="sb-invoice__logo" src=${ SB.brandLogo } alt=${ SB.store.name } />`
							: html`<p class="sb-invoice__shop">${ SB.store.name }</p>` }
						<p class="sb-invoice__meta">
							${ store.simple_bangla_contact_address || '' }
							${ store.simple_bangla_contact_phone
								? html`<br />ফোন: ${ store.simple_bangla_contact_phone }`
								: null }
							${ store.simple_bangla_contact_email
								? html`<br />ইমেইল: ${ store.simple_bangla_contact_email }`
								: null }
						</p>
					</div>
					<div class="sb-invoice__id">
						<p class="sb-invoice__title">ইনভয়েস</p>
						<p class="sb-invoice__meta">
							অর্ডার নম্বর: <strong>#${ order.number }</strong><br />
							তারিখ: ${ dateOnly( order.date_created ) }<br />
							অবস্থা: ${ statusLabelBn( order.status ) }
						</p>
					</div>
				</header>

				<section class="sb-invoice__to">
					<p class="sb-invoice__label">গ্রাহকের তথ্য</p>
					<p class="sb-invoice__name">${ customerName( order ) }</p>
					<address class="sb-address">
						${ addressLines( address, true )
							.slice( 1 )
							.map( ( line, i ) => html`<span key=${ i }>${ line }</span>` ) }
						${ order.billing && order.billing.phone ? html`<span>ফোন: ${ order.billing.phone }</span>` : null }
					</address>
				</section>

				<table class="sb-invoice__items">
					<thead>
						<tr>
							<th>পণ্য</th>
							<th>দাম</th>
							<th>পরিমাণ</th>
							<th>মোট</th>
						</tr>
					</thead>
					<tbody>
						${ items.map(
							( item ) => html`
								<tr key=${ item.id }>
									<td>
										${ item.name }
										${ item.sku ? html`<span class="sb-invoice__sku">${ item.sku }</span>` : null }
									</td>
									<td>${ money( item.price ) }</td>
									<td>${ item.quantity }</td>
									<td>${ money( item.total ) }</td>
								</tr>
							`
						) }
					</tbody>
				</table>

				<div class="sb-invoice__totals">
					<div class="sb-totals__row"><span>সাবটোটাল</span><span>${ money( subtotal ) }</span></div>
					${ Number( order.discount_total ) > 0
						? html`<div class="sb-totals__row"><span>ছাড়</span><span>− ${ money( order.discount_total ) }</span></div>`
						: null }
					${ shipping > 0
						? html`<div class="sb-totals__row"><span>ডেলিভারি চার্জ</span><span>${ money( shipping ) }</span></div>`
						: null }
					<div class="sb-totals__row sb-totals__row--strong">
						<span>সর্বমোট</span><span>${ money( order.total ) }</span>
					</div>
				</div>

				<footer class="sb-invoice__foot">
					<p>পেমেন্ট পদ্ধতি: <strong>${ order.payment_method_title || '—' }</strong></p>
					${ order.customer_note ? html`<p>গ্রাহকের নোট: ${ order.customer_note }</p>` : null }
					<p class="sb-invoice__thanks">আমাদের সাথে কেনাকাটা করার জন্য ধন্যবাদ।</p>
					<p class="sb-invoice__meta">${ SB.store.url }</p>
				</footer>
			</article>
		</div>
	`;
}
