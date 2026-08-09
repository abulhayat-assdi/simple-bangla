/*
 * The two homepage banner pairs.
 *
 * The reference shows banners only ever as a pair — one narrow panel beside one wide one — twice
 * down the page, and the theme follows that. Either half renders on its own, and a pair with
 * neither is skipped entirely, so the four slots below are places a banner may go rather than four
 * that have to be filled.
 */

import { html, Card, Field, Badge } from '../ui.js';
import { MediaField } from '../media.js';
import { useSettings, SettingsPage, indexesFrom, LinkInput } from '../settings-form.js';

/** Where each pair sits once the homepage is assembled, so the screen is not just "pair 1, pair 2". */
const PLACEMENT = {
	1: 'Between the second and third product rows',
	2: 'Between the fourth and fifth product rows',
};

const SLOTS = [
	{ key: 'small', label: 'Narrow panel', hint: 'Portrait-ish, around 595×280.' },
	{ key: 'wide', label: 'Wide panel', hint: 'Landscape, around 1024×512.' },
];

export function Banners() {
	const state = useSettings( 'banners' );
	const pairs = indexesFrom( state.fields, /^simple_bangla_home_banner_(\d+)_small_image$/ );

	return html`
		<${ SettingsPage }
			title="Banners"
			lead="Two promotional rows down the homepage, each a narrow panel beside a wide one."
			state=${ state }
		>
			${ pairs.map( ( pair ) => {
				const used = SLOTS.filter(
					( slot ) => state.values[ 'simple_bangla_home_banner_' + pair + '_' + slot.key + '_image' ]
				).length;

				return html`
					<${ Card }
						key=${ pair }
						wide
						title=${ 'Banner pair ' + pair }
						action=${ used
							? html`<${ Badge } tone="ok">${ used } of 2 in use<//>`
							: html`<${ Badge }>Hidden<//>` }
					>
						<p class="sb-hint">${ PLACEMENT[ pair ] || 'On the homepage.' }</p>

						<div class="sb-grid-cards">
							${ SLOTS.map( ( slot ) => {
								const base = 'simple_bangla_home_banner_' + pair + '_' + slot.key;

								return html`
									<div class="sb-subcard" key=${ slot.key }>
										<p class="sb-section-label">${ slot.label }</p>

										<${ MediaField }
											value=${ state.values[ base + '_image' ] }
											onChange=${ ( id ) => state.set( base + '_image', id ) }
											hint=${ slot.hint }
										/>

										<${ Field } label="Links to" id=${ base + '-link' }>
											<${ LinkInput }
												id=${ base + '-link' }
												value=${ state.values[ base + '_link' ] }
												onChange=${ ( value ) => state.set( base + '_link', value ) }
											/>
										<//>
									</div>
								`;
							} ) }
						</div>
					<//>
				`;
			} ) }
		<//>
	`;
}
