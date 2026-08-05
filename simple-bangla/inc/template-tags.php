<?php
/**
 * Reusable render helpers.
 *
 * Everything here echoes escaped markup. Later phases add the product card, the mega menu
 * and the homepage section renderers alongside these.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the site logo, or the site name when no logo has been set.
 */
function simple_bangla_site_branding() {

	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}

	$tag = ( is_front_page() && is_home() ) ? 'h1' : 'p';

	printf(
		'<%1$s class="sb-site-title"><a href="%2$s" rel="home">%3$s</a></%1$s>',
		esc_attr( $tag ),
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

/**
 * Render the product-only search form used in the header.
 *
 * Degrades to a plain GET search against WordPress core when JavaScript is off — the
 * AJAX layer added in Phase 2 only ever enhances this form, it does not replace it.
 */
function simple_bangla_product_search_form() {

	// Without WooCommerce there is no product post type, so search everything instead.
	$has_products = post_type_exists( 'product' );
	$field_id     = 'sb-search-' . wp_unique_id();
	?>
	<form role="search" method="get" class="sb-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr( $field_id ); ?>">
			<?php esc_html_e( 'Search products', 'simple-bangla' ); ?>
		</label>
		<input
			type="search"
			id="<?php echo esc_attr( $field_id ); ?>"
			class="sb-search__field"
			name="s"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			placeholder="<?php esc_attr_e( 'Search for products…', 'simple-bangla' ); ?>"
			autocomplete="off"
		/>
		<?php if ( $has_products ) : ?>
			<input type="hidden" name="post_type" value="product" />
		<?php endif; ?>
		<button type="submit" class="sb-search__submit">
			<span class="screen-reader-text"><?php esc_html_e( 'Search', 'simple-bangla' ); ?></span>
			<?php simple_bangla_icon( 'search' ); ?>
		</button>
	</form>
	<?php
}

/**
 * Where the header's account link should point.
 *
 * Prefers the WooCommerce My Account page, falls back to the /user/ page the theme creates
 * on activation, and finally to wp-login.php so the link is never dead.
 *
 * @return string
 */
function simple_bangla_account_url() {

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$account = wc_get_page_permalink( 'myaccount' );

		if ( $account ) {
			return $account;
		}
	}

	$user_page = get_page_by_path( 'user' );

	if ( $user_page ) {
		return (string) get_permalink( $user_page );
	}

	return wp_login_url();
}

/**
 * The theme's inline SVG icon set.
 *
 * Inline rather than an icon font or sprite sheet: a handful of 24px glyphs cost less as
 * markup than an extra request, and they inherit currentColor for free.
 *
 * @return array<string,string> Icon slug => path data.
 */
function simple_bangla_icons() {
	return array(
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'cart'   => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.55L21 7H6"/>',
		'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'menu'   => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'  => '<path d="m6 6 12 12M18 6 6 18"/>',
		'chevron' => '<path d="m9 6 6 6-6 6"/>',
		'arrow-up' => '<path d="M12 20V5m0 0-6 6m6-6 6 6"/>',
		'phone'  => '<path d="M6 3h3l2 5-2.5 1.5a12 12 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2.2 2A16 16 0 0 1 4 5.2 2 2 0 0 1 6 3Z"/>',
		'home'   => '<path d="M4 11 12 4l8 7v8a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1Z"/>',
		'shop'   => '<path d="M4 8h16l-1 12H5Zm4 0V6a4 4 0 0 1 8 0v2"/>',
		'chat'   => '<path d="M21 12a8 8 0 1 1-3.2-6.4M21 12v0M4 20l1.6-3.2"/>',
	);
}

/**
 * Print one inline SVG icon.
 *
 * @param string $name  Icon slug from simple_bangla_icons().
 * @param int    $size  Pixel size for width and height.
 */
function simple_bangla_icon( $name, $size = 24 ) {

	$icons = simple_bangla_icons();

	if ( ! isset( $icons[ $name ] ) ) {
		return;
	}

	printf(
		'<svg class="sb-icon sb-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		absint( $size ),
		// The paths are theme-authored constants, not user input; wp_kses would strip valid SVG.
		$icons[ $name ] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Print the pagination block used by every archive.
 */
function simple_bangla_pagination() {

	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => esc_html__( 'Previous', 'simple-bangla' ),
			'next_text'          => esc_html__( 'Next', 'simple-bangla' ),
			'screen_reader_text' => esc_html__( 'Page navigation', 'simple-bangla' ),
			'class'              => 'sb-pagination',
		)
	);
}

/**
 * Print the meta line under a post title.
 */
function simple_bangla_posted_on() {

	printf(
		'<p class="sb-entry-meta"><time datetime="%1$s">%2$s</time> <span class="sb-entry-meta__sep">·</span> %3$s</p>',
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() ),
		esc_html( get_the_author() )
	);
}

/**
 * Decide which sidebar, if any, the current view should show.
 *
 * @return string Sidebar ID, or an empty string for a full-width layout.
 */
function simple_bangla_active_sidebar_id() {

	$is_shop = function_exists( 'is_woocommerce' ) && is_woocommerce();

	$id = $is_shop ? 'sidebar-shop' : 'sidebar-blog';

	if ( is_page() || is_404() || is_front_page() ) {
		$id = '';
	}

	/**
	 * Filter the sidebar chosen for the current view.
	 *
	 * @param string $id Sidebar ID, or '' for none.
	 */
	$id = apply_filters( 'simple_bangla_active_sidebar_id', $id );

	return ( $id && is_active_sidebar( $id ) ) ? $id : '';
}
