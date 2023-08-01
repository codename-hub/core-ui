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
  <div class="col-lg-9" id="<?= $data['field_id'] ?>">

      <?php
$index = 0;
foreach ($data['field_elements'] as $element) { ?>
        <div>
          <input
            type="radio"
            id="<?= $data['field_name'] ?>___<?= $index ?>"
            name="<?= $data['field_name'] ?>"
            class="icheck <?= $data['field_class'] ?>"
            <?php
      if ($data['field_readonly']) {
          echo 'disabled';
      } ?>
            value="<?= $element[$data['field_valuefield']] ?>"
            <?php
      if (!is_array($data['field_value']) && $element[$data['field_valuefield']] == $data['field_value']) {
          echo 'checked';
      }
      if (is_array($data['field_value']) && in_array($element[$data['field_valuefield']], $data['field_value'])) {
          echo 'checked';
      }
    ?>>
          <label for="<?= $data['field_name'] ?>___<?= $index++ ?>">
              <?= $element[$data['field_displayfield']] ?>
          </label>
        </div>
          <?php
} ?>

    <div id="<?= $data['field_id'] ?>error" class="joCrudValidation"></div>

  </div>
</div>
