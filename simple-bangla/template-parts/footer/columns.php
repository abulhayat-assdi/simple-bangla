<?php
/**
 * Footer link columns.
 *
 * Each column renders its assigned menu; with none assigned — the state of every fresh
 * install — it falls back to the pages the theme created on activation, so the footer is
 * never three empty headings.
 *
 * The first column has a third source, ahead of both: the pages ticked for the footer on the CMS's
 * Content Pages screen. See inc/pages.php for why a tick wins over an assigned menu — briefly,
 * because a tick box that did nothing on a site that had been set up properly would be worse than
 * no tick box. Tick nothing and this file behaves exactly as it did before that screen existed.
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
	/*
	 * There is no third column. It held Customer Service, and every route it offered — phone,
	 * email, WhatsApp, the address — is already in the brand cell beside it, where a customer
	 * looks first. A column repeating them as page links was a longer way round to the same
	 * number.
	 */
);

/** Pages the owner ticked for the footer. Empty on any site that has never used the screen. */
$simple_bangla_ticked = simple_bangla_footer_link_pages();
?>

<?php foreach ( $simple_bangla_columns as $simple_bangla_location => $simple_bangla_column ) : ?>
	<div class="sb-footer__column">
		<h2 class="sb-footer__heading"><?php echo esc_html( $simple_bangla_column['heading'] ); ?></h2>

		<?php
		if ( 'footer-1' === $simple_bangla_location && $simple_bangla_ticked ) {

			echo '<ul class="sb-footer__menu">';

			foreach ( $simple_bangla_ticked as $simple_bangla_ticked_page ) {
				printf(
					'<li><a href="%s">%s</a></li>',
					esc_url( (string) get_permalink( $simple_bangla_ticked_page ) ),
					esc_html( get_the_title( $simple_bangla_ticked_page ) )
				);
			}

			echo '</ul>';

		} elseif ( has_nav_menu( $simple_bangla_location ) ) {

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
