<?php
namespace codename\core\ui\tests;

use \codename\core\test\base;
use \codename\core\test\overrideableApp;

class fieldTest extends base
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
   * [testInvalidConfiguration description]
   */
  public function testInvalidConfiguration(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID');

    $field = new \codename\core\ui\field([]);
  }

  /**
   * [testConfiguration description]
   */
  public function testConfiguration(): void {
    $field = new \codename\core\ui\field([
      'field_name'  => 'example',
      'field_type'  => 'text',
      'field_value' => 'example',
    ]);

    $result = $field->getConfig();
    $this->assertEquals([
      'field_name'        => 'example',
      'field_type'        => 'text',
      'field_id'          => 'example',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => false,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'example',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $result->get());

    $this->assertFalse($field->isRequired());

    $field->setProperty('field_required', true);

    $this->assertTrue($field->getProperty('field_required'));

    $field->setValue('example_editing');

    $result = $field->getConfig();
    $this->assertEquals([
      'field_name'        => 'example',
      'field_type'        => 'text',
      'field_id'          => 'example',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => true,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'example_editing',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $result->get());

  }

  /**
   * [testProperties description]
   */
  public function testProperties(): void {
    $properties = \codename\core\ui\field::getProperties();

    $this->assertEquals([
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
   */
  public function testNormalizedFieldValue(): void {
    $fields = [
      [ 'name' => 'example', 'value' => true, 'datatype' => 'boolean', 'result' => true ],
      [ 'name' => 'example', 'value' => 1, 'datatype' => 'boolean', 'result' => true ],

      [ 'name' => 'example', 'value' => '', 'datatype' => 'boolean', 'result' => null ],
      [ 'name' => 'example', 'value' => '1', 'datatype' => 'boolean', 'result' => true ],
      [ 'name' => 'example', 'value' => '0', 'datatype' => 'boolean', 'result' => false ],
      [ 'name' => 'example', 'value' => 'true', 'datatype' => 'boolean', 'result' => true ],
      [ 'name' => 'example', 'value' => 'false', 'datatype' => 'boolean', 'result' => false ],

      [ 'name' => 'example', 'value' => '', 'datatype' => 'number_natural', 'result' => null ],
      [ 'name' => 'example', 'value' => '1', 'datatype' => 'number_natural', 'result' => 1 ],
      [ 'name' => 'example', 'value' => null, 'datatype' => 'number_natural', 'result' => null ],
      [ 'name' => 'example', 'value' => 1, 'datatype' => 'number_natural', 'result' => 1 ],
    ];

    foreach($fields as $field) {
      $result = \codename\core\ui\field::getNormalizedFieldValue($field['name'], $field['value'], $field['datatype']);
      $this->assertEquals($field['result'], $result);
    }

  }

  /**
   * [testNormalizedFieldValueInvalidValueCase1 description]
   */
  public function testNormalizedFieldValueInvalidValueCase1(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_FIELD_NORMALIZEFIELD_BOOLEAN_INVALID');
    $result = \codename\core\ui\field::getNormalizedFieldValue('example', 2, 'boolean');
  }

  /**
   * [testNormalizedFieldValueInvalidValueCase1 description]
   */
  public function testNormalizedFieldValueInvalidValueCase2(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_FIELD_NORMALIZEFIELD_BOOLEAN_INVALID');
    $result = \codename\core\ui\field::getNormalizedFieldValue('example', '2', 'boolean');
  }

  /**
   * [testOutputData description]
   */
  public function testOutputData(): void {
    $field = new \codename\core\ui\field([
      'field_name'  => 'example',
      'field_type'  => 'text',
      'field_value' => 'example',
    ]);

    $result = $field->output(true);
    $this->assertEquals([
      'field_name'        => 'example',
      'field_type'        => 'text',
      'field_id'          => 'example',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => false,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'example',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $result);

  }

  /**
   * [testOutputData description]
   */
  public function testOutput(): void {
    $field = new \codename\core\ui\field([
      'field_name'          => 'example',
      'field_type'          => 'text',
      'field_value'         => function() {
        return 'example';
      },
      'field_idfield'       => 'id',
      'field_displayfield'  => 'id',
      'field_valuefield'    => 'name',
      'field_elements'      => function() {
        return [
          [
            'id'    => 1,
            'name'  => 'name',
          ]
        ];
      },
    ]);

    $field->setType('default');

    $output = $field->output();
    $this->assertEquals('frontend/field/default/text', $output);

  }

  /**
   * [testOutputCase2 description]
   */
  public function testOutputCase2(): void {
    $field = new \codename\core\ui\field([
      'field_name'          => 'example',
      'field_type'          => 'text',
      'field_value'         => function() {
        return 'example';
      },
      'field_displayfield'  => 'id',
      'field_valuefield'    => 'name',
      'field_elements'      => function() {
        return [
          [
            'id'    => 1,
            'name'  => 'name',
          ]
        ];
      },
    ]);

    $field->setType('default');

    $output = $field->output();
    $this->assertEquals('frontend/field/default/text', $output);

  }

  /**
   * [testOutputDataWithForm description]
   */
  public function testOutputDataWithForm(): void {
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);

    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'formfieldexample',
      'field_type'      => 'text',
      'field_value'     => 'formfieldexample',
    ]));

    $field = new \codename\core\ui\field([
      'field_name'  => 'example',
      'field_type'  => 'form',
      'field_value' => [
        'formfieldexample'  => 'formfieldexample',
      ],
      'form'            => $form,
    ]);

    $result = $field->output(true);
    $this->assertEquals([
      'formfieldexample'  => 'formfieldexample',
    ], $result['field_value']);

  }

}
