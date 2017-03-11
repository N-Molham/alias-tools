<?php

require_once 'vendor/autoload.php';

$cli = new League\CLImate\CLImate;

// target estimate
$estimate_best = isset( $argv[1] ) ? abs( floatval( $argv[1] ) ) : null;
if ( empty( $estimate_best ) )
{
	// skip
	dd( 'Invalid estimate input' );
}

// Excepted case scenario = estimate + 70%
$estimate_excpected = $estimate_best + ( $estimate_best * 0.7 );

// worst case scenario = will take 3 time needed time
$estimate_worst = $estimate_best * 3;

// final estimate formula
$estimate_final = ( $estimate_best + ( 4 * $estimate_excpected ) + $estimate_worst ) / 6;

// output
$cli->lightGreenTable( [
	[ 'Best Case', 		'B', 								amount( $estimate_best ) ],
	[ 'Excepted Case', 	'EX = B + 70%', 					amount( $estimate_excpected ) ],
	[ 'Worst Case', 	'W = 3 x B', 						amount( $estimate_worst ) ],
	[ 'Final Case', 	'F = ( B + ( 4 x EX ) + W ) / 6', 	amount( $estimate_final ) ],
	[ 'Client Amount', 	'F + 15%', 							amount( $estimate_final ) ],
	[ 'Earned amount', 	'F - 10%', 							amount( $estimate_final ) ],
] );

function amount( $amount ) 
{
	return sprintf( '$%s', number_format( $amount, 2 ) );
}