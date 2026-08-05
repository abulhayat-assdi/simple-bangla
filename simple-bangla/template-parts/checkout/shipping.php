<?php
/**
 * The delivery-charge choice, rendered under the billing address.
 *
 * This is WooCommerce's shipping-rate selector rebuilt so the label and the price sit at
 * opposite ends of a tappable row. The input names, values and `shipping_method` class are
 * WooCommerce's own, and the markup stays inside the checkout form, so its delegated change
 * handler recalculates the order total without any script of ours.
 *
 * The list is outside `#order_review`, which is the fragment WooCommerce replaces on every
 * recalculation. That is deliberate — it keeps the customer's choice from being re-rendered
 * under their finger mid-tap. The trade is that a rate list which changes with the address
 * would go stale here; this store's two flat rates never do, because they are a choice rather
 * than a zone matched from a state field.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
	return;
}

$simple_bangla_packages = WC()->shipping()->get_packages();

if ( empty( $simple_bangla_packages ) ) {
	return;
}

$simple_bangla_chosen = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
?>

<div class="sb-checkout__block sb-checkout__shipping">

	<h3 class="sb-checkout__legend"><?php esc_html_e( 'ডেলিভারি চার্জ', 'simple-bangla' ); ?></h3>

	<?php foreach ( $simple_bangla_packages as $simple_bangla_index => $simple_bangla_package ) : ?>
		<?php
		$simple_bangla_rates = isset( $simple_bangla_package['rates'] ) ? $simple_bangla_package['rates'] : array();

		if ( empty( $simple_bangla_rates ) ) {
			continue;
		}

		$simple_bangla_picked = isset( $simple_bangla_chosen[ $simple_bangla_index ] ) ? $simple_bangla_chosen[ $simple_bangla_index ] : '';
		?>

		<ul
			<?php echo ( 0 === $simple_bangla_index ) ? 'id="shipping_method"' : ''; ?>
			class="woocommerce-shipping-methods sb-shipping"
		>
			<?php foreach ( $simple_bangla_rates as $simple_bangla_rate ) : ?>
				<?php
				$simple_bangla_field_id = 'shipping_method_' . $simple_bangla_index . '_' . sanitize_title( $simple_bangla_rate->get_id() );

				// Taxes are off on these rates, but a store that switches them on should still
				// show the price the customer will actually pay.
				$simple_bangla_cost = (float) $simple_bangla_rate->get_cost();

				if ( $simple_bangla_cost > 0 && WC()->cart->display_prices_including_tax() ) {
					$simple_bangla_cost += (float) $simple_bangla_rate->get_shipping_tax();
				}
				?>
				<li class="sb-shipping__option">
					<input
						type="radio"
						name="shipping_method[<?php echo esc_attr( $simple_bangla_index ); ?>]"
						data-index="<?php echo esc_attr( $simple_bangla_index ); ?>"
						id="<?php echo esc_attr( $simple_bangla_field_id ); ?>"
						value="<?php echo esc_attr( $simple_bangla_rate->get_id() ); ?>"
						class="shipping_method"
						<?php checked( $simple_bangla_rate->get_id(), $simple_bangla_picked ); ?>
					/>
					<label for="<?php echo esc_attr( $simple_bangla_field_id ); ?>">
						<span class="sb-shipping__label"><?php echo esc_html( $simple_bangla_rate->get_label() ); ?></span>
						<span class="sb-shipping__cost">
							<?php
							if ( $simple_bangla_cost > 0 ) {
								echo wp_kses_post( wc_price( $simple_bangla_cost ) );
							} else {
								esc_html_e( 'ফ্রি', 'simple-bangla' );
							}
							?>
						</span>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>

	<?php endforeach; ?>
</div>
