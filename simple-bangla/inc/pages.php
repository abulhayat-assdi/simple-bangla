<?php
/**
 * Static pages, and which of them the footer links to.
 *
 * The store owner writes About Us, Privacy Policy, Delivery & Return and anything else from the
 * CMS's Content Pages screen, and ticks a box there to put a page in the footer. This file owns
 * the half of that which the storefront needs: the meta key the tick is stored in, and the query
 * that turns it into a list of links.
 *
 * **It lives in the theme, not in the CMS plugin, for the same reason the block list and the order
 * statuses do.** The footer is rendered by the theme, and the project's standing rule is that
 * deactivating the management interface changes nothing a customer sees. A footer whose links came
 * from the plugin would lose them the moment the plugin was switched off. The plugin only exposes
 * this meta key over REST so the CMS can write it — see simple-bangla-cms/inc/rest-pages.php.
 *
 * **Ticked pages take over the first footer column, ahead of any menu assigned to it.** That
 * ordering is deliberate and it is the only surprising thing here. `footer-1` may have a nav menu
 * assigned — the demo importer assigns one — and if the menu won, the tick box would be a control
 * that silently did nothing on exactly the installs that have been set up properly, which is the
 * one failure this project keeps refusing to ship. So: tick nothing and the column behaves exactly
 * as it always has (assigned menu, else the default pages). Tick anything and the ticks are the
 * column. Columns two and three are untouched and stay menu-driven.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where a page's "show this in the footer" tick is stored.
 *
 * Underscore-prefixed, so WordPress treats it as protected and nothing can write it over REST
 * without an explicit auth callback. The CMS plugin registers exactly that, gated on the same
 * capability as its Content Pages screen.
 */
const SIMPLE_BANGLA_FOOTER_LINK_META = '_simple_bangla_footer_link';

/**
 * The published pages ticked for the footer.
 *
 * Ordered by menu order and then title, which is the order the CMS lists them in — so what the
 * owner sees on that screen is the order the footer prints.
 *
 * Capped at twenty. A footer column with more links than that is a mistake being made, not a
 * requirement being met, and an unbounded query here would let one bad tick run the query on every
 * page load of the site.
 *
 * @return WP_Post[]
 */
function simple_bangla_footer_link_pages() {

	static $pages = null;

	// The footer renders once per request, but the CMS's own sanity checks and any future caller
	// should not each pay for the query.
	if ( null !== $pages ) {
		return $pages;
	}

	$pages = get_posts(
		array(
			'post_type'        => 'page',
			'post_status'      => 'publish',
			'numberposts'      => 20,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			// A boolean meta written over REST stores '1' for true and an empty string for false,
			// so an unticked page keeps a row that must not match.
			'meta_key'         => SIMPLE_BANGLA_FOOTER_LINK_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'       => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'suppress_filters' => false,
		)
	);

	return $pages;
}

/**
 * Whether a page is ticked for the footer.
 *
 * @param int $page_id Page ID.
 * @return bool
 */
function simple_bangla_page_in_footer( $page_id ) {
	return '1' === (string) get_post_meta( (int) $page_id, SIMPLE_BANGLA_FOOTER_LINK_META, true );
}
