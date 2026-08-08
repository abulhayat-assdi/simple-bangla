<?php
/**
 * Who is signed in, and what this installation can do.
 *
 * The interface calls this first. It answers three questions in one round trip: who the user
 * is, which abilities they hold, and whether the environment is healthy enough for the CMS to
 * behave — so a missing theme or a disabled analytics table shows as an explained banner rather
 * than as an empty screen the owner has to guess about.
 *
 * Authentication is WordPress's own login cookie plus the `wp_rest` nonce. There is no token to
 * store, nothing in localStorage for a cross-site script to steal, and no second set of
 * credentials to leak. That is only possible because the CMS is served from the store's own
 * domain, which is the main reason it is.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the session route.
 */
function simple_bangla_cms_register_session_route() {

	register_rest_route(
		SIMPLE_BANGLA_CMS_REST_NS,
		'/session',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'simple_bangla_cms_session_response',
			'permission_callback' => simple_bangla_cms_permission( 'dashboard.view' ),
		)
	);
}
add_action( 'rest_api_init', 'simple_bangla_cms_register_session_route' );

/**
 * Build the session payload.
 *
 * @return WP_REST_Response
 */
function simple_bangla_cms_session_response() {
	return rest_ensure_response( simple_bangla_cms_session_payload() );
}

/**
 * The session as a plain array.
 *
 * Split out from the REST callback because the app shell embeds exactly this payload in its
 * first response. The interface therefore paints without waiting on a round trip, and there is
 * still only one definition of what a session looks like.
 *
 * @return array<string,mixed>
 */
function simple_bangla_cms_session_payload() {

	$user = wp_get_current_user();

	return array(
		'user'        => array(
			'id'           => $user->ID,
			'display_name' => $user->display_name,
			'email'        => $user->user_email,
			'avatar'       => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
			// Shown under the name in the sidebar, the way the reference dashboard does.
			'role_label'   => simple_bangla_cms_role_label( $user ),
		),
		'abilities'   => simple_bangla_cms_user_abilities(),
		// Handed back so a long-lived tab can refresh its nonce before the current one ages out.
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'store'       => array(
			'name'            => get_bloginfo( 'name' ),
			'url'             => home_url( '/' ),
			'currency'        => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
			'currency_symbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '',
			'decimals'        => function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2,
		),
		'environment' => array(
			'plugin_version' => SIMPLE_BANGLA_CMS_VERSION,
			'wp_version'     => get_bloginfo( 'version' ),
			'wc_version'     => defined( 'WC_VERSION' ) ? WC_VERSION : null,
			'theme_active'   => simple_bangla_cms_theme_is_active(),
			'hpos_enabled'   => simple_bangla_cms_hpos_enabled(),
			'wp_admin_url'   => admin_url(),
		),
	);
}

/**
 * A human label for the user's highest role.
 *
 * @param WP_User $user User.
 * @return string
 */
function simple_bangla_cms_role_label( $user ) {

	if ( empty( $user->roles ) ) {
		return '';
	}

	$role  = reset( $user->roles );
	$names = wp_roles()->get_names();

	return isset( $names[ $role ] ) ? translate_user_role( $names[ $role ] ) : ucfirst( $role );
}
