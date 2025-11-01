<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Pagerfanta\Pagerfanta;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class OrphanVisitsAction extends AbstractListVisitsAction
{
    protected const string ROUTE_PATH = '/visits/orphan';

    protected function getVisitsPaginator(ServerRequestInterface $request, ApiKey $apiKey): Pagerfanta
    {
        $orphanParams = OrphanVisitsParams::fromRawData($request->getQueryParams());
        return $this->visitsHelper->orphanVisits($orphanParams, $apiKey);
    }
}
