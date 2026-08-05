<?php
/**
 * The storefront homepage.
 *
 * A fixed sequence of sections whose content comes from the Customizer — see
 * inc/customizer-home.php. The order matches what the reference site actually renders:
 * deals, categories, two rows, banners, two rows, banners, two rows.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="sb-home">

	<?php
	get_template_part( 'template-parts/home/hero' );
	get_template_part( 'template-parts/home/circles' );

	/*
	 * Rows and banner pairs interleave: after rows 2 and 4 a banner pair appears. Driving both
	 * from one loop keeps that rhythm in a single place instead of eight hard-coded calls.
	 */
	$simple_bangla_banner_after = array(
		2 => 1,
		4 => 2,
	);

	for ( $simple_bangla_row = 1; $simple_bangla_row <= SIMPLE_BANGLA_HOME_ROWS; $simple_bangla_row++ ) {

		get_template_part(
			'template-parts/home/product-row',
			null,
			array( 'row' => $simple_bangla_row )
		);

		if ( isset( $simple_bangla_banner_after[ $simple_bangla_row ] ) ) {
			get_template_part(
				'template-parts/home/banner-pair',
				null,
				array( 'pair' => $simple_bangla_banner_after[ $simple_bangla_row ] )
			);
		}
	}

	if ( ! post_type_exists( 'product' ) ) :
		?>
		<div class="sb-container sb-home__notice">
			<p><?php esc_html_e( 'Activate WooCommerce to fill this homepage with your products.', 'simple-bangla' ); ?></p>
		</div>
		<?php
	endif;
	?>

</main>

<?php
get_footer();
