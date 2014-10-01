<?php
define('VERSIONPRESS_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins/versionpress');
define('VERSIONPRESS_MIRRORING_DIR', VERSIONPRESS_PLUGIN_DIR . '/db');

/**
 * Nette is currently referenced as a minified library. We only need pieces from it so we should
 * ideally create a custom distribution at some point in the future.
 *
 * (Note: Nette 2.2 already uses the modular structure, however, it supports PHP 5.3 only. This might be
 * a problem for us, or not, see http://jira.agilio.cz/browse/WP-10 and http://jira.agilio.cz/browse/WP-40.
 */
require_once(VERSIONPRESS_PLUGIN_DIR . '/vendor/autoload.php');
NDebugger::enable(NDebugger::DETECT, VERSIONPRESS_PLUGIN_DIR . '/log');

$robotLoader = new NRobotLoader();
$robotLoader->addDirectory(VERSIONPRESS_PLUGIN_DIR . '/src');
$robotLoader->setCacheStorage(new NFileStorage(VERSIONPRESS_PLUGIN_DIR . '/temp'));
$robotLoader->register();

global $wpdb, $versionPressContainer;
$versionPressContainer = DIContainer::getConfiguredInstance();

if (file_exists(VERSIONPRESS_PLUGIN_DIR . '/.active')) {
    $wpdb = $versionPressContainer->resolve(VersionPressServices::DATABASE);
}