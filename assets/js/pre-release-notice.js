/**
 * Dismiss the pre-release notice.
 */
( () => {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', () => {
		const notices = document.querySelectorAll( '[data-antispam-bee-pre-release-notice]' );

		for ( const notice of notices ) {
			notice.addEventListener( 'click', ( event ) => {
				const dismiss = event.target.closest(
					'.notice-dismiss, [data-antispam-bee-dismiss]'
				);

				if ( ! dismiss ) {
					return;
				}

				// Core already hides and removes the notice for its own close
				// button, so only block the Dismiss link's default navigation.
				if ( ! dismiss.closest( '.notice-dismiss' ) ) {
					event.preventDefault();
				}

				const href = notice.dataset.antispamBeeDismissLink;

				// The notice may already be gone once core has handled the close
				// button, so only hide it here when the AJAX call finished before
				// core's own removal.
				wp.ajax.post( antispamBeePreReleaseNotice.action, {
					_ajax_nonce: antispamBeePreReleaseNotice.nonce,
				} )
					.done( () => {
						if ( notice.isConnected ) {
							notice.style.display = 'none';
						}
					} )
					.fail( () => {
						if ( href ) {
							window.location.href = href;
						}
					} );
			} );
		}
	} );
} )();
