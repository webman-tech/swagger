<?php
/** @var array $css */
/** @var array $js */
/** @var string $title */
/** @var array $ui_config */

use WebmanTech\Swagger\Helper\ArrayHelper;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $title ?></title>
    <?php foreach ($css as $url): ?>
    <link rel="stylesheet" href="<?= $url ?>" />
    <?php endforeach; ?>
</head>
<body>
<div id="<?= ltrim((string) $ui_config['dom_id'], '#') ?>"></div>
<?php foreach ($js as $url): ?>
    <script src="<?= $url ?>" crossorigin></script>
<?php endforeach; ?>
<script>
    window.onload = () => {
        window.ui = SwaggerUIBundle(<?= ArrayHelper::formatUIParams($ui_config) ?>);
    };
</script>
</body>
</html>