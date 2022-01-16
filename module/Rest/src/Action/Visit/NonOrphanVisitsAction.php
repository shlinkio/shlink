<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtilsTrait;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class NonOrphanVisitsAction extends AbstractRestAction
{
    use PagerfantaUtilsTrait;

    protected const ROUTE_PATH = '/visits/non-orphan';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(private VisitsStatsHelperInterface $visitsHelper)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = VisitsParams::fromRawData($request->getQueryParams());
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);
        $visits = $this->visitsHelper->nonOrphanVisits($params, $apiKey);

        return new JsonResponse([
            'visits' => $this->serializePaginator($visits),
        ]);
    }
}
