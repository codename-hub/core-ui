<?php
namespace codename\core\ui\templateengine\twig\environment;
use \Twig\Environment;
use \codename\core\exception;

/**
 * Overrides some core functions of the Twig Environment
 * As the original developers did not foresee some general use cases
 * This class enables us to vary the template class prefix
 * and allow multiple instances of Twig to be used independently
 * */
class core extends \Twig\Environment {

  /**
   * Prefix used for generated template classes prefix.
   * Sounds a little bit redundant, doesn't it?
   * @var string
   */
  protected $templateClassPrefixPrefix = null;

  /**
   * [setTemplateClassPrefixPrefix description]
   * @param string $prefix [description]
   */
  public function setTemplateClassPrefixPrefix(string $prefix) {
    if($this->templateClassPrefixPrefix == null) {
      $this->templateClassPrefixPrefix = '__'.$prefix.'_';
    } else {
      throw new exception('EXCEPTION_CORE_UI_TEMPLATEENGINE_TWIG_ENVIRONMENT_CANNOT_CHANGE_TEMPLATE_CLASS_PREFIX_PREFIX', exception::$ERRORLEVEL_FATAL);
    }
  }

  /**
   * @inheritDoc
   */
  public function getTemplateClass($name, $index = null)
  {
    return ($this->templateClassPrefixPrefix ?? '').parent::getTemplateClass($name, $index);
  }
}