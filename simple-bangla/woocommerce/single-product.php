<?php
/**
 * Single product.
 *
 * A full override so the gallery, summary and trust row can share one grid. Everything inside
 * the summary still comes from WooCommerce's own hooks, so variations, add-to-cart, reviews
 * and any plugin that hooks the summary all keep working.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	global $product;
	?>

	<div class="sb-container sb-product">

		<?php woocommerce_breadcrumb(); ?>

		<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'sb-product__layout', $product ); ?>>

			<div class="sb-product__gallery">
				<?php
				/** This hook is documented in woocommerce/templates/content-single-product.php */
				do_action( 'woocommerce_before_single_product_summary' );
				?>
			</div>

			<main id="main" class="sb-product__summary">
				<?php
				/** This hook is documented in woocommerce/templates/content-single-product.php */
				do_action( 'woocommerce_single_product_summary' );
				?>

				<?php get_template_part( 'template-parts/product/assurances' ); ?>
			</main>

		</div>

		<?php
		/** This hook is documented in woocommerce/templates/content-single-product.php */
		do_action( 'woocommerce_after_single_product_summary' );
		?>

		<?php
		if ( function_exists( 'simple_bangla_recently_viewed' ) ) {
			simple_bangla_recently_viewed( get_the_ID() );
		}
		?>

	</div>

	<?php
endwhile;

get_footer();
