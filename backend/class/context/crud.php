<?php

namespace codename\core\ui\context;

use codename\core\app;
use codename\core\context;
use codename\core\context\contextInterface;
use codename\core\datacontainer;
use codename\core\exception;
use codename\core\export\exportInterface;
use codename\core\helper\file;
use codename\core\model;
use codename\core\NoticeException;
use codename\core\request\filesInterface;
use codename\core\value\text;
use codename\rest\response\json;
use LogicException;
use ReflectionException;

/**
 * CRUD Context base class
 * @package \codename\core\ui
 */
class crud extends context implements contextInterface
{
    /**
     * You loaded a view that requires the model's primary key to be sent.
     * It seems that I did not receive it in the current request container.
     * @var string
     */
    public const EXCEPTION_VIEW_CRUD_EDIT_PRIMARYKEYNOTSENT = 'EXCEPTION_VIEW_CRUD_EDIT_PRIMARYKEYNOTSENT';

    /**
     * You loaded a view that requires the model's primary key to be sent.
     * It seems that I did not receive it in the current request container.
     * @var string
     */
    public const EXCEPTION_VIEW_CRUD_DELETE_PRIMARYKEYNOTSENT = 'EXCEPTION_VIEW_CRUD_DELETE_PRIMARYKEYNOTSENT';

    /**
     * You are trying to use the nested instance of a CRUD editor.
     * Unfortunately it seems to remain NULL until this point of time.
     * @var string
     */
    public const EXCEPTION_GETCRUDINSTANCE_CRUDPROPERTYISNULL = 'EXCEPTION_GETCRUDINSTANCE_CRUDPROPERTYISNULL';

    /**
     * Overwrite what model to use in the CRUD generator
     * @var null|string
     */
    protected ?string $modelName = null;

    /**
     * Overwrite the name of the app the requested model is located
     * @var null|string
     */
    protected ?string $modelApp = null;

    /**
     * Holds the model for this CRUD generator
     * @var null|model
     */
    protected ?model $model = null;

    /**
     * Holds the CRUD instance for this request
     * @var null|\codename\core\ui\crud
     */
    protected ?\codename\core\ui\crud $crud = null;

    /**
     * Creates the CRUD instance in the context instance
     * Sends the name of the primary key to the response
     * @throws ReflectionException
     * @throws exception
     * @todo Why do we have to set the template here again?
     */
    public function __construct()
    {
        $this->getResponse()->setData('primarykey', $this->getModelinstance()->getPrimaryKey());
        // $this->getResponse()->setData('template', 'basic');

        $dict = [
          'context' => $this->getRequest()->getData('context'),
          'view' => $this->getRequest()->getData('context') . '_' . $this->getRequest()->getData('view'),
        ];

        if ($this->getRequest()->getData('action')) {
            $dict['action'] = $this->getRequest()->getData('context') . '_' . $this->getRequest()->getData('view') . '___' . $this->getRequest()->getData('action');
        }

        foreach ($dict as &$d) {
            if ($d !== null) {
                $d = app::getTranslate()->translate('CRUD.' . $d);
            }
        }

        $this->getResponse()->setData('crud_label', $dict);

        $this->setCrudInstance(new \codename\core\ui\crud($this->getModelinstance()));

        // hook into crud instance init
        // we need to change the output type to bare json config
        if ($this->getResponse() instanceof json) {
            $this->getCrudInstance()->outputFormConfig = true;
        }

        return $this;
    }

    /**
     * Returns the exact model instance that was requested
     * @return model
     * @throws ReflectionException
     * @throws exception
     * @access public
     */
    public function getModelinstance(): model
    {
        if (is_null($this->model)) {
            $this->setModelinstance($this->getModel($this->getModelname(), $this->getModelapp()));
        }
        return $this->model;
    }

    /**
     * Stores the model instance in this class
     * @param model $model
     * @return void
     * @access public
     */
    public function setModelinstance(model $model): void
    {
        $this->model = $model;
    }

    /**
     * Returns the name of the requested model
     * @return string
     * @access public
     */
    public function getModelname(): string
    {
        if (is_null($this->modelName)) {
            return $this->getRequest()->getData('context');
        }
        return $this->modelName;
    }

    /**
     * Sets the name of the model that will be requested
     * @param string $modelName
     * @return void
     * @access public
     */
    public function setModelname(string $modelName): void
    {
        $this->modelName = $modelName;
    }

    /**
     * Returns the app the requested model is located in
     * @return string
     * @throws ReflectionException
     * @throws exception
     * @access public
     */
    public function getModelapp(): string
    {
        if (is_null($this->modelApp)) {
            return app::getApp();
        }
        return $this->modelApp;
    }

    /**
     * Sets the app the requested model is located in
     * @param string $modelApp
     * @return void
     * @access public
     */
    public function setModelapp(string $modelApp): void
    {
        $this->modelApp = $modelApp;
    }

    /**
     * Set the CRUD instance of this context
     * @param \codename\core\ui\crud $crud
     * @return void
     */
    protected function setCrudInstance(\codename\core\ui\crud $crud): void
    {
        $this->crud = $crud;
    }

    /**
     * Return the CRUD instance of this context
     * @return \codename\core\ui\crud
     * @throws exception
     */
    public function getCrudInstance(): \codename\core\ui\crud
    {
        if (is_null($this->crud)) {
            throw new exception(self::EXCEPTION_GETCRUDINSTANCE_CRUDPROPERTYISNULL, exception::$ERRORLEVEL_WARNING, ($this->model->getPrimaryKey() ?? null));
        }
        return $this->crud;
    }

    /**
     * Using the CRUD generator to generate the CRUD config
     * @return void
     * @throws ReflectionException
     * @throws exception
     * @access public
     */
    public function view_crud_config(): void
    {
        $this->getResponse()->setData('context', 'crud');
        $this->getCrudInstance()->listconfig();
    }

    /**
     * Crud Stats
     * also to be used for async counts
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function view_crud_stats(): void
    {
        $this->getResponse()->setData('context', 'crud');
        $this->getCrudInstance()->stats();
    }

    /**
     * If there are overridden crud_list functions
     * this may be used for doing this special override
     * @return void
     */
    public function action_crud_stats(): void
    {
    }

    /**
     * Using the CRUD generator to generate the list page
     * @return void
     * @throws ReflectionException
     * @throws NoticeException
     * @throws exception
     * @access public
     */
    public function view_crud_list(): void
    {
        $this->getResponse()->setData('context', 'crud');
        if ($this->getRequest()->getData('action') == 'crud_stats') {
            $this->getCrudInstance()->stats();
        } else {
            $this->getCrudInstance()->listview();
        }
    }

    /**
     * Using the CRUD generator to generate the edit page
     * @return void
     * @throws ReflectionException
     * @throws exception
     * @access public
     */
    public function view_crud_edit(): void
    {
        $this->getResponse()->setData('context', 'crud');
        $primaryKey = $this->getModelinstance()->getPrimaryKey();

        if (!$this->getRequest()->isDefined($primaryKey) || strlen($this->getRequest()->getData($primaryKey)) == 0) {
            throw new exception(self::EXCEPTION_VIEW_CRUD_EDIT_PRIMARYKEYNOTSENT, exception::$ERRORLEVEL_WARNING, $primaryKey);
        }

        $this->getCrudInstance()->edit($this->getRequest()->getData($primaryKey));
    }

    /**
     * Using the CRUD generator to generate the show page
     * @return void
     * @throws ReflectionException
     * @throws exception
     * @access public
     */
    public function view_crud_show(): void
    {
        $this->getResponse()->setData('context', 'crud');
        $this->getResponse()->setData('view', 'crud_show');
        $primaryKey = $this->getModelinstance()->getPrimaryKey();

        if (!$this->getRequest()->isDefined($primaryKey) || strlen($this->getRequest()->getData($primaryKey)) == 0) {
            throw new exception(self::EXCEPTION_VIEW_CRUD_EDIT_PRIMARYKEYNOTSENT, exception::$ERRORLEVEL_WARNING, $primaryKey);
        }

        $this->getCrudInstance()->show($this->getRequest()->getData($primaryKey));
    }

    /**
     * Using the CRUD generator to generate the delete page
     * @return void
     * @throws ReflectionException
     * @throws exception
     * @access public
     */
    public function view_crud_delete(): void
    {
        $this->getResponse()->setData('context', 'crud');
        $primaryKey = $this->getModelinstance()->getPrimaryKey();

        if (!$this->getRequest()->isDefined($primaryKey) || strlen($this->getRequest()->getData($primaryKey)) == 0) {
            throw new exception(self::EXCEPTION_VIEW_CRUD_DELETE_PRIMARYKEYNOTSENT, exception::$ERRORLEVEL_WARNING, $primaryKey);
        }

        // If confirmed, delete action
        if ($this->getRequest()->isDefined('__confirm')) {
            $this->getModelinstance()->delete($this->getRequest()->getData($primaryKey));
            $this->getResponse()->setRedirect('', $this->getRequest()->getData('context'), 'crud_list');
            $this->getResponse()->doRedirect();
        }

        // Load stuff to show
        $this->getResponse()->setData('keyname', $primaryKey);
        $this->getResponse()->setData('keyvalue', $this->getRequest()->getData($primaryKey));
        $this->getResponse()->setData('modelObject', $this->getModelinstance()->load($this->getRequest()->getData($primaryKey)));
    }

    /**
     * Using the CRUD generator to generate the create page
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function view_crud_create(): void
    {
        $this->getResponse()->setData('context', 'crud');
        $this->getCrudInstance()->create();
    }

    /**
     * Using the CRUD generator to overwrite/edit multiple datasets
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public function view_bulk_edit(): void
    {
        $this->getResponse()->setData('context', 'crud');
        $this->getCrudInstance()->bulkEdit();
    }

    /**
     * Using the CRUD generator to delete multiple datasets at once
     * @return void
     * @throws exception
     */
    public function view_bulk_delete(): void
    {
        $this->getResponse()->setData('context', 'crud');
        $this->getCrudInstance()->bulkDelete();
    }

    /**
     * [action_import description]
     * @return void [type] [description]
     * @throws ReflectionException
     * @throws exception
     */
    public function action_import(): void
    {
        $this->getResponse()->setData('context', 'crud');
        if ($this->getCrudInstance()->getConfig()->exists('import>_security>group')) {
            $group = $this->getCrudInstance()->getConfig()->get('import>_security>group');
            if (app::getAuth()->memberOf($group)) {
                // get import file
                $request = $this->getRequest();
                if ($request instanceof filesInterface) {
                    $importFileUpload = $request->getFiles()['crud_import_file'] ?? null;
                    if ($importFileUpload && $importFileUpload['tmp_name']) {
                        $json = json_decode(file_get_contents($importFileUpload['tmp_name']), true);
                        $this->getCrudInstance()->import($json);
                    } else {
                        throw new exception('CRUD_IMPORT_INVALID_IMPORT_FILE_UPLOAD', exception::$ERRORLEVEL_ERROR);
                    }
                } else {
                    throw new exception('CRUD_IMPORT_INVALID_REQUEST', exception::$ERRORLEVEL_ERROR);
                }
            } else {
                throw new exception('CRUD_IMPORT_NOT_ALLOWED_BY_AUTH', exception::$ERRORLEVEL_ERROR);
            }
        } else {
            throw new exception('CRUD_IMPORT_NOT_ALLOWED_BY_CONFIG', exception::$ERRORLEVEL_ERROR);
        }
    }

    /**
     * Enables export of the current crud_list
     * @return void
     * @throws NoticeException
     * @throws ReflectionException
     * @throws exception
     */
    public function action_export(): void
    {
        $this->getResponse()->setData('context', 'crud');

        if ($this->getCrudInstance()->getConfig()->exists('export>_security>group')) {
            $group = $this->getCrudInstance()->getConfig()->get('export>_security>group');
            if (app::getAuth()->memberOf($group)) {
                // handle raw mode
                $rawMode = false;
                if ($this->getRequest()->getData('export_mode') === 'raw') {
                    if (!$this->getCrudInstance()->getConfig()->get('export>allowRaw')) {
                        throw new exception('EXPORT_MODE_RAW_NOT_ALLOWED', exception::$ERRORLEVEL_ERROR);
                    }

                    // perform raw modifications
                    $rawMode = true;
                }

                // selected export ids
                if ($selectedIds = $this->getRequest()->getData('export_selected_id')) {
                    $this->getCrudInstance()->getMyModel()->addDefaultFilter(
                        $this->getCrudInstance()->getMyModel()->getPrimaryKey(),
                        $selectedIds
                    );
                }

                $this->getCrudInstance()->export($rawMode);

                // should be customizable: (e.g. excel, standard, ...) exports.
                $exportType = $this->getRequest()->getData('export_type');

                // check export types
                if (!in_array($exportType, $this->getCrudInstance()->getConfig()->get('export>allowedTypes'))) {
                    throw new exception('EXPORT_TYPE_NOT_ALLOWED', exception::$ERRORLEVEL_ERROR, $exportType);
                }

                $exportClass = app::getInheritedClass('export_' . $exportType);
                $export = new $exportClass();

                if ($export instanceof exportInterface) {
                    if (!$rawMode) {
                        foreach ($this->getResponse()->getData('visibleFields') as $field) {
                            $export->addField(new text($field));
                        }
                    } elseif ($this->getResponse()->getData('rows')[0] ?? false) {
                        foreach ($this->getResponse()->getData('rows')[0] as $key => $irrelevantValue) {
                            $export->addField(new text($key));
                        }
                    } else {
                        // error?
                    }

                    foreach ($this->getResponse()->getData('rows') as $row) {
                        $export->addRow(new datacontainer($row));
                    }

                    // stupid.
                    $fileExtension = match ($exportType) {
                        'json' => 'json',
                        'csv_excel' => 'csv',
                        default => throw new LogicException("Method not implemented."),
                    };

                    $filename = 'Export_' . $this->getCrudInstance()->getMyModel()->getIdentifier() . '_' . time() . '.' . $fileExtension;
                    $exportedFile = '/tmp/' . $filename;

                    $export->setFilename($exportedFile)->export();

                    file::downloadToClient($exportedFile, $filename);

                    //
                // TODO: delete tmp file - this requires downloadToClient NOT to stop things executing.
                    //
                } else {
                    throw new exception('EXPORT_CLASS_INVALID', exception::$ERRORLEVEL_FATAL, $exportClass);
                }
            } else {
                throw new exception('EXPORT_DISALLOWED_BY_AUTH', exception::$ERRORLEVEL_ERROR);
            }
        } else {
            throw new exception('EXPORT_DISALLOWED_BY_CONFIG', exception::$ERRORLEVEL_ERROR);
        }
    }
}
