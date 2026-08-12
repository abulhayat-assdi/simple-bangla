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

<?php
/*
 * WooCommerce's own account block, restored verbatim.
 *
 * This store does not offer registration at the checkout, so on it none of this renders — which is
 * exactly why it went missing from the override in the first place, and exactly why it had to come
 * back. The moment anyone ticks "Allow customers to create an account during checkout", WooCommerce
 * starts validating and saving `account_password`; with no field on the page the customer is asked
 * for a password they were never shown a box for, and the checkout fails with an error they cannot
 * act on. A template override has to keep the parts it does not use.
 */
if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) :
	?>
	<div class="sb-checkout__block woocommerce-account-fields">

		<?php if ( ! $checkout->is_registration_required() ) : ?>
			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input
						class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
						id="createaccount"
						type="checkbox"
						name="createaccount"
						value="1"
						<?php checked( ( true === $checkout->get_value( 'createaccount' ) ), true ); ?>
					/>
					<span><?php esc_html_e( 'একটি অ্যাকাউন্ট খুলবেন?', 'simple-bangla' ); ?></span>
				</label>
			</p>
		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>
			<div class="create-account">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
	<?php
endif;
