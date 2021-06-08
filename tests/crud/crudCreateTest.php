<?php
namespace codename\core\ui\tests\crud;

use codename\core\test\base;
use codename\core\test\overrideableApp;

class crudCreateTest extends base {

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
   * [testCrudCreateForm description]
   */
  public function testCrudCreateForm(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);

    $crudInstance->eventCrudFormInit->addEventHandler(new \codename\core\eventHandler(function(\codename\core\ui\form $form) {
      $form->fields = array_filter($form->fields, function(\codename\core\ui\field $item) {
        return $item->getConfig()->get('field_type') !== 'submit'; // only allow non-submits
      });
      return $form;
    }));

    $crudInstance->create();

    // renderer?
    $this->assertEquals('frontend/form/compact/form', \codename\core\app::getResponse()->getData('form'));

    $form = $crudInstance->getForm();
    $this->assertInstanceOf(\codename\core\ui\form::class, $form);
    $this->assertEquals('hidden', $form->getField('testmodel_id')->getProperty('field_type'));
    $this->assertEquals('input', $form->getField('testmodel_text')->getProperty('field_type'));

    $fields = $form->getFields();
    $this->assertCount(8, $fields);
  }

  /**
   * [testCrudCreateFormSendSuccess description]
   */
  public function testCrudCreateFormSendSuccess(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->create();

    // renderer?
    $this->assertEquals('frontend/form/compact/form', \codename\core\app::getResponse()->getData('form'));

    $form = $crudInstance->getForm();

    $this->assertInstanceOf(\codename\core\ui\form::class, $form);

    $formSentField = null;
    foreach($form->getFields() as $field) {
      // detect form sent field
      if(strpos($field->getProperty('field_name'), 'formSent') === 0) {
        $formSentField = $field;
      }
    }

    // emulate a request 'submitting' the form
    \codename\core\app::getRequest()->setData($formSentField->getProperty('field_name'), 1);
    \codename\core\app::getRequest()->setData('testmodel_text', 'abc');

    // create a new crud instance to simulate creation
    // NOTE: reset the model to avoid the EXCEPTION_MODEL_SCHEMATIC_SQL_CHILDREN_AMBIGUOUS_JOINS error
    $model = $this->getModel('testmodel');
    $saveCrudInstance = new \codename\core\ui\crud($model);
    $saveCrudInstance->create();

    $res = $model->search()->getResult();
    $this->assertCount(1, $res);
  }

  /**
   * [testCrudCreateFormSendSuccessWithEventCrudBeforeSave description]
   */
  public function testCrudCreateFormSendSuccessWithEventCrudBeforeSave(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->create();

    // renderer?
    $this->assertEquals('frontend/form/compact/form', \codename\core\app::getResponse()->getData('form'));

    $form = $crudInstance->getForm();

    $this->assertInstanceOf(\codename\core\ui\form::class, $form);

    $formSentField = null;
    foreach($form->getFields() as $field) {
      // detect form sent field
      if(strpos($field->getProperty('field_name'), 'formSent') === 0) {
        $formSentField = $field;
      }
    }

    // emulate a request 'submitting' the form
    \codename\core\app::getRequest()->setData($formSentField->getProperty('field_name'), 1);
    \codename\core\app::getRequest()->setData('testmodel_text', 'abc');

    // create a new crud instance to simulate creation
    // NOTE: reset the model to avoid the EXCEPTION_MODEL_SCHEMATIC_SQL_CHILDREN_AMBIGUOUS_JOINS error
    $model = $this->getModel('testmodel');
    $saveCrudInstance = new \codename\core\ui\crud($model);

    $saveCrudInstance->eventCrudBeforeSave->addEventHandler(new \codename\core\eventHandler(function(array $data) {}));

    $saveCrudInstance->create();

    $res = $model->search()->getResult();
    $this->assertCount(1, $res);

    $this->assertEquals('abc', $res[0]['testmodel_text']);
  }

  /**
   * [testCrudCreateFormInvalid description]
   */
  public function testCrudCreateFormInvalid(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->create();

    // renderer?
    $this->assertEquals('frontend/form/compact/form', \codename\core\app::getResponse()->getData('form'));

    $form = $crudInstance->getForm();

    $this->assertInstanceOf(\codename\core\ui\form::class, $form);

    $formSentField = null;
    foreach($form->getFields() as $field) {
      // detect form sent field
      if(strpos($field->getProperty('field_name'), 'formSent') === 0) {
        $formSentField = $field;
      }
    }

    // emulate a request 'submitting' the form
    \codename\core\app::getRequest()->setData($formSentField->getProperty('field_name'), 1);
    \codename\core\app::getRequest()->setData('testmodel_text', 'abc');

    // create a new crud instance to simulate creation
    // NOTE: reset the model to avoid the EXCEPTION_MODEL_SCHEMATIC_SQL_CHILDREN_AMBIGUOUS_JOINS error
    $model = $this->getModel('testmodel');
    $saveCrudInstance = new \codename\core\ui\crud($model);

    $saveCrudInstance->onCreateFormfield = function(array &$fielddata) {
      if($fielddata['field_name'] === 'testmodel_text') {
        $fielddata['field_datatype'] = 'number_natural';
      }
    };

    $this->assertEmpty($saveCrudInstance->create());

    $this->assertEquals('validation_error', \codename\core\app::getResponse()->getData('view'));
    $errors = \codename\core\app::getResponse()->getData('errors');

    $this->assertCount(1, $errors);
    $this->assertEquals([
      [
        '__IDENTIFIER'  => 'testmodel_text',
        '__CODE'        => 'VALIDATION.FIELD_INVALID',
        '__TYPE'        => 'VALIDATION',
        '__DETAILS'     => [
          [
            '__IDENTIFIER'  => 'VALUE',
            '__CODE'        => 'VALIDATION.VALUE_NOT_A_NUMBER',
            '__TYPE'        => 'VALIDATION',
            '__DETAILS'     => 'abc',
          ],
        ],
      ]
    ], $errors);
  }

  /**
   * [testCrudCreateModelInvalid description]
   */
  public function testCrudCreateModelInvalid(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->create();

    // renderer?
    $this->assertEquals('frontend/form/compact/form', \codename\core\app::getResponse()->getData('form'));

    $form = $crudInstance->getForm();

    $this->assertInstanceOf(\codename\core\ui\form::class, $form);

    $formSentField = null;
    foreach($form->getFields() as $field) {
      // detect form sent field
      if(strpos($field->getProperty('field_name'), 'formSent') === 0) {
        $formSentField = $field;
      }
    }

    // emulate a request 'submitting' the form
    \codename\core\app::getRequest()->setData($formSentField->getProperty('field_name'), 1);
    \codename\core\app::getRequest()->setData('testmodel_text', 'abc');

    // create a new crud instance to simulate creation
    // NOTE: reset the model to avoid the EXCEPTION_MODEL_SCHEMATIC_SQL_CHILDREN_AMBIGUOUS_JOINS error
    $model = $this->getModel('testmodel');
    $saveCrudInstance = new \codename\core\ui\crud($model);

    $saveCrudInstance->eventCrudBeforeValidation->addEventHandler(new \codename\core\eventHandler(function(array $data) {
      $data['testmodel_testmodeljoin_id'] = 'abc';
      return $data;
    }));

    $this->assertEmpty($saveCrudInstance->create());

    $this->assertEquals('save_error', \codename\core\app::getResponse()->getData('view'));
    $errors = \codename\core\app::getResponse()->getData('errors');

    $this->assertCount(1, $errors);
    $this->assertEquals([
      [
        '__IDENTIFIER'  => 'testmodel_testmodeljoin_id',
        '__CODE'        => 'VALIDATION.FIELD_INVALID',
        '__TYPE'        => 'VALIDATION',
        '__DETAILS'     => [
          [
            '__IDENTIFIER'  => 'VALUE',
            '__CODE'        => 'VALIDATION.VALUE_NOT_A_NUMBER',
            '__TYPE'        => 'VALIDATION',
            '__DETAILS'     => 'abc',
          ],
        ],
      ]
    ], $errors);
  }

  /**
   * [testCrudCreateValidationError description]
   */
  public function testCrudCreateValidationError(): void {
    $model = $this->getModel('testmodel');
    $crudInstance = new \codename\core\ui\crud($model);
    $crudInstance->create();

    // renderer?
    $this->assertEquals('frontend/form/compact/form', \codename\core\app::getResponse()->getData('form'));

    $form = $crudInstance->getForm();

    $this->assertInstanceOf(\codename\core\ui\form::class, $form);

    $formSentField = null;
    foreach($form->getFields() as $field) {
      // detect form sent field
      if(strpos($field->getProperty('field_name'), 'formSent') === 0) {
        $formSentField = $field;
      }
    }

    // emulate a request 'submitting' the form
    \codename\core\app::getRequest()->setData($formSentField->getProperty('field_name'), 1);
    \codename\core\app::getRequest()->setData('testmodel_text', 'abc');

    // create a new crud instance to simulate creation
    // NOTE: reset the model to avoid the EXCEPTION_MODEL_SCHEMATIC_SQL_CHILDREN_AMBIGUOUS_JOINS error
    $model = $this->getModel('testmodel');
    $saveCrudInstance = new \codename\core\ui\crud($model);

    $saveCrudInstance->eventCrudValidation->addEventHandler(new \codename\core\eventHandler(function($data) {
      $errors = new \codename\core\errorstack('VALIDATION');
      $errors->addError('EXAMPLE', 'EXAMPLE', 'EXAMPLE');

      return $errors->getErrors();
    }));

    $this->assertEmpty($saveCrudInstance->create());

    $this->assertEquals('save_error', \codename\core\app::getResponse()->getData('view'));
    $errors = \codename\core\app::getResponse()->getData('errors');

    $this->assertCount(1, $errors);
    $this->assertEquals([
      [
        '__IDENTIFIER'  => 'EXAMPLE',
        '__CODE'        => 'VALIDATION.EXAMPLE',
        '__TYPE'        => 'VALIDATION',
        '__DETAILS'     => 'EXAMPLE',
      ]
    ], $errors);
  }

}
