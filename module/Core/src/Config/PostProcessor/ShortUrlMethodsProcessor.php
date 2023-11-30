<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\PostProcessor;

use Fig\Http\Message\RequestMethodInterface;
use Mezzio\Router\Route;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Util\RedirectStatus;

use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;

/**
 * Sets the appropriate allowed methods on the redirect route, based on the redirect status code that was configured.
 *  * For "legacy" status codes (301 and 302) the redirect URL will work only on GET method.
 *  * For other status codes (307 and 308) the redirect URL will work on any method.
 */
class ShortUrlMethodsProcessor
{
    public function __invoke(array $config): array
    {
        $allRoutes = $config['routes'] ?? [];
        $redirectRoute = null;
        $rest = [];

        // Get default route from routes array
        foreach ($allRoutes as $route) {
            if ($route['name'] === RedirectAction::class) {
                $redirectRoute ??= $route;
            } else {
                $rest[] = $route;
            }
        }

        if ($redirectRoute === null) {
            return $config;
        }

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
