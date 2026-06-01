import { defineConfig, devices } from '@playwright/test';

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
		baseURL: 'http://localhost:8889',
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
