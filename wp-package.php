<?php
error_reporting( E_ALL );

// target package name
$package_name = $argc >= 2 ? preg_replace( '/\\\$/', '', $argv[1] ) : null;

if ( null === $package_name )
{
	// none passed
	die( 'ERROR: invalid package!' );
}

// current working directory
$current_dir = getcwd();

// full package path
$full_path = $current_dir . DIRECTORY_SEPARATOR . $package_name;
if ( '\\' !== $full_path[ strlen( $full_path ) - 1 ] )
{
	$full_path .= '\\';
}

if ( !file_exists( $full_path ) )
{
	die( 'ERROR: invalid package path, "' . $full_path . '"' );
}

// open dir
$package_dir = dir( $full_path );

// override package name
$package_name = isset( $argv[3] ) ? ( 'same' === $argv[3] || 'null' === $argv[3] ? $package_name : $argv[3] ) : $package_name;

// version
$package_ver = isset( $argv[2] ) ? $argv[2] : null;
if ( null === $package_ver )
{
	// package main files
	$package_info = null;
	$direct_files = array_merge( [ 'init.php', 'style.css' ], scandir( $full_path ) );
	foreach ( $direct_files as $file_name )
	{
		$file_name = $full_path . $file_name;
		if ( is_dir( $file_name ) || !file_exists( $file_name ) )
		{
			// skip directories
			continue;
		}

		$package_info = get_file_data( $file_name, [ 'version' => 'Version', ] );
		if ( isset( $package_info['version'] ) && !empty( $package_info['version'] ) )
		{
			// stop as the target version found
			// set package version
			$package_ver = $package_info['version'];
		}
	}
}

// append version if set
$package_name = $package_ver ? $package_name . '-ver-' . $package_ver : $package_name;

// package zip
$package_zip = new ZipArchive();
$package_zip->open( $current_dir . DIRECTORY_SEPARATOR . $package_name . '.zip', ZipArchive::OVERWRITE );

HZip::zipDir( $full_path, $current_dir . DIRECTORY_SEPARATOR . $package_name . '.zip' );

class HZip
{
	/**
	 * Files skip list
	 *
	 * @var array
	 */
	private static $skip_list = [
		'.',
		'..',
		'.hg',
		'.git',
		'.idea',
		'tmp',
		'.hg_archival.txt',
		'.env',
		'.env.example',
		'.project',
		'node_modules',
	];

	/**
	 * Add files and sub-directories in a folder to zip file.
	 *
	 * @param string     $folder
	 * @param ZipArchive $zip_file
	 * @param int        $exclusive_length Number of text to be exclusived from the file path.
	 *
	 * @return void
	 */
	private static function folder_to_zip( $folder, &$zip_file, $exclusive_length )
	{
		$handle = opendir( $folder );
		while ( false !== ( $f = readdir( $handle ) ) )
		{
			if ( in_array( $f, self::$skip_list ) )
			{
				// skip
				continue;
			}

			// build file path
			$file_path = $folder . $f;

			// Remove prefix from file path before add to zip.
			$local_path = substr( $file_path, $exclusive_length );

			if ( is_file( $file_path ) )
			{
				// append file
				$zip_file->addFile( $file_path, $local_path );
			}
			elseif ( is_dir( $file_path ) )
			{
				// Add sub-directory.
				$zip_file->addEmptyDir( $local_path );
				self::folder_to_zip( $file_path . DIRECTORY_SEPARATOR, $zip_file, $exclusive_length );
			}
		}
		closedir( $handle );
	}

	/**
	 * Zip a folder (include itself).
	 *
	 * @param string $source_path Path of directory to be zip.
	 * @param string $outZipPath Path of output zip file.
	 *
	 * @return void
	 */
	public static function zipDir( $source_path, $outZipPath )
	{
		$path_info   = pathinfo( $source_path );
		$parent_path = $path_info['dirname'];
		$dir_name    = $path_info['basename'];

		$zip_file = new ZipArchive();
		$zip_file->open( $outZipPath, ZipArchive::OVERWRITE );
		$zip_file->addEmptyDir( $dir_name );
		self::folder_to_zip( $source_path, $zip_file, strlen( $parent_path . DIRECTORY_SEPARATOR ) );
		$zip_file->close();
	}
}

/**
 * Retrieve metadata from a file.
 *
 * Searches for metadata in the first 8kiB of a file, such as a plugin or theme.
 * Each piece of metadata must be on its own line. Fields can not span multiple
 * lines, the value will get cut at the end of the first line.
 *
 * If the file data is not within that first 8kiB, then the author should correct
 * their plugin file and move the data headers to the top.
 *
 * @link https://codex.wordpress.org/File_Header
 *
 * @since 2.9.0
 *
 * @param string $file Path to the file.
 * @param array  $default_headers List of headers, in the format array('HeaderKey' => 'Header Name').
 * @param string $context Optional. If specified adds filter hook "extra_{$context}_headers".
 *                                Default empty.
 *
 * @return array Array of file headers in `HeaderKey => Header Value` format.
 */
function get_file_data( $file, $default_headers, $context = '' )
{
	// We don't need to write to the file, so just open for reading.
	$fp = fopen( $file, 'r' );

	// Pull only the first 8kiB of the file in.
	$file_data = fread( $fp, 8192 );

	// PHP will close file handle, but we are good citizens.
	fclose( $fp );

	// Make sure we catch CR-only line endings.
	$file_data = str_replace( "\r", "\n", $file_data );

	/**
	 * Filter extra file headers by context.
	 *
	 * The dynamic portion of the hook name, `$context`, refers to
	 * the context where extra headers might be loaded.
	 *
	 * @since 2.9.0
	 *
	 * @param array $extra_context_headers Empty array by default.
	 */
	if ( $context && $extra_headers = array() )
	{
		$extra_headers = array_combine( $extra_headers, $extra_headers ); // keys equal values
		$all_headers   = array_merge( $extra_headers, (array) $default_headers );
	}
	else
	{
		$all_headers = $default_headers;
	}

	foreach ( $all_headers as $field => $regex )
	{
		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] )
		{
			$all_headers[ $field ] = _cleanup_header_comment( $match[1] );
		}
		else
		{
			$all_headers[ $field ] = '';
		}
	}

	return $all_headers;
}

/**
 * Strip close comment and close php tags from file headers used by WP.
 *
 * @since 2.8.0
 * @access private
 *
 * @see https://core.trac.wordpress.org/ticket/8497
 *
 * @param string $str Header comment to clean up.
 *
 * @return string
 */
function _cleanup_header_comment( $str )
{
	return trim( preg_replace( "/\s*(?:\*\/|\?>).*/", '', $str ) );
}