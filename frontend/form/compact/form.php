<?php namespace codename\core\ui;
app::getResponse()->requireResource('js', '/assets/plugins/jquery.loadinganim/jquery.loadinganim.js');
app::getResponse()->requireResource('css', '/assets/plugins/jquery.loadinganim/jquery.loadinganim.css');
?>
<div id="core_ui_form_result_<?=$data->config['form_id']?>">
</div>

<div id="core_ui_form_container_<?=$data->config['form_id']?>" class="hcForm">
    <form enctype="multipart/form-data" data-ajax="true" accept-charset="UTF-8" id="<?=$data->config['form_id']?>" action="<?=$data->config['form_action']?>" method="<?=$data->config['form_method']?>" class="separate-sections validatable form-horizontal">
        <?php
        if(property_exists($data, 'fieldsets')) {
            foreach ($data->fieldsets as $fieldset) {
                echo $fieldset->output();
            }
        }
        foreach ($data->fields as $field) {
            echo $field->output();
        }
        ?>
    </form>
</div>

<?php if(isset($data->config['form_text_requiredfields']) && $data->config['form_text_requiredfields'] != null) { ?>
<div class="pull-right">
    <label><i><?= $data->config['form_text_requiredfields'] ?></i></label>
</div>
<?php } ?>
<div style="clear:both;"></div>

<script type="text/javascript">
    $(document).ready(function () {

        // Default response handling, if enabled.
        $(document).on('form_container_<?=$data->config['form_id']?>_default', function(event, data, callbackData) {
           $(callbackData.form_result).empty().append(  data.content );
        });

        //
        // Default Ajax handler
        //
        $('#core_ui_form_container_<?=$data->config['form_id']?> form').on('form_ajax', function(event, url, formData) {
          $.ajax({
              url: url,
              type: 'POST',
              data: formData,
              cache: false,
              contentType: false,
              dataType: "json",
              processData: false,
              success: function(data){
                $('#core_ui_form_container_<?=$data->config['form_id']?> form').trigger('form_ajax_success', [data]);
              },
              error: function(data) {
                $('#core_ui_form_container_<?=$data->config['form_id']?> form').trigger('form_ajax_error', [data]);
              }
          });
        });

        //
        // Ajax Success Handling
        //
        $('#core_ui_form_container_<?=$data->config['form_id']?> form').on('form_ajax_success', function(event, data) {
          var callbackData = {
            form_id: '<?=$data->config['form_id']?>',
            form_container: $('#core_ui_form_container_<?=$data->config['form_id']?>'),
            form_result: $('#core_ui_form_result_<?=$data->config['form_id']?>')
          };
          <?php if(isset($data->config['form_js_submitted_callback'])) { ?>
          // definitive submitted callback
          $(document).trigger('<?=$data->config['form_js_submitted_callback']?>', [data, callbackData]);
          <?php } ?>
          <?php if(!isset($data->config['form_js_default_callback']) || $data->config['form_js_default_callback'] !== false) { ?>
          // default callback
          $(document).trigger('form_container_<?=$data->config['form_id']?>_default', [data, callbackData]);
          <?php } ?>
          <?php if(isset($data->config['form_js_success_callback'])) { ?>
          // success callback
          if(typeof data.form_success != 'undefined') {
            if(data.form_success == true) {
              $(document).trigger('<?=$data->config['form_js_success_callback']?>', [data, callbackData]);
            }
          }
          <?php } ?>
        });

        //
        // Ajax Error Handling
        //
        $('#core_ui_form_container_<?=$data->config['form_id']?> form').on('form_ajax_error', function(event, data) {
          var callbackData = {
            form_id: '<?=$data->config['form_id']?>',
            form_container: $('#core_ui_form_container_<?=$data->config['form_id']?>'),
            form_result: $('#core_ui_form_result_<?=$data->config['form_id']?>')
          };
          <?php if(isset($data->config['form_js_submitted_callback'])) { ?>
          // definitive submitted callback
          $(document).trigger('<?=$data->config['form_js_submitted_callback']?>', [data, callbackData]);
          <?php } ?>

          <?php if(isset($data->config['form_js_error_callback'])) { ?>
          // error callback
          $(document).trigger('<?=$data->config['form_js_error_callback']?>', [data, callbackData]);
          <?php } ?>
          // show errormessage
          alert('Es ist ein Fehler aufgetreten.');
        });



        $('#core_ui_form_container_<?=$data->config['form_id']?>').off('submit');
        $('#core_ui_form_container_<?=$data->config['form_id']?>').bind('submit', function() {

            // trim fields for those notorious copy-paste addicts ;-)
            $("input").each(function() {
              if($(this).attr('type') != 'file') {
                $(this).val($(this).val().trim());
              }
            });

            var url = '<?=$data->config['form_action']?><?php if(strpos($data->config['form_action'], '?') !== false) { ?>&template=json<?php } else {  ?>?template=json<?php } ?>';

            var formData = new FormData($('#core_ui_form_container_<?=$data->config['form_id']?> form')[0]);

            $(this).find('input').removeClass('MFormError');
            $(this).find('select').removeClass('MFormError');
            $(this).find('textarea').removeClass('MFormError');
            $(this).find('.joCrudValidation').empty();

            <?php if(isset($data->config['form_js_beforesubmit_callback'])) { ?>
            // pre-submit callback
            var callbackData = {
              form_id: '<?=$data->config['form_id']?>',
              form_container: $('#core_ui_form_container_<?=$data->config['form_id']?>'),
              form_result: $('#core_ui_form_result_<?=$data->config['form_id']?>')
            };
            $(document).trigger('<?=$data->config['form_js_beforesubmit_callback']?>', [formData, callbackData]);
            <?php } ?>

            $('#core_ui_form_container_<?=$data->config['form_id']?> form').trigger('form_ajax', [url, formData]);

            return false;
        });
    });
</script>

<?php
  // additional scripts paths defined
  if(isset($data->config['form_scripts'])) {
    if(isset($data->config['form_scripts']['path'])) {
      foreach($data->config['form_scripts']['path'] as $scriptPath) {
        echo(app::parseFile(app::getInheritedPath($scriptPath), $data));
      }
    }
    if(isset($data->config['form_scripts']['code'])) {
      foreach($data->config['form_scripts']['code'] as $code) {
        echo($code);
      }
    }
  }
?>
