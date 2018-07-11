<?php
namespace codename\core\ui\templateengine\twig\loader;

/**
 * [filesystem description]
 */
class filesystem extends \Twig\Loader\FilesystemLoader {

  /**
   * default template file suffix (e.g. .twig)
   * @var [type]
   */
  public $templateFileSuffix = '';

  /**
   * @inheritDoc
   */
  protected function findTemplate($name, $throw = true)
  {
    return parent::findTemplate($name . $this->templateFileSuffix, $throw);
  }
}
