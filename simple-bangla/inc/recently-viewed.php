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
 * Record the product currently being viewed.
 *
 * Hooked to template_redirect so it runs before any output — setcookie() after the first byte
 * is a "headers already sent" warning waiting to happen.
 */
function simple_bangla_track_viewed_product() {

	if ( ! function_exists( 'is_product' ) || ! is_product() || is_admin() ) {
		return;
	}

	$product_id = get_queried_object_id();

	if ( ! $product_id ) {
		return;
	}

	$viewed = simple_bangla_viewed_products();

	// Re-visiting a product moves it to the front rather than duplicating it.
	$viewed = array_values( array_diff( $viewed, array( $product_id ) ) );
	array_unshift( $viewed, $product_id );
	$viewed = array_slice( $viewed, 0, SIMPLE_BANGLA_VIEWED_LIMIT );

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
