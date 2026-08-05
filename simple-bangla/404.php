<?php
/**
 * 404 template.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="sb-container sb-layout sb-layout--narrow">

	<main id="main" class="sb-layout__main">

		<section class="sb-no-results">

			<p class="sb-404__code">404</p>

			<h1 class="sb-no-results__title"><?php esc_html_e( 'This page has moved on', 'simple-bangla' ); ?></h1>

			<p><?php esc_html_e( 'The link may be out of date, or the product may have sold out. Try a search, or head back to the shop.', 'simple-bangla' ); ?></p>

			<?php simple_bangla_product_search_form(); ?>

			<p class="sb-404__actions">
				<a class="sb-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php esc_html_e( 'Back to home', 'simple-bangla' ); ?>
				</a>
				<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
					<a class="sb-btn sb-btn--ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
						<?php esc_html_e( 'Browse the shop', 'simple-bangla' ); ?>
					</a>
				<?php endif; ?>
			</p>

		</section>

	</main>

</div>

<?php
get_footer();
