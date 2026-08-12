<?php
/**
 * Keep the theme's stored links pointing at whatever address the site is answering on.
 *
 * WordPress rewrites nothing when a site moves domain. Anything holding a full URL keeps holding
 * the old one, and the shop's own homepage then sends customers to a site the owner no longer
 * controls. The theme stores full URLs in nine settings — five hero slide links and four banner
 * links — plus whatever custom links the owner has put in a menu, so it has to answer for them.
 *
 * The mechanism is one remembered fact: the address the site had the last time this ran. When it
 * no longer matches, every stored link on the old address is moved to the new one. Nothing is
 * checked at render time — links are corrected in place, once per move, so the homepage costs
 * exactly what it did before.
 *
 * In the theme rather than the CMS plugin, for the reason the block list and the order statuses
 * are: a broken banner link is customer-visible, and switching the management interface off must
 * not start sending shoppers to a dead domain.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** Where the last-known site address is remembered. */
const SIMPLE_BANGLA_ADDRESS_OPTION = 'simple_bangla_site_address';

/** A record of what the last move changed, kept so a surprising rewrite can be inspected. */
const SIMPLE_BANGLA_ADDRESS_LOG = 'simple_bangla_address_log';

/** Held for the duration of a move so concurrent requests do not repeat it. */
const SIMPLE_BANGLA_ADDRESS_LOCK = 'simple_bangla_address_lock';

/**
 * Every theme setting that holds a full URL to somewhere on this site.
 *
 * Deliberately not "every setting that looks like a URL". The social, WhatsApp and Messenger
 * fields are addresses on other people's sites and must never be touched by a move of ours.
 *
 * @return string[] Theme mod names.
 */
function simple_bangla_address_settings() {

	$keys = array();

	for ( $i = 1; $i <= SIMPLE_BANGLA_HERO_SLIDES; $i++ ) {
		$keys[] = 'simple_bangla_hero_' . $i . '_link';
	}

	for ( $pair = 1; $pair <= SIMPLE_BANGLA_HOME_BANNERS; $pair++ ) {
		foreach ( array( 'small', 'wide' ) as $slot ) {
			$keys[] = 'simple_bangla_home_banner_' . $pair . '_' . $slot . '_link';
		}
	}

	/**
	 * Filter the settings a change of address rewrites.
	 *
	 * @param string[] $keys Theme mod names holding a full URL to this site.
	 */
	return (array) apply_filters( 'simple_bangla_address_settings', $keys );
}

/**
 * The comparable part of a URL: host plus path, without scheme, case or trailing slash.
 *
 * Scheme is dropped on purpose. home_url() runs its result through set_url_scheme(), so the same
 * site answers `http://` on one request and `https://` on the next; comparing whole strings would
 * read that as a move and rewrite the links back and forth on every other page load.
 *
 * @param string $url Any URL.
 * @return string Empty string when the URL has no host.
 */
function simple_bangla_address_root( $url ) {

	$parts = wp_parse_url( (string) $url );

	if ( empty( $parts['host'] ) ) {
		return '';
	}

	$path = isset( $parts['path'] ) ? untrailingslashit( $parts['path'] ) : '';

	return strtolower( $parts['host'] ) . $path;
}

/**
 * Whether a URL sits on a given address.
 *
 * The comparison appends a slash to both sides before testing the prefix, or the address
 * `old.example` would also claim `old.example.attacker.test`.
 *
 * @param string $root Root of the URL under test, from simple_bangla_address_root().
 * @param string $on   Root of the address being tested against.
 * @return bool
 */
function simple_bangla_address_covers( $on, $root ) {

	if ( '' === $on || '' === $root ) {
		return false;
	}

	return $root === $on || 0 === strpos( $root . '/', $on . '/' );
}

/**
 * Move one URL from an old address onto the site's current one, keeping everything below the root.
 *
 * @param string $url  The stored URL.
 * @param string $from Root of the address being left, as simple_bangla_address_root() returns it.
 * @return string The URL unchanged when it does not sit on that address.
 */
function simple_bangla_move_url( $url, $from ) {

	$url  = (string) $url;
	$root = simple_bangla_address_root( $url );

	if ( '' === $root || ! simple_bangla_address_covers( $from, $root ) ) {
		return $url;
	}

	$parts = wp_parse_url( $url );
	$path  = isset( $parts['path'] ) ? $parts['path'] : '';

	// Everything the old address had below its own root — the part that has to survive the move.
	$rest = ltrim( (string) substr( untrailingslashit( $path ), strlen( $from ) - strlen( $parts['host'] ) ), '/' );

	/*
	 * A directory-style link keeps its trailing slash and a file-style one must not gain a slash
	 * it never had: WordPress redirects between the two spellings, and a banner that costs every
	 * shopper a redirect is a slower banner for no reason.
	 */
	$slash = ( '' !== $rest && '/' === substr( $path, -1 ) ) ? '/' : '';
	$moved = home_url( '/' . $rest . $slash );

	if ( ! empty( $parts['query'] ) ) {
		$moved .= '?' . $parts['query'];
	}

	if ( ! empty( $parts['fragment'] ) ) {
		$moved .= '#' . $parts['fragment'];
	}

	return $moved;
}

/**
 * Whether this site would answer a path with real content.
 *
 * Only used on the first run, where nothing was remembered and a link on a foreign host is
 * therefore ambiguous: it is either left over from a move nobody recorded, or a deliberate link to
 * somebody else's site. The question that separates the two is whether this site can answer it.
 * `/shop/` and `/product-category/microphone/` can; `/simplebangla` on Facebook cannot.
 *
 * Asked with slug lookups rather than url_to_postid(), which is the obvious tool and does not work
 * here: measured against WooCommerce 11 with pretty permalinks and 139 rewrite rules registered, it
 * answers 0 for the shop page — the single most likely destination of a banner on this site. A
 * resolver that says no is not a safe failure, because a no leaves the stale link in place, which
 * is the bug this file exists to fix.
 *
 * @param string $path Path from the stored URL.
 * @return bool
 */
function simple_bangla_address_resolves( $path ) {

	$path = trim( (string) $path, '/' );

	// The old home page itself. Nothing to look up — this site certainly has one.
	if ( '' === $path ) {
		return true;
	}

	$types = array( 'page', 'post', 'product' );

	// The whole path, which is what a page — nested or not — is addressed by.
	if ( get_page_by_path( $path, OBJECT, $types ) ) {
		return true;
	}

	$slug = basename( $path );

	/*
	 * A banner most often points at a category, which is a term archive rather than a post, so the
	 * last segment is checked against the taxonomies the theme links to.
	 */
	foreach ( array( 'product_cat', 'product_tag', 'category' ) as $taxonomy ) {

		if ( taxonomy_exists( $taxonomy ) && get_term_by( 'slug', $slug, $taxonomy ) ) {
			return true;
		}
	}

	/*
	 * The last segment as a post name, which is how a product is addressed: its permalink carries
	 * a `/product/` prefix that the path lookup above will never match.
	 */
	$found = get_posts(
		array(
			'name'                   => $slug,
			'post_type'              => $types,
			'post_status'            => 'publish',
			'numberposts'            => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return ! empty( $found );
}

/**
 * Decide what one stored link becomes, by whichever of the two rules applies.
 *
 * @param string $stored The stored URL.
 * @param string $from   Root of the remembered previous address, '' when there is none.
 * @param string $here   Root of the current address.
 * @return string
 */
function simple_bangla_move_link( $stored, $from, $here ) {

	// The ordinary case: the site moved and we know where from, so only that address is touched.
	if ( '' !== $from ) {
		return simple_bangla_move_url( $stored, $from );
	}

	/*
	 * The first run, healing a move made before anything was remembering. A link already on this
	 * site is right as it stands, and one this site cannot answer belongs to somebody else.
	 */
	$root = simple_bangla_address_root( $stored );

	if ( '' === $root || simple_bangla_address_covers( $here, $root ) ) {
		return $stored;
	}

	if ( ! simple_bangla_address_resolves( (string) wp_parse_url( $stored, PHP_URL_PATH ) ) ) {
		return $stored;
	}

	/*
	 * The host alone, not the root: the root includes the link's own path, and moving a URL off
	 * its whole path would send `/shop/` to the homepage. A previous install living in a
	 * subdirectory is the one case this cannot work out on its own, and it would leave the
	 * subdirectory in the new link rather than drop a segment silently.
	 */
	return simple_bangla_move_url( $stored, strtolower( (string) wp_parse_url( $stored, PHP_URL_HOST ) ) );
}

/**
 * Every custom-link item across every menu.
 *
 * Custom links are the only menu items holding a URL. Page, category and product items store an
 * object id and their link is rebuilt from it on every render, so a move never reaches them.
 *
 * @return int[] Menu item post IDs.
 */
function simple_bangla_custom_menu_items() {

	$items = get_posts(
		array(
			'post_type'      => 'nav_menu_item',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_meta_query -- Runs once per site move.
			'meta_query'     => array(
				array(
					'key'   => '_menu_item_type',
					'value' => 'custom',
				),
			),
		)
	);

	return array_map( 'intval', (array) $items );
}

/**
 * Rewrite the theme's stored links and the menu's custom links onto the current address.
 *
 * @param string $from Root of the address being left, or '' on a first run with nothing remembered.
 * @return array<int,array{what:string,from:string,to:string}> What changed.
 */
function simple_bangla_move_stored_links( $from ) {

	$here    = simple_bangla_address_root( home_url() );
	$changed = array();

	foreach ( simple_bangla_address_settings() as $key ) {

		$stored = (string) get_theme_mod( $key, '' );

		if ( '' === $stored ) {
			continue;
		}

		$moved = simple_bangla_move_link( $stored, $from, $here );

		if ( $moved === $stored ) {
			continue;
		}

		set_theme_mod( $key, $moved );

		$changed[] = array(
			'what' => $key,
			'from' => $stored,
			'to'   => $moved,
		);
	}

	foreach ( simple_bangla_custom_menu_items() as $item_id ) {

		$stored = (string) get_post_meta( $item_id, '_menu_item_url', true );

		if ( '' === $stored ) {
			continue;
		}

		$moved = simple_bangla_move_link( $stored, $from, $here );

		if ( $moved === $stored ) {
			continue;
		}

		update_post_meta( $item_id, '_menu_item_url', $moved );

		$changed[] = array(
			'what' => 'menu item ' . $item_id,
			'from' => $stored,
			'to'   => $moved,
		);
	}

	return $changed;
}

/**
 * Take the one lock that lets a request perform the move.
 *
 * add_option() is the claim rather than a read followed by a write: it fails when the row already
 * exists, and that failure is atomic in a way "look, find nothing, write" is not. A move on a busy
 * shop is several requests arriving together, each seeing the same stale address.
 *
 * @return bool
 */
function simple_bangla_claim_address_move() {

	if ( add_option( SIMPLE_BANGLA_ADDRESS_LOCK, time(), '', false ) ) {
		return true;
	}

	// A lock left behind by a request that died mid-move must not block the site forever.
	if ( (int) get_option( SIMPLE_BANGLA_ADDRESS_LOCK, 0 ) > time() - 5 * MINUTE_IN_SECONDS ) {
		return false;
	}

	update_option( SIMPLE_BANGLA_ADDRESS_LOCK, time(), false );

	return true;
}

/**
 * Notice a change of address and move the stored links to follow it.
 *
 * Runs on every request and does nothing beyond reading one autoloaded option until the site
 * actually moves.
 */
function simple_bangla_watch_site_address() {

	$here  = simple_bangla_address_root( home_url() );
	$known = (string) get_option( SIMPLE_BANGLA_ADDRESS_OPTION, '' );

	if ( '' === $here || $known === $here ) {
		return;
	}

	if ( ! simple_bangla_claim_address_move() ) {
		return;
	}

	$changed = simple_bangla_move_stored_links( $known );

	// Written before the lock is dropped, so a request arriving in between reads the new address
	// and returns above rather than repeating the work.
	update_option( SIMPLE_BANGLA_ADDRESS_OPTION, $here, true );

	if ( $changed ) {
		update_option(
			SIMPLE_BANGLA_ADDRESS_LOG,
			array(
				'at'      => current_time( 'mysql' ),
				'from'    => '' === $known ? '(not recorded)' : $known,
				'to'      => $here,
				'changed' => $changed,
			),
			false
		);
	}

	delete_option( SIMPLE_BANGLA_ADDRESS_LOCK );
}
/*
 * On wp_loaded rather than init, and the difference matters. The first-run repair asks
 * url_to_postid() and taxonomy_exists() whether this site can answer a path, and WooCommerce
 * registers `product` and `product_cat` on init at priority 5 — the same priority this would
 * naturally want. It would happen to run second today, because plugins add their hooks before a
 * theme does, but a repair whose correctness rests on hook registration order is one plugin away
 * from silently deciding the shop has no categories and leaving every stale link in place.
 */
add_action( 'wp_loaded', 'simple_bangla_watch_site_address' );
