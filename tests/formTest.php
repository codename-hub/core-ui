<?php
namespace codename\core\ui\tests;

use \codename\core\tests\base;
use \codename\core\tests\overrideableApp;

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
      ]
    ]);
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

    $result = $form->jsonSerialize();
    $this->assertCount(4, $result);
    $this->assertEquals([
      'form_id'     => 'exampleform',
      'form_action' => 'post',
      'form_method' => '',
    ], $result['config'] ?? []);
    $this->assertEmpty($result['fields'] ?? []);
    $this->assertEmpty($result['fieldsets'] ?? []);
    $this->assertEmpty($result['errorstack']->getErrors() ?? []);

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
      'field_name'  => 'invalidexample2',
      'field_type'  => 'text',
      'field_value' => 'invalidexample2',
    ]), 0);

    $fields = $form->getFields();
    $this->assertCount(2, $fields ?? []);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[0]);
    $this->assertInstanceOf(\codename\core\ui\field::class, $fields[1]);

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

    $result = $form->jsonSerialize();
    $this->assertCount(4, $result);
    $this->assertEquals([
      'form_id'     => 'core_form_invalidexampleform',
      'form_action' => 'post',
      'form_method' => '',
    ], $result['config'] ?? []);
    $this->assertCount(2, $result['fields'] ?? []);
    $this->assertCount(0, $result['fieldsets'] ?? []);
    $this->assertEmpty($result['errorstack']->getErrors() ?? []);

    $this->assertFalse($form->isSent());

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

    $result = $form->jsonSerialize();
    $this->assertCount(4, $result);
    $this->assertEquals([
      'form_id'     => 'core_form_validexampleform',
      'form_action' => 'post',
      'form_method' => '',
    ], $result['config'] ?? []);
    $this->assertCount(0, $result['fields'] ?? []);
    $this->assertCount(1, $result['fieldsets'] ?? []);
    $this->assertEmpty($result['errorstack']->getErrors() ?? []);

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

}
