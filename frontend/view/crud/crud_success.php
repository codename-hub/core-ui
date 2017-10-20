<?php namespace codename\core\ui; ?>
<div class="alert alert-success alert-dismissible" role="alert" id="crudAlert">
    <button type="button" class="close" data-dismiss="alert">
    	<span aria-hidden="true">&times;</span>
    	<span class="sr-only">Close</span>
    </button>
    <?=app::translate("CRUD.". app::getResponse()->getData('CRUD_FEEDBACK'))?>
</div>
<script>
    $(document).ready(function() {
        $('html, body').animate({
        	scrollTop: $('#crudAlert').offset().top
        });
        window.setTimeout('$(\'#crudAlert\').fadeOut(300, function(){ $(this).remove();});', 2500);

        <?php if(app::getRequest()->getData('crud_success_prevent_default') != true) { ?>
        window.setTimeout('window.history.back();', 3000);
        <?php } ?>
    });
</script>
