<?php
/**
 * The three reassurance lines under the add-to-cart area.
 *
 * Delivery, warranty and returns are the questions a gadget shopper in Bangladesh asks before
 * anything else, so they are answered on the page rather than buried in a policy link.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_points = array(
	array(
		'icon'  => 'truck',
		'title' => __( 'Fast delivery', 'simple-bangla' ),
		'text'  => __( 'Next day inside Dhaka, 2–3 days elsewhere.', 'simple-bangla' ),
	),
	array(
		'icon'  => 'shield',
		'title' => __( 'Warranty covered', 'simple-bangla' ),
		'text'  => __( 'Every product carries its manufacturer warranty.', 'simple-bangla' ),
	),
	array(
		'icon'  => 'refresh',
		'title' => __( 'Easy returns', 'simple-bangla' ),
		'text'  => __( 'Seven days to return anything unopened.', 'simple-bangla' ),
	),
);
?>
<ul class="sb-assurances">
	<?php foreach ( $simple_bangla_points as $simple_bangla_point ) : ?>
		<li class="sb-assurances__item">
			<?php simple_bangla_icon( $simple_bangla_point['icon'], 22 ); ?>
			<span>
				<strong><?php echo esc_html( $simple_bangla_point['title'] ); ?></strong>
				<span><?php echo esc_html( $simple_bangla_point['text'] ); ?></span>
			</span>
		</li>
	<?php endforeach; ?>
</ul>
