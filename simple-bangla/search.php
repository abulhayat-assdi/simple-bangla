<?php
/**
 * Search results.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="sb-container sb-layout">

	<main id="main" class="sb-layout__main">

		<header class="sb-page-header">
			<h1 class="sb-page-header__title">
				<?php
				printf(
					/* translators: %s: the search term. */
					esc_html__( 'Results for “%s”', 'simple-bangla' ),
					esc_html( get_search_query() )
				);
				?>
			</h1>
			<p class="sb-page-header__description">
				<?php
				printf(
					/* translators: %s: number of results found. */
					esc_html( _n( '%s result', '%s results', (int) $wp_query->found_posts, 'simple-bangla' ) ),
					esc_html( number_format_i18n( (int) $wp_query->found_posts ) )
				);
				?>
			</p>
		</header>

		<?php if ( have_posts() ) : ?>

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
