<?php
namespace codename\core\ui\tests\crud;

use codename\core\tests\base;

class crudTest extends base {

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
    $this->getModel('testmodel')
      ->addFilter('testmodel_id', 0, '>')
      ->delete();
  }

  /**
   * @inheritDoc
   */
  protected function setUp(): void
  {
    $app = static::createApp();

    // Additional overrides to get a more complete app lifecycle
    // and allow static global app::getModel() to work correctly
    $app->__setApp('crudtest');
    $app->__setVendor('codename');
    $app->__setNamespace('\\codename\\core\\ui\\tests\\crud');
    $app->__setHomedir('codename/core-ui/tests/crud');

    $app->getAppstack();

    // avoid re-init
    if(static::$initialized) {
      return;
    }

    static::$initialized = true;

    static::setEnvironmentConfig([
      'test' => [
        'database' => [
          // NOTE: by default, we do these tests using
          // pure in-memory sqlite.
          'default' => [
            'driver' => 'sqlite',
            // 'database_file' => 'testmodel.sqlite',
            'database_file' => ':memory:',
          ],
        ],
        'cache' => [
          'default' => [
            'driver' => 'memory'
          ]
        ],
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
            'driver' => 'dummy',
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

    static::createModel('crudtest', 'testmodel', [
      'field' => [
        'testmodel_id',
        'testmodel_created',
        'testmodel_modified',
        'testmodel_text',
        'testmodel_unique_single',
        'testmodel_unique_multi1',
        'testmodel_unique_multi2',
      ],
      'primary' => [
        'testmodel_id'
      ],
      'unique' => [
        'testmodel_unique_single',
        [ 'testmodel_unique_multi1', 'testmodel_unique_multi2' ],
      ],
      'options' => [
        'testmodel_unique_single' => [
          'length' => 16
        ],
        'testmodel_unique_multi1' => [
          'length' => 16
        ],
        'testmodel_unique_multi2' => [
          'length' => 16
        ],
      ],
      'datatype' => [
        'testmodel_id'       => 'number_natural',
        'testmodel_created'  => 'text_timestamp',
        'testmodel_modified' => 'text_timestamp',
        'testmodel_text'     => 'text',
        'testmodel_unique_single' => 'text',
        'testmodel_unique_multi1' => 'text',
        'testmodel_unique_multi2' => 'text',
      ],
      'connection' => 'default'
    ]
    // ,function($schema, $model, $config) {
    //   return new \codename\core\io\tests\target\model\testmodel([]);
    // }
    );

    static::architect('crudtest', 'codename', 'test');
  }

  // public function testCrudInit(): void {
  //   $model = $this->getModel('testmodel');
  //   $crudInstance = new \codename\core\ui\crud($model);
  // }

  /**
   * [testCrudInit description]
   */
  public function testCrudInit(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);



    $crudInstance->create();
  }

}
