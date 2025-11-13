<?php
/** @var array $css */
/** @var array $js */
/** @var string $title */
/** @var array $ui_config */

/** @var string $dto_generator_url */

use WebmanTech\Swagger\Helper\ArrayHelper;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title><?= $title ?></title>
    <?php foreach ($css as $url): ?>
        <link rel="stylesheet" href="<?= $url ?>"/>
    <?php endforeach; ?>
</head>
<body>
<?php if (!empty($dto_generator_url)): ?>
    <div style="display: flex; justify-content: flex-end; padding: 12px 20px 0 20px;">
        <a href="<?= $dto_generator_url ?>" target="_blank"
           style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border: 1px solid rgba(15,23,42,0.3); border-radius: 999px; color: #1f2937; text-decoration: none; font-size: 12px; font-weight: 600; opacity: 0.8; transition: opacity 0.2s ease;">
            DTO Generator
        </a>
    </div>
<?php endif; ?>
<div id="<?= ltrim((string)$ui_config['dom_id'], '#') ?>"></div>
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
