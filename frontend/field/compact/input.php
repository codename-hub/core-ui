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
            value="<?=$data['field_value']?>"
            type="text"
            class="validate[<?=$data['field_validator']?>] form-control <?=$data['field_class']?>"
            title="<?=$data['field_title']?>"
            data-datatype="<?=$data['field_validator']?>"
            data-placeholder="<?=$data['field_placeholder']?>"
            data-description="<?=$data['field_description']?>"
            data-prompt-position="topLeft"
            <?php if ($data['field_readonly']) { echo 'readonly'; } ?>
        />
        <div id="<?=$data['field_id']?>error" class="joCrudValidation"></div>
    </div>
</div>
