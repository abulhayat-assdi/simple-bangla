<?php
/**
 * The one thing `wp/v2/pages` cannot already do.
 *
 * Pages have been in core's REST API since WordPress 4.7 — list, create, update, trash, slugs,
 * statuses and content all included — so the Content Pages screen is built on that rather than on
 * anything invented here. Re-implementing it would have meant re-implementing revisions, autosave
 * locking, `kses` on the way in and capability mapping, all of which core already gets right.
 *
 * What core does not know about is the theme's "show this page in the footer" tick. It is stored in
 * a protected post meta key that the theme defines and reads (see simple-bangla/inc/pages.php);
 * without the registration below, the CMS could not see it and — worse — a page saved from the CMS
 * would keep a tick the owner could neither see nor clear.
 *
 * The meta belongs to the theme. Only its REST exposure lives here, which is the same split as the
 * menu-item icon in inc/menu-icon.php and for the same reason: deactivate this plugin and the
 * footer goes on printing exactly the same links.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Expose the theme's footer-link tick over REST.
 *
 * On `rest_api_init` rather than `init`: it is only needed for REST, and the theme's constant does
 * not exist when this plugin loads — plugins load before themes.
 */
function simple_bangla_cms_register_page_meta() {

	if ( ! defined( 'SIMPLE_BANGLA_FOOTER_LINK_META' ) ) {
		return;
	}

	register_post_meta(
		'page',
		SIMPLE_BANGLA_FOOTER_LINK_META,
		array(
			'type'          => 'boolean',
			'description'   => __( 'Show a link to this page in the first footer column.', 'simple-bangla-cms' ),
			'single'        => true,
			'default'       => false,
			'show_in_rest'  => true,
			/*
			 * The key is underscore-prefixed, so WordPress treats it as protected and refuses both
			 * reads and writes over REST unless an auth callback says otherwise.
			 *
			 * Gated on `edit_pages` — the same capability behind this screen's `content.manage`
			 * ability — rather than on `edit_theme_options`. Ticking a page into the footer is a
			 * navigation decision, so `edit_theme_options` was the defensible alternative; but the
			 * person who writes the Delivery & Return page is the person who needs it linked, and
			 * splitting the two would have produced a tick box that saved everything on the form
			 * except itself.
			 */
			'auth_callback' => function () {
				return current_user_can( 'edit_pages' );
			},
		)
	);
}
add_action( 'rest_api_init', 'simple_bangla_cms_register_page_meta' );
