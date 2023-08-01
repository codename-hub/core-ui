<?php

namespace codename\core\ui;

$data = $data ?? [];
?>
<div class="form-group">
  <label
    title="<?= $data['field_description'] ?>"
    for="<?= $data['field_id'] ?>"
    class="control-label col-lg-3"
  >
      <?= $data['field_title'] ?><?php
if ($data['field_required']) { ?>
        <abbr title="Dieses Feld muss angegeben werden">(*)</abbr><?php
} ?>
  </label>
  <div class="col-lg-9">
    <div id="<?= $data['field_id'] ?>">
      <input
        id="<?= $data['field_id'] ?>-input"
        name="<?= $data['field_name'] ?><?php
if ($data['field_multiple']) {
    echo '[]';
} ?>"
        value="<?= $data['field_value'] ?>"
        type="file"
        class="validate[<?= $data['field_validator'] ?>] form-control <?= $data['field_class'] ?>"
        title="<?= $data['field_title'] ?>"
        data-datatype="<?= $data['field_validator'] ?>"
        data-placeholder="<?= $data['field_placeholder'] ?>"
        data-description="<?= $data['field_description'] ?>"
        data-prompt-position="topLeft"
        <?php
if ($data['field_readonly']) {
    echo 'readonly';
} ?>
        <?php
if ($data['field_multiple']) {
    echo 'multiple';
} ?>
      />
    </div>
    <div id="<?= $data['field_id'] ?>error" class="joCrudValidation"></div>
  </div>
</div>
