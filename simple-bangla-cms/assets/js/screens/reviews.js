/*
 * Product reviews.
 *
 * A moderation queue rather than an editor: the useful actions on a review someone else wrote are
 * approve, unapprove, mark as spam and delete. The text itself is editable through WooCommerce's
 * API, but a shop rewriting its customers' reviews is not a feature worth building a form for.
 *
 * "Pending" is the landing filter, not "all". The only reason to open this screen is that
 * something is waiting; a list that opens on three hundred approved reviews buries the two that
 * need an answer.
 */

import {
	html,
	useState,
	useEffect,
	useCallback,
	Icon,
	Badge,
	Select,
	Confirm,
	Pagination,
	EmptyState,
	ErrorBox,
	toast,
} from '../ui.js';
import { api, apiList } from '../api.js';
import { href, onLinkClick } from '../router.js';

const PER_PAGE = 20;

const STATUSES = [
	{ value: 'hold', label: 'Pending' },
	{ value: 'approved', label: 'Approved' },
	{ value: 'spam', label: 'Spam' },
	{ value: 'all', label: 'All' },
];

export function Reviews() {
	const [ rows, setRows ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ total, setTotal ] = useState( 0 );
	const [ pages, setPages ] = useState( 1 );
	const [ status, setStatus ] = useState( 'hold' );
	const [ busy, setBusy ] = useState( true );
	const [ error, setError ] = useState( null );

	const [ deleting, setDeleting ] = useState( null );
	const [ working, setWorking ] = useState( 0 );

	// Reviews carry a product_id and no product name, and one product usually owns several of the
	// reviews on a page — so names are fetched once per distinct product, not once per row.
	const [ names, setNames ] = useState( {} );

	const load = useCallback( async () => {
		setBusy( true );
		setError( null );

		try {
			const result = await apiList( 'wc/v3/products/reviews', {
				params: { page, per_page: PER_PAGE, status, orderby: 'date_gmt', order: 'desc' },
			} );

			setRows( result.items );
			setTotal( result.total );
			setPages( result.pages );

			const missing = [ ...new Set( result.items.map( ( r ) => r.product_id ) ) ].filter(
				( id ) => id && ! names[ id ]
			);

			if ( missing.length ) {
				const products = await apiList( 'wc/v3/products', {
					params: { include: missing.join( ',' ), per_page: missing.length, _fields: 'id,name' },
				} );

				const found = {};
				products.items.forEach( ( p ) => {
					found[ p.id ] = p.name;
				} );
				setNames( ( current ) => ( { ...current, ...found } ) );
			}
		} catch ( e ) {
			setError( e );
		} finally {
			setBusy( false );
		}
	}, [ page, status, names ] );

	// Deliberately keyed on the filters alone. `load` also closes over `names`, which it fills in
	// itself as products resolve — depending on it would re-run the effect on its own result.
	useEffect( () => {
		load();
	}, [ page, status ] );

	const moderate = async ( review, next ) => {
		setWorking( review.id );

		try {
			await api( 'wc/v3/products/reviews/' + review.id, { method: 'POST', body: { status: next } } );

			// The row no longer belongs in a filtered list, so drop it rather than leaving a row
			// whose badge disagrees with the filter above it.
			if ( status !== 'all' && next !== status ) {
				setRows( ( current ) => current.filter( ( r ) => r.id !== review.id ) );
				setTotal( ( current ) => Math.max( 0, current - 1 ) );
			} else {
				setRows( ( current ) => current.map( ( r ) => ( r.id === review.id ? { ...r, status: next } : r ) ) );
			}

			toast( next === 'approved' ? 'Review approved' : next === 'spam' ? 'Marked as spam' : 'Review unapproved' );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setWorking( 0 );
		}
	};

	const remove = async () => {
		setWorking( deleting.id );

		try {
			await api( 'wc/v3/products/reviews/' + deleting.id + '?force=true', { method: 'DELETE' } );
			setRows( ( current ) => current.filter( ( r ) => r.id !== deleting.id ) );
			setTotal( ( current ) => Math.max( 0, current - 1 ) );
			setDeleting( null );
			toast( 'Review deleted' );
		} catch ( e ) {
			toast( e.message, 'bad' );
		} finally {
			setWorking( 0 );
		}
	};

	return html`
		<div class="sb-page">
			<div class="sb-page__header">
				<div>
					<h1 class="sb-page__title">Reviews</h1>
					<p class="sb-page__lead">
						${ total } ${ status === 'hold' ? 'waiting for a decision' : 'in this view' }.
					</p>
				</div>
			</div>

			<div class="sb-toolbar">
				<${ Select }
					value=${ status }
					options=${ STATUSES }
					onChange=${ ( value ) => {
						setStatus( value );
						setPage( 1 );
					} }
				/>
			</div>

			${ error ? html`<${ ErrorBox } error=${ error } onRetry=${ load } />` : null }

			${ busy && ! rows.length
				? html`<div class="sb-media-loading"><span class="sb-spinner"></span></div>`
				: rows.length
				? html`
						<div class="sb-reviews">
							${ rows.map(
								( review ) => html`
									<${ ReviewCard }
										key=${ review.id }
										review=${ review }
										product=${ names[ review.product_id ] }
										busy=${ working === review.id }
										onModerate=${ moderate }
										onDelete=${ () => setDeleting( review ) }
									/>
								`
							) }
						</div>
				  `
				: html`<${ EmptyState }
						title=${ status === 'hold' ? 'Nothing waiting' : 'No reviews here' }
						body=${ status === 'hold'
							? 'Every review has been dealt with.'
							: 'Try a different filter.' }
				  />` }

			<${ Pagination } page=${ page } pages=${ pages } total=${ total } noun="reviews" onPage=${ setPage } />

			${ deleting
				? html`<${ Confirm }
						title="Delete this review?"
						body=${ 'The review by ' + deleting.reviewer + ' is removed permanently.' }
						confirmLabel="Delete permanently"
						busy=${ !! working }
						onConfirm=${ remove }
						onClose=${ () => setDeleting( null ) }
				  />`
				: null }
		</div>
	`;
}

function ReviewCard( { review, product, busy, onModerate, onDelete } ) {
	const path = '/products/' + review.product_id;

	return html`
		<article class="sb-review">
			<header class="sb-review__head">
				<div>
					<p class="sb-review__who">
						<strong>${ review.reviewer }</strong>
						${ review.verified ? html` <${ Badge } tone="ok">Verified buyer<//>` : null }
						<${ StatusBadge } status=${ review.status } />
					</p>
					<p class="sb-review__meta">
						<${ Stars } rating=${ review.rating } />
						${ ' on ' }
						<a href=${ href( path ) } onClick=${ ( e ) => onLinkClick( e, path ) }>
							${ product || 'product #' + review.product_id }
						</a>
						${ ' · ' + String( review.date_created || '' ).slice( 0, 10 ) }
					</p>
				</div>
			</header>

			<!--
				Rendered as text, not markup. WooCommerce hands this back with WordPress's comment
				filters already applied, but it is still the one field on this screen written by a
				stranger, and a moderation queue is precisely where an unreviewed review should not
				be executing anything. The tags it carries are only paragraph wrappers; CSS keeps
				the line breaks.
			-->
			<div class="sb-review__body">${ toText( review.review ) }</div>

			<footer class="sb-review__tools">
				${ review.status !== 'approved'
					? html`<button
							class="sb-btn sb-btn--primary sb-btn--sm"
							disabled=${ busy }
							onClick=${ () => onModerate( review, 'approved' ) }
					  >
							Approve
					  </button>`
					: html`<button
							class="sb-btn sb-btn--ghost sb-btn--sm"
							disabled=${ busy }
							onClick=${ () => onModerate( review, 'hold' ) }
					  >
							Unapprove
					  </button>` }

				${ review.status !== 'spam'
					? html`<button
							class="sb-btn sb-btn--ghost sb-btn--sm"
							disabled=${ busy }
							onClick=${ () => onModerate( review, 'spam' ) }
					  >
							Spam
					  </button>`
					: null }

				<button class="sb-btn sb-btn--ghost sb-btn--sm" disabled=${ busy } onClick=${ onDelete }>
					Delete
				</button>
			</footer>
		</article>
	`;
}

function StatusBadge( { status } ) {
	const map = {
		approved: [ 'ok', 'Approved' ],
		hold: [ 'warn', 'Pending' ],
		spam: [ 'bad', 'Spam' ],
		trash: [ 'bad', 'Trashed' ],
	};

	const [ tone, label ] = map[ status ] || [ 'muted', status ];

	return html` <${ Badge } tone=${ tone }>${ label }<//>`;
}

/**
 * The words out of WooCommerce's paragraph-wrapped review body, with its line breaks kept.
 *
 * Parsed with DOMParser rather than by assigning innerHTML to a detached div. A parsed document is
 * inert — no script runs and no `<img src>` or `onerror` is fetched — whereas innerHTML on a
 * detached element still kicks off resource loads in some browsers. For the one field on this
 * screen that a stranger wrote, that difference is the whole point.
 */
function toText( value ) {
	if ( ! value ) {
		return '';
	}

	const spaced = String( value )
		.replace( /<\/p>\s*<p[^>]*>/gi, '\n\n' )
		.replace( /<br\s*\/?>/gi, '\n' );

	return new DOMParser().parseFromString( spaced, 'text/html' ).body.textContent.trim();
}

function Stars( { rating } ) {
	const score = Number( rating || 0 );

	return html`
		<span class="sb-stars" title=${ score + ' out of 5' }>
			<span class="sb-sr">${ score } out of 5</span>
			${ [ 1, 2, 3, 4, 5 ].map(
				( n ) => html`<span key=${ n } class=${ n <= score ? 'is-on' : '' }><${ Icon } name="star" size=${ 14 } /></span>`
			) }
		</span>
	`;
}
