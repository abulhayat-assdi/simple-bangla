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

/**
 * The footer's link columns.
 *
 * Defined here rather than in the template because two things now need to agree about them: the
 * template that prints the columns, and `simple_bangla_footer_pages()` below, which answers the
 * CMS's question "which pages does the footer link to?". Two copies of this list would drift the
 * first time a column changed, and the CMS would quietly offer the wrong pages to edit.
 *
 * The headings are read at call time because one of them is the site's own name.
 *
 * @return array<string,array{heading:string,fallback:string[]}> Keyed by nav-menu location.
 */
function simple_bangla_footer_columns() {

	/*
	 * The fallback lists name every page simple_bangla_default_pages() creates, and that is not a
	 * coincidence to be broken casually: a default page missing from both lists is created on
	 * activation, linked from nowhere, and reachable only by typing its address. `contact-us` and
	 * `terms-and-condition` were exactly that until 2026-08-12 — Contact Us, on a shop, invisible.
	 *
	 * These lists are also what the demo importer builds its footer menus from, so the two are kept
	 * in step by hand; see simple_bangla_demo_footer_menus() in inc/demo-content.php.
	 */
	return array(
		'footer-1' => array(
			'heading'  => get_bloginfo( 'name' ),
			'fallback' => array( 'about-us', 'privacy-policy', 'refund_returns', 'terms-and-condition' ),
		),
		'footer-2' => array(
			'heading'  => __( 'Helps', 'simple-bangla' ),
			'fallback' => array( 'contact-us', 'tutorials', 'warranty-policy', 'special-deals' ),
		),
	);
}

/**
 * Which of a column's three possible sources is actually in charge.
 *
 * The order is the interesting part and it is stated once, here: a tick beats an assigned menu,
 * an assigned menu beats the theme's own defaults. See the file header for why a tick wins.
 *
 * @param string $location Nav-menu location.
 * @return string `ticked`, `menu` or `fallback`.
 */
function simple_bangla_footer_column_source( $location ) {

	if ( 'footer-1' === $location && simple_bangla_footer_link_pages() ) {
		return 'ticked';
	}

	if ( has_nav_menu( $location ) ) {
		return 'menu';
	}

	return 'fallback';
}

/**
 * The published pages one footer column links to.
 *
 * A menu can hold category links and custom URLs as well as pages; only the pages come back, because
 * the only caller is a screen for editing page *content* and there is nothing to edit about a link
 * to a category archive. Top-level items only, matching the `depth => 1` the template renders with.
 *
 * @param string $location Nav-menu location.
 * @param array  $column   Entry from simple_bangla_footer_columns().
 * @return WP_Post[]
 */
function simple_bangla_footer_column_pages( $location, $column ) {

	$source = simple_bangla_footer_column_source( $location );

	if ( 'ticked' === $source ) {
		return simple_bangla_footer_link_pages();
	}

	$pages = array();

	if ( 'menu' === $source ) {

		$locations = get_nav_menu_locations();
		$items     = isset( $locations[ $location ] ) ? wp_get_nav_menu_items( $locations[ $location ] ) : array();

		foreach ( (array) $items as $item ) {

			if ( 'post_type' !== $item->type || 'page' !== $item->object || (int) $item->menu_item_parent ) {
				continue;
			}

			$page = get_post( (int) $item->object_id );

			/*
			 * Any status, unlike the two branches below.
			 *
			 * A menu prints its item whether or not the page behind it is published, so the footer
			 * genuinely does link to a draft page that has been added to a footer menu — the theme's
			 * own Refund and Returns Policy is exactly that on a fresh install. Filtering drafts out
			 * here would hide the one page most in need of writing from the screen for writing pages.
			 * The CMS shows the status beside each row, so a draft is visible as a draft.
			 */
			if ( $page ) {
				$pages[] = $page;
			}
		}

		return $pages;
	}

	foreach ( $column['fallback'] as $slug ) {

		$page = get_page_by_path( $slug );

		if ( $page && 'publish' === $page->post_status ) {
			$pages[] = $page;
		}
	}

	return $pages;
}

/**
 * Every page the footer links to, in the order it prints them.
 *
 * This is what the CMS's Content Pages screen lists. The owner asked for that screen to hold the
 * pages they actually maintain rather than every page WordPress happens to contain — Cart, Checkout,
 * My Account and Sample Page are not writing, and offering them for editing was an invitation to
 * break the shop.
 *
 * Deduplicated by ID: the same page can legitimately be linked from both columns, and it should be
 * one row on that screen rather than two.
 *
 * @return WP_Post[]
 */
function simple_bangla_footer_pages() {

	$pages = array();

	foreach ( simple_bangla_footer_columns() as $location => $column ) {
		foreach ( simple_bangla_footer_column_pages( $location, $column ) as $page ) {
			$pages[ $page->ID ] = $page;
		}
	}

	return array_values( $pages );
}
