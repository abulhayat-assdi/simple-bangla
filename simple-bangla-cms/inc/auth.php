<?php
/**
 * Who may do what.
 *
 * The CMS never asks WordPress "is this an administrator?". It asks whether the current user
 * holds a named *ability*, and each ability maps onto a real WordPress capability. Today the
 * store has one user and every mapping resolves to something an administrator already has, so
 * the indirection buys nothing — which is exactly why it has to exist now rather than later.
 * When staff accounts arrive, granting an order-desk user `edit_shop_orders` and nothing else
 * produces a correctly locked-down CMS with no changes to any endpoint.
 *
 * The client is never trusted about its own permissions. `/session` reports abilities so the
 * interface can hide what the user cannot use, but every endpoint re-checks server-side; a
 * hidden button is a courtesy, not a control.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every ability the CMS knows about, and the WordPress capability that grants it.
 *
 * WooCommerce registers `edit_shop_orders`, `edit_products` and `manage_woocommerce` itself, so
 * these are real capabilities rather than invented ones — a role built with any WooCommerce role
 * editor will line up with this table without extra work.
 *
 * @return array<string,array{cap:string,label:string}>
 */
function simple_bangla_cms_abilities() {

	$abilities = array(
		/*
		 * The shell's landing screen, and the gate on `/manage` itself. **Derived, not mapped** —
		 * see simple_bangla_cms_can(). It used to map to `read`, which every registered customer on
		 * a WooCommerce store holds, so anyone who had ever placed an order with an account could
		 * open the CMS and read the shop's order counts, catalogue size, stock figures and version
		 * information. Mapping it to a real management capability instead would have picked one:
		 * `manage_woocommerce` shuts out a content editor, `edit_pages` shuts out the order desk.
		 * The honest rule is that whoever can use any screen may see the shell, and nobody else.
		 */
		'dashboard.view'     => array(
			'cap'   => 'read',
			'label' => __( 'See the dashboard', 'simple-bangla-cms' ),
		),
		'orders.view'        => array(
			'cap'   => 'edit_shop_orders',
			'label' => __( 'View orders', 'simple-bangla-cms' ),
		),
		'orders.manage'      => array(
			'cap'   => 'edit_shop_orders',
			'label' => __( 'Change order status and refund', 'simple-bangla-cms' ),
		),
		'products.view'      => array(
			'cap'   => 'edit_products',
			'label' => __( 'View products', 'simple-bangla-cms' ),
		),
		'products.manage'    => array(
			'cap'   => 'edit_products',
			'label' => __( 'Create and edit products', 'simple-bangla-cms' ),
		),
		// There is no customers.view: the Customers screen was removed on 2026-08-09 because a
		// cash-on-delivery shop's customers are its orders, not its registered accounts.
		// Homepage rows, hero, banners, footer, colours — everything stored as a theme_mod.
		'appearance.manage'  => array(
			'cap'   => 'edit_theme_options',
			'label' => __( 'Edit the homepage, footer and colours', 'simple-bangla-cms' ),
		),
		/*
		 * Pages: About Us, Privacy Policy, Delivery & Return and anything else the owner writes.
		 * `edit_pages` rather than `edit_theme_options`, because this is writing content, not
		 * configuring the site — and it is the capability core itself checks on `wp/v2/pages`, so a
		 * user who holds the ability can never be shown a screen every request on it would refuse.
		 */
		'content.manage'     => array(
			'cap'   => 'edit_pages',
			'label' => __( 'Write and edit site pages', 'simple-bangla-cms' ),
		),
		'store.manage'       => array(
			'cap'   => 'manage_woocommerce',
			'label' => __( 'Change store settings', 'simple-bangla-cms' ),
		),
		'staff.manage'       => array(
			'cap'   => 'promote_users',
			'label' => __( 'Add and remove staff accounts', 'simple-bangla-cms' ),
		),
		/*
		 * Reviews are comments, and WooCommerce gates its review endpoints on `moderate_comments`
		 * rather than on any product capability. Mapping them to `products.view` would have shown
		 * the screen to a catalogue editor who then got a 403 from every request on it.
		 */
		'reviews.moderate'   => array(
			'cap'   => 'moderate_comments',
			'label' => __( 'Approve and remove product reviews', 'simple-bangla-cms' ),
		),
	);

	/**
	 * Filter the ability map.
	 *
	 * @param array<string,array{cap:string,label:string}> $abilities Ability => capability map.
	 */
	return apply_filters( 'simple_bangla_cms_abilities', $abilities );
}

/**
 * The ability that is the shell rather than a screen.
 *
 * It gates `/manage`, `/session` and `/dashboard`, and it is the one ability that is not a
 * capability lookup — holding it means holding at least one of the others.
 */
const SIMPLE_BANGLA_CMS_SHELL_ABILITY = 'dashboard.view';

/**
 * Whether the given user holds an ability.
 *
 * An unknown ability is denied rather than allowed. A typo in a `permission_callback` should
 * lock a route, never open one.
 *
 * @param string       $ability Ability key from simple_bangla_cms_abilities().
 * @param WP_User|null $user    User to test, or null for the current user.
 * @return bool
 */
function simple_bangla_cms_can( $ability, $user = null ) {

	$abilities = simple_bangla_cms_abilities();

	if ( ! isset( $abilities[ $ability ] ) ) {
		return false;
	}

	$holds = static function ( $cap ) use ( $user ) {
		return $user ? user_can( $user, $cap ) : current_user_can( $cap );
	};

	if ( SIMPLE_BANGLA_CMS_SHELL_ABILITY === $ability ) {

		if ( ! $holds( $abilities[ $ability ]['cap'] ) ) {
			return false;
		}

		foreach ( $abilities as $key => $spec ) {
			if ( SIMPLE_BANGLA_CMS_SHELL_ABILITY !== $key && $holds( $spec['cap'] ) ) {
				return true;
			}
		}

		return false;
	}

	return $holds( $abilities[ $ability ]['cap'] );
}

/**
 * The abilities the given user holds, as a flat list.
 *
 * @param int $user_id User ID, or 0 for the current user.
 * @return string[]
 */
function simple_bangla_cms_user_abilities( $user_id = 0 ) {

	$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();

	if ( ! $user || ! $user->exists() ) {
		return array();
	}

	$granted = array();

	// Through simple_bangla_cms_can() rather than a second capability loop, so the shell ability's
	// derived rule is applied here too. Reporting it from a straight `user_can( 'read' )` would put
	// a Dashboard item in the sidebar of someone the route then refuses.
	foreach ( array_keys( simple_bangla_cms_abilities() ) as $ability ) {
		if ( simple_bangla_cms_can( $ability, $user ) ) {
			$granted[] = $ability;
		}
	}

	return $granted;
}

/**
 * Build a `permission_callback` for a route.
 *
 * Returns a closure rather than a bare capability string so the 401/403 distinction survives:
 * the interface needs to know whether to show a login screen or an "you do not have access"
 * message, and a plain capability check collapses both into one response.
 *
 * @param string $ability Ability key.
 * @return callable
 */
function simple_bangla_cms_permission( $ability ) {

	return static function () use ( $ability ) {

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'sb_cms_not_logged_in',
				__( 'You are signed out. Sign in again to continue.', 'simple-bangla-cms' ),
				array( 'status' => 401 )
			);
		}

		if ( ! simple_bangla_cms_can( $ability ) ) {
			return new WP_Error(
				'sb_cms_forbidden',
				__( 'Your account does not have permission to do this.', 'simple-bangla-cms' ),
				array( 'status' => 403 )
			);
		}

		return true;
	};
}

/**
 * The error returned when a settings route runs against the wrong active theme.
 *
 * @return WP_Error
 */
function simple_bangla_cms_wrong_theme_error() {
	return new WP_Error(
		'sb_cms_theme_inactive',
		__( 'The Simple Bangla theme is not active, so its settings cannot be read or changed.', 'simple-bangla-cms' ),
		array( 'status' => 409 )
	);
}
