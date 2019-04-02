<?php
namespace codename\core\ui;
use codename\core\exception;

/**
 * [app description]
 * app override class for ui purposes
 * this class may not be initialized or run, at all.
 */
class app extends \codename\core\app {

  /**
   * Exception thrown if you call a method in this overridden class
   * that shouldn't be called
   * @var string
   */
  public const EXCEPTION_CORE_UI_APP_ILLEGAL_CALL = 'EXCEPTION_CORE_UI_APP_ILLEGAL_CALL';

  /**
   * @inheritDoc
   * this class may not be run
   */
  public function run()
  {
    throw new exception(self::EXCEPTION_CORE_UI_APP_ILLEGAL_CALL, exception::$ERRORLEVEL_FATAL);
  }

  /**
   * @inheritDoc
   * this class may not be constructed/initialized
   */
  public function __CONSTRUCT()
  {
    throw new exception(self::EXCEPTION_CORE_UI_APP_ILLEGAL_CALL, exception::$ERRORLEVEL_FATAL);
  }

  /**
   * [protected description]
   * @var array
   */
  protected static $requireJsAssets = array();

  /**
   * [protected description]
   * @var [type]
   */
  protected static $requireJsAdded = false;

  /**
   * [requireAsset description]
   * @param string $type  [description]
   * @param string|string[] $asset [description]
   */
  public static function requireAsset(string $type, $asset) {
    if($type === 'requirejs') {
      // add requireJS if not already added
      if(!self::$requireJsAdded) {
        app::getResponse()->requireResource('js', '/assets/requirejs/require.js', 0);
        app::getResponse()->requireResource('js', '/assets/require.config.js', 1);
        // requirecss must be issued earlier, if used
        // app::getResponse()->requireResource('js', '/assets/require-css/css.js', 2);
        // app::getResponse()->requireResource('script',
        //   "require(['require-css']);", 1
        // );
        /* app::getResponse()->requireResource('script',
          "requirejs.config({
              baseUrl: 'library',
          });", 0
        );*/
        self::$requireJsAdded = true;
      }

      $assets = [];
      if(is_array($asset)) {
        $assets = $asset;
      } else {
        $assets = [$asset];
      }

      foreach($assets as $a) {
        if(!in_array($a, self::$requireJsAssets)) {
          app::getResponse()->requireResource('script', "require(['{$a}'])");
          self::$requireJsAssets[] = $a;
        }
      }

    } else if($type === 'requirecss') {
      $type = 'css';
      if(is_array($asset)) {
        foreach($asset as $a) {
          app::getResponse()->requireResource($type, $a);
        }
      } else {
        app::getResponse()->requireResource($type, $asset);
      }
    } else {
      // TODO: require each resource?
      if(is_array($asset)) {
        foreach($asset as $a) {
          app::getResponse()->requireResource($type, $a);
        }
      } else {
        app::getResponse()->requireResource($type, $asset);
      }
    }
  }

  /**
   * [protected description]
   * @var \codename\core\generator\urlGeneratorInterface
   */
  protected static $urlGenerator = null;

  /**
   * [getUrlGenerator description]
   * @return \codename\core\generator\urlGeneratorInterface [description]
   */
  public static function getUrlGenerator() : \codename\core\generator\urlGeneratorInterface {
    if(self::$urlGenerator == null) {
      self::$urlGenerator = new \codename\core\generator\urlGenerator();
    }
    return self::$urlGenerator;
  }

  /**
   * [setUrlGenerator description]
   * @param \codename\core\generator\urlGeneratorInterface $generator [description]
   */
  public static function setUrlGenerator(\codename\core\generator\urlGeneratorInterface $generator) {
    self::$urlGenerator = $generator;
  }

  /**
   * returns the current server/FE endpoint
   * including Protocol and Port (on need)
   *
   * @return string [description]
   */
  public static function getCurrentServerEndpoint() : string {
    // url base prefix preparation
    $port = $_SERVER['X-Forwarded-Port'] ?? $_SERVER['SERVER_PORT'] ?? null;
    $proto = (($_SERVER['HTTPS'] ?? null) === 'on') ? 'https' : 'http';
    $portSuffix = (($proto === 'https' && $port != 443) || ($proto === 'http' && $port != 80)) ? (':'.$port) : '';
    $urlBase = $proto.'://'.$_SERVER['SERVER_NAME'].$portSuffix;
    return $urlBase;
  }

}
