<?php

namespace codename\core\ui\tests\crud;

use codename\core\app;
use codename\core\errorstack;
use codename\core\eventHandler;
use codename\core\test\base;
use codename\core\test\overrideableApp;
use codename\core\ui\crud;
use codename\core\ui\field;
use codename\core\ui\form;
use codename\core\ui\tests\crud\model\testmodel;
use codename\core\ui\tests\crud\model\testmodelcollection;
use codename\core\ui\tests\crud\model\testmodelforcejoin;
use codename\core\ui\tests\crud\model\testmodeljoin;
use Exception;
use ReflectionException;

class crudCreateTest extends base
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
     * [testCrudCreateForm description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudCreateForm(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        $crudInstance->eventCrudFormInit->addEventHandler(
            new eventHandler(function (form $form) {
                $form->fields = array_filter($form->fields, function (field $item) {
                    return $item->getConfig()->get('field_type') !== 'submit'; // only allow non-submits
                });
                return $form;
            })
        );

        $crudInstance->create();

        // renderer?
        static::assertEquals('frontend/form/compact/form', app::getResponse()->getData('form'));

        $form = $crudInstance->getForm();
        static::assertInstanceOf(form::class, $form);
        static::assertEquals('hidden', $form->getField('testmodel_id')->getProperty('field_type'));
        static::assertEquals('input', $form->getField('testmodel_text')->getProperty('field_type'));

        $fields = $form->getFields();
        static::assertCount(8, $fields);
    }

    /**
     * [testCrudCreateFormSendSuccess description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudCreateFormSendSuccess(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->create();

        // renderer?
        static::assertEquals('frontend/form/compact/form', app::getResponse()->getData('form'));

        $form = $crudInstance->getForm();

        static::assertInstanceOf(form::class, $form);

        $formSentField = null;
        foreach ($form->getFields() as $field) {
            // detect form sent field
            if (str_starts_with($field->getConfig()->get('field_name'), 'formSent')) {
                $formSentField = $field;
            }
        }

        // emulate a request 'submitting' the form
        app::getRequest()->setData($formSentField->getConfig()->get('field_name'), 1);
        app::getRequest()->setData('testmodel_text', 'abc');

        // create a new crud instance to simulate creation
        // NOTE: reset the model to avoid the EXCEPTION_MODEL_SCHEMATIC_SQL_CHILDREN_AMBIGUOUS_JOINS error
        $model = $this->getModel('testmodel');
        $saveCrudInstance = new crud($model);
        $saveCrudInstance->create();

        $res = $model->search()->getResult();
        static::assertCount(1, $res);
    }

    /**
     * [testCrudCreateFormSendSuccessWithEventCrudBeforeSave description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudCreateFormSendSuccessWithEventCrudBeforeSave(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->create();

        // renderer?
        static::assertEquals('frontend/form/compact/form', app::getResponse()->getData('form'));

        $form = $crudInstance->getForm();

        static::assertInstanceOf(form::class, $form);

        $formSentField = null;
        foreach ($form->getFields() as $field) {
            // detect form sent field
            if (str_starts_with($field->getConfig()->get('field_name'), 'formSent')) {
                $formSentField = $field;
            }
        }

        // emulate a request 'submitting' the form
        app::getRequest()->setData($formSentField->getConfig()->get('field_name'), 1);
        app::getRequest()->setData('testmodel_text', 'abc');

        // create a new crud instance to simulate creation
        // NOTE: reset the model to avoid the EXCEPTION_MODEL_SCHEMATIC_SQL_CHILDREN_AMBIGUOUS_JOINS error
        $model = $this->getModel('testmodel');
        $saveCrudInstance = new crud($model);

        $saveCrudInstance->eventCrudBeforeSave->addEventHandler(
            new eventHandler(function (array $data) {
            })
        );

        $saveCrudInstance->create();

        $res = $model->search()->getResult();
        static::assertCount(1, $res);

        static::assertEquals('abc', $res[0]['testmodel_text']);
    }

    /**
     * [testCrudCreateFormInvalid description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudCreateFormInvalid(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->create();

        // renderer?
        static::assertEquals('frontend/form/compact/form', app::getResponse()->getData('form'));

        $form = $crudInstance->getForm();

        static::assertInstanceOf(form::class, $form);

        $formSentField = null;
        foreach ($form->getFields() as $field) {
            // detect form sent field
            if (str_starts_with($field->getConfig()->get('field_name'), 'formSent')) {
                $formSentField = $field;
            }
        }

        // emulate a request 'submitting' the form
        app::getRequest()->setData($formSentField->getConfig()->get('field_name'), 1);
        app::getRequest()->setData('testmodel_text', 'abc');

        // create a new crud instance to simulate creation
        // NOTE: reset the model to avoid the EXCEPTION_MODEL_SCHEMATIC_SQL_CHILDREN_AMBIGUOUS_JOINS error
        $model = $this->getModel('testmodel');
        $saveCrudInstance = new crud($model);

        $saveCrudInstance->onCreateFormfield = function (array &$fielddata) {
            if ($fielddata['field_name'] === 'testmodel_text') {
                $fielddata['field_datatype'] = 'number_natural';
            }
        };

        try {
            $saveCrudInstance->create();
        } catch (Exception) {
            static::fail();
        }

        static::assertEquals('validation_error', app::getResponse()->getData('view'));
        $errors = app::getResponse()->getData('errors');

        static::assertCount(1, $errors);
        static::assertEquals([
          [
            '__IDENTIFIER' => 'testmodel_text',
            '__CODE' => 'VALIDATION.FIELD_INVALID',
            '__TYPE' => 'VALIDATION',
            '__DETAILS' => [
              [
                '__IDENTIFIER' => 'VALUE',
                '__CODE' => 'VALIDATION.VALUE_NOT_A_NUMBER',
                '__TYPE' => 'VALIDATION',
                '__DETAILS' => 'abc',
              ],
            ],
          ],
        ], $errors);
    }

    /**
     * [testCrudCreateModelInvalid description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudCreateModelInvalid(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->create();

        // renderer?
        static::assertEquals('frontend/form/compact/form', app::getResponse()->getData('form'));

        $form = $crudInstance->getForm();

        static::assertInstanceOf(form::class, $form);

        $formSentField = null;
        foreach ($form->getFields() as $field) {
            // detect form sent field
            if (str_starts_with($field->getConfig()->get('field_name'), 'formSent')) {
                $formSentField = $field;
            }
        }

        // emulate a request 'submitting' the form
        app::getRequest()->setData($formSentField->getConfig()->get('field_name'), 1);
        app::getRequest()->setData('testmodel_text', 'abc');

        // create a new crud instance to simulate creation
        // NOTE: reset the model to avoid the EXCEPTION_MODEL_SCHEMATIC_SQL_CHILDREN_AMBIGUOUS_JOINS error
        $model = $this->getModel('testmodel');
        $saveCrudInstance = new crud($model);

        $saveCrudInstance->eventCrudBeforeValidation->addEventHandler(
            new eventHandler(function (array $data) {
                $data['testmodel_testmodeljoin_id'] = 'abc';
                return $data;
            })
        );

        try {
            $saveCrudInstance->create();
        } catch (Exception) {
            static::fail();
        }

        static::assertEquals('save_error', app::getResponse()->getData('view'));
        $errors = app::getResponse()->getData('errors');

        static::assertCount(1, $errors);
        static::assertEquals([
          [
            '__IDENTIFIER' => 'testmodel_testmodeljoin_id',
            '__CODE' => 'VALIDATION.FIELD_INVALID',
            '__TYPE' => 'VALIDATION',
            '__DETAILS' => [
              [
                '__IDENTIFIER' => 'VALUE',
                '__CODE' => 'VALIDATION.VALUE_NOT_A_NUMBER',
                '__TYPE' => 'VALIDATION',
                '__DETAILS' => 'abc',
              ],
            ],
          ],
        ], $errors);
    }

    /**
     * [testCrudCreateValidationError description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudCreateValidationError(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->create();

        // renderer?
        static::assertEquals('frontend/form/compact/form', app::getResponse()->getData('form'));

        $form = $crudInstance->getForm();

        static::assertInstanceOf(form::class, $form);

        $formSentField = null;
        foreach ($form->getFields() as $field) {
            // detect form sent field
            if (str_starts_with($field->getConfig()->get('field_name'), 'formSent')) {
                $formSentField = $field;
            }
        }

        // emulate a request 'submitting' the form
        app::getRequest()->setData($formSentField->getConfig()->get('field_name'), 1);
        app::getRequest()->setData('testmodel_text', 'abc');

        // create a new crud instance to simulate creation
        // NOTE: reset the model to avoid the EXCEPTION_MODEL_SCHEMATIC_SQL_CHILDREN_AMBIGUOUS_JOINS error
        $model = $this->getModel('testmodel');
        $saveCrudInstance = new crud($model);

        $saveCrudInstance->eventCrudValidation->addEventHandler(
            new eventHandler(function ($data) {
                $errors = new errorstack('VALIDATION');
                $errors->addError('EXAMPLE', 'EXAMPLE', 'EXAMPLE');

                return $errors->getErrors();
            })
        );

        try {
            $saveCrudInstance->create();
        } catch (Exception) {
            static::fail();
        }

        static::assertEquals('save_error', app::getResponse()->getData('view'));
        $errors = app::getResponse()->getData('errors');

        static::assertCount(1, $errors);
        static::assertEquals([
          [
            '__IDENTIFIER' => 'EXAMPLE',
            '__CODE' => 'VALIDATION.EXAMPLE',
            '__TYPE' => 'VALIDATION',
            '__DETAILS' => 'EXAMPLE',
          ],
        ], $errors);
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     * @throws \codename\core\exception
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
     * {@inheritDoc}
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    protected function setUp(): void
    {
        overrideableApp::resetRequest();
        overrideableApp::resetResponse();
        $app = static::createApp();

        // Additional overrides to get a more complete app lifecycle
        // and allow static global app::getModel() to work correctly
        $app::__setApp('crudtest');
        $app::__setVendor('codename');
        $app::__setNamespace('\\codename\\core\\ui\\tests\\crud');
        $app::__setHomedir(__DIR__);

        $app::getAppstack();

        // avoid re-init
        if (static::$initialized) {
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
                'driver' => 'memory',
              ],
            ],
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
                'driver' => 'dummy',
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

        static::createModel(
            'crudtest',
            'testmodel',
            testmodel::$staticConfig,
            function ($schema, $model, $config) {
                return new testmodel([]);
            }
        );

        static::createModel(
            'crudtest',
            'testmodeljoin',
            testmodeljoin::$staticConfig,
            function ($schema, $model, $config) {
                return new testmodeljoin([]);
            }
        );

        static::createModel(
            'crudtest',
            'testmodelforcejoin',
            testmodelforcejoin::$staticConfig,
            function ($schema, $model, $config) {
                return new testmodelforcejoin([]);
            }
        );

        static::createModel(
            'crudtest',
            'testmodelcollection',
            testmodelcollection::$staticConfig,
            function ($schema, $model, $config) {
                return new testmodelcollection([]);
            }
        );

        static::architect('crudtest', 'codename', 'test');
    }
}
