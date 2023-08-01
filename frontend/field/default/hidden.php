<?php

namespace codename\core\ui;

$data = $data ?? [];
?>
<input
  id="<?= $data['field_id'] ?>"
  name="<?= $data['field_name'] ?>"
  value="<?= $data['field_value'] ?>"
  type="hidden"
/>
