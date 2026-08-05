<?php
/**
 * Sidebar.
 *
 * Renders whichever widget area suits the current view, or nothing at all — the layout CSS
 * collapses to a single column when this outputs no markup.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_sidebar_id = simple_bangla_active_sidebar_id();

if ( ! $simple_bangla_sidebar_id ) {
	return;
}
?>
<aside id="secondary" class="sb-sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'simple-bangla' ); ?>">
	<?php dynamic_sidebar( $simple_bangla_sidebar_id ); ?>
</aside>
