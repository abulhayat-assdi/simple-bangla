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

/*
 * The columns themselves live in inc/pages.php, because the CMS has to be able to ask which pages
 * this footer links to and answering that from a second copy of the list would drift. There is no
 * third column: it held Customer Service, and every route it offered — phone, email, WhatsApp, the
 * address — is already in the brand cell beside it, where a customer looks first.
 */
$simple_bangla_columns = simple_bangla_footer_columns();
?>

<?php foreach ( $simple_bangla_columns as $simple_bangla_location => $simple_bangla_column ) : ?>
	<div class="sb-footer__column">
		<h2 class="sb-footer__heading"><?php echo esc_html( $simple_bangla_column['heading'] ); ?></h2>

		<?php
		// Which source wins is decided in one place, so this template and the CMS's list of editable
		// pages can never disagree about what the footer is showing.
		if ( 'menu' === simple_bangla_footer_column_source( $simple_bangla_location ) ) {

			// Rendered by WordPress rather than from the page list, because a menu may legitimately
			// hold category links and custom URLs, and those have to print too.
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

			foreach ( simple_bangla_footer_column_pages( $simple_bangla_location, $simple_bangla_column ) as $simple_bangla_page ) {
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
