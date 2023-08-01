<?php

namespace codename\core\ui;

use codename\core\app;
use codename\core\bootstrapInstance;
use codename\core\config;
use codename\core\config\json;
use codename\core\datacontainer;
use codename\core\event;
use codename\core\exception;
use codename\core\helper\deepaccess;
use codename\core\model;
use codename\core\model\plugin\join;
use codename\core\model\virtualFieldResultInterface;
use codename\core\NoticeException;
use codename\core\response;
use codename\core\transaction;
use codename\core\ui;
use codename\core\value\text\modelfield;
use codename\rest\model\exposesRemoteApiInterface;
use ReflectionException;

/**
 * The CRUD generator uses it's model to display the model's content.
 * It is capable of Creating, Reading, Updating and Deleting data in the models.
 * It utilizes several frontend resources located in the core frontend folder.
 * Override these templates by adding these files in your application's directory
 * @package codename\core\ui
 */
class crud extends bootstrapInstance
{
    /**
     * The desired field cannot be found in the current model.
     * @var string
     */
    public const EXCEPTION_MAKEFORM_FIELDNOTFOUNDINMODEL = 'EXCEPTION_MAKEFORM_FIELDNOTFOUNDINMODEL';

    /**
     * The desired field cannot be found in the current model.
     * @var string
     */
    public const EXCEPTION_MAKEFIELD_FIELDNOTFOUNDINMODEL = 'EXCEPTION_MAKEFIELD_FIELDNOTFOUNDINMODEL';

    /**
     * The foreign-model reference object is not valid.
     * @todo use value-object here
     * @var string
     */
    public const EXCEPTION_MAKEFIELD_INVALIDREFERENCEOBJECT = 'EXCEPTION_MAKEFIELD_INVALIDREFERENCEOBJECT';

    /**
     * The model order object is not valid.
     * @todo use value-object here
     * @var string
     */
    public const EXCEPTION_MAKEFIELD_INVALIDORDEROBJECT = 'EXCEPTION_MAKEFIELD_INVALIDORDEROBJECT';

    /**
     * The model filter object is not valid.
     * @todo use value-object here
     * @var string
     */
    public const EXCEPTION_MAKEFIELD_INVALIDFILTEROBJECT = 'EXCEPTION_MAKEFIELD_INVALIDFILTEROBJECT';

    /**
     * The model filter flag operator is not valid. should be = or !=
     * @todo use value-object here
     * @var string
     */
    public const EXCEPTION_MAKEFIELD_FILTER_FLAG_INVALIDOPERATOR = 'EXCEPTION_MAKEFIELD_FILTER_FLAG_INVALIDOPERATOR';

    /**
     * The action button object is not valid
     * @todo use value-object here
     * @var string
     */
    public const EXCEPTION_ADDACTION_INVALIDACTIONOBJECT = 'EXCEPTION_ADDACTION_INVALIDACTIONOBJECT';

    /**
     * Contains the request entry that will hold all filters applied to the crud list
     * @var string
     */
    public const CRUD_FILTER_IDENTIFIER = '_cf';
    /**
     * exception thrown if a given model does not define child configuration
     * but crud tries to use it
     * @var string
     */
    public const EXCEPTION_CRUD_CHILDREN_CONFIG_MODEL_CONFIG_CHILDREN_IS_NULL = 'EXCEPTION_CRUD_CHILDREN_CONFIG_MODEL_CONFIG_CHILDREN_IS_NULL';
    /**
     * If true, does not render the form
     * Instead, output form config
     * @var bool
     */
    public bool $outputFormConfig = false;
    /**
     * This event will be fired whenever a method of this CRUD instance generates a form instance.
     * Use this event to alter the current form of the CRUD instance (e.g. for asking for more fields)
     * @var event
     */
    public event $eventCrudFormInit;
    /**
     * This event is fired before the validation starts.
     * @example Imagine cases where you don't want a user to input data, but you must
     * add it to the entry, because the missing fields would violate the model's
     * constraints. Here you can do anything you want with the entry array.
     * @var event
     */
    public event $eventCrudBeforeValidation;
    /**
     * This event is fired after validation has been successful.
     * @var event
     */
    public event $eventCrudAfterValidation;
    /**
     * This event is fired after validation has been successful.
     * We might run additional validators here.
     * output must be either null, empty array or errors found in additional validators
     * @var event
     */
    public event $eventCrudValidation;
    /**
     * This event is fired whenever the CRUD generator wants to save a validated entry (or updates)
     * to a model. It is given the $data and must return the $data.
     * @example Imagine you want to manipulate entries on a model when saving the entry
     * from the CRUD generator. This is version will happen after the validation.
     * @var event
     */
    public event $eventCrudBeforeSave;
    /**
     * This event is fired whenever the CRUD generator successfully completed an operation
     * to a model. It is given the $data.
     * @var event
     */
    public event $eventCrudSuccess;
    /**
     * crud is in readonly mode
     * @var bool
     */
    public bool $readOnly = false;
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
     * Contains the model this CRUD instance is based upon
     * @var model
     */
    protected model $model;
    /**
     * Contains the form instance we are working with
     * @var form
     */
    protected form $form;
    /**
     * Contains all the fields that will be displayed in the CRUD generator
     * @var array
     */
    protected array $fields = [];
    /**
     * Contains all fields Configurations that are displayed in the CRUD generator
     * @var array
     */
    protected array $fieldsformConfig = [];
    /**
     * Contains the ID of the CRUD form
     * @var string
     */
    protected string $form_id = 'crud_default_form';
    /**
     * Contains the dataset of the model. May be empty when creating a new entry
     * @var null|datacontainer
     */
    protected ?datacontainer $data = null;
    /**
     * contains an instance of config storage
     * @var config
     */
    protected config $config;
    /**
     * Contains a list of fields and their modifiers
     * @var array $modifiers
     */
    protected array $modifiers = [];
    /**
     * Contains a list of row modifiers (callables)
     * @var callable[] $rowModifiers
     */
    protected array $rowModifiers = [];
    /**
     * [protected description]
     * @var crud[]
     */
    protected array $childCruds = [];
    /**
     * Cache configurations
     * @var bool
     */
    protected bool $useConfigCache = true;
    /**
     * [protected description]
     * @var callable[]
     */
    protected array $resultsetModifiers = [];
    /**
     * default column ordering
     * @var string[]
     */
    protected array $columnOrder = [];
    /**
     * [protected description]
     * @var array|null
     */
    protected ?array $resultData = null;
    /**
     * internal and temporary pagination switch (for exporting)
     * @var bool
     */
    protected bool $allowPagination = true;
    /**
     * defines raw, unformatted mode
     * @var bool
     */
    protected bool $rawMode = false;
    /**
     * [protected description]
     * @var null|array
     */
    protected ?array $formNormalizationData = null;
    /**
     * [protected description]
     * @var null|string
     */
    protected ?string $crudSeekOverridePkeyOrder = null;
    /**
     * list of fields that are configured
     * to just provide a basic configuration
     * and skip unnecessary stuff (e.g. FKEY value fetching)
     * @var string[]
     */
    protected array $customizedFields = [];
    /**
     * customized, provided filters
     * @var array
     */
    protected array $providedFilters = [];
    /**
     * whether the crud_list
     * should provide raw result parts
     * from the model query
     * @var bool
     */
    protected bool $provideRawData = false;
    /**
     * [protected description]
     * @var model[]
     */
    protected array $cachedModels = [];

    /**
     * Creates the instance and sets the $model of this instance. Also creates the form instance
     * @param model $model
     * @param array|null $requestData
     * @param string $crudConfig [optional explicit crud config]
     * @throws ReflectionException
     * @throws exception
     */
    public function __construct(model $model, ?array $requestData = null, string $crudConfig = '')
    {
        $this->eventCrudFormInit = new event('EVENT_CRUD_FORM_INIT');
        $this->eventCrudBeforeValidation = new event('EVENT_CRUD_BEFORE_VALIDATION');
        $this->eventCrudAfterValidation = new event('EVENT_CRUD_AFTER_VALIDATION');
        $this->eventCrudValidation = new event('EVENT_CRUD_VALIDATION');
        $this->eventCrudBeforeSave = new event('EVENT_CRUD_BEFORE_SAVE');
        $this->eventCrudSuccess = new event('EVENT_CRUD_SUCCESS');
        $this->model = $model;
        if ($requestData != null) {
            $this->setFormNormalizationData($requestData);
        }
        $this->setConfig($crudConfig);
        $this->setChildCruds();
        $this->updateChildCrudConfigs();
        $this->form = new form([
          'form_action' => ui\app::getUrlGenerator()->generateFromParameters([
            'context' => $this->getRequest()->getData('context'),
            'view' => $this->getRequest()->getData('view'),
          ]),
          'form_method' => 'post',
          'form_id' => $this->form_id,
        ]);
        return $this;
    }

    /**
     * reads config from the 'children' key
     * and creates instances for those children (cruds)
     * @throws ReflectionException
     * @throws exception
     */
    protected function setChildCruds(): void
    {
        // apply nested children config
        if ($this->config->exists('children')) {
            foreach ($this->config->get('children') as $child) {
                //
                // the master child configuration
                // in the model
                //
                $childConfig = $this->model->config->get('children>' . $child);

                //
                // optional crud/form overrides
                //
                $childCrudConfig = $this->config->get('children_config>' . $child);

                if ($childConfig != null) {
                    // we handle a single-ref foreign key field as base
                    // for a nested model as a virtual object key
                    if ($childConfig['type'] == 'foreign') {
                        // get the foreign key config
                        $foreignConfig = $this->model->config->get('foreign>' . $childConfig['field']);
                        // get the respective model
                        $childModel = $this->getModel($foreignConfig['model'], $foreignConfig['app'] ?? '', $foreignConfig['vendor'] ?? '');
                        // build a child crud
                        $crud = new crud($childModel, $this->getFormNormalizationData()[$child] ?? []);

                        //
                        // Handle optional configs
                        //
                        if (isset($childCrudConfig['crud'])) {
                            $crud->setConfig($childCrudConfig['crud']);
                        }
                        if (isset($childCrudConfig['form'])) {
                            $crud->useForm($childCrudConfig['form']);
                        }

                        // make only a part of the request visible to the crud instance
                        $crud->setFormNormalizationData($this->getFormNormalizationData()[$child] ?? []);

                        // store it for later
                        $this->childCruds[$child] = $crud;

                        //
                        // CHANGED/FEATURE force_virtual_join 2020-07-21
                        // This method uses the new feature of the core framework
                        // this allows virtualizing the table join
                        // to avoid RDBMS join limitations by abstracting the whole thing.
                        //
                        // You have to enable 'force_virtual_join' in the respective model child config
                        // Cruds enable this feature automatically, while you may opt in into its usage
                        // when querying models regularly.
                        //
                        if ($childConfig['force_virtual_join'] ?? false) {
                            $virtualJoinModel = $crud->getMyModel();
                            $virtualJoinModel->setForceVirtualJoin(true);
                            $this->getMyModel()->addModel($virtualJoinModel, join::TYPE_LEFT, $childConfig['field'], $foreignConfig['key']);
                        } else {
                            // join the model upon the current
                            $this->getMyModel()->addModel($crud->getMyModel(), join::TYPE_LEFT, $childConfig['field'], $foreignConfig['key']);
                        }

                        //
                        // Enable virtual field results
                        //
                        if (interface_exists('\\codename\\core\\model\\virtualFieldResultInterface') && $this->getMyModel() instanceof virtualFieldResultInterface) {
                            $this->getMyModel()->setVirtualFieldResult(true);
                        }
                    } elseif ($childConfig['type'] === 'collection') {
                        // Collection, not a crud.

                        // get the collection config
                        $collectionConfig = $this->model->config->get('collection>' . $child);
                        // get the respective model
                        $childModel = $this->getModel($collectionConfig['model'], $collectionConfig['app'] ?? '', $collectionConfig['vendor'] ?? '');

                        $this->getMyModel()->addCollectionModel($childModel, $child);

                        //
                        // Enable virtual field results
                        //
                        if (interface_exists('\\codename\\core\\model\\virtualFieldResultInterface') && $this->getMyModel() instanceof virtualFieldResultInterface) {
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
     * returns the request data
     * that is used for normalization
     * in form-related functions
     * @return array
     */
    protected function getFormNormalizationData(): array
    {
        if ($this->formNormalizationData == null) {
            $this->setFormNormalizationData($this->getRequest()->getData());
        }
        return $this->formNormalizationData;
    }

    /**
     * sets the underlying data used during normalization
     * in the normal use case, this is the pure request data
     * @param array $data [description]
     */
    public function setFormNormalizationData(array $data): void
    {
        $this->formNormalizationData = $data;
    }

    /**
     * access data array currently used by this crud
     * @param string $key [description]
     * @return mixed
     */
    public function getData(string $key = ''): mixed
    {
        return $this->data?->getData($key);
    }

    /**
     * Loads data from a form configuration file
     * @param string $identifier
     * @return crud
     * @throws ReflectionException
     * @throws exception
     * @todo USE CACHE FOR CONFIGS
     */
    public function useForm(string $identifier): crud
    {
        $this->getForm()->setId($identifier);

        $formConfig = $this->loadFormConfig($identifier);

        //
        // update child crud configs
        //
        if ($formConfig->exists('children_config')) {
            $childrenConfig = $formConfig->get('children_config');
            foreach ($childrenConfig as $childName => $childConfig) {
                if (isset($this->childCruds[$childName])) {
                    if (isset($childConfig['crud'])) {
                        $this->childCruds[$childName]->setConfig($childConfig['crud']);
                    }
                    if (isset($childConfig['form'])) {
                        $this->childCruds[$childName]->useForm($childConfig['form']);
                    }
                }
            }
        }

        if ($formConfig->exists('tag')) {
            $this->getForm()->config['form_tag'] = $formConfig->get('tag');
        }

        if ($formConfig->exists('fieldset')) {
            foreach ($formConfig->get('fieldset') as $key => $fieldset) {
                $newFieldset = new fieldset(['fieldset_name' => $key]);
                foreach ($formConfig->get('fieldset>' . $key . '>field') as $field) {
                    $options = [];
                    $options['field_required'] = ($formConfig->exists('fieldset>' . $key . '>required') && in_array($field, $formConfig->get('fieldset>' . $key . '>required')));
                    $options['field_readonly'] = ($formConfig->exists('fieldset>' . $key . '>readonly') && in_array($field, $formConfig->get('fieldset>' . $key . '>readonly')));

                    //
                    // CHANGED 2021-10-27: flag fields in fieldsets now use the same type/handling as root-level flag fields
                    //
                    if ($field == $this->getMyModel()->table . '_flag') {
                        $flags = $this->getMyModel()->config->get('flag');
                        if (!is_array($flags)) {
                            continue;
                        }

                        $value = [];
                        $elements = [];

                        foreach ($flags as $flagname => $flag) {
                            $value[$flagname] = !is_null($this->data) && $this->getMyModel()->isFlag($flag, $this->data->getData());
                            $elements[] = [
                              'name' => $flagname,
                              'display' => app::getTranslate()->translate('DATAFIELD.' . $field . '_' . $flagname),
                              'value' => $flag,
                            ];
                        }

                        $fielddata = [
                          'field_name' => $this->getMyModel()->table . '_flag',
                          'field_type' => 'multicheckbox',
                          'field_datatype' => 'structure',
                          'field_title' => app::getTranslate()->translate('DATAFIELD.' . $field),
                          'field_multiple' => true,
                          'field_value' => $value,
                          'field_elements' => $elements,
                          'field_idfield' => 'name',
                          'field_displayfield' => '{$element["display"]}', // todo: translate!
                          'field_valuefield' => 'value',
                        ];

                        if ($this->readOnly
                          || ($this->config->exists('readonly') && is_array($this->config->get('readonly')) && in_array($field, $this->config->get('readonly')))
                          || $options['field_readonly']
                        ) {
                            $fielddata['field_readonly'] = true;
                        }

                        if ($options['field_required'] ?? false) {
                            $fielddata['field_required'] = true;
                        }

                        $c = &$this->onCreateFormfield;
                        if ($c !== null && is_callable($c)) {
                            $c($fielddata);
                        }

                        $formField = new field($fielddata);

                        $c = &$this->onFormfieldCreated;
                        if ($c !== null && is_callable($c)) {
                            $c($formField);
                        }

                        $newFieldset->addField($formField);
                        continue;
                    }

                    $newFieldset->addField($this->makeField($field, $options));
                }
                $this->getForm()->addFieldset($newFieldset);
            }
        } elseif ($formConfig->exists("field")) {
            $this->fields = $formConfig->get("field");
            $this->fieldsformConfig = [];
            if ($formConfig->exists('required')) {
                $this->fieldsformConfig['required'] = $formConfig->get('required');
            }
            if ($formConfig->exists('readonly')) {
                $this->fieldsformConfig['readonly'] = $formConfig->get('readonly');
            }
        }

        return $this;
    }

    /**
     * Returns the form instance of this CRUD generator instance
     * @return form
     */
    public function getForm(): form
    {
        return $this->form;
    }

    /**
     * [loadFormConfig description]
     * @param string $identifier [description]
     * @return config
     * @throws ReflectionException
     * @throws exception
     */
    protected function loadFormConfig(string $identifier): config
    {
        // prepare config
        $config = null;
        $cacheGroup = app::getVendor() . '_' . app::getApp() . '_CRUD_FORM';
        $cacheKey = $identifier;

        //
        // Try to retrieve cached config
        //
        if ($this->useConfigCache) {
            if ($cachedConfig = app::getCache()->get($cacheGroup, $cacheKey)) {
                $config = new config($cachedConfig);
            }
        }

        //
        // If config not already set by cache, get it
        //
        if (!$config) {
            $config = new json('config/crud/form_' . $identifier . '.json');

            // Cache, if enabled.
            if ($this->useConfigCache) {
                app::getCache()->set($cacheGroup, $cacheKey, $config->get());
            }
        }

        return $config;
    }

    /**
     * Returns the private model of this instance
     * @return model
     */
    public function getMyModel(): model
    {
        return $this->model;
    }

    /**
     * Creates the field instance for the given field and adds information to it.
     * @param string $field [description]
     * @param array $options [description]
     * @return field
     * @throws ReflectionException
     * @throws exception
     */
    public function makeField(string $field, array $options = []): field
    {
        // load model config for simplicity
        $modelconfig = $this->getMyModel()->config->get();

        // Error if field not in model
        if (!in_array($field, $this->getMyModel()->getFields())) {
            throw new exception(self::EXCEPTION_MAKEFIELD_FIELDNOTFOUNDINMODEL, exception::$ERRORLEVEL_ERROR, $field);
        }

        // Create basic formfield array
        $fielddata = [
          'field_id' => $field,
          'field_name' => $field,
          'field_title' => app::getTranslate()->translate('DATAFIELD.' . $field),
          'field_description' => app::getTranslate()->translate('DATAFIELD.' . $field . '_DESCRIPTION'),
          'field_type' => 'input',
          'field_required' => $options['field_required'] ?? false,
          'field_placeholder' => app::getTranslate()->translate('DATAFIELD.' . $field),
          'field_multiple' => false,
          'field_readonly' => $options['field_readonly'] ?? false,
        ];

        // Get the displaytype of this field
        if (array_key_exists('datatype', $modelconfig) && array_key_exists($field, $modelconfig['datatype'])) {
            $fielddata['field_type'] = $this->getDisplaytype($modelconfig['datatype'][$field]);
            $fielddata['field_datatype'] = $modelconfig['datatype'][$field];
        }

        if ($fielddata['field_type'] == 'yesno') {
            $fielddata['field_type'] = 'select';
            $fielddata['field_displayfield'] = '{$element[\'field_name\']}';
            $fielddata['field_valuefield'] = 'field_value';

            // NOTE: Datatype for this kind of pseudo-boolean field must be null or so
            // because the boolean validator really needs a bool.
            $fielddata['field_datatype'] = null;
            $fielddata['field_elements'] = [
              [
                'field_value' => true,
                'field_name' => 'Ja',
              ],
              [
                'field_value' => false,
                'field_name' => 'Nein',
              ],
            ];
        }

        if ($this->config->exists("required")) {
            if (in_array($field, $this->config->get('required'))) {
                $fielddata['field_required'] = true;
            }
        }

        if (!is_null($this->data)) {
            $fielddata['field_value'] = ($this->data->isDefined($field) ? $this->getMyModel()->exportField(new modelfield($field), $this->data->getData($field)) : null);
        }

        // Set primary key field hidden
        if ($field == $this->getMyModel()->getPrimaryKey()) {
            $fielddata['field_type'] = 'hidden';
        }

        // Decode object datatype
        if (str_contains($fielddata['field_type'], 'bject_')) {
            $fielddata['field_value'] = app::object2array(json_decode($fielddata['field_value']));
        }
        if ($this->getMyModel()->config->exists("required") && in_array($field, $this->getMyModel()->config->get("required"))) {
            $fielddata['field_required'] = true;
        }

        // Modify field to be a reference dropdown
        if (array_key_exists('foreign', $modelconfig) && array_key_exists($field, $modelconfig['foreign'])) {
            if (!app::getValidator('structure_config_modelreference')->reset()->isValid($modelconfig['foreign'][$field])) {
                throw new exception(self::EXCEPTION_MAKEFIELD_INVALIDREFERENCEOBJECT, exception::$ERRORLEVEL_ERROR, $modelconfig['foreign'][$field]);
            }

            $foreign = $modelconfig['foreign'][$field];

            $elements = $this->getModel($foreign['model'], $foreign['app'] ?? app::getApp());

            //
            // skip basic model setup, if we're using the remote api interface anyway.
            //
            if (!($elements instanceof exposesRemoteApiInterface) || !isset($foreign['remote_source'])) {
                if (array_key_exists('order', $foreign) && is_array($foreign['order'])) {
                    foreach ($foreign['order'] as $order) {
                        if (!app::getValidator('structure_config_modelorder')->reset()->isValid($order)) {
                            throw new exception(self::EXCEPTION_MAKEFIELD_INVALIDORDEROBJECT, exception::$ERRORLEVEL_ERROR, $order);
                        }
                        $elements->addOrder($order['field'], $order['direction']);
                    }
                }

                if (array_key_exists('filter', $foreign) && is_array($foreign['filter'])) {
                    foreach ($foreign['filter'] as $filter) {
                        if (!app::getValidator('structure_config_modelfilter')->reset()->isValid($filter)) {
                            throw new exception(self::EXCEPTION_MAKEFIELD_INVALIDFILTEROBJECT, exception::$ERRORLEVEL_ERROR, $filter);
                        }
                        if ($filter['field'] == $elements->getIdentifier() . '_flag') {
                            if ($filter['operator'] == '=') {
                                $elements->withFlag($elements->config->get('flag>' . $filter['value']));
                            } elseif ($filter['operator'] == '!=') {
                                $elements->withoutFlag($elements->config->get('flag>' . $filter['value']));
                            } else {
                                throw new exception(self::EXCEPTION_MAKEFIELD_FILTER_FLAG_INVALIDOPERATOR, exception::$ERRORLEVEL_ERROR, $filter);
                            }
                        } else {
                            $elements->addFilter($filter['field'], $filter['value'], $filter['operator']);
                        }
                    }
                }
            }

            $fielddata['field_type'] = 'select';
            $fielddata['field_displayfield'] = $foreign['display'];
            $fielddata['field_valuefield'] = $foreign['key'];

            if ($elements instanceof exposesRemoteApiInterface && isset($foreign['remote_source'])) {
                $apiEndpoint = $elements->getExposedApiEndpoint();
                $fielddata['field_remote_source'] = $apiEndpoint;

                $remoteSource = $foreign['remote_source'] ?? [];

                $filterKeys = [];
                foreach ($remoteSource['filter_key'] as $filterKey => $filterData) {
                    if (is_array($filterData)) {
                        foreach ($filterData as $filterDataData) {
                            $filterKeys[$filterKey][$filterDataData] = true;
                        }
                    } else {
                        $filterKeys[$filterData] = true;
                    }
                }

                $fielddata['field_remote_source_filter_key'] = $filterKeys;

                //
                // Explicit Filter Key
                // for retrieving an already set, unique and strictly defined value
                //
                if ($remoteSource['explicit_filter_key'] ?? false) {
                    $fielddata['field_remote_source_explicit_filter_key'] = $remoteSource['explicit_filter_key'];
                }

                $fielddata['field_remote_source_parameter'] = $remoteSource['parameters'] ?? [];
                $fielddata['field_remote_source_display_key'] = $remoteSource['display_key'] ?? null;
                $fielddata['field_remote_source_links'] = $foreign['remote_source']['links'] ?? [];
                $fielddata['field_valuefield'] = $foreign['key'];
                $fielddata['field_displayfield'] = $foreign['key']; // $defaultDisplayField[$foreign['model']] ?? $foreign['key'];
            } elseif (!in_array($field, $this->customizedFields)) {
                $fielddata['field_elements'] = $elements->search()->getResult();
            }

            if (array_key_exists('datatype', $modelconfig) && array_key_exists($field, $modelconfig['datatype']) && $modelconfig['datatype'][$field] == 'structure') {
                $fielddata['field_multiple'] = true;
            }
        }

        //
        // nested crud / submodel
        //
        if ($this->config->exists('children') && in_array($field, $this->config->get('children'))) {
            $childConfig = $this->model->config->get('children>' . $field);

            if ($childConfig['type'] === 'foreign') {
                //
                // Handle nested forms
                //
                $fielddata['field_type'] = 'form';

                // provide a sub-form config !
                $crud = $this->childCruds[$field];
                $crud->onCreateFormfield = $this->onCreateFormfield;
                $crud->onFormfieldCreated = $this->onFormfieldCreated;

                if ($this->readOnly) {
                    $crud->readOnly = $this->readOnly;
                }

                // available child config keys:
                // - type (e.g. foreign)
                // - field (reference field)
                $childIdentifierValue = ($this->data && $this->data->isDefined($childConfig['field']) ? $this->getMyModel()->exportField(new modelfield($childConfig['field']), $this->data->getData($childConfig['field'])) : null);
                $form = $crud->makeForm($childIdentifierValue, false); // make form without submit

                $fielddata['form'] = $form;
                $formdata = [];
                foreach ($form->getFields() as $field) {
                    $formdata[$field->getConfig()->get('field_name')] = $field->getConfig()->get('field_value');
                }
                $fielddata['field_value'] = $formdata;
            } elseif ($childConfig['type'] === 'collection') {
                //
                // Handle collections
                //
                $collectionConfig = $this->model->config->get('collection>' . $field);
                $fielddata['field_type'] = 'table';
                $fielddata['field_datatype'] = 'structure';

                $crud = new crud($this->getModel($collectionConfig['model'], $collectionConfig['app'] ?? '', $collectionConfig['vendor'] ?? ''));
                $crud->onCreateFormfield = $this->onCreateFormfield;
                $crud->onFormfieldCreated = $this->onFormfieldCreated;
                // TODO: allow custom crud config somehow?
                // $crud->setConfig('some-crud-config');

                $fielddata['field_rowkey'] = $crud->getMyModel()->getPrimaryKey();

                $fielddata['visibleFields'] = $crud->getConfig()->get('visibleFields');

                $fielddata['labels'] = [];
                foreach ($fielddata['visibleFields'] as $field) {
                    $fielddata['labels'][$field] = app::getTranslate()->translate('DATAFIELD.' . $field);
                }

                $form = $crud->makeForm(null, false);
                $fielddata['form'] = $form->output(true);
            }
        }

        if ($this->readOnly) {
            $fielddata['field_readonly'] = true;
        }

        $c = &$this->onCreateFormfield;
        if ($c !== null && is_callable($c)) {
            $c($fielddata);
        }

        $field = new field($fielddata);
        $field->setType('compact');

        $c = &$this->onFormfieldCreated;
        if ($c !== null && is_callable($c)) {
            $c($field);
        }

        // Add the field to the form
        return $field;
    }

    /**
     * Resolve a datatype to a forced display type
     * @param string $datatype
     * @return string
     */
    public function getDisplaytype(string $datatype): string
    {
        return self::getDisplaytypeStatic($datatype);
    }

    /**
     * [getDisplaytypeStatic description]
     * @param string $datatype [description]
     * @return string           [description]
     */
    public static function getDisplaytypeStatic(string $datatype): string
    {
        return match ($datatype) {
            'structure_address' => 'structure_address',
            'structure_text_telephone' => 'structure_text_telephone',
            'structure' => 'structure',
            'boolean' => 'yesno',
            'text_date', 'text_date_birthdate' => 'date',
            'text_timestamp' => 'timestamp',
            //
            // CHANGED 2020-05-26: relativetime field detection/determination
            // moved to here from field class
            //
            'text_datetime_relative' => 'relativetime',
            default => 'input',
        };
    }

    /**
     * Adds the important fields to the form instance of this crud editor
     *
     * @param string|null $primarykey [primary key of the entry to be used as value base or null]
     * @param bool $addSubmitButton [whether the form should add a submit button field by default]
     * @return form                [the form (also contained in this crud instance, accessible via ->getForm())]
     * @throws ReflectionException
     * @throws exception
     */
    public function makeForm(null|string $primarykey = null, bool $addSubmitButton = true): form
    {
        $this->useEntry($primarykey);

        // set request data only visible for this form
        // usually, this is the complete request,
        // but it may only be a part of it.
        $this->getForm()->setFormRequest($this->getFormNormalizationData());

        if ($this->config->exists('tag')) {
            $this->getForm()->config['form_tag'] = $this->config->get('tag');
        }

        // use "field", if defined in crud config
        if ($this->config->exists('field') && count($this->fields) === 0) {
            $this->fields = $this->config->get('field');
        }

        if (count($this->fields) == 0 && count($this->getForm()->getFieldsets()) == 0) {
            $this->fields = $this->getMyModel()->config->get('field');
        }

        // Be sure to show the primary key in the form
        $this->fields[] = $this->getMyModel()->getPrimaryKey();
        $this->fields = array_unique($this->fields);

        foreach ($this->fields as $field) {
            if ($this->config->exists('disabled') && is_array($this->config->get('disabled')) && in_array($field, $this->config->get('disabled'))) {
                continue;
            }

            if (in_array($field, [$this->getMyModel()->table . "_modified", $this->getMyModel()->table . "_created"])) {
                continue;
            }
            if (!in_array($field, $this->getMyModel()->config->get('field'))) {
                throw new exception(self::EXCEPTION_MAKEFORM_FIELDNOTFOUNDINMODEL, exception::$ERRORLEVEL_ERROR, $field);
            }

            // exclude child model fields that have an active children config for this crud
            if ($this->config->exists('children') && $this->getMyModel()->config->exists('children')) {
                // if field exists in a child config field reference
                $found = false;
                foreach ($this->config->get('children') as $childField) {
                    if (($childConfig = $this->getMyModel()->config->get('children>' . $childField)) !== null) {
                        if ($childConfig['type'] === 'foreign' && $childConfig['field'] == $field) {
                            $found = true;
                            break;
                        } elseif ($childField === $field && $childConfig['type'] === 'collection') {
                            // $found = true;
                            break;
                        }
                    }
                }
                if ($found) {
                    continue;
                }
            }

            if ($field == $this->getMyModel()->table . '_flag') {
                $flags = $this->getMyModel()->config->get('flag');
                if (!is_array($flags)) {
                    continue;
                }

                $value = [];
                $elements = [];

                foreach ($flags as $flagname => $flag) {
                    $value[$flagname] = !is_null($this->data) && $this->getMyModel()->isFlag($flag, $this->data->getData());
                    $elements[] = [
                      'name' => $flagname,
                      'display' => app::getTranslate()->translate('DATAFIELD.' . $field . '_' . $flagname),
                      'value' => $flag,
                    ];
                }

                $fielddata = [
                  'field_name' => $this->getMyModel()->table . '_flag',
                  'field_type' => 'multicheckbox',
                  'field_datatype' => 'structure',
                  'field_title' => app::getTranslate()->translate('DATAFIELD.' . $field),
                  'field_multiple' => true,
                  'field_value' => $value,
                  'field_elements' => $elements,
                  'field_idfield' => 'name',
                  'field_displayfield' => '{$element["display"]}', // todo: translate!
                  'field_valuefield' => 'value',
                ];

                if ($this->readOnly || ($this->config->exists('readonly') && is_array($this->config->get('readonly')) && in_array($field, $this->config->get('readonly')))) {
                    $fielddata['field_readonly'] = true;
                }

                $c = &$this->onCreateFormfield;
                if ($c !== null && is_callable($c)) {
                    $c($fielddata);
                }

                $formField = new field($fielddata);

                $c = &$this->onFormfieldCreated;
                if ($c !== null && is_callable($c)) {
                    $c($formField);
                }

                $this->getForm()->addField($formField);

                continue;
            }

            $options = [];
            if (($this->fieldsformConfig['readonly'] ?? false) && in_array($field, $this->fieldsformConfig['readonly'])) {
                $options['field_readonly'] = true;
            }
            if (($this->fieldsformConfig['required'] ?? false) && in_array($field, $this->fieldsformConfig['required'])) {
                $options['field_required'] = true;
            }
            if ($this->config->exists('readonly') && is_array($this->config->get('readonly')) && in_array($field, $this->config->get('readonly'))) {
                $options['field_readonly'] = true;
            }
            if ($this->config->exists('required') && is_array($this->config->get('required')) && in_array($field, $this->config->get('required'))) {
                $options['field_required'] = true;
            }

            $this->getForm()->addField($this->makeField($field, $options))->setType('compact');
        }

        if ($addSubmitButton) {
            $this->getForm()->addField(
                (new field([
                  'field_name' => 'name',
                  'field_title' => app::getTranslate()->translate('BUTTON.BTN_SAVE'),
                  'field_description' => 'description',
                  'field_id' => 'submit',
                  'field_type' => 'submit',
                  'field_value' => app::getTranslate()->translate('BUTTON.BTN_SAVE'),
                ]))->setType('compact')
            );
        }

        $form = $this->getForm();

        // pass the output config type to the form instance
        $form->outputConfig = $this->outputFormConfig;

        return $form;
    }

    /**
     * Loads one object from the CRUD generator's model if the primary key is defined.
     * @param null|int|string $primarykey
     * @return crud
     * @throws ReflectionException
     * @throws exception
     */
    public function useEntry(null|int|string $primarykey = null): static
    {
        if (is_null($primarykey)) {
            $this->getResponse()->setData('CRUD_FEEDBACK', 'ENTRY_CREATE');
            return $this;
        }
        $this->getResponse()->setData('CRUD_FEEDBACK', 'ENTRY_UPDATE');
        $this->data = new datacontainer($this->getMyModel()->load($primarykey));

        return $this;
    }

    /**
     * returns the current crud configuration
     *
     * @return config [description]
     */
    public function getConfig(): config
    {
        return $this->config;
    }

    /**
     * set config by identifier
     * @param string $identifier [description]
     * @return crud             [description]
     * @throws ReflectionException
     * @throws exception
     */
    public function setConfig(string $identifier = ''): crud
    {
        $this->config = $this->loadConfig($identifier);

        $this->customizedFields = $this->config->get('customized_fields') ?? [];

        if ($identifier !== '') {
            $this->updateChildCrudConfigs();
        }

        return $this;
    }

    /**
     * [updateChildCrudConfigs description]
     * @return void [type] [description]
     * @throws ReflectionException
     * @throws exception
     */
    protected function updateChildCrudConfigs(): void
    {
        if ($this->config->exists('children_config')) {
            $childrenConfig = $this->config->get('children_config');
            foreach ($childrenConfig as $childName => $childConfig) {
                if (isset($this->childCruds[$childName])) {
                    if (isset($childConfig['crud'])) {
                        $this->childCruds[$childName]->setConfig($childConfig['crud']);
                    }
                    if (isset($childConfig['form'])) {
                        $this->childCruds[$childName]->useForm($childConfig['form']);
                    }
                }
            }
        }
    }

    /**
     * russian function
     * limits field output to visibleFields & optionally: "internalFields" in crud config
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function limitFieldOutput(): void
    {
        $visibleFields = $this->getConfig()->get('visibleFields');
        $internalFields = $this->getConfig()->get('internalFields') ?? [];
        $this->model->hideAllFields()->addField(implode(',', array_merge($visibleFields, $internalFields)));
        foreach ($this->childCruds as $crud) {
            $crud->limitFieldOutput();
        }
    }

    /**
     * Enable/disable caching of the crud config
     * @param bool $state [description]
     * @return crud        [description]
     */
    public function setConfigCache(bool $state): crud
    {
        $this->useConfigCache = $state;
        return $this;
    }

    /**
     * [setCustomizedFields description]
     * @param array $fields [description]
     */
    public function setCustomizedFields(array $fields): void
    {
        $this->customizedFields = $fields;
    }

    /**
     * I will add a modifier for a field.
     * Modifiers are used to change the output of a field in the CRUD list.
     * @param string $field
     * @param callable $modifier
     * @return crud
     * @example addModifier('status_id', function($row) {return '<span class="pill">'.$row['status_name'].'</span>';});
     */
    public function addModifier(string $field, callable $modifier): crud
    {
        $this->modifiers[$field] = $modifier;
        return $this;
    }

    /**
     * add a modifier for modifying the whole resultset
     * @param callable $modifier [description]
     * @return crud           [description]
     */
    public function addResultsetModifier(callable $modifier): crud
    {
        $this->resultsetModifiers[] = $modifier;
        return $this;
    }

    /**
     * configures column ordering for crud
     * @param array $columns [description]
     * @return crud
     */
    public function setColumnOrder(array $columns = []): crud
    {
        $this->columnOrder = $columns;
        return $this;
    }

    /**
     * I will add a ROW modifier
     * which can change the output "<tr>" elements attributes
     * @param callable $modifier [description]
     * @return crud           [description]
     * @example addModifier('status_id', function($row) {return '<span class="pill">'.$row['status_name'].'</span>';});
     */
    public function addRowModifier(callable $modifier): crud
    {
        $this->rowModifiers[] = $modifier;
        return $this;
    }

    /**
     * Returns the config for listview()
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function listconfig(): void
    {
        $visibleFields = $this->config->get('visibleFields');

        // Only append primarykey, if not added to visibleFields
        if (!in_array($this->getMyModel()->getPrimaryKey(), $visibleFields)) {
            $visibleFields[] = $this->getMyModel()->getPrimaryKey();
        }

        $formattedFields = [];

        //
        // Format foreign key values as defined by the model
        //
        if (!is_null($this->getMyModel()->config->get('foreign'))) {
            $foreignKeys = $this->getMyModel()->config->get('foreign');

            $formattedFields = array_reduce(array_keys($foreignKeys), function ($carry, $key) {
                // foreign keys use a formatted output field AND a data key
                $carry[$key] = $key . '_FORMATTED';
                return $carry;
            }, $formattedFields);
        }

        //
        // also include "modifier" fields as _FORMATTED ones.
        //
        $formattedFields = array_merge(
            $formattedFields,
            array_reduce(array_keys($this->modifiers), function ($carry, $key) {
                // use modifier key as final field
                $carry[$key] = $key;
                return $carry;
            }, [])
        );

        //
        // Fields that are available as raw data AND as a _FORMATTED one
        //
        $this->getResponse()->setData('formattedFields', $formattedFields);

        //
        // Enable custom selection of displayed fields (columns)
        //
        $this->getResponse()->setData('enable_displayfieldselection', ($this->config->exists('displayFieldSelection') ? $this->config->get('displayFieldSelection') : false));

        if ($this->config->exists('availableFields')) {
            $availableFields = $this->config->get('availableFields');
        } else {
            // enable ALL fields of the model to be displayed
            $availableFields = $this->getMyModel()->config->get('field');
        }

        // add formatted fields to availableFields
        $availableFields = array_merge($availableFields, array_keys($formattedFields));

        // remove all disabled fields
        if ($this->config->exists('disabled')) {
            $availableFields = array_diff($availableFields, $this->config->get('disabled'));
        }

        // merge and kill duplicates
        $availableFields = array_values(array_unique($availableFields));

        $displayFields = [];

        // display fields are either the visibleFields (defined in config) or submitted
        // in the latter case, we have to check for legitimacy first.
        if ($this->getRequest()->isDefined('display_selectedfields') && $this->getResponse()->getData('enable_displayfieldselection')) {
            $selectedFields = $this->getRequest()->getData('display_selectedfields');
            if (is_array($selectedFields)) {
                //
                // NOTE/CHANGED 2019-06-14: we have to include some more fields
                //
                $avFields = array_unique(array_merge($visibleFields, $availableFields));
                foreach ($selectedFields as $displayField) {
                    if (in_array($displayField, $avFields)) {
                        $displayFields[] = $displayField;
                    }
                }
            }
        }

        if (count($displayFields) > 0) {
            $visibleFields = $displayFields;
        } else {
            // add all modifier fields by default
            // if no field selection provided
            $visibleFields = array_merge($visibleFields, array_keys($this->modifiers));
        }

        if (!in_array($this->getMyModel()->getPrimaryKey(), $visibleFields)) {
            $visibleFields[] = $this->getMyModel()->getPrimaryKey();
        }

        //
        // Provide some labels for frontend display
        //
        $fieldLabels = [];
        foreach (array_merge($availableFields, $visibleFields, $formattedFields) as $field) {
            if (!is_string($field)) {
                continue;
            }
            $fieldLabels[$field] = app::getTranslate()->translate('DATAFIELD.' . $field);
        }
        foreach ($availableFields as $field) {
            if ($fieldLabels[$field] ?? false) {
                $fieldLabels[$field] = app::getTranslate()->translate('DATAFIELD.' . $field);
            }
        }
        $this->getResponse()->setData('labels', $fieldLabels);
        if ($this->getConfig()->exists('export>_security>group')) {
            if ($enableExport = app::getAuth()->memberOf($this->getConfig()->get('export>_security>group'))) {
                $this->getResponse()->setData('export_types', $this->getConfig()->get('export>allowedTypes'));
            }
            $this->getResponse()->setData('enable_export', $enableExport);
        } else {
            $this->getResponse()->setData('enable_export', false);
        }

        if ($this->getConfig()->exists('import>_security>group')) {
            if ($enableImport = app::getAuth()->memberOf($this->getConfig()->get('import>_security>group'))) {
                // $this->getResponse()->setData('export_types', $this->getConfig()->get('export>allowedTypes'));
            }
            $this->getResponse()->setData('enable_import', $enableImport);
        } else {
            $this->getResponse()->setData('enable_import', false);
        }

        $fieldActions = $this->config->get("action>field") ?? [];
        $filters = $this->config->get('visibleFilters', []);
        // merge-in provided filters
        $filters = array_merge($filters, $this->providedFilters);

        //
        // build a form from filters
        //
        $filterForm = null;

        if (count($filters) > 0) {
            $filterForm = new form([
              'form_id' => 'filterform',
              'form_method' => 'post',
              'form_action' => '',
            ]);

            $filterForm->setFormRequest($this->getRequest()->getData(self::CRUD_FILTER_IDENTIFIER) ?? []);

            foreach ($filters as $filterSpecifier => $filterConfig) {
                $specifier = explode('.', $filterSpecifier);
                $useModel = $this->getMyModel();

                $fName = $specifier[count($specifier) - 1];

                if (count($specifier) == 2) {
                    // we have a model/table reference
                    $useModel = $this->getModel($specifier[0]);
                }

                // field is a foreign key
                if (!($filterConfig['wildcard'] ?? false) && in_array($fName, $useModel->config->get('field'))) {
                    $field = $this->makeFieldForeign($useModel, $fName, $filterConfig); // options?
                } elseif ($filterConfig['config']['field_config'] ?? false) {
                    $fieldData = array_merge(
                        [
                          'field_title' => app::getTranslate()->translate('DATAFIELD.' . $fName),
                          'field_name' => $filterSpecifier,
                          'field_type' => 'input',
                        ],
                        $filterConfig['config']['field_config']
                    );
                    $field = new field($fieldData);
                } else {
                    // wildcard, no normalization needed
                    $field = new field([
                      'field_title' => app::getTranslate()->translate('DATAFIELD.' . $fName),
                      'field_name' => $filterSpecifier,
                      'field_type' => 'input',
                    ]);
                }

                $filterForm->addField($field);
            }
        }

        if (count($this->columnOrder) > 0) {
            $visibleFields = array_values(array_unique(array_merge(array_intersect($this->columnOrder, $visibleFields), $visibleFields), SORT_REGULAR));
        } else {
            $visibleFields = array_values(array_unique($visibleFields, SORT_REGULAR));
        }
        $this->getResponse()->setData('filterform', $filterForm?->output(true));

        $this->getResponse()->setData('topActions', $this->prepareActionsOutput($this->config->get("action>top") ?? []));
        $this->getResponse()->setData('bulkActions', $this->prepareActionsOutput($this->config->get("action>bulk") ?? []));
        $this->getResponse()->setData('elementActions', $this->prepareActionsOutput($this->config->get("action>element") ?? []));
        $this->getResponse()->setData('fieldActions', $this->prepareActionsOutput($fieldActions) ?? []);
        $this->getResponse()->setData('visibleFields', $visibleFields);
        $this->getResponse()->setData('availableFields', $availableFields);
        $this->getResponse()->setData('crud_filter_identifier', self::CRUD_FILTER_IDENTIFIER);
        $this->getResponse()->setData('filters_used', $filterForm?->normalizeData($filterForm->getData()));
        $this->getResponse()->setData('enable_search_bar', $this->config->exists("visibleFilters>_search"));
        $this->getResponse()->setData('modelinstance', $this->getMyModel());
    }

    /**
     * function for making fields, independent of the current crud model
     * @param model $model [description]
     * @param string $field [description]
     * @param array $options [description]
     * @return field           [description]
     * @throws ReflectionException
     * @throws exception
     */
    public function makeFieldForeign(model $model, string $field, array $options = []): field
    {
        // load model config for simplicity
        $modelconfig = $model->config->get();

        // Error if field not in model
        if (!in_array($field, $model->getFields())) {
            throw new exception(self::EXCEPTION_MAKEFIELD_FIELDNOTFOUNDINMODEL, exception::$ERRORLEVEL_ERROR, $field);
        }

        // Create basic formfield array
        $fielddata = [
          'field_id' => $field,
          'field_name' => $field,
          'field_title' => app::getTranslate()->translate('DATAFIELD.' . $field),
          'field_description' => app::getTranslate()->translate('DATAFIELD.' . $field . '_DESCRIPTION'),
          'field_type' => 'input',
          'field_required' => $options['field_required'] ?? false,
          'field_placeholder' => app::getTranslate()->translate('DATAFIELD.' . $field),
          'field_multiple' => false,
          'field_readonly' => $options['field_readonly'] ?? false,
        ];

        // Get the displaytype of this field
        if (array_key_exists('datatype', $modelconfig) && array_key_exists($field, $modelconfig['datatype'])) {
            $fielddata['field_type'] = $this->getDisplaytype($modelconfig['datatype'][$field]);
            $fielddata['field_datatype'] = $modelconfig['datatype'][$field];
        }

        if ($fielddata['field_type'] == 'yesno') {
            $fielddata['field_type'] = 'select';
            $fielddata['field_displayfield'] = '{$element[\'field_name\']}';
            $fielddata['field_valuefield'] = 'field_value';

            // NOTE: Datatype for this kind of pseudo-boolean field must be null or so
            // because the boolean validator really needs a bool.
            $fielddata['field_datatype'] = null;
            $fielddata['field_elements'] = [
              [
                'field_value' => true,
                'field_name' => 'Ja',
              ],
              [
                'field_value' => false,
                'field_name' => 'Nein',
              ],
            ];
        }

        // Modify field to be a reference dropdown
        if (array_key_exists('foreign', $modelconfig) && array_key_exists($field, $modelconfig['foreign'])) {
            if (!app::getValidator('structure_config_modelreference')->reset()->isValid($modelconfig['foreign'][$field])) {
                throw new exception(self::EXCEPTION_MAKEFIELD_INVALIDREFERENCEOBJECT, exception::$ERRORLEVEL_ERROR, $modelconfig['foreign'][$field]);
            }

            $foreign = $modelconfig['foreign'][$field];

            $elements = $this->getModel($foreign['model'], $foreign['app'] ?? app::getApp());

            //
            // skip basic model setup, if we're using the remote api interface anyway.
            //
            if (!($elements instanceof exposesRemoteApiInterface) || !isset($foreign['remote_source'])) {
                if (array_key_exists('order', $foreign) && is_array($foreign['order'])) {
                    foreach ($foreign['order'] as $order) {
                        if (!app::getValidator('structure_config_modelorder')->reset()->isValid($order)) {
                            throw new exception(self::EXCEPTION_MAKEFIELD_INVALIDORDEROBJECT, exception::$ERRORLEVEL_ERROR, $order);
                        }
                        $elements->addOrder($order['field'], $order['direction']);
                    }
                }

                if (array_key_exists('filter', $foreign) && is_array($foreign['filter'])) {
                    foreach ($foreign['filter'] as $filter) {
                        if (!app::getValidator('structure_config_modelfilter')->reset()->isValid($filter)) {
                            throw new exception(self::EXCEPTION_MAKEFIELD_INVALIDFILTEROBJECT, exception::$ERRORLEVEL_ERROR, $filter);
                        }
                        if ($filter['field'] == $elements->getIdentifier() . '_flag') {
                            if ($filter['operator'] == '=') {
                                $elements->withFlag($elements->config->get('flag>' . $filter['value']));
                            } elseif ($filter['operator'] == '!=') {
                                $elements->withoutFlag($elements->config->get('flag>' . $filter['value']));
                            } else {
                                throw new exception(self::EXCEPTION_MAKEFIELD_FILTER_FLAG_INVALIDOPERATOR, exception::$ERRORLEVEL_ERROR, $filter);
                            }
                        } else {
                            $elements->addFilter($filter['field'], $filter['value'], $filter['operator']);
                        }
                    }
                }
            }

            $fielddata['field_type'] = 'select';
            $fielddata['field_displayfield'] = $foreign['display'];
            $fielddata['field_valuefield'] = $foreign['key'];

            if ($elements instanceof exposesRemoteApiInterface && isset($foreign['remote_source'])) {
                $apiEndpoint = $elements->getExposedApiEndpoint();
                $fielddata['field_remote_source'] = $apiEndpoint;

                $remoteSource = $foreign['remote_source'] ?? [];

                $filterKeys = [];
                foreach ($remoteSource['filter_key'] as $filterKey => $filterData) {
                    if (is_array($filterData)) {
                        foreach ($filterData as $filterDataData) {
                            $filterKeys[$filterKey][$filterDataData] = true;
                        }
                    } else {
                        $filterKeys[$filterData] = true;
                    }
                }

                $fielddata['field_remote_source_filter_key'] = $filterKeys;

                //
                // Explicit Filter Key
                // for retrieving an already set, unique and strictly defined value
                //
                if ($remoteSource['explicit_filter_key'] ?? false) {
                    $fielddata['field_remote_source_explicit_filter_key'] = $remoteSource['explicit_filter_key'];
                }

                $fielddata['field_remote_source_parameter'] = $remoteSource['parameters'] ?? [];
                $fielddata['field_remote_source_display_key'] = $remoteSource['display_key'] ?? null;
                $fielddata['field_remote_source_links'] = $foreign['remote_source']['links'] ?? [];
                $fielddata['field_valuefield'] = $foreign['key'];
                $fielddata['field_displayfield'] = $foreign['key'];
            } elseif (!in_array($field, $this->customizedFields)) {
                $fielddata['field_elements'] = $elements->search()->getResult();
            }

            //
            // by default, we allow multiselect
            //
            $multiple = true;
            if (array_key_exists('field_multiple', $options)) {
                $multiple = $options['field_multiple'];
            } elseif (array_key_exists('multiple', $options)) {
                $multiple = $options['multiple'];
            }

            if ($multiple) {
                $fielddata['field_datatype'] = 'structure';
                $fielddata['field_multiple'] = $multiple;
            }

            if ($elementDatatype = $modelconfig['datatype'][$field] ?? false) {
                //
                // if multiselect, provide element datatype for correct conversions
                //
                if ($multiple) {
                    $fielddata['field_element_datatype'] = $elementDatatype;
                } else {
                    $fielddata['field_datatype'] = $elementDatatype;
                }
            }
        }

        $c = &$this->onCreateFormfield;
        if ($c !== null && is_callable($c)) {
            $c($fielddata);
        }

        $field = new field($fielddata);
        $field->setType('compact');

        $c = &$this->onFormfieldCreated;
        if ($c !== null && is_callable($c)) {
            $c($field);
        }

        // Add the field to the form
        return $field;
    }

    /**
     * prepare action configs for output
     *
     * @param array $actions [description]
     * @return array          [description]
     * @throws ReflectionException
     * @throws exception
     */
    protected function prepareActionsOutput(array $actions): array
    {
        $handled = [];
        foreach ($actions as $key => $value) {
            if (array_key_exists('_security', $value) && array_key_exists('group', $value['_security'])) {
                if (!app::getAuth()->memberOf($value['_security']['group'])) {
                    continue;
                }
            }
            if (array_key_exists('condition', $value)) {
                $condition = null;
                eval($value['condition']);
                if (!$condition) {
                    continue;
                }
            }
            $value['display'] = app::getTranslate()->translate("BUTTON.BTN_" . $key);

            $handled[$key] = $value;
        }
        return $handled;
    }

    /**
     * [stats description]
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function stats(): void
    {
        $this->applyFilters();

        if ($this->allowPagination) {
            $this->makePagination();
        }
    }

    /**
     * Will apply defaultFilter properties to the model instance of this CRUD generator
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    protected function applyFilters(): void
    {
        if (!$this->getRequest()->isDefined(self::CRUD_FILTER_IDENTIFIER)) {
            return;
        }
        $filters = $this->getRequest()->getData(self::CRUD_FILTER_IDENTIFIER);
        if (!is_array($filters)) {
            return;
        }

        if (array_key_exists('search', $filters) && $filters['search'] != '') {
            if ($this->config->exists("visibleFilters>_search") && is_array($this->config->get("visibleFilters>_search"))) {
                $filterCollection = [];
                foreach ($this->config->get("visibleFilters>_search>fields") as $field) {
                    $filterCollection[] = ['field' => $field, 'value' => $this->getFilterstring($filters['search'], $field, true), 'operator' => 'LIKE'];
                }
                $this->getMyModel()->addDefaultFilterCollection($filterCollection, 'OR');
            }
            // do NOT return; as we may use other filters, too. Why tho?
            // return;
        }

        foreach ($filters as $key => $value) {
            // exclude search key here, as we're not returning after a wildcard search anymore
            if ($key === 'search') {
                continue;
            }

            if ($providedFilter = $this->providedFilters[$key] ?? false) {
                $providedFilter['callback']($this, $value);
                continue;
            }

            if ($key == $this->getMyModel()->getIdentifier() . '_flag') {
                if (is_array($value)) {
                    foreach ($value as $flagval) {
                        $this->getMyModel()->withDefaultFlag($this->getFilterstring($flagval, $key));
                    }
                } else {
                    $this->getMyModel()->withDefaultFlag($this->getFilterstring($value, $key));
                }
            } elseif (is_array($value) && $this->model->config->exists("datatype>" . $key) && in_array($this->model->config->get("datatype>" . $key), ['text_timestamp', 'text_date'])) {
                $this->getMyModel()->addDefaultFilter($key, $this->getFilterstring($value[0], $key), '>=');
                $this->getMyModel()->addDefaultFilter($key, $this->getFilterstring($value[1], $key), '<=');
            } else {
                $wildcard = $this->config->exists("visibleFilters>" . $key . ">wildcard") && ($this->config->get("visibleFilters>" . $key . ">wildcard") == true);
                $operator = $this->config->exists("visibleFilters>" . $key . ">operator") ? $this->config->get("visibleFilters>" . $key . ">operator") : $this->getDefaultoperator($key, $wildcard);
                $this->getMyModel()->addDefaultFilter($key, $this->getFilterstring($value, $key, $wildcard), $operator);
            }
        }
    }

    /**
     * Will return the filterable string that is used for the given field's datatype
     * @param mixed $value [description]
     * @param string $field [description]
     * @param bool $wildcard [description]
     * @return mixed            [description]
     * @throws ReflectionException
     * @throws exception
     */
    protected function getFilterstring(mixed $value, string $field, bool $wildcard = false): mixed
    {
        if (is_array($value)) {
            return $value;
        }
        return match ($this->getMyModel()->getFieldtype(new modelfield($field))) {
            'number_natural' => $value,
            default => $wildcard ? '%' . $value . '%' : $value,
        };
    }

    /**
     * Returns the default CRUD filter for the given filters
     * @param string $field [description]
     * @param bool $wildcard [description]
     * @return string            [description]
     * @throws ReflectionException
     * @throws exception
     */
    protected function getDefaultoperator(string $field, bool $wildcard = false): string
    {
        return match ($this->getMyModel()->getFieldtype(new modelfield($field))) {
            'number_natural' => '=',
            default => $wildcard ? 'LIKE' : '=',
        };
    }

    /**
     * Sends the pagination data to the response
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    protected function makePagination(): void
    {
        //
        // small HACK:
        // temporarily add a count field
        // perform the query using the given configuration
        // and remove it afterwards.
        //
        if ($this->getConfig()->get('seek') || $this->getRequest()->getData('crud_stats_async')) {
            $count = null;
        } else {
            $start = microtime(true);
            $count = $this->getMyModel()->getCount();
            $end = microtime(true);

            // DEBUG!
            $this->getResponse()->setData('_count_time', ($end - $start));
        }

        // default value, if none of the below works:
        $page = 1;
        if ($this->getRequest()->isDefined('crud_pagination_page')) {
            // explicit page request
            $page = (int)$this->getRequest()->getData('crud_pagination_page');
        } elseif ($this->getRequest()->isDefined('crud_pagination_page_prev')) {
            // fallback to previous page value, if page hasn't been submitted
            $page = (int)$this->getRequest()->getData('crud_pagination_page_prev');
        }

        if ($this->getRequest()->isDefined('crud_pagination_limit')) {
            $limit = (int)$this->getRequest()->getData('crud_pagination_limit');
        } else {
            $limit = $this->config->get("pagination>limit", 10);
        }

        if ($this->getConfig()->get('seek') || $this->getRequest()->getData('crud_stats_async')) {
            $pages = null;
        } else {
            $pages = ($limit == 0 || $count == 0) ? 1 : ceil($count / $limit);
        }

        // when not in seek mode (normal mode), limit last page to max. page available
        if (!$this->getConfig()->get('seek') && !$this->getRequest()->getData('crud_stats_async')) {
            // pagination limit change with present page param, that is out of range:
            if ($page > $pages) {
                $page = $pages;
            }
        }

        if ($this->getConfig()->get('seek') === true) {
            //
            // Alternative pagination method: Seeking!
            //
            $firstId = $this->getRequest()->getData('crud_pagination_first_id');
            $lastId = $this->getRequest()->getData('crud_pagination_last_id');
            $seekMode = $this->getRequest()->getData('crud_pagination_seek') ?? 0;

            $ordering = 'ASC'; // is this really our default?
            if ($this->config->get("order")) {
                foreach ($this->config->get("order") as $order) {
                    if ($order['field'] === $this->getMyModel()->getPrimaryKey()) {
                        $ordering = $order['direction'];
                    }
                }
            }

            // stable position
            if ($firstId && $seekMode == 0) {
                $operator = $ordering === 'ASC' ? '>=' : '<=';
                $this->getMyModel()->addFilter($this->getMyModel()->getPrimaryKey(), $firstId, $operator);
            }

            // we're moving backwards
            if ($firstId && $seekMode < 0) {
                $operator = $ordering === 'ASC' ? '<' : '>';
                $this->getMyModel()->addFilter($this->getMyModel()->getPrimaryKey(), $firstId, $operator);
                $this->crudSeekOverridePkeyOrder = $ordering === 'ASC' ? 'DESC' : 'ASC'; // enable overriding the other ordering...
            }

            // we're moving forward
            if ($lastId && $seekMode > 0) {
                $operator = $ordering === 'ASC' ? '>' : '<';
                $this->getMyModel()->addFilter($this->getMyModel()->getPrimaryKey(), $lastId, $operator);
            }

            $this->getMyModel()->setLimit($limit);
        } elseif ($pages > 1 || $this->getRequest()->getData('crud_stats_async')) {
            $this->getMyModel()->setLimit($limit)->setOffset(($page - 1) * $limit);
        }

        // Response
        $this->getResponse()->addData(
            [
              'crud_pagination_seek_enabled' => $this->getConfig()->get('seek') === true,
              'crud_pagination_count' => $count,
              'crud_pagination_page' => $page,
              'crud_pagination_pages' => $pages,
              'crud_pagination_limit' => $limit,
            ]
        );
    }

    /**
     * [setResultData description]
     * @param array $data [description]
     */
    public function setResultData(array $data): void
    {
        $this->resultData = $data;
    }

    /**
     * [export description]
     * @param bool $raw [enables raw export]
     * @return void
     * @throws NoticeException
     * @throws ReflectionException
     * @throws exception
     */
    public function export(bool $raw = false): void
    {
        // disable limit and offset temporarily
        $this->allowPagination = false;
        $this->rawMode = $raw;
        $this->listview();
        $this->rawMode = false;
        $this->allowPagination = true;
    }

    /**
     * Returns a list of the entries in the model and paginate, filter and order it
     * @return void
     * @throws NoticeException
     * @throws ReflectionException
     * @throws exception
     */
    public function listview(): void
    {
        $visibleFields = $this->config->get('visibleFields');

        // Only append primarykey, if not added to visibleFields
        if (!in_array($this->getMyModel()->getPrimaryKey(), $visibleFields)) {
            $visibleFields[] = $this->getMyModel()->getPrimaryKey();
        }

        $formattedFields = [];

        //
        // Format foreign key values as defined by the model
        //
        if (!is_null($this->getMyModel()->config->get('foreign'))) {
            $foreignKeys = $this->getMyModel()->config->get('foreign');

            $formattedFields = array_reduce(array_keys($foreignKeys), function ($carry, $key) {
                // foreign keys use a formatted output field AND a data key
                $carry[$key] = $key . '_FORMATTED';
                return $carry;
            }, $formattedFields);
        }

        //
        // also include "modifier" fields as _FORMATTED ones.
        //
        $formattedFields = array_merge(
            $formattedFields,
            array_reduce(array_keys($this->modifiers), function ($carry, $key) {
                // use modifier key as final field
                $carry[$key] = $key;
                return $carry;
            }, [])
        );

        //
        // Fields that are available as raw data AND as a _FORMATTED one
        //
        $this->getResponse()->setData('formattedFields', $formattedFields);

        //
        // Enable custom selection of displayed fields (columns)
        //
        $this->getResponse()->setData('enable_displayfieldselection', ($this->config->exists('displayFieldSelection') ? $this->config->get('displayFieldSelection') : false));

        if ($this->config->exists('availableFields')) {
            $availableFields = $this->config->get('availableFields');
        } else {
            // enable ALL fields of the model to be displayed
            $availableFields = $this->getMyModel()->config->get('field');
        }

        // add formatted fields to availableFields
        $availableFields = array_merge($availableFields, array_keys($formattedFields));

        // remove all disabled fields
        if ($this->config->exists('disabled')) {
            $availableFields = array_diff($availableFields, $this->config->get('disabled'));
        }

        // merge and kill duplicates
        $availableFields = array_values(array_unique($availableFields));

        $displayFields = [];

        // display fields are either the visibleFields (defined in config) or submitted
        // in the latter case, we have to check for legitimacy first.
        if ($this->getRequest()->isDefined('display_selectedfields') && $this->getResponse()->getData('enable_displayfieldselection')) {
            $selectedFields = $this->getRequest()->getData('display_selectedfields');
            if (is_array($selectedFields)) {
                //
                // NOTE/CHANGED 2019-06-14: we have to include some more fields
                //
                $avFields = array_unique(array_merge($visibleFields, $availableFields));
                foreach ($selectedFields as $displayField) {
                    if (in_array($displayField, $avFields)) {
                        $displayFields[] = $displayField;
                    }
                }
            }
        }

        if (count($displayFields) > 0) {
            $visibleFields = $displayFields;
        } else {
            // add all modifier fields by default
            // if no field selection provided
            $visibleFields = array_merge($visibleFields, array_keys($this->modifiers));
        }

        if (!in_array($this->getMyModel()->getPrimaryKey(), $visibleFields)) {
            $visibleFields[] = $this->getMyModel()->getPrimaryKey();
        }

        //
        // Provide some labels for frontend display
        //
        $fieldLabels = [];
        foreach (array_merge($availableFields, $visibleFields, $formattedFields) as $field) {
            if (!is_string($field)) {
                continue;
            }
            $fieldLabels[$field] = app::getTranslate()->translate('DATAFIELD.' . $field);
        }
        foreach ($availableFields as $field) {
            if ($fieldLabels[$field] ?? false) {
                $fieldLabels[$field] = app::getTranslate()->translate('DATAFIELD.' . $field);
            }
        }
        $this->getResponse()->setData('labels', $fieldLabels);

        $this->applyFilters();

        if ($this->allowPagination) {
            $this->makePagination();
        }

        if ($this->crudSeekOverridePkeyOrder ?? false) {
            // Seek-mode order hack
            // following ordering happens during runtime below
            $this->getMyModel()->addOrder($this->getMyModel()->getPrimaryKey(), $this->crudSeekOverridePkeyOrder);
        }

        foreach ($this->config->get("order") as $order) {
            $this->getMyModel()->addOrder($order['field'], $order['direction']);
        }


        if ($this->getConfig()->exists('export>_security>group')) {
            if ($enableExport = app::getAuth()->memberOf($this->getConfig()->get('export>_security>group'))) {
                $this->getResponse()->setData('export_types', $this->getConfig()->get('export>allowedTypes'));
            }
            $this->getResponse()->setData('enable_export', $enableExport);
        } else {
            $this->getResponse()->setData('enable_export', false);
        }

        if ($this->getConfig()->exists('import>_security>group')) {
            if ($enableImport = app::getAuth()->memberOf($this->getConfig()->get('import>_security>group'))) {
                // $this->getResponse()->setData('export_types', $this->getConfig()->get('export>allowedTypes'));
            }
            $this->getResponse()->setData('enable_import', $enableImport);
        } else {
            $this->getResponse()->setData('enable_import', false);
        }

        $fieldActions = $this->config->get("action>field") ?? [];
        $filters = $this->config->get('visibleFilters', []);
        // merge-in provided filters
        $filters = array_merge($filters, $this->providedFilters);

        //
        // build a form from filters
        //
        $filterForm = null;

        if (count($filters) > 0) {
            $filterForm = new form([
              'form_id' => 'filterform',
              'form_method' => 'post',
              'form_action' => '',
            ]);

            $filterForm->setFormRequest($this->getRequest()->getData(self::CRUD_FILTER_IDENTIFIER) ?? []);

            foreach ($filters as $filterSpecifier => $filterConfig) {
                $specifier = explode('.', $filterSpecifier);
                $useModel = $this->getMyModel();

                $fName = $specifier[count($specifier) - 1];

                if (count($specifier) == 2) {
                    // we have a model/table reference
                    $useModel = $this->getModel($specifier[0]);
                }

                // field is a foreign key
                if (!($filterConfig['wildcard'] ?? false) && in_array($fName, $useModel->config->get('field'))) {
                    $field = $this->makeFieldForeign($useModel, $fName, $filterConfig); // options?
                } elseif ($filterConfig['config']['field_config'] ?? false) {
                    $fieldData = array_merge(
                        [
                          'field_title' => app::getTranslate()->translate('DATAFIELD.' . $fName),
                          'field_name' => $filterSpecifier,
                          'field_type' => 'input',
                        ],
                        $filterConfig['config']['field_config']
                    );
                    $field = new field($fieldData);
                } else {
                    // wildcard, no normalization needed
                    $field = new field([
                      'field_title' => app::getTranslate()->translate('DATAFIELD.' . $fName),
                      'field_name' => $filterSpecifier,
                      'field_type' => 'input',
                    ]);
                }

                $filterForm->addField($field);
            }
        }

        //
        // NOTE/EXPERIMENTAL:
        // if $visibleFields contains one or more elements that are arrays
        // (e.g. object-path-style fields)
        // this may not work properly in some cases?
        //
        if (count($this->columnOrder) > 0) {
            $visibleFields = array_values(array_unique(array_merge(array_intersect($this->columnOrder, $visibleFields), $visibleFields), SORT_REGULAR));
        } else {
            $visibleFields = array_values(array_unique($visibleFields, SORT_REGULAR));
        }

        $resultData = $this->resultData ?? $this->getMyModel()->search()->getResult();

        //
        // Seek mode runtime ordering
        //
        if ($this->crudSeekOverridePkeyOrder ?? false) {
            //
            // Stable usort based on main models' PKEY
            // this is done in reverse, as we previously changed core ordering in ::makePagination
            //
            self::stable_usort($resultData, function ($a, $b) {
                //
                // NOTE: we use the spaceship operator here, which outputs -1, 0 or 1 depending on value equality
                // and we finally multiply it by -1 to re-gain the old/original PKEY ordering
                //
                return ($a[$this->getMyModel()->getPrimaryKey()] <=> $b[$this->getMyModel()->getPrimaryKey()])
                  *
                  ($this->crudSeekOverridePkeyOrder === 'ASC' ? -1 : 1);
            });
        }


        if (count($this->resultsetModifiers) > 0) {
            foreach ($this->resultsetModifiers as $modifier) {
                $resultData = $modifier($resultData);
            }
        }

        // Send data to the response
        if ($this->rawMode) {
            $this->getResponse()->setData('rows', $resultData);
        } else {
            $this->getResponse()->setData('rows', $this->makeFields($resultData, $visibleFields));
        }

        $this->getResponse()->setData('filterform', $filterForm?->output(true));

        $this->getResponse()->setData('topActions', $this->prepareActionsOutput($this->config->get("action>top") ?? []));
        $this->getResponse()->setData('bulkActions', $this->prepareActionsOutput($this->config->get("action>bulk") ?? []));
        $this->getResponse()->setData('elementActions', $this->prepareActionsOutput($this->config->get("action>element") ?? []));
        $this->getResponse()->setData('fieldActions', $this->prepareActionsOutput($fieldActions) ?? []);
        $this->getResponse()->setData('visibleFields', $visibleFields);
        $this->getResponse()->setData('availableFields', $availableFields);

        $this->getResponse()->setData('crud_filter_identifier', self::CRUD_FILTER_IDENTIFIER);
        $this->getResponse()->setData('filters_used', $filterForm?->normalizeData($filterForm->getData()));
        $this->getResponse()->setData('enable_search_bar', $this->config->exists("visibleFilters>_search"));
        $this->getResponse()->setData('modelinstance', $this->getMyModel());

        // editable mode:
        if ($this->getRequest()->getData('crud_editable')) {
            $form = $this->makeForm(null, false);
            $this->getResponse()->setData('formconfig', $form->output(true));
        }

        //
        // Alternative pagination method: seek
        // provide first and last id fetched
        //
        if ($this->getConfig()->get('seek') === true) {
            $rows = $this->getResponse()->getData('rows');
            if (is_array($rows) && count($rows) !== 0) {
                $first = reset($rows);
                $last = end($rows);
                $this->getResponse()->addData([
                  'crud_pagination_first_id' => $first[$this->getMyModel()->getPrimaryKey()],
                  'crud_pagination_last_id' => $last[$this->getMyModel()->getPrimaryKey()],
                ]);
            }
        }
    }

    /**
     * stable usort function
     * @param array $array
     * @param $value_compare_func
     * @return bool
     */
    protected static function stable_usort(array &$array, $value_compare_func): bool
    {
        $index = 0;
        foreach ($array as &$item) {
            $item = [$index++, $item];
        }
        $result = usort($array, function ($a, $b) use ($value_compare_func) {
            $result = call_user_func($value_compare_func, $a[1], $b[1]);
            return $result == 0 ? $a[0] - $b[0] : $result;
        });
        foreach ($array as &$item) {
            $item = $item[1];
        }
        return $result;
    }

    /**
     * This method loops all the given datasets in $rows and the given $fields.
     * The method generates a new output array and tries to overwrite field values by using getFieldoutput()
     * @param array $rows
     * @param array $fields
     * @return array
     * @throws NoticeException
     * @throws ReflectionException
     * @throws exception
     */
    protected function makeFields(array $rows, array $fields): array
    {
        // simply return dataset, if there is no modifier (row, field) and no foreign key data to be fetched
        if (count($this->rowModifiers) == 0
          && count($this->modifiers) == 0
          && is_null($this->getMyModel()->config->get('foreign'))
        ) {
            return $rows;
        }

        $searchForFields = $fields;
        if (count($this->modifiers) > 0) { // merge or replace?
            $searchForFields = array_merge($searchForFields, array_keys($this->modifiers));
        }

        $myRows = [];
        foreach ($rows as $row) {
            if ($this->provideRawData) {
                $object = $row;
            } else {
                $object = [];
            }

            if (count($this->modifiers) > 0 || !is_null($this->getMyModel()->config->get('foreign'))) {
                foreach ($searchForFields as $field) {
                    $o = $this->getFieldoutput($row, $field);
                    //
                    // field is an array: object path
                    //
                    if (is_array($field)) {
                        $object = deepaccess::set($object, $field, $o[0]);
                        continue;
                    }
                    // @NOTE: we're differentiating between a pre-formatted and a raw value here:
                    // if array index 1 is set, this is the formatted value.
                    if (array_key_exists(1, $o)) {
                        $object[$field . '_FORMATTED'] = $o[1];
                    }
                    $object[$field] = $o[0];
                }
            } else {
                $object = $row;
            }

            if (count($this->rowModifiers) > 0) {
                $attributes = [];
                foreach ($this->rowModifiers as $rowModifier) {
                    $modifierOutput = $rowModifier($row);
                    if (is_array($modifierOutput)) {
                        $attributes = array_merge_recursive($attributes, $modifierOutput);
                    }
                }

                $object['__modifier'] = join(
                    ' ',
                    array_map(function ($key) use ($attributes) {
                        if (is_bool($attributes[$key])) {
                            return $attributes[$key] ? $key : '';
                        }
                        return $key . '="' . $attributes[$key] . '"';
                    }, array_keys($attributes))
                );
            }

            $myRows[] = $object;
        }
        return $myRows;
    }

    /**
     * This method will return the output value of the given $field using the data from the given $row.
     * It will determine the output value by the following two situations:
     * #1: The $field has been given a modifier using ->addModifier($field, $callable)
     * #2: The $field has been configured to display data from another model (a.k.a foreign key / reference)
     * @param array $row
     * @param array|string $field
     * @return array
     * @throws NoticeException
     * @throws ReflectionException
     * @throws exception
     */
    protected function getFieldoutput(array $row, array|string $field): array
    {
        if (is_array($field)) {
            return [deepaccess::get($row, $field)];
        }

        if (array_key_exists($field, $this->modifiers)) {
            return [$this->modifiers[$field]($row)];
        }

        if (!isset($row[$field])) {
            return [null];
        }

        if ($field == $this->getMyModel()->table . '_flag') {
            $flags = $this->getMyModel()->config->get("flag");
            $ret = '';
            foreach ($flags as $flagname => $flagval) {
                if ($this->getMyModel()->isFlag($flagval, $row)) {
                    $text = app::getTranslate()->translate('DATAFIELD.' . $field . '_' . $flagname);
                    $ret .= '<span class="badge">' . $text . '</span>';
                }
            }
            return [$row[$field], $ret];
        }

        $foreignkeys = $this->getMyModel()->config->get("foreign");
        if (!is_array($foreignkeys) || !array_key_exists($field, $foreignkeys)) {
            return [$row[$field]];
        }

        if (array_key_exists('optional', $foreignkeys[$field]) && $foreignkeys[$field]['optional'] && $row[$field] == null) {
            return [$row[$field]];
        }

        // TODO: We may have to differentiate here
        // for values which still have to be displayed in some way,
        // but they're NULL. ...

        $obj = $foreignkeys[$field];

        if ($obj['display'] != null) {
            if (is_array($row[$field])) {
                $vals = [];
                foreach ($row[$field] as $val) {
                    $element = $this->getModelCached($obj['model'])->loadByUnique($obj['key'], $val);
                    if (count($element) > 0) {
                        @eval('$vals[] = "' . $obj['display'] . '";');
                    }
                }
                $ret = implode(', ', $vals);
            } else {
                // $field should be $obj['key']. check dependencies, correct mistakes and do it right!
                // TODO: wrap this in a try/catch statement
                // bare/json datasource's may lose unique keys. fallback to null or "undefined"?

                // first: try to NOT perform an additional query
                $ret = null; // default fallback value

                // NOTE: we silence E_NOTICE's in core app
                // therefore, temporary override the error handler
                // and throw an internal exception to catch.
                // In This case, we know the eval failed, and we have to re-try.
                // This will/should fail, when a specific key is missing
                set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line) {
                    throw new NoticeException($err_msg, 0, $err_severity, $err_file, $err_line);
                }, E_NOTICE);

                try {
                    $evalResult = @eval('$ret = "' . $obj['display'] . '";');
                } catch (NoticeException) {
                    $evalResult = false;
                }

                // restore error handler, should be the core-app one.
                restore_error_handler();
                //
                // NOTE/WARNING:
                // eval only returns FALSE, if there's an exception thrown internally
                // as we changed the app class to no longer throw an exception on a Notice
                // (e.g. if array index/key not set), we don't run into the situation
                //
                // so, we now check for $evalResult === null
                //
                // CHANGED 2021-04-14: see note above, we override the error handler temporarily
                //
                if (!$evalResult) {
                    $element = $this->getModelCached($obj['model'], $obj['app'] ?? '', $obj['vendor'] ?? '')->loadByUnique($obj['key'], $row[$field]);
                    if (count($element) > 0) {
                        @eval('$ret = "' . $obj['display'] . '";');
                    } else {
                        $ret = null;
                    }
                }
            }
            return [$row[$field], $ret];
        } else {
            return [$row[$field]];
        }
    }

    /**
     * [getModelCached description]
     * @param string $model [description]
     * @param string $app [description]
     * @param string $vendor [description]
     * @return model         [description]
     * @throws ReflectionException
     * @throws exception
     */
    protected function getModelCached(string $model, string $app = '', string $vendor = ''): model
    {
        $identifier = implode(',', [$model, $app, $vendor]);
        if (!($this->cachedModels[$identifier] ?? false)) {
            $this->cachedModels[$identifier] = $this->getModel($model, $app, $vendor);
        }
        return $this->cachedModels[$identifier];
    }

    /**
     * imports a previously exported dataset
     *
     * @param array $data [description]
     * @param bool $ignorePkeys [description]
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function import(array $data, bool $ignorePkeys = true): void
    {
        foreach ($data as $dataset) {
            $this->getMyModel()->reset();
            if (count($errors = $this->getMyModel()->validate($dataset)->getErrors()) > 0) {
                // erroneous dataset found
                throw new exception('CRUD_IMPORT_INVALID_DATASET', exception::$ERRORLEVEL_ERROR, $errors);
            }
        }

        foreach ($data as &$dataset) {
            if (($dataset[$this->getMyModel()->getPrimaryKey()] ?? false) && $ignorePkeys) {
                unset($dataset[$this->getMyModel()->getPrimaryKey()]);
            }
            // TODO: recurse?
            $this->getMyModel()->entryMake($dataset)->entrySave();
        }

        $this->getResponse()->setData('import_data', $data);
    }

    /**
     * Adds a top action
     * @param array $action
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function addTopaction(array $action): void
    {
        $this->addAction('top', $action);
    }

    /**
     * Adds an action button / element to the given action type.
     * @param string $type
     * @param array $action
     * @return void
     * @throws ReflectionException
     * @throws exception
     * @todo use really abstract and usable action value-object in here.
     */
    protected function addAction(string $type, array $action): void
    {
        if (count($errors = app::getValidator('structure_config_crud_action')->reset()->validate($action)) > 0) {
            throw new exception(self::EXCEPTION_ADDACTION_INVALIDACTIONOBJECT, exception::$ERRORLEVEL_ERROR, $errors);
        }

        $config = $this->config->get();
        $config['action'][$type][$action['name']] = $action;
        $this->config = new config($config);
    }

    /**
     * Adds a bulk action
     * @param array $action
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function addBulkaction(array $action): void
    {
        $this->addAction('bulk', $action);
    }

    /**
     * Adds an element action
     * @param array $action
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function addElementaction(array $action): void
    {
        $this->addAction('element', $action);
    }

    /**
     * Returns a form HTML code for creating an object. After validating the data in the form class AND validating the data in the model class, we will try to store the data in the model.
     * @throws ReflectionException
     * @throws exception
     */
    public function create(): void
    {
        $this->getResponse()->setData('context', 'crud');

        $form = $this->makeForm();

        // Fire the form init event
        $hookval = $this->eventCrudFormInit->invokeWithResult($this, $form);
        if ($hookval instanceof form) {
            $form = $hookval;
        }

        if (!$form->isSent()) {
            $this->getResponse()->setData('form', $form->output($this->outputFormConfig));
            return;
        }

        $data = $this->getMyModel()->normalizeData($this->getFormNormalizationData());

        // Fire the before validation event
        $newData = $this->eventCrudBeforeValidation->invokeWithResult($this, $data);
        if (is_array($newData)) {
            $data = $newData;
        }

        // form validation before model validation
        if (!$form->isValid()) {
            $this->getResponse()->setStatus(response::STATUS_INTERNAL_ERROR);
            $this->getResponse()->setData('errors', $form->getErrorstack()->getErrors());
            $this->getResponse()->setData('view', 'validation_error');
            return;
        }

        if (!$this->getMyModel()->isValid($data)) {
            $this->getResponse()->setStatus(response::STATUS_INTERNAL_ERROR);
            $this->getResponse()->setData('errors', $this->getMyModel()->getErrors());
            $this->getResponse()->setData('view', 'save_error');
            return;
        }

        // Fire hook after successful validation
        $this->eventCrudAfterValidation->invoke($this, $data);

        // Fire hook for additional validators
        $errorResults = $this->eventCrudValidation->invokeWithAllResults($this, $data);

        $errors = [];
        foreach ($errorResults as $errorCollection) {
            if (count($errorCollection) > 0) {
                $errors = array_merge($errors, $errorCollection);
            }
        }

        if (count($errors) > 0) {
            $this->getResponse()->setStatus(response::STATUS_INTERNAL_ERROR);
            $this->getResponse()->setData('errors', $errors);
            $this->getResponse()->setData('view', 'save_error');
            return;
        }

        $this->eventCrudBeforeSave->invoke($this, $data);

        $model = $this->getMyModel();
        if ($model instanceof model\schematic\sql || $model instanceof model\abstractDynamicValueModel) {
            $model->saveWithChildren($data);
        } else {
            throw new exception('CRUD_CREATE_WRONG_MODEL', exception::$ERRORLEVEL_FATAL);
        }

        // eventCrudBeforeSave MUST NOT modify data, due to crud mechanics. Data might be modified in eventCrudBeforeValidation or so
        $this->eventCrudSuccess->invoke($this, $data);

        $this->getResponse()->setData($this->getMyModel()->getPrimaryKey(), $this->getMyModel()->lastInsertId());

        $this->getResponse()->setData('view', 'crud_success');
    }

    /**
     * @return void
     * @throws exception
     */
    public function bulkDelete(): void
    {
        if (!$this->getRequest()->isDefined($this->getMyModel()->getPrimaryKey())) {
            throw new exception('CRUD_BULK_DELETE_PRIMARYKEY_UNDEFINED', exception::$ERRORLEVEL_ERROR);
        }
    }

    /**
     * [bulkEdit description]
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function bulkEdit(): void
    {
        if ($this->getRequest()->isDefined('data')) {
            $data = $this->getRequest()->getData('data');

            //
            // Validate
            //
            foreach ($data as $entry) {
                // get full entry with modified delta
                if ($entry[$this->getMyModel()->getPrimaryKey()] ?? false) {
                    $currentEntry = $this->getMyModel()->load($entry[$this->getMyModel()->getPrimaryKey()]);
                } else {
                    $currentEntry = [];
                }
                $currentEntry = array_replace_recursive($currentEntry, $entry);

                // TODO: validate using bulk form?

                if (!$this->getMyModel()->isValid($currentEntry)) {
                    $this->getResponse()->setStatus(response::STATUS_INTERNAL_ERROR);
                    $this->getResponse()->setData('errors', $this->getMyModel()->getErrors());
                    $this->getResponse()->setData('view', 'save_error');
                    return;
                }
            }

            //
            // Save
            //
            $transaction = new transaction('crud_bulk_edit', [$this->getMyModel()]);
            $transaction->start();

            $pkeyValues = [];

            foreach ($data as $entry) {
                //
                // TODO: how to handle delta edits on nested models?
                //
                $model = $this->getMyModel();
                if ($model instanceof model\schematic\sql || $model instanceof model\abstractDynamicValueModel) {
                    $model->saveWithChildren($entry);
                } else {
                    throw new exception('CRUD_BULKEDIT_WRONG_MODEL', exception::$ERRORLEVEL_FATAL);
                }

                if ($pkeyValue = $entry[$this->getMyModel()->getPrimaryKey()] ?? false) {
                    $pkeyValues[] = $pkeyValue;
                } else {
                    $pkeyValues[] = $this->getMyModel()->lastInsertId();
                }
            }

            $transaction->end();

            $this->getResponse()->setData($this->getMyModel()->getPrimaryKey(), $pkeyValues);
        } else {
            throw new exception('CRUD_BULK_EDIT_DATA_UNDEFINED', exception::$ERRORLEVEL_ERROR);
        }
    }

    /**
     * Returns the form HTML code for editing an existing entry. Will make sure the given data is compliant to the form's and model's configuration
     * @param int|string $primarykey
     * @throws ReflectionException
     * @throws exception
     */
    public function edit(int|string $primarykey): void
    {
        $form = $this->makeForm($primarykey);

        // Fire the form init event
        $hookval = $this->eventCrudFormInit->invokeWithResult($this, $form);

        if ($hookval instanceof form) {
            $form = $hookval;
        }

        if ($this->config->exists('action>crud_edit')) {
            $this->getResponse()->setData('editActions', $this->config->get('action>crud_edit'));
        }

        if (!$form->isSent()) {
            $this->getResponse()->setData('form', $form->output($this->outputFormConfig));
            return;
        }

        // we can use $form->getData() here, but then we're receiving a lot more data (e.g. non-input or disabled fields!)
        $data = $this->getMyModel()->normalizeData($this->getFormNormalizationData());

        $newData = $this->eventCrudBeforeValidation->invokeWithResult($this, $data);
        if (is_array($newData)) {
            $data = $newData;
        }

        // form validation before model validation
        if (!$form->isValid()) {
            $this->getResponse()->setStatus(response::STATUS_INTERNAL_ERROR);
            $this->getResponse()->setData('errors', $form->getErrorstack()->getErrors());
            $this->getResponse()->setData('view', 'validation_error');
            return;
        }

        $this->getMyModel()->entryLoad($primarykey);

        $this->getMyModel()->entryUpdate($data);

        if (count($errors = $this->getMyModel()->entryValidate()) > 0) {
            $this->getResponse()->setStatus(response::STATUS_INTERNAL_ERROR);
            $this->getResponse()->setData('errors', $errors);
            $this->getResponse()->setData('view', 'save_error');
            return;
        }

        // Fire hook after successful validation
        $this->eventCrudAfterValidation->invoke($this, $data);

        // Fire hook for additional validators
        $errorResults = $this->eventCrudValidation->invokeWithAllResults($this, $data);

        $errors = [];
        foreach ($errorResults as $errorCollection) {
            if (count($errorCollection) > 0) {
                $errors = array_merge($errors, $errorCollection);
            }
        }

        if (count($errors) > 0) {
            $this->getResponse()->setStatus(response::STATUS_INTERNAL_ERROR);
            $this->getResponse()->setData('errors', $errors);
            $this->getResponse()->setData('view', 'save_error');
            return;
        }

        // eventCrudBeforeSave MUST NOT modify data, due to crud mechanics. Data might be modified in eventCrudBeforeValidation or so
        $this->eventCrudBeforeSave->invoke($this, $data);

        $this->getMyModel()->entryUpdate($data);
        $this->getMyModel()->entrySave();

        $this->eventCrudSuccess->invokeWithResult($this, $data);

        $this->getResponse()->setData('view', 'crud_success');
    }

    /**
     * Returns the form HTML code for showing an existing entry without editing function. Will make sure the given data is compliant to the form's and model's configuration
     * @param int|string $primaryKey [description]
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function show(int|string $primaryKey): void
    {
        $this->readOnly = true;

        // apply to all nested cruds
        foreach ($this->childCruds as $crud) {
            $crud->readOnly = true;
        }

        // use modified makeForm function, that allows $addSubmitButton = false (second argument)
        $form = $this->makeForm($primaryKey, false);

        // Fire the form init event
        $hookval = $this->eventCrudFormInit->invokeWithResult($this, $form);

        if ($hookval instanceof form) {
            $form = $hookval;
        }

        $this->getResponse()->setData('form', $form->output($this->outputFormConfig));
    }

    /**
     * [useData description]
     * @param array $data [description]
     * @return crud        [description]
     */
    public function useData(array $data): crud
    {
        $this->data = new datacontainer($data);
        return $this;
    }

    /**
     * [useFormNormalizationData description]
     * @return crud [description]
     * @throws exception
     */
    public function useFormNormalizationData(): crud
    {
        $this->data = new datacontainer($this->getMyModel()->normalizeData($this->getFormNormalizationData()));
        foreach ($this->childCruds as $crud) {
            $crud->useFormNormalizationData();
        }
        return $this;
    }

    /**
     * provides a custom filter option
     * @param string $name [description]
     * @param array $config [description]
     * @param callable $cb [description]
     * @return void [type]           [description]
     */
    public function provideFilter(string $name, array $config, callable $cb): void
    {
        $this->providedFilters[$name] = [
          'config' => $config,
          'callback' => $cb,
        ];
    }

    /**
     * [setProvideRawData description]
     * @param bool $state [description]
     */
    public function setProvideRawData(bool $state): void
    {
        $this->provideRawData = $state;
    }

    /**
     * loads the crud config
     * defaults to schema_table, if no identifier (or '') is specified
     * @param string $identifier [description]
     * @return config             [description]
     * @throws ReflectionException
     * @throws exception
     */
    protected function loadConfig(string $identifier = ''): config
    {
        if ($identifier == '') {
            $identifier = $this->getMyModel()->schema . '_' . $this->getMyModel()->table;
        }

        // prepare config
        $config = null;
        $cacheGroup = app::getVendor() . '_' . app::getApp() . '_CRUD_CONFIG';
        $cacheKey = $identifier;

        //
        // Try to retrieve cached config
        //
        if ($this->useConfigCache) {
            if ($cachedConfig = app::getCache()->get($cacheGroup, $cacheKey)) {
                $config = new config($cachedConfig);
            }
        }

        //
        // If config not already set by cache, get it
        //
        if (!$config) {
            $config = new json('config/crud/' . $identifier . '.json', true);

            // Cache, if enabled.
            if ($this->useConfigCache) {
                app::getCache()->set($cacheGroup, $cacheKey, $config->get());
            }
        }

        return $config;
    }
}
