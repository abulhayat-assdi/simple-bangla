<?php
/**
 * Footer brand cell: logo, phone, email, then the row of contact circles.
 *
 * Everything stacks in the first grid column — the reference keeps the whole contact block
 * under the logo on the left, not split across the row.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_phone   = simple_bangla_get_contact( 'phone' );
// The dialable form is what decides whether the row appears; the display string is only printed.
$simple_bangla_tel     = simple_bangla_tel_href( $simple_bangla_phone );
$simple_bangla_email   = simple_bangla_get_contact( 'email' );
$simple_bangla_address = simple_bangla_get_contact( 'address' );
$simple_bangla_socials = simple_bangla_social_links();
?>
<div class="sb-footer__brand">

	<?php
	if ( has_custom_logo() ) {
		the_custom_logo();
	} else {
		printf(
			'<p class="sb-footer__site-name"><a href="%s" rel="home">%s</a></p>',
			esc_url( home_url( '/' ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
	}
	?>

	<?php if ( $simple_bangla_tel ) : ?>
		<a class="sb-footer__contact-item" href="<?php echo esc_url( 'tel:' . $simple_bangla_tel ); ?>">
			<?php simple_bangla_icon( 'phone', 20 ); ?>
			<span><?php echo esc_html( $simple_bangla_phone ); ?></span>
		</a>
	<?php endif; ?>

	<?php if ( $simple_bangla_email && is_email( $simple_bangla_email ) ) : ?>
		<a class="sb-footer__contact-item" href="<?php echo esc_url( 'mailto:' . $simple_bangla_email ); ?>">
			<?php simple_bangla_icon( 'mail', 20 ); ?>
			<span><?php echo esc_html( $simple_bangla_email ); ?></span>
		</a>
	<?php endif; ?>

	<?php if ( $simple_bangla_address ) : ?>
		<p class="sb-footer__address">
			<?php simple_bangla_icon( 'pin', 20 ); ?>
			<span><?php echo esc_html( $simple_bangla_address ); ?></span>
		</p>
	<?php endif; ?>

	<?php if ( $simple_bangla_socials ) : ?>
		<ul class="sb-footer__social">
			<?php foreach ( $simple_bangla_socials as $simple_bangla_social ) : ?>
				<li>
					<a
						href="<?php echo esc_url( $simple_bangla_social['url'] ); ?>"
						<?php
						// mailto: and tel: are handed to the device, not opened in a tab — a
						// target="_blank" on either leaves an empty window behind on mobile.
						$simple_bangla_handoff = preg_match( '/^(mailto|tel):/', $simple_bangla_social['url'] );
						echo $simple_bangla_handoff ? '' : 'target="_blank" rel="noopener"';
						?>
					>
						<?php simple_bangla_icon( $simple_bangla_social['key'], 20 ); ?>
						<span class="screen-reader-text"><?php echo esc_html( $simple_bangla_social['label'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

</div>
