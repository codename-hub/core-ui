<?php

namespace codename\core\ui\tests\crud\model;

use codename\core\test\sqlModel;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodelwrongforeign extends sqlModel
{
    /**
     * static configuration
     * for usage in unit tests
     * @var array
     */
    public static array $staticConfig = [
      'field' => [
        'testmodelwrongforeign_id',
        'testmodelwrongforeign_created',
        'testmodelwrongforeign_modified',
        'testmodelwrongforeign_testmodeljoin_id',
        'testmodelwrongforeign_testmodeljoin',
        'testmodelwrongforeign_text',
      ],
      'primary' => [
        'testmodelwrongforeign_id',
      ],
      'children' => [
        'testmodelwrongforeign_testmodeljoin' => [
          'type' => 'foreign',
          'field' => 'testmodelwrongforeign_testmodeljoin_id',
        ],
      ],
      'foreign' => [
        'testmodelwrongforeign_testmodeljoin_id' => [
            // 'schema'  => 'crudtest',
            // 'model'   => 'testmodeljoin',
            // 'key'     => 'testmodeljoin_id',
            // 'display' => '{$element["testmodeljoin_text"]}'
        ],
      ],
      'required' => [
        'testmodelwrongforeign_text',
      ],
      'options' => [
      ],
      'datatype' => [
        'testmodelwrongforeign_id' => 'number_natural',
        'testmodelwrongforeign_created' => 'text_timestamp',
        'testmodelwrongforeign_modified' => 'text_timestamp',
        'testmodelwrongforeign_testmodeljoin_id' => 'structure',
        'testmodelwrongforeign_testmodeljoin' => 'virtual',
        'testmodelwrongforeign_text' => 'text',
      ],
      'connection' => 'default',
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $modeldata = [])
    {
        parent::__construct('crudtest', 'testmodelwrongforeign', static::$staticConfig);
    }
}
