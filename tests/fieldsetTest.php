<?php

namespace codename\core\ui\tests;

use codename\core\exception;
use codename\core\test\base;
use codename\core\test\overrideableApp;
use codename\core\ui\field;
use codename\core\ui\fieldset;
use ReflectionException;

class fieldsetTest extends base
{
    /**
     * [testGeneric description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testGeneric(): void
    {
        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
        ]);

        $fieldset->addField(
            new field([
              'field_name' => 'example',
              'field_type' => 'text',
              'field_value' => 'example',
            ])
        );
        $fieldset->addField(
            new field([
              'field_name' => 'example2',
              'field_type' => 'text',
              'field_value' => 'example2',
            ]),
            0
        );

        $config = $fieldset->output(true);

        static::assertEquals('example', $config['fieldset_id']);
        static::assertEquals('FIELDSET_EXAMPLE', $config['fieldset_name']);

        static::assertCount(2, $config['fields'] ?? []);
        static::assertInstanceOf(field::class, $config['fields'][0]);
        static::assertInstanceOf(field::class, $config['fields'][1]);

        static::assertEquals([
          'field_name' => 'example2',
          'field_type' => 'text',
          'field_id' => 'example2',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => false,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'example2',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $config['fields'][0]->getConfig()->get());

        $fields = $fieldset->getFields();
        static::assertCount(2, $fields ?? []);
        static::assertInstanceOf(field::class, $fields[0]);
        static::assertInstanceOf(field::class, $fields[1]);

        static::assertEquals([
          'field_name' => 'example2',
          'field_type' => 'text',
          'field_id' => 'example2',
          'field_fieldtype' => 'input',
          'field_class' => 'input',
          'field_required' => false,
          'field_readonly' => false,
          'field_ajax' => false,
          'field_noninput' => false,
          'field_placeholder' => '',
          'field_value' => 'example2',
          'field_datatype' => 'text',
          'field_validator' => '',
          'field_description' => '',
          'field_title' => '',
        ], $fields[0]->getConfig()->get());
    }

    /**
     * [testFieldsetNameOverride description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testFieldsetNameOverride(): void
    {
        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
          'fieldset_name_override' => 'example_override',
        ]);

        $config = $fieldset->output(true);

        static::assertEquals('example', $config['fieldset_id']);
        static::assertEquals('example_override', $config['fieldset_name']);

        static::assertCount(0, $config['fields'] ?? []);
    }

    /**
     * [testOutput description]
     * @throws ReflectionException
     * @throws exception
     */
    public function testOutput(): void
    {
        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
          'fieldset_name_override' => 'example_override',
        ]);

        $fieldset->addField(
            new field([
              'field_name' => 'example',
              'field_type' => 'text',
              'field_value' => 'example',
            ])
        );

        $fieldset->setType('default');

        $output = $fieldset->output();
        static::assertEquals('frontend/fieldset/default', $output);
    }

    /**
     * Tests \JsonSerializable Interface integrity
     * @throws ReflectionException
     * @throws exception
     */
    public function testJsonSerialize(): void
    {
        $fieldset = new fieldset([
          'fieldset_id' => 'example',
          'fieldset_name' => 'example',
          'fieldset_name_override' => 'example_override',
        ]);

        $fieldset->addField(
            new field([
              'field_name' => 'example',
              'field_type' => 'text',
              'field_value' => 'example',
            ])
        );

        $fieldset->setType('default');

        $fieldset->output(true);

        $jsonData = json_decode(json_encode($fieldset), true);

        static::assertEquals('example', $jsonData['fieldset_id']);
        static::assertEquals('example_override', $jsonData['fieldset_name']);

        static::assertCount(1, $jsonData['fields']);
        $fieldData = $jsonData['fields'][0];
        static::assertEquals('example', $fieldData['field_name']);
        static::assertEquals('text', $fieldData['field_type']);
        static::assertEquals('example', $fieldData['field_value']);
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
