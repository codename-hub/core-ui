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

    <!-- <input class="icheck" id="icheck1" type="checkbox"> <label for="icheck1" class="">FB-Link vorhanden</label> -->
      <?php
$counter = 1;
foreach ($data['field_elements'] as $element) { ?>

        <input
          id="<?= $data['field_id'] ?>-<?= $counter ?>"
          name="<?= $data['field_name'] ?>[]"
          type="checkbox"
          class="icheck validate[<?= $data['field_validator'] ?>] form-control <?= $data['field_class'] ?>"
          title="<?= $element[$data['field_displayfield']]; ?>"
          value="<?= $element[$data['field_valuefield']] ?>"
          data-prompt-position="topLeft"
          <?php
    if ($data['field_value'] != null && !is_array($data['field_value']) && $element[$data['field_valuefield']] == $data['field_value']) {
        echo 'checked';
    }
    if (is_array($data['field_value']) && in_array($element[$data['field_valuefield']], $data['field_value'])) {
        echo 'checked';
    }
    ?>
          <?php
    if ($data['field_readonly']) {
        echo 'disabled="disabled"';
    } ?>
        />

        <label for="<?= $data['field_id'] ?>-<?= $counter ?>">
            <?= $element[$data['field_displayfield']]; ?>
        </label>
        <br />
          <?php
    $counter++;
} ?>

    <!-- OTHER FIELD -->

    <!--
      <div id="<?= $data['field_id'] ?>error" class="joCrudValidation"></div>
    -->
  </div>
</div>
