<?php
/**
 * The browser-tab icon.
 *
 * WordPress prints its own favicon links from wp_site_icon() on wp_head, but only when the
 * `site_icon` option holds an attachment. With that option empty, WordPress 6.1 and later serve a
 * default favicon of their own — the blue WordPress mark — so a shop that never opened Settings
 * shows somebody else's logo in every tab. That is the state this file fixes.
 *
 * It fixes it by filtering `get_site_icon_url()` rather than by printing link tags of its own.
 * has_site_icon() is only a call to get_site_icon_url(), so answering that one filter turns the
 * whole of core's icon handling on: the 32px and 192px icons, the 180px apple-touch-icon, the
 * Windows tile, and the /favicon.ico redirect, all of it consistent and none of it duplicated
 * here. Printing a second <link rel="icon"> beside core's would have meant two answers to one
 * question the moment a real site icon was set.
 *
 * The fallback is the site logo, which is the picture the shop has already uploaded — the header,
 * the footer and the CMS sign-in page all read the same `custom_logo` mod. A wide wordmark scaled
 * into a 16px tab is legible only as a smudge of the right colour, which still beats the
 * WordPress mark; the proper answer is a square icon, and the Site icon field in the CMS (and
 * Customizer → Site Identity) is where that goes. Setting one takes precedence here
 * automatically, because this filter only speaks when core had nothing to say.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fall back to the site logo when no site icon has been chosen.
 *
 * @param string $url     Icon URL core resolved, empty when the option is unset.
 * @param int    $size    Requested square size in pixels.
 * @param int    $blog_id Site the icon was asked for, 0 for the current one.
 * @return string
 */
function simple_bangla_site_icon_fallback( $url, $size, $blog_id ) {

	// A real site icon always wins; this is a fallback, not an override.
	if ( $url ) {
		return $url;
	}

	/*
	 * Core fires this filter *after* restoring the current blog, so on multisite an explicit
	 * $blog_id would be answered with this site's logo. Nothing here can resolve another site's
	 * logo, so the honest response is to leave the request alone.
	 */
	if ( $blog_id && is_multisite() && get_current_blog_id() !== (int) $blog_id ) {
		return $url;
	}

	$logo_id = (int) get_theme_mod( 'custom_logo' );

	if ( ! $logo_id ) {
		return $url;
	}

	/*
	 * Asking for the requested square hands WordPress's own size matching the job: a 32px tab
	 * icon is served from the 150px thumbnail rather than from a 1024px original, without this
	 * file generating or cropping anything.
	 */
	$size = max( 1, (int) $size );
	$src  = wp_get_attachment_image_url( $logo_id, array( $size, $size ) );

	return $src ? $src : $url;
}
add_filter( 'get_site_icon_url', 'simple_bangla_site_icon_fallback', 10, 3 );
