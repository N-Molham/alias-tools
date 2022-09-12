<?php

use League\CLImate\CLImate;

error_reporting(E_ALL);

// to load packages
require_once __DIR__.DIRECTORY_SEPARATOR.'vendor/autoload.php';

// command interface instance
$cmd = new CLImate();

$fuseScoreParams = [
    'f' => 'Frequency',
    'u' => 'Users',
    's' => 'Severity',
    'e' => 'Effort',
];

$fuseScoreVars = [
    'f' => 0,
    'u' => 0,
    's' => 0,
    'e' => 0,
];

foreach ($fuseScoreParams as $var => $label) {

    // requires info
    $value = prompt_input($label, true, '/^\d+$/');

    $fuseScoreVars[$var] = (int) $value;
}

$fuseScore = round(($fuseScoreVars['f'] * $fuseScoreVars['u'] * $fuseScoreVars['s']) / $fuseScoreVars['e'], 2);

$cmd->info('FUSE score: '.$fuseScore.' ~= '.round($fuseScore));

/**
 * Prompt user for input some info
 *
 * @param string $hint
 * @param bool $isRequired
 * @param string $validRegex
 * @return string
 * @throws Exception
 */
function prompt_input(string $hint, bool $isRequired = false, string $validRegex = '') : string
{
    global $cmd;

    $inputValue = ($cmd->input($hint.' >'))->prompt();

    if ($isRequired && empty($inputValue)) {
        $cmd->error('This input is required');

        return prompt_input($hint, $isRequired);
    }

    if (! empty($inputValue) && ! empty($validRegex) && ! preg_match($validRegex, $inputValue)) {
        $cmd->error('Not a valid input');

        return prompt_input($hint, $isRequired, $validRegex);
    }

    return $inputValue;
}
