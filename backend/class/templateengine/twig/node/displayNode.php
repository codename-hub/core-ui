<?php

namespace codename\core\ui\templateengine\twig\node;

use Twig\Compiler;
use Twig\Node\Node;

class displayNode extends Node
{
    /**
     * @var string
     */
    protected string $templateFileExtension = '';

    /**
     * @inheritdoc
     * @param string|null $templateFileExtension
     */
    public function __construct(?string $templateFileExtension = '')
    {
        parent::__construct();
        $this->templateFileExtension = $templateFileExtension ?? $this->templateFileExtension;
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
          ->addDebugInfo($this)
          ->write(
              '
              protected function loadTemplate($template, $templateName = null, $line = null, $index = null)
                {
                    $template .= "' . $this->templateFileExtension . '";
                    if (str_starts_with($template, \'./\') && $templateName) {
                        $template = dirname($templateName) . ltrim($template, \'.\');
                    }
                    return parent::loadTemplate($template, $templateName, $line, $index);
                }
          '
          );
    }
}
