<?php namespace codename\core\ui; ?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= app::getResponse()->getData('title') ?></title>
        <?php foreach(app::getResponse()->getResources('js') as $js) { ?>
            <script src="<?=$js?>" type="text/javascript"></script>
        <?php } ?>
        <?php foreach(app::getResponse()->getResources('script') as $script) { ?>
            <script type="text/javascript"><?= $script ?></script>
        <?php } ?>
        <?php foreach(app::getResponse()->getResources('css') as $css) { ?>
            <link rel="stylesheet" type="text/css" href="<?=$css?>" />
        <?php } ?>
        <?php foreach(app::getResponse()->getResources('style') as $style) { ?>
            <style>
            <?= $style ?>
            </style>
        <?php } ?>
        <?php foreach(app::getResponse()->getResources('head') as $head) { ?>
          <?= $head ?>
        <?php } ?>
    </head>
    <body>
        <?= app::getResponse()->getData('content') ?>
    </body>
</html>
