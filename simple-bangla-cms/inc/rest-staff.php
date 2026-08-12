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
					// The email is the identity; the username is derived from it when not sent, so
					// the owner is asked for one thing rather than two that must not collide.
					'email'    => array( 'type' => 'string', 'required' => true ),
					'password' => array( 'type' => 'string', 'required' => true ),
					'name'     => array( 'type' => 'string', 'required' => false ),
					'username' => array( 'type' => 'string', 'required' => false ),
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
		/*
		 * Whether the acting user may touch *this* account. Both are meta capabilities resolved
		 * against the target, so they answer "may I edit the owner's account", which the route's
		 * own `promote_users` check cannot. The endpoint enforces the same two; these exist so the
		 * screen does not offer a control that is going to be refused.
		 */
		'can_edit'   => current_user_can( 'promote_user', $user->ID ),
		'can_delete' => current_user_can( 'delete_user', $user->ID ),
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
			// The roles this user may actually hand out, not every role the screen knows about. A
			// shop manager offered `shop_manager` here would pick it and be refused on save.
			'roles'       => simple_bangla_cms_assignable_roles(),
			'can_grant_admin' => current_user_can( 'manage_options' ),
			'admin_count' => count( get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) ),
		)
	);
}

/**
 * The roles WordPress will let the acting user hand out.
 *
 * `get_editable_roles()` is the same list wp-admin's own user screens are built from, and consulting
 * it is what makes the rules below WordPress's rules rather than this plugin's guesses. WooCommerce
 * filters it: a shop manager is offered `customer` and nothing else, so a shop manager cannot make
 * a second shop manager here any more than they can in wp-admin.
 *
 * It lives in wp-admin/includes/user.php, which a REST request has not loaded.
 *
 * @return array<string,string> Role slug => label, restricted to the ones this screen manages.
 */
function simple_bangla_cms_assignable_roles() {

	if ( ! function_exists( 'get_editable_roles' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

	return array_intersect_key( simple_bangla_cms_staff_roles(), get_editable_roles() );
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

	/*
	 * The general form of the check above, and the one that catches everything it does not.
	 * `staff.manage` maps to `promote_users`, which WooCommerce grants to every shop manager — so
	 * without this a shop manager could hand out `shop_manager`, and a shop with one trusted
	 * order-desk account would be one screen away from having several.
	 */
	if ( ! isset( simple_bangla_cms_assignable_roles()[ $role ] ) ) {
		return new WP_Error(
			'sb_cms_role_not_allowed',
			__( 'Your account cannot give someone that role.', 'simple-bangla-cms' ),
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
 * A username that is free, derived from an email address.
 *
 * The owner is asked for an email and a password, not a username — one identity to hand over rather
 * than two, and no chance of a staff member being told the wrong half. WordPress still needs a
 * `user_login`, so the local part of the address becomes one, with a number appended until it is
 * free. It is never shown as something to type: sign-in accepts the email.
 *
 * @param string $email Sanitised email address.
 * @return string Empty when nothing usable can be made of it.
 */
function simple_bangla_cms_username_from_email( $email ) {

	$base = sanitize_user( substr( $email, 0, strpos( $email, '@' ) ), true );
	$base = trim( (string) $base );

	if ( '' === $base ) {
		return '';
	}

	$candidate = $base;

	// Bounded rather than a while(true): a hundred collisions on one local part means something is
	// wrong, and an endpoint that can spin forever is worse than one that gives up and says so.
	for ( $suffix = 2; $suffix < 100 && username_exists( $candidate ); $suffix++ ) {
		$candidate = $base . $suffix;
	}

	return username_exists( $candidate ) ? '' : $candidate;
}

/**
 * Create a staff account.
 *
 * The password is set here, at the owner's request: this is a small shop where a new account is
 * usually being handed to someone sitting in the same room, and "check your email for a link" is a
 * step that fails whenever the site cannot send mail — which, on a shared Bangladeshi host with no
 * SMTP plugin, is most of the time. The password is never echoed back in the response.
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

	$email    = sanitize_email( $request->get_param( 'email' ) );
	$password = (string) $request->get_param( 'password' );

	if ( ! is_email( $email ) ) {
		return new WP_Error( 'sb_cms_bad_email', __( 'That is not a valid email address.', 'simple-bangla-cms' ), array( 'status' => 400 ) );
	}

	if ( email_exists( $email ) ) {
		return new WP_Error( 'sb_cms_email_taken', __( 'An account already uses that email address.', 'simple-bangla-cms' ), array( 'status' => 400 ) );
	}

	// Not sanitised: a password is compared byte for byte, and stripping characters out of it would
	// store something other than what the owner typed and then hand it to the staff member.
	if ( strlen( $password ) < 8 ) {
		return new WP_Error(
			'sb_cms_weak_password',
			__( 'The password needs to be at least 8 characters.', 'simple-bangla-cms' ),
			array( 'status' => 400 )
		);
	}

	$username = sanitize_user( (string) $request->get_param( 'username' ), true );

	if ( '' === $username ) {
		$username = simple_bangla_cms_username_from_email( $email );
	} elseif ( username_exists( $username ) ) {
		return new WP_Error( 'sb_cms_username_taken', __( 'That username is already taken.', 'simple-bangla-cms' ), array( 'status' => 400 ) );
	}

	if ( '' === $username ) {
		return new WP_Error( 'sb_cms_bad_username', __( 'No usable sign-in name could be made from that email address.', 'simple-bangla-cms' ), array( 'status' => 400 ) );
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => $password,
			'display_name' => sanitize_text_field( (string) $request->get_param( 'name' ) ) ?: $username,
			'role'         => $role,
		)
	);

	if ( is_wp_error( $user_id ) ) {
		return new WP_Error( 'sb_cms_create_failed', $user_id->get_error_message(), array( 'status' => 400 ) );
	}

	/*
	 * 'user' rather than 'both': the admin copy is noise for an account the owner just created on
	 * purpose. The welcome mail carries the sign-in name and a link to change the password — useful
	 * as a second route in, and harmless when it never arrives, because the password the owner typed
	 * already works.
	 */
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

	/*
	 * `promote_user` is a *meta* capability: it is resolved against this particular target, unlike
	 * the `promote_users` the route's permission callback checks. The difference is the whole point.
	 * WooCommerce maps it to `do_not_allow` when a non-administrator aims at an administrator, so
	 * this one line is what stops a shop manager demoting the owner from a phone. Without it the
	 * only guard was "is this the last administrator", which a shop with two administrators passes.
	 */
	if ( ! current_user_can( 'promote_user', $user->ID ) ) {
		return new WP_Error(
			'sb_cms_cannot_promote',
			__( 'Your account cannot change that person\'s role.', 'simple-bangla-cms' ),
			array( 'status' => 403 )
		);
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

	/*
	 * `delete_user` with the target, not the blanket `delete_users`. WooCommerce grants a shop
	 * manager `delete_users`, so the plural form was a check that every caller reaching this line
	 * already passed — it read like a guard and let a shop manager erase an administrator's account.
	 * The singular form is a meta capability resolved against this user, and WooCommerce refuses it.
	 */
	if ( ! current_user_can( 'delete_user', $user->ID ) ) {
		return new WP_Error( 'sb_cms_cannot_delete', __( 'Your account cannot remove that person.', 'simple-bangla-cms' ), array( 'status' => 403 ) );
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
