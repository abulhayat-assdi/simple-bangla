<?php
/**
 * Hot Deals — a horizontal strip of products that are currently on sale.
 *
 * Renders nothing when nothing is discounted, which is the honest outcome: an empty
 * "Hot Deals" shelf is worse than no shelf.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_query = simple_bangla_product_query(
	array(
		'count'   => (int) simple_bangla_home_option( 'hotdeals_count', 8 ),
		'on_sale' => true,
		'orderby' => 'date',
	)
);

if ( ! $simple_bangla_query || ! $simple_bangla_query->have_posts() ) {
	return;
}

$simple_bangla_heading = simple_bangla_home_option( 'hotdeals_heading', __( 'Hot Deals', 'simple-bangla' ) );
$simple_bangla_shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
?>
<section class="sb-home-section sb-home-section--deals">
	<div class="sb-container">
		<?php
		simple_bangla_section_head( $simple_bangla_heading, $simple_bangla_shop );
		simple_bangla_render_product_loop( $simple_bangla_query, true );
		?>
	</div>
</section>
