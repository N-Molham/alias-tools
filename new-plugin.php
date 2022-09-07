<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 18-Jun-17
 * Time: 12:28 PM
 */

use EasyGit\Repository;
use League\CLImate\CLImate;

error_reporting(E_ALL);

// to load packages
require_once __DIR__.DIRECTORY_SEPARATOR.'vendor/autoload.php';

// command interface instance
$cmd = new CLImate();

// plugin info
$pluginInfo = [
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
$pluginDefaults = [
    'author_name'    => 'Nabeel Molham',
    'author_email'   => 'n.molham@gmail.com',
    'author_website' => 'https://nabeel.molham.me/',
];

// list of recommended prefixed
$fixedPrefixes = [
    'woocommerce'                => 'wc',
    'buddypress'                 => 'bp',
    'gravityforms'               => 'gforms',
    'gravity forms'              => 'gforms',
    'easydigitaldownloads'       => 'eed',
    'easy digital downloads'     => 'eed',
    'contact form 7'             => 'cf7',
    'advanced custom fields'     => 'acf',
    'advanced custom fields pro' => 'acf_pro',
    'w3 total cache'             => 'w3tc',
    'ninja forms'                => 'nf',
    'mailchimp'                  => 'mc',
    'userpro'                    => 'userpro',
    'ultimate member'            => 'um',
    'badgeos'                    => 'badgeos',
    'wp job manager'             => 'wpjm',
    'listify'                    => 'listify',
    'visual composer'            => 'vc',
];

foreach ($pluginInfo as $infoKey => $infoLabel) {
    // check if input is required or not
    $isRequired = 0 === strpos($infoLabel, '*');
    $infoLabel = str_replace('*', '', $infoLabel);

    // requires info
    try {
        $infoValue = prompt_input($infoLabel, $isRequired, 'namespace' === $infoKey ? '/^[a-zA-Z][a-zA-Z0-9_]+((\\[a-zA-Z][a-zA-Z0-9_]+)+)*/' : '');
    } catch (Exception $exception) {
        $cmd->error($exception->getMessage());
        exit;
    }

    if (empty($infoValue) && array_key_exists($infoKey, $pluginDefaults)) {
        // use default value then
        $infoValue = $pluginDefaults[$infoKey];
    }

    switch ($infoKey) {
        case 'plugin_name':
            $infoValue = ucwords($infoValue);
            break;
        case 'plugin_slug':
            if (empty($infoValue)) {
                // generate the slug from plugin's name
                $infoValue = preg_replace(['/[^a-z0-9_\-]/', '/-+/'], [
                    '',
                    '-',
                ], preg_replace('/\s+/', '-', mb_strtolower($pluginInfo['plugin_name'])));
            }
            break;
        case 'text_domain':
            if (empty($infoValue)) {
                // use plugin's slug
                $infoValue = $pluginInfo['plugin_slug'];
            }
            break;
        case 'naming_prefix':
            if (empty($infoValue)) {
                $plugin_name = $pluginInfo['plugin_name'];
                foreach ($fixedPrefixes as $prefix_name => $prefix) {
                    if (false !== stripos($plugin_name, $prefix_name)) {
                        // use recommended one
                        $infoValue = $prefix.'_';
                        $plugin_name = trim(str_ireplace($prefix_name, '', $plugin_name));
                        break;
                    }
                }
                unset($prefix_name, $prefix);

                // generate based on plugin's name
                preg_match_all('/[A-Z]/', $plugin_name, $matches);
                $infoValue .= mb_strtolower(implode('', array_map('trim', $matches[0]))).'_';
            } else {
                // sanitize
                $infoValue = preg_replace('/[^a-z_]/', '', mb_strtolower(preg_match('/_$/', $infoValue) ? $infoValue : $infoValue.'_'));
            }
            break;
        case 'namespace':
            if (empty($infoValue)) {
                // generate based on plugin's name
                $infoValue = preg_replace('/\s+/', '_', str_replace('-', '', $pluginInfo['plugin_name']));
            }
            break;
    }

    $pluginInfo[$infoKey] = $infoValue;
    unset($infoValue, $isRequired, $infoLabel);
}

createRepo($pluginInfo);

/**
 * Create plugin
 *
 * @param $pluginInfo
 * @return void
 */
function createRepo($pluginInfo)
{
    global $cmd;

    $dirExists = file_exists($pluginInfo['plugin_slug']);

    if ($dirExists) {
        $continue = $cmd->input('Directory exists with the same name, would you like to continue?');
        $continue->accept(['Y', 'N'], true);

        if ('n' === mb_strtolower($continue->prompt())) {
            $cmd->info('Exit');

            // skip
            return;
        }
    }

    if (false === $dirExists) {
        // clone boilerplate plugin
        $cmd->info('Cloning boilerplate plugin...');
        Repository::cloneFromUrl('https://github.com/N-Molham/wp-plugins-boilerplate.git', $pluginInfo['plugin_slug']);
    }

    $pluginOldFile = $pluginInfo['plugin_slug'].DIRECTORY_SEPARATOR.'init.php';
    if (file_exists($pluginOldFile)) {
        // rename main file
        $plugin_new_file = str_replace('init.', $pluginInfo['plugin_slug'].'.', $pluginOldFile);
        $cmd->info(sprintf('Renaming plugin\'s main file "%s" > "%s"', $pluginOldFile, $plugin_new_file));
        rename($pluginOldFile, $plugin_new_file);
    }

    // generate replace array
    $replace_matches = [
        'WP Plugins Boilerplate'    => $pluginInfo['plugin_name'],
        'Plugin Description'        => $pluginInfo['plugin_desc'],
        'init.php'                  => $pluginInfo['plugin_slug'].'.php',
        'wp_plugin_boilerplate'     => str_replace('-', '_', $pluginInfo['plugin_slug']),
        'wp-plugin-boilerplate'     => $pluginInfo['plugin_slug'],
        'wp-plugin-domain'          => $pluginInfo['text_domain'],
        'wppb_'                     => $pluginInfo['naming_prefix'],
        'WPPB_'                     => mb_strtoupper($pluginInfo['naming_prefix']),
        'WP_Plugins\Boilerplate'    => $pluginInfo['namespace'],
        'Nabeel Molham'             => $pluginInfo['author_name'],
        'https://nabeel.molham.me/' => $pluginInfo['author_website'],
        'n.molham@gmail.com'        => $pluginInfo['author_email'],
    ];

    $cmd->info('Loading PHP & JS files...');

    // get PHP & JS files
    $pluginFiles = array_filter(getDirFiles($pluginInfo['plugin_slug']), static function ($file_name) {
        return preg_match('/(\.(php|js|md))$/', $file_name);
    });

    foreach ($pluginFiles as $fileName) {
        $cmd->comment(sprintf('Replace text in "%s"', $fileName));

        file_put_contents(
            $fileName,
            str_replace(
                array_keys($replace_matches),
                array_values($replace_matches),
                file_get_contents($fileName)
            )
        );
    }
}

/**
 * @param $dir
 * @param array $scan_files
 * @return array
 */
function getDirFiles($dir, array &$scan_files = []) : array
{
    $files = scandir($dir, SCANDIR_SORT_NONE);

    foreach ($files as $file_name) {
        $path = realpath($dir.DIRECTORY_SEPARATOR.$file_name);
        if (! in_array($file_name, ['.', '..', '.git'], true)) {
            if (is_dir($path)) {
                getDirFiles($path, $scan_files);
            }
            $scan_files[] = $path;
        }
    }

    return $scan_files;
}


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
