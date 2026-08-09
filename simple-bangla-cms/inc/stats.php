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
 * Bumped whenever an order changes, and mixed into every cache key.
 *
 * The dashboard is cached per period now, so there is no longer a single transient to delete — and
 * enumerating the keys to delete them would mean keeping a list of every period anyone has ever
 * asked for. A version in the key retires the whole set at once; the abandoned entries expire on
 * their own five-minute TTL.
 */
const SIMPLE_BANGLA_CMS_STATS_VERSION = 'simple_bangla_cms_stats_version';

/**
 * Resolve a period key into the range it means.
 *
 * Accepted: `all`, `30d`, `90d`, `12m`, or a single month as `YYYY-MM`. Anything else falls back to
 * `30d` rather than erroring — a dashboard is not worth a 400, and the response says which period it
 * actually used so the interface can correct itself.
 *
 * Bounds are GMT, because both tables queried below store GMT. `to` is exclusive.
 *
 * @param string $period Period key.
 * @return array{key:string,label:string,from:?string,to:?string}
 */
function simple_bangla_cms_resolve_period( $period ) {

	$period = is_string( $period ) ? trim( $period ) : '';
	$now    = time();

	if ( 'all' === $period ) {
		return array(
			'key'   => 'all',
			'label' => __( 'All time', 'simple-bangla-cms' ),
			'from'  => null,
			'to'    => null,
		);
	}

	if ( preg_match( '/^(\d{4})-(\d{2})$/', $period, $found ) ) {

		$year  = (int) $found[1];
		$month = (int) $found[2];

		if ( $year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12 ) {

			// Built from the site's own timezone and converted, so "August" means the shop's August
			// rather than UTC's — six hours of orders would otherwise fall into the wrong month.
			$start = simple_bangla_cms_local_to_gmt( sprintf( '%04d-%02d-01 00:00:00', $year, $month ) );
			$end   = simple_bangla_cms_local_to_gmt(
				gmdate( 'Y-m-d 00:00:00', strtotime( sprintf( '%04d-%02d-01 +1 month', $year, $month ) ) )
			);

			return array(
				'key'   => $period,
				'label' => date_i18n( 'F Y', strtotime( sprintf( '%04d-%02d-01', $year, $month ) ) ),
				'from'  => $start,
				'to'    => $end,
			);
		}
	}

	$spans = array(
		'30d' => array( 30 * DAY_IN_SECONDS, __( 'Last 30 days', 'simple-bangla-cms' ) ),
		'90d' => array( 90 * DAY_IN_SECONDS, __( 'Last 90 days', 'simple-bangla-cms' ) ),
		'12m' => array( 365 * DAY_IN_SECONDS, __( 'Last 12 months', 'simple-bangla-cms' ) ),
	);

	$key = isset( $spans[ $period ] ) ? $period : '30d';

	return array(
		'key'   => $key,
		'label' => $spans[ $key ][1],
		'from'  => gmdate( 'Y-m-d H:i:s', $now - $spans[ $key ][0] ),
		'to'    => null,
	);
}

/**
 * A local wall-clock time as GMT, for the SQL bounds.
 *
 * @param string $local `Y-m-d H:i:s` in the site's timezone.
 * @return string Same instant in GMT.
 */
function simple_bangla_cms_local_to_gmt( $local ) {

	if ( function_exists( 'get_gmt_from_date' ) ) {
		return get_gmt_from_date( $local );
	}

	return $local;
}

/**
 * The order statuses the CMS reports counts for, in the order the dashboard shows them.
 *
 * @return string[] Status slugs without the `wc-` prefix.
 */
function simple_bangla_cms_reported_statuses() {
	// The theme's two extra stages are in the middle, in the order a parcel travels. Without
	// `sb-courier` here the dashboard's own figures would stop agreeing with the Orders screen the
	// moment the first parcel was dispatched.
	return array( 'pending', 'processing', 'on-hold', 'sb-courier', 'completed', 'sb-returned', 'cancelled', 'refunded', 'failed' );
}

/**
 * The whole dashboard payload, cached.
 *
 * @param bool $force Recompute even if a cached copy exists.
 * @return array<string,mixed>
 */
function simple_bangla_cms_dashboard_stats( $force = false, $period = '30d' ) {

	$range = simple_bangla_cms_resolve_period( $period );
	$key   = SIMPLE_BANGLA_CMS_STATS_KEY . '_' . (int) get_option( SIMPLE_BANGLA_CMS_STATS_VERSION, 1 ) . '_' . md5( $range['key'] );

	if ( ! $force ) {
		$cached = get_transient( $key );

		if ( is_array( $cached ) ) {
			$cached['cached'] = true;
			return $cached;
		}
	}

	$stats = array(
		'generated_at' => current_time( 'c' ),
		'cached'       => false,
		'currency'     => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
		'period'       => $range,
		'orders'       => simple_bangla_cms_order_counts( $range ),
		'revenue'      => simple_bangla_cms_revenue( $range ),
		// Deliberately outside the period. Stock and the size of the catalogue are facts about now,
		// not about a month — "how many products did I have in March" is a question nobody asks, and
		// answering it from today's data while calling it March would be a lie.
		'catalog'      => simple_bangla_cms_catalog_counts(),
	);

	set_transient( $key, $stats, SIMPLE_BANGLA_CMS_STATS_TTL );

	return $stats;
}

/**
 * Retire every cached dashboard.
 *
 * Wired to order events rather than left to expire, so a new order shows up the moment the
 * owner refreshes instead of up to five minutes later. At this store's volume the recompute is
 * cheap; the five-minute TTL is the ceiling for a quiet store, not the normal path.
 */
function simple_bangla_cms_flush_stats() {
	update_option( SIMPLE_BANGLA_CMS_STATS_VERSION, (int) get_option( SIMPLE_BANGLA_CMS_STATS_VERSION, 1 ) + 1, false );
}
add_action( 'woocommerce_new_order', 'simple_bangla_cms_flush_stats' );
add_action( 'woocommerce_order_status_changed', 'simple_bangla_cms_flush_stats' );
add_action( 'woocommerce_delete_order', 'simple_bangla_cms_flush_stats' );

/**
 * How many orders sit in each status.
 *
 * Two paths, and the split is worth the code. With no date bounds WooCommerce's own
 * `wc_orders_count()` is both correct and free — it reads a cached count and works under either
 * storage backend. Once a period is chosen there is no core function that answers "orders per
 * status between these dates", so it is one grouped query against whichever table holds the orders.
 *
 * The query counts, it does not load. A month of orders instantiated as WC_Order objects would be
 * the one thing this file exists to avoid.
 *
 * @param array|null $range From simple_bangla_cms_resolve_period(); null means all time.
 * @return array{by_status:array<string,int>,total:int}
 */
function simple_bangla_cms_order_counts( $range = null ) {

	$wanted = simple_bangla_cms_reported_statuses();
	$counts = array_fill_keys( $wanted, 0 );

	$bounded = $range && ( $range['from'] || $range['to'] );

	if ( ! $bounded ) {

		foreach ( $wanted as $status ) {

			if ( function_exists( 'wc_orders_count' ) ) {
				$counts[ $status ] = (int) wc_orders_count( $status );
			} else {
				$posts             = wp_count_posts( 'shop_order' );
				$counts[ $status ] = isset( $posts->{'wc-' . $status} ) ? (int) $posts->{'wc-' . $status} : 0;
			}
		}

		return array(
			'by_status' => $counts,
			'total'     => array_sum( $counts ),
		);
	}

	global $wpdb;

	$hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
		&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

	if ( $hpos ) {
		$table  = $wpdb->prefix . 'wc_orders';
		$status = 'status';
		$date   = 'date_created_gmt';
		$where  = "type = 'shop_order'";
	} else {
		$table  = $wpdb->posts;
		$status = 'post_status';
		$date   = 'post_date_gmt';
		$where  = "post_type = 'shop_order'";
	}

	$clauses = array( $where );
	$params  = array();

	if ( $range['from'] ) {
		$clauses[] = "{$date} >= %s";
		$params[]  = $range['from'];
	}

	if ( $range['to'] ) {
		// Exclusive, so a month's last second cannot also be the next month's first.
		$clauses[] = "{$date} < %s";
		$params[]  = $range['to'];
	}

	$sql = "SELECT {$status} AS s, COUNT(*) AS n FROM {$table} WHERE " . implode( ' AND ', $clauses ) . " GROUP BY {$status}";

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rows = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) ) : $wpdb->get_results( $sql );
	// phpcs:enable

	foreach ( (array) $rows as $row ) {

		// Both backends store the `wc-` prefix; the CMS speaks in bare slugs.
		$slug = preg_replace( '/^wc-/', '', (string) $row->s );

		if ( isset( $counts[ $slug ] ) ) {
			$counts[ $slug ] = (int) $row->n;
		}
	}

	return array(
		'by_status' => $counts,
		'total'     => array_sum( $counts ),
	);
}

/**
 * Gross sales in the chosen period, and all time beside it.
 *
 * Two figures rather than one, because "৳48,000 this month" only means something next to what the
 * shop has taken altogether. `total` therefore ignores the period on purpose.
 *
 * @param array|null $range From simple_bangla_cms_resolve_period().
 * @return array{total:float,period:float,source:string}
 */
function simple_bangla_cms_revenue( $range = null ) {

	global $wpdb;

	$table = $wpdb->prefix . 'wc_order_stats';

	if ( ! simple_bangla_cms_table_exists( $table ) ) {
		return array(
			'total'  => 0.0,
			'period' => 0.0,
			// The interface shows a hint rather than a wrong number when analytics is unavailable.
			'source' => 'unavailable',
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

	$clauses = array( "status IN ( {$placeholders} )" );
	$params  = $paid;

	if ( $range && $range['from'] ) {
		$clauses[] = 'date_created_gmt >= %s';
		$params[]  = $range['from'];
	}

	if ( $range && $range['to'] ) {
		$clauses[] = 'date_created_gmt < %s';
		$params[]  = $range['to'];
	}

	$period = (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE( SUM( total_sales ), 0 ) FROM {$table} WHERE " . implode( ' AND ', $clauses ),
			$params
		)
	);
	// phpcs:enable

	return array(
		'total'  => round( $total, 2 ),
		'period' => round( $period, 2 ),
		'source' => 'order_stats',
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
