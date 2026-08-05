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
define( 'SIMPLE_BANGLA_VERSION', '1.0.0' );

/** Absolute path to the theme directory, with a trailing slash. */
define( 'SIMPLE_BANGLA_DIR', trailingslashit( get_template_directory() ) );

/** Public URL of the theme directory, with a trailing slash. */
define( 'SIMPLE_BANGLA_URI', trailingslashit( get_template_directory_uri() ) );

require_once SIMPLE_BANGLA_DIR . 'inc/setup.php';
require_once SIMPLE_BANGLA_DIR . 'inc/enqueue.php';
require_once SIMPLE_BANGLA_DIR . 'inc/customizer.php';
require_once SIMPLE_BANGLA_DIR . 'inc/template-tags.php';

/**
 * WooCommerce-specific code is only loaded when WooCommerce is actually active.
 *
 * The theme is required to survive on a stock install, so every WooCommerce touch point
 * has to be behind this guard rather than a function_exists() check scattered per callback.
 */
if ( class_exists( 'WooCommerce' ) ) {
	require_once SIMPLE_BANGLA_DIR . 'inc/woocommerce.php';
}
