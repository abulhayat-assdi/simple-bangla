<?php
/**
 * The cart and checkout are the classic forms, whatever the pages contain.
 *
 * The theme decided on 2026-08-06 that Cart and Checkout are WooCommerce's classic shortcodes and
 * not its block versions, because a classic theme cannot template a checkout that renders itself in
 * JavaScript from the Store API. Everything the owner asked for lives on that decision: the Bangla
 * copy, the delivery-charge radios moved up beside the address, the order summary with no delivery
 * row, the visually-hidden labels — plus the order block list, which hooks a validation action only
 * the classic checkout fires.
 *
 * **That decision was enforced in the demo importer, which was the wrong place for it.** The
 * importer swaps the two pages' content from blocks to shortcodes, and it only runs if somebody
 * clicks the button. WooCommerce 11 creates those pages holding the block versions, so on a store
 * where the importer had not been run — which is every store set up by hand, and any store where
 * WooCommerce later recreated a missing page — the checkout rendered as WooCommerce's default block
 * and every one of the things above was silently absent. Nothing failed. The checkout worked, in
 * English, at WooCommerce's own layout, with the block list not consulted.
 *
 * So the theme now answers for it directly, and the hard constraint that the theme runs on a stock
 * install with zero setup is true of the checkout as well.
 *
 * **Rendered as the shortcode, not rewritten in the database.** The pages keep whatever markup they
 * hold; only what a visitor is shown changes. Switch the theme away and WooCommerce's block
 * checkout comes back by itself, which is the same reversibility rule the rest of the theme is
 * built on — and it means this file and the importer's swap cannot disagree, because the swap is
 * now merely a tidier version of what would happen anyway.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * The block versions of the two pages, and the shortcode each is replaced by.
 *
 * The mini-cart and the individual inner blocks are deliberately absent. Only the two top-level
 * blocks that *are* a whole page are swapped; a shop that puts a cart block in a sidebar is doing
 * something this theme has no opinion about.
 *
 * @return array<string,string> Block name => shortcode tag.
 */
function simple_bangla_classic_page_blocks() {

	return array(
		'woocommerce/cart'     => 'woocommerce_cart',
		'woocommerce/checkout' => 'woocommerce_checkout',
	);
}

/**
 * Render the classic form in place of the block.
 *
 * `pre_render_block` rather than `render_block`, because returning a string here short-circuits the
 * block entirely. The checkout block carries some thirty inner blocks; with `render_block` every one
 * of them would be rendered in full and then thrown away.
 *
 * Front end only. In wp-admin the owner should see the block they actually have — an editor that
 * showed the shortcode's output instead would be describing a page that does not exist.
 *
 * @param string|null $pre_render   Pre-rendered content, or null to render normally.
 * @param array       $parsed_block The block being rendered.
 * @return string|null
 */
function simple_bangla_render_classic_block( $pre_render, $parsed_block ) {

	if ( null !== $pre_render || is_admin() ) {
		return $pre_render;
	}

	$name       = isset( $parsed_block['blockName'] ) ? (string) $parsed_block['blockName'] : '';
	$shortcodes = simple_bangla_classic_page_blocks();

	if ( ! isset( $shortcodes[ $name ] ) ) {
		return $pre_render;
	}

	return do_shortcode( '[' . $shortcodes[ $name ] . ']' );
}
add_filter( 'pre_render_block', 'simple_bangla_render_classic_block', 10, 2 );

/**
 * Keep WooCommerce's block-only assets off the two pages.
 *
 * With the block never rendered, its script and style are weight for markup that is not on the
 * page. WooCommerce enqueues them from the block type, so dequeuing on `wp_enqueue_scripts` at a
 * late priority is the point at which they are known to be registered.
 *
 * Guarded on the block genuinely having been replaced rather than on the page id, so a checkout page
 * the owner has already converted to the shortcode is not touched — there is nothing to remove there
 * and no reason to be running this at all.
 */
function simple_bangla_dequeue_block_checkout_assets() {

	if ( ! is_cart() && ! is_checkout() ) {
		return;
	}

	$post = get_post();

	if ( ! $post ) {
		return;
	}

	foreach ( array_keys( simple_bangla_classic_page_blocks() ) as $block ) {

		if ( ! has_block( $block, $post ) ) {
			continue;
		}

		$handle = str_replace( 'woocommerce/', 'wc-', $block ) . '-block-frontend';

		wp_dequeue_script( $handle );
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'simple_bangla_dequeue_block_checkout_assets', 99 );
