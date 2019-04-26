<?php
namespace codename\core\ui\context;

use codename\core\app;
use codename\core\exception;

/**
 * CRUD Context base class
 * @package \codename\core\ui
 */
class crud extends \codename\core\context implements \codename\core\context\contextInterface {

    /**
     * You loaded a view that requires the model's primary key to be sent.
     * <br />It seems that I did not receive it in the current request container.
     * @var string
     */
    CONST EXCEPTION_VIEW_CRUD_EDIT_PRIMARYKEYNOTSENT = 'EXCEPTION_VIEW_CRUD_EDIT_PRIMARYKEYNOTSENT';

    /**
     * You loaded a view that requires the model's primary key to be sent.
     * <br />It seems that I did not receive it in the current request container.
     * @var string
     */
    CONST EXCEPTION_VIEW_CRUD_DELETE_PRIMARYKEYNOTSENT = 'EXCEPTION_VIEW_CRUD_DELETE_PRIMARYKEYNOTSENT';

    /**
     * You are trying to use the nested instance of a CRUD editor.
     * <br />Unfortunately it seems to remain NULL until this point of time.
     * @var string
     */
    CONST EXCEPTION_GETCRUDINSTANCE_CRUDPROPERTYISNULL = 'EXCEPTION_GETCRUDINSTANCE_CRUDPROPERTYISNULL';

    /**
     * Overwrite what model to use in the CRUD generator
     * @var string
     */
    protected $modelName = null;

    /**
     * Overwrite the name of the app the requested model is located
     * @var string
     */
    protected $modelApp = null;

    /**
     * Holds the model for this CRUD generator
     * @var \codename\core\model
     */
    protected $model = null;

    /**
     * Holds the CRUD instance for this request
     * @var \codename\core\ui\crud
     */
    protected $crud = null;

    /**
     * Creates the CRUD instance in the context instance
     * <br />Sends the name of the primary key tot he response
     * @return \codename\core\ui\context\crud
     * @todo Why do we have to set the template here again?
     */
    public function __construct() {
        $this->getResponse()->setData('primarykey', $this->getModelinstance()->getPrimarykey());
        // $this->getResponse()->setData('template', 'basic');

        $dict = [
          'context' => $this->getRequest()->getData('context'),
          'view'    => $this->getRequest()->getData('context').'_'.$this->getRequest()->getData('view')
        ];

        if($this->getRequest()->getData('action')) {
          $dict['action'] = $this->getRequest()->getData('context').'_'.$this->getRequest()->getData('view').'___'.$this->getRequest()->getData('action');
        }

        foreach($dict as &$d) {
          if($d !== null) {
            $d = app::getTranslate()->translate('CRUD.'.$d);
          }
        }

        $this->getResponse()->setData('crud_label', $dict);

        $this->setCrudinstance(new \codename\core\ui\crud($this->getModelinstance()));

        // hook into crud instance init
        // we need to change the output type to bare json config
        if($this->getResponse() instanceof \codename\rest\response\json) {
          $this->getCrudinstance()->outputFormConfig = true;
        }

        return $this;
    }

    /**
     * Using the CRUD generator to generate the list page
     * @return void
     * @access public
     */
    public function view_crud_list () {
        $this->getResponse()->setData('context', 'crud');
        $this->getCrudinstance()->listview();
        return;
    }

    /**
     * Using the CRUD generator to generate the edit page
     * @return void
     * @access public
     */
    public function view_crud_edit () {
        $this->getResponse()->setData('context', 'crud');
        $primaryKey = $this->getModelinstance()->getPrimarykey();

        if(!$this->getRequest()->isDefined($primaryKey) || strlen($this->getRequest()->getData($primaryKey)) == 0) {
            throw new \codename\core\exception(self::EXCEPTION_VIEW_CRUD_EDIT_PRIMARYKEYNOTSENT, \codename\core\exception::$ERRORLEVEL_WARNING, $primaryKey);
        }

        $this->getCrudinstance()->edit($this->getRequest()->getData($primaryKey));
        return;
    }

    /**
     * Using the CRUD generator to generate the show page
     * @author Kevin Dargel <kevin@jocoon.de>
     * @return void
     * @access public
     */
    public function view_crud_show () {
        $this->getResponse()->setData('context', 'crud');
        $this->getResponse()->setData('view', 'crud_show');
        $primaryKey = $this->getModelinstance()->getPrimarykey();

        if(!$this->getRequest()->isDefined($primaryKey) || strlen($this->getRequest()->getData($primaryKey)) == 0) {
            throw new \codename\core\exception(self::EXCEPTION_VIEW_CRUD_EDIT_PRIMARYKEYNOTSENT, \codename\core\exception::$ERRORLEVEL_WARNING, $primaryKey);
        }

        $this->getCrudinstance()->show($this->getRequest()->getData($primaryKey));
        return;
    }

    /**
     * Using the CRUD generator to generate the delete page
     * @return void
     * @access public
     */
    public function view_crud_delete () {
        $this->getResponse()->setData('context', 'crud');
        $primaryKey = $this->getModelinstance()->getPrimarykey();

        if(!$this->getRequest()->isDefined($primaryKey) || strlen($this->getRequest()->getData($primaryKey)) == 0) {
            throw new \codename\core\exception(self::EXCEPTION_VIEW_CRUD_DELETE_PRIMARYKEYNOTSENT, \codename\core\exception::$ERRORLEVEL_WARNING, $primaryKey);
        }

        // If confirmed, delete action
        if($this->getRequest()->isDefined('__confirm')) {
            $this->getModelinstance()->delete($this->getRequest()->getData($primaryKey));
            $this->getResponse()->setRedirect('', $this->getRequest()->getData('context'), 'crud_list');
            $this->getResponse()->doRedirect();
        }

        // Load stuff to show
        $this->getResponse()->setData('keyname', $primaryKey);
        $this->getResponse()->setData('keyvalue', $this->getRequest()->getData($primaryKey));
        $this->getResponse()->setData('modelObject', $this->getModelinstance()->load($this->getRequest()->getData($primaryKey)));
        return;
    }

    /**
     * Using the CRUD generator to generate the create page
     * @return void
     */
    public function view_crud_create() {
        $this->getResponse()->setData('context', 'crud');
        $this->getCrudinstance()->create();
        return;
    }

    /**
     * Using the CRUD generator to overwrite/edit multiple datasets
     * @return void
     */
    public function view_bulk_edit() {
        $this->getResponse()->setData('context', 'crud');
        $this->getCrudinstance()->bulkEdit();
        return;
    }

    /**
     * Using the CRUD generator to delete multiple datasets at once
     * @return void
     */
    public function view_bulk_delete() {
        $this->getResponse()->setData('context', 'crud');
        $this->getCrudinstance()->bulkDelete();
        return;
    }

    /**
     * [action_import description]
     * @return [type] [description]
     */
    public function action_import() {
      $this->getResponse()->setData('context', 'crud');
      if($this->getCrudinstance()->getConfig()->exists('import>_security>group')) {
        $group = $this->getCrudinstance()->getConfig()->get('import>_security>group');
        if(\codename\core\app::getAuth()->memberOf($group)) {
          // get import file
          $request = $this->getRequest();
          if($request instanceof \codename\core\request\filesInterface) {
            $importFileUpload = $request->getFiles()['crud_import_file'] ?? null;
            if($importFileUpload && $importFileUpload['tmp_name']) {
              $json = json_decode(file_get_contents($importFileUpload['tmp_name']), true);
              $this->getCrudinstance()->import($json);
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
     */
    public function action_export() {
      $this->getResponse()->setData('context', 'crud');

      if($this->getCrudinstance()->getConfig()->exists('export>_security>group')) {
        $group = $this->getCrudinstance()->getConfig()->get('export>_security>group');
        if(\codename\core\app::getAuth()->memberOf($group)) {

            // handle raw mode
            $rawMode = false;
            if($this->getRequest()->getData('export_mode') === 'raw') {
              if(!$this->getCrudinstance()->getConfig()->get('export>allowRaw')) {
                throw new exception('EXPORT_MODE_RAW_NOT_ALLOWED', exception::$ERRORLEVEL_ERROR);
              }

              // perform raw modifications
              $rawMode = true;
            }

            // selected export ids
            if($selectedIds = $this->getRequest()->getData('export_selected_id')) {
              $this->getCrudinstance()->getMyModel()->addDefaultFilter(
                $this->getCrudinstance()->getMyModel()->getPrimaryKey(),
                $selectedIds
              );
            }

            $this->getCrudinstance()->export($rawMode);

            // should be customizable: (e.g. excel, standard, ...) exports.
            $exportType = $this->getRequest()->getData('export_type');

            // check export types
            if(!in_array($exportType, $this->getCrudinstance()->getConfig()->get('export>allowedTypes'))) {
              throw new exception('EXPORT_TYPE_NOT_ALLOWED', exception::$ERRORLEVEL_ERROR, $exportType);
            }

            $exportClass = \codename\core\app::getInheritedClass('export_'.$exportType);
            $export = new $exportClass();

            if($export instanceof \codename\core\export\exportInterface) {

              if(!$rawMode) {
                foreach($this->getResponse()->getData('visibleFields') as $field) {
                  $export->addField(new \codename\core\value\text($field));
                }
              } else {
                if($this->getResponse()->getData('rows')[0] ?? false) {
                  foreach($this->getResponse()->getData('rows')[0] as $key => $irrelevantValue) {
                    $export->addField(new \codename\core\value\text($key));
                  }
                } else {
                  // error?
                }
              }

              foreach($this->getResponse()->getData('rows') as $row) {
                $export->addRow(new \codename\core\datacontainer($row));
              }

              // stupid.
              switch($exportType) {
                case 'json':
                  $fileExtension = 'json';
                  break;
                case 'csv_excel':
                  $fileExtension = 'csv';
                  break;
              }

              $filename = 'Export_' . $this->getCrudinstance()->getMyModel()->getIdentifier() . '_' . time() . '.' . $fileExtension;
              $exportedFile = '/tmp/' . $filename;

              $export->setFilename($exportedFile)->export();

              \codename\core\helper\file::downloadToClient($exportedFile, $filename);

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
      return;
    }

    /**
     * Returns the name of the requested model
     * @return string
     * @access public
     */
    public function getModelname() : string {
        if(is_null($this->modelName)) {
            return $this->getRequest()->getData('context');
        }
        return $this->modelName;
    }

    /**
     * Returns the app the requested model is located in
     * @return string
     * @access public
     */
    public function getModelapp() : string {
        if(is_null($this->modelApp)) {
            return \codename\core\app::getApp();
        }
        return $this->modelApp;
    }

    /**
     * Sets the app the requested model is located in
     * @param string $modelApp
     * @return void
     * @access public
     */
    public function setModelapp(string $modelApp) {
        $this->modelApp = $modelApp;
        return;
    }

    /**
     * Sets the name of the model that will be requested
     * @param string $modelName
     * @return void
     * @access public
     */
    public function setModelname(string $modelName) {
        $this->modelName = $modelName;
        return;
    }

    /**
     * Stores the model instance in this class
     * @param \codename\core\model $model
     * @return void
     * @access public
     */
    public function setModelinstance(\codename\core\model $model) {
        $this->model = $model;
        return;
    }

    /**
     * Returns the exact model instance that was requested
     * @return \codename\core\model
     * @access public
     */
    public function getModelinstance() : \codename\core\model {
        if(is_null($this->model)) {
            $this->setModelinstance($this->getModel($this->getModelname(), $this->getModelapp()));
        }
        return $this->model;
    }

    /**
     * Set the CRUD instance of this context
     * @param \codename\core\ui\crud $crud
     * @return void
     */
    protected function setCrudinstance(\codename\core\ui\crud $crud) {
        $this->crud = $crud;
        return;
    }

    /**
     * Return the CRUD instance of this context
     * @return \codename\core\ui\crud
     */
    public function getCrudinstance() : \codename\core\ui\crud {
        if(is_null($this->crud)) {
            throw new \codename\core\exception(self::EXCEPTION_GETCRUDINSTANCE_CRUDPROPERTYISNULL, \codename\core\exception::$ERRORLEVEL_WARNING, ($this->model->getPrimarykey() ?? null));
        }
        return $this->crud;
    }

}
