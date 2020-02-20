<?php
namespace codename\core\ui;
use \codename\core\app;

/**
 * Fieldsets are holding instances of \codename\core\ui\field. They will be displayed as HTML fieldsets
 * It utilizes several frontend resources located in the core frontend folder.
 * <br />Override these templates by adding these files in your application's directory
 * @package core
 * @since 2016-03-17
 */
class fieldset implements \JsonSerializable {

    /**
     * Contains the display type of the instance
     * @var string $type
     */
    private $type = 'default';

    /**
     * Contains all the data for the form element
     * @var array $data
     * @todo make instance of datacontainer?
     */
    private $data = array();

    /**
     * Creates a fieldset. A fieldset belongs into a form and contains multiple instances of \codename\core\ui\field
     * @param array $fieldset
     * @return fieldset
     */
    public function __CONSTRUCT(array $fieldset) {
        $fieldset['fieldset_id'] = $fieldset['fieldset_id'] ?? $fieldset['fieldset_name'];
        if(isset($fieldset['fieldset_name_override'])) {
          $fieldset['fieldset_name'] = $fieldset['fieldset_name_override'];
        } else {
          $fieldset['fieldset_name'] = app::getTranslate()->translate('DATAFIELD.FIELDSET_' . $fieldset['fieldset_name']);
        }
        $this->data = $fieldset;
        if(!array_key_exists('fields', $this->getData())) {
            $this->data['fields'] = array();
        }
    }

    /**
     * Adds a field to the data array of this instance
     * @param field $field
     * @param int   $position [position where to insert the field; -1 is last, -2 the second last]
     * @return fieldset
     */
    public function addField(field $field, int $position = -1) : fieldset {
      if($field->getProperty('field_name') === '__mandate_customer_creation') {
        \codename\core\app::getResponse()->setData('fields_before', $this->data['fields']);
        \codename\core\app::getResponse()->setData('fields_position!', $position);
      }
      if($position !== -1) {
        array_splice($this->data['fields'], $position, 0, [ $field ]);
        if($field->getProperty('field_name') === '__mandate_customer_creation') {
          \codename\core\app::getResponse()->setData('fields_after', $this->data['fields']);
        }
        return $this;
      } else {
        array_push($this->data['fields'], $field);
        return $this;
      }
    }

    /**
     * Will output the fieldset's content
     * @param   bool          $outputConfig   [optional: do not render, but output config]
     * @return string|array
     */
    public function output(bool $outputConfig = false) {
      if($outputConfig) {
        // just data for pure-data output (e.g. serializer)
        return $this->data;
      } else {
        $templateEngine = $this->templateEngine;
        if($templateEngine == null) {
          $templateEngine = app::getTemplateEngine();
        }

        // override field template engines on a weak basis
        foreach($this->getFields() as $field) {
          if($field->getTemplateEngine() == null) {
            $field->setTemplateEngine($templateEngine);
          }
        }

        return $templateEngine->render('fieldset/' . $this->type, $this->getData());
      }
    }

    /**
     * Returns the instance's data array
     * @return array
     */
    protected function getData() : array {
        return $this->data;
    }

    /**
     * Setter for the type of output to generate
     * @author Kevin Dargel
     * @param string $type
     * @return fieldset
     */
    public function setType(string $type) : fieldset {
        $this->type = $type;
        return $this;
    }

    /**
     * Defines which template engine to use
     * @var \codename\core\templateengine
     */
    protected $templateEngine = null;

    /**
     * Setter for the templateEngine to use
     * @param  \codename\core\templateengine $templateEngine [description]
     * @return fieldset                   [description]
     */
    public function setTemplateEngine(\codename\core\templateengine $templateEngine) : fieldset {
      $this->templateEngine = $templateEngine;
      return $this;
    }

    /**
     * [getTemplateEngine description]
     * @return \codename\core\templateengine|null [description]
     */
    public function getTemplateEngine() : ?\codename\core\templateengine {
      return $this->templateEngine;
    }

    /**
     * @return \codename\core\ui\field[]
     */
    public function getFields() : array {
      return $this->data['fields'];
    }

    /**
     * @inheritDoc
     * custom serialization to allow bare config field output
     */
    public function jsonSerialize()
    {
      return $this->output(true);
    }

}
