<?php

namespace codename\core\ui;

use codename\core\app;
use codename\core\datacontainer;
use codename\core\errorstack;
use codename\core\exception;
use codename\core\request;
use codename\core\request\filesInterface;
use codename\core\templateengine;
use JsonSerializable;
use ReflectionException;

use function array_column;
use function count;
use function in_array;
use function is_array;

/**
 * Forms can either hold instances of \codename\core\ui\field or \codename\core\ui\fieldset
 * Frontend resources are being used here. Create the file paths in your application to override them
 * @package core
 * @since 2016-01-11
 * @todo create and implement \codename\core\data
 * @todo centralized view for outputting errors
 */
class form implements JsonSerializable
{
    /**
     * This exception is thrown when you misconfigured the array for the form constructor.
     * @var string
     */
    public const EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID = 'EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID';

    /**
     * This occurs when you try outputting a form that doesn't contain at least one single field instance.
     * @var string
     */
    public const EXCEPTION_OUTPUT_FORMISEMPTY = 'EXCEPTION_OUTPUT_FORMISEMPTY';

    /**
     * This callback is used when your form is not sent at all.
     * e.g. Use it to output the form in a standard view
     * @var string
     */
    public const CALLBACK_FORM_NOT_SENT = 'FORM_NOT_SENT';

    /**
     * This callback is used when the form cannot be validated correctly
     * e.g. use it to display a standard error output for all forms.
     * @var string
     */
    public const CALLBACK_FORM_NOT_VALID = 'FORM_NOT_VALID';

    /**
     * This is the callback that runs, when your form is valid.
     * e.g. use it to store the information by default.
     * @var string
     */
    public const CALLBACK_FORM_VALID = 'FORM_VALID';

    /**
     * This is the callback that runs during/after validation (before finishing it)
     * e.g. to hook into the validation process and run some more validators
     * @var string
     */
    public const CALLBACK_FORM_VALIDATION = 'FORM_VALIDATION';
    /**
     * [EXCEPTION_FORM_VALIDATION_SELECTIVE_UNKNOWN_FIELD_IDS description]
     * @var string
     */
    public const EXCEPTION_FORM_VALIDATION_SELECTIVE_UNKNOWN_FIELD_IDS = "EXCEPTION_FORM_VALIDATION_SELECTIVE_UNKNOWN_FIELD_IDS";
    /**
     * Contains the configuration for the form
     * @var array $config
     */
    public array $config = [];
    /**
     * Contains the form fields for the form
     * @var field[] $fields
     */
    public array $fields = [];
    /**
     * Contains all the fieldsets that will be displayed in the CRUD generator
     * @var fieldset[]
     */
    public array $fieldsets = [];
    /**
     * Contains the errorstack for this form
     * @var errorstack
     */
    public errorstack $errorstack;
    /**
     * determines the output type
     * either false (rendered) or true (pure config)
     * @var bool
     */
    public bool $outputConfig = false;
    /**
     * Contains an array of data (fieldnames as keys, their send value as value)
     * @var datacontainer
     */
    protected datacontainer $data;
    /**
     * Defines what form and field objects shall be used when generating output
     * @var string
     */
    protected string $type = 'default';
    /**
     * Contains a list of callbacks
     * @example form->addCallback(\codename\core\ui\form::CALLBACK_FORM_NOT_SENT, function($form) {});
     * @example form->addCallback(\codename\core\ui\form::CALLBACK_FORM_NOT_VALID, function($form) {});
     * @var array
     */
    protected array $callbacks = [];
    /**
     * [protected description]
     * @var null|field
     */
    protected ?field $formSentField = null;
    /**
     * [protected description]
     * @var null|datacontainer
     */
    protected ?datacontainer $requestData = null;
    /**
     * Defines which template engine to use
     * @var null|templateengine
     */
    protected ?templateengine $templateEngine = null;

    /**
     * Stores the configuration and fields in the instance
     * @param array $data [config]
     * @throws ReflectionException
     * @throws exception
     */
    public function __construct(array $data)
    {
        if (count($errors = app::getValidator('structure_config_form')->reset()->validate($data)) > 0) {
            throw new exception(self::EXCEPTION_CONSTRUCT_CONFIGURATIONINVALID, exception::$ERRORLEVEL_FATAL, $errors);
        }

        $this->data = new datacontainer([]);
        $this->config = $data;
        $this->errorstack = new errorstack("VALIDATION");

        $this->addCallback(form::CALLBACK_FORM_NOT_SENT, function (form $form) {
            app::getResponse()->setData('form', $form->output($this->outputConfig));
        })->addCallback(form::CALLBACK_FORM_NOT_VALID, function (form $form) {
            app::getResponse()->setData('errors', $form->getErrorstack()->getErrors());
            app::getResponse()->setData('context', 'form');
            app::getResponse()->setData('view', 'error');
        });

        return $this;
    }

    /**
     * I will overwrite the $callback for the given $identifier.
     * Please add Callbacks by requiring an instance of \codename\core\ui\form named $form.
     * @param string $identifier
     * @param callable $callback
     * @return form
     * @example ->addCallback(\codename\core\ui\form::CALLBACK_FORM_NOT_SENT, function(\codename\core\ui\form $form) {die('OK!');});
     */
    public function addCallback(string $identifier, callable $callback): form
    {
        $this->callbacks[$identifier] = $callback;
        return $this;
    }

    /**
     * Outputs the form HTML code
     * @param bool $outputConfig [optional: do not render, but output config]
     * @return string|form
     * @throws ReflectionException
     * @throws exception
     */
    public function output(bool $outputConfig = false): string|form
    {
        if (count($this->fields) == 0 && count($this->fieldsets) == 0) {
            throw new exception(self::EXCEPTION_OUTPUT_FORMISEMPTY, exception::$ERRORLEVEL_FATAL, null);
        }

        if ($this->formSentField == null) {
            $this->addField(
                $this->formSentField = new field([
                  'field_name' => 'formSent' . $this->config['form_id'],
                  'field_type' => 'hidden',
                  'field_value' => 1,
                ])
            );
        }

        if ($outputConfig) {
            return $this;
        } else {
            $templateEngine = $this->templateEngine;
            if ($templateEngine == null) {
                $templateEngine = app::getTemplateEngine();
            }

            // override field template engines on a weak basis
            foreach ($this->fields as $field) {
                if ($field->getTemplateEngine() == null) {
                    $field->setTemplateEngine($templateEngine);
                }
            }

            // override fieldset template engines on a weak basis
            foreach ($this->fieldsets as $fieldset) {
                if ($fieldset->getTemplateEngine() == null) {
                    $fieldset->setTemplateEngine($templateEngine);
                }
            }

            return $templateEngine->render('form/' . $this->type . '/form', $this->getData());
        }
    }

    /**
     * Adds the given $field to the form instance
     * @param field $field
     * @param int $position [position where to insert the field; -1 is last, -2 the second last]
     * @return form
     */
    public function addField(field $field, int $position = -1): form
    {
        if ($position !== -1) {
            array_splice($this->fields, $position, 0, [$field]);
        } else {
            $this->fields[] = $field;
        }
        return $this;
    }

    /**
     * [getTemplateEngine description]
     * @return null|templateengine [description]
     */
    public function getTemplateEngine(): ?templateengine
    {
        return $this->templateEngine;
    }

    /**
     * Setter for the templateEngine to use
     * @param templateengine $templateEngine [description]
     * @return form                   [description]
     */
    public function setTemplateEngine(templateengine $templateEngine): form
    {
        $this->templateEngine = $templateEngine;
        return $this;
    }

    /**
     * Returns the errorstack instance of this form
     * @return errorstack
     */
    public function getErrorstack(): errorstack
    {
        return $this->errorstack;
    }

    /**
     * normalizes given data
     * using the form fields in this form
     *
     * @param array $data [description]
     * @return null|array
     * @throws exception
     */
    public function normalizeData(array $data): ?array
    {
        $newdata = [];
        foreach ($this->fields as $field) {
            $key = $field->getConfig()->get('field_name');
            if (array_key_exists($key, $data)) {
                if ($data[$key] && $field->getConfig()->get('field_datatype') === 'structure' && $elementDatatype = $field->getConfig()->get('field_element_datatype')) {
                    // if not an array, make it an array.
                    if (!is_array($data[$key])) {
                        $data[$key] = [$data[$key]];
                    }
                    $normalizedValue = array_map(function ($element) use ($key, $elementDatatype) {
                        return field::getNormalizedFieldValue($key, $element, $elementDatatype);
                    }, $data[$key]);
                    $newdata[$key] = $normalizedValue;
                } else {
                    $newdata[$key] = field::getNormalizedFieldValue($key, $data[$key] ?? null, $field->getConfig()->get('field_datatype'));
                }
            }
        }
        return count($newdata) > 0 ? $newdata : null;
    }

    /**
     * Setter for the type of output to generate
     * @param string $type
     * @return form
     */
    public function setType(string $type): form
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Sets the identifier for the current form
     * @param string $identifier
     * @return form
     */
    public function setId(string $identifier): form
    {
        $this->config['form_id'] = "core_form_" . $identifier;
        return $this;
    }

    /**
     * sets the form action value in the config
     * @param string $value [description]
     * @return form          [description]
     */
    public function setAction(string $value): form
    {
        $this->config['form_action'] = $value;
        return $this;
    }

    /**
     * Adds a $fieldset to the instance
     * @param fieldset $fieldset
     * @param int $position [position where to insert the fieldset; -1 is last, -2 the second last]
     * @return form
     */
    public function addFieldset(fieldset $fieldset, int $position = -1): form
    {
        if ($position !== -1) {
            array_splice($this->fieldsets, $position, 0, [$fieldset]);
        } else {
            $this->fieldsets[] = $fieldset;
        }
        return $this;
    }

    /**
     * I am a standardized method for working off an existing form instance.
     * By default, the FORM_NOT_SENT callback will use the form template to output it.
     * By default, the FORM_NOT_VALID callback will output the occurred errors using the standard outputs.
     * @return form
     * @throws ReflectionException
     * @throws exception
     */
    public function work(): form
    {
        if (!$this->isSent()) {
            $this->fireCallback(form::CALLBACK_FORM_NOT_SENT);
            return $this;
        }

        if (!$this->isValid()) {
            $this->fireCallback(form::CALLBACK_FORM_NOT_VALID);
            return $this;
        }

        $this->fireCallback(form::CALLBACK_FORM_VALID);

        return $this;
    }

    /**
     * Returns true if the form of the instance has been sent in a previous request.
     * This is determined by checking if the request instance contains the "formSent.$FORMID" field
     * @return bool
     * @throws ReflectionException
     * @throws exception
     */
    public function isSent(): bool
    {
        return app::getInstance('request')->isDefined('formSent' . $this->config['form_id']);
    }

    /**
     * I will try accessing the callback identified by the given $identifier.
     * I will pass the current instance of \codename\core\ui\form to the callback method named $form
     * If the desired callback does not exist, I will do nothing.
     * @param string $identifier
     * @return void
     */
    private function fireCallback(string $identifier): void
    {
        if (array_key_exists($identifier, $this->callbacks)) {
            call_user_func($this->callbacks[$identifier], $this);
        }
    }

    /**
     * Returns true if all fields of the form have been filled correctly.
     * Checks if required fields are filled
     * Also uses the given field_types as validators to check the fields
     * @param array|null $fieldIds
     * @return bool
     * @throws ReflectionException
     * @throws exception
     */
    public function isValid(array $fieldIds = null): bool
    {
        $tFields = $this->fields;

        foreach ($this->fieldsets as $fieldset) {
            $tFields = array_merge($tFields, $fieldset->getFields());
        }

        foreach ($tFields as $field) {
            // skip noninput fields
            if ($field->getConfig()->get('field_noninput') === true) {
                continue;
            }

            // skip internal sent-determination field
            if ($field === $this->formSentField) {
                continue;
            }

            if (is_array($fieldIds)) {
                if (sizeof($fieldIds) > 0) {
                    $currentFieldId = $field->getConfig()->get('field_id');
                    if (in_array($currentFieldId, $fieldIds)) {
                        $index = array_search($currentFieldId, $fieldIds);
                        if ($index !== false) {
                            unset($fieldIds[$index]); // Remove it from the to-be-checked IDs
                        }
                    } else {
                        continue;
                    }
                } else {
                    break;
                }
            } elseif ($field->getConfig()->get('field_ajax') === true) {
                continue;
            }


            $fieldname = $field->getConfig()->get('field_name');

            // Check for existence of the field
            if ($field->isRequired() && !$this->fieldSent($field)) {
                $this->errorstack->addError($fieldname, 'FIELD_NOT_SET');
                continue;
            }

            $fieldtype = $field->getConfig()->get('field_datatype');

            $displaytype = $field->getConfig()->get('field_type');
            if ($displaytype == 'checkbox') {
                $fieldtype = 'boolean';
            }
            if (is_null($fieldtype)) {
                continue;
            }

            if ($displaytype == 'form') {
                $subform = $field->getConfig()->get('form');

                // provide subform-related data to the subform
                if ($subform instanceof form && $this->fieldValue($field) !== null) {
                    $subform->getErrorstack()->reset();
                    $subform->setFormRequest($this->fieldValue($field));
                    if (!$subform->isValid()) {
                        $this->errorstack->addError($fieldname, 'FIELD_INVALID', $subform->getErrorstack()->getErrors());
                    }
                }
            }

            if (($value = $this->fieldValue($field)) != null) {
                $validation = app::getValidator($fieldtype)->reset()->validate($value);
                if (count($validation) > 0) {
                    $this->errorstack->addError($fieldname, 'FIELD_INVALID', $validation);
                } elseif (in_array($displaytype, ['select', 'radiogroup'])) {
                    //
                    // check selected element if field_elements is exists
                    //
                    $fieldElements = $field->getConfig()->get('field_elements');
                    if (is_array($fieldElements) && count($fieldElements) > 0) {
                        $fieldElementValues = array_column($fieldElements, $field->getConfig()->get('field_valuefield') ?? 'value');
                        if (count($fieldElementValues) > 0) {
                            if (is_array($value)) {
                                foreach ($value as $valueElement) {
                                    if (!in_array($valueElement, $fieldElementValues)) {
                                        // error for wrong field value
                                        $this->errorstack->addError($fieldname, 'FIELD_INVALID', $valueElement);
                                        break;
                                    }
                                }
                            } elseif (!in_array($value, $fieldElementValues)) {
                                // error for wrong field value
                                $this->errorstack->addError($fieldname, 'FIELD_INVALID', $value);
                            }
                        }
                    }
                }
            }
        }

        if (is_array($fieldIds) && sizeof($fieldIds) > 0) {
            // some field ids do not exist - $fieldIds has to be of size zero here.
            throw new exception(self::EXCEPTION_FORM_VALIDATION_SELECTIVE_UNKNOWN_FIELD_IDS, exception::$ERRORLEVEL_ERROR, $fieldIds);
        }

        // FIRE ON VALIDATION HOOK
        $this->fireCallback(form::CALLBACK_FORM_VALIDATION);

        return $this->errorstack->isSuccess();
    }

    /**
     * Returns all the fields in the form instance
     * @return field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Returns true if the given $field has been submitted in the las request
     * Uses the request object to find the field's name
     * @param field $field
     * @return bool
     */
    public function fieldSent(field $field): bool
    {
        switch ($field->getConfig()->get('field_type')) {
            case 'file' :
            case 'signature' : // temporary fix for signature fields
                $requestInstance = app::getRequest();
                if ($requestInstance instanceof filesInterface) {
                    return array_key_exists($field->getConfig()->get('field_name'), $requestInstance->getFiles());
                } else {
                    return array_key_exists($field->getConfig()->get('field_name'), $_FILES);
                }
                // no break
            default:
                if ($this->getFormRequest()->isDefined($field->getConfig()->get('field_name'))) {
                    if (is_array($value = $this->getFormRequest()->getData($field->getConfig()->get('field_name'))) && count($value) === 0) {
                        return false;
                    } else {
                        return true;
                    }
                } else {
                    return false;
                }
        }
    }

    /**
     * [getFormRequest description]
     * @return request [description]
     */
    protected function getFormRequest(): datacontainer
    {
        if ($this->requestData == null) {
            $this->setFormRequest(app::getRequest()->getData());
        }
        return $this->requestData;
    }

    /**
     * [setFormRequest description]
     * @param array $requestData [description]
     */
    public function setFormRequest(array $requestData): void
    {
        $this->requestData = new datacontainer($requestData);
    }

    /**
     * Returns the data stored in the form
     * Will return the whole dataset if you don't supply a $key
     * Will return null if the given $key does not exist in the dataset
     * @param string $key
     * @return mixed
     */
    public function getData(string $key = ''): mixed
    {
//        if (is_null($this->data)) {
//            foreach ($this->fields as $field) {
//                $this->data->setData($field->getConfig()->get('field_name'), $this->fieldValue($field));
//            }
//        }
        $data = $this->getFormRequest()->getData();
        if (isset($_FILES)) {
            $requestInstance = app::getRequest();
            if ($requestInstance instanceof filesInterface) {
                $data = array_merge($data, $requestInstance->getFiles());
            } else {
                $data = array_merge($data, $_FILES);
            }
        }
        $this->data->addData($data);
        return $this->data->getData($key);
    }

    /**
     * Returns the given $field instance's value depending on it's datatype
     * @param field $field [description]
     * @return mixed                           [description]
     */
    public function fieldValue(field $field): mixed
    {
        return match ($field->getConfig()->get('field_type')) {
            'checkbox' => $this->getFormRequest()->isDefined($field->getConfig()->get('field_name')),
            'file' => $_FILES[$field->getConfig()->get('field_name')] ?? null,
            default => $this->getFormRequest()->getData($field->getConfig()->get('field_name')),
        };
    }

    /**
     * returns a field instance based on a search for the given field name
     * or null, if not found
     * @param string $fieldName [description]
     * @return field|null
     */
    public function getField(string $fieldName): ?field
    {
        foreach ($this->getFields() as $field) {
            if ($field->getConfig()->get('field_name') == $fieldName) {
                return $field;
            }
        }
        foreach ($this->getFieldsets() as $fieldset) {
            foreach ($fieldset->getFields() as $field) {
                if ($field->getConfig()->get('field_name') == $fieldName) {
                    return $field;
                }
            }
        }
        return null;
    }

    /**
     * Returns the array of fieldsets here
     * @return fieldset[]
     */
    public function getFieldsets(): array
    {
        return $this->fieldsets;
    }

    /**
     * returns a field instance based on a path (as array)
     * or null, if not found
     * @param array $fieldPath [description]
     * @return field|null
     * @throws exception
     */
    public function getFieldRecursive(array $fieldPath): ?field
    {
        $fieldName = array_shift($fieldPath);
        foreach ($this->getFields() as $field) {
            if ($field->getConfig()->get('field_name') == $fieldName) {
                if (count($fieldPath) === 0) {
                    // end reached
                    return $field;
                } elseif ($form = $field->getConfig()->get('form')) {
                    // if we get a property named "form"
                    // dive deeper
                    if ($form instanceof form) {
                        return $form->getFieldRecursive($fieldPath);
                    } else {
                        throw new exception('FORM_GETFIELDRECURSIVE_INVALID_FORM_INSTANCE', exception::$ERRORLEVEL_ERROR, $fieldName);
                    }
                } else {
                    throw new exception('FORM_GETFIELDRECURSIVE_NO_FORM_INSTANCE', exception::$ERRORLEVEL_ERROR, $fieldName);
                }
            }
        }
        foreach ($this->getFieldsets() as $fieldset) {
            foreach ($fieldset->getFields() as $field) {
                if ($field->getConfig()->get('field_name') == $fieldName) {
                    if (count($fieldPath) === 0) {
                        // end reached
                        return $field;
                    } elseif ($form = $field->getConfig()->get('form')) {
                        // if we get a property named "form"
                        // dive deeper
                        if ($form instanceof form) {
                            return $form->getFieldRecursive($fieldPath);
                        } else {
                            throw new exception('FORM_GETFIELDRECURSIVE_INVALID_FORM_INSTANCE', exception::$ERRORLEVEL_ERROR, $fieldName);
                        }
                    } else {
                        throw new exception('FORM_GETFIELDRECURSIVE_NO_FORM_INSTANCE', exception::$ERRORLEVEL_ERROR, $fieldName);
                    }
                }
            }
        }
        return null;
    }

    /**
     * {@inheritDoc}
     * custom serialization to allow bare config form output
     */
    public function jsonSerialize(): mixed
    {
        return [
          'config' => $this->config,
          'fields' => $this->fields,
          'fieldsets' => $this->fieldsets,
          'errorstack' => $this->errorstack,
        ];
    }
}
