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
define('CORE_ENVIRONMENT', 'test');

// cross-project autoloader
$globalBootstrap = realpath(__DIR__.'/../../../../bootstrap-cli.php');
if(file_exists($globalBootstrap)) {
  echo("Including autoloader at " . $globalBootstrap . chr(10) );
  require_once $globalBootstrap;
} else {
  die("ERROR: No global bootstrap.cli.php found. You might want to initialize your cross-project autoloader using the root composer.json first." . chr(10) );
}

// core autoloader, to inherit test classes
$coreAutoload = realpath(__DIR__.'/../../core/vendor/autoload.php');
if(file_exists($coreAutoload)) {
  echo("Including autoloader at " . $coreAutoload . chr(10) );
  require_once $coreAutoload;
} else {
  die("ERROR: No core vendor/autoloader.php found. Please call \"composer dump-autoload\" in the core directory." . chr(10) );
}

// local autoloader
$localAutoload = realpath(__DIR__.'/../vendor/autoload.php');
if(file_exists($localAutoload)) {
  echo("Including autoloader at " . $localAutoload . chr(10) );
  require_once $localAutoload;
} else {
  die("ERROR: No local vendor/autoloader.php found. Please call \"composer dump-autoload\" in this directory." . chr(10) );
}
