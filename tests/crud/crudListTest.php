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

    $this->getModel('testmodelforcejoin')
      ->addFilter('testmodelforcejoin_id', 0, '>')
      ->delete();

    $this->getModel('testmodelcollection')
      ->addFilter('testmodelcollection_id', 0, '>')
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
    $app->__setHomedir(__DIR__);

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

    static::createModel(
      'crudtest', 'testmodelforcejoin',
      \codename\core\ui\tests\crud\model\testmodelforcejoin::$staticConfig,
      function($schema, $model, $config) {
        return new \codename\core\ui\tests\crud\model\testmodelforcejoin([]);
      }
    );

    static::createModel(
      'crudtest', 'testmodelcollection',
      \codename\core\ui\tests\crud\model\testmodelcollection::$staticConfig,
      function($schema, $model, $config) {
        return new \codename\core\ui\tests\crud\model\testmodelcollection([]);
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

    $crudInstance->onCreateFormfield = function(array &$fielddata) {
      $fielddata['field_example'] = 'example';
    };
    $crudInstance->onFormfieldCreated = function(\codename\core\ui\field &$field) {
      if($field->getProperty('field_example') === 'example') {
        $field->setProperty('field_example', 'example2');
      }
    };

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

    $fields = $responseData['filterform']->getFields();
    $this->assertCount(3, $fields);

    // only by foreign fields
    $this->assertEquals('example2', $fields[1]->getProperty('field_example'));

  }

  /**
   * [testCrudListConfigDisplaySelectedFields description]
   */
  public function testCrudListConfigDisplaySelectedFields(): void {
    \codename\core\app::getRequest()->setData('display_selectedfields', [ 'testmodel_text' ]);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $resultConfig = $crudInstance->listconfig();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'testmodel_text',
      'testmodel_id',
    ], $responseData['visibleFields']);

  }

  /**
   * [testCrudListConfigDisplaySelectedFieldsWithForceJoin description]
   */
  public function testCrudListConfigDisplaySelectedFieldsWithForceJoin(): void {
    \codename\core\app::getRequest()->setData('display_selectedfields', [ 'testmodelforcejoin_text' ]);

    $model = $this->getModel('testmodelforcejoin');
    $crudInstance = new \codename\core\ui\crud($model);
    $resultConfig = $crudInstance->listconfig();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'testmodelforcejoin_text',
      'testmodelforcejoin_id',
    ], $responseData['visibleFields']);

  }

  /**
   * [testCrudListConfigImportAndExport description]
   */
  public function testCrudListConfigImportAndExport(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->setConfig('crudtest_testmodel_crudlistconfig');
    $resultConfig = $crudInstance->listconfig();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertTrue($responseData['enable_import']);
    $this->assertTrue($responseData['enable_export']);
    $this->assertEquals([
      'json',
    ], $responseData['export_types']);

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

    $crudInstance->addRowModifier(function($row) {
      return [
        'example1' => 'omfg!',
        'example2' => true,
      ];
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
        '__modifier'                            => 'example1="omfg!" example2',
      ],
    ], $responseData['rows']);

    $this->assertEquals('example1="omfg!" example2', $responseData['rows'][0]['__modifier']);

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
   * [testCrudListViewSmall description]
   */
  public function testCrudListViewSmall(): void {
    // set demo data
    $model = $this->getModel('testmodeljoin');
    $model->save([
      'testmodeljoin_text'    => 'example',
    ]);

    $model = $this->getModel('testmodeljoin');
    $crudInstance = new \codename\core\ui\crud($model);
    $resultView = $crudInstance->listview();
    $this->assertEmpty($resultView);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'testmodeljoin_text',
      'testmodeljoin_id',
    ], $responseData['visibleFields']);

    $this->assertCount(1, $responseData['rows']);
    $this->assertEquals('example', $responseData['rows'][0]['testmodeljoin_text']);

  }

  /**
   * [testCrudListViewSmallWithRowModifier description]
   */
  public function testCrudListViewSmallWithRowModifier(): void {
    // set demo data
    $model = $this->getModel('testmodeljoin');
    $model->save([
      'testmodeljoin_text'    => 'example',
    ]);

    $model = $this->getModel('testmodeljoin');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->addRowModifier(function($row) {
      return [
        'example1' => 'omfg!',
        'example2' => true,
      ];
    });
    $resultView = $crudInstance->listview();
    $this->assertEmpty($resultView);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'testmodeljoin_text',
      'testmodeljoin_id',
    ], $responseData['visibleFields']);

    $this->assertCount(1, $responseData['rows']);
    $this->assertEquals('example', $responseData['rows'][0]['testmodeljoin_text']);
    $this->assertEquals('example1="omfg!" example2', $responseData['rows'][0]['__modifier']);

  }

  /**
   * [testCrudListViewWithSetResultData description]
   */
  public function testCrudListViewWithSetResultData(): void {
    $model = $this->getModel('testmodeljoin');
    $model->save([
      'testmodeljoin_text'    => 'example',
    ]);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model, null, 'crudtest_testmodel_field_is_array');
    $crudInstance->setProvideRawData(true);
    $crudInstance->setResultData([
      [
        'testmodel_text'              => [
          'example' => 'example',
        ],
        'testmodel_testmodeljoin_id'  => [ 1, 2, 3, 4, 5 ],
      ],
    ]);
    $resultView = $crudInstance->listview();
    $this->assertEmpty($resultView);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      [ 'testmodel_text', 'example' ],
      'testmodel_testmodeljoin_id',
      'testmodel_id',
    ], $responseData['visibleFields']);

    $this->assertEquals([
      [
        'testmodel_text'              => [
          'example' => 'example',
        ],
        'testmodel_testmodeljoin_id'  => [ 1, 2, 3, 4, 5 ],
        'testmodel_testmodeljoin_id_FORMATTED' => 'example',
        'testmodel_id'                => null,
      ],
    ], $responseData['rows'], json_encode($responseData['rows']));
  }

  /**
   * [testCrudListViewWithSeparateConfig description]
   */
  public function testCrudListViewWithSeparateConfig(): void {
    \codename\core\app::getRequest()->setData('crud_pagination_page', 10);
    \codename\core\app::getRequest()->setData('crud_pagination_limit', 10);

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
        'testmodel_testmodeljoin_id'            => '5',
        'example'                               => 'example',
      ],
    ], $responseData['rows']);

    $this->assertEquals([
      'crud_pagination_seek_enabled'  => false,
      'crud_pagination_count'         => 1,
      'crud_pagination_page'          => 1,
      'crud_pagination_pages'         => 1.0,
      'crud_pagination_limit'         => 10,
    ], [
      'crud_pagination_seek_enabled'  => $responseData['crud_pagination_seek_enabled'],
      'crud_pagination_count'         => $responseData['crud_pagination_count'],
      'crud_pagination_page'          => $responseData['crud_pagination_page'],
      'crud_pagination_pages'         => $responseData['crud_pagination_pages'],
      'crud_pagination_limit'         => $responseData['crud_pagination_limit'],
    ]);
  }

  /**
   * [testCrudListViewDisplaySelectedFields description]
   */
  public function testCrudListViewDisplaySelectedFields(): void {
    \codename\core\app::getRequest()->setData('display_selectedfields', [ 'testmodel_text' ]);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $resultConfig = $crudInstance->listview();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'testmodel_text',
      'testmodel_id',
    ], $responseData['visibleFields']);

  }

  /**
   * [testCrudListViewImportAndExport description]
   */
  public function testCrudListViewImportAndExport(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->setConfig('crudtest_testmodel_crudlistconfig');
    $resultConfig = $crudInstance->listview();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertTrue($responseData['enable_import']);
    $this->assertTrue($responseData['enable_export']);
    $this->assertEquals([
      'json',
    ], $responseData['export_types']);

  }

  /**
   * [testCrudListViewCrudEditable description]
   */
  public function testCrudListViewCrudEditable(): void {
    \codename\core\app::getRequest()->setData('crud_editable', true);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $resultConfig = $crudInstance->listview();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'testmodel_text',
      'testmodel_testmodeljoin_id',
      'testmodel_id',
    ], $responseData['visibleFields']);

  }

  /**
   * [testCrudListViewSeekStablePosition description]
   */
  public function testCrudListViewSeekStablePosition(): void {
    // set demo data
    $model = $this->getModel('testmodel');
    $model->saveWithChildren([
      'testmodel_text'          => 'moepse1',
      'testmodel_flag'          => 1,
    ]);
    $model->saveWithChildren([
      'testmodel_text'          => 'moepse2',
      'testmodel_flag'          => 2,
    ]);
    $model->saveWithChildren([
      'testmodel_text'          => 'moepse3',
      'testmodel_flag'          => 4,
    ]);

    \codename\core\app::getRequest()->setData('crud_pagination_first_id', 1);
    \codename\core\app::getRequest()->setData('crud_pagination_seek', 0);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->setConfig('crudtest_testmodel_seek');
    $resultConfig = $crudInstance->listview();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertCount(2, $responseData['rows']);
    $this->assertEquals('moepse1', $responseData['rows'][0]['testmodel_text']);
    $this->assertEquals(1, $responseData['rows'][0]['testmodel_flag']);
    $this->assertEquals('moepse2', $responseData['rows'][1]['testmodel_text']);
    $this->assertEquals(2, $responseData['rows'][1]['testmodel_flag']);

  }

  /**
   * [testCrudListViewSeekMovingBackwards description]
   */
  public function testCrudListViewSeekMovingBackwards(): void {
    // set demo data
    $model = $this->getModel('testmodel');
    $model->saveWithChildren([
      'testmodel_text'          => 'moepse1',
    ]);
    $model->saveWithChildren([
      'testmodel_text'          => 'moepse2',
    ]);
    $model->saveWithChildren([
      'testmodel_text'          => 'moepse3',
    ]);

    $model->addFilter('testmodel_text', 'moepse3');
    $res = $model->search()->getResult();

    \codename\core\app::getRequest()->setData('crud_pagination_first_id', $res[0]['testmodel_id']);
    \codename\core\app::getRequest()->setData('crud_pagination_seek', -1);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->setConfig('crudtest_testmodel_seek');
    $resultConfig = $crudInstance->listview();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertCount(2, $responseData['rows']);
    $this->assertEquals('moepse1', $responseData['rows'][0]['testmodel_text']);
    $this->assertEquals('moepse2', $responseData['rows'][1]['testmodel_text']);

  }

  /**
   * [testCrudListViewSeekMovingForwards description]
   */
  public function testCrudListViewSeekMovingForwards(): void {
    // set demo data
    $model = $this->getModel('testmodel');
    $model->saveWithChildren([
      'testmodel_text'          => 'moepse1',
    ]);
    $model->saveWithChildren([
      'testmodel_text'          => 'moepse2',
    ]);
    $model->saveWithChildren([
      'testmodel_text'          => 'moepse3',
    ]);

    $model->addFilter('testmodel_text', 'moepse2');
    $res = $model->search()->getResult();

    \codename\core\app::getRequest()->setData('crud_pagination_last_id', $res[0]['testmodel_id']);
    \codename\core\app::getRequest()->setData('crud_pagination_seek', 1);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->setConfig('crudtest_testmodel_seek');
    $resultConfig = $crudInstance->listview();
    $this->assertEmpty($resultConfig);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertCount(1, $responseData['rows']);
    $this->assertEquals('moepse3', $responseData['rows'][0]['testmodel_text']);

  }

  /**
   * [testCrudListViewPaginationWithFilter description]
   */
  public function testCrudListViewPaginationWithFilter(): void {
    \codename\core\app::getRequest()->setData('crud_pagination_page_prev', 2);
    \codename\core\app::getRequest()->setData('crud_pagination_limit', 2);

    // set demo data
    $model = $this->getModel('testmodel');
    $model->saveWithChildren([
      'testmodel_text'          => 'example1',
      'testmodel_flag'          => 1,
    ]);
    $model->saveWithChildren([
      'testmodel_text'          => 'example2',
      'testmodel_flag'          => 1,
    ]);
    $model->saveWithChildren([
      'testmodel_text'          => 'example3',
      'testmodel_flag'          => 1,
    ]);
    $model->saveWithChildren([
      'testmodel_text'          => 'example4',
      'testmodel_flag'          => 1,
    ]);
    $model->saveWithChildren([
      'testmodel_text'          => 'example5',
      'testmodel_flag'          => 1,
    ]);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);

    \codename\core\app::getRequest()->setData($crudInstance::CRUD_FILTER_IDENTIFIER, [
      'testmodel_flag'          => 1,
    ]);

    $resultView = $crudInstance->listview();
    $this->assertEmpty($resultView);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'testmodel_text',
      'testmodel_testmodeljoin_id',
      'testmodel_id',
    ], $responseData['visibleFields']);

    $this->assertCount(2, $responseData['rows']);
    $this->assertEquals('example3', $responseData['rows'][0]['testmodel_text']);
    $this->assertEquals('example4', $responseData['rows'][1]['testmodel_text']);

    $this->assertEquals([
      'crud_pagination_seek_enabled'  => false,
      'crud_pagination_count'         => 5,
      'crud_pagination_page'          => 2,
      'crud_pagination_pages'         => 3.0,
      'crud_pagination_limit'         => 2,
    ], [
      'crud_pagination_seek_enabled'  => $responseData['crud_pagination_seek_enabled'],
      'crud_pagination_count'         => $responseData['crud_pagination_count'],
      'crud_pagination_page'          => $responseData['crud_pagination_page'],
      'crud_pagination_pages'         => $responseData['crud_pagination_pages'],
      'crud_pagination_limit'         => $responseData['crud_pagination_limit'],
    ]);
  }

  /**
   * [testCrudListViewFilter description]
   */
  public function testCrudListViewFilter(): void {
    \codename\core\app::getRequest()->setData('crud_pagination_page_prev', 2);
    \codename\core\app::getRequest()->setData('crud_pagination_limit', 2);

    // set demo data
    $model = $this->getModel('testmodel');
    $model->saveWithChildren([
      'testmodel_text'            => 'example1',
      'testmodel_number_natural'  => 1,
      'testmodel_flag'            => 3,
    ]);
    $model->saveWithChildren([
      'testmodel_text'            => 'example2',
      'testmodel_number_natural'  => 1,
      'testmodel_flag'            => 3,
    ]);
    $model->saveWithChildren([
      'testmodel_text'            => 'example3',
      'testmodel_number_natural'  => 1,
      'testmodel_flag'            => 3,
    ]);
    $model->saveWithChildren([
      'testmodel_text'            => 'example4',
      'testmodel_number_natural'  => 1,
      'testmodel_flag'            => 3,
    ]);
    $model->saveWithChildren([
      'testmodel_text'            => 'example5',
      'testmodel_number_natural'  => 1,
      'testmodel_flag'            => 3,
    ]);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->setConfig('crudtest_testmodel_filter');

    // added filters
    $now = new \DateTime('now');
    \codename\core\app::getRequest()->setData($crudInstance::CRUD_FILTER_IDENTIFIER, [
      'provide_filter_example'    => true,
      'testmodel_flag'            => [ 1, 2 ],
      'testmodel_text'            => [
        'example2',
        'example3',
        'example4',
        'example5',
      ],
      'testmodel_number_natural'  => 1,
      'search'                    => 'example%',
      'testmodel_created'         => [
        $now->format('Y-m-d 00:00:00'),
        $now->format('Y-m-d 23:59:59'),
      ],
    ]);

    $crudInstance->provideFilter('provide_filter_example', [
      'datatype' => 'text',
    ], function(\codename\core\ui\crud $crudInstance, $filterValue) {
      $crudInstance->getMyModel()->addFilter('testmodel_text', 'example1', '!=');
    });

    $resultView = $crudInstance->listview();
    $this->assertEmpty($resultView);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertEquals([
      'testmodel_text',
      'testmodel_testmodeljoin_id',
      'testmodel_id',
    ], $responseData['visibleFields']);

    $this->assertCount(2, $responseData['rows']);
    $this->assertEquals('example4', $responseData['rows'][0]['testmodel_text']);
    $this->assertEquals('example5', $responseData['rows'][1]['testmodel_text']);
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
    if($groupName == 'group_true') {
      return true;
    }
    return false;
  }
}
