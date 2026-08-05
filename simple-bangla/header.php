<?php
/**
 * Site header.
 *
 * Phase 1 ships a working, accessible baseline: skip link, branding, product search and a
 * plain primary menu. Phase 2 replaces the nav block with the three-level mega menu walker,
 * the sticky behaviour and the live cart widget.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main">
	<?php esc_html_e( 'Skip to content', 'simple-bangla' ); ?>
</a>

<div id="page" class="sb-site">

	<header id="masthead" class="sb-header">

		<div class="sb-header__bar">
			<div class="sb-container sb-header__inner">

				<div class="sb-header__brand">
					<?php simple_bangla_site_branding(); ?>
				</div>

				<div class="sb-header__search">
					<?php simple_bangla_product_search_form(); ?>
				</div>

				<div class="sb-header__actions">
					<a class="sb-header__action" href="<?php echo esc_url( simple_bangla_account_url() ); ?>">
						<?php simple_bangla_icon( 'user' ); ?>
						<span class="sb-header__action-label"><?php esc_html_e( 'Account', 'simple-bangla' ); ?></span>
					</a>
					<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
						<a class="sb-header__action" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
							<?php simple_bangla_icon( 'cart' ); ?>
							<span class="sb-header__action-label"><?php esc_html_e( 'Cart', 'simple-bangla' ); ?></span>
						</a>
					<?php endif; ?>
				</div>

			</div>
		</div>

		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<nav class="sb-nav" aria-label="<?php esc_attr_e( 'Primary', 'simple-bangla' ); ?>">
				<div class="sb-container">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'sb-nav__list',
							'depth'          => 3,
						)
					);
					?>
				</div>
			</nav>
		<?php endif; ?>

	</header>
