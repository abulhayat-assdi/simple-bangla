<?php
/**
 * Checkout form.
 *
 * Wraps WooCommerce's own checkout in the theme's banner and step bar. The form itself is left
 * to WooCommerce — its fields, validation, coupon handling and payment hooks all stay intact,
 * which is what keeps gateways and shipping plugins working.
 *
 * The copy here is Bangla by decision: this is the one page a customer types into, and the
 * shop's buyers read Bangla. Every string still goes through __(), so the page can be
 * translated like any other.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Checkout $checkout */
?>

<div class="sb-checkout">

	<div class="sb-checkout__banner">
		<div class="sb-container">
			<h1 class="sb-checkout__title"><?php esc_html_e( 'চেক আউট', 'simple-bangla' ); ?></h1>
			<?php get_template_part( 'template-parts/checkout/steps', null, array( 'current' => 'review' ) ); ?>
		</div>
	</div>

	<div class="sb-container">

		<p class="sb-checkout__instruction">
			<?php esc_html_e( 'অর্ডারটি কনফার্ম করতে ফর্মটি সম্পূর্ণ পূরণ করে নিচের Place Order বাটনে ক্লিক করুন।', 'simple-bangla' ); ?>
		</p>

		<?php if ( ! is_ajax() ) : ?>
			<?php
			/** This hook is documented in woocommerce/templates/checkout/form-checkout.php */
			do_action( 'woocommerce_before_checkout_form', $checkout );
			?>
		<?php endif; ?>

		<?php
		// A guest who is not allowed to order without an account gets the login prompt instead.
		if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {

			echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'অর্ডার করতে হলে আগে লগইন করুন।', 'simple-bangla' ) ) );
			return;
		}
		?>

		<form name="checkout" method="post" class="checkout woocommerce-checkout sb-checkout__form"
			action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

			<?php if ( $checkout->get_checkout_fields() ) : ?>

				<div class="sb-checkout__details">
					<?php
					/** This hook is documented in woocommerce/templates/checkout/form-checkout.php */
					do_action( 'woocommerce_checkout_before_customer_details' );
					?>

					<div class="col2-set" id="customer_details">
						<div class="col-1">
							<?php
							/** This hook is documented in woocommerce/templates/checkout/form-checkout.php */
							do_action( 'woocommerce_checkout_billing' );
							?>
						</div>

						<div class="col-2">
							<?php
							/** This hook is documented in woocommerce/templates/checkout/form-checkout.php */
							do_action( 'woocommerce_checkout_shipping' );
							?>
						</div>
					</div>

					<?php
					/** This hook is documented in woocommerce/templates/checkout/form-checkout.php */
					do_action( 'woocommerce_checkout_after_customer_details' );
					?>
				</div>

			<?php endif; ?>

			<div class="sb-checkout__order">
				<h2 class="sb-checkout__subtitle"><?php esc_html_e( 'আপনার অর্ডার', 'simple-bangla' ); ?></h2>

				<?php
				/** This hook is documented in woocommerce/templates/checkout/form-checkout.php */
				do_action( 'woocommerce_checkout_before_order_review_heading' );

				/** This hook is documented in woocommerce/templates/checkout/form-checkout.php */
				do_action( 'woocommerce_checkout_before_order_review' );
				?>

				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php
					/** This hook is documented in woocommerce/templates/checkout/form-checkout.php */
					do_action( 'woocommerce_checkout_order_review' );
					?>
				</div>

				<?php
				/** This hook is documented in woocommerce/templates/checkout/form-checkout.php */
				do_action( 'woocommerce_checkout_after_order_review' );
				?>
			</div>

		</form>

		<?php
		/** This hook is documented in woocommerce/templates/checkout/form-checkout.php */
		do_action( 'woocommerce_after_checkout_form', $checkout );
		?>

	</div>
</div>
