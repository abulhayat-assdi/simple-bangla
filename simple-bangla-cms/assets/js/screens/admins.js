/*
 * Staff accounts.
 *
 * Every guard that matters here is enforced on the server in `inc/rest-staff.php` — you cannot
 * change your own role, you cannot delete yourself, the last administrator cannot be removed or
 * demoted, and only an administrator can hand out the administrator role. This screen mirrors those
 * rules in what it offers, because a disabled control explains itself better than an error does, but
 * the disabling is a courtesy and the endpoint is the control.
 *
 * The password is set here, at the owner's request. It is deliberately not masked: it is being typed
 * once in order to be handed to someone in the same room, and a masked field would only add the
 * mistyped password that a "confirm" box then exists to catch. The endpoint never echoes it back.
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
	toast,
} from '../ui.js';
import { api } from '../api.js';

export function Admins() {
	const [ data, setData ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ busy, setBusy ] = useState( false );

	const [ adding, setAdding ] = useState( false );
	const [ removing, setRemoving ] = useState( null );

	const load = () => {
		setError( null );
		setData( null );

		api( '/staff' ).then( setData ).catch( setError );
	};

	useEffect( load, [] );

	const roleOptions = data
		? Object.entries( data.roles )
				.filter( ( [ slug ] ) => slug !== 'administrator' || data.can_grant_admin )
				.map( ( [ slug, label ] ) => ( { value: slug, label } ) )
		: [];

	const changeRole = async ( user, role ) => {
		setBusy( true );

		try {
			const saved = await api( '/staff/' + user.id, { method: 'POST', body: { role } } );

			setData( ( current ) => ( {
				...current,
				staff: current.staff.map( ( u ) => ( u.id === saved.id ? saved : u ) ),
			} ) );

			toast( saved.name + ' is now ' + saved.role_label );
		} catch ( e ) {
			toast( e.message, 'bad' );
			// The select has already moved to the rejected value, so put the truth back on screen.
			load();
		} finally {
			setBusy( false );
		}
	};

	const create = async ( draft ) => {
		setBusy( true );

		try {
			const created = await api( '/staff', { method: 'POST', body: draft } );

			setData( ( current ) => ( { ...current, staff: [ ...current.staff, created ] } ) );
			setAdding( false );
			toast( created.name + ' added. They can sign in with ' + created.email + ' and the password you set.' );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	const remove = async () => {
		setBusy( true );

		try {
			await api( '/staff/' + removing.id, { method: 'DELETE' } );

			setData( ( current ) => ( {
				...current,
				staff: current.staff.filter( ( u ) => u.id !== removing.id ),
			} ) );

			setRemoving( null );
			toast( 'Account removed' );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setBusy( false );
		}
	};

	if ( error ) {
		return html`<div class="sb-page">
			<h1 class="sb-page__title">Manage Admins</h1>
			<${ ErrorBox } error=${ error } onRetry=${ load } />
		</div>`;
	}

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Manage Admins</h1>
					<p class="sb-page__lead">Who can sign in and manage the shop.</p>
				</div>
				<div class="sb-row sb-page__actions">
					<button class="sb-btn sb-btn--primary" disabled=${ ! data } onClick=${ () => setAdding( true ) }>
						Add staff
					</button>
				</div>
			</div>

			${ ! data
				? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
				: ! data.staff.length
				? html`<${ EmptyState } title="No staff accounts" body="Add one to let someone else help run the shop." />`
				: html`
						<${ Card }>
							<div class="sb-table-wrap">
								<table class="sb-table">
									<thead>
										<tr>
											<th>Person</th>
											<th>Role</th>
											<th class="sb-table__actions-col"><span class="sb-sr">Actions</span></th>
										</tr>
									</thead>
									<tbody>
										${ data.staff.map(
											( user ) => html`
												<tr key=${ user.id }>
													<td>
														<span class="sb-table__name">
															${ user.name }
															${ user.is_self ? html` <${ Badge } tone="ok">You<//>` : null }
														</span>
														<div class="sb-table__sub">${ user.email }</div>
													</td>
													<td>
														${ user.is_self || user.unmanaged
															? html`<span class="sb-table__sub">${ user.role_label }</span>`
															: html`<${ Select }
																	value=${ user.role }
																	options=${ roleOptions }
																	disabled=${ busy }
																	onChange=${ ( role ) => changeRole( user, role ) }
															  />` }
														${ user.is_self
															? html`<div class="sb-table__sub">
																	Ask another administrator to change your own role.
															  </div>`
															: null }
														${ user.unmanaged
															? html`<div class="sb-table__sub">
																	Managed in WordPress — this screen does not change this role.
															  </div>`
															: null }
													</td>
													<td>
														${ user.is_self
															? null
															: html`<button
																	class="sb-btn sb-btn--ghost sb-btn--sm"
																	disabled=${ busy }
																	onClick=${ () => setRemoving( user ) }
															  >
																	Remove
															  </button>` }
													</td>
												</tr>
											`
										) }
									</tbody>
								</table>
							</div>

							${ data.admin_count === 1
								? html`<p class="sb-hint">
										There is one administrator. Add a second before removing or demoting it, or
										nobody will be able to reach the shop's settings.
								  </p>`
								: null }
						<//>
				  ` }

			${ adding
				? html`<${ StaffEditor }
						roles=${ roleOptions }
						busy=${ busy }
						onSave=${ create }
						onClose=${ () => setAdding( false ) }
				  />`
				: null }

			${ removing
				? html`<${ Confirm }
						title=${ 'Remove ' + removing.name + '?' }
						body="They lose access immediately. Orders they handled are not affected."
						confirmLabel="Remove"
						busy=${ busy }
						onConfirm=${ remove }
						onClose=${ () => setRemoving( null ) }
				  />`
				: null }
		</div>
	`;
}

/** What the endpoint refuses below, mirrored so the message arrives while the field is in view. */
const PASSWORD_MIN = 8;

function StaffEditor( { roles, busy, onSave, onClose } ) {
	const [ draft, setDraft ] = useState( {
		email: '',
		password: '',
		name: '',
		role: roles.length ? roles[ roles.length - 1 ].value : 'customer',
	} );

	// Typed once and handed over, so it is shown rather than masked. Hiding it here would protect
	// nothing — the owner is looking at their own screen and about to read it out loud anyway — and
	// would only invite the mistyped password that a "confirm" field then exists to catch.
	const set = ( patch ) => setDraft( ( current ) => ( { ...current, ...patch } ) );

	const emailOk = /^\S+@\S+\.\S+$/.test( draft.email.trim() );
	const passwordOk = draft.password.length >= PASSWORD_MIN;
	const valid = emailOk && passwordOk && draft.role;

	return html`
		<${ Modal }
			title="Add staff"
			onClose=${ onClose }
			footer=${ html`
				<button class="sb-btn sb-btn--ghost" onClick=${ onClose }>Cancel</button>
				<button class="sb-btn sb-btn--primary" disabled=${ busy || ! valid } onClick=${ () => onSave( draft ) }>
					${ busy ? 'Adding…' : 'Add' }
				</button>
			` }
		>
			<${ Field } label="Name" id="st-name">
				<input class="sb-input" id="st-name" value=${ draft.name } onInput=${ ( e ) => set( { name: e.target.value } ) } />
			<//>

			<${ Field } label="Email" id="st-email" hint="This is what they sign in with.">
				<input
					class=${ 'sb-input' + ( draft.email && ! emailOk ? ' is-invalid' : '' ) }
					id="st-email"
					type="email"
					autocomplete="off"
					value=${ draft.email }
					onInput=${ ( e ) => set( { email: e.target.value } ) }
				/>
			<//>

			<${ Field }
				label="Password"
				id="st-pass"
				hint=${ 'At least ' + PASSWORD_MIN + ' characters. Shown as you type so you can pass it on — they can change it later.' }
			>
				<div class="sb-row">
					<input
						class=${ 'sb-input' + ( draft.password && ! passwordOk ? ' is-invalid' : '' ) }
						id="st-pass"
						type="text"
						autocomplete="off"
						spellcheck="false"
						value=${ draft.password }
						onInput=${ ( e ) => set( { password: e.target.value } ) }
					/>
					<button
						class="sb-btn sb-btn--ghost sb-btn--sm"
						type="button"
						onClick=${ () => set( { password: suggestPassword() } ) }
					>
						Suggest
					</button>
				</div>
			<//>

			<${ Field } label="Role" id="st-role">
				<${ Select } id="st-role" value=${ draft.role } options=${ roles } onChange=${ ( role ) => set( { role } ) } />
			<//>
		<//>
	`;
}

/**
 * A password worth suggesting.
 *
 * crypto.getRandomValues rather than Math.random, and an alphabet with no `l`, `1`, `O` or `0` in
 * it — this password is going to be read off a screen and typed into a phone by someone else.
 *
 * @return {string}
 */
function suggestPassword() {
	const alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	const bytes = new Uint32Array( 14 );

	crypto.getRandomValues( bytes );

	return [ ...bytes ].map( ( n ) => alphabet[ n % alphabet.length ] ).join( '' );
}
