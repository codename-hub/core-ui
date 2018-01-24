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
            value="<?=!is_null($data['field_value']) ? date($data['field_valueformat'] ?? app::translate('DATETIME.FORMAT_DATE'), strtotime($data['field_value'])) : ''?>"
            type="text"
            class="validate[<?=$data['field_validator']?>] form-control <?=$data['field_class']?> datepickers"
            title="<?=$data['field_title']?>"
            data-datatype="<?=$data['field_validator']?>"
            data-placeholder="<?=$data['field_placeholder']?>"
            data-description="<?=$data['field_description']?>"
            data-prompt-position="topLeft"
            <?php if ($data['field_readonly']) { echo 'readonly'; } ?>
            <?php if ($data['field_readonly']) { echo 'disabled'; } ?>
        />
        <div id="<?=$data['field_id']?>error" class="joCrudValidation"></div>
    </div>
</div>


<script>
$('.datepickers').datepicker({
	format : '<?= $data['field_format'] ?? 'yyyy-mm-dd' ?>',
	language : 'de',
	autoUpdateInput : false
});
</script>
