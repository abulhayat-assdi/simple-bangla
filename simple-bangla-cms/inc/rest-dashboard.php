<?php
/**
 * The dashboard endpoint.
 *
 * One request, every figure the overview screen shows. See inc/stats.php for why the numbers
 * are gathered the way they are.
 *
 * Revenue is withheld from a user who cannot see orders. Today that is nobody, because the only
 * account is the owner's — but an order-desk account added later should not learn the store's
 * turnover from a dashboard tile, and the rule is cheaper to write now than to remember later.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the dashboard route.
 */
function simple_bangla_cms_register_dashboard_route() {

	register_rest_route(
		SIMPLE_BANGLA_CMS_REST_NS,
		'/dashboard',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'simple_bangla_cms_dashboard_response',
			'permission_callback' => simple_bangla_cms_permission( 'dashboard.view' ),
			'args'                => array(
				'refresh' => array(
					'type'              => 'boolean',
					'required'          => false,
					'default'           => false,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'description'       => __( 'Recompute instead of returning the cached figures.', 'simple-bangla-cms' ),
				),
				/*
				 * Deliberately not an enum. A month is `YYYY-MM`, so the valid set is unbounded, and
				 * an unrecognised value is answered with the default period rather than a 400 — the
				 * response reports the period it actually used, which is what the interface reads.
				 */
				'period'  => array(
					'type'        => 'string',
					'required'    => false,
					'default'     => '30d',
					'description' => __( 'all, 30d, 90d, 12m, or a single month as YYYY-MM.', 'simple-bangla-cms' ),
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'simple_bangla_cms_register_dashboard_route' );

/**
 * Build the dashboard payload.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function simple_bangla_cms_dashboard_response( $request ) {

	$stats = simple_bangla_cms_dashboard_stats(
		(bool) $request->get_param( 'refresh' ),
		(string) $request->get_param( 'period' )
	);

	if ( ! simple_bangla_cms_can( 'orders.view' ) ) {
		unset( $stats['revenue'] );
	}

	return rest_ensure_response( $stats );
}
