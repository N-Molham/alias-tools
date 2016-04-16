<?php
error_reporting( E_ALL );

require_once 'vendor/autoload.php';

// new installation name
$instance_name = $argc >= 2 ? preg_replace( '/\\\$/', '', $argv[1] ) : null;
$new_instance  = !( isset( $argv[2] ) && 'update' === $argv[2] );

if ( null === $instance_name )
{
	// none passed
	die( 'ERROR: invalid instance name!' );
}

// current working directory
$current_dir = getcwd();

// final instance location path
$full_path = $current_dir . DIRECTORY_SEPARATOR . $instance_name;
$temp_path = $current_dir . DIRECTORY_SEPARATOR . 'wordpress';

if ( $new_instance && file_exists( $full_path ) )
{
	die( 'Error: instance directory already exists' );
}

if ( !$new_instance && !file_exists( $full_path ) )
{
	die( 'Error: Can not find instance directory to update' );
}

if ( $new_instance && file_exists( $temp_path ) )
{
	die( 'Error: instance temp directory already exists' );
}

if ( $new_instance )
{
	// WP info
	$wp_info = unserialize( file_get_contents( 'https://api.wordpress.org/core/version-check/1.6/' ) );
	if ( !is_array( $wp_info ) )
	{
		die( 'ERROR: unable to load latest WP status' );
	}

	// first offer
	$wp_info      = $wp_info['offers'][0];
	$package_name = __DIR__ . DIRECTORY_SEPARATOR . sprintf( 'wordpress-%s.zip', $wp_info['current'] );

	if ( !file_exists( $package_name ) )
	{
		// download the last version first
		echo 'Latest wp version zip file not found, Downloading > ', $wp_info['download'], PHP_EOL;

		// start downloading
		file_put_contents( $package_name, file_get_contents( $wp_info['download'] ) );

		echo 'Download done', PHP_EOL;
	}

	// ZIP instance
	$zip      = new ZipArchive();
	$open_zip = $zip->open( $package_name );

	if ( true === $open_zip )
	{
		echo 'Extracting zip file ...', PHP_EOL;
		$zip->extractTo( $current_dir );
		$zip->close();
	}
	else
	{
		die( 'Error: unable to open package zip file, ' . $open_zip );
	}

	echo 'Extraction completed', PHP_EOL;

	if ( rename( $current_dir . DIRECTORY_SEPARATOR . 'wordpress', $full_path ) )
	{
		printf( 'Instance renamed to "%s"', $instance_name );
	}
	else
	{
		die( 'Error: Unable to rename extracted directory.' );
	}
}
else
{
	dump( 'update instance' );
}