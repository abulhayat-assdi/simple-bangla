<?php
/**
 * The desktop navigation row.
 *
 * Hybrid arrangement: the reference hides its whole menu behind a burger at every width, so
 * the burger is reproduced here as an "All Categories" button that opens the same drawer.
 * The mega menu stays beside it, because burying a shop's categories behind one extra click
 * costs sales.
 *
 * Below 1024px this row is hidden and the drawer takes over on its own.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>
<nav class="sb-nav" aria-label="<?php esc_attr_e( 'Primary', 'simple-bangla' ); ?>">
	<div class="sb-container sb-nav__inner">

		<button
			type="button"
			class="sb-nav__categories"
			aria-expanded="false"
			aria-controls="sb-drawer"
			data-sb-drawer-open
		>
			<?php simple_bangla_icon( 'menu', 20 ); ?>
			<span><?php esc_html_e( 'All Categories', 'simple-bangla' ); ?></span>
		</button>

		<?php
		/*
		 * No hotline here. The reference's nav strip carries nothing but the menu, and the
		 * phone number already appears in the drawer, the footer and the mobile Call button.
		 */
		simple_bangla_primary_menu();
		?>

	</div>
</nav>
