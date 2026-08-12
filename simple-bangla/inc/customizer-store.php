<?php
/**
 * Store details: contact routes, social profiles, and the footer's payment strip.
 *
 * Everything the footer, the mobile bottom bar, the WhatsApp float and the product page's
 * "order on WhatsApp" button need comes from here, so none of it is ever hard-coded in a
 * template.
 *
 * **Every content field ships empty, and the example lives in its description** (2026-08-12).
 * These used to default to `+880 1XXX-XXXXXX`, `hello@simplebangla.com` and three
 * `.../simplebangla` social URLs, on the reasoning that an obvious placeholder invites being
 * replaced. On a shop that went live before anyone opened the Customizer it did the opposite:
 * `simple_bangla_tel_href()` strips the X's, so the mobile Call button rendered as `tel:+8801`
 * and the WhatsApp float as `wa.me/8801` — the two most-tapped controls on a phone, both dialling
 * nothing, and neither of them looking broken until a customer tapped one. The social defaults
 * were worse than useless: they pointed at handles this shop may not own.
 *
 * Empty is the honest default because every consumer already treats a blank field as "not
 * configured" and simply does not render the control. So an unconfigured shop shows no Call
 * button rather than a dead one, and the example the owner needs is on screen beside the field
 * where an example belongs.
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
		'phone'          => array(
			'label'       => __( 'Phone number', 'simple-bangla' ),
			'description' => __( 'Shown in the footer, the menu drawer and the mobile Call button. Leave it empty and no Call button is shown at all. Example: +880 1712-345678', 'simple-bangla' ),
			'default'     => '',
			'sanitize'    => 'sanitize_text_field',
			'type'        => 'text',
		),
		'whatsapp'       => array(
			'label'       => __( 'WhatsApp number', 'simple-bangla' ),
			'description' => __( 'Powers the floating WhatsApp button and the Order on WhatsApp button on each product. Leave it empty and neither is shown. Example: +880 1712-345678', 'simple-bangla' ),
			'default'     => '',
			'sanitize'    => 'sanitize_text_field',
			'type'        => 'text',
		),
		'email'          => array(
			'label'       => __( 'Email address', 'simple-bangla' ),
			'description' => __( 'Shown in the footer contact row. Example: hello@yourshop.com', 'simple-bangla' ),
			'default'     => '',
			'sanitize'    => 'sanitize_email',
			'type'        => 'email',
		),
		'messenger'      => array(
			'label'       => __( 'Messenger username', 'simple-bangla' ),
			'description' => __( 'Just the username, without m.me/. Used by the mobile Chat button. Example: yourshoppage', 'simple-bangla' ),
			'default'     => '',
			'sanitize'    => 'sanitize_text_field',
			'type'        => 'text',
		),
		/*
		 * Each social profile is a URL plus a switch.
		 *
		 * Blanking the URL already drops the icon, but that throws the address away — and a shop that
		 * pauses its Instagram for a month should not have to find the link again afterwards. The
		 * switch is the reversible version of the same decision. Default on, so nothing disappears
		 * from a footer that was working before this field existed — with the URL empty by default
		 * the switch has nothing to show either way, which is the intended starting state.
		 */
		'facebook'       => array(
			'label'       => __( 'Facebook URL', 'simple-bangla' ),
			'description' => __( 'The full address of your page. Example: https://facebook.com/yourshop', 'simple-bangla' ),
			'default'     => '',
			'sanitize'    => 'esc_url_raw',
			'type'        => 'url',
		),
		'facebook_show'  => array(
			'label'       => __( 'Show Facebook', 'simple-bangla' ),
			'description' => __( 'Uncheck to hide the icon without losing the address.', 'simple-bangla' ),
			'default'     => true,
			'sanitize'    => 'simple_bangla_sanitize_checkbox',
			'type'        => 'bool',
		),
		'instagram'      => array(
			'label'       => __( 'Instagram URL', 'simple-bangla' ),
			'description' => __( 'The full address of your profile. Example: https://instagram.com/yourshop', 'simple-bangla' ),
			'default'     => '',
			'sanitize'    => 'esc_url_raw',
			'type'        => 'url',
		),
		'instagram_show' => array(
			'label'       => __( 'Show Instagram', 'simple-bangla' ),
			'description' => __( 'Uncheck to hide the icon without losing the address.', 'simple-bangla' ),
			'default'     => true,
			'sanitize'    => 'simple_bangla_sanitize_checkbox',
			'type'        => 'bool',
		),
		'youtube'        => array(
			'label'       => __( 'YouTube URL', 'simple-bangla' ),
			'description' => __( 'The full address of your channel. Example: https://youtube.com/@yourshop', 'simple-bangla' ),
			'default'     => '',
			'sanitize'    => 'esc_url_raw',
			'type'        => 'url',
		),
		'youtube_show'   => array(
			'label'       => __( 'Show YouTube', 'simple-bangla' ),
			'description' => __( 'Uncheck to hide the icon without losing the address.', 'simple-bangla' ),
			'default'     => true,
			'sanitize'    => 'simple_bangla_sanitize_checkbox',
			'type'        => 'bool',
		),
		'address'        => array(
			'label'       => __( 'Street address', 'simple-bangla' ),
			'description' => __( 'One line. Shown under the footer logo and printed on the invoice. Example: 12 Elephant Road, Dhaka 1205', 'simple-bangla' ),
			'default'     => '',
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
 * Whether a store-detail switch is on.
 *
 * Separate from simple_bangla_get_contact() because that one is typed for strings and a boolean
 * field run through it would come back as an empty string — which reads as "off" whether it is or
 * not. An unknown key answers true, so a caller asking about a field that has no switch behaves as
 * it did before switches existed.
 *
 * @param string $key Field key from simple_bangla_contact_fields().
 * @return bool
 */
function simple_bangla_contact_enabled( $key ) {

	$fields = simple_bangla_contact_fields();

	if ( ! isset( $fields[ $key ] ) ) {
		return true;
	}

	return (bool) get_theme_mod( 'simple_bangla_contact_' . $key, $fields[ $key ]['default'] );
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

		if ( ! $url || ! simple_bangla_contact_enabled( $key . '_show' ) ) {
			continue;
		}

		$links[] = array(
			'key'   => $key,
			'label' => $label,
			'url'   => $url,
		);
	}

	/*
	 * The phone number already appears as a line of text above these circles, but a shopper
	 * looking for "how do I call them" scans the icon row, not the paragraph — and on a phone
	 * the circle is the tap target. tel: rather than a page, so it dials straight away.
	 */
	$phone = simple_bangla_tel_href( simple_bangla_get_contact( 'phone' ) );

	if ( $phone ) {
		$links[] = array(
			'key'   => 'phone',
			'label' => __( 'Call us', 'simple-bangla' ),
			'url'   => 'tel:' . $phone,
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
				// The registry names the data's type; the Customizer wants a control's name, and it
				// has no control called "bool".
				'type'        => 'bool' === $field['type'] ? 'checkbox' : $field['type'],
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
