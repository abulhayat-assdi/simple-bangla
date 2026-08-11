<?php
/**
 * Install both packages the way wp-admin does, in a clean WordPress with nothing mounted:
 * Theme_Upgrader and Plugin_Upgrader, once fresh and once with overwrite_package — which is
 * what "Replace current with uploaded" runs. A sentinel file is planted between the two so the
 * overwrite is proved to have removed the older copy rather than merged into it.
 *
 * Results are written to a mounted file: runPHP output is not printed by the Playground CLI.
 *
 * Run it from PowerShell, with the source deliberately NOT mounted — the point is to install
 * what the zips contain, not what the working tree contains:
 *
 *   npx @wp-playground/cli@latest server --port=8899 `
 *     --mount-dir "<abs>\dist"  /wordpress/sb-dist `
 *     --mount-dir "<abs>\tools" /wordpress/sb-tools `
 *     --mount-dir "<abs>\dist"  /wordpress/sb-out `
 *     --blueprint "<abs>\tools\install-blueprint.json"
 *
 * then read sb-out/install-report.txt. Point sb-out at any writable mounted directory.
 */

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$lines = array();
$pass  = 0;
$fail  = 0;

$ok = function ( $name, $cond, $detail = '' ) use ( &$lines, &$pass, &$fail ) {
	if ( $cond ) { $pass++; $lines[] = "PASS  $name"; }
	else { $fail++; $lines[] = "FAIL  $name" . ( $detail ? "  — $detail" : '' ); }
};

$themeZip  = glob( '/wordpress/sb-dist/simple-bangla-[0-9]*.zip' );
$themeZip  = array_values( array_filter( $themeZip, function ( $p ) {
	return preg_match( '~/simple-bangla-\d[\d.]*\.zip$~', $p );
} ) );
$pluginZip = glob( '/wordpress/sb-dist/simple-bangla-cms-*.zip' );

$ok( 'the theme package is readable', ! empty( $themeZip ) );
$ok( 'the plugin package is readable', ! empty( $pluginZip ) );

if ( $themeZip && $pluginZip ) {

	$themeZip  = end( $themeZip );
	$pluginZip = end( $pluginZip );
	$lines[]   = 'INFO  ' . basename( $themeZip ) . ' + ' . basename( $pluginZip );

	// --- theme, fresh ------------------------------------------------------
	$upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
	$result   = $upgrader->install( $themeZip );
	$ok( 'theme installs into a clean site', true === $result, is_wp_error( $result ) ? $result->get_error_message() : var_export( $result, true ) );

	$theme = wp_get_theme( 'simple-bangla' );
	$ok( 'WordPress can read the installed theme', $theme->exists(), $theme->errors() ? implode( '; ', $theme->errors()->get_error_messages() ) : '' );
	$ok( 'it reports its own name and version', 'Simple Bangla' === $theme->get( 'Name' ), $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) );
	$lines[] = 'INFO  installed theme version ' . $theme->get( 'Version' );

	$themeDir = get_theme_root() . '/simple-bangla';
	$ok( 'style.css landed inside the theme folder, not at the root', file_exists( "$themeDir/style.css" ) );
	$ok( 'a nested file survived the extraction', file_exists( "$themeDir/template-parts/footer/mobile-bar.php" ) );
	$ok( 'the translation template shipped', file_exists( "$themeDir/languages/simple-bangla.pot" ) );
	/*
	 * The packaging bug this guards against extracts one file whose entire name is
	 * "simple-bangla\style.css", sitting at the themes root with no directory at all. Ask the
	 * directory what names it holds rather than asking file_exists() about a backslash path —
	 * on this VFS that probe answered yes for a file that does not exist, which reported a
	 * correct package as broken.
	 */
	$roots    = array_diff( scandir( get_theme_root() ), array( '.', '..' ) );
	$slashed  = array_filter( $roots, function ( $n ) { return false !== strpos( $n, '\\' ); } );
	$ok( 'nothing was extracted with a literal backslash name', empty( $slashed ), implode( ', ', $slashed ) );
	$ok( 'the theme arrived as a directory named simple-bangla', in_array( 'simple-bangla', $roots, true ) && is_dir( get_theme_root() . '/simple-bangla' ), implode( ', ', $roots ) );
	$lines[] = 'INFO  themes root holds: ' . implode( ', ', $roots );

	// The control the first version of this check was missing.
	$ok( 'file_exists() control: an invented name is absent', ! file_exists( get_theme_root() . '/simple-bangla/definitely-not-here.css' ) );

	switch_theme( 'simple-bangla' );
	$ok( 'the theme activates', 'simple-bangla' === get_stylesheet() , get_stylesheet() );

	// --- theme, replace ----------------------------------------------------
	file_put_contents( "$themeDir/sb-stale-file.txt", 'left over from the older copy' );
	$upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
	$result   = $upgrader->install( $themeZip, array( 'overwrite_package' => true ) );
	$ok( 'theme reinstalls over itself (Replace current with uploaded)', true === $result, is_wp_error( $result ) ? $result->get_error_message() : var_export( $result, true ) );
	$ok( 'the replace removed the older copy rather than merging into it', ! file_exists( "$themeDir/sb-stale-file.txt" ) );
	$ok( 'the theme still reads after the replace', wp_get_theme( 'simple-bangla' )->exists() );

	// --- plugin, fresh -----------------------------------------------------
	$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
	$result   = $upgrader->install( $pluginZip );
	$ok( 'plugin installs into a clean site', true === $result, is_wp_error( $result ) ? $result->get_error_message() : var_export( $result, true ) );

	$pluginFile = 'simple-bangla-cms/simple-bangla-cms.php';
	$pluginDir  = WP_PLUGIN_DIR . '/simple-bangla-cms';
	$ok( 'the bootstrap landed inside the plugin folder', file_exists( WP_PLUGIN_DIR . '/' . $pluginFile ) );
	$ok( 'the vendored Preact bundle shipped', file_exists( "$pluginDir/assets/vendor/preact-htm.module.js" ) );
	$ok( 'the plugin translation template shipped', file_exists( "$pluginDir/languages/simple-bangla-cms.pot" ) );

	$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $pluginFile );
	$ok( 'WordPress reads the plugin header', 'Simple Bangla CMS' === $data['Name'], $data['Name'] . ' ' . $data['Version'] );
	$lines[] = 'INFO  installed plugin version ' . $data['Version'];

	$activated = activate_plugin( $pluginFile );
	$ok( 'the plugin activates', null === $activated, is_wp_error( $activated ) ? $activated->get_error_message() : '' );

	// --- plugin, replace ---------------------------------------------------
	file_put_contents( "$pluginDir/sb-stale-file.txt", 'left over from the older copy' );
	$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
	$result   = $upgrader->install( $pluginZip, array( 'overwrite_package' => true ) );
	$ok( 'plugin reinstalls over itself', true === $result, is_wp_error( $result ) ? $result->get_error_message() : var_export( $result, true ) );
	$ok( 'the plugin replace removed the older copy', ! file_exists( "$pluginDir/sb-stale-file.txt" ) );
	$ok( 'the bootstrap is still there after the replace', file_exists( WP_PLUGIN_DIR . '/' . $pluginFile ) );

	// --- the storefront actually renders -----------------------------------
	$home = wp_remote_get( home_url( '/' ), array( 'timeout' => 120 ) );
	if ( is_wp_error( $home ) ) {
		$lines[] = 'INFO  home request could not be made in-process: ' . $home->get_error_message();
	} else {
		$body = wp_remote_retrieve_body( $home );
		$ok( 'the storefront returns 200 with the installed theme', 200 === wp_remote_retrieve_response_code( $home ) );
		$ok( 'the phone bottom bar is in the markup', false !== strpos( $body, 'sb-bottom-bar' ) );
	}
}

$lines[] = '';
$lines[] = "$pass passed, $fail failed";
file_put_contents( '/wordpress/sb-out/install-report.txt', implode( "\n", $lines ) . "\n" );
