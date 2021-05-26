<?php
namespace codename\core\ui\tests;

use \codename\core\tests\base;
use \codename\core\tests\overrideableApp;

class appTest extends base
{

  /**
   * @inheritDoc
   */
  protected function setUp(): void
  {
    parent::setUp();

    $app = static::createApp();
    overrideableApp::__injectApp([
      'vendor' => 'codename',
      'app' => 'core-ui',
      'namespace' => '\\codename\\core\\ui'
    ]);
    $app->getAppstack();
  }


  /**
   * [testConstruct description]
   */
  public function testConstruct(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_CORE_UI_APP_ILLEGAL_CALL');

    $app = new \codename\core\ui\app();
  }

  /**
   * [testRun description]
   */
  public function testRun(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_CORE_UI_APP_ILLEGAL_CALL');

    $app = \codename\core\ui\app::run();
  }


}
