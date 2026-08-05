<?php
/**
 * The product card.
 *
 * Used by the shop, every product archive, search results, the homepage rows and the related
 * products strip — one card definition for the whole site.
 *
 * This template deliberately does not fire the stock `woocommerce_before_shop_loop_item` /
 * `woocommerce_after_shop_loop_item` action stack. Those hooks exist to assemble WooCommerce's
 * own card out of parts, and running them here would append a second, differently-shaped
 * add-to-cart button under ours. The one integration point worth keeping is
 * `woocommerce_before_shop_loop_item_title`, which is where badge-style plugins hook in.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product || ! $product->is_visible() ) {
	return;
}

$simple_bangla_permalink = $product->get_permalink();
?>
<li <?php wc_product_class( 'sb-card', $product ); ?>>

	<div class="sb-card__media">

		<?php if ( $product->is_on_sale() ) : ?>
			<span class="sb-card__badge">
				<?php
				/**
				 * Filter the on-sale ribbon text.
				 *
				 * @param string     $text    Ribbon copy.
				 * @param WC_Product $product The product being rendered.
				 */
				echo esc_html( apply_filters( 'simple_bangla_sale_badge_text', __( 'Hot Deals', 'simple-bangla' ), $product ) );
				?>
			</span>
		<?php endif; ?>

		<?php if ( ! $product->is_in_stock() ) : ?>
			<span class="sb-card__badge sb-card__badge--out"><?php esc_html_e( 'Out of stock', 'simple-bangla' ); ?></span>
		<?php endif; ?>

		<a class="sb-card__link" href="<?php echo esc_url( $simple_bangla_permalink ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			/*
			 * woocommerce_template_loop_product_thumbnail() would emit the placeholder at the
			 * theme's card size and handle srcset for us, but it wraps nothing — the 1:1 frame
			 * below is what stops a portrait photo from breaking the grid rhythm.
			 */
			echo wp_kses_post( $product->get_image( 'simple-bangla-card', array( 'class' => 'sb-card__image' ) ) );
			?>
		</a>

		<?php
		/** This hook is documented in woocommerce/templates/content-product.php */
		do_action( 'woocommerce_before_shop_loop_item_title' );
		?>
	</div>

	<div class="sb-card__body">

		<h3 class="sb-card__title">
			<a href="<?php echo esc_url( $simple_bangla_permalink ); ?>">
				<?php echo esc_html( $product->get_name() ); ?>
			</a>
		</h3>

		<?php if ( $product->get_price_html() ) : ?>
			<div class="sb-card__price">
				<?php echo wp_kses_post( $product->get_price_html() ); ?>
			</div>
		<?php endif; ?>

		<a class="sb-btn sb-card__cta" href="<?php echo esc_url( $simple_bangla_permalink ); ?>">
			<?php esc_html_e( 'Order Now', 'simple-bangla' ); ?>
			<span class="screen-reader-text"><?php echo esc_html( $product->get_name() ); ?></span>
		</a>

	</div>
</li>
