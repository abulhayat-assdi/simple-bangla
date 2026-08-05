<?php
/**
 * Site footer.
 *
 * One grid row: brand block, three link columns, map. Then the payment strip, then a
 * full-bleed black copyright bar. That is the reference's arrangement, measured off its own
 * markup and stylesheet rather than guessed from a screenshot.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>

	<footer id="colophon" class="sb-footer">

		<div class="sb-container sb-footer__grid">
			<?php
			get_template_part( 'template-parts/footer/brand' );
			get_template_part( 'template-parts/footer/columns' );
			?>
		</div>

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

		<div class="sb-footer__bar">
			<div class="sb-container">
				<p class="sb-footer__copyright">
					<?php
					printf(
						/* translators: 1: current year, 2: site name. */
						esc_html__( '© %1$s %2$s. All rights reserved', 'simple-bangla' ),
						// wp_date(), not gmdate(): the copyright year should follow the store's
						// timezone, or a Dhaka shop shows last year for its first six hours.
						esc_html( wp_date( 'Y' ) ),
						esc_html( get_bloginfo( 'name' ) )
					);
					?>
				</p>
			</div>
		</div>

	</footer>

	<?php get_template_part( 'template-parts/footer/floats' ); ?>
	<?php get_template_part( 'template-parts/footer/mobile-bar' ); ?>

</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
