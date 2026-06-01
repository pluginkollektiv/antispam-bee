/**
 * Lifecycle script: runs after `wp-env destroy`.
 *
 * Removes the asb-lang-api container left behind by start.js.
 */
import { execSync } from 'child_process';

try {
	execSync( 'docker rm -f asb-lang-api', { stdio: 'inherit' } );
} catch {}
