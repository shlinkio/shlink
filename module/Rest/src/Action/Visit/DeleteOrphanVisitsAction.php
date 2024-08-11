<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Visit\VisitsDeleterInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class DeleteOrphanVisitsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/visits/orphan';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_DELETE];

    public function __construct(private readonly VisitsDeleterInterface $visitsDeleter)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);
        $result = $this->visitsDeleter->deleteOrphanVisits($apiKey);

        return new JsonResponse($result->toArray('deletedVisits'));
    }
}
