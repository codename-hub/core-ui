<?php

namespace codename\core\ui\tests\crud\model;

use codename\core\test\sqlModel;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodelwrongforeignfilter extends sqlModel
{
    /**
     * static configuration
     * for usage in unit tests
     * @var array
     */
    public static array $staticConfig = [
      'field' => [
        'testmodelwrongforeignfilter_id',
        'testmodelwrongforeignfilter_created',
        'testmodelwrongforeignfilter_modified',
        'testmodelwrongforeignfilter_testmodeljoin_id',
        'testmodelwrongforeignfilter_testmodeljoin',
        'testmodelwrongforeignfilter_text',
      ],
      'primary' => [
        'testmodelwrongforeignfilter_id',
      ],
      'children' => [
        'testmodelwrongforeignfilter_testmodeljoin' => [
          'type' => 'foreign',
          'field' => 'testmodelwrongforeignfilter_testmodeljoin_id',
        ],
      ],
      'foreign' => [
        'testmodelwrongforeignfilter_testmodeljoin_id' => [
          'schema' => 'crudtest',
          'model' => 'testmodeljoin',
          'key' => 'testmodeljoin_id',
          'display' => '{$element["testmodeljoin_text"]}',
          'filter' => [
            [],
          ],
        ],
      ],
      'required' => [
        'testmodelwrongforeignfilter_text',
      ],
      'options' => [
      ],
      'datatype' => [
        'testmodelwrongforeignfilter_id' => 'number_natural',
        'testmodelwrongforeignfilter_created' => 'text_timestamp',
        'testmodelwrongforeignfilter_modified' => 'text_timestamp',
        'testmodelwrongforeignfilter_testmodeljoin_id' => 'structure',
        'testmodelwrongforeignfilter_testmodeljoin' => 'virtual',
        'testmodelwrongforeignfilter_text' => 'text',
      ],
      'connection' => 'default',
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $modeldata = [])
    {
        parent::__construct('crudtest', 'testmodelwrongforeignfilter', static::$staticConfig);
    }
}
