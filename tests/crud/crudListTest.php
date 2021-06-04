<?php
namespace codename\core\ui\tests\crud;

use codename\core\test\base;
use codename\core\test\overrideableApp;

class crudListTest extends base {

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

    $this->getModel('testmodeljoin')
      ->addFilter('testmodeljoin_id', 0, '>')
      ->delete();
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
    $app->__setApp('crudtest');
    $app->__setVendor('codename');
    $app->__setNamespace('\\codename\\core\\ui\\tests\\crud');
    $app->__setHomedir('codename/core-ui/tests/crud');

    $app->getAppstack();

    // avoid re-init
    if(static::$initialized) {
      return;
    }

    // TODO: rework via overrideableApp
    $app->__injectClientInstance('auth', 'default', new dummyAuth);

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
        'auth' => [
          'default' => [
            'driver' => 'dummy'
          ]
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

    static::createModel(
      'crudtest', 'testmodel',
      \codename\core\ui\tests\crud\model\testmodel::$staticConfig,
      function($schema, $model, $config) {
        return new \codename\core\ui\tests\crud\model\testmodel([]);
      }
    );

    static::createModel(
      'crudtest', 'testmodeljoin',
      \codename\core\ui\tests\crud\model\testmodeljoin::$staticConfig,
      function($schema, $model, $config) {
        return new \codename\core\ui\tests\crud\model\testmodeljoin([]);
      }
    );

    static::architect('crudtest', 'codename', 'test');
  }

  /**
   * [testCrudListConfig description]
   */
  public function testCrudListConfig(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);

    $crudInstance->addTopaction([
      'name'        => 'exampleTopName',
      'view'        => 'exampleTopView',
      'context'     => 'exampleTopContext',
      'icon'        => 'exampleTopIcon',
      'btnClass'    => 'exampleTopBtnClass',
    ]);

    $crudInstance->addBulkaction([
      'name'        => 'exampleBulkName',
      'view'        => 'exampleBulkView',
      'context'     => 'exampleBulkContext',
      'icon'        => 'exampleBulkIcon',
      'btnClass'    => 'exampleBulkBtnClass',
      '_security'   => [
        'group'     => 'example'
      ],
    ]);

    $crudInstance->addElementaction([
      'name'        => 'exampleElementName',
      'view'        => 'exampleElementView',
      'context'     => 'exampleElementContext',
      'icon'        => 'exampleElementIcon',
      'btnClass'    => 'exampleElementBtnClass',
      'condition'   => '$condition = false;'
    ]);

    $crudInstance->addModifier('example', function($row) {
      return 'example';
    });

    $resultConfig = $crudInstance->listconfig();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'exampleTopName'  => [
        'name'        => 'exampleTopName',
        'view'        => 'exampleTopView',
        'context'     => 'exampleTopContext',
        'icon'        => 'exampleTopIcon',
        'btnClass'    => 'exampleTopBtnClass',
        'display'     => 'BTN_EXAMPLETOPNAME',
      ]
    ], $responseData['topActions']);
    $this->assertEmpty($responseData['bulkActions']);
    $this->assertEmpty($responseData['elementActions']);

    $this->assertEquals([
      'testmodel_text',
      'testmodel_testmodeljoin_id',
      'testmodel_id',
      'example',
    ], $responseData['visibleFields']);

    $this->assertInstanceOf(\codename\core\ui\form::class, $responseData['filterform']);

  }

  /**
   * [testCrudListConfigWithSeparateConfig description]
   */
  public function testCrudListConfigWithSeparateConfig(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->setConfig('crudtest_testmodel_crudlistconfig');

    $crudInstance->addTopaction([
      'name'        => 'exampleTopName',
      'view'        => 'exampleTopView',
      'context'     => 'exampleTopContext',
      'icon'        => 'exampleTopIcon',
      'btnClass'    => 'exampleTopBtnClass',
    ]);

    $crudInstance->addBulkaction([
      'name'        => 'exampleBulkName',
      'view'        => 'exampleBulkView',
      'context'     => 'exampleBulkContext',
      'icon'        => 'exampleBulkIcon',
      'btnClass'    => 'exampleBulkBtnClass',
      '_security'   => [
        'group'     => 'example'
      ],
    ]);

    $crudInstance->addElementaction([
      'name'        => 'exampleElementName',
      'view'        => 'exampleElementView',
      'context'     => 'exampleElementContext',
      'icon'        => 'exampleElementIcon',
      'btnClass'    => 'exampleElementBtnClass',
      'condition'   => '$condition = false;'
    ]);

    $customizedFields = $crudInstance->getConfig()->get('customized_fields');
    $this->assertEquals([
      'testmodel_testmodeljoin_id',
    ], $customizedFields);
    $crudInstance->setCustomizedFields($customizedFields);

    $crudInstance->addModifier('example', function($row) {
      return 'example';
    });

    $crudInstance->setColumnOrder([
      'testmodel_id',
      'testmodel_text',
    ]);

    $resultConfig = $crudInstance->listconfig();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'exampleTopName'  => [
        'name'        => 'exampleTopName',
        'view'        => 'exampleTopView',
        'context'     => 'exampleTopContext',
        'icon'        => 'exampleTopIcon',
        'btnClass'    => 'exampleTopBtnClass',
        'display'     => 'BTN_EXAMPLETOPNAME',
      ]
    ], $responseData['topActions']);
    $this->assertEmpty($responseData['bulkActions']);
    $this->assertEmpty($responseData['elementActions']);

    $this->assertEquals([
      'testmodel_id',
      'testmodel_text',
      'testmodel_testmodeljoin_id',
      'example',
    ], $responseData['visibleFields']);

    $this->assertInstanceOf(\codename\core\ui\form::class, $responseData['filterform']);

  }

  /**
   * [testCrudListView description]
   */
  public function testCrudListView(): void {
    // set demo data
    $model = $this->getModel('testmodel')->addModel($this->getModel('testmodeljoin'));
    $model->saveWithChildren([
      'testmodel_text'          => 'moep',
      'testmodel_testmodeljoin' => [
        'testmodeljoin_text'    => 'se',
      ]
    ]);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);

    $crudInstance->addModifier('example', function($row) {
      return 'example';
    });

    $crudInstance->addResultsetModifier(function($results) {
      foreach($results as &$result) {
        $result['testmodel_text'] .= $result['testmodel_testmodeljoin']['testmodeljoin_text'];
      }
      return $results;
    });

    $resultView = $crudInstance->listview();
    $this->assertEmpty($resultView);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'testmodel_text',
      'testmodel_testmodeljoin_id',
      'testmodel_id',
      'example',
    ], $responseData['visibleFields']);

    $this->assertInstanceOf(\codename\core\ui\form::class, $responseData['filterform']);

    $this->assertEquals([
      [
        'testmodel_text'                        => 'moepse',
        'testmodel_testmodeljoin_id_FORMATTED'  => 'se',
        'testmodel_testmodeljoin_id'            => '1',
        'testmodel_id'                          => '1',
        'example'                               => 'example',
      ],
    ], $responseData['rows']);

    $this->assertEquals([
      'crud_pagination_seek_enabled'  => false,
      'crud_pagination_count'         => 1,
      'crud_pagination_page'          => 1,
      'crud_pagination_pages'         => 1.0,
      'crud_pagination_limit'         => 5,
    ], [
      'crud_pagination_seek_enabled'  => $responseData['crud_pagination_seek_enabled'],
      'crud_pagination_count'         => $responseData['crud_pagination_count'],
      'crud_pagination_page'          => $responseData['crud_pagination_page'],
      'crud_pagination_pages'         => $responseData['crud_pagination_pages'],
      'crud_pagination_limit'         => $responseData['crud_pagination_limit'],
    ]);
  }

  /**
   * [testCrudListViewWithSeparateConfig description]
   */
  public function testCrudListViewWithSeparateConfig(): void {
    // set demo data
    $model = $this->getModel('testmodel')->addModel($this->getModel('testmodeljoin'));
    $model->saveWithChildren([
      'testmodel_text'          => 'moep',
      'testmodel_testmodeljoin' => [
        'testmodeljoin_text'    => 'se',
      ]
    ]);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->setConfig('crudtest_testmodel_crudlistconfig');

    $crudInstance->addModifier('example', function($row) {
      return 'example';
    });

    $crudInstance->setColumnOrder([
      'testmodel_id',
      'testmodel_text',
    ]);

    $crudInstance->addResultsetModifier(function($results) {
      foreach($results as &$result) {
        $result['testmodel_text'] .= $result['testmodel_testmodeljoin']['testmodeljoin_text'];
      }
      return $results;
    });

    $resultView = $crudInstance->listview();
    $this->assertEmpty($resultView);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'testmodel_id',
      'testmodel_text',
      'testmodel_testmodeljoin_id',
      'example',
    ], $responseData['visibleFields']);

    $this->assertInstanceOf(\codename\core\ui\form::class, $responseData['filterform']);

    $fields = $responseData['filterform']->getFields();
    $this->assertCount(3, $fields);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[0]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[1]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[2]);
    $this->assertEquals('field_config_example_title', $fields[0]->getConfig()->get('field_title'));

    $this->assertEquals([
      [
        'testmodel_id'                          => '2',
        'testmodel_text'                        => 'moepse',
        'testmodel_testmodeljoin_id_FORMATTED'  => 'se',
        'testmodel_testmodeljoin_id'            => '2',
        'example'                               => 'example',
      ],
    ], $responseData['rows']);

    $this->assertEquals([
      'crud_pagination_seek_enabled'  => false,
      'crud_pagination_count'         => 1,
      'crud_pagination_page'          => 1,
      'crud_pagination_pages'         => 1.0,
      'crud_pagination_limit'         => 5,
    ], [
      'crud_pagination_seek_enabled'  => $responseData['crud_pagination_seek_enabled'],
      'crud_pagination_count'         => $responseData['crud_pagination_count'],
      'crud_pagination_page'          => $responseData['crud_pagination_page'],
      'crud_pagination_pages'         => $responseData['crud_pagination_pages'],
      'crud_pagination_limit'         => $responseData['crud_pagination_limit'],
    ]);
  }

}

class dummyAuth extends \codename\core\auth {
  /**
   * @inheritDoc
   */
  public function authenticate(\codename\core\credential $credential): array
  {
    throw new \LogicException('Not implemented'); // TODO
  }

  /**
   * @inheritDoc
   */
  public function createCredential(array $parameters) : \codename\core\credential
  {
    throw new \LogicException('Not implemented'); // TODO
  }

  /**
   * @inheritDoc
   */
  public function makeHash(\codename\core\credential $credential): string
  {
    throw new \LogicException('Not implemented'); // TODO
  }

  /**
   * @inheritDoc
   */
  public function isAuthenticated(): bool
  {
    return true; // ?
  }

  /**
   * @inheritDoc
   */
  public function memberOf(string $groupName): bool
  {
    return false;
  }
}
