import { execSync } from 'child_process';

export class WpCli {
	private run( args: string ): string {
		const cmd = `npx wp-env run tests-cli wp ${ args }`;
		try {
			return execSync( cmd, {
				encoding: 'utf8',
				stdio: [ 'pipe', 'pipe', 'pipe' ],
				timeout: 30_000,
			} ).trim();
		} catch ( error: any ) {
			throw new Error( `WP-CLI failed: ${ cmd }\n${ error.stderr }` );
		}
	}

	optionGet( name: string ): any {
		return JSON.parse( this.run( `option get ${ name } --format=json` ) );
	}

	optionUpdate( name: string, value: unknown ): void {
		const json = JSON.stringify( value ).replace( /'/g, "'\\''" );
		this.run( `option update ${ name } '${ json }' --format=json` );
	}

	commentCreate( fields: Record< string, string | number > ): number {
		const args = Object.entries( fields )
			.map( ( [ k, v ] ) => `--${ k }="${ v }"` )
			.join( ' ' );
		const id = this.run( `comment create ${ args } --porcelain` );
		return parseInt( id, 10 );
	}

	commentDeleteAll(): void {
		const deleteByStatus = ( status: string ) => {
			const ids = this.run(
				`comment list --status=${ status } --format=ids`
			);
			if ( ids ) {
				ids.split( ' ' )
					.filter( Boolean )
					.forEach( ( id ) =>
						this.run( `comment delete ${ id } --force` )
					);
			}
		};
		deleteByStatus( 'spam' );
		deleteByStatus( 'hold' );
	}

	pluginActivate( slug: string ): void {
		this.run( `plugin activate ${ slug }` );
	}

	pluginDeactivate( slug: string ): void {
		this.run( `plugin deactivate ${ slug }` );
	}

	postCreate( fields: Record< string, string > ): number {
		const args = Object.entries( fields )
			.map( ( [ k, v ] ) => `--${ k }="${ v }"` )
			.join( ' ' );
		const id = this.run( `post create ${ args } --porcelain` );
		return parseInt( id, 10 );
	}

	postExists( id: number ): boolean {
		try {
			this.run( `post get ${ id } --field=ID` );
			return true;
		} catch {
			return false;
		}
	}
}
