import { execSync } from 'child_process';

function wpCli( args: string ): string {
	return execSync( `npx wp-env run tests-cli wp ${ args }`, {
		encoding: 'utf8',
		stdio: [ 'pipe', 'pipe', 'pipe' ],
		timeout: 30_000,
	} ).trim();
}

/**
 * Whether a post with the given ID exists.
 *
 * @param id Post ID to look for.
 */
function postExists( id: number ): boolean {
	try {
		wpCli( `post get ${ id } --field=ID` );
		return true;
	} catch {
		return false;
	}
}

/**
 * Ensure a post exists under an exact ID.
 *
 * `wp post create` cannot choose an ID — it takes whatever the auto-increment hands out. A
 * database that had lost post 1 could therefore never get it back: the previous fallback
 * created a post with some other ID, the next run found post 1 still missing, and created
 * another one. The specs navigate to `/?p=1`, so every comment and trackback test then failed
 * with a misleading "waiting for locator('#comment')" timeout. See #801.
 *
 * Create the post, then move it to the required ID.
 *
 * @param id         Required post ID.
 * @param createArgs Arguments passed to `wp post create`.
 */
function ensurePostId( id: number, createArgs: string ): void {
	if ( postExists( id ) ) {
		return;
	}

	const createdId = wpCli( `post create ${ createArgs } --porcelain` );
	const prefix = wpCli( 'db prefix' );

	wpCli(
		`db query 'UPDATE ${ prefix }posts SET ID = ${ id } WHERE ID = ${ createdId }'`
	);
	wpCli( 'cache flush' );

	if ( ! postExists( id ) ) {
		throw new Error(
			`E2E setup could not create post ID ${ id }: it is still missing after creating ` +
				`post ${ createdId } and moving it. The test database is in an unexpected ` +
				'state — run `npm run env:clean` and start the suite again.'
		);
	}
}

export default async function globalSetup() {
	wpCli( 'plugin activate antispam-bee' );

	// Use plain (query-string) permalinks so `wp-trackback.php?p=1` resolves
	// correctly. Pretty permalinks embed the post date which varies per
	// environment and breaks the `?p=1` resolution in `wp-trackback.php`.
	wpCli( 'option update permalink_structure ""' );
	wpCli( 'rewrite flush' );

	// Post ID 1 is the seed post every spec comments on via `/?p=1`.
	ensurePostId(
		1,
		'--post_title="Hello world!" --post_status=publish --comment_status=open'
	);
	if ( wpCli( 'post get 1 --field=comment_status' ) !== 'open' ) {
		wpCli( 'post update 1 --comment_status=open' );
	}

	// Post ID 2 is used by the trackback URL-in-local-DB test as the seed post,
	// so WordPress does not reject the trackback to post 1 as a duplicate.
	ensurePostId(
		2,
		'--post_title="Sample Page" --post_status=publish --post_type=page --comment_status=open'
	);
}
