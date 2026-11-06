/**
 * Unstoppable Memberships — frontend scripts.
 *
 * Minimal by design. The `umemPublic` object (ajaxUrl, nonce) is localized
 * from PHP and used by checkout / account AJAX added in a later pass.
 *
 * @package Unstoppable_Memberships
 */
( function () {
	'use strict';

	// Confirm destructive subscription actions (cancel) before they post.
	document.addEventListener( 'click', function ( e ) {
		var trigger = e.target.closest( '[data-umem-confirm]' );
		if ( ! trigger ) {
			return;
		}
		var message = trigger.getAttribute( 'data-umem-confirm' );
		if ( message && ! window.confirm( message ) ) {
			e.preventDefault();
		}
	} );
}() );
