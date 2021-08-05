<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtilsTrait;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;

class OrphanVisitsAction extends AbstractRestAction
{
    use PagerfantaUtilsTrait;

    protected const ROUTE_PATH = '/visits/orphan';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(
        private VisitsStatsHelperInterface $visitsHelper,
        private DataTransformerInterface $orphanVisitTransformer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = VisitsParams::fromRawData($request->getQueryParams());
        $visits = $this->visitsHelper->orphanVisits($params);

        return new JsonResponse([
            'visits' => $this->serializePaginator($visits, $this->orphanVisitTransformer),
        ]);
    }
}
