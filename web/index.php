<?php
declare(strict_types = 1);

use Stratify\Framework\Application;

// Serve static files when running with PHP's built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']))) {
    return false;
}

/** @var Application $application */
$application = require __DIR__ . '/../res/bootstrap.php';

$application->http()->run();
