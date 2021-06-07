<?php
namespace codename\core\ui\tests\crud\model;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodel extends \codename\core\test\sqlModel {
  /**
   * @inheritDoc
   */
  public function __CONSTRUCT(array $modeldata = array())
  {
    parent::__CONSTRUCT('crudtest', 'testmodel', static::$staticConfig);
  }

  /**
   * static configuration
   * for usage in unit tests
   * @var array
   */
  public static $staticConfig = [
    'field' => [
      'testmodel_id',
      'testmodel_created',
      'testmodel_modified',
      'testmodel_testmodeljoin_id',
      'testmodel_testmodeljoin',
      'testmodel_text',
      'testmodel_number_natural',
      'testmodel_flag',
      'testmodel_unique_single',
      'testmodel_unique_multi1',
      'testmodel_unique_multi2',
    ],
    'flag' => [
      'example1' => 1,
      'example2' => 2,
      'example4' => 4,
      'example8' => 8,
    ],
    'primary' => [
      'testmodel_id'
    ],
    'unique' => [
      'testmodel_unique_single',
      [ 'testmodel_unique_multi1', 'testmodel_unique_multi2' ],
    ],
    'children' => [
      'testmodel_testmodeljoin' => [
        'type' => 'foreign',
        'field' => 'testmodel_testmodeljoin_id',
      ]
    ],
    'foreign' => [
      'testmodel_testmodeljoin_id' => [
        'schema'  => 'crudtest',
        'model'   => 'testmodeljoin',
        'key'     => 'testmodeljoin_id',
        'display' => '{$element["testmodeljoin_text"]}'
      ],
    ],
    'options' => [
      'testmodel_unique_single' => [
        'length' => 16
      ],
      'testmodel_unique_multi1' => [
        'length' => 16
      ],
      'testmodel_unique_multi2' => [
        'length' => 16
      ],
    ],
    'datatype' => [
      'testmodel_id'                => 'number_natural',
      'testmodel_created'           => 'text_timestamp',
      'testmodel_modified'          => 'text_timestamp',
      'testmodel_testmodeljoin_id'  => 'number_natural',
      'testmodel_testmodeljoin'     => 'virtual',
      'testmodel_text'              => 'text',
      'testmodel_number_natural'    => 'number_natural',
      'testmodel_flag'              => 'number_natural',
      'testmodel_unique_single'     => 'text',
      'testmodel_unique_multi1'     => 'text',
      'testmodel_unique_multi2'     => 'text',
    ],
    'connection' => 'default'
  ];
}
