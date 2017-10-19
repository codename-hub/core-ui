<?php namespace codename\core\ui; ?>
<div id="core_ui_form_result_<?=$data->config['form_id']?>">
</div>

<div id="core_ui_form_container_<?=$data->config['form_id']?>" class="hcForm">
    <form enctype="multipart/form-data" accept-charset="UTF-8" id="<?=$data->config['form_id']?>" action="<?=$data->config['form_action']?>" method="<?=$data->config['form_method']?>" class="separate-sections validatable form-horizontal">
        <?php
        if(property_exists($data, 'fieldsets')) {
            foreach ($data->fieldsets as $fieldset) {
                echo $fieldset->output();
            }
        }
        foreach ($data->fields as $field) {
            echo $field->output();
        }
        ?>
    </form>
</div>

<?php if(isset($data->config['form_text_requiredfields']) && $data->config['form_text_requiredfields'] != null) { ?>
<div class="pull-right">
    <label><i><?= $data->config['form_text_requiredfields'] ?></i></label>
</div>
<?php } ?>
<div style="clear:both;"></div>

<?php
  // additional scripts paths defined
  if(isset($data->config['form_scripts'])) {
    if(isset($data->config['form_scripts']['path'])) {
      foreach($data->config['form_scripts']['path'] as $scriptPath) {
        echo(app::parseFile(app::getInheritedPath($scriptPath), $data));
      }
    }
    if(isset($data->config['form_scripts']['code'])) {
      foreach($data->config['form_scripts']['code'] as $code) {
        echo($code);
      }
    }
  }
?>
