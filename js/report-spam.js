( function() {
	var buttons = document.querySelectorAll( '.report-comment-to-asb-button' ),
		dialog = document.querySelector( '.a11y-dialog-container' ),
		messageParagraph = dialog.querySelector( '.report-spam-dialog-message' );
	if ( buttons.length === 0 || ! dialog || ! messageParagraph ) {
		return;
	}

	var reportSpamButton = dialog.querySelector( '.asb-report-spam-button' );
	if ( ! reportSpamButton ) {
		return;
	}

	dialog.addEventListener( 'hide', function() {
		reportSpamButton.removeAttribute( 'disabled' );
		messageParagraph.textContent = '';
	} );

	// Add event listener to buttons in the quick action of comments.
	for ( var i = 0; i < buttons.length; i++ ) {
		var button = buttons[i];
		button.addEventListener( 'click', function() {
			var button = this,
				dataset = button.dataset,
				commentData = {
					author: dataset.author,
					email: dataset.email,
					ip: dataset.ip,
					host: dataset.host,
					url: dataset.url,
					content: dataset.content,
					agent: dataset.agent,
					id: dataset.id
				};

			reportSpamButton.setAttribute( 'data-comment-ids', commentData.id );
		} );
	}

	// Add action to the report spam button in the modal.
	reportSpamButton.addEventListener( 'click', function( e ) {
		reportSpamButton.setAttribute( 'disabled', 'disabled' );
		var button = this,
			commentIds = button.getAttribute( 'data-comment-ids' );

		if ( commentIds === null ) {
			commentIds = '';
		}

		commentIds = commentIds.split( ',' );

		for ( var i = 0; i < commentIds.length; i++ ) {
			commentIds[i] = parseInt( commentIds[i] );
		}

		wp.apiFetch( {
			path: 'antispam-bee/v1/report-spam',
			method: 'POST',
			data: { comment_ids: commentIds }
		} ).then( ( res ) => {
			if ( res.success === true ) {
				messageParagraph.textContent = asbReportSpam.reportSuccessful;

				return;
			}
			messageParagraph.textContent = asbReportSpam.reportFailed;
		} );
	} );
} )();