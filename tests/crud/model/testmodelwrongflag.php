<?php

namespace codename\core\ui\tests\crud\model;

use codename\core\test\sqlModel;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class testmodelwrongflag extends sqlModel
{
    /**
     * static configuration
     * for usage in unit tests
     * @var array
     */
    public static array $staticConfig = [
      'field' => [
        'testmodelwrongflag_id',
        'testmodelwrongflag_created',
        'testmodelwrongflag_modified',
        'testmodelwrongflag_text',
        'testmodelwrongflag_flag',
      ],
      'flag' => null,
      'primary' => [
        'testmodelwrongflag_id',
      ],
      'options' => [
      ],
      'datatype' => [
        'testmodelwrongflag_id' => 'number_natural',
        'testmodelwrongflag_created' => 'text_timestamp',
        'testmodelwrongflag_modified' => 'text_timestamp',
        'testmodelwrongflag_text' => 'text',
        'testmodelwrongflag_flag' => 'number_natural',
      ],
      'connection' => 'default',
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $modeldata = [])
    {
        parent::__construct('crudtest', 'testmodelwrongflag', static::$staticConfig);
    }
}
