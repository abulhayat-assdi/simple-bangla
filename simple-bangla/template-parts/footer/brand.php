<?php
/**
 * Footer brand row: logo, address, contact routes and social profiles.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_phone   = simple_bangla_get_contact( 'phone' );
$simple_bangla_email   = simple_bangla_get_contact( 'email' );
$simple_bangla_address = simple_bangla_get_contact( 'address' );
$simple_bangla_socials = simple_bangla_social_links();
?>
<div class="sb-container sb-footer__brand-row">

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

		$simple_bangla_tagline = get_bloginfo( 'description' );

		if ( $simple_bangla_tagline ) {
			printf( '<p class="sb-footer__tagline">%s</p>', esc_html( $simple_bangla_tagline ) );
		}

		if ( $simple_bangla_address ) {
			printf(
				'<p class="sb-footer__address">%s%s</p>',
				simple_bangla_get_icon( 'pin', 18 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-authored SVG.
				esc_html( $simple_bangla_address )
			);
		}
		?>
	</div>

	<div class="sb-footer__contact">
		<?php if ( $simple_bangla_phone ) : ?>
			<a class="sb-footer__contact-item" href="<?php echo esc_url( 'tel:' . simple_bangla_tel_href( $simple_bangla_phone ) ); ?>">
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

		<?php if ( $simple_bangla_socials ) : ?>
			<ul class="sb-footer__social">
				<?php foreach ( $simple_bangla_socials as $simple_bangla_social ) : ?>
					<li>
						<a href="<?php echo esc_url( $simple_bangla_social['url'] ); ?>" target="_blank" rel="noopener">
							<?php simple_bangla_icon( $simple_bangla_social['key'], 20 ); ?>
							<span class="screen-reader-text"><?php echo esc_html( $simple_bangla_social['label'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

</div>
