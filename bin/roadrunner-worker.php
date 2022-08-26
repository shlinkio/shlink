<?php

declare(strict_types=1);

use Mezzio\Application;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Shlinkio\Shlink\EventDispatcher\RoadRunner\RoadRunnerTaskConsumerToListener;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;

(static function (): void {
    $rrMode = getenv('RR_MODE');
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/../config/container.php';

    if ($rrMode === 'http') {
        // This was spin-up as a web worker
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
            } catch (Throwable $e) {
                $worker->getWorker()->error((string) $e);
            }
        }
    } else {
        $container->get(RoadRunnerTaskConsumerToListener::class)->listenForTasks();
    }
})();
