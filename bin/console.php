<?php

use ShyimStoreApi\Commands\SyncPackagistCommand;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$app = new \ShyimStoreApi\Application();

$console = new Application();
$console->add(new SyncPackagistCommand());
$console->run();