/**
 * Covers the honeypot's output-buffer injection (#906) for a comment form that
 * is not built with `comment_form()`, as rendered by the `custom-comment-form`
 * mu-plugin.
 */
import { adminLogin, DEFAULT_OPTIONS, expect, test } from '../fixtures/base';

const CUSTOM_FORM = '/?asb_custom_comment_form=1';
const HONEYPOT = 'textarea[aria-hidden="true"]';

function enableOutputBuffer( cli: import('../fixtures/wp-cli').WpCli ) {
	cli.optionUpdate( 'antispam_bee_options', {
		...DEFAULT_OPTIONS,
		comment: {
			...DEFAULT_OPTIONS.comment,
			rule_asb_honeypot_output_buffer: 'on',
		},
	} );
}

async function submitCustomForm(
	page: import('@playwright/test').Page,
	opts: { author: string; email: string; fillHoneypot?: boolean }
) {
	await page.goto( CUSTOM_FORM );
	await page.fill(
		'#custom-comment',
		'A comment sent through a custom form.'
	);
	if ( opts.fillHoneypot ) {
		await page.evaluate( ( selector ) => {
			const hp = document.querySelector(
				selector
			) as HTMLTextAreaElement | null;
			if ( hp ) {
				hp.value = 'bot filling honeypot';
			}
		}, HONEYPOT );
	}
	await page.fill( '#custom-author', opts.author );
	await page.fill( '#custom-email', opts.email );
	await page.click( '#custom-submit' );
}

test.describe( 'Honeypot output-buffer injection', () => {
	test( 'a custom form gets no honeypot without output buffering', async ( {
		page,
	} ) => {
		await page.goto( CUSTOM_FORM );
		await expect( page.locator( HONEYPOT ) ).toHaveCount( 0 );
	} );

	test( 'a custom form gets the honeypot with output buffering', async ( {
		page,
		cli,
	} ) => {
		enableOutputBuffer( cli );

		await page.goto( CUSTOM_FORM );
		await expect( page.locator( HONEYPOT ) ).toHaveCount( 1 );
		// The visible field keeps its id, so its label still points at it.
		await expect( page.locator( '#custom-comment' ) ).not.toHaveAttribute(
			'name',
			'comment'
		);
	} );

	test( 'a genuine comment through a custom form gets through', async ( {
		page,
		cli,
	} ) => {
		enableOutputBuffer( cli );

		await submitCustomForm( page, {
			author: 'Marge Simpson',
			email: 'marge.simpson@example.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).not.toContainText(
			'Marge Simpson'
		);
		await page.goto(
			'/wp-admin/edit-comments.php?comment_status=moderated'
		);
		await expect( page.locator( 'body' ) ).toContainText( 'Marge Simpson' );
	} );

	test( 'a bot filling the honeypot of a custom form is caught', async ( {
		page,
		cli,
	} ) => {
		enableOutputBuffer( cli );

		await submitCustomForm( page, {
			author: 'Sideshow Bob',
			email: 'sideshow.bob@example.com',
			fillHoneypot: true,
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Sideshow Bob' );
		await expect( page.locator( 'body' ) ).toContainText( 'Honeypot' );
	} );
} );
