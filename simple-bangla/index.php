<?php
/**
 * The fallback template.
 *
 * WordPress falls back here whenever a more specific template is missing, so it has to
 * render a sane loop for any query.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="sb-container sb-layout">

	<main id="main" class="sb-layout__main">

		<?php if ( have_posts() ) : ?>

			<?php if ( is_home() && ! is_front_page() ) : ?>
				<header class="sb-page-header">
					<h1 class="sb-page-header__title"><?php single_post_title(); ?></h1>
				</header>
			<?php endif; ?>

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
