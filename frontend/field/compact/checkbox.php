<?php namespace codename\core\ui;?>
<div class="form-group">
    <label
        title="<?=$data['field_description']?>"
        for="<?=$data['field_id']?>"
        class="control-label col-lg-3">
        <?=$data['field_title']?><?php if($data['field_required']) {?> <abbr title="Dieses Feld muss angegeben werden">(*)</abbr><?php } ?>
    </label>
    <div class="col-lg-9">
        <input
            id="<?=$data['field_id']?>"
            name="<?=$data['field_name']?>"
            type="checkbox"
            class="validate[<?=$data['field_validator']?>] form-control <?=$data['field_class']?>"
            title="<?=$data['field_title']?>"
            data-prompt-position="topLeft"
            <?php if (is_bool($data['field_value']) && $data['field_value']) { echo 'checked'; } ?>
            <?php if ($data['field_readonly']) { echo 'readonly="readonly"'; } ?>
            <?php if ($data['field_readonly']) { echo 'disabled="disabled"'; } ?>
        />
        <div id="<?=$data['field_id']?>error" class="joCrudValidation"></div>
    </div>
</div>
