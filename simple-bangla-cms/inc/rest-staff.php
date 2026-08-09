<?php
/**
 * Staff accounts.
 *
 * `wp/v2/users` can list, create and delete users, so this is one of the few places the project
 * re-implements something core already offers — and the reason is the guards, not the CRUD. The
 * rules below are what stop a store owner from locking themselves out of their own shop on a phone,
 * and core's endpoint enforces none of them:
 *
 * - You cannot change your own role, and you cannot delete yourself.
 * - The last administrator cannot be removed or demoted.
 * - Only an administrator can hand out the administrator role.
 *
 * Enforcing these with filters instead would have applied them to wp-admin too, which is the escape
 * hatch this whole project depends on staying unchanged.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * The roles the CMS is willing to manage.
 *
 * A deliberately short list. WordPress ships eight or so, most of which describe a magazine rather
 * than a shop, and offering `author` on a store's staff screen invites a choice that means nothing
 * here.
 *
 * @return array<string,string> Role slug => label.
 */
function simple_bangla_cms_staff_roles() {

	$roles = array(
		'administrator' => __( 'Administrator — full access, including WordPress itself', 'simple-bangla-cms' ),
		'shop_manager'  => __( 'Shop manager — orders, products and store settings', 'simple-bangla-cms' ),
		'customer'      => __( 'Customer — can order, no management access', 'simple-bangla-cms' ),
	);

	// shop_manager only exists while WooCommerce is active; offering it otherwise would create a
	// user with a role that grants nothing.
	if ( ! get_role( 'shop_manager' ) ) {
		unset( $roles['shop_manager'] );
	}

	return $roles;
}

/**
 * Register the staff routes.
 */
function simple_bangla_cms_register_staff_routes() {

	register_rest_route(
		SIMPLE_BANGLA_CMS_REST_NS,
		'/staff',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'simple_bangla_cms_staff_list',
				'permission_callback' => simple_bangla_cms_permission( 'staff.manage' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'simple_bangla_cms_staff_create',
				'permission_callback' => simple_bangla_cms_permission( 'staff.manage' ),
				'args'                => array(
					'username' => array( 'type' => 'string', 'required' => true ),
					'email'    => array( 'type' => 'string', 'required' => true ),
					'name'     => array( 'type' => 'string', 'required' => false ),
					'role'     => array( 'type' => 'string', 'required' => true ),
				),
			),
		)
	);

	register_rest_route(
		SIMPLE_BANGLA_CMS_REST_NS,
		'/staff/(?P<id>\d+)',
		array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'simple_bangla_cms_staff_update',
				'permission_callback' => simple_bangla_cms_permission( 'staff.manage' ),
				'args'                => array(
					'role' => array( 'type' => 'string', 'required' => true ),
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => 'simple_bangla_cms_staff_delete',
				'permission_callback' => simple_bangla_cms_permission( 'staff.manage' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'simple_bangla_cms_register_staff_routes' );

/**
 * One user, as the interface needs it.
 *
 * @param WP_User $user User.
 * @return array
 */
function simple_bangla_cms_staff_payload( $user ) {

	$roles = simple_bangla_cms_staff_roles();
	$role  = isset( $user->roles[0] ) ? $user->roles[0] : '';

	global $wp_roles;
	$label = isset( $wp_roles->role_names[ $role ] ) ? translate_user_role( $wp_roles->role_names[ $role ] ) : $role;

	return array(
		'id'         => (int) $user->ID,
		'username'   => $user->user_login,
		'name'       => $user->display_name,
		'email'      => $user->user_email,
		'role'       => $role,
		'role_label' => $label,
		'avatar'     => get_avatar_url( $user->ID, array( 'size' => 48 ) ),
		'registered' => $user->user_registered,
		'is_self'    => get_current_user_id() === (int) $user->ID,
		// True when this role is outside the short list above, which means the CMS can show the
		// account but must not offer to change it into something it does not understand.
		'unmanaged'  => $role && ! isset( $roles[ $role ] ),
	);
}

/**
 * Everyone with management access, plus the acting user.
 *
 * Customers are excluded: there can be tens of thousands of them and they have their own screen.
 *
 * @return WP_REST_Response
 */
function simple_bangla_cms_staff_list() {

	$users = get_users(
		array(
			'role__not_in' => array( 'customer', 'subscriber' ),
			'orderby'      => 'display_name',
			'number'       => 200,
		)
	);

	$out = array();

	foreach ( $users as $user ) {
		$out[] = simple_bangla_cms_staff_payload( $user );
	}

	return rest_ensure_response(
		array(
			'staff'       => $out,
			'roles'       => simple_bangla_cms_staff_roles(),
			'can_grant_admin' => current_user_can( 'manage_options' ),
			'admin_count' => count( get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) ),
		)
	);
}

/**
 * Whether the acting user may assign this role at all.
 *
 * @param string $role Role slug.
 * @return WP_Error|true
 */
function simple_bangla_cms_check_role( $role ) {

	if ( ! isset( simple_bangla_cms_staff_roles()[ $role ] ) ) {
		return new WP_Error(
			'sb_cms_bad_role',
			__( 'That is not a role this screen manages.', 'simple-bangla-cms' ),
			array( 'status' => 400 )
		);
	}

	if ( 'administrator' === $role && ! current_user_can( 'manage_options' ) ) {
		return new WP_Error(
			'sb_cms_cannot_grant_admin',
			__( 'Only an administrator can make someone else an administrator.', 'simple-bangla-cms' ),
			array( 'status' => 403 )
		);
	}

	return true;
}

/**
 * Load a staff user for editing, refusing the cases that would be a mistake.
 *
 * @param int    $id     User ID.
 * @param string $action 'update' or 'delete', for the message.
 * @return WP_User|WP_Error
 */
function simple_bangla_cms_staff_target( $id, $action ) {

	$user = get_userdata( (int) $id );

	if ( ! $user ) {
		return new WP_Error( 'sb_cms_no_user', __( 'That account no longer exists.', 'simple-bangla-cms' ), array( 'status' => 404 ) );
	}

	/*
	 * Self-service is the whole risk here. Demoting yourself on a phone, in a shop with one
	 * account, ends with nobody able to reach either the CMS or wp-admin.
	 */
	if ( get_current_user_id() === (int) $user->ID ) {
		return new WP_Error(
			'sb_cms_not_self',
			'delete' === $action
				? __( 'You cannot remove your own account.', 'simple-bangla-cms' )
				: __( 'You cannot change your own role. Ask another administrator to do it.', 'simple-bangla-cms' ),
			array( 'status' => 400 )
		);
	}

	return $user;
}

/**
 * Whether removing or demoting this user would leave the shop with no administrator.
 *
 * @param WP_User $user     The user being changed.
 * @param string  $new_role The role they would end up with, or '' when being deleted.
 * @return WP_Error|true
 */
function simple_bangla_cms_check_last_admin( $user, $new_role = '' ) {

	if ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
		return true;
	}

	if ( 'administrator' === $new_role ) {
		return true;
	}

	$admins = get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) );

	if ( count( $admins ) > 1 ) {
		return true;
	}

	return new WP_Error(
		'sb_cms_last_admin',
		__( 'This is the only administrator. Make someone else an administrator first.', 'simple-bangla-cms' ),
		array( 'status' => 400 )
	);
}

/**
 * Create a staff account.
 *
 * No password is set or sent here. WordPress emails the new user a link to choose their own, which
 * keeps the password out of this request, out of the response, and out of whatever the owner would
 * otherwise have typed it into to pass it on.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function simple_bangla_cms_staff_create( $request ) {

	$role  = sanitize_key( $request->get_param( 'role' ) );
	$check = simple_bangla_cms_check_role( $role );

	if ( is_wp_error( $check ) ) {
		return $check;
	}

	$username = sanitize_user( $request->get_param( 'username' ), true );
	$email    = sanitize_email( $request->get_param( 'email' ) );

	if ( ! $username ) {
		return new WP_Error( 'sb_cms_bad_username', __( 'That username cannot be used.', 'simple-bangla-cms' ), array( 'status' => 400 ) );
	}

	if ( ! is_email( $email ) ) {
		return new WP_Error( 'sb_cms_bad_email', __( 'That is not a valid email address.', 'simple-bangla-cms' ), array( 'status' => 400 ) );
	}

	if ( username_exists( $username ) ) {
		return new WP_Error( 'sb_cms_username_taken', __( 'That username is already taken.', 'simple-bangla-cms' ), array( 'status' => 400 ) );
	}

	if ( email_exists( $email ) ) {
		return new WP_Error( 'sb_cms_email_taken', __( 'An account already uses that email address.', 'simple-bangla-cms' ), array( 'status' => 400 ) );
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => wp_generate_password( 24, true, true ),
			'display_name' => sanitize_text_field( (string) $request->get_param( 'name' ) ) ?: $username,
			'role'         => $role,
		)
	);

	if ( is_wp_error( $user_id ) ) {
		return new WP_Error( 'sb_cms_create_failed', $user_id->get_error_message(), array( 'status' => 400 ) );
	}

	// 'user' sends the "set your password" email; the admin copy is not wanted for an account the
	// owner just created on purpose.
	wp_new_user_notification( $user_id, null, 'user' );

	return rest_ensure_response( simple_bangla_cms_staff_payload( get_userdata( $user_id ) ) );
}

/**
 * Change a staff member's role.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function simple_bangla_cms_staff_update( $request ) {

	$user = simple_bangla_cms_staff_target( $request->get_param( 'id' ), 'update' );

	if ( is_wp_error( $user ) ) {
		return $user;
	}

	$role  = sanitize_key( $request->get_param( 'role' ) );
	$check = simple_bangla_cms_check_role( $role );

	if ( is_wp_error( $check ) ) {
		return $check;
	}

	$check = simple_bangla_cms_check_last_admin( $user, $role );

	if ( is_wp_error( $check ) ) {
		return $check;
	}

	$user->set_role( $role );

	return rest_ensure_response( simple_bangla_cms_staff_payload( get_userdata( $user->ID ) ) );
}

/**
 * Remove a staff account.
 *
 * Their posts are reassigned to the acting user rather than deleted. Orders are unaffected either
 * way — WooCommerce keys those to the customer, not to whoever processed them.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function simple_bangla_cms_staff_delete( $request ) {

	$user = simple_bangla_cms_staff_target( $request->get_param( 'id' ), 'delete' );

	if ( is_wp_error( $user ) ) {
		return $user;
	}

	$check = simple_bangla_cms_check_last_admin( $user );

	if ( is_wp_error( $check ) ) {
		return $check;
	}

	if ( ! current_user_can( 'delete_users' ) ) {
		return new WP_Error( 'sb_cms_cannot_delete', __( 'Your account cannot remove users.', 'simple-bangla-cms' ), array( 'status' => 403 ) );
	}

	// wp_delete_user() lives in wp-admin and is not loaded on a REST request.
	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

	$deleted = wp_delete_user( $user->ID, get_current_user_id() );

	if ( ! $deleted ) {
		return new WP_Error( 'sb_cms_delete_failed', __( 'That account could not be removed.', 'simple-bangla-cms' ), array( 'status' => 500 ) );
	}

	return rest_ensure_response( array( 'deleted' => true, 'id' => (int) $user->ID ) );
}
