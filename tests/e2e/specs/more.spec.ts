/**
 * Covers more.feature: dashboard spam counter and statistics.
 *
 * The `dashboard_chart` option and `#ab_chart` element were removed in v3.
 * Those two Behat scenarios are skipped here with an explanatory note.
 */
import { test, expect, adminLogin } from '../fixtures/base';

const spamComments = [
	{
		comment: 'Release the viagra!',
		author: 'Mr. Burns',
		email: 'montgomery.c.burns.1866@nuclear-secrets.com',
		url: 'http://nuclear-secrets.com',
	},
	{
		comment: 'Release the viagra, again!',
		author: 'Mr. Burns',
		email: 'montgomery.c.burns.1866@nuclear-secrets.com',
		url: 'http://nuclear-secrets.com',
	},
];

async function submitSpamComment(
	page: import( '@playwright/test' ).Page,
	index: number
) {
	const data = spamComments[ index - 1 ];
	await page.goto( '/?p=1' );
	await page.fill( '#comment', data.comment );
	await page.fill( '#author', data.author );
	await page.fill( '#email', data.email );
	await page.fill( '#url', data.url );
	await page.click( '#submit' );
}

test.describe( 'Dashboard statistics', () => {
	test( 'spam counter widget is hidden when disabled', async ( {
		page,
		cli,
	} ) => {
		// Statistics widget is disabled by default; confirm it is absent.
		await adminLogin( page );
		await page.goto( '/wp-admin/' );
		await expect( page.locator( 'body' ) ).not.toContainText(
			'comments blocked'
		);
	} );

	test( 'spam counter widget shows when enabled', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.general.general_statistics_on_dashboard_active = 'on';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await adminLogin( page );
		await page.goto( '/wp-admin/' );
		await expect( page.locator( 'body' ) ).toContainText(
			'comments blocked'
		);
	} );

	test( 'spam counter increments when spam is caught', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.general.general_statistics_on_dashboard_active = 'on';
		cli.optionUpdate( 'antispam_bee_options', opts );

		// Submit two spam comments (caught by regexp rule).
		await submitSpamComment( page, 1 );
		await submitSpamComment( page, 2 );

		await adminLogin( page );
		await page.goto( '/wp-admin/' );
		await expect( page.locator( 'body' ) ).toContainText(
			'2 comments blocked'
		);
	} );

	test.skip(
		'dashboard chart enabled — option removed in v3',
		async () => {}
	);

	test.skip(
		'dashboard chart disabled — option removed in v3',
		async () => {}
	);
} );
