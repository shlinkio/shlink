<?php
use Zend\Expressive\Application;

chdir(dirname(__DIR__));

/** @var Application $app */
$app = include 'config/app.php';
$app->run();
