<?php
/**
 * Shop and product category archives.
 *
 * A full override rather than a hook arrangement: the filter panel and the widget area have to
 * share one collapsible container on mobile, and WooCommerce's hook order cannot express that.
 *
 * @package Simple_Bangla
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="sb-container sb-shop">

	<?php woocommerce_breadcrumb(); ?>

	<header class="sb-page-header">
		<h1 class="sb-page-header__title"><?php woocommerce_page_title(); ?></h1>
		<?php
		if ( is_product_taxonomy() ) {
			$simple_bangla_description = term_description();

			if ( $simple_bangla_description ) {
				printf( '<div class="sb-page-header__description">%s</div>', wp_kses_post( $simple_bangla_description ) );
			}
		}
		?>
	</header>

	<div class="sb-shop__layout">

		<?php get_template_part( 'template-parts/shop/filters' ); ?>

		<main id="main" class="sb-shop__main">

			<?php
			/**
			 * This hook is documented in woocommerce/templates/archive-product.php.
			 *
			 * Fired for WooCommerce's notices, which had nowhere to print on this template — an
			 * "added to your cart" confirmation or a stock error simply never appeared. Its result
			 * count and sort callbacks are unhooked in inc/woocommerce.php, because the toolbar
			 * below calls both directly.
			 */
			do_action( 'woocommerce_before_shop_loop' );
			?>

			<div class="sb-shop__toolbar">
				<?php woocommerce_result_count(); ?>
				<?php woocommerce_catalog_ordering(); ?>

				<button type="button" class="sb-btn sb-btn--ghost sb-btn--sm sb-shop__filter-toggle" aria-expanded="false" aria-controls="sb-shop-filters">
					<?php simple_bangla_icon( 'filter', 16 ); ?>
					<?php esc_html_e( 'Filters', 'simple-bangla' ); ?>
				</button>
			</div>

			<div class="sb-shop__results" id="sb-shop-results" data-sb-results>
				<?php get_template_part( 'template-parts/shop/results' ); ?>
			</div>

		</main>

	</div>

	<?php
	if ( function_exists( 'simple_bangla_recently_viewed' ) ) {
		simple_bangla_recently_viewed();
	}
	?>

</div>

<?php
get_footer();
