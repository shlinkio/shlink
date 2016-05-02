<?php
use Interop\Container\ContainerInterface;
use Zend\Expressive\Application;

/** @var ContainerInterface $container */
$container = include __DIR__ . '/../config/container.php';
/** @var Application $app */
$app = $container->get(Application::class);
$app->run();
