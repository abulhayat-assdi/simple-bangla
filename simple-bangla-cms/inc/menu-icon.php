<?php
/**
 * The one thing `wp/v2/menu-items` cannot already do.
 *
 * WordPress has exposed menus and menu items over REST since 5.9, so the Menu screen needs no
 * endpoint of its own — except for the theme's per-item icon, which lives in a post meta key the
 * theme defines and nothing has registered for REST. Without this the icon would be invisible to
 * the CMS and, worse, a menu item saved from the CMS would keep an icon the owner could neither
 * see nor change.
 *
 * The meta belongs to the theme; only its REST exposure lives here. That keeps the rule the whole
 * project runs on: deactivating this plugin changes nothing about the storefront. The walker goes
 * on reading the same key from the same place, and the Customizer and Appearance → Menus screens
 * go on writing it.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Expose the theme's menu-item icon over REST.
 *
 * Registered on `rest_api_init` rather than `init` because it is only needed for REST, and the
 * theme's constant does not exist until the theme has loaded — which is after this plugin.
 */
function simple_bangla_cms_register_menu_icon_meta() {

	if ( ! defined( 'SIMPLE_BANGLA_MENU_ICON_KEY' ) ) {
		return;
	}

	register_post_meta(
		'nav_menu_item',
		SIMPLE_BANGLA_MENU_ICON_KEY,
		array(
			'type'              => 'integer',
			'description'       => __( 'Attachment ID of the icon shown beside this menu item.', 'simple-bangla-cms' ),
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			/*
			 * The key begins with an underscore, so WordPress treats it as protected and refuses
			 * both reads and writes over REST unless an auth callback says otherwise. The theme
			 * gates the same field on `edit_theme_options` in Appearance → Menus; this is the same
			 * gate, and it is also the capability behind the CMS's `appearance.manage` ability.
			 */
			'auth_callback'     => function () {
				return current_user_can( 'edit_theme_options' );
			},
		)
	);
}
add_action( 'rest_api_init', 'simple_bangla_cms_register_menu_icon_meta' );

/**
 * Report the menu locations the theme registered, and which menu is in each.
 *
 * `wp/v2/menus` names the locations a menu is assigned to, which answers "where does this menu
 * appear?" but not "what locations exist and which are empty?" — and an empty primary location is
 * exactly the state where the storefront shows no navigation at all and the owner needs telling.
 */
function simple_bangla_cms_register_menu_routes() {

	register_rest_route(
		SIMPLE_BANGLA_CMS_REST_NS,
		'/menu-locations',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'simple_bangla_cms_menu_locations',
			'permission_callback' => simple_bangla_cms_permission( 'appearance.manage' ),
		)
	);
}
add_action( 'rest_api_init', 'simple_bangla_cms_register_menu_routes' );

/**
 * The theme's nav menu locations with their assigned menu.
 *
 * @return WP_REST_Response
 */
function simple_bangla_cms_menu_locations() {

	$assigned = get_nav_menu_locations();
	$out      = array();

	foreach ( get_registered_nav_menus() as $slug => $label ) {

		$menu_id = isset( $assigned[ $slug ] ) ? (int) $assigned[ $slug ] : 0;
		$menu    = $menu_id ? wp_get_nav_menu_object( $menu_id ) : false;

		$out[] = array(
			'location'  => $slug,
			'label'     => $label,
			'menu_id'   => $menu ? (int) $menu->term_id : 0,
			'menu_name' => $menu ? $menu->name : '',
		);
	}

	return rest_ensure_response( $out );
}
