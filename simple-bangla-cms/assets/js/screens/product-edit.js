/*
 * The product editor.
 *
 * Two decisions worth knowing about.
 *
 * Descriptions are edited with the plugin's own small rich-text control (`editor.js`), which the
 * owner asked for on 2026-08-09 in place of the raw-HTML textareas this screen shipped with. What
 * is stored is unchanged — HTML in the same two fields — so a product written before the change
 * opens correctly, and its "HTML" toggle is still the textarea for anything the toolbar cannot say.
 *
 * Deleting sends a product to the trash rather than erasing it. WooCommerce's default for
 * `DELETE` is exactly that, and a store owner who mis-taps on a phone should not lose a product
 * and its order history to a single confirmation.
 */

import {
	html,
	useState,
	useEffect,
	useCallback,
	Card,
	Field,
	Select,
	Switch,
	Badge,
	Confirm,
	ErrorBox,
	toast,
} from '../ui.js';
import { api, apiList, money, SB } from '../api.js';
import { navigate, href, onLinkClick } from '../router.js';
import { GalleryField } from '../media.js';
import { RichText } from '../editor.js';

/** What a brand-new product starts as. */
const BLANK = {
	name: '',
	type: 'simple',
	status: 'draft',
	featured: false,
	sku: '',
	regular_price: '',
	sale_price: '',
	description: '',
	short_description: '',
	manage_stock: false,
	stock_quantity: null,
	stock_status: 'instock',
	backorders: 'no',
	low_stock_amount: null,
	categories: [],
	images: [],
};

export function ProductEdit( { id } ) {
	const isNew = id === 'new';

	const [ product, setProduct ] = useState( isNew ? { ...BLANK } : null );
	const [ categories, setCategories ] = useState( [] );
	const [ variations, setVariations ] = useState( [] );
	const [ dirty, setDirty ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ confirming, setConfirming ] = useState( false );

	/* -- load -- */

	useEffect( () => {
		apiList( 'wc/v3/products/categories', { params: { per_page: 100, orderby: 'name' } } )
			.then( ( r ) => setCategories( r.items ) )
			.catch( () => setCategories( [] ) );
	}, [] );

	useEffect( () => {
		if ( isNew ) {
			setProduct( { ...BLANK } );
			setVariations( [] );
			setDirty( false );
			return;
		}

		setError( null );

		api( 'wc/v3/products/' + id )
			.then( ( loaded ) => {
				setProduct( loaded );
				setDirty( false );

				if ( loaded.type === 'variable' ) {
					return apiList( 'wc/v3/products/' + id + '/variations', { params: { per_page: 100 } } )
						.then( ( r ) => setVariations( r.items ) );
				}

				setVariations( [] );
				return null;
			} )
			.catch( setError );
	}, [ id, isNew ] );

	// Leaving with unsaved work is almost always a mistake, and a product form holds a lot of it.
	useEffect( () => {
		if ( ! dirty ) {
			return undefined;
		}

		const warn = ( event ) => {
			event.preventDefault();
			event.returnValue = '';
		};

		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ dirty ] );

	const set = useCallback( ( patch ) => {
		setProduct( ( current ) => ( { ...current, ...patch } ) );
		setDirty( true );
	}, [] );

	/* -- save -- */

	const save = async () => {
		if ( ! product.name.trim() ) {
			toast( 'Give the product a name before saving.', 'bad' );
			return;
		}

		setSaving( true );

		try {
			const payload = {
				name: product.name,
				status: product.status,
				featured: product.featured,
				sku: product.sku,
				description: product.description,
				short_description: product.short_description,
				manage_stock: product.manage_stock,
				stock_status: product.stock_status,
				backorders: product.backorders,
				categories: product.categories.map( ( c ) => ( { id: c.id } ) ),
				images: product.images.map( ( image ) => ( { id: image.id } ) ),
			};

			// A variable product's price lives on its variations; sending an empty regular_price
			// for one would blank the parent and make WooCommerce show no price range at all.
			if ( product.type !== 'variable' ) {
				payload.regular_price = String( product.regular_price ?? '' );
				payload.sale_price = String( product.sale_price ?? '' );
			}

			if ( product.manage_stock ) {
				payload.stock_quantity = Number( product.stock_quantity || 0 );
				payload.low_stock_amount = product.low_stock_amount === null || product.low_stock_amount === ''
					? null
					: Number( product.low_stock_amount );
			}

			const saved = isNew
				? await api( 'wc/v3/products', { method: 'POST', body: { ...payload, type: 'simple' } } )
				: await api( 'wc/v3/products/' + id, { method: 'PUT', body: payload } );

			if ( variations.length ) {
				await api( 'wc/v3/products/' + saved.id + '/variations/batch', {
					method: 'POST',
					body: {
						update: variations.map( ( v ) => ( {
							id: v.id,
							sku: v.sku,
							regular_price: String( v.regular_price ?? '' ),
							sale_price: String( v.sale_price ?? '' ),
							manage_stock: v.manage_stock,
							stock_quantity: v.manage_stock ? Number( v.stock_quantity || 0 ) : null,
							stock_status: v.stock_status,
						} ) ),
					},
				} );
			}

			setProduct( saved );
			setDirty( false );
			toast( isNew ? 'Product created' : 'Product saved' );

			if ( isNew ) {
				// Replace rather than push: the back button should not return to a blank form that
				// no longer corresponds to anything.
				navigate( '/products/' + saved.id, true );
			}
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setSaving( false );
		}
	};

	const remove = async () => {
		setSaving( true );

		try {
			await api( 'wc/v3/products/' + id, { method: 'DELETE' } );
			setDirty( false );
			toast( 'Product moved to trash' );
			navigate( '/products', true );
		} catch ( e ) {
			toast( e.message, 'bad' );
			setSaving( false );
			setConfirming( false );
		}
	};

	/* -- render -- */

	if ( error ) {
		return html`<div class="sb-page"><${ ErrorBox } error=${ error } /></div>`;
	}

	if ( ! product ) {
		return html`<div class="sb-page"><div class="sb-media-loading"><span class="sb-spinner"></span></div></div>`;
	}

	const isVariable = product.type === 'variable';

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<p class="sb-crumb">
						<a href=${ href( '/products' ) } onClick=${ ( e ) => onLinkClick( e, '/products' ) }>Products</a>
					</p>
					<h1 class="sb-page__title">${ isNew ? 'New product' : product.name || 'Untitled' }</h1>
					<p class="sb-page__lead">
						${ isNew
							? 'Simple product. Variable products are created in WordPress and edited here.'
							: html`<${ Badge } tone="muted">${ product.type }<//> ${ product.permalink
									? html` · <a href=${ product.permalink } target="_blank" rel="noopener">View on site</a>`
									: null }` }
					</p>
				</div>
				<div class="sb-row sb-page__actions">
					${ ! isNew
						? html`<button class="sb-btn sb-btn--ghost" onClick=${ () => setConfirming( true ) }>Delete</button>`
						: null }
					<button class="sb-btn sb-btn--primary" onClick=${ save } disabled=${ saving }>
						${ saving ? 'Saving…' : dirty || isNew ? 'Save' : 'Saved' }
					</button>
				</div>
			</div>

			<div class="sb-editor">
				<div class="sb-editor__main">
					<${ Card } title="Details">
						<${ Field } label="Name" id="p-name">
							<input
								class="sb-input"
								id="p-name"
								value=${ product.name }
								onInput=${ ( e ) => set( { name: e.target.value } ) }
							/>
						<//>
						<${ Field } label="Short description" id="p-short" hint="Shown near the price on the product page.">
							<${ RichText }
								id="p-short"
								rows=${ 4 }
								value=${ product.short_description }
								onChange=${ ( short_description ) => set( { short_description } ) }
							/>
						<//>
						<${ Field } label="Description" id="p-desc" hint="The full description tab.">
							<${ RichText }
								id="p-desc"
								rows=${ 10 }
								value=${ product.description }
								onChange=${ ( description ) => set( { description } ) }
							/>
						<//>
					<//>

					<${ Card } title="Images">
						<${ GalleryField } images=${ product.images || [] } onChange=${ ( images ) => set( { images } ) } />
					<//>

					${ isVariable
						? html`<${ Variations }
								variations=${ variations }
								onChange=${ ( next ) => {
									setVariations( next );
									setDirty( true );
								} }
						  />`
						: null }
				</div>

				<div class="sb-editor__side">
					<${ Card } title="Pricing">
						${ isVariable
							? html`<p class="sb-hint">
									A variable product takes its price from each variation. Edit them below.
							  </p>`
							: html`
									<${ Field } label="Regular price" id="p-price">
										<input
											class="sb-input"
											id="p-price"
											inputmode="decimal"
											value=${ product.regular_price ?? '' }
											onInput=${ ( e ) => set( { regular_price: e.target.value } ) }
										/>
									<//>
									<${ Field }
										label="Sale price"
										id="p-sale"
										hint=${ product.sale_price && Number( product.sale_price ) >= Number( product.regular_price || 0 )
											? 'This is not lower than the regular price, so no discount will show.'
											: 'Leave empty for no discount.' }
									>
										<input
											class="sb-input"
											id="p-sale"
											inputmode="decimal"
											value=${ product.sale_price ?? '' }
											onInput=${ ( e ) => set( { sale_price: e.target.value } ) }
										/>
									<//>
									${ product.regular_price
										? html`<p class="sb-hint">
												Customers see ${ money( product.sale_price || product.regular_price ) }.
										  </p>`
										: null }
							  ` }
					<//>

					<${ Card } title="Inventory">
						<${ Field } label="SKU" id="p-sku">
							<input
								class="sb-input"
								id="p-sku"
								value=${ product.sku || '' }
								onInput=${ ( e ) => set( { sku: e.target.value } ) }
							/>
						<//>
						<${ Switch }
							label="Track stock quantity"
							checked=${ product.manage_stock }
							onChange=${ ( manage_stock ) => set( { manage_stock } ) }
						/>
						${ product.manage_stock
							? html`
									<${ Field } label="Quantity in stock" id="p-qty">
										<input
											class="sb-input"
											id="p-qty"
											type="number"
											value=${ product.stock_quantity ?? 0 }
											onInput=${ ( e ) => set( { stock_quantity: e.target.value } ) }
										/>
									<//>
									<${ Field }
										label="Low stock warning at"
										id="p-low"
										hint="Leave empty to use the store default."
									>
										<input
											class="sb-input"
											id="p-low"
											type="number"
											value=${ product.low_stock_amount ?? '' }
											onInput=${ ( e ) => set( { low_stock_amount: e.target.value } ) }
										/>
									<//>
									<${ Field } label="Backorders" id="p-back">
										<${ Select }
											id="p-back"
											value=${ product.backorders }
											onChange=${ ( backorders ) => set( { backorders } ) }
											options=${ [
												{ value: 'no', label: 'Do not allow' },
												{ value: 'notify', label: 'Allow, but notify' },
												{ value: 'yes', label: 'Allow' },
											] }
										/>
									<//>
							  `
							: html`
									<${ Field } label="Stock status" id="p-stock">
										<${ Select }
											id="p-stock"
											value=${ product.stock_status }
											onChange=${ ( stock_status ) => set( { stock_status } ) }
											options=${ [
												{ value: 'instock', label: 'In stock' },
												{ value: 'outofstock', label: 'Out of stock' },
												{ value: 'onbackorder', label: 'On backorder' },
											] }
										/>
									<//>
							  ` }
					<//>

					<${ Card } title="Organisation">
						<${ Field } label="Status" id="p-status">
							<${ Select }
								id="p-status"
								value=${ product.status }
								onChange=${ ( status ) => set( { status } ) }
								options=${ [
									{ value: 'publish', label: 'Published' },
									{ value: 'draft', label: 'Draft' },
									{ value: 'pending', label: 'Pending review' },
									{ value: 'private', label: 'Private' },
								] }
							/>
						<//>
						<${ Switch }
							label="Featured product"
							hint="Featured products can be pulled into homepage rows."
							checked=${ product.featured }
							onChange=${ ( featured ) => set( { featured } ) }
						/>
						<span class="sb-field__label">Categories</span>
						<div class="sb-checklist">
							${ categories.length
								? categories.map( ( category ) => {
										const checked = ( product.categories || [] ).some( ( c ) => c.id === category.id );

										return html`
											<label class="sb-checklist__item" key=${ category.id }>
												<input
													type="checkbox"
													checked=${ checked }
													onChange=${ ( e ) =>
														set( {
															categories: e.target.checked
																? [ ...( product.categories || [] ), { id: category.id, name: category.name } ]
																: ( product.categories || [] ).filter( ( c ) => c.id !== category.id ),
														} ) }
												/>
												<span>${ category.parent ? '— ' : '' }${ category.name }</span>
											</label>
										`;
								  } )
								: html`<p class="sb-hint">No categories yet.</p>` }
						</div>
					<//>
				</div>
			</div>

			${ confirming
				? html`<${ Confirm }
						title="Delete this product?"
						body=${ 'It moves to the trash and can be restored from WordPress. Orders that already contain it are not affected.' }
						confirmLabel="Move to trash"
						busy=${ saving }
						onConfirm=${ remove }
						onClose=${ () => setConfirming( false ) }
				  />`
				: null }
		</div>
	`;
}

/**
 * Per-variation price and stock.
 *
 * Creating variations and managing attribute terms is not offered: that is a genuinely large
 * interface, and this store's catalogue is mostly simple products. Existing variations can be
 * priced and counted, which is the part that changes weekly.
 */
function Variations( { variations, onChange } ) {
	const update = ( index, patch ) => {
		const next = [ ...variations ];
		next[ index ] = { ...next[ index ], ...patch };
		onChange( next );
	};

	return html`
		<${ Card } title=${ 'Variations (' + variations.length + ')' }>
			${ variations.length
				? html`
						<div class="sb-table-wrap">
							<table class="sb-table sb-table--compact">
								<thead>
									<tr>
										<th>Variation</th>
										<th>SKU</th>
										<th>Regular</th>
										<th>Sale</th>
										<th>Stock</th>
									</tr>
								</thead>
								<tbody>
									${ variations.map(
										( variation, index ) => html`
											<tr key=${ variation.id }>
												<td class="sb-table__sub">
													${ ( variation.attributes || [] ).map( ( a ) => a.option ).join( ' / ' ) ||
													'#' + variation.id }
												</td>
												<td>
													<input
														class="sb-input sb-input--sm"
														value=${ variation.sku || '' }
														onInput=${ ( e ) => update( index, { sku: e.target.value } ) }
													/>
												</td>
												<td>
													<input
														class="sb-input sb-input--sm"
														inputmode="decimal"
														value=${ variation.regular_price ?? '' }
														onInput=${ ( e ) => update( index, { regular_price: e.target.value } ) }
													/>
												</td>
												<td>
													<input
														class="sb-input sb-input--sm"
														inputmode="decimal"
														value=${ variation.sale_price ?? '' }
														onInput=${ ( e ) => update( index, { sale_price: e.target.value } ) }
													/>
												</td>
												<td>
													${ variation.manage_stock
														? html`<input
																class="sb-input sb-input--sm"
																type="number"
																value=${ variation.stock_quantity ?? 0 }
																onInput=${ ( e ) => update( index, { stock_quantity: e.target.value } ) }
														  />`
														: html`<${ Select }
																value=${ variation.stock_status }
																onChange=${ ( stock_status ) => update( index, { stock_status } ) }
																options=${ [
																	{ value: 'instock', label: 'In stock' },
																	{ value: 'outofstock', label: 'Out of stock' },
																	{ value: 'onbackorder', label: 'Backorder' },
																] }
														  />` }
												</td>
											</tr>
										`
									) }
								</tbody>
							</table>
						</div>
				  `
				: html`<p class="sb-hint">This variable product has no variations yet.</p>` }
		<//>
	`;
}
