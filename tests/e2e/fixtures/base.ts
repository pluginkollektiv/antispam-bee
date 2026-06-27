import { test as base, expect, Page } from '@playwright/test';
import { WpCli } from './wp-cli';

// Default v3 plugin options — matches src/Helpers/Settings.php::$defaults.
export const DEFAULT_OPTIONS = {
	comment: {
		rule_asb_regexp_active: 'on',
		rule_asb_honeypot_active: 'on',
		rule_asb_db_spam_active: 'on',
		rule_asb_bbcode_active: 'on',
		post_processor_asb_save_reason_active: 'on',
		rule_asb_approved_email_active: 'on',
	},
	linkback: {
		rule_asb_regexp_active: 'on',
		rule_asb_db_spam_active: 'on',
		rule_asb_bbcode_active: 'on',
		post_processor_asb_save_reason_active: 'on',
	},
	general: {
		general_delete_data_on_uninstall_active: 'on',
	},
};

type Fixtures = {
	cli: WpCli;
};

export const test = base.extend< Fixtures >( {
	cli: async ( {}, use ) => {
		await use( new WpCli() );
	},

	page: async ( { page, cli }, use ) => {
		cli.optionUpdate( 'antispam_bee_options', DEFAULT_OPTIONS );
		cli.commentDeleteAll();
		await use( page );
	},
} );

export { expect };

/**
 * Log in to wp-admin and return a Page in the admin context.
 * Uses a fresh browser context so it doesn't affect the front-end `page` fixture.
 */
export async function adminLogin( page: Page ): Promise< Page > {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', 'admin' );
	await page.fill( '#user_pass', 'password' );
	await page.click( '#wp-submit' );
	await page.waitForURL( /wp-admin/ );
	return page;
}
