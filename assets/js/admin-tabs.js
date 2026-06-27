document.addEventListener( 'DOMContentLoaded', () => {
	const tabs = document.querySelectorAll( '#ab_main .nav-tab-wrapper .nav-tab' );
	const tabContents = document.querySelectorAll( '#ab_main .nav-tab__content' );
	const refererInput = document.querySelector( '[name="_wp_http_referer"]' );
	const TAB_PARAM = 'tab';

	for ( const tab of tabs ) {
		tab.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			switchToTab( tab.dataset.tab );
		} );

		tab.addEventListener( 'keydown', handleKeydown );
	}

	window.addEventListener( 'popstate', () => {
		const slug = new URLSearchParams( window.location.search ).get( TAB_PARAM );
		if ( slug ) {
			setActiveTab( slug );
		}
	} );

	/**
	 * Handle keyboard navigation between tabs.
	 *
	 * @param {KeyboardEvent} event
	 */
	function handleKeydown( event ) {
		const tabList = Array.from( tabs );
		const idx = tabList.indexOf( event.currentTarget );
		let newIdx;

		switch ( event.key ) {
			case 'ArrowLeft':
				newIdx = ( idx - 1 + tabList.length ) % tabList.length;
				break;
			case 'ArrowRight':
				newIdx = ( idx + 1 ) % tabList.length;
				break;
			case 'Home':
				newIdx = 0;
				break;
			case 'End':
				newIdx = tabList.length - 1;
				break;
			default:
				return;
		}

		event.preventDefault();
		tabList[ newIdx ].focus();
		switchToTab( tabList[ newIdx ].dataset.tab );
	}

	/**
	 * Switch to the given tab and update the URL.
	 *
	 * @param {string} slug Tab slug to activate.
	 */
	function switchToTab( slug ) {
		setActiveTab( slug );
		updateUrl( slug );
	}

	/**
	 * Activate a tab panel and update ARIA state.
	 *
	 * @param {string} slug Tab slug to activate.
	 */
	function setActiveTab( slug ) {
		for ( const tab of tabs ) {
			const active = tab.dataset.tab === slug;
			tab.classList.toggle( 'nav-tab-active', active );
			tab.ariaSelected = String( active );
			tab.tabIndex = active ? 0 : -1;
		}

		for ( const content of tabContents ) {
			content.hidden = content.id !== 'nav-tab__content--' + slug;
		}
	}

	/**
	 * Update the browser URL and WP referer field for the active tab.
	 *
	 * @param {string} slug Tab slug.
	 */
	function updateUrl( slug ) {
		const setTab = ( search ) => {
			const params = new URLSearchParams( search || '' );
			params.set( TAB_PARAM, slug );
			return params.toString();
		};

		const [ path, search ] = window.location.href.split( '?' );
		history.replaceState( null, null, path + '?' + setTab( search ) );

		if ( refererInput && refererInput.value.includes( '?' ) ) {
			const [ refPath, refSearch ] = refererInput.value.split( '?' );
			refererInput.value = refPath + '?' + setTab( refSearch );
		}
	}
} );
