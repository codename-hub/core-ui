<?php

namespace codename\core\ui\tests;

use codename\core\exception;
use codename\core\test\base;
use codename\core\test\overrideableApp;
use codename\core\ui\field;
use codename\core\ui\fieldset;
use codename\core\ui\form;
use ReflectionException;

class formTest extends base
{
    /**
     * [testInvalidConstruct description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testInvalidConstruct(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID');

        new form([
          'form_id' => 'exampleform',
          'form_action' => 'post',
        ]);
    }

    /**
     * [testEmptyForm description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testEmptyForm(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_OUTPUT_FORMISEMPTY');

        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'post',
          'form_method' => '',
        ]);

        $form->output(true);
    }

    /**
     * [testGeneric description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testGeneric(): void
    {
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'post',
          'form_method' => '',
        ]);

        static::assertEquals([
          'form_id' => 'exampleform',
          'form_action' => 'post',
          'form_method' => '',
        ], $form->config);
        static::assertCount(0, $form->getFields());
        static::assertCount(0, $form->getFieldsets());
        static::assertEmpty($form->getErrorstack()->getErrors());

        static::assertEmpty($form->getTemplateEngine());

        $templateEngine = overrideableApp::getTemplateEngine();
        $form->setTemplateEngine($templateEngine);
        static::assertNotEmpty($form->getTemplateEngine());
    }

    /**
     * [testInvalidFormWithFields description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testInvalidFormWithFields(): void
    {
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);

        $form->addField(
            new field([
              'field_name' => 'invalidexample',
              'field_type' => 'text',
              'field_value' => null,
              'field_required' => true,
            ])
        );
        $form->addField(
            new field([
              'field_name' => 'noninputexample',
              'field_type' => 'text',
              'field_value' => null,
              'field_noninput' => true,
            ])
        );
        $form->addField(
            new field([
              'field_name' => 'invalidexample2',
              'field_type' => 'text',
              'field_value' => 'invalidexample2',
            ]),
            0
        );

        $fields = $form->getFields();
        static::assertCount(3, $fields ?? []);
        static::assertInstanceOf(field::class, $fields[0]);
        static::assertInstanceOf(field::class, $fields[1]);
        static::assertInstanceOf(field::class, $fields[2]);

        static::assertEquals([
          'field_name' => 'invalidexample2',
          'field_type' => 'text',
          'field_id' => 'invalidexample2',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => false,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'invalidexample2',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $fields[0]->getConfig()->get());

        $form->setId('invalidexampleform');
        $form->setAction('post');

        static::assertEquals([
          'form_id' => 'core_form_invalidexampleform',
          'form_action' => 'post',
          'form_method' => '',
        ], $form->config);
        static::assertCount(3, $form->getFields());
        static::assertCount(0, $form->getFieldsets());
        static::assertEmpty($form->getErrorstack()->getErrors());

        static::assertFalse($form->isSent());

        $result = $form->output(true);
        static::assertNotEmpty($result);

        overrideableApp::getRequest()->setData('formSentcore_form_invalidexampleform', true);

        static::assertTrue($form->isSent());

        static::assertFalse($form->isValid());

        $errors = $form->getErrorstack()->getErrors();

        static::assertCount(1, $errors);
        static::assertEquals([
          [
            '__IDENTIFIER' => 'invalidexample',
            '__CODE' => 'VALIDATION.FIELD_NOT_SET',
            '__TYPE' => 'VALIDATION',
            '__DETAILS' => null,
          ],
        ], $errors);
    }

    /**
     * [testValidFormWithFields description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testValidFormWithFields(): void
    {
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);

        $form->addField(
            new field([
              'field_name' => 'validfieldexample',
              'field_type' => 'text',
              'field_value' => 'validfieldexample',
              'field_required' => true,
            ])
        );
        $form->addField(
            new field([
              'field_name' => 'validfieldexample2',
              'field_type' => 'text',
              'field_value' => 'validfieldexample2',
            ]),
            0
        );

        $fields = $form->getFields();
        static::assertCount(2, $fields ?? []);
        static::assertInstanceOf(field::class, $fields[0]);
        static::assertInstanceOf(field::class, $fields[1]);

        static::assertEquals([
          'field_name' => 'validfieldexample2',
          'field_type' => 'text',
          'field_id' => 'validfieldexample2',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => false,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'validfieldexample2',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $fields[0]->getConfig()->get());

        $form->setId('validfieldexampleform');
        $form->setAction('post');

        static::assertEquals([
          'form_id' => 'core_form_validfieldexampleform',
          'form_action' => 'post',
          'form_method' => '',
        ], $form->config);
        static::assertCount(2, $form->getFields());
        static::assertCount(0, $form->getFieldsets());
        static::assertEmpty($form->getErrorstack()->getErrors());

        static::assertFalse($form->isSent());

        overrideableApp::getRequest()->setData('formSentcore_form_validfieldexampleform', true);
        overrideableApp::getRequest()->setData('validfieldexample', 'validfieldexample');

        static::assertTrue($form->isSent());
        static::assertTrue($form->isValid());

        $errors = $form->getErrorstack()->getErrors();
        static::assertCount(0, $errors);
        static::assertEmpty($errors);

        $data = $form->getData();
        $data = $form->normalizeData($data);
        static::assertEquals([
          'validfieldexample' => 'validfieldexample',
        ], $data);
    }

    /**
     * [testInvalidFormWithFields description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testValidFormWithFieldset(): void
    {
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);

        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
          'fieldset_name_override' => 'example',
        ]);

        $fieldset->addField(
            new field([
              'field_name' => 'validexample',
              'field_type' => 'text',
              'field_value' => null,
              'field_required' => true,
            ])
        );
        $fieldset->addField(
            new field([
              'field_name' => 'validexample2',
              'field_type' => 'text',
              'field_value' => 'validexample2',
            ]),
            0
        );

        $form->addFieldset($fieldset);

        $fieldsets = $form->getFieldsets();
        static::assertCount(1, $fieldsets ?? []);
        static::assertInstanceOf(fieldset::class, $fieldsets[0]);

        $form->setId('validexampleform');
        $form->setAction('post');

        static::assertEquals([
          'form_id' => 'core_form_validexampleform',
          'form_action' => 'post',
          'form_method' => '',
        ], $form->config);
        static::assertCount(0, $form->getFields());
        static::assertCount(1, $form->getFieldsets());
        static::assertEmpty($form->getErrorstack()->getErrors());

        static::assertFalse($form->isSent());

        overrideableApp::getRequest()->setData('formSentcore_form_validexampleform', true);
        overrideableApp::getRequest()->setData('validexample', 'validexample');

        static::assertTrue($form->isSent());
        static::assertTrue($form->isValid());

        $errors = $form->getErrorstack()->getErrors();
        static::assertCount(0, $errors);
        static::assertEmpty($errors);

        $data = $form->getData();
        // NOTE: returned null, if not fields is set
        $data = $form->normalizeData($data);
        static::assertEmpty($data);
    }

    /**
     * [testFormOutput description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testFormOutput(): void
    {
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);

        $form->addField(
            new field([
              'field_name' => 'fieldexample',
              'field_type' => 'text',
              'field_value' => 'fieldexample',
            ])
        );
        $form->addField(
            new field([
              'field_name' => 'fieldexample2',
              'field_type' => 'text',
              'field_value' => 'fieldexample2',
            ])
        );

        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
          'fieldset_name_override' => 'example',
        ]);
        $fieldset->addField(
            new field([
              'field_name' => 'fieldsetexample',
              'field_type' => 'text',
              'field_value' => 'fieldsetexample',
            ])
        );
        $fieldset->addField(
            new field([
              'field_name' => 'fieldsetexample2',
              'field_type' => 'text',
              'field_value' => 'fieldsetexample2',
            ]),
            0
        );
        $form->addFieldset($fieldset);

        $fieldset->setType('default');

        $output = $form->output();
        static::assertEquals('frontend/form/default/form', $output);
    }

    /**
     * [testFormSearchFieldByGetField description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testFormSearchFieldByGetField(): void
    {
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);
        $form->addField(
            new field([
              'field_name' => 'validfieldexample',
              'field_type' => 'text',
              'field_value' => 'validfieldexample',
              'field_required' => true,
            ])
        );
        $form->addField(
            new field([
              'field_name' => 'validfieldexample2',
              'field_type' => 'text',
              'field_value' => 'validfieldexample2',
            ]),
            0
        );
        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
          'fieldset_name_override' => 'example',
        ]);
        $fieldset->addField(
            new field([
              'field_name' => 'validexample',
              'field_type' => 'text',
              'field_value' => null,
              'field_required' => true,
            ])
        );
        $fieldset->addField(
            new field([
              'field_name' => 'validexample2',
              'field_type' => 'text',
              'field_value' => 'validexample2',
            ]),
            0
        );
        $form->addFieldset($fieldset);

        $field = $form->getField('validfieldexample2');
        static::assertEquals([
          'field_name' => 'validfieldexample2',
          'field_type' => 'text',
          'field_id' => 'validfieldexample2',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => false,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'validfieldexample2',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $field->getConfig()->get());

        $field = $form->getField('validexample2');
        static::assertEquals([
          'field_name' => 'validexample2',
          'field_type' => 'text',
          'field_id' => 'validexample2',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => false,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'validexample2',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $field->getConfig()->get());

        $field = $form->getField('fieldnotfound');
        static::assertNull($field);
    }

    /**
     * [testFormSearchFieldByGetFieldRecursive description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testFormSearchFieldByGetFieldRecursive(): void
    {
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);
        $form->addField(
            new field([
              'field_name' => 'validfieldexample',
              'field_type' => 'text',
              'field_value' => 'validfieldexample',
              'field_required' => true,
            ])
        );
        $form->addField(
            new field([
              'field_name' => 'validfieldexample2',
              'field_type' => 'text',
              'field_value' => 'validfieldexample2',
            ]),
            0
        );
        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
          'fieldset_name_override' => 'example',
        ]);
        $fieldset->addField(
            new field([
              'field_name' => 'validexample',
              'field_type' => 'text',
              'field_value' => null,
              'field_required' => true,
            ])
        );
        $fieldset->addField(
            new field([
              'field_name' => 'validexample2',
              'field_type' => 'text',
              'field_value' => 'validexample2',
            ]),
            0
        );
        $form->addFieldset($fieldset);

        $field = $form->getFieldRecursive(['validfieldexample2']);
        static::assertEquals([
          'field_name' => 'validfieldexample2',
          'field_type' => 'text',
          'field_id' => 'validfieldexample2',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => false,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'validfieldexample2',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $field->getConfig()->get());

        $field = $form->getFieldRecursive(['validexample2']);
        static::assertEquals([
          'field_name' => 'validexample2',
          'field_type' => 'text',
          'field_id' => 'validexample2',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => false,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'validexample2',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $field->getConfig()->get());

        $field = $form->getFieldRecursive(['fieldnotfound']);
        static::assertNull($field);
    }

    /**
     * [testFormFieldWithoutForm description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testFormFieldWithoutForm(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('FORM_GETFIELDRECURSIVE_NO_FORM_INSTANCE');
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);
        $form->addField(
            new field([
              'field_name' => 'example',
              'field_type' => 'text',
              'field_value' => 'example',
            ])
        );

        $form->getFieldRecursive(['example', 'example']);
    }

    /**
     * [testFormFieldWithoutFormInstance description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testFormFieldWithoutFormInstance(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('FORM_GETFIELDRECURSIVE_INVALID_FORM_INSTANCE');
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);
        $form->addField(
            new field([
              'field_name' => 'example',
              'field_type' => 'text',
              'field_value' => 'example',
              'form' => 'example',
            ])
        );

        $form->getFieldRecursive(['example', 'example']);
    }

    /**
     * [testFormFieldWithFormInstance description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testFormFieldWithFormInstance(): void
    {
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);
        $form->addField(
            new field([
              'field_name' => 'example',
              'field_type' => 'text',
              'field_value' => 'example',
              'form' => (new form([
                'form_id' => 'exampleform',
                'form_action' => 'get',
                'form_method' => '',
              ])),
            ])
        );

        $field = $form->getFieldRecursive(['example', 'example']);
        static::assertNull($field);
    }

    /**
     * [testFormFieldsetWithoutForm description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testFormFieldsetWithoutForm(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('FORM_GETFIELDRECURSIVE_NO_FORM_INSTANCE');
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);
        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
          'fieldset_name_override' => 'example',
        ]);
        $fieldset->addField(
            new field([
              'field_name' => 'example',
              'field_type' => 'text',
              'field_value' => 'example',
            ])
        );
        $form->addFieldset($fieldset);

        $form->getFieldRecursive(['example', 'example']);
    }

    /**
     * [testFormFieldsetWithoutFormInstance description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testFormFieldsetWithoutFormInstance(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('FORM_GETFIELDRECURSIVE_INVALID_FORM_INSTANCE');
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);
        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
          'fieldset_name_override' => 'example',
        ]);
        $fieldset->addField(
            new field([
              'field_name' => 'example',
              'field_type' => 'text',
              'field_value' => 'example',
              'form' => 'example',
            ])
        );
        $form->addFieldset($fieldset);

        $form->getFieldRecursive(['example', 'example']);
    }

    /**
     * [testFormFieldsetWithFormInstance description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testFormFieldsetWithFormInstance(): void
    {
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);
        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
          'fieldset_name_override' => 'example',
        ]);
        $fieldset->addField(
            new field([
              'field_name' => 'example',
              'field_type' => 'text',
              'field_value' => 'example',
              'form' => (new form([
                'form_id' => 'exampleform',
                'form_action' => 'get',
                'form_method' => '',
              ])),
            ])
        );
        $form->addFieldset($fieldset);

        $field = $form->getFieldRecursive(['example', 'example']);
        static::assertNull($field);
    }

    /**
     * [testJsonSerialize description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testJsonSerialize(): void
    {
        $form = new form([
          'form_id' => 'serialized_form',
          'form_action' => '',
          'form_method' => '',
        ]);

        $form->addField(
            new field([
              'field_name' => 'field1',
              'field_type' => 'input',
              'field_value' => 'value1',
            ])
        );

        $fieldset = new fieldset([
          'fieldset_id' => 'serialized_fieldset',
          'fieldset_name' => 'serialized_fieldset_name',
        ]);
        $form->addFieldset($fieldset);

        $fieldset->addField(
            new field([
              'field_name' => 'field2',
              'field_type' => 'input',
              'field_value' => 'value2',
            ])
        );


        $jsonData = json_decode(json_encode($form), true);
        static::assertEquals('serialized_form', $jsonData['config']['form_id']);
        static::assertEquals('', $jsonData['config']['form_action']);
        static::assertEquals('', $jsonData['config']['form_method']);
        static::assertCount(1, $jsonData['fields']);
        static::assertEquals('field1', $jsonData['fields'][0]['field_name']);
        static::assertEquals('value1', $jsonData['fields'][0]['field_value']);
        static::assertCount(1, $jsonData['fieldsets']);
        static::assertCount(1, $jsonData['fieldsets'][0]['fields']);
        static::assertEquals('field2', $jsonData['fieldsets'][0]['fields'][0]['field_name']);
        static::assertEquals('value2', $jsonData['fieldsets'][0]['fields'][0]['field_value']);
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     * @throws exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $app = static::createApp();
        overrideableApp::__injectApp([
          'vendor' => 'codename',
          'app' => 'core-ui',
          'namespace' => '\\codename\\core\\ui',
        ]);

        $app::getAppstack();

        static::setEnvironmentConfig([
          'test' => [
            'cache' => [
              'default' => [
                'driver' => 'memory',
              ],
            ],
            'translate' => [
              'default' => [
                'driver' => 'json',
                'inherit' => true,
              ],
            ],
            'templateengine' => [
              'default' => [
                'driver' => 'dummy',
              ],
            ],
          ],
        ]);
    }
}
