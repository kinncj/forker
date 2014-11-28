#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';

use kinncj\Forker\Command\Fork;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new Fork);
$application->run();