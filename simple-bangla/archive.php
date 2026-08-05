<?php
/**
 * Archive template — categories, tags, authors, dates.
 *
 * The WooCommerce product archive does not come through here; it is handled by
 * woocommerce/archive-product.php.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="sb-container sb-layout">

	<main id="main" class="sb-layout__main">

		<?php if ( have_posts() ) : ?>

			<header class="sb-page-header">
				<?php
				the_archive_title( '<h1 class="sb-page-header__title">', '</h1>' );
				the_archive_description( '<div class="sb-page-header__description">', '</div>' );
				?>
			</header>

			<div class="sb-entry-list">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', get_post_type() );
				endwhile;
				?>
			</div>

			<?php simple_bangla_pagination(); ?>

		<?php else : ?>

			<?php get_template_part( 'template-parts/content', 'none' ); ?>

		<?php endif; ?>

	</main>

	<?php get_sidebar(); ?>

</div>

<?php
get_footer();
