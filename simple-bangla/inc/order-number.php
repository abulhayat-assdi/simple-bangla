<?php
/**
 * Unique alphanumeric order numbers.
 *
 * Out of the box an order is numbered with its own database ID — `#234`, then `#235`, then `#236`.
 * That is unique, but it is also a running count of everything the shop has ever sold, printed on
 * every invoice and read out over the phone. The owner asked (2026-08-12) for a number that mixes
 * letters and digits instead.
 *
 * **In the theme, not the CMS plugin**, for the same reason as the block list and the two extra
 * order stages: the number is customer-visible — it is in the confirmation email, on the thank-you
 * page and in My Account — so with the management interface switched off, an order must still
 * answer to the number its customer was given.
 *
 * ### Uniqueness is by construction, not by luck
 *
 * The number is `<3 random> <the order ID, re-spelled>`. The tail is the order's own ID written in
 * a 31-character alphabet, so two orders can no more share a number than they can share an ID —
 * there is no "generate, check the database, hope nobody else was mid-checkout" race to lose. The
 * three leading characters are random so that consecutive orders do not read as consecutive, which
 * is the whole point of the change.
 *
 * The alphabet drops `0`, `O`, `1`, `I` and `L`, because this number is read down a phone line and
 * written on a parcel. The first character is always a letter, which means a generated number can
 * never be all digits and therefore can never collide with the plain numeric number an order placed
 * before this file existed still carries.
 *
 * ### Older orders keep their old number, deliberately
 *
 * Nothing is back-filled. An order whose customer already holds an email saying `#234` must not
 * quietly become `K7M229K` on the invoice — the two would then disagree with no way to tell which
 * was real. Only orders created from now on get a generated number, and because those always start
 * with a letter the two kinds can never clash.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** Order meta key holding the generated number. */
const SIMPLE_BANGLA_ORDER_NUMBER_META = '_sb_order_number';

/**
 * The characters a number is spelled with.
 *
 * `0`/`O`, `1`/`I` and `L` are absent: every one of them is a pair of characters that look alike in
 * most fonts, and this string gets copied off a screen onto a courier's label by hand.
 */
const SIMPLE_BANGLA_ORDER_NUMBER_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

/** The subset of the alphabet that is unambiguously a letter. */
const SIMPLE_BANGLA_ORDER_NUMBER_LETTERS = 'ABCDEFGHJKMNPQRSTUVWXYZ';

/**
 * Re-spell an integer in the order-number alphabet.
 *
 * Padded so that early orders are not visibly early — order 5 and order 5,000 produce a string of
 * the same length until the shop genuinely outgrows it.
 *
 * @param int $value      Non-negative integer, normally an order ID.
 * @param int $min_length Pad to at least this many characters.
 * @return string
 */
function simple_bangla_encode_order_id( $value, $min_length = 4 ) {

	$alphabet = SIMPLE_BANGLA_ORDER_NUMBER_ALPHABET;
	$base     = strlen( $alphabet );
	$value    = max( 0, (int) $value );
	$out      = '';

	do {
		$out   = $alphabet[ $value % $base ] . $out;
		$value = intdiv( $value, $base );
	} while ( $value > 0 );

	return str_pad( $out, max( 1, (int) $min_length ), $alphabet[0], STR_PAD_LEFT );
}

/**
 * The random head of a number.
 *
 * `wp_rand()` rather than `rand()`: it seeds from a cryptographic source where one is available, and
 * a predictable head would make the whole number as guessable as the ID it hides.
 *
 * @param int $length How many characters. The first is always a letter.
 * @return string
 */
function simple_bangla_order_number_head( $length = 3 ) {

	$letters = SIMPLE_BANGLA_ORDER_NUMBER_LETTERS;
	$out     = $letters[ wp_rand( 0, strlen( $letters ) - 1 ) ];

	for ( $i = 1; $i < (int) $length; $i++ ) {
		$out .= SIMPLE_BANGLA_ORDER_NUMBER_ALPHABET[ wp_rand( 0, strlen( SIMPLE_BANGLA_ORDER_NUMBER_ALPHABET ) - 1 ) ];
	}

	return $out;
}

/**
 * Build the number for one order.
 *
 * The prefix is empty by default because every template that prints a number already prints a `#`
 * in front of it, and `#SB-K7M229K` says "number" twice. The filter is here for a shop that wants
 * its parcels marked with something recognisable at a courier's depot.
 *
 * @param int $order_id Order ID.
 * @return string
 */
function simple_bangla_generate_order_number( $order_id ) {

	/**
	 * Filter the prefix put in front of every generated order number.
	 *
	 * @param string $prefix Default empty.
	 */
	$prefix = (string) apply_filters( 'simple_bangla_order_number_prefix', '' );

	return $prefix . simple_bangla_order_number_head() . simple_bangla_encode_order_id( $order_id );
}

/**
 * Give a newly created order its number.
 *
 * `woocommerce_new_order` is the one hook every creation path goes through — the checkout, the REST
 * API, the CMS, wp-admin and the demo importer alike — so there is no route by which an order can
 * come into being without one.
 *
 * The number is written once and never rewritten. A number that changed after the fact would
 * contradict the email the customer is holding.
 *
 * @param int           $order_id Order ID.
 * @param WC_Order|null $order    The order, on WooCommerce versions that pass it.
 */
function simple_bangla_assign_order_number( $order_id, $order = null ) {

	if ( ! $order instanceof WC_Abstract_Order ) {
		$order = wc_get_order( $order_id );
	}

	if ( ! $order || '' !== (string) $order->get_meta( SIMPLE_BANGLA_ORDER_NUMBER_META ) ) {
		return;
	}

	$order->update_meta_data( SIMPLE_BANGLA_ORDER_NUMBER_META, simple_bangla_generate_order_number( $order->get_id() ) );
	$order->save();
}
add_action( 'woocommerce_new_order', 'simple_bangla_assign_order_number', 10, 2 );

/**
 * Answer with the generated number wherever WooCommerce asks for one.
 *
 * This single filter is what puts the new number on the thank-you page, in every WooCommerce email,
 * in My Account, on the CMS order list, card, detail screen and invoice, and in the `invoice` field
 * sent to the courier — all of those already ask `get_order_number()` rather than reading the ID.
 *
 * An order without the meta falls through to whatever WooCommerce was going to say, which is its ID.
 *
 * @param string   $number Number WooCommerce would use.
 * @param WC_Order $order  The order.
 * @return string
 */
function simple_bangla_order_number( $number, $order ) {

	if ( ! $order instanceof WC_Abstract_Order ) {
		return $number;
	}

	$generated = (string) $order->get_meta( SIMPLE_BANGLA_ORDER_NUMBER_META );

	return '' !== $generated ? $generated : $number;
}
add_filter( 'woocommerce_order_number', 'simple_bangla_order_number', 10, 2 );

/**
 * Let an order search look inside the new number.
 *
 * Without this the CMS's search box — whose placeholder promises "order number" — would find an
 * order by the customer's phone but not by the number printed on their receipt.
 *
 * Two filters, because WooCommerce searches orders twice over: `woocommerce_shop_order_search_fields`
 * when orders are posts, and `woocommerce_order_table_search_query_meta_keys` when they live in the
 * High-Performance Order Storage tables. They take the same shape — a list of meta keys — and
 * WooCommerce's own documentation names each as the other's counterpart, so one callback serves both.
 * A shop can be switched between the two storage modes at any time, so registering only the mode in
 * use today would make the search stop working on a day nobody was touching this code.
 *
 * @param string[] $keys Meta keys WooCommerce searches.
 * @return string[]
 */
function simple_bangla_order_number_search_field( $keys ) {

	$keys[] = SIMPLE_BANGLA_ORDER_NUMBER_META;

	return $keys;
}
add_filter( 'woocommerce_shop_order_search_fields', 'simple_bangla_order_number_search_field' );
add_filter( 'woocommerce_order_table_search_query_meta_keys', 'simple_bangla_order_number_search_field' );

/**
 * Are orders stored in WooCommerce's own tables rather than as posts?
 *
 * @return bool
 */
function simple_bangla_orders_use_hpos() {

	return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
		&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
}

/**
 * Find an order by its printed number.
 *
 * Not used by the theme itself — it exists so that the CMS, a courier callback or a support script
 * can turn what a customer read out into an order without knowing how the number is built. A leading
 * `#` is tolerated because that is how the number appears on screen and is therefore how it gets
 * pasted back in.
 *
 * **A direct query, not `wc_get_orders()`.** The obvious version of this function passed a
 * `meta_query`, and on the legacy post-table store WooCommerce silently drops it: every call came
 * back with the newest order regardless of the number asked for — a lookup that answers confidently
 * and wrongly, which is worse than one that fails. One indexed equality read against the meta table
 * cannot be misread that way.
 *
 * @param string $number Printed order number.
 * @return int Order ID, or 0.
 */
function simple_bangla_order_id_by_number( $number ) {

	global $wpdb;

	$number = ltrim( trim( (string) $number ), '#' );

	if ( '' === $number ) {
		return 0;
	}

	if ( simple_bangla_orders_use_hpos() ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = %s AND meta_value = %s ORDER BY order_id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				SIMPLE_BANGLA_ORDER_NUMBER_META,
				$number
			)
		);
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s ORDER BY post_id DESC LIMIT 1",
				SIMPLE_BANGLA_ORDER_NUMBER_META,
				$number
			)
		);
	}

	if ( $id && wc_get_order( (int) $id ) ) {
		return (int) $id;
	}

	// An order placed before this file existed still answers to its ID.
	return is_numeric( $number ) && wc_get_order( (int) $number ) ? (int) $number : 0;
}
