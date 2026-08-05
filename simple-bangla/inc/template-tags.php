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
	$results_id   = $field_id . '-results';
	?>
	<div class="sb-search-wrap" data-sb-search>
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
				role="combobox"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $results_id ); ?>"
				aria-autocomplete="list"
			/>
			<?php if ( $has_products ) : ?>
				<input type="hidden" name="post_type" value="product" />
			<?php endif; ?>
			<button type="submit" class="sb-search__submit">
				<span class="screen-reader-text"><?php esc_html_e( 'Search', 'simple-bangla' ); ?></span>
				<?php simple_bangla_icon( 'search' ); ?>
			</button>
		</form>

		<?php // Filled by assets/js/search.js. Stays empty — and harmless — without JavaScript. ?>
		<div class="sb-search__results" id="<?php echo esc_attr( $results_id ); ?>" role="listbox" hidden></div>
	</div>
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
		'chevron-left' => '<path d="m15 6-6 6 6 6"/>',
		'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
		'arrow-up' => '<path d="M12 20V5m0 0-6 6m6-6 6 6"/>',
		'phone'  => '<path d="M6 3h3l2 5-2.5 1.5a12 12 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2.2 2A16 16 0 0 1 4 5.2 2 2 0 0 1 6 3Z"/>',
		'home'   => '<path d="M4 11 12 4l8 7v8a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1Z"/>',
		'shop'   => '<path d="M4 8h16l-1 12H5Zm4 0V6a4 4 0 0 1 8 0v2"/>',
		'chat'   => '<path d="M21 12a8 8 0 1 1-3.2-6.4M21 12v0M4 20l1.6-3.2"/>',
		'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
		'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'pin'    => '<path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
		'truck'  => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>',
		'shield' => '<path d="M12 3l7 3v5.5c0 4.2-2.9 7.6-7 9.5-4.1-1.9-7-5.3-7-9.5V6Z"/><path d="m9 12 2 2 4-4"/>',
		'refresh' => '<path d="M20 11A8 8 0 0 0 6.3 6.3L4 8.5"/><path d="M4 4v4.5h4.5"/><path d="M4 13a8 8 0 0 0 13.7 4.7L20 15.5"/><path d="M20 20v-4.5h-4.5"/>',
		'check'  => '<path d="m5 12 5 5L20 7"/>',
		'plus'   => '<path d="M12 5v14M5 12h14"/>',
		'minus'  => '<path d="M5 12h14"/>',
		// Brand marks are filled shapes, so they are drawn with fill and no stroke.
		'whatsapp' => '<path fill="currentColor" stroke="none" d="M12.04 2A9.9 9.9 0 0 0 2.1 11.9a9.8 9.8 0 0 0 1.35 4.97L2 22l5.28-1.38a9.9 9.9 0 0 0 4.76 1.21h.01A9.9 9.9 0 0 0 22 11.93 9.9 9.9 0 0 0 12.04 2Zm5.8 14.06c-.24.69-1.42 1.32-1.96 1.36-.5.05-.98.24-3.3-.7-2.79-1.13-4.55-3.98-4.69-4.17-.13-.18-1.11-1.5-1.11-2.86 0-1.36.7-2.02.95-2.3.25-.27.55-.34.73-.34.18 0 .37 0 .53.01.17.01.4-.06.62.49.24.58.8 2 .87 2.15.07.14.12.31.02.5-.1.18-.15.3-.29.46-.15.16-.31.36-.44.49-.15.14-.3.3-.13.6.18.29.78 1.29 1.67 2.09 1.15 1.02 2.12 1.34 2.42 1.49.3.15.47.12.65-.07.18-.2.75-.87.95-1.17.2-.3.4-.25.66-.15.27.1 1.68.79 1.97.93.29.15.48.22.55.34.07.13.07.72-.17 1.4Z"/>',
		'facebook' => '<path fill="currentColor" stroke="none" d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.45 2.89h-2.33v6.99A10 10 0 0 0 22 12Z"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor" stroke="none"/>',
		'youtube' => '<path fill="currentColor" stroke="none" d="M21.6 7.2a2.5 2.5 0 0 0-1.77-1.77C18.25 5 12 5 12 5s-6.25 0-7.83.43A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.77 1.77C5.75 19 12 19 12 19s6.25 0 7.83-.43a2.5 2.5 0 0 0 1.77-1.77A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8ZM10 15.1V8.9l5.2 3.1Z"/>',
	);
}

/**
 * Print one inline SVG icon.
 *
 * @param string $name  Icon slug from simple_bangla_icons().
 * @param int    $size  Pixel size for width and height.
 */
function simple_bangla_icon( $name, $size = 24 ) {

	// The paths are theme-authored constants, not user input; wp_kses would strip valid SVG.
	echo simple_bangla_get_icon( $name, $size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return one inline SVG icon instead of printing it.
 *
 * @param string $name Icon slug from simple_bangla_icons().
 * @param int    $size Pixel size for width and height.
 * @return string Empty string for an unknown slug.
 */
function simple_bangla_get_icon( $name, $size = 24 ) {

	$icons = simple_bangla_icons();

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg class="sb-icon sb-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		absint( $size ),
		$icons[ $name ]
	);
}

/**
 * Print the header cart link.
 *
 * Renders nothing without WooCommerce — there is no cart to link to, and a dead basket icon
 * is worse than no icon. The count and total are wrapped in their own elements so WooCommerce's
 * fragment refresh can swap them after an AJAX add-to-cart without redrawing the whole link.
 */
function simple_bangla_cart_link() {

	if ( ! function_exists( 'wc_get_cart_url' ) || ! WC()->cart ) {
		return;
	}
	?>
	<a class="sb-header__action sb-cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
		<span class="sb-cart-link__icon">
			<?php simple_bangla_icon( 'cart' ); ?>
			<?php simple_bangla_cart_count(); ?>
		</span>
		<span class="sb-header__action-label">
			<?php simple_bangla_cart_total(); ?>
			<span class="sb-cart-link__word"><?php esc_html_e( 'Cart', 'simple-bangla' ); ?></span>
		</span>
	</a>
	<?php
}

/**
 * The item-count bubble on the cart link. Also used as a cart fragment.
 */
function simple_bangla_cart_count() {

	$count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

	printf(
		'<span class="sb-cart-link__count"%1$s>%2$s<span class="screen-reader-text"> %3$s</span></span>',
		$count ? '' : ' data-empty="1"',
		esc_html( number_format_i18n( $count ) ),
		esc_html(
			sprintf(
				/* translators: %s: number of items in the cart. */
				_n( 'item in cart', 'items in cart', $count, 'simple-bangla' ),
				$count
			)
		)
	);
}

/**
 * The cart subtotal on the cart link. Also used as a cart fragment.
 */
function simple_bangla_cart_total() {

	$total = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_subtotal() : '';

	printf(
		'<span class="sb-cart-link__total">%s</span>',
		// get_cart_subtotal() returns WooCommerce's own price markup, which is trusted.
		wp_kses_post( $total )
	);
}

/**
 * The current product listing's own URL, with every filter stripped off.
 *
 * Rebuilt from the queried object rather than by editing the request, so "clear filters"
 * always lands on a clean, canonical archive URL.
 *
 * @return string
 */
function simple_bangla_listing_base_url() {

	$term = get_queried_object();

	if ( $term instanceof WP_Term ) {

		$link = get_term_link( $term );

		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'shop' );
	}

	return home_url( '/' );
}

/**
 * Build a product query for a homepage row or strip.
 *
 * @param array $args {
 *     @type int    $term_id  product_cat term ID, 0 for any.
 *     @type int    $count    How many products.
 *     @type bool   $on_sale  Restrict to products currently on sale.
 *     @type string $orderby  WP_Query orderby.
 * }
 * @return WP_Query|null Null when WooCommerce is not available.
 */
function simple_bangla_product_query( $args = array() ) {

	if ( ! post_type_exists( 'product' ) ) {
		return null;
	}

	$args = wp_parse_args(
		$args,
		array(
			'term_id' => 0,
			'count'   => 12,
			'on_sale' => false,
			'orderby' => 'date',
		)
	);

	$query_args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => (int) $args['count'],
		'orderby'             => $args['orderby'],
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	// Respect the store's "hide out of stock" and catalog visibility settings.
	if ( function_exists( 'WC' ) ) {
		$query_args['tax_query'] = WC()->query->get_tax_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	if ( $args['term_id'] ) {
		$query_args['tax_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => (int) $args['term_id'],
			'include_children' => true,
		);
	}

	if ( $args['on_sale'] && function_exists( 'wc_get_product_ids_on_sale' ) ) {

		$on_sale = wc_get_product_ids_on_sale();

		// post__in with an empty array returns everything, which is the opposite of the intent.
		$query_args['post__in'] = $on_sale ? $on_sale : array( 0 );
	}

	return new WP_Query( $query_args );
}

/**
 * Render a set of product cards.
 *
 * @param WP_Query $query  Query holding the products.
 * @param bool     $slider Render as a horizontal snap track rather than a grid.
 */
function simple_bangla_render_product_loop( $query, $slider = false ) {

	if ( ! $query instanceof WP_Query || ! $query->have_posts() ) {
		return;
	}

	$classes = 'sb-products' . ( $slider ? ' sb-products--slider' : '' );

	if ( $slider ) {
		echo '<div class="sb-slider" data-sb-slider>';
	}

	printf( '<ul class="%s">', esc_attr( $classes ) );

	while ( $query->have_posts() ) {
		$query->the_post();

		// wc_get_template_part respects the theme override in woocommerce/content-product.php
		// and keeps the $product global set up for it.
		wc_get_template_part( 'content', 'product' );
	}

	echo '</ul>';

	if ( $slider ) {
		echo '</div>';
	}

	wp_reset_postdata();
}

/**
 * Print a section heading with an optional "View All" button.
 *
 * @param string $title Heading text.
 * @param string $url   Destination for the button; omit to hide it.
 */
function simple_bangla_section_head( $title, $url = '' ) {

	if ( '' === trim( $title ) && ! $url ) {
		return;
	}

	echo '<div class="sb-section-head">';

	printf( '<h2 class="sb-section-head__title">%s</h2>', esc_html( $title ) );

	if ( $url ) {
		printf(
			'<a class="sb-section-head__link" href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'View All', 'simple-bangla' )
		);
	}

	echo '</div>';
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
