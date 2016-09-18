<?php

// target estimate
$estimate = isset( $argv[1] ) ? abs( floatval( $argv[1] ) ) : null;
if ( empty( $estimate ) )
{
	// skip
	dd( 'Invalid estimate input' );
}

dump( sprintf( "Client's amount: $%s", number_format( $estimate + ( $estimate * 0.15 ), 2 ) ) );
dump( sprintf( "Earned amount:   $%s", number_format( $estimate - ( $estimate * 0.10 ), 2 ) ) );