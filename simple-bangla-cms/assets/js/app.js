/*
 * The application shell.
 *
 * Routing lives in router.js so screens can navigate on their own; this file only decides which
 * screen a path maps to and draws the chrome around it.
 *
 * Nav links keep a real href even though a click is intercepted. Middle-click, ctrl-click and
 * "open in new tab" then behave the way the owner expects, which an onClick-only button breaks.
 */

import { html, render, useState, useEffect, Icon, Placeholder, Toaster } from './ui.js';
import { SB, canAny } from './api.js';
import { NAV, BUILT_THROUGH, findRoute } from './nav.js';
import { useRoute, href, onLinkClick, canGoBack, goBack, parentPath } from './router.js';
import { Overview } from './screens/overview.js';
import { Products } from './screens/products.js';
import { ProductEdit } from './screens/product-edit.js';
import { Categories } from './screens/categories.js';
import { Orders } from './screens/orders.js';
import { OrderDetail } from './screens/order-detail.js';
import { Invoice } from './screens/invoice.js';
import { Hero } from './screens/hero.js';
import { HotDeals } from './screens/hot-deals.js';
import { Circles } from './screens/circles.js';
import { Rows } from './screens/rows.js';
import { Banners } from './screens/banners.js';
import { MenuScreen } from './screens/menu.js';
import { FooterSettings } from './screens/footer.js';
import { Reviews } from './screens/reviews.js';
import { Settings } from './screens/settings.js';
import { Coupons } from './screens/coupons.js';
import { Customers } from './screens/customers.js';
import { Blocked } from './screens/blocked.js';
import { Admins } from './screens/admins.js';

/**
 * Screens with no parameters in their path.
 *
 * The homepage group added five at once, all of the same shape; another five `if` blocks would
 * have buried the two routes that genuinely need a pattern match.
 */
const SIMPLE = {
	'/': { title: 'Overview', view: () => html`<${ Overview } />` },
	'/products': { title: 'Products', view: () => html`<${ Products } />` },
	'/categories': { title: 'Categories', view: () => html`<${ Categories } />` },
	'/orders': { title: 'Orders', view: () => html`<${ Orders } />` },
	'/hero': { title: 'Hero Slider', view: () => html`<${ Hero } />` },
	'/hot-deals': { title: 'Hot Deals', view: () => html`<${ HotDeals } />` },
	'/circles': { title: 'Category Circles', view: () => html`<${ Circles } />` },
	'/rows': { title: 'Product Rows', view: () => html`<${ Rows } />` },
	'/banners': { title: 'Banners', view: () => html`<${ Banners } />` },
	'/coupons': { title: 'Coupons', view: () => html`<${ Coupons } />` },
	'/menu': { title: 'Menu', view: () => html`<${ MenuScreen } />` },
	'/footer': { title: 'Footer', view: () => html`<${ FooterSettings } />` },
	'/reviews': { title: 'Reviews', view: () => html`<${ Reviews } />` },
	'/settings': { title: 'Settings', view: () => html`<${ Settings } />` },
	'/customers': { title: 'Customers', view: () => html`<${ Customers } />` },
	'/blocked': { title: 'Blocked List', view: () => html`<${ Blocked } />` },
	'/admins': { title: 'Manage Admins', view: () => html`<${ Admins } />` },
};

/**
 * Map a path to the screen that renders it and the nav item it belongs under.
 *
 * `navKey` is what the sidebar highlights, so /products/57 keeps "Products" lit rather than
 * leaving nothing selected while the owner edits.
 */
function resolve( path ) {
	if ( SIMPLE[ path ] ) {
		return { navKey: path, title: SIMPLE[ path ].title, view: SIMPLE[ path ].view() };
	}

	const product = path.match( /^\/products\/([A-Za-z0-9-]+)$/ );

	if ( product ) {
		return {
			navKey: '/products',
			title: product[ 1 ] === 'new' ? 'New product' : 'Edit product',
			view: html`<${ ProductEdit } id=${ product[ 1 ] } />`,
		};
	}

	// The invoice is matched before the plain order route, or /orders/57/invoice would fall
	// through to "not found" — the id pattern does not allow the trailing segment.
	const invoice = path.match( /^\/orders\/(\d+)\/invoice$/ );

	if ( invoice ) {
		return { navKey: '/orders', title: 'Invoice', view: html`<${ Invoice } id=${ invoice[ 1 ] } /> ` };
	}

	const order = path.match( /^\/orders\/(\d+)$/ );

	if ( order ) {
		return { navKey: '/orders', title: 'Order', view: html`<${ OrderDetail } id=${ order[ 1 ] } />` };
	}

	const item = findRoute( path );

	if ( item ) {
		return {
			navKey: item.path,
			title: item.label,
			view: html`
				<div class="sb-page">
					<h1 class="sb-page__title">${ item.label }</h1>
					<p class="sb-page__lead">Not built yet.</p>
					<${ Placeholder } title=${ item.label } phase=${ item.phase } />
				</div>
			`,
		};
	}

	return {
		navKey: null,
		title: 'Not found',
		view: html`
			<div class="sb-page">
				<h1 class="sb-page__title">Not found</h1>
				<p class="sb-page__lead">There is no screen at this address.</p>
			</div>
		`,
	};
}

function App() {
	const path = useRoute();
	const [ drawer, setDrawer ] = useState( false );

	const route = resolve( path );

	useEffect( () => {
		document.title = route.title + ' — ' + ( SB.store ? SB.store.name : 'Manage' );
	}, [ route.title ] );

	// Any navigation closes the drawer; leaving it open over the new screen on a phone hides it.
	useEffect( () => setDrawer( false ), [ path ] );

	return html`
		<div class=${ 'sb-shell' + ( drawer ? ' is-open' : '' ) }>
			<${ Rail } navKey=${ route.navKey } />

			<button class="sb-scrim" aria-label="Close menu" onClick=${ () => setDrawer( false ) }></button>

			<div class="sb-main">
				<header class="sb-topbar">
					<button
						class="sb-burger"
						aria-label="Open menu"
						aria-expanded=${ drawer }
						onClick=${ () => setDrawer( ! drawer ) }
					>
						<${ Icon } name="menu" size=${ 20 } />
					</button>
					<${ BackButton } path=${ path } />
					<span class="sb-topbar__spacer"></span>
					<${ UserChip } />
				</header>

				<main>${ route.view }</main>
			</div>

			<${ Toaster } />
		</div>
	`;
}

/**
 * One step back.
 *
 * It lives in the topbar rather than in each screen for two reasons: it is then in the same place on
 * all twenty screens, including the ones drawn by shared components like SettingsPage, and adding a
 * screen cannot forget it.
 *
 * It carries a real href to the path it would fall back to, so it can be middle-clicked and reads as
 * a link to assistive technology; the click handler prefers the browser's own history, which brings
 * the scroll position and the previous screen's state back with it.
 */
function BackButton( { path } ) {
	if ( ! canGoBack( path ) ) {
		return null;
	}

	return html`
		<a
			class="sb-backbtn"
			href=${ href( parentPath( path ) ) }
			onClick=${ ( event ) => {
				if ( event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0 ) {
					return;
				}

				event.preventDefault();
				goBack( path );
			} }
		>
			<${ Icon } name="back" size=${ 18 } />
			<span>Back</span>
		</a>
	`;
}

function Rail( { navKey } ) {
	return html`
		<nav class="sb-rail" aria-label="Store management">
			<div class="sb-rail__brand">
				<a href=${ href( '/' ) } onClick=${ ( e ) => onLinkClick( e, '/' ) }>
					${ SB.brandLogo
						? html`<img class="sb-brand__logo" src=${ SB.brandLogo } alt=${ SB.store.name } />`
						: html`<span class="sb-brand__name">${ SB.store ? SB.store.name : 'Manage' }</span>` }
				</a>
			</div>

			<div class="sb-rail__nav">
				${ NAV.map( ( group ) => {
					const items = group.items.filter( ( item ) => canAny( item.ability ) );

					if ( ! items.length ) {
						return null;
					}

					return html`
						<div class="sb-rail__group" key=${ group.legend }>
							<p class="sb-rail__legend">${ group.legend }</p>
							${ items.map(
								( item ) => html`
									<a
										key=${ item.path }
										class=${ 'sb-nav-item' + ( item.path === navKey ? ' is-active' : '' ) }
										href=${ href( item.path ) }
										aria-current=${ item.path === navKey ? 'page' : null }
										onClick=${ ( event ) => onLinkClick( event, item.path ) }
									>
										<span class="sb-nav-item__icon"><${ Icon } name=${ item.icon } /></span>
										<span>${ item.label }</span>
										${ item.phase > BUILT_THROUGH
											? html`<span class="sb-nav-item__soon">soon</span>`
											: null }
									</a>
								`
							) }
						</div>
					`;
				} ) }
			</div>

			<div class="sb-rail__foot">
				<p><a href=${ SB.store ? SB.store.url : '/' }>View store</a></p>
				<p><a href=${ SB.logoutUrl }>Sign out</a></p>
				<p>
					<a href=${ SB.environment ? SB.environment.wp_admin_url : '#' }>WordPress admin</a>
					— updates and payment settings only
				</p>
			</div>
		</nav>
	`;
}

function UserChip() {
	const user = SB.user || {};

	return html`
		<div class="sb-user">
			<span class="sb-user__meta">
				<span class="sb-user__name">${ user.display_name }</span><br />
				<span class="sb-user__role">${ user.role_label }</span>
			</span>
			${ user.avatar ? html`<img class="sb-user__avatar" src=${ user.avatar } alt="" />` : null }
		</div>
	`;
}

render( html`<${ App } />`, document.getElementById( 'sb-app' ) );
