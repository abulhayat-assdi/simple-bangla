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

		<?php
		/**
		 * This hook is documented in woocommerce/templates/content-single-product.php.
		 *
		 * WooCommerce hangs its notice output here and nothing else, so without it an "added to
		 * your cart" confirmation had nowhere to print — and neither did the error when a variation
		 * was not chosen or stock had run out. With AJAX add-to-cart off on product pages, the page
		 * simply reloaded and said nothing at all.
		 */
		do_action( 'woocommerce_before_single_product' );
		?>

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
			</main>

		</div>

		<?php
		/** This hook is documented in woocommerce/templates/content-single-product.php */
		do_action( 'woocommerce_after_single_product_summary' );
		?>

		<?php
		// Products from this product's own categories. Replaces the recently-viewed strip that
		// used to sit here — a shopper on a product page wants alternatives to it, not a list
		// of the pages they have already been on.
		if ( function_exists( 'simple_bangla_related_products' ) ) {
			simple_bangla_related_products( get_the_ID() );
		}
		?>

	</div>

	<?php
endwhile;

get_footer();
