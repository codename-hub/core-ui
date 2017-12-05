<?php namespace codename\core\ui; ?>
<div class="alert alert-warning alert-dismissible" role="alert" id="crudAlert">
    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
    <?=app::translate('CRUD.SAVE_ERROR')?>
</div>
<script>
require(['domReady!', 'jquery'], function () {
  $('html, body').animate({
  	scrollTop: $('#crudAlert').offset().top
  });
  <?php foreach (app::getResponse()->getData('errors') as $error) { ?>
      $("#<?=$error['__IDENTIFIER']?>").addClass('MFormError');
      $("#<?=$error['__IDENTIFIER']?>error").html('<?php echo (isset($error['__DETAILS'][0]['__TEXT']) ? $error['__DETAILS'][0]['__TEXT'] : (isset($error['__DETAILS'][0]['__CODE']) ? app::translate($error['__DETAILS'][0]['__CODE']) : app::translate($error['__CODE'])))?>');
  <?php } ?>
});
</script>
