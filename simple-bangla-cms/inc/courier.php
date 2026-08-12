<?php
/**
 * Couriers: dispatching an order, and checking a customer's delivery record.
 *
 * This lives entirely in the plugin, unlike the block list. The rule the project runs on is that
 * deactivating the CMS must not change the storefront, and a block list enforced from here would
 * have failed that in the dangerous direction. Dispatching a parcel is the opposite case: it is a
 * management action that only ever happens inside this interface, nothing on the storefront reads
 * it, and a shop with the plugin switched off simply has no Send-to-Courier button. So the theme
 * knows nothing about couriers.
 *
 * **Two different sets of credentials per courier, and they are not interchangeable.**
 *
 * - *Dispatch* uses the documented merchant API — an API key and secret, or an OAuth client. This
 *   is the supported, versioned surface.
 * - *The delivery record* ("fraud check") does not exist in any of the three documented APIs. The
 *   figure every Bangladeshi fraud-check site shows comes from signing in to the courier's own
 *   merchant portal with the shop's portal email and password and calling the endpoint the portal's
 *   own dashboard calls. That is what is implemented below, because it is the only thing that
 *   exists — but it is undocumented and unversioned, so it is treated as something that will break
 *   one day: it is cached, it never blocks a dispatch, and a failure is reported as "could not be
 *   read" rather than as "this customer is fine".
 *
 * Credentials are an option rather than a `theme_mod`. They are operational secrets, not
 * appearance, and the settings bridge deliberately carries only scalars the Customizer could also
 * write. Stored with autoload off so a secret is not on every front-end page load.
 *
 * @package Simple_Bangla_CMS
 */

defined( 'ABSPATH' ) || exit;

/** Where the courier configuration is stored. */
const SIMPLE_BANGLA_CMS_COURIER_OPTION = 'simple_bangla_cms_courier';

/** How long a delivery record is reused before it is fetched again. */
const SIMPLE_BANGLA_CMS_FRAUD_TTL = 6 * HOUR_IN_SECONDS;

/**
 * The courier whose delivery record *is* the report.
 *
 * See simple_bangla_cms_courier_records() for why one of the three is singled out.
 */
const SIMPLE_BANGLA_CMS_RECORD_PRIMARY = 'steadfast';

/** How long a request to a courier is given before it is abandoned. */
const SIMPLE_BANGLA_CMS_COURIER_TIMEOUT = 20;

/**
 * The couriers the CMS can talk to, and what each one needs.
 *
 * `secret` fields are never sent back to the browser — the interface is told whether one is set,
 * not what it is. Anything marked `record` is only used for the delivery-record lookup, so a shop
 * that only wants to dispatch parcels can leave those blank and the button still works.
 *
 * @return array<string,array>
 */
function simple_bangla_cms_courier_providers() {

	return array(
		'steadfast' => array(
			'label'  => __( 'Steadfast', 'simple-bangla-cms' ),
			'fields' => array(
				'api_key'         => array(
					'label'       => __( 'API key', 'simple-bangla-cms' ),
					'secret'      => false,
					'description' => __( 'From the Steadfast merchant panel, under API.', 'simple-bangla-cms' ),
				),
				'secret_key'      => array(
					'label'  => __( 'Secret key', 'simple-bangla-cms' ),
					'secret' => true,
				),
				'portal_email'    => array(
					'label'       => __( 'Portal email', 'simple-bangla-cms' ),
					'secret'      => false,
					'record'      => true,
					'description' => __( 'The email you sign in to steadfast.com.bd with. This is what a customer’s delivery record is read with. The API key above cannot do it — Steadfast publishes no API for that figure, so without this the record on an order stays blank.', 'simple-bangla-cms' ),
				),
				'portal_password' => array(
					'label'  => __( 'Portal password', 'simple-bangla-cms' ),
					'secret' => true,
					'record' => true,
				),
			),
		),
		'pathao'    => array(
			'label'  => __( 'Pathao Courier', 'simple-bangla-cms' ),
			'fields' => array(
				'client_id'     => array(
					'label'  => __( 'Client ID', 'simple-bangla-cms' ),
					'secret' => false,
				),
				'client_secret' => array(
					'label'  => __( 'Client secret', 'simple-bangla-cms' ),
					'secret' => true,
				),
				'username'      => array(
					'label'       => __( 'Merchant email', 'simple-bangla-cms' ),
					'secret'      => false,
					'description' => __( 'The same email you sign in to the Pathao merchant panel with. Used for both dispatch and the delivery record.', 'simple-bangla-cms' ),
				),
				'password'      => array(
					'label'  => __( 'Merchant password', 'simple-bangla-cms' ),
					'secret' => true,
				),
				'store_id'      => array(
					'label'  => __( 'Store ID', 'simple-bangla-cms' ),
					'secret' => false,
				),
				'city_id'       => array(
					'label'       => __( 'Default city ID', 'simple-bangla-cms' ),
					'secret'      => false,
					'description' => __( 'Pathao takes numeric city and zone IDs, not the address text a customer types, so a default is needed. Dhaka is 1.', 'simple-bangla-cms' ),
				),
				'zone_id'       => array(
					'label'  => __( 'Default zone ID', 'simple-bangla-cms' ),
					'secret' => false,
				),
			),
		),
		'redx'      => array(
			'label'  => __( 'RedX', 'simple-bangla-cms' ),
			'fields' => array(
				'access_token'  => array(
					'label'       => __( 'API access token', 'simple-bangla-cms' ),
					'secret'      => true,
					'description' => __( 'From the RedX merchant panel, under Developer / API.', 'simple-bangla-cms' ),
				),
				'area_id'       => array(
					'label'       => __( 'Default delivery area ID', 'simple-bangla-cms' ),
					'secret'      => false,
					'description' => __( 'RedX takes a numeric area ID rather than the address text, so a default is needed.', 'simple-bangla-cms' ),
				),
				'portal_phone'  => array(
					'label'       => __( 'Portal phone number', 'simple-bangla-cms' ),
					'secret'      => false,
					'record'      => true,
					'description' => __( 'The number you sign in to the RedX merchant panel with. Only used to read a customer’s delivery record.', 'simple-bangla-cms' ),
				),
				'portal_password' => array(
					'label'  => __( 'Portal password', 'simple-bangla-cms' ),
					'secret' => true,
					'record' => true,
				),
			),
		),
	);
}

/**
 * The stored courier configuration, with every known field present.
 *
 * @return array{active:string,providers:array<string,array<string,string>>}
 */
function simple_bangla_cms_courier_settings() {

	$stored = get_option( SIMPLE_BANGLA_CMS_COURIER_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();

	$out = array(
		'active'    => isset( $stored['active'] ) ? (string) $stored['active'] : '',
		'providers' => array(),
	);

	foreach ( simple_bangla_cms_courier_providers() as $key => $provider ) {
		foreach ( $provider['fields'] as $field => $spec ) {
			$out['providers'][ $key ][ $field ] = isset( $stored['providers'][ $key ][ $field ] )
				? (string) $stored['providers'][ $key ][ $field ]
				: '';
		}
	}

	// An active courier that has since been removed from the registry would leave every dispatch
	// failing with a message about a courier the owner cannot see on the settings screen.
	if ( ! isset( $out['providers'][ $out['active'] ] ) ) {
		$out['active'] = '';
	}

	return $out;
}

/**
 * Write the courier configuration.
 *
 * A secret arrives as an empty string when the interface had nothing to show for it, which means
 * "unchanged" rather than "clear it" — the interface never receives the stored value, so it cannot
 * echo it back, and treating a blank as a deletion would wipe the keys every time the owner saved
 * an unrelated field. Clearing one is done by sending the literal string this function looks for.
 *
 * @param array $raw Incoming settings.
 * @return array The configuration as stored.
 */
function simple_bangla_cms_save_courier_settings( $raw ) {

	$current   = simple_bangla_cms_courier_settings();
	$providers = simple_bangla_cms_courier_providers();

	$clean = array(
		'active'    => '',
		'providers' => array(),
	);

	$active = isset( $raw['active'] ) ? sanitize_key( $raw['active'] ) : $current['active'];

	if ( isset( $providers[ $active ] ) ) {
		$clean['active'] = $active;
	}

	foreach ( $providers as $key => $provider ) {
		foreach ( $provider['fields'] as $field => $spec ) {

			$sent = isset( $raw['providers'][ $key ][ $field ] ) ? (string) $raw['providers'][ $key ][ $field ] : null;

			if ( null === $sent ) {
				$clean['providers'][ $key ][ $field ] = $current['providers'][ $key ][ $field ];
				continue;
			}

			if ( ! empty( $spec['secret'] ) && '' === $sent ) {
				$clean['providers'][ $key ][ $field ] = $current['providers'][ $key ][ $field ];
				continue;
			}

			if ( ! empty( $spec['secret'] ) && '__clear__' === $sent ) {
				$clean['providers'][ $key ][ $field ] = '';
				continue;
			}

			// sanitize_text_field, not a password-safe pass-through: none of these are passwords the
			// shop chose. They are keys and portal logins copied from a panel, and every one of them
			// is safe to trim and strip tags from.
			$clean['providers'][ $key ][ $field ] = sanitize_text_field( $sent );
		}
	}

	update_option( SIMPLE_BANGLA_CMS_COURIER_OPTION, $clean, false );

	return $clean;
}

/**
 * The configuration as the interface may see it.
 *
 * Secrets become a boolean. There is no reason for a stored API secret to travel back to a browser,
 * and a screen that displays one invites it being read over a shoulder or captured in a screenshot.
 *
 * @return array
 */
function simple_bangla_cms_courier_public_settings() {

	$settings  = simple_bangla_cms_courier_settings();
	$providers = simple_bangla_cms_courier_providers();

	$out = array(
		'active'    => $settings['active'],
		'providers' => array(),
	);

	foreach ( $providers as $key => $provider ) {

		$values = array();

		foreach ( $provider['fields'] as $field => $spec ) {
			$values[ $field ] = empty( $spec['secret'] )
				? $settings['providers'][ $key ][ $field ]
				: '';
		}

		$out['providers'][ $key ] = array(
			'label'      => $provider['label'],
			'fields'     => array_map(
				static function ( $spec ) {
					return array(
						'label'       => $spec['label'],
						'secret'      => ! empty( $spec['secret'] ),
						'record'      => ! empty( $spec['record'] ),
						'description' => isset( $spec['description'] ) ? $spec['description'] : '',
					);
				},
				$provider['fields']
			),
			'values'     => $values,
			// Which secrets are already stored, so the interface can say "set" instead of showing an
			// empty box that looks like nothing was ever saved.
			'has_secret' => array_map(
				static function ( $field ) use ( $settings, $key ) {
					return '' !== $settings['providers'][ $key ][ $field ];
				},
				array_combine( array_keys( $provider['fields'] ), array_keys( $provider['fields'] ) )
			),
			'ready'      => ! is_wp_error( simple_bangla_cms_courier_ready( $key, 'dispatch' ) ),
			'can_check'  => ! is_wp_error( simple_bangla_cms_courier_ready( $key, 'record' ) ),
		);
	}

	return $out;
}

/**
 * Whether a courier has everything it needs for a job.
 *
 * @param string $provider Provider key.
 * @param string $job      'dispatch' or 'record'.
 * @return WP_Error|true
 */
function simple_bangla_cms_courier_ready( $provider, $job = 'dispatch' ) {

	$providers = simple_bangla_cms_courier_providers();

	if ( ! isset( $providers[ $provider ] ) ) {
		return new WP_Error( 'sb_cms_no_courier', __( 'That courier is not one this shop can use.', 'simple-bangla-cms' ), array( 'status' => 400 ) );
	}

	$values  = simple_bangla_cms_courier_settings()['providers'][ $provider ];
	$missing = array();

	foreach ( $providers[ $provider ]['fields'] as $field => $spec ) {

		$for_record = ! empty( $spec['record'] );

		// A dispatch does not need the portal login, and a record lookup does not need the API key.
		if ( ( 'record' === $job ) !== $for_record ) {
			continue;
		}

		if ( '' === $values[ $field ] ) {
			$missing[] = $spec['label'];
		}
	}

	if ( $missing ) {
		return new WP_Error(
			'sb_cms_courier_unconfigured',
			sprintf(
				/* translators: 1: courier name, 2: comma-separated field names. */
				__( '%1$s is missing: %2$s. Add it under Settings → Courier.', 'simple-bangla-cms' ),
				$providers[ $provider ]['label'],
				implode( ', ', $missing )
			),
			array( 'status' => 409 )
		);
	}

	return true;
}

/* ------------------------------------------------------------------ HTTP */

/**
 * Decode a courier's reply, or say why it could not be used.
 *
 * Every one of these endpoints answers a bad credential with a 200 and an HTML sign-in page at
 * least some of the time, so "did the request succeed" is not the same question as "is this JSON".
 *
 * @param array|WP_Error $response wp_remote_* result.
 * @param string         $label    Courier name, for the message.
 * @return array|WP_Error
 */
function simple_bangla_cms_courier_json( $response, $label ) {

	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'sb_cms_courier_unreachable',
			sprintf(
				/* translators: 1: courier name, 2: the transport's own message. */
				__( 'Could not reach %1$s: %2$s', 'simple-bangla-cms' ),
				$label,
				$response->get_error_message()
			),
			array( 'status' => 502 )
		);
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) ) {
		return new WP_Error(
			'sb_cms_courier_unreadable',
			sprintf(
				/* translators: 1: courier name, 2: HTTP status code. */
				__( '%1$s answered with something this could not read (HTTP %2$d). The credentials are the usual cause.', 'simple-bangla-cms' ),
				$label,
				(int) wp_remote_retrieve_response_code( $response )
			),
			array( 'status' => 502 )
		);
	}

	return $body;
}

/* ------------------------------------------------------------------ dispatch */

/**
 * The gateways whose money the courier collects at the door.
 *
 * Cash on delivery is the only one this shop uses. Every other method it could add — bKash, Nagad,
 * SSLCommerz — takes the money before the order exists, so there is nothing left to collect.
 *
 * @return string[] Gateway ids.
 */
function simple_bangla_cms_cod_methods() {

	/**
	 * Filter the gateways treated as cash on delivery.
	 *
	 * @param string[] $methods Gateway ids.
	 */
	return array_filter( array_map( 'strval', (array) apply_filters( 'simple_bangla_cms_cod_methods', array( 'cod' ) ) ) );
}

/**
 * What the courier collects at the door.
 *
 * **This asks the payment method, not the order status, and the difference is the whole point.**
 * The obvious test is `$order->is_paid()`, and it is wrong here: it is status-based, and
 * WooCommerce's Cash on Delivery gateway moves an order to `processing` the moment it is placed —
 * before a single Taka has changed hands. So `is_paid()` answers true for every COD order this shop
 * takes, and the courier was being told to collect nothing on every parcel it was handed.
 *
 * The direction of the fallback is deliberate. An order with no recorded gateway is treated as cash,
 * because the two mistakes are not equally expensive: collecting from a customer who already paid is
 * a refund and an apology, while collecting nothing from one who has not is money that never arrives
 * and that nobody notices until the month is reconciled.
 *
 * @param WC_Order $order Order.
 * @return float Amount in the store currency.
 */
function simple_bangla_cms_amount_to_collect( $order ) {

	$method = (string) $order->get_payment_method();

	if ( '' === $method || in_array( $method, simple_bangla_cms_cod_methods(), true ) ) {
		return (float) $order->get_total();
	}

	return 0.0;
}

/**
 * What a courier needs to know about an order.
 *
 * @param WC_Order $order Order.
 * @return array
 */
function simple_bangla_cms_courier_parcel( $order ) {

	$address = array_filter(
		array(
			$order->get_billing_address_1(),
			$order->get_billing_address_2(),
			$order->get_billing_city(),
			$order->get_billing_state(),
		)
	);

	$names = array();

	foreach ( $order->get_items() as $item ) {
		$names[] = $item->get_name() . ' × ' . $item->get_quantity();
	}

	$collect = simple_bangla_cms_amount_to_collect( $order );

	return array(
		'invoice' => $order->get_order_number(),
		'name'    => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
		'phone'   => $order->get_billing_phone(),
		'address' => implode( ', ', $address ),
		'collect' => $collect,
		'note'    => implode( ', ', $names ),
		'items'   => max( 1, array_sum( array_map( static function ( $item ) {
			return (int) $item->get_quantity();
		}, array_values( $order->get_items() ) ) ) ),
	);
}

/**
 * Hand an order to a courier.
 *
 * @param WC_Order $order    Order.
 * @param string   $provider Provider key, or '' for the configured default.
 * @return array|WP_Error {provider, consignment_id, tracking_code, raw}
 */
function simple_bangla_cms_courier_dispatch( $order, $provider = '' ) {

	$settings = simple_bangla_cms_courier_settings();
	$provider = $provider ? sanitize_key( $provider ) : $settings['active'];

	if ( ! $provider ) {
		return new WP_Error(
			'sb_cms_no_active_courier',
			__( 'No courier is set up yet. Add one under Settings → Courier.', 'simple-bangla-cms' ),
			array( 'status' => 409 )
		);
	}

	$ready = simple_bangla_cms_courier_ready( $provider, 'dispatch' );

	if ( is_wp_error( $ready ) ) {
		return $ready;
	}

	$parcel = simple_bangla_cms_courier_parcel( $order );

	if ( ! $parcel['phone'] || ! $parcel['address'] ) {
		return new WP_Error(
			'sb_cms_parcel_incomplete',
			__( 'This order has no phone number or no address, so no courier will accept it.', 'simple-bangla-cms' ),
			array( 'status' => 400 )
		);
	}

	$config = $settings['providers'][ $provider ];

	switch ( $provider ) {
		case 'steadfast':
			$result = simple_bangla_cms_steadfast_dispatch( $config, $parcel );
			break;
		case 'pathao':
			$result = simple_bangla_cms_pathao_dispatch( $config, $parcel );
			break;
		case 'redx':
			$result = simple_bangla_cms_redx_dispatch( $config, $parcel );
			break;
		default:
			$result = new WP_Error( 'sb_cms_no_courier', __( 'That courier is not one this shop can use.', 'simple-bangla-cms' ), array( 'status' => 400 ) );
	}

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$result['provider']       = $provider;
	$result['provider_label'] = simple_bangla_cms_courier_providers()[ $provider ]['label'];
	$result['sent_at']        = time();

	/*
	 * Recorded on the order, not only in a note. A note is the audit trail a human reads; this is
	 * what stops the same parcel being booked twice when the screen is reopened, and it is what the
	 * order list reads to show a consignment number.
	 */
	$order->update_meta_data( '_sb_courier', $result );
	$order->save();

	$order->add_order_note(
		sprintf(
			/* translators: 1: courier name, 2: consignment id, 3: tracking code. */
			__( 'Sent to %1$s. Consignment %2$s, tracking %3$s.', 'simple-bangla-cms' ),
			$result['provider_label'],
			$result['consignment_id'] ? $result['consignment_id'] : '—',
			$result['tracking_code'] ? $result['tracking_code'] : '—'
		)
	);

	return $result;
}

/**
 * Steadfast: create a consignment.
 *
 * @param array $config Stored fields.
 * @param array $parcel From simple_bangla_cms_courier_parcel().
 * @return array|WP_Error
 */
function simple_bangla_cms_steadfast_dispatch( $config, $parcel ) {

	$response = wp_remote_post(
		'https://portal.packzy.com/api/v1/create_order',
		array(
			'timeout' => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT,
			'headers' => array(
				'Api-Key'      => $config['api_key'],
				'Secret-Key'   => $config['secret_key'],
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'invoice'           => $parcel['invoice'],
					'recipient_name'    => $parcel['name'],
					'recipient_phone'   => $parcel['phone'],
					'recipient_address' => $parcel['address'],
					'cod_amount'        => $parcel['collect'],
					'note'              => $parcel['note'],
				)
			),
		)
	);

	$body = simple_bangla_cms_courier_json( $response, __( 'Steadfast', 'simple-bangla-cms' ) );

	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['consignment'] ) ) {
		return new WP_Error(
			'sb_cms_courier_refused',
			isset( $body['message'] ) ? (string) $body['message'] : __( 'Steadfast refused the parcel.', 'simple-bangla-cms' ),
			array( 'status' => 502 )
		);
	}

	return array(
		'consignment_id' => (string) ( $body['consignment']['consignment_id'] ?? '' ),
		'tracking_code'  => (string) ( $body['consignment']['tracking_code'] ?? '' ),
		'status'         => (string) ( $body['consignment']['status'] ?? '' ),
	);
}

/**
 * Pathao: an OAuth token, then an order.
 *
 * @param array $config Stored fields.
 * @param array $parcel From simple_bangla_cms_courier_parcel().
 * @return array|WP_Error
 */
function simple_bangla_cms_pathao_dispatch( $config, $parcel ) {

	$token = simple_bangla_cms_pathao_token( $config );

	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$response = wp_remote_post(
		'https://api-hermes.pathao.com/aladdin/api/v1/orders',
		array(
			'timeout' => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'store_id'            => (int) $config['store_id'],
					'merchant_order_id'   => $parcel['invoice'],
					'recipient_name'      => $parcel['name'],
					'recipient_phone'     => $parcel['phone'],
					'recipient_address'   => $parcel['address'],
					'recipient_city'      => (int) $config['city_id'],
					'recipient_zone'      => (int) $config['zone_id'],
					// 48 is Pathao's "normal delivery"; 2 is a parcel rather than a document.
					'delivery_type'       => 48,
					'item_type'           => 2,
					'item_quantity'       => $parcel['items'],
					'item_weight'         => 0.5,
					'amount_to_collect'   => $parcel['collect'],
					'item_description'    => $parcel['note'],
				)
			),
		)
	);

	$body = simple_bangla_cms_courier_json( $response, __( 'Pathao', 'simple-bangla-cms' ) );

	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['data']['consignment_id'] ) ) {
		return new WP_Error(
			'sb_cms_courier_refused',
			isset( $body['message'] ) ? (string) $body['message'] : __( 'Pathao refused the parcel.', 'simple-bangla-cms' ),
			array( 'status' => 502 )
		);
	}

	return array(
		'consignment_id' => (string) $body['data']['consignment_id'],
		'tracking_code'  => (string) $body['data']['consignment_id'],
		'status'         => (string) ( $body['data']['order_status'] ?? '' ),
	);
}

/**
 * A Pathao access token.
 *
 * Cached for slightly less than the hour Pathao issues it for, because a token fetched on every
 * dispatch turns a batch of twenty parcels into forty requests.
 *
 * @param array $config Stored fields.
 * @return string|WP_Error
 */
function simple_bangla_cms_pathao_token( $config ) {

	$cache_key = 'sb_cms_pathao_token_' . md5( $config['client_id'] . '|' . $config['username'] );
	$cached    = get_transient( $cache_key );

	if ( $cached ) {
		return $cached;
	}

	$response = wp_remote_post(
		'https://api-hermes.pathao.com/aladdin/api/v1/issue-token',
		array(
			'timeout' => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'client_id'     => $config['client_id'],
					'client_secret' => $config['client_secret'],
					'grant_type'    => 'password',
					'username'      => $config['username'],
					'password'      => $config['password'],
				)
			),
		)
	);

	$body = simple_bangla_cms_courier_json( $response, __( 'Pathao', 'simple-bangla-cms' ) );

	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['access_token'] ) ) {
		return new WP_Error(
			'sb_cms_courier_auth',
			__( 'Pathao would not issue a token. Check the client ID, secret and merchant login.', 'simple-bangla-cms' ),
			array( 'status' => 502 )
		);
	}

	set_transient( $cache_key, $body['access_token'], 50 * MINUTE_IN_SECONDS );

	return $body['access_token'];
}

/**
 * RedX: create a parcel.
 *
 * @param array $config Stored fields.
 * @param array $parcel From simple_bangla_cms_courier_parcel().
 * @return array|WP_Error
 */
function simple_bangla_cms_redx_dispatch( $config, $parcel ) {

	$response = wp_remote_post(
		'https://openapi.redx.com.bd/v1.0.0-beta/parcel',
		array(
			'timeout' => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT,
			'headers' => array(
				'API-ACCESS-TOKEN' => 'Bearer ' . $config['access_token'],
				'Content-Type'     => 'application/json',
				'Accept'           => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'customer_name'           => $parcel['name'],
					'customer_phone'          => $parcel['phone'],
					'customer_address'        => $parcel['address'],
					'delivery_area_id'        => (int) $config['area_id'],
					'merchant_invoice_id'     => $parcel['invoice'],
					'cash_collection_amount'  => (string) $parcel['collect'],
					'parcel_weight'           => 500,
					'value'                   => $parcel['collect'],
					'instruction'             => $parcel['note'],
				)
			),
		)
	);

	$body = simple_bangla_cms_courier_json( $response, __( 'RedX', 'simple-bangla-cms' ) );

	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['tracking_id'] ) ) {
		return new WP_Error(
			'sb_cms_courier_refused',
			isset( $body['message'] ) ? (string) $body['message'] : __( 'RedX refused the parcel.', 'simple-bangla-cms' ),
			array( 'status' => 502 )
		);
	}

	return array(
		'consignment_id' => (string) $body['tracking_id'],
		'tracking_code'  => (string) $body['tracking_id'],
		'status'         => '',
	);
}

/* ------------------------------------------------------------------ delivery record */

/**
 * A phone number in the one form every courier portal is asked with.
 *
 * The same Bangladeshi mobile arrives as `01712345678`, `+8801712345678`, `8801712345678` and
 * `01712-345678` depending on the customer and the keyboard — the same problem the theme's block
 * list solves, and the same answer: decide on the last ten digits. Normalising here rather than
 * inside each courier keeps one cached answer per customer instead of one per spelling.
 *
 * @param string $phone Phone number as typed.
 * @return string Local 11-digit form, or '' if it is not a number worth asking about.
 */
function simple_bangla_cms_record_phone( $phone ) {

	$digits = preg_replace( '/\D+/', '', (string) $phone );

	if ( strlen( $digits ) < 10 ) {
		return '';
	}

	return '0' . substr( $digits, -10 );
}

/**
 * Whether an array is a plain list rather than a record.
 *
 * `array_is_list()` is PHP 8.1 and this plugin runs on whatever the shop's host offers.
 *
 * @param array $value Array.
 * @return bool
 */
function simple_bangla_cms_is_list( $value ) {

	if ( ! is_array( $value ) ) {
		return false;
	}

	return array() === $value || array_keys( $value ) === range( 0, count( $value ) - 1 );
}

/**
 * The first non-empty string among a set of candidate keys.
 *
 * @param array $row  Source row.
 * @param array $keys Keys to try, in order of preference.
 * @return string
 */
function simple_bangla_cms_first_string( $row, $keys ) {

	foreach ( $keys as $key ) {

		if ( ! isset( $row[ $key ] ) || ! is_scalar( $row[ $key ] ) ) {
			continue;
		}

		$value = trim( wp_strip_all_tags( (string) $row[ $key ] ) );

		if ( '' !== $value ) {
			return $value;
		}
	}

	return '';
}

/**
 * One merchant report, in the shape the interface draws.
 *
 * @param mixed $entry A string, or a row from the courier's reply.
 * @return array{text:string,source:string,date:string}|null
 */
function simple_bangla_cms_record_report_entry( $entry ) {

	if ( is_string( $entry ) || is_numeric( $entry ) ) {
		$text = trim( wp_strip_all_tags( (string) $entry ) );

		return '' === $text ? null : array(
			'text'   => $text,
			'source' => '',
			'date'   => '',
		);
	}

	if ( ! is_array( $entry ) ) {
		return null;
	}

	$text   = simple_bangla_cms_first_string( $entry, array( 'details', 'detail', 'report', 'reason', 'note', 'comment', 'message', 'description', 'text', 'body', 'remarks', 'title' ) );
	$source = simple_bangla_cms_first_string( $entry, array( 'merchant', 'merchant_name', 'shop', 'shop_name', 'store', 'store_name', 'reported_by', 'company', 'user', 'name' ) );
	$date   = simple_bangla_cms_first_string( $entry, array( 'created_at', 'reported_at', 'date', 'time', 'updated_at' ) );

	// A row with neither a complaint nor an author is some other part of the payload that happened
	// to sit under a key this looks at — a count, a flag, a pagination block.
	if ( '' === $text && '' === $source ) {
		return null;
	}

	return array(
		// Long enough for a real complaint, short enough that a runaway field cannot fill the card.
		'text'   => function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 500 ) : substr( $text, 0, 500 ),
		'source' => $source,
		'date'   => $date,
	);
}

/**
 * Any notes or complaints other merchants have filed against a number.
 *
 * There is no published schema for this — the portal's own dashboard renders it and nothing
 * documents the field it comes back in — so rather than betting on one key this reads every key
 * the reply plausibly carries it under and normalises whatever is found. A wrong guess costs an
 * empty list, which is the honest answer when nothing recognisable arrived; betting on a single
 * key would cost a silent "no reports" over a customer who has several.
 *
 * @param array $body Decoded reply.
 * @return array<int,array{text:string,source:string,date:string}>
 */
function simple_bangla_cms_record_reports( $body ) {

	$haystacks = array( $body );

	foreach ( array( 'data', 'result', 'customer' ) as $nest ) {
		if ( isset( $body[ $nest ] ) && is_array( $body[ $nest ] ) ) {
			$haystacks[] = $body[ $nest ];
		}
	}

	$keys  = array( 'reports', 'fraud_reports', 'frauds', 'fraud', 'notes', 'remarks', 'comments', 'feedbacks', 'customer_reports' );
	$found = array();
	$seen  = array();

	foreach ( $haystacks as $haystack ) {
		foreach ( $keys as $key ) {

			if ( ! isset( $haystack[ $key ] ) || ! is_array( $haystack[ $key ] ) ) {
				continue;
			}

			// A single report may arrive as the record itself rather than as a list of one.
			$rows = simple_bangla_cms_is_list( $haystack[ $key ] ) ? $haystack[ $key ] : array( $haystack[ $key ] );

			foreach ( $rows as $row ) {

				$report = simple_bangla_cms_record_report_entry( $row );

				if ( ! $report ) {
					continue;
				}

				// The same report reached through two of the keys above is still one report.
				$fingerprint = md5( $report['text'] . '|' . $report['source'] . '|' . $report['date'] );

				if ( isset( $seen[ $fingerprint ] ) ) {
					continue;
				}

				$seen[ $fingerprint ] = true;
				$found[]              = $report;

				if ( count( $found ) >= 20 ) {
					return $found;
				}
			}
		}
	}

	return $found;
}

/**
 * A customer's record with every courier this shop can ask.
 *
 * **Steadfast is the report**; the other two are corroboration. It is the courier this shop
 * actually dispatches with, it is the only one of the three whose portal publishes the notes other
 * merchants have filed against a number, and it is therefore the one whose absence is itself an
 * answer — an unconfigured Steadfast comes back as a line saying so, while an unconfigured Pathao
 * or RedX is simply not mentioned. Since this report no longer carries the shop's own order
 * history, silence from all three would otherwise be an empty card with nothing to explain it.
 *
 * Each courier is asked separately and a failure is reported per courier rather than failing the
 * whole report — one expired portal password should not hide the figures that did arrive.
 *
 * @param string $phone Phone number.
 * @param bool   $fresh Skip the cache.
 * @return array<int,array>
 */
function simple_bangla_cms_courier_records( $phone, $fresh = false ) {

	$out   = array();
	$phone = simple_bangla_cms_record_phone( $phone );

	if ( '' === $phone ) {
		return $out;
	}

	foreach ( simple_bangla_cms_courier_providers() as $key => $provider ) {

		$primary = ( SIMPLE_BANGLA_CMS_RECORD_PRIMARY === $key );
		$ready   = simple_bangla_cms_courier_ready( $key, 'record' );

		if ( is_wp_error( $ready ) ) {

			if ( $primary ) {
				$out[] = array(
					'provider' => $key,
					'label'    => $provider['label'],
					'primary'  => true,
					'cached'   => false,
					'error'    => $ready->get_error_message(),
				);
			}

			continue;
		}

		// Versioned: the cached shape gained the reports list, and a six-hour-old entry from before
		// that would render as a customer with no reports rather than as one never asked about.
		$cache_key = 'sb_cms_record2_' . $key . '_' . md5( $phone );
		$cached    = $fresh ? false : get_transient( $cache_key );

		if ( false !== $cached ) {
			$cached['cached'] = true;
			$out[]            = $cached;
			continue;
		}

		$config = simple_bangla_cms_courier_settings()['providers'][ $key ];

		switch ( $key ) {
			case 'steadfast':
				$record = simple_bangla_cms_steadfast_record( $config, $phone );
				break;
			case 'pathao':
				$record = simple_bangla_cms_pathao_record( $config, $phone );
				break;
			case 'redx':
				$record = simple_bangla_cms_redx_record( $config, $phone );
				break;
			default:
				continue 2;
		}

		$entry = array(
			'provider' => $key,
			'label'    => $provider['label'],
			'primary'  => $primary,
			'cached'   => false,
		);

		if ( is_wp_error( $record ) ) {
			$entry['error'] = $record->get_error_message();
		} else {
			$entry = array_merge( $entry, $record );
			// Only a real answer is cached. Caching a failure would keep showing "could not be read"
			// for six hours after the password was corrected.
			set_transient( $cache_key, $entry, SIMPLE_BANGLA_CMS_FRAUD_TTL );
		}

		$out[] = $entry;
	}

	return $out;
}

/**
 * Steadfast's delivery record.
 *
 * There is no API-key route to this figure; the merchant portal's own dashboard calls this endpoint
 * behind a session cookie, so that is what is done here — fetch the sign-in page for its CSRF token,
 * post the portal login, then ask. Undocumented and liable to change, which is why the caller treats
 * a failure as "unknown" rather than as an answer.
 *
 * @param array  $config Stored fields.
 * @param string $phone  Phone number.
 * @return array|WP_Error
 */
function simple_bangla_cms_steadfast_record( $config, $phone ) {

	$page = wp_remote_get( 'https://steadfast.com.bd/login', array( 'timeout' => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT ) );

	if ( is_wp_error( $page ) ) {
		return $page;
	}

	$html  = wp_remote_retrieve_body( $page );
	$token = '';

	if ( preg_match( '/name=["\']_token["\'][^>]*value=["\']([^"\']+)["\']/', $html, $found ) ) {
		$token = $found[1];
	} elseif ( preg_match( '/name=["\']csrf-token["\'][^>]*content=["\']([^"\']+)["\']/', $html, $found ) ) {
		$token = $found[1];
	}

	if ( ! $token ) {
		return new WP_Error( 'sb_cms_record_no_token', __( 'Steadfast’s sign-in page has changed, so its delivery record cannot be read.', 'simple-bangla-cms' ) );
	}

	$cookies = wp_remote_retrieve_cookies( $page );

	$login = wp_remote_post(
		'https://steadfast.com.bd/login',
		array(
			'timeout'     => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT,
			'cookies'     => $cookies,
			// Following the redirect would drop the session cookie the sign-in sets.
			'redirection' => 0,
			'body'        => array(
				'_token'   => $token,
				'email'    => $config['portal_email'],
				'password' => $config['portal_password'],
			),
		)
	);

	if ( is_wp_error( $login ) ) {
		return $login;
	}

	$cookies = array_merge( $cookies, wp_remote_retrieve_cookies( $login ) );

	$check = wp_remote_get(
		'https://steadfast.com.bd/user/frauds/check/' . rawurlencode( $phone ),
		array(
			'timeout' => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT,
			'cookies' => $cookies,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	$body = simple_bangla_cms_courier_json( $check, __( 'Steadfast', 'simple-bangla-cms' ) );

	if ( is_wp_error( $body ) ) {
		return $body;
	}

	return simple_bangla_cms_record_shape(
		(int) ( $body['total_delivered'] ?? $body['success'] ?? 0 ),
		(int) ( $body['total_cancelled'] ?? $body['cancel'] ?? 0 ),
		// Always an array, even when empty: Steadfast is the one portal that publishes these, so
		// "asked and found none" is a real answer here and must not read like the two couriers that
		// were never able to be asked.
		simple_bangla_cms_record_reports( $body )
	);
}

/**
 * Pathao's delivery record, through the merchant portal.
 *
 * @param array  $config Stored fields.
 * @param string $phone  Phone number.
 * @return array|WP_Error
 */
function simple_bangla_cms_pathao_record( $config, $phone ) {

	$login = wp_remote_post(
		'https://merchant.pathao.com/api/v1/login',
		array(
			'timeout' => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'username' => $config['username'],
					'password' => $config['password'],
				)
			),
		)
	);

	$body = simple_bangla_cms_courier_json( $login, __( 'Pathao', 'simple-bangla-cms' ) );

	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['access_token'] ) ) {
		return new WP_Error( 'sb_cms_record_auth', __( 'Pathao would not accept that merchant login.', 'simple-bangla-cms' ) );
	}

	$check = wp_remote_post(
		'https://merchant.pathao.com/api/v1/user/success',
		array(
			'timeout' => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT,
			'headers' => array(
				'Authorization' => 'Bearer ' . trim( $body['access_token'] ),
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'    => wp_json_encode( array( 'phone' => $phone ) ),
		)
	);

	$data = simple_bangla_cms_courier_json( $check, __( 'Pathao', 'simple-bangla-cms' ) );

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$stats = isset( $data['data']['customer'] ) ? $data['data']['customer'] : ( $data['data'] ?? array() );

	return simple_bangla_cms_record_shape(
		(int) ( $stats['successful_delivery'] ?? 0 ),
		max( 0, (int) ( $stats['total_delivery'] ?? 0 ) - (int) ( $stats['successful_delivery'] ?? 0 ) )
	);
}

/**
 * RedX's delivery record, through the merchant portal.
 *
 * @param array  $config Stored fields.
 * @param string $phone  Phone number.
 * @return array|WP_Error
 */
function simple_bangla_cms_redx_record( $config, $phone ) {

	$digits = preg_replace( '/\D+/', '', (string) $phone );
	$digits = '88' . substr( $digits, -11 );

	$login = wp_remote_post(
		'https://api.redx.com.bd/v4/auth/login',
		array(
			'timeout' => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'phone'    => '88' . substr( preg_replace( '/\D+/', '', $config['portal_phone'] ), -11 ),
					'password' => $config['portal_password'],
				)
			),
		)
	);

	$body = simple_bangla_cms_courier_json( $login, __( 'RedX', 'simple-bangla-cms' ) );

	if ( is_wp_error( $body ) ) {
		return $body;
	}

	$token = $body['data']['accessToken'] ?? $body['accessToken'] ?? $body['access_token'] ?? '';

	if ( ! $token ) {
		return new WP_Error( 'sb_cms_record_auth', __( 'RedX would not accept that portal login.', 'simple-bangla-cms' ) );
	}

	$check = wp_remote_get(
		add_query_arg(
			'phoneNumber',
			$digits,
			'https://redx.com.bd/api/redx_se/admin/parcel/customer-success-return-rate'
		),
		array(
			'timeout' => SIMPLE_BANGLA_CMS_COURIER_TIMEOUT,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept'        => 'application/json',
			),
		)
	);

	$data = simple_bangla_cms_courier_json( $check, __( 'RedX', 'simple-bangla-cms' ) );

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$stats = $data['data'] ?? $data;

	return simple_bangla_cms_record_shape(
		(int) ( $stats['deliveredParcels'] ?? $stats['success'] ?? 0 ),
		(int) ( $stats['returnedParcels'] ?? $stats['cancel'] ?? 0 )
	);
}

/**
 * One courier's numbers, in the single shape the interface renders.
 *
 * The rate is computed here rather than trusted from each courier, because the three of them do not
 * agree on whether it is a fraction, a percentage, or rounded.
 *
 * @param int        $delivered Successful deliveries.
 * @param int        $returned  Refused or returned parcels.
 * @param array|null $reports   Merchant reports filed against the number, or null when this
 *                              courier has no way of answering that question at all.
 * @return array{delivered:int,returned:int,total:int,rate:int|null,reports:array,reports_read:bool}
 */
function simple_bangla_cms_record_shape( $delivered, $returned, $reports = null ) {

	$total = $delivered + $returned;

	return array(
		'delivered'    => $delivered,
		'returned'     => $returned,
		'total'        => $total,
		// Null rather than 0 for a customer no courier has seen: "0% successful" and "never ordered"
		// are opposite recommendations and must not render the same.
		'rate'         => $total ? (int) round( $delivered / $total * 100 ) : null,
		'reports'      => is_array( $reports ) ? $reports : array(),
		// The same distinction one line down from the same trap: "nobody has complained" and "this
		// courier does not publish complaints" are opposite readings of an empty list.
		'reports_read' => is_array( $reports ),
	);
}

/* ------------------------------------------------------------------ REST */

/**
 * Register the courier routes.
 */
function simple_bangla_cms_register_courier_routes() {

	register_rest_route(
		SIMPLE_BANGLA_CMS_REST_NS,
		'/courier',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'simple_bangla_cms_courier_get',
				'permission_callback' => simple_bangla_cms_permission( 'store.manage' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'simple_bangla_cms_courier_save',
				'permission_callback' => simple_bangla_cms_permission( 'store.manage' ),
			),
		)
	);

	register_rest_route(
		SIMPLE_BANGLA_CMS_REST_NS,
		'/orders/(?P<id>\d+)/courier',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'simple_bangla_cms_courier_send',
			'permission_callback' => simple_bangla_cms_permission( 'orders.manage' ),
			'args'                => array(
				'provider' => array( 'type' => 'string', 'required' => false ),
				'force'    => array( 'type' => 'boolean', 'required' => false ),
			),
		)
	);

	register_rest_route(
		SIMPLE_BANGLA_CMS_REST_NS,
		'/orders/(?P<id>\d+)/record',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'simple_bangla_cms_courier_record_get',
			'permission_callback' => simple_bangla_cms_permission( 'orders.view' ),
			'args'                => array(
				'refresh' => array( 'type' => 'boolean', 'required' => false ),
			),
		)
	);
}
add_action( 'rest_api_init', 'simple_bangla_cms_register_courier_routes' );

/**
 * Publish the dispatch record as a field on the order itself.
 *
 * It is stored as `_sb_courier` meta, and WooCommerce does return custom meta inside `meta_data` —
 * but which meta it is willing to expose has changed more than once across WooCommerce versions,
 * and the order list would silently lose its courier column the day it changes again. A registered
 * field is a promise: it is read-only, it is named, and it does not depend on how WooCommerce feels
 * about underscores.
 */
function simple_bangla_cms_register_courier_field() {

	register_rest_field(
		'shop_order',
		'sb_courier',
		array(
			'get_callback' => static function ( $order ) {
				$wc_order = wc_get_order( isset( $order['id'] ) ? $order['id'] : 0 );

				if ( ! $wc_order ) {
					return null;
				}

				$stored = $wc_order->get_meta( '_sb_courier' );

				return is_array( $stored ) ? $stored : null;
			},
			'schema'       => array(
				'description' => __( 'What this order was sent to a courier as, if it has been.', 'simple-bangla-cms' ),
				'type'        => array( 'object', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		)
	);
}
add_action( 'rest_api_init', 'simple_bangla_cms_register_courier_field' );

/**
 * Return the courier configuration, secrets withheld.
 *
 * @return WP_REST_Response
 */
function simple_bangla_cms_courier_get() {
	return rest_ensure_response( simple_bangla_cms_courier_public_settings() );
}

/**
 * Write the courier configuration.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function simple_bangla_cms_courier_save( $request ) {

	simple_bangla_cms_save_courier_settings(
		array(
			'active'    => $request->get_param( 'active' ),
			'providers' => (array) $request->get_param( 'providers' ),
		)
	);

	return rest_ensure_response( simple_bangla_cms_courier_public_settings() );
}

/**
 * Load an order for a courier route.
 *
 * @param WP_REST_Request $request Request.
 * @return WC_Order|WP_Error
 */
function simple_bangla_cms_courier_order( $request ) {

	if ( ! function_exists( 'wc_get_order' ) ) {
		return new WP_Error( 'sb_cms_no_woocommerce', __( 'WooCommerce is not active.', 'simple-bangla-cms' ), array( 'status' => 409 ) );
	}

	$order = wc_get_order( (int) $request->get_param( 'id' ) );

	if ( ! $order ) {
		return new WP_Error( 'sb_cms_no_order', __( 'That order no longer exists.', 'simple-bangla-cms' ), array( 'status' => 404 ) );
	}

	return $order;
}

/**
 * Send an order to the courier.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function simple_bangla_cms_courier_send( $request ) {

	$order = simple_bangla_cms_courier_order( $request );

	if ( is_wp_error( $order ) ) {
		return $order;
	}

	$existing = $order->get_meta( '_sb_courier' );

	// Booking the same parcel twice means two couriers arriving, or the same one twice, and the
	// shop paying for both. `force` exists because a first attempt can genuinely need repeating.
	if ( $existing && ! $request->get_param( 'force' ) ) {
		return new WP_Error(
			'sb_cms_already_sent',
			sprintf(
				/* translators: 1: courier name, 2: consignment id. */
				__( 'This order was already sent to %1$s as consignment %2$s.', 'simple-bangla-cms' ),
				isset( $existing['provider_label'] ) ? $existing['provider_label'] : __( 'a courier', 'simple-bangla-cms' ),
				isset( $existing['consignment_id'] ) ? $existing['consignment_id'] : '—'
			),
			array(
				'status'   => 409,
				'shipment' => $existing,
			)
		);
	}

	$result = simple_bangla_cms_courier_dispatch( $order, (string) $request->get_param( 'provider' ) );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	/*
	 * **A dispatch does not change the status** (owner's decision, 2026-08-12, replacing the move to
	 * the theme's `sb-courier` stage this used to make). The order stays in New Orders wearing its
	 * consignment number, and it leaves only when someone says how it ended — delivered, cancelled or
	 * returned. The reasoning is the shop's own: an order handed to the courier this morning is still
	 * the shop's outstanding work, and moving it to a tab of its own meant every parcel had to be
	 * found twice.
	 *
	 * Nothing replaces the transition here. The dispatch is already recorded twice over by
	 * `simple_bangla_cms_courier_dispatch()` — as `_sb_courier` meta, which is what the list reads to
	 * show the consignment number and what refuses a second booking, and as an order note, which is
	 * the trail a human reads. A status was only ever the third telling of the same fact.
	 */

	return rest_ensure_response(
		array(
			'shipment' => $result,
			'status'   => $order->get_status(),
		)
	);
}

/**
 * The delivery record for an order's phone number.
 *
 * This used to lead with a count of the shop's own orders from the number. The owner asked for it
 * removed (2026-08-12): the shop's own history is already on the Orders screen a search away, and
 * putting it first in a card headed "fraud" invited reading a good customer's four completed orders
 * here as evidence about how they behave *everywhere*, which is the only question this card exists
 * to answer.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function simple_bangla_cms_courier_record_get( $request ) {

	$order = simple_bangla_cms_courier_order( $request );

	if ( is_wp_error( $order ) ) {
		return $order;
	}

	$phone = $order->get_billing_phone();

	return rest_ensure_response(
		array(
			'phone'    => $phone,
			'couriers' => simple_bangla_cms_courier_records( $phone, (bool) $request->get_param( 'refresh' ) ),
		)
	);
}
