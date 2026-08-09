<?php
/**
 * Order summary on the checkout page.
 *
 * Three departures from WooCommerce's own template, all visible:
 *
 * 1. Each line carries the product thumbnail and a remove link, so the summary is something a
 *    customer can check and correct without going back to the cart.
 * 2. The shipping row is gone. The delivery charge is chosen beside the address instead —
 *    see `template-parts/checkout/shipping.php` — and it is still counted in the total.
 *
 * Every hook WooCommerce fires in its own version is fired here in the same order, so payment
 * gateways, fee lines and tax rows keep working.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>

<table class="shop_table woocommerce-checkout-review-order-table sb-order-table">
	<thead>
		<tr>
			<th class="product-name"><?php esc_html_e( 'পণ্য', 'simple-bangla' ); ?></th>
			<th class="product-total"><?php esc_html_e( 'মোট', 'simple-bangla' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				continue;
			}

			$thumbnail = $_product->get_image( 'woocommerce_gallery_thumbnail' );
			?>
			<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
				<td class="product-name">
					<div class="sb-order-line">

						<a
							href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>"
							class="sb-order-line__remove"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( '%s সরিয়ে ফেলুন', 'simple-bangla' ), wp_strip_all_tags( $_product->get_name() ) ) ); ?>"
						>&times;</a>

						<?php if ( $thumbnail ) : ?>
							<span class="sb-order-line__media"><?php echo wp_kses_post( $thumbnail ); ?></span>
						<?php endif; ?>

						<span class="sb-order-line__text">
							<?php
							// WooCommerce's own filter, which plugins use to add markup to the name.
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) );

							wc_get_formatted_cart_item_data( $cart_item );
							?>

							<?php
							/*
							 * The stepper replaces the "× 2" that WooCommerce prints here — and with it
							 * the `woocommerce_checkout_cart_item_quantity` filter, which only exists to
							 * restyle that static text. This is the one place a customer can change how
							 * many they are buying, because the product page no longer asks.
							 */
							$sold_individually = $_product->is_sold_individually();
							?>
							<span class="sb-qty" data-sb-qty data-sb-key="<?php echo esc_attr( $cart_item_key ); ?>">
								<button
									type="button"
									class="sb-qty__btn"
									data-sb-qty-step="-1"
									<?php disabled( true, $sold_individually || $cart_item['quantity'] <= 1 ); ?>
									aria-label="<?php esc_attr_e( 'পরিমাণ কমান', 'simple-bangla' ); ?>"
								>&minus;</button>

								<span class="sb-qty__value" aria-live="polite"><?php echo esc_html( (string) $cart_item['quantity'] ); ?></span>

								<button
									type="button"
									class="sb-qty__btn"
									data-sb-qty-step="1"
									<?php disabled( true, $sold_individually ); ?>
									aria-label="<?php esc_attr_e( 'পরিমাণ বাড়ান', 'simple-bangla' ); ?>"
								>+</button>
							</span>
						</span>
					</div>
				</td>

				<td class="product-total">
					<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?>
				</td>
			</tr>
			<?php
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</tbody>

	<tfoot>
		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'সাবটোটাল', 'simple-bangla' ); ?></th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php
		/*
		 * `woocommerce_review_order_before_shipping` and `..._after_shipping` still fire, because
		 * plugins hang delivery-date pickers and pickup-point selectors on them. Only the rate
		 * list itself is absent — it is rendered beside the address instead.
		 */
		if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
			do_action( 'woocommerce_review_order_before_shipping' );
			do_action( 'woocommerce_review_order_after_shipping' );
		}
		?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th><?php esc_html_e( 'সর্বমোট', 'simple-bangla' ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</tfoot>
</table>

