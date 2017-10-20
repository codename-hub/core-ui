<?php namespace codename\core\ui;?>
<div class="form-group">
    <label class="col-lg-3"></label>
    <div class="col-lg-9">
        <button
          name="<?=$data['field_name']?>"
          value="<?=$data['field_value']?>"
          onclick="submit();";
          class="btn btn-lg pull-right <?=$data['field_class']?>"
          title="<?=$data['field_title']?>"
        ><?=$data['field_title']?></button>
    </div>
</div>
