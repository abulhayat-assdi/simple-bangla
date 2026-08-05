<?php
/**
 * Shop filtering and incremental loading.
 *
 * The filters are ordinary GET parameters on the archive URL, applied server-side in
 * pre_get_posts. That means the filter form works with JavaScript switched off, the filtered
 * view is linkable and bookmarkable, and there is exactly one place where a filter turns into
 * a query.
 *
 * assets/js/shop.js then re-fetches that same URL with `sb_ajax=1`, which short-circuits the
 * response to just the results fragment. No second query builder, so the two paths cannot
 * drift apart.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read the active filters out of the request.
 *
 * @return array{sale:bool,min:int,max:int}
 */
function simple_bangla_active_filters() {

	// Reading public, read-only query vars off a GET archive URL. There is no state change
	// here to protect, so this is not a nonce surface.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	return array(
		'sale' => ! empty( $_GET['sb_sale'] ),
		'min'  => isset( $_GET['sb_min'] ) ? absint( wp_unslash( $_GET['sb_min'] ) ) : 0,
		'max'  => isset( $_GET['sb_max'] ) ? absint( wp_unslash( $_GET['sb_max'] ) ) : 0,
	);
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}

/**
 * Is any filter currently narrowing the archive?
 *
 * @return bool
 */
function simple_bangla_has_active_filters() {

	$filters = simple_bangla_active_filters();

	return $filters['sale'] || $filters['min'] || $filters['max'];
}

/**
 * Apply the theme's filters to the main product query.
 *
 * @param WP_Query $query The query being prepared.
 */
function simple_bangla_filter_query( $query ) {

	if ( is_admin() || ! $query->is_main_query() || ! simple_bangla_is_product_listing() ) {
		return;
	}

	$filters = simple_bangla_active_filters();

	if ( $filters['sale'] ) {

		$on_sale = wc_get_product_ids_on_sale();

		// An empty post__in is ignored by WP_Query, which would show the whole catalogue —
		// the opposite of "only discounted". A sentinel ID keeps the result set empty.
		$query->set( 'post__in', $on_sale ? $on_sale : array( 0 ) );
	}

	if ( $filters['min'] || $filters['max'] ) {

		$meta_query = (array) $query->get( 'meta_query' );

		$range = array( 'key' => '_price' );

		if ( $filters['min'] && $filters['max'] ) {
			$range['value']   = array( $filters['min'], $filters['max'] );
			$range['compare'] = 'BETWEEN';
			$range['type']    = 'NUMERIC';
		} elseif ( $filters['min'] ) {
			$range['value']   = $filters['min'];
			$range['compare'] = '>=';
			$range['type']    = 'NUMERIC';
		} else {
			$range['value']   = $filters['max'];
			$range['compare'] = '<=';
			$range['type']    = 'NUMERIC';
		}

		$meta_query[] = $range;
		$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
}
add_action( 'pre_get_posts', 'simple_bangla_filter_query' );

/**
 * Answer `sb_ajax=1` with only the results fragment.
 *
 * Everything served here is content the same URL would already render publicly; the parameter
 * changes how much of the page is returned, not what the visitor is allowed to see.
 */
function simple_bangla_shop_fragment() {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only public view.
	if ( empty( $_GET['sb_ajax'] ) || ! simple_bangla_is_product_listing() ) {
		return;
	}

	// Tell caches this varies from the full page at the same path.
	nocache_headers();
	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

	get_template_part( 'template-parts/shop/results' );

	exit;
}
add_action( 'template_redirect', 'simple_bangla_shop_fragment' );

/**
 * The cheapest and dearest published product prices, for the price-range inputs.
 *
 * @return array{min:int,max:int}
 */
function simple_bangla_price_bounds() {

	$cached = get_transient( 'simple_bangla_price_bounds' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	global $wpdb;

	// A direct query because there is no WooCommerce API for "cheapest and dearest in one
	// round trip", and running two meta_key-ordered WP_Query calls costs more than this does.
	$row = $wpdb->get_row(
		"SELECT MIN(CAST(meta_value AS DECIMAL(12,2))) AS min_price,
		        MAX(CAST(meta_value AS DECIMAL(12,2))) AS max_price
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = '_price'
		   AND pm.meta_value != ''
		   AND p.post_type = 'product'
		   AND p.post_status = 'publish'"
	);

	$bounds = array(
		'min' => $row ? (int) floor( (float) $row->min_price ) : 0,
		'max' => $row ? (int) ceil( (float) $row->max_price ) : 0,
	);

	set_transient( 'simple_bangla_price_bounds', $bounds, HOUR_IN_SECONDS );

	return $bounds;
}

/**
 * Drop the cached price bounds whenever a product's price could have moved.
 *
 * @param int $post_id Saved post ID.
 */
function simple_bangla_flush_price_bounds( $post_id ) {

	if ( 'product' === get_post_type( $post_id ) ) {
		delete_transient( 'simple_bangla_price_bounds' );
	}
}
add_action( 'save_post', 'simple_bangla_flush_price_bounds' );
add_action( 'deleted_post', 'simple_bangla_flush_price_bounds' );
