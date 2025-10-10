<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mezzio\Application;
use Psr\Container\ContainerInterface;

use function frankenphp_handle_request;
use function gc_collect_cycles;

(static function (): void {
    /** @var ContainerInterface $container */
    $container = include __DIR__ . '/../config/container.php';
    $app = $container->get(Application::class);
    $responseEmitter = $container->get(EmitterInterface::class);
    $handler = static function () use ($app, $responseEmitter): void {
        $response = $app->handle(ServerRequestFactory::fromGlobals());
        $responseEmitter->emit($response);
    };

    $maxRequests = (int) ($_SERVER['MAX_REQUESTS'] ?? 0);
    for ($nbRequests = 0; !$maxRequests || $nbRequests < $maxRequests; ++$nbRequests) {
        $keepRunning = frankenphp_handle_request($handler);

        // Call the garbage collector to reduce the chances of it being triggered in the middle of a page generation
        gc_collect_cycles();

        if (! $keepRunning) {
            break;
        }
    }
})();
