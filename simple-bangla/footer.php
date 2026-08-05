<?php
/**
 * Site footer.
 *
 * Phase 1 baseline only. Phase 6 replaces the inner blocks with the four-column footer,
 * the payment strip, the scroll-to-top button, the mobile sticky bar and the WhatsApp float.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>

	<footer id="colophon" class="sb-footer">
		<div class="sb-container">

			<?php if ( has_nav_menu( 'footer-1' ) || has_nav_menu( 'footer-2' ) || has_nav_menu( 'footer-3' ) ) : ?>
				<div class="sb-footer__columns">
					<?php
					$simple_bangla_footer_menus = array(
						'footer-1' => __( 'Simple Bangla', 'simple-bangla' ),
						'footer-2' => __( 'Helps', 'simple-bangla' ),
						'footer-3' => __( 'Customer Service', 'simple-bangla' ),
					);

					foreach ( $simple_bangla_footer_menus as $simple_bangla_location => $simple_bangla_heading ) :

						if ( ! has_nav_menu( $simple_bangla_location ) ) {
							continue;
						}
						?>
						<div class="sb-footer__column">
							<h2 class="sb-footer__heading"><?php echo esc_html( $simple_bangla_heading ); ?></h2>
							<?php
							wp_nav_menu(
								array(
									'theme_location' => $simple_bangla_location,
									'container'      => false,
									'menu_class'     => 'sb-footer__menu',
									'depth'          => 1,
								)
							);
							?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

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

</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
