<?php
/**
 *
 * This is a per-project autoloading file
 * For initializing the local project and enabling it for development purposes
 *
 * you need to build up your fullstack autoloading structure
 * using composer install / composer update
 * e.g. for <root>/composer.json
 *
 * and you need to build a local composer classmap
 * that enables the usage of composer's 'autoload-dev' setting
 * just for this project
 *
 * You should not want to do a "composer install" or "composer update" here.
 *
 */

// Default fixed environment for unit tests
use codename\core\app;
use codename\core\test\overrideableApp;

const CORE_ENVIRONMENT = 'test';

// cross-project autoloader
$globalBootstrap = realpath(__DIR__ . '/../../../../bootstrap-cli.php');
if (file_exists($globalBootstrap)) {
    echo("Including autoloader at " . $globalBootstrap . chr(10));
    require_once $globalBootstrap;
} else {
    die("ERROR: No global bootstrap.cli.php found. You might want to initialize your cross-project autoloader using the root composer.json first." . chr(10));
}

// local autoloader
$localAutoload = realpath(__DIR__ . '/../vendor/autoload.php');
if (file_exists($localAutoload)) {
    echo("Including autoloader at " . $localAutoload . chr(10));
    require_once $localAutoload;
} else {
    die("ERROR: No local vendor/autoloader.php found. Please call \"composer dump-autoload --dev\" in this directory." . chr(10));
}

//
// This allows having only a local autoloader and no global one
// (e.g. single-project unit testing)
//
if (!file_exists($globalBootstrap) && !file_exists($localAutoload)) {
    die("ERROR: No global bootstrap.cli.php or local vendor/autoloader.php found. You might want to initialize your cross-project or single-project autoloader first." . chr(10));
}

if (!$globalBootstrap) {
    // Fallback to this project's vendor dir (and add a slash at the end - because realpath doesn't add it)
    define("CORE_VENDORDIR", realpath(dirname(__FILE__) . '/../vendor/') . '/');
}

// Explicitly reset any appdata left
// or implicitly re-init base data.
overrideableApp::reset();

//
// Special quirk for single-project unit testing
// We need to override the homedir for this app
// as the framework itself assumes it resides in composer's vendor dir
//
// Additionally, we need to do this every time the appstack gets initialized in the tests
// and only if this app is used, somehow.
//
app::getHook()->add(app::EVENT_APP_APPSTACK_AVAILABLE, function () {
    overrideableApp::__modifyAppstackEntry('codename', 'core-ui', [
      'homedir' => realpath(__DIR__ . '/../'), // One dir up (project root)
    ]);
});
