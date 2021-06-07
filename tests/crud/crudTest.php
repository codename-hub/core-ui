<?php
namespace codename\core\ui\tests\crud;

use codename\core\test\base;
use codename\core\test\overrideableApp;

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
   * Tests basic crud init
   * @coversNothing
   */
  public function testCrudInit(): void {

    $this->expectNotToPerformAssertions();
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);

    // just test, if it doesn't crash
    $crudInstance->create();
  }

  /**
   * [testCrudStats description]
   */
  public function testCrudStats(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    \codename\core\app::getRequest()->setData($crudInstance::CRUD_FILTER_IDENTIFIER, false);

    $stats = $crudInstance->stats();
    $this->assertEmpty($stats);

    $responseData = overrideableApp::getResponse()->getData();
    $this->assertEquals([
      'crud_pagination_seek_enabled'  => false,
      'crud_pagination_count'         => 0,
      'crud_pagination_page'          => 1,
      'crud_pagination_pages'         => 1,
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
   * [testCrudDisplaytypeStatic description]
   */
  public function testCrudDisplaytypeStatic(): void {
    $types = [
      'structure_address'         => 'structure_address',
      'structure_text_telephone'  => 'structure_text_telephone',
      'structure'                 => 'structure',
      'boolean'                   => 'yesno',
      'text_date'                 => 'date',
      'text_date_birthdate'       => 'date',
      'text_timestamp'            => 'timestamp',
      'text_datetime_relative'    => 'relativetime',
      'input'                     => 'input',
      'example'                   => 'input',
    ];
    foreach($types as $daType => $diType) {
      $result = \codename\core\ui\crud::getDisplaytypeStatic($daType);
      $this->assertEquals($diType, $result);
    }
  }

  /**
   * [testCrudGetData description]
   */
  public function testCrudGetDataNull(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);

    $result = $crudInstance->getData('example');
    $this->assertNull($result);

  }

  /**
   * [testCrudSetRequestDataAndNormalizationData description]
   */
  public function testCrudSetRequestDataAndNormalizationData(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model, [
      'testmodel_text'          => 'moep',
      'testmodel_testmodeljoin' => [
        'testmodeljoin_text'    => 'se',
      ],
      'wrong_field'             => 'hello'
    ]);
    $crudInstance->useFormNormalizationData();

    $result = $crudInstance->getData();
    $this->assertEquals([
      'testmodel_text'          => 'moep',
      'testmodel_testmodeljoin' => [
        'testmodeljoin_text'    => 'se',
      ],
    ], $result);
  }

  /**
   * [testCrudImport description]
   */
  public function testCrudImport(): void {
    $data = [
      [ 'testmodel_id' => 1, 'testmodel_text' => 'example1' ],
      [ 'testmodel_id' => 2, 'testmodel_text' => 'example2' ],
      [ 'testmodel_id' => 3, 'testmodel_text' => 'example3' ],
    ];

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $resultView = $crudInstance->import($data);
    $this->assertEmpty($resultView);

    $responseData = overrideableApp::getResponse()->getData();

    $dataClean = [];
    foreach($data as $v) {
      unset($v['testmodel_id']);
      $dataClean[] = $v;
    }

    $this->assertCount(3, $responseData['import_data']);
    $this->assertEquals($dataClean, $responseData['import_data']);

    $res = $model->search()->getResult();
    $this->assertCount(3, $res);

  }

  /**
   * [testCrudImportInvalid description]
   */
  public function testCrudImportInvalid(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('CRUD_IMPORT_INVALID_DATASET');

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->import([
      [ 'testmodel_testmodeljoin_id' => 'example' ],
    ]);

  }

  /**
   * [testCrudExport description]
   */
  public function testCrudExport(): void {
    // set demo data
    $model = $this->getModel('testmodel')->addModel($this->getModel('testmodeljoin'));
    $model->saveWithChildren([
      'testmodel_text'          => 'example',
    ]);

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $resultView = $crudInstance->export(true);
    $this->assertEmpty($resultView);

    $responseData = overrideableApp::getResponse()->getData();

    $this->assertCount(1, $responseData['rows']);
    $this->assertNotEmpty($responseData['rows'][0]['testmodel_id']);
    $this->assertNotEmpty($responseData['rows'][0]['testmodel_created']);
    $this->assertEmpty($responseData['rows'][0]['testmodel_modified']);
    $this->assertEmpty($responseData['rows'][0]['testmodel_testmodeljoin_id']);
    $this->assertEquals('example', $responseData['rows'][0]['testmodel_text']);
    $this->assertEmpty($responseData['rows'][0]['testmodel_unique_single']);
    $this->assertEmpty($responseData['rows'][0]['testmodel_unique_multi1']);
    $this->assertEmpty($responseData['rows'][0]['testmodel_unique_multi2']);
    $this->assertNotEmpty($responseData['rows'][0]['testmodel_testmodeljoin']);
    $this->assertEquals([
      'testmodeljoin_id'        => null,
      'testmodeljoin_created'   => null,
      'testmodeljoin_modified'  => null,
      'testmodeljoin_text'      => null,
    ], $responseData['rows'][0]['testmodel_testmodeljoin']);

  }

  /**
   * [testCrudUseFormWithFields description]
   */
  public function testCrudUseFormWithFields(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->useForm('testmodel');
    $crudInstance->useForm('testmodel'); // check for cache
    $crudInstance->outputFormConfig = true;
    $form = $crudInstance->makeForm(null, false);

    $this->assertInstanceOf(\codename\core\ui\form::class, $form);

    $fields = $form->getFields();
    $this->assertCount(4, $fields);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[0]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[1]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[2]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[3]);
  }

  /**
   * [testCrudUseFormWithFieldsets description]
   */
  public function testCrudUseFormWithFieldsets(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->useForm('testmodel_fieldsets');
    $crudInstance->outputFormConfig = true;
    $form = $crudInstance->makeForm(null, false);

    $this->assertInstanceOf(\codename\core\ui\form::class, $form);

    $fieldsets = $form->getFieldsets();
    $this->assertCount(2, $fieldsets);
    $this->assertInstanceOf(\codename\core\ui\fieldset::class, $fieldsets[0]);
    $this->assertInstanceOf(\codename\core\ui\fieldset::class, $fieldsets[1]);

    $fields = $fieldsets[0]->getFields();
    $this->assertCount(2, $fields);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[0]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[1]);

    $fields = $fieldsets[1]->getFields();
    $this->assertCount(6, $fields);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[0]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[1]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[2]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[3]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[4]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[5]);
  }

  /**
   * [testCrudAddActionTopValid description]
   */
  public function testCrudAddActionTopValid(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $return = $crudInstance->addTopaction([
      'name'        => 'exampleTopName',
      'view'        => 'exampleTopView',
      'context'     => 'exampleTopContext',
      'icon'        => 'exampleTopIcon',
      'btnClass'    => 'exampleTopBtnClass',
    ]);

    $this->assertEmpty($result);
    $this->assertEquals([
      'name'        => 'exampleTopName',
      'view'        => 'exampleTopView',
      'context'     => 'exampleTopContext',
      'icon'        => 'exampleTopIcon',
      'btnClass'    => 'exampleTopBtnClass',
    ], $crudInstance->getConfig()->get('action>top>exampleTopName'));
  }

  /**
   * [testCrudAddActionBulkValid description]
   */
  public function testCrudAddActionBulkValid(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $return = $crudInstance->addBulkaction([
      'name'        => 'exampleBulkName',
      'view'        => 'exampleBulkView',
      'context'     => 'exampleBulkContext',
      'icon'        => 'exampleBulkIcon',
      'btnClass'    => 'exampleBulkBtnClass',
    ]);

    $this->assertEmpty($result);
    $this->assertEquals([
      'name'        => 'exampleBulkName',
      'view'        => 'exampleBulkView',
      'context'     => 'exampleBulkContext',
      'icon'        => 'exampleBulkIcon',
      'btnClass'    => 'exampleBulkBtnClass',
    ], $crudInstance->getConfig()->get('action>bulk>exampleBulkName'));
  }

  /**
   * [testCrudAddActionElementValid description]
   */
  public function testCrudAddActionElementValid(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $return = $crudInstance->addElementaction([
      'name'        => 'exampleElementName',
      'view'        => 'exampleElementView',
      'context'     => 'exampleElementContext',
      'icon'        => 'exampleElementIcon',
      'btnClass'    => 'exampleElementBtnClass',
    ]);

    $this->assertEmpty($result);
    $this->assertEquals([
      'name'        => 'exampleElementName',
      'view'        => 'exampleElementView',
      'context'     => 'exampleElementContext',
      'icon'        => 'exampleElementIcon',
      'btnClass'    => 'exampleElementBtnClass',
    ], $crudInstance->getConfig()->get('action>element>exampleElementName'));
  }

  /**
   * [testCrudAddActionTopInvalid description]
   */
  public function testCrudAddActionTopInvalid(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_ADDACTION_INVALIDACTIONOBJECT');

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $return = $crudInstance->addTopaction([]);
  }

  /**
   * [testCrudAddActionBulkInvalid description]
   */
  public function testCrudAddActionBulkInvalid(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_ADDACTION_INVALIDACTIONOBJECT');

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $return = $crudInstance->addBulkaction([]);
  }

  /**
   * [testCrudAddActionElementInvalid description]
   */
  public function testCrudAddActionElementInvalid(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_ADDACTION_INVALIDACTIONOBJECT');

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $return = $crudInstance->addElementaction([]);
  }

}
