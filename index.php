<?php /** @noinspection PhpUnhandledExceptionInspection */

require_once __DIR__ . '/vendor/autoload.php';

$app = new Lighter\Application(file_get_contents(__DIR__ . '/logo.txt'), '2.1.1');
$app->configure();
$app->run();
