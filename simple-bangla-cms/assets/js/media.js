/*
 * Choosing and uploading images.
 *
 * WordPress's own media modal is not available here: it is jQuery, Backbone and a dependency
 * chain that only exists inside wp-admin. This is a small replacement over `wp/v2/media` — a
 * grid, a search box and an upload button, which is the whole of what the CMS needs.
 *
 * Uploads go straight to the library, so an image added while editing a product is available
 * everywhere afterwards rather than being trapped on that product.
 */

import { html, useState, useEffect, useCallback, Modal, Icon, EmptyState, toast } from './ui.js';
import { api, apiList, apiUpload } from './api.js';

/**
 * The picker dialog.
 *
 * @param {object}   props
 * @param {boolean}  props.multiple Allow more than one selection.
 * @param {Function} props.onSelect Called with an array of attachments.
 * @param {Function} props.onClose  Dismiss.
 */
export function MediaPicker( { multiple = false, onSelect, onClose } ) {
	const [ items, setItems ] = useState( [] );
	const [ search, setSearch ] = useState( '' );
	const [ busy, setBusy ] = useState( true );
	const [ uploading, setUploading ] = useState( false );
	const [ chosen, setChosen ] = useState( [] );
	const [ error, setError ] = useState( null );

	const load = useCallback( async ( term ) => {
		setBusy( true );
		setError( null );

		try {
			const { items: found } = await apiList( 'wp/v2/media', {
				params: { media_type: 'image', per_page: 40, orderby: 'date', order: 'desc', search: term },
			} );
			setItems( found );
		} catch ( e ) {
			setError( e );
		} finally {
			setBusy( false );
		}
	}, [] );

	// Debounced so typing a filename does not fire a request per keystroke.
	useEffect( () => {
		const timer = setTimeout( () => load( search ), search ? 300 : 0 );
		return () => clearTimeout( timer );
	}, [ search, load ] );

	const toggle = ( item ) => {
		if ( ! multiple ) {
			setChosen( [ item ] );
			return;
		}

		setChosen( ( current ) =>
			current.some( ( c ) => c.id === item.id )
				? current.filter( ( c ) => c.id !== item.id )
				: [ ...current, item ]
		);
	};

	const upload = async ( event ) => {
		const files = [ ...event.target.files ];

		if ( ! files.length ) {
			return;
		}

		setUploading( true );

		try {
			const uploaded = [];

			for ( const file of files ) {
				uploaded.push( await apiUpload( file ) );
			}

			// Put them at the front rather than refetching: the new files are what the user wants
			// to click next, and a refetch would drop them to wherever the sort puts them.
			setItems( ( current ) => [ ...uploaded, ...current ] );
			setChosen( multiple ? uploaded : [ uploaded[ 0 ] ] );
			toast( uploaded.length + ' image' + ( uploaded.length > 1 ? 's' : '' ) + ' uploaded' );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setUploading( false );
			event.target.value = '';
		}
	};

	return html`
		<${ Modal }
			wide
			title=${ multiple ? 'Choose images' : 'Choose an image' }
			onClose=${ onClose }
			footer=${ html`
				<span class="sb-pager__info">
					${ chosen.length ? chosen.length + ' selected' : 'Nothing selected' }
				</span>
				<span class="sb-row">
					<button class="sb-btn sb-btn--ghost" onClick=${ onClose }>Cancel</button>
					<button
						class="sb-btn sb-btn--primary"
						disabled=${ ! chosen.length }
						onClick=${ () => {
							onSelect( chosen );
							onClose();
						} }
					>
						Use ${ multiple && chosen.length > 1 ? 'images' : 'image' }
					</button>
				</span>
			` }
		>
			<div class="sb-media-bar">
				<input
					class="sb-input"
					type="search"
					placeholder="Search the library"
					value=${ search }
					onInput=${ ( e ) => setSearch( e.target.value ) }
				/>
				<label class="sb-btn sb-btn--ghost">
					<${ Icon } name="image" />
					${ uploading ? 'Uploading…' : 'Upload' }
					<input
						type="file"
						accept="image/*"
						multiple=${ multiple }
						hidden
						disabled=${ uploading }
						onChange=${ upload }
					/>
				</label>
			</div>

			${ error ? html`<p class="sb-alert sb-alert--error">${ error.message }</p>` : null }

			${ busy
				? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
				: items.length
				? html`
						<div class="sb-media-grid">
							${ items.map( ( item ) => {
								const selected = chosen.some( ( c ) => c.id === item.id );

								return html`
									<button
										key=${ item.id }
										type="button"
										class=${ 'sb-media-item' + ( selected ? ' is-selected' : '' ) }
										aria-pressed=${ selected }
										onClick=${ () => toggle( item ) }
									>
										<img src=${ thumbOf( item ) } alt=${ item.alt_text || '' } loading="lazy" />
									</button>
								`;
							} ) }
						</div>
				  `
				: html`<${ EmptyState }
						title="No images found"
						body=${ search ? 'Nothing matches that search.' : 'Upload one to get started.' }
				  />` }
		<//>
	`;
}

/** The smallest sensible rendition, falling back to the original. */
function thumbOf( attachment ) {
	const sizes = attachment.media_details && attachment.media_details.sizes;

	if ( sizes ) {
		const pick = sizes.thumbnail || sizes.medium || sizes.woocommerce_thumbnail;

		if ( pick ) {
			return pick.source_url;
		}
	}

	return attachment.source_url;
}

/**
 * A single-image form control.
 */
export function MediaField( { label, value, onChange, hint } ) {
	const [ open, setOpen ] = useState( false );
	const [ preview, setPreview ] = useState( null );

	// The value is an attachment ID; the interface needs a URL to draw.
	useEffect( () => {
		let cancelled = false;

		if ( ! value ) {
			setPreview( null );
			return undefined;
		}

		api( 'wp/v2/media/' + value )
			.then( ( item ) => {
				if ( ! cancelled ) {
					setPreview( thumbOf( item ) );
				}
			} )
			.catch( () => {
				// A deleted attachment should show as "no image", not as an error the owner
				// cannot act on.
				if ( ! cancelled ) {
					setPreview( null );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ value ] );

	return html`
		<div class="sb-field">
			${ label ? html`<span class="sb-field__label">${ label }</span>` : null }
			<div class="sb-mediafield">
				<div class="sb-thumb">
					${ preview
						? html`<img src=${ preview } alt="" />`
						: html`<span class="sb-thumb__empty"><${ Icon } name="image" size=${ 22 } /></span>` }
				</div>
				<div class="sb-row">
					<button type="button" class="sb-btn sb-btn--ghost" onClick=${ () => setOpen( true ) }>
						${ value ? 'Replace' : 'Choose image' }
					</button>
					${ value
						? html`<button type="button" class="sb-btn sb-btn--ghost" onClick=${ () => onChange( 0 ) }>
								Remove
						  </button>`
						: null }
				</div>
			</div>
			${ hint ? html`<p class="sb-hint">${ hint }</p>` : null }
			${ open
				? html`<${ MediaPicker }
						onClose=${ () => setOpen( false ) }
						onSelect=${ ( picked ) => onChange( picked[ 0 ].id ) }
				  />`
				: null }
		</div>
	`;
}

/**
 * The product image list: first entry is the featured image, the rest are the gallery.
 *
 * @param {object}   props
 * @param {object[]} props.images   WooCommerce image objects ({ id, src }).
 * @param {Function} props.onChange Called with the new array.
 */
export function GalleryField( { images, onChange } ) {
	const [ open, setOpen ] = useState( false );

	const move = ( index, delta ) => {
		const next = [ ...images ];
		const target = index + delta;

		if ( target < 0 || target >= next.length ) {
			return;
		}

		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		onChange( next );
	};

	return html`
		<div>
			${ images.length
				? html`
						<div class="sb-gallery">
							${ images.map(
								( image, index ) => html`
									<figure class="sb-gallery__item" key=${ image.id || index }>
										<img src=${ image.src } alt=${ image.alt || '' } />
										${ index === 0
											? html`<figcaption class="sb-gallery__flag">Featured</figcaption>`
											: null }
										<div class="sb-gallery__tools">
											<button
												type="button"
												class="sb-icon-btn"
												title="Move earlier"
												disabled=${ index === 0 }
												onClick=${ () => move( index, -1 ) }
											>
												‹
											</button>
											<button
												type="button"
												class="sb-icon-btn"
												title="Move later"
												disabled=${ index === images.length - 1 }
												onClick=${ () => move( index, 1 ) }
											>
												›
											</button>
											<button
												type="button"
												class="sb-icon-btn"
												title="Remove"
												onClick=${ () => onChange( images.filter( ( _, i ) => i !== index ) ) }
											>
												×
											</button>
										</div>
									</figure>
								`
							) }
						</div>
				  `
				: html`<p class="sb-hint">No images yet. The first one you add becomes the featured image.</p>` }

			<button type="button" class="sb-btn sb-btn--ghost" onClick=${ () => setOpen( true ) }>
				<${ Icon } name="image" /> Add images
			</button>

			${ open
				? html`<${ MediaPicker }
						multiple
						onClose=${ () => setOpen( false ) }
						onSelect=${ ( picked ) =>
							onChange( [
								...images,
								// WooCommerce keys product images by attachment id; src is what we draw
								// with until the product is saved and it echoes its own URL back.
								...picked
									.filter( ( p ) => ! images.some( ( i ) => i.id === p.id ) )
									.map( ( p ) => ( { id: p.id, src: thumbOf( p ), alt: p.alt_text || '' } ) ),
							] ) }
				  />`
				: null }
		</div>
	`;
}
