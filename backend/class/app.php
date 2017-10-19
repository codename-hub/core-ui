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

}