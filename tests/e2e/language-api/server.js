import http from 'http';
import { franc } from 'franc';

const PORT = 8080;

http.createServer( ( req, res ) => {
	if ( req.method !== 'POST' ) {
		res.writeHead( 405 );
		res.end();
		return;
	}

	let raw = '';
	req.on( 'data', ( chunk ) => ( raw += chunk ) );
	req.on( 'end', () => {
		try {
			const { body: text } = JSON.parse( raw );
			const code = franc( text );
			res.writeHead( 200, { 'Content-Type': 'application/json' } );
			res.end( JSON.stringify( { code } ) );
		} catch {
			res.writeHead( 400 );
			res.end();
		}
	} );
} ).listen( PORT, () => console.log( `Language API ready on :${ PORT }` ) );
