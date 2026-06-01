import * as http from 'http';
import * as https from 'https';

export interface TrackbackParams {
	title: string;
	excerpt: string;
	url: string;
	blog_name: string;
}

export async function sendTrackback(
	baseUrl: string,
	postId: number,
	params: TrackbackParams,
	retries = 1
): Promise< void > {
	try {
		return await sendTrackbackOnce( baseUrl, postId, params );
	} catch ( err: any ) {
		if ( retries > 0 && err?.message?.includes( 'socket hang up' ) ) {
			await new Promise( ( r ) => setTimeout( r, 1000 ) );
			return sendTrackback( baseUrl, postId, params, retries - 1 );
		}
		throw err;
	}
}

function sendTrackbackOnce(
	baseUrl: string,
	postId: number,
	params: TrackbackParams
): Promise< void > {
	const body = new URLSearchParams( Object.entries( params ) ).toString();
	const endpoint = new URL( `/wp-trackback.php?p=${ postId }`, baseUrl );
	const client = endpoint.protocol === 'https:' ? https : http;

	return new Promise( ( resolve, reject ) => {
		const req = client.request(
			endpoint,
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
					'Content-Length': Buffer.byteLength( body ),
				},
			},
			( res ) => {
				let data = '';
				res.on( 'data', ( chunk ) => ( data += chunk ) );
				res.on( 'end', () => {
					if ( data.includes( '<error>0</error>' ) ) {
						resolve();
					} else {
						reject(
							new Error(
								`Trackback failed. Response: ${ data }`
							)
						);
					}
				} );
			}
		);
		req.on( 'error', reject );
		req.write( body );
		req.end();
	} );
}
