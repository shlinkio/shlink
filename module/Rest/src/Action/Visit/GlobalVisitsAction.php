<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class GlobalVisitsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/visits';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(private VisitsStatsHelperInterface $statsHelper)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        return new JsonResponse([
            'visits' => $this->statsHelper->getVisitsStats($apiKey),
        ]);
    }
}
