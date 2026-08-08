<?php
/**
 * Reading and writing the theme's settings.
 *
 * This is the endpoint that lets the homepage, footer and palette be edited without the
 * Customizer. It writes plain `theme_mod` values — the same storage the Customizer writes — so
 * the two remain interchangeable and the theme needs no knowledge that this plugin exists.
 *
 * Only keys present in the generated schema can be written. An unknown key is rejected rather
 * than stored, so a compromised or buggy client cannot invent theme mods, and a renamed theme
 * setting fails loudly at the boundary instead of quietly writing a value nothing reads.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the settings routes.
 */
function simple_bangla_cms_register_settings_routes() {

	register_rest_route(
		SIMPLE_BANGLA_CMS_REST_NS,
		'/settings',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'simple_bangla_cms_settings_get',
				'permission_callback' => simple_bangla_cms_permission( 'appearance.manage' ),
				'args'                => array(
					'group' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
						'description'       => __( 'Return only one group: colors, store, hero, hotdeals, circles, rows, banners.', 'simple-bangla-cms' ),
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'simple_bangla_cms_settings_save',
				'permission_callback' => simple_bangla_cms_permission( 'appearance.manage' ),
				'args'                => array(
					'values' => array(
						'type'        => 'object',
						'required'    => true,
						'description' => __( 'Theme mod name to new value.', 'simple-bangla-cms' ),
					),
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'simple_bangla_cms_register_settings_routes' );

/**
 * Return the schema, the current values and any referenced media.
 *
 * Schema travels with the values so the interface can render a settings form it was not
 * hard-coded for. Adding a colour token to the theme puts a new colour picker in the CMS
 * without a front-end release.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function simple_bangla_cms_settings_get( $request ) {

	if ( ! simple_bangla_cms_theme_is_active() ) {
		return simple_bangla_cms_wrong_theme_error();
	}

	$group  = $request->get_param( 'group' );
	$schema = simple_bangla_cms_settings_schema();

	$fields = array();
	$values = array();
	$media  = array();

	foreach ( $schema as $key => $spec ) {

		if ( $group && $group !== $spec['group'] ) {
			continue;
		}

		$value = simple_bangla_cms_cast( get_theme_mod( $key, $spec['default'] ), $spec['type'] );

		// The sanitiser is a server-side concern; sending it would only invite the client to
		// think it can choose one.
		$fields[ $key ] = array(
			'type'    => $spec['type'],
			'group'   => $spec['group'],
			'label'   => $spec['label'],
			'default' => $spec['default'],
		);

		$values[ $key ] = $value;

		if ( 'media' === $spec['type'] && $value ) {
			$payload = simple_bangla_cms_media_payload( $value );

			if ( $payload ) {
				$media[ $value ] = $payload;
			}
		}
	}

	return rest_ensure_response(
		array(
			'fields' => $fields,
			'values' => $values,
			// Keyed by attachment ID so several fields pointing at one image cost one entry.
			'media'  => (object) $media,
		)
	);
}

/**
 * Write a batch of settings.
 *
 * The whole batch is validated before anything is written. A form that fails halfway would
 * leave the homepage in a state the owner never asked for and cannot easily identify.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function simple_bangla_cms_settings_save( $request ) {

	if ( ! simple_bangla_cms_theme_is_active() ) {
		return simple_bangla_cms_wrong_theme_error();
	}

	$incoming = $request->get_param( 'values' );

	if ( ! is_array( $incoming ) || ! $incoming ) {
		return new WP_Error(
			'sb_cms_no_values',
			__( 'No settings were sent.', 'simple-bangla-cms' ),
			array( 'status' => 400 )
		);
	}

	$schema    = simple_bangla_cms_settings_schema();
	$clean     = array();
	$rejected  = array();

	foreach ( $incoming as $key => $value ) {

		if ( ! isset( $schema[ $key ] ) ) {
			$rejected[ $key ] = __( 'Unknown setting.', 'simple-bangla-cms' );
			continue;
		}

		$spec      = $schema[ $key ];
		$sanitized = call_user_func( $spec['sanitize'], $value );

		// sanitize_hex_color() and sanitize_email() both answer "unusable" with an empty result
		// rather than an error, so an invalid colour must be caught here or it would silently
		// blank a token and drop the theme back to its compiled default.
		if ( 'color' === $spec['type'] && ! $sanitized ) {
			$rejected[ $key ] = __( 'Not a valid colour.', 'simple-bangla-cms' );
			continue;
		}

		if ( 'email' === $spec['type'] && '' !== $value && ! $sanitized ) {
			$rejected[ $key ] = __( 'Not a valid email address.', 'simple-bangla-cms' );
			continue;
		}

		if ( 'media' === $spec['type'] && $sanitized && ! simple_bangla_cms_media_payload( $sanitized ) ) {
			$rejected[ $key ] = __( 'That image no longer exists in the media library.', 'simple-bangla-cms' );
			continue;
		}

		$clean[ $key ] = simple_bangla_cms_cast( $sanitized, $spec['type'] );
	}

	if ( $rejected ) {
		return new WP_Error(
			'sb_cms_invalid_settings',
			__( 'Some settings could not be saved, so none were changed.', 'simple-bangla-cms' ),
			array(
				'status'   => 400,
				'rejected' => $rejected,
			)
		);
	}

	foreach ( $clean as $key => $value ) {
		set_theme_mod( $key, $value );
	}

	/**
	 * Fires after the CMS writes theme settings.
	 *
	 * @param array<string,mixed> $clean The values written, keyed by theme mod name.
	 */
	do_action( 'simple_bangla_cms_settings_saved', $clean );

	return rest_ensure_response(
		array(
			'saved'  => array_keys( $clean ),
			'values' => $clean,
		)
	);
}
