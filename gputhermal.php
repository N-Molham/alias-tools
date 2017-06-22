<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 18-Jun-17
 * Time: 12:28 PM
 */

use EasyGit\Repository;

error_reporting( E_ALL );

// to load packages
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

// command interface instance
$cmd = new \League\CLImate\CLImate();

// default thermal info file path
$file_path = empty( $argv[1] ) ? 'D:\Google Drive\GPU-Z Sensor Log.txt' : $argv[1];
if ( !file_exists( $file_path ) || !is_readable( $file_path ) )
{
	// error loading file
	dd( 'File does not exists or unreadable!' );
}

// open file
$file_handler = fopen( $file_path, 'rb' );
if ( false === $file_path )
{
	dd( 'Error opening file' );
}

// table headers/titles
$table_headers = array_map( 'trim', explode( ',', fgets( $file_handler ) ) );
$row_separator = str_repeat( '=', max( array_map( 'mb_strlen', $table_headers ) ) );

// the last 5 readings
$recent_reading = array_map( function ( $line )
{
	return array_map( 'trim', explode( ',', $line ) );
}, array_map( 'trim', explode( "\n", file_tail( $file_handler, 5 ) ) ) );

$table_rows = [];
foreach ( $recent_reading as $reading )
{
	foreach ( $reading as $col_index => $col_value )
	{
		$table_rows[] = [
			$table_headers[ $col_index ],
			$col_value,
		];
	}

	$table_rows[] = [ $row_separator, $row_separator ];
}

$cmd->table( $table_rows );

/**
 * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
 *
 *
 *
 * @author Torleif Berger, Lorenzo Stanco
 * @link http://stackoverflow.com/a/15025877/995958
 * @license http://creativecommons.org/licenses/by/3.0/
 *
 * @param resource $file_handler
 * @param int      $lines
 * @param bool     $adaptive
 *
 * @return string
 */
function file_tail( $file_handler, $lines = 1, $adaptive = true )
{
	// Sets buffer size, according to the number of lines to retrieve.
	// This gives a performance boost when reading a few lines from the file.
	$buffer_size = !$adaptive ? 4096 : ( $lines < 2 ? 64 : ( $lines < 10 ? 512 : 4096 ) );

	// Jump to last character
	fseek( $file_handler, -1, SEEK_END );

	// Read it and adjust line number if necessary
	// (Otherwise the result would be wrong if file doesn't end with a blank line)
	if ( fread( $file_handler, 1 ) !== "\n" )
	{
		--$lines;
	}

	// Start reading
	$output = '';
	$chunk  = '';

	// While we would like more
	while ( ftell( $file_handler ) > 0 && $lines >= 0 )
	{
		// Figure out how far back we should jump
		$seek = min( ftell( $file_handler ), $buffer_size );

		// Do the jump (backwards, relative to where we are)
		fseek( $file_handler, -$seek, SEEK_CUR );

		// Read a chunk and prepend it to our output
		$output = ( $chunk = fread( $file_handler, $seek ) ) . $output;

		// Jump back to where we started reading
		fseek( $file_handler, -mb_strlen( $chunk, '8bit' ), SEEK_CUR );

		// Decrease our line counter
		$lines -= substr_count( $chunk, "\n" );
	}

	// While we have too many lines
	// (Because of buffer size we might have read too many)
	while ( $lines++ < 0 )
	{
		// Find first newline and remove all text before that
		$output = substr( $output, strpos( $output, "\n" ) + 1 );
	}

	// Close file and return
	fclose( $file_handler );

	return trim( $output );
}
