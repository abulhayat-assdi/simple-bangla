<?php
/**
 * WooCommerce integration.
 *
 * Only loaded when WooCommerce is active — see functions.php.
 *
 * Phase 1 covers currency presentation and the wrappers the archive templates need.
 * The Buy Now handler, cart fragments and template overrides arrive in later phases.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/*
 * Currency presentation: ৳ 1,999 — comma thousands, no decimals.
 *
 * Done through WooCommerce's own display filters rather than by overwriting the
 * woocommerce_price_* options, so the store owner's settings stay intact and the theme
 * can be switched away cleanly.
 */

/**
 * Drop the decimal places from every displayed price.
 *
 * Gadget prices in Bangladesh are whole Taka; showing `.00` is noise.
 *
 * @return int
 */
function simple_bangla_price_decimals() {
	return 0;
}
add_filter( 'wc_get_price_decimals', 'simple_bangla_price_decimals' );

/**
 * Use a comma as the thousands separator.
 *
 * @return string
 */
function simple_bangla_price_thousand_separator() {
	return ',';
}
add_filter( 'wc_get_price_thousand_separator', 'simple_bangla_price_thousand_separator' );

/**
 * Format a raw number as a Taka price string for use outside WooCommerce's own markup.
 *
 * Returns plain text, not HTML — callers escape it at the point of output.
 *
 * @param float $amount Amount in the store currency.
 * @return string e.g. "৳ 1,999".
 */
function simple_bangla_format_price( $amount ) {

	return get_woocommerce_currency_symbol() . ' ' . number_format( (float) $amount, 0, '.', ',' );
}

/**
 * Remove WooCommerce's default page wrappers.
 *
 * The theme supplies its own container in woocommerce/ and the header/footer templates,
 * so the stock <div id="primary"> would nest a second, conflicting layout.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

/**
 * Open the theme's own WooCommerce content wrapper.
 */
function simple_bangla_wrapper_start() {
	echo '<div class="sb-container sb-shop"><main id="main" class="sb-shop__main">';
}
add_action( 'woocommerce_before_main_content', 'simple_bangla_wrapper_start', 10 );

/**
 * Close the theme's WooCommerce content wrapper.
 */
function simple_bangla_wrapper_end() {
	echo '</main></div>';
}
add_action( 'woocommerce_after_main_content', 'simple_bangla_wrapper_end', 10 );

/**
 * Hide the stock WooCommerce sidebar.
 *
 * The shop sidebar is rendered by the theme's own archive template so that the filter
 * panel and the widget area share one collapsible container on mobile.
 */
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
