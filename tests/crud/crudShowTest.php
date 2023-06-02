<?php

namespace codename\core\ui\tests\crud;

use codename\core\app;
use codename\core\eventHandler;
use codename\core\exception;
use codename\core\test\base;
use codename\core\test\overrideableApp;
use codename\core\ui\crud;
use codename\core\ui\field;
use codename\core\ui\form;
use codename\core\ui\tests\crud\model\testmodel;
use codename\core\ui\tests\crud\model\testmodelcollection;
use codename\core\ui\tests\crud\model\testmodelforcejoin;
use codename\core\ui\tests\crud\model\testmodeljoin;
use ReflectionException;

class crudShowTest extends base
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
     * [testCrudShowForm description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudShowForm(): void
    {
        $model = $this->getModel('testmodel');

        // insert an entry and pass on PKEY for further 'editing'
        $model->save([
          'testmodel_text' => 'XYZ',
        ]);
        $id = $model->lastInsertId();

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

        $crudInstance->show($id);

        // renderer?
        static::assertEquals('frontend/form/compact/form', app::getResponse()->getData('form'));

        $form = $crudInstance->getForm();
        static::assertInstanceOf(form::class, $form);

        static::assertEquals('exampleTag', $form->config['form_tag']);

        $fields = $form->getFields();
        static::assertCount(8, $fields);

        foreach ($fields as $field) {
            if (
                $field->getConfig()->get('field_name') == $model->getPrimaryKey() ||
                $field->getConfig()->get('field_type') == 'hidden'
            ) {
                continue;
            }
            static::assertTrue($field->getConfig()->get('field_readonly'), print_r($field->getConfig()->get(), true));
        }
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     * @throws exception
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
     * @throws exception
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
