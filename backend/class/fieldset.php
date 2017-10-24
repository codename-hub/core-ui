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
class fieldset {

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
     */
    public function addField(field $field) : fieldset {
        array_push($this->data['fields'], $field);
        return $this;
    }

    /**
     * Will output the fieldset's content
     * @return string
     */
    public function output() : string {
        return app::getTemplateEngine($this->templateEngine)->render('fieldset/' . $this->type, $this->getData());
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
     * @var string
     */
    protected $templateEngine = 'default';

    /**
     * Setter for the templateEngine to use
     * @param  string $templateEngine [description]
     * @return fieldset               [description]
     */
    public function setTemplateEngine(string $templateEngine) : fieldset {
      $this->templateEngine = $templateEngine;
      return $this;
    }

    /**
     * @return \codename\core\ui\field[]
     */
    public function getFields() : array {
      return $this->data['fields'];
    }

}
