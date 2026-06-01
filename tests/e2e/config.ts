/**
 * Base URL of the WordPress test site.
 *
 * `wp-scripts test-playwright` reads the port from the wp-env config and sets
 * WP_BASE_URL before spawning the Playwright process. This variable picks that
 * up automatically so tests always hit the same site as the browser fixture,
 * regardless of which port wp-env happens to use.
 *
 * Override by setting WP_BASE_URL in the environment before running the suite.
 */
export const WP_BASE_URL =
	process.env.WP_BASE_URL ?? 'http://localhost:8889';
