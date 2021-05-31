<?php
namespace codename\core\ui\tests;

use \codename\core\test\base;
use \codename\core\test\overrideableApp;

class formTest extends base
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
   * [testInvalidConstruct description]
   */
  public function testInvalidConstruct(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID');

    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'post',
    ]);

  }

  /**
   * [testEmptyForm description]
   */
  public function testEmptyForm(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('EXCEPTION_OUTPUT_FORMISEMPTY');

    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'post',
      'form_method' => '',
    ]);

    $form->output(true);

  }

  /**
   * [testGeneric description]
   */
  public function testGeneric(): void {
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'post',
      'form_method' => '',
    ]);

    $this->assertEquals([
      'form_id'     => 'exampleform',
      'form_action' => 'post',
      'form_method' => '',
    ], $form->config);
    $this->assertCount(0, $form->getFields());
    $this->assertCount(0, $form->getFieldsets());
    $this->assertEmpty($form->getErrorstack()->getErrors());

    $this->assertEmpty($form->getTemplateEngine());

    $templateEngine = overrideableApp::getTemplateEngine();
    $form->setTemplateEngine($templateEngine);
    $this->assertNotEmpty($form->getTemplateEngine());

  }

  /**
   * [testInvalidFormWithFields description]
   */
  public function testInvalidFormWithFields(): void {
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);

    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'invalidexample',
      'field_type'      => 'text',
      'field_value'     => null,
      'field_required'  => true,
    ]));
    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'noninputexample',
      'field_type'      => 'text',
      'field_value'     => null,
      'field_noninput'  => true,
    ]));
    $form->addField(new \codename\core\ui\field([
      'field_name'  => 'invalidexample2',
      'field_type'  => 'text',
      'field_value' => 'invalidexample2',
    ]), 0);

    $fields = $form->getFields();
    $this->assertCount(3, $fields ?? []);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[0]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[1]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[2]);

    $this->assertEquals([
      'field_name'        => 'invalidexample2',
      'field_type'        => 'text',
      'field_id'          => 'invalidexample2',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => false,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'invalidexample2',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $fields[0]->getConfig()->get());

    $form->setId('invalidexampleform');
    $form->setAction('post');

    $this->assertEquals([
      'form_id'     => 'core_form_invalidexampleform',
      'form_action' => 'post',
      'form_method' => '',
    ], $form->config);
    $this->assertCount(3, $form->getFields());
    $this->assertCount(0, $form->getFieldsets());
    $this->assertEmpty($form->getErrorstack()->getErrors());

    $this->assertFalse($form->isSent());

    $result = $form->output(true);
    $this->assertNotEmpty($result);

    overrideableApp::getRequest()->setData('formSentcore_form_invalidexampleform', true);

    $this->assertTrue($form->isSent());

    $this->assertFalse($form->isValid());

    $errors = $form->getErrorstack()->getErrors();

    $this->assertCount(1, $errors);
    $this->assertEquals([
      [
        '__IDENTIFIER'  => 'invalidexample',
        '__CODE'        => 'VALIDATION.FIELD_NOT_SET',
        '__TYPE'        => 'VALIDATION',
        '__DETAILS'     => null,
      ]
    ], $errors);

  }

  /**
   * [testValidFormWithFields description]
   */
  public function testValidFormWithFields(): void {
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);

    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'validfieldexample',
      'field_type'      => 'text',
      'field_value'     => 'validfieldexample',
      'field_required'  => true,
    ]));
    $form->addField(new \codename\core\ui\field([
      'field_name'  => 'validfieldexample2',
      'field_type'  => 'text',
      'field_value' => 'validfieldexample2',
    ]), 0);

    $fields = $form->getFields();
    $this->assertCount(2, $fields ?? []);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[0]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[1]);

    $this->assertEquals([
      'field_name'        => 'validfieldexample2',
      'field_type'        => 'text',
      'field_id'          => 'validfieldexample2',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => false,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'validfieldexample2',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $fields[0]->getConfig()->get());

    $form->setId('validfieldexampleform');
    $form->setAction('post');

    $this->assertEquals([
      'form_id'     => 'core_form_validfieldexampleform',
      'form_action' => 'post',
      'form_method' => '',
    ], $form->config);
    $this->assertCount(2, $form->getFields());
    $this->assertCount(0, $form->getFieldsets());
    $this->assertEmpty($form->getErrorstack()->getErrors());

    $this->assertFalse($form->isSent());

    overrideableApp::getRequest()->setData('formSentcore_form_validfieldexampleform', true);
    overrideableApp::getRequest()->setData('validfieldexample', 'validfieldexample');

    $this->assertTrue($form->isSent());
    $this->assertTrue($form->isValid());

    $errors = $form->getErrorstack()->getErrors();
    $this->assertCount(0, $errors);
    $this->assertEmpty($errors);

    $data = $form->getData();
    $data = $form->normalizeData($data);
    $this->assertEquals([
      'validfieldexample' => 'validfieldexample'
    ], $data);

  }

  /**
   * [testInvalidFormWithFields description]
   */
  public function testValidFormWithFieldset(): void {
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);

    $fieldset = new \codename\core\ui\fieldset([
      'fieldset_id'             => 'example',
      'fieldset_name'           => 'example',
      'fieldset_name_override'  => 'example',
    ]);

    $fieldset->addField(new \codename\core\ui\field([
      'field_name'      => 'validexample',
      'field_type'      => 'text',
      'field_value'     => null,
      'field_required'  => true,
    ]));
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'  => 'validexample2',
      'field_type'  => 'text',
      'field_value' => 'validexample2',
    ]), 0);

    $form->addFieldset($fieldset);

    $fieldsets = $form->getFieldsets();
    $this->assertCount(1, $fieldsets ?? []);
    $this->assertInstanceOf(\codename\core\ui\fieldset::class, $fieldsets[0]);

    $form->setId('validexampleform');
    $form->setAction('post');

    $this->assertEquals([
      'form_id'     => 'core_form_validexampleform',
      'form_action' => 'post',
      'form_method' => '',
    ], $form->config);
    $this->assertCount(0, $form->getFields());
    $this->assertCount(1, $form->getFieldsets());
    $this->assertEmpty($form->getErrorstack()->getErrors());

    $this->assertFalse($form->isSent());

    overrideableApp::getRequest()->setData('formSentcore_form_validexampleform', true);
    overrideableApp::getRequest()->setData('validexample', 'validexample');

    $this->assertTrue($form->isSent());
    $this->assertTrue($form->isValid());

    $errors = $form->getErrorstack()->getErrors();
    $this->assertCount(0, $errors);
    $this->assertEmpty($errors);

    $data = $form->getData();
    // NOTE: returned null, if not fields is set
    $data = $form->normalizeData($data);
    $this->assertEmpty($data);

  }

  /**
   * [testFormOutput description]
   */
  public function testFormOutput(): void {
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);

    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'fieldexample',
      'field_type'      => 'text',
      'field_value'     => 'fieldexample',
    ]));
    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'fieldexample2',
      'field_type'      => 'text',
      'field_value'     => 'fieldexample2',
    ]));

    $fieldset = new \codename\core\ui\fieldset([
      'fieldset_id'             => 'example',
      'fieldset_name'           => 'example',
      'fieldset_name_override'  => 'example',
    ]);
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'      => 'fieldsetexample',
      'field_type'      => 'text',
      'field_value'     => 'fieldsetexample',
    ]));
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'  => 'fieldsetexample2',
      'field_type'  => 'text',
      'field_value' => 'fieldsetexample2',
    ]), 0);
    $form->addFieldset($fieldset);

    $fieldset->setType('default');

    $output = $form->output();
    $this->assertEquals('frontend/form/default/form', $output);

  }

  /**
   * [testFormSearchFieldByGetField description]
   */
  public function testFormSearchFieldByGetField(): void {
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);
    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'validfieldexample',
      'field_type'      => 'text',
      'field_value'     => 'validfieldexample',
      'field_required'  => true,
    ]));
    $form->addField(new \codename\core\ui\field([
      'field_name'  => 'validfieldexample2',
      'field_type'  => 'text',
      'field_value' => 'validfieldexample2',
    ]), 0);
    $fieldset = new \codename\core\ui\fieldset([
      'fieldset_id'             => 'example',
      'fieldset_name'           => 'example',
      'fieldset_name_override'  => 'example',
    ]);
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'      => 'validexample',
      'field_type'      => 'text',
      'field_value'     => null,
      'field_required'  => true,
    ]));
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'  => 'validexample2',
      'field_type'  => 'text',
      'field_value' => 'validexample2',
    ]), 0);
    $form->addFieldset($fieldset);

    $field = $form->getField('validfieldexample2');
    $this->assertEquals([
      'field_name'        => 'validfieldexample2',
      'field_type'        => 'text',
      'field_id'          => 'validfieldexample2',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => false,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'validfieldexample2',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $field->getConfig()->get());

    $field = $form->getField('validexample2');
    $this->assertEquals([
      'field_name'        => 'validexample2',
      'field_type'        => 'text',
      'field_id'          => 'validexample2',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => false,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'validexample2',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $field->getConfig()->get());

    $field = $form->getField('fieldnotfound');
    $this->assertNull($field);

  }

  /**
   * [testFormSearchFieldByGetFieldRecursive description]
   */
  public function testFormSearchFieldByGetFieldRecursive(): void {
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);
    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'validfieldexample',
      'field_type'      => 'text',
      'field_value'     => 'validfieldexample',
      'field_required'  => true,
    ]));
    $form->addField(new \codename\core\ui\field([
      'field_name'  => 'validfieldexample2',
      'field_type'  => 'text',
      'field_value' => 'validfieldexample2',
    ]), 0);
    $fieldset = new \codename\core\ui\fieldset([
      'fieldset_id'             => 'example',
      'fieldset_name'           => 'example',
      'fieldset_name_override'  => 'example',
    ]);
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'      => 'validexample',
      'field_type'      => 'text',
      'field_value'     => null,
      'field_required'  => true,
    ]));
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'  => 'validexample2',
      'field_type'  => 'text',
      'field_value' => 'validexample2',
    ]), 0);
    $form->addFieldset($fieldset);

    $field = $form->getFieldRecursive([ 'validfieldexample2' ]);
    $this->assertEquals([
      'field_name'        => 'validfieldexample2',
      'field_type'        => 'text',
      'field_id'          => 'validfieldexample2',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => false,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'validfieldexample2',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $field->getConfig()->get());

    $field = $form->getFieldRecursive([ 'validexample2' ]);
    $this->assertEquals([
      'field_name'        => 'validexample2',
      'field_type'        => 'text',
      'field_id'          => 'validexample2',
      'field_fieldtype'   => 'input',
      'field_class'       => 'input',
      'field_required'    => false,
      'field_readonly'    => false,
      'field_ajax'        => false,
      'field_noninput'    => false,
      'field_placeholder' => '',
      'field_value'       => 'validexample2',
      'field_datatype'    => 'text',
      'field_validator'   => '',
      'field_description' => '',
      'field_title'       => '',
    ], $field->getConfig()->get());

    $field = $form->getFieldRecursive([ 'fieldnotfound' ]);
    $this->assertNull($field);

  }

  /**
   * [testFormFieldWithoutForm description]
   */
  public function testFormFieldWithoutForm(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('FORM_GETFIELDRECURSIVE_NO_FORM_INSTANCE');
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);
    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'example',
      'field_type'      => 'text',
      'field_value'     => 'example',
    ]));

    $field = $form->getFieldRecursive([ 'example', 'example' ]);
  }

  /**
   * [testFormFieldWithoutFormInstance description]
   */
  public function testFormFieldWithoutFormInstance(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('FORM_GETFIELDRECURSIVE_INVALID_FORM_INSTANCE');
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);
    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'example',
      'field_type'      => 'text',
      'field_value'     => 'example',
      'form'            => 'example',
    ]));

    $field = $form->getFieldRecursive([ 'example', 'example' ]);
  }

  /**
   * [testFormFieldWithFormInstance description]
   */
  public function testFormFieldWithFormInstance(): void {
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);
    $form->addField(new \codename\core\ui\field([
      'field_name'      => 'example',
      'field_type'      => 'text',
      'field_value'     => 'example',
      'form'            => (new \codename\core\ui\form([
        'form_id'     => 'exampleform',
        'form_action' => 'get',
        'form_method' => '',
      ])),
    ]));

    $field = $form->getFieldRecursive([ 'example', 'example' ]);
    $this->assertNull($field);
  }

  /**
   * [testFormFieldsetWithoutForm description]
   */
  public function testFormFieldsetWithoutForm(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('FORM_GETFIELDRECURSIVE_NO_FORM_INSTANCE');
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);
    $fieldset = new \codename\core\ui\fieldset([
      'fieldset_id'             => 'example',
      'fieldset_name'           => 'example',
      'fieldset_name_override'  => 'example',
    ]);
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'      => 'example',
      'field_type'      => 'text',
      'field_value'     => 'example',
    ]));
    $form->addFieldset($fieldset);

    $field = $form->getFieldRecursive([ 'example', 'example' ]);
  }

  /**
   * [testFormFieldsetWithoutFormInstance description]
   */
  public function testFormFieldsetWithoutFormInstance(): void {
    $this->expectException(\codename\core\exception::class);
    $this->expectExceptionMessage('FORM_GETFIELDRECURSIVE_INVALID_FORM_INSTANCE');
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);
    $fieldset = new \codename\core\ui\fieldset([
      'fieldset_id'             => 'example',
      'fieldset_name'           => 'example',
      'fieldset_name_override'  => 'example',
    ]);
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'      => 'example',
      'field_type'      => 'text',
      'field_value'     => 'example',
      'form'            => 'example',
    ]));
    $form->addFieldset($fieldset);

    $field = $form->getFieldRecursive([ 'example', 'example' ]);
  }

  /**
   * [testFormFieldsetWithFormInstance description]
   */
  public function testFormFieldsetWithFormInstance(): void {
    $form = new \codename\core\ui\form([
      'form_id'     => 'exampleform',
      'form_action' => 'get',
      'form_method' => '',
    ]);
    $fieldset = new \codename\core\ui\fieldset([
      'fieldset_id'             => 'example',
      'fieldset_name'           => 'example',
      'fieldset_name_override'  => 'example',
    ]);
    $fieldset->addField(new \codename\core\ui\field([
      'field_name'      => 'example',
      'field_type'      => 'text',
      'field_value'     => 'example',
      'form'            => (new \codename\core\ui\form([
        'form_id'     => 'exampleform',
        'form_action' => 'get',
        'form_method' => '',
      ])),
    ]));
    $form->addFieldset($fieldset);

    $field = $form->getFieldRecursive([ 'example', 'example' ]);
    $this->assertNull($field);
  }

}
