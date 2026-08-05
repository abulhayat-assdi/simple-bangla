<?php
/**
 * The desktop navigation row.
 *
 * Hidden below 1024px, where template-parts/header/drawer.php takes over.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>
<nav class="sb-nav" aria-label="<?php esc_attr_e( 'Primary', 'simple-bangla' ); ?>">
	<div class="sb-container sb-nav__inner">

		<?php simple_bangla_primary_menu(); ?>

		<?php
		$simple_bangla_phone = simple_bangla_get_contact( 'phone' );

		if ( $simple_bangla_phone ) :
			?>
			<a class="sb-nav__hotline" href="<?php echo esc_url( 'tel:' . simple_bangla_tel_href( $simple_bangla_phone ) ); ?>">
				<?php simple_bangla_icon( 'phone', 18 ); ?>
				<span><?php echo esc_html( $simple_bangla_phone ); ?></span>
			</a>
		<?php endif; ?>

	</div>
</nav>
