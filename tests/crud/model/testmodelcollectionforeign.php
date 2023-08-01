<?php

namespace codename\core\ui\tests\crud\model;

use codename\core\test\sqlModel;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodelcollectionforeign extends sqlModel
{
    /**
     * static configuration
     * for usage in unit tests
     * @var array
     */
    public static array $staticConfig = [
      'field' => [
        'testmodelcollectionforeign_id',
        'testmodelcollectionforeign_created',
        'testmodelcollectionforeign_modified',
        'testmodelcollectionforeign_testmodel_id',
        'testmodelcollectionforeign_text',
      ],
      'primary' => [
        'testmodelcollectionforeign_id',
      ],
      'foreign' => [
        'testmodelcollectionforeign_testmodel_id' => [
          'schema' => 'crudtest',
          'model' => 'testmodel',
          'key' => 'testmodel_id',
          'display' => '{$element["testmodel_text"]}',
          'order' => [
            [
              'field' => 'testmodel_id',
              'direction' => 'ASC',
            ],
          ],
          'filter' => [
            [
              'field' => 'testmodel_id',
              'operator' => '!=',
              'value' => null,
            ],
            [
              'field' => 'testmodel_flag',
              'operator' => '=',
              'value' => 'example1',
            ],
            [
              'field' => 'testmodel_flag',
              'operator' => '!=',
              'value' => 'example2',
            ],
          ],
        ],
      ],
      'datatype' => [
        'testmodelcollectionforeign_id' => 'number_natural',
        'testmodelcollectionforeign_created' => 'text_timestamp',
        'testmodelcollectionforeign_modified' => 'text_timestamp',
        'testmodelcollectionforeign_testmodel_id' => 'number_natural',
        'testmodelcollectionforeign_text' => 'text',
      ],
      'connection' => 'default',
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $modeldata = [])
    {
        parent::__construct('crudtest', 'testmodelcollectionforeign', static::$staticConfig);
    }
}
