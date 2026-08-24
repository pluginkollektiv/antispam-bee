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
					'[data-antispam-bee-dismiss], [data-antispam-bee-dismiss-link]'
				);

				if ( ! dismiss ) {
					return;
				}

				event.preventDefault();

				const href = dismiss.dataset.antispamBeeDismissLink;

				wp.ajax.post( antispamBeePreReleaseNotice.action, {
					_ajax_nonce: antispamBeePreReleaseNotice.nonce,
				} )
					.done( () => {
						notice.remove();
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
