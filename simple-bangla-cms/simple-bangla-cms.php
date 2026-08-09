<?php
/**
 * Plugin Name:       Simple Bangla CMS
 * Plugin URI:        https://simplebangla.com/
 * Description:       A custom store-management interface for Simple Bangla, so day-to-day work happens outside wp-admin. This plugin is the API half: authentication, the settings bridge and the dashboard endpoints.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Simple Bangla
 * Text Domain:       simple-bangla-cms
 * Domain Path:       /languages
 * WC requires at least: 8.0
 *
 * The plugin deliberately owns nothing the theme owns. Every homepage, colour and store-detail
 * value it writes is the same `theme_mod` the Customizer already writes, so the theme keeps
 * reading exactly what it reads today and the Customizer stays usable as a fallback. Deactivating
 * this plugin removes the interface and changes nothing about the storefront.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

define( 'SIMPLE_BANGLA_CMS_VERSION', '1.0.0' );
define( 'SIMPLE_BANGLA_CMS_FILE', __FILE__ );
define( 'SIMPLE_BANGLA_CMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_BANGLA_CMS_URL', plugin_dir_url( __FILE__ ) );

/**
 * REST namespace.
 *
 * Products, orders, customers, coupons and reviews are NOT re-implemented here — WooCommerce's
 * own `wc/v3` already covers them, is maintained by WooCommerce, and stays correct across
 * updates. This namespace only fills the gaps `wc/v3` has no concept of: theme settings, an
 * aggregated dashboard, the theme's menu locations, the order block list, and staff accounts.
 *
 * Staff is the one place something core already offers is reimplemented, and the reason is the
 * guards rather than the CRUD — `wp/v2/users` will happily let an owner demote their only
 * administrator from a phone. See inc/rest-staff.php.
 */
define( 'SIMPLE_BANGLA_CMS_REST_NS', 'sb-cms/v1' );

/** The theme whose `theme_mod` values this plugin is allowed to write. */
define( 'SIMPLE_BANGLA_CMS_THEME', 'simple-bangla' );

require_once SIMPLE_BANGLA_CMS_DIR . 'inc/auth.php';
require_once SIMPLE_BANGLA_CMS_DIR . 'inc/schema.php';
require_once SIMPLE_BANGLA_CMS_DIR . 'inc/stats.php';
require_once SIMPLE_BANGLA_CMS_DIR . 'inc/router.php';
require_once SIMPLE_BANGLA_CMS_DIR . 'inc/app.php';
require_once SIMPLE_BANGLA_CMS_DIR . 'inc/rest-session.php';
require_once SIMPLE_BANGLA_CMS_DIR . 'inc/rest-settings.php';
require_once SIMPLE_BANGLA_CMS_DIR . 'inc/rest-dashboard.php';
require_once SIMPLE_BANGLA_CMS_DIR . 'inc/menu-icon.php';
require_once SIMPLE_BANGLA_CMS_DIR . 'inc/rest-blocklist.php';
require_once SIMPLE_BANGLA_CMS_DIR . 'inc/rest-staff.php';

/**
 * Tell WooCommerce this plugin is safe under High-Performance Order Storage.
 *
 * Without this declaration WooCommerce refuses to let a store enable HPOS while the plugin is
 * active. Nothing here touches the order tables directly except the analytics read in
 * inc/stats.php, which queries `wc_order_stats` — a table HPOS does not change.
 */
function simple_bangla_cms_declare_hpos() {

	if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
		'custom_order_tables',
		SIMPLE_BANGLA_CMS_FILE,
		true
	);
}
add_action( 'before_woocommerce_init', 'simple_bangla_cms_declare_hpos' );

/**
 * Whether the environment this plugin needs is actually present.
 *
 * @return bool
 */
function simple_bangla_cms_is_ready() {
	return class_exists( 'WooCommerce' );
}

/**
 * Whether the Simple Bangla theme is the active theme.
 *
 * This matters more than it looks. `theme_mod` values are stored per theme, so writing them
 * while a different theme is active would silently configure the wrong theme and leave the
 * owner staring at a homepage that ignored every change. The settings endpoints refuse to run
 * unless this is true.
 *
 * @return bool
 */
function simple_bangla_cms_theme_is_active() {
	return SIMPLE_BANGLA_CMS_THEME === get_stylesheet() || SIMPLE_BANGLA_CMS_THEME === get_template();
}

/**
 * Load translations.
 */
function simple_bangla_cms_load_textdomain() {
	load_plugin_textdomain( 'simple-bangla-cms', false, dirname( plugin_basename( SIMPLE_BANGLA_CMS_FILE ) ) . '/languages' );
}
add_action( 'init', 'simple_bangla_cms_load_textdomain' );

/**
 * Warn in wp-admin when something the CMS depends on is missing or misconfigured.
 *
 * These are the only screens this plugin adds to wp-admin, on purpose: the whole point of the
 * project is that the owner does not go there. A notice is worth the exception because each of
 * these conditions makes the CMS behave in a way that would otherwise look like a bug.
 */
function simple_bangla_cms_admin_notices() {

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( ! simple_bangla_cms_is_ready() ) {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Simple Bangla CMS needs WooCommerce to be installed and active.', 'simple-bangla-cms' )
		);
		return;
	}

	if ( ! simple_bangla_cms_theme_is_active() ) {
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'Simple Bangla CMS is active but the Simple Bangla theme is not. Content and appearance settings are disabled until it is, because theme settings are stored per theme.', 'simple-bangla-cms' )
		);
	}

	if ( ! simple_bangla_cms_permalinks_ready() ) {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			sprintf(
				/* translators: %s: URL of the permalink settings screen. */
				wp_kses_post( __( 'Permalinks are set to Plain, so <code>%1$s</code> will not open. Choose <strong>Post name</strong> under <a href="%2$s">Settings → Permalinks</a>.', 'simple-bangla-cms' ) ),
				esc_html( simple_bangla_cms_url() ),
				esc_url( admin_url( 'options-permalink.php' ) )
			)
		);
	}

	if ( ! simple_bangla_cms_hpos_enabled() ) {
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'High-Performance Order Storage is off. Turn it on under WooCommerce → Settings → Advanced → Features. Order screens in the CMS get dramatically slower without it once the store passes a few thousand orders.', 'simple-bangla-cms' )
		);
	}
}
add_action( 'admin_notices', 'simple_bangla_cms_admin_notices' );

/**
 * Whether WooCommerce is storing orders in its own tables rather than `wp_posts`.
 *
 * @return bool
 */
function simple_bangla_cms_hpos_enabled() {

	if ( ! class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
		return false;
	}

	return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
}
