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
 * Two families only. The reference design uses five, which is a performance problem we are
 * deliberately not inheriting. Oswald carries headings and navigation; Lato carries body copy,
 * product titles and buttons.
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

	$families = array();

	if ( 'off' !== $oswald ) {
		$families[] = 'Oswald:wght@400;500;600';
	}

	if ( 'off' !== $lato ) {
		$families[] = 'Lato:wght@400;600;700';
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
 * Enqueue front-end styles and scripts.
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
