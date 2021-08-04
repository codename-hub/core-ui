<?php
namespace codename\core\ui\tests\crud\model;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodeljoin extends \codename\core\test\sqlModel {
  /**
   * @inheritDoc
   */
  public function __CONSTRUCT(array $modeldata = array())
  {
    parent::__CONSTRUCT('crudtest', 'testmodeljoin', static::$staticConfig);
  }

  /**
   * static configuration
   * for usage in unit tests
   * @var array
   */
  public static $staticConfig = [
    'field' => [
      'testmodeljoin_id',
      'testmodeljoin_created',
      'testmodeljoin_modified',
      'testmodeljoin_text',
    ],
    'primary' => [
      'testmodeljoin_id'
    ],
    'datatype' => [
      'testmodeljoin_id'            => 'number_natural',
      'testmodeljoin_created'       => 'text_timestamp',
      'testmodeljoin_modified'      => 'text_timestamp',
      'testmodeljoin_text'          => 'text',
    ],
    'connection' => 'default'
  ];
}
