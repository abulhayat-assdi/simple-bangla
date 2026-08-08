<?php
/**
 * The numbers behind the dashboard.
 *
 * The dashboard is the screen the owner opens most, so it is the screen most able to make the
 * CMS feel slow. Two rules keep it fast.
 *
 * First, it is one request. A dashboard that fires six REST calls pays the cost of booting
 * WordPress six times; every figure on the screen is gathered here and returned together.
 *
 * Second, nothing is counted by loading orders. Revenue reads WooCommerce's own `wc_order_stats`
 * summary table and stock reads `wc_product_meta_lookup` — both are indexed tables WooCommerce
 * maintains for exactly this purpose. Summing `wc_get_orders()` would work today at a few
 * hundred orders and become unusable at fifty thousand.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/** How long a computed dashboard stays valid. */
const SIMPLE_BANGLA_CMS_STATS_TTL = 5 * MINUTE_IN_SECONDS;

/** Transient key for the cached dashboard. */
const SIMPLE_BANGLA_CMS_STATS_KEY = 'simple_bangla_cms_stats';

/**
 * The order statuses the CMS reports counts for, in the order the dashboard shows them.
 *
 * @return string[] Status slugs without the `wc-` prefix.
 */
function simple_bangla_cms_reported_statuses() {
	return array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' );
}

/**
 * The whole dashboard payload, cached.
 *
 * @param bool $force Recompute even if a cached copy exists.
 * @return array<string,mixed>
 */
function simple_bangla_cms_dashboard_stats( $force = false ) {

	if ( ! $force ) {
		$cached = get_transient( SIMPLE_BANGLA_CMS_STATS_KEY );

		if ( is_array( $cached ) ) {
			$cached['cached'] = true;
			return $cached;
		}
	}

	$stats = array(
		'generated_at' => current_time( 'c' ),
		'cached'       => false,
		'currency'     => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
		'orders'       => simple_bangla_cms_order_counts(),
		'revenue'      => simple_bangla_cms_revenue(),
		'catalog'      => simple_bangla_cms_catalog_counts(),
	);

	set_transient( SIMPLE_BANGLA_CMS_STATS_KEY, $stats, SIMPLE_BANGLA_CMS_STATS_TTL );

	return $stats;
}

/**
 * Drop the cached dashboard.
 *
 * Wired to order events rather than left to expire, so a new order shows up the moment the
 * owner refreshes instead of up to five minutes later. At this store's volume the recompute is
 * cheap; the five-minute TTL is the ceiling for a quiet store, not the normal path.
 */
function simple_bangla_cms_flush_stats() {
	delete_transient( SIMPLE_BANGLA_CMS_STATS_KEY );
}
add_action( 'woocommerce_new_order', 'simple_bangla_cms_flush_stats' );
add_action( 'woocommerce_order_status_changed', 'simple_bangla_cms_flush_stats' );
add_action( 'woocommerce_delete_order', 'simple_bangla_cms_flush_stats' );

/**
 * How many orders sit in each status.
 *
 * @return array{by_status:array<string,int>,total:int}
 */
function simple_bangla_cms_order_counts() {

	$counts = array();
	$total  = 0;

	foreach ( simple_bangla_cms_reported_statuses() as $status ) {

		// wc_orders_count() reads whichever storage WooCommerce is configured for, so this stays
		// correct under both HPOS and the legacy posts table.
		if ( function_exists( 'wc_orders_count' ) ) {
			$count = (int) wc_orders_count( $status );
		} else {
			$posts = wp_count_posts( 'shop_order' );
			$count = isset( $posts->{'wc-' . $status} ) ? (int) $posts->{'wc-' . $status} : 0;
		}

		$counts[ $status ] = $count;
		$total            += $count;
	}

	return array(
		'by_status' => $counts,
		'total'     => $total,
	);
}

/**
 * Gross sales, all time and over the last thirty days.
 *
 * @return array{total:float,last_30_days:float,source:string}
 */
function simple_bangla_cms_revenue() {

	global $wpdb;

	$table = $wpdb->prefix . 'wc_order_stats';

	if ( ! simple_bangla_cms_table_exists( $table ) ) {
		return array(
			'total'        => 0.0,
			'last_30_days' => 0.0,
			// The interface shows a hint rather than a wrong number when analytics is unavailable.
			'source'       => 'unavailable',
		);
	}

	$paid = function_exists( 'wc_get_is_paid_statuses' ) ? wc_get_is_paid_statuses() : array( 'processing', 'completed' );
	$paid = array_map(
		static function ( $status ) {
			return 'wc-' . $status;
		},
		$paid
	);

	$placeholders = implode( ',', array_fill( 0, count( $paid ), '%s' ) );

	// Direct query against WooCommerce's own analytics summary table: it is indexed on
	// date_created and status, which is the whole reason it exists. Result is cached by the
	// caller, so this runs at most once every five minutes.
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$total = (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE( SUM( total_sales ), 0 ) FROM {$table} WHERE status IN ( {$placeholders} )",
			$paid
		)
	);

	$since = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );

	$recent = (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE( SUM( total_sales ), 0 ) FROM {$table} WHERE status IN ( {$placeholders} ) AND date_created_gmt >= %s",
			array_merge( $paid, array( $since ) )
		)
	);
	// phpcs:enable

	return array(
		'total'        => round( $total, 2 ),
		'last_30_days' => round( $recent, 2 ),
		'source'       => 'order_stats',
	);
}

/**
 * Product, category and stock figures.
 *
 * @return array{products:int,categories:int,out_of_stock:int,low_stock:int}
 */
function simple_bangla_cms_catalog_counts() {

	global $wpdb;

	$products = wp_count_posts( 'product' );
	$products = isset( $products->publish ) ? (int) $products->publish : 0;

	$categories = taxonomy_exists( 'product_cat' )
		? (int) wp_count_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		)
		: 0;

	$out_of_stock = 0;
	$low_stock    = 0;

	$lookup = $wpdb->prefix . 'wc_product_meta_lookup';

	if ( simple_bangla_cms_table_exists( $lookup ) ) {

		$threshold = function_exists( 'get_option' ) ? (int) get_option( 'woocommerce_notify_low_stock_amount', 2 ) : 2;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$out_of_stock = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$lookup} WHERE stock_status = 'outofstock'"
		);

		// Only products that actually manage stock have a quantity; the rest are NULL and are
		// correctly excluded rather than counted as zero.
		$low_stock = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$lookup} WHERE stock_quantity IS NOT NULL AND stock_quantity <= %d AND stock_status != 'outofstock'",
				$threshold
			)
		);
		// phpcs:enable
	}

	return array(
		'products'     => $products,
		'categories'   => $categories,
		'out_of_stock' => $out_of_stock,
		'low_stock'    => $low_stock,
	);
}

/**
 * Whether a database table is present.
 *
 * WooCommerce Analytics can be disabled, and a store that has never run its table creation will
 * not have `wc_order_stats`. Querying a missing table throws a database error into the log on
 * every dashboard load, so it is checked once and cached for the request.
 *
 * @param string $table Fully-prefixed table name.
 * @return bool
 */
function simple_bangla_cms_table_exists( $table ) {

	static $known = array();

	if ( isset( $known[ $table ] ) ) {
		return $known[ $table ];
	}

	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	// phpcs:enable

	$known[ $table ] = ( $found === $table );

	return $known[ $table ];
}
