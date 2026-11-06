/**
 * Unstoppable Memberships — content-restriction metabox.
 *
 * Reveals the "Allowed levels" list when "Specific membership levels" is
 * selected in the Membership Access box. Loaded on the post/page editor.
 *
 * @package Unstoppable_Memberships
 */
( function () {
	'use strict';

	function init() {
		var sel  = document.getElementById( 'umem_access' );
		var pick = document.querySelector( '.umem-level-picker' );
		if ( ! sel || ! pick ) {
			return;
		}
		function sync() {
			if ( 'levels' === sel.value ) {
				pick.classList.remove( 'umem-hidden' );
			} else {
				pick.classList.add( 'umem-hidden' );
			}
		}
		sel.addEventListener( 'change', sync );
		sync();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
