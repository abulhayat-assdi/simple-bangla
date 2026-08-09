/*
 * The six homepage product rows.
 *
 * Heading and category are two independent fields, which is the whole point of the screen. The
 * reference site derives one from the other and gets it wrong on five of its six rows — a shelf
 * headed "Powerbank" listing earbuds — and the theme was built not to reproduce that. An interface
 * that quietly renamed the row when the category changed would put the bug back.
 */

import { html, useState, useEffect, Card, Field, Select, Switch, Badge } from '../ui.js';
import { apiList } from '../api.js';
import {
	useSettings,
	SettingsPage,
	NumberInput,
	indexesFrom,
	COUNT_MIN,
	COUNT_MAX,
} from '../settings-form.js';

/** Flatten the category tree into indented options, parents before their children. */
function toOptions( categories ) {
	const byParent = new Map();

	categories.forEach( ( category ) => {
		const list = byParent.get( category.parent ) || [];
		list.push( category );
		byParent.set( category.parent, list );
	} );

	const options = [ { value: 0, label: 'Theme default for this row' } ];

	const walk = ( parent, depth ) => {
		const children = ( byParent.get( parent ) || [] ).sort( ( a, b ) => a.name.localeCompare( b.name ) );

		children.forEach( ( category ) => {
			options.push( {
				value: category.id,
				label: ( depth ? '— '.repeat( depth ) : '' ) + category.name + ' (' + category.count + ')',
			} );

			walk( category.id, depth + 1 );
		} );
	};

	walk( 0, 0 );

	return options;
}

export function Rows() {
	const state = useSettings( 'rows' );
	const [ categories, setCategories ] = useState( [] );

	useEffect( () => {
		apiList( 'wc/v3/products/categories', { params: { per_page: 100, hide_empty: false } } )
			.then( ( result ) => setCategories( result.items ) )
			.catch( () => setCategories( [] ) );
	}, [] );

	const rows = indexesFrom( state.fields, /^simple_bangla_home_row_(\d+)_enabled$/ );
	const options = toOptions( categories );
	const live = rows.filter( ( n ) => state.values[ 'simple_bangla_home_row_' + n + '_enabled' ] ).length;

	return html`
		<${ SettingsPage }
			title="Product Rows"
			lead=${ 'The product shelves down the homepage. ' + live + ' of ' + rows.length + ' switched on.' }
			state=${ state }
		>
			<div class="sb-grid-cards">
				${ rows.map( ( n ) => {
					const prefix = 'simple_bangla_home_row_' + n + '_';
					const enabled = !! state.values[ prefix + 'enabled' ];
					const chosen = Number( state.values[ prefix + 'cat' ] || 0 );
					const wanted = Number( state.values[ prefix + 'count' ] || 0 );
					const category = categories.find( ( c ) => c.id === chosen );
					const short = category && category.count < wanted;

					return html`
						<${ Card }
							key=${ n }
							title=${ 'Row ' + n }
							action=${ enabled
								? html`<${ Badge } tone="ok">On<//>`
								: html`<${ Badge }>Off<//>` }
						>
							<${ Switch }
								checked=${ enabled }
								label="Show this row"
								onChange=${ ( value ) => state.set( prefix + 'enabled', value ) }
							/>

							<${ Field } label="Heading" id=${ 'row-head-' + n }>
								<input
									class="sb-input"
									id=${ 'row-head-' + n }
									value=${ state.values[ prefix + 'heading' ] || '' }
									onInput=${ ( e ) => state.set( prefix + 'heading', e.target.value ) }
								/>
							<//>

							<${ Field }
								label="Products from"
								id=${ 'row-cat-' + n }
								hint="The heading is separate on purpose — the shelf can be titled anything you like."
							>
								<${ Select }
									id=${ 'row-cat-' + n }
									value=${ chosen }
									options=${ options }
									onChange=${ ( value ) => state.set( prefix + 'cat', Number( value ) ) }
								/>
							<//>

							<${ Field }
								label="How many products"
								id=${ 'row-count-' + n }
								hint=${ `${ COUNT_MIN }–${ COUNT_MAX }.` }
							>
								<${ NumberInput }
									id=${ 'row-count-' + n }
									min=${ COUNT_MIN }
									max=${ COUNT_MAX }
									value=${ state.values[ prefix + 'count' ] }
									onChange=${ ( value ) => state.set( prefix + 'count', value ) }
								/>
							<//>

							${ ! chosen
								? html`<p class="sb-hint">
										On "theme default" the row looks for the category this position was designed
										around, and falls back to all products if it does not exist.
								  </p>`
								: short
								? html`<p class="sb-hint">
										${ category.name } has ${ category.count } product${ category.count === 1
											? ''
											: 's' }, so the row will show ${ category.count }.
								  </p>`
								: null }
						<//>
					`;
				} ) }
			</div>
		<//>
	`;
}
