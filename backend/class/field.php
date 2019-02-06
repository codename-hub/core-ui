<?php
namespace codename\core\ui;
use \codename\core\app;
use codename\core\exception;

/**
 * Instances of this class are utilized by the \codename\core\ui\form class for displaying and validating input data
 * @package core
 * @since 2016-02-06
 * @todo add functions to manage lists (listAddelement, listSetelements, listGetelements)
 */
class field implements \JsonSerializable {

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
     * @param   bool          $outputConfig   [optional: do not render, but output config]
     * @return  string|array
     */
    public function output (bool $outputConfig = false) {
        $data = $this->config->get();
        // @TODO: this may be used to check other array keys for callables, too.
        // this NEEDS to check for string or function first, as we may have a value called 'Max' ...
        if(!is_string($data['field_value']) && is_callable($data['field_value'])) {
          // Replace field_value with its callable result
          $data['field_value'] = $data['field_value']();
        }
        if(isset($data['field_elements']) && !is_string($data['field_elements']) && is_callable($data['field_elements'])) {
          // Replace field_value with its callable result
          $data['field_elements'] = $data['field_elements']();
        }

        // re-structure field configuration for output
        if(!empty($data['field_elements'])) {
          $renderedElements = [];
          foreach($data['field_elements'] as $element) {
            $ret = null;
            eval('$ret = "' . $data['field_displayfield'] . '";');

            if(isset($data['field_idfield'])) {
              $rendered = [
                'id'    => $element[$data['field_idfield']],
                'name'  => $ret,
                'value' => $element[$data['field_valuefield']]
              ];
            } else {
              $rendered = [
                'name' => $ret,
                'value' => $element[$data['field_valuefield']]
              ];
            }
            $renderedElements[] = $rendered;
          }
          $data['field_elements'] = $renderedElements;
          $data['field_valuefield'] = 'value';
          $data['field_displayfield'] = 'name';
          if(isset($data['field_idfield'])) {
            $data['field_idfield'] = 'id';
          } else {

          }
        }

        // normalize field value at output time
        // which may be the serialization as JSON
        $data['field_value'] = self::getNormalizedFieldValue($data['field_name'], $data['field_value'], $data['field_datatype']);

        if($outputConfig) {

          // bare config
          return $data;

        } else {

          // render
          $templateEngine = $this->templateEngine;

          // Fallback to default engine, if nothing set
          if($this->templateEngine == null) {
            $templateEngine = app::getTemplateEngine();
          }

          return $templateEngine->render('field/' . $this->type . '/' . $this->config->get('field_type'), $data);

        }
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
            $field['field_placeholder'] = $field['field_title'] ?? '';
        }

        if(!array_key_exists('field_value', $field)) {
          $field['field_value'] = null;
        }

        if(!array_key_exists('field_datatype', $field)) {
          $field['field_datatype'] = ($field['field_type'] == 'submit' ? null : 'text');
        }

        //
        // if field_datatype is set (not null), perform field_value normalization
        //
        if($field['field_datatype']) {
          $field['field_value'] = self::getNormalizedFieldValue($field['field_name'], $field['field_value'], $field['field_datatype']);
        }

        if(!array_key_exists('field_validator', $field)) {
            $field['field_validator'] = '';
        }

        if(!array_key_exists('field_description', $field)) {
            $field['field_description'] = '';
        }
        if(!array_key_exists('field_title', $field)) {
            $field['field_title'] = '';
        }
        return $field;
    }

    /**
     * [getNormalizedFieldValue description]
     * @param  string $fieldName [description]
     * @param  [type] $value     [description]
     * @param  [type] $datatype  [description]
     * @return [type]            [description]
     */
    public static function getNormalizedFieldValue(string $fieldName, $value, $datatype) {
      switch($datatype) {
        case 'boolean':
          // pure boolean
          if(is_bool($value)) {
            // dont change. field_value has a valid datatype
            break;
          }
          // int: 0 or 1
          if(is_int($value)) {
            if($value !== 1 && $value !== 0) {
              throw new exception('EXCEPTION_FIELD_NORMALIZEFIELD_BOOLEAN_INVALID', exception::$ERRORLEVEL_ERROR, [
                'field' => $fieldName,
                'value' => $value
              ]);
            }
            $value = $value === 1 ? true : false;
            break;
          }
          // string boolean
          if(is_string($value)) {
            // fallback, empty string
            if(strlen($value) === 0) {
              $value = null;
              break;
            }
            if($value === 'true') {
              $value = true;
              break;
            } elseif ($value === 'false') {
              $value = false;
              break;
            }
          }
        case 'number_natural':
          $value = $value === null ? null : intval($value);
      }
      
      // DEBUG:
      // \codename\core\app::getResponse()->setData('meh debug_'.$fieldName, [
      //   'value' => $value,
      //   'datatype' => $datatype
      // ]);
      //
      return $value;
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

    /**
     * @inheritDoc
     * custom serialization to allow bare config field output
     */
    public function jsonSerialize()
    {
      return $this->output(true);
    }
}
