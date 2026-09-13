/**
 * Base URL of the WordPress test site.
 *
 * The test site is the `wp-env` environment described by `.wp-env.test.json`, which
 * pins port 8889. `wp-scripts test-playwright` only derives WP_BASE_URL from an
 * `env.tests` section of the default `.wp-env.json`, and that section no longer
 * exists — the tests environment has a configuration file of its own — so this
 * default is what points the suite at the right site.
 *
 * Override by setting WP_BASE_URL in the environment before running the suite.
 */
export const WP_BASE_URL =
	process.env.WP_BASE_URL ?? 'http://localhost:8889';
