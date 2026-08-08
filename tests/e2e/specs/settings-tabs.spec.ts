/**
 * Covers the single-form client-side tab switching on the settings page,
 * introduced in https://github.com/pluginkollektiv/antispam-bee/pull/722
 *
 * Ported from `tests/e2e/settings/tabs.spec.js`, which was written before the
 * Playwright config existed. It sat outside `testDir` and was never collected,
 * so these assertions had never run — see #790.
 */
import { adminLogin, expect, test } from '../fixtures/base';

const SETTINGS_URL = '/wp-admin/options-general.php?page=antispam_bee';
const TAB_SLUGS = [ 'general', 'comment', 'linkback' ];

test.describe( 'Settings tabs', () => {
	test.beforeEach( async ( { page } ) => {
		await adminLogin( page );
		await page.goto( SETTINGS_URL );
	} );

	test( 'all tab panels are present in the DOM on initial load', async ( {
		page,
	} ) => {
		const tabs = page.locator( '#ab_main .nav-tab-wrapper .nav-tab' );
		await expect( tabs ).toHaveCount( TAB_SLUGS.length );

		for ( const slug of TAB_SLUGS ) {
			await expect(
				page.locator( `#nav-tab__content--${ slug }` )
			).toBeAttached();
		}
	} );

	test( 'only the active tab panel is visible on load', async ( {
		page,
	} ) => {
		const visiblePanels = page.locator(
			'#ab_main .nav-tab__content:not([hidden])'
		);
		await expect( visiblePanels ).toHaveCount( 1 );
	} );

	test( 'switching tabs does not reload the page', async ( { page } ) => {
		// The original spec listened for `framenavigated` and expected none. That can
		// never hold: `switchToTab()` calls `history.replaceState()` by design, and
		// Playwright reports same-document history updates as frame navigations. What
		// the tab UI actually promises is that no *document* load happens, so mark the
		// current document and check the marker survives — a real navigation discards it.
		await page.evaluate( () => {
			( window as unknown as { __asbTabMarker?: boolean } ).__asbTabMarker =
				true;
		} );

		const tabs = page.locator( '#ab_main .nav-tab-wrapper .nav-tab' );
		await tabs.nth( 1 ).click();

		await expect( tabs.nth( 1 ) ).toHaveClass( /nav-tab-active/ );
		await expect(
			page.locator( '#ab_main .nav-tab__content:not([hidden])' )
		).toHaveCount( 1 );

		const markerSurvived = await page.evaluate(
			() =>
				( window as unknown as { __asbTabMarker?: boolean } )
					.__asbTabMarker === true
		);
		expect( markerSurvived ).toBe( true );
	} );

	test( 'URL is updated to reflect the active tab after click', async ( {
		page,
	} ) => {
		const tabs = page.locator( '#ab_main .nav-tab-wrapper .nav-tab' );
		const secondSlug = await tabs.nth( 1 ).getAttribute( 'data-tab' );

		await tabs.nth( 1 ).click();

		await expect( page ).toHaveURL( new RegExp( `tab=${ secondSlug }` ) );
	} );

	test( 'settings changed on one tab are preserved when switching tabs and saving', async ( {
		page,
	} ) => {
		// Toggle the first checkbox on the general tab.
		const checkbox = page
			.locator( '#nav-tab__content--general input[type="checkbox"]' )
			.first();
		const name = await checkbox.getAttribute( 'name' );
		const originalState = await checkbox.isChecked();

		await checkbox.click();
		await expect( checkbox ).toBeChecked( { checked: ! originalState } );

		// Switch to a different tab and back — the value must survive in the DOM.
		const tabs = page.locator( '#ab_main .nav-tab-wrapper .nav-tab' );
		await tabs.nth( 1 ).click();
		await tabs.nth( 0 ).click();

		await expect( page.locator( `[name="${ name }"]` ) ).toBeChecked( {
			checked: ! originalState,
		} );

		// Save and verify the setting persisted across a full page reload.
		await page
			.locator( '#nav-tab__content--general [type="submit"]' )
			.click();
		await page.waitForURL( /settings-updated/ );

		await page.goto( SETTINGS_URL );
		await expect( page.locator( `[name="${ name }"]` ) ).toBeChecked( {
			checked: ! originalState,
		} );
	} );

	test( 'keyboard navigation moves focus between tabs', async ( {
		page,
	} ) => {
		const firstTab = page
			.locator( '#ab_main .nav-tab-wrapper .nav-tab' )
			.first();
		await firstTab.focus();
		await page.keyboard.press( 'ArrowRight' );

		const secondTab = page
			.locator( '#ab_main .nav-tab-wrapper .nav-tab' )
			.nth( 1 );
		await expect( secondTab ).toBeFocused();
		await expect( secondTab ).toHaveClass( /nav-tab-active/ );
	} );
} );
