<?php
namespace codename\core\ui\frontend;
use \codename\core\validator;
use \codename\core\app;
use \codename\core\exception;
use codename\core\datacontainer;

/**
 * base class for frontend elements
 */
abstract class element {

  /**
   * config
   * @var \codename\core\config
   */
  protected $config;

  /**
   * data
   * @var datacontainer
   */
  public $data;

  /**
   * validator used for validating the given configuration
   * @var string
   */
  protected $configValidatorName = 'structure_config_frontend_element';

  /**
   * [getValidator description]
   * @return validator [description]
   */
  protected function getValidator() : validator {
    return app::getValidator($this->configValidatorName);
  }

  /**
   * [__construct description]
   * @param array $config [description]
   */
  public function __construct(array $config = array(), array $data = array()) {
    if(count($errors = $this->getValidator()->reset()->validate($config)) === 0) {
      $this->config = new \codename\core\config($config);
    } else {
      throw new exception(self::EXCEPTION_CORE_UI_FRONTEND_ELEMENT_INVALID_CONFIG, exception::$ERRORLEVEL_ERROR, $config);
    }
    $this->data = new datacontainer($data);
  }

  /**
   * exception that idenfities an invalid config
   * @var string
   */
  const EXCEPTION_CORE_UI_FRONTEND_ELEMENT_INVALID_CONFIG = 'EXCEPTION_CORE_UI_FRONTEND_ELEMENT_INVALID_CONFIG';

  /**
   * [handleData description]
   * @return array [description]
   */
  protected function handleData() : array {
    return $this->data()->getData();
  }

  /**
   * [public description]
   * @var [type]
   */
  public function outputData() : array {
    return $this->handleData();
  }

  /**
   * string output
   * @return string
   */
  public function outputString() : string {
    return app::getTemplateEngine($this->config->get('templateengine', 'default'))->render($this->templatePath, $this->handleData());
  }

  /**
   * templatePath
   * @var string
   */
  protected $templatePath;

}
