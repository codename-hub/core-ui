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
   * Default global sandbox
   * with some minimalistic stuff
   * allowing include + template_from_string
   * @var array
   */
  public const DefaultConfigSandboxGlobalWithIncludes = [
    'sandbox_enabled' => true,
    'sandbox_mode'    => 'global',
    'sandbox' => [
      'tags' => [
        'if',
        'for',
      ],
      'functions' => [
        'include',
        'template_from_string',
      ],
    ]
  ];

  /**
   * Default sandbox
   * with some minimalistic stuff
   * allowing include + template_from_string
   * only enabled when using sandbox rendering explicitly
   * @var array
   */
  public const DefaultConfigSandboxWithIncludes = [
    'sandbox_enabled' => true,
    'sandbox_mode'    => null,
    'sandbox' => [
      'tags' => [
        'if',
        'for',
      ],
      'functions' => [
        'include',
        'template_from_string',
      ],
    ]
  ];

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
        $appDir = app::getHomedir($vendor, $app);
        $dir = $appDir . 'frontend/';

        // the frontend root dir has to exist
        // otherwise, we're adding it to the paths
        // for Twig to search for templates in
        if(app::getFilesystem()->dirAvailable($dir)) {
          $paths[] = $dir;
        }
      }
    }

    // Configure path suffixing only for FS Loader
     $fsLoader = new \codename\core\ui\templateengine\twig\loader\filesystem($paths, CORE_VENDORDIR);
     $fsLoader->templateFileSuffix = $this->templateFileExtension;
     $this->twigLoader = $fsLoader;

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
    // $this->twigInstance->templateFileSuffix = $this->templateFileExtension;

    $extensions = [];

    $extensions[] = new extension\routing;
    $extensions[] = new \Twig\Extensions\IntlExtension();
    $extensions[] = new \Twig\Extension\StringLoaderExtension();

    if(!empty($config['sandbox_enabled']) && $config['sandbox_enabled']) {
      $globalSandbox = !empty($config['sandbox_mode']) && $config['sandbox_mode'] == 'global';

      $allowedTags        = $config['sandbox']['tags'] ?? [];
      $allowedFilters     = array_merge($config['sandbox']['filters'] ?? [], [ 'escape' ]); // auto-escape needs this at all times
      $allowedMethods     = $config['sandbox']['methods'] ?? [];
      $allowedProperties  = $config['sandbox']['properties'] ?? [];
      $allowedFunctions   = $config['sandbox']['functions'] ?? [];

      $policy = new \Twig\Sandbox\SecurityPolicy(
        $allowedTags,
        $allowedFilters,
        $allowedMethods,
        $allowedProperties,
        $allowedFunctions
      );

      $extensions[] = $this->sandboxExtensionInstance = new \Twig\Extension\SandboxExtension($policy, $globalSandbox);
    }

    //
    // Special sandbox overide for compatibility
    // allows executing renderStringSandboxed without really using the sandbox
    //
    if(($config['sandbox_enabled'] ?? null) === false & (($config['sandbox_mode'] ?? null) === 'override')) {
      $this->sandboxOverride = true;
    }

    $this->twigInstance->setExtensions($extensions);

    // Add request and response containers, globally
    $this->twigInstance->addGlobal('request', app::getRequest());
    $this->twigInstance->addGlobal('response', app::getResponse());
    $this->twigInstance->addGlobal('frontend', \codename\core\ui\app::getInstance('frontend'));

    //
    // workaround to perform on-demand init of translation client, only if defined in environment
    //
    if(app::getEnvironment()->get(app::getEnv().'>translate>default')) {
      //
      // NOTE: might fail in unconfigured env, as "inherit" config exists
      // (bare core app without anything added)
      //
      try {
        $this->twigInstance->addGlobal('translate', app::getTranslate('default'));
      } catch (\Exception $e) {
        // swallow exception.
      }
    }

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
   * [protected description]
   * @var \Twig\Extension\SandboxExtension
   */
  protected $sandboxExtensionInstance = null;

  /**
   * Sandbox mode override
   * @var bool
   */
  protected $sandboxOverride = false;

  /**
   * adds a function available during the render process
   * @param string   $name     [description]
   * @param callable $function [description]
   */
  public function addFunction(string $name, callable $function) {
    $this->twigInstance->addFunction(new \Twig\TwigFunction($name, $function));
  }

  /**
   * [renderSandboxed description]
   * @param  string $referencePath   [description]
   * @param  array  $variableContext [description]
   * @return string                  [description]
   */
  public function renderSandboxed(string $referencePath, array $variableContext) : string {

    if(!$this->sandboxOverride && !$this->sandboxExtensionInstance) {
      throw new exception('TEMPLATEENGINE_TWIG_NO_SANDBOX_INSTANCE', exception::$ERRORLEVEL_ERROR);
    }

    if(!$this->sandboxOverride) {
      // Store sandbox state
      $prevSandboxState = $this->sandboxExtensionInstance->isSandboxed();
    }

    // enable sandbox for a brief moment
    if(!$this->sandboxOverride && !$prevSandboxState) {
      $this->sandboxExtensionInstance->enableSandbox();
    }

    $twigTemplate = $this->twigInstance->load($referencePath);
    $rendered = $twigTemplate->render($variableContext);

    // disable sandbox again, if it has been disabled before
    if(!$this->sandboxOverride && !$prevSandboxState) {
      $this->sandboxExtensionInstance->disableSandbox();
    }

    return $rendered;
  }

  /**
   * [renderStringSandboxed description]
   * @param  string $template        [description]
   * @param  array  $variableContext [description]
   * @return string                  [description]
   */
  public function renderStringSandboxed(string $template, array $variableContext) : string {
    if(!$this->sandboxOverride && !$this->sandboxExtensionInstance) {
      throw new exception('TEMPLATEENGINE_TWIG_NO_SANDBOX_INSTANCE', exception::$ERRORLEVEL_ERROR);
    }

    if(!$this->sandboxOverride) {
      // Store sandbox state
      $prevSandboxState = $this->sandboxExtensionInstance->isSandboxed();
    }

    // enable sandbox for a brief moment
    if(!$this->sandboxOverride && !$prevSandboxState) {
      $this->sandboxExtensionInstance->enableSandbox();
    }

    $twigTemplate = $this->twigInstance->createTemplate($template);
    $rendered = $twigTemplate->render($variableContext);

    // disable sandbox again, if it has been disabled before
    if(!$this->sandboxOverride && !$prevSandboxState) {
      $this->sandboxExtensionInstance->disableSandbox();
    }

    return $rendered;
  }

  /**
   * @inheritDoc
   *
   * twig loads a custom element/partial/whatever like this (fixed:)
   * frontend/<referencePath>.twig
   */
  public function render(string $referencePath, $data = null): string {
    $twigTemplate = $this->twigInstance->load($referencePath);
    try {
      return $twigTemplate->render(array(
        'data' => $data
      ));
    } catch (\Twig\Error\RuntimeError $e) {
      throw $e->getPrevious();
    }

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
