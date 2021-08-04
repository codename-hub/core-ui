<?php
namespace codename\core\ui\tests\crud\model;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodelwrongflag extends \codename\core\test\sqlModel {
  /**
   * @inheritDoc
   */
  public function __CONSTRUCT(array $modeldata = array())
  {
    parent::__CONSTRUCT('crudtest', 'testmodelwrongflag', static::$staticConfig);
  }

  /**
   * static configuration
   * for usage in unit tests
   * @var array
   */
  public static $staticConfig = [
    'field' => [
      'testmodelwrongflag_id',
      'testmodelwrongflag_created',
      'testmodelwrongflag_modified',
      'testmodelwrongflag_text',
      'testmodelwrongflag_flag',
    ],
    'flag' => null,
    'primary' => [
      'testmodelwrongflag_id'
    ],
    'options' => [
    ],
    'datatype' => [
      'testmodelwrongflag_id'                => 'number_natural',
      'testmodelwrongflag_created'           => 'text_timestamp',
      'testmodelwrongflag_modified'          => 'text_timestamp',
      'testmodelwrongflag_text'              => 'text',
      'testmodelwrongflag_flag'              => 'number_natural',
    ],
    'connection' => 'default'
  ];
}
