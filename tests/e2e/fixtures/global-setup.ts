import { execSync } from 'child_process';

function wpCli( args: string ): string {
	return execSync( `npx wp-env run tests-cli wp ${ args }`, {
		encoding: 'utf8',
		stdio: [ 'pipe', 'pipe', 'pipe' ],
		timeout: 30_000,
	} ).trim();
}

export default async function globalSetup() {
	wpCli( 'plugin activate antispam-bee' );

	// Use plain (query-string) permalinks so `wp-trackback.php?p=1` resolves
	// correctly. Pretty permalinks embed the post date which varies per
	// environment and breaks the `?p=1` resolution in `wp-trackback.php`.
	wpCli( 'option update permalink_structure ""' );
	wpCli( 'rewrite flush' );

	// Ensure post ID 1 exists with comments open.
	try {
		const status = wpCli( 'post get 1 --field=comment_status' );
		if ( status !== 'open' ) {
			wpCli( 'post update 1 --comment_status=open' );
		}
	} catch {
		wpCli(
			'post create --post_title="Hello world!" --post_status=publish --comment_status=open --porcelain'
		);
	}

	// Ensure post ID 2 exists — used by trackback URL-in-local-DB test as the
	// seed post, so WordPress does not reject the trackback to post 1 as a duplicate.
	try {
		wpCli( 'post get 2 --field=ID' );
	} catch {
		wpCli(
			'post create --post_title="Sample Page" --post_status=publish --post_type=page --comment_status=open --porcelain'
		);
	}
}
