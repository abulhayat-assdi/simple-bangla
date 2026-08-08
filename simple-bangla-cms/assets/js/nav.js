/*
 * The sidebar.
 *
 * Every screen the CMS will ever have is listed from day one, with the phase that delivers it.
 * Hiding the unbuilt ones would make the interface look finished while quietly owing most of
 * itself; showing them with a "soon" marker keeps the remaining work visible to the owner and
 * gives the whole thing a shape to navigate.
 *
 * `ability` is what the sidebar filters on. It is the same ability the endpoint checks, so a
 * staff account never sees a link that would only answer 403.
 */

/** Phases at or below this are actually built. */
export const BUILT_THROUGH = 4;

export const NAV = [
	{
		legend: 'Store',
		items: [
			{ path: '/', label: 'Overview', icon: 'grid', ability: 'dashboard.view', phase: 2 },
			{ path: '/orders', label: 'Orders', icon: 'bag', ability: 'orders.view', phase: 4 },
			{ path: '/products', label: 'Products', icon: 'box', ability: 'products.view', phase: 3 },
			{ path: '/categories', label: 'Categories', icon: 'layers', ability: 'products.view', phase: 3 },
			// Coupons ship with the settings work in phase 6, not with orders — the badge has to
			// say so or "soon" would disappear from a screen that is still a placeholder.
			{ path: '/coupons', label: 'Coupons', icon: 'tag', ability: 'store.manage', phase: 6 },
		],
	},
	{
		legend: 'Homepage',
		items: [
			{ path: '/hero', label: 'Hero Slider', icon: 'image', ability: 'appearance.manage', phase: 5 },
			{ path: '/hot-deals', label: 'Hot Deals', icon: 'flame', ability: 'appearance.manage', phase: 5 },
			{ path: '/circles', label: 'Category Circles', icon: 'circle', ability: 'appearance.manage', phase: 5 },
			{ path: '/rows', label: 'Product Rows', icon: 'rows', ability: 'appearance.manage', phase: 5 },
			{ path: '/banners', label: 'Banners', icon: 'banner', ability: 'appearance.manage', phase: 5 },
		],
	},
	{
		legend: 'Site',
		items: [
			{ path: '/menu', label: 'Menu', icon: 'menu', ability: 'appearance.manage', phase: 6 },
			{ path: '/footer', label: 'Footer', icon: 'footer', ability: 'appearance.manage', phase: 6 },
			{ path: '/reviews', label: 'Reviews', icon: 'star', ability: 'products.view', phase: 6 },
			{ path: '/settings', label: 'Settings', icon: 'gear', ability: 'store.manage', phase: 6 },
		],
	},
	{
		legend: 'People',
		items: [
			{ path: '/customers', label: 'Customers', icon: 'users', ability: 'customers.view', phase: 7 },
			{ path: '/blocked', label: 'Blocked List', icon: 'ban', ability: 'store.manage', phase: 7 },
			{ path: '/admins', label: 'Manage Admins', icon: 'shield', ability: 'staff.manage', phase: 7 },
		],
	},
];

/**
 * Find the nav entry for a path.
 *
 * @param {string} path Route path, e.g. "/orders".
 * @return {object|null}
 */
export function findRoute( path ) {
	for ( const group of NAV ) {
		for ( const item of group.items ) {
			if ( item.path === path ) {
				return item;
			}
		}
	}

	return null;
}
