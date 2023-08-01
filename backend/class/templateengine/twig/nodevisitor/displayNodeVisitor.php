<?php

namespace codename\core\ui\templateengine\twig\nodevisitor;

use codename\core\ui\templateengine\twig\node\displayNode;
use Twig\Environment;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class displayNodeVisitor implements NodeVisitorInterface
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
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    /**
     * @inheritdoc
     */
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if ($node instanceof ModuleNode) {
            $node->setNode(
                'class_end',
                new Node([
                  new displayNode($this->templateFileExtension),
                  $node->getNode('class_end'),
                ])
            );
        }
        return $node;
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return 0;
    }
}
