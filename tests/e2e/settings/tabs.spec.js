/**
 * Settings page – tabbed interface
 *
 * Covers the single-form client-side tab switching introduced in
 * https://github.com/pluginkollektiv/antispam-bee/pull/722
 *
 * NOTE: No Playwright config exists yet. These tests will pass once the
 * project-level playwright.config.js is set up with baseURL and
 * storageState (authenticated admin session).
 */

const { test, expect } = require( '@playwright/test' );

const SETTINGS_URL = '/wp-admin/options-general.php?page=antispam_bee';

test.describe( 'Settings tabs', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( SETTINGS_URL );
	} );

	test( 'all tab panels are present in the DOM on initial load', async ( { page } ) => {
		const tabs = page.locator( '#ab_main .nav-tab-wrapper .nav-tab' );
		await expect( tabs ).toHaveCount( 3 ); // general, comment, linkback

		for ( const slug of [ 'general', 'comment', 'linkback' ] ) {
			await expect( page.locator( `#nav-tab__content--${ slug }` ) ).toBeAttached();
		}
	} );

	test( 'only the active tab panel is visible on load', async ( { page } ) => {
		const visiblePanels = page.locator( '#ab_main .nav-tab__content:not([hidden])' );
		await expect( visiblePanels ).toHaveCount( 1 );
	} );

	test( 'switching tabs does not trigger a page navigation', async ( { page } ) => {
		const navigationEvents = [];
		page.on( 'framenavigated', () => navigationEvents.push( true ) );

		const tabs = page.locator( '#ab_main .nav-tab-wrapper .nav-tab' );
		await tabs.nth( 1 ).click();

		expect( navigationEvents ).toHaveLength( 0 );

		await expect( tabs.nth( 1 ) ).toHaveClass( /nav-tab-active/ );
		await expect( page.locator( '#ab_main .nav-tab__content:not([hidden])' ) ).toHaveCount( 1 );
	} );

	test( 'URL is updated to reflect the active tab after click', async ( { page } ) => {
		const tabs = page.locator( '#ab_main .nav-tab-wrapper .nav-tab' );
		const secondSlug = await tabs.nth( 1 ).getAttribute( 'data-tab' );

		await tabs.nth( 1 ).click();

		await expect( page ).toHaveURL( new RegExp( `tab=${ secondSlug }` ) );
	} );

	test( 'settings changed on one tab are preserved when switching tabs and saving', async ( { page } ) => {
		// Toggle the first checkbox on the general tab.
		const checkbox = page.locator( '#nav-tab__content--general input[type="checkbox"]' ).first();
		const name = await checkbox.getAttribute( 'name' );
		const originalState = await checkbox.isChecked();

		await checkbox.click();
		await expect( checkbox ).toBeChecked( { checked: ! originalState } );

		// Switch to a different tab and back — value must survive in DOM.
		const tabs = page.locator( '#ab_main .nav-tab-wrapper .nav-tab' );
		await tabs.nth( 1 ).click();
		await tabs.nth( 0 ).click();

		await expect( page.locator( `[name="${ name }"]` ) ).toBeChecked( { checked: ! originalState } );

		// Save and verify the setting persisted after a full page reload.
		await page.locator( '#nav-tab__content--general [type="submit"]' ).click();
		await page.waitForURL( /settings-updated/ );

		await page.goto( SETTINGS_URL );
		await expect( page.locator( `[name="${ name }"]` ) ).toBeChecked( { checked: ! originalState } );

		// Restore original state.
		await page.locator( `[name="${ name }"]` ).click();
		await page.locator( '#nav-tab__content--general [type="submit"]' ).click();
		await page.waitForURL( /settings-updated/ );
	} );

	test( 'keyboard navigation moves focus between tabs', async ( { page } ) => {
		const firstTab = page.locator( '#ab_main .nav-tab-wrapper .nav-tab' ).first();
		await firstTab.focus();
		await page.keyboard.press( 'ArrowRight' );

		const secondTab = page.locator( '#ab_main .nav-tab-wrapper .nav-tab' ).nth( 1 );
		await expect( secondTab ).toHaveClass( /nav-tab-active/ );
	} );
} );
