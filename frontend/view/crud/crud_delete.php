<?php namespace codename\core\ui; ?>
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header">
                <div class="title"><?=app::translate('CRUD.DELETE_HEADER_CONFIRM')?></div>
            </div>
            <div class="box-content padded">
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <?=app::translate('CRUD.DELETE_TEXT_REALLY')?>
                    <br /><i><?=app::translate('CRUD.DELETE_TEXT_INHERITANCE')?></i>
                </div>
                <a class="btn btn-success" href="/?context=<?=app::getRequest()->getData('context')?>&view=crud_list"><i class="icon-ok"></i> <?=app::translate('CRUD.DELETE_BTN_ABORT')?></a>
                <a class="btn btn-danger pull-right" id="btnConfirm" href="/?context=<?=app::getRequest()->getData('context')?>&view=<?=app::getRequest()->getData('view')?>&<?=app::getResponse()->getData('keyname')?>=<?=app::getResponse()->getData('keyvalue')?>&__confirm=1"><i class="icon-trash"></i> <?=app::translate('CRUD.DELETE_BTN_PROCEED')?></a>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).on('ready', function(){
        $('#btnConfirm').focus();
    });
</script>
