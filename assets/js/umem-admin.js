/**
 * Unstoppable Memberships — admin scripts.
 *
 * Shows/hides billing-related fields on the level editor based on the
 * selected billing type. Progressive enhancement only: with JS off the
 * server still sanitizes and ignores irrelevant fields.
 *
 * @package Unstoppable_Memberships
 */
( function () {
	'use strict';

	function rowFor( el ) {
		return el ? el.closest( 'tr' ) : null;
	}

	function toggleLevelFields() {
		var type = document.getElementById( 'umem_billing_type' );
		if ( ! type ) {
			return;
		}

		var isPaid      = 'free' !== type.value;
		var isRecurring = 'recurring' === type.value;

		var priceRow    = rowFor( document.getElementById( 'umem_price' ) );
		var intervalSel = document.querySelector( '[name="umem_level[billing_interval]"]' );
		var intervalRow = rowFor( intervalSel );
		var trialRow    = rowFor( document.getElementById( 'umem_trial' ) );
		var signupRow   = rowFor( document.getElementById( 'umem_signup_fee' ) );
		var graceRow    = rowFor( document.getElementById( 'umem_grace' ) );
		var stripeRow   = rowFor( document.getElementById( 'umem_stripe_price' ) );

		[ priceRow, signupRow ].forEach( function ( row ) {
			if ( row ) {
				row.style.display = isPaid ? '' : 'none';
			}
		} );

		[ intervalRow, trialRow, graceRow, stripeRow ].forEach( function ( row ) {
			if ( row ) {
				row.style.display = isRecurring ? '' : 'none';
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var type = document.getElementById( 'umem_billing_type' );
		if ( type ) {
			type.addEventListener( 'change', toggleLevelFields );
			toggleLevelFields();
		}

		// Auto-fill slug from name if slug is left blank.
		var name = document.getElementById( 'umem_name' );
		var slug = document.getElementById( 'umem_slug' );
		if ( name && slug ) {
			name.addEventListener( 'blur', function () {
				if ( '' === slug.value.trim() ) {
					slug.value = name.value
						.toLowerCase()
						.replace( /[^a-z0-9]+/g, '-' )
						.replace( /^-+|-+$/g, '' );
				}
			} );
		}
	} );
}() );

/* Add Member screen: user-mode toggle + user search autocomplete. */
( function ( $ ) {
	'use strict';
	if ( ! $ ) {
		return;
	}
	$( function () {
		var $modeRadios = $( 'input[name="umem_member[user_mode]"]' );
		if ( $modeRadios.length ) {
			var toggleMode = function () {
				var mode = $( 'input[name="umem_member[user_mode]"]:checked' ).val();
				$( '.umem-existing-user' ).toggle( 'existing' === mode );
				$( '.umem-new-user' ).toggle( 'new' === mode );
			};
			$modeRadios.on( 'change', toggleMode );
			toggleMode();
		}

		var cfg      = window.umemAdmin || {};
		var $search  = $( '#umem_user_search' );
		var $hidden  = $( '#umem_user_id' );
		var $picked  = $( '#umem_user_selected' );

		if ( $search.length && $.fn.autocomplete && cfg.ajaxUrl ) {
			$search.autocomplete( {
				minLength: 2,
				delay: 250,
				source: function ( request, response ) {
					$.getJSON( cfg.ajaxUrl, {
						action: 'umem_search_users',
						nonce: cfg.userSearchNonce,
						term: request.term
					} ).done( function ( res ) {
						response( ( res && res.success && res.data ) ? res.data : [] );
					} ).fail( function () {
						response( [] );
					} );
				},
				focus: function () {
					return false;
				},
				select: function ( event, ui ) {
					$hidden.val( ui.item.id );
					$search.val( ui.item.value );
					if ( $picked.length ) {
						$picked.text( ( cfg.selectedLabel || 'Selected:' ) + ' ' + ui.item.label );
					}
					return false;
				}
			} );

			$search.on( 'input', function () {
				$hidden.val( 0 );
				$picked.text( '' );
			} );
		}
	} );
}( window.jQuery ) );
