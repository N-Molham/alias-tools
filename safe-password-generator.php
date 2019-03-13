<?php

/**
 * Generate safe password check by "Have I been Pwned API"
 * 
 * @see https://haveibeenpwned.com/API/v2#PwnedPasswords
 */
class Safe_Password_Generator {

	/**
	 * @see https://developer.wordpress.org/reference/functions/wp_generate_password/
	 *
	 * @param int  $length
	 * @param bool $special_chars
	 * @param bool $extra_special_chars
	 *
	 * @return string
	 */
	public static function generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {

		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			
		if ( $special_chars ) {

			$chars .= '!@#$%^&*()';

		}

		if ( $extra_special_chars ) {
			
			$chars .= '-_ []{}<>~`+=,.;:/?|';

		}
	 
		$password = '';

		for ( $i = 0; $i < $length; $i++ ) {

			$password .= substr( $chars, self::rand( 0, strlen( $chars ) - 1 ), 1 );

		}

		$is_pwned = self::is_pwned( sha1( $password ) );

		if ( is_int( $is_pwned ) && $is_pwned > 0 ) {

			$password = self::generate_password( $length, $special_chars, $extra_special_chars );

		}

		return $password;

	}

	/**
	 * @see https://developer.wordpress.org/reference/functions/wp_rand/
	 * 
	 * @param int $min
	 * @param int $max
	 *
	 * @return int
	 */
	public static function rand( $min = 0, $max = 0 ) {

		global $rnd_value;
 
		// Some misconfigured 32bit environments (Entropy PHP, for example) truncate integers larger than PHP_INT_MAX to PHP_INT_MAX rather than overflowing them to floats.
		$max_random_number = 3000000000 === 2147483647 ? (float) '4294967295' : 4294967295; // 4294967295 = 0xffffffff
	 
		// We only handle Ints, floats are truncated to their integer value.
		$min = (int) $min;
		$max = (int) $max;
	 
		// Use PHP's CSPRNG, or a compatible method
		static $use_random_int_functionality = true;
		if ( $use_random_int_functionality ) {
			try {
				$_max = ( 0 != $max ) ? $max : $max_random_number;
				// wp_rand() can accept arguments in either order, PHP cannot.
				$_max = max( $min, $_max );
				$_min = min( $min, $_max );
				$val  = random_int( $_min, $_max );
				if ( false !== $val ) {
					return absint( $val );
				} else {
					$use_random_int_functionality = false;
				}
			} catch ( Error $e ) {
				$use_random_int_functionality = false;
			} catch ( Exception $e ) {
				$use_random_int_functionality = false;
			}
		}
	 
		// Reset $rnd_value after 14 uses
		// 32(md5) + 40(sha1) + 40(sha1) / 8 = 14 random numbers from $rnd_value
		if ( strlen( $rnd_value ) < 8 ) {
			
			static $seed = '';
			
			$rnd_value  = md5( uniqid( microtime() . mt_rand(), true ) . $seed );
			$rnd_value .= sha1( $rnd_value );
			$rnd_value .= sha1( $rnd_value . $seed );
			$seed       = md5( $seed . $rnd_value );

		}
	 
		// Take the first 8 digits for our value
		$value = substr( $rnd_value, 0, 8 );
	 
		// Strip the first eight, leaving the remainder for the next call to wp_rand().
		$rnd_value = substr( $rnd_value, 8 );
	 
		$value = abs( hexdec( $value ) );
	 
		// Reduce the value to be within the min - max range
		if ( $max != 0 ) {
			$value = $min + ( $max - $min + 1 ) * $value / ( $max_random_number + 1 );
		}
	 
		return abs( intval( $value ) );

	}

	/**
	 * Check if password is pwned by the API
	 * 
	 * @param string $password_hash the password hashed using SHA-1
	 *
	 * @return false|int|string int if a match found, string on error, otherwise false
	 */
	public static function is_pwned( $password_hash ) {

		// extract matching parts
		$password_hash = strtoupper( $password_hash );
		$prefix = substr( $password_hash, 0, 5 );
		$suffix = substr( $password_hash, 5 );

		// make the HTTPS request
		$curl = curl_init( 'https://api.pwnedpasswords.com/range/' . $prefix );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$results = curl_exec( $curl );

		// check if results came back successfully, otherwise, return the error message
		if ( 200 !== curl_getinfo( $curl, CURLINFO_HTTP_CODE ) ) {

			return $results;

		}

		// close connection
		curl_close( $curl );

		// find a match
		$match_pos = strpos( $results, $suffix );

		// good news, not nothing found
		if ( false === $match_pos ) {

			return false;

		}

		// get recurrence position
		$suffix_end_pos = $match_pos + 36;
		$line_break_pos = strpos( $results, "\n", $match_pos );

		return (int) trim( substr( $results, $suffix_end_pos, $line_break_pos - $suffix_end_pos ) );

	}

}
