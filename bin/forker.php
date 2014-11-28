#!/usr/bin/env php
<?php
$forkerDirectory = dirname(__DIR__);

if (!is_file($forkerDirectory . '/composer.lock')) {
    echo 'You must run composer install before use forker' . PHP_EOL;
    exit;
}

require_once $forkerDirectory . '/vendor/autoload.php';

use kinncj\Forker\Command\Fork;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new Fork);
$application->run();
