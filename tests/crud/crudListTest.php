<?php

namespace codename\core\ui\tests\crud;

use codename\core\app;
use codename\core\auth;
use codename\core\credential;
use codename\core\model\schematic\sql;
use codename\core\test\base;
use codename\core\test\overrideableApp;
use codename\core\ui\crud;
use codename\core\ui\field;
use codename\core\ui\form;
use codename\core\ui\tests\crud\model\testmodel;
use codename\core\ui\tests\crud\model\testmodelcollection;
use codename\core\ui\tests\crud\model\testmodelforcejoin;
use codename\core\ui\tests\crud\model\testmodeljoin;
use DateTime;
use Exception;
use LogicException;
use ReflectionException;

class crudListTest extends base
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
     * [testCrudListConfig description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListConfig(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        $crudInstance->addTopaction([
          'name' => 'exampleTopName',
          'view' => 'exampleTopView',
          'context' => 'exampleTopContext',
          'icon' => 'exampleTopIcon',
          'btnClass' => 'exampleTopBtnClass',
        ]);

        $crudInstance->addBulkaction([
          'name' => 'exampleBulkName',
          'view' => 'exampleBulkView',
          'context' => 'exampleBulkContext',
          'icon' => 'exampleBulkIcon',
          'btnClass' => 'exampleBulkBtnClass',
          '_security' => [
            'group' => 'example',
          ],
        ]);

        $crudInstance->addElementaction([
          'name' => 'exampleElementName',
          'view' => 'exampleElementView',
          'context' => 'exampleElementContext',
          'icon' => 'exampleElementIcon',
          'btnClass' => 'exampleElementBtnClass',
          'condition' => '$condition = false;',
        ]);

        $crudInstance->addModifier('example', function ($row) {
            return 'example';
        });

        try {
            $crudInstance->listconfig();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'exampleTopName' => [
            'name' => 'exampleTopName',
            'view' => 'exampleTopView',
            'context' => 'exampleTopContext',
            'icon' => 'exampleTopIcon',
            'btnClass' => 'exampleTopBtnClass',
            'display' => 'BTN_EXAMPLETOPNAME',
          ],
        ], $responseData['topActions']);
        static::assertEmpty($responseData['bulkActions']);
        static::assertEmpty($responseData['elementActions']);

        static::assertEquals([
          'testmodel_text',
          'testmodel_testmodeljoin_id',
          'testmodel_id',
          'example',
        ], $responseData['visibleFields']);

        static::assertInstanceOf(form::class, $responseData['filterform']);
    }

    /**
     * [testCrudListConfigWithSeparateConfig description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListConfigWithSeparateConfig(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        $crudInstance->onCreateFormfield = function (array &$fielddata) {
            $fielddata['field_example'] = 'example';
        };
        $crudInstance->onFormfieldCreated = function (field $field) {
            if ($field->getConfig()->get('field_example') === 'example') {
                $field->setProperty('field_example', 'example2');
            }
        };

        $crudInstance->setConfig('crudtest_testmodel_crudlistconfig');

        $crudInstance->addTopaction([
          'name' => 'exampleTopName',
          'view' => 'exampleTopView',
          'context' => 'exampleTopContext',
          'icon' => 'exampleTopIcon',
          'btnClass' => 'exampleTopBtnClass',
        ]);

        $crudInstance->addBulkaction([
          'name' => 'exampleBulkName',
          'view' => 'exampleBulkView',
          'context' => 'exampleBulkContext',
          'icon' => 'exampleBulkIcon',
          'btnClass' => 'exampleBulkBtnClass',
          '_security' => [
            'group' => 'example',
          ],
        ]);

        $crudInstance->addElementaction([
          'name' => 'exampleElementName',
          'view' => 'exampleElementView',
          'context' => 'exampleElementContext',
          'icon' => 'exampleElementIcon',
          'btnClass' => 'exampleElementBtnClass',
          'condition' => '$condition = false;',
        ]);

        $customizedFields = $crudInstance->getConfig()->get('customized_fields');
        static::assertEquals([
          'testmodel_testmodeljoin_id',
        ], $customizedFields);
        $crudInstance->setCustomizedFields($customizedFields);

        $crudInstance->addModifier('example', function ($row) {
            return 'example';
        });

        $crudInstance->setColumnOrder([
          'testmodel_id',
          'testmodel_text',
        ]);

        try {
            $crudInstance->listconfig();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'exampleTopName' => [
            'name' => 'exampleTopName',
            'view' => 'exampleTopView',
            'context' => 'exampleTopContext',
            'icon' => 'exampleTopIcon',
            'btnClass' => 'exampleTopBtnClass',
            'display' => 'BTN_EXAMPLETOPNAME',
          ],
        ], $responseData['topActions']);
        static::assertEmpty($responseData['bulkActions']);
        static::assertEmpty($responseData['elementActions']);

        static::assertEquals([
          'testmodel_id',
          'testmodel_text',
          'testmodel_testmodeljoin_id',
          'example',
        ], $responseData['visibleFields']);

        static::assertInstanceOf(form::class, $responseData['filterform']);

        $fields = $responseData['filterform']->getFields();
        static::assertCount(3, $fields);

        // only by foreign fields
        static::assertEquals('example2', $fields[1]->getProperty('field_example'));
    }

    /**
     * [testCrudListConfigDisplaySelectedFields description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListConfigDisplaySelectedFields(): void
    {
        app::getRequest()->setData('display_selectedfields', ['testmodel_text']);

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        try {
            $crudInstance->listconfig();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'testmodel_text',
          'testmodel_id',
        ], $responseData['visibleFields']);
    }

    /**
     * [testCrudListConfigDisplaySelectedFieldsWithForceJoin description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListConfigDisplaySelectedFieldsWithForceJoin(): void
    {
        app::getRequest()->setData('display_selectedfields', ['testmodelforcejoin_text']);

        $model = $this->getModel('testmodelforcejoin');
        $crudInstance = new crud($model);

        try {
            $crudInstance->listconfig();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'testmodelforcejoin_text',
          'testmodelforcejoin_id',
        ], $responseData['visibleFields']);
    }

    /**
     * [testCrudListConfigImportAndExport description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListConfigImportAndExport(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->setConfig('crudtest_testmodel_crudlistconfig');

        try {
            $crudInstance->listconfig();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertTrue($responseData['enable_import']);
        static::assertTrue($responseData['enable_export']);
        static::assertEquals([
          'json',
        ], $responseData['export_types']);
    }

    /**
     * [testCrudListView description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListView(): void
    {
        // set demo data
        $model = $this->getModel('testmodel')->addModel($joinedModel = $this->getModel('testmodeljoin'));
        if (!($model instanceof sql)) {
            static::fail('setup fail');
        }
        $model->saveWithChildren([
          'testmodel_text' => 'moep',
          'testmodel_testmodeljoin' => [
            'testmodeljoin_text' => 'se',
          ],
        ]);

        $rootId = $model->lastInsertId();
        $joinedId = $joinedModel->lastInsertId();

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        $crudInstance->addModifier('example', function ($row) {
            return 'example';
        });

        $crudInstance->addResultsetModifier(function ($results) {
            foreach ($results as &$result) {
                $result['testmodel_text'] .= $result['testmodel_testmodeljoin']['testmodeljoin_text'];
            }
            return $results;
        });

        $crudInstance->addRowModifier(function ($row) {
            return [
              'example1' => 'omfg!',
              'example2' => true,
            ];
        });

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'testmodel_text',
          'testmodel_testmodeljoin_id',
          'testmodel_id',
          'example',
        ], $responseData['visibleFields']);

        static::assertInstanceOf(form::class, $responseData['filterform']);

        static::assertEquals([
          [
            'testmodel_text' => 'moepse',
            'testmodel_testmodeljoin_id_FORMATTED' => 'se',
            'testmodel_testmodeljoin_id' => $joinedId,
            'testmodel_id' => $rootId,
            'example' => 'example',
            '__modifier' => 'example1="omfg!" example2',
          ],
        ], $responseData['rows']);

        static::assertEquals('example1="omfg!" example2', $responseData['rows'][0]['__modifier']);

        static::assertEquals([
          'crud_pagination_seek_enabled' => false,
          'crud_pagination_count' => 1,
          'crud_pagination_page' => 1,
          'crud_pagination_pages' => 1.0,
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
     * [testCrudListViewSmall description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewSmall(): void
    {
        // set demo data
        $model = $this->getModel('testmodeljoin');
        $model->save([
          'testmodeljoin_text' => 'example',
        ]);

        $model = $this->getModel('testmodeljoin');
        $crudInstance = new crud($model);

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'testmodeljoin_text',
          'testmodeljoin_id',
        ], $responseData['visibleFields']);

        static::assertCount(1, $responseData['rows']);
        static::assertEquals('example', $responseData['rows'][0]['testmodeljoin_text']);
    }

    /**
     * [testCrudListViewSmallWithRowModifier description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewSmallWithRowModifier(): void
    {
        // set demo data
        $model = $this->getModel('testmodeljoin');
        $model->save([
          'testmodeljoin_text' => 'example',
        ]);

        $model = $this->getModel('testmodeljoin');
        $crudInstance = new crud($model);
        $crudInstance->addRowModifier(function ($row) {
            return [
              'example1' => 'omfg!',
              'example2' => true,
            ];
        });

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'testmodeljoin_text',
          'testmodeljoin_id',
        ], $responseData['visibleFields']);

        static::assertCount(1, $responseData['rows']);
        static::assertEquals('example', $responseData['rows'][0]['testmodeljoin_text']);
        static::assertEquals('example1="omfg!" example2', $responseData['rows'][0]['__modifier']);
    }

    /**
     * [testCrudListViewWithSetResultData description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewWithSetResultData(): void
    {
        $model = $this->getModel('testmodeljoin');
        $model->save([
          'testmodeljoin_text' => 'example',
        ]);

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model, null, 'crudtest_testmodel_field_is_array');
        $crudInstance->setProvideRawData(true);
        $crudInstance->setResultData([
          [
            'testmodel_text' => [
              'example' => 'example',
            ],
            'testmodel_testmodeljoin_id' => [1, 2, 3, 4, 5],
          ],
        ]);

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          ['testmodel_text', 'example'],
          'testmodel_testmodeljoin_id',
          'testmodel_id',
        ], $responseData['visibleFields']);

        static::assertEquals([
          [
            'testmodel_text' => [
              'example' => 'example',
            ],
            'testmodel_testmodeljoin_id' => [1, 2, 3, 4, 5],
            'testmodel_testmodeljoin_id_FORMATTED' => 'example',
            'testmodel_id' => null,
          ],
        ], $responseData['rows'], json_encode($responseData['rows']));
    }

    /**
     * [testCrudListViewWithSeparateConfig description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewWithSeparateConfig(): void
    {
        app::getRequest()->setData('crud_pagination_page', 10);
        app::getRequest()->setData('crud_pagination_limit', 10);

        // set demo data
        $model = $this->getModel('testmodel')->addModel($joinedModel = $this->getModel('testmodeljoin'));
        if (!($model instanceof sql)) {
            static::fail('setup fail');
        }
        $model->saveWithChildren([
          'testmodel_text' => 'moep',
          'testmodel_testmodeljoin' => [
            'testmodeljoin_text' => 'se',
          ],
        ]);

        $rootId = $model->lastInsertId();
        $joinedId = $joinedModel->lastInsertId();

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->setConfig('crudtest_testmodel_crudlistconfig');

        $crudInstance->addModifier('example', function ($row) {
            return 'example';
        });

        $crudInstance->setColumnOrder([
          'testmodel_id',
          'testmodel_text',
        ]);

        $crudInstance->addResultsetModifier(function ($results) {
            foreach ($results as &$result) {
                $result['testmodel_text'] .= $result['testmodel_testmodeljoin']['testmodeljoin_text'];
            }
            return $results;
        });

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'testmodel_id',
          'testmodel_text',
          'testmodel_testmodeljoin_id',
          'example',
        ], $responseData['visibleFields']);

        static::assertInstanceOf(form::class, $responseData['filterform']);

        $fields = $responseData['filterform']->getFields();
        static::assertCount(3, $fields);
        static::assertInstanceOf(field::class, $fields[0]);
        static::assertInstanceOf(field::class, $fields[1]);
        static::assertInstanceOf(field::class, $fields[2]);
        static::assertEquals('field_config_example_title', $fields[0]->getConfig()->get('field_title'));

        static::assertEquals([
          [
            'testmodel_id' => $rootId,
            'testmodel_text' => 'moepse',
            'testmodel_testmodeljoin_id_FORMATTED' => 'se',
            'testmodel_testmodeljoin_id' => $joinedId,
            'example' => 'example',
          ],
        ], $responseData['rows']);

        static::assertEquals([
          'crud_pagination_seek_enabled' => false,
          'crud_pagination_count' => 1,
          'crud_pagination_page' => 1,
          'crud_pagination_pages' => 1.0,
          'crud_pagination_limit' => 10,
        ], [
          'crud_pagination_seek_enabled' => $responseData['crud_pagination_seek_enabled'],
          'crud_pagination_count' => $responseData['crud_pagination_count'],
          'crud_pagination_page' => $responseData['crud_pagination_page'],
          'crud_pagination_pages' => $responseData['crud_pagination_pages'],
          'crud_pagination_limit' => $responseData['crud_pagination_limit'],
        ]);
    }

    /**
     * [testCrudListViewDisplaySelectedFields description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewDisplaySelectedFields(): void
    {
        app::getRequest()->setData('display_selectedfields', ['testmodel_text']);

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'testmodel_text',
          'testmodel_id',
        ], $responseData['visibleFields']);
    }

    /**
     * [testCrudListViewImportAndExport description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewImportAndExport(): void
    {
        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->setConfig('crudtest_testmodel_crudlistconfig');

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertTrue($responseData['enable_import']);
        static::assertTrue($responseData['enable_export']);
        static::assertEquals([
          'json',
        ], $responseData['export_types']);
    }

    /**
     * [testCrudListViewCrudEditable description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewCrudEditable(): void
    {
        app::getRequest()->setData('crud_editable', true);

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'testmodel_text',
          'testmodel_testmodeljoin_id',
          'testmodel_id',
        ], $responseData['visibleFields']);
    }

    /**
     * [testCrudListViewSeekStablePosition description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewSeekStablePosition(): void
    {
        // set demo data
        $model = $this->getModel('testmodel');
        if (!($model instanceof sql)) {
            static::fail('setup fail');
        }
        $model->saveWithChildren([
          'testmodel_text' => 'moepse1',
          'testmodel_flag' => 1,
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'moepse2',
          'testmodel_flag' => 2,
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'moepse3',
          'testmodel_flag' => 4,
        ]);

        app::getRequest()->setData('crud_pagination_first_id', 1);
        app::getRequest()->setData('crud_pagination_seek', 0);

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->setConfig('crudtest_testmodel_seek');

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertCount(2, $responseData['rows']);
        static::assertEquals('moepse1', $responseData['rows'][0]['testmodel_text']);
        static::assertEquals(1, $responseData['rows'][0]['testmodel_flag']);
        static::assertEquals('moepse2', $responseData['rows'][1]['testmodel_text']);
        static::assertEquals(2, $responseData['rows'][1]['testmodel_flag']);
    }

    /**
     * [testCrudListViewSeekMovingBackwards description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewSeekMovingBackwards(): void
    {
        // set demo data
        $model = $this->getModel('testmodel');
        if (!($model instanceof sql)) {
            static::fail('setup fail');
        }
        $model->saveWithChildren([
          'testmodel_text' => 'moepse1',
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'moepse2',
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'moepse3',
        ]);

        $model->addFilter('testmodel_text', 'moepse3');
        $res = $model->search()->getResult();

        app::getRequest()->setData('crud_pagination_first_id', $res[0]['testmodel_id']);
        app::getRequest()->setData('crud_pagination_seek', -1);

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->setConfig('crudtest_testmodel_seek');

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertCount(2, $responseData['rows']);
        static::assertEquals('moepse1', $responseData['rows'][0]['testmodel_text']);
        static::assertEquals('moepse2', $responseData['rows'][1]['testmodel_text']);
    }

    /**
     * [testCrudListViewSeekMovingForwards description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewSeekMovingForwards(): void
    {
        // set demo data
        $model = $this->getModel('testmodel');
        if (!($model instanceof sql)) {
            static::fail('setup fail');
        }
        $model->saveWithChildren([
          'testmodel_text' => 'moepse1',
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'moepse2',
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'moepse3',
        ]);

        $model->addFilter('testmodel_text', 'moepse2');
        $res = $model->search()->getResult();

        app::getRequest()->setData('crud_pagination_last_id', $res[0]['testmodel_id']);
        app::getRequest()->setData('crud_pagination_seek', 1);

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->setConfig('crudtest_testmodel_seek');

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertCount(1, $responseData['rows']);
        static::assertEquals('moepse3', $responseData['rows'][0]['testmodel_text']);
    }

    /**
     * [testCrudListViewPaginationWithFilter description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewPaginationWithFilter(): void
    {
        app::getRequest()->setData('crud_pagination_page_prev', 2);
        app::getRequest()->setData('crud_pagination_limit', 2);

        // set demo data
        $model = $this->getModel('testmodel');
        if (!($model instanceof sql)) {
            static::fail('setup fail');
        }
        $model->saveWithChildren([
          'testmodel_text' => 'example1',
          'testmodel_flag' => 1,
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'example2',
          'testmodel_flag' => 1,
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'example3',
          'testmodel_flag' => 1,
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'example4',
          'testmodel_flag' => 1,
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'example5',
          'testmodel_flag' => 1,
        ]);

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);

        app::getRequest()->setData($crudInstance::CRUD_FILTER_IDENTIFIER, [
          'testmodel_flag' => 1,
        ]);

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'testmodel_text',
          'testmodel_testmodeljoin_id',
          'testmodel_id',
        ], $responseData['visibleFields']);

        static::assertCount(2, $responseData['rows']);
        static::assertEquals('example3', $responseData['rows'][0]['testmodel_text']);
        static::assertEquals('example4', $responseData['rows'][1]['testmodel_text']);

        static::assertEquals([
          'crud_pagination_seek_enabled' => false,
          'crud_pagination_count' => 5,
          'crud_pagination_page' => 2,
          'crud_pagination_pages' => 3.0,
          'crud_pagination_limit' => 2,
        ], [
          'crud_pagination_seek_enabled' => $responseData['crud_pagination_seek_enabled'],
          'crud_pagination_count' => $responseData['crud_pagination_count'],
          'crud_pagination_page' => $responseData['crud_pagination_page'],
          'crud_pagination_pages' => $responseData['crud_pagination_pages'],
          'crud_pagination_limit' => $responseData['crud_pagination_limit'],
        ]);
    }

    /**
     * [testCrudListViewFilter description]
     * @throws ReflectionException
     * @throws \codename\core\exception
     */
    public function testCrudListViewFilter(): void
    {
        app::getRequest()->setData('crud_pagination_page_prev', 2);
        app::getRequest()->setData('crud_pagination_limit', 2);

        // set demo data
        $model = $this->getModel('testmodel');
        if (!($model instanceof sql)) {
            static::fail('setup fail');
        }
        $model->saveWithChildren([
          'testmodel_text' => 'example1',
          'testmodel_number_natural' => 1,
          'testmodel_flag' => 3,
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'example2',
          'testmodel_number_natural' => 1,
          'testmodel_flag' => 3,
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'example3',
          'testmodel_number_natural' => 1,
          'testmodel_flag' => 3,
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'example4',
          'testmodel_number_natural' => 1,
          'testmodel_flag' => 3,
        ]);
        $model->saveWithChildren([
          'testmodel_text' => 'example5',
          'testmodel_number_natural' => 1,
          'testmodel_flag' => 3,
        ]);

        $model = $this->getModel('testmodel');
        $crudInstance = new crud($model);
        $crudInstance->setConfig('crudtest_testmodel_filter');

        // added filters
        $now = new DateTime('now');
        app::getRequest()->setData($crudInstance::CRUD_FILTER_IDENTIFIER, [
          'provide_filter_example' => true,
          'testmodel_flag' => [1, 2],
          'testmodel_text' => [
            'example2',
            'example3',
            'example4',
            'example5',
          ],
          'testmodel_number_natural' => 1,
          'search' => 'example%',
          'testmodel_created' => [
            $now->format('Y-m-d 00:00:00'),
            $now->format('Y-m-d 23:59:59'),
          ],
        ]);

        $crudInstance->provideFilter('provide_filter_example', [
          'datatype' => 'text',
        ], function (crud $crudInstance, $filterValue) {
            $crudInstance->getMyModel()->addFilter('testmodel_text', 'example1', '!=');
        });

        try {
            $crudInstance->listview();
        } catch (Exception) {
            static::fail();
        }

        $responseData = overrideableApp::getResponse()->getData();

        static::assertEquals([
          'testmodel_text',
          'testmodel_testmodeljoin_id',
          'testmodel_id',
        ], $responseData['visibleFields']);

        static::assertCount(2, $responseData['rows']);
        static::assertEquals('example4', $responseData['rows'][0]['testmodel_text']);
        static::assertEquals('example5', $responseData['rows'][1]['testmodel_text']);
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

        // TODO: rework via overrideableApp
        $app::__injectClientInstance('auth', 'default', new dummyAuth());

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
                'driver' => 'dummy',
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

class dummyAuth extends auth
{
    /**
     * {@inheritDoc}
     */
    public function authenticate(credential $credential): array
    {
        throw new LogicException('Not implemented'); // TODO
    }

    /**
     * {@inheritDoc}
     */
    public function createCredential(array $parameters): credential
    {
        throw new LogicException('Not implemented'); // TODO
    }

    /**
     * {@inheritDoc}
     */
    public function makeHash(credential $credential): string
    {
        throw new LogicException('Not implemented'); // TODO
    }

    /**
     * {@inheritDoc}
     */
    public function isAuthenticated(): bool
    {
        return true; // ?
    }

    /**
     * {@inheritDoc}
     */
    public function memberOf(string $groupName): bool
    {
        if ($groupName == 'group_true') {
            return true;
        }
        return false;
    }
}
