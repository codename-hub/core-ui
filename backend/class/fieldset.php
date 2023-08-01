<?php

namespace codename\core\ui;

use codename\core\app;
use codename\core\exception;
use codename\core\templateengine;
use JsonSerializable;
use ReflectionException;

/**
 * Fieldsets are holding instances of \codename\core\ui\field. They will be displayed as HTML fieldsets
 * It utilizes several frontend resources located in the core frontend folder.
 * Override these templates by adding these files in your application's directory
 * @package core
 * @since 2016-03-17
 */
class fieldset implements JsonSerializable
{
    /**
     * Defines which template engine to use
     * @var null|templateengine
     */
    protected ?templateengine $templateEngine = null;
    /**
     * Contains the display type of the instance
     * @var string $type
     */
    private string $type = 'default';
    /**
     * Contains all the data for the form element
     * @var array $data
     * @todo make instance of datacontainer?
     */
    private array $data;

    /**
     * Creates a fieldset. A fieldset belongs into a form and contains multiple instances of \codename\core\ui\field
     * @param array $fieldset
     * @throws ReflectionException
     * @throws exception
     */
    public function __construct(array $fieldset)
    {
        $fieldset['fieldset_id'] = $fieldset['fieldset_id'] ?? $fieldset['fieldset_name'] ?? null;
        if (isset($fieldset['fieldset_name_override'])) {
            $fieldset['fieldset_name'] = $fieldset['fieldset_name_override'];
        } else {
            $fieldset['fieldset_name'] = app::getTranslate()->translate('DATAFIELD.FIELDSET_' . $fieldset['fieldset_name']);
        }
        $this->data = $fieldset;
        if (!array_key_exists('fields', $this->getData())) {
            $this->data['fields'] = [];
        }
    }

    /**
     * Returns the instance's data array
     * @return array
     */
    protected function getData(): array
    {
        return $this->data;
    }

    /**
     * Adds a field to the data array of this instance
     * @param field $field
     * @param int $position [position where to insert the field; -1 is last, -2 the second last]
     * @return fieldset
     */
    public function addField(field $field, int $position = -1): fieldset
    {
        if ($position !== -1) {
            array_splice($this->data['fields'], $position, 0, [$field]);
        } else {
            $this->data['fields'][] = $field;
        }
        return $this;
    }

    /**
     * Setter for the type of output to generate
     * @param string $type
     * @return fieldset
     */
    public function setType(string $type): fieldset
    {
        $this->type = $type;
        return $this;
    }

    /**
     * {@inheritDoc}
     * custom serialization to allow bare config field output
     * @return mixed
     * @throws ReflectionException
     * @throws exception
     */
    public function jsonSerialize(): mixed
    {
        return $this->output(true);
    }

    /**
     * Will output the fieldset's content
     * @param bool $outputConfig [optional: do not render, but output config]
     * @return string|array
     * @throws ReflectionException
     * @throws exception
     */
    public function output(bool $outputConfig = false): string|array
    {
        if ($outputConfig) {
            // just data for pure-data output (e.g. serializer)
            return $this->data;
        } else {
            $templateEngine = $this->templateEngine;
            if ($templateEngine == null) {
                $templateEngine = app::getTemplateEngine();
            }

            // override field template engines on a weak basis
            foreach ($this->getFields() as $field) {
                if ($field->getTemplateEngine() == null) {
                    $field->setTemplateEngine($templateEngine);
                }
            }

            return $templateEngine->render('fieldset/' . $this->type, $this->getData());
        }
    }

    /**
     * [getTemplateEngine description]
     * @return templateengine|null [description]
     */
    public function getTemplateEngine(): ?templateengine
    {
        return $this->templateEngine;
    }

    /**
     * Setter for the templateEngine to use
     * @param templateengine $templateEngine [description]
     * @return fieldset                   [description]
     */
    public function setTemplateEngine(templateengine $templateEngine): fieldset
    {
        $this->templateEngine = $templateEngine;
        return $this;
    }

    /**
     * @return field[]
     */
    public function getFields(): array
    {
        return $this->data['fields'];
    }
}
