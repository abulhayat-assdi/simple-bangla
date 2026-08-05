<?php
/**
 * Primary navigation: the three-level mega menu walker, plus the per-item icon field.
 *
 * The reference site gives most top-level items a small square icon and nests categories
 * three deep. Both are reproduced here without a plugin: the icon is an attachment ID stored
 * in menu item meta, and the walker decides on its own whether a branch renders as a plain
 * dropdown or as a multi-column mega panel.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** Meta key holding the attachment ID of a menu item's icon. */
const SIMPLE_BANGLA_MENU_ICON_KEY = '_simple_bangla_menu_icon';

/**
 * Three-level navigation walker.
 *
 * Two things it does that Walker_Nav_Menu does not:
 *
 * 1. Marks a top-level branch as "mega" when it has grandchildren, so CSS can lay that one
 *    panel out in columns while a shallow branch stays a simple dropdown.
 * 2. Emits a real <button> next to any parent link. Hover alone is unusable on touch and
 *    unreachable by keyboard, so the button is the actual mechanism and hover is the
 *    pointer-only shortcut layered on top of it.
 */
class Simple_Bangla_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Whether the branch currently being written out is a mega panel.
	 *
	 * start_lvl() is called without a reference to the parent item, so start_el() records
	 * the answer here for the <ul> that immediately follows it.
	 *
	 * @var bool
	 */
	protected $current_is_mega = false;

	/**
	 * Note what each element's subtree looks like before it is rendered.
	 *
	 * This is the only point in the walk where the children of the children are visible,
	 * which is what the mega-vs-dropdown decision needs.
	 *
	 * @param object $element            Menu item.
	 * @param array  $children_elements  All remaining items, keyed by parent ID.
	 * @param int    $max_depth          Maximum depth.
	 * @param int    $depth              Current depth.
	 * @param array  $args               wp_nav_menu arguments.
	 * @param string $output             Accumulated markup.
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {

		if ( ! $element ) {
			return;
		}

		$element->sb_has_children = ! empty( $children_elements[ $element->ID ] );
		$element->sb_is_mega      = false;

		if ( 0 === $depth && $element->sb_has_children ) {
			foreach ( $children_elements[ $element->ID ] as $child ) {
				if ( ! empty( $children_elements[ $child->ID ] ) ) {
					$element->sb_is_mega = true;
					break;
				}
			}
		}

		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}

	/**
	 * Open a sub-level.
	 *
	 * @param string   $output Accumulated markup.
	 * @param int      $depth  Depth of the level being opened.
	 * @param stdClass $args   wp_nav_menu arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {

		$classes = array( 'sb-nav__sub', 'sb-nav__sub--depth-' . ( $depth + 1 ) );

		if ( 0 === $depth && $this->current_is_mega ) {
			$classes[] = 'sb-nav__sub--mega';
		}

		$output .= sprintf( '<ul class="%s">', esc_attr( implode( ' ', $classes ) ) );
	}

	/**
	 * Close a sub-level.
	 *
	 * @param string   $output Accumulated markup.
	 * @param int      $depth  Depth of the level being closed.
	 * @param stdClass $args   wp_nav_menu arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}

	/**
	 * Render one menu item.
	 *
	 * @param string   $output            Accumulated markup.
	 * @param WP_Post  $item              Menu item.
	 * @param int      $depth             Current depth.
	 * @param stdClass $args              wp_nav_menu arguments.
	 * @param int      $current_object_id Current object ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $current_object_id = 0 ) {

		$has_children          = ! empty( $item->sb_has_children );
		$is_mega               = ! empty( $item->sb_is_mega );
		$this->current_is_mega = $is_mega;

		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'sb-nav__item';
		$classes[] = 'sb-nav__item--depth-' . $depth;

		if ( $has_children ) {
			$classes[] = 'sb-nav__item--has-children';
		}

		if ( $is_mega ) {
			$classes[] = 'sb-nav__item--mega';
		}

		/** This filter is documented in wp-includes/class-walker-nav-menu.php */
		$classes = apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth );

		$output .= sprintf( '<li class="%s">', esc_attr( implode( ' ', $classes ) ) );

		$attributes = array(
			'href'   => ! empty( $item->url ) ? $item->url : '',
			'title'  => ! empty( $item->attr_title ) ? $item->attr_title : '',
			'target' => ! empty( $item->target ) ? $item->target : '',
			'rel'    => ! empty( $item->xfn ) ? $item->xfn : '',
		);

		// A link that opens a new tab and can reach an attacker-controlled page needs this.
		if ( '_blank' === $attributes['target'] && empty( $attributes['rel'] ) ) {
			$attributes['rel'] = 'noopener';
		}

		$attribute_string = '';

		foreach ( $attributes as $name => $value ) {
			if ( '' === $value ) {
				continue;
			}
			$value             = ( 'href' === $name ) ? esc_url( $value ) : esc_attr( $value );
			$attribute_string .= sprintf( ' %s="%s"', esc_attr( $name ), $value );
		}

		$icon  = simple_bangla_menu_icon_markup( $item->ID, $depth );
		$title = apply_filters( 'the_title', $item->title, $item->ID );

		/** This filter is documented in wp-includes/class-walker-nav-menu.php */
		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

		$output .= sprintf(
			'<a class="sb-nav__link"%1$s>%2$s<span class="sb-nav__label">%3$s</span></a>',
			$attribute_string,
			$icon, // Already escaped by simple_bangla_menu_icon_markup().
			esc_html( $title )
		);

		if ( $has_children ) {
			$output .= sprintf(
				'<button type="button" class="sb-nav__toggle" aria-expanded="false"><span class="screen-reader-text">%s</span><svg class="sb-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg></button>',
				sprintf(
					/* translators: %s: the parent menu item's title, e.g. "Gadgets". */
					esc_html__( 'Show submenu for %s', 'simple-bangla' ),
					esc_html( $title )
				)
			);
		}
	}

	/**
	 * Close one menu item.
	 *
	 * @param string   $output Accumulated markup.
	 * @param WP_Post  $item   Menu item.
	 * @param int      $depth  Current depth.
	 * @param stdClass $args   wp_nav_menu arguments.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}

/**
 * Build the <img> for a menu item's icon, or an empty string when it has none.
 *
 * Icons are optional per item by design — the reference site proves not every entry has one,
 * so the walker has to render cleanly either way.
 *
 * @param int $item_id Menu item ID.
 * @param int $depth   Depth, which decides the rendered size.
 * @return string Escaped markup, or ''.
 */
function simple_bangla_menu_icon_markup( $item_id, $depth = 0 ) {

	$attachment_id = (int) get_post_meta( $item_id, SIMPLE_BANGLA_MENU_ICON_KEY, true );

	if ( ! $attachment_id ) {
		return '';
	}

	$size = ( 0 === $depth ) ? 24 : 20;

	$markup = wp_get_attachment_image(
		$attachment_id,
		array( $size, $size ),
		false,
		array(
			'class'   => 'sb-nav__icon',
			// Decorative: the label right beside it already names the destination.
			'alt'     => '',
			'loading' => 'lazy',
		)
	);

	return $markup ? $markup : '';
}

/**
 * Render the primary menu, or a sensible stand-in when none has been assigned.
 *
 * A brand-new install has no menu at all. Rather than show an empty bar, fall back to the
 * store's own top-level product categories so the navigation is useful from the first page load.
 */
function simple_bangla_primary_menu() {

	if ( has_nav_menu( 'primary' ) ) {

		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'sb-nav__list',
				'depth'          => 3,
				'walker'         => new Simple_Bangla_Nav_Walker(),
			)
		);

		return;
	}

	simple_bangla_primary_menu_fallback();
}

/**
 * Stand-in navigation built from WooCommerce product categories.
 */
function simple_bangla_primary_menu_fallback() {

	$items = array();

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$items[] = array(
			'url'   => wc_get_page_permalink( 'shop' ),
			'label' => __( 'Shop', 'simple-bangla' ),
		);
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
			'number'     => 8,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$items[] = array(
				'url'   => get_term_link( $term ),
				'label' => $term->name,
			);
		}
	}

	if ( empty( $items ) ) {
		return;
	}

	echo '<ul class="sb-nav__list">';

	foreach ( $items as $item ) {

		if ( is_wp_error( $item['url'] ) ) {
			continue;
		}

		printf(
			'<li class="sb-nav__item sb-nav__item--depth-0"><a class="sb-nav__link" href="%s"><span class="sb-nav__label">%s</span></a></li>',
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
	}

	echo '</ul>';
}

/* ------------------------------------------------------------------ *
 * Admin: the per-item icon field on Appearance → Menus
 * ------------------------------------------------------------------ */

/**
 * Add the icon picker to each menu item in the admin.
 *
 * @param int      $item_id Menu item ID.
 * @param WP_Post  $item    Menu item.
 * @param int      $depth   Depth.
 * @param stdClass $args    Menu arguments.
 */
function simple_bangla_menu_icon_field( $item_id, $item = null, $depth = 0, $args = null ) {

	$attachment_id = (int) get_post_meta( $item_id, SIMPLE_BANGLA_MENU_ICON_KEY, true );
	$preview       = $attachment_id ? wp_get_attachment_image( $attachment_id, array( 40, 40 ) ) : '';
	?>
	<p class="field-sb-icon description description-wide sb-menu-icon" data-item-id="<?php echo esc_attr( $item_id ); ?>">
		<label><?php esc_html_e( 'Menu icon (optional)', 'simple-bangla' ); ?></label><br />
		<span class="sb-menu-icon__preview"><?php echo wp_kses_post( $preview ); ?></span>
		<input
			type="hidden"
			class="sb-menu-icon__value"
			name="simple_bangla_menu_icon[<?php echo esc_attr( $item_id ); ?>]"
			value="<?php echo esc_attr( (string) $attachment_id ); ?>"
		/>
		<button type="button" class="button sb-menu-icon__select"><?php esc_html_e( 'Choose image', 'simple-bangla' ); ?></button>
		<button type="button" class="button-link sb-menu-icon__remove"<?php echo $attachment_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'simple-bangla' ); ?></button>
	</p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'simple_bangla_menu_icon_field', 10, 4 );

/**
 * Persist the icon choice when a menu is saved.
 *
 * @param int $menu_id         Menu ID.
 * @param int $menu_item_db_id Menu item ID.
 */
function simple_bangla_save_menu_icon( $menu_id, $menu_item_db_id ) {

	// Nonce and capability are both already enforced by wp-admin/nav-menus.php before this
	// hook fires; re-checking the nonce here would read a field this callback does not own.
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( ! isset( $_POST['simple_bangla_menu_icon'][ $menu_item_db_id ] ) ) {
		return;
	}

	$attachment_id = absint( wp_unslash( $_POST['simple_bangla_menu_icon'][ $menu_item_db_id ] ) );

	if ( $attachment_id && 'attachment' === get_post_type( $attachment_id ) ) {
		update_post_meta( $menu_item_db_id, SIMPLE_BANGLA_MENU_ICON_KEY, $attachment_id );
		return;
	}

	delete_post_meta( $menu_item_db_id, SIMPLE_BANGLA_MENU_ICON_KEY );
}
add_action( 'wp_update_nav_menu_item', 'simple_bangla_save_menu_icon', 10, 2 );

/**
 * Load the media-picker glue on the menus screen only.
 *
 * @param string $hook Current admin page.
 */
function simple_bangla_menu_icon_assets( $hook ) {

	if ( 'nav-menus.php' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_script(
		'simple-bangla-menu-icon',
		SIMPLE_BANGLA_URI . 'assets/js/admin-menu-icon.js',
		array(),
		simple_bangla_asset_version( 'assets/js/admin-menu-icon.js' ),
		true
	);

	wp_localize_script(
		'simple-bangla-menu-icon',
		'simpleBanglaMenuIcon',
		array(
			'title'  => __( 'Choose a menu icon', 'simple-bangla' ),
			'button' => __( 'Use this icon', 'simple-bangla' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'simple_bangla_menu_icon_assets' );
