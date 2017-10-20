<?php namespace codename\core\ui;

app::getResponse()->requireResource('js', '/assets/plugins/jquery.bsmodal/jquery.bsmodal.js');
app::getResponse()->requireResource('js', '/assets/plugins/jquery.bsmodal/jquery.bsmodal.init.js');

// data
$data_rows = app::getResponse()->getData('rows');
$data_fields = app::getResponse()->getData('visibleFields');

$display_availablefields = app::getResponse()->getData('availableFields');
$display_selectedfields = $data_fields;

// actions
$actions_bulk = app::getResponse()->getData('bulkActions');
$actions_element = app::getResponse()->getData('elementActions');
$actions_top = app::getResponse()->getData('topActions');
$actions_field = app::getResponse()->getData('fieldActions');

$model = app::getResponse()->getData('modelinstance');
$crud_filter_identifier = app::getResponse()->getData('crud_filter_identifier');

// filters
$filters_used =   app::getResponse()->getData('filters_used');
$filters_unused = app::getResponse()->getData('filters');

// enablers

$enable_actions_element = (count($actions_element) > 0);
$enable_actions_field = (count($actions_field) > 0);
$enable_actions_bulk = (count($actions_bulk) > 0);
$enable_filters = (count($filters_unused) > 0);

$enable_actions_top = (count($actions_top) > 0) || $enable_filters;

$enable_top_bar = ($enable_actions_bulk || $enable_filters || $enable_actions_top);
$enable_seach_bar = app::getResponse()->getData('enable_search_bar');
$enable_displayfieldselection = app::getResponse()->getData('enable_displayfieldselection');
$enable_export = app::getResponse()->getData('enable_export');
// pagination
$crud_pagination_pages = app::getResponse()->getData('crud_pagination_pages');
$crud_pagination_page  = app::getResponse()->getData('crud_pagination_page');
$crud_pagination_limit = app::getResponse()->getData('crud_pagination_limit');
$crud_pagination_count = app::getResponse()->getData('crud_pagination_count');
$crud_pagination_display=count($data_rows);

// basefilter
$url_baseurl = app::getResponse()->getData('url_baseurl');
?>
<?php if($enable_top_bar) { ?>
    <div class="row padded">
        <?php if($enable_actions_top) { ?>
            <div class="btn-group">
                <?php if(is_array($actions_top)) { ?>
                  <?php foreach($actions_top as $key => $value) { ?>
                    <?php if(isset($value['items']) && is_array($value['items'])) { ?>
                      <div class="btn-group pull-right">
                        <button class="btn btn-xs btn-success dropdown-toggle" data-toggle="dropdown"><?= app::getTranslate()->translate("BUTTON.BTN_" . $key)?> <span class="caret"></span></button>
                          <ul class="dropdown-menu">
                              <?php foreach($value['items'] as $itemKey => $itemValue) {
                                $requestParams = array(
                                  'context' => $itemValue['context'] ?? app::getRequest()->getData('context'),
                                  'view' => $itemValue['view'],
                                );

                                if(isset($itemValue['parameter'])) {
                                  if(is_array($itemValue['parameter'])) {
                                    foreach($itemValue['parameter'] as $param) {
                                      $requestParams[$param] = app::getRequest()->getData($param);
                                    }
                                  } else {
                                    $requestParams[$itemValue['parameter']] = app::getRequest()->getData($itemValue['parameter']);
                                  }
                                }

                                if(isset($itemValue['params'])) {
                                  $requestParams = array_merge($requestParams, $itemValue['params']);
                                }
                                ?>
                                <li><a href="/?<?= http_build_query($requestParams) ?>" class="<?=$itemValue['btnClass'] ?? ''?>"><i class="<?=$itemValue['icon']?>"></i> <?= isset($itemValue['translationkey']) ? app::getTranslate()->translate($itemValue['translationkey']) : app::getTranslate()->translate("BUTTON.BTN_" . $itemKey)?></a></li>
                              <?php } ?>
                          </ul>
                      </div>
                    <?php } else {

                      $requestParams = array(
                        'context' => $value['context'] ?? app::getRequest()->getData('context'),
                        'view' => $value['view'],
                      );

                      if(isset($value['parameter'])) {
                        if(is_array($value['parameter'])) {
                          foreach($value['parameter'] as $param) {
                            $requestParams[$param] = app::getRequest()->getData($param);
                          }
                        } else {
                          $requestParams[$value['parameter']] = app::getRequest()->getData($value['parameter']);
                        }
                      }

                      if(isset($value['params'])) {
                        $requestParams = array_merge($requestParams, $value['params']);
                      }
                      ?>
                      <a href="/?<?= http_build_query($requestParams) ?>" class="btn btn-xs <?=$value['btnClass']?>"><i class="<?=$value['icon']?>"></i> <?= isset($value['translationkey']) ? app::getTranslate()->translate($value['translationkey']) : app::getTranslate()->translate("BUTTON.BTN_" . $key)?></a>
                    <?php } ?>
                  <?php } ?>
                <?php } ?>
            <?php if($enable_filters) { ?>
                <div class="btn-group">
                    <button class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown"><i class="icon icon-filter"></i> <?=app::translate('BUTTON.BTN_FILTER')?> <span class="caret"></span></button>
                    <ul class="dropdown-menu">
                        <?php foreach($filters_unused as $field => $filter) { ?>
                          <?php if($field != '_search') { ?>
                            <li class="btnAdder" data-fieldname="<?=app::getTranslate()->translate('DATAFIELD.' . $field)?>" data-filterfield="<?=$field?>" data-multiple="<?= (isset($filter['multiple']) && $filter['multiple']) ? 'true' : 'false' ?>" <?php if(isset($filter['filtertype'])) { ?>data-filtertype="<?= $filter['filtertype'] ?>"<?php } ?> <?php if(isset($filter['filteroptions'])) { ?>data-filteroptions="<?=htmlspecialchars(json_encode($filter['filteroptions']))?>" <?php } ?>><a data-filterfield="<?=$field?>"><i class="icon icon-plus"></i> <?=app::getTranslate()->translate('DATAFIELD.' . $field)?></a></li>
                          <?php } ?>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>
            </div>
        <?php } ?>
        <?php if($enable_actions_bulk) { ?>
            <div class="btn-group pull-right">
                <button class="btn btn-xs btn-success dropdown-toggle" data-toggle="dropdown">Bulk actions <span class="caret"></span></button>
                <ul class="dropdown-menu">
                    <?php foreach($actions_bulk as $key => $value) { ?>
                        <li><a class="btnBulk" href="/?context=<?=$value['context']?>&view=<?=$value['view']?><?=(array_key_exists('action', $value) ? '&action=' . $value['action'] : '')?>"><i class="<?=$value['icon']?>"></i> <?= app::getTranslate()->translate("BUTTON.BTN_" . $key)?></a></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
    </div>
<?php } ?>

<form  method="GET">
  <input type="hidden" name="context" value="<?=app::getRequest()->getData('context')?>" />
  <input type="hidden" name="view" value="<?=app::getRequest()->getData('view')?>" />
  <input type="hidden" name="prev_page" value="<?=$crud_pagination_page?>" />

  <?php if($enable_filters || $enable_displayfieldselection) { ?>

        <div class="row padded">

            <!-- select displayed columns/fields -->
            <?php if($enable_displayfieldselection) { ?>
              <div id="crudFieldSelection">
                <?php if(is_array($display_availablefields) && is_array($display_selectedfields)) {
                  // to preserve order:
                  $orderedDisplayFields = array_unique(array_merge($display_selectedfields, $display_availablefields));
                  ?>
                  <div class="col-md-2 alert-info">
                    <select class="chzn-select" name="display_selectedfields[]" multiple="multiple">
                      <?php foreach($orderedDisplayFields as $field) { ?>
                        <option value="<?=$field?>" <?=in_array($field, $display_selectedfields) ? 'selected="selected"' : ''?>><?=app::getTranslate()->translate('DATAFIELD.' . $field)?></option>
                      <?php } ?>
                    </select>
                  </div>
                <?php } else {
                  if(!is_array($display_availablefields)) {
                    echo("<br>not an array: display_availablefields");
                  }
                  if(!is_array($display_selectedfields)) {
                    echo("<br>not an array: display_selectedfields");
                  }
                } ?>
              </div>
            <?php } ?>

            <?php if($enable_filters) { ?>
              <div id="crudFilterList">
                  <?php if($enable_seach_bar) { ?>
                      <div class="col-md-2 alert-info">
                        <?=app::translate('BUTTON.BTN_SEARCH')?>:<br />
          	            <input style="width:100px;" type="text" placeholder="<?=app::translate('BUTTON.BTN_SEARCH')?>" name="<?=$crud_filter_identifier?>[search]" value="<?=app::getRequest()->getData($crud_filter_identifier.'>search')?>" />
                        <button type="button" class="close" onclick="$('input[name=\'<?=$crud_filter_identifier?>[search]\']').val('');">×</button>
                      </div>
                  <?php } ?>
                  <?php if(is_array($filters_used)) { ?>
                      <?php foreach($filters_used as $field => $filter) { ?>
                      	<?php if($field == 'search') { continue; }?>
                          <div class="col-md-2 alert-info">
                              <?=app::getTranslate()->translate('DATAFIELD.' . $field)?>:<br />
                              <?php if(isset($filters_unused[$field]['filteroptions'])){?>
                                <select class="chzn-select" style="width:100px;" name="<?=$crud_filter_identifier?>[<?=$field?>]<?= $filters_unused[$field]['multiple'] ? '[]' : '' ?>" <?= $filters_unused[$field]['multiple'] ? 'multiple="multiple"' : '' ?>>
                                  <?php foreach($filters_unused[$field]['filteroptions'] as $key => $value) { ?>
                                    <option value="<?=$key?>"
                                      <?php if(is_array(app::getRequest()->getData($crud_filter_identifier)[$field])) {
                                        if(in_array($key, app::getRequest()->getData($crud_filter_identifier)[$field])) {
                                          echo('selected="selected"');
                                        }
                                      } else {
                                        if(app::getRequest()->getData($crud_filter_identifier)[$field] == $key) {
                                          echo('selected="selected"');
                                        }
                                      } ?>>
                                      <?=$value?>
                                    </option>
                                  <?php } ?>
                                </select>
                              <?php } elseif ( isset($filters_unused[$field]['filtertype']) && $filters_unused[$field]['filtertype'] == 'date_range' ) { ?>
                                <input class="datepicker-db" style="width:100px;" type="text" name="<?=$crud_filter_identifier?>[<?=$field?>][0]" placeholder="<?=app::getTranslate()->translate('DATAFIELD.' . $field)?>" value="<?=app::getRequest()->getData($crud_filter_identifier)[$field][0]?>" />
                                <input class="datepicker-db" style="width:100px;" type="text" name="<?=$crud_filter_identifier?>[<?=$field?>][1]" placeholder="<?=app::getTranslate()->translate('DATAFIELD.' . $field)?>" value="<?=app::getRequest()->getData($crud_filter_identifier)[$field][1]?>" />
                              <?php } else { ?>
                                <input style="width:100px;" type="text" name="<?=$crud_filter_identifier?>[<?=$field?>]" placeholder="<?=app::getTranslate()->translate('DATAFIELD.' . $field)?>" value="<?=app::getRequest()->getData($crud_filter_identifier)[$field]?>" />
                              <?php } ?>
                              <button type="button" class="close" data-dismiss="alert">×</button>
                          </div>
                      <?php } ?>
                  <?php } ?>
              </div>
            <?php } ?>

        </div>
        <input type="submit" class="btn btn-xs btn-success" value="<?=app::translate('BUTTON.BTN_FILTER')?>" />
        <?php if($enable_export === true) { ?>
          <button type="submit" class="btn btn-xs btn-warning pull-right" name="action" value="export"><i class="icon-download-alt"></i><?=app::translate('BUTTON.BTN_EXPORT')?></button>
        <?php } ?>

<?php } ?>

  <div class="box">
      <table class="table table-normal responsive" aria-describedby="DataTables_Table_0_info">
          <thead>
              <tr>
                  <?php if($enable_actions_bulk) { ?>
                      <td><input type="checkbox" name="check_all"/></td>
                  <?php } ?>
                  <?php if($enable_actions_element) { ?>
                      <td></td>
                  <?php } ?>
                  <?php foreach ($data_fields as $field) { ?>
                      <td title="<?=app::getTranslate()->translate('DATAFIELD.' . $field . '_DESCRIPTION')?>"><?=app::getTranslate()->translate('DATAFIELD.' . $field)?></td>
                  <?php } ?>
              </tr>
          </thead>
          <tbody>
              <?php foreach ($data_rows as $row) { ?>
                  <tr <?=$row['__modifier'] ?? ''?>>

                    <?php if($enable_actions_bulk) { ?>
                      <td>
                        <?php if($row[$model->getPrimarykey()] != null) { ?>
                          <input type="checkbox" class="chkbxPrimary" data-id="<?=$row[$model->getPrimarykey()]?>"/>
                        <?php } ?>
                      </td>
                    <?php } ?>

                    <?php if($enable_actions_element) { ?>
                        <td style="width:25px;">
                          <?php if($row[$model->getPrimarykey()] != null) { ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                	<i class="icon icon-cogs"></i> <?=app::getTranslate()->translate("BUTTON.BTN_OPTIONS")?> <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach($actions_element as $key => $value) {
                                        if(!array_key_exists('context', $value)) {
                                            $value['context'] = app::getRequest()->getData('context');
                                        }
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
                                        } ?>
                                        <li>

                                          <?php
                                            $requestParams = array(
                                              'context' => $value['context'] ?? app::getRequest()->getData('context'),
                                              'view' => $value['view'],
                                              app::getResponse()->getData('primarykey') => $row[app::getResponse()->getData('primarykey')]
                                            );

                                            if(isset($value['parameter'])) {
                                              if(is_array($value['parameter'])) {
                                                foreach($value['parameter'] as $param) {
                                                  $requestParams[$param] = $row[$param] ?? null;
                                                }
                                              } else {
                                                $requestParams[$value['parameter']] = $row[$value['parameter']] ?? null;
                                              }
                                            }

                                            if(isset($value['params'])) {
                                              $requestParams = array_merge($requestParams, $value['params']);
                                            }
                                            ?>

                                            <a data-type="<?=$value['type'] ?? ''?>" data-id="<?=$row[app::getResponse()->getData('primarykey')]?>" href="/?<?=http_build_query($requestParams)?>" title="<?=app::getTranslate()->translate("BUTTON.BTN_" . $key)?>">
                                                <i class="<?=$value['icon']?>"></i> <?=app::getTranslate()->translate("BUTTON.BTN_" . $key)?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                          <?php } ?>
                        </td>
                      <?php } ?>

                      <?php foreach ($data_fields as $field) { ?>
                          <td>
                              <?php if($enable_actions_field && array_key_exists($field, $actions_field)) {
                                  $fieldData = array_key_exists($field.'_FORMATTED', $row) ? $row[$field] : $row[$field.'_FORMATTED'] ; ?>

                                  <?php

                                    $items = array();

                                    foreach($actions_field[$field] as $item) {

                                      if(isset($item['type']) && $item['type'] == 'menu') {
                                        $menuItems = array();

                                        foreach($item['items'] as $menuItemData) {

                                          $params = array(
                                            'context' => $menuItemData['context'] ?? app::getResponse()->getData('context'), // @TODO: fallback to current context if not defined
                                            'view' => $menuItemData['view'] ?? app::getResponse()->getData('view'),
                                            $field => $fieldData
                                          );
                                          if(isset($menuItemData['params']) && is_array($menuItemData['params'])) {
                                            $params = array_merge($params, $menuItemData['params']);
                                          }

                                          $menuItems[] = \jocoon\joBase\frontend\link::create(
                  													$params,
                  													app::getTranslate()->translate("BUTTON.BTN_" . ($menuItemData['title'] ?? '')), //$menuItemData['icon'],
                  													app::getTranslate()->translate("BUTTON.BTN_" . ($menuItemData['title'] ?? '')),
                  													array($menuItemData['classes'] ?? ''),
                  													array(
                  														'data-type' => ($menuItemData['type'] ?? ''),
                  														'data-id' => $fieldData,
                  													)
                  												);
                                        }

                                        $items[] = \jocoon\joBase\frontend\buttonMenu::create(
                													$menuItems,
                													$item['icon'] ?? '',
                													app::getTranslate()->translate("BUTTON.BTN_" . ($item['title'] ?? '')),
                													array($item['classes'] ?? ''),
                													array(
                														'data-type' => $item['type'] ?? '',
                														'data-id' => $fieldData,
                													)
                												);

                                      } else {

                                        $params = array(
                                          'context' => $item['context'] ?? app::getResponse()->getData('context'), // @TODO: fallback to current context if not defined
                                          'view' => $item['view']?? app::getResponse()->getData('view'),
                                          $field => $fieldData
                                        );
                                        if(isset($item['params']) && is_array($item['params'])) {
                                          $params = array_merge($params, $item['params']);
                                        }

                                        $items[] = \jocoon\joBase\frontend\buttonLink::create(
                													$params,
                													$item['icon'] ?? '',
                													app::getTranslate()->translate("BUTTON.BTN_" . ($item['title'] ?? '')),
                													array($item['classes'] ?? ''),
                													array(
                														'data-type' => $item['type'] ?? '',
                														'data-id' => $fieldData,
                													)
                												);
                                      }
                                    }
                                    ?>

                                    <?=\jocoon\joBase\frontend\buttonGroup::getHtml($items)?>

                              <?php } ?>
                              <?= array_key_exists($field.'_FORMATTED', $row) ? $row[$field.'_FORMATTED'] : $row[$field] ?>
                          </td>
                      <?php } ?>
                  </tr>
              <?php } ?>
          </tbody>
          <tfoot>
              <tr>
                  <td colspan="<?=count($data_fields) + 2?>" style="text-align:center">
                      <small>
                          Zeige <?=$crud_pagination_display?> von <?=$crud_pagination_count?>
                      </small>
                      <br />
                      <?php if ($crud_pagination_pages > 1) { ?>
                          <?php for($page = 1;$page <= $crud_pagination_pages; $page++) { ?>
                              <button type="submit" name="page" value="<?=$page?>" class="btn btn-xs <?php if($page == $crud_pagination_page) { ?> btn-success <?php } else {?> btn-default <?php } ?>"><?=$page?></button>
                          <?php } ?>
                      <?php } ?>

                      <?php
                        // show a pagination item count selection
                        $paginationOptions = array(0,10,25,50,100,200,500);
                        $paginationOptions[] = $crud_pagination_limit; // add the current option to the array
                        $paginationOptions = array_unique($paginationOptions); // filter out duplicate
                        sort($paginationOptions); // and sort by value, ASC
                      ?>
                      <br><br>
                      <label>
                        <?= app::getTranslate()->translate('CRUD.SELECT_PAGINATION_LIMIT') ?>
                        <select name="crud_pagination_limit" onchange="submit();">
                          <?php foreach($paginationOptions as $value) { ?>
                            <option value="<?=$value?>" <?=$crud_pagination_limit==$value ? 'selected' : ''?>><?= ($value==0) ? app::getTranslate()->translate('CRUD.SELECT_ALL') : $value ?></option>
                          <?php } ?>
                        </select>
                      </label>
                  </td>
              </tr>
          </tfoot>
      </table>
  </div>

</form>


<script>

function initPlugins() {
  $('.datepicker-db').datepicker({
    format : 'yyyy-mm-dd',
    language : 'de',
    autoUpdateInput : false
  });
  $('select.chzn-select').select2();
}
$(document).bind('ready', function() {

    initPlugins();

    $('body').delegate('.btnBulk', 'click', function(event) {
        var keys = '';
        var delimiter = '';
        event.preventDefault();
        $( 'input:checkbox[class=chkbxPrimary]:checked' ).each(function( index ) {
            if($(this).prop('checked')) {
              keys = keys + delimiter + $(this).data('id');
              delimiter  = ',';
            }
        });
        $(this).attr('href', $(this).attr('href') + '&<?=$model->getPrimarykey()?>=' + keys);
        setModalClickAction(this);
    });
    $(document).delegate('.btnAdder', 'click', function() {
        var filterfield = $(this).data('filterfield');
        var fieldname = $(this).data('fieldname');
        var filteroptions = $(this).data('filteroptions');
        var filtertype = $(this).data('filtertype');
        var multiple = $(this).data('multiple');
        html = '';
        if(typeof filteroptions != 'undefined') {
          var options = '';
          $.each(filteroptions, function(key,value) {
            options += '<option value="' + key + '">' + value + '</option>'
          });

          var multipleAttr = '';
          var fieldArray = '';
          if(multiple) {
            multipleAttr = 'multiple="multiple"';
            fieldArray = '[]';
          }
          html = '<select class="chzn-select" id="crudFilter' + filterfield + '" style="width:100px;" name="<?=$crud_filter_identifier?>[' + filterfield + ']'+fieldArray+'" ' + multipleAttr + '>' + options + '</select>'
        } else {
          if(typeof filtertype != 'undefined' && (filtertype == 'date_range')) {
            html = '<input id="crudFilter' + filterfield + '_0" class="datepicker-db" style="width:100px;" type="text" name="<?=$crud_filter_identifier?>[' + filterfield + '][0]" placeholder="' + fieldname + '" value="" />';
            html += '<input id="crudFilter' + filterfield + '_1" class="datepicker-db" style="width:100px;" type="text" name="<?=$crud_filter_identifier?>[' + filterfield + '][1]" placeholder="' + fieldname + '" value="" />';
          } else {
            html = '<input id="crudFilter' + filterfield + '" style="width:100px;" type="text" name="<?=$crud_filter_identifier?>[' + filterfield + ']" placeholder="' + fieldname + '" value="" />';
          }
        }
        $('#crudFilterList').append('<div class="col-md-2 alert-info">' + fieldname + ':<br />' + html + '<button type="button" class="close" data-dismiss="alert">×</button></div>');
        $('#crudFilter' + filterfield + '.chzn-select').select2();
        initPlugins();
        $(this).remove();
        $('#crudFilter' + filterfield).focus();
    });
});

</script>
