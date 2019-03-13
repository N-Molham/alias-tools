const sha1  = require( 'sha1' );
const https = require( 'https' );

/**
 * Generate safe password check by "Have I been Pwned API"
 *
 * @see https://haveibeenpwned.com/API/v2#PwnedPasswords
 */
class Safe_Password_Generator {

	/**
	 * @see https://developer.wordpress.org/reference/functions/wp_generate_password/
	 *
	 * @param {Number}  length
	 * @param {Boolean} special_chars
	 * @param {Boolean} extra_special_chars
	 *
	 * @return {String}
	 */
	static generate_password( length = 12, special_chars = true, extra_special_chars = false ) {

		let password = '',
		    chars    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		if ( special_chars ) {

			chars += '!@#$%^&*()';

		}

		if ( extra_special_chars ) {

			chars += '-_ []{}<>~`+=,.;:/?|';

		}

		for ( let i = 0; i < length; i++ ) {

			password += chars.substr( Math.floor( Math.random() * (chars.length - 1) ), 1 );

		}

		return password;

	}

	/**
	 * Check if password is pwned by the API
	 *
	 * @param {String} password_hash the password hashed using SHA-1
	 * @return {Promise<Number|Boolean|String>}
	 */
	static is_pwned( password_hash ) {

		// extract matching parts
		password_hash = password_hash.toUpperCase();
		let prefix    = password_hash.substr( 0, 5 );
		let suffix    = password_hash.substr( 5 );

		return new Promise( function ( resolve, reject ) {

			// make the HTTPS request
			https.get( 'https://api.pwnedpasswords.com/range/' + prefix, function ( response ) {

				let results  = '',
				    is_error = 200 !== response.statusCode;

				response.on( 'data', function ( chunk ) {

					results += chunk;

				} );

				response.on( 'end', function () {

					if ( is_error ) {

						reject( results );
						return;

					}

					// find a match
					let match_pos = results.indexOf( suffix );

					// good news, not nothing found
					if ( match_pos < 0 ) {

						resolve( false );
						return;

					}

					// get recurrence position
					let suffix_end_pos = match_pos + 36;
					let line_break_pos = results.indexOf( "\n", match_pos );

					resolve( parseInt( results.substr( suffix_end_pos, line_break_pos - suffix_end_pos ).trim() ) );

				} );

			} ).on( 'error', function ( error ) {

				reject( error.message );

			} );

		} );

	}

}
