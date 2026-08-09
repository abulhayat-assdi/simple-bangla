/*
 * Hot Deals.
 *
 * There is nothing to choose here but a number: the row is filled automatically from whichever
 * products are on sale, newest first. That makes "how many" a question the owner cannot answer
 * without knowing how many products are actually on sale right now — so the screen shows them.
 * An empty preview is the honest early warning that the homepage row will be empty too.
 */

import { html, useState, useEffect, Card, Field, Badge, EmptyState } from '../ui.js';
import { apiList, money } from '../api.js';
import { href, onLinkClick } from '../router.js';
import { useSettings, SettingsPage, NumberInput, COUNT_MIN, COUNT_MAX } from '../settings-form.js';

const COUNT_KEY = 'simple_bangla_home_hotdeals_count';

export function HotDeals() {
	const state = useSettings( 'hotdeals' );
	const count = Number( state.values[ COUNT_KEY ] || 0 );

	return html`
		<${ SettingsPage }
			title="Hot Deals"
			lead="The card slider beside the hero banner. It fills itself with products that have a sale price."
			state=${ state }
		>
			<${ Card } title="How many to show">
				<${ Field }
					label="Products in the slider"
					id="hd-count"
					hint=${ `Only products with a sale price appear, newest first. ${ COUNT_MIN }–${ COUNT_MAX }.` }
				>
					<${ NumberInput }
						id="hd-count"
						min=${ COUNT_MIN }
						max=${ COUNT_MAX }
						value=${ state.values[ COUNT_KEY ] }
						onChange=${ ( value ) => state.set( COUNT_KEY, value ) }
					/>
				<//>
			<//>

			<${ OnSalePreview } limit=${ count } />
		<//>
	`;
}

/**
 * What the row will actually contain.
 *
 * Read straight from WooCommerce with the same filter the theme uses, so this is the row, not an
 * approximation of it.
 */
function OnSalePreview( { limit } ) {
	const [ items, setItems ] = useState( null );
	const [ total, setTotal ] = useState( 0 );

	useEffect( () => {
		let cancelled = false;

		apiList( 'wc/v3/products', {
			params: {
				on_sale: true,
				status: 'publish',
				per_page: Math.max( 1, Math.min( limit || 8, 20 ) ),
				orderby: 'date',
				order: 'desc',
			},
		} )
			.then( ( result ) => {
				if ( ! cancelled ) {
					setItems( result.items );
					setTotal( result.total );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setItems( [] );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ limit ] );

	const short = items && total > 0 && total < limit;

	return html`
		<${ Card }
			title="On sale right now"
			action=${ items
				? short
					? html`<${ Badge } tone="warn">${ total } available<//>`
					: html`<${ Badge } tone="ok">${ total } available<//>`
				: null }
		>
			${ ! items
				? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
				: ! items.length
				? html`<${ EmptyState }
						title="Nothing is on sale"
						body="The Hot Deals slider is hidden until at least one product has a sale price."
				  />`
				: html`
						${ short
							? html`<p class="sb-alert sb-alert--warn">
									Only ${ total } product${ total === 1 ? ' is' : 's are' } on sale, so the slider
									will show ${ total } rather than ${ limit }.
							  </p>`
							: null }

						<ul class="sb-minilist">
							${ items.map( ( product ) => {
								const image = product.images && product.images[ 0 ];

								return html`
									<li class="sb-minilist__item" key=${ product.id }>
										<span class="sb-minilist__thumb">
											${ image
												? html`<img src=${ image.src } alt="" loading="lazy" />`
												: null }
										</span>
										<a
											class="sb-minilist__name"
											href=${ href( '/products/' + product.id ) }
											onClick=${ ( e ) => onLinkClick( e, '/products/' + product.id ) }
										>
											${ product.name }
										</a>
										<span class="sb-minilist__meta">
											<s>${ money( product.regular_price ) }</s>
											${ ' ' }
											<strong>${ money( product.sale_price || product.price ) }</strong>
										</span>
									</li>
								`;
							} ) }
						</ul>
				  ` }
		<//>
	`;
}
