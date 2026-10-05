/**
 * Covers how an admin learns that the honeypot does not reach the comment form
 * (#908), using the comment form of the `custom-comment-form` mu-plugin, which
 * is not built with `comment_form()` and so never gets the honeypot.
 */
import { adminLogin, expect, test } from '../fixtures/base';

const CUSTOM_FORM = '/?asb_custom_comment_form=1';
const WARNING =
	'The Antispam Bee honeypot could not be added to your comment form';

/**
 * Forget what earlier tests recorded about rendered forms, so each test starts
 * from a site that has neither rendered a form nor received a comment.
 *
 * @param cli WP-CLI wrapper of the test site.
 */
function resetHoneypotState( cli: import('../fixtures/wp-cli').WpCli ) {
	for ( const option of [
		'antispam_bee_honeypot_injection',
		'antispam_bee_honeypot_unguarded_submission',
	] ) {
		try {
			cli.optionDelete( option );
		} catch {
			// Not set, which is the state wanted.
		}
	}
}

/**
 * Submit a comment the way the custom form does, which carries no honeypot.
 *
 * The redirect after the submission is not followed: on the test site it leads
 * to a post whose form is built with `comment_form()`, which would record the
 * honeypot as present again. On a site with a custom form, that page would show
 * the custom form too.
 *
 * @param page The page to submit it with.
 */
async function commentThroughCustomForm(
	page: import('@playwright/test').Page
) {
	await page.goto( CUSTOM_FORM );
	const response = await page.request.post( '/wp-comments-post.php', {
		form: {
			comment: 'A comment from a form the honeypot cannot reach.',
			author: 'Apu Nahasapeemapetilon',
			email: 'apu@example.com',
			comment_post_ID: '1',
		},
		maxRedirects: 0,
	} );
	expect( response.status() ).toBe( 302 );
}

test.describe( 'Honeypot status in Site Health', () => {
	test( 'a comment form without the honeypot is reported', async ( {
		page,
		cli,
	} ) => {
		resetHoneypotState( cli );
		await commentThroughCustomForm( page );

		await adminLogin( page );
		await page.goto( '/wp-admin/site-health.php' );
		await expect( page.getByText( WARNING ) ).toBeVisible();
	} );

	test( 'the report goes away once a comment form carries the honeypot', async ( {
		page,
		cli,
	} ) => {
		resetHoneypotState( cli );
		await commentThroughCustomForm( page );

		// A regular post renders its form through comment_form().
		await page.goto( '/?p=1' );

		await adminLogin( page );
		await page.goto( '/wp-admin/site-health.php' );
		await expect(
			page.getByText(
				'The Antispam Bee honeypot is in your comment form'
			)
		).toBeAttached();
		await expect( page.getByText( WARNING ) ).toHaveCount( 0 );
	} );
} );

test.describe( 'Honeypot notice on the settings page', () => {
	const SETTINGS_PAGE = '/wp-admin/options-general.php?page=antispam_bee';
	const NOTICE =
		'The Antispam Bee honeypot could not be added to your comment form.';

	test( 'a comment form without the honeypot is reported on the settings page only', async ( {
		page,
		cli,
	} ) => {
		resetHoneypotState( cli );
		await commentThroughCustomForm( page );

		await adminLogin( page );
		await page.goto( SETTINGS_PAGE );
		await expect( page.getByText( NOTICE ) ).toBeVisible();

		await page.goto( '/wp-admin/index.php' );
		await expect( page.getByText( NOTICE ) ).toHaveCount( 0 );
	} );

	test( 'the notice goes away once a comment form carries the honeypot', async ( {
		page,
		cli,
	} ) => {
		resetHoneypotState( cli );
		await commentThroughCustomForm( page );
		await page.goto( '/?p=1' );

		await adminLogin( page );
		await page.goto( SETTINGS_PAGE );
		await expect( page.getByText( NOTICE ) ).toHaveCount( 0 );
	} );
} );
