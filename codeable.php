<?php

// autoload packages
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

// Command Line tool initialization
$cli = new League\CLImate\CLImate;

// target estimate
$estimate_best = isset( $argv[1] ) ? abs( (float) $argv[1] ) : null;
if ( empty( $estimate_best ) )
{
	// prompt message
	$input = $cli->input( 'Your estimate:' );

	// accept only positive float/int number
	$input->accept( function ( $response )
	{
		$response = abs( (float) $response );

		return $response > 0;
	} );

	// prompt for amount
	$estimate_best = $input->prompt();
}

// Excepted case scenario = estimate + 70%
$estimate_expected = $estimate_best + ( $estimate_best * 0.7 );

// worst case scenario = will take 3 time needed time
$estimate_worst = $estimate_best * 3;

// final estimate formula to add risk factor cost
$estimate_final = ( $estimate_best + ( 4 * $estimate_expected ) + $estimate_worst ) / 6;

// output
$cli->lightGreenTable( [
	[
		'Case',
		'Var',
		'Formula',
		'Amount',
	],
	[
		'Best Case',
		'BC',
		'BC',
		amount( $estimate_best ),
	],
	[
		'Excepted Case',
		'EC',
		'BC + 70%',
		amount( $estimate_expected ),
	],
	[
		'Worst Case',
		'WC',
		'3xBC',
		amount( $estimate_worst ),
	],
	[
		'Final Case',
		'FC',
		'( BC + 4xEC + WC ) / 6',
		amount( $estimate_final ),
	],
	[
		'Client Amount',
		'CA',
		'FC + 15%',
		amount( $estimate_final + ( $estimate_final * 0.15 ) ),
	],
	[
		'Earned amount',
		'EA',
		'FC - 10%',
		amount( $estimate_final - ( $estimate_final * 0.10 ) ),
	],
] );

/**
 * US$ Money format given amount
 *
 * @param int|float $amount
 *
 * @return string
 */
function amount( $amount )
{
	return sprintf( '$%s', number_format( $amount, 2 ) );
}