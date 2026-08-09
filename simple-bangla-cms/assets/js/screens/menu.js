/*
 * The navigation menu.
 *
 * WordPress has exposed menus and menu items over REST since 5.9, so this screen is built on
 * `wp/v2/menus` and `wp/v2/menu-items` rather than on anything invented here. The plugin adds one
 * thing: the theme's per-item icon, registered for REST in inc/menu-icon.php.
 *
 * Reordering is buttons, not drag-and-drop. A three-level tree that has to work under a thumb on a
 * phone is where drag-and-drop stops being the obvious choice — and "move up" is unambiguous in a
 * way that dropping between two nested rows is not. Depth is changed by choosing a parent, which
 * is also how an item gets promoted to the top level.
 *
 * Deleting takes the item's children with it, and says so. WordPress does not re-parent them; they
 * would keep pointing at an id that no longer exists and simply vanish from the menu with no way
 * to find them again.
 */

import {
	html,
	useState,
	useEffect,
	Card,
	Field,
	Select,
	Badge,
	Modal,
	Confirm,
	EmptyState,
	ErrorBox,
	Icon,
	toast,
} from '../ui.js';
import { api, apiList } from '../api.js';
import { MediaField } from '../media.js';

/** Mirrors SIMPLE_BANGLA_MENU_ICON_KEY in the theme's inc/nav-walker.php. */
const ICON_META = '_simple_bangla_menu_icon';

/** The theme nests three deep; deeper items would render but never be reachable. */
const MAX_DEPTH = 2;

export function MenuScreen() {
	const [ locations, setLocations ] = useState( null );
	const [ menus, setMenus ] = useState( [] );
	const [ menuId, setMenuId ] = useState( 0 );
	const [ items, setItems ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ busy, setBusy ] = useState( false );

	const [ editing, setEditing ] = useState( null );
	const [ deleting, setDeleting ] = useState( null );

	const loadMenus = () => {
		setError( null );

		Promise.all( [
			api( '/menu-locations' ),
			apiList( 'wp/v2/menus', { params: { per_page: 100 } } ),
		] )
			.then( ( [ locs, menuList ] ) => {
				setLocations( locs );
				setMenus( menuList.items );

				// Open on whatever the storefront is actually showing.
				const primary = locs.find( ( l ) => l.location === 'primary' && l.menu_id );
				const first = locs.find( ( l ) => l.menu_id );

				setMenuId( ( current ) =>
					current || ( primary ? primary.menu_id : first ? first.menu_id : menuList.items[ 0 ]?.id || 0 )
				);
			} )
			.catch( setError );
	};

	useEffect( loadMenus, [] );

	const loadItems = () => {
		if ( ! menuId ) {
			/*
			 * No menu chosen yet means one of two very different things, and they must not look
			 * alike. Until the menu list arrives there is nothing to say — keep the spinner (null).
			 * Once it has arrived and is empty, the site genuinely has no menus and the empty state
			 * is the truth. An earlier version set [] in both cases, so every visit to this screen
			 * flashed "This menu is empty" over a menu that was about to load.
			 */
			setItems( menus.length ? null : [] );
			return;
		}

		setItems( null );

		apiList( 'wp/v2/menu-items', {
			params: { menus: menuId, per_page: 100, orderby: 'menu_order', order: 'asc', status: 'publish' },
		} )
			.then( ( result ) => setItems( result.items ) )
			.catch( setError );
	};

	useEffect( loadItems, [ menuId, menus.length ] );

	const rows = flatten( items || [] );

	/* -- writes -- */

	const patch = async ( id, body ) => {
		setBusy( true );

		try {
			const saved = await api( 'wp/v2/menu-items/' + id, { method: 'POST', body } );
			setItems( ( current ) => current.map( ( i ) => ( i.id === id ? saved : i ) ) );
			return saved;
		} catch ( e ) {
			toast( e.message, 'bad' );
			return null;
		} finally {
			setBusy( false );
		}
	};

	const move = async ( row, delta ) => {
		const siblings = rows.filter( ( r ) => r.item.parent === row.item.parent ).map( ( r ) => r.item );
		const index = siblings.findIndex( ( i ) => i.id === row.item.id );
		const target = siblings[ index + delta ];

		if ( ! target ) {
			return;
		}

		setBusy( true );

		try {
			// Swap the two positions. Menu order is a single sequence across the whole menu, so
			// exchanging the pair moves one item without disturbing anything else in it.
			const a = row.item.menu_order;
			const b = target.menu_order;

			const [ first, second ] = await Promise.all( [
				api( 'wp/v2/menu-items/' + row.item.id, { method: 'POST', body: { menu_order: b } } ),
				api( 'wp/v2/menu-items/' + target.id, { method: 'POST', body: { menu_order: a } } ),
			] );

			setItems( ( current ) =>
				current.map( ( i ) => ( i.id === first.id ? first : i.id === second.id ? second : i ) )
			);
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	const save = async ( draft ) => {
		setBusy( true );

		try {
			const body = {
				title: draft.title,
				menus: menuId,
				parent: Number( draft.parent ) || 0,
				status: 'publish',
				meta: { [ ICON_META ]: Number( draft.icon ) || 0 },
			};

			if ( draft.kind === 'taxonomy' ) {
				body.type = 'taxonomy';
				body.object = 'product_cat';
				body.object_id = Number( draft.object_id );
			} else {
				body.type = 'custom';
				body.url = draft.url;
			}

			if ( draft.id ) {
				const saved = await api( 'wp/v2/menu-items/' + draft.id, { method: 'POST', body } );
				setItems( ( current ) => current.map( ( i ) => ( i.id === draft.id ? saved : i ) ) );
			} else {
				// New items go to the end of their level.
				body.menu_order = ( items || [] ).reduce( ( max, i ) => Math.max( max, i.menu_order || 0 ), 0 ) + 1;

				const created = await api( 'wp/v2/menu-items', { method: 'POST', body } );
				setItems( ( current ) => [ ...current, created ] );
			}

			toast( draft.id ? 'Menu item saved' : 'Menu item added' );
			setEditing( null );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	const remove = async () => {
		const doomed = withDescendants( items || [], deleting.id );

		setBusy( true );

		try {
			// Deepest first, so a parent is never removed while something still points at it.
			for ( const id of doomed.reverse() ) {
				await api( 'wp/v2/menu-items/' + id + '?force=true', { method: 'DELETE' } );
			}

			setItems( ( current ) => current.filter( ( i ) => ! doomed.includes( i.id ) ) );
			toast( doomed.length > 1 ? doomed.length + ' items removed' : 'Menu item removed' );
			setDeleting( null );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	/* -- render -- */

	if ( error ) {
		return html`<div class="sb-page">
			<h1 class="sb-page__title">Menu</h1>
			<${ ErrorBox } error=${ error } onRetry=${ loadMenus } />
		</div>`;
	}

	if ( ! locations ) {
		return html`<div class="sb-page">
			<h1 class="sb-page__title">Menu</h1>
			<div class="sb-media-loading"><span class="sb-spinner"></span></div>
		</div>`;
	}

	const primary = locations.find( ( l ) => l.location === 'primary' );
	const unassigned = primary && ! primary.menu_id;

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Menu</h1>
					<p class="sb-page__lead">The navigation across the top of the shop.</p>
				</div>
				<div class="sb-row sb-page__actions">
					<button
						class="sb-btn sb-btn--primary"
						disabled=${ ! menuId }
						onClick=${ () => setEditing( blank() ) }
					>
						Add item
					</button>
				</div>
			</div>

			${ unassigned
				? html`<p class="sb-alert sb-alert--warn">
						No menu is assigned to the theme's primary location, so the shop is showing no
						navigation. Assign one under Appearance → Menus in WordPress.
				  </p>`
				: null }

			${ menus.length > 1
				? html`<div class="sb-toolbar">
						<${ Select }
							value=${ menuId }
							options=${ menus.map( ( m ) => ( {
								value: m.id,
								label: m.name + ( locationOf( locations, m.id ) ? ' — ' + locationOf( locations, m.id ) : '' ),
							} ) ) }
							onChange=${ ( value ) => setMenuId( Number( value ) ) }
						/>
				  </div>`
				: null }

			<${ Card }>
				${ ! items
					? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
					: ! rows.length
					? html`<${ EmptyState }
							title="This menu is empty"
							body="Add a link to a category or any page on the site."
					  />`
					: html`
							<ul class=${ 'sb-menutree' + ( busy ? ' is-busy' : '' ) }>
								${ rows.map( ( row, index ) => {
									const siblings = rows.filter( ( r ) => r.item.parent === row.item.parent );
									const at = siblings.findIndex( ( r ) => r.item.id === row.item.id );

									return html`
										<li
											class=${ 'sb-menurow sb-menurow--d' + row.depth }
											key=${ row.item.id }
										>
											<span class="sb-menurow__icon">
												${ iconOf( row.item )
													? html`<${ MenuIconThumb } id=${ iconOf( row.item ) } />`
													: null }
											</span>

											<span class="sb-menurow__main">
												<button
													class="sb-linkbtn sb-menurow__title"
													onClick=${ () => setEditing( toDraft( row.item ) ) }
												>
													${ titleOf( row.item ) || 'Untitled' }
												</button>
												<span class="sb-menurow__url">
													${ row.item.type === 'taxonomy'
														? html`<${ Badge }>Category<//> `
														: null }
													${ row.item.url }
												</span>
											</span>

											<span class="sb-menurow__tools">
												<button
													class="sb-icon-btn"
													title="Move up"
													disabled=${ busy || at === 0 }
													onClick=${ () => move( row, -1 ) }
												>
													↑
												</button>
												<button
													class="sb-icon-btn"
													title="Move down"
													disabled=${ busy || at === siblings.length - 1 }
													onClick=${ () => move( row, 1 ) }
												>
													↓
												</button>
												<button
													class="sb-icon-btn"
													title="Remove"
													disabled=${ busy }
													onClick=${ () => setDeleting( row.item ) }
												>
													×
												</button>
											</span>
										</li>
									`;
								} ) }
							</ul>
					  ` }
			<//>

			${ editing
				? html`<${ ItemEditor }
						draft=${ editing }
						items=${ items || [] }
						busy=${ busy }
						onSave=${ save }
						onClose=${ () => setEditing( null ) }
				  />`
				: null }

			${ deleting
				? html`<${ Confirm }
						title=${ 'Remove "' + ( titleOf( deleting ) || 'this item' ) + '"?' }
						body=${ describeRemoval( items || [], deleting ) }
						confirmLabel="Remove"
						busy=${ busy }
						onConfirm=${ remove }
						onClose=${ () => setDeleting( null ) }
				  />`
				: null }
		</div>
	`;
}

/* ------------------------------------------------------------------ the tree */

/** Menu items in display order, each with the depth it sits at. */
function flatten( items ) {
	const byParent = new Map();

	items.forEach( ( item ) => {
		const list = byParent.get( item.parent ) || [];
		list.push( item );
		byParent.set( item.parent, list );
	} );

	byParent.forEach( ( list ) => list.sort( ( a, b ) => ( a.menu_order || 0 ) - ( b.menu_order || 0 ) ) );

	const out = [];

	const walk = ( parent, depth ) => {
		( byParent.get( parent ) || [] ).forEach( ( item ) => {
			out.push( { item, depth } );
			walk( item.id, depth + 1 );
		} );
	};

	walk( 0, 0 );

	return out;
}

/** An item and everything nested under it, parents before children. */
function withDescendants( items, id ) {
	const out = [ id ];

	items
		.filter( ( i ) => i.parent === id )
		.forEach( ( child ) => out.push( ...withDescendants( items, child.id ) ) );

	return out;
}

function describeRemoval( items, item ) {
	const count = withDescendants( items, item.id ).length - 1;

	return count
		? `This also removes the ${ count } item${ count === 1 ? '' : 's' } nested under it. WordPress does not keep them.`
		: 'It is removed from the menu. Nothing else changes.';
}

function titleOf( item ) {
	return item.title && typeof item.title === 'object' ? item.title.rendered || item.title.raw : item.title;
}

function iconOf( item ) {
	return Number( ( item.meta && item.meta[ ICON_META ] ) || 0 );
}

function locationOf( locations, menuId ) {
	const hit = locations.find( ( l ) => l.menu_id === menuId );

	return hit ? hit.label : '';
}

function blank() {
	return { id: 0, title: '', url: '', kind: 'custom', object_id: 0, parent: 0, icon: 0 };
}

function toDraft( item ) {
	return {
		id: item.id,
		title: titleOf( item ) || '',
		url: item.url || '',
		kind: item.type === 'taxonomy' ? 'taxonomy' : 'custom',
		object_id: item.object_id || 0,
		parent: item.parent || 0,
		icon: iconOf( item ),
	};
}

/* ------------------------------------------------------------------ pieces */

/** A saved icon, drawn from its attachment id. */
function MenuIconThumb( { id } ) {
	const [ src, setSrc ] = useState( '' );

	useEffect( () => {
		let cancelled = false;

		api( 'wp/v2/media/' + id )
			.then( ( item ) => {
				if ( ! cancelled ) {
					const sizes = item.media_details && item.media_details.sizes;
					setSrc( ( sizes && sizes.thumbnail ? sizes.thumbnail.source_url : item.source_url ) || '' );
				}
			} )
			.catch( () => {} );

		return () => {
			cancelled = true;
		};
	}, [ id ] );

	return src ? html`<img src=${ src } alt="" loading="lazy" />` : html`<${ Icon } name="image" size=${ 14 } />`;
}

function ItemEditor( { draft: initial, items, busy, onSave, onClose } ) {
	const [ draft, setDraft ] = useState( initial );
	const [ categories, setCategories ] = useState( [] );

	const set = ( patch ) => setDraft( ( current ) => ( { ...current, ...patch } ) );

	useEffect( () => {
		apiList( 'wc/v3/products/categories', { params: { per_page: 100, hide_empty: false } } )
			.then( ( r ) => setCategories( r.items ) )
			.catch( () => setCategories( [] ) );
	}, [] );

	// A parent has to be an item that is not this one, not one of its own descendants, and not so
	// deep that the child would fall past the third level the theme renders.
	const forbidden = draft.id ? withDescendants( items, draft.id ) : [];

	const parentOptions = [
		{ value: 0, label: 'Top level' },
		...flatten( items )
			.filter( ( r ) => ! forbidden.includes( r.item.id ) && r.depth < MAX_DEPTH )
			.map( ( r ) => ( {
				value: r.item.id,
				label: '— '.repeat( r.depth ) + ( titleOf( r.item ) || 'Untitled' ),
			} ) ),
	];

	const valid = draft.title.trim() && ( draft.kind === 'taxonomy' ? draft.object_id : draft.url.trim() );

	return html`
		<${ Modal }
			title=${ draft.id ? 'Edit menu item' : 'Add menu item' }
			onClose=${ onClose }
			footer=${ html`
				<button class="sb-btn sb-btn--ghost" onClick=${ onClose }>Cancel</button>
				<button
					class="sb-btn sb-btn--primary"
					disabled=${ busy || ! valid }
					onClick=${ () => onSave( draft ) }
				>
					${ busy ? 'Saving…' : 'Save' }
				</button>
			` }
		>
			<${ Field } label="Label" id="mi-title" hint="What shows in the menu.">
				<input
					class="sb-input"
					id="mi-title"
					value=${ draft.title }
					onInput=${ ( e ) => set( { title: e.target.value } ) }
				/>
			<//>

			<${ Field } label="Points at" id="mi-kind">
				<${ Select }
					id="mi-kind"
					value=${ draft.kind }
					options=${ [
						{ value: 'taxonomy', label: 'A product category' },
						{ value: 'custom', label: 'Any address' },
					] }
					onChange=${ ( value ) => set( { kind: value } ) }
				/>
			<//>

			${ draft.kind === 'taxonomy'
				? html`<${ Field }
						label="Category"
						id="mi-cat"
						hint="The link follows the category, so it stays correct if the category is renamed."
					>
						<${ Select }
							id="mi-cat"
							value=${ draft.object_id }
							options=${ [
								{ value: 0, label: 'Choose a category' },
								...categories
									.slice()
									.sort( ( a, b ) => a.name.localeCompare( b.name ) )
									.map( ( c ) => ( { value: c.id, label: c.name + ' (' + c.count + ')' } ) ),
							] }
							onChange=${ ( value ) => set( { object_id: Number( value ) } ) }
						/>
					<//>`
				: html`<${ Field } label="Address" id="mi-url" hint="A full URL, or a path like /shop/.">
						<input
							class="sb-input"
							id="mi-url"
							type="text"
							inputmode="url"
							placeholder="/shop/"
							value=${ draft.url }
							onInput=${ ( e ) => set( { url: e.target.value } ) }
						/>
					<//>` }

			<${ Field } label="Sits under" id="mi-parent">
				<${ Select }
					id="mi-parent"
					value=${ draft.parent }
					options=${ parentOptions }
					onChange=${ ( value ) => set( { parent: Number( value ) } ) }
				/>
			<//>

			<${ MediaField }
				label="Icon (optional)"
				value=${ draft.icon }
				onChange=${ ( id ) => set( { icon: id } ) }
				hint="A small square image, around 24×24. Most items on the reference site have one; none of them need it."
			/>
		<//>
	`;
}
