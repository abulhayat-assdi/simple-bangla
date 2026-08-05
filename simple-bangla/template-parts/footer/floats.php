<?php
/**
 * Floating controls: WhatsApp and back to top.
 *
 * The scroll button is rendered hidden and revealed by assets/js/ui.js, so it never sits there
 * as a dead control when scripting is unavailable.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

$simple_bangla_whatsapp = simple_bangla_whatsapp_url( __( 'Hello! I have a question about your products.', 'simple-bangla' ) );
?>
<div class="sb-floats">

	<?php if ( $simple_bangla_whatsapp ) : ?>
		<a class="sb-float sb-float--whatsapp" href="<?php echo esc_url( $simple_bangla_whatsapp ); ?>" target="_blank" rel="noopener">
			<?php simple_bangla_icon( 'whatsapp', 26 ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Chat on WhatsApp', 'simple-bangla' ); ?></span>
		</a>
	<?php endif; ?>

	<button type="button" class="sb-float sb-float--top" data-sb-to-top hidden>
		<?php simple_bangla_icon( 'arrow-up', 22 ); ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Back to top', 'simple-bangla' ); ?></span>
	</button>

</div>
