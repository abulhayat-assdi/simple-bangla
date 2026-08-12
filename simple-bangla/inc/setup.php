<?php
/**
 * Theme setup — supports, menus, image sizes, widget areas, first-run pages.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports, navigation menus and image sizes.
 */
function simple_bangla_setup() {

	load_theme_textdomain( 'simple-bangla', SIMPLE_BANGLA_DIR . 'languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'customize-selective-refresh-widgets' );

	add_theme_support(
		'custom-logo',
		array(
			'height'               => 60,
			'width'                => 200,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => false,
		)
	);

	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
			'navigation-widgets',
		)
	);

	/*
	 * WooCommerce.
	 *
	 * The three wc-product-gallery-* supports are deliberately NOT declared. Turning them on
	 * makes WooCommerce load jQuery, flexslider, photoswipe and zoom on every product page —
	 * roughly 90 KB of script to swap an image. The theme ships its own gallery instead
	 * (woocommerce/single-product/product-image.php plus assets/js/product.js), which is a
	 * few hundred bytes of vanilla JS and degrades to a plain list of images without it.
	 */
	add_theme_support( 'woocommerce' );

	// Two footer columns, not three: the footer's Customer Service column was dropped, and a
	// menu location nothing renders is a place to file work that then never appears.
	register_nav_menus(
		array(
			'primary'  => esc_html__( 'Primary Mega Menu', 'simple-bangla' ),
			'footer-1' => esc_html__( 'Footer Column 1', 'simple-bangla' ),
			'footer-2' => esc_html__( 'Footer Column 2', 'simple-bangla' ),
		)
	);

	/*
	 * Image sizes.
	 *
	 * Hard-cropped throughout: the product card and the category circle are both square
	 * by design, and letting WordPress letterbox them would break the grid rhythm.
	 *
	 * Performance note: card images are max ~270px wide on desktop (4-column grid at
	 * 1200px). 400px covers a 1.5× retina phone (270px × 1.5 = 405px) without sending
	 * 6× more bytes than necessary. The hero banner is a separate, wider size because
	 * it spans the full column — the card size would look blurry there.
	 *
	 * After changing these sizes, run "Regenerate Thumbnails" to re-crop existing media.
	 */
	set_post_thumbnail_size( 600, 600, true );
	add_image_size( 'simple-bangla-hero', 1200, 500, true );
	add_image_size( 'simple-bangla-category-icon', 150, 150, true );
	add_image_size( 'simple-bangla-gallery', 600, 600, true );
	add_image_size( 'simple-bangla-card', 400, 400, true );
}
add_action( 'after_setup_theme', 'simple_bangla_setup' );

/**
 * Expose the custom image sizes in the media picker so editors can choose them.
 *
 * @param array $sizes Existing selectable sizes.
 * @return array
 */
function simple_bangla_custom_image_size_names( $sizes ) {
	return array_merge(
		$sizes,
		array(
			'simple-bangla-hero'          => esc_html__( 'Hero Banner (1200×500)', 'simple-bangla' ),
			'simple-bangla-category-icon' => esc_html__( 'Category Icon (150×150)', 'simple-bangla' ),
			'simple-bangla-gallery'       => esc_html__( 'Product Gallery (600×600)', 'simple-bangla' ),
			'simple-bangla-card'          => esc_html__( 'Product Card (400×400)', 'simple-bangla' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'simple_bangla_custom_image_size_names' );

/**
 * Set the global content width used by oEmbeds and wide images.
 */
function simple_bangla_content_width() {
	// Matches --sb-container minus the gutters defined in assets/css/base.css.
	$GLOBALS['content_width'] = apply_filters( 'simple_bangla_content_width', 1160 );
}
add_action( 'after_setup_theme', 'simple_bangla_content_width', 0 );

/**
 * Register widget areas.
 */
function simple_bangla_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Shop Sidebar', 'simple-bangla' ),
			'id'            => 'sidebar-shop',
			'description'   => esc_html__( 'Shown beside the shop and product category archives.', 'simple-bangla' ),
			'before_widget' => '<section id="%1$s" class="sb-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="sb-widget__title">',
			'after_title'   => '</h2>',
		)
	);

	register_sidebar(
		array(
			'name'          => esc_html__( 'Blog Sidebar', 'simple-bangla' ),
			'id'            => 'sidebar-blog',
			'description'   => esc_html__( 'Shown beside posts and non-shop archives.', 'simple-bangla' ),
			'before_widget' => '<section id="%1$s" class="sb-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="sb-widget__title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'simple_bangla_widgets_init' );

/**
 * The static pages the theme's menus and footer link to.
 *
 * Slug => default title. Created once on theme activation so a fresh install has
 * somewhere for every navigation link to land instead of 404ing.
 *
 * @return array<string,string>
 */
function simple_bangla_default_pages() {
	return array(
		'tutorials'           => __( 'Tutorials', 'simple-bangla' ),
		'special-deals'       => __( 'Special Deals', 'simple-bangla' ),
		/*
		 * No user / login / register / logout pages. They were empty placeholders standing in
		 * for an account system this store does not run — every order is a guest
		 * cash-on-delivery order — and the footer no longer links to them.
		 */
		'warranty-policy'     => __( 'Warranty Policy', 'simple-bangla' ),
		'about-us'            => __( 'About Us', 'simple-bangla' ),
		'privacy-policy'      => __( 'Privacy Policy', 'simple-bangla' ),
		'refund_returns'      => __( 'Delivery &amp; Return Policy', 'simple-bangla' ),
		'contact-us'          => __( 'Contact Us', 'simple-bangla' ),
		'terms-and-condition' => __( 'Terms &amp; Conditions', 'simple-bangla' ),
	);
}

/**
 * Create any missing default page on theme activation.
 *
 * Runs once, on switch. Existing pages are never touched, so re-activating the theme
 * cannot overwrite content the store owner has written.
 */
function simple_bangla_create_default_pages() {

	$privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );

	foreach ( simple_bangla_default_pages() as $slug => $title ) {

		$existing = get_page_by_path( $slug );

		if ( $existing ) {

			/*
			 * WordPress creates its own Privacy Policy page as a draft. get_page_by_path()
			 * finds it, so the theme used to skip creating one — and the footer link 404'd on
			 * every fresh install. Publishing that specific page fixes it without ever
			 * touching a page the store owner has deliberately left unpublished.
			 */
			if (
				$existing->ID === $privacy_page_id
				&& in_array( $existing->post_status, array( 'draft', 'auto-draft' ), true )
			) {
				wp_update_post(
					array(
						'ID'          => $existing->ID,
						'post_status' => 'publish',
					)
				);
			}

			continue;
		}

		wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => sprintf(
					/* translators: %s: the page title, e.g. "About Us". */
					esc_html__( 'Placeholder content for the %s page. Edit this page to add your own copy.', 'simple-bangla' ),
					wp_strip_all_tags( $title )
				),
			)
		);
	}
}
add_action( 'after_switch_theme', 'simple_bangla_create_default_pages' );

/**
 * Tell the admin that the theme needs WooCommerce.
 *
 * The theme does not fatal without it — it just loses the shop — so this is a notice,
 * not a hard requirement.
 */
function simple_bangla_woocommerce_notice() {

	if ( class_exists( 'WooCommerce' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Simple Bangla needs WooCommerce for its shop, cart and product features. Everything else works without it.', 'simple-bangla' )
	);
}
add_action( 'admin_notices', 'simple_bangla_woocommerce_notice' );

/**
 * Add a body class describing the current layout so CSS does not have to guess.
 *
 * @param array $classes Existing body classes.
 * @return array
 */
function simple_bangla_body_classes( $classes ) {

	if ( ! is_singular() ) {
		$classes[] = 'sb-archive-view';
	}

	if ( ! is_active_sidebar( 'sidebar-shop' ) && ! is_active_sidebar( 'sidebar-blog' ) ) {
		$classes[] = 'sb-no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'simple_bangla_body_classes' );
