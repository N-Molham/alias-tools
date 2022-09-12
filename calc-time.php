<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 18-Jun-17
 * Time: 12:28 PM
 */

error_reporting(E_ALL);

// time input
$time = $argv[1] ?? '0';

// hours/minutes
$time_parts = explode(':', $time);

$overage = 0.3;

// cast
$time_parts = array_map('abs', array_map('intval', $time_parts));

// hours to minutes
$time_parts[0] *= 60;

// add overage
$time_parts = array_map(static function ($part) use ($overage) {
    return (int) round($part + ($part * $overage));
}, $time_parts);

// final/total hours
echo 'Total/Final Hours: [', round(array_sum($time_parts) / 60), ']';
