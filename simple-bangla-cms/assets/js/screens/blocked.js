/*
 * The block list.
 *
 * The enforcement is the theme's — `simple-bangla/inc/blocklist.php` refuses the checkout — and this
 * screen is only its editor. That split is deliberate: a block list enforced from the plugin would
 * stop working the moment the plugin was deactivated, which is the one direction a safety control
 * must never fail in.
 *
 * The whole list is sent on every save rather than one entry at a time. It is a short list edited
 * rarely, and replacing it wholesale means adding, editing and removing are the same request with
 * no half-applied state to reason about.
 *
 * Phone numbers are stored exactly as the owner typed them, so they stay recognisable, and matched
 * on their last ten digits, so `01712345678` and `+8801712345678` are the same person. That
 * normalisation lives in the theme and is not repeated here; two copies of it would eventually
 * disagree, and the disagreement would look like a blocked customer who could still order.
 *
 * The second kind of entry is an IP address, which replaced email on 2026-08-09. An order's own IP
 * is shown on its detail screen precisely so it can be copied into here.
 */

import {
	html,
	useState,
	useEffect,
	Card,
	Field,
	Select,
	Badge,
	Confirm,
	EmptyState,
	ErrorBox,
	toast,
} from '../ui.js';
import { api } from '../api.js';
import { useUnsavedWarning } from '../settings-form.js';

const TYPES = [
	{ value: 'phone', label: 'Phone number' },
	{ value: 'ip', label: 'IP address' },
];

export function Blocked() {
	const [ entries, setEntries ] = useState( null );
	const [ baseline, setBaseline ] = useState( '' );
	const [ error, setError ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ removing, setRemoving ] = useState( null );

	const [ type, setType ] = useState( 'phone' );
	const [ value, setValue ] = useState( '' );
	const [ note, setNote ] = useState( '' );

	const load = () => {
		setError( null );
		setEntries( null );

		api( '/blocklist' )
			.then( ( data ) => {
				setEntries( data.entries );
				setBaseline( JSON.stringify( data.entries ) );
			} )
			.catch( setError );
	};

	useEffect( load, [] );

	const dirty = entries !== null && JSON.stringify( entries ) !== baseline;

	useUnsavedWarning( dirty );

	const save = async ( next ) => {
		setSaving( true );

		try {
			const result = await api( '/blocklist', { method: 'POST', body: { entries: next } } );

			setEntries( result.entries );
			setBaseline( JSON.stringify( result.entries ) );

			// The theme drops anything it cannot use. Saying "saved" over a silently discarded row
			// would only be discovered when the blocked customer ordered again.
			if ( result.rejected ) {
				toast(
					result.rejected +
						( result.rejected === 1 ? ' entry was' : ' entries were' ) +
						' not usable and has been left off the list.',
					'bad'
				);
			} else {
				toast( 'Block list saved' );
			}

			return true;
		} catch ( e ) {
			toast( e.message, 'bad' );
			return false;
		} finally {
			setSaving( false );
		}
	};

	const add = async () => {
		const trimmed = value.trim();

		if ( ! trimmed ) {
			toast( 'Enter a number or an IP address first.', 'bad' );
			return;
		}

		const next = [ ...entries, { type, value: trimmed, note: note.trim(), added: Math.floor( Date.now() / 1000 ) } ];

		if ( await save( next ) ) {
			setValue( '' );
			setNote( '' );
		}
	};

	const remove = async () => {
		const next = entries.filter( ( _, index ) => index !== removing.index );

		if ( await save( next ) ) {
			setRemoving( null );
		}
	};

	if ( error ) {
		return html`<div class="sb-page">
			<h1 class="sb-page__title">Blocked List</h1>
			<${ ErrorBox } error=${ error } onRetry=${ load } />
		</div>`;
	}

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Blocked List</h1>
					<p class="sb-page__lead">
						Phone numbers and IP addresses that cannot place an order. Checked at the checkout,
						before anything is written.
					</p>
				</div>
			</div>

			<div class="sb-form">
				<${ Card } title="Block someone">
					<p class="sb-hint">
						A phone number matches however it is typed — with or without +880, spaces or dashes.
						An IP address must match exactly; you will find each order's on its own screen. Mobile
						networks share addresses between many people, so block an IP only when a number is
						not enough.
					</p>

					<div class="sb-grid-cards">
						<${ Field } label="Type" id="bl-type">
							<${ Select } id="bl-type" value=${ type } options=${ TYPES } onChange=${ setType } />
						<//>

						<${ Field } label=${ type === 'ip' ? 'IP address' : 'Phone number' } id="bl-value">
							<input
								class="sb-input"
								id="bl-value"
								type="text"
								autocomplete="off"
								spellcheck="false"
								placeholder=${ type === 'ip' ? '103.86.220.14' : '01712345678' }
								value=${ value }
								onInput=${ ( e ) => setValue( e.target.value ) }
							/>
						<//>

						<${ Field } label="Reason (optional)" id="bl-note" hint="For your own reference. The customer never sees it.">
							<input
								class="sb-input"
								id="bl-note"
								autocomplete="off"
								placeholder="Refused delivery twice"
								value=${ note }
								onInput=${ ( e ) => setNote( e.target.value ) }
							/>
						<//>
					</div>

					<button class="sb-btn sb-btn--primary" disabled=${ saving || ! entries } onClick=${ add }>
						${ saving ? 'Saving…' : 'Add to the list' }
					</button>
				<//>

				<${ Card } title=${ 'On the list' + ( entries ? ' (' + entries.length + ')' : '' ) }>
					${ ! entries
						? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
						: ! entries.length
						? html`<${ EmptyState }
								title="Nobody is blocked"
								body="Everyone can order. Add a number above when you need to stop one."
						  />`
						: html`
								<div class="sb-table-wrap">
									<table class="sb-table">
										<thead>
											<tr>
												<th>Blocked</th>
												<th>Reason</th>
												<th>Added</th>
												<th class="sb-table__actions-col"><span class="sb-sr">Actions</span></th>
											</tr>
										</thead>
										<tbody>
											${ entries.map(
												( entry, index ) => html`
													<tr key=${ entry.type + entry.value }>
														<td>
															<span class="sb-table__name">${ entry.value }</span>
															<div class="sb-table__sub">
																<${ Badge }>${ entry.type === 'ip' ? 'IP address' : 'Phone' }<//>
															</div>
														</td>
														<td class="sb-table__sub">${ entry.note || '—' }</td>
														<td class="sb-table__sub">
															${ entry.added
																? new Date( entry.added * 1000 ).toISOString().slice( 0, 10 )
																: '—' }
														</td>
														<td>
															<button
																class="sb-btn sb-btn--ghost sb-btn--sm"
																disabled=${ saving }
																onClick=${ () => setRemoving( { entry, index } ) }
															>
																Unblock
															</button>
														</td>
													</tr>
												`
											) }
										</tbody>
									</table>
								</div>
						  ` }
				<//>
			</div>

			${ removing
				? html`<${ Confirm }
						title=${ 'Unblock ' + removing.entry.value + '?' }
						body="They will be able to place orders again straight away."
						confirmLabel="Unblock"
						busy=${ saving }
						onConfirm=${ remove }
						onClose=${ () => setRemoving( null ) }
				  />`
				: null }
		</div>
	`;
}
