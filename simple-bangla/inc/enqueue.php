<?php
/**
 * Asset registration.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version for a theme asset.
 *
 * Uses the file's modification time when it can be read, so local edits appear immediately
 * without bumping SIMPLE_BANGLA_VERSION on every save. Falls back to the theme version.
 *
 * @param string $relative_path Path relative to the theme root, e.g. 'assets/css/base.css'.
 * @return string
 */
function simple_bangla_asset_version( $relative_path ) {

	$absolute = SIMPLE_BANGLA_DIR . ltrim( $relative_path, '/' );

	if ( file_exists( $absolute ) ) {
		return (string) filemtime( $absolute );
	}

	return SIMPLE_BANGLA_VERSION;
}

/**
 * The Google Fonts stylesheet URL, or an empty string when web fonts are switched off.
 *
 * Three families. The reference uses five; Chelsea Market is the one deliberately not
 * inherited, because it is a display face with no Bengali glyphs and this theme has to stay
 * Bangla-ready. Oswald carries headings and navigation, Lato carries body copy and buttons,
 * Raleway carries the footer column headings so they match the reference exactly.
 *
 * @return string
 */
function simple_bangla_fonts_url() {

	/**
	 * Filter whether the theme loads Google Fonts at all.
	 *
	 * Returning false drops both requests and falls back to the system stack declared
	 * in the --sb-font-* custom properties.
	 *
	 * @param bool $enabled Default true.
	 */
	if ( ! apply_filters( 'simple_bangla_enable_google_fonts', true ) ) {
		return '';
	}

	/*
	 * translators: If Oswald does not render well in your language, translate this to 'off'.
	 * Do not translate into your own alphabet.
	 */
	$oswald = _x( 'on', 'Oswald font: on or off', 'simple-bangla' );

	/*
	 * translators: If Lato does not render well in your language, translate this to 'off'.
	 * Do not translate into your own alphabet.
	 */
	$lato = _x( 'on', 'Lato font: on or off', 'simple-bangla' );

	/*
	 * translators: If Raleway does not render well in your language, translate this to 'off'.
	 * Do not translate into your own alphabet.
	 */
	$raleway = _x( 'on', 'Raleway font: on or off', 'simple-bangla' );

	$families = array();

	if ( 'off' !== $oswald ) {
		$families[] = 'Oswald:wght@400;500;600';
	}

	if ( 'off' !== $lato ) {
		$families[] = 'Lato:wght@400;600;700';
	}

	// Only weight 900 — the footer headings are the sole place Raleway appears.
	if ( 'off' !== $raleway ) {
		$families[] = 'Raleway:wght@900';
	}

	/*
	 * translators: If Baskervville does not render well in your language, translate this to
	 * 'off'. Do not translate into your own alphabet.
	 */
	$baskervville = _x( 'on', 'Baskervville font: on or off', 'simple-bangla' );

	// Google ships Baskervville in one weight; the reference faux-bolds it to 600 and so do we.
	if ( 'off' !== $baskervville ) {
		$families[] = 'Baskervville';
	}

	if ( empty( $families ) ) {
		return '';
	}

	return add_query_arg(
		array(
			'family'  => implode( '&family=', $families ),
			'display' => 'swap',
		),
		'https://fonts.googleapis.com/css2'
	);
}

/**
 * Is the current request a WooCommerce archive that renders product cards?
 *
 * @return bool
 */
function simple_bangla_is_product_listing() {

	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}

	return is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy();
}

/**
 * Register one theme stylesheet.
 *
 * Every sheet depends on the base one, so the cascade order is declared rather than
 * accidental — tokens and the reset always land first.
 *
 * @param string $handle   Handle suffix, e.g. 'header'.
 * @param string $filename File under assets/css/.
 */
function simple_bangla_enqueue_style( $handle, $filename ) {

	wp_enqueue_style(
		'simple-bangla-' . $handle,
		SIMPLE_BANGLA_URI . 'assets/css/' . $filename,
		array( 'simple-bangla-base' ),
		simple_bangla_asset_version( 'assets/css/' . $filename )
	);
}

/**
 * Register one theme script.
 *
 * @param string $handle   Handle suffix, e.g. 'header'.
 * @param string $filename File under assets/js/.
 */
function simple_bangla_enqueue_script( $handle, $filename ) {

	wp_enqueue_script(
		'simple-bangla-' . $handle,
		SIMPLE_BANGLA_URI . 'assets/js/' . $filename,
		array(),
		simple_bangla_asset_version( 'assets/js/' . $filename ),
		true
	);
}

/**
 * Enqueue front-end styles and scripts.
 *
 * Sheets and scripts are split by view so a shopper on a product page never downloads the
 * homepage slider or the shop filter code. Only base, header and footer are unconditional.
 */
function simple_bangla_enqueue_assets() {

	$fonts_url = simple_bangla_fonts_url();

	if ( $fonts_url ) {
		// esc_url_raw, not esc_url: this is a URL being handed to an API, not printed as markup.
		wp_enqueue_style( 'simple-bangla-fonts', esc_url_raw( $fonts_url ), array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google serves its own versioned CSS.
	}

	wp_enqueue_style(
		'simple-bangla-base',
		SIMPLE_BANGLA_URI . 'assets/css/base.css',
		array(),
		simple_bangla_asset_version( 'assets/css/base.css' )
	);

	// Only the colours the store owner actually changed; empty on a default install.
	$palette = simple_bangla_palette_css();

	if ( $palette ) {
		wp_add_inline_style( 'simple-bangla-base', $palette );
	}

	simple_bangla_enqueue_style( 'header', 'header.css' );
	simple_bangla_enqueue_style( 'footer', 'footer.css' );

	$is_listing = simple_bangla_is_product_listing();
	$is_product = function_exists( 'is_product' ) && is_product();
	$is_home    = is_front_page();

	// The card appears on the homepage rows, every archive, search results and the
	// "related products" strip, so its sheet follows all four.
	if ( $is_home || $is_listing || $is_product || is_search() ) {
		simple_bangla_enqueue_style( 'card', 'card.css' );
	}

	if ( $is_home ) {
		simple_bangla_enqueue_style( 'home', 'home.css' );
	}

	// Strips appear on the homepage rows, under a product (related + recently viewed) and
	// under the shop archive (recently viewed), so the arrows follow all three.
	if ( $is_home || $is_product || $is_listing ) {
		simple_bangla_enqueue_script( 'slider', 'slider.js' );
		wp_localize_script(
			'simple-bangla-slider',
			'simpleBanglaSlider',
			array(
				'i18n' => array(
					'prev'  => __( 'Previous products', 'simple-bangla' ),
					'next'  => __( 'Next products', 'simple-bangla' ),
					'pages' => __( 'Product pages', 'simple-bangla' ),
					'page'  => __( 'Page', 'simple-bangla' ),
				),
			)
		);
	}

	if ( $is_listing ) {
		simple_bangla_enqueue_style( 'shop', 'shop.css' );
		simple_bangla_enqueue_script( 'shop', 'shop.js' );
		wp_localize_script(
			'simple-bangla-shop',
			'simpleBanglaShop',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'simple_bangla_filter' ),
				'i18n'    => array(
					'loading' => __( 'Loading…', 'simple-bangla' ),
					'noMore'  => __( 'That is everything.', 'simple-bangla' ),
					'error'   => __( 'Something went wrong. Please try again.', 'simple-bangla' ),
				),
			)
		);
	}

	// Cart, checkout and the order-received page share one sheet.
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		simple_bangla_enqueue_style( 'checkout', 'checkout.css' );
	}

	if ( $is_product ) {
		simple_bangla_enqueue_style( 'product', 'product.css' );
		simple_bangla_enqueue_script( 'product', 'product.js' );
	}

	simple_bangla_enqueue_script( 'header', 'header.js' );
	wp_localize_script(
		'simple-bangla-header',
		'simpleBanglaHeader',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'simple_bangla_search' ),
			'i18n'    => array(
				'noResults' => __( 'No products matched.', 'simple-bangla' ),
				'viewAll'   => __( 'See all results', 'simple-bangla' ),
			),
		)
	);

	simple_bangla_enqueue_script( 'ui', 'ui.js' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'simple_bangla_enqueue_assets' );

/**
 * Warm up the Google Fonts connections before the stylesheet is parsed.
 *
 * Saves a DNS lookup plus a TLS handshake on first paint, which is most of the cost of
 * a web font on a slow mobile connection.
 *
 * @param array  $urls           URLs already queued for this hint.
 * @param string $relation_type  The hint being generated.
 * @return array
 */
function simple_bangla_resource_hints( $urls, $relation_type ) {

	if ( 'preconnect' !== $relation_type || ! wp_style_is( 'simple-bangla-fonts', 'enqueued' ) ) {
		return $urls;
	}

	$urls[] = array(
		'href' => 'https://fonts.gstatic.com',
		'crossorigin',
	);

	return $urls;
}
add_filter( 'wp_resource_hints', 'simple_bangla_resource_hints', 10, 2 );

/**
 * Add `defer` to theme scripts.
 *
 * Theme JavaScript is progressive enhancement only — nothing renders because of it — so it
 * never needs to block parsing. Third-party and WooCommerce scripts are left alone, because
 * their ordering assumptions are not ours to change.
 *
 * @param string $tag    The full <script> tag.
 * @param string $handle Registered handle.
 * @return string
 */
function simple_bangla_defer_scripts( $tag, $handle ) {

	if ( 0 !== strpos( $handle, 'simple-bangla-' ) || false !== strpos( $tag, ' defer' ) ) {
		return $tag;
	}

	return str_replace( ' src=', ' defer src=', $tag );
}
add_filter( 'script_loader_tag', 'simple_bangla_defer_scripts', 10, 2 );
