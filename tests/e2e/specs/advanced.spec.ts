/**
 * Covers advanced.feature: flag_spam / no_notice / spam reason visibility.
 *
 * Honeypot detection is the spam trigger throughout; the honeypot textarea
 * has aria-hidden="true" and name="comment" after v3's DOM injection.
 */
import { test, expect, adminLogin, DEFAULT_OPTIONS } from '../fixtures/base';

async function fillHoneypotComment(
	page: import( '@playwright/test' ).Page,
	opts: { author: string; email: string; url?: string }
) {
	await page.goto( '/?p=1' );
	await page.fill( '#comment', 'A perfectly normal comment.' );
	// Simulate a bot filling the honeypot field.
	await page.evaluate( () => {
		const hp = document.querySelector(
			'textarea[aria-hidden="true"]'
		) as HTMLTextAreaElement | null;
		if ( hp ) hp.value = 'filling honeypot';
	} );
	await page.fill( '#author', opts.author );
	await page.fill( '#email', opts.email );
	if ( opts.url ) {
		await page.fill( '#url', opts.url );
	}
	await page.click( '#submit' );
}

test.describe( 'Advanced spam settings', () => {
	test( 'spam is saved in database (flag_spam on)', async ( {
		page,
		cli,
	} ) => {
		// Default options keep spam flagged (delete processor is off).
		await fillHoneypotComment( page, {
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
			url: 'http://nuclear-secrets.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Mr. Burns' );
		await expect( page.locator( 'body' ) ).toContainText( 'Honeypot' );
	} );

	test( 'spam is deleted and not saved (flag_spam off)', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.post_processor_asb_delete_spam_active = 'on';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await fillHoneypotComment( page, {
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
			url: 'http://nuclear-secrets.com',
		} );

		await expect( page.locator( 'body' ) ).toContainText( 'Spam deleted.' );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Mr. Burns' );
	} );

	test( 'spam reason is saved and visible (save_reason on)', async ( {
		page,
		cli,
	} ) => {
		// Default options already have save_reason enabled.
		await fillHoneypotComment( page, {
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Honeypot' );
	} );

	test( 'spam reason is not visible (save_reason off)', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.post_processor_asb_save_reason_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await fillHoneypotComment( page, {
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Mr. Burns' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Honeypot' );
	} );
} );
