<?php namespace codename\core\ui;
app::requireAsset('requirejs', ['select2']);
app::requireAsset('requirecss', '/assets/select2/dist/css/select2.css');
?>

<div class="form-group">
    <label
        title="<?=$data['field_description']?>"
        for="<?=$data['field_id']?>"
        class="control-label col-lg-3">
        <?=$data['field_title']?><?php if($data['field_required']) {?> <abbr title="Dieses Feld muss angegeben werden">(*)</abbr><?php } ?>
    </label>
    <div class="col-lg-9">

        <?php if ($data['field_readonly'] && $data['field_required'] && !is_array($data['field_value'])) { ?>
          <input type="hidden" name="<?=$data['field_name']?>" value="<?=$data['field_value']?>">
        <?php }?>

        <select
            id="<?=$data['field_id']?>"
            name="<?=$data['field_name']?><?php if(isset($data['field_multiple']) && is_bool($data['field_multiple']) && $data['field_multiple']) { echo '[]'; } ?>"
            class="chzn-select validate[<?=$data['field_validator']?>] <?=$data['field_class']?>"
            title="<?=$data['field_title']?>"
            data-datatype="<?=$data['field_validator']?>"
            data-placeholder="<?=$data['field_placeholder']?>"
            data-description="<?=$data['field_description']?>"
            data-prompt-position="topLeft"
            <?php if (isset($data['field_onchange'])) { ?>onchange="<?=$data['field_onchange']?>"<?php } ?>
            <?php if ($data['field_readonly']) { echo 'disabled'; } ?>
            <?php if (isset($data['field_multiple']) && is_bool($data['field_multiple']) && $data['field_multiple']) { echo 'multiple'; } ?>
        >
            <?php if(!isset($data['field_multiple']) || ($data['field_multiple'] == false)) { ?>
              <option value="" <?php if(is_string($data['field_value']) && strlen($data['field_value']) == 0) { ?>selected<?php } ?>><?=app::getTranslate()->translate('CRUD.PLEASE_SELECT')?></option>
            <?php } ?>

            <?php
            $elements = $data['field_elements'];
            if(!is_string($elements) && is_callable($elements)) {
              $elements = $elements();
            }
            ?>
            <?php foreach($elements as $element) { ?>
                <option
                    value="<?=$element[$data['field_valuefield']]?>"
                    <?php
                        if(!is_array($data['field_value']) && $element[$data['field_valuefield']] == $data['field_value']) { echo 'selected'; }
                        if(is_array($data['field_value']) && in_array($element[$data['field_valuefield']], $data['field_value'])) {echo 'selected'; }
                    ?>
                ><?=eval('echo "' . $data['field_displayfield'] . '";');?></option>
            <?php } ?>
        </select>
        <div id="<?=$data['field_id']?>error" class="joCrudValidation"></div>

    </div>
</div>

<script>
require(['!select2'], function() {
  $('.chzn-select').select2();
});
</script>