/*
 * Coupons.
 *
 * WooCommerce's own `wc/v3/coupons`, with the editor in a dialog rather than on its own route. A
 * coupon is a dozen fields with no images, no variations and no description worth scrolling — the
 * product editor earns a full screen and this does not.
 *
 * Deleting is permanent here, unlike a product. WooCommerce would trash a coupon, but the CMS has
 * no trash view to retrieve it from, so a trashed coupon would simply vanish with no way back
 * except wp-admin. Better to say plainly that it is gone.
 */

import {
	html,
	useState,
	useEffect,
	useCallback,
	Badge,
	Field,
	Select,
	Switch,
	Modal,
	Confirm,
	Pagination,
	EmptyState,
	ErrorBox,
	toast,
} from '../ui.js';
import { api, apiList, money } from '../api.js';

const PER_PAGE = 20;

const TYPES = [
	{ value: 'percent', label: 'Percentage discount' },
	{ value: 'fixed_cart', label: 'Fixed cart discount' },
	{ value: 'fixed_product', label: 'Fixed product discount' },
];

const BLANK = {
	code: '',
	discount_type: 'percent',
	amount: '',
	description: '',
	date_expires: '',
	minimum_amount: '',
	usage_limit: null,
	usage_limit_per_user: null,
	individual_use: false,
	free_shipping: false,
};

export function Coupons() {
	const [ rows, setRows ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ total, setTotal ] = useState( 0 );
	const [ pages, setPages ] = useState( 1 );
	const [ search, setSearch ] = useState( '' );
	const [ busy, setBusy ] = useState( true );
	const [ error, setError ] = useState( null );

	const [ editing, setEditing ] = useState( null );
	const [ deleting, setDeleting ] = useState( null );
	const [ working, setWorking ] = useState( false );

	const load = useCallback( async () => {
		setBusy( true );
		setError( null );

		try {
			const result = await apiList( 'wc/v3/coupons', {
				params: { page, per_page: PER_PAGE, search, orderby: 'date', order: 'desc' },
			} );

			setRows( result.items );
			setTotal( result.total );
			setPages( result.pages );
		} catch ( e ) {
			setError( e );
		} finally {
			setBusy( false );
		}
	}, [ page, search ] );

	useEffect( () => {
		const timer = setTimeout( load, search ? 300 : 0 );
		return () => clearTimeout( timer );
	}, [ load, search ] );

	const save = async ( draft ) => {
		if ( ! draft.code.trim() ) {
			toast( 'Give the coupon a code before saving.', 'bad' );
			return;
		}

		setWorking( true );

		try {
			const payload = {
				code: draft.code.trim(),
				discount_type: draft.discount_type,
				amount: String( draft.amount || '0' ),
				description: draft.description,
				// WooCommerce wants an ISO date or an empty string; a blank input means "never".
				date_expires: draft.date_expires || null,
				minimum_amount: String( draft.minimum_amount || '' ),
				usage_limit: draft.usage_limit === '' ? null : draft.usage_limit,
				usage_limit_per_user: draft.usage_limit_per_user === '' ? null : draft.usage_limit_per_user,
				individual_use: !! draft.individual_use,
				free_shipping: !! draft.free_shipping,
			};

			if ( draft.id ) {
				await api( 'wc/v3/coupons/' + draft.id, { method: 'POST', body: payload } );
			} else {
				await api( 'wc/v3/coupons', { method: 'POST', body: payload } );
			}

			toast( draft.id ? 'Coupon saved' : 'Coupon created' );
			setEditing( null );
			load();
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setWorking( false );
		}
	};

	const remove = async () => {
		setWorking( true );

		try {
			await api( 'wc/v3/coupons/' + deleting.id + '?force=true', { method: 'DELETE' } );
			toast( 'Coupon deleted' );
			setDeleting( null );
			load();
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setWorking( false );
		}
	};

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Coupons</h1>
					<p class="sb-page__lead">${ total } coupon${ total === 1 ? '' : 's' }.</p>
				</div>
				<button class="sb-btn sb-btn--primary" onClick=${ () => setEditing( { ...BLANK } ) }>
					Add coupon
				</button>
			</div>

			<div class="sb-toolbar">
				<input
					class="sb-input sb-toolbar__search"
					type="search"
					placeholder="Search by code"
					value=${ search }
					onInput=${ ( e ) => {
						setSearch( e.target.value );
						setPage( 1 );
					} }
				/>
			</div>

			${ error ? html`<${ ErrorBox } error=${ error } onRetry=${ load } />` : null }

			${ busy && ! rows.length
				? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
				: rows.length
				? html`
						<div class=${ 'sb-table-wrap' + ( busy ? ' is-busy' : '' ) }>
							<table class="sb-table">
								<thead>
									<tr>
										<th>Code</th>
										<th>Discount</th>
										<th>Used</th>
										<th>Expires</th>
										<th class="sb-table__actions-col"><span class="sb-sr">Actions</span></th>
									</tr>
								</thead>
								<tbody>
									${ rows.map(
										( coupon ) => html`
											<tr key=${ coupon.id }>
												<td>
													<button
														class="sb-table__name sb-linkbtn"
														onClick=${ () => setEditing( toDraft( coupon ) ) }
													>
														${ coupon.code }
													</button>
													${ coupon.description
														? html`<div class="sb-table__sub">${ coupon.description }</div>`
														: null }
												</td>
												<td><${ Discount } coupon=${ coupon } /></td>
												<td><${ Usage } coupon=${ coupon } /></td>
												<td><${ Expiry } coupon=${ coupon } /></td>
												<td>
													<button
														class="sb-btn sb-btn--ghost sb-btn--sm"
														onClick=${ () => setDeleting( coupon ) }
													>
														Delete
													</button>
												</td>
											</tr>
										`
									) }
								</tbody>
							</table>
						</div>
				  `
				: html`<${ EmptyState }
						title="No coupons"
						body=${ search ? 'Nothing matches that search.' : 'Create one to run a discount.' }
				  />` }

			<${ Pagination } page=${ page } pages=${ pages } total=${ total } noun="coupons" onPage=${ setPage } />

			${ editing
				? html`<${ CouponEditor }
						coupon=${ editing }
						busy=${ working }
						onSave=${ save }
						onClose=${ () => setEditing( null ) }
				  />`
				: null }

			${ deleting
				? html`<${ Confirm }
						title=${ 'Delete ' + deleting.code + '?' }
						body="This removes the coupon permanently. Orders that already used it keep their discount."
						confirmLabel="Delete permanently"
						busy=${ working }
						onConfirm=${ remove }
						onClose=${ () => setDeleting( null ) }
				  />`
				: null }
		</div>
	`;
}

/** WooCommerce's coupon shape, reduced to what the dialog edits. */
function toDraft( coupon ) {
	return {
		id: coupon.id,
		code: coupon.code,
		discount_type: coupon.discount_type,
		amount: coupon.amount,
		description: coupon.description || '',
		// The API sends "2026-09-01T00:00:00"; the date input wants the day only.
		date_expires: coupon.date_expires ? String( coupon.date_expires ).slice( 0, 10 ) : '',
		minimum_amount: coupon.minimum_amount && coupon.minimum_amount !== '0' ? coupon.minimum_amount : '',
		usage_limit: coupon.usage_limit === null ? '' : coupon.usage_limit,
		usage_limit_per_user: coupon.usage_limit_per_user === null ? '' : coupon.usage_limit_per_user,
		individual_use: !! coupon.individual_use,
		free_shipping: !! coupon.free_shipping,
	};
}

function Discount( { coupon } ) {
	const amount = coupon.discount_type === 'percent'
		? Number( coupon.amount || 0 ) + '%'
		: money( coupon.amount );

	const labels = {
		percent: 'off the order',
		fixed_cart: 'off the order',
		fixed_product: 'off each item',
	};

	return html`
		<span class="sb-price"><strong>${ amount }</strong></span>
		<div class="sb-table__sub">${ labels[ coupon.discount_type ] || coupon.discount_type }</div>
	`;
}

function Usage( { coupon } ) {
	const used = Number( coupon.usage_count || 0 );
	const limit = coupon.usage_limit;

	if ( ! limit ) {
		return html`<span class="sb-table__sub">${ used } · no limit</span>`;
	}

	return html`<${ Badge } tone=${ used >= limit ? 'bad' : used >= limit * 0.8 ? 'warn' : 'ok' }
		>${ used } of ${ limit }<//
	>`;
}

function Expiry( { coupon } ) {
	if ( ! coupon.date_expires ) {
		return html`<span class="sb-table__sub">Never</span>`;
	}

	const day = String( coupon.date_expires ).slice( 0, 10 );
	// Compared as plain days. WooCommerce stores this without a zone, and turning it into a moment
	// in time would shift the expiry date by six hours for Dhaka.
	const today = new Date().toISOString().slice( 0, 10 );
	const past = day < today;

	return html`<${ Badge } tone=${ past ? 'bad' : 'muted' }>${ past ? 'Expired ' : '' }${ day }<//>`;
}

function CouponEditor( { coupon, busy, onSave, onClose } ) {
	const [ draft, setDraft ] = useState( coupon );
	const set = ( patch ) => setDraft( ( current ) => ( { ...current, ...patch } ) );

	return html`
		<${ Modal }
			wide
			title=${ draft.id ? 'Edit ' + coupon.code : 'New coupon' }
			onClose=${ onClose }
			footer=${ html`
				<button class="sb-btn sb-btn--ghost" onClick=${ onClose }>Cancel</button>
				<button class="sb-btn sb-btn--primary" disabled=${ busy } onClick=${ () => onSave( draft ) }>
					${ busy ? 'Saving…' : 'Save' }
				</button>
			` }
		>
			<${ Field } label="Code" id="cp-code" hint="What the customer types at checkout. Not case sensitive.">
				<input
					class="sb-input"
					id="cp-code"
					autocomplete="off"
					value=${ draft.code }
					onInput=${ ( e ) => set( { code: e.target.value } ) }
				/>
			<//>

			<div class="sb-grid-cards">
				<${ Field } label="Type" id="cp-type">
					<${ Select }
						id="cp-type"
						value=${ draft.discount_type }
						options=${ TYPES }
						onChange=${ ( value ) => set( { discount_type: value } ) }
					/>
				<//>

				<${ Field }
					label=${ draft.discount_type === 'percent' ? 'Percentage' : 'Amount' }
					id="cp-amount"
				>
					<input
						class="sb-input"
						id="cp-amount"
						type="number"
						inputmode="decimal"
						min="0"
						step="0.01"
						value=${ draft.amount }
						onInput=${ ( e ) => set( { amount: e.target.value } ) }
					/>
				<//>
			</div>

			<${ Field } label="Description" id="cp-desc" hint="For your own reference. Customers do not see it.">
				<input
					class="sb-input"
					id="cp-desc"
					value=${ draft.description }
					onInput=${ ( e ) => set( { description: e.target.value } ) }
				/>
			<//>

			<div class="sb-grid-cards">
				<${ Field } label="Expires on" id="cp-expires" hint="Leave blank for no expiry.">
					<input
						class="sb-input"
						id="cp-expires"
						type="date"
						value=${ draft.date_expires }
						onInput=${ ( e ) => set( { date_expires: e.target.value } ) }
					/>
				<//>

				<${ Field } label="Minimum spend" id="cp-min" hint="Leave blank for no minimum.">
					<input
						class="sb-input"
						id="cp-min"
						type="number"
						inputmode="decimal"
						min="0"
						value=${ draft.minimum_amount }
						onInput=${ ( e ) => set( { minimum_amount: e.target.value } ) }
					/>
				<//>

				<${ Field } label="Total uses allowed" id="cp-limit" hint="Leave blank for unlimited.">
					<input
						class="sb-input"
						id="cp-limit"
						type="number"
						inputmode="numeric"
						min="0"
						value=${ draft.usage_limit }
						onInput=${ ( e ) => set( { usage_limit: e.target.value === '' ? '' : Number( e.target.value ) } ) }
					/>
				<//>

				<${ Field } label="Uses per customer" id="cp-peruser" hint="Leave blank for unlimited.">
					<input
						class="sb-input"
						id="cp-peruser"
						type="number"
						inputmode="numeric"
						min="0"
						value=${ draft.usage_limit_per_user }
						onInput=${ ( e ) =>
							set( { usage_limit_per_user: e.target.value === '' ? '' : Number( e.target.value ) } ) }
					/>
				<//>
			</div>

			<${ Switch }
				checked=${ draft.individual_use }
				label="Cannot be combined with other coupons"
				onChange=${ ( value ) => set( { individual_use: value } ) }
			/>

			<${ Switch }
				checked=${ draft.free_shipping }
				label="Also gives free delivery"
				hint="Only has an effect if a delivery rate is set to allow it."
				onChange=${ ( value ) => set( { free_shipping: value } ) }
			/>
		<//>
	`;
}
