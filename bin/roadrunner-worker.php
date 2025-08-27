<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Mezzio\Application;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Common\Middleware\RequestIdMiddleware;
use Shlinkio\Shlink\EventDispatcher\RoadRunner\RoadRunnerTaskConsumerToListener;
use Spiral\RoadRunner\Http\PSR7Worker;

use function gc_collect_cycles;
use function Shlinkio\Shlink\Config\env;

(static function (): void {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/../config/container.php';
    $rrMode = env('RR_MODE');
    $gcCollectCycles = env('GC_COLLECT_CYCLES', default: false);

    if ($rrMode === 'http') {
        // This was spin-up as a web worker
        $app = $container->get(Application::class);
        $worker = $container->get(PSR7Worker::class);

        while ($req = $worker->waitRequest()) {
            try {
                $worker->respond($app->handle($req));
            } catch (Throwable $e) {
                $worker->getWorker()->error((string) $e);
            } finally {
                if ($gcCollectCycles) {
                    gc_collect_cycles();
                }
            }
        }
    } else {
        $requestIdMiddleware = $container->get(RequestIdMiddleware::class);
        $container->get(RoadRunnerTaskConsumerToListener::class)->listenForTasks(
            fn (string $requestId) => $requestIdMiddleware->setCurrentRequestId($requestId),
        );
    }
})();
