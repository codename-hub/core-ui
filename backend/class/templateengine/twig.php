<?php

namespace codename\core\ui\templateengine;

use codename\core\app;
use codename\core\clientInterface;
use codename\core\datacontainer;
use codename\core\exception;
use codename\core\helper\clicolors;
use codename\core\request\cli;
use codename\core\templateengine;
use codename\core\ui\templateengine\twig\environment\core;
use codename\core\ui\templateengine\twig\extension;
use ReflectionException;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Sandbox\SecurityPolicy;
use Twig\Template;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Twig Template Engine Abstractor
 */
class twig extends templateengine implements clientInterface
{
    /**
     * Default global sandbox
     * with some minimalistic stuff
     * allowing to include + template_from_string
     * @var array
     */
    public const DefaultConfigSandboxGlobalWithIncludes = [
      'sandbox_enabled' => true,
      'sandbox_mode' => 'global',
      'sandbox' => [
        'tags' => [
          'if',
          'for',
        ],
        'functions' => [
          'include',
          'template_from_string',
        ],
      ],
    ];

    /**
     * Default sandbox
     * with some minimalistic stuff
     * allowing to include + template_from_string
     * only enabled when using sandbox rendering explicitly
     * @var array
     */
    public const DefaultConfigSandboxWithIncludes = [
      'sandbox_enabled' => true,
      'sandbox_mode' => null,
      'sandbox' => [
        'tags' => [
          'if',
          'for',
        ],
        'functions' => [
          'include',
          'template_from_string',
        ],
      ],
    ];

    /**
     * its very own client name
     * @var string|null [type]
     */
    protected ?string $clientName = null;
    /**
     * twig instance
     * @var core
     */
    protected core $twigInstance;
    /**
     * File extension automatically added for finding extensions
     * @var string
     */
    protected string $templateFileExtension = '.twig';
    /**
     * [protected description]
     * @var null|SandboxExtension
     */
    protected ?SandboxExtension $sandboxExtensionInstance = null;
    /**
     * Sandbox mode override
     * @var bool
     */
    protected bool $sandboxOverride = false;

    /**
     * {@inheritDoc}
     * @param array $config
     * @throws LoaderError
     * @throws ReflectionException
     * @throws exception
     */
    public function __construct(array $config = [])
    {
        // Check for existence of Twig Classes.
        if (!class_exists('\\Twig\\Environment')) {
            throw new exception("CORE_TEMPLATEENGINE_TWIG_CLASS_DOES_NOT_EXIST", exception::$ERRORLEVEL_FATAL);
        }

        // Default asset dir
        // required for explicit images, css, etc. referenced from template
        $config['assets_path'] = $config['assets_path'] ?? 'twig_assets_path';

        parent::__construct($config);

        if (!empty($config['template_file_extension'])) {
            $this->templateFileExtension = $config['template_file_extension'];
        }

        $paths = [];

        // collect appstack paths
        // to search for views in
        // this includes the current app
        // so, no need to add it explicitly
        foreach (app::getAppstack() as $parentapp) {
            $vendor = $parentapp['vendor'];
            $app = $parentapp['app'];
            if ($vendor != 'corename' && $app != 'core') {
                $appDir = app::getHomedir($vendor, $app);
                $dir = $appDir . 'frontend/';

                // the frontend root dir has to exist
                // otherwise, we're adding it to the paths
                // for Twig to search for templates in
                if (app::getFilesystem()->dirAvailable($dir)) {
                    $paths[] = $dir;
                }
            }
        }

        $twigLoader = new FilesystemLoader($paths, CORE_VENDORDIR);

        $options = $config['environment'] ?? [];

        $this->twigInstance = new core($twigLoader, $options);

        $extensions = [];

        $extensions[] = new extension\display($this->templateFileExtension);
        $extensions[] = new extension\routing();
        $extensions[] = new IntlExtension();
        $extensions[] = new StringLoaderExtension();

        if (!empty($config['sandbox_enabled'])) {
            $globalSandbox = !empty($config['sandbox_mode']) && $config['sandbox_mode'] == 'global';

            $allowedTags = $config['sandbox']['tags'] ?? [];
            $allowedFilters = array_merge($config['sandbox']['filters'] ?? [], ['escape']); // auto-escape needs this at all times
            $allowedMethods = $config['sandbox']['methods'] ?? [];
            $allowedProperties = $config['sandbox']['properties'] ?? [];
            $allowedFunctions = $config['sandbox']['functions'] ?? [];

            $policy = new SecurityPolicy(
                $allowedTags,
                $allowedFilters,
                $allowedMethods,
                $allowedProperties,
                $allowedFunctions
            );

            $extensions[] = $this->sandboxExtensionInstance = new SandboxExtension($policy, $globalSandbox);
        }

        //
        // Special sandbox override for compatibility
        // allows executing renderStringSandboxed without really using the sandbox
        //
        if (($config['sandbox_enabled'] ?? null) === false & (($config['sandbox_mode'] ?? null) === 'override')) {
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
        if (app::getEnvironment()->get(app::getEnv() . '>translate>default')) {
            //
            // NOTE: might fail in unconfigured env, as "inherit" config exists
            // (bare core app without anything added)
            //
            try {
                $this->twigInstance->addGlobal('translate', app::getTranslate());
            } catch (\Exception) {
                // swallow exception.
            }
        }

        // add testing for array
        $this->twigInstance->addTest(
            new TwigTest('array', function ($value) {
                return is_array($value);
            })
        );

        // add testing for string
        $this->twigInstance->addTest(
            new TwigTest('string', function ($value) {
                return is_string($value);
            })
        );


        $assetsTempDir = $this->getAssetsPath();

        //
        // This is meant mostly for internal rendering purposes
        //
        $this->twigInstance->addFunction(
            new TwigFunction('asset_path', function (Environment $env, string $name, bool $ignoreMissing = true) use ($assetsTempDir) {
                $template = null;
                // TODO: limit debug_backtrace ?
                foreach (debug_backtrace() as $trace) {
                    if (isset($trace['object']) && $trace['object'] instanceof Template && 'Twig_Template' !== get_class($trace['object'])) {
                        $template = $trace['object'];
                        break; // break on first one.
                    }
                }

                $path = null;
                if ($template) {
                    $templateName = $template->getTemplateName();
                    $dir = pathinfo($templateName, PATHINFO_DIRNAME);
                    foreach (app::getAppstack() as $app) {
                        $path = realpath(app::getHomedir($app['vendor'], $app['app']) . '/frontend/' . $dir . '/' . $name);
                        if ($path !== false) {
                            break;
                        }
                    }
                }

                if ($path) {
                    $hash = md5($path);
                    $filename = pathinfo($name, PATHINFO_BASENAME);
                    $tmpFile = $hash . '_' . $filename;
                    $tmpFilePath = $assetsTempDir . $tmpFile;
                    if (!app::getFilesystem()->fileAvailable($tmpFilePath)) {
                        // copy to temp dir
                        if (!app::getFilesystem()->fileCopy($path, $tmpFilePath)) {
                            throw new exception('ASSET_COPY_FAILED', exception::$ERRORLEVEL_ERROR);
                        }
                    } else {
                        // exists. we MAY check integrity?
                    }

                    return $tmpFilePath;
                } else {
                    // error, not found?
                    throw new exception('ASSET_UNAVAILABLE', exception::$ERRORLEVEL_ERROR, $name);
                }
            }, [
              'needs_environment' => true,
            ])
        );

        $this->twigInstance->addFunction(
            new TwigFunction('strpadleft', function ($string, $pad_length, $pad_string = " ") {
                return str_pad($string, $pad_length, $pad_string, STR_PAD_LEFT);
            })
        );

        $this->twigInstance->addFunction(
            new TwigFunction('strpadright', function ($string, $pad_length, $pad_string = " ") {
                return str_pad($string, $pad_length, $pad_string);
            })
        );

        $this->twigInstance->addFunction(
            new TwigFunction('var_export', function ($value) {
                return var_export($value, true);
            })
        );

        $this->twigInstance->addFunction(
            new TwigFunction('print_r', function ($value) {
                return print_r($value, true);
            })
        );


        if (app::getRequest() instanceof cli) {
            $this->twigInstance->addFunction(
                new TwigFunction('cli_format', function ($value, $color) {
                    return clicolors::getInstance()->getColoredString($value, $color);
                })
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAssetsPath(): string
    {
        return sys_get_temp_dir() . '/' . ($this->config->get('assets_path') ?? 'twig_assets_path') . '/';
    }

    /**
     * adds a function available during the render process
     * @param string $name [description]
     * @param callable $function [description]
     */
    public function addFunction(string $name, callable $function): void
    {
        $this->twigInstance->addFunction(new TwigFunction($name, $function));
    }

    /**
     * {@inheritDoc}
     */
    public function getClientName(string $name): string
    {
        return $this->clientName;
    }

    /**
     * {@inheritDoc}
     * @param string $name
     * @throws exception
     */
    public function setClientName(string $name): void
    {
        if ($this->clientName == null) {
            $this->clientName = $name;
            $this->twigInstance->setTemplateClassPrefixPrefix($this->clientName);
        } else {
            throw new exception("EXCEPTION_CORE_CLIENT_INTERFACE_CANNOT_RENAME_CLIENT", exception::$ERRORLEVEL_FATAL, $this->clientName);
        }
    }

    /**
     * [renderSandboxed description]
     * @param string $referencePath [description]
     * @param array $variableContext [description]
     * @return string                  [description]
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     * @throws exception
     */
    public function renderSandboxed(string $referencePath, array $variableContext): string
    {
        if (!$this->sandboxOverride && !$this->sandboxExtensionInstance) {
            throw new exception('TEMPLATEENGINE_TWIG_NO_SANDBOX_INSTANCE', exception::$ERRORLEVEL_ERROR);
        }

        $prevSandboxState = false;
        if (!$this->sandboxOverride) {
            // Store sandbox state
            $prevSandboxState = $this->sandboxExtensionInstance->isSandboxed();
        }

        // enable sandbox for a brief moment
        if (!$this->sandboxOverride && !$prevSandboxState) {
            $this->sandboxExtensionInstance->enableSandbox();
        }

        $twigTemplate = $this->twigInstance->load($referencePath . $this->templateFileExtension);
        $rendered = $twigTemplate->render($variableContext);

        // disable sandbox again, if it has been disabled before
        if (!$this->sandboxOverride && !$prevSandboxState) {
            $this->sandboxExtensionInstance->disableSandbox();
        }

        return $rendered;
    }

    /**
     * {@inheritDoc}
     *
     * twig loads a custom element/partial/whatever like this (fixed:)
     * frontend/<referencePath>.twig
     * @param string $referencePath
     * @param array|datacontainer|null $data
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function render(string $referencePath, array|datacontainer|null $data = null): string
    {
        $twigTemplate = $this->twigInstance->load($referencePath . $this->templateFileExtension);
        try {
            return $twigTemplate->render([
              'data' => $data,
            ]);
        } catch (Throwable $e) {
            throw $e->getPrevious() ?? new exception("CORE_TEMPLATEENGINE_TWIG_RENDER_INTERNAL_ERROR", exception::$ERRORLEVEL_FATAL);
        }
    }

    /**
     * [renderStringSandboxed description]
     * @param string $template [description]
     * @param array $variableContext [description]
     * @return string                  [description]
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     * @throws exception
     */
    public function renderStringSandboxed(string $template, array $variableContext): string
    {
        if (!$this->sandboxOverride && !$this->sandboxExtensionInstance) {
            throw new exception('TEMPLATEENGINE_TWIG_NO_SANDBOX_INSTANCE', exception::$ERRORLEVEL_ERROR);
        }

        $prevSandboxState = false;
        if (!$this->sandboxOverride) {
            // Store sandbox state
            $prevSandboxState = $this->sandboxExtensionInstance->isSandboxed();
        }

        // enable sandbox for a brief moment
        if (!$this->sandboxOverride && !$prevSandboxState) {
            $this->sandboxExtensionInstance->enableSandbox();
        }

        $twigTemplate = $this->twigInstance->createTemplate($template);
        $rendered = $twigTemplate->render($variableContext);

        // disable sandbox again, if it has been disabled before
        if (!$this->sandboxOverride && !$prevSandboxState) {
            $this->sandboxExtensionInstance->disableSandbox();
        }

        return $rendered;
    }

    /**
     * {@inheritDoc}
     *
     * twig loads a view like this (fixed:)
     * frontend/view/<context>/<viewPath>.html.twig
     * NOTE: extension .twig added by render()
     * @param string $viewPath
     * @param array|datacontainer|null $data
     * @return string
     * @throws Throwable
     */
    public function renderView(string $viewPath, array|datacontainer|null $data = null): string
    {
        return $this->render('view/' . $viewPath, $data);
    }

    /**
     * {@inheritDoc}
     *
     * twig loads a template like this (fixed:)
     * frontend/template/<name>/template.html.twig
     * NOTE: extension .twig added by render()
     * @param string $templatePath
     * @param array|datacontainer|null $data
     * @return string
     * @throws Throwable
     */
    public function renderTemplate(string $templatePath, array|datacontainer|null $data = null): string
    {
        return $this->render('template/' . $templatePath . '/template', $data);
    }
}
