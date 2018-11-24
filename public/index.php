<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Common\Exec\ExecutionContext;
use Zend\Expressive\Application;

/** @var ContainerInterface $container */
$container = include __DIR__ . '/../config/container.php';

putenv(sprintf('CURRENT_SHLINK_CONTEXT=%s', ExecutionContext::WEB));
$container->get(Application::class)->run();
