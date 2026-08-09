<?php
/**
 * The bridge between the CMS and the theme's stored settings.
 *
 * Nothing here invents a field. The schema is generated from the theme's own registry
 * functions — simple_bangla_color_tokens(), simple_bangla_contact_fields() and the
 * SIMPLE_BANGLA_HOME_* constants — so the CMS and the Customizer are guaranteed to describe the
 * same settings with the same defaults and the same sanitisers. Add a colour token to the theme
 * and it appears in the CMS with no change here; that is the point.
 *
 * The schema is built at request time rather than at plugin load, because plugins load before
 * themes and none of those functions exist yet when this file is included.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every theme setting the CMS may read or write.
 *
 * The key is the literal `theme_mod` name, so a write is a plain set_theme_mod() with no
 * translation step that could drift.
 *
 * @return array<string,array{type:string,default:mixed,sanitize:callable,group:string,label:string}>
 */
function simple_bangla_cms_settings_schema() {

	static $schema = null;

	if ( null !== $schema ) {
		return $schema;
	}

	$schema = array();

	/* -- Logo -- */

	/*
	 * WordPress's own `custom_logo` theme mod, not a field of ours. The theme already asks
	 * has_custom_logo() in the header and the footer and falls back to the site name when it is
	 * empty, so writing this one key replaces the wordmark everywhere at once — including this
	 * CMS's own sign-in page, which reads the same mod. Storing a second "logo" setting would
	 * have meant two answers to one question, and the Customizer's Site Identity panel editing
	 * only one of them.
	 */
	$schema['custom_logo'] = array(
		'type'        => 'media',
		'default'     => 0,
		'sanitize'    => 'absint',
		'group'       => 'brand',
		'label'       => __( 'Site logo', 'simple-bangla-cms' ),
		'description' => __( 'Shown in the header and the footer. Leave it empty to print the site name as text instead. A wide PNG or SVG on a transparent background works best — the header shows it up to 44px tall, the footer up to 72px.', 'simple-bangla-cms' ),
	);

	/* -- Colours -- */

	if ( function_exists( 'simple_bangla_color_tokens' ) ) {
		foreach ( simple_bangla_color_tokens() as $key => $token ) {
			$schema[ 'simple_bangla_color_' . $key ] = array(
				'type'        => 'color',
				'default'     => $token['default'],
				'sanitize'    => 'simple_bangla_cms_sanitize_hex',
				'group'       => 'colors',
				'label'       => $token['label'],
				// The theme already explains what each token paints. Repeating that sentence in the
				// interface would be a second copy to keep true.
				'description' => isset( $token['description'] ) ? $token['description'] : '',
			);
		}
	}

	/* -- Store details -- */

	if ( function_exists( 'simple_bangla_contact_fields' ) ) {
		foreach ( simple_bangla_contact_fields() as $key => $field ) {
			$schema[ 'simple_bangla_contact_' . $key ] = array(
				'type'        => $field['type'],
				'default'     => $field['default'],
				'sanitize'    => $field['sanitize'],
				'group'       => 'store',
				'label'       => $field['label'],
				'description' => isset( $field['description'] ) ? $field['description'] : '',
			);
		}
	}

	$schema['simple_bangla_payment_strip'] = array(
		'type'     => 'media',
		'default'  => 0,
		'sanitize' => 'absint',
		'group'    => 'store',
		'label'    => __( 'Payment methods image', 'simple-bangla-cms' ),
	);

	/* -- Hero slider -- */

	$slides = defined( 'SIMPLE_BANGLA_HERO_SLIDES' ) ? SIMPLE_BANGLA_HERO_SLIDES : 5;

	for ( $i = 1; $i <= $slides; $i++ ) {

		$schema[ 'simple_bangla_hero_' . $i . '_image' ] = array(
			'type'     => 'media',
			'default'  => 0,
			'sanitize' => 'absint',
			'group'    => 'hero',
			/* translators: %d: slide number. */
			'label'    => sprintf( __( 'Hero slide %d — image', 'simple-bangla-cms' ), $i ),
		);

		$schema[ 'simple_bangla_hero_' . $i . '_link' ] = array(
			'type'     => 'url',
			'default'  => '',
			'sanitize' => 'esc_url_raw',
			'group'    => 'hero',
			/* translators: %d: slide number. */
			'label'    => sprintf( __( 'Hero slide %d — link', 'simple-bangla-cms' ), $i ),
		);
	}

	/* -- Hot Deals and category circles -- */

	$count_sanitizer = function_exists( 'simple_bangla_sanitize_count' ) ? 'simple_bangla_sanitize_count' : 'absint';
	$bool_sanitizer  = function_exists( 'simple_bangla_sanitize_checkbox' ) ? 'simple_bangla_sanitize_checkbox' : 'rest_sanitize_boolean';

	$schema['simple_bangla_home_hotdeals_heading'] = array(
		'type'     => 'text',
		'default'  => __( 'Hot Deals', 'simple-bangla-cms' ),
		'sanitize' => 'sanitize_text_field',
		'group'    => 'hotdeals',
		'label'    => __( 'Hot Deals — heading', 'simple-bangla-cms' ),
	);

	$schema['simple_bangla_home_hotdeals_count'] = array(
		'type'     => 'int',
		'default'  => 8,
		'sanitize' => $count_sanitizer,
		'group'    => 'hotdeals',
		'label'    => __( 'Hot Deals — how many products', 'simple-bangla-cms' ),
	);

	$schema['simple_bangla_home_circles_count'] = array(
		'type'     => 'int',
		'default'  => 6,
		'sanitize' => $count_sanitizer,
		'group'    => 'circles',
		'label'    => __( 'Category circles — how many', 'simple-bangla-cms' ),
	);

	/* -- Product rows -- */

	$rows          = defined( 'SIMPLE_BANGLA_HOME_ROWS' ) ? SIMPLE_BANGLA_HOME_ROWS : 6;
	$row_defaults  = function_exists( 'simple_bangla_home_row_defaults' ) ? simple_bangla_home_row_defaults() : array();

	for ( $row = 1; $row <= $rows; $row++ ) {

		$prefix = 'simple_bangla_home_row_' . $row . '_';

		$schema[ $prefix . 'enabled' ] = array(
			'type'     => 'bool',
			'default'  => true,
			'sanitize' => $bool_sanitizer,
			'group'    => 'rows',
			/* translators: %d: row number. */
			'label'    => sprintf( __( 'Row %d — show this row', 'simple-bangla-cms' ), $row ),
		);

		$schema[ $prefix . 'heading' ] = array(
			'type'     => 'text',
			'default'  => isset( $row_defaults[ $row ]['heading'] ) ? $row_defaults[ $row ]['heading'] : '',
			'sanitize' => 'sanitize_text_field',
			'group'    => 'rows',
			/* translators: %d: row number. */
			'label'    => sprintf( __( 'Row %d — heading', 'simple-bangla-cms' ), $row ),
		);

		// 0 means "any category". The theme resolves 0 to a sensible default by slug.
		$schema[ $prefix . 'cat' ] = array(
			'type'     => 'term',
			'default'  => 0,
			'sanitize' => 'absint',
			'group'    => 'rows',
			/* translators: %d: row number. */
			'label'    => sprintf( __( 'Row %d — category', 'simple-bangla-cms' ), $row ),
		);

		$schema[ $prefix . 'count' ] = array(
			'type'     => 'int',
			'default'  => 12,
			'sanitize' => $count_sanitizer,
			'group'    => 'rows',
			/* translators: %d: row number. */
			'label'    => sprintf( __( 'Row %d — how many products', 'simple-bangla-cms' ), $row ),
		);
	}

	/* -- Banner pairs -- */

	$pairs = defined( 'SIMPLE_BANGLA_HOME_BANNERS' ) ? SIMPLE_BANGLA_HOME_BANNERS : 2;

	for ( $pair = 1; $pair <= $pairs; $pair++ ) {

		foreach ( array( 'small', 'wide' ) as $slot ) {

			$id = 'simple_bangla_home_banner_' . $pair . '_' . $slot;

			$schema[ $id . '_image' ] = array(
				'type'     => 'media',
				'default'  => 0,
				'sanitize' => 'absint',
				'group'    => 'banners',
				'label'    => 'small' === $slot
					/* translators: %d: banner pair number. */
					? sprintf( __( 'Banner pair %d — narrow image', 'simple-bangla-cms' ), $pair )
					/* translators: %d: banner pair number. */
					: sprintf( __( 'Banner pair %d — wide image', 'simple-bangla-cms' ), $pair ),
			);

			$schema[ $id . '_link' ] = array(
				'type'     => 'url',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'group'    => 'banners',
				'label'    => 'small' === $slot
					/* translators: %d: banner pair number. */
					? sprintf( __( 'Banner pair %d — narrow link', 'simple-bangla-cms' ), $pair )
					/* translators: %d: banner pair number. */
					: sprintf( __( 'Banner pair %d — wide link', 'simple-bangla-cms' ), $pair ),
			);
		}
	}

	return $schema;
}

/**
 * Sanitise a hex colour, keeping the caller's default when the input is malformed.
 *
 * sanitize_hex_color() returns null rather than a colour for bad input, and storing null would
 * make the theme fall back to its compiled default — which looks like the save silently failed.
 *
 * @param string $value Raw colour.
 * @return string|null Null when unusable, so the caller can reject the write.
 */
function simple_bangla_cms_sanitize_hex( $value ) {
	return sanitize_hex_color( is_string( $value ) ? $value : '' );
}

/**
 * Cast a stored value to the type the schema promises.
 *
 * theme_mod values arrive from the database as strings often enough that an untyped response
 * would hand the interface "0" where it expects false, and "12" where it expects 12.
 *
 * @param mixed  $value Raw value.
 * @param string $type  Schema type.
 * @return mixed
 */
function simple_bangla_cms_cast( $value, $type ) {

	switch ( $type ) {
		case 'bool':
			return (bool) $value;
		case 'int':
		case 'media':
		case 'term':
			return (int) $value;
		default:
			return is_scalar( $value ) ? (string) $value : '';
	}
}

/**
 * Read one setting, typed.
 *
 * @param string $key Theme mod name.
 * @return mixed Null when the key is not in the schema.
 */
function simple_bangla_cms_get_setting( $key ) {

	$schema = simple_bangla_cms_settings_schema();

	if ( ! isset( $schema[ $key ] ) ) {
		return null;
	}

	$spec = $schema[ $key ];

	return simple_bangla_cms_cast( get_theme_mod( $key, $spec['default'] ), $spec['type'] );
}

/**
 * Every setting, typed, keyed by theme mod name.
 *
 * @return array<string,mixed>
 */
function simple_bangla_cms_get_settings() {

	$values = array();

	foreach ( simple_bangla_cms_settings_schema() as $key => $spec ) {
		$values[ $key ] = simple_bangla_cms_cast( get_theme_mod( $key, $spec['default'] ), $spec['type'] );
	}

	return $values;
}

/**
 * Resolve an attachment to the shape the interface needs to render a thumbnail.
 *
 * @param int $attachment_id Attachment ID.
 * @return array{id:int,url:string,thumb:string,alt:string}|null
 */
function simple_bangla_cms_media_payload( $attachment_id ) {

	$attachment_id = (int) $attachment_id;

	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		return null;
	}

	$full  = wp_get_attachment_image_url( $attachment_id, 'full' );
	$thumb = wp_get_attachment_image_url( $attachment_id, 'medium' );

	if ( ! $full ) {
		return null;
	}

	return array(
		'id'    => $attachment_id,
		'url'   => $full,
		'thumb' => $thumb ? $thumb : $full,
		'alt'   => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
	);
}
