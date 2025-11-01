<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Pagerfanta\Pagerfanta;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtils;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

abstract class AbstractListVisitsAction extends AbstractRestAction
{
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(protected readonly VisitsStatsHelperInterface $visitsHelper)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);
        $visits = $this->getVisitsPaginator($request, $apiKey);

        return new JsonResponse(['visits' => PagerfantaUtils::serializePaginator($visits)]);
    }

    /**
     * @return Pagerfanta<Visit>
     */
    abstract protected function getVisitsPaginator(ServerRequestInterface $request, ApiKey $apiKey): Pagerfanta;
}
