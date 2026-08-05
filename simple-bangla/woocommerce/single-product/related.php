<?php
/**
 * Related products.
 *
 * Overridden so the strip uses the theme's own card grid and slider markup instead of
 * WooCommerce's `ul.products.columns-4`, which none of the theme's CSS knows about.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $related_products ) ) {
	return;
}
?>
<section class="sb-strip sb-related">
	<?php simple_bangla_section_head( __( 'You may also like', 'simple-bangla' ) ); ?>

	<div class="sb-slider" data-sb-slider>
		<ul class="sb-products sb-products--slider">
			<?php
			foreach ( $related_products as $related_product ) {

				$post_object = get_post( $related_product->get_id() );

				setup_postdata( $GLOBALS['post'] = $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found

				wc_get_template_part( 'content', 'product' );
			}
			?>
		</ul>
	</div>
</section>
<?php
wp_reset_postdata();
