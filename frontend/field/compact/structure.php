<?php namespace codename\core\ui;
app::getResponse()->requireResource('js', '/assets/plugins/jsoneditor/jsoneditor.min.js');
app::getResponse()->requireResource('css', '/assets/plugins/jsoneditor/jsoneditor.min.css');
app::getResponse()->requireResource('js', '/assets/plugins/jsoneditor/jsoneditor.min.js');
?>
<?php
if(!is_array($data['field_value'])) {
}
?>

<div class="form-group">
  <label
    title="<?=$data['field_description']?>"
    for="<?=$data['field_id']?>"
    class="control-label col-lg-3">
    <?=$data['field_title']?><?php if($data['field_required']) {?> <abbr title="Dieses Feld muss angegeben werden">(*)</abbr><?php } ?>
  </label>
  <div class="col-lg-9">
    <input type="hidden" id="<?=$data['field_id']?>-value" name="<?=$data['field_name']?>" value="<?=htmlentities(json_encode($data['field_value']))?>" />

    <div id="<?=$data['field_id']?>error" class="joCrudValidation"></div>

    <div id="<?=$data['field_id']?>" class="validate[<?=$data['field_validator']?>] <?=$data['field_class']?>" style="height:400px;"></div>
  </div>
</div>

<script>
    // create the editor
    var container = document.getElementById("<?=$data['field_id']?>");

    <?php if($data['field_readonly'] === true || ($data['field_datatype'] != 'text_json' && $data['field_datatype'] != 'structure')) {  // todo: use strpos for derived datatypes ?>

      var options = {
        mode: 'text'
        <?php if($data['field_readonly'] != true) { ?>
        ,onChange: function() {
          var text = editor.getText();
          console.log("jsoneditor::onChange getText: " + text);
          document.getElementById("<?=$data['field_id']?>-value").value = text;
        }
        <?php } ?>
      };

    <?php } else { ?>

    var options = {
      mode: 'tree',
      modes: ['code', 'form', 'text', 'tree', 'view'], // allowed modes
      onError: function (err) {
        alert(err.toString());
      },
      onChange: function() {
        var json = JSON.stringify(editor.get());
        console.log("jsoneditor::onChange " + json);
        document.getElementById("<?=$data['field_id']?>-value").value = json;
      },
      onModeChange: function (newMode, oldMode) {
        console.log('jsoneditor: Mode switched from', oldMode, 'to', newMode);
      }
    };

    <?php }?>

    // mode = viewer for READONLY!
    // modes: ['code', 'form', 'text', 'tree', 'view']

    var editor = new JSONEditor(container, options);

    // set json
    var json = JSON.parse(document.getElementById("<?=$data['field_id']?>-value").value);

    editor.set(json);

    // get json. onchange?
    // var json = editor.get();
</script>
