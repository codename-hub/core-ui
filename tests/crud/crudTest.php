<?php

namespace codename\core\ui\tests\crud;

use codename\core\app;
use codename\core\exception;
use codename\core\model\schematic\sql;
use codename\core\NoticeException;
use codename\core\test\base;
use codename\core\test\overrideableApp;
use codename\core\ui\crud;
use codename\core\ui\field;
use codename\core\ui\fieldset;
use codename\core\ui\form;
use codename\core\ui\tests\crud\model\testmodel;
use codename\core\ui\tests\crud\model\testmodelcollection;
use codename\core\ui\tests\crud\model\testmodelcollectionforeign;
use codename\core\ui\tests\crud\model\testmodelforcejoin;
use codename\core\ui\tests\crud\model\testmodeljoin;
use codename\core\ui\tests\crud\model\testmodelwrongflag;
use codename\core\ui\tests\crud\model\testmodelwrongforeign;
use codename\core\ui\tests\crud\model\testmodelwrongforeignfilter;
use codename\core\ui\tests\crud\model\testmodelwrongforeignorder;
use ReflectionException;

class crudTest extends base
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
     * Tests basic crud init
     * @coversNothing
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudInit(): void
    {
        $this->expectNotToPerformAssertions();
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        // just test, if it doesn't crash
        $crudInstance->create();
    }

    /**
     * [testCrudInitWrongChildrenConfig description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudInitWrongChildrenConfig(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_CRUD_CHILDREN_CONFIG_MODEL_CONFIG_CHILDREN_IS_NULL');

        $model = $this->getModel('testmodel');
        new crud($model, null, 'crudtest_testmodel_wrong_children');
    }

    /**
     * [testCrudStats description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudStats(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        app::getRequest()->setData($crudInstance::CRUD_FILTER_IDENTIFIER, false);

        try {
            $crudInstance->stats();
        } catch (exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();
        static::assertEquals([
          'crud_pagination_seek_enabled' => false,
          'crud_pagination_count' => 0,
          'crud_pagination_page' => 1,
          'crud_pagination_pages' => 1,
          'crud_pagination_limit' => 5,
        ], [
          'crud_pagination_seek_enabled' => $responseData['crud_pagination_seek_enabled'],
          'crud_pagination_count' => $responseData['crud_pagination_count'],
          'crud_pagination_page' => $responseData['crud_pagination_page'],
          'crud_pagination_pages' => $responseData['crud_pagination_pages'],
          'crud_pagination_limit' => $responseData['crud_pagination_limit'],
        ]);
    }

    /**
     * [testCrudDisplaytypeStatic description]
     */
    public function testCrudDisplaytypeStatic(): void
    {
        $types = [
          'structure_address' => 'structure_address',
          'structure_text_telephone' => 'structure_text_telephone',
          'structure' => 'structure',
          'boolean' => 'yesno',
          'text_date' => 'date',
          'text_date_birthdate' => 'date',
          'text_timestamp' => 'timestamp',
          'text_datetime_relative' => 'relativetime',
          'input' => 'input',
          'example' => 'input',
        ];
        foreach ($types as $daType => $diType) {
            $result = crud::getDisplaytypeStatic($daType);
            static::assertEquals($diType, $result);
        }
    }

    /**
     * [testCrudGetData description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudGetDataNull(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        $result = $crudInstance->getData('example');
        static::assertNull($result);
    }

    /**
     * [testCrudSetRequestDataAndNormalizationData description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudSetRequestDataAndNormalizationData(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model, [
          'testmodel_text' => 'moep',
          'testmodel_testmodeljoin' => [
            'testmodeljoin_text' => 'se',
          ],
          'wrong_field' => 'hello',
        ]);
        $crudInstance->useFormNormalizationData();

        $result = $crudInstance->getData();
        static::assertEquals([
          'testmodel_text' => 'moep',
          'testmodel_testmodeljoin' => [
            'testmodeljoin_text' => 'se',
          ],
        ], $result);
    }

    /**
     * [testCrudUseDataAndGetData description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudUseDataAndGetData(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->useData([
          'testmodel_text' => 'moep',
          'testmodel_testmodeljoin' => [
            'testmodeljoin_text' => 'se',
          ],
        ]);
        $result = $crudInstance->getData();
        static::assertEquals([
          'testmodel_text' => 'moep',
          'testmodel_testmodeljoin' => [
            'testmodeljoin_text' => 'se',
          ],
        ], $result);
    }

    /**
     * [testCrudImport description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudImport(): void
    {
        $data = [
          ['testmodel_id' => 1, 'testmodel_text' => 'example1'],
          ['testmodel_id' => 2, 'testmodel_text' => 'example2'],
          ['testmodel_id' => 3, 'testmodel_text' => 'example3'],
        ];

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        try {
            $crudInstance->import($data);
        } catch (exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        $dataClean = [];
        foreach ($data as $v) {
            unset($v['testmodel_id']);
            $dataClean[] = $v;
        }

        static::assertCount(3, $responseData['import_data']);
        static::assertEquals($dataClean, $responseData['import_data']);

        $res = $model->search()->getResult();
        static::assertCount(3, $res);
    }

    /**
     * [testCrudImportInvalid description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudImportInvalid(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('CRUD_IMPORT_INVALID_DATASET');

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->import([
          ['testmodel_testmodeljoin_id' => 'example'],
        ]);
    }

    /**
     * [testCrudExport description]
     * @throws ReflectionException
     * @throws NoticeException
     * @throws exception
     */
    public function testCrudExport(): void
    {
        // set demo data
        $model = $this->getModel('testmodel')->addModel($this->getModel('testmodeljoin'));
        if (!($model instanceof sql)) {
            static::fail('setup fail');
        }
        $model->saveWithChildren([
          'testmodel_text' => 'example',
        ]);

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        try {
            $crudInstance->export(true);
        } catch (exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertCount(1, $responseData['rows']);
        static::assertNotEmpty($responseData['rows'][0]['testmodel_id']);
        static::assertNotEmpty($responseData['rows'][0]['testmodel_created']);
        static::assertEmpty($responseData['rows'][0]['testmodel_modified']);
        static::assertEmpty($responseData['rows'][0]['testmodel_testmodeljoin_id']);
        static::assertEquals('example', $responseData['rows'][0]['testmodel_text']);
        static::assertEmpty($responseData['rows'][0]['testmodel_unique_single']);
        static::assertEmpty($responseData['rows'][0]['testmodel_unique_multi1']);
        static::assertEmpty($responseData['rows'][0]['testmodel_unique_multi2']);
        static::assertNotEmpty($responseData['rows'][0]['testmodel_testmodeljoin']);
        static::assertEquals([
          'testmodeljoin_id' => null,
          'testmodeljoin_created' => null,
          'testmodeljoin_modified' => null,
          'testmodeljoin_text' => null,
        ], $responseData['rows'][0]['testmodel_testmodeljoin']);
    }

    /**
     * [testCrudMakeFormWithWrongField description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFieldForeignFieldNotFound(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_MAKEFIELD_FIELDNOTFOUNDINMODEL');

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model, null, 'crudtest_testmodel_field_not_found');
        $crudInstance->makeFieldForeign($model, 'example');
    }

    /**
     * [testCrudMakeFieldFieldNotFound description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFieldFieldNotFound(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_MAKEFIELD_FIELDNOTFOUNDINMODEL');

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model, null, 'crudtest_testmodel_field_not_found');
        $crudInstance->makeField('example');
    }

    /**
     * [testCrudMakeFieldInvalidReferenceObject description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFieldForeignInvalidReferenceObject(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_MAKEFIELD_INVALIDREFERENCEOBJECT');

        $model = $this->getModel('testmodelwrongforeign');
        $crudInstance = new crud($model, null, 'crudtest_testmodel_field_not_found');
        $crudInstance->makeFieldForeign($model, 'testmodelwrongforeign_testmodeljoin_id');
    }

    /**
     * [testCrudMakeFieldInvalidOrderObject description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFieldForeignInvalidOrderObject(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_MAKEFIELD_INVALIDORDEROBJECT');

        $model = $this->getModel('testmodelwrongforeignorder');
        $crudInstance = new crud($model, null, 'crudtest_testmodel_field_not_found');
        $crudInstance->makeFieldForeign($model, 'testmodelwrongforeignorder_testmodeljoin_id');
    }

    /**
     * [testCrudMakeFieldInvalidFilterObject description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFieldForeignInvalidFilterObject(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_MAKEFIELD_INVALIDFILTEROBJECT');

        $model = $this->getModel('testmodelwrongforeignfilter');
        $crudInstance = new crud($model, null, 'crudtest_testmodel_field_not_found');
        $crudInstance->makeFieldForeign($model, 'testmodelwrongforeignfilter_testmodeljoin_id');
    }

    /**
     * [testCrudMakeFieldForeign description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFieldForeignForeign(): void
    {
        $model = $this->getModel('testmodelcollectionforeign');
        $crudInstance = new crud($model);
        $field = $crudInstance->makeFieldForeign($model, 'testmodelcollectionforeign_testmodel_id');

        static::assertInstanceOf(field::class, $field);
    }

    /**
     * [testCrudMakeFieldInvalidReferenceObject description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFieldInvalidReferenceObject(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_MAKEFIELD_INVALIDREFERENCEOBJECT');

        $model = $this->getModel('testmodelwrongforeign');
        $crudInstance = new crud($model, null, 'crudtest_testmodel_field_not_found');
        $crudInstance->makeField('testmodelwrongforeign_testmodeljoin_id');
    }

    /**
     * [testCrudMakeFieldInvalidOrderObject description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFieldInvalidOrderObject(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_MAKEFIELD_INVALIDORDEROBJECT');

        $model = $this->getModel('testmodelwrongforeignorder');
        $crudInstance = new crud($model, null, 'crudtest_testmodel_field_not_found');
        $crudInstance->makeField('testmodelwrongforeignorder_testmodeljoin_id');
    }

    /**
     * [testCrudMakeFieldInvalidFilterObject description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFieldInvalidFilterObject(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_MAKEFIELD_INVALIDFILTEROBJECT');

        $model = $this->getModel('testmodelwrongforeignfilter');
        $crudInstance = new crud($model, null, 'crudtest_testmodel_field_not_found');
        $crudInstance->makeField('testmodelwrongforeignfilter_testmodeljoin_id');
    }

    /**
     * [testCrudMakeFieldForeign description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFieldForeign(): void
    {
        $model = $this->getModel('testmodelcollectionforeign');
        $crudInstance = new crud($model);
        $field = $crudInstance->makeField('testmodelcollectionforeign_testmodel_id');

        static::assertInstanceOf(field::class, $field);
    }

    /**
     * [testCrudMakeFormWithWrongField description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFormWithWrongField(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_MAKEFORM_FIELDNOTFOUNDINMODEL');

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model, null, 'crudtest_testmodel_field_not_found');
        $crudInstance->makeForm(null, false);
    }

    /**
     * [testCrudMakeFormWithWrongField description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudMakeFormWithWrongFlag(): void
    {
        $model = $this->getModel('testmodelwrongflag');
        $crudInstance = new crud($model);
        $form = $crudInstance->makeForm(null, false);

        static::assertInstanceOf(form::class, $form);

        $fields = $form->getFields();
        static::assertCount(2, $fields);
    }

    /**
     * [testCrudUseFormWithFields description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudUseFormWithFields(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->setConfigCache(true);
        $crudInstance->useForm('testmodel');
        $crudInstance->useForm('testmodel'); // check for cache
        $crudInstance->outputFormConfig = true;
        $crudInstance->onFormfieldCreated = function (field $field) {
            $field->setProperty('example', 'example');
        };
        $form = $crudInstance->makeForm(null, false);

        static::assertInstanceOf(form::class, $form);

        $fields = $form->getFields();
        static::assertCount(4, $fields);
        static::assertInstanceOf(field::class, $fields[0]);
        static::assertInstanceOf(field::class, $fields[1]);
        static::assertInstanceOf(field::class, $fields[2]);
        static::assertInstanceOf(field::class, $fields[3]);

        static::assertEquals('example', $fields[0]->getProperty('example'));
        static::assertEquals('example', $fields[1]->getProperty('example'));
        static::assertEquals('example', $fields[2]->getProperty('example'));
        static::assertEquals('example', $fields[3]->getProperty('example'));
    }

    /**
     * [testCrudUseFormWithFieldsets description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudUseFormWithFieldsets(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->useForm('testmodel_fieldsets');
        $crudInstance->outputFormConfig = true;
        $form = $crudInstance->makeForm(null, false);

        static::assertInstanceOf(form::class, $form);

        $fieldsets = $form->getFieldsets();
        static::assertCount(2, $fieldsets);
        static::assertInstanceOf(fieldset::class, $fieldsets[0]);
        static::assertInstanceOf(fieldset::class, $fieldsets[1]);

        $fields = $fieldsets[0]->getFields();
        static::assertCount(2, $fields);
        static::assertInstanceOf(field::class, $fields[0]);
        static::assertInstanceOf(field::class, $fields[1]);

        $fields = $fieldsets[1]->getFields();
        // CHANGED 2021-10-27: flag fields in fieldsets have not been handled correctly (legacy type)
        static::assertCount(2, $fields);
        static::assertInstanceOf(field::class, $fields[0]);
        static::assertInstanceOf(field::class, $fields[1]);
    }

    /**
     * [testCrudAddActionTopValid description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudAddActionTopValid(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        try {
            $crudInstance->addTopaction([
              'name' => 'exampleTopName',
              'view' => 'exampleTopView',
              'context' => 'exampleTopContext',
              'icon' => 'exampleTopIcon',
              'btnClass' => 'exampleTopBtnClass',
            ]);
        } catch (exception) {
            static::fail();
        }

        static::assertEquals([
          'name' => 'exampleTopName',
          'view' => 'exampleTopView',
          'context' => 'exampleTopContext',
          'icon' => 'exampleTopIcon',
          'btnClass' => 'exampleTopBtnClass',
        ], $crudInstance->getConfig()->get('action>top>exampleTopName'));
    }

    /**
     * [testCrudAddActionBulkValid description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudAddActionBulkValid(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        try {
            $crudInstance->addBulkaction([
              'name' => 'exampleBulkName',
              'view' => 'exampleBulkView',
              'context' => 'exampleBulkContext',
              'icon' => 'exampleBulkIcon',
              'btnClass' => 'exampleBulkBtnClass',
            ]);
        } catch (exception) {
            static::fail();
        }

        static::assertEquals([
          'name' => 'exampleBulkName',
          'view' => 'exampleBulkView',
          'context' => 'exampleBulkContext',
          'icon' => 'exampleBulkIcon',
          'btnClass' => 'exampleBulkBtnClass',
        ], $crudInstance->getConfig()->get('action>bulk>exampleBulkName'));
    }

    /**
     * [testCrudAddActionElementValid description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudAddActionElementValid(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        try {
            $crudInstance->addElementaction([
              'name' => 'exampleElementName',
              'view' => 'exampleElementView',
              'context' => 'exampleElementContext',
              'icon' => 'exampleElementIcon',
              'btnClass' => 'exampleElementBtnClass',
            ]);
        } catch (exception) {
            static::fail();
        }

        static::assertEquals([
          'name' => 'exampleElementName',
          'view' => 'exampleElementView',
          'context' => 'exampleElementContext',
          'icon' => 'exampleElementIcon',
          'btnClass' => 'exampleElementBtnClass',
        ], $crudInstance->getConfig()->get('action>element>exampleElementName'));
    }

    /**
     * [testCrudAddActionTopInvalid description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudAddActionTopInvalid(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_ADDACTION_INVALIDACTIONOBJECT');

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->addTopaction([]);
    }

    /**
     * [testCrudAddActionBulkInvalid description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudAddActionBulkInvalid(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_ADDACTION_INVALIDACTIONOBJECT');

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->addBulkaction([]);
    }

    /**
     * [testCrudAddActionElementInvalid description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testCrudAddActionElementInvalid(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_ADDACTION_INVALIDACTIONOBJECT');

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->addElementaction([]);
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

        $this->getModel('testmodelwrongflag')
          ->addFilter('testmodelwrongflag_id', 0, '>')
          ->delete();

        $this->getModel('testmodelwrongforeign')
          ->addFilter('testmodelwrongforeign_id', 0, '>')
          ->delete();

        $this->getModel('testmodelwrongforeignorder')
          ->addFilter('testmodelwrongforeignorder_id', 0, '>')
          ->delete();

        $this->getModel('testmodelwrongforeignfilter')
          ->addFilter('testmodelwrongforeignfilter_id', 0, '>')
          ->delete();

        $this->getModel('testmodelcollectionforeign')
          ->addFilter('testmodelcollectionforeign_id', 0, '>')
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

        static::createModel(
            'crudtest',
            'testmodelwrongflag',
            testmodelwrongflag::$staticConfig,
            function ($schema, $model, $config) {
                return new testmodelwrongflag([]);
            }
        );

        static::createModel(
            'crudtest',
            'testmodelwrongforeign',
            testmodelwrongforeign::$staticConfig,
            function ($schema, $model, $config) {
                return new testmodelwrongforeign([]);
            }
        );

        static::createModel(
            'crudtest',
            'testmodelwrongforeignorder',
            testmodelwrongforeignorder::$staticConfig,
            function ($schema, $model, $config) {
                return new testmodelwrongforeignorder([]);
            }
        );

        static::createModel(
            'crudtest',
            'testmodelwrongforeignfilter',
            testmodelwrongforeignfilter::$staticConfig,
            function ($schema, $model, $config) {
                return new testmodelwrongforeignfilter([]);
            }
        );

        static::createModel(
            'crudtest',
            'testmodelcollectionforeign',
            testmodelcollectionforeign::$staticConfig,
            function ($schema, $model, $config) {
                return new testmodelcollectionforeign([]);
            }
        );

        static::architect('crudtest', 'codename', 'test');
    }
}
