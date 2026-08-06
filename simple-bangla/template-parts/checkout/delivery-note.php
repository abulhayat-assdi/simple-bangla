<?php
/**
 * Optional delivery note, rendered under the shipping-charge choice.
 *
 * Wraps WooCommerce's own 'order_comments' field (relabelled in inc/woocommerce.php) rather
 * than inventing a new one, so the text a customer types here saves onto the order's customer
 * note exactly the way WooCommerce already handles that field — no extra processing needed.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_checkout = WC()->checkout();
$simple_bangla_fields   = $simple_bangla_checkout->get_checkout_fields( 'order' );
$simple_bangla_field    = isset( $simple_bangla_fields['order_comments'] ) ? $simple_bangla_fields['order_comments'] : array();

if ( ! $simple_bangla_field ) {
	return;
}
?>

<div class="sb-checkout__block sb-checkout__note">

	<h3 class="sb-checkout__legend"><?php esc_html_e( 'ডেলিভারি নোট', 'simple-bangla' ); ?></h3>

	<?php woocommerce_form_field( 'order_comments', $simple_bangla_field, $simple_bangla_checkout->get_value( 'order_comments' ) ); ?>

</div>
