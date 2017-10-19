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
   */
  public function run()
  {
    throw new exception(self::EXCEPTION_CORE_UI_APP_ILLEGAL_CALL, exception::$ERRORLEVEL_FATAL);
  }

  /**
   * @inheritDoc
   */
  public function __CONSTRUCT()
  {
    throw new exception(self::EXCEPTION_CORE_UI_APP_ILLEGAL_CALL, exception::$ERRORLEVEL_FATAL);
  }

  /**
   * This contains an array of application names including the vendor names
   * <br />The stack is created by searching the file ./config/parent.app in the app's directory
   * <br />Example: array('codename_exampleapp', 'codename_someapp', 'coename_core')
   * @var \codename\core\value\structure\appstack
   */
  protected static $appstack = null;

  /**
   * creates and sets the appstack for the current app
   * for the \codename\core\ui namespaces, this injects the core-ui application to the appstack
   * right before the core framework itself
   * @return array [description]
   */
  final protected static function makeCurrentAppstack() : array {
    $stack = app::makeAppstack(app::getVendor(), app::getApp());

    // inject current 'app' before core app
    $uiApp = array('vendor' => 'codename', 'app' => 'core-ui');
    array_splice($stack, -1, 0, array($uiApp));

    self::$appstack = new \codename\core\value\structure\appstack($stack);
    return $stack;
  }

  /**
   * Returns the appstack of the instance. Can be used to load files by their existance (not my app? -> parent app? -> parent's parent...)
   * NOTE: this overridden method explicitly calls core\ui\app::makeCurrentAppstack on the first call
   * to provide a different/injected appstack
   * @return array
   */
  public static function getAppstack() : array {
      if(self::$appstack == null) {
          self::makeCurrentAppstack();
      }
      return self::$appstack->get();
  }

  /**
   * Overridden method for UI purposes
   * includes the non-App-namespace \codename\core\ui
   * Get path of file (in APP dir OR in core dir) if it exists there - throws error if neither
   * @param string $file
   * @return string
   */
  public static function getInheritedPath(string $file, array $useAppstack = null) : string {
    return \codename\core\app::getInheritedPath($file, self::getAppstack());
  }

}