/*
 * Product categories.
 *
 * Shown as a tree rather than a flat list because the storefront menu is three levels deep and
 * several category names repeat across branches — "Airpod's" appears under both itself and
 * Gadgets. A flat list of names would be ambiguous exactly where it matters.
 *
 * Deleting a term is permanent: WordPress has no trash for taxonomy terms, so WooCommerce
 * requires `force=true` and there is nothing to restore afterwards. The confirmation says so.
 */

import {
	html,
	useState,
	useEffect,
	useCallback,
	Icon,
	Modal,
	Confirm,
	Field,
	Select,
	EmptyState,
	ErrorBox,
	toast,
} from '../ui.js';
import { api, apiList } from '../api.js';
import { MediaField } from '../media.js';

const BLANK = { name: '', slug: '', parent: 0, description: '', image: null };

export function Categories() {
	const [ items, setItems ] = useState( [] );
	const [ busy, setBusy ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ editing, setEditing ] = useState( null );
	const [ deleting, setDeleting ] = useState( null );
	const [ working, setWorking ] = useState( false );

	const load = useCallback( async () => {
		setBusy( true );
		setError( null );

		try {
			// 100 is WooCommerce's ceiling per page; a store with more categories than that has a
			// different problem than this screen.
			const { items: found } = await apiList( 'wc/v3/products/categories', {
				params: { per_page: 100, orderby: 'name', hide_empty: false },
			} );
			setItems( found );
		} catch ( e ) {
			setError( e );
		} finally {
			setBusy( false );
		}
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	const save = async ( draft ) => {
		if ( ! draft.name.trim() ) {
			toast( 'Give the category a name.', 'bad' );
			return;
		}

		setWorking( true );

		try {
			const payload = {
				name: draft.name,
				// Omitted rather than sent empty when it was never typed. WooCommerce derives one
				// from the name on create; on an update an empty string is a request to blank the
				// slug, which it answers by regenerating from the name — quietly breaking every
				// link to a category that was only being renamed.
				...( draft.slug.trim() ? { slug: draft.slug.trim() } : {} ),
				parent: Number( draft.parent ) || 0,
				description: draft.description,
				// Passing an empty image object is how WooCommerce is told to clear one.
				image: draft.image ? { id: draft.image } : {},
			};

			if ( draft.id ) {
				await api( 'wc/v3/products/categories/' + draft.id, { method: 'PUT', body: payload } );
				toast( 'Category saved' );
			} else {
				await api( 'wc/v3/products/categories', { method: 'POST', body: payload } );
				toast( 'Category created' );
			}

			setEditing( null );
			await load();
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setWorking( false );
		}
	};

	const remove = async () => {
		setWorking( true );

		try {
			await api( 'wc/v3/products/categories/' + deleting.id, {
				method: 'DELETE',
				params: { force: true },
			} );
			toast( 'Category deleted' );
			setDeleting( null );
			await load();
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setWorking( false );
		}
	};

	const tree = buildTree( items );

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Categories</h1>
					<p class="sb-page__lead">${ items.length } categories. These drive the shop menu and the homepage rows.</p>
				</div>
				<button class="sb-btn sb-btn--primary" onClick=${ () => setEditing( { ...BLANK } ) }>
					Add category
				</button>
			</div>

			${ error ? html`<${ ErrorBox } error=${ error } onRetry=${ load } />` : null }

			${ busy
				? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
				: items.length
				? html`
						<div class="sb-table-wrap">
							<table class="sb-table">
								<thead>
									<tr>
										<th class="sb-table__thumb-col"><span class="sb-sr">Image</span></th>
										<th>Name</th>
										<th>Slug</th>
										<th>Products</th>
										<th class="sb-table__actions-col"><span class="sb-sr">Actions</span></th>
									</tr>
								</thead>
								<tbody>
									${ tree.map(
										( { category, depth } ) => html`
											<tr key=${ category.id }>
												<td>
													<div class="sb-table__thumb">
														${ category.image
															? html`<img src=${ category.image.src } alt="" loading="lazy" />`
															: html`<span class="sb-thumb__empty"><${ Icon } name="image" size=${ 18 } /></span>` }
													</div>
												</td>
												<td>
													<span class="sb-table__name" style=${ 'padding-left:' + depth * 18 + 'px' }>
														${ depth ? '└ ' : '' }${ category.name }
													</span>
												</td>
												<td class="sb-table__sub">${ category.slug }</td>
												<td class="sb-table__sub">${ category.count }</td>
												<td>
													<div class="sb-row">
														<button
															class="sb-btn sb-btn--ghost sb-btn--sm"
															onClick=${ () =>
																setEditing( {
																	id: category.id,
																	name: category.name,
																	slug: category.slug,
																	parent: category.parent,
																	description: category.description,
																	image: category.image ? category.image.id : null,
																} ) }
														>
															Edit
														</button>
														<button class="sb-btn sb-btn--ghost sb-btn--sm" onClick=${ () => setDeleting( category ) }>
															Delete
														</button>
													</div>
												</td>
											</tr>
										`
									) }
								</tbody>
							</table>
						</div>
				  `
				: html`<${ EmptyState } title="No categories yet" body="Add one to start organising the catalogue." />` }

			${ editing
				? html`<${ CategoryForm }
						draft=${ editing }
						all=${ items }
						busy=${ working }
						onSave=${ save }
						onClose=${ () => setEditing( null ) }
				  />`
				: null }

			${ deleting
				? html`<${ Confirm }
						title=${ 'Delete "' + deleting.name + '"?' }
						body=${ deleting.count
							? deleting.count +
							  ' products are in this category. They will not be deleted, but they will lose this category. Categories cannot be restored.'
							: 'Categories cannot be restored once deleted.' }
						busy=${ working }
						onConfirm=${ remove }
						onClose=${ () => setDeleting( null ) }
				  />`
				: null }
		</div>
	`;
}

/**
 * The address WordPress will make out of a name.
 *
 * A preview only — the real slug is generated server-side by sanitize_title(), and this cannot be
 * an exact copy of it (Bangla names go through remove_accents() and a transliteration table this
 * has no access to). It is here so the owner sees that the address is being taken care of, not so
 * anything is decided by it. A name that leaves nothing behind falls back to saying so rather than
 * showing an empty box, which would read as a bug.
 *
 * @param {string} name Category name.
 * @return {string}
 */
function slugPreview( name ) {
	const slug = ( name || '' )
		.toLowerCase()
		.trim()
		.replace( /['’]/g, '' )
		.replace( /[^a-z0-9ঀ-৿]+/g, '-' )
		.replace( /^-+|-+$/g, '' );

	return slug || '…';
}

function CategoryForm( { draft, all, busy, onSave, onClose } ) {
	const [ form, setForm ] = useState( draft );

	/*
	 * Both the slug and the parent live behind this. The owner asked not to have to write a slug,
	 * and asked whether Parent was needed at all — it is, because several of the shop's categories
	 * are already nested and removing the control would make that unreachable, but it is answered
	 * once when a category is created and never again. Neither belongs in the way of the two fields
	 * that are edited every time.
	 */
	const [ advanced, setAdvanced ] = useState( false );

	const set = ( patch ) => setForm( ( current ) => ( { ...current, ...patch } ) );

	// A category cannot be its own parent, and offering itself in the list invites a term with a
	// broken hierarchy that only shows up as a missing menu item later.
	const parents = all.filter( ( c ) => c.id !== form.id );

	return html`
		<${ Modal }
			title=${ form.id ? 'Edit category' : 'New category' }
			onClose=${ onClose }
			footer=${ html`
				<button class="sb-btn sb-btn--ghost" onClick=${ onClose }>Cancel</button>
				<button class="sb-btn sb-btn--primary" disabled=${ busy } onClick=${ () => onSave( form ) }>
					${ busy ? 'Saving…' : 'Save category' }
				</button>
			` }
		>
			<${ Field }
				label="Name"
				id="c-name"
				hint=${ form.slug.trim()
					? 'Address: /product-category/' + form.slug.trim() + '/'
					: 'Address: /product-category/' + slugPreview( form.name ) + '/ — made from the name.' }
			>
				<input class="sb-input" id="c-name" value=${ form.name } onInput=${ ( e ) => set( { name: e.target.value } ) } />
			<//>

			<${ Field } label="Description" id="c-desc">
				<textarea
					class="sb-input sb-textarea"
					id="c-desc"
					rows="3"
					value=${ form.description }
					onInput=${ ( e ) => set( { description: e.target.value } ) }
				></textarea>
			<//>

			<${ MediaField }
				label="Category image"
				hint="Used by the homepage category circles."
				value=${ form.image }
				onChange=${ ( image ) => set( { image } ) }
			/>

			<button class="sb-disclosure" type="button" aria-expanded=${ advanced } onClick=${ () => setAdvanced( ! advanced ) }>
				<${ Icon } name="chevron" size=${ 14 } />
				<span>Advanced</span>
			</button>

			${ advanced
				? html`
						<div class="sb-disclosure__body">
							<${ Field }
								label="Parent category"
								id="c-parent"
								hint="Nests this inside another one, so its address becomes /product-category/parent/this/ and the parent's page also lists these products."
							>
								<${ Select }
									id="c-parent"
									value=${ form.parent }
									onChange=${ ( parent ) => set( { parent } ) }
									options=${ [
										{ value: 0, label: 'No parent (top level)' },
										...parents.map( ( c ) => ( { value: c.id, label: c.name } ) ),
									] }
								/>
							<//>

							<${ Field }
								label="Slug"
								id="c-slug"
								hint=${ form.id
									? 'The address. Changing it breaks existing links to this category.'
									: 'Leave empty and one is made from the name.' }
							>
								<input
									class="sb-input"
									id="c-slug"
									spellcheck="false"
									autocomplete="off"
									value=${ form.slug }
									onInput=${ ( e ) => set( { slug: e.target.value } ) }
								/>
							<//>
						</div>
				  `
				: null }
		<//>
	`;
}

/**
 * Flatten the categories into display order, carrying each one's depth.
 *
 * WooCommerce returns a flat list with parent ids; rendering it in that order puts children
 * above their parents whenever the alphabet says so.
 */
function buildTree( items ) {
	const byParent = new Map();

	items.forEach( ( item ) => {
		const key = item.parent || 0;
		byParent.set( key, [ ...( byParent.get( key ) || [] ), item ] );
	} );

	const out = [];

	const walk = ( parent, depth ) => {
		( byParent.get( parent ) || [] ).forEach( ( category ) => {
			out.push( { category, depth } );
			walk( category.id, depth + 1 );
		} );
	};

	walk( 0, 0 );

	// Anything whose parent is missing (a deleted term, a filtered page) would otherwise vanish.
	items.forEach( ( item ) => {
		if ( ! out.some( ( row ) => row.category.id === item.id ) ) {
			out.push( { category: item, depth: 0 } );
		}
	} );

	return out;
}
