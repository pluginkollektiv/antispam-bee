document.addEventListener( 'DOMContentLoaded', () => {
	const reasons = document.querySelectorAll( '#ab_main input[type="checkbox"][data-asb-active-field]' );

	for ( const reason of reasons ) {
		const rule = document.getElementById( reason.dataset.asbActiveField );
		if ( ! rule ) {
			continue;
		}

		// Sync once on load too: the browser can restore an unsaved rule state on reload.
		toggleReason( reason, rule.checked );
		rule.addEventListener( 'change', () => toggleReason( reason, rule.checked ) );
	}

	/**
	 * Enable or disable a reason checkbox to match the state of its rule.
	 *
	 * @param {HTMLInputElement} reason Reason checkbox.
	 * @param {boolean}          active Whether the rule is active.
	 */
	function toggleReason( reason, active ) {
		const label = reason.closest( 'label' );
		let keep = document.querySelector( 'input[type="hidden"][name="' + CSS.escape( reason.name ) + '"]' );

		reason.disabled = ! active;
		label.classList.toggle( 'asb-checkbox-group-disabled', ! active );

		// A disabled checkbox is not submitted and the save replaces the stored reasons, so keep a checked one.
		if ( ! active && reason.checked && ! keep ) {
			keep = document.createElement( 'input' );
			keep.type = 'hidden';
			keep.name = reason.name;
			keep.value = 'on';
			label.after( keep );
		} else if ( active && keep ) {
			keep.remove();
		}
	}
} );
