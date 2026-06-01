/**
 * Covers trackback.feature: spam detection for trackback/pingback submissions.
 *
 * Trackbacks are sent via HTTP POST to /wp-trackback.php using the helper
 * utility; no browser interaction is required for submission.
 */
import { test, expect, adminLogin } from '../fixtures/base';
import { sendTrackback } from '../utils/trackback';

const BASE_URL = 'http://localhost:8889';

test.describe( 'Trackback spam filtering', () => {
	test( 'BBCode in trackback excerpt is detected as spam', async ( {
		page,
		cli,
	} ) => {
		await sendTrackback( BASE_URL, 1, {
			title: 'Nuclear Power Plants',
			excerpt: "Use [url='http://example.com']bbCode[/url] for cheap energy!",
			url: 'http://nuclear-power.rocks',
			blog_name: 'Mr. Burns Spam Corp.',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText(
			'Nuclear Power Plants'
		);
		await expect( page.locator( 'body' ) ).toContainText( 'BBCode' );
	} );

	test( 'trackback from URL in local spam DB is detected', async ( {
		page,
		cli,
	} ) => {
		// Pre-create a spam comment with the matching URL on post 2 (not post 1).
		// This avoids WordPress's "duplicate ping from same URL for THIS post" rejection
		// while still seeding the cross-post local DB that the rule queries.
		cli.commentCreate( {
			comment_content: 'Spam comment',
			comment_author: 'Spammer',
			comment_author_email: 'spam@spam-trackback.com',
			comment_author_url: 'http://spam-trackback-url.com',
			comment_author_IP: '10.0.0.1',
			comment_date: '2020-01-01 00:00:00',
			comment_approved: 'spam',
			comment_post_ID: 2,
		} );

		await sendTrackback( BASE_URL, 1, {
			title: 'Spam Trackback',
			excerpt: 'A trackback from a known spam URL.',
			url: 'http://spam-trackback-url.com',
			blog_name: 'Spam Blog',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Spam Trackback' );
		await expect( page.locator( 'body' ) ).toContainText( 'Local DB' );
	} );

	test( 'trackback from IP in local spam DB is detected', async ( {
		page,
		cli,
	} ) => {
		test.setTimeout( 90_000 );

		// Submit a browser comment that gets caught as spam by regexp.
		// Both the browser and the trackback HTTP request originate from the same
		// host machine, so WordPress records the same IP for both.
		await page.goto( '/?p=1' );
		await page.fill( '#comment', 'buy amazing Neutrons here!' );
		await page.fill( '#author', 'Spammer' );
		await page.fill( '#email', 'spam@spamip.com' );
		await page.fill( '#url', 'http://spam-ip-site.com' );
		await page.click( '#submit' );

		// Wait for the local spam DB to persist the entry.
		await page.waitForTimeout( 15_000 );

		await sendTrackback( BASE_URL, 1, {
			title: 'Trackback From Spam IP',
			excerpt: 'A perfectly normal trackback.',
			url: 'http://different-url-for-trackback.com',
			blog_name: 'Another Blog',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText(
			'Trackback From Spam IP'
		);
		await expect( page.locator( 'body' ) ).toContainText( 'Local DB' );
	} );

	test( 'regex detects spam keyword in trackback', async ( {
		page,
		cli,
	} ) => {
		await sendTrackback( BASE_URL, 1, {
			title: 'Buy Viagra Online',
			excerpt: 'Cheap Viagra available now!',
			url: 'http://pharma-spam.com',
			blog_name: 'Pharma Blog',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText(
			'Buy Viagra Online'
		);
		await expect( page.locator( 'body' ) ).toContainText( 'RegExp match' );
	} );

	test( 'trackback title matching post title is detected', async ( {
		page,
		cli,
	} ) => {
		// Get the title of post ID 1.
		const postTitle = 'Hello world!';

		await sendTrackback( BASE_URL, 1, {
			title: postTitle,
			excerpt: 'A trackback where blog name matches the post title.',
			url: 'http://some-blog.com',
			blog_name: postTitle,
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText(
			'Linkback Post Title'
		);
	} );

	test( 'language rule blocks trackback in wrong language', async ( {
		page,
		cli,
	} ) => {
		test.fixme(
			true,
			'Language rule calls an external translation API; may be flaky in CI.'
		);

		const opts = cli.optionGet( 'antispam_bee_options' );
		opts.linkback.rule_asb_lang_spam_active = 'on';
		opts.linkback.rule_asb_lang_spam_allowed = [ 'de' ];
		opts.linkback.rule_asb_regexp_active = '';
		opts.linkback.rule_asb_db_spam_active = '';
		opts.linkback.rule_asb_bbcode_active = '';
		cli.optionUpdate( 'antispam_bee_options', opts );

		await sendTrackback( BASE_URL, 1, {
			title: 'English Trackback',
			excerpt:
				'This is an English trackback that should be blocked when only German is allowed.',
			url: 'http://english-blog.com',
			blog_name: 'English Blog',
		} );

		await adminLogin( page );
		await page.goto( '/wp-admin/edit-comments.php?comment_status=spam' );
		await expect( page.locator( 'body' ) ).toContainText( 'Language' );
	} );
} );
