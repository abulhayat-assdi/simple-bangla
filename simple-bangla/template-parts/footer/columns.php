<?php
/**
 * Footer link columns.
 *
 * Each column renders its assigned menu; with none assigned — the state of every fresh
 * install — it falls back to the pages the theme created on activation, so the footer is
 * never three empty headings.
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
	 * No account, registration or login link here. The shop takes guest cash-on-delivery
	 * orders and runs no membership, so customer service means the routes that actually
	 * reach a person or answer a question — see simple_bangla_account_route_slugs(), which
	 * also strips those links from an assigned menu.
	 */
	'footer-3' => array(
		'heading'  => __( 'Customer Service', 'simple-bangla' ),
		'fallback' => array( 'contact-us', 'refund_returns', 'terms-and-condition' ),
	),
);
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
