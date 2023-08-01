<?php

namespace codename\core\ui\tests\crud\model;

use codename\core\test\sqlModel;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodeljoin extends sqlModel
{
    /**
     * static configuration
     * for usage in unit tests
     * @var array
     */
    public static array $staticConfig = [
      'field' => [
        'testmodeljoin_id',
        'testmodeljoin_created',
        'testmodeljoin_modified',
        'testmodeljoin_text',
      ],
      'primary' => [
        'testmodeljoin_id',
      ],
      'datatype' => [
        'testmodeljoin_id' => 'number_natural',
        'testmodeljoin_created' => 'text_timestamp',
        'testmodeljoin_modified' => 'text_timestamp',
        'testmodeljoin_text' => 'text',
      ],
      'connection' => 'default',
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $modeldata = [])
    {
        parent::__construct('crudtest', 'testmodeljoin', static::$staticConfig);
    }
}
