<?php

namespace codename\core\ui;

use codename\core\response\http;

$response = app::getResponse();
if (!($response instanceof http)) {
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <title><?= $response->getData('title') ?></title>
      <?php
      foreach ($response->getResources('js') as $js) { ?>
        <script src="<?= $js ?>" type="text/javascript"></script>
          <?php
      } ?>
      <?php
      foreach ($response->getResources('script') as $script) { ?>
        <script type="text/javascript"><?= $script ?></script>
          <?php
      } ?>
      <?php
      foreach ($response->getResources('css') as $css) { ?>
        <link rel="stylesheet" type="text/css" href="<?= $css ?>" />
          <?php
      } ?>
      <?php
      foreach ($response->getResources('style') as $style) { ?>
        <style>
          <?= $style ?>
        </style>
          <?php
      } ?>
      <?php
      foreach ($response->getResources('head') as $head) { ?>
          <?= $head ?>
          <?php
      } ?>
  </head>
  <body>
      <?= $response->getData('content') ?>
  </body>
</html>
