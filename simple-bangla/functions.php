<?php
/**
 * Simple Bangla — theme bootstrap.
 *
 * Defines shared constants and pulls in the feature modules. Nothing else belongs
 * in this file; every actual feature lives under inc/.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme version.
 *
 * Used as the asset cache-buster in production. During development inc/enqueue.php
 * prefers the file modification time so edits show up without bumping this.
 */
define( 'SIMPLE_BANGLA_VERSION', '1.3.0' );

/** Absolute path to the theme directory, with a trailing slash. */
define( 'SIMPLE_BANGLA_DIR', trailingslashit( get_template_directory() ) );

/** Public URL of the theme directory, with a trailing slash. */
define( 'SIMPLE_BANGLA_URI', trailingslashit( get_template_directory_uri() ) );

require_once SIMPLE_BANGLA_DIR . 'inc/setup.php';
require_once SIMPLE_BANGLA_DIR . 'inc/enqueue.php';
require_once SIMPLE_BANGLA_DIR . 'inc/customizer.php';
require_once SIMPLE_BANGLA_DIR . 'inc/customizer-store.php';
require_once SIMPLE_BANGLA_DIR . 'inc/customizer-home.php';
require_once SIMPLE_BANGLA_DIR . 'inc/template-tags.php';
/*
 * The footer's page links. Outside the WooCommerce guard: the footer renders on a stock install
 * too, and a page ticked for it must appear whether or not the shop is running.
 */
require_once SIMPLE_BANGLA_DIR . 'inc/pages.php';
/*
 * The browser-tab icon. Outside the WooCommerce guard for the same reason as the footer's pages:
 * without it WordPress prints its own default favicon on every page of a stock install.
 */
require_once SIMPLE_BANGLA_DIR . 'inc/site-icon.php';
require_once SIMPLE_BANGLA_DIR . 'inc/nav-walker.php';
require_once SIMPLE_BANGLA_DIR . 'inc/ajax-search.php';
require_once SIMPLE_BANGLA_DIR . 'inc/demo-content.php';

/**
 * WooCommerce-specific code is only loaded when WooCommerce is actually active.
 *
 * The theme is required to survive on a stock install, so every WooCommerce touch point
 * has to be behind this guard rather than a function_exists() check scattered per callback.
 */
if ( class_exists( 'WooCommerce' ) ) {
	require_once SIMPLE_BANGLA_DIR . 'inc/woocommerce.php';
	require_once SIMPLE_BANGLA_DIR . 'inc/recently-viewed.php';
	require_once SIMPLE_BANGLA_DIR . 'inc/ajax-filter.php';
	/*
	 * The block list enforces itself at the checkout, so it belongs to the theme that owns the
	 * checkout. The CMS plugin only edits the list; deactivating it must not quietly let blocked
	 * customers order again.
	 */
	require_once SIMPLE_BANGLA_DIR . 'inc/blocklist.php';
	/*
	 * The two extra order stages. In the theme because a status is customer-visible in My Account
	 * and in every WooCommerce email — with the plugin off, real orders must still have a name.
	 */
	require_once SIMPLE_BANGLA_DIR . 'inc/order-status.php';
}
