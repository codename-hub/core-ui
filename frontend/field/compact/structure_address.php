<?php namespace codename\core\ui;?>
<?php
if(!is_array($data['field_value'])) {
    $data['field_value'] = array();
}
if(!array_key_exists('country_id', $data['field_value'])) { $data['field_value']['country_id'] = null; }
if(!array_key_exists('postalcode', $data['field_value'])) { $data['field_value']['postalcode'] = null; }
if(!array_key_exists('city', $data['field_value'])) { $data['field_value']['city'] = null; }
if(!array_key_exists('street', $data['field_value'])) { $data['field_value']['street'] = null; }
if(!array_key_exists('number', $data['field_value'])) { $data['field_value']['number'] = null; }
?>
<input type="hidden" name="<?=$data['field_name']?>_" value="1" />
<input type="hidden" name="<?=$data['field_name']?>__COUNTRY_ID" value="1" />
<?=(new \jocoon\joBase\field(array('field_type' => 'input','field_name' => $data['field_id'] . '__POSTALCODE','field_title' => app::getTranslate()->translate('DATAFIELD.POSTALCODE'),'field_required' => true, 'field_readonly' => $data['field_readonly'], 'field_value' => $data['field_value']['postalcode'])))->output();?>
<?=(new \jocoon\joBase\field(array('field_type' => 'input','field_name' => $data['field_id'] . '__CITY','field_title' => app::getTranslate()->translate('DATAFIELD.CITY'),'field_required' => true, 'field_readonly' => $data['field_readonly'], 'field_value' => $data['field_value']['city'])))->output();?>
<?=(new \jocoon\joBase\field(array('field_type' => 'input','field_name' => $data['field_id'] . '__STREET','field_title' => app::getTranslate()->translate('DATAFIELD.STREET'),'field_required' => true, 'field_readonly' => $data['field_readonly'], 'field_value' => $data['field_value']['street'])))->output();?>
<?=(new \jocoon\joBase\field(array('field_type' => 'input','field_name' => $data['field_id'] . '__NUMBER','field_title' => app::getTranslate()->translate('DATAFIELD.NUMBER'),'field_required' => true,'field_readonly' => $data['field_readonly'], 'field_value' => $data['field_value']['number'])))->output();?>
