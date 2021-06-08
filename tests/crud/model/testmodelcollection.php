<?php
namespace codename\core\ui\tests\crud\model;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodelcollection extends \codename\core\test\sqlModel {
  /**
   * @inheritDoc
   */
  public function __CONSTRUCT(array $modeldata = array())
  {
    parent::__CONSTRUCT('crudtest', 'testmodelcollection', static::$staticConfig);
  }

  /**
   * static configuration
   * for usage in unit tests
   * @var array
   */
  public static $staticConfig = [
    'field' => [
      'testmodelcollection_id',
      'testmodelcollection_created',
      'testmodelcollection_modified',
      'testmodelcollection_testmodel_id',
      'testmodelcollection_text',
    ],
    'primary' => [
      'testmodelcollection_id'
    ],
    'foreign' => [
      'testmodelcollection_testmodel_id' => [
        'schema'  => 'crudtest',
        'model'   => 'testmodel',
        'key'     => 'testmodel_id',
        'display' => '{$element["testmodel_text"]}'
      ],
    ],
    'datatype' => [
      'testmodelcollection_id'            => 'number_natural',
      'testmodelcollection_created'       => 'text_timestamp',
      'testmodelcollection_modified'      => 'text_timestamp',
      'testmodelcollection_testmodel_id'  => 'number_natural',
      'testmodelcollection_text'          => 'text',
    ],
    'connection' => 'default'
  ];
}
