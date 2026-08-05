<?php
/**
 * Single post template.
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
			get_template_part( 'template-parts/content', get_post_type() );

			the_post_navigation(
				array(
					'prev_text' => '<span class="sb-post-nav__label">' . esc_html__( 'Previous', 'simple-bangla' ) . '</span> %title',
					'next_text' => '<span class="sb-post-nav__label">' . esc_html__( 'Next', 'simple-bangla' ) . '</span> %title',
					'class'     => 'sb-post-nav',
				)
			);

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
