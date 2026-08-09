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
export const BUILT_THROUGH = 9;

export const NAV = [
	{
		legend: 'Store',
		items: [
			// "Dashboard" here and "Dashboard" as the page's own heading. It read "Overview" in the
			// sidebar and "Dashboard" on the page, which is the kind of small disagreement that makes
			// an interface feel unfinished.
			{ path: '/', label: 'Dashboard', icon: 'grid', ability: 'dashboard.view', phase: 2 },
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
			/*
			 * First in the group, above Menu: the pages are the thing being linked to, and an owner
			 * looking for "where do I write About Us" should not have to read past four navigation
			 * and appearance screens to find it.
			 */
			{ path: '/content', label: 'Content Pages', icon: 'file', ability: 'content.manage', phase: 9 },
			{ path: '/menu', label: 'Menu', icon: 'menu', ability: 'appearance.manage', phase: 6 },
			{ path: '/footer', label: 'Footer', icon: 'footer', ability: 'appearance.manage', phase: 6 },
			{ path: '/reviews', label: 'Reviews', icon: 'star', ability: 'reviews.moderate', phase: 6 },
			// Two abilities, because the screen holds both halves: the palette is appearance, the
			// address and currency are the store's. Each card re-checks for itself.
			{ path: '/settings', label: 'Settings', icon: 'gear', ability: [ 'appearance.manage', 'store.manage' ], phase: 6 },
		],
	},
	{
		legend: 'People',
		items: [
			/*
			 * There is no Customers screen (removed 2026-08-09, owner's decision). It could only ever
			 * list registered accounts, and on a cash-on-delivery shop almost nobody registers — so it
			 * showed an empty table beside a sentence explaining that the real answer was on the
			 * Orders screen. Searching Orders by phone number is that answer, and it always was.
			 */
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

/**
 * Which group a nav path belongs to.
 *
 * The topbar's breadcrumb needs it. Read from NAV rather than stored a second time, so a screen
 * moved between groups moves in the breadcrumb too.
 *
 * @param {string} path Route path.
 * @return {string} Empty when the path is not a nav destination.
 */
export function findGroup( path ) {
	for ( const group of NAV ) {
		if ( group.items.some( ( item ) => item.path === path ) ) {
			return group.legend;
		}
	}

	return '';
}
