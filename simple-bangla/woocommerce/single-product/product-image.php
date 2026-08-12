<?php
/**
 * Product gallery.
 *
 * Replaces WooCommerce's flexslider/photoswipe gallery with a main image and a row of
 * thumbnails. Without assets/js/product.js the thumbnails are still anchors to the full-size
 * files, so the gallery degrades to something usable rather than something broken.
 *
 * @package Simple_Bangla
 * @version 10.5.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$simple_bangla_main_id  = $product->get_image_id();
$simple_bangla_gallery  = $product->get_gallery_image_ids();
$simple_bangla_all      = array_values( array_filter( array_merge( array( $simple_bangla_main_id ), $simple_bangla_gallery ) ) );
$simple_bangla_has_more = count( $simple_bangla_all ) > 1;
?>
<div class="sb-gallery" data-sb-gallery>

	<div class="sb-gallery__stage">
		<?php if ( $product->is_on_sale() ) : ?>
			<span class="sb-card__badge"><?php esc_html_e( 'Hot Deals', 'simple-bangla' ); ?></span>
		<?php endif; ?>

		<?php
		if ( $simple_bangla_main_id ) {
			echo wp_kses_post(
				wp_get_attachment_image(
					$simple_bangla_main_id,
					'simple-bangla-card',
					false,
					array(
						'class'         => 'sb-gallery__image',
						'data-sb-stage' => 'true',
						'alt'           => $product->get_name(),
						// The hero image is the largest thing above the fold; never defer it.
						'fetchpriority' => 'high',
					)
				)
			);
		} else {
			printf(
				'<img class="sb-gallery__image" src="%s" alt="%s" width="600" height="600" />',
				esc_url( wc_placeholder_img_src( 'simple-bangla-card' ) ),
				esc_attr( $product->get_name() )
			);
		}
		?>
	</div>

	<?php if ( $simple_bangla_has_more ) : ?>
		<ul class="sb-gallery__thumbs">
			<?php foreach ( $simple_bangla_all as $simple_bangla_index => $simple_bangla_id ) : ?>
				<?php
				$simple_bangla_full = wp_get_attachment_image_url( $simple_bangla_id, 'simple-bangla-card' );

				if ( ! $simple_bangla_full ) {
					continue;
				}

				$simple_bangla_srcset = wp_get_attachment_image_srcset( $simple_bangla_id, 'simple-bangla-card' );
				?>
				<li>
					<a
						class="sb-gallery__thumb<?php echo 0 === $simple_bangla_index ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $simple_bangla_full ); ?>"
						data-sb-full="<?php echo esc_url( $simple_bangla_full ); ?>"
						data-sb-srcset="<?php echo esc_attr( (string) $simple_bangla_srcset ); ?>"
					>
						<?php
						echo wp_kses_post(
							wp_get_attachment_image(
								$simple_bangla_id,
								'thumbnail',
								false,
								array(
									'alt'     => '',
									'loading' => 'lazy',
								)
							)
						);
						?>
						<span class="screen-reader-text">
							<?php
							printf(
								/* translators: %d: image number within the gallery. */
								esc_html__( 'Show image %d', 'simple-bangla' ),
								(int) $simple_bangla_index + 1
							);
							?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

</div>
