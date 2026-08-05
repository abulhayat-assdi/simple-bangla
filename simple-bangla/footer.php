<?php
/**
 * Site footer.
 *
 * White background with dark headings, matching the reference — a dark footer would fight the
 * black header bar for weight at opposite ends of the page.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>

	<footer id="colophon" class="sb-footer">

		<?php get_template_part( 'template-parts/footer/brand' ); ?>
		<?php get_template_part( 'template-parts/footer/columns' ); ?>

		<?php
		$simple_bangla_payment = (int) get_theme_mod( 'simple_bangla_payment_strip', 0 );

		if ( $simple_bangla_payment ) :
			?>
			<div class="sb-container sb-footer__payments">
				<?php
				echo wp_kses_post(
					wp_get_attachment_image(
						$simple_bangla_payment,
						'large',
						false,
						array(
							'alt'     => esc_attr__( 'Accepted payment methods', 'simple-bangla' ),
							'loading' => 'lazy',
						)
					)
				);
				?>
			</div>
		<?php endif; ?>

		<div class="sb-container">
			<p class="sb-footer__copyright">
				<?php
				printf(
					/* translators: 1: current year, 2: site name. */
					esc_html__( '© %1$s %2$s. All rights reserved.', 'simple-bangla' ),
					// wp_date(), not gmdate(): the copyright year should follow the store's
					// timezone, or a Dhaka shop shows last year for its first six hours.
					esc_html( wp_date( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>
		</div>

	</footer>

	<?php get_template_part( 'template-parts/footer/floats' ); ?>
	<?php get_template_part( 'template-parts/footer/mobile-bar' ); ?>

</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
