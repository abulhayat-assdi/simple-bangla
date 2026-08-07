<?php
/**
 * Store details: contact routes, social profiles, and the footer's payment strip.
 *
 * Everything the footer, the mobile bottom bar, the WhatsApp float and the product page's
 * "order on WhatsApp" button need comes from here, so none of it is ever hard-coded in a
 * template. Defaults are obvious placeholders — the store owner replaces them in the
 * Customizer without touching code.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/**
 * The store-detail fields, their defaults and how each is sanitised.
 *
 * @return array<string,array{label:string,description:string,default:string,sanitize:string,type:string}>
 */
function simple_bangla_contact_fields() {
	return array(
		'phone'     => array(
			'label'       => __( 'Phone number', 'simple-bangla' ),
			'description' => __( 'Shown in the footer, the menu drawer and the mobile Call button.', 'simple-bangla' ),
			'default'     => '+880 1XXX-XXXXXX',
			'sanitize'    => 'sanitize_text_field',
			'type'        => 'text',
		),
		'whatsapp'  => array(
			'label'       => __( 'WhatsApp number', 'simple-bangla' ),
			'description' => __( 'Powers the floating WhatsApp button and the Order on WhatsApp button on each product.', 'simple-bangla' ),
			'default'     => '+880 1XXX-XXXXXX',
			'sanitize'    => 'sanitize_text_field',
			'type'        => 'text',
		),
		'email'     => array(
			'label'       => __( 'Email address', 'simple-bangla' ),
			'description' => __( 'Shown in the footer contact row.', 'simple-bangla' ),
			'default'     => 'hello@simplebangla.com',
			'sanitize'    => 'sanitize_email',
			'type'        => 'email',
		),
		'messenger' => array(
			'label'       => __( 'Messenger username', 'simple-bangla' ),
			'description' => __( 'Just the username, without m.me/. Used by the mobile Chat button.', 'simple-bangla' ),
			'default'     => 'simplebangla',
			'sanitize'    => 'sanitize_text_field',
			'type'        => 'text',
		),
		'facebook'  => array(
			'label'       => __( 'Facebook URL', 'simple-bangla' ),
			'description' => '',
			'default'     => 'https://facebook.com/simplebangla',
			'sanitize'    => 'esc_url_raw',
			'type'        => 'url',
		),
		'instagram' => array(
			'label'       => __( 'Instagram URL', 'simple-bangla' ),
			'description' => '',
			'default'     => 'https://instagram.com/simplebangla',
			'sanitize'    => 'esc_url_raw',
			'type'        => 'url',
		),
		'youtube'   => array(
			'label'       => __( 'YouTube URL', 'simple-bangla' ),
			'description' => '',
			'default'     => 'https://youtube.com/@simplebangla',
			'sanitize'    => 'esc_url_raw',
			'type'        => 'url',
		),
		'address'   => array(
			'label'       => __( 'Street address', 'simple-bangla' ),
			'description' => __( 'One line. Shown under the footer logo.', 'simple-bangla' ),
			'default'     => 'Dhaka, Bangladesh',
			'sanitize'    => 'sanitize_text_field',
			'type'        => 'text',
		),
	);
}

/**
 * Read one store-detail field.
 *
 * @param string $key Field key from simple_bangla_contact_fields().
 * @return string Empty string when the field is unknown or blank.
 */
function simple_bangla_get_contact( $key ) {

	$fields = simple_bangla_contact_fields();

	if ( ! isset( $fields[ $key ] ) ) {
		return '';
	}

	$value = get_theme_mod( 'simple_bangla_contact_' . $key, $fields[ $key ]['default'] );

	return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Reduce a human-formatted phone number to something a tel: link accepts.
 *
 * "+880 1712-345678" becomes "+8801712345678". Everything that is not a digit or the leading
 * plus is dropped, because dialers choke on spaces and dashes on some Android builds.
 *
 * @param string $phone Display number.
 * @return string
 */
function simple_bangla_tel_href( $phone ) {

	$digits = preg_replace( '/[^0-9]/', '', $phone );

	if ( ! $digits ) {
		return '';
	}

	return ( 0 === strpos( trim( $phone ), '+' ) ? '+' : '' ) . $digits;
}

/**
 * Build a wa.me link, optionally pre-filling the message.
 *
 * @param string $message Prefilled message body.
 * @return string Empty string when no WhatsApp number is configured.
 */
function simple_bangla_whatsapp_url( $message = '' ) {

	$digits = preg_replace( '/[^0-9]/', '', simple_bangla_get_contact( 'whatsapp' ) );

	if ( ! $digits ) {
		return '';
	}

	$url = 'https://wa.me/' . $digits;

	if ( $message ) {
		$url = add_query_arg( 'text', rawurlencode( $message ), $url );
	}

	return $url;
}

/**
 * The configured contact icons, in display order.
 *
 * Email and WhatsApp sit in the same row as the social profiles because that is how the
 * reference footer arranges them — five circles, not three social ones plus a stray mailto.
 *
 * @return array<int,array{key:string,label:string,url:string}>
 */
function simple_bangla_social_links() {

	$links = array();

	$networks = array(
		'facebook'  => __( 'Facebook', 'simple-bangla' ),
		'instagram' => __( 'Instagram', 'simple-bangla' ),
		'youtube'   => __( 'YouTube', 'simple-bangla' ),
	);

	foreach ( $networks as $key => $label ) {

		$url = simple_bangla_get_contact( $key );

		if ( ! $url ) {
			continue;
		}

		$links[] = array(
			'key'   => $key,
			'label' => $label,
			'url'   => $url,
		);
	}

	$email = simple_bangla_get_contact( 'email' );

	if ( $email && is_email( $email ) ) {
		$links[] = array(
			'key'   => 'mail',
			'label' => __( 'Email us', 'simple-bangla' ),
			'url'   => 'mailto:' . $email,
		);
	}

	$whatsapp = simple_bangla_whatsapp_url();

	if ( $whatsapp ) {
		$links[] = array(
			'key'   => 'whatsapp',
			'label' => __( 'WhatsApp', 'simple-bangla' ),
			'url'   => $whatsapp,
		);
	}

	return $links;
}

/**
 * Register the store-details section.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function simple_bangla_customize_store( $wp_customize ) {

	$wp_customize->add_section(
		'simple_bangla_store',
		array(
			'title'       => __( 'Store details', 'simple-bangla' ),
			'description' => __( 'Contact routes and social profiles. These placeholders are wired through the whole theme — replace them with your real details before launch.', 'simple-bangla' ),
			'panel'       => 'simple_bangla_panel',
			'priority'    => 20,
		)
	);

	foreach ( simple_bangla_contact_fields() as $key => $field ) {

		$setting_id = 'simple_bangla_contact_' . $key;

		$wp_customize->add_setting(
			$setting_id,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => $field['sanitize'],
				'transport'         => 'refresh',
				'capability'        => 'edit_theme_options',
			)
		);

		$wp_customize->add_control(
			$setting_id,
			array(
				'label'       => $field['label'],
				'description' => $field['description'],
				'section'     => 'simple_bangla_store',
				'type'        => $field['type'],
			)
		);
	}

	// Payment methods strip — an image rather than a set of brand icons, because which cards
	// and wallets a Bangladeshi store accepts varies and bKash/Nagad have no standard glyph.
	$wp_customize->add_setting(
		'simple_bangla_payment_strip',
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
			'simple_bangla_payment_strip',
			array(
				'label'       => __( 'Payment methods image', 'simple-bangla' ),
				'description' => __( 'A single wide image showing the payment methods you accept. Shown near the bottom of the footer.', 'simple-bangla' ),
				'section'     => 'simple_bangla_store',
				'mime_type'   => 'image',
			)
		)
	);
}
add_action( 'customize_register', 'simple_bangla_customize_store', 20 );
