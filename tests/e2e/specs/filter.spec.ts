/**
 * Covers filter.feature: all spam detection rule scenarios.
 *
 * Spam reason strings match the v3 source (not the old Behat assertions):
 *   - "Local DB"     (was "Local DB Spam")
 *   - "RegExp match" (was "Regular Expression")
 *   - "Language"     (was "Comment Language")
 *   - "Honeypot" and "BBCode" unchanged.
 */
import { test, expect, adminLogin } from '../fixtures/base';

async function fillComment(
	page: import( '@playwright/test' ).Page,
	opts: {
		comment: string;
		author: string;
		email: string;
		url?: string;
		fillHoneypot?: boolean;
	}
) {
	await page.goto( '/?p=1' );
	await page.fill( '#comment', opts.comment );
	if ( opts.fillHoneypot ) {
		await page.evaluate( () => {
			const hp = document.querySelector(
				'textarea[aria-hidden="true"]'
			) as HTMLTextAreaElement | null;
			if ( hp ) hp.value = 'bot filling honeypot';
		} );
	}
	await page.fill( '#author', opts.author );
	await page.fill( '#email', opts.email );
	if ( opts.url ) {
		await page.fill( '#url', opts.url );
	}
	await page.click( '#submit' );
}

test.describe( 'Spam filter mechanisms', () => {
	test( 'honeypot catches spam comment', async ( { page, cli } ) => {
		await fillComment( page, {
			comment: 'Release the hounds!',
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
			url: 'http://nuclear-secrets.com',
			fillHoneypot: true,
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Mr. Burns' );
		await expect( page.locator( 'body' ) ).toContainText( 'Honeypot' );
	} );

	test( 'honeypot spam is deleted when delete processor is active', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.post_processor_asb_delete_spam_active = 'on';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await fillComment( page, {
			comment: 'Release the hounds!',
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
			fillHoneypot: true,
		} );

		await expect( page.locator( 'body' ) ).toContainText( 'Spam deleted.' );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Mr. Burns' );
	} );

	test( 'local spam DB flags comment from same IP', async ( {
		page,
		cli,
	} ) => {
		test.setTimeout( 90_000 );

		// First comment — caught by RegExp ("buy amazing" matches the built-in pattern).
		await fillComment( page, {
			comment: 'buy amazing Neutrons here!',
			author: 'Montgomery',
			email: 'montgomery.c.burns.1866@aol.com',
			url: 'http://nuclear-secrets.com',
		} );

		// Wait for the local spam DB to persist the entry.
		await page.waitForTimeout( 15_000 );

		// Second comment from same IP — should be caught by local DB.
		await fillComment( page, {
			comment: 'Excellent indeed!',
			author: 'Monty',
			email: 'monty.1983@nuclear-secrets.com',
			url: 'http://nuclear-secrets.info',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Monty' );
		await expect( page.locator( 'body' ) ).toContainText( 'Local DB' );
	} );

	test( 'local spam DB does not flag when db_spam rule is off', async ( {
		page,
		cli,
	} ) => {
		// Disable the local DB rule but keep regexp active.
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.rule_asb_db_spam_active = '';
		opts.comment.rule_asb_regexp_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		// Pre-create a spam comment from a known IP via WP-CLI.
		cli.commentCreate( {
			comment_content: 'Spam comment',
			comment_author: 'Spammer',
			comment_author_email: 'spam@example.com',
			comment_author_url: 'http://spam.com',
			comment_author_IP: '127.0.0.1',
			comment_date: '2020-01-01 00:00:00',
			comment_approved: 'spam',
			comment_post_ID: 1,
		} );

		// A new comment from the same IP should pass (db rule is off).
		await fillComment( page, {
			comment: 'A totally legitimate comment.',
			author: 'Legitimate User',
			email: 'legit@example.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).not.toContainText(
			'Legitimate User'
		);
	} );

	test( 'local spam DB flags by email', async ( { page, cli } ) => {
		test.setTimeout( 90_000 );

		// First comment — caught by RegExp ("buy amazing" matches the built-in pattern).
		await fillComment( page, {
			comment: 'buy amazing Neutrons here!',
			author: 'Montgomery',
			email: 'same-email@nuclear.com',
			url: 'http://nuclear-secrets.com',
		} );

		await page.waitForTimeout( 15_000 );

		// Second comment from same email — caught by local DB.
		await fillComment( page, {
			comment: 'Excellent indeed!',
			author: 'Monty',
			email: 'same-email@nuclear.com',
			url: 'http://other-site.info',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Local DB' );
	} );

	test( 'local spam DB flags by URL', async ( { page, cli } ) => {
		test.setTimeout( 90_000 );

		// "buy amazing" matches the built-in regexp pattern.
		await fillComment( page, {
			comment: 'buy amazing Neutrons here!',
			author: 'Montgomery',
			email: 'montgomery@nuclear.com',
			url: 'http://shared-spam-url.com',
		} );

		await page.waitForTimeout( 15_000 );

		await fillComment( page, {
			comment: 'Excellent indeed!',
			author: 'Monty',
			email: 'monty@different.com',
			url: 'http://shared-spam-url.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Local DB' );
	} );

	test( 'regex detects spam keyword (Viagra)', async ( { page, cli } ) => {
		await fillComment( page, {
			comment: 'Buy Viagra now!',
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Mr. Burns' );
		await expect( page.locator( 'body' ) ).toContainText( 'RegExp match' );
	} );

	test( 'regex detects spam keyword (luxurybrandsale)', async ( {
		page,
		cli,
	} ) => {
		await fillComment( page, {
			comment: 'Check out luxurybrandsale for deals!',
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'RegExp match' );
	} );

	test( 'regex disabled allows spam keywords through', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.rule_asb_regexp_active = '';
		opts.comment.rule_asb_db_spam_active = '';
		opts.comment.rule_asb_honeypot_active = '';
		opts.comment.rule_asb_bbcode_active = '';
		opts.comment.rule_asb_approved_email_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await fillComment( page, {
			comment: 'Buy Viagra now!',
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		// Comment should be pending, not spam.
		await page.goto(
			'/wp-admin/edit-comments.php?comment_status=moderated'
		);
		await expect( page.locator( 'body' ) ).toContainText( 'Mr. Burns' );
	} );

	test( 'BBCode in comment is detected as spam', async ( { page, cli } ) => {
		await fillComment( page, {
			comment: "Check out [url='http://example.com']our store[/url]!",
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Mr. Burns' );
		await expect( page.locator( 'body' ) ).toContainText( 'BBCode' );
	} );

	test( 'BBCode detection disabled allows BBCode through', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.rule_asb_bbcode_active = '';
		opts.comment.rule_asb_regexp_active = '';
		opts.comment.rule_asb_db_spam_active = '';
		opts.comment.rule_asb_honeypot_active = '';
		opts.comment.rule_asb_approved_email_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await fillComment( page, {
			comment: "Check out [url='http://example.com']our store[/url]!",
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		await page.goto(
			'/wp-admin/edit-comments.php?comment_status=moderated'
		);
		await expect( page.locator( 'body' ) ).toContainText( 'Mr. Burns' );
	} );

	test( 'language rule blocks comment in wrong language', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.rule_asb_lang_spam_active = 'on';
		opts.comment.rule_asb_lang_spam_allowed = { de: 'on' };
		// Disable other rules so only language rule fires.
		opts.comment.rule_asb_regexp_active = '';
		opts.comment.rule_asb_db_spam_active = '';
		opts.comment.rule_asb_honeypot_active = '';
		opts.comment.rule_asb_bbcode_active = '';
		opts.comment.rule_asb_approved_email_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await fillComment( page, {
			// Long English sentence so franc has enough trigrams for reliable detection.
			comment:
				'This is a comment written entirely in English and it should be blocked because the site only allows comments written in the German language.',
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Language' );
	} );

	test( 'language rule skips short comments (too little text to detect)', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.rule_asb_lang_spam_active = 'on';
		opts.comment.rule_asb_lang_spam_allowed = { de: 'on' };
		opts.comment.rule_asb_regexp_active = '';
		opts.comment.rule_asb_db_spam_active = '';
		opts.comment.rule_asb_honeypot_active = '';
		opts.comment.rule_asb_bbcode_active = '';
		opts.comment.rule_asb_approved_email_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await fillComment( page, {
			comment: 'Hi',
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		// Short comments bypass language detection and should not be in spam.
		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Language' );
	} );

	test( 'language rule allows comment in the allowed language', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.rule_asb_lang_spam_active = 'on';
		opts.comment.rule_asb_lang_spam_allowed = { en: 'on' };
		opts.comment.rule_asb_regexp_active = '';
		opts.comment.rule_asb_db_spam_active = '';
		opts.comment.rule_asb_honeypot_active = '';
		opts.comment.rule_asb_bbcode_active = '';
		opts.comment.rule_asb_approved_email_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await fillComment( page, {
			comment:
				'This is a perfectly legitimate comment written in English and it should be allowed through because English is the configured allowed language.',
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Mr. Burns' );
	} );

	test( 'language rule with multiple allowed languages blocks unlisted language', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.rule_asb_lang_spam_active = 'on';
		opts.comment.rule_asb_lang_spam_allowed = { de: 'on', it: 'on' };
		opts.comment.rule_asb_regexp_active = '';
		opts.comment.rule_asb_db_spam_active = '';
		opts.comment.rule_asb_honeypot_active = '';
		opts.comment.rule_asb_bbcode_active = '';
		opts.comment.rule_asb_approved_email_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await fillComment( page, {
			comment:
				'This is an English comment that should be blocked because only German and Italian are on the allowed language list for this site.',
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Language' );
	} );

	test( 'language rule with multiple allowed languages passes listed language', async ( {
		page,
		cli,
	} ) => {
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.rule_asb_lang_spam_active = 'on';
		opts.comment.rule_asb_lang_spam_allowed = { it: 'on', en: 'on' };
		opts.comment.rule_asb_regexp_active = '';
		opts.comment.rule_asb_db_spam_active = '';
		opts.comment.rule_asb_honeypot_active = '';
		opts.comment.rule_asb_bbcode_active = '';
		opts.comment.rule_asb_approved_email_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await fillComment( page, {
			comment:
				'This is a perfectly legitimate comment written in English and it should pass because English is included in the allowed language list alongside Italian.',
			author: 'Mr. Burns',
			email: 'burns@nuclear.com',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Mr. Burns' );
	} );

	test( 'manually marking a comment as spam updates local DB', async ( {
		page,
		cli,
	} ) => {
		test.setTimeout( 90_000 );

		// Disable all rules except db_spam so a legitimate comment passes first.
		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.comment.rule_asb_regexp_active = '';
		opts.comment.rule_asb_honeypot_active = '';
		opts.comment.rule_asb_bbcode_active = '';
		opts.comment.rule_asb_approved_email_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		// Post a legitimate comment.
		await fillComment( page, {
			comment: 'A totally legitimate comment.',
			author: 'Legitimate User',
			email: 'legit@example.com',
			url: 'http://legit-site.com',
		} );

		// Mark it as spam via admin.
		await adminLogin( page );
		await page.goto(
			'/wp-admin/edit-comments.php?comment_status=moderated'
		);
		// Row actions are CSS-hidden until the row is hovered.
		const commentRow = page.locator( 'table.widefat tbody tr' ).first();
		await commentRow.hover();
		const spamLink = page.locator( '.row-actions .spam a' ).first();
		await spamLink.click( { force: true } );
		await page.waitForURL( /edit-comments/ );

		// Wait for the DB to sync.
		await page.waitForTimeout( 15_000 );

		// Log out so the second comment form has author/email/URL fields.
		await page.context().clearCookies();

		// A second comment from the same IP should now be caught by local DB.
		await fillComment( page, {
			comment: 'Another comment.',
			author: 'Also Legit',
			email: 'also@different.com',
			url: 'http://different-site.com',
		} );

		// Re-login to check the spam folder.
		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Also Legit' );
		await expect( page.locator( 'body' ) ).toContainText( 'Local DB' );
	} );
} );
