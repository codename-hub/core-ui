<?php

namespace codename\core\ui\tests;

use codename\core\exception;
use codename\core\generator\restUrlGenerator;
use codename\core\generator\urlGenerator;
use codename\core\test\base;
use codename\core\test\overrideableApp;
use codename\core\ui\app;
use ReflectionException;

class appTest extends base
{
    /**
     * [testConstruct description]
     */
    public function testConstruct(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_CORE_UI_APP_ILLEGAL_CALL');

        new app();
    }

    /**
     * [testRun description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testRun(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_CORE_UI_APP_ILLEGAL_CALL');

        (new appRun())->run();
    }

    /**
     * [testUrlGenerator description]
     */
    public function testUrlGenerator(): void
    {
        $urlGenerator = app::getUrlGenerator();
        static::assertEquals((new urlGenerator()), $urlGenerator);

        $restUrlGenerator = new restUrlGenerator();
        app::setUrlGenerator($restUrlGenerator);

        $getUrlGenerator = app::getUrlGenerator();
        static::assertEquals($restUrlGenerator, $getUrlGenerator);
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     * @throws exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $app = static::createApp();
        overrideableApp::__injectApp([
          'vendor' => 'codename',
          'app' => 'core-ui',
          'namespace' => '\\codename\\core\\ui',
        ]);
        $app::getAppstack();
    }
}

class appRun extends app
{

    /**
     *
     */
    public function __construct()
    {
    }

}
