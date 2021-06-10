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
    // reset, to also reset twig instances
    // and their stored request/response instances
    // which might be recreated/reset during tests anyways
    overrideableApp::reset();
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
          ],
          'sandbox_available' => [
            'driver' => 'twig',
            'sandbox_enabled' => true,
            'sandbox_mode'    => null // not globally enabled
          ],
          'sandbox_global' => [
            'driver' => 'twig',
            'sandbox_enabled' => true,
            'sandbox_mode'    => 'global' // sandbox for everything
          ],
          'otherext' => [
            'driver' => 'twig',
            'template_file_extension' => '.otherext.twig'
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
   * Basic test for getting the internal client name
   */
  public function testGetClientName(): void {
    $instance = app::getTemplateEngine();
    if($instance instanceof \codename\core\clientInterface) {
      $this->assertEquals('templateenginedefault', $instance->getClientName('default'));
    }
  }

  /**
   * Test that renaming the client fails (after it has been initialized via app/env)
   */
  public function testRenameClientInstanceFails(): void {
    $instance = app::getTemplateEngine();
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_CORE_CLIENT_INTERFACE_CANNOT_RENAME_CLIENT');
    if($instance instanceof \codename\core\clientInterface) {
      $instance->setClientName('abc');
    }
  }

  /**
   * Makes sure renderStringSandboxed fails, if sandbox is not enabled at all
   */
  public function testRenderStringSandboxedFailsNotEnabled(): void {
    $instance = app::getTemplateEngine();
    if($instance instanceof \codename\core\ui\templateengine\twig) {
      $this->expectException(\codename\core\exception::class);
      $this->expectExceptionMessage('TEMPLATEENGINE_TWIG_NO_SANDBOX_INSTANCE');
      $rendered = $instance->renderStringSandboxed('{{ someVariable }}', []);
    }
  }

  /**
   * Makes sure renderSandboxed fails, if sandbox is not enabled at all
   */
  public function testRenderSandboxedFailsNotEnabled(): void {
    $instance = app::getTemplateEngine();
    if($instance instanceof \codename\core\ui\templateengine\twig) {
      $this->expectException(\codename\core\exception::class);
      $this->expectExceptionMessage('TEMPLATEENGINE_TWIG_NO_SANDBOX_INSTANCE');
      $rendered = $instance->renderSandboxed('test_templates/example1', []);
    }
  }

  /**
   * [testStringTemplate description]
   */
  public function testStringTemplate(): void {
    $instance = app::getTemplateEngine('sandbox_available');
    if($instance instanceof \codename\core\ui\templateengine\twig) {
      $rendered = $instance->renderStringSandboxed('{{ someVariable }}', [ 'someVariable' => '123' ]);
      $this->assertEquals('123', $rendered);
    }
  }

  /**
   * [testStringTemplateSandboxedGlobal description]
   */
  public function testStringTemplateSandboxedGlobal(): void {
    $instance = app::getTemplateEngine('sandbox_global');
    if($instance instanceof \codename\core\ui\templateengine\twig) {
      $rendered = $instance->renderStringSandboxed('{{ someVariable }}', [ 'someVariable' => '123' ]);
      $this->assertEquals('123', $rendered);

      $this->expectException(\Twig\Sandbox\SecurityError::class);
      app::getResponse()->setData('key', 'abc');
      $rendered = $instance->renderStringSandboxed('{{ response.getData("key") }}', []);
    }
  }

  /**
   * [testStringTemplateSandboxedStandby description]
   */
  public function testStringTemplateSandboxedStandby(): void {
    $instance = app::getTemplateEngine('sandbox_available');
    if($instance instanceof \codename\core\ui\templateengine\twig) {
      $rendered = $instance->renderStringSandboxed('{{ someVariable }}', [ 'someVariable' => '123' ]);
      $this->assertEquals('123', $rendered);

      $this->expectException(\Twig\Sandbox\SecurityError::class);
      app::getResponse()->setData('key', 'abc');
      $rendered = $instance->renderStringSandboxed('{{ response.getData("key") }}', []);
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
   * [testDefectiveTemplateSyntaxError description]
   */
  public function testDefectiveTemplateSyntaxError(): void {
    $this->expectException(\Twig\Error\SyntaxError::class);
    $rendered = app::getTemplateEngine()->render('test_templates/test_defective_syntax_error', []);
  }

  /**
   * [testDefectiveTemplateRuntimeError description]
   */
  public function testDefectiveTemplateRuntimeError(): void {
    $this->expectExceptionMessage('Crash');
    $instance = new \codename\core\ui\templateengine\twig([]);
    $instance->addFunction('crashingFunction', function() {
      throw new \Exception('Crash');
    });
    $rendered = $instance->render('test_templates/test_defective_crashingFunction', []);
  }

  /**
   * Tests config with a differing file extension
   */
  public function testOtherTemplateFileExtension(): void {
    $rendered = app::getTemplateEngine('otherext')->render('test_templates/test', [ 'someVariable' => 'yes' ]);
    $this->assertEquals("TestOtherExt yes\n", $rendered);
  }

  /**
   * Tests config with a differing file extension, looking for a nonexisting file
   * (implicitly)
   */
  public function testOtherTemplateFileExtensionNonexistingWillFail(): void {
    $this->expectException(\Twig\Error\LoaderError::class);
    // NOTE: example1.twig DOES exist, but example1.otherext.twig does NOT.
    $rendered = app::getTemplateEngine('otherext')->render('test_templates/example1', [ 'someVariable' => 'yes' ]);
  }

  /**
   * Tests rendering a template file
   * in a sandbox - without accessing disallowed stuff
   */
  public function testRenderSandboxed(): void {
    $rendered = app::getTemplateEngine('sandbox_available')->renderSandboxed('test_templates/test_sandboxed', [ 'sandboxedVariable' => 'foo' ]);
    $this->assertEquals("foo\n", $rendered);
  }

  /**
   * Tests accessing a disallowed method
   * in a template file will fail
   */
  public function testRenderSandboxedWillFail(): void {
    $this->expectException(\Twig\Sandbox\SecurityError::class);
    app::getRequest();
    app::getResponse()->setData('key', 'abc');
    $rendered = app::getTemplateEngine('sandbox_global')->renderSandboxed('test_templates/test_sandboxed_access', [ 'sandboxedVariable' => 'foo' ]);
  }

  /**
   * Tests accessing an allowed method access
   * in a template file will succeed
   */
  public function testRenderSandboxedWillSucceed(): void {
    app::getRequest();
    app::getResponse()->setData('key', 'abc');

    $instance = new \codename\core\ui\templateengine\twig([
      'sandbox_enabled' => true,
      'sandbox' => [
        'methods' => [
          \codename\core\response::class => 'getData'
        ]
      ]
    ]);

    $rendered = $instance->renderSandboxed('test_templates/test_sandboxed_access', [ 'sandboxedVariable' => 'foo' ]);
    $this->assertEquals("foo abc\n", $rendered);
  }

  /**
   * Tests accessing an allowed method access
   * in a sandboxed string template succeeds
   */
  public function testRenderSandboxedStringWillSucceed(): void {
    app::getRequest();
    app::getResponse()->setData('key', 'xyz');

    $instance = new \codename\core\ui\templateengine\twig([
      'sandbox_enabled' => true,
      'sandbox' => [
        'methods' => [
          \codename\core\response::class => 'getData'
        ]
      ]
    ]);

    $rendered = $instance->renderStringSandboxed('{{ sandboxedVariable }} {{ response.getData("key") }}', [ 'sandboxedVariable' => 'foo' ]);
    $this->assertEquals("foo xyz", $rendered);
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
   * Special core-ui feature: relative paths may be used in a twig-template
   * (relative to current file/cwd)
   */
  public function testIncludedRelativeFileTemplate(): void {
    $rendered = app::getTemplateEngine()->render('test_templates/example_includes_relative', [ 'example1' => 'abc' ]);
    $this->assertEquals("Example1 abc\n", $rendered);
  }

  /**
   * Tests some core-ui provided Twig-"tests"
   * (usage with varname is <...> )
   */
  public function testIntegratedTwigTests(): void {
    $instance = app::getTemplateEngine('sandbox_available');
    //
    // Tests 'array' test
    //
    $rendered = $instance->renderStringSandboxed('{{ value is array ? 1 : 0 }}', [ 'value' => [ 1, 2, 3] ]);
    $this->assertEquals("1", $rendered);
    $rendered = $instance->renderStringSandboxed('{{ value is array ? 1 : 0 }}', [ 'value' => 'abc' ]);
    $this->assertEquals("0", $rendered);

    //
    // Tests 'string' test
    //
    $rendered = $instance->renderStringSandboxed('{{ value is string ? 1 : 0 }}', [ 'value' => 123 ]);
    $this->assertEquals("0", $rendered);
    $rendered = $instance->renderStringSandboxed('{{ value is string ? 1 : 0 }}', [ 'value' => [ 1, 2, 3 ] ]);
    $this->assertEquals("0", $rendered);
    $rendered = $instance->renderStringSandboxed('{{ value is string ? 1 : 0 }}', [ 'value' => '123' ]);
    $this->assertEquals("1", $rendered);
  }

  /**
   * [testRenderView description]
   */
  public function testRenderView(): void {
    $this->markTestIncomplete('renderView makes only sense when used in conjunction with app lifecycle');
  }

  /**
   * [testRenderTemplate description]
   */
  public function testRenderTemplate(): void {
    $this->markTestIncomplete('renderView makes only sense when used in conjunction with app lifecycle');
  }


}
