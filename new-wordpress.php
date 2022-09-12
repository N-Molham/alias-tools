<?php
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

// new or update
$newInstance = isset($argv[1]) && 'new' === $argv[1];

// installation name
$instanceName = isset($argv[2]) ? preg_replace('/\\\$/', '', $argv[2]) : null;

// configs
$wpConfig = [
    'dbname'    => '?',
    'dbuser'    => '?',
    'dbpass'    => '',
    'dbhost'    => 'localhost',
    'dbprefix'  => 'wp_',
    'dbcharset' => 'utf8',
    'dbcollate' => '',
    'locale'    => '',
];

$argIndex = 3;
foreach ($wpConfig as $configName => $configValue) {
    $argValue = $argv[$argIndex] ?? null;
    if ('?' === $configValue && null === $argValue) {
        dd(sprintf('Missing config [%s] argument number [%s]', $configName, $argIndex));
    }

    // save config value
    $wpConfig[$configName] = $argValue ?? $configValue;

    // next argument
    $argIndex++;
}
unset($configName, $argValue, $argIndex);

if (null === $instanceName) {
    // none passed
    dd('ERROR: invalid instance name!');
}

// current working directory
$currentDir = getcwd();

// final instance location path
$fullPath = $currentDir.DIRECTORY_SEPARATOR.$instanceName;
$tempPath = $currentDir.DIRECTORY_SEPARATOR.'wordpress';

if ($newInstance && file_exists($fullPath)) {
    dd('Error: instance directory already exists');
}

if (! $newInstance && ! file_exists($fullPath)) {
    dd('Error: Can not find instance directory to update');
}

if ($newInstance && file_exists($tempPath)) {
    dd('Error: instance temp directory already exists');
}

if ($newInstance) {
    dump('Create new instance');

    dump('Checking for WordPress latest version ...');

    // WP info
    $wpInfo = unserialize(file_get_contents('https://api.wordpress.org/core/version-check/1.6/'));
    if (! is_array($wpInfo)) {
        dd('ERROR: unable to load latest WP status');
    }

    $wpInfo = $wpInfo['offers'][0];
    dump('Last version is: '.$wpInfo['current']);

    // first offer
    $packageName = __DIR__.DIRECTORY_SEPARATOR.sprintf('wordpress-%s.zip', $wpInfo['current']);

    if (file_exists($packageName)) {
        // already downloaded
        dump('Use cached copy.');
    } else {
        // download the last version first
        dump('Downloading zip file > '.$wpInfo['download']);

        // start downloading
        file_put_contents($packageName, file_get_contents($wpInfo['download']));

        dump('Download done');
    }

    // ZIP instance
    $zip = new ZipArchive();
    $openZip = $zip->open($packageName);

    if (true === $openZip) {
        dump('Extracting zip file ...');
        $zip->extractTo($currentDir);
        $zip->close();
    } else {
        dd('Error: unable to open package zip file, '.$openZip);
    }

    dump('Extraction completed');

    if (rename($currentDir.DIRECTORY_SEPARATOR.'wordpress', $fullPath)) {
        dump(sprintf('Instance renamed to "%s"', $instanceName));
    } else {
        dd('Error: Unable to rename extracted directory.');
    }
}

// cwd to the instance
chdir($fullPath);

// check if WP CLI installed
$wpcliCheck = shell_exec('wp core');
if (strpos($wpcliCheck, 'wp core config') === false) {
    dd('Error: WP CLI is not installed');
}

$configCmd = 'wp core config';
foreach ($wpConfig as $configName => $configValue) {
    $configCmd .= sprintf(' --%s="%s"', $configName, $configValue);
}

dd(shell_exec($configCmd));

if (! function_exists('dd')):
    function dd($args)
    {
        call_user_func_array('dump', func_get_args());
        die();
    }
endif;
