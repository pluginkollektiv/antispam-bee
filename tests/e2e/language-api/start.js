/**
 * Lifecycle script: runs after `wp-env start`.
 *
 * Builds the asb-lang-api Docker image, starts a container from it, and
 * connects that container to the same network as the wp-env tests-wordpress
 * container so WordPress can reach it as http://asb-lang-api:3000/.
 */
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import { dirname } from 'path';

const __dirname = dirname( fileURLToPath( import.meta.url ) );
const CONTAINER_NAME = 'asb-lang-api';

function run( cmd, opts = {} ) {
	const result = execSync( cmd, { encoding: 'utf8', ...opts } );
	return typeof result === 'string' ? result.trim() : '';
}

// Build the image.
run( `docker build -t ${ CONTAINER_NAME } "${ __dirname }"`, { stdio: 'inherit' } );

// Remove any stale container from a previous run.
try {
	run( `docker rm -f ${ CONTAINER_NAME }`, { stdio: 'pipe' } );
} catch {}

// Start the container (no network yet — we connect it below).
run( `docker run -d --name ${ CONTAINER_NAME } ${ CONTAINER_NAME }`, {
	stdio: 'inherit',
} );

// Find the wp-env tests-wordpress container so we can read its network.
const allContainers = run( 'docker ps --format "{{.Names}}"' ).split( '\n' );
const testsContainer = allContainers.find(
	( name ) => name.includes( 'wp-env' ) && name.includes( 'tests-wordpress' )
);

if ( ! testsContainer ) {
	console.error( 'ERROR: Could not find the wp-env tests-wordpress container.' );
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
