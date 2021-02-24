<?php
use EasyGit\Repository;

error_reporting( E_ALL );

// to load packages
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

// command interface instance
$cmd = new \League\CLImate\CLImate();

$fuse_score_params = [
	'f' => 'Frequency',
	'u' => 'Users',
	's' => 'Severity',
	'e' => 'Effort',
];

$fuse_score_vars = [
	'f' => 0,
	'u' => 0,
	's' => 0,
	'e' => 0,
];

foreach ( $fuse_score_params as $var => $label ) {

	// requires info
	$value = prompt_input( $label, true, '/^\d+$/' );

	$fuse_score_vars[ $var ] = (int) $value;
}

$fuse_score = round( ( $fuse_score_vars['f'] * $fuse_score_vars['u'] * $fuse_score_vars['s'] ) / $fuse_score_vars['e'], 2 );

$cmd->info( 'FUSE score: ' . $fuse_score . ' ~= ' . round( $fuse_score ) );

/**
 * Prompt user for input some info
 *
 * @param string $hint
 * @param bool $is_required
 * @param string $valid_regex
 * @return string
 */
function prompt_input( $hint, $is_required = false, $valid_regex = '' ) {
	global $cmd;

	$input_value = ( $cmd->input( $hint . ' >' ) )->prompt();

	if ( $is_required && empty( $input_value ) ) {
		$cmd->error( 'This input is required' );

		return prompt_input( $hint, $is_required );
	}

	if ( ! empty( $input_value ) && ! empty( $valid_regex ) && ! preg_match( $valid_regex, $input_value ) ) {
		$cmd->error( 'Not a valid input' );

		return prompt_input( $hint, $is_required, $valid_regex );
	}

	return $input_value;
}