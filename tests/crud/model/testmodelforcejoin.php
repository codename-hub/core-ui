<?php

namespace codename\core\ui\tests\crud\model;

use codename\core\test\sqlModel;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodelforcejoin extends sqlModel
{
    /**
     * static configuration
     * for usage in unit tests
     * @var array
     */
    public static array $staticConfig = [
      'field' => [
        'testmodelforcejoin_id',
        'testmodelforcejoin_created',
        'testmodelforcejoin_modified',
        'testmodelforcejoin_testmodeljoin_id',
        'testmodelforcejoin_testmodeljoin',
        'testmodelforcejoin_text',
        'testmodelforcejoin_number_natural',
        'testmodelforcejoin_flag',
        'testmodelforcejoin_unique_single',
        'testmodelforcejoin_unique_multi1',
        'testmodelforcejoin_unique_multi2',
      ],
      'flag' => [
        'example1' => 1,
        'example2' => 2,
        'example4' => 4,
        'example8' => 8,
      ],
      'primary' => [
        'testmodelforcejoin_id',
      ],
      'unique' => [
        'testmodelforcejoin_unique_single',
        ['testmodelforcejoin_unique_multi1', 'testmodelforcejoin_unique_multi2'],
      ],
      'children' => [
        'testmodelforcejoin_testmodeljoin' => [
          'type' => 'foreign',
          'field' => 'testmodelforcejoin_testmodeljoin_id',
          'force_virtual_join' => true,
        ],
      ],
      'foreign' => [
        'testmodelforcejoin_testmodeljoin_id' => [
          'schema' => 'crudtest',
          'model' => 'testmodeljoin',
          'key' => 'testmodeljoin_id',
          'display' => '{$element["testmodeljoin_text"]}',
        ],
      ],
      'options' => [
        'testmodelforcejoin_unique_single' => [
          'length' => 16,
        ],
        'testmodelforcejoin_unique_multi1' => [
          'length' => 16,
        ],
        'testmodelforcejoin_unique_multi2' => [
          'length' => 16,
        ],
      ],
      'datatype' => [
        'testmodelforcejoin_id' => 'number_natural',
        'testmodelforcejoin_created' => 'text_timestamp',
        'testmodelforcejoin_modified' => 'text_timestamp',
        'testmodelforcejoin_testmodeljoin_id' => 'number_natural',
        'testmodelforcejoin_testmodeljoin' => 'virtual',
        'testmodelforcejoin_text' => 'text',
        'testmodelforcejoin_number_natural' => 'number_natural',
        'testmodelforcejoin_flag' => 'number_natural',
        'testmodelforcejoin_unique_single' => 'text',
        'testmodelforcejoin_unique_multi1' => 'text',
        'testmodelforcejoin_unique_multi2' => 'text',
      ],
      'connection' => 'default',
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $modeldata = [])
    {
        parent::__construct('crudtest', 'testmodelforcejoin', static::$staticConfig);
    }
}
