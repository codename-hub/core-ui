<?php
namespace codename\core\ui\tests\crud;

use codename\core\app;
use codename\core\datacontainer;

use codename\core\test\base;
use codename\core\test\overrideableApp;

class twigTest extends base {

  /**
   * [protected description]
   * @var bool
   */
  protected static $initialized = false;

  /**
   * @inheritDoc
   */
  public static function tearDownAfterClass(): void
  {
    parent::tearDownAfterClass();
    static::$initialized = false;
  }

  /**
   * @inheritDoc
   */
  protected function tearDown(): void
  {

  }

  /**
   * @inheritDoc
   */
  protected function setUp(): void
  {
    overrideableApp::resetRequest();
    overrideableApp::resetResponse();
    $app = static::createApp();

    // Additional overrides to get a more complete app lifecycle
    // and allow static global app::getModel() to work correctly
    $app->__setApp('twigtest');
    $app->__setVendor('codename');
    $app->__setNamespace('\\codename\\core\\ui\\tests\\templateengine\\twig');
    $app->__setHomedir('codename/core-ui/tests/templateengine/twig');

    $app->getAppstack();

    // avoid re-init
    if(static::$initialized) {
      return;
    }

    static::$initialized = true;

    static::setEnvironmentConfig([
      'test' => [
        'filesystem' =>[
          'local' => [
            'driver' => 'local',
          ]
        ],
        'translate' => [
          'default' => [
            'driver' => 'dummy',
          ]
        ],
        'templateengine' => [
          'default' => [
            'driver' => 'twig',
          ]
        ],
        'log' => [
          'default' => [
            'driver' => 'system',
            'data' => [
              'name' => 'dummy'
            ]
          ]
        ],
      ]
    ]);
  }

  /**
   * Makes sure we get the correct class
   * and implicitly tests the basic initialization
   */
  public function testInstance(): void {
    $this->assertInstanceOf(\codename\core\ui\templateengine\twig::class, app::getTemplateEngine());
  }

  /**
   * Pure string template
   */
  public function testStringTemplate(): void {
    $instance = app::getTemplateEngine();
    if($instance instanceof \codename\core\ui\templateengine\twig) {
      $rendered = $instance->renderStringSandboxed('{{ someVariable }}', [ 'someVariable' => '123' ]);
      $this->assertEquals('123', $rendered);
    }
  }

  /**
   * Simple twig file
   */
  public function testSimpleFileTemplate(): void {
    $rendered = app::getTemplateEngine()->render('test_templates/example1', [ 'example1' => 'abc' ]);
    $this->assertEquals("Example1 abc\n", $rendered);
  }

  /**
   * Tests a simple file inclusion, semi-absolute (relative to project FE root)
   * but inherited across apps
   */
  public function testIncludedFileTemplate(): void {
    $rendered = app::getTemplateEngine()->render('test_templates/example_includes', [ 'example1' => 'abc' ]);
    $this->assertEquals("Example1 abc\n", $rendered);
  }

  /**
   * [testIncludedRelativeFileTemplate description]
   */
  public function testIncludedRelativeFileTemplate(): void {
    $rendered = app::getTemplateEngine()->render('test_templates/example_includes_relative', [ 'example1' => 'abc' ]);
    $this->assertEquals("Example1 abc\n", $rendered);
  }


}
