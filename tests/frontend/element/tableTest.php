<?php

namespace codename\core\ui\tests\frontend\element;

use codename\core\exception;
use codename\core\test\base;
use codename\core\test\overrideableApp;
use codename\core\ui\frontend\element\table;
use ReflectionException;

class tableTest extends base
{
    /**
     * [testInvalidConstruct description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testInvalidConstruct(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_CORE_UI_FRONTEND_ELEMENT_INVALID_CONFIG');
        new core_ui_frontend_element_table([], []);
    }

    /**
     * [testOutputDataWithoutData description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testOutputDataWithoutData(): void
    {
        $table = new table([
          'columns' => [0, 1, 2],
        ]);

        $outputData = $table->outputData();
        static::assertEquals([
          'max' => [1, 1, 1],
          'header' => [0, 1, 2],
          'rows' => [],
          'footer' => [],
        ], $outputData);
    }

    /**
     * [testOutputDataWithoutData description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testOutputDataWithData(): void
    {
        $data = [
          [
            0 => 'Test 1',
            1 => 'Test 2',
            2 => 3,
          ],
        ];
        $table = new table([], $data);

        $outputData = $table->outputData();
        static::assertEquals([
          'max' => [6, 6, 1],
          'header' => [0, 1, 2],
          'rows' => [
            ['Test 1', 'Test 2', 3],
          ],
          'footer' => [],
        ], $outputData, json_encode($outputData));
    }

    /**
     * [testOutputStringWithoutData description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testOutputStringWithoutData(): void
    {
        $table = new table();
        static::assertEquals('frontend/element/table/default', $table->outputString());
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

        static::setEnvironmentConfig([
          'test' => [
            'cache' => [
              'default' => [
                'driver' => 'memory',
              ],
            ],
            'translate' => [
              'default' => [
                'driver' => 'json',
                'inherit' => true,
              ],
            ],
            'templateengine' => [
              'default' => [
                'driver' => 'dummy',
              ],
            ],
          ],
        ]);
    }
}

/**
 * [core_ui_frontend_element_table description]
 */
class core_ui_frontend_element_table extends table
{
    /**
     * validator used for validating the given configuration
     * @var string
     */
    protected $configValidatorName = 'number';

    /**
     * @param array $config
     * @param array $data
     * @throws ReflectionException
     * @throws exception
     */
    public function __construct(array $config = [], array $data = [])
    {
        parent::__construct($config, $data);
    }
}
