<?php
namespace codename\core\ui\templateengine;
use \codename\core\app;

/**
 * Dummy Template Engine
 * For testing purposes. Just returns partial paths
 * that would be accessed if it was a real templating engine
 */
class dummy extends \codename\core\templateengine {

  /**
   * @inheritDoc
   */
  public function __construct(array $config = array())
  {
    parent::__construct($config);
  }

  /**
   * @inheritDoc
   */
  public function render(string $referencePath, $data = null): string {
    return 'frontend/'.$referencePath;
  }

  /**
   * @inheritDoc
   */
  public function renderView(string $viewPath, $data = null): string {
    return 'view/'.$viewPath;
  }

  /**
   * @inheritDoc
   */
  public function renderTemplate( string $templatePath, $data = null): string {
    return 'template/'.$templatePath;
  }

}
