/*
 * Staff accounts.
 *
 * Every guard that matters here is enforced on the server in `inc/rest-staff.php` — you cannot
 * change your own role, you cannot delete yourself, the last administrator cannot be removed or
 * demoted, and only an administrator can hand out the administrator role. This screen mirrors those
 * rules in what it offers, because a disabled control explains itself better than an error does, but
 * the disabling is a courtesy and the endpoint is the control.
 *
 * No password is set here. WordPress emails a new account a link to choose their own, which keeps it
 * out of this screen, out of the response, and out of whatever the owner would otherwise have typed
 * it into to pass it along.
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
			toast( created.name + ' added. They have been emailed a link to set a password.' );
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

function StaffEditor( { roles, busy, onSave, onClose } ) {
	const [ draft, setDraft ] = useState( {
		username: '',
		email: '',
		name: '',
		role: roles.length ? roles[ roles.length - 1 ].value : 'customer',
	} );

	const set = ( patch ) => setDraft( ( current ) => ( { ...current, ...patch } ) );
	const valid = draft.username.trim() && draft.email.trim() && draft.role;

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

			<${ Field } label="Username" id="st-user" hint="What they sign in with. It cannot be changed later.">
				<input
					class="sb-input"
					id="st-user"
					autocomplete="off"
					value=${ draft.username }
					onInput=${ ( e ) => set( { username: e.target.value } ) }
				/>
			<//>

			<${ Field } label="Email" id="st-email" hint="Where the link to set a password is sent.">
				<input
					class="sb-input"
					id="st-email"
					type="email"
					autocomplete="off"
					value=${ draft.email }
					onInput=${ ( e ) => set( { email: e.target.value } ) }
				/>
			<//>

			<${ Field } label="Role" id="st-role">
				<${ Select } id="st-role" value=${ draft.role } options=${ roles } onChange=${ ( role ) => set( { role } ) } />
			<//>
		<//>
	`;
}
