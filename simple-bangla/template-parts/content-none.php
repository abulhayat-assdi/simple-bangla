<?php
/**
 * Shown when a loop finds nothing.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="sb-no-results">

	<h1 class="sb-no-results__title"><?php esc_html_e( 'Nothing found', 'simple-bangla' ); ?></h1>

	<?php if ( is_search() ) : ?>

		<p><?php esc_html_e( 'No results matched that search. Try a different spelling or a shorter phrase.', 'simple-bangla' ); ?></p>
		<?php simple_bangla_product_search_form(); ?>

	<?php else : ?>

		<p><?php esc_html_e( 'There is nothing here yet. Try the shop, or search for what you need.', 'simple-bangla' ); ?></p>
		<?php simple_bangla_product_search_form(); ?>

	<?php endif; ?>

</section>
