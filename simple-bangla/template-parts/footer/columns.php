<?php
/**
 * Footer link columns, plus the map panel.
 *
 * Each column renders its assigned menu. When no menu has been assigned — which is the state
 * of every fresh install — it falls back to the pages the theme created on activation, so the
 * footer is never three empty headings.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_columns = array(
	'footer-1' => array(
		'heading'  => get_bloginfo( 'name' ),
		'fallback' => array( 'about-us', 'privacy-policy', 'refund_returns', 'terms-and-condition' ),
	),
	'footer-2' => array(
		'heading'  => __( 'Helps', 'simple-bangla' ),
		'fallback' => array( 'tutorials', 'warranty-policy', 'special-deals', 'user' ),
	),
	'footer-3' => array(
		'heading'  => __( 'Customer Service', 'simple-bangla' ),
		'fallback' => array( 'contact-us', 'register', 'login', 'password-reset' ),
	),
);

$simple_bangla_map = simple_bangla_get_contact( 'map' );
?>
<div class="sb-container sb-footer__columns">

	<?php foreach ( $simple_bangla_columns as $simple_bangla_location => $simple_bangla_column ) : ?>
		<div class="sb-footer__column">
			<h2 class="sb-footer__heading"><?php echo esc_html( $simple_bangla_column['heading'] ); ?></h2>

			<?php
			if ( has_nav_menu( $simple_bangla_location ) ) {

				wp_nav_menu(
					array(
						'theme_location' => $simple_bangla_location,
						'container'      => false,
						'menu_class'     => 'sb-footer__menu',
						'depth'          => 1,
					)
				);

			} else {

				echo '<ul class="sb-footer__menu">';

				foreach ( $simple_bangla_column['fallback'] as $simple_bangla_slug ) {

					$simple_bangla_page = get_page_by_path( $simple_bangla_slug );

					if ( ! $simple_bangla_page || 'publish' !== $simple_bangla_page->post_status ) {
						continue;
					}

					printf(
						'<li><a href="%s">%s</a></li>',
						esc_url( (string) get_permalink( $simple_bangla_page ) ),
						esc_html( get_the_title( $simple_bangla_page ) )
					);
				}

				echo '</ul>';
			}
			?>
		</div>
	<?php endforeach; ?>

	<div class="sb-footer__column sb-footer__column--map">
		<h2 class="sb-footer__heading"><?php esc_html_e( 'Find us', 'simple-bangla' ); ?></h2>

		<?php if ( $simple_bangla_map ) : ?>
			<div class="sb-footer__map">
				<iframe
					src="<?php echo esc_url( $simple_bangla_map ); ?>"
					title="<?php esc_attr_e( 'Store location on Google Maps', 'simple-bangla' ); ?>"
					loading="lazy"
					referrerpolicy="no-referrer-when-downgrade"
					allowfullscreen
				></iframe>
			</div>
		<?php else : ?>
			<p class="sb-footer__map-empty">
				<?php esc_html_e( 'Add a Google Maps embed URL in the Customizer to show your shop here.', 'simple-bangla' ); ?>
			</p>
		<?php endif; ?>
	</div>

</div>
