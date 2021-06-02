<?php
namespace codename\core\ui\tests\crud;

use codename\core\test\base;
use codename\core\test\overrideableApp;

class crudShowTest extends base {

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
   * [testCrudShowForm description]
   */
  public function testCrudShowForm(): void {
    $model = $this->getModel('testmodel');

    // insert an entry and pass on PKEY for further 'editing'
    $model->save([
      'testmodel_text' => 'XYZ'
    ]);
    $id = $model->lastInsertId();

    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);

    $crudInstance->eventCrudFormInit->addEventHandler(new \codename\core\eventHandler(function(\codename\core\ui\form $form) {
      $form->fields = array_filter($form->fields, function(\codename\core\ui\field $item) {
        return $item->getConfig()->get('field_type') !== 'submit'; // only allow non-submits
      });
      return $form;
    }));

    $crudInstance->show($id);

    // renderer?
    $this->assertEquals('frontend/form/compact/form', \codename\core\app::getResponse()->getData('form'));

    $form = $crudInstance->getForm();
    $this->assertInstanceOf(\codename\core\ui\form::class, $form);

    $this->assertEquals('exampleTag', $form->config['form_tag']);

    $fields = $form->getFields();
    $this->assertCount(4, $fields);

    foreach($fields as $field) {
      if(
        $field->getConfig()->get('field_name') == $model->getPrimarykey() ||
        $field->getConfig()->get('field_type') == 'hidden'
      ) {
        continue;
      }
      $this->assertTrue($field->getConfig()->get('field_readonly'), print_r($field->getConfig()->get(), true));
    }
  }

}
