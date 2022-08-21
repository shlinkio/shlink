<?php

declare(strict_types=1);

use Mezzio\Application;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;

(static function (): void {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/../config/container.php';
    $app = $container->get(Application::class);
    $worker = new PSR7Worker(
        Worker::create(),
        $container->get(ServerRequestFactoryInterface::class),
        $container->get(StreamFactoryInterface::class),
        $container->get(UploadedFileFactoryInterface::class),
    );

    while ($req = $worker->waitRequest()) {
        try {
            $worker->respond($app->handle($req));
        } catch (Throwable $throwable) {
            $worker->getWorker()->error((string) $throwable);
        }
    }
})();
