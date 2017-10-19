<?php
namespace codename\core\ui;
use codename\core\app;

/**
 * [app description]
 * app shim wrapper class for wrapping the 'real' app classes' functionality for UI purposes
 */
class app {

  /**
   * This contains an array of application names including the vendor names
   * <br />The stack is created by searching the file ./config/parent.app in the app's directory
   * <br />Example: array('codename_exampleapp', 'codename_someapp', 'coename_core')
   * @var \codename\core\value\structure\appstack
   */
  protected static $appstack = null;

  /**
   * creates and sets the appstack for the current app
   * @return array [description]
   */
  final protected static function makeCurrentAppstack() : array {
    $stack = \codename\core\app::makeAppstack(app::getVendor(), app::getApp());

    // inject current 'app' before core app
    $uiApp = array('vendor' => 'codename', 'app' => 'core-ui');
    $stack = array_splice($stack, (count($stack)-1), 0, $uiApp)

    self::$appstack = new \codename\core\value\structure\appstack($stack);
    return $stack;
  }

  /**
   * Returns the appstack of the instance. Can be used to load files by their existance (not my app? -> parent app? -> parent's parent...)
   * @return array
   */
  public static function getAppstack() : array {
      if(self::$appstack == null) {
          self::makeCurrentAppstack();
      }
      return self::$appstack->get();
  }

  /**
   * Get path of file (in APP dir OR in core dir) if it exists there - throws error if neither
   * @param string $file
   * @return string
   */
  public static function getInheritedPath(string $file, array $useAppstack = null) : string {
    return \codename\core\app::getInheritedPath($file, self::getAppstack());
  }

}