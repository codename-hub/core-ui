<?php namespace codename\core\ui;
app::requireAsset('requirecss', [
  '/assets/bootstrap/dist/css/bootstrap.css',
  '/assets/core-ui-assets/css/coreadmin/stylesheets/application.css'
]);
app::requireAsset('requirejs', [
  'bootstrap', 'bootstrap-datepicker'
]);
?>
<?=app::getResponse()->getData('form')?>
