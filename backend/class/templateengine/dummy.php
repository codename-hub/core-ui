<?php

namespace codename\core\ui\templateengine;

use codename\core\datacontainer;
use codename\core\templateengine;

/**
 * Dummy Template Engine
 * For testing purposes. Just returns partial paths
 * that would be accessed if it was a real templating engine
 */
class dummy extends templateengine
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
     */
    public function render(string $referencePath, array|datacontainer|null $data = null): string
    {
        return 'frontend/' . $referencePath;
    }

    /**
     * {@inheritDoc}
     */
    public function renderView(string $viewPath, array|datacontainer|null $data = null): string
    {
        return 'view/' . $viewPath;
    }

    /**
     * {@inheritDoc}
     */
    public function renderTemplate(string $templatePath, array|datacontainer|null $data = null): string
    {
        return 'template/' . $templatePath;
    }
}
