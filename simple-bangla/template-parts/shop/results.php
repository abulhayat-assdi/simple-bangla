<?php
/**
 * The product grid plus its pagination.
 *
 * Rendered inside the archive on a normal page load, and returned on its own when
 * inc/ajax-filter.php short-circuits an `sb_ajax=1` request. Keeping it in one file is what
 * lets the filtered and unfiltered paths stay identical.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;

if ( ! woocommerce_product_loop() || ! have_posts() ) {
	?>
	<div class="sb-no-results">
		<h2 class="sb-no-results__title"><?php esc_html_e( 'No products found', 'simple-bangla' ); ?></h2>
		<p>
			<?php
			echo simple_bangla_has_active_filters()
				? esc_html__( 'Nothing matches those filters. Try widening the price range or clearing them.', 'simple-bangla' )
				: esc_html__( 'There is nothing in this category yet. Have a look at the rest of the shop.', 'simple-bangla' );
			?>
		</p>
		<?php if ( simple_bangla_has_active_filters() ) : ?>
			<p>
				<a class="sb-btn sb-btn--ghost" href="<?php echo esc_url( simple_bangla_listing_base_url() ); ?>">
					<?php esc_html_e( 'Clear filters', 'simple-bangla' ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
	<?php
	return;
}

/*
 * get_next_posts_page_link() happily returns a link to a page that does not exist, so the
 * bound is checked here rather than trusted.
 */
$simple_bangla_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$simple_bangla_next  = ( $simple_bangla_paged < (int) $wp_query->max_num_pages )
	? get_next_posts_page_link()
	: '';
?>

<ul class="sb-products">
	<?php
	while ( have_posts() ) {
		the_post();
		wc_get_template_part( 'content', 'product' );
	}
	?>
</ul>

<?php if ( $simple_bangla_next ) : ?>
	<div class="sb-shop__more">
		<?php // shop.js turns this into a Load more button; without it, it is plain pagination. ?>
		<a class="sb-btn sb-btn--ghost" href="<?php echo esc_url( $simple_bangla_next ); ?>" data-sb-next rel="next">
			<?php esc_html_e( 'Load more products', 'simple-bangla' ); ?>
		</a>
	</div>
<?php endif; ?>

<?php
// The numbered links stay in the markup for crawlers and for anyone without JavaScript.
the_posts_pagination(
	array(
		'mid_size'           => 1,
		'prev_text'          => esc_html__( 'Previous', 'simple-bangla' ),
		'next_text'          => esc_html__( 'Next', 'simple-bangla' ),
		'screen_reader_text' => esc_html__( 'Product pages', 'simple-bangla' ),
		'class'              => 'sb-pagination sb-shop__pagination',
	)
);
