<?php
/**
 * The three-step progress bar above the cart, checkout and thank-you pages.
 *
 * Purely informational — it reports where the shopper is, it is not navigation. Steps already
 * passed are links back; the current one and the ones ahead are not.
 *
 * @param array $args {
 *     @type string $current One of 'cart', 'review', 'done'.
 * }
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_current = isset( $args['current'] ) ? $args['current'] : 'cart';

$simple_bangla_steps = array(
	'cart'   => array(
		'label' => __( 'কার্ট', 'simple-bangla' ),
		'icon'  => 'cart',
		'url'   => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
	),
	'review' => array(
		'label' => __( 'অর্ডার', 'simple-bangla' ),
		'icon'  => 'truck',
		'url'   => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
	),
	'done'   => array(
		'label' => __( 'সম্পন্ন', 'simple-bangla' ),
		'icon'  => 'check',
		'url'   => '',
	),
);

$simple_bangla_order = array_keys( $simple_bangla_steps );
$simple_bangla_index = array_search( $simple_bangla_current, $simple_bangla_order, true );
$simple_bangla_index = ( false === $simple_bangla_index ) ? 0 : $simple_bangla_index;
?>
<ol class="sb-steps">
	<?php foreach ( $simple_bangla_order as $simple_bangla_position => $simple_bangla_key ) : ?>
		<?php
		$simple_bangla_step = $simple_bangla_steps[ $simple_bangla_key ];

		$simple_bangla_state = 'ahead';

		if ( $simple_bangla_position < $simple_bangla_index ) {
			$simple_bangla_state = 'done';
		} elseif ( $simple_bangla_position === $simple_bangla_index ) {
			$simple_bangla_state = 'current';
		}

		// Only a step already completed is worth offering as a way back.
		$simple_bangla_link = ( 'done' === $simple_bangla_state && $simple_bangla_step['url'] );
		$simple_bangla_tag  = $simple_bangla_link ? 'a' : 'span';
		?>
		<li class="sb-steps__item sb-steps__item--<?php echo esc_attr( $simple_bangla_state ); ?>">
			<<?php echo esc_attr( $simple_bangla_tag ); ?>
				class="sb-steps__link"
				<?php echo $simple_bangla_link ? 'href="' . esc_url( $simple_bangla_step['url'] ) . '"' : ''; ?>
				<?php echo ( 'current' === $simple_bangla_state ) ? 'aria-current="step"' : ''; ?>
			>
				<span class="sb-steps__marker">
					<?php simple_bangla_icon( 'done' === $simple_bangla_state ? 'check' : $simple_bangla_step['icon'], 20 ); ?>
				</span>
				<span class="sb-steps__label"><?php echo esc_html( $simple_bangla_step['label'] ); ?></span>
			</<?php echo esc_attr( $simple_bangla_tag ); ?>>
		</li>
	<?php endforeach; ?>
</ol>
