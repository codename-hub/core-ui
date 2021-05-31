<?php
namespace codename\core\ui\tests;

use \codename\core\test\base;
use \codename\core\test\overrideableApp;

class fieldsetTest extends base
{

  /**
   * @inheritDoc
   */
  protected function setUp(): void
  {
    parent::setUp();

    $app = static::createApp();
    overrideableApp::__injectApp([
      'vendor' => 'codename',
      'app' => 'core-ui',
      'namespace' => '\\codename\\core\\ui'
    ]);

    $app->getAppstack();

    static::setEnvironmentConfig([
      'test' => [
        'cache' => [
          'default' => [
            'driver' => 'memory'
          ]
        ],
        'translate' => [
          'default' => [
            'driver'  => 'json',
            'inherit' => true,
          ]
        ],
        'templateengine' => [
          'default' => [
            'driver' => 'dummy',
          ]
        ],
      ]
    ]);
  }

  /**
   * [testGeneric description]
   */
  public function testGeneric(): void {
    $fieldset = new \codename\core\ui\fieldset([
      'fieldset_id'   => 'example',
      'fieldset_name' => 'example',
    ]);

    $fieldset->addField(new \codename\core\ui\field([
      'field_name'  => 'example',
      'field_type'  => 'text',
      'field_value' => 'example',
    ]));
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'  => 'example2',
      'field_type'  => 'text',
      'field_value' => 'example2',
    ]), 0);

    $config = $fieldset->output(true);

    $this->assertEquals('example', $config['fieldset_id']);
    $this->assertEquals('FIELDSET_EXAMPLE', $config['fieldset_name']);

    $this->assertCount(2, $config['fields'] ?? []);
    $this->assertInstanceOf(\codename\core\ui\field::class, $config['fields'][0]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $config['fields'][1]);

    $this->assertEquals([
      'field_name'        => 'example2',
      'field_type'        => 'text',
      'field_id'          => 'example2',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => false,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'example2',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $config['fields'][0]->getConfig()->get());

    $fields = $fieldset->getFields();
    $this->assertCount(2, $fields ?? []);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[0]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[1]);

    $this->assertEquals([
      'field_name'        => 'example2',
      'field_type'        => 'text',
      'field_id'          => 'example2',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => false,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'example2',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $fields[0]->getConfig()->get());
  }

  /**
   * [testFieldsetNameOverride description]
   */
  public function testFieldsetNameOverride(): void {
    $fieldset = new \codename\core\ui\fieldset([
      'fieldset_id'             => 'example',
      'fieldset_name'           => 'example',
      'fieldset_name_override'  => 'example_override',
    ]);

    $config = $fieldset->output(true);

    $this->assertEquals('example', $config['fieldset_id']);
    $this->assertEquals('example_override', $config['fieldset_name']);

    $this->assertCount(0, $config['fields'] ?? []);
  }

  /**
   * [testOutput description]
   */
  public function testOutput(): void {
    $fieldset = new \codename\core\ui\fieldset([
      'fieldset_id'             => 'example',
      'fieldset_name'           => 'example',
      'fieldset_name_override'  => 'example_override',
    ]);

    $fieldset->addField(new \codename\core\ui\field([
      'field_name'  => 'example',
      'field_type'  => 'text',
      'field_value' => 'example',
    ]));

    $fieldset->setType('default');

    $output = $fieldset->output();
    $this->assertEquals('frontend/fieldset/default', $output);
  }

}
