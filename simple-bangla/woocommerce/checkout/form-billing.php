<?php
/**
 * Billing form.
 *
 * Identical to WooCommerce's own, except that the heading says what the block is actually for on
 * this store: it is the address a courier will deliver to, not a billing address kept apart from
 * one. The separate shipping-address form is switched off in `inc/woocommerce.php`.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Checkout $checkout */
?>

<div class="sb-checkout__block woocommerce-billing-fields">

	<h3 class="sb-checkout__legend"><?php esc_html_e( 'ডেলিভারি তথ্য', 'simple-bangla' ); ?></h3>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">
		<?php foreach ( $checkout->get_checkout_fields( 'billing' ) as $key => $field ) : ?>
			<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
		<?php endforeach; ?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>
