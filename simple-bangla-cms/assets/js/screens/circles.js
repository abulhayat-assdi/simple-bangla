/*
 * Category circles.
 *
 * The round shortcuts under the hero. The theme does not store a list of them — it takes the
 * top-level product categories in menu order and stops at the count set here. So this screen edits
 * two different things at once: the count, which is a theme setting, and the picture and position
 * of each category, which are WooCommerce's.
 *
 * Both are shown together because the owner is answering one question ("what should the circles
 * be?"), and sending them to Categories for the image and back here for the order would be a
 * filing decision leaking into the interface.
 *
 * Save is not atomic across the two APIs — WooCommerce has no batch endpoint that also writes a
 * theme mod. Categories are written first, one at a time, and a failure stops the run with the
 * successful ones already committed and shown as saved. Reporting "nothing was saved" after four
 * of six went through would be the more comfortable lie.
 */

import {
	html,
	useState,
	useEffect,
	Card,
	Field,
	Badge,
	EmptyState,
	ErrorBox,
	toast,
} from '../ui.js';
import { api, apiList } from '../api.js';
import { href, onLinkClick } from '../router.js';
import { MediaField } from '../media.js';
import { useSettings, SettingsPage, NumberInput, COUNT_MIN, COUNT_MAX } from '../settings-form.js';

const COUNT_KEY = 'simple_bangla_home_circles_count';

/** The order the theme reads: menu order first, then name to break the ties it leaves. */
function inDisplayOrder( list ) {
	return [ ...list ].sort(
		( a, b ) => ( a.menu_order || 0 ) - ( b.menu_order || 0 ) || a.name.localeCompare( b.name )
	);
}

/**
 * The attachment id on a category, or 0.
 *
 * WooCommerce sends `image` as an empty PHP array when a category has no thumbnail, which arrives
 * as `[]` — truthy in JavaScript. A plain `category.image ? category.image.id : 0` therefore yields
 * `undefined` rather than 0 for exactly the categories that have no picture, and every one of them
 * would read as permanently unsaved.
 *
 * @param {object} category WooCommerce category.
 * @return {number}
 */
function imageIdOf( category ) {
	return category && category.image && category.image.id ? Number( category.image.id ) : 0;
}

/** Only the fields this screen can change, so a comparison ignores counts and descriptions. */
function editableOf( category ) {
	return {
		image: imageIdOf( category ),
		menu_order: Number( category.menu_order || 0 ),
	};
}

export function Circles() {
	const state = useSettings( 'circles' );

	const [ cats, setCats ] = useState( null );
	const [ baseline, setBaseline ] = useState( {} );
	const [ catsError, setCatsError ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	const loadCats = () => {
		setCatsError( null );

		// `menu_order` is not in the REST controller's orderby enum, so the sort happens here.
		apiList( 'wc/v3/products/categories', {
			params: { parent: 0, per_page: 100, hide_empty: false },
		} )
			.then( ( result ) => {
				setCats( inDisplayOrder( result.items ) );

				const base = {};
				result.items.forEach( ( c ) => {
					base[ c.id ] = editableOf( c );
				} );
				setBaseline( base );
			} )
			.catch( setCatsError );
	};

	useEffect( loadCats, [] );

	const patch = ( id, changes ) => {
		setCats( ( current ) =>
			current.map( ( c ) => ( c.id === id ? { ...c, ...changes } : c ) )
		);
	};

	const changedCats = ( cats || [] ).filter( ( c ) => {
		const was = baseline[ c.id ];
		const now = editableOf( c );

		return ! was || was.image !== now.image || was.menu_order !== now.menu_order;
	} );

	const dirty = state.dirty || changedCats.length > 0;

	const save = async () => {
		setSaving( true );

		try {
			for ( const category of changedCats ) {
				const now = editableOf( category );

				// An id of 0 is how WooCommerce is told to drop the thumbnail; omitting the key
				// would leave the old one in place.
				const saved = await api( 'wc/v3/products/categories/' + category.id, {
					method: 'POST',
					body: { image: { id: now.image }, menu_order: now.menu_order },
				} );

				setBaseline( ( current ) => ( { ...current, [ category.id ]: editableOf( saved ) } ) );
				patch( category.id, { image: saved.image, menu_order: saved.menu_order } );
			}

			if ( state.dirty ) {
				const ok = await state.save();

				if ( ! ok ) {
					return;
				}
			} else if ( changedCats.length ) {
				toast( 'Saved' );
			}

			setCats( ( current ) => inDisplayOrder( current ) );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setSaving( false );
		}
	};

	const count = Number( state.values[ COUNT_KEY ] || 0 );

	return html`
		<${ SettingsPage }
			title="Category Circles"
			lead="The round category shortcuts under the hero, taken from your top-level categories in the order below."
			state=${ state }
			dirty=${ dirty }
			saving=${ saving || state.saving }
			onSave=${ save }
		>
			<${ Card } title="How many to show">
				<${ Field }
					label="Circles on the homepage"
					id="circ-count"
					hint=${ `The first few categories in the order below. The rest stay in the shop but not on the homepage. ${ COUNT_MIN }–${ COUNT_MAX }.` }
				>
					<${ NumberInput }
						id="circ-count"
						min=${ COUNT_MIN }
						max=${ COUNT_MAX }
						value=${ state.values[ COUNT_KEY ] }
						onChange=${ ( value ) => state.set( COUNT_KEY, value ) }
					/>
				<//>
			<//>

			<${ Card } title="Top-level categories">
				${ catsError
					? html`<${ ErrorBox } error=${ catsError } onRetry=${ loadCats } />`
					: ! cats
					? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
					: ! cats.length
					? html`<${ EmptyState }
							title="No top-level categories"
							body="Create one on the Categories screen and it will appear here."
							action=${ html`<a
								class="sb-btn sb-btn--primary"
								href=${ href( '/categories' ) }
								onClick=${ ( e ) => onLinkClick( e, '/categories' ) }
								>Go to Categories</a
							>` }
					  />`
					: html`
							<p class="sb-hint">
								A category with no picture falls back to the first letter of its name.
							</p>

							<div class="sb-circlelist">
								${ cats.map( ( category, index ) => {
									const shown = index < count;

									return html`
										<div
											class=${ 'sb-circlerow' + ( shown ? '' : ' is-hidden' ) }
											key=${ category.id }
										>
											<div class="sb-circlerow__image">
												<${ MediaField }
													value=${ imageIdOf( category ) }
													onChange=${ ( id ) =>
														patch( category.id, { image: id ? { id } : null } ) }
												/>
											</div>

											<div class="sb-circlerow__body">
												<p class="sb-circlerow__name">
													${ category.name }
													${ shown
														? html` <${ Badge } tone="ok">On the homepage<//>`
														: html` <${ Badge }>Not shown<//>` }
												</p>
												<p class="sb-hint">${ category.count } products</p>
											</div>

											${ /*
											 * Order is its own column rather than the third thing in the body.
											 * The picker on the left is one button wide when a category has no
											 * picture and two when it has one, so with everything in a single
											 * flex row every category's name started at a different x and the
											 * list read as ragged — which it was. Three fixed grid columns line
											 * up the names, the counts and the number boxes down the page.
											 */ null }
											<div class="sb-circlerow__order">
												<${ Field } label="Order" id=${ 'circ-order-' + category.id }>
													<${ NumberInput }
														id=${ 'circ-order-' + category.id }
														min=${ 0 }
														max=${ 999 }
														value=${ category.menu_order || 0 }
														onChange=${ ( value ) =>
															patch( category.id, { menu_order: value === '' ? 0 : value } ) }
													/>
												<//>
											</div>
										</div>
									`;
								} ) }
							</div>
					  ` }
			<//>
		<//>
	`;
}
