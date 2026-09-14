/**
 * Lifecycle script: runs after `wp-env start`.
 *
 * Builds the asb-lang-api Docker image, starts a container from it, and
 * connects that container to the same network as the WordPress container of the
 * test environment, so WordPress can reach it as http://asb-lang-api:3000/.
 */
import { execSync } from 'child_process';
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __dirname = dirname( fileURLToPath( import.meta.url ) );
const CONTAINER_NAME = 'asb-lang-api';

/*
 * The test environment is a `wp-env` configuration of its own, so its WordPress
 * container is the plain `wordpress` service of that configuration — there is no
 * `tests-wordpress` container any more. Several wp-env environments can run side by
 * side from this directory, and their container names differ only in a hash, so the
 * published port from `.wp-env.test.json` is what identifies the right one.
 */
const TEST_ENV_PORT = JSON.parse(
	readFileSync( join( __dirname, '..', '..', '..', '.wp-env.test.json' ), 'utf8' )
).port;

function run( cmd, opts = {} ) {
	const result = execSync( cmd, { encoding: 'utf8', ...opts } );
	return typeof result === 'string' ? result.trim() : '';
}

// Build the image.
run( `docker build -t ${ CONTAINER_NAME } "${ __dirname }"`, { stdio: 'inherit' } );

// Remove any stale container from a previous run.
try {
	run( `docker rm -f ${ CONTAINER_NAME }`, { stdio: 'pipe' } );
} catch {
}

// Start the container (no network yet — we connect it below).
run( `docker run -d --name ${ CONTAINER_NAME } ${ CONTAINER_NAME }`, {
	stdio: 'inherit',
} );

// Find the WordPress container of the test environment so we can read its network.
const testsContainer = run( 'docker ps --format "{{.Names}}\t{{.Ports}}"' )
	.split( '\n' )
	.map( ( line ) => line.split( '\t' ) )
	.find(
		( [ name, ports ] ) =>
			name?.includes( 'wp-env' ) &&
			name?.includes( 'wordpress' ) &&
			ports?.includes( `:${ TEST_ENV_PORT }->` )
	)?.[ 0 ];

if ( ! testsContainer ) {
	console.error(
		`ERROR: Could not find the wp-env WordPress container on port ${ TEST_ENV_PORT }.`
	);
	process.exit( 1 );
}

const networksJson = run(
	`docker inspect "${ testsContainer }" --format "{{json .NetworkSettings.Networks}}"`
);
const networkName = Object.keys( JSON.parse( networksJson ) )[ 0 ];

// Connect the lang-api container to the wp-env tests network.
run( `docker network connect "${ networkName }" ${ CONTAINER_NAME }`, {
	stdio: 'inherit',
} );
console.log( `${ CONTAINER_NAME } connected to ${ networkName }` );
