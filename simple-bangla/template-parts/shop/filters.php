<?php
/**
 * Shop sidebar: the filter form and the widget area, in one collapsible panel.
 *
 * It is a plain GET form. Submitting it reloads the archive with the filters in the URL, which
 * is what makes a filtered view linkable and what keeps it working without JavaScript.
 * assets/js/shop.js intercepts the submit and swaps the grid instead.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_filters = simple_bangla_active_filters();
$simple_bangla_bounds  = simple_bangla_price_bounds();
$simple_bangla_action  = simple_bangla_listing_base_url();

$simple_bangla_terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'parent'     => 0,
		'hide_empty' => true,
		'number'     => 12,
	)
);

$simple_bangla_current_term = is_product_category() ? get_queried_object_id() : 0;
?>
<aside class="sb-shop__sidebar" id="sb-shop-filters">

	<form class="sb-filters" method="get" action="<?php echo esc_url( $simple_bangla_action ); ?>" data-sb-filters>

		<div class="sb-filters__group">
			<h2 class="sb-filters__title"><?php esc_html_e( 'Price', 'simple-bangla' ); ?></h2>

			<div class="sb-filters__range">
				<label class="screen-reader-text" for="sb-min"><?php esc_html_e( 'Minimum price', 'simple-bangla' ); ?></label>
				<input
					type="number"
					id="sb-min"
					name="sb_min"
					inputmode="numeric"
					min="0"
					placeholder="<?php echo esc_attr( (string) $simple_bangla_bounds['min'] ); ?>"
					value="<?php echo $simple_bangla_filters['min'] ? esc_attr( (string) $simple_bangla_filters['min'] ) : ''; ?>"
				/>
				<span class="sb-filters__dash" aria-hidden="true">–</span>
				<label class="screen-reader-text" for="sb-max"><?php esc_html_e( 'Maximum price', 'simple-bangla' ); ?></label>
				<input
					type="number"
					id="sb-max"
					name="sb_max"
					inputmode="numeric"
					min="0"
					placeholder="<?php echo esc_attr( (string) $simple_bangla_bounds['max'] ); ?>"
					value="<?php echo $simple_bangla_filters['max'] ? esc_attr( (string) $simple_bangla_filters['max'] ) : ''; ?>"
				/>
			</div>

			<p class="sb-filters__hint">
				<?php
				printf(
					/* translators: 1: lowest price in the catalogue, 2: highest price. */
					esc_html__( 'Everything in stock runs from %1$s to %2$s.', 'simple-bangla' ),
					esc_html( simple_bangla_format_price( $simple_bangla_bounds['min'] ) ),
					esc_html( simple_bangla_format_price( $simple_bangla_bounds['max'] ) )
				);
				?>
			</p>
		</div>

		<div class="sb-filters__group">
			<label class="sb-filters__check">
				<input type="checkbox" name="sb_sale" value="1" <?php checked( $simple_bangla_filters['sale'] ); ?> />
				<span><?php esc_html_e( 'On sale only', 'simple-bangla' ); ?></span>
			</label>
		</div>

		<div class="sb-filters__actions">
			<button type="submit" class="sb-btn sb-btn--sm"><?php esc_html_e( 'Apply', 'simple-bangla' ); ?></button>
			<?php if ( simple_bangla_has_active_filters() ) : ?>
				<a class="sb-filters__clear" href="<?php echo esc_url( $simple_bangla_action ); ?>">
					<?php esc_html_e( 'Clear', 'simple-bangla' ); ?>
				</a>
			<?php endif; ?>
		</div>

	</form>

	<?php if ( ! is_wp_error( $simple_bangla_terms ) && $simple_bangla_terms ) : ?>
		<nav class="sb-widget sb-filters__cats" aria-label="<?php esc_attr_e( 'Product categories', 'simple-bangla' ); ?>">
			<h2 class="sb-widget__title"><?php esc_html_e( 'Categories', 'simple-bangla' ); ?></h2>
			<ul>
				<?php foreach ( $simple_bangla_terms as $simple_bangla_term ) : ?>
					<?php
					$simple_bangla_link = get_term_link( $simple_bangla_term );

					if ( is_wp_error( $simple_bangla_link ) ) {
						continue;
					}
					?>
					<li>
						<a
							href="<?php echo esc_url( $simple_bangla_link ); ?>"
							<?php echo ( $simple_bangla_current_term === $simple_bangla_term->term_id ) ? 'aria-current="page"' : ''; ?>
						>
							<?php echo esc_html( $simple_bangla_term->name ); ?>
							<span class="sb-filters__count"><?php echo esc_html( number_format_i18n( $simple_bangla_term->count ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>

	<?php
	if ( is_active_sidebar( 'sidebar-shop' ) ) {
		dynamic_sidebar( 'sidebar-shop' );
	}
	?>

</aside>
