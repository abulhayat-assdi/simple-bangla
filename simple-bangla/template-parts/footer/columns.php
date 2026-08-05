<?php
/**
 * Footer link columns and the map cell.
 *
 * Each column renders its assigned menu; with none assigned — the state of every fresh
 * install — it falls back to the pages the theme created on activation, so the footer is
 * never three empty headings.
 *
 * The map cell carries no heading. The reference does not give it one, and a lone "Find us"
 * label above an embed is a label the map already provides.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_columns = array(
	'footer-1' => array(
		'heading'  => get_bloginfo( 'name' ),
		'fallback' => array( 'about-us', 'privacy-policy', 'refund_returns' ),
	),
	'footer-2' => array(
		'heading'  => __( 'Helps', 'simple-bangla' ),
		'fallback' => array( 'tutorials', 'warranty-policy', 'special-deals' ),
	),
	'footer-3' => array(
		'heading'  => __( 'Customer Service', 'simple-bangla' ),
		'fallback' => array( 'contact-us', 'register', 'terms-and-condition' ),
	),
);

$simple_bangla_map = simple_bangla_get_contact( 'map' );
?>

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

<div class="sb-footer__map-cell">
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
		<div class="sb-footer__map sb-footer__map--empty">
			<?php simple_bangla_icon( 'pin', 28 ); ?>
			<p><?php esc_html_e( 'Add a Google Maps embed URL in the Customizer to show your shop here.', 'simple-bangla' ); ?></p>
		</div>
	<?php endif; ?>
</div>
