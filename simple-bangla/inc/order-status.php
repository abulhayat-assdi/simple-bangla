<?php
/**
 * The two order stages WooCommerce does not have.
 *
 * A cash-on-delivery shop moves an order through five stages: it arrives, it goes to the courier,
 * and then it either arrives at the customer, comes back, or was killed before it ever shipped.
 * WooCommerce has statuses for three of those and nothing for the middle one.
 *
 * **This replaces the earlier decision to map the stages onto existing statuses** (2026-08-09, after
 * the owner placed a real test order). That mapping used `processing` to mean "with the courier",
 * which cannot work: WooCommerce's own Cash on Delivery gateway sets an order to `processing` the
 * moment it is placed, so every new order appeared as already dispatched. There is no spare status
 * to borrow — `on-hold` means "waiting for payment" everywhere else in WooCommerce and in its
 * emails — so the two missing stages are registered here instead.
 *
 * **In the theme, not the plugin**, for the same reason as the block list: an order's status is
 * visible to the customer in My Account and in every WooCommerce email. If these were the plugin's,
 * deactivating the management interface would leave real orders sitting in a status nothing could
 * name, which is a worse failure than losing the interface.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * The stages this theme adds, keyed by the status slug WooCommerce will use.
 *
 * The `wc-` prefix is WooCommerce's convention for an order status registered as a post status; the
 * REST API and `$order->get_status()` both report the slug without it.
 *
 * @return array<string,array{label:string,plural:string}>
 */
function simple_bangla_extra_order_statuses() {

	return array(
		'wc-sb-courier'  => array(
			'label'  => _x( 'Courier-এ আছে', 'Order status', 'simple-bangla' ),
			/* translators: %s: number of orders. */
			'plural' => _n_noop( 'Courier-এ আছে <span class="count">(%s)</span>', 'Courier-এ আছে <span class="count">(%s)</span>', 'simple-bangla' ),
		),
		'wc-sb-returned' => array(
			'label'  => _x( 'Returned', 'Order status', 'simple-bangla' ),
			/* translators: %s: number of orders. */
			'plural' => _n_noop( 'Returned <span class="count">(%s)</span>', 'Returned <span class="count">(%s)</span>', 'simple-bangla' ),
		),
	);
}

/**
 * Register them as post statuses.
 *
 * `exclude_from_search` and the three `show_in_*` flags copy what WooCommerce sets on its own order
 * statuses. Getting them wrong is how an order ends up visible in a site search or missing from the
 * customer's own order list.
 */
function simple_bangla_register_order_statuses() {

	foreach ( simple_bangla_extra_order_statuses() as $slug => $status ) {
		register_post_status(
			$slug,
			array(
				'label'                     => $status['label'],
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => $status['plural'],
			)
		);
	}
}
add_action( 'init', 'simple_bangla_register_order_statuses' );

/**
 * Offer them wherever WooCommerce lists order statuses.
 *
 * This one filter is what puts them in the wp-admin status dropdown, in the REST API's `status`
 * enum — WooCommerce builds that enum from this list, so no separate registration is needed — and
 * in the CMS's own bulk-change control.
 *
 * Inserted in the order a parcel actually travels rather than appended, so the dropdown reads as a
 * journey instead of as core's statuses with two afterthoughts at the end.
 *
 * @param array $statuses Slug => label.
 * @return array
 */
function simple_bangla_add_order_statuses( $statuses ) {

	$out = array();

	foreach ( $statuses as $slug => $label ) {

		$out[ $slug ] = $label;

		if ( 'wc-processing' === $slug ) {
			$out['wc-sb-courier'] = simple_bangla_extra_order_statuses()['wc-sb-courier']['label'];
		}

		if ( 'wc-completed' === $slug ) {
			$out['wc-sb-returned'] = simple_bangla_extra_order_statuses()['wc-sb-returned']['label'];
		}
	}

	// If WooCommerce ever renames those two, appending is better than dropping the stages entirely.
	foreach ( simple_bangla_extra_order_statuses() as $slug => $status ) {
		if ( ! isset( $out[ $slug ] ) ) {
			$out[ $slug ] = $status['label'];
		}
	}

	return $out;
}
add_filter( 'wc_order_statuses', 'simple_bangla_add_order_statuses' );

/**
 * A parcel with the courier has been paid for as far as stock is concerned.
 *
 * Without this WooCommerce treats the status as unpaid and would reduce stock a second time if the
 * order were later moved to completed. `processing` is on this list for exactly the same reason.
 *
 * @param string[] $statuses Status slugs, without the `wc-` prefix.
 * @return string[]
 */
function simple_bangla_paid_order_statuses( $statuses ) {

	$statuses[] = 'sb-courier';

	return $statuses;
}
add_filter( 'woocommerce_order_is_paid_statuses', 'simple_bangla_paid_order_statuses' );

/**
 * Put a returned parcel's stock back.
 *
 * A refused parcel comes back to the shop and is sellable again, which is the whole difference
 * between this stage and `completed`. WooCommerce restocks on `cancelled` and on a refund but knows
 * nothing about this status, so it is done here — guarded by WooCommerce's own `_order_stock_reduced`
 * flag, which its restock helper clears, so a status toggled twice cannot restock twice.
 *
 * @param int $order_id Order ID.
 */
function simple_bangla_restock_returned_order( $order_id ) {

	$order = wc_get_order( $order_id );

	if ( ! $order || ! $order->get_data_store()->get_stock_reduced( $order_id ) ) {
		return;
	}

	wc_increase_stock_levels( $order_id );
}
add_action( 'woocommerce_order_status_sb-returned', 'simple_bangla_restock_returned_order' );
