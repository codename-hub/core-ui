<?php

namespace codename\core\ui\frontend;

use codename\core\app;
use codename\core\config;
use codename\core\datacontainer;
use codename\core\exception;
use codename\core\validator;
use ReflectionException;

/**
 * base class for frontend elements
 */
abstract class element
{
    /**
     * exception that identities an invalid config
     * @var string
     */
    public const EXCEPTION_CORE_UI_FRONTEND_ELEMENT_INVALID_CONFIG = 'EXCEPTION_CORE_UI_FRONTEND_ELEMENT_INVALID_CONFIG';
    /**
     * data
     * @var datacontainer
     */
    public datacontainer $data;
    /**
     * config
     * @var config
     */
    protected config $config;
    /**
     * validator used for validating the given configuration
     * @var string
     */
    protected $configValidatorName = 'structure_config_frontend_element';
    /**
     * templatePath
     * @var string
     */
    protected $templatePath;

    /**
     * [__construct description]
     * @param array $config [description]
     * @param array $data
     * @throws ReflectionException
     * @throws exception
     */
    public function __construct(array $config = [], array $data = [])
    {
        if (count($this->getValidator()->reset()->validate($config)) === 0) {
            $this->config = new config($config);
        } else {
            throw new exception(self::EXCEPTION_CORE_UI_FRONTEND_ELEMENT_INVALID_CONFIG, exception::$ERRORLEVEL_ERROR, $config);
        }
        $this->data = new datacontainer($data);
    }

    /**
     * [getValidator description]
     * @return validator [description]
     * @throws ReflectionException
     * @throws exception
     */
    protected function getValidator(): validator
    {
        return app::getValidator($this->configValidatorName);
    }

    /**
     * [public description]
     * @var [type]
     */
    public function outputData(): array
    {
        return $this->handleData();
    }

    /**
     * [handleData description]
     * @return array [description]
     */
    protected function handleData(): array
    {
        return $this->data->getData();
    }

    /**
     * string output
     * @return string
     * @throws ReflectionException
     * @throws exception
     */
    public function outputString(): string
    {
        return app::getTemplateEngine($this->config->get('templateengine', 'default'))->render($this->templatePath, $this->handleData());
    }
}
