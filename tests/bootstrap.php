<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (!file_exists(__DIR__ . '/support/helpers.php')) {
    mkdir(__DIR__ . '/support');
    copy(__DIR__ . '/../vendor/workerman/webman-framework/src/support/helpers.php', __DIR__ . '/support/helpers.php');
}
require_once __DIR__ . '/support/helpers.php';

require_once __DIR__ . '/../vendor/workerman/webman-framework/src/support/bootstrap.php';
