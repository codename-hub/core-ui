<?php

namespace codename\core\ui\tests\templateengine\twig;

use codename\core\app;
use codename\core\clientInterface;
use codename\core\response;
use codename\core\test\base;
use codename\core\test\overrideableApp;
use codename\core\ui\templateengine\twig;
use Exception;
use ReflectionException;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Sandbox\SecurityError;

class twigTest extends base
{
    /**
     * [protected description]
     * @var bool
     */
    protected static bool $initialized = false;

    /**
     * {@inheritDoc}
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        static::$initialized = false;
    }

    /**
     * Makes sure we get the correct class
     * and implicitly tests the basic initialization
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testInstance(): void
    {
        static::assertInstanceOf(twig::class, app::getTemplateEngine());
    }

    /**
     * Basic test for getting the internal client name
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testGetClientName(): void
    {
        $instance = app::getTemplateEngine();
        if ($instance instanceof clientInterface) {
            static::assertEquals('templateenginedefault', $instance->getClientName('default'));
        }
    }

    /**
     * Test that renaming the client fails (after it has been initialized via app/env)
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testRenameClientInstanceFails(): void
    {
        $instance = app::getTemplateEngine();
        $this->expectException(\codename\core\exception::class);
        $this->expectExceptionMessage('EXCEPTION_CORE_CLIENT_INTERFACE_CANNOT_RENAME_CLIENT');
        if ($instance instanceof clientInterface) {
            $instance->setClientName('abc');
        }
    }

    /**
     * Makes sure renderStringSandboxed fails, if sandbox is not enabled at all
     * @throws LoaderError
     * @throws ReflectionException
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testRenderStringSandboxedFailsNotEnabled(): void
    {
        $instance = app::getTemplateEngine();
        if ($instance instanceof twig) {
            $this->expectException(\codename\core\exception::class);
            $this->expectExceptionMessage('TEMPLATEENGINE_TWIG_NO_SANDBOX_INSTANCE');
            $instance->renderStringSandboxed('{{ someVariable }}', []);
        }
    }

    /**
     * Makes sure renderSandboxed fails, if sandbox is not enabled at all
     * @throws LoaderError
     * @throws ReflectionException
     * @throws SyntaxError
     * @throws Throwable
     * @throws RuntimeError
     * @throws \codename\core\exception
     */
    public function testRenderSandboxedFailsNotEnabled(): void
    {
        $instance = app::getTemplateEngine();
        if ($instance instanceof twig) {
            $this->expectException(\codename\core\exception::class);
            $this->expectExceptionMessage('TEMPLATEENGINE_TWIG_NO_SANDBOX_INSTANCE');
            $instance->renderSandboxed('test_templates/example1', []);
        }
    }

    /**
     * Overriding the sandbox
     * @throws LoaderError
     * @throws ReflectionException
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testSandboxOverride(): void
    {
        $instance = new twig([
          'sandbox_enabled' => false,
          'sandbox_mode' => 'override',
        ]);
        app::getResponse()->setData('key', 'yes');
        $rendered = $instance->renderStringSandboxed('{{ sandboxedVariable }} {{ response.getData("key") }}', ['sandboxedVariable' => 'foo']);
        static::assertEquals("foo yes", $rendered);
    }

    /**
     * [testStringTemplate description]
     * @throws LoaderError
     * @throws ReflectionException
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testStringTemplate(): void
    {
        $instance = app::getTemplateEngine('sandbox_available');
        if ($instance instanceof twig) {
            $rendered = $instance->renderStringSandboxed('{{ someVariable }}', ['someVariable' => '123']);
            static::assertEquals('123', $rendered);
        }
    }

    /**
     * [testStringTemplateSandboxedGlobal description]
     * @throws LoaderError
     * @throws ReflectionException
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testStringTemplateSandboxedGlobal(): void
    {
        $instance = app::getTemplateEngine('sandbox_global');
        if ($instance instanceof twig) {
            $rendered = $instance->renderStringSandboxed('{{ someVariable }}', ['someVariable' => '123']);
            static::assertEquals('123', $rendered);

            $this->expectException(SecurityError::class);
            app::getResponse()->setData('key', 'abc');
            $instance->renderStringSandboxed('{{ response.getData("key") }}', []);
        }
    }

    /**
     * [testStringTemplateSandboxedStandby description]
     * @throws LoaderError
     * @throws ReflectionException
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testStringTemplateSandboxedStandby(): void
    {
        $instance = app::getTemplateEngine('sandbox_available');
        if ($instance instanceof twig) {
            $rendered = $instance->renderStringSandboxed('{{ someVariable }}', ['someVariable' => '123']);
            static::assertEquals('123', $rendered);

            $this->expectException(SecurityError::class);
            app::getResponse()->setData('key', 'abc');
            $instance->renderStringSandboxed('{{ response.getData("key") }}', []);
        }
    }

    /**
     * Simple twig file
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testSimpleFileTemplate(): void
    {
        $rendered = app::getTemplateEngine()->render('test_templates/example1', ['example1' => 'abc']);
        static::assertEquals("Example1 abc\n", $rendered);
    }

    /**
     * [testDefectiveTemplateSyntaxError description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testDefectiveTemplateSyntaxError(): void
    {
        $this->expectException(SyntaxError::class);
        app::getTemplateEngine()->render('test_templates/test_defective_syntax_error', []);
    }

    /**
     * [testDefectiveTemplateRuntimeError description]
     * @throws LoaderError
     * @throws ReflectionException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testDefectiveTemplateRuntimeError(): void
    {
        $this->expectExceptionMessage('Crash');
        $instance = new twig([]);
        $instance->addFunction('crashingFunction', function () {
            throw new Exception('Crash');
        });
        $instance->render('test_templates/test_defective_crashingFunction', []);
    }

    /**
     * Tests config with a differing file extension
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testOtherTemplateFileExtension(): void
    {
        $rendered = app::getTemplateEngine('otherext')->render('test_templates/test', ['someVariable' => 'yes']);
        static::assertEquals("TestOtherExt yes\n", $rendered);
    }

    /**
     * Tests config with a differing file extension, looking for a nonexisting file
     * (implicitly)
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testOtherTemplateFileExtensionNonexistingWillFail(): void
    {
        $this->expectException(LoaderError::class);
        // NOTE: example1.twig DOES exist, but example1.otherext.twig does NOT.
        app::getTemplateEngine('otherext')->render('test_templates/example1', ['someVariable' => 'yes']);
    }

    /**
     * Tests rendering a template file
     * in a sandbox - without accessing disallowed stuff
     * @throws LoaderError
     * @throws ReflectionException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testRenderSandboxed(): void
    {
        $instance = app::getTemplateEngine('sandbox_available');
        if (!($instance instanceof twig)) {
            static::fail('setup fail');
        }
        $rendered = $instance->renderSandboxed('test_templates/test_sandboxed', ['sandboxedVariable' => 'foo']);
        static::assertEquals("foo\n", $rendered);
    }

    /**
     * Tests accessing a disallowed method
     * in a template file will fail
     * @throws LoaderError
     * @throws ReflectionException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testRenderSandboxedWillFail(): void
    {
        $this->expectException(SecurityError::class);
        app::getRequest();
        app::getResponse()->setData('key', 'abc');
        $instance = app::getTemplateEngine('sandbox_global');
        if (!($instance instanceof twig)) {
            static::fail('setup fail');
        }
        $instance->renderSandboxed('test_templates/test_sandboxed_access', ['sandboxedVariable' => 'foo']);
    }

    /**
     * Tests accessing an allowed method access
     * in a template file will succeed
     * @throws LoaderError
     * @throws ReflectionException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testRenderSandboxedWillSucceed(): void
    {
        app::getRequest();
        app::getResponse()->setData('key', 'abc');

        $instance = new twig([
          'sandbox_enabled' => true,
          'sandbox' => [
            'methods' => [
              response::class => 'getData',
            ],
          ],
        ]);

        $rendered = $instance->renderSandboxed('test_templates/test_sandboxed_access', ['sandboxedVariable' => 'foo']);
        static::assertEquals("foo abc\n", $rendered);
    }

    /**
     * Tests accessing an allowed method access
     * in a sandboxed string template succeeds
     * @throws LoaderError
     * @throws ReflectionException
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testRenderSandboxedStringWillSucceed(): void
    {
        app::getRequest();
        app::getResponse()->setData('key', 'xyz');

        $instance = new twig([
          'sandbox_enabled' => true,
          'sandbox' => [
            'methods' => [
              response::class => 'getData',
            ],
          ],
        ]);

        $rendered = $instance->renderStringSandboxed('{{ sandboxedVariable }} {{ response.getData("key") }}', ['sandboxedVariable' => 'foo']);
        static::assertEquals("foo xyz", $rendered);
    }

    /**
     * Tests a simple file inclusion, semi-absolute (relative to project FE root)
     * but inherited across apps
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testIncludedFileTemplate(): void
    {
        $rendered = app::getTemplateEngine()->render('test_templates/example_includes', ['example1' => 'abc']);
        static::assertEquals("Example1 abc\n", $rendered);
    }

    /**
     * Special core-ui feature: relative paths may be used in a twig-template
     * (relative to current file/cwd)
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testIncludedRelativeFileTemplate(): void
    {
        $rendered = app::getTemplateEngine()->render('test_templates/example_includes_relative', ['example1' => 'abc']);
        static::assertEquals("Example1 abc\n", $rendered);
    }

    /**
     * Tests dynamic (non-sandboxed) template inclusion
     * using full context passthrough
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testIncludeTemplateFromString(): void
    {
        $rendered = app::getTemplateEngine()->render('test_templates/test_include_template_from_string', [
          'template' => 'TemplateRendered: {{ data.someVariable }}',
          'someVariable' => 'def123',
        ]);
        static::assertEquals("TemplateRendered: def123\n", $rendered);
    }

    /**
     * Tests some core-ui provided Twig-"tests"
     * (usage with varname is <...> )
     * @throws LoaderError
     * @throws ReflectionException
     * @throws SyntaxError
     * @throws Throwable
     * @throws \codename\core\exception
     */
    public function testIntegratedTwigTests(): void
    {
        $instance = app::getTemplateEngine('sandbox_available');
        if (!($instance instanceof twig)) {
            static::fail('setup fail');
        }
        //
        // Tests 'array' test
        //
        $rendered = $instance->renderStringSandboxed('{{ value is array ? 1 : 0 }}', ['value' => [1, 2, 3]]);
        static::assertEquals("1", $rendered);
        $rendered = $instance->renderStringSandboxed('{{ value is array ? 1 : 0 }}', ['value' => 'abc']);
        static::assertEquals("0", $rendered);

        //
        // Tests 'string' test
        //
        $rendered = $instance->renderStringSandboxed('{{ value is string ? 1 : 0 }}', ['value' => 123]);
        static::assertEquals("0", $rendered);
        $rendered = $instance->renderStringSandboxed('{{ value is string ? 1 : 0 }}', ['value' => [1, 2, 3]]);
        static::assertEquals("0", $rendered);
        $rendered = $instance->renderStringSandboxed('{{ value is string ? 1 : 0 }}', ['value' => '123']);
        static::assertEquals("1", $rendered);
    }

    /**
     * [testRenderView description]
     */
    public function testRenderView(): void
    {
        static::markTestIncomplete('renderView makes only sense when used in conjunction with app lifecycle');
    }

    /**
     * [testRenderTemplate description]
     */
    public function testRenderTemplate(): void
    {
        static::markTestIncomplete('renderView makes only sense when used in conjunction with app lifecycle');
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    protected function setUp(): void
    {
        overrideableApp::resetRequest();
        overrideableApp::resetResponse();
        // reset, to also reset twig instances
        // and their stored request/response instances
        // which might be recreated/reset during tests anyway
        overrideableApp::reset();
        $app = static::createApp();

        // Additional overrides to get a more complete app lifecycle
        // and allow static global app::getModel() to work correctly
        $app::__setApp('twigtest');
        $app::__setVendor('codename');
        $app::__setNamespace('\\codename\\core\\ui\\tests\\templateengine\\twig');
        $app::__setHomedir(__DIR__);

        $app::getAppstack();

        // NOTE: if we reset the app in setUp(), we have to execute this initialization routine again, no matter what.
        // avoid re-init
        // if(static::$initialized) {
        //   return;
        // }

        static::$initialized = true;

        static::setEnvironmentConfig([
          'test' => [
            'filesystem' => [
              'local' => [
                'driver' => 'local',
              ],
            ],
            'translate' => [
              'default' => [
                'driver' => 'dummy',
              ],
            ],
            'templateengine' => [
              'default' => [
                'driver' => 'twig',
              ],
              'sandbox_available' => [
                'driver' => 'twig',
                'sandbox_enabled' => true,
                'sandbox_mode' => null, // not globally enabled
              ],
              'sandbox_global' => [
                'driver' => 'twig',
                'sandbox_enabled' => true,
                'sandbox_mode' => 'global', // sandbox for everything
              ],
              'otherext' => [
                'driver' => 'twig',
                'template_file_extension' => '.otherext.twig',
              ],
            ],
            'log' => [
              'default' => [
                'driver' => 'system',
                'data' => [
                  'name' => 'dummy',
                ],
              ],
            ],
          ],
        ]);
    }
}
