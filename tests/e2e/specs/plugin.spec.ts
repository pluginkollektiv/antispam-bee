import { test, expect, adminLogin } from '../fixtures/base';

test.describe( 'Plugin basics', () => {
	test( 'plugin appears on the plugins page', async ( { page } ) => {
		await adminLogin( page );
		await page.goto( '/wp-admin/plugins.php' );
		await expect( page.locator( 'body' ) ).toContainText( 'Antispam Bee' );
	} );

	test( 'can activate the plugin', async ( { page, cli } ) => {
		cli.pluginDeactivate( 'antispam-bee' );
		await adminLogin( page );
		await page.goto( '/wp-admin/plugins.php' );
		await page.click( "[data-slug='antispam-bee'] .activate a" );  // data-slug = sanitized plugin name
		await expect( page.getByText( 'Plugin activated.' ) ).toBeVisible();
	} );

	test( 'can deactivate the plugin', async ( { page, cli } ) => {
		cli.pluginActivate( 'antispam-bee' );
		await adminLogin( page );
		await page.goto( '/wp-admin/plugins.php' );
		await page.click( "[data-slug='antispam-bee'] .deactivate a" );
		await expect( page.getByText( 'Plugin deactivated.' ) ).toBeVisible();
		// Re-activate so subsequent tests are not affected.
		cli.pluginActivate( 'antispam-bee' );
	} );
} );
