<?php
namespace codename\core\ui\templateengine\twig\extension;
// use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use \codename\core\app;
use \codename\core\generator\urlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\TwigFunction;

/**
 * Provides integration of the core frameworks routing component with Twig.
 * */
class routing extends AbstractExtension
{
    /**
     * @var \codename\core\generator\url
     */
    protected $generator;

    public function __construct(/* UrlGeneratorInterface $generator */)
    {
        // $this->generator = $generator;
        $this->generator = new \codename\core\generator\urlGenerator;
    }
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('url', array($this, 'getUrl'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
            new TwigFunction('path', array($this, 'getPath'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
        );
    }
    /**
     * @param string $name
     * @param array  $parameters
     * @param bool   $relative
     *
     * @return string
     */
    public function getPath($name, $parameters = array(), $relative = false)
    {
        // return $this->generator->generate($name, $parameters, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
        return $this->generator->generateFromRoute($name, $parameters, $relative ? urlGeneratorInterface::RELATIVE_PATH : urlGeneratorInterface::ABSOLUTE_PATH);
    }
    /**
     * @param string $name
     * @param array  $parameters
     * @param bool   $schemeRelative
     *
     * @return string
     */
    public function getUrl($name, $parameters = array(), $schemeRelative = false)
    {
        // return $this->generator->generate($name, $parameters, $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
        return $this->generator->generateFromRoute($name, $parameters, $schemeRelative ? urlGeneratorInterface::NETWORK_PATH : urlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Determines at compile time whether the generated URL will be safe and thus
     * saving the unneeded automatic escaping for performance reasons.
     *
     * The URL generation process percent encodes non-alphanumeric characters. So there is no risk
     * that malicious/invalid characters are part of the URL. The only character within an URL that
     * must be escaped in html is the ampersand ("&") which separates query params. So we cannot mark
     * the URL generation as always safe, but only when we are sure there won't be multiple query
     * params. This is the case when there are none or only one constant parameter given.
     * E.g. we know beforehand this will be safe:
     * - path('route')
     * - path('route', {'param': 'value'})
     * But the following may not:
     * - path('route', var)
     * - path('route', {'param': ['val1', 'val2'] }) // a sub-array
     * - path('route', {'param1': 'value1', 'param2': 'value2'})
     * If param1 and param2 reference placeholder in the route, it would still be safe. But we don't know.
     *
     * @param Node $argsNode The arguments of the path/url function
     *
     * @return array An array with the contexts the URL is safe
     *
     * @final since version 3.4
     */
    public function isUrlGenerationSafe(Node $argsNode)
    {
        // support named arguments
        $paramsNode = $argsNode->hasNode('parameters') ? $argsNode->getNode('parameters') : (
            $argsNode->hasNode(1) ? $argsNode->getNode(1) : null
        );
        if (null === $paramsNode || $paramsNode instanceof ArrayExpression && count($paramsNode) <= 2 &&
            (!$paramsNode->hasNode(1) || $paramsNode->getNode(1) instanceof ConstantExpression)
        ) {
            return array('html');
        }
        return array();
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'routing';
    }
}