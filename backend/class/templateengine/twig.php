<?php
namespace codename\core\ui\templateengine;
use \codename\core\app;
use \codename\core\exception;
use \codename\core\ui\templateengine\twig\extension;

/**
 * Twig Template Engine Abstractor
 */
class twig extends \codename\core\templateengine implements \codename\core\clientInterface {

  /**
   * its very own client name
   * @var [type]
   */
  protected $clientName = null;

  /**
   * @inheritDoc
   */
  public function setClientName(string $name)
  {
    if($this->clientName == null) {
      $this->clientName = $name;
      $this->twigInstance->setTemplateClassPrefixPrefix($this->clientName);
    } else {
      throw new exception("EXCEPTION_CORE_CLIENT_INTERFACE_CANNOT_RENAME_CLIENT", exception::$ERRORLEVEL_FATAL, $this->clientName);
    }
  }

  /**
   * @inheritDoc
   */
  public function getClientName(string $name)
  {
    return $this->clientName;
  }

  /**
   * twig instance
   * @var \codename\core\ui\templateengine\twig\environment\core
   */
  protected $twigInstance = null;

  /**
   * twig loader
   * @var \Twig\Loader\LoaderInterface
   */
  protected $twigLoader = null;

  /**
   * File extension automatically added for finding extensions
   * @var string
   */
  protected $templateFileExtension = '.twig';

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

    if(!empty($config['template_file_extension'])) {
      $this->templateFileExtension = $config['template_file_extension'];
    }

    $paths = array();

    // collect appstack paths
    // to search for views in
    // this includes the current app
    // so, no need to add it explicitly
    foreach(app::getAppstack() as $parentapp) {
      $vendor = $parentapp['vendor'];
      $app = $parentapp['app'];
      if($vendor != 'corename' && $app != 'core') {
        $filename = CORE_VENDORDIR . $vendor . '/' . $app . '/' . 'frontend/';
        $paths[] = $filename;
      }
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

    $this->twigInstance = new \codename\core\ui\templateengine\twig\environment\core($this->twigLoader, $options);
    $this->twigInstance->templateFileSuffix = $this->templateFileExtension;
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

    $this->twigInstance->addFunction(new \Twig\TwigFunction('strpadleft', function($string, $pad_length, $pad_string = " ") {
      return str_pad($string, $pad_length, $pad_string, STR_PAD_LEFT);
    }));

    $this->twigInstance->addFunction(new \Twig\TwigFunction('strpadright', function($string, $pad_length, $pad_string = " ") {
      return str_pad($string, $pad_length, $pad_string, STR_PAD_RIGHT);
    }));

    $this->twigInstance->addFunction(new \Twig\TwigFunction('var_export', function($value) {
      return var_export($value, true);
    }));

    $this->twigInstance->addFunction(new \Twig\TwigFunction('print_r', function($value) {
      return print_r($value, true);
    }));


    if(app::getRequest() instanceof \codename\core\request\cli) {
      $this->twigInstance->addFunction(new \Twig\TwigFunction('cli_format', function($value, $color) {
        return \codename\core\helper\clicolors::getInstance()->getColoredString($value, $color);
      }));
    }
  }

  /**
   * @inheritDoc
   *
   * twig loads a custom element/partial/whatever like this (fixed:)
   * frontend/<referencePath>.twig
   */
  public function render(string $referencePath, $data = null): string {
    $twigTemplate = $this->twigInstance->load($referencePath);
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