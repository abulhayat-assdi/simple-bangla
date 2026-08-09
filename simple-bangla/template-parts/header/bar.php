<?php
/**
 * The black logo bar: menu trigger, branding, product search, account and cart.
 *
 * Account is icon-only and the cart sits in an outlined box showing the running total —
 * both measured off the reference header rather than guessed.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sb-header__bar">
	<div class="sb-container sb-header__inner">

		<button
			type="button"
			class="sb-header__burger"
			aria-expanded="false"
			aria-controls="sb-drawer"
			data-sb-drawer-open
		>
			<?php simple_bangla_icon( 'menu' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Open menu', 'simple-bangla' ); ?></span>
		</button>

		<div class="sb-header__brand">
			<?php simple_bangla_site_branding(); ?>
		</div>

		<div class="sb-header__search">
			<?php simple_bangla_product_search_form(); ?>
		</div>

		<div class="sb-header__actions">
			<?php
			/*
			 * No account icon. Every order here is a guest cash-on-delivery order, so the link
			 * only ever led to WooCommerce's My Account page — a sign-in form for accounts the
			 * shop does not ask anyone to create. simple_bangla_account_url() is still there if
			 * it is ever wanted back.
			 */
			simple_bangla_cart_link();
			?>
		</div>

	</div>
</div>
