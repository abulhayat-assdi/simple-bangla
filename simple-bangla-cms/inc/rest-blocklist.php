<?php
/**
 * Editing the order block list.
 *
 * The list itself, and the checkout validation that enforces it, belong to the theme — see
 * `simple-bangla/inc/blocklist.php` for why. This endpoint is only the editor for it, and it calls
 * the theme's own `simple_bangla_save_blocklist()` rather than repeating the normalisation rules.
 * Two copies of "how do you compare a Bangladeshi phone number" would eventually disagree, and the
 * disagreement would show up as a blocked customer who could still order.
 *
 * Because the enforcement is the theme's, the endpoint refuses to run when the theme is not active:
 * writing a list nothing reads would look like it was working.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the block-list routes.
 */
function simple_bangla_cms_register_blocklist_routes() {

	register_rest_route(
		SIMPLE_BANGLA_CMS_REST_NS,
		'/blocklist',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'simple_bangla_cms_blocklist_get',
				'permission_callback' => simple_bangla_cms_permission( 'store.manage' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'simple_bangla_cms_blocklist_save',
				'permission_callback' => simple_bangla_cms_permission( 'store.manage' ),
				'args'                => array(
					'entries' => array(
						'type'        => 'array',
						'required'    => true,
						'description' => __( 'The whole list. Sent complete, not appended to.', 'simple-bangla-cms' ),
					),
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'simple_bangla_cms_register_blocklist_routes' );

/**
 * Whether the theme half of this feature is present.
 *
 * @return WP_Error|true
 */
function simple_bangla_cms_blocklist_ready() {

	if ( ! simple_bangla_cms_theme_is_active() ) {
		return simple_bangla_cms_wrong_theme_error();
	}

	if ( ! function_exists( 'simple_bangla_save_blocklist' ) ) {
		return new WP_Error(
			'sb_cms_blocklist_unavailable',
			__( 'The theme is active but its block list is unavailable, so nothing would enforce the list.', 'simple-bangla-cms' ),
			array( 'status' => 409 )
		);
	}

	return true;
}

/**
 * Return the list.
 *
 * @return WP_REST_Response|WP_Error
 */
function simple_bangla_cms_blocklist_get() {

	$ready = simple_bangla_cms_blocklist_ready();

	if ( is_wp_error( $ready ) ) {
		return $ready;
	}

	return rest_ensure_response(
		array(
			'entries' => array_values( simple_bangla_blocklist() ),
		)
	);
}

/**
 * Replace the list.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function simple_bangla_cms_blocklist_save( $request ) {

	$ready = simple_bangla_cms_blocklist_ready();

	if ( is_wp_error( $ready ) ) {
		return $ready;
	}

	$sent = (array) $request->get_param( 'entries' );

	// The theme drops anything unusable, so comparing counts is how the interface learns that an
	// entry was rejected. Reporting "saved" over a silently discarded row would be a lie the owner
	// only discovers when the blocked customer orders again.
	$stored = simple_bangla_save_blocklist( $sent );

	return rest_ensure_response(
		array(
			'entries'  => array_values( $stored ),
			'sent'     => count( $sent ),
			'rejected' => max( 0, count( $sent ) - count( $stored ) ),
		)
	);
}
