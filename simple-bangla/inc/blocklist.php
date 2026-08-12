<?php
/**
 * The order block list.
 *
 * A cash-on-delivery store in Bangladesh accumulates a handful of numbers that repeatedly order and
 * then refuse the parcel. Every such attempt costs a real courier fee, so the shop needs to be able
 * to say no at the checkout.
 *
 * **This lives in the theme, not in the CMS plugin, and that was a deliberate choice.** The rule the
 * whole project runs on is that deactivating the plugin changes nothing about the storefront — and a
 * block list enforced from the plugin would break it in the worst possible direction: deactivating
 * the management interface would quietly let blocked customers order again. Checkout is the theme's,
 * so the blocking is the theme's. The CMS only edits the list.
 *
 * The list is an option rather than a `theme_mod` because it is operational data, not appearance. A
 * theme mod would also be the wrong shape: the settings bridge carries scalars, and this is a list of
 * records.
 *
 * **Two kinds of entry: a phone number and an IP address** (owner's decision, 2026-08-09, replacing
 * the email entry the first version had). Email was the least useful of the three on a store where
 * most orders are placed as a guest and the address box is filled in with whatever gets past
 * validation. An IP is the opposite: it is recorded automatically, and it is what catches the same
 * person coming back with a fresh number.
 *
 * An IP is a weak identifier and this file does not pretend otherwise. Mobile carriers in Bangladesh
 * put thousands of subscribers behind one address, so an IP block can catch people it was not aimed
 * at, and anyone determined can change theirs in seconds. It is a way to stop a nuisance cheaply,
 * not a security control, and the refusal it produces is the same polite one a blocked number gets.
 *
 * Entries stored by the earlier version with `type: email` are no longer valid and are ignored —
 * they stop being enforced, and the next save from the CMS drops them.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** Where the list is stored. */
const SIMPLE_BANGLA_BLOCKLIST_OPTION = 'simple_bangla_blocklist';

/**
 * The block list, as stored.
 *
 * @return array<int,array{type:string,value:string,note:string,added:int}>
 */
function simple_bangla_blocklist() {

	$list = get_option( SIMPLE_BANGLA_BLOCKLIST_OPTION, array() );

	return is_array( $list ) ? $list : array();
}

/**
 * Reduce a phone number to the part worth comparing.
 *
 * The same Bangladeshi mobile arrives as `01712345678`, `+8801712345678`, `8801712345678` and
 * `01712-345678` depending on the customer and the keyboard. Comparing the raw strings would block
 * one spelling of a number and wave the next one through, which is the same as not blocking at all.
 * Everything non-numeric goes, then the last ten digits — which is the part that is actually unique
 * to a subscriber, with the leading zero and any country code stripped off.
 *
 * @param string $phone Raw phone number.
 * @return string Empty when there is nothing comparable left.
 */
function simple_bangla_normalize_phone( $phone ) {

	$digits = preg_replace( '/\D+/', '', (string) $phone );

	if ( strlen( $digits ) < 6 ) {
		return '';
	}

	return substr( $digits, -10 );
}

/**
 * Normalise one list entry, or reject it.
 *
 * @param array $entry Raw entry.
 * @return array{type:string,value:string,note:string,added:int}|null
 */
function simple_bangla_blocklist_entry( $entry ) {

	if ( ! is_array( $entry ) ) {
		return null;
	}

	// Anything that is not explicitly an IP is treated as a phone number, which is also what turns a
	// leftover `email` entry from the previous version into one that then fails validation below.
	$type  = isset( $entry['type'] ) && 'ip' === $entry['type'] ? 'ip' : 'phone';
	$value = isset( $entry['value'] ) ? trim( (string) $entry['value'] ) : '';

	if ( '' === $value ) {
		return null;
	}

	if ( 'ip' === $type ) {
		// Both families, because a Bangladeshi mobile network can hand out either and the address
		// shown on the order is whatever the customer arrived on.
		$value = filter_var( $value, FILTER_VALIDATE_IP );

		if ( ! $value ) {
			return null;
		}
	} else {
		$value = sanitize_text_field( $value );

		// Stored as typed so the owner recognises it; matched on the normalised form.
		if ( '' === simple_bangla_normalize_phone( $value ) ) {
			return null;
		}
	}

	return array(
		'type'  => $type,
		'value' => $value,
		'note'  => isset( $entry['note'] ) ? sanitize_text_field( (string) $entry['note'] ) : '',
		'added' => isset( $entry['added'] ) ? absint( $entry['added'] ) : time(),
	);
}

/**
 * Replace the whole list.
 *
 * Written as one value rather than appended to, so the CMS can reorder and remove without a second
 * endpoint, and so a half-applied write cannot leave a partially blocked list.
 *
 * @param array $entries Raw entries.
 * @return array The list as stored.
 */
function simple_bangla_save_blocklist( $entries ) {

	$clean = array();
	$seen  = array();

	foreach ( (array) $entries as $entry ) {

		$entry = simple_bangla_blocklist_entry( $entry );

		if ( ! $entry ) {
			continue;
		}

		// One key per person, so the same number typed two ways is stored once.
		$key = $entry['type'] . ':' . ( 'phone' === $entry['type']
			? simple_bangla_normalize_phone( $entry['value'] )
			: $entry['value'] );

		if ( isset( $seen[ $key ] ) ) {
			continue;
		}

		$seen[ $key ] = true;
		$clean[]      = $entry;
	}

	update_option( SIMPLE_BANGLA_BLOCKLIST_OPTION, $clean, false );

	return $clean;
}

/**
 * The address the shopper appears to be on.
 *
 * WooCommerce's own resolver rather than `REMOTE_ADDR`, deliberately, and for a different reason
 * than the CMS sign-in throttle — which ignores forwarded-for headers precisely because they are
 * spoofable. Here the goal is that the address matched is the same one WooCommerce writes onto the
 * order and the CMS then shows, so that copying it from an order into this list actually blocks that
 * customer. Two resolvers disagreeing would produce a block that silently never fires.
 *
 * @return string
 */
function simple_bangla_client_ip() {

	if ( class_exists( 'WC_Geolocation' ) ) {
		return (string) WC_Geolocation::get_ip_address();
	}

	return isset( $_SERVER['REMOTE_ADDR'] )
		? (string) filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP )
		: '';
}

/**
 * Whether this phone number or IP address is on the list.
 *
 * @param string $phone Billing phone.
 * @param string $ip    Client IP address.
 * @return array|false The matching entry, or false.
 */
function simple_bangla_find_block( $phone, $ip = '' ) {

	$phone_key = simple_bangla_normalize_phone( $phone );
	$ip_key    = filter_var( trim( (string) $ip ), FILTER_VALIDATE_IP );

	foreach ( simple_bangla_blocklist() as $entry ) {

		$entry = simple_bangla_blocklist_entry( $entry );

		if ( ! $entry ) {
			continue;
		}

		if ( 'phone' === $entry['type'] && $phone_key && simple_bangla_normalize_phone( $entry['value'] ) === $phone_key ) {
			return $entry;
		}

		if ( 'ip' === $entry['type'] && $ip_key && $entry['value'] === $ip_key ) {
			return $entry;
		}
	}

	return false;
}

/**
 * What a refused customer is told.
 *
 * One function because the refusal now has two callers — the classic checkout and the Store API —
 * and a shop that said two different things depending on which checkout the customer happened to
 * reach would be the one detail nobody thought to keep in step.
 *
 * It deliberately does not say "you are blocked". The shop may want to talk to the person, and
 * someone whose number was mistyped onto the list deserves a way back rather than an accusation.
 *
 * @return string
 */
function simple_bangla_block_message() {

	$contact = simple_bangla_get_contact( 'phone' );

	return $contact
		? sprintf(
			/* translators: %s: the shop's phone number. */
			__( 'এই তথ্য দিয়ে অর্ডার করা যাচ্ছে না। অনুগ্রহ করে আমাদের সাথে যোগাযোগ করুন: %s', 'simple-bangla' ),
			$contact
		)
		: __( 'এই তথ্য দিয়ে অর্ডার করা যাচ্ছে না। অনুগ্রহ করে আমাদের সাথে যোগাযোগ করুন।', 'simple-bangla' );
}

/**
 * Refuse a checkout from a blocked customer.
 *
 * Hooked on validation rather than on order creation: the customer is told before anything is
 * written, so no half-order and no stock movement has to be undone.
 *
 * The message says the order cannot be placed and points at the shop's phone number. It deliberately
 * does not say "you are blocked" — the shop may want to talk to the person, and someone whose number
 * was mistyped onto the list deserves a way back rather than an accusation.
 *
 * @param array    $data   Posted checkout fields.
 * @param WP_Error $errors Error collector.
 */
function simple_bangla_block_checkout( $data, $errors ) {

	$phone = isset( $data['billing_phone'] ) ? $data['billing_phone'] : '';

	if ( ! simple_bangla_find_block( $phone, simple_bangla_client_ip() ) ) {
		return;
	}

	$errors->add( 'simple_bangla_blocked', simple_bangla_block_message() );
}
add_action( 'woocommerce_after_checkout_validation', 'simple_bangla_block_checkout', 10, 2 );

/**
 * Refuse the same customer on the Store API.
 *
 * `woocommerce_after_checkout_validation` is fired by the classic checkout and by nothing else, so
 * for as long as it was the only hook here the block list had a hole in it that no amount of
 * checking the checkout page would have revealed: **`/wp-json/wc/store/v1/checkout` is registered
 * whatever the checkout page contains.** A blocked customer did not need to find the block
 * checkout — the route was there to be posted to either way.
 *
 * `woocommerce_store_api_checkout_update_order_from_request` is the counterpart moment: the draft
 * order has been filled in from the request and payment has not been taken. A `RouteException`
 * thrown here is what the Store API turns into an error the customer sees, so it is deliberately
 * not a bare `Exception` — the route's own handler answers that with an unhelpful 500.
 *
 * The draft order exists at this point, which the classic path avoids. That is WooCommerce's design
 * rather than a choice made here: the Store API builds the draft before it will validate anything.
 * A `checkout-draft` is not a real order, is never counted anywhere, and the CMS's own order views
 * exclude it.
 *
 * @param WC_Order $order Draft order.
 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException When the customer is blocked.
 */
function simple_bangla_block_store_api_checkout( $order ) {

	if ( ! simple_bangla_find_block( $order->get_billing_phone(), simple_bangla_client_ip() ) ) {
		return;
	}

	$exception = '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException';

	if ( ! class_exists( $exception ) ) {
		return;
	}

	throw new $exception( 'simple_bangla_blocked', simple_bangla_block_message(), 403 );
}
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'simple_bangla_block_store_api_checkout', 10, 1 );
