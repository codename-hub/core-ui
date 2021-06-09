<?php
namespace codename\core\ui\templateengine\twig\template;

/**
 * Provides integration of the core frameworks routing component with Twig.
 * */
abstract class baseTemplate extends \Twig\Template
{
  /**
   * @inheritDoc
   */
  protected function loadTemplate(
    $template,
    $templateName = null,
    $line = null,
    $index = null
  ) {

    if (substr($template, 0, 2) === './') {

      /**
       * f.e.
       * template: ./subview.twig.html
       * templateName: view/twigcontext/default.twig
       *
       * 1. strip the last component of templateName away
       * 2. use as prefix for template
       * 3. go for it!
       */

       $template = dirname($templateName) . ltrim($template, '.');
    }
    return parent::loadTemplate($template, $templateName, $line, $index);
  }
  
}