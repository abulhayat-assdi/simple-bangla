<?php
/**
 * Sticky bottom bar for phones.
 *
 * Five destinations, with Home in the middle rendered icon-only — that is how the reference
 * lays it out, and the wider centre target is the one people hit with a thumb.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_phone     = simple_bangla_get_contact( 'phone' );
$simple_bangla_messenger = simple_bangla_get_contact( 'messenger' );

$simple_bangla_items = array();

if ( function_exists( 'wc_get_page_permalink' ) ) {
	$simple_bangla_items[] = array(
		'icon'  => 'shop',
		'label' => __( 'Shop', 'simple-bangla' ),
		'url'   => wc_get_page_permalink( 'shop' ),
	);
}

if ( $simple_bangla_phone ) {
	$simple_bangla_items[] = array(
		'icon'  => 'phone',
		'label' => __( 'Call', 'simple-bangla' ),
		'url'   => 'tel:' . simple_bangla_tel_href( $simple_bangla_phone ),
	);
}

$simple_bangla_items[] = array(
	'icon'      => 'home',
	'label'     => __( 'Home', 'simple-bangla' ),
	'url'       => home_url( '/' ),
	'icon_only' => true,
);

if ( $simple_bangla_messenger ) {
	$simple_bangla_items[] = array(
		'icon'     => 'chat',
		'label'    => __( 'Chat', 'simple-bangla' ),
		'url'      => 'https://m.me/' . rawurlencode( $simple_bangla_messenger ),
		'external' => true,
	);
}

if ( function_exists( 'wc_get_cart_url' ) ) {
	$simple_bangla_items[] = array(
		'icon'  => 'cart',
		'label' => __( 'Cart', 'simple-bangla' ),
		'url'   => wc_get_cart_url(),
	);
}

if ( count( $simple_bangla_items ) < 2 ) {
	return;
}
?>
<nav class="sb-bottom-bar" aria-label="<?php esc_attr_e( 'Quick links', 'simple-bangla' ); ?>">
	<?php foreach ( $simple_bangla_items as $simple_bangla_item ) : ?>
		<a
			class="sb-bottom-bar__item<?php echo empty( $simple_bangla_item['icon_only'] ) ? '' : ' sb-bottom-bar__item--home'; ?>"
			href="<?php echo esc_url( $simple_bangla_item['url'] ); ?>"
			<?php echo empty( $simple_bangla_item['external'] ) ? '' : 'target="_blank" rel="noopener"'; ?>
		>
			<?php simple_bangla_icon( $simple_bangla_item['icon'], 22 ); ?>
			<?php if ( empty( $simple_bangla_item['icon_only'] ) ) : ?>
				<span class="sb-bottom-bar__label"><?php echo esc_html( $simple_bangla_item['label'] ); ?></span>
			<?php else : ?>
				<span class="screen-reader-text"><?php echo esc_html( $simple_bangla_item['label'] ); ?></span>
			<?php endif; ?>
		</a>
	<?php endforeach; ?>
</nav>
