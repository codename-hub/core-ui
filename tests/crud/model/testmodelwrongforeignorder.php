<?php

namespace codename\core\ui\tests\crud\model;

use codename\core\test\sqlModel;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodelwrongforeignorder extends sqlModel
{
    /**
     * static configuration
     * for usage in unit tests
     * @var array
     */
    public static array $staticConfig = [
      'field' => [
        'testmodelwrongforeignorder_id',
        'testmodelwrongforeignorder_created',
        'testmodelwrongforeignorder_modified',
        'testmodelwrongforeignorder_testmodeljoin_id',
        'testmodelwrongforeignorder_testmodeljoin',
        'testmodelwrongforeignorder_text',
      ],
      'primary' => [
        'testmodelwrongforeignorder_id',
      ],
      'children' => [
        'testmodelwrongforeignorder_testmodeljoin' => [
          'type' => 'foreign',
          'field' => 'testmodelwrongforeignorder_testmodeljoin_id',
        ],
      ],
      'foreign' => [
        'testmodelwrongforeignorder_testmodeljoin_id' => [
          'schema' => 'crudtest',
          'model' => 'testmodeljoin',
          'key' => 'testmodeljoin_id',
          'display' => '{$element["testmodeljoin_text"]}',
          'order' => [
            [],
          ],
        ],
      ],
      'required' => [
        'testmodelwrongforeignorder_text',
      ],
      'options' => [
      ],
      'datatype' => [
        'testmodelwrongforeignorder_id' => 'number_natural',
        'testmodelwrongforeignorder_created' => 'text_timestamp',
        'testmodelwrongforeignorder_modified' => 'text_timestamp',
        'testmodelwrongforeignorder_testmodeljoin_id' => 'structure',
        'testmodelwrongforeignorder_testmodeljoin' => 'virtual',
        'testmodelwrongforeignorder_text' => 'text',
      ],
      'connection' => 'default',
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $modeldata = [])
    {
        parent::__construct('crudtest', 'testmodelwrongforeignorder', static::$staticConfig);
    }
}
