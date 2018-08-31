<?php
namespace codename\core\ui;
use \codename\core\event;
use \codename\core\ui;

use codename\core\exception;

/**
 * The CRUD generator uses it's model to display the model's content.
 * <br />It is capable of Creating, Reading, Updating and Deleting data in the models.
 * It utilizes several frontend resources located in the core frontend folder.
 * <br />Override these templates by adding these files in your application's directory
 * @package codename\core\ui
 */
class crud extends \codename\core\bootstrapInstance {

    /**
     * The desired field cannot be found in the current model.
     * @var string
     */
    CONST EXCEPTION_MAKEFORM_FIELDNOTFOUNDINMODEL = 'EXCEPTION_MAKEFORM_FIELDNOTFOUNDINMODEL';

    /**
     * The desired field cannot be found in the current model.
     * @var string
     */
    CONST EXCEPTION_MAKEFIELD_FIELDNOTFOUNDINMODEL = 'EXCEPTION_MAKEFIELD_FIELDNOTFOUNDINMODEL';

    /**
     * The foreign-model reference object is not valid.
     * @todo use value-object here
     * @var string
     */
    CONST EXCEPTION_MAKEFIELD_INVALIDREFERENCEOBJECT = 'EXCEPTION_MAKEFIELD_INVALIDREFERENCEOBJECT';

    /**
     * The model order object is not valid.
     * @todo use value-object here
     * @var string
     */
    CONST EXCEPTION_MAKEFIELD_INVALIDORDEROBJECT = 'EXCEPTION_MAKEFIELD_INVALIDORDEROBJECT';

    /**
     * The model filter object is not valid.
     * @todo use value-object here
     * @var string
     */
    CONST EXCEPTION_MAKEFIELD_INVALIDFILTEROBJECT = 'EXCEPTION_MAKEFIELD_INVALIDFILTEROBJECT';

    /**
     * The model filter flag operator is not valid. should be = or !=
     * @todo use value-object here
     * @var string
     */
    CONST EXCEPTION_MAKEFIELD_FILTER_FLAG_INVALIDOPERATOR = 'EXCEPTION_MAKEFIELD_FILTER_FLAG_INVALIDOPERATOR';

    /**
     * The action button object is not valid
     * @todo use value-object here
     * @var string
     */
    CONST EXCEPTION_ADDACTION_INVALIDACTIONOBJECT = 'EXCEPTION_ADDACTION_INVALIDACTIONOBJECT';

    /**
     * Contains the request entry that will hold all filters applied to the crud list
     * @var string
     */
    CONST CRUD_FILTER_IDENTIFIER = '_cf';

    /**
     * Contains the model this CRUD instance is based upon
     * @var \codename\core\model
     */
    protected $model = null;

    /**
     * Contains the form instance we are working with
     * @var \codename\core\ui\form
     */
    protected $form = null;

    /**
     * Contains all the fields that will be displayed in the CRUD generator
     * @var array
     */
    protected $fields = array();

    /**
     * Contains the ID of the CRUD form
     * @var string
     */
    protected $form_id = 'crud_default_form';

    /**
     * Contains the dataset of the model. May be empty when creating a new entry
     * @var \codename\core\datacontainer
     */
    protected $data = null;

    /**
     * contains an instance of config storage
     * @var \codename\core\config
     */
    protected $config = null;

    /**
     * Contains a list of fields and their modifiers
     * @var array $modifiers
     */
    protected $modifiers = array();

    /**
     * Contains a list of row modifiers (callables)
     * @var callable[] $rowModifiers
     */
    protected $rowModifiers = array();

    /**
     * If true, does not render the form
     * Instead, output form config
     * @var bool
     */
    public $outputFormConfig = false;

    /**
     * Creates the instance and sets the $model of this instance. Also creates the form instance
     * @param  \codename\core\model   $model       [description]
     * @param  array|null             $requestData [description]
     */
    public function __CONSTRUCT(\codename\core\model $model, array $requestData = null) {
        $this->eventCrudFormInit = new event('EVENT_CRUD_FORM_INIT');
        $this->eventCrudBeforeValidation = new event('EVENT_CRUD_BEFORE_VALIDATION');
        $this->eventCrudAfterValidation = new event('EVENT_CRUD_AFTER_VALIDATION');
        $this->eventCrudValidation = new event('EVENT_CRUD_VALIDATION');
        $this->eventCrudBeforeSave = new event('EVENT_CRUD_BEFORE_SAVE');
        $this->eventCrudSuccess = new event('EVENT_CRUD_SUCCESS');
        $this->model = $model;
        if($requestData != null) {
          $this->setFormNormalizationData($requestData);
        }
        $this->setConfig();
        $this->setChildCruds();
        $this->updateChildCrudConfigs();
        $this->form = new \codename\core\ui\form(array(
          'form_action' => ui\app::getUrlGenerator()->generateFromParameters(array(
            'context' => $this->getRequest()->getData('context'),
            'view' => $this->getRequest()->getData('view')
          )),
          'form_method' => 'post',
          'form_id' => $this->form_id
        ));
        return $this;
    }

    /**
     * [protected description]
     * @var \codename\core\ui\crud[]
     */
    protected $childCruds = [];

    /**
     * reads config from the 'children' key
     * and creates instances for those children (cruds)
     */
    protected function setChildCruds() {
      // apply nested children config
      if($this->config->exists('children')) {
        foreach($this->config->get('children') as $child) {

          //
          // the master child configuration
          // in the model
          //
          $childConfig = $this->model->config->get('children>'.$child);

          //
          // optional crud/form overrides
          //
          $childCrudConfig = $this->config->get('children_config>'.$child);
          // DEBUG: \codename\core\app::getResponse()->setData('debug_crud_setchildren_'.$child, $childCrudConfig);

          if($childConfig != null) {
            // we handle a single-ref foreign key field as base
            // for a nested model as a virtual object key
            if($childConfig['type'] == 'foreign') {

              // get the foreign key config
              $foreignConfig = $this->model->config->get('foreign>'.$childConfig['field']);
              // get the respective model
              $childModel = $this->getModel($foreignConfig['model'], $foreignConfig['app'] ?? '', $foreignConfig['vendor'] ?? '');
              // build a child crud
              $crud = new \codename\core\ui\crud($childModel, $this->getFormNormalizationData()[$child] ?? []);

              //
              // Handle optional configs
              //
              if(isset($childCrudConfig['crud'])) {
                // DEBUG: \codename\core\app::getResponse()->setData('debug_crud_setchildren_'.$child.'_crud', $childCrudConfig['crud']);
                $crud->setConfig($childCrudConfig['crud']);
              }
              if(isset($childCrudConfig['form'])) {
                // DEBUG: \codename\core\app::getResponse()->setData('debug_crud_setchildren_'.$child.'_form', $childCrudConfig['form']);
                $crud->useForm($childCrudConfig['form']);
              }

              // make only a part of the request visible to the crud instance
              // DEBUG: $this->getResponse()->setData('debug_crud_'.$child, $this->getFormNormalizationData()[$child] ?? []);
              $crud->setFormNormalizationData($this->getFormNormalizationData()[$child] ?? []);

              // store it for later
              $this->childCruds[$child] = $crud;

              // join the model upon the current
              $this->getMyModel()->addModel($crud->getMyModel(), \codename\core\model\plugin\join::TYPE_LEFT, $childConfig['field'], $foreignConfig['key']);

              //
              // Enable virtual field results
              //
              if(interface_exists('\\codename\\core\\model\\virtualFieldResultInterface') && $this->getMyModel() instanceof \codename\core\model\virtualFieldResultInterface) {
                $this->getMyModel()->setVirtualFieldResult(true);
              }
            } else if($childConfig['type'] === 'collection') {

              // Collection, not a crud.

              // get the collection config
              $collectionConfig = $this->model->config->get('collection>'.$child);
              // get the respective model
              $childModel = $this->getModel($collectionConfig['model'], $collectionConfig['app'] ?? '', $collectionConfig['vendor'] ?? '');

              $this->getMyModel()->addCollectionModel($childModel, $child);

              //
              // Enable virtual field results
              //
              if(interface_exists('\\codename\\core\\model\\virtualFieldResultInterface') && $this->getMyModel() instanceof \codename\core\model\virtualFieldResultInterface) {
                $this->getMyModel()->setVirtualFieldResult(true);
              }
            }
          } else {
            throw new exception(self::EXCEPTION_CRUD_CHILDREN_CONFIG_MODEL_CONFIG_CHILDREN_IS_NULL, exception::$ERRORLEVEL_ERROR, $child);
          }
        }
      }
    }

    /**
     * exception thrown if a given model does not define child configuration
     * but crud tries to use it
     * @var string
     */
    const EXCEPTION_CRUD_CHILDREN_CONFIG_MODEL_CONFIG_CHILDREN_IS_NULL = 'EXCEPTION_CRUD_CHILDREN_CONFIG_MODEL_CONFIG_CHILDREN_IS_NULL';

    /**
     * loads the crud config
     * defaults to schema_table, if no identifier (or '') is specified
     * @param  string                $identifier [description]
     * @return \codename\core\config             [description]
     */
    protected function loadConfig(string $identifier = '') : \codename\core\config{
      if($identifier == '') {
        $identifier = $this->getMyModel()->schema . '_' . $this->getMyModel()->table;
      }
      return new \codename\core\config\json('config/crud/' . $identifier . '.json', true);
    }

    /**
     * set config by identifier
     * @param  string                 $identifier [description]
     * @return \codename\core\ui\crud             [description]
     */
    public function setConfig(string $identifier = '') : \codename\core\ui\crud {
      $this->config = $this->loadConfig($identifier);

      if($identifier !== '') {
        $this->updateChildCrudConfigs();
      }

      return $this;
    }

    /**
     * [updateChildCrudConfigs description]
     * @return [type] [description]
     */
    protected function updateChildCrudConfigs() {
      if($this->config->exists('children_config')) {
        $childrenConfig = $this->config->get('children_config');
        foreach($childrenConfig as $childName => $childConfig) {
          if(isset($this->childCruds[$childName])) {
            if(isset($childConfig['crud'])) {
              $this->childCruds[$childName]->setConfig($childConfig['crud']);
            }
            if(isset($childConfig['form'])) {
              $this->childCruds[$childName]->useForm($childConfig['form']);
            }
          }
        }
      }
    }

    /**
     * access data array currently used by this crud
     * @param  string $key [description]
     * @return array|null|mixed
     */
    public function getData(string $key = '') {
      if($this->data !== null) {
        return $this->data->getData($key);
      } else {
        return null;
      }
    }

    /**
     * I will add a modifier for a field.
     * <br />Modifiers are used to change the output of a field in the CRUD list.
     * @example addModifier('status_id', function($row) {return '<span class="pill">'.$row['status_name'].'</span>';});
     * @param string $field
     * @param callable $modifier
     * @return \codename\core\ui\crud
     */
    public function addModifier(string $field, callable $modifier) : \codename\core\ui\crud {
        $this->modifiers[$field] = $modifier;
        return $this;
    }

    /**
     * default column ordering
     * @var string[]
     */
    protected $columnOrder = array();

    /**
     * configures column ordering for crud
     * @param array $columns [description]
     * @return \codename\core\ui\crud
     */
    public function setColumnOrder(array $columns = array()) : \codename\core\ui\crud {
      $this->columnOrder = $columns;
      return $this;
    }

    /**
     * I will add a ROW modifier
     * which can change the output <tr> elements attributes
     * @example addModifier('status_id', function($row) {return '<span class="pill">'.$row['status_name'].'</span>';});
     * @param  callable               $modifier [description]
     * @return \codename\core\ui\crud           [description]
     */
    public function addRowModifier(callable $modifier) : \codename\core\ui\crud {
        $this->rowModifiers[] = $modifier;
        return $this;
    }

    /**
     * Returns a list of the entries in the model and paginate, filter and order it
     * @return void
     */
    public function listview() {
        $visibleFields = $this->config->get('visibleFields');

        // Only append primarykey, if not added to visibleFields
        if(!in_array($this->getMyModel()->getPrimarykey(), $visibleFields)) {
          $visibleFields[] = $this->getMyModel()->getPrimarykey();
        }

        $formattedFields = [];
        if(!is_null($this->getMyModel()->config->get('foreign'))) {
          $foreignKeys = $this->getMyModel()->config->get('foreign');
          /*
          $formattedFields = array_map(function($key, $foreign) {
            return [ $key => $key.'_FORMATTED' ];
          }, array_keys($foreignKeys), $foreignKeys);
          */
          $formattedFields = array_reduce(array_keys($foreignKeys), function ($carry, $key) use ($foreignKeys){
              $carry[$key] = $key.'_FORMATTED'; // $foreignKeys[$key];
              return $carry;
          }, $foreignKeys);
        }
        $this->getResponse()->setData('formattedFields', $formattedFields);

        $this->getResponse()->setData('enable_displayfieldselection', ($this->config->exists('displayFieldSelection') ? $this->config->get('displayFieldSelection') : false));

        if($this->config->exists('availableFields')) {
          $availableFields = $this->config->get('availableFields');
        } else {
          // enable ALL fields of the model to be displayed
          $availableFields = $this->getMyModel()->config->get('field');
        }

        // remove all disabled fields
        if($this->config->exists('disabled')) {
          $availableFields = array_diff($availableFields, $this->config->get('disabled'));
        }

        $displayFields = array();

        // display fields are either the visibleFields (defined in config) or submitted
        // in the latter case, we have to check for legitimacy first.
        if($this->getRequest()->isDefined('display_selectedfields') && $this->getResponse()->getData('enable_displayfieldselection') == true) {
          $selectedFields = $this->getRequest()->getData('display_selectedfields');
          if(is_array($selectedFields)) {
            foreach($selectedFields as $displayField) {
              if(in_array($displayField, $availableFields)) {
                $displayFields[] = $displayField;
              }
            }
          }
        }

        if(sizeof($displayFields) > 0) {
          $visibleFields = $displayFields;
        }

        if(!in_array($this->getMyModel()->getPrimarykey(), $visibleFields)) {
          $visibleFields[] = $this->getMyModel()->getPrimarykey();
        }

        $fieldLabels = [];
        foreach(array_merge($availableFields, $formattedFields) as $field) {
          $fieldLabels[$field] = app::getTranslate()->translate('DATAFIELD.'.$field);
        }
        $this->getResponse()->setData('labels', $fieldLabels);

        //
        // NOTE: CHANGED on 2018-08-31
        // If we explicitly add fields here
        // the model may rush into a severe result normalization situation.
        //
        // $this->getMyModel()->addField(implode(',', $visibleFields));
        //

        $this->applyFilters();

        if($this->allowPagination) {
          $this->makePagination();
        }

        foreach ($this->config->get("order") as $order) {
            $this->getMyModel()->addOrder($order['field'], $order['direction']);
        }

        // if($this->getConfig()->exists('export>_security>group')) {
        //   $this->getResponse()->setData('enable_export', app::getAuth()->memberOf($this->getConfig()->get('export>_security>group')));
        // } else {
        //   $this->getResponse()->setData('enable_export', false);
        // }

        $fieldActions = $this->config->get("action>field") ?? array();
        $filters = $this->config->get('visibleFilters', array());

        foreach($filters as $tFName => &$fData) {

          $specifier = explode('.', $tFName);
          $useModel = $this->getMyModel();

          $fName = $specifier[count($specifier)-1];

          if(count($specifier) == 2) {
            // we have a model/table reference
            $useModel = $this->getModel($specifier[0]);
          }

          // handle date_range filter.
          // @TODO: create a more general UI-specific function that returns datatype-specific fields/filter-UI elements
          if(in_array($useModel->config->get('datatype>'.$fName), array('text_timestamp', 'text_date'))) {
            $fData['filtertype'] = 'date_range';
          }

          // field is a foreign key
          if($fData['wildcard'] == false && $useModel->config->exists('foreign>'.$fName)) {
            // modify filter, add filteroptions
            $fConfig = $useModel->config->get('foreign>'.$fName);
            $fModel = $this->getModel($fConfig['model']);
            if(isset($fConfig['filter'])) {
              foreach($fConfig['filter'] as $modelFilter) {
                $fModel->addFilter($modelFilter['field'],$modelFilter['value'],$modelFilter['operator']);
              }
            }
            $fResult = $fModel->search()->getResult();
            $fData['filteroptions'] = array();
            foreach($fResult as $element) {
              $ret = '';
              eval('$ret = "' . $fConfig['display'] . '";');
              $fData['filteroptions'][$element[$fConfig['key']]] = $ret;
            }
          }

          // field is flag field
          if($fName == $useModel->getIdentifier() . '_flag') {
            $flagConfig = $useModel->config->get('flag');
            $fData['filteroptions'] = array();
            foreach($flagConfig as $flagName => $flagValue) {
              $fData['filteroptions'][$flagValue] = app::getTranslate()->translate('DATAFIELD.' . $fName . '_' . $flagName);
            }
          }
        }

        $visibleFields = array_unique(array_merge($this->columnOrder, $visibleFields, array_keys($this->modifiers)));

        // Send data to the response
        $this->getResponse()->setData('rows', $this->makeFields($this->getMyModel()->search()->getResult(), $visibleFields));
        $this->getResponse()->setData('topActions', $this->prepareActionsOutput($this->config->get("action>top") ?? []));
        $this->getResponse()->setData('bulkActions', $this->prepareActionsOutput($this->config->get("action>bulk") ?? []));
        $this->getResponse()->setData('elementActions', $this->prepareActionsOutput($this->config->get("action>element") ?? []));
        $this->getResponse()->setData('fieldActions', $this->prepareActionsOutput($fieldActions) ?? []);
        $this->getResponse()->setData('visibleFields', $visibleFields);
        $this->getResponse()->setData('availableFields', $availableFields);

        $this->getResponse()->setData('filters', $filters);
        $this->getResponse()->setData('crud_filter_identifier', self::CRUD_FILTER_IDENTIFIER);
        $this->getResponse()->setData('filters_used', $this->getRequest()->getData(self::CRUD_FILTER_IDENTIFIER));
        $this->getResponse()->setData('filters_unused', $filters);
        $this->getResponse()->setData('enable_search_bar', $this->config->exists("visibleFilters>_search"));
        $this->getResponse()->setData('modelinstance', $this->getMyModel());

        // editable mode:
        if($this->getRequest()->getData('crud_editable')) {
          $form = $this->makeForm(null, false);
          $this->getResponse()->setData('formconfig', $form->output(true));
        }
        return;
    }

    /**
     * prepare action configs for output
     *
     * @param  array $actions [description]
     * @return array          [description]
     */
    protected function prepareActionsOutput(array $actions) : array {
      $handled = [];
      foreach($actions as $key => $value) {
        // we can't do this at the moment using our Vue App framework
        // if(!array_key_exists('context', $value)) {
        //     $value['context'] = app::getRequest()->getData('context');
        // }
        if(array_key_exists('_security', $value) && array_key_exists('group', $value['_security'])) {
          if(!app::getAuth()->memberOf($value['_security']['group'])) {
            continue;
          }
        }
        if(array_key_exists('condition', $value)) {
            eval($value['condition']);
            if(!$condition) {
                continue;
            }
        }
        $value['display'] = app::getTranslate()->translate("BUTTON.BTN_" . $key);

        $handled[$key] = $value;
      }
      return $handled;
    }

    /**
     * internal and temporary pagination switch (for exporting)
     * @var bool
     */
    protected $allowPagination = true;

    /**
     *
     */
    public function export() {
      // disable limit and offset temporarily
      $this->allowPagination = false;
      $this->listview();
      $this->allowPagination = true;
    }

    /**
     * Adds a top action
     * @param array $action
     * @return void
     */
    public function addTopaction(array $action) {
        $this->addAction('top', $action);
        return;
    }

    /**
     * Adds a bulk action
     * @param array $action
     * @return void
     */
    public function addBulkaction(array $action) {
        $this->addAction('bulk', $action);
        return;
    }

    /**
     * Adds an element action
     * @param array $action
     * @return void
     */
    public function addElementaction(array $action) {
        $this->addAction('element', $action);
        return;
    }

    /**
     * Adds the important fields to the form instance of this crud editor
     *
     * @param  string|null          $primarykey      [primary key of the entry to be used as value base or null]
     * @param  bool                 $addSubmitButton [whether the form should add a submit button field by default]
     * @return \codename\core\ui\form                [the form (also contained in this crud instance, accessible via ->getForm())]
     */
    public function makeForm($primarykey = null, $addSubmitButton = true) : \codename\core\ui\form {
        $this->useEntry($primarykey);

        // set request data only visible for this form
        // usually, this is the complete request
        // but it may only be a part of it.
        $this->getForm()->setFormRequest($this->getFormNormalizationData());

        if($this->config->exists('tag')) {
          $this->getForm()->config['form_tag'] = $this->config->get('tag');
        }

        // use "field", if defined in crud config
        if($this->config->exists('field') && count($this->fields) === 0) {
          $this->fields = $this->config->get('field');
        }

        if(count($this->fields) == 0 && count($this->getForm()->getFieldsets()) == 0) {
            $this->fields = $this->getMyModel()->config->get('field');
        }

        // Be sure to show the primary key in the form
        array_push($this->fields, $this->getMyModel()->getPrimarykey());
        $this->fields = array_unique($this->fields);

        foreach($this->fields as $field) {
            if($this->config->exists('disabled') && is_array($this->config->get('disabled')) && in_array($field, $this->config->get('disabled'))) {
                continue;
            }

            if(in_array($field, array($this->getMyModel()->table . "_modified", $this->getMyModel()->table . "_created"))) {
                continue;
            }
            if(!in_array($field, $this->getMyModel()->config->get('field'))) {
                throw new \codename\core\exception(self::EXCEPTION_MAKEFORM_FIELDNOTFOUNDINMODEL, \codename\core\exception::$ERRORLEVEL_ERROR, $field);
            }

            // exclude child model fields that have an active children config for this crud
            if($this->config->exists('children') && $this->getMyModel()->config->exists('children')) {
              // if field exists in a child config field reference
              $found = false;
              foreach($this->config->get('children') as $childField) {
                if(($childConfig = $this->getMyModel()->config->get('children>'.$childField)) !== null) {
                  if($childConfig['type'] === 'foreign' && $childConfig['field'] == $field) {
                    $found = true;
                    break;
                  } else if($childField === $field && $childConfig['type'] === 'collection') {
                    // $found = true;
                    break;
                  }
                }
              }
              if($found) {
                continue;
              }
            }

            if($field == $this->getMyModel()->table . '_flag') {
                $flags = $this->getMyModel()->config->get('flag');
                if(!is_array($flags)) {
                    continue;
                }

                $value = [];
                $elements = [];

                foreach($flags as $flagname => $flag) {
                  $value[$flagname] = !is_null($this->data) ? $this->getMyModel()->isFlag($flag, $this->data->getData()) : false;
                  $elements[] = [
                    'name'    => $flagname,
                    'display' => app::getTranslate()->translate('DATAFIELD.' . $field . '_' . $flagname),
                    'value'   => $flag
                  ];
                }

                $fielddata = array (
                    'field_name' => $this->getMyModel()->table . '_flag',
                    'field_type' => 'multicheckbox',
                    'field_datatype' => 'structure',
                    'field_title' => app::getTranslate()->translate('DATAFIELD.' . $field),
                    'field_multiple' => true,
                    'field_value' => $value,
                    'field_elements' => $elements,
                    'field_idfield' => 'name',
                    'field_displayfield' => '{$element["display"]}', // todo: translate!
                    'field_valuefield' => 'value'
                );

                if($this->readOnly) {
                  $fielddata['field_readonly'] = true;
                }

                $c = &$this->onCreateFormfield;
                if($c !== null && is_callable($c)) {
                  $c($fielddata);
                }

                $formField = new \codename\core\ui\field($fielddata);

                $c = &$this->onFormfieldCreated;
                if($c !== null && is_callable($c)) {
                  $c($formField);
                }

                $this->getForm()->addField($formField);

                continue;
            }

            $this->getForm()->addField($this->makeField($field))->setType('compact');
        }

        if($addSubmitButton) {
          $this->getForm()->addField((new field(array(
            'field_name' => 'name',
            'field_title' => 'submit',
            'field_description' => 'description',
            'field_id' => 'submit',
            'field_type' => 'submit',
            'field_value' => app::getTranslate()->translate('BUTTON.BTN_SAVE')
          )))->setType('compact'));
        }

        $form = $this->getForm();

        // pass the output config type to the form instance
        $form->outputConfig = $this->outputFormConfig;

        return $form;
    }

    /**
     * This event will be fired whenever a method of this CRUD instance generates a form instance.
     * <br />Use this event to alter the current form of the CRUD instance (e.g. for asking for more fields)
     * @var event
     */
    public $eventCrudFormInit;

    /**
     * This event is fired before the validation starts.
     * @example Imagine cases where you don't want a user to input data but you must
     * <br />add it to the entry, because the missing fields would violate the model's
     * <br />constraints. Here you can do anything you want with the entry array.
     * @var event
     */
    public $eventCrudBeforeValidation;

    /**
     * This event is fired after validation has been successful.
     * @var event
     */
    public $eventCrudAfterValidation;

    /**
     * This event is fired after validation has been successful.
     * We might run additional validators here.
     * output must be either null, empty array or errors found in additional validators
     * @var event
     */
    public $eventCrudValidation;

    /**
     * This event is fired whenever the CRUD generator wants to save a validated entry (or updates)
     * <br />to a model. It is given the $data and must return the $data.
     * @example Imagine you want to manipulate entries on a model when saving the entry
     * <br />from the CRUD generator. This is version will happen after the validation.
     * @var event
     */
    public $eventCrudBeforeSave;

    /**
     * This event is fired whenever the CRUD generator successfully completed an operation
     * <br />to a model. It is given the $data.
     * @var event
     */
    public $eventCrudSuccess;

    /**
     * Returns a form HTML code for creating an object. After validating the data in the form class AND validating the data in the model class, we will try to store the data in the model.
     */
    public function create() {
        $this->getResponse()->setData('context', 'crud');

        $form = $this->makeForm();

        // OLD: $hookval = app::getHook()->fire(\codename\core\hook::EVENT_CRUD_CREATE_FORM_INIT, $form);
        // Fire the form init event
        $hookval = $this->eventCrudFormInit->invokeWithResult($this, $form);
        if(is_object($hookval) && $hookval instanceof \codename\core\ui\form) {
            $form = $hookval;
        }

        if(!$form->isSent()) {
            $this->getResponse()->setData('form', $form->output($this->outputFormConfig));
            return;
        }

        $data = $this->getMyModel()->normalizeData( $this->getFormNormalizationData() );

        // OLD: $newData = app::getHook()->fire(\codename\core\hook::EVENT_CRUD_CREATE_BEFORE_VALIDATION, $data);
        // Fire the before validation event
        $newData = $this->eventCrudBeforeValidation->invokeWithResult($this, $data);
        if(is_array($newData)) {
            $data = $newData;
        }

        // form validation before model validation
        if(!$form->isValid()) {
          $this->getResponse()->setStatus(\codename\core\response::STATUS_INTERNAL_ERROR);
          $this->getResponse()->setData('errors', $form->getErrorstack()->getErrors());
          $this->getResponse()->setData('view', 'validation_error');
          return;
        }

        if(!$this->getMyModel()->isValid($data)) {
            $this->getResponse()->setStatus(\codename\core\response::STATUS_INTERNAL_ERROR);
            $this->getResponse()->setData('errors', $this->getMyModel()->getErrors());
            $this->getResponse()->setData('view', 'save_error');
            return;
        }

        // Fire hook after successful validation
        // OLD app::getHook()->fire(\codename\core\hook::EVENT_CRUD_CREATE_AFTER_VALIDATION, $data);
        $this->eventCrudAfterValidation->invoke($this, $data);

        // Fire hook for additional validators
        // OLD: $errors = app::getHook()->fire(\codename\core\hook::EVENT_CRUD_CREATE_VALIDATION, $data);
        $errorResults = $this->eventCrudValidation->invokeWithAllResults($this, $data);

        $errors = array();
        foreach($errorResults as $errorCollection) {
          if(count($errorCollection) > 0) {
            $errors = array_merge($errors, $errorCollection);
          }
        }

        if(count($errors) > 0) {
          $this->getResponse()->setStatus(\codename\core\response::STATUS_INTERNAL_ERROR);
          $this->getResponse()->setData('errors', $errors);
          $this->getResponse()->setData('view', 'save_error');
          return;
        }

        // OLD: app::getHook()->fire(\codename\core\hook::EVENT_CRUD_CREATE_BEFORE_SAVE, $data);
        $this->eventCrudBeforeSave->invoke($this, $data);

        $this->getMyModel()->saveWithChildren($data);

        // OLD: app::getHook()->fire(\codename\core\hook::EVENT_CRUD_CREATE_SUCCESS, $data);
        $this->eventCrudSuccess->invoke($this, $data);

        $this->getResponse()->setData('view', 'crud_success');
    }

    /**
     * @todo DOCUMENTATION
     */
    public function bulkDelete() {
        if(!$this->getRequest()->isDefined($this->getMyModel()->getPrimarykey())) {
            return;
        }
    }

    /**
     * [bulkEdit description]
     * @return void
     */
    public function bulkEdit() {
      if($this->getRequest()->isDefined('data')) {
        $data = $this->getRequest()->getData('data');

        //
        // Validate
        //
        foreach($data as $entry) {

          // get full entry with modified delta
          $currentEntry = $this->getMyModel()->load($entry[$this->getMyModel()->getPrimarykey()]);
          $currentEntry = array_replace_recursive($currentEntry, $entry);

          // TODO: validate using bulk form?

          if(!$this->getMyModel()->isValid($currentEntry)) {
              $this->getResponse()->setStatus(\codename\core\response::STATUS_INTERNAL_ERROR);
              $this->getResponse()->setData('errors', $this->getMyModel()->getErrors());
              $this->getResponse()->setData('view', 'save_error');
              return;
          }
        }

        //
        // Save
        //
        $transaction = new \codename\core\transaction('crud_bulk_edit', [ $this->getMyModel() ]);
        $transaction->start();

        foreach($data as $entry) {
          //
          // TODO: how to handle delta edits on nested models?
          //
          $this->getMyModel()->saveWithChildren($entry);
        }

        $transaction->end();

      } else {
        throw new exception('CRUD_BULK_EDIT_DATA_UNDEFINED', exception::$ERRORLEVEL_ERROR);
      }
    }

    /**
     * returns the request data
     * that is used for normalization
     * in form-related functions
     * @return array
     */
    protected function getFormNormalizationData() : array {
      if($this->formNormalizationData == null) {
        $this->setFormNormalizationData($this->getRequest()->getData());
      }
      return $this->formNormalizationData;
    }

    /**
     * [protected description]
     * @var array
     */
    protected $formNormalizationData = null;

    /**
     * sets the underlying data used during normalization
     * in the normal use case, this is the pure request data
     * @param array $data [description]
     */
    public function setFormNormalizationData(array $data) {
      $this->formNormalizationData = $data;
    }

    /**
     * Returnes the form HTML code for editing an existing entry. Will make sure the given data is compliant to the form's and model's configuration
     * @param string|int $primarykey
     */
    public function edit($primarykey) {
        $form = $this->makeForm($primarykey);

        // OLD: $hookval = app::getHook()->fire(\codename\core\hook::EVENT_CRUD_EDIT_FORM_INIT, $form);
        // Fire the form init event
        $hookval = $this->eventCrudFormInit->invokeWithResult($this, $form);

        if(is_object($hookval) && $hookval instanceof \codename\core\ui\form) {
            $form = $hookval;
        }

        if($this->config->exists('action>crud_edit')) {
          $this->getResponse()->setData('editActions', $this->config->get('action>crud_edit'));
        }

        if(!$form->isSent()) {
            $this->getResponse()->setData('form', $form->output($this->outputFormConfig));
            return;
        }

        // we can use $form->getData() here, but then we're receiving a lot more data (e.g. non-input or disabled fields!)
        $data = $this->getMyModel()->normalizeData( $this->getFormNormalizationData() );

        // DEBUG: \codename\core\app::getResponse()->setData('crud_debug_'.$this->model->getIdentifier().'_data_incoming', $data);

        // OLD: $newData = app::getHook()->fire(\codename\core\hook::EVENT_CRUD_EDIT_BEFORE_VALIDATION, $data);
        $newData = $this->eventCrudBeforeValidation->invokeWithResult($this, $data);
        if(is_array($newData)) {
            $data = $newData;
        }

        // form validation before model validation
        if(!$form->isValid()) {
          $this->getResponse()->setStatus(\codename\core\response::STATUS_INTERNAL_ERROR);
          $this->getResponse()->setData('errors', $form->getErrorstack()->getErrors());
          $this->getResponse()->setData('view', 'validation_error');
          return;
        }

        // DEBUG: \codename\core\app::getResponse()->setData('crud_debug_'.$this->model->getIdentifier().'_data_new', $newData);

        $this->getMyModel()->entryLoad($primarykey);
        // DEBUG: \codename\core\app::getResponse()->setData('crud_debug_'.$this->model->getIdentifier().'_entry_loaded', $this->getMyModel()->getData());

        $this->getMyModel()->entryUpdate($data);
        // DEBUG: \codename\core\app::getResponse()->setData('crud_debug_'.$this->model->getIdentifier().'_entry_updated', $this->getMyModel()->getData());


        if(count($errors = $this->getMyModel()->entryValidate()) > 0) {
            $this->getResponse()->setStatus(\codename\core\response::STATUS_INTERNAL_ERROR);
            $this->getResponse()->setData('errors', $errors);
            $this->getResponse()->setData('view', 'save_error');
            return;
        }

        // Fire hook after successful validation
        // OLD: app::getHook()->fire(\codename\core\hook::EVENT_CRUD_EDIT_AFTER_VALIDATION, $data);
        $this->eventCrudAfterValidation->invoke($this, $data);

        // Fire hook for additional validators
        // OLD: $errors = app::getHook()->fire(\codename\core\hook::EVENT_CRUD_EDIT_VALIDATION, $data);
        $errorResults = $this->eventCrudValidation->invokeWithAllResults($this, $data);

        $errors = array();
        foreach($errorResults as $errorCollection) {
          if(count($errorCollection) > 0) {
            $errors = array_merge($errors, $errorCollection);
          }
        }

        if(count($errors) > 0) {
          $this->getResponse()->setStatus(\codename\core\response::STATUS_INTERNAL_ERROR);
          $this->getResponse()->setData('errors', $errors);
          $this->getResponse()->setData('view', 'save_error');
          return;
        }

        // OLD: $newData = app::getHook()->fire(\codename\core\hook::EVENT_CRUD_EDIT_BEFORE_SAVE, $data);
        $newData = $this->eventCrudBeforeSave->invokeWithResult($this, $data);
        if(is_array($newData)) {
            $data = $newData;
            $this->getMyModel()->entryUpdate($data);
        }

        $this->getMyModel()->entrySave();

        // OLD:: app::getHook()->fire(\codename\core\hook::EVENT_CRUD_EDIT_SUCCESS, $data);
        $newData = $this->eventCrudSuccess->invokeWithResult($this, $data);

        $this->getResponse()->setData('view', 'crud_success');
    }

    /**
     * crud is in readonly mode
     * @var bool
     */
    public $readOnly = false;

    /**
     * Returns the form HTML code for showing an existing entry without editing function. Will make sure the given data is compliant to the form's and model's configuration
     * @param  string|int $primaryKey [description]
     * @return void
     */
    public function show($primaryKey) {

      $this->readOnly = true;

      // Readonly handling is now done in makeForm
      if($this->readOnly) {
        // apply to all nested cruds
        foreach($this->childCruds as $crud) {
          $crud->readOnly = true;
        }
      }

      // @TODO create new hooks for CRUD_SHOW.

      // use modified makeForm function, that allows $addSubmitButton = false (second argument)
      $form = $this->makeForm($primaryKey, false);

      $this->getResponse()->setData('form', $form->output($this->outputFormConfig));
    }

    /**
     * Loads data from a form configuration file
     * @param string $identifier
     * @return crud
     * @todo USE CACHE FOR CONFIGS
     */
    public function useForm(string $identifier) : crud {
        $this->getForm()->setId($identifier);

        $formConfig = new \codename\core\config\json('config/crud/form_' . $identifier . '.json');

        //
        // update child crud configs
        //
        if($formConfig->exists('children_config')) {
          $childrenConfig = $formConfig->get('children_config');
          foreach($childrenConfig as $childName => $childConfig) {
            if(isset($this->childCruds[$childName])) {
              // DEBUG: \codename\core\app::getResponse()->setData('debug_crud_useform_childconfig_'.$identifier.'_'.$childName, $childConfig);
              if(isset($childConfig['crud'])) {
                $this->childCruds[$childName]->setConfig($childConfig['crud']);
              }
              if(isset($childConfig['form'])) {
                $this->childCruds[$childName]->useForm($childConfig['form']);
              }
            }
          }
        }

        if($formConfig->exists('tag')) {
          $this->getForm()->config['form_tag'] = $formConfig->get('tag');
        }

        if($formConfig->exists('fieldset')) {
            foreach($formConfig->get('fieldset') as $key => $fieldset) {
                $newFieldset = new fieldset(array('fieldset_name' => $key));
                foreach($formConfig->get("fieldset>{$key}>field") as $field) {
                    $options = array();
                    $options['field_required'] = ($formConfig->exists("fieldset>required") && in_array($field, $formConfig->get("fieldset>required")));
                    $options['field_readonly'] = ($formConfig->exists("fieldset>{$key}>readonly") && in_array($field, $formConfig->get("fieldset>{$key}>readonly")));
                    if($field == $this->getMyModel()->table . '_flag') {
                        $flags = $this->getMyModel()->config->get('flag');
                        foreach($flags as $flagname => $flag) {
                            $newFieldset->addField(new \codename\core\ui\field(
                                    array (
                                        'field_name' => $this->getMyModel()->table . '_flag[' . $flagname . ']',
                                        'field_type' => 'checkbox',
                                        'field_title' => app::getTranslate()->translate('DATAFIELD.' . $field . '_' . $flagname),
                                        'field_value' => !is_null($this->data) ? $this->getMyModel()->isFlag($flag, $this->data->getData()) : false,
                                        'field_readonly' => $options['field_readonly']
                                    )
                                )
                            );
                        }
                        $newFieldset->addField( new \codename\core\ui\field(
                            array (
                                'field_name' => $this->getMyModel()->table . '_flag[__empty]',
                                'field_type' => 'hidden',
                                'field_title' => '',
                                'field_value' => '',
                                'field_readonly' => $options['field_readonly']
                            )
                        ));
                        continue;
                    }
                    $newFieldset->addField($this->makeField($field, $options));
                }
                $this->getForm()->addFieldset($newFieldset);
            }
        } elseif ($formConfig->exists("field")) {
            $this->fields = $formConfig->get("field");
        }

        // DEBUG: \codename\core\app::getResponse()->setData('debug_crud_fields_set_'.($this->getForm()->config['form_tag'] ?? 'no-tag').'_'.$identifier, $this->fields);

        return $this;
    }

    /**
     * Loads one object from the CRUD generator's model if the primary key is defined.
     * @param string|int|null $primarykey
     * @throws \codename\core\exception
     */
    public function useEntry($primarykey = null) {
        if(is_null($primarykey)) {
            $this->getResponse()->setData('CRUD_FEEDBACK', 'ENTRY_CREATE');
            return $this;
        }
        $this->getResponse()->setData('CRUD_FEEDBACK', 'ENTRY_UPDATE');
        $this->data = new \codename\core\datacontainer($this->getMyModel()->load($primarykey));

        // DEBUG: $this->getResponse()->setData('crud_'.$this->model->getIdentifier().'_entry', $this->data->getData());

        return $this;
    }

    /**
     * [useData description]
     * @param  array $data [description]
     * @return crud        [description]
     */
    public function useData(array $data) : crud {
      $this->data = new \codename\core\datacontainer(
        // $this->getMyModel()->normalizeData($data)
        $data
      );
      return $this;
    }

    /**
     * [useFormNormalizationData description]
     * @return crud [description]
     */
    public function useFormNormalizationData() : crud {
      $this->data = new \codename\core\datacontainer($this->getMyModel()->normalizeData( $this->getFormNormalizationData() ));
      foreach($this->childCruds as $crud) {
        $crud->useFormNormalizationData();
      }
      return $this;
    }

    /**
     * returns the current crud configuration
     *
     * @return \codename\core\config [description]
     */
    public function getConfig() : \codename\core\config {
        return $this->config;
    }

    /**
     * Sends the pagination data to the response
     * @return void
     */
    protected function makePagination() {

        //
        // small HACK:
        // temporily add a count field
        // perform the query using the given configuration
        // and remove it afterwards.
        //
        $count = (int) $this->getMyModel()->addCalculatedField('__count', 'count(*)')->search()->getResult()[0]['__count'];
        $this->getMyModel()->removeCalculatedField('__count');

        // default value, if none of the below works:
        $page = 1;
        if($this->getRequest()->isDefined('crud_pagination_page')) {
          // explicit page request
          $page = (int) $this->getRequest()->getData('crud_pagination_page');
        } else if($this->getRequest()->isDefined('crud_pagination_page_prev')) {
          // fallback to previous page value, if page hasn't been submitted
          $page = (int) $this->getRequest()->getData('crud_pagination_page_prev');
        }

        if($this->getRequest()->isDefined('crud_pagination_limit')) {
          $limit = (int) $this->getRequest()->getData('crud_pagination_limit');
        } else {
          $limit = $this->config->get("pagination>limit", 10);
        }

        $pages = ($limit==0||$count==0) ? 1 : ceil($count / $limit);

        // pagination limit change with present page param, that is out of range:
        if($page > $pages) {
          $page = $pages;
        }

        if($pages > 1) {
          $this->getMyModel()->setLimit($limit)->setOffset(($page-1) * $limit);
        }



        // Response
        $this->getResponse()->addData(
            array(
                'crud_pagination_count' => $count,
                'crud_pagination_page' => $page,
                'crud_pagination_pages' => $pages,
                'crud_pagination_limit' => $limit
            )
        );
        return;
    }

    /**
     * Resolve a datatype to a foreced display type
     * @param string $datatype
     * @return string
     */
    public function getDisplaytype(string $datatype) : string {
        switch($datatype) {
            case 'structure_address':
                return 'structure_address';
            case 'structure':
                return 'structure';
            case 'boolean':
                return 'yesno';
            case 'text_date':
                return 'date';
            case 'text_timestamp':
                return 'timestamp';
            default:
                return 'input';
            break;
        }
    }

    /**
     * Creates the field instance for the given field and adds information to it.
     * @param  string $field   [description]
     * @param  array  $options [description]
     * @throws \codename\core\exception
     * @return field
     */
    public function makeField(string $field, array $options = array()) : field {
        // load model config for simplicity
        $modelconfig = $this->getMyModel()->config->get();

        // Error if field not in model
        if(!in_array($field, $this->getMyModel()->getFields())) {
            throw new \codename\core\exception(self::EXCEPTION_MAKEFIELD_FIELDNOTFOUNDINMODEL, \codename\core\exception::$ERRORLEVEL_ERROR, $field);
        }

        // Create basic formfield array
        $fielddata = array(
                'field_id' => $field,
                'field_name' => $field,
                'field_title' => app::getTranslate()->translate('DATAFIELD.' . $field ),
                'field_description' => app::getTranslate()->translate('DATAFIELD.' . $field . '_DESCRIPTION' ),
                'field_type' => 'input',
                'field_required' => false,
                'field_placeholder' => app::getTranslate()->translate('DATAFIELD.' . $field ),
                'field_multiple' => false,
                'field_readonly' => $options['field_readonly'] ?? false
        );

        // Get the displaytype of this field
        if (array_key_exists('datatype', $modelconfig) && array_key_exists($field, $modelconfig['datatype'])) {
            $fielddata['field_type'] = $this->getDisplaytype($modelconfig['datatype'][$field]);
            $fielddata['field_datatype'] = $modelconfig['datatype'][$field];
        }

        if($fielddata['field_type'] == 'yesno') {
            $fielddata['field_type'] = 'select';
            $fielddata['field_displayfield'] = '{$element[\'field_name\']}';
            $fielddata['field_valuefield'] = 'field_value';

            // NOTE: Datatype for this kind of pseudo-boolean field must be null or so
            // because the boolean validator really needs a bool.
            $fielddata['field_datatype'] = null;
            $fielddata['field_elements'] = array(
                    array(
                        'field_value' => "true",
                        'field_name' => 'Ja'
                    ),
                    array(
                        'field_value' => "false",
                        'field_name' => 'Nein'
                    )
            );
        }

        if($this->config->exists("required>$field")) {
            $fielddata['field_required'] = true;
            $this->getMyModel()->requireField($field);
        }

        if(!is_null($this->data)) {
            $fielddata['field_value'] = ($this->data->isDefined($field) ? $this->getMyModel()->exportField(new \codename\core\value\text\modelfield($field), $this->data->getData($field)) : null);
        }

        // Set primary key field hidden
        if($field == $this->getMyModel()->getPrimarykey()) {
          if(($options['field_readonly'] ?? false) == true) {
            $fielddata['field_type'] = 'infopanel';
          } else {
            $fielddata['field_type'] = 'hidden';
          }
        }

        // Decode object datatypes
        if(strpos($fielddata['field_type'], 'bject_') != false) {
            $fielddata['field_value'] = app::object2array(json_decode($fielddata['field_value']));
        }
        $fielddata['field_required'] = ($this->getMyModel()->config->exists("required") && in_array($field, $this->getMymodel()->config->get("required")));

        // Modify field to be a reference dropdown
        if(array_key_exists('foreign', $modelconfig) && array_key_exists($field, $modelconfig['foreign'])) {
            if(!app::getValidator('structure_config_modelreference')->isValid($modelconfig['foreign'][$field])) {
                throw new \codename\core\exception(self::EXCEPTION_MAKEFIELD_INVALIDREFERENCEOBJECT, \codename\core\exception::$ERRORLEVEL_ERROR, $modelconfig['foreign'][$field]);
            }

            $foreign = $modelconfig['foreign'][$field];

            $elements = $this->getModel($foreign['model'], $foreign['app'] ?? app::getApp());

            if(array_key_exists('order', $foreign) && is_array($foreign['order'])) {
                foreach ($foreign['order'] as $order) {
                    if(!app::getValidator('structure_config_modelorder')->isValid($order)) {
                        throw new \codename\core\exception(self::EXCEPTION_MAKEFIELD_INVALIDORDEROBJECT, \codename\core\exception::$ERRORLEVEL_ERROR, $order);
                    }
                    $elements->addOrder($order['field'], $order['direction']);
                }
            }

            if(array_key_exists('filter', $foreign) && is_array($foreign['filter'])) {
                foreach ($foreign['filter'] as $filter) {
                    if(!app::getValidator('structure_config_modelfilter')->isValid($filter)) {
                        throw new \codename\core\exception(self::EXCEPTION_MAKEFIELD_INVALIDFILTEROBJECT, \codename\core\exception::$ERRORLEVEL_ERROR, $filter);
                    }
                    if($filter['field'] == $elements->getIdentifier() . '_flag') {
                      if($filter['operator'] == '=') {
                        $elements->withFlag($elements->config->get('flag>'.$filter['value']));
                      } else if($filter['operator'] == '!=') {
                        $elements->withoutFlag($elements->config->get('flag>'.$filter['value']));
                      } else {
                        throw new \codename\core\exception(self::EXCEPTION_MAKEFIELD_FILTER_FLAG_INVALIDOPERATOR, \codename\core\exception::$ERRORLEVEL_ERROR, $filter);
                      }
                    } else {
                      $elements->addFilter($filter['field'], $filter['value'], $filter['operator']);
                    }
                }
            }

            $fielddata['field_type'] = 'select';
            $fielddata['field_displayfield'] = $foreign['display'];
            $fielddata['field_valuefield'] = $foreign['key'];

            if($elements instanceof \codename\rest\model\exposesRemoteApiInterface && isset($foreign['remote_source'])) {
              // $fielddata['field_elements'] = $elements->search()->getResult();
              $apiEndpoint = $elements->getExposedApiEndpoint();
              $fielddata['field_remote_source'] = $apiEndpoint;

              $remoteSource = $foreign['remote_source'] ?? [];

              //
              // if(array_key_exists($foreign['model'], $defaultRemoteApiFilters)) {
              //   $field['field_remote_source_parameter'] = [
              //     'filter' => array_merge($defaultRemoteApiFilters[$foreign['model']], [] /*($foreign['filter'] ?? [])*/ )
              //   ];
              // }

              $filterKeys = [];
              foreach($remoteSource['filter_key'] as $filterKey => $filterData) {
                if(is_array($filterData)) {
                  foreach($filterData as $filterDataKey => $filterDataData) {
                    $filterKeys[$filterKey][$filterDataData] = true;
                  }
                } else {
                  $filterKeys[$filterData] = true;
                }
              }

              $fielddata['field_remote_source_filter_key'] = $filterKeys;
              /*
              if(array_key_exists($foreign['model'], $remoteApiFilterKeys)) {
                $field['field_remote_source_filter_key'] = $remoteSource['filter_key'];
              }
              */
              $fielddata['field_remote_source_parameter'] = $remoteSource['parameters'] ?? [];
              $fielddata['field_remote_source_display_key'] = $remoteSource['display_key'] ?? null;
              $fielddata['field_remote_source_links'] = $foreign['remote_source']['links'] ?? [];
              $fielddata['field_valuefield'] = $foreign['key'];
              $fielddata['field_displayfield'] = $foreign['key']; // $defaultDisplayField[$foreign['model']] ?? $foreign['key'];

            } else {
              $fielddata['field_elements'] = $elements->search()->getResult();
            }

            if(array_key_exists('datatype', $modelconfig) && array_key_exists($field, $modelconfig['datatype']) && $modelconfig['datatype'][$field] == 'structure') {
                $fielddata['field_multiple'] = true;
            }
        }


        //
        // nested crud / submodel
        //
        if($this->config->exists('children') && in_array($field, $this->config->get('children'))) {

          $childConfig = $this->model->config->get('children>'.$field);

          if($childConfig['type'] === 'foreign') {
            //
            // Handle nested forms
            //
            $fielddata['field_type'] = 'form';

            // provide a sub-form config !
            // $crud = new \codename\core\ui\crud($this->getModel($foreign['model'], $foreign['app'] ?? '', $foreign['vendor'] ?? ''));
            $crud = $this->childCruds[$field];

            if($this->readOnly) {
              $crud->readOnly = $this->readOnly;
            }

            // available child config keys:
            // - type (e.g. foreign)
            // - field (reference field)
            $childIdentifierValue = ($this->data && $this->data->isDefined($childConfig['field']) ? $this->getMyModel()->exportField(new \codename\core\value\text\modelfield($childConfig['field']), $this->data->getData($childConfig['field'])) : null);
            $form = $crud->makeForm($childIdentifierValue, false); // make form without submit

            // $this->getResponse()->setData('debug_crud_form_' . $field, $crud->getFormNormalizationData());
            // $form->setFormRequest($crud->getFormNormalizationData());

            $fielddata['form'] = $form;
            $formdata = [];
            foreach($form->getFields() as $field) {
              $formdata[$field->getProperty('field_name')] = $field->getProperty('field_value');
            }
            $fielddata['field_value'] = $formdata;
          } else if($childConfig['type'] === 'collection') {
            //
            // Handle collections
            //
            $collectionConfig = $this->model->config->get('collection>'.$field);
            $fielddata['field_type'] = 'table';
            $fielddata['field_datatype'] = 'structure';

            $crud = new \codename\core\ui\crud($this->getModel($collectionConfig['model'], $collectionConfig['app'] ?? '', $collectionConfig['vendor'] ?? ''));
            // TODO: allow custom crud config somehow?
            // $crud->setConfig('some-crud-config');

            $fielddata['visibleFields'] = $crud->getConfig()->get('visibleFields');

            $fielddata['labels'] = [];
            foreach($fielddata['visibleFields'] as $field) {
              $fielddata['labels'][$field] = app::getTranslate()->translate('DATAFIELD.'.$field);
            }

            $form = $crud->makeForm(null, false);
            $fielddata['form'] = $form->output(true);
          }
        }

        if($this->readOnly) {
          $fielddata['field_readonly'] = true;
        }

        $c = &$this->onCreateFormfield;
        if($c !== null && is_callable($c)) {
          $c($fielddata);
        }

        $field = new field($fielddata);
        $field->setType('compact');

        $c = &$this->onFormfieldCreated;
        if($c !== null && is_callable($c)) {
          $c($field);
        }

        // Add the field to the form
        return $field;
    }

    /**
     * Provides a way to hook into the formfield creation process
     * the fielddata array used for the field-.ctor is being used as argument
     * @var callable
     */
    public $onCreateFormfield = null;

    /**
     * Provides a way to hook into when the formfield has been created
     * the created field is being used as argument
     * @var callable
     */
    public $onFormfieldCreated = null;

    /**
     * Adds an action button / element to the given action type.
     * @param string $type
     * @param array $action
     * @return void
     * @todo use really abstract and usable action value-object in here.
     */
    protected function addAction(string $type, array $action) {
        if(count($errors = app::getValidator('structure_config_crud_action')->validate($action)) > 0) {
            throw new \codename\core\exception(self::EXCEPTION_ADDACTION_INVALIDACTIONOBJECT, \codename\core\exception::$ERRORLEVEL_ERROR, $errors);
        }

        $config = $this->config->get();
        $config['action'][$type][$action['name']] = $action;
        $this->config = new \codename\core\config($config);

        return;
    }

    /**
     * Returns the default CRUD filter for the given filters
     * @param  string  $field    [description]
     * @param  bool    $wildcard [description]
     * @return string            [description]
     */
    protected function getDefaultoperator(string $field, bool $wildcard = false) : string {
        switch($this->getMyModel()->getFieldtype(new \codename\core\value\text\modelfield($field))) {
            case 'number_natural' :
                return '=';
                break;
            default:
                return $wildcard ? 'ILIKE' : '=';
                break;
        }
    }

    /**
     * Will return the filterable string that is used for the given field's datatype
     * @param  string  $value    [description]
     * @param  string  $field    [description]
     * @param  bool    $wildcard [description]
     * @return string            [description]
     */
    protected function getFilterstring(string $value, string $field, bool $wildcard = false) : string {
        switch($this->getMyModel()->getFieldtype(new \codename\core\value\text\modelfield($field))) {
            case 'number_natural' :
                return $value;
                break;
            default:
                return $wildcard ? "%{$value}%" : "{$value}";
                break;
        }
    }

    /**
     * Will apply defaultFilter properties to the model instance of this CRUD generator
     * @return void
     */
    protected function applyFilters() {
        if(!$this->getRequest()->isDefined(self::CRUD_FILTER_IDENTIFIER)) {
            return;
        }
        $filters = $this->getRequest()->getData(self::CRUD_FILTER_IDENTIFIER);
        if(!is_array($filters)) {
            return;
        }

        if(array_key_exists('search', $filters) && $filters['search'] != '') {
            if($this->config->exists("visibleFilters>_search") && is_array($this->config->get("visibleFilters>_search"))) {
                $filterCollection = array();
                foreach($this->config->get("visibleFilters>_search>fields") as $field) {
                    $filterCollection[] = array('field' => $field, 'value' => $this->getFilterstring($filters['search'], $field, true), 'operator' => 'ILIKE');
                }
                $this->getMyModel()->addDefaultFilterCollection($filterCollection, 'OR');
            }
            // do NOT return; as we may use other filters, too. Why tho?
            // return;
        }

        foreach($filters as $key => $value) {
            // exclude search key here, as we're not returning after a wildcard search anymore
            if($key === 'search' || (!is_array($value) && strlen($value) == 0)) {
                continue;
            }
            if($key == $this->getMyModel()->getIdentifier() . '_flag') {
              if(is_array($value)) {
                foreach($value as $flagval) {
                  $this->getMyModel()->withDefaultFlag($this->getFilterstring($flagval, $key, false));
                }
              } else {
                $this->getMyModel()->withDefaultFlag($this->getFilterstring($value, $key, false));
              }
            } else {
              if(is_array($value) && $this->model->config->exists("datatype>".$key) && in_array($this->model->config->exists("datatype>".$key), array('text_timestamp', 'text_date'))) {
                $this->getMyModel()->addDefaultfilter($key, $this->getFilterstring($value[0], $key, false), '>=');
                $this->getMyModel()->addDefaultfilter($key, $this->getFilterstring($value[1], $key, false), '<=');
              } else {
                $wildcard = $this->config->exists("visibleFilters>".$key.">wildcard") && ($this->config->get("visibleFilters>".$key.">wildcard") == true);
                $operator = $this->config->exists("visibleFilters>".$key.">operator") ? $this->config->get("visibleFilters>".$key.">operator") : $this->getDefaultoperator($key, $wildcard);
                $this->getMyModel()->addDefaultfilter($key, $this->getFilterstring($value, $key, $wildcard), $operator);
              }
            }
        }
        return;
    }

    /**
     * This method loops all the given datasets in $rows and the given $fields.
     * <br />The method generates a new output array and tries to overwrite field values by using getFieldoutput()
     * @param array $rows
     * @param array $fields
     * @return array
     */
    protected function makeFields(array $rows, array $fields) : array {

        // simply return dataset, if there is no modifier (row, field) and no foreign key data to be fetched
        if(count($this->rowModifiers) == 0
          && count($this->modifiers) == 0
          && is_null($this->getMyModel()->config->get('foreign'))
        ) {
            return $rows;
        }

        $searchForFields = $fields;
        if(count($this->modifiers) > 0) { // merge or replace?
          $searchForFields = array_merge($searchForFields, array_keys($this->modifiers));
        }

        $myRows = array();
        foreach($rows as $row) {
            $object = array();

            if(count($this->modifiers) > 0 || !is_null($this->getMyModel()->config->get('foreign'))) {

              /* $searchForFields = $fields;
              if(count($this->modifiers) > 0) { // merge or replace?
                $searchForFields = array_merge($searchForFields, array_keys($this->modifiers));
              }*/

              /*
              if(!is_null($this->getMyModel()->config->get('foreign'))) { // merge or replace?
                // do not add foreign keys as defaults to this one
                // as it causes a lot of extra queries.
                $searchForFields = array_merge($searchForFields, array_keys($this->getMyModel()->config->get('foreign')));
              }
              */

              foreach($searchForFields as $field) {
                  $o = $this->getFieldoutput($row, $field);
                  // @NOTE: we're differentiating between a pre-formatted and a raw value here:
                  // if array index 1 is set, this is the formatted value.
                  if(array_key_exists(1, $o)) {
                    $object[$field.'_FORMATTED'] = $o[1];
                    $object[$field] = $o[0];
                  } else {
                    $object[$field] = $o[0];
                  }
              }
            } else {
              $object = $row;
            }

            if(count($this->rowModifiers) > 0) {
              $attributes = array();
              foreach($this->rowModifiers as $rowModifier) {
                $modifierOutput = $rowModifier($row);
                if(is_array($modifierOutput)) {
                  $attributes = array_merge_recursive($attributes, $modifierOutput);
                }
              }

              $object['__modifier'] = join(' ', array_map(function($key) use ($attributes)
                {
                   if(is_bool($attributes[$key]))
                   {
                      return $attributes[$key]?$key:'';
                   }
                   return $key.'="'.$attributes[$key].'"';
                }, array_keys($attributes))
              );
            }

            $myRows[] = $object;
        }
        return $myRows;
    }

    /**
     * This method will return the output value of the given $field using the data from the given $row.
     * <br />It will determine the output value by the following two situations:
     * <br />#1: The $field has been given a modifier using ->addModifier($field, $callable)
     * <br />#2: The $field has been configured to display data from another model (a.k.a foreign key / reference)
     * @param array $row
     * @param string $field
     * @return string
     */
    protected function getFieldoutput(array $row, string $field) {

        if(array_key_exists($field, $this->modifiers)) {
            if(array_key_exists($field, $row)) {
              return array($row[$field], $this->modifiers[$field]($row));
            } else {
              return array($this->modifiers[$field]($row));
            }
        }

        if(!isset($row[$field])) {
          return array(null);
        }

        if($field == $this->getMyModel()->table . '_flag') {
          $flags = $this->getMyModel()->config->get("flag");
          $ret = '';
          foreach($flags as $flagname => $flagval) {
            if($this->getMyModel()->isFlag($flagval, $row)) {
              $text = app::getTranslate()->translate('DATAFIELD.' . $field . '_' . $flagname);
              $ret .= "<span class=\"badge\">{$text}</span>";
            }
          }
          return array($row[$field], $ret);
        }

        $foreignkeys = $this->getMyModel()->config->get("foreign");
        if(!is_array($foreignkeys) || !array_key_exists($field, $foreignkeys)) {
          return array($row[$field]);
        }

        if(array_key_exists('optional', $foreignkeys[$field]) && $foreignkeys[$field]['optional']==true && $row[$field]==NULL) {
          return array($row[$field]);
        }

        // TODO: We may have to differentiate here
        // for values which still have to be displayed in some way,
        // but they're NULL. ...

        $obj = $foreignkeys[$field];

        if($obj['display'] != null) {
          if(is_null($row[$field]) || !isset($row[$field])) {
            $ret = '';
          } else if(is_array($row[$field])) {
            $vals = array();
            foreach($row[$field] as $val) {
              $element = $this->getModel($obj['model'])->loadByUnique($obj['key'], $val);
              @eval('$vals[] = "' . $obj['display'] . '";');
            }
            $ret = implode(', ', $vals);
          } else {
            // $field should be $obj['key']. check dependencies, correct mistakes and do it right!
            // TODO: wrap this in a try/catch statement
            // bare/json datasources may lose unique keys. fallback to null or "undefined"?

            // first: try to NOT perform an additional query
            $element = $row;
            $evalResult = false;
            try {
              $evalResult = @eval('$ret = "' . $obj['display'] . '";');
            } catch (\Exception $e) {
              $evalResult = false;
            }
            if($evalResult === false) {
              $element = $this->getModel($obj['model'], $obj['app'] ?? '', $obj['vendor'] ?? '')->loadByUnique($obj['key'], $row[$field]);
              @eval('$ret = "' . $obj['display'] . '";');
            }
          }
          return array($row[$field], $ret);
        } else {
          return array($row[$field]);
        }

        if($row[$field]==NULL) {
          return array(null);
        }
    }

    /**
     * Returns the form instance of this CRUD generator instance
     * @return \codename\core\ui\form
     */
    public function getForm() : \codename\core\ui\form {
        return $this->form;
    }

    /**
     * Returns the private model of this instance
     * @return \codename\core\model
     */
    public function getMyModel() : \codename\core\model {
        return $this->model;
    }

}
