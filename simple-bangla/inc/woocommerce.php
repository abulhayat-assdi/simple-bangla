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
 * The single-product template prints its own category-based "You may also like" strip via
 * simple_bangla_related_products(), so WooCommerce's related shelf is unhooked — with both on,
 * the same products would appear twice under every product.
 */
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

/*
 * woocommerce/content-product.php draws its own image and ribbon, then fires
 * `woocommerce_before_shop_loop_item_title` so badge plugins still get their hook. WooCommerce
 * itself hangs the stock thumbnail and sale flash on that same hook, which rendered a second
 * copy of every product image — and a stray "Sale!" label — inside each card. Unhooking just
 * those two leaves the extension point intact.
 */
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );

/*
 * The shop archive fires `woocommerce_before_shop_loop` so that WooCommerce's notices — and any
 * plugin hanging off it — actually appear. Its own result count and sort dropdown are unhooked
 * because archive-product.php calls both directly, inside a toolbar row they share with the
 * mobile filter button; leaving them hooked would print each of them twice on every shop page.
 *
 * Same pattern as the card's `woocommerce_before_shop_loop_item_title` above: fire the hook, keep
 * the extension point, unhook only the core callbacks the template has taken over.
 */
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

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

	// A browser only submits the clicked button's name/value pair, so without this hidden
	// field WooCommerce's own add-to-cart button never fires and $_REQUEST['add-to-cart']
	// is empty — WC_Form_Handler::add_to_cart_action() bails before our redirect filter runs.
	printf(
		'<input type="hidden" name="add-to-cart" value="%1$d" /><button type="submit" name="sb_buy_now" value="1" class="sb-btn sb-buy-now">%2$s</button>',
		absint( $product->get_id() ),
		esc_html__( 'অর্ডার করুন', 'simple-bangla' )
	);
}
// Buy Now ("অর্ডার করুন") moves to after_add_to_cart_form so it sits below the add-to-cart
// row — the same position the WhatsApp button used to occupy.
add_action( 'woocommerce_after_add_to_cart_form', 'simple_bangla_buy_now_button', 15 );

/**
 * Customize the single product Add to Cart button text to Bangla.
 *
 * @return string
 */
function simple_bangla_single_add_to_cart_text() {
	return __( 'কার্টে যোগ করুন', 'simple-bangla' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'simple_bangla_single_add_to_cart_text' );

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
 * Say nothing when the add-to-cart was a one-click order.
 *
 * "X has been added to your cart. [View cart]" is useful on a product page, where the shopper
 * stays put and needs telling something happened. On a one-click order it lands at the top of
 * the checkout — announcing a step the customer can already see the result of, and offering a
 * link back to the cart, away from the page they were sent to finish.
 *
 * Returning an empty string means no notice is stored at all; `wc_add_notice()` skips empty
 * messages. Ordinary Add to cart still reports itself.
 *
 * @param string $message The notice WooCommerce is about to store.
 * @return string
 */
function simple_bangla_silence_buy_now_message( $message ) {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce has already
	// validated this add-to-cart request; this only reads which button submitted it.
	return empty( $_REQUEST['sb_buy_now'] ) ? $message : '';
}
add_filter( 'wc_add_to_cart_message_html', 'simple_bangla_silence_buy_now_message' );

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
 * Offer WhatsApp as an ordering route beside the add-to-cart button.
 *
 * Ordering over WhatsApp is how a large share of Bangladeshi storefronts actually take orders,
 * so the message is pre-filled with the product name and its URL.
 *
 * Moved to woocommerce_after_add_to_cart_button (priority 20) so it appears in the same row
 * as "কার্টে যোগ করুন", taking the position that "অর্ডার করুন" previously held.
 */
function simple_bangla_whatsapp_order_button() {

	global $product;

	if ( ! $product ) {
		return;
	}

	$url = simple_bangla_whatsapp_url(
		sprintf(
			/* translators: 1: product name, 2: product URL. */
			__( 'হ্যালো! আমি অর্ডার করতে চাই: %1$s (%2$s)', 'simple-bangla' ),
			$product->get_name(),
			$product->get_permalink()
		)
	);

	if ( ! $url ) {
		return;
	}

	printf(
		'<input type="hidden" name="sb_whatsapp_dummy" value="0" /><a class="sb-btn sb-whatsapp-order" href="%1$s" target="_blank" rel="noopener">%2$s<span>%3$s</span></a>',
		esc_url( $url ),
		// wp_kses_post() strips <svg> and <path> outright, so the theme's own icon markup is
		// echoed as-is. It contains no dynamic input.
		simple_bangla_get_icon( 'whatsapp', 20 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'হোয়াটসঅ্যাপে অর্ডার করুন', 'simple-bangla' )
	);
}
// WhatsApp button now sits beside "কার্টে যোগ করুন", inside the form row.
add_action( 'woocommerce_after_add_to_cart_button', 'simple_bangla_whatsapp_order_button', 20 );

// The "কল করে অর্ডার করুন" button was removed from the product page at the store owner's
// request (2026-08-19). The phone number is still shown in the header and footer.

/**
 * Drop the quantity box from the product page.
 *
 * A shopper on a product page is deciding *whether* to buy, not how many; the count belongs at
 * the checkout, where the stepper beside each line changes it against the real cart. Buffering
 * between WooCommerce's own two hooks removes the field without overriding
 * `global/quantity-input.php`, which the cart and the mini-cart also render.
 *
 * The hidden input is what keeps this a removal rather than a break: WooCommerce reads
 * `$_REQUEST['quantity']` when the form is submitted, and Buy Now posts the same form.
 */
function simple_bangla_hide_quantity_start() {

	if ( ! is_product() ) {
		return;
	}

	simple_bangla_quantity_buffering( true );
	ob_start();
}
// Last on the way in, first on the way out, so the buffer contains the quantity field and
// nothing else — anything another plugin hangs on these two hooks is printed, not swallowed.
add_action( 'woocommerce_before_add_to_cart_quantity', 'simple_bangla_hide_quantity_start', 999 );

/**
 * Discard the buffered quantity field and post 1 instead.
 */
function simple_bangla_hide_quantity_end() {

	// Only close a buffer this theme opened. `ob_get_level()` would also be true for someone
	// else's buffer, and closing that one would swallow a page.
	if ( ! simple_bangla_quantity_buffering() ) {
		return;
	}

	simple_bangla_quantity_buffering( false );
	ob_end_clean();

	echo '<input type="hidden" name="quantity" value="1" />';
}

/**
 * Remember whether the quantity buffer above is currently open.
 *
 * @param bool|null $set New state, or null to read.
 * @return bool
 */
function simple_bangla_quantity_buffering( $set = null ) {

	static $open = false;

	if ( null !== $set ) {
		$open = (bool) $set;
	}

	return $open;
}
add_action( 'woocommerce_after_add_to_cart_quantity', 'simple_bangla_hide_quantity_end', 0 );

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

	foreach ( array( 'billing_last_name', 'billing_company', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode', 'billing_country', 'billing_email' ) as $unwanted ) {
		unset( $fields['billing'][ $unwanted ] );
	}

	$fields['billing']['billing_first_name'] = array(
		'label'       => __( 'নাম', 'simple-bangla' ),
		'placeholder' => __( 'আপনার নাম লিখুন', 'simple-bangla' ),
		'priority'    => 10,
		'required'    => true,
		'class'       => array( 'form-row-wide' ),
	);

	$fields['billing']['billing_phone'] = array(
		'label'       => __( 'মোবাইল নাম্বার', 'simple-bangla' ),
		'placeholder' => __( 'আপনার মোবাইল নাম্বার লিখুন', 'simple-bangla' ),
		'priority'    => 20,
		'required'    => true,
		'type'        => 'tel',
		'class'       => array( 'form-row-wide' ),
	);

	$fields['billing']['billing_address_1'] = array(
		'label'       => __( 'সম্পূর্ণ ঠিকানা', 'simple-bangla' ),
		'placeholder' => __( 'আপনার ঠিকানা লিখুন', 'simple-bangla' ),
		'priority'    => 30,
		'required'    => true,
		'class'       => array( 'form-row-wide' ),
	);

	$fields['billing']['billing_alt_phone'] = array(
		'label'       => __( 'ব্যাকআপ নাম্বার (অপশনাল)', 'simple-bangla' ),
		'placeholder' => __( 'ব্যাকআপ নাম্বার', 'simple-bangla' ),
		'priority'    => 40,
		'required'    => false,
		'type'        => 'tel',
		'class'       => array( 'form-row-wide' ),
	);

	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments'] = array_merge(
			$fields['order']['order_comments'],
			array(
				'label'       => __( 'ডেলিভারি নোট', 'simple-bangla' ),
				'placeholder' => __( 'ডেলিভারি সংক্রান্ত কোন নির্দেশনা থাকলে লিখুন (ঐচ্ছিক)', 'simple-bangla' ),
				'required'    => false,
				'class'       => array( 'form-row-wide' ),
			)
		);
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'simple_bangla_checkout_fields', 20 );

/**
 * Save custom checkout field (billing_alt_phone) to order meta.
 *
 * @param int $order_id Order ID.
 */
function simple_bangla_save_checkout_alt_phone( $order_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! empty( $_POST['billing_alt_phone'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$alt_phone = sanitize_text_field( wp_unslash( $_POST['billing_alt_phone'] ) );
		update_post_meta( $order_id, '_billing_alt_phone', $alt_phone );
		update_post_meta( $order_id, 'billing_alt_phone', $alt_phone );
	}
}
add_action( 'woocommerce_checkout_update_order_meta', 'simple_bangla_save_checkout_alt_phone' );

/**
 * Render Payment heading inside checkout payment box.
 */
function simple_bangla_payment_heading() {
	echo '<h4 class="sb-payment-heading"><em>Payment</em></h4>';
}
add_action( 'woocommerce_review_order_before_payment', 'simple_bangla_payment_heading' );

/**
 * Customize the place order button HTML with shopping cart icon matching reference image.
 *
 * @param string $button_html Original button HTML.
 * @return string
 */
function simple_bangla_order_button_html( $button_html ) {
	$icon = '<svg class="sb-place-order-icon" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0 0 20 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>';

	return sprintf(
		'<button type="submit" class="button alt sb-btn-place-order" name="woocommerce_checkout_place_order" id="place_order" value="%1$s" data-value="%1$s">%2$s <span>%3$s</span></button>',
		esc_attr__( 'Order Now', 'simple-bangla' ),
		$icon,
		esc_html__( 'Order Now', 'simple-bangla' )
	);
}
add_filter( 'woocommerce_order_button_html', 'simple_bangla_order_button_html' );

/**
 * Render the delivery note after the shipping-charge choice.
 *
 * WooCommerce's own "Additional information" block (built around this same `order_comments`
 * field) lives in core's checkout/form-shipping.php — a template this theme never overrides,
 * so it still runs from the `woocommerce_checkout_shipping` hook in form-checkout.php and would
 * render the field a second time, unstyled, if left enabled. It is switched off here and
 * rendered directly instead, styled to match the address and shipping-charge boxes beside it.
 * It still posts under WooCommerce's own 'order_comments' key, which `WC_Checkout::create_order()`
 * already saves as the order's customer note without any extra processing here.
 */
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

function simple_bangla_checkout_delivery_note() {
	get_template_part( 'template-parts/checkout/delivery-note' );
}
add_action( 'woocommerce_checkout_after_customer_details', 'simple_bangla_checkout_delivery_note', 20 );

/*
 * Drop WooCommerce's own order-details and customer-address tables from the thank-you page.
 *
 * They are hooked onto `woocommerce_thankyou`, which the theme's thankyou.php still fires so that
 * gateways and plugins keep their slot. Only these two default renderers are removed: the theme's
 * own cards already show every line they would print — items, totals, payment method, address —
 * in Bangla and in the page's own layout, so leaving them on meant every fact appearing twice, the
 * second time in an unstyled English table.
 */
remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

/*
 * Drop the separate "ship to a different address" form. The parcel goes to the one address the
 * customer typed, and asking for a second one on a cash-on-delivery order is a second chance to
 * get it wrong. Shipping *rates* are unaffected — those come from needs_shipping(), not from
 * needs_shipping_address().
 */
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

/**
 * Put the delivery-charge choice under the address, not inside the order summary.
 *
 * WooCommerce renders shipping rates as a row of the order-review table. Here the customer is
 * choosing between "inside Dhaka" and "outside Dhaka" — that is part of saying where the parcel
 * goes, so it belongs beside the address they just typed, not in the column that totals it up.
 *
 * The radios keep WooCommerce's own names and classes, and they are still inside the checkout
 * form, so its delegated change handler recalculates the total exactly as before.
 */
function simple_bangla_checkout_shipping_choice() {
	get_template_part( 'template-parts/checkout/shipping' );
}
add_action( 'woocommerce_checkout_after_customer_details', 'simple_bangla_checkout_shipping_choice' );

/*
 * The coupon form stays where WooCommerce puts it: above the checkout form, not inside the order
 * summary where the design would rather have it. It is a `<form>` of its own, and the order
 * summary sits inside the checkout `<form>` — nesting one form in another is invalid HTML, and a
 * browser drops the inner tag, which would leave "Apply coupon" submitting the checkout itself.
 * The stylesheet reduces it to a single line of text so it stops reading as a boxed prompt.
 */

/**
 * Repeat the instruction where the customer is about to act on it.
 *
 * The same line runs across the top of the page, but by the time someone has filled the form in
 * they have scrolled past it, and the button it names is the one right below this.
 */
function simple_bangla_checkout_submit_note() {

	printf(
		'<p class="sb-checkout__submit-note">%s</p>',
		esc_html__( 'অর্ডারটি কনফার্ম করতে ফর্মটি সম্পূর্ণ পূরণ করে নিচের Place Order বাটনে ক্লিক করুন।', 'simple-bangla' )
	);
}
add_action( 'woocommerce_review_order_before_submit', 'simple_bangla_checkout_submit_note' );

/**
 * Drop WooCommerce's default privacy-policy paragraph above Place Order.
 *
 * The Bangla submit note right below it already tells the customer what happens next; the
 * English "Your personal data will be used…" boilerplate was a second, redundant notice in the
 * wrong language for this page. Filtering the option (rather than clearing it in the database)
 * leaves the store's own Accounts & Privacy setting untouched if the owner ever wants it back.
 */
add_filter( 'pre_option_woocommerce_checkout_privacy_policy_text', '__return_empty_string' );

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

/**
 * Change one cart line's quantity from the checkout's own stepper.
 *
 * The product page no longer asks how many, so this is where the question gets answered. It
 * writes to the real cart rather than to a form field, which is why the page can then simply
 * ask WooCommerce to re-render the order review — totals, coupons and delivery charge included
 * — instead of trying to recompute anything in the browser.
 */
function simple_bangla_ajax_cart_quantity() {

	check_ajax_referer( 'simple_bangla_cart_qty', 'nonce' );

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => __( 'কার্ট পাওয়া যায়নি।', 'simple-bangla' ) ), 500 );
	}

	$key      = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
	$quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 0;
	$item     = $key ? WC()->cart->get_cart_item( $key ) : null;

	if ( ! $item ) {
		wp_send_json_error( array( 'message' => __( 'পণ্যটি আর কার্টে নেই।', 'simple-bangla' ) ), 404 );
	}

	$product = $item['data'];

	// Removing a line is the × button's job; the stepper only ever counts.
	$quantity = max( 1, $quantity );

	if ( $product->is_sold_individually() ) {
		$quantity = 1;
	}

	if ( ! $product->has_enough_stock( $quantity ) ) {
		wp_send_json_error(
			array(
				'message' => sprintf(
					/* translators: %s: the number of units still in stock. */
					__( 'স্টকে আছে মাত্র %s টি।', 'simple-bangla' ),
					number_format_i18n( (float) $product->get_stock_quantity() )
				),
			),
			409
		);
	}

	WC()->cart->set_quantity( $key, $quantity, true );

	wp_send_json_success( array( 'quantity' => $quantity ) );
}
add_action( 'wp_ajax_simple_bangla_cart_qty', 'simple_bangla_ajax_cart_quantity' );
add_action( 'wp_ajax_nopriv_simple_bangla_cart_qty', 'simple_bangla_ajax_cart_quantity' );
