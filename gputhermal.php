<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 18-Jun-17
 * Time: 12:28 PM
 */

use League\CLImate\CLImate;

error_reporting(E_ALL);

// to load packages
require_once __DIR__.DIRECTORY_SEPARATOR.'vendor/autoload.php';

// command interface instance
$cmd = new CLImate();

// default thermal info file path
$filePath = empty($argv[1]) ? 'D:\Google Drive\GPU-Z Sensor Log.txt' : $argv[1];
if (! file_exists($filePath) || ! is_readable($filePath)) {
    // error loading file
    dd('File does not exists or unreadable!');
}

// open file
$fileHandler = fopen($filePath, 'rb');
if (false === $filePath) {
    dd('Error opening file');
}

// table headers/titles
$tableHeaders = array_map('trim', explode(',', fgets($fileHandler)));
$rowSeparator = str_repeat('=', max(array_map('mb_strlen', $tableHeaders)));

// the last 5 readings
$recentReading = array_map(static function ($line) {
    return array_map('trim', explode(',', $line));
}, array_map('trim', explode("\n", fileTail($fileHandler, 5))));

$tableRows = [];
foreach ($recentReading as $reading) {
    foreach ($reading as $colIndex => $colValue) {
        $tableRows[] = [
            $tableHeaders[$colIndex],
            $colValue,
        ];
    }

    $tableRows[] = [$rowSeparator, $rowSeparator];
}

$cmd->table($tableRows);

/**
 * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
 *
 *
 *
 * @author Torleif Berger, Lorenzo Stanco
 * @link http://stackoverflow.com/a/15025877/995958
 * @license http://creativecommons.org/licenses/by/3.0/
 *
 * @param resource $fileHandler
 * @param int $lines
 * @param bool $adaptive
 *
 * @return string
 */
function fileTail($fileHandler, int $lines = 1, bool $adaptive = true) : string
{
    // Sets buffer size, according to the number of lines to retrieve.
    // This gives a performance boost when reading a few lines from the file.
    $bufferSize = ! $adaptive ? 4096 : ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

    // Jump to last character
    fseek($fileHandler, -1, SEEK_END);

    // Read it and adjust line number if necessary
    // (Otherwise the result would be wrong if file doesn't end with a blank line)
    if (fread($fileHandler, 1) !== "\n") {
        --$lines;
    }

    // Start reading
    $output = '';
    $chunk = '';

    // While we would like more
    while (ftell($fileHandler) > 0 && $lines >= 0) {
        // Figure out how far back we should jump
        $seek = min(ftell($fileHandler), $bufferSize);

        // Do the jump (backwards, relative to where we are)
        fseek($fileHandler, -$seek, SEEK_CUR);

        // Read a chunk and prepend it to our output
        $output = ($chunk = fread($fileHandler, $seek)).$output;

        // Jump back to where we started reading
        fseek($fileHandler, -mb_strlen($chunk, '8bit'), SEEK_CUR);

        // Decrease our line counter
        $lines -= substr_count($chunk, "\n");
    }

    // While we have too many lines
    // (Because of buffer size we might have read too many)
    while ($lines++ < 0) {
        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
    }

    // Close file and return
    fclose($fileHandler);

    return trim($output);
}
