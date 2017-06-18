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

// plugin info
$plugin_info = [
	'plugin_name'    => '*Plugin Name',
	'plugin_desc'    => 'Plugin Description',
	'plugin_slug'    => 'Plugin Slug (leave empty to generate from plugin\'s name)',
	'text_domain'    => 'Text Domain (leave empty use plugin\'s slug)',
	'naming_prefix'  => 'Naming Prefix (leave empty to generate from plugin\'s name)',
	'namespace'      => 'Namespace (leave empty to generate from plugin\'s name)',
	'author_name'    => 'Plugin Author Name',
	'author_email'   => 'Plugin Author Email',
	'author_website' => 'Plugin Author Website',
];

// default values
$plugin_defaults = [
	'author_name'    => 'Nabeel Molham',
	'author_email'   => 'n.molham@gmail.com',
	'author_website' => 'https://nabeel.molham.me/',
];

foreach ( $plugin_info as $info_key => $info_label )
{
	// check if input is required or not
	$is_required = 0 === strpos( $info_label, '*' );
	$info_label  = str_replace( '*', '', $info_label );

	// requires info
	$info_value = prompt_input( $info_label, $is_required, 'namespace' === $info_key ? '/^[a-zA-Z][a-zA-Z0-9_]+((\\[a-zA-Z][a-zA-Z0-9_]+)+)*/' : '' );

	if ( empty( $info_value ) && array_key_exists( $info_key, $plugin_defaults ) )
	{
		// use default value then
		$info_value = $plugin_defaults[ $info_key ];
	}

	switch ( $info_key )
	{
		case 'plugin_slug':
			if ( empty( $info_value ) )
			{
				// generate the slug from plugin's name
				$info_value = preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '-', mb_strtolower( $plugin_info['plugin_name'] ) ) );
			}
			break;
		case 'text_domain':
			if ( empty( $info_value ) )
			{
				// use plugin's slug
				$info_value = $plugin_info['plugin_slug'];
			}
			break;
		case 'naming_prefix':
			if ( empty( $info_value ) )
			{
				// generate based on plugin's name
				preg_match_all( '/[A-Z]/', $plugin_info['plugin_name'], $matches );
				$info_value = mb_strtolower( implode( '', array_map( 'trim', $matches[0] ) ) ) . '_';
			}
			else
			{
				// sanitize
				$info_value = preg_replace( '/[^a-z_]/', '', mb_strtolower( preg_match( '/_$/', $info_value ) ? $info_value : $info_value . '_' ) );
			}
			break;
		case 'namespace':
			if ( empty( $info_value ) )
			{
				// generate based on plugin's name
				$info_value = preg_replace( '/\s+/', '_', mb_convert_case( mb_strtolower( $info_value ), MB_CASE_TITLE ) );
			}
			break;
	}

	$plugin_info[ $info_key ] = $info_value;
	unset( $info_value, $is_required, $info_label );
}

create_repo( $plugin_info );

/**
 * Create plugin
 *
 * @param $plugin_info
 *
 * @return void
 */
function create_repo( $plugin_info )
{
	global $cmd;

	$dir_exists = file_exists( $plugin_info['plugin_slug'] );

	if ( $dir_exists )
	{
		$continue = $cmd->input( 'Directory exists with the same name, would you like to continue?' );
		$continue->accept( [ 'Y', 'N' ], true );

		if ( 'n' === mb_strtolower( $continue->prompt() ) )
		{
			$cmd->info( 'Exit' );

			// skip
			return;
		}
	}

	if ( false === $dir_exists )
	{
		// clone boilerplate plugin
		$cmd->info( 'Cloning boilerplate plugin...' );
		Repository::cloneFromUrl( 'https://github.com/N-Molham/wp-plugins-boilerplate.git', $plugin_info['plugin_slug'] );
	}

	$plugin_old_file = $plugin_info['plugin_slug'] . DIRECTORY_SEPARATOR . 'init.php';
	if ( file_exists( $plugin_old_file ) )
	{
		// rename main file
		$plugin_new_file = str_replace( 'init.', $plugin_info['plugin_slug'] . '.', $plugin_old_file );
		$cmd->info( sprintf( 'Renaming plugin\'s main file "%s" > "%s"', $plugin_old_file, $plugin_new_file ) );
		rename( $plugin_old_file, $plugin_new_file );
	}

	// generate replace array
	$replace_matches = [
		'WP Plugins Boilerplate'   => $plugin_info['plugin_name'],
		'Plugin Description'       => $plugin_info['plugin_desc'],
		'init.php'                 => $plugin_info['plugin_slug'] . '.php',
		'wp_plugin_boilerplate'    => str_replace( '-', '_', $plugin_info['plugin_slug'] ),
		'wp-plugin-boilerplate'    => $plugin_info['plugin_slug'],
		'wp-plugin-domain'         => $plugin_info['text_domain'],
		'wppb_'                    => $plugin_info['naming_prefix'],
		'WPPB_'                    => mb_strtoupper( $plugin_info['naming_prefix'] ),
		'WP_Plugins\Boilerplate'   => $plugin_info['namespace'],
		'Nabeel Molham'            => $plugin_info['author_name'],
		'http://nabeel.molham.me/' => $plugin_info['author_website'],
		'n.molham@gmail.com'       => $plugin_info['author_email'],
	];

	$cmd->info( 'Loading PHP & JS files...' );

	// get PHP & JS files
	$plugin_files = array_filter( get_dir_files( $plugin_info['plugin_slug'] ), function ( $file_name )
	{
		return preg_match( '/(\.(php|js|md))$/', $file_name );
	} );

	foreach ( $plugin_files as $file_name )
	{
		$cmd->comment( sprintf( 'Replace text in "%s"', $file_name ) );

		file_put_contents(
			$file_name,
			str_replace(
				array_keys( $replace_matches ),
				array_values( $replace_matches ),
				file_get_contents( $file_name )
			)
		);
	}
}

function get_dir_files( $dir, &$scan_files = [] )
{
	$files = scandir( $dir, SCANDIR_SORT_NONE );

	foreach ( $files as $file_name )
	{
		$path = realpath( $dir . DIRECTORY_SEPARATOR . $file_name );
		if ( !in_array( $file_name, [ '.', '..', '.git' ], true ) )
		{
			if ( is_dir( $path ) )
			{
				get_dir_files( $path, $scan_files );
				$scan_files[] = $path;
			}
			else
			{
				$scan_files[] = $path;
			}
		}
	}

	return $scan_files;
}


/**
 * Prompt user for input some info
 *
 * @param string $hint
 * @param bool   $is_required
 * @param string $valid_regex
 *
 * @return string
 */
function prompt_input( $hint, $is_required = false, $valid_regex = '' )
{
	global $cmd;

	$input_value = ( $cmd->input( $hint . ' >' ) )->prompt();

	if ( $is_required && empty( $input_value ) )
	{
		$cmd->error( 'This input is required' );

		return prompt_input( $hint, $is_required );
	}

	if ( !empty( $input_value ) && !empty( $valid_regex ) && !preg_match( $valid_regex, $input_value ) )
	{
		$cmd->error( 'Not a valid input' );

		return prompt_input( $hint, $is_required, $valid_regex );
	}

	return $input_value;
}