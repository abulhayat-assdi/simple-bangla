/*
 * The Dashboard.
 *
 * Every figure here arrives in one request. See inc/stats.php for why the server gathers them
 * that way rather than letting this screen ask five times.
 *
 * **Laid out on one four-column grid** (2026-08-09). It used to be two `auto-fit` grids, one holding
 * five tiles and one holding four, so the two rows resolved to different tile widths and nothing
 * lined up down the page. Revenue is two tiles rather than one number with a second in its footnote,
 * which fills the first row honestly instead of padding it.
 */

import { html, useState, useEffect, useCallback, useMemo, Icon, Stat, Select, ErrorBox } from '../ui.js';
import { api, money, count, SB, can } from '../api.js';
import { href, onLinkClick } from '../router.js';
import { ORDER_VIEWS } from '../order-utils.js';

/** How each stage is drawn. Kept beside the screen that draws them, not in the shared table. */
const STAGE_ICONS = { new: 'clock', courier: 'truck', completed: 'bag', returned: 'refresh' };
const STAGE_TONES = { new: 'warn', courier: '', completed: 'ok', returned: 'bad' };

/** What the dashboard opens on. */
const DEFAULT_PERIOD = '30d';

/**
 * The periods offered, newest first: the rolling spans, then the last two years month by month.
 *
 * The months are generated here rather than fetched, because the browser already knows today's
 * date and a second request to be told the names of the months would be absurd. The server parses
 * `YYYY-MM` and answers with the range it resolved, so the two cannot drift.
 *
 * Two years is the cut-off. A shop that wants March 2023 has outgrown a dropdown and wants a report.
 *
 * @return Array<{value:string,label:string}>
 */
function periodOptions() {
	const options = [
		{ value: '30d', label: 'Last 30 days' },
		{ value: '90d', label: 'Last 90 days' },
		{ value: '12m', label: 'Last 12 months' },
		{ value: 'all', label: 'All time' },
	];

	const now = new Date();

	for ( let back = 0; back < 24; back++ ) {
		// Day 1 of the month, so stepping back never lands on the 31st of a 30-day month.
		const month = new Date( now.getFullYear(), now.getMonth() - back, 1 );
		const value = month.getFullYear() + '-' + String( month.getMonth() + 1 ).padStart( 2, '0' );

		options.push( {
			value,
			label: month.toLocaleDateString( undefined, { month: 'long', year: 'numeric' } ),
		} );
	}

	return options;
}

export function Overview() {
	const [ data, setData ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ busy, setBusy ] = useState( true );

	const [ period, setPeriod ] = useState( DEFAULT_PERIOD );

	const options = useMemo( periodOptions, [] );

	const load = useCallback(
		async ( refresh = false ) => {
			setBusy( true );
			setError( null );

			try {
				const params = { period };

				if ( refresh ) {
					params.refresh = 1;
				}

				setData( await api( '/dashboard', { params } ) );
			} catch ( e ) {
				setError( e );
			} finally {
				setBusy( false );
			}
		},
		[ period ]
	);

	useEffect( () => {
		load();
	}, [ load ] );

	const orders = ( data && data.orders && data.orders.by_status ) || {};
	const catalog = ( data && data.catalog ) || {};
	const revenue = data && data.revenue;
	const loading = busy && ! data;

	// The server's own answer, so the caption cannot claim a period the figures are not from.
	const periodLabel = ( data && data.period && data.period.label ) || '';

	const stageCount = ( view ) => view.statuses.reduce( ( sum, status ) => sum + ( orders[ status ] || 0 ), 0 );

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Dashboard</h1>
					<p class="sb-page__lead">
						Welcome back${ SB.user ? ', ' + SB.user.display_name.split( ' ' )[ 0 ] : '' } — here is your
						store today.
					</p>
				</div>
				<div class="sb-row sb-page__actions">
					<${ Select }
						value=${ period }
						options=${ options }
						disabled=${ busy }
						onChange=${ setPeriod }
					/>
					<button class="sb-btn sb-btn--ghost" onClick=${ () => load( true ) } disabled=${ busy }>
						<${ Icon } name="refresh" size=${ 16 } />
						${ busy ? 'Refreshing…' : 'Refresh' }
					</button>
				</div>
			</div>

			<${ Notices } env=${ SB.environment } revenue=${ revenue } />

			${ error ? html`<${ ErrorBox } error=${ error } onRetry=${ () => load() } />` : null }

			${ can( 'orders.view' ) && revenue
				? html`
						<h2 class="sb-section-label">Revenue<span class="sb-section-label__period">${ periodLabel }</span></h2>
						<div class="sb-stats">
							<${ Stat }
								icon="money"
								tone="ok"
								wide
								label=${ periodLabel || 'Selected period' }
								loading=${ loading }
								value=${ money( revenue.period ) }
								note="Paid orders placed in this period"
							/>
							<${ Stat }
								icon="layers"
								wide
								label="All time"
								loading=${ loading }
								value=${ money( revenue.total ) }
								note="Every paid order the store has taken"
							/>
						</div>
				  `
				: null }

			<h2 class="sb-section-label">Orders<span class="sb-section-label__period">${ periodLabel }</span></h2>
			<div class="sb-stats">
				${ /*
				 * The same four stages the Orders screen filters by, summed the same way, so the two
				 * screens cannot disagree. Reading raw WooCommerce statuses here was what made the
				 * dashboard say "Processing 1" beside an Orders screen that called the same order new.
				 */
				ORDER_VIEWS.filter( ( view ) => view.key !== 'failed' ).map(
					( view ) => html`
						<${ Stat }
							key=${ view.key }
							icon=${ STAGE_ICONS[ view.key ] }
							tone=${ STAGE_TONES[ view.key ] }
							label=${ view.label }
							loading=${ loading }
							value=${ count( stageCount( view ) ) }
							to=${ can( 'orders.view' ) ? href( '/orders' ) : null }
							onNavigate=${ ( e ) => onLinkClick( e, '/orders' ) }
						/>
					`
				) }
			</div>

			<h2 class="sb-section-label">
				Catalog<span class="sb-section-label__period">Right now, not the period above</span>
			</h2>
			<div class="sb-stats">
				<${ Stat }
					icon="box"
					label="Products"
					loading=${ loading }
					value=${ count( catalog.products ) }
					to=${ can( 'products.view' ) ? href( '/products' ) : null }
					onNavigate=${ ( e ) => onLinkClick( e, '/products' ) }
				/>
				<${ Stat }
					icon="layers"
					label="Categories"
					loading=${ loading }
					value=${ count( catalog.categories ) }
					to=${ can( 'products.view' ) ? href( '/categories' ) : null }
					onNavigate=${ ( e ) => onLinkClick( e, '/categories' ) }
				/>
				<${ Stat }
					icon="alert"
					tone=${ catalog.out_of_stock ? 'bad' : null }
					label="Out of stock"
					loading=${ loading }
					value=${ count( catalog.out_of_stock ) }
					to=${ can( 'products.view' ) ? href( '/products' ) : null }
					onNavigate=${ ( e ) => onLinkClick( e, '/products' ) }
				/>
				<${ Stat }
					icon="alert"
					tone=${ catalog.low_stock ? 'warn' : null }
					label="Low stock"
					loading=${ loading }
					value=${ count( catalog.low_stock ) }
					to=${ can( 'products.view' ) ? href( '/products' ) : null }
					onNavigate=${ ( e ) => onLinkClick( e, '/products' ) }
				/>
			</div>

			<${ QuickActions } />

			<p class="sb-updated">
				${ data && data.generated_at
					? 'Figures updated ' +
					  new Date( data.generated_at ).toLocaleTimeString() +
					  ( data.cached ? ' · cached for five minutes' : '' )
					: '' }
			</p>
		</div>
	`;
}

/**
 * The handful of things a shop owner opens the CMS to start.
 *
 * Each one is gated on the ability its destination needs, so a staff account is never offered a
 * shortcut into a 403. Anything the account cannot reach simply is not built, and if that leaves
 * nothing the card does not render at all rather than showing an empty box.
 */
function QuickActions() {
	const actions = [
		{
			to: '/orders',
			ability: 'orders.view',
			icon: 'bag',
			title: 'Confirm new orders',
			desc: 'Call the customer, then send to the courier',
		},
		{
			to: '/products/new',
			ability: 'products.manage',
			icon: 'box',
			title: 'Add a product',
			desc: 'Name, price, photos and stock',
		},
		{
			to: '/coupons',
			ability: 'store.manage',
			icon: 'tag',
			title: 'Create a coupon',
			desc: 'A discount code for a campaign',
		},
		{
			// The one destination that is not a CMS route: the shop itself, as a customer sees it.
			external: SB.store ? SB.store.url : '/',
			icon: 'grid',
			title: 'View the store',
			desc: 'Open the shop in a new tab',
		},
	].filter( ( action ) => ! action.ability || can( action.ability ) );

	if ( ! actions.length ) {
		return null;
	}

	return html`
		<h2 class="sb-section-label">Shortcuts</h2>
		<div class="sb-quick">
			${ actions.map(
				( action ) => html`
					<a
						key=${ action.title }
						class="sb-quick__item"
						href=${ action.external || href( action.to ) }
						target=${ action.external ? '_blank' : null }
						rel=${ action.external ? 'noreferrer' : null }
						onClick=${ action.external ? null : ( e ) => onLinkClick( e, action.to ) }
					>
						<span class="sb-quick__icon"><${ Icon } name=${ action.icon } /></span>
						<span class="sb-quick__text">
							<span class="sb-quick__title">${ action.title }</span>
							<span class="sb-quick__desc">${ action.desc }</span>
						</span>
						<span class="sb-quick__arrow"><${ Icon } name="chevron" size=${ 16 } /></span>
					</a>
				`
			) }
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

	return notices.map( ( text ) => html`<p class="sb-alert sb-alert--warn">${ text }</p>` );
}
