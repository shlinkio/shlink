<?php

declare(strict_types=1);

use Mezzio\Application;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\EventDispatcher\RoadRunner\RoadRunnerTaskConsumerToListener;
use Spiral\RoadRunner\Http\PSR7Worker;

use function Shlinkio\Shlink\Config\env;

(static function (): void {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/../config/container.php';
    $rrMode = env('RR_MODE');

    if ($rrMode === 'http') {
        // This was spin-up as a web worker
        $app = $container->get(Application::class);
        $worker = $container->get(PSR7Worker::class);

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
