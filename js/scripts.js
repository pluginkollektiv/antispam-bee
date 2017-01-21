jQuery(document).ready(
	function($) {
		function ab_flag_spam() {
			var $$ = $('#ab_flag_spam'),
				nextAll = $$.parent('li').nextAll();

			nextAll.css(
				'display',
				( $$.is(':checked') ? 'list-item' : 'none' )
			);
		}

		$('#ab_flag_spam').on(
			'change',
			ab_flag_spam
		);

		ab_flag_spam();
	}
);
