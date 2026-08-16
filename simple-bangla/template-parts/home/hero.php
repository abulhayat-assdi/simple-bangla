<?php
/**
 * The hero row: a narrow Hot Deals card slider beside the wide banner carousel.
 *
 * This is the arrangement the reference actually opens with — not a full-width Hot Deals
 * shelf, and not a bare hero. Either half renders on its own if the other has nothing to
 * show, so a store with no banners uploaded still gets a sensible top of page.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_slides = simple_bangla_hero_slides();

$simple_bangla_deals = simple_bangla_product_query(
	array(
		'count'   => (int) simple_bangla_home_option( 'hotdeals_count', 8 ),
		'on_sale' => true,
		'orderby' => 'date',
	)
);

$simple_bangla_has_deals = $simple_bangla_deals && $simple_bangla_deals->have_posts();

if ( ! $simple_bangla_has_deals && empty( $simple_bangla_slides ) ) {
	return;
}

$simple_bangla_classes = 'sb-hero__grid';

if ( ! $simple_bangla_has_deals || empty( $simple_bangla_slides ) ) {
	$simple_bangla_classes .= ' sb-hero__grid--single';
}
?>
<section class="sb-hero">
	<div class="sb-container <?php echo esc_attr( $simple_bangla_classes ); ?>">

		<?php if ( $simple_bangla_has_deals ) : ?>
			<div class="sb-hero__deals">
				<?php
				// One card at a time; the width is set by the --sb-per-view override in home.css.
				simple_bangla_render_product_loop( $simple_bangla_deals, true, 'sb-products--single' );
				?>
			</div>
		<?php endif; ?>

		<?php if ( $simple_bangla_slides ) : ?>
			<div class="sb-hero__banner sb-slider" data-sb-slider>
				<div class="sb-hero__track" data-sb-track>
					<?php foreach ( $simple_bangla_slides as $simple_bangla_index => $simple_bangla_slide ) : ?>
						<?php
						$simple_bangla_attrs = array(
							'class' => 'sb-hero__image',
							'alt'   => '',
						);

						/*
						 * The hero is the largest thing above the fold, so the first slide is
						 * fetched at high priority and the rest are deferred.
						 */
						if ( 0 === $simple_bangla_index ) {
							$simple_bangla_attrs['fetchpriority'] = 'high';
						} else {
							$simple_bangla_attrs['loading'] = 'lazy';
						}

						/*
						 * Load the full uncropped image so custom banner graphics fit perfectly
						 * without cutting off top/bottom text or icons.
						 */
						$simple_bangla_image = wp_get_attachment_image(
							$simple_bangla_slide['image'],
							'full',
							false,
							$simple_bangla_attrs
						);

						if ( ! $simple_bangla_image ) {
							continue;
						}
						?>
						<?php if ( $simple_bangla_slide['link'] ) : ?>
							<a class="sb-hero__slide" href="<?php echo esc_url( $simple_bangla_slide['link'] ); ?>">
								<?php echo wp_kses_post( $simple_bangla_image ); ?>
							</a>
						<?php else : ?>
							<div class="sb-hero__slide">
								<?php echo wp_kses_post( $simple_bangla_image ); ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

	</div>
</section>
