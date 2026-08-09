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

	$type  = isset( $entry['type'] ) && 'email' === $entry['type'] ? 'email' : 'phone';
	$value = isset( $entry['value'] ) ? trim( (string) $entry['value'] ) : '';

	if ( '' === $value ) {
		return null;
	}

	if ( 'email' === $type ) {
		$value = sanitize_email( $value );

		if ( ! $value ) {
			return null;
		}

		$value = strtolower( $value );
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
 * Whether this phone or email is on the list.
 *
 * @param string $phone Billing phone.
 * @param string $email Billing email.
 * @return array|false The matching entry, or false.
 */
function simple_bangla_find_block( $phone, $email ) {

	$phone_key = simple_bangla_normalize_phone( $phone );
	$email_key = strtolower( trim( (string) $email ) );

	foreach ( simple_bangla_blocklist() as $entry ) {

		$entry = simple_bangla_blocklist_entry( $entry );

		if ( ! $entry ) {
			continue;
		}

		if ( 'phone' === $entry['type'] && $phone_key && simple_bangla_normalize_phone( $entry['value'] ) === $phone_key ) {
			return $entry;
		}

		if ( 'email' === $entry['type'] && $email_key && $entry['value'] === $email_key ) {
			return $entry;
		}
	}

	return false;
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
	$email = isset( $data['billing_email'] ) ? $data['billing_email'] : '';

	if ( ! simple_bangla_find_block( $phone, $email ) ) {
		return;
	}

	$contact = simple_bangla_get_contact( 'phone' );

	$errors->add(
		'simple_bangla_blocked',
		$contact
			? sprintf(
				/* translators: %s: the shop's phone number. */
				__( 'এই তথ্য দিয়ে অর্ডার করা যাচ্ছে না। অনুগ্রহ করে আমাদের সাথে যোগাযোগ করুন: %s', 'simple-bangla' ),
				$contact
			)
			: __( 'এই তথ্য দিয়ে অর্ডার করা যাচ্ছে না। অনুগ্রহ করে আমাদের সাথে যোগাযোগ করুন।', 'simple-bangla' )
	);
}
add_action( 'woocommerce_after_checkout_validation', 'simple_bangla_block_checkout', 10, 2 );
