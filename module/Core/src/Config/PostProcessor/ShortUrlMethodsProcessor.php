<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\PostProcessor;

use Fig\Http\Message\RequestMethodInterface;
use Mezzio\Router\Route;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Util\RedirectStatus;

use function array_values;
use function count;
use function Functional\partition;

use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;

class ShortUrlMethodsProcessor
{
    public function __invoke(array $config): array
    {
        [$redirectRoutes, $rest] = partition(
            $config['routes'] ?? [],
            static fn (array $route) => $route['name'] === RedirectAction::class,
        );
        if (count($redirectRoutes) === 0) {
            return $config;
        }

        [$redirectRoute] = array_values($redirectRoutes);
        $redirectStatus = RedirectStatus::tryFrom(
            $config['redirects']['redirect_status_code'] ?? 0,
        ) ?? DEFAULT_REDIRECT_STATUS_CODE;
        $redirectRoute['allowed_methods'] = $redirectStatus->isLegacyStatus()
            ? [RequestMethodInterface::METHOD_GET]
            : Route::HTTP_METHOD_ANY;

        $config['routes'] = [...$rest, $redirectRoute];
        return $config;
    }
}
