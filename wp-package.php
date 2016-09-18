<?php

// plugin name
$plugin_name = $argv[1];
$plugin_version = isset( $argv[2] ) ? $argv[2] : null;

// excludes
$excludes = [
	'node_modules/**\*',
	'node_modules/',
	'.hg/**\*',
	'.hg/',
	'.git/**\*',
	'.git/',
	'.gitignore',
	'.hgignore',
	'.sass-cache',
];

// shell command
$command = [ 
	'zip -9 -r',
	$plugin_version ? '{plugin_name}-ver-{plugin_version}.zip' : '{plugin_name}.zip',
	'{plugin_name} -x',
];

// build final shell command
foreach ( $excludes as $exclude_path )
{
	$command[] = '{plugin_name}/' . $exclude_path;
}

// the final command
$command = str_replace( [ '{plugin_name}', '{plugin_version}' ], [ $plugin_name, $plugin_version ], implode( ' ', $command ) );

preg_match( '/[a-z0-9\-\.]+\.zip/', $command, $matchs );
$plugin_zip = array_shift( $matchs );

if ( file_exists( $plugin_zip ) )
{
	// delete file first to replace
	unlink( $plugin_zip );
}

// execute it
shell_exec( $command );