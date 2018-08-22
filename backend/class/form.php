<?php
namespace codename\core\ui;
use \codename\core\app;

/**
 * Forms can either hold instances of \codename\core\ui\field or \codename\core\ui\fieldset
 * Frontend resources are being used here. Create the file paths in your application to override them
 * @package core
 * @since 2016-01-11
 * @todo create and implement \codename\core\data
 * @todo centralized view for outputting errors
 */
class form implements \JsonSerializable {

    /**
     * This exception is thrown when you misconfigured the array for the form constructor.
     * @var string
     */
    CONST EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID = 'EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID';

    /**
     * This occurs when you try outputting a form that doesn't contain at least one single field instance.
     * @var string
     */
    CONST EXCEPTION_OUTPUT_FORMISEMPTY = 'EXCEPTION_OUTPUT_FORMISEMPTY';

    /**
     * This callback is used when your form is not sent at all.
     * <br />e.g. Use it to output the form in a standard view
     * @var string
     */
    CONST CALLBACK_FORM_NOT_SENT = 'FORM_NOT_SENT';

    /**
     * This callback is used when the form cannot be validated correctly
     * <br />e.g. use it to display a standard error output for all forms.
     * @var string
     */
    CONST CALLBACK_FORM_NOT_VALID = 'FORM_NOT_VALID';

    /**
     * This is the callback that runs, when your form is valid.
     * <br />e.g. use it to store the information by default.
     * @var string
     */
    CONST CALLBACK_FORM_VALID = 'FORM_VALID';

    /**
     * This is the callback that runs during/after validation (before finishing it)
     * <br />e.g. to hook into the validation process and run some more validators
     * @var string
     */
    CONST CALLBACK_FORM_VALIDATION = 'FORM_VALIDATION';


    /**
     * Contains the configuration for the form
     * @var array $config
     * @todo implement \codename\core\config
     */
    public $config = array();

    /**
     * Contains the form fields for the form
     * @var \codename\core\ui\field[] $fields
     */
    public $fields = array();

    /**
     * Contains an array of data (fieldnames as keys, their sent value as value)
     * @todo make this an instance of (idea) \codename\core\data
     * @var \codename\core\datacontainer
     */
    protected $data = null;

    /**
     * Defines what form and field objects shall be used when generating output
     * @var string
     */
    protected $type = 'default';

    /**
     * Contains all the fieldsets that will be displayed in the CRUD generator
     * @var \codename\core\ui\fieldset[]
     */
    public $fieldsets = array();

    /**
     * Contains the errorstack for this form
     * @var \codename\core\errorstack
     */
    public $errorstack = null;

    /**
     * determines the output type
     * either false (rendered) or true (pure config)
     * @var bool
     */
    public $outputConfig = false;

    /**
     * Contains a list of callbacks
     * @example form->addCallback(\codename\core\ui\form::CALLBACK_FORM_NOT_SENT, function($form) {});
     * @example form->addCallback(\codename\core\ui\form::CALLBACK_FORM_NOT_VALID, function($form) {});
     * @var array
     */
    protected $callbacks = array();

    /**
     * Stores the configuration and fields in the instance
     * @param array $data [config]
     * @return form
     */
    public function __CONSTRUCT(array $data) {
        if (count($errors = app::getValidator('structure_config_form')->validate($data)) > 0) {
            throw new \codename\core\exception(self::EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID, \codename\core\exception::$ERRORLEVEL_FATAL, $errors);
        }

        $this->data = new \codename\core\datacontainer(array());
        $this->config = $data;
        $this->errorstack = new \codename\core\errorstack("VALIDATION");

        // if(!isset($this->config['form_text_requiredfields'])) {
        //   $this->config['form_text_requiredfields'] = app::translate('CRUD.REQUIREDFIELDS');
        // }

        $this->addCallback(\codename\core\ui\form::CALLBACK_FORM_NOT_SENT, function(\codename\core\ui\form $form) {
            app::getResponse()->setData('form', $form->output($this->outputConfig));
            return;
        })->addCallback(\codename\core\ui\form::CALLBACK_FORM_NOT_VALID, function(\codename\core\ui\form $form) {
            app::getResponse()->setData('errors', $form->getErrorstack()->getErrors());
            app::getResponse()->setData('context', 'form');
            app::getResponse()->setData('view', 'error');
            return;
        });

        return $this;
    }

    /**
     * [protected description]
     * @var field
     */
    protected $formSentField = null;

    /**
     * Outputs the form HTML code
     * @param  bool          $outputConfig   [optional: do not render, but output config]
     * @return string|array
     */
    public function output(bool $outputConfig = false) {
        if(count($this->fields) == 0 && count($this->fieldsets) == 0) {
            throw new \codename\core\exception(self::EXCEPTION_OUTPUT_FORMISEMPTY, \codename\core\exception::$ERRORLEVEL_FATAL, null);
        }

        if($this->formSentField == null) {
          $this->addField($this->formSentField = new field(array(
                  'field_name' => 'formSent' . $this->config['form_id'],
                  'field_type' => 'hidden',
                  'field_value' => 1
          )));
        }

        if($outputConfig) {
          return $this;
        } else {
          $templateEngine = $this->templateEngine;
          if($templateEngine == null) {
            $templateEngine = app::getTemplateEngine();
          }

          // override field template engines on a weak basis
          foreach($this->fields as $field) {
            if($field->getTemplateEngine() == null) {
              $field->setTemplateEngine($templateEngine);
            }
          }

          // override fieldset template engines on a weak basis
          foreach($this->fieldsets as $fieldset) {
            if($fieldset->getTemplateEngine() == null) {
              $fieldset->setTemplateEngine($templateEngine);
            }
          }

          return $templateEngine->render('form/' . $this->type . '/form', $this);
        }
    }

    /**
     * Returns true if the form of the instance has been sent in a previous request.
     * <br />This is determined by checking if the request instance contains the "formSent.$FORMID" field
     * @return bool
     */
    public function isSent() : bool {
        return app::getInstance('request')->isDefined('formSent' . $this->config['form_id']);
    }

    /**
     * Returns true if all fields of the form have been filled correctly.
     * <br />Checks if required fields are filled
     * <br />Also uses the given field_types as validators to check the fields
     * @return bool
     */
    public function isValid(array $fieldIds = null) : bool {

        $tFields = $this->fields;

        foreach($this->fieldsets as $fieldset) {
          $tFields = array_merge($tFields, $fieldset->getFields());
        }

        foreach($tFields as $field) {

          // skip noninput fields
          if($field->getConfig()->get('field_noninput') === true) {
            continue;
          }

          // skip internal sent-determination field
          if($field === $this->formSentField) {
            continue;
          }

          if($fieldIds !== null && is_array($fieldIds)) {
            if(sizeof($fieldIds) > 0) {
              $currentFieldId = $field->getConfig()->get('field_id');
              if(in_array($currentFieldId, $fieldIds)) {
                $index = array_search($currentFieldId, $fieldIds);
                if($index !== FALSE) {
                  unset($fieldIds[$index]); // Remove it from the to-be-checked IDs
                }
              } else {
                continue;
              }
            } else {
              break;
            }
          } else {
            if($field->getConfig()->get('field_ajax') === true) {
              continue;
            }
          }



          $fieldname = $field->getConfig()->get('field_name');

          // Check for existance of the field
          if($field->isRequired() && !$this->fieldSent($field) ) {
              $this->errorstack->addError($fieldname, 'FIELD_NOT_SET');
              continue;
          }

          $fieldtype = $field->getConfig()->get('field_datatype');

          $displaytype = $field->getConfig()->get('field_type');
          if($displaytype == 'checkbox') {
              $fieldtype = 'boolean';
          }
          if(is_null($fieldtype)) {
              continue;
          }

          if($displaytype == 'form') {
            $subform = $field->getProperty('form');

            // provide subform-related data to the subform
            if($subform instanceof \codename\core\ui\form) {
              $subform->setFormRequest($this->fieldValue($field));
            }
            if(!$subform->isValid()) {
              $this->errorstack->addError($fieldname, 'FIELD_INVALID', $subform->getErrorstack()->getErrors());
            }
            continue;
          }

          if(($value = $this->fieldValue($field)) != null) {
            $validation = app::getValidator($fieldtype)->reset()->validate($value);
            if(count($validation) != 0) {
                $this->errorstack->addError($fieldname, 'FIELD_INVALID', $validation);
            }
          }
      }

      if($fieldIds !== NULL && is_array($fieldIds) && sizeof($fieldIds) > 0) {
        // some field ids do not exist - $fieldIds has to be of size zero here.
        throw new \codename\core\exception(self::EXCEPTION_FORM_VALIDATION_SELECTIVE_UNKNOWN_FIELD_IDS, \codename\core\exception::$ERRORLEVEL_ERROR, $fieldIds);
      }

      // FIRE ON VALIDATION HOOK
      $this->fireCallback(\codename\core\ui\form::CALLBACK_FORM_VALIDATION);

      return $this->errorstack->isSuccess();
    }

    /**
     * [EXCEPTION_FORM_VALIDATION_SELECTIVE_UNKNOWN_FIELD_IDS description]
     * @var string
     */
    const EXCEPTION_FORM_VALIDATION_SELECTIVE_UNKNOWN_FIELD_IDS = "EXCEPTION_FORM_VALIDATION_SELECTIVE_UNKNOWN_FIELD_IDS";

    /**
     * Returns true if the given $field has been submitted in the las request
     * <br />Uses the request object to find the fields's name
     * @param \codename\core\ui\field $field
     * @return bool
     */
    public function fieldSent(\codename\core\ui\field $field) : bool {
        switch ($field->getProperty('field_type')) {
            case 'file' :
                if(app::getRequest() instanceof \codename\core\request\filesInterface) {
                  return array_key_exists($field->getProperty('field_name'), app::getRequest()->getFiles());
                } else {
                  return array_key_exists($field->getProperty('field_name'), $_FILES);
                }
                break;
            default:
                $request = $this->getFormRequest()->getData($field->getProperty('field_name'));
                if(is_array($request)) {
                  return sizeof($request) > 0;
                } else {
                  return (strlen($request) > 0);
                }
                break;
        }
        return false;
    }

    /**
     * [protected description]
     * @var \codename\core\datacontainer
     */
    protected $requestData = null;

    /**
     * [getFormRequest description]
     * @return \codename\core\request [description]
     */
    protected function getFormRequest() : \codename\core\datacontainer {
      if($this->requestData == null) {
        $this->setFormRequest(app::getRequest()->getData());
      }
      return $this->requestData;
    }

    /**
     * [setFormRequest description]
     * @param array $requestData [description]
     */
    public function setFormRequest(array $requestData) {
      $this->requestData = new \codename\core\datacontainer( $requestData );
    }

    /**
     * Returns the given $field instance's value depending on it's datatype
     * @param  \codename\core\ui\field $field  [description]
     * @return mixed                           [description]
     */
    public function fieldValue(\codename\core\ui\field $field) {
        switch ($field->getProperty('field_type')) {
            case 'checkbox' :
                return $this->getFormRequest()->isDefined($field->getProperty('field_name'));
                break;
            case 'file' :
                return $_FILES[$field->getProperty('field_name')] ?? null;
                break;
            default:
                return $this->getFormRequest()->getData($field->getProperty('field_name'));
                break;
        }
        return null;
    }

    /**
     * Returns the errorstack instance of this form
     * @return \codename\core\errorstack
     */
    public function getErrorstack() : \codename\core\errorstack {
        return $this->errorstack;
    }

    /**
     * Returns the data stored in the form
     * <br />Will return the whole dataset if you don't supply a $key
     * <br />Will return null if the given $key does not exist in the dataset
     * @return multitype
     */
    public function getData(string $key = '') {
        if(is_null($this->data)) {
            foreach($this->fields as $field) {
                $this->data->setData($field->getConfig()->get('field_name'), $this->fieldValue($field));
            }
        }
        $data = $this->getFormRequest()->getData();
        if(isset($_FILES)) {
          if(app::getRequest() instanceof \codename\core\request\filesInterface) {
            $data = array_merge($data, app::getRequest()->getFiles());
          } else {
            $data = array_merge($data, $_FILES);
          }
        }
        $this->data->addData($data);
        return $this->data->getData($key);
    }

    /**
     * Setter for the type of output to generate
     * @param string $type
     * @return form
     */
    public function setType(string $type) : form {
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
     * @return form                   [description]
     */
    public function setTemplateEngine(\codename\core\templateengine $templateEngine) : form {
      $this->templateEngine = $templateEngine;
      return $this;
    }

    /**
     * [getTemplateEngine description]
     * @return \codename\core\templateengine\null [description]
     */
    public function getTemplateEngine() : ?\codename\core\templateengine {
      return $this->templateEngine;
    }

    /**
     * Adds the given $field to the form instance
     * @param field $field
     * @return form
     */
    public function addField(field $field) : form {
        array_push($this->fields, $field);
        return $this;
    }

    /**
     * Returns all the fields in the form instance
     * @return array
     */
    public function getFields() : array {
        return $this->fields;
    }

    /**
     * Sets the identifier for the current form
     * @param string $identifier
     * @return form
     */
    public function setId(string $identifier) : form {
        $this->config['form_id'] = "core_form_" . $identifier;
        return $this;
    }

    /**
     * Adds a $fieldset to the instance
     * @param fieldset $fieldset
     * @return form
     */
    public function addFieldset(fieldset $fieldset) : form {
        $this->fieldsets[] = $fieldset;
        return $this;
    }

    /**
     * Returns the array of fieldsets here
     * @return array
     */
    public function getFieldsets() : array {
        return $this->fieldsets;
    }

    /**
     * I will overwrite the $callback for the given $identifier.
     * <br />Please add Callbacks by requiring an instance of \codename\core\ui\form named $form.
     * @example ->addCallback(\codename\core\ui\form::CALLBACK_FORM_NOT_SENT, function(\codename\core\ui\form $form) {die('OK!');});
     * @return \codename\core\ui\form
     */
    public function addCallback(string $identifier, callable $callback) : \codename\core\ui\form {
        $this->callbacks[$identifier] = $callback;
        return $this;
    }

    /**
     * I will try accessing the callback identified by the given $identifier.
     * <br />I will pass the current instance of \codename\core\ui\form to the callback method named $form
     * <br />If the desired callback does not exist, I will do nothing.
     * @return \codename\core\ui\form
     */
    private function fireCallback(string $identifier) : \codename\core\ui\form {
        if(array_key_exists($identifier, $this->callbacks)) {
            call_user_func($this->callbacks[$identifier], $this);
        }
        return $this;
    }

    /**
     * I am a standardized method for working off an existing form instance.
     * <br />By default, the FORM_NOT_SENT callback will use the form template to output it.
     * <br />By default, the FORM_NOT_VALID callback will output the occured errors using the standard outputs.
     * @return \codename\core\ui\form
     */
    public function work() : \codename\core\ui\form {
        if(!$this->isSent()) {
            $this->fireCallback(\codename\core\ui\form::CALLBACK_FORM_NOT_SENT);
            return $this;
        }

        if(!$this->isValid()) {
            $this->fireCallback(\codename\core\ui\form::CALLBACK_FORM_NOT_VALID);
            return $this;
        }

        $this->fireCallback(\codename\core\ui\form::CALLBACK_FORM_VALID);

        return $this;
    }

    /**
     * @inheritDoc
     * custom serialization to allow bare config form output
     */
    public function jsonSerialize()
    {
      return [
        'config' => $this->config,
        'fields' => $this->fields,
        'fieldsets' => $this->fieldsets,
        'errorstack' => $this->errorstack
      ];
    }

}
