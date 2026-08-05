<?php
/**
 * Static page template.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="sb-container sb-layout">

	<main id="main" class="sb-layout__main">

		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', 'page' );

			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>

	</main>

	<?php get_sidebar(); ?>

</div>

<?php
get_footer();
