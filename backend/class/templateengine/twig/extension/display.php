<?php

namespace codename\core\ui\templateengine\twig\extension;

use codename\core\ui\templateengine\twig\nodevisitor\displayNodeVisitor;
use Twig\Extension\AbstractExtension;

class display extends AbstractExtension
{
    /**
     * @var string
     */
    protected string $templateFileExtension = '';

    /**
     * @param string|null $templateFileExtension
     */
    public function __construct(?string $templateFileExtension = '')
    {
        $this->templateFileExtension = $templateFileExtension ?? $this->templateFileExtension;
    }

    /**
     * @inheritdoc
     */
    public function getNodeVisitors(): array
    {
        return [new displayNodeVisitor($this->templateFileExtension)];
    }
}
