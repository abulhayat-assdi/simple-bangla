<?php
/**
 * Order received — the thank-you page.
 *
 * Same banner and step bar as the checkout, with the last step marked done, then the order
 * summary WooCommerce would normally print. Cash-on-delivery buyers get told what happens
 * next, because "thank you" alone answers none of the questions they actually have.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Order|false $order */
?>

<div class="sb-checkout sb-thankyou">

	<div class="sb-checkout__banner">
		<div class="sb-container">
			<h1 class="sb-checkout__title"><?php esc_html_e( 'ধন্যবাদ', 'simple-bangla' ); ?></h1>
			<?php get_template_part( 'template-parts/checkout/steps', null, array( 'current' => 'done' ) ); ?>
		</div>
	</div>

	<div class="sb-container">

		<?php if ( ! $order ) : ?>

			<p class="sb-thankyou__lead">
				<?php echo esc_html( apply_filters( 'woocommerce_thankyou_order_received_text', __( 'আপনার অর্ডারটি গ্রহণ করা হয়েছে।', 'simple-bangla' ), null ) ); ?>
			</p>

		<?php elseif ( $order->has_status( 'failed' ) ) : ?>

			<p class="sb-thankyou__lead sb-thankyou__lead--failed">
				<?php esc_html_e( 'দুঃখিত, পেমেন্টটি সম্পন্ন হয়নি। অনুগ্রহ করে আবার চেষ্টা করুন।', 'simple-bangla' ); ?>
			</p>

			<p class="sb-thankyou__actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="sb-btn">
					<?php esc_html_e( 'আবার চেষ্টা করুন', 'simple-bangla' ); ?>
				</a>
			</p>

		<?php else : ?>

			<div class="sb-thankyou__card">
				<span class="sb-thankyou__tick"><?php simple_bangla_icon( 'check', 34 ); ?></span>

				<p class="sb-thankyou__lead">
					<?php esc_html_e( 'আপনার অর্ডারটি আমরা পেয়েছি। ধন্যবাদ!', 'simple-bangla' ); ?>
				</p>

				<p class="sb-thankyou__note">
					<?php esc_html_e( 'আমাদের একজন প্রতিনিধি শীঘ্রই আপনার নাম্বারে কল করে অর্ডারটি নিশ্চিত করবেন।', 'simple-bangla' ); ?>
				</p>
			</div>

			<ul class="sb-thankyou__overview">
				<li>
					<span><?php esc_html_e( 'অর্ডার নাম্বার', 'simple-bangla' ); ?></span>
					<strong><?php echo esc_html( $order->get_order_number() ); ?></strong>
				</li>
				<li>
					<span><?php esc_html_e( 'তারিখ', 'simple-bangla' ); ?></span>
					<strong><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong>
				</li>
				<?php if ( $order->get_billing_phone() ) : ?>
					<li>
						<span><?php esc_html_e( 'মোবাইল', 'simple-bangla' ); ?></span>
						<strong><?php echo esc_html( $order->get_billing_phone() ); ?></strong>
					</li>
				<?php endif; ?>
				<li>
					<span><?php esc_html_e( 'মোট', 'simple-bangla' ); ?></span>
					<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
				</li>
				<li>
					<span><?php esc_html_e( 'পেমেন্ট', 'simple-bangla' ); ?></span>
					<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
				</li>
			</ul>

			<?php
			// WooCommerce's own order table and customer details, so emails, plugins and the
			// account page all keep agreeing with what is shown here.
			do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
			do_action( 'woocommerce_thankyou', $order->get_id() );
			?>

			<p class="sb-thankyou__actions">
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>" class="sb-btn">
					<?php esc_html_e( 'আরও কেনাকাটা করুন', 'simple-bangla' ); ?>
				</a>
			</p>

		<?php endif; ?>

	</div>
</div>
