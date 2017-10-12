<?php
declare(strict_types=1);

use Interop\Container\ContainerInterface;
use Zend\Expressive\Application;

/** @var ContainerInterface $container */
$container = include __DIR__ . '/../config/container.php';
$app = $container->get(Application::class)->run();
