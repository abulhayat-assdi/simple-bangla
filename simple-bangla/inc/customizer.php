<?php
/**
 * Customizer options.
 *
 * Phase 1 registers the colour palette only. Every colour in the theme resolves through a
 * CSS custom property declared in assets/css/base.css, and each of those properties is wired
 * to one control here — so the whole palette can be re-skinned from a single Customizer panel
 * without touching a stylesheet.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * The design tokens exposed as Customizer colour controls.
 *
 * Defaults were measured off the reference design, not guessed. The palette is black and
 * warm cream; the only red in the system is the struck-through original price.
 *
 * @return array<string,array{var:string,label:string,description:string,default:string}>
 */
function simple_bangla_color_tokens() {
	return array(
		'brand'      => array(
			'var'         => '--sb-brand',
			'label'       => __( 'Brand', 'simple-bangla' ),
			'description' => __( 'Buttons, sale ribbon, active states.', 'simple-bangla' ),
			'default'     => '#000000',
		),
		'brand_dark' => array(
			'var'         => '--sb-brand-dark',
			'label'       => __( 'Brand (hover)', 'simple-bangla' ),
			'description' => __( 'Button and link hover state.', 'simple-bangla' ),
			'default'     => '#2d3134',
		),
		'ink'        => array(
			'var'         => '--sb-ink',
			'label'       => __( 'Text', 'simple-bangla' ),
			'description' => __( 'Body copy, headings, current price.', 'simple-bangla' ),
			'default'     => '#333333',
		),
		'muted'      => array(
			'var'         => '--sb-muted',
			'label'       => __( 'Muted text', 'simple-bangla' ),
			'description' => __( 'Section headings, captions, secondary text.', 'simple-bangla' ),
			'default'     => '#7e7e7e',
		),
		'sale'       => array(
			'var'         => '--sb-sale',
			'label'       => __( 'Original price', 'simple-bangla' ),
			'description' => __( 'The struck-through price on a discounted product.', 'simple-bangla' ),
			'default'     => '#ba5e5e',
		),
		'line'       => array(
			'var'         => '--sb-line',
			'label'       => __( 'Borders', 'simple-bangla' ),
			'description' => __( 'Card borders, dividers, input outlines.', 'simple-bangla' ),
			'default'     => '#e5e7eb',
		),
		'bg'         => array(
			'var'         => '--sb-bg',
			'label'       => __( 'Page background', 'simple-bangla' ),
			'description' => __( 'The base background behind everything.', 'simple-bangla' ),
			'default'     => '#ffffff',
		),
		'bg_alt'     => array(
			'var'         => '--sb-bg-alt',
			'label'       => __( 'Card background', 'simple-bangla' ),
			'description' => __( 'Product cards and alternating sections.', 'simple-bangla' ),
			'default'     => '#fffcf7',
		),
		'footer_bg'  => array(
			'var'         => '--sb-footer-bg',
			'label'       => __( 'Footer background', 'simple-bangla' ),
			'description' => __( 'Background of the site footer.', 'simple-bangla' ),
			'default'     => '#ffffff',
		),
	);
}

/**
 * Read one palette colour, falling back to its measured default.
 *
 * @param string $key Token key from simple_bangla_color_tokens().
 * @return string Hex colour.
 */
function simple_bangla_get_color( $key ) {

	$tokens = simple_bangla_color_tokens();

	if ( ! isset( $tokens[ $key ] ) ) {
		return '';
	}

	$value = get_theme_mod( 'simple_bangla_color_' . $key, $tokens[ $key ]['default'] );

	// sanitize_hex_color() returns null for anything malformed, so fall back rather than emit junk.
	return sanitize_hex_color( $value ) ? $value : $tokens[ $key ]['default'];
}

/**
 * Register the Customizer panel, sections and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function simple_bangla_customize_register( $wp_customize ) {

	$wp_customize->add_panel(
		'simple_bangla_panel',
		array(
			'title'       => __( 'Simple Bangla', 'simple-bangla' ),
			'description' => __( 'Theme-wide options for layout, colour and store details.', 'simple-bangla' ),
			'priority'    => 25,
		)
	);

	$wp_customize->add_section(
		'simple_bangla_colors',
		array(
			'title'       => __( 'Colours', 'simple-bangla' ),
			'description' => __( 'Every colour in the theme comes from this palette. Changing one here changes it everywhere.', 'simple-bangla' ),
			'panel'       => 'simple_bangla_panel',
			'priority'    => 10,
		)
	);

	foreach ( simple_bangla_color_tokens() as $key => $token ) {

		$setting_id = 'simple_bangla_color_' . $key;

		$wp_customize->add_setting(
			$setting_id,
			array(
				'default'           => $token['default'],
				'sanitize_callback' => 'sanitize_hex_color',
				// The palette is emitted as one inline <style> block, so a refresh is the honest transport.
				'transport'         => 'refresh',
				'capability'        => 'edit_theme_options',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				$setting_id,
				array(
					'label'       => $token['label'],
					'description' => $token['description'],
					'section'     => 'simple_bangla_colors',
					'settings'    => $setting_id,
				)
			)
		);
	}

	// Live-preview the bits of the header that are plain text.
	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
		$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

		$wp_customize->selective_refresh->add_partial(
			'blogname',
			array(
				'selector'        => '.sb-site-title',
				'render_callback' => 'simple_bangla_partial_blogname',
			)
		);
	}
}
add_action( 'customize_register', 'simple_bangla_customize_register' );

/**
 * Selective-refresh callback for the site title.
 */
function simple_bangla_partial_blogname() {
	bloginfo( 'name' );
}

/**
 * Build the :root override block for any colour the store owner has changed.
 *
 * assets/css/base.css already declares every token at its default value, so the theme renders
 * correctly with no inline CSS at all. This only emits the deltas, which keeps the payload at
 * zero bytes for a default install.
 *
 * @return string CSS, or an empty string when nothing has been customised.
 */
function simple_bangla_palette_css() {

	$declarations = array();

	foreach ( simple_bangla_color_tokens() as $key => $token ) {

		$value = simple_bangla_get_color( $key );

		if ( strtolower( $value ) === strtolower( $token['default'] ) ) {
			continue;
		}

		$declarations[] = sprintf( '%s:%s', $token['var'], $value );
	}

	if ( empty( $declarations ) ) {
		return '';
	}

	return ':root{' . implode( ';', $declarations ) . '}';
}
