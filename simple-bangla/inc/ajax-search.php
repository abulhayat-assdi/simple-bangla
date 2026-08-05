<?php
/**
 * Live product search behind the header search field.
 *
 * The form itself is a plain GET search that works with JavaScript switched off. This endpoint
 * only enhances it: it answers with a small JSON payload the header script renders as a
 * suggestion list. Nothing here is required for the site to function.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** How many suggestions a single request may return. */
const SIMPLE_BANGLA_SEARCH_LIMIT = 6;

/**
 * Flatten WooCommerce's price markup into a plain string.
 *
 * The JSON payload is inserted with textContent, so entities have to be decoded here — an
 * undecoded `&#2547;` would literally render as those seven characters. The whitespace collapse
 * matters too: WooCommerce's currency symbol carries its own non-breaking space, and the
 * "left with space" position adds a second one.
 *
 * @param string $html Price markup from wc_price().
 * @return string
 */
function simple_bangla_price_to_text( $html ) {

	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );

	// \xC2\xA0 is a UTF-8 non-breaking space, which \s does not match without the /u flag.
	return trim( preg_replace( '/[\s\x{00A0}]+/u', ' ', $text ) );
}

/**
 * Answer a live-search request.
 *
 * Responds to both the logged-in and logged-out hooks: shoppers are usually anonymous, and
 * a store owner browsing their own site must get the same results.
 */
function simple_bangla_ajax_search() {

	check_ajax_referer( 'simple_bangla_search', 'nonce' );

	$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

	// Two characters is the point where results stop being the entire catalogue.
	if ( mb_strlen( $term ) < 2 ) {
		wp_send_json_success( array( 'results' => array() ) );
	}

	$post_type = post_type_exists( 'product' ) ? 'product' : 'post';

	$query = new WP_Query(
		array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			's'                      => $term,
			'posts_per_page'         => SIMPLE_BANGLA_SEARCH_LIMIT,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	$results = array();

	foreach ( $query->posts as $post ) {

		$result = array(
			'title' => wp_strip_all_tags( get_the_title( $post ) ),
			'url'   => get_permalink( $post ),
			'image' => get_the_post_thumbnail_url( $post, 'thumbnail' ),
			'price' => '',
		);

		if ( 'product' === $post_type && function_exists( 'wc_get_product' ) ) {

			$product = wc_get_product( $post );

			if ( $product ) {
				/*
				 * Not get_price_html(): that markup carries WooCommerce's screen-reader
				 * scaffolding ("Original price was: … Current price is: …"), which reads as
				 * gibberish once the tags are stripped for a one-line suggestion.
				 */
				$result['price'] = simple_bangla_price_to_text( wc_price( (float) $product->get_price() ) );
			}
		}

		$results[] = $result;
	}

	wp_send_json_success(
		array(
			'results' => $results,
			'allUrl'  => add_query_arg(
				array_filter(
					array(
						's'         => $term,
						'post_type' => 'product' === $post_type ? 'product' : '',
					)
				),
				home_url( '/' )
			),
		)
	);
}
add_action( 'wp_ajax_simple_bangla_search', 'simple_bangla_ajax_search' );
add_action( 'wp_ajax_nopriv_simple_bangla_search', 'simple_bangla_ajax_search' );
