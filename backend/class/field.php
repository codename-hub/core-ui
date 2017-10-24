<?php
namespace codename\core\ui;
use \codename\core\app;

/**
 * Instances of this class are utilized by the \codename\core\ui\form class for displaying and validating input data
 * @package core
 * @since 2016-02-06
 * @todo add functions to manage lists (listAddelement, listSetelements, listGetelements)
 */
class field {

    CONST EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID = 'EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID';

    /**
     * the type of output to generate
     * @var string $type
     */
    protected $type = 'default';

    /**
     * Contains all the properties that can be loaded into a field
     * @var array
     */
    protected static $properties = array(
            'field_id',
            'field_name',
            'field_title',
            'field_description',
            'field_type',
            'field_required',
            'field_placeholder',
            'field_multiple',
            'field_ajax',
            'field_noninput'
    );

    /**
     * Contains an instance of \codename\core\config
     * @var \codename\core\config
     */
    protected $config = null;

    /**
     * Creates instance of the form class and sets it's data
     * @param array $field
     */
    public function __CONSTRUCT(array $field) {
        // Normalize the field properties
        $field = $this->normalizeField($field);

        if(count($errors = app::getValidator('structure_config_field')->validate($field)) > 0) {
            throw new \codename\core\exception(self::EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID, \codename\core\exception::$ERRORLEVEL_FATAL, $errors);
        }

        // Create config instance
        $this->config = new \codename\core\config($field);

        return $this;
    }

    /**
     * Generates the output for the form and returns it.
     * @return string
     */
    public function output () : string {
        $data = $this->config->get();
        // @TODO: this may be used to check other array keys for callables, too.
        // this NEEDS to check for string or function first, as we may have a value called 'Max' ...
        if(!is_string($data['field_value']) && is_callable($data['field_value'])) {
          // Replace field_value with its callable result
          $data['field_value'] = $data['field_value']();
        }
        if(isset($data['field_elements']) && !is_string($data['field_elements']) && is_callable($data['field_value'])) {
          // Replace field_value with its callable result
          $data['field_elements'] = $data['field_elements']();
        }

        $templateEngine = $this->templateEngine;

        // Fallback to default engine, if nothing set
        if($this->templateEngine == null) {
          $templateEngine = app::getTemplateEngine();
        }

        return $templateEngine->render('field/' . $this->type . '/' . $this->config->get('field_type'), $data);
    }

    /**
     * Setter for the type of output to generate
     * @param string $type
     * @return field
     * @todo REFACTOR OUT
     */
    public function setType(string $type) : field {
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
     * @return field                   [description]
     */
    public function setTemplateEngine(\codename\core\templateengine $templateEngine) : field {
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
     * Returns the list of properties that can exist in a field instance
     * @return array
     */
    public static function getProperties() : array {
        return self::$properties;
    }

    /**
     * Returns the config instance of this field
     * @return \codename\core\config
     */
    public function getConfig() : \codename\core\config {
        return $this->config;
    }

    /**
     * Returns true if the field is required
     * @return bool
     */
    public function isRequired() : bool {
        return $this->config->get('field_required');
    }

    /**
     * Sets defaults to the missing array keys
     * @param array $field
     * @return array
     */
    protected function normalizeField(array $field) : array {
        if(!array_key_exists('field_id', $field)) {
            $field['field_id'] = str_replace(array('[', ']'), '___', $field['field_name']);
        }

        if(!array_key_exists('field_fieldtype', $field) || strlen($field['field_fieldtype']) == 0) {
            $field['field_fieldtype'] = 'input';
        }

        if(!array_key_exists('field_class', $field) || strlen($field['field_class']) == 0) {
            $field['field_class'] = 'input';
        }

        if(!array_key_exists('field_required', $field)) {
            $field['field_required'] = false;
        }

        if(!array_key_exists('field_readonly', $field)) {
            $field['field_readonly'] = false;
        }

        if(!array_key_exists('field_ajax', $field)) {
            $field['field_ajax'] = false;
        }

        if(!array_key_exists('field_noninput', $field)) {
            $field['field_noninput'] = false;
        }

        if(!array_key_exists('field_placeholder', $field)) {
            $field['field_placeholder'] = '';
        }

        if(!array_key_exists('field_datatype', $field)) {
            $field['field_datatype'] = ($field['field_type'] == 'submit' ? null : 'text');
        }

        if(!array_key_exists('field_validator', $field)) {
            $field['field_validator'] = '';
        }

        if(!array_key_exists('field_description', $field)) {
            $field['field_description'] = '';
        }

        if(!array_key_exists('field_value', $field)) {
            $field['field_value'] = null;
        }
        if(!array_key_exists('field_title', $field)) {
            $field['field_title'] = '';
        }
        return $field;
    }

    /**
     * Returns a property from the instance's configuration object
     * @param string $property
     * @return mixed
     * @deprecated
     */
    public function getProperty(string $property) {
        return $this->config->get($property);
    }

    /**
     * Sets a property in the instance's configuration object
     * @param string $property
     * @return mixed
     * @deprecated
     */
    public function setProperty(string $property, $value) {
        return $this->config->set($property, $value);
    }

    /**
     * overwrites the field_value
     * by creating a fresh internal field config
     * @param mixed $value
     */
    public function setValue($value) {
      $cfg = $this->config->get();
      $cfg['field_value'] = $value;
      $this->config = new \codename\core\config($cfg);
      return;
    }
}
