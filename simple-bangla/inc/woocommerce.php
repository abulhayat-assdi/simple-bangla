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

/*
 * The theme overrides archive-product.php and single-product.php outright and supplies its own
 * containers there, so WooCommerce's <div id="primary"> wrapper is never wanted. These removals
 * are belt-and-braces for any WooCommerce template the theme has not overridden.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

/**
 * Hide the stock WooCommerce sidebar.
 *
 * The shop sidebar is rendered by the theme's own archive template so that the filter
 * panel and the widget area share one collapsible container on mobile.
 */
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

/**
 * How many products a shop or category page lists before paginating.
 *
 * @return int
 */
function simple_bangla_products_per_page() {
	return 16;
}
add_filter( 'loop_shop_per_page', 'simple_bangla_products_per_page', 20 );

/**
 * Columns per row in the product loop.
 *
 * The number is only advisory — the grid is CSS, not columns markup — but WooCommerce uses it
 * for the shortcode and for the related-products count, so it should agree with the stylesheet.
 *
 * @return int
 */
function simple_bangla_loop_columns() {
	return 4;
}
add_filter( 'loop_shop_columns', 'simple_bangla_loop_columns' );

/*
 * The theme's gallery draws its own sale ribbon, so WooCommerce's floating .onsale span would
 * be a second badge in the same corner.
 */
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );

/*
 * woocommerce/content-product.php draws its own image and ribbon, then fires
 * `woocommerce_before_shop_loop_item_title` so badge plugins still get their hook. WooCommerce
 * itself hangs the stock thumbnail and sale flash on that same hook, which rendered a second
 * copy of every product image — and a stray "Sale!" label — inside each card. Unhooking just
 * those two leaves the extension point intact.
 */
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );

/**
 * Add a Buy Now button beside Add to Cart.
 *
 * It is a second submit inside WooCommerce's own form, not a separate link, so variations,
 * quantity and validation all apply to it exactly as they do to Add to Cart. The only
 * difference is where the shopper lands afterwards.
 */
function simple_bangla_buy_now_button() {

	global $product;

	// A product that has to be configured elsewhere cannot be bought in one step.
	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}

	printf(
		'<button type="submit" name="sb_buy_now" value="1" class="sb-btn sb-btn--ghost sb-buy-now">%s</button>',
		esc_html__( 'Buy Now', 'simple-bangla' )
	);
}
add_action( 'woocommerce_after_add_to_cart_button', 'simple_bangla_buy_now_button', 20 );

/**
 * Send a Buy Now purchase straight to checkout.
 *
 * @param string $url Where WooCommerce intended to send the shopper.
 * @return string
 */
function simple_bangla_buy_now_redirect( $url ) {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce has already
	// validated this add-to-cart request; this only reads which button submitted it.
	if ( empty( $_REQUEST['sb_buy_now'] ) ) {
		return $url;
	}

	return wc_get_checkout_url();
}
add_filter( 'woocommerce_add_to_cart_redirect', 'simple_bangla_buy_now_redirect' );

/**
 * Buy Now has to be a real form round trip, not an AJAX add.
 *
 * WooCommerce's AJAX add-to-cart intercepts the button and stays on the page, which would
 * silently swallow the redirect. Disabling AJAX add-to-cart on single product pages only
 * leaves the archive behaviour untouched — and the archive card links to the product anyway.
 *
 * @param bool $enabled Whether AJAX adding is on.
 * @return bool
 */
function simple_bangla_disable_ajax_add_to_cart( $enabled ) {
	return is_product() ? false : $enabled;
}
add_filter( 'woocommerce_is_ajax_add_to_cart_enabled', 'simple_bangla_disable_ajax_add_to_cart' );

/**
 * Offer WhatsApp as an ordering route under the add-to-cart form.
 *
 * Ordering over WhatsApp is how a large share of Bangladeshi storefronts actually take orders,
 * so the message is pre-filled with the product name and its URL.
 */
function simple_bangla_whatsapp_order_button() {

	global $product;

	if ( ! $product ) {
		return;
	}

	$url = simple_bangla_whatsapp_url(
		sprintf(
			/* translators: 1: product name, 2: product URL. */
			__( 'Hello! I would like to order: %1$s (%2$s)', 'simple-bangla' ),
			$product->get_name(),
			$product->get_permalink()
		)
	);

	if ( ! $url ) {
		return;
	}

	printf(
		'<a class="sb-btn sb-whatsapp-order" href="%1$s" target="_blank" rel="noopener">%2$s<span>%3$s</span></a>',
		esc_url( $url ),
		// wp_kses_post() strips <svg> and <path> outright, so the theme's own icon markup is
		// echoed as-is. It contains no dynamic input.
		simple_bangla_get_icon( 'whatsapp', 20 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'Order on WhatsApp', 'simple-bangla' )
	);
}
add_action( 'woocommerce_after_add_to_cart_form', 'simple_bangla_whatsapp_order_button', 20 );

/**
 * Cut the checkout down to what a cash-on-delivery order actually needs.
 *
 * WooCommerce's default billing form asks for eleven fields. A courier in Bangladesh needs a
 * name, a phone number and one written address; everything else is friction on the one page
 * where friction costs orders. The delivery charge comes from the shipping choice rather than
 * from a state field, so dropping city, state and postcode changes nothing about the total.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function simple_bangla_checkout_fields( $fields ) {

	foreach ( array( 'billing_last_name', 'billing_company', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode' ) as $unwanted ) {
		unset( $fields['billing'][ $unwanted ] );
	}

	$labels = array(
		'billing_first_name' => array(
			'label'       => __( 'আপনার নাম', 'simple-bangla' ),
			'placeholder' => __( 'আপনার নাম লিখুন', 'simple-bangla' ),
			'priority'    => 10,
			'required'    => true,
			'class'       => array( 'form-row-wide' ),
		),
		'billing_phone'      => array(
			'label'       => __( 'মোবাইল নাম্বার', 'simple-bangla' ),
			'placeholder' => __( '০১XXXXXXXXX', 'simple-bangla' ),
			'priority'    => 20,
			'required'    => true,
			'class'       => array( 'form-row-wide' ),
		),
		'billing_address_1'  => array(
			'label'       => __( 'সম্পূর্ণ ঠিকানা', 'simple-bangla' ),
			'placeholder' => __( 'বাসা, রোড, এলাকা ও জেলা', 'simple-bangla' ),
			'priority'    => 30,
			'required'    => true,
			'class'       => array( 'form-row-wide' ),
		),
		'billing_email'      => array(
			'label'       => __( 'ইমেইল (ঐচ্ছিক)', 'simple-bangla' ),
			'placeholder' => __( 'you@example.com', 'simple-bangla' ),
			'priority'    => 40,
			'required'    => false,
			'class'       => array( 'form-row-wide' ),
		),
	);

	foreach ( $labels as $key => $overrides ) {
		if ( isset( $fields['billing'][ $key ] ) ) {
			$fields['billing'][ $key ] = array_merge( $fields['billing'][ $key ], $overrides );
		}
	}

	// The store only ships within Bangladesh, so the country picker is a question with one
	// answer. It stays in the array — WooCommerce needs it — but hidden and prefilled.
	if ( isset( $fields['billing']['billing_country'] ) ) {
		$fields['billing']['billing_country']['class'] = array( 'form-row-wide', 'sb-hidden-field' );
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'simple_bangla_checkout_fields', 20 );

/**
 * Order notes are noise on a phone-confirmed COD order.
 *
 * @return bool
 */
function simple_bangla_disable_order_notes() {
	return false;
}
add_filter( 'woocommerce_enable_order_notes_field', 'simple_bangla_disable_order_notes' );

/*
 * Drop the separate "ship to a different address" form. The parcel goes to the one address the
 * customer typed, and asking for a second one on a cash-on-delivery order is a second chance to
 * get it wrong. Shipping *rates* are unaffected — those come from needs_shipping(), not from
 * needs_shipping_address().
 */
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

/**
 * Show the breadcrumb the theme's own templates render, with the theme's markup.
 *
 * @param array $args Existing breadcrumb arguments.
 * @return array
 */
function simple_bangla_breadcrumb_args( $args ) {

	$args['delimiter']   = '<span class="sb-breadcrumb__sep" aria-hidden="true">/</span>';
	$args['wrap_before'] = '<nav class="sb-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'simple-bangla' ) . '">';
	$args['wrap_after']  = '</nav>';
	$args['before']      = '';
	$args['after']       = '';

	return $args;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'simple_bangla_breadcrumb_args' );

/**
 * Keep the header cart link in sync after an AJAX add-to-cart.
 *
 * WooCommerce replaces each matched selector's outerHTML with the string we return, so both
 * callbacks have to re-render the same element they are keyed on — hence the output buffering
 * around the template tags rather than a bespoke second copy of the markup.
 *
 * @param array $fragments Selector => markup.
 * @return array
 */
function simple_bangla_cart_fragments( $fragments ) {

	ob_start();
	simple_bangla_cart_count();
	$fragments['.sb-cart-link__count'] = ob_get_clean();

	ob_start();
	simple_bangla_cart_total();
	$fragments['.sb-cart-link__total'] = ob_get_clean();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'simple_bangla_cart_fragments' );
