<?php
/**
 * One configurable product row.
 *
 * The heading and the category are read from two separate settings on purpose. The reference
 * site derives one from the other and gets it wrong on five of six rows — a shelf labelled
 * "Powerbank" listing earbuds. Keeping them independent is what makes that impossible here.
 *
 * @param array $args {
 *     @type int $row Row number, 1-based.
 * }
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_row = isset( $args['row'] ) ? (int) $args['row'] : 0;

if ( ! $simple_bangla_row ) {
	return;
}

if ( ! simple_bangla_home_option( 'row_' . $simple_bangla_row . '_enabled', true ) ) {
	return;
}

$simple_bangla_defaults = simple_bangla_home_row_defaults();
$simple_bangla_term_id  = simple_bangla_home_row_term( $simple_bangla_row );

$simple_bangla_query = simple_bangla_product_query(
	array(
		'term_id' => $simple_bangla_term_id,
		'count'   => (int) simple_bangla_home_option( 'row_' . $simple_bangla_row . '_count', 12 ),
	)
);

if ( ! $simple_bangla_query || ! $simple_bangla_query->have_posts() ) {
	return;
}

$simple_bangla_heading = simple_bangla_home_option(
	'row_' . $simple_bangla_row . '_heading',
	isset( $simple_bangla_defaults[ $simple_bangla_row ] ) ? $simple_bangla_defaults[ $simple_bangla_row ]['heading'] : ''
);

$simple_bangla_view_all = '';

if ( $simple_bangla_term_id ) {
	$simple_bangla_link = get_term_link( $simple_bangla_term_id, 'product_cat' );

	if ( ! is_wp_error( $simple_bangla_link ) ) {
		$simple_bangla_view_all = $simple_bangla_link;
	}
} elseif ( function_exists( 'wc_get_page_permalink' ) ) {
	$simple_bangla_view_all = wc_get_page_permalink( 'shop' );
}
?>
<section class="sb-home-section sb-home-section--row">
	<div class="sb-container">
		<?php
		simple_bangla_section_head( $simple_bangla_heading, $simple_bangla_view_all );
		simple_bangla_render_product_loop( $simple_bangla_query, true );
		?>
	</div>
</section>
