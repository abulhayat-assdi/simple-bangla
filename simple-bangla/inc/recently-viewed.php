<?php
/**
 * "Recently viewed" product tracking.
 *
 * Stored in a first-party cookie rather than user meta, because the shoppers who benefit most
 * from it are the ones who never log in. WooCommerce already sets the same kind of cookie for
 * its own recently-viewed widget, so this adds no new privacy surface — but it is deliberately
 * kept to bare product IDs and nothing else.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** Cookie name. */
const SIMPLE_BANGLA_VIEWED_COOKIE = 'simple_bangla_viewed';

/** How many products to remember. */
const SIMPLE_BANGLA_VIEWED_LIMIT = 12;

/**
 * The product IDs the visitor has looked at, newest first.
 *
 * @return int[]
 */
function simple_bangla_viewed_products() {

	if ( empty( $_COOKIE[ SIMPLE_BANGLA_VIEWED_COOKIE ] ) ) {
		return array();
	}

	$raw = sanitize_text_field( wp_unslash( $_COOKIE[ SIMPLE_BANGLA_VIEWED_COOKIE ] ) );
	$ids = array_filter( array_map( 'absint', explode( '|', $raw ) ) );

	return array_slice( array_unique( $ids ), 0, SIMPLE_BANGLA_VIEWED_LIMIT );
}

/**
 * What the list becomes once this product has been seen.
 *
 * Separate from the tracker below so the "has anything actually changed?" question can be asked —
 * and tested — without a request, a query and a cookie header in the way.
 *
 * @param int $product_id Product being viewed.
 * @return int[]
 */
function simple_bangla_viewed_after( $product_id ) {

	$viewed = simple_bangla_viewed_products();

	// Re-visiting a product moves it to the front rather than duplicating it.
	$viewed = array_values( array_diff( $viewed, array( (int) $product_id ) ) );
	array_unshift( $viewed, (int) $product_id );

	return array_slice( $viewed, 0, SIMPLE_BANGLA_VIEWED_LIMIT );
}

/**
 * Record the product currently being viewed.
 *
 * Hooked to template_redirect so it runs before any output — setcookie() after the first byte
 * is a "headers already sent" warning waiting to happen.
 */
function simple_bangla_track_viewed_product() {

	if ( ! function_exists( 'is_product' ) || ! is_product() || is_admin() ) {
		return;
	}

	/**
	 * Whether to record recently-viewed products at all.
	 *
	 * **This exists because of page caching, and the trade-off is worth stating plainly.** Writing a
	 * cookie on the product template means every product page answers with a `Set-Cookie` header,
	 * and most host and CDN page caches refuse to store a response that carries one. On the shop's
	 * single most-visited template that is the difference between a cached page and a full
	 * WordPress boot per visitor.
	 *
	 * The write below is now skipped when the list has not actually changed, which covers repeat
	 * views of the same product, but a shopper moving between products still writes on each one —
	 * that is inherent to a server-rendered strip. A shop that puts a page cache in front of the
	 * storefront and wants the product template cacheable should return false here; the strip then
	 * stops updating and, once the cookie expires, disappears. Nothing else changes.
	 *
	 * @param bool $track Whether to record the view.
	 */
	if ( ! apply_filters( 'simple_bangla_track_recently_viewed', true ) ) {
		return;
	}

	$product_id = get_queried_object_id();

	if ( ! $product_id ) {
		return;
	}

	$viewed = simple_bangla_viewed_after( $product_id );

	// Nothing to write when the visitor is looking at the product that is already at the front of
	// their list — a reload, a back button, a variation change. Re-sending an identical cookie
	// would cost the page its cacheability for no change at all.
	if ( simple_bangla_viewed_products() === $viewed ) {
		return;
	}

	setcookie(
		SIMPLE_BANGLA_VIEWED_COOKIE,
		implode( '|', $viewed ),
		array(
			'expires'  => time() + MONTH_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		)
	);

	// Keep the current request consistent with the cookie it just set.
	$_COOKIE[ SIMPLE_BANGLA_VIEWED_COOKIE ] = implode( '|', $viewed );
}
add_action( 'template_redirect', 'simple_bangla_track_viewed_product' );

/**
 * Render the recently-viewed strip.
 *
 * @param int $exclude Product ID to leave out — usually the one being viewed.
 */
function simple_bangla_recently_viewed( $exclude = 0 ) {

	$ids = array_diff( simple_bangla_viewed_products(), array( (int) $exclude ) );

	// One lonely card is not a "recently viewed" shelf.
	if ( count( $ids ) < 2 ) {
		return;
	}

	$query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'post__in'            => $ids,
			'orderby'             => 'post__in',
			'posts_per_page'      => SIMPLE_BANGLA_VIEWED_LIMIT,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	if ( ! $query->have_posts() ) {
		return;
	}
	?>
	<section class="sb-strip sb-recently-viewed">
		<div class="sb-container">
			<?php
			simple_bangla_section_head( __( 'Recently viewed', 'simple-bangla' ) );
			simple_bangla_render_product_loop( $query, true );
			?>
		</div>
	</section>
	<?php
}
