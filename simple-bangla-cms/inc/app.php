<?php
/**
 * The documents the /manage route serves.
 *
 * These are hand-written HTML documents rather than theme templates. `wp_head()` would drag in
 * the storefront's stylesheets, WooCommerce's scripts and every plugin's opinion about the
 * <head>, none of which a management screen wants — and the whole point of the project is that
 * this interface looks nothing like WordPress.
 *
 * The shell embeds the session payload inline. The interface therefore paints its sidebar and
 * user chip on first byte instead of after a round trip, and the dashboard request is the only
 * thing it waits for.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL of a file in the plugin's assets directory, cache-busted by plugin version.
 *
 * @param string $relative Path below assets/.
 * @return string
 */
function simple_bangla_cms_asset( $relative ) {
	return add_query_arg( 'ver', SIMPLE_BANGLA_CMS_VERSION, SIMPLE_BANGLA_CMS_URL . 'assets/' . ltrim( $relative, '/' ) );
}

/**
 * Cache-bust the modules `app.js` imports, not just `app.js`.
 *
 * A version query on the entry script only busts the entry script. Everything it imports is
 * requested at its own bare URL — `.../assets/js/ui.js` — which the browser will happily serve from
 * cache, so an update could ship a new `app.js` beside a month-old `ui.js` and break screens in ways
 * that look nothing like a caching problem. Nothing before this had noticed because no release had
 * changed a shared module and an entry point together.
 *
 * An import map fixes it with no build step: a map whose key is a URL-like specifier is matched
 * against the *resolved* URL of an import, so every relative import in the bundle is rewritten to
 * the versioned one. The list is read off the directory rather than written out, so a new module
 * cannot be forgotten.
 *
 * Falls back silently to unversioned URLs if the directory cannot be read — a stale module is a
 * smaller problem than no interface at all.
 */
function simple_bangla_cms_import_map() {

	// The whole assets directory, not just js/ — `ui.js` imports the vendored Preact bundle out of
	// `assets/vendor/`, and a map that stopped at js/ would leave the one file every screen depends
	// on as the single unversioned import.
	$base = SIMPLE_BANGLA_CMS_DIR . 'assets/';
	$url  = SIMPLE_BANGLA_CMS_URL . 'assets/';

	if ( ! is_dir( $base ) ) {
		return;
	}

	/*
	 * An iterator rather than glob() with GLOB_BRACE. The brace flag is a GNU extension: PHP defines
	 * the constant everywhere but the pattern silently matches nothing where the underlying libc has
	 * no support for it, and this printed an empty map under Playground's WASM build before that was
	 * noticed. An empty map is invisible — every import simply resolves unversioned, which is the
	 * exact bug this function exists to prevent.
	 */
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS )
	);

	$imports = array();

	foreach ( $files as $file ) {

		if ( 'js' !== strtolower( $file->getExtension() ) ) {
			continue;
		}

		$relative = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $base ) ) );

		$imports[ $url . $relative ] = add_query_arg( 'ver', SIMPLE_BANGLA_CMS_VERSION, $url . $relative );
	}

	if ( ! $imports ) {
		return;
	}

	?>
	<script type="importmap"><?php echo wp_json_encode( array( 'imports' => $imports ) ); ?></script>
	<?php
}

/**
 * Open a CMS document.
 *
 * @param string $title      Document title.
 * @param string $body_class Class on <body>.
 */
function simple_bangla_cms_head( $title, $body_class ) {

	$icon = get_site_icon_url( 180 );

	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $title ); ?></title>
	<?php if ( $icon ) : ?>
	<link rel="icon" href="<?php echo esc_url( $icon ); ?>">
	<?php endif; ?>
	<link rel="stylesheet" href="<?php echo esc_url( simple_bangla_cms_asset( 'css/cms.css' ) ); ?>">
</head>
<body class="<?php echo esc_attr( $body_class ); ?>">
	<?php
}

/**
 * Close a CMS document.
 */
function simple_bangla_cms_foot() {
	?>
</body>
</html>
	<?php
}

/**
 * The store's wordmark, as a logo image when one is set and as text when it is not.
 */
function simple_bangla_cms_brand() {

	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';

	if ( $logo ) {
		printf(
			'<img class="sb-brand__logo" src="%s" alt="%s">',
			esc_url( $logo ),
			esc_attr( get_bloginfo( 'name' ) )
		);
		return;
	}

	printf( '<span class="sb-brand__name">%s</span>', esc_html( get_bloginfo( 'name' ) ) );
}

/**
 * The sign-in screen.
 *
 * @param WP_Error|null $error Error from a failed attempt.
 */
function simple_bangla_cms_render_login( $error = null ) {

	simple_bangla_cms_head(
		/* translators: %s: store name. */
		sprintf( __( 'Sign in — %s', 'simple-bangla-cms' ), get_bloginfo( 'name' ) ),
		'sb-login-page'
	);

	?>
	<main class="sb-login">
		<form class="sb-login__card" method="post" action="<?php echo esc_url( simple_bangla_cms_url() ); ?>">

			<div class="sb-login__brand"><?php simple_bangla_cms_brand(); ?></div>
			<p class="sb-login__lead"><?php esc_html_e( 'Sign in to manage your store.', 'simple-bangla-cms' ); ?></p>

			<?php if ( is_wp_error( $error ) ) : ?>
				<p class="sb-alert sb-alert--error" role="alert"><?php echo esc_html( $error->get_error_message() ); ?></p>
			<?php endif; ?>

			<label class="sb-field">
				<span class="sb-field__label"><?php esc_html_e( 'Username or email', 'simple-bangla-cms' ); ?></span>
				<input class="sb-input" type="text" name="log" autocomplete="username" required autofocus>
			</label>

			<label class="sb-field">
				<span class="sb-field__label"><?php esc_html_e( 'Password', 'simple-bangla-cms' ); ?></span>
				<input class="sb-input" type="password" name="pwd" autocomplete="current-password" required>
			</label>

			<label class="sb-check">
				<input type="checkbox" name="rememberme" value="1" checked>
				<span><?php esc_html_e( 'Keep me signed in', 'simple-bangla-cms' ); ?></span>
			</label>

			<button class="sb-btn sb-btn--primary sb-btn--block" type="submit">
				<?php esc_html_e( 'Sign in', 'simple-bangla-cms' ); ?>
			</button>

			<input type="hidden" name="sb_cms_action" value="login">
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( simple_bangla_cms_requested_url() ); ?>">
			<?php wp_nonce_field( 'sb_cms_login' ); ?>

			<p class="sb-login__foot">
				<a href="<?php echo esc_url( wp_lostpassword_url( simple_bangla_cms_url() ) ); ?>"><?php esc_html_e( 'Forgot password?', 'simple-bangla-cms' ); ?></a>
				<span aria-hidden="true">·</span>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to store', 'simple-bangla-cms' ); ?></a>
			</p>
		</form>
	</main>
	<?php

	simple_bangla_cms_foot();
}

/**
 * The URL the visitor actually asked for, used to return them there after signing in.
 *
 * @return string
 */
function simple_bangla_cms_requested_url() {

	$path = simple_bangla_cms_current_path();
	$base = simple_bangla_cms_base();

	if ( $path === $base ) {
		return simple_bangla_cms_url();
	}

	return simple_bangla_cms_url( substr( $path, strlen( $base ) + 1 ) );
}

/**
 * Shown to a signed-in user whose account has no business here.
 */
function simple_bangla_cms_render_no_access() {

	simple_bangla_cms_head( __( 'No access', 'simple-bangla-cms' ), 'sb-login-page' );

	?>
	<main class="sb-login">
		<div class="sb-login__card">
			<div class="sb-login__brand"><?php simple_bangla_cms_brand(); ?></div>
			<p class="sb-alert sb-alert--error"><?php esc_html_e( 'Your account does not have access to store management.', 'simple-bangla-cms' ); ?></p>
			<p class="sb-login__foot">
				<a href="<?php echo esc_url( simple_bangla_cms_logout_url() ); ?>"><?php esc_html_e( 'Sign out', 'simple-bangla-cms' ); ?></a>
				<span aria-hidden="true">·</span>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to store', 'simple-bangla-cms' ); ?></a>
			</p>
		</div>
	</main>
	<?php

	simple_bangla_cms_foot();
}

/**
 * The nonce-protected sign-out URL.
 *
 * Built with add_query_arg() rather than wp_nonce_url(), which HTML-escapes its ampersand. That
 * is right for printing straight into markup and wrong for the copy embedded in the boot JSON,
 * where "&amp;" survives into the DOM and renames the parameter to "amp;_wpnonce" — a sign-out
 * link that silently does nothing. Callers escape at the point of output instead.
 *
 * @return string
 */
function simple_bangla_cms_logout_url() {

	return add_query_arg(
		array(
			'sb_cms_action' => 'logout',
			'_wpnonce'      => wp_create_nonce( 'sb_cms_logout' ),
		),
		simple_bangla_cms_url()
	);
}

/**
 * The application shell.
 */
function simple_bangla_cms_render_app() {

	$boot    = simple_bangla_cms_session_payload();
	$logo_id = (int) get_theme_mod( 'custom_logo' );

	// Everything the interface needs to render its chrome before its first request.
	$boot['base']      = wp_make_link_relative( simple_bangla_cms_url() );
	$boot['restRoot']  = esc_url_raw( rest_url() );
	$boot['namespace'] = SIMPLE_BANGLA_CMS_REST_NS;
	$boot['logoutUrl'] = simple_bangla_cms_logout_url();
	$boot['brandLogo'] = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';

	simple_bangla_cms_head(
		/* translators: %s: store name. */
		sprintf( __( 'Manage — %s', 'simple-bangla-cms' ), get_bloginfo( 'name' ) ),
		'sb-app-page'
	);

	?>
	<div id="sb-app">
		<div class="sb-boot" role="status"><span class="sb-spinner"></span></div>
	</div>

	<noscript>
		<p class="sb-alert sb-alert--error"><?php esc_html_e( 'Store management needs JavaScript enabled.', 'simple-bangla-cms' ); ?></p>
	</noscript>

	<script id="sb-cms-boot" type="application/json"><?php
		// Printed as JSON in a script tag rather than as a JS assignment: nothing in the payload
		// can then terminate the script early, whatever a store name happens to contain.
		echo wp_json_encode( $boot );
	?></script>

	<?php
	// The map has to be in the document before the module that its imports are resolved for.
	simple_bangla_cms_import_map();
	?>

	<script type="module" src="<?php echo esc_url( simple_bangla_cms_asset( 'js/app.js' ) ); ?>"></script>
	<?php

	simple_bangla_cms_foot();
}
