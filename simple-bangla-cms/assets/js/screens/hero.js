/*
 * The hero slider.
 *
 * The homepage opens with a two-column row: a one-card Hot Deals slider on the left and this
 * banner carousel on the right. A slide with no image is skipped by the theme rather than drawn
 * blank, so the five slots are a maximum and not a quota — which the empty ones say on screen, or
 * an owner who filled three would wonder what the other two were doing.
 */

import { html, Card, Field, Badge } from '../ui.js';
import { MediaField } from '../media.js';
import { useSettings, SettingsPage, indexesFrom, LinkInput } from '../settings-form.js';

export function Hero() {
	const state = useSettings( 'hero' );
	const slides = indexesFrom( state.fields, /^simple_bangla_hero_(\d+)_image$/ );
	const filled = slides.filter( ( n ) => state.values[ 'simple_bangla_hero_' + n + '_image' ] ).length;

	return html`
		<${ SettingsPage }
			title="Hero Slider"
			lead=${ 'The wide banner carousel at the top of the homepage. ' +
				( filled ? filled + ' of ' + slides.length + ' slides in use.' : 'No slides yet.' ) }
			state=${ state }
		>
			<div class="sb-grid-cards">
				${ slides.map( ( n ) => {
					const imageKey = 'simple_bangla_hero_' + n + '_image';
					const linkKey = 'simple_bangla_hero_' + n + '_link';
					const image = state.values[ imageKey ];

					return html`
						<${ Card }
							key=${ n }
							title=${ 'Slide ' + n }
							action=${ image
								? html`<${ Badge } tone="ok">Live<//>`
								: html`<${ Badge }>Empty<//>` }
						>
							<${ MediaField }
								value=${ image }
								onChange=${ ( id ) => state.set( imageKey, id ) }
								hint="Wide landscape, around 1024×512. Slides without an image are skipped."
							/>

							<${ Field } label="Links to" id=${ 'hero-link-' + n }>
								<${ LinkInput }
									id=${ 'hero-link-' + n }
									value=${ state.values[ linkKey ] }
									onChange=${ ( value ) => state.set( linkKey, value ) }
								/>
							<//>
						<//>
					`;
				} ) }
			</div>
		<//>
	`;
}
