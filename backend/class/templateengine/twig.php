<?php
namespace codename\core\ui\templateengine;
use \codename\core\app;
use \codename\core\exception;
use \codename\core\ui\templateengine\twig\extension;

/**
 * Twig Template Engine Abstractor
 */
class twig extends \codename\core\templateengine {

  /**
   * twig instance
   * @var \Twig\Environment
   */
  protected $twigInstance = null;

  /**
   * twig loader
   * @var \Twig\Loader\LoaderInterface
   */
  protected $twigLoader = null;

  /**
   * @inheritDoc
   */
  public function __construct(array $config = array())
  {
    // Check for existance of Twig Classes.
    if (!class_exists('\\Twig\\Environment')) {
      throw new exception("CORE_TEMPLATEENGINE_TWIG_CLASS_DOES_NOT_EXIST", exception::$ERRORLEVEL_FATAL);
    }

    parent::__construct($config);
    $paths = array();

    // add current app home frontend to paths
    $paths[] = app::getHomedir() . 'frontend/';

    // collect appstack paths
    // to search for views in
    foreach(app::getAppstack() as $parentapp) {
      $vendor = $parentapp['vendor'];
      $app = $parentapp['app'];
      $filename = CORE_VENDORDIR . $vendor . '/' . $app . '/' . 'frontend/';
    }

    $this->twigLoader = new \Twig\Loader\FilesystemLoader($paths, CORE_VENDORDIR);

    /**
     * Important Note:
     * we're using a custom class as template base
     * to support relative paths in embed/include/... twig blocks
     */
    $options = array_merge(
      array(
        'base_template_class' => '\\codename\\core\\ui\\templateengine\\twig\\template\\baseTemplate'
      ),
      $config['environment'] ?? array()
    );

    $this->twigInstance = new \Twig\Environment($this->twigLoader, $options);
    $this->twigInstance->addExtension(new extension\routing);

    // Add request and response containers, globally
    $this->twigInstance->addGlobal('request', app::getRequest());
    $this->twigInstance->addGlobal('response', app::getResponse());

    // add testing for array
    $this->twigInstance->addTest(new \Twig\TwigTest('array', function ($value) {
      return is_array($value);
    }));

    // add testing for string
    $this->twigInstance->addTest(new \Twig\TwigTest('string', function ($value) {
      return is_string($value);
    }));
  }

  /**
   * @inheritDoc
   *
   * twig loads a custom element/partial/whatever like this (fixed:)
   * frontend/<referencePath>.twig
   */
  public function render(string $referencePath, $data = null): string {
    $twigTemplate = $this->twigInstance->load($referencePath . '.twig');
    return $twigTemplate->render(array(
      'data' => $data
    ));
  }

  /**
   * @inheritDoc
   *
   * twig loads a view like this (fixed:)
   * frontend/view/<context>/<viewPath>.html.twig
   * NOTE: extension .twig added by render()
   */
  public function renderView(string $viewPath, $data = null) : string {
    return $this->render('view/' . $viewPath, $data);
  }

  /**
   * @inheritDoc
   *
   * twig loads a template like this (fixed:)
   * frontend/template/<name>/template.html.twig
   * NOTE: extension .twig added by render()
   */
  public function renderTemplate(string $templatePath, $data = null) : string {
    return $this->render('template/' . $templatePath . '/template', $data);
  }

}