<?php
/**
 * Homepage builder options.
 *
 * The homepage is assembled from a fixed sequence of sections whose content is chosen here:
 * a Hot Deals slider, a row of category circles, six product rows and two banner pairs
 * interleaved between them. That order is what the reference site actually renders.
 *
 * The reference wires five of its six rows to the wrong category — a heading that says
 * "Powerbank" above a shelf of earbuds. Heading text and target category are kept as separate
 * settings here specifically so that bug cannot be reproduced by accident.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** How many product rows the homepage offers. */
const SIMPLE_BANGLA_HOME_ROWS = 6;

/** How many banner pairs the homepage offers. */
const SIMPLE_BANGLA_HOME_BANNERS = 2;

/** How many slides the hero carousel offers. */
const SIMPLE_BANGLA_HERO_SLIDES = 5;

/**
 * The configured hero slides, skipping any slot with no image.
 *
 * @return array<int,array{image:int,link:string}>
 */
function simple_bangla_hero_slides() {

	$slides = array();

	for ( $i = 1; $i <= SIMPLE_BANGLA_HERO_SLIDES; $i++ ) {

		$image = (int) get_theme_mod( 'simple_bangla_hero_' . $i . '_image', 0 );

		if ( ! $image ) {
			continue;
		}

		$slides[] = array(
			'image' => $image,
			'link'  => (string) get_theme_mod( 'simple_bangla_hero_' . $i . '_link', '' ),
		);
	}

	return $slides;
}

/**
 * Default heading and category slug for each row, used until the owner picks their own.
 *
 * @return array<int,array{heading:string,slug:string}>
 */
function simple_bangla_home_row_defaults() {
	return array(
		1 => array(
			'heading' => __( 'Best Selling', 'simple-bangla' ),
			'slug'    => 'best-selling',
		),
		2 => array(
			'heading' => __( 'Microphone', 'simple-bangla' ),
			'slug'    => 'microphone',
		),
		3 => array(
			'heading' => __( 'Smart Watch', 'simple-bangla' ),
			'slug'    => 'smart-watch',
		),
		4 => array(
			'heading' => __( 'Headphone', 'simple-bangla' ),
			'slug'    => 'headphone',
		),
		5 => array(
			'heading' => __( 'Power Bank', 'simple-bangla' ),
			'slug'    => 'power-bank',
		),
		6 => array(
			'heading' => __( 'Gadgets', 'simple-bangla' ),
			'slug'    => 'gadgets',
		),
	);
}

/**
 * Product categories as a Customizer select list.
 *
 * @return array<int|string,string> Term ID => name, with 0 meaning "any category".
 */
function simple_bangla_product_cat_choices() {

	$choices = array( 0 => __( '— Any category —', 'simple-bangla' ) );

	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return $choices;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => 200,
		)
	);

	if ( is_wp_error( $terms ) ) {
		return $choices;
	}

	foreach ( $terms as $term ) {
		// Depth is worth showing: several category names repeat across branches.
		$choices[ $term->term_id ] = ( $term->parent ? '— ' : '' ) . $term->name;
	}

	return $choices;
}

/**
 * Resolve a row's configured category to a term ID.
 *
 * Falls back to the slug in simple_bangla_home_row_defaults() so a fresh install with the
 * demo categories imported shows something sensible before anything is configured.
 *
 * @param int $row Row number, 1-based.
 * @return int Term ID, or 0 for "any".
 */
function simple_bangla_home_row_term( $row ) {

	$configured = (int) get_theme_mod( 'simple_bangla_home_row_' . $row . '_cat', 0 );

	if ( $configured ) {
		return $configured;
	}

	$defaults = simple_bangla_home_row_defaults();

	if ( ! isset( $defaults[ $row ] ) || ! taxonomy_exists( 'product_cat' ) ) {
		return 0;
	}

	$term = get_term_by( 'slug', $defaults[ $row ]['slug'], 'product_cat' );

	return $term ? (int) $term->term_id : 0;
}

/**
 * Read one homepage setting.
 *
 * @param string $key     Setting key without the theme prefix.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function simple_bangla_home_option( $key, $default = '' ) {
	return get_theme_mod( 'simple_bangla_home_' . $key, $default );
}

/**
 * Sanitise a checkbox.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function simple_bangla_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Clamp a product count to something a slider can actually show.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function simple_bangla_sanitize_count( $value ) {
	return max( 2, min( 24, absint( $value ) ) );
}

/**
 * Register the homepage sections.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function simple_bangla_customize_home( $wp_customize ) {

	$wp_customize->add_section(
		'simple_bangla_home',
		array(
			'title'       => __( 'Homepage sections', 'simple-bangla' ),
			'description' => __( 'The homepage renders in a fixed order: Hot Deals, category circles, two product rows, a banner pair, two more rows, a second banner pair, then the last two rows.', 'simple-bangla' ),
			'panel'       => 'simple_bangla_panel',
			'priority'    => 30,
		)
	);

	$add = function ( $id, $default, $sanitize, $control ) use ( $wp_customize ) {

		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $default,
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
				'capability'        => 'edit_theme_options',
			)
		);

		$control['section'] = 'simple_bangla_home';
		$wp_customize->add_control( $id, $control );
	};

	/* -- Hero carousel -- */

	for ( $slide = 1; $slide <= SIMPLE_BANGLA_HERO_SLIDES; $slide++ ) {

		$wp_customize->add_setting(
			'simple_bangla_hero_' . $slide . '_image',
			array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
				'capability'        => 'edit_theme_options',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				'simple_bangla_hero_' . $slide . '_image',
				array(
					/* translators: %d: slide number. */
					'label'       => sprintf( __( 'Hero slide %d — image', 'simple-bangla' ), $slide ),
					'description' => 1 === $slide
						? __( 'The wide banner beside Hot Deals at the top of the homepage. Around 1024×512 works well. Leave every slide empty to hide the hero.', 'simple-bangla' )
						: '',
					'section'     => 'simple_bangla_home',
					'mime_type'   => 'image',
				)
			)
		);

		$add(
			'simple_bangla_hero_' . $slide . '_link',
			'',
			'esc_url_raw',
			array(
				/* translators: %d: slide number. */
				'label' => sprintf( __( 'Hero slide %d — link', 'simple-bangla' ), $slide ),
				'type'  => 'url',
			)
		);
	}

	/* -- Hot Deals -- */

	$add(
		'simple_bangla_home_hotdeals_heading',
		__( 'Hot Deals', 'simple-bangla' ),
		'sanitize_text_field',
		array(
			'label' => __( 'Hot Deals — heading', 'simple-bangla' ),
			'type'  => 'text',
		)
	);

	$add(
		'simple_bangla_home_hotdeals_count',
		8,
		'simple_bangla_sanitize_count',
		array(
			'label'       => __( 'Hot Deals — how many products', 'simple-bangla' ),
			'description' => __( 'Only products that are actually on sale appear here.', 'simple-bangla' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min' => 2,
				'max' => 24,
			),
		)
	);

	/* -- Category circles -- */

	$add(
		'simple_bangla_home_circles_count',
		6,
		'simple_bangla_sanitize_count',
		array(
			'label'       => __( 'Category circles — how many', 'simple-bangla' ),
			'description' => __( 'Top-level product categories, shown as circular thumbnails.', 'simple-bangla' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min' => 2,
				'max' => 12,
			),
		)
	);

	/* -- Product rows -- */

	$defaults = simple_bangla_home_row_defaults();

	for ( $row = 1; $row <= SIMPLE_BANGLA_HOME_ROWS; $row++ ) {

		$add(
			'simple_bangla_home_row_' . $row . '_enabled',
			true,
			'simple_bangla_sanitize_checkbox',
			array(
				/* translators: %d: row number. */
				'label' => sprintf( __( 'Row %d — show this row', 'simple-bangla' ), $row ),
				'type'  => 'checkbox',
			)
		);

		$add(
			'simple_bangla_home_row_' . $row . '_heading',
			$defaults[ $row ]['heading'],
			'sanitize_text_field',
			array(
				/* translators: %d: row number. */
				'label' => sprintf( __( 'Row %d — heading', 'simple-bangla' ), $row ),
				'type'  => 'text',
			)
		);

		$add(
			'simple_bangla_home_row_' . $row . '_cat',
			0,
			'absint',
			array(
				/* translators: %d: row number. */
				'label'       => sprintf( __( 'Row %d — category', 'simple-bangla' ), $row ),
				'description' => __( 'The heading above and the category here are separate on purpose. Set both.', 'simple-bangla' ),
				'type'        => 'select',
				'choices'     => simple_bangla_product_cat_choices(),
			)
		);

		$add(
			'simple_bangla_home_row_' . $row . '_count',
			12,
			'simple_bangla_sanitize_count',
			array(
				/* translators: %d: row number. */
				'label'       => sprintf( __( 'Row %d — how many products', 'simple-bangla' ), $row ),
				'type'        => 'number',
				'input_attrs' => array(
					'min' => 2,
					'max' => 24,
				),
			)
		);
	}

	/* -- Banner pairs -- */

	for ( $pair = 1; $pair <= SIMPLE_BANGLA_HOME_BANNERS; $pair++ ) {

		foreach ( array( 'small', 'wide' ) as $slot ) {

			$id = 'simple_bangla_home_banner_' . $pair . '_' . $slot;

			$wp_customize->add_setting(
				$id . '_image',
				array(
					'default'           => 0,
					'sanitize_callback' => 'absint',
					'transport'         => 'refresh',
					'capability'        => 'edit_theme_options',
				)
			);

			$wp_customize->add_control(
				new WP_Customize_Media_Control(
					$wp_customize,
					$id . '_image',
					array(
						'label'     => 'small' === $slot
							/* translators: %d: banner pair number. */
							? sprintf( __( 'Banner pair %d — narrow image', 'simple-bangla' ), $pair )
							/* translators: %d: banner pair number. */
							: sprintf( __( 'Banner pair %d — wide image', 'simple-bangla' ), $pair ),
						'section'   => 'simple_bangla_home',
						'mime_type' => 'image',
					)
				)
			);

			$add(
				$id . '_link',
				'',
				'esc_url_raw',
				array(
					'label' => 'small' === $slot
						/* translators: %d: banner pair number. */
						? sprintf( __( 'Banner pair %d — narrow link', 'simple-bangla' ), $pair )
						/* translators: %d: banner pair number. */
						: sprintf( __( 'Banner pair %d — wide link', 'simple-bangla' ), $pair ),
					'type'  => 'url',
				)
			);
		}
	}
}
add_action( 'customize_register', 'simple_bangla_customize_home', 30 );
