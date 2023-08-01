<?php

namespace codename\core\ui\templateengine;

use codename\core\app;
use codename\core\datacontainer;
use codename\core\exception;
use codename\core\templateengine;
use ReflectionException;

/**
 * Simple Template Engine
 * for just using .php files (inline-code-based)
 */
class simple extends templateengine
{
    /**
     * {@inheritDoc}
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * {@inheritDoc}
     * @param string $viewPath
     * @param array|datacontainer|null $data
     * @return string
     * @throws ReflectionException
     * @throws exception
     */
    public function renderView(string $viewPath, array|datacontainer|null $data = null): string
    {
        return $this->render("view/" . $viewPath, $data);
    }

    /**
     * {@inheritDoc}
     * @param string $referencePath
     * @param array|datacontainer|null $data
     * @return string
     * @throws ReflectionException
     * @throws exception
     */
    public function render(string $referencePath, array|datacontainer|null $data = null): string
    {
        return app::parseFile(app::getInheritedPath("frontend/" . $referencePath . ".php"), $data);
    }

    /**
     * {@inheritDoc}
     * @param string $templatePath
     * @param array|datacontainer|null $data
     * @return string
     * @throws ReflectionException
     * @throws exception
     */
    public function renderTemplate(string $templatePath, array|datacontainer|null $data = null): string
    {
        return $this->render("template/" . $templatePath . "/template", $data);
    }
}
