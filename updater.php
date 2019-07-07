<?php declare(strict_types = 1);

use Externals\EmailSynchronizer;
use Stratify\Framework\Application;

/** @var Application $application */
$application = require __DIR__ . '/res/bootstrap.php';

$synchronizer = $application->getContainer()->get(EmailSynchronizer::class);

lambda(function () use ($synchronizer) {
    $synchronizer->synchronize();
});
