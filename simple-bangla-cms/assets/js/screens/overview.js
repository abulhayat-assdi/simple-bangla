/*
 * The Overview screen.
 *
 * Every figure here arrives in one request. See inc/stats.php for why the server gathers them
 * that way rather than letting this screen ask five times.
 */

import { html, useState, useEffect, useCallback, Icon, Stat, ErrorBox } from '../ui.js';
import { api, money, count, SB, can } from '../api.js';

export function Overview() {
	const [ data, setData ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ busy, setBusy ] = useState( true );

	const load = useCallback( async ( refresh = false ) => {
		setBusy( true );
		setError( null );

		try {
			setData( await api( '/dashboard', { params: refresh ? { refresh: 1 } : null } ) );
		} catch ( e ) {
			setError( e );
		} finally {
			setBusy( false );
		}
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	const orders = ( data && data.orders && data.orders.by_status ) || {};
	const catalog = ( data && data.catalog ) || {};
	const revenue = data && data.revenue;
	const loading = busy && ! data;

	return html`
		<div class="sb-page">
			<h1 class="sb-page__title">Dashboard</h1>
			<p class="sb-page__lead">
				Welcome back${ SB.user ? ', ' + SB.user.display_name.split( ' ' )[ 0 ] : '' } — here is your store today.
			</p>

			<${ Notices } env=${ SB.environment } revenue=${ revenue } />

			${ error ? html`<${ ErrorBox } error=${ error } onRetry=${ () => load() } />` : null }

			<div class="sb-section-label">Revenue and orders</div>
			<div class="sb-stats">
				${ can( 'orders.view' ) && revenue
					? html`<${ Stat }
							icon="money"
							tone="ok"
							wide
							label="Total revenue"
							loading=${ loading }
							value=${ money( revenue.total ) }
							note=${ 'Last 30 days ' + money( revenue.last_30_days ) }
					  />`
					: null }
				<${ Stat } icon="clock" tone="warn" label="Pending" loading=${ loading } value=${ count( orders.pending ) } />
				<${ Stat } icon="cart" label="Processing" loading=${ loading } value=${ count( orders.processing ) } />
				<${ Stat } icon="bag" tone="ok" label="Completed" loading=${ loading } value=${ count( orders.completed ) } />
				<${ Stat } icon="refresh" tone="bad" label="Refunded" loading=${ loading } value=${ count( orders.refunded ) } />
			</div>

			<div class="sb-section-label">Catalog</div>
			<div class="sb-stats">
				<${ Stat } icon="box" label="Products" loading=${ loading } value=${ count( catalog.products ) } />
				<${ Stat } icon="layers" label="Categories" loading=${ loading } value=${ count( catalog.categories ) } />
				<${ Stat }
					icon="alert"
					tone=${ catalog.out_of_stock ? 'bad' : null }
					label="Out of stock"
					loading=${ loading }
					value=${ count( catalog.out_of_stock ) }
				/>
				<${ Stat }
					icon="alert"
					tone=${ catalog.low_stock ? 'warn' : null }
					label="Low stock"
					loading=${ loading }
					value=${ count( catalog.low_stock ) }
				/>
			</div>

			<p class="sb-page__lead" style="margin-top:24px">
				<button class="sb-btn sb-btn--ghost" onClick=${ () => load( true ) } disabled=${ busy }>
					<${ Icon } name="refresh" />
					${ busy ? 'Refreshing…' : 'Refresh figures' }
				</button>
				${ data && data.generated_at
					? html`<span class="sb-stat__note"> Updated ${ new Date( data.generated_at ).toLocaleTimeString() }${ data.cached ? ' (cached)' : '' }</span>`
					: null }
			</p>
		</div>
	`;
}

/**
 * Conditions that make the dashboard behave in a way that would otherwise look like a bug.
 */
function Notices( { env, revenue } ) {
	const notices = [];

	if ( env && ! env.theme_active ) {
		notices.push(
			'The Simple Bangla theme is not active, so homepage and appearance settings are unavailable.'
		);
	}

	if ( env && ! env.hpos_enabled ) {
		notices.push(
			'High-Performance Order Storage is off. Turn it on in WooCommerce → Settings → Advanced → Features before the store grows.'
		);
	}

	if ( revenue && revenue.source === 'unavailable' ) {
		notices.push(
			'WooCommerce Analytics has not built its reporting tables yet, so revenue reads zero until it does.'
		);
	}

	if ( ! notices.length ) {
		return null;
	}

	return notices.map(
		( text ) => html`<p class="sb-alert sb-alert--warn">${ text }</p>`
	);
}
