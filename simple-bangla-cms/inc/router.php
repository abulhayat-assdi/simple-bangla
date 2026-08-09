<?php
/**
 * The /manage route.
 *
 * The interface is served from the store's own domain rather than a subdomain, and this is where
 * that pays off: the visitor already carries a WordPress login cookie, so authentication is
 * WordPress's own with nothing extra to store, expire or steal.
 *
 * Matching is done against the request path rather than through a rewrite rule. A rewrite rule
 * needs a flush to take effect, and a flush that silently did not happen — a file copied without
 * reactivating the plugin, a migration, a caching layer — would leave the owner staring at a 404
 * on the only URL they use. Path matching has no such state.
 *
 * Everything runs on `init` because the login form posts to itself and needs to redirect, which
 * must happen before any output.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/** Failed sign-ins from one address before it is made to wait. */
const SIMPLE_BANGLA_CMS_MAX_ATTEMPTS = 5;

/** How long that wait lasts. */
const SIMPLE_BANGLA_CMS_LOCKOUT = 15 * MINUTE_IN_SECONDS;

/**
 * The path segment the CMS answers on.
 *
 * @return string
 */
function simple_bangla_cms_base() {
	return trim( (string) apply_filters( 'simple_bangla_cms_base', 'manage' ), '/' );
}

/**
 * A URL inside the CMS.
 *
 * @param string $path Sub-path, may be empty.
 * @return string
 */
function simple_bangla_cms_url( $path = '' ) {
	return home_url( '/' . simple_bangla_cms_base() . '/' . ltrim( $path, '/' ) );
}

/**
 * The current request path, relative to the WordPress home directory.
 *
 * Subdirectory installs matter here: on a site at example.com/shop/ the CMS lives at
 * /shop/manage, and a naive match on the raw REQUEST_URI would never fire.
 *
 * @return string Path with no leading or trailing slash, no query string.
 */
function simple_bangla_cms_current_path() {

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );

	if ( $home && '/' !== $home && 0 === strpos( $path, $home ) ) {
		$path = substr( $path, strlen( $home ) );
	}

	return trim( rawurldecode( $path ), '/' );
}

/**
 * Whether the site is set up so that /manage can reach WordPress at all.
 *
 * With "Plain" permalinks WordPress writes no rewrite block, so on most servers a request for
 * /manage/ is answered by Apache looking for a directory of that name and returning its own 404 —
 * PHP never runs and nothing here gets a chance to help. The owner would see a bare 404 on the
 * only URL they use, with no clue why.
 *
 * @return bool
 */
function simple_bangla_cms_permalinks_ready() {
	return (bool) get_option( 'permalink_structure' );
}

/**
 * Whether this request is for the CMS.
 *
 * @return bool
 */
function simple_bangla_cms_is_request() {

	$base = simple_bangla_cms_base();
	$path = simple_bangla_cms_current_path();

	return $path === $base || 0 === strpos( $path, $base . '/' );
}

/**
 * Serve the CMS.
 */
function simple_bangla_cms_route() {

	if ( ! simple_bangla_cms_is_request() ) {
		return;
	}

	// A management screen must never be cached by a proxy or indexed by a crawler.
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );

	simple_bangla_cms_handle_logout();

	$error = simple_bangla_cms_handle_login();

	if ( ! is_user_logged_in() ) {
		simple_bangla_cms_render_login( $error );
		exit;
	}

	if ( ! simple_bangla_cms_can( 'dashboard.view' ) ) {
		simple_bangla_cms_render_no_access();
		exit;
	}

	simple_bangla_cms_render_app();
	exit;
}
add_action( 'init', 'simple_bangla_cms_route', 20 );

/**
 * Sign the user out when asked.
 */
function simple_bangla_cms_handle_logout() {

	if ( ! isset( $_GET['sb_cms_action'] ) || 'logout' !== $_GET['sb_cms_action'] ) {
		return;
	}

	// Nonce-checked so a stray <img src> on another site cannot sign the owner out mid-task.
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'sb_cms_logout' ) ) {
		return;
	}

	wp_logout();
	wp_safe_redirect( simple_bangla_cms_url() );
	exit;
}

/**
 * Process the sign-in form.
 *
 * @return WP_Error|null Error to show on the form, or null.
 */
function simple_bangla_cms_handle_login() {

	if ( ! isset( $_POST['sb_cms_action'] ) || 'login' !== $_POST['sb_cms_action'] ) {
		return null;
	}

	$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'sb_cms_login' ) ) {
		return new WP_Error( 'expired', __( 'That form had been open too long. Please try again.', 'simple-bangla-cms' ) );
	}

	if ( simple_bangla_cms_is_locked_out() ) {
		return new WP_Error(
			'locked',
			sprintf(
				/* translators: %d: number of minutes. */
				__( 'Too many failed attempts. Try again in %d minutes.', 'simple-bangla-cms' ),
				(int) ( SIMPLE_BANGLA_CMS_LOCKOUT / MINUTE_IN_SECONDS )
			)
		);
	}

	/*
	 * Either an email address or a username is accepted, because staff accounts are created from an
	 * email and the username the CMS derives from it is never shown to them. No work is needed for
	 * that: core registers both wp_authenticate_username_password and wp_authenticate_email_password
	 * on `authenticate`, and sanitize_user() in its non-strict mode keeps the "@" and the dots. The
	 * strict flag must stay off here — it would quietly turn an address into a username nobody has.
	 */
	$user = wp_signon(
		array(
			'user_login'    => isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '',
			'user_password' => isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '',
			'remember'      => ! empty( $_POST['rememberme'] ),
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {

		simple_bangla_cms_record_failure();

		// WordPress's own messages distinguish "no such user" from "wrong password", which tells
		// an attacker which half of the guess was right. One message for both.
		return new WP_Error( 'failed', __( 'That email, username or password is not right.', 'simple-bangla-cms' ) );
	}

	simple_bangla_cms_clear_failures();
	wp_set_current_user( $user->ID );

	$target = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
	$target = $target ? wp_validate_redirect( $target, simple_bangla_cms_url() ) : simple_bangla_cms_url();

	wp_safe_redirect( $target );
	exit;
}

/**
 * The throttle bucket for the current caller.
 *
 * Keyed on REMOTE_ADDR only. Forwarded-for headers are deliberately ignored: they are trivially
 * spoofed, and trusting one would let an attacker rotate the header to get unlimited attempts.
 * Behind a reverse proxy this throttles the proxy, which is a limitation worth having over a
 * control that can be bypassed by typing.
 *
 * @return string
 */
function simple_bangla_cms_throttle_key() {

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

	return 'sb_cms_fails_' . md5( $ip );
}

/**
 * Whether the caller is currently locked out.
 *
 * @return bool
 */
function simple_bangla_cms_is_locked_out() {
	return (int) get_transient( simple_bangla_cms_throttle_key() ) >= SIMPLE_BANGLA_CMS_MAX_ATTEMPTS;
}

/**
 * Count a failed attempt.
 */
function simple_bangla_cms_record_failure() {

	$key   = simple_bangla_cms_throttle_key();
	$fails = (int) get_transient( $key ) + 1;

	set_transient( $key, $fails, SIMPLE_BANGLA_CMS_LOCKOUT );
}

/**
 * Forget failures after a successful sign-in.
 */
function simple_bangla_cms_clear_failures() {
	delete_transient( simple_bangla_cms_throttle_key() );
}
