<?php

namespace codename\core\ui\tests;

use codename\core\exception;
use codename\core\test\base;
use codename\core\test\overrideableApp;
use codename\core\ui\field;
use codename\core\ui\form;
use ReflectionException;

class fieldTest extends base
{
    /**
     * [testInvalidConfiguration description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testInvalidConfiguration(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID');

        new field([]);
    }

    /**
     * [testConfiguration description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testConfiguration(): void
    {
        $field = new field([
          'field_name' => 'example',
          'field_type' => 'text',
          'field_value' => 'example',
        ]);

        $result = $field->getConfig();
        static::assertEquals([
          'field_name' => 'example',
          'field_type' => 'text',
          'field_id' => 'example',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => false,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'example',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $result->get());

        static::assertFalse($field->isRequired());

        $field->setProperty('field_required', true);

        static::assertTrue($field->getConfig()->get('field_required'));

        $field->setValue('example_editing');

        $result = $field->getConfig();
        static::assertEquals([
          'field_name' => 'example',
          'field_type' => 'text',
          'field_id' => 'example',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => true,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'example_editing',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $result->get());
    }

    /**
     * [testProperties description]
     */
    public function testProperties(): void
    {
        $properties = field::getProperties();

        static::assertEquals([
          'field_id',
          'field_name',
          'field_title',
          'field_description',
          'field_type',
          'field_required',
          'field_placeholder',
          'field_multiple',
          'field_ajax',
          'field_noninput',
        ], $properties);
    }

    /**
     * [testNormalizedFieldValue description]
     * @throws exception
     */
    public function testNormalizedFieldValue(): void
    {
        $fields = [
          ['name' => 'example', 'value' => true, 'datatype' => 'boolean', 'result' => true],
          ['name' => 'example', 'value' => 1, 'datatype' => 'boolean', 'result' => true],

          ['name' => 'example', 'value' => '', 'datatype' => 'boolean', 'result' => null],
          ['name' => 'example', 'value' => '1', 'datatype' => 'boolean', 'result' => true],
          ['name' => 'example', 'value' => '0', 'datatype' => 'boolean', 'result' => false],
          ['name' => 'example', 'value' => 'true', 'datatype' => 'boolean', 'result' => true],
          ['name' => 'example', 'value' => 'false', 'datatype' => 'boolean', 'result' => false],

          ['name' => 'example', 'value' => '', 'datatype' => 'number_natural', 'result' => null],
          ['name' => 'example', 'value' => '1', 'datatype' => 'number_natural', 'result' => 1],
          ['name' => 'example', 'value' => null, 'datatype' => 'number_natural', 'result' => null],
          ['name' => 'example', 'value' => 1, 'datatype' => 'number_natural', 'result' => 1],
        ];

        foreach ($fields as $field) {
            $result = field::getNormalizedFieldValue($field['name'], $field['value'], $field['datatype']);
            static::assertEquals($field['result'], $result);
        }
    }

    /**
     * [testNormalizedFieldValueInvalidValueCase1 description]
     */
    public function testNormalizedFieldValueInvalidValueCase1(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_FIELD_NORMALIZEFIELD_BOOLEAN_INVALID');
        field::getNormalizedFieldValue('example', 2, 'boolean');
    }

    /**
     * [testNormalizedFieldValueInvalidValueCase1 description]
     */
    public function testNormalizedFieldValueInvalidValueCase2(): void
    {
        $this->expectException(exception::class);
        $this->expectExceptionMessage('EXCEPTION_FIELD_NORMALIZEFIELD_BOOLEAN_INVALID');
        field::getNormalizedFieldValue('example', '2', 'boolean');
    }

    /**
     * [testOutputData description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testOutputData(): void
    {
        $field = new field([
          'field_name' => 'example',
          'field_type' => 'text',
          'field_value' => 'example',
        ]);

        $result = $field->output(true);
        static::assertEquals([
          'field_name' => 'example',
          'field_type' => 'text',
          'field_id' => 'example',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => false,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'example',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $result);
    }

    /**
     * [testOutputData description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testOutput(): void
    {
        $field = new field([
          'field_name' => 'example',
          'field_type' => 'text',
          'field_value' => function () {
              return 'example';
          },
          'field_idfield' => 'id',
          'field_displayfield' => 'id',
          'field_valuefield' => 'name',
          'field_elements' => function () {
              return [
                [
                  'id' => 1,
                  'name' => 'name',
                ],
              ];
          },
        ]);

        $field->setType('default');

        $output = $field->output();
        static::assertEquals('frontend/field/default/text', $output);
    }

    /**
     * [testOutputCase2 description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testOutputCase2(): void
    {
        $field = new field([
          'field_name' => 'example',
          'field_type' => 'text',
          'field_value' => function () {
              return 'example';
          },
          'field_displayfield' => 'id',
          'field_valuefield' => 'name',
          'field_elements' => function () {
              return [
                [
                  'id' => 1,
                  'name' => 'name',
                ],
              ];
          },
        ]);

        $field->setType('default');

        $output = $field->output();
        static::assertEquals('frontend/field/default/text', $output);
    }

    /**
     * [testOutputDataWithForm description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testOutputDataWithForm(): void
    {
        $form = new form([
          'form_id' => 'exampleform',
          'form_action' => 'get',
          'form_method' => '',
        ]);

        $form->addField(
            new field([
              'field_name' => 'formfieldexample',
              'field_type' => 'text',
              'field_value' => 'formfieldexample',
            ])
        );

        $field = new field([
          'field_name' => 'example',
          'field_type' => 'form',
          'field_value' => [
            'formfieldexample' => 'formfieldexample',
          ],
          'form' => $form,
        ]);

        $result = $field->output(true);
        static::assertEquals([
          'formfieldexample' => 'formfieldexample',
        ], $result['field_value']);
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
