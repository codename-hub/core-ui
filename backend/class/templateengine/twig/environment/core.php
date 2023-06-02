<?php

namespace codename\core\ui\templateengine\twig\environment;

use codename\core\exception;
use Twig\Environment;
use Twig\Error\LoaderError;

/**
 * Overrides some core functions of the Twig Environment
 * As the original developers did not foresee some general use cases
 * This class enables us to vary the template class prefix
 * and allow multiple instances of Twig to be used independently
 * */
class core extends Environment
{
    /**
     * Prefix used for generated template classes prefix.
     * Sounds a little redundant, doesn't it?
     * @var null|string
     */
    protected ?string $templateClassPrefixPrefix = null;

    /**
     * [setTemplateClassPrefixPrefix description]
     * @param string $prefix [description]
     * @throws exception
     */
    public function setTemplateClassPrefixPrefix(string $prefix): void
    {
        if ($this->templateClassPrefixPrefix == null) {
            $this->templateClassPrefixPrefix = '__' . $prefix . '_';
        } else {
            throw new exception('EXCEPTION_CORE_UI_TEMPLATEENGINE_TWIG_ENVIRONMENT_CANNOT_CHANGE_TEMPLATE_CLASS_PREFIX_PREFIX', exception::$ERRORLEVEL_FATAL);
        }
    }

    /**
     * {@inheritDoc}
     * @param $name
     * @param null $index
     * @return string
     * @throws LoaderError
     */
    public function getTemplateClass($name, $index = null): string
    {
        return ($this->templateClassPrefixPrefix ?? '') . parent::getTemplateClass($name, $index);
    }
}
