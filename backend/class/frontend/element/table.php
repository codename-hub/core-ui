<?php
namespace codename\core\ui\frontend\element;

/**
 * base class for frontend elements
 */
class table extends \codename\core\ui\frontend\element {

    /**
     * @inheritDoc
     */
    public function __construct(array $config = array(), array $data = array())
    {
      $this->templatePath = 'element/table/default';
      parent::__construct($config, $data);
    }

    /**
     * @inheritDoc
     */
    protected function handleData(): array
    {
      // we assume rows and key => value data in this table by default. @TODO: change this to auto-recognize it? (e.g. assoc array)


      $columns = array();
      $rows = array();


      $data = $this->data->getData();


      if($this->config->exists('columns')) {
        // defined columns
        $columns = $this->config->get('columns');
      } else {
        // autogenerate columns
        foreach($data as $key => $value) {
          if(!is_string($key) && is_numeric($key)) {
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
        'footer' => []
      ];

      foreach($columns as $col) {
        $table['max'][$col] = strlen($col);
      }

      foreach($data as $index => $indexValue) {

        $rowValues = [];
        foreach($indexValue as $key => $value) {
          if(in_array($key, $columns)) {

            // detect max column value length
            // for cli output...
            if(strlen($value) > $table['max'][$key]) {
              $table['max'][$key] = strlen($value);
            }

            $rowValues[$key] = $value;
          }
        }

        $table['rows'][] = $rowValues;
      }

      // print_r($this->data);
      // print_r($table);

      return $table;
    }

}
