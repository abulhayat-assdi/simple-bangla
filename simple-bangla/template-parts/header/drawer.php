<?php
/**
 * Off-canvas navigation for small screens.
 *
 * The same menu markup as the desktop bar, moved into a panel. It is rendered once and
 * revealed with CSS rather than duplicated per breakpoint — one menu in the DOM, one source
 * of truth for the walker's aria state.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sb-drawer" id="sb-drawer" hidden>

	<div class="sb-drawer__backdrop" data-sb-drawer-close></div>

	<div class="sb-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Site menu', 'simple-bangla' ); ?>">

		<div class="sb-drawer__head">
			<span class="sb-drawer__title"><?php esc_html_e( 'Menu', 'simple-bangla' ); ?></span>
			<button type="button" class="sb-drawer__close" data-sb-drawer-close>
				<?php simple_bangla_icon( 'close' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'simple-bangla' ); ?></span>
			</button>
		</div>

		<nav class="sb-drawer__nav" aria-label="<?php esc_attr_e( 'Mobile', 'simple-bangla' ); ?>">
			<?php simple_bangla_primary_menu(); ?>
		</nav>

		<div class="sb-drawer__foot">
			<a class="sb-btn sb-btn--block" href="<?php echo esc_url( simple_bangla_account_url() ); ?>">
				<?php simple_bangla_icon( 'user' ); ?>
				<?php esc_html_e( 'My Account', 'simple-bangla' ); ?>
			</a>
			<?php
			$simple_bangla_phone = simple_bangla_get_contact( 'phone' );

			if ( $simple_bangla_phone ) :
				?>
				<a class="sb-drawer__phone" href="<?php echo esc_url( 'tel:' . simple_bangla_tel_href( $simple_bangla_phone ) ); ?>">
					<?php simple_bangla_icon( 'phone' ); ?>
					<?php echo esc_html( $simple_bangla_phone ); ?>
				</a>
			<?php endif; ?>
		</div>

	</div>
</div>
