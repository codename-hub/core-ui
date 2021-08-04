<?php
namespace codename\core\ui\tests\frontend\element;

use \codename\core\test\base;
use \codename\core\test\overrideableApp;

class tableTest extends base
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

    static::setEnvironmentConfig([
      'test' => [
        'cache' => [
          'default' => [
            'driver' => 'memory'
          ]
        ],
        'translate' => [
          'default' => [
            'driver'  => 'json',
            'inherit' => true,
          ]
        ],
        'templateengine' => [
          'default' => [
            'driver' => 'dummy',
          ]
        ],
      ]
    ]);
  }

  /**
   * [testInvalidConstruct description]
   */
  public function testInvalidConstruct(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_CORE_UI_FRONTEND_ELEMENT_INVALID_CONFIG');
    $form = new core_ui_frontend_element_table([], []);
  }

  /**
   * [testOutputDataWithoutData description]
   */
  public function testOutputDataWithoutData(): void {
    $table = new \codename\core\ui\frontend\element\table([
      'columns' => [ 0, 1, 2 ]
    ]);

    $outputData = $table->outputData();
    $this->assertEquals([
      'max'     => [ 1, 1, 1 ],
      'header'  => [ 0, 1, 2 ],
      'rows'    => [],
      'footer'  => []
    ], $outputData);

  }

  /**
   * [testOutputDataWithoutData description]
   */
  public function testOutputDataWithData(): void {
    $data = [
      [
        0 => 'Test 1',
        1 => 'Test 2',
        2 => 3,
      ]
    ];
    $table = new \codename\core\ui\frontend\element\table([], $data);

    $outputData = $table->outputData();
    $this->assertEquals([
      'max'     => [ 6, 6, 1 ],
      'header'  => [ 0, 1, 2 ],
      'rows'    => [
        [ 'Test 1', 'Test 2', 3 ]
      ],
      'footer'  => []
    ], $outputData, json_encode($outputData));

  }

  /**
   * [testOutputStringWithoutData description]
   */
  public function testOutputStringWithoutData(): void {
    $table = new \codename\core\ui\frontend\element\table();
    $this->assertEquals('frontend/element/table/default', $table->outputString());
  }

}

/**
 * [core_ui_frontend_element_table description]
 */
class core_ui_frontend_element_table extends \codename\core\ui\frontend\element\table {

  protected $configValidatorName = 'number';

  function __construct(array $config = array(), array $data = array()) {
    parent::__construct($config, $data);
  }
}
