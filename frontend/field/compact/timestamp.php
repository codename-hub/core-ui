<?php

namespace codename\core\ui;

use codename\core\response\http;

$data = $data ?? [];

$response = app::getResponse();
if (!($response instanceof http)) {
    exit();
}

$response->requireResource('css', 'assets/plugins/datetimepicker/jquery.datetimepicker.min.css');
$response->requireResource('js', 'assets/plugins/datetimepicker/jquery.datetimepicker.full.js');
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
  <div class="col-lg-9" id="<?= $data['field_id'] ?>_datetimepicker_parent">
    <input
      id="<?= $data['field_id'] ?>"
      name="<?= $data['field_name'] ?>"
      value<?php
if (!is_null($data['field_value'])) { ?>="<?= date($data['field_formatinternal'] ?? 'Y-m-d H:i:s', strtotime($data['field_value'])) ?>"<?php
} ?>
      type="datetime-local"
      class="validate[<?= $data['field_validator'] ?>] form-control <?= $data['field_class'] ?> datetimepickers"
      title="<?= $data['field_title'] ?>"
      data-datatype="<?= $data['field_validator'] ?>"
      data-placeholder="<?= $data['field_placeholder'] ?>"
      data-description="<?= $data['field_description'] ?>"
      data-prompt-position="topLeft"
      <?php
  if ($data['field_readonly']) {
      echo 'readonly';
  } ?>
    />
      <?php
  if (!$data['field_readonly'] && (!isset($data['field_allowblank']) || $data['field_allowblank'])) { ?>
        <button
          class="icon-remove-sign" type="button" onclick="$('#<?= $data['field_id'] ?>').val('');" style="position: absolute;
            top: 0;
            bottom: 0;
            right: 15px;
            width: 26px;
            color: #c00;"
        ></button>
          <?php
  } ?>
    <div id="<?= $data['field_id'] ?>error" class="joCrudValidation"></div>
  </div>
</div>

<style>
  .jo-datetimepicker:not(.xdsoft_inline) {
    margin-top: 26px;
    margin-left: 15px;
    top: 0 !important;
    left: 0 !important;
  }
</style>

<script>

  // global datetimepicker setting for locale
  $.datetimepicker.setLocale('de');
  $.datetimepicker.setDateFormatter({
    parseDate: function(date, format) {
      let d = moment(date, format);
      return d.isValid() ? d.toDate() : false;
    },
    formatDate: function(date, format) {
      return moment(date).format(format);
    },
  });

  // $(document).ready(function() {
  $('#<?=$data['field_id']?>').datetimepicker({
    lazyInit: true,
    className: 'jo-datetimepicker',
    parentID: $('#<?=$data['field_id']?>').parent(), // $('#<?=$data['field_id']?>_datetimepicker_parent'),
    inline: <?= (isset($data['field_inline']) && $data['field_inline']) ? 'true' : 'false' ?>,
    timepicker: <?= (isset($data['field_timepicker']) && !$data['field_timepicker']) ? 'false' : 'true' ?>,
    format: '<?= $data['field_format'] ?? 'YYYY-MM-DD HH:mm:ss' ?>',
    formatDate: '<?= $data['field_formatdate'] ?? 'YYYY-MM-DD' ?>',
    formatTime: '<?= $data['field_formattime'] ?? 'HH:mm:ss' ?>',
    defaultDate: new Date(),
    validateOnBlur: true,
    scrollMonth: false,
    mask: '<?= $data['field_mask'] ?? '9999-99-99 29:59:59' ?>',
    // defaultSelect: true,
    allowBlank: <?= isset($data['field_allowblank']) && !$data['field_allowblank'] ? 'true' : 'false' ?>, // only if not required? - filtered by validator
    todayButton: true
      <?= isset($data['field_dayofweekstart']) ? 'dayOfWeekStart: ' . $data['field_dayofweekstart'] . ',' : '' ?>
      <?= isset($data['field_disabledweekdays']) ? 'disabledWeekDays: ' . json_encode($data['field_disabledweekdays']) . ',' : '' ?>
      <?= isset($data['field_allowdates']) ? 'allowDates: ' . json_encode($data['field_allowdates']) . ',' : '' ?>
      <?= isset($data['field_disableddates']) ? 'disabledDates: ' . json_encode($data['field_disableddates']) . ',' : '' ?>
      <?= isset($data['field_timestep']) ? 'step: ' . $data['field_timestep'] . ',' : '' ?>
      <?= isset($data['field_mindate']) ? 'minDate: new Date(' . $data['field_mindate'] . '),' : '' ?>
      <?= isset($data['field_maxdate']) ? 'maxDate: new Date(' . $data['field_maxdate'] . '),' : '' ?>
      <?= isset($data['field_mintime']) ? 'minTime: \'' . $data['field_mintime'] . '\',' : '' ?>
      <?= isset($data['field_maxtime']) ? 'maxTime: \'' . $data['field_maxtime'] . '\',' : '' ?>
      <?= isset($data['field_minhour']) ? 'minHour: ' . $data['field_minhour'] . ',' : '' ?>
      <?= isset($data['field_maxhour']) ? 'maxHour: ' . $data['field_maxhour'] . ',' : '' ?>,

  });
  // });
</script>
