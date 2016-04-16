<?php
error_reporting( E_ALL );

require_once 'vendor/autoload.php';

// new or update
$new_instance = isset( $argv[1] ) && 'new' === $argv[1];

// installation name
$instance_name = isset( $argv[2] ) ? preg_replace( '/\\\$/', '', $argv[2] ) : null;

// configs
$wp_config = [
	'dbname'    => '?',
	'dbuser'    => '?',
	'dbpass'    => '',
	'dbhost'    => 'localhost',
	'dbprefix'  => 'wp_',
	'dbcharset' => 'utf8',
	'dbcollate' => '',
	'locale'    => '',
];

$arg_index = 3;
foreach ( $wp_config as $config_name => $config_value )
{
	$arg_value = isset( $argv[ $arg_index ] ) ? $argv[ $arg_index ] : null;
	if ( '?' === $config_value && null === $arg_value )
	{
		dd( sprintf( 'Missing config [%s] argument number [%s]', $config_name, $arg_index ) );
	}

	// save config value
	$wp_config[ $config_name ] = null !== $arg_value ? $arg_value : $config_value;

	// next argument
	$arg_index++;
}
unset( $config_name, $config_value, $arg_value, $arg_index );

if ( null === $instance_name )
{
	// none passed
	dd( 'ERROR: invalid instance name!' );
}

// current working directory
$current_dir = getcwd();

// final instance location path
$full_path = $current_dir . DIRECTORY_SEPARATOR . $instance_name;
$temp_path = $current_dir . DIRECTORY_SEPARATOR . 'wordpress';

if ( $new_instance && file_exists( $full_path ) )
{
	dd( 'Error: instance directory already exists' );
}

if ( !$new_instance && !file_exists( $full_path ) )
{
	dd( 'Error: Can not find instance directory to update' );
}

if ( $new_instance && file_exists( $temp_path ) )
{
	dd( 'Error: instance temp directory already exists' );
}

if ( $new_instance )
{
	dump( 'Create new instance' );

	dump( 'Checking for WordPress latest version ...' );

	// WP info
	$wp_info = unserialize( file_get_contents( 'https://api.wordpress.org/core/version-check/1.6/' ) );
	if ( !is_array( $wp_info ) )
	{
		dd( 'ERROR: unable to load latest WP status' );
	}

	$wp_info = $wp_info['offers'][0];
	dump( 'Last version is: ' . $wp_info['current'] );

	// first offer
	$package_name = __DIR__ . DIRECTORY_SEPARATOR . sprintf( 'wordpress-%s.zip', $wp_info['current'] );

	if ( file_exists( $package_name ) )
	{
		// already downloaded
		dump( 'Use cached copy.' );
	}
	else
	{
		// download the last version first
		dump( 'Downloading zip file > ' . $wp_info['download'] );

		// start downloading
		file_put_contents( $package_name, file_get_contents( $wp_info['download'] ) );

		dump( 'Download done' );
	}

	// ZIP instance
	$zip      = new ZipArchive();
	$open_zip = $zip->open( $package_name );

	if ( true === $open_zip )
	{
		dump( 'Extracting zip file ...' );
		$zip->extractTo( $current_dir );
		$zip->close();
	}
	else
	{
		dd( 'Error: unable to open package zip file, ' . $open_zip );
	}

	dump( 'Extraction completed' );

	if ( rename( $current_dir . DIRECTORY_SEPARATOR . 'wordpress', $full_path ) )
	{
		dump( sprintf( 'Instance renamed to "%s"', $instance_name ) );
	}
	else
	{
		dd( 'Error: Unable to rename extracted directory.' );
	}
}

// cwd to the instance
chdir( $full_path );

// check if WP CLI installed
$wpcli_check = shell_exec( 'wp core' );
if ( strpos( $wpcli_check, 'wp core config' ) === false )
{
	dd( 'Error: WP CLI is not installed' );
}

$config_cmd = 'wp core config';
foreach ( $wp_config as $config_name => $config_value )
{
	$config_cmd .= sprintf( ' --%s="%s"', $config_name, $config_value );
}

dd( shell_exec( $config_cmd ) );

if ( !function_exists( 'dd' ) ):
	function dd( $args )
	{
		call_user_func_array( 'dump', func_get_args() );
		die();
	}
endif;