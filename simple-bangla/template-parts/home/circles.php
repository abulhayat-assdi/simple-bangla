<?php
/**
 * Category circles — round thumbnails linking to the top-level product categories.
 *
 * A category with no thumbnail still renders: the frame falls back to the first letter of
 * the category name, so an unfinished catalogue does not leave holes in the row.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

if ( ! taxonomy_exists( 'product_cat' ) ) {
	return;
}

$simple_bangla_terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'parent'     => 0,
		'hide_empty' => false,
		'number'     => (int) simple_bangla_home_option( 'circles_count', 6 ),
		'orderby'    => 'menu_order',
	)
);

if ( is_wp_error( $simple_bangla_terms ) || empty( $simple_bangla_terms ) ) {
	return;
}
?>
<section class="sb-home-section sb-home-section--circles">
	<div class="sb-container">
		<ul class="sb-circles">
			<?php foreach ( $simple_bangla_terms as $simple_bangla_term ) : ?>
				<?php
				$simple_bangla_link  = get_term_link( $simple_bangla_term );
				$simple_bangla_thumb = (int) get_term_meta( $simple_bangla_term->term_id, 'thumbnail_id', true );

				if ( is_wp_error( $simple_bangla_link ) ) {
					continue;
				}
				?>
				<li class="sb-circles__item">
					<a class="sb-circles__link" href="<?php echo esc_url( $simple_bangla_link ); ?>">
						<span class="sb-circles__frame">
							<?php if ( $simple_bangla_thumb ) : ?>
								<?php
								echo wp_kses_post(
									wp_get_attachment_image(
										$simple_bangla_thumb,
										'simple-bangla-category-icon',
										false,
										array(
											'alt'     => '',
											'loading' => 'lazy',
										)
									)
								);
								?>
							<?php else : ?>
								<span class="sb-circles__initial" aria-hidden="true">
									<?php echo esc_html( mb_substr( $simple_bangla_term->name, 0, 1 ) ); ?>
								</span>
							<?php endif; ?>
						</span>
						<span class="sb-circles__label"><?php echo esc_html( $simple_bangla_term->name ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
