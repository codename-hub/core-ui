<?php
namespace codename\core\ui\templateengine;
use \codename\core\app;

/**
 * Simple Template Engine
 * for just using .php files (inline-code-based)
 */
class simple extends \codename\core\templateengine {

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
  public function render(string $referencePath, $data): string {
    return app::parseFile(app::getInheritedPath("frontend/" . $referencePath . ".php"), $data);
  }

  /**
   * @inheritDoc
   */
  public function renderView(string $viewPath, $data): string {
    return $this->render("view/" . $data->getData('context') . "/" . $viewPath, $data);
  }

  /**
   * @inheritDoc
   */
  public function renderTemplate( string $templatePath, $data): string {
    return $this->render("template/" . $templatePath . "/template.php", $data);
  }

}