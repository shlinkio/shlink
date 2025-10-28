<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Pagerfanta\Pagerfanta;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Visit\Model\WithDomainVisitsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class NonOrphanVisitsAction extends AbstractListVisitsAction
{
    protected const string ROUTE_PATH = '/visits/non-orphan';

    protected function getVisitsPaginator(ServerRequestInterface $request, ApiKey $apiKey): Pagerfanta
    {
        $params = WithDomainVisitsParams::fromRawData($request->getQueryParams());
        return $this->visitsHelper->nonOrphanVisits($params, $apiKey);
    }
}
