<?php
/**
 * Order received — the thank-you page.
 *
 * Rebuilt to the owner's reference screenshot (2026-08-07): a banner that confirms the order,
 * then three cards — who the parcel goes to, what is in it, and what will be paid — instead of
 * WooCommerce's stock tables. Those tables are removed in inc/woocommerce.php; the
 * `woocommerce_thankyou` hooks still fire below so gateways and plugins keep their slot.
 *
 * Every fact here is read back off the saved order, never from the session, so a reload or a
 * bookmarked URL shows the same page.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Order|false $order */
?>

<div class="sb-checkout sb-thankyou">

	<div class="sb-checkout__banner sb-checkout__banner--thankyou">
		<div class="sb-container">

			<?php
			/*
			 * A failed payment has not finished the journey, so the bar stops at the order step
			 * rather than ticking "সম্পন্ন" green over a page that says the opposite.
			 */
			$simple_bangla_failed = ( $order && $order->has_status( 'failed' ) );

			get_template_part(
				'template-parts/checkout/steps',
				null,
				array( 'current' => $simple_bangla_failed ? 'review' : 'done' )
			);
			?>

			<?php if ( $simple_bangla_failed ) : ?>

				<span class="sb-thankyou__medallion sb-thankyou__medallion--failed">
					<?php simple_bangla_icon( 'close', 40 ); ?>
				</span>

				<h1 class="sb-thankyou__title"><?php esc_html_e( 'পেমেন্টটি সম্পন্ন হয়নি', 'simple-bangla' ); ?></h1>

				<p class="sb-thankyou__subtitle">
					<?php esc_html_e( 'দুঃখিত, আপনার পেমেন্টটি গ্রহণ করা যায়নি। অনুগ্রহ করে আবার চেষ্টা করুন।', 'simple-bangla' ); ?>
				</p>

			<?php else : ?>

				<span class="sb-thankyou__medallion">
					<?php simple_bangla_icon( 'check', 40 ); ?>
				</span>

				<h1 class="sb-thankyou__title"><?php esc_html_e( 'আপনার অর্ডারের জন্য ধন্যবাদ!', 'simple-bangla' ); ?></h1>

				<p class="sb-thankyou__subtitle">
					<?php esc_html_e( 'আপনার অর্ডারটি সফলভাবে সম্পন্ন হয়েছে।', 'simple-bangla' ); ?>
				</p>

			<?php endif; ?>

		</div>
	</div>

	<div class="sb-container sb-thankyou__body">

		<?php if ( ! $order ) : ?>

			<div class="sb-ty-card">
				<p class="sb-ty-lead">
					<?php echo esc_html( apply_filters( 'woocommerce_thankyou_order_received_text', __( 'আপনার অর্ডারটি গ্রহণ করা হয়েছে।', 'simple-bangla' ), null ) ); ?>
				</p>
			</div>

		<?php elseif ( $order->has_status( 'failed' ) ) : ?>

			<div class="sb-ty-card">
				<p class="sb-ty-lead">
					<?php esc_html_e( 'টাকা কেটে নেওয়া হয়ে থাকলে চিন্তার কারণ নেই — আবার চেষ্টা করার আগে আমাদের সাথে যোগাযোগ করুন।', 'simple-bangla' ); ?>
				</p>
				<p class="sb-thankyou__actions">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="sb-btn">
						<?php esc_html_e( 'আবার চেষ্টা করুন', 'simple-bangla' ); ?>
					</a>
				</p>
			</div>

		<?php else : ?>

			<?php
			$simple_bangla_items    = $order->get_items();
			$simple_bangla_currency = array( 'currency' => $order->get_currency() );
			$simple_bangla_address  = $order->get_formatted_shipping_address();

			// Shipping is turned off as a separate address at checkout, so billing is the parcel's address.
			if ( ! $simple_bangla_address ) {
				$simple_bangla_address = $order->get_formatted_billing_address();
			}
			?>

			<div class="sb-ty-card sb-ty-receipt">

				<dl class="sb-ty-receipt__grid">
					<div class="sb-ty-receipt__cell">
						<dt><?php esc_html_e( 'অর্ডার নাম্বার', 'simple-bangla' ); ?></dt>
						<dd class="sb-ty-receipt__id">#<?php echo esc_html( $order->get_order_number() ); ?></dd>
					</div>
					<div class="sb-ty-receipt__cell sb-ty-receipt__cell--end">
						<dt><?php esc_html_e( 'তারিখ', 'simple-bangla' ); ?></dt>
						<dd><?php echo esc_html( wc_format_datetime( $order->get_date_created(), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) ) ); ?></dd>
					</div>
				</dl>

				<p class="sb-ty-receipt__pill"><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></p>

			</div>

			<section class="sb-ty-card">

				<h2 class="sb-ty-card__head">
					<span class="sb-ty-card__icon"><?php simple_bangla_icon( 'user', 18 ); ?></span>
					<?php esc_html_e( 'কাস্টমারের তথ্য', 'simple-bangla' ); ?>
				</h2>

				<dl class="sb-ty-facts">

					<div class="sb-ty-fact">
						<dt><?php simple_bangla_icon( 'user', 14 ); ?><?php esc_html_e( 'পুরো নাম', 'simple-bangla' ); ?></dt>
						<dd><?php echo esc_html( trim( $order->get_formatted_billing_full_name() ) ); ?></dd>
					</div>

					<?php if ( $order->get_billing_phone() ) : ?>
						<div class="sb-ty-fact">
							<dt><?php simple_bangla_icon( 'phone', 14 ); ?><?php esc_html_e( 'মোবাইল', 'simple-bangla' ); ?></dt>
							<dd><?php echo esc_html( $order->get_billing_phone() ); ?></dd>
						</div>
					<?php endif; ?>

					<?php if ( $order->get_billing_email() ) : ?>
						<div class="sb-ty-fact">
							<dt><?php simple_bangla_icon( 'mail', 14 ); ?><?php esc_html_e( 'ইমেইল', 'simple-bangla' ); ?></dt>
							<dd><?php echo esc_html( $order->get_billing_email() ); ?></dd>
						</div>
					<?php endif; ?>

					<?php if ( $simple_bangla_address ) : ?>
						<div class="sb-ty-fact sb-ty-fact--wide">
							<dt><?php simple_bangla_icon( 'pin', 14 ); ?><?php esc_html_e( 'ডেলিভারি ঠিকানা', 'simple-bangla' ); ?></dt>
							<dd><?php echo wp_kses_post( $simple_bangla_address ); ?></dd>
						</div>
					<?php endif; ?>

					<?php if ( $order->get_customer_note() ) : ?>
						<div class="sb-ty-fact sb-ty-fact--wide">
							<dt><?php simple_bangla_icon( 'chat', 14 ); ?><?php esc_html_e( 'ডেলিভারি নোট', 'simple-bangla' ); ?></dt>
							<dd><?php echo esc_html( $order->get_customer_note() ); ?></dd>
						</div>
					<?php endif; ?>

				</dl>

			</section>

			<section class="sb-ty-card">

				<h2 class="sb-ty-card__head">
					<span class="sb-ty-card__icon"><?php simple_bangla_icon( 'box', 18 ); ?></span>
					<?php esc_html_e( 'অর্ডার করা পণ্য', 'simple-bangla' ); ?>
					<span class="sb-ty-card__count">
						<?php
						printf(
							/* translators: %s: number of products in the order. */
							esc_html( _n( '%s টি পণ্য', '%s টি পণ্য', count( $simple_bangla_items ), 'simple-bangla' ) ),
							esc_html( number_format_i18n( count( $simple_bangla_items ) ) )
						);
						?>
					</span>
				</h2>

				<ul class="sb-ty-items">
					<?php foreach ( $simple_bangla_items as $simple_bangla_item_id => $simple_bangla_item ) : ?>
						<?php
						$simple_bangla_product = $simple_bangla_item->get_product();
						$simple_bangla_link    = ( $simple_bangla_product && $simple_bangla_product->is_visible() ) ? $simple_bangla_product->get_permalink( $simple_bangla_item ) : '';
						?>
						<li class="sb-ty-item">

							<span class="sb-ty-item__thumb">
								<?php
								if ( $simple_bangla_product ) {
									echo wp_kses_post( $simple_bangla_product->get_image( 'woocommerce_thumbnail' ) );
								}
								?>
							</span>

							<span class="sb-ty-item__info">

								<span class="sb-ty-item__name">
									<?php if ( $simple_bangla_link ) : ?>
										<a href="<?php echo esc_url( $simple_bangla_link ); ?>"><?php echo esc_html( $simple_bangla_item->get_name() ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $simple_bangla_item->get_name() ); ?>
									<?php endif; ?>
								</span>

								<span class="sb-ty-item__qty">
									<?php
									printf(
										/* translators: 1: unit price, 2: quantity ordered. */
										wp_kses_post( __( '%1$s × %2$s', 'simple-bangla' ) ),
										wp_kses_post( wc_price( $order->get_item_total( $simple_bangla_item, false, true ), $simple_bangla_currency ) ),
										esc_html( number_format_i18n( $simple_bangla_item->get_quantity() ) )
									);
									?>
								</span>

								<?php
								// Variation attributes and any custom item meta, exactly as WooCommerce records them.
								wc_display_item_meta( $simple_bangla_item );
								?>

							</span>

							<span class="sb-ty-item__total">
								<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $simple_bangla_item ) ); ?>
							</span>

						</li>
					<?php endforeach; ?>
				</ul>

				<?php
				$simple_bangla_rows = array(
					array(
						'label' => __( 'সাবটোটাল', 'simple-bangla' ),
						'value' => $order->get_subtotal_to_display(),
					),
				);

				if ( $order->get_total_discount() > 0 ) {
					$simple_bangla_rows[] = array(
						'label' => __( 'ডিসকাউন্ট', 'simple-bangla' ),
						'value' => '-' . wc_price( $order->get_total_discount(), $simple_bangla_currency ),
					);
				}

				foreach ( $order->get_fees() as $simple_bangla_fee ) {
					$simple_bangla_rows[] = array(
						'label' => $simple_bangla_fee->get_name(),
						'value' => wc_price( $simple_bangla_fee->get_total(), $simple_bangla_currency ),
					);
				}

				// Shown whenever the order carries a shipping line, so free delivery still reads as a choice made.
				if ( $order->get_shipping_method() ) {
					$simple_bangla_rows[] = array(
						'label' => __( 'ডেলিভারি চার্জ', 'simple-bangla' ),
						'note'  => $order->get_shipping_method(),
						'value' => wc_price( $order->get_shipping_total(), $simple_bangla_currency ),
					);
				}

				foreach ( $order->get_tax_totals() as $simple_bangla_tax ) {
					$simple_bangla_rows[] = array(
						'label' => $simple_bangla_tax->label,
						'value' => $simple_bangla_tax->formatted_amount,
					);
				}
				?>

				<dl class="sb-ty-totals">

					<?php foreach ( $simple_bangla_rows as $simple_bangla_row ) : ?>
						<div class="sb-ty-totals__row">
							<dt>
								<?php echo esc_html( $simple_bangla_row['label'] ); ?>
								<?php if ( ! empty( $simple_bangla_row['note'] ) ) : ?>
									<small><?php echo esc_html( $simple_bangla_row['note'] ); ?></small>
								<?php endif; ?>
							</dt>
							<dd><?php echo wp_kses_post( $simple_bangla_row['value'] ); ?></dd>
						</div>
					<?php endforeach; ?>

					<div class="sb-ty-totals__row sb-ty-totals__row--grand">
						<dt><?php esc_html_e( 'সর্বমোট', 'simple-bangla' ); ?></dt>
						<dd><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></dd>
					</div>

				</dl>

			</section>

			<section class="sb-ty-card">

				<h2 class="sb-ty-card__head">
					<span class="sb-ty-card__icon"><?php simple_bangla_icon( 'card', 18 ); ?></span>
					<?php esc_html_e( 'পেমেন্টের তথ্য', 'simple-bangla' ); ?>
				</h2>

				<dl class="sb-ty-facts">
					<div class="sb-ty-fact">
						<dt><?php esc_html_e( 'পেমেন্ট মাধ্যম', 'simple-bangla' ); ?></dt>
						<dd><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></dd>
					</div>
					<div class="sb-ty-fact">
						<dt><?php esc_html_e( 'প্রদেয় পরিমাণ', 'simple-bangla' ); ?></dt>
						<dd class="sb-ty-fact__amount"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></dd>
					</div>
				</dl>

				<?php if ( 'cod' === $order->get_payment_method() ) : ?>
					<p class="sb-ty-callout">
						<?php
						printf(
							/* translators: %s: order total. */
							wp_kses_post( __( 'পণ্যটি হাতে পাওয়ার সময় %s ডেলিভারি ম্যানকে পরিশোধ করবেন। টাকাটি প্রস্তুত রাখার অনুরোধ রইল।', 'simple-bangla' ) ),
							wp_kses_post( '<strong>' . $order->get_formatted_order_total() . '</strong>' )
						);
						?>
					</p>
				<?php endif; ?>

			</section>

			<p class="sb-ty-followup">
				<?php esc_html_e( 'আমাদের একজন প্রতিনিধি শীঘ্রই আপনার নাম্বারে কল করে অর্ডারটি নিশ্চিত করবেন।', 'simple-bangla' ); ?>
			</p>

			<?php
			/*
			 * Gateways and plugins still get their slot; WooCommerce's own duplicate order tables
			 * are unhooked in inc/woocommerce.php. The output is buffered so the wrapper is only
			 * printed when something actually hooked in — an empty div would still take a row of
			 * the grid above, and with cash on delivery configured there is usually nothing here.
			 */
			ob_start();
			do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
			do_action( 'woocommerce_thankyou', $order->get_id() );
			$simple_bangla_hooked = trim( ob_get_clean() );
			?>

			<?php if ( $simple_bangla_hooked ) : ?>
				<?php
				// Printed raw: this is gateway and plugin markup, and wp_kses_post would strip the
				// forms, inputs and scripts a payment plugin legitimately renders here.
				?>
				<div class="sb-ty-hooks"><?php echo $simple_bangla_hooked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>

			<p class="sb-thankyou__actions">
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>" class="sb-btn">
					<?php esc_html_e( 'আরও কেনাকাটা করুন', 'simple-bangla' ); ?>
				</a>
			</p>

		<?php endif; ?>

	</div>
</div>
