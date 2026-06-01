import { defineConfig, devices } from '@playwright/test';
import { WP_BASE_URL } from './tests/e2e/config';

export default defineConfig( {
	testDir: './tests/e2e/specs',
	// DB state is shared; sequential execution is required.
	fullyParallel: false,
	workers: 1,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 1 : 0,
	reporter: [
		[ 'list' ],
		[ 'html', { outputFolder: 'tests/e2e/report', open: 'never' } ],
	],
	use: {
		baseURL: WP_BASE_URL,
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
	globalSetup: './tests/e2e/fixtures/global-setup.ts',
} );
