<?php
/**
 * A single entry in a loop.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'sb-entry' ); ?>>

	<?php if ( has_post_thumbnail() && ! is_singular() ) : ?>
		<a class="sb-entry__thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
		</a>
	<?php endif; ?>

	<div class="sb-entry__body">

		<?php
		if ( is_singular() ) {

			// Cart, checkout and order-received print their own banner headline instead.
			if ( ! simple_bangla_hide_entry_title() ) {
				the_title( '<h1 class="sb-entry__title">', '</h1>' );
			}
		} else {
			the_title(
				'<h2 class="sb-entry__title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">',
				'</a></h2>'
			);
		}

		if ( 'post' === get_post_type() ) {
			simple_bangla_posted_on();
		}
		?>

		<div class="sb-entry__content">
			<?php
			if ( is_singular() ) {

				the_content();

				wp_link_pages(
					array(
						'before' => '<nav class="sb-page-links">' . esc_html__( 'Pages:', 'simple-bangla' ),
						'after'  => '</nav>',
					)
				);

			} else {
				the_excerpt();
				?>
				<a class="sb-btn sb-btn--ghost" href="<?php the_permalink(); ?>">
					<?php esc_html_e( 'Read more', 'simple-bangla' ); ?>
					<span class="screen-reader-text"><?php the_title(); ?></span>
				</a>
				<?php
			}
			?>
		</div>

	</div>
</article>
