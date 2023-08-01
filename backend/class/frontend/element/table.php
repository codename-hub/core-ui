<?php

namespace codename\core\ui\frontend\element;

use codename\core\ui\frontend\element;

/**
 * base class for frontend elements
 */
class table extends element
{
    /**
     * {@inheritDoc}
     */
    public function __construct(array $config = [], array $data = [])
    {
        $this->templatePath = 'element/table/default';
        parent::__construct($config, $data);
    }

    /**
     * {@inheritDoc}
     */
    protected function handleData(): array
    {
        // we assume rows and key => value data in this table by default. @TODO: change this to auto-recognize it? (e.g. assoc array)

        $columns = [];

        $data = $this->data->getData();

        if ($this->config->exists('columns')) {
            // defined columns
            $columns = $this->config->get('columns');
        } else {
            // autogenerate columns
            foreach ($data as $key => $value) {
                if (!is_string($key) && is_numeric($key)) {
                    // numeric index => ROWS!
                    //
                    $columns = array_unique(array_merge($columns, array_keys($value)));
                } else {
                    // ASSOC array!
                    // key => value !
                }
            }
        }

        // generate table
        //
        $table = [
          'max' => [],
          'header' => $columns,
          'rows' => [],
          'footer' => [],
        ];

        foreach ($columns as $col) {
            $table['max'][$col] = strlen($col);
        }

        foreach ($data as $indexValue) {
            $rowValues = [];
            foreach ($indexValue as $key => $value) {
                if (in_array($key, $columns)) {
                    // convert non-string to string, somehow
                    if (!is_string($value)) {
                        $value = print_r($value, true);
                    }

                    // detect max column value length
                    // for cli output...
                    if (strlen($value) > $table['max'][$key]) {
                        $table['max'][$key] = strlen($value);
                    }

                    $rowValues[$key] = $value;
                }
            }

            $table['rows'][] = $rowValues;
        }

        return $table;
    }
}
