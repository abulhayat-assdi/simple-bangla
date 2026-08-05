<?php
/**
 * A banner pair: one narrow panel beside one wide panel.
 *
 * The reference shows banners only ever as a pair, twice down the page — not as the four
 * separate full-width strips the written spec described.
 *
 * @param array $args {
 *     @type int $pair Pair number, 1-based.
 * }
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_pair = isset( $args['pair'] ) ? (int) $args['pair'] : 0;

if ( ! $simple_bangla_pair ) {
	return;
}

$simple_bangla_slots = array();

foreach ( array( 'small', 'wide' ) as $simple_bangla_slot ) {

	$simple_bangla_key   = 'simple_bangla_home_banner_' . $simple_bangla_pair . '_' . $simple_bangla_slot;
	$simple_bangla_image = (int) get_theme_mod( $simple_bangla_key . '_image', 0 );

	if ( ! $simple_bangla_image ) {
		continue;
	}

	$simple_bangla_slots[ $simple_bangla_slot ] = array(
		'image' => $simple_bangla_image,
		'link'  => (string) get_theme_mod( $simple_bangla_key . '_link', '' ),
	);
}

// One banner alone still reads fine; nothing configured means nothing to show.
if ( empty( $simple_bangla_slots ) ) {
	return;
}
?>
<section class="sb-home-section sb-home-section--banners">
	<div class="sb-container">
		<div class="sb-banners">
			<?php foreach ( $simple_bangla_slots as $simple_bangla_slot => $simple_bangla_banner ) : ?>
				<?php
				$simple_bangla_markup = wp_get_attachment_image(
					$simple_bangla_banner['image'],
					'large',
					false,
					array(
						'class'   => 'sb-banners__image',
						'alt'     => '',
						'loading' => 'lazy',
					)
				);

				if ( ! $simple_bangla_markup ) {
					continue;
				}

				$simple_bangla_class = 'sb-banners__item sb-banners__item--' . $simple_bangla_slot;
				?>
				<?php if ( $simple_bangla_banner['link'] ) : ?>
					<a class="<?php echo esc_attr( $simple_bangla_class ); ?>" href="<?php echo esc_url( $simple_bangla_banner['link'] ); ?>">
						<?php echo wp_kses_post( $simple_bangla_markup ); ?>
					</a>
				<?php else : ?>
					<div class="<?php echo esc_attr( $simple_bangla_class ); ?>">
						<?php echo wp_kses_post( $simple_bangla_markup ); ?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
