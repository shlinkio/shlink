<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Pagerfanta\Pagerfanta;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class NonOrphanVisitsAction extends AbstractListVisitsAction
{
    protected const ROUTE_PATH = '/visits/non-orphan';

    protected function getVisitsPaginator(
        ServerRequestInterface $request,
        VisitsParams $params,
        ApiKey $apiKey,
    ): Pagerfanta {
        return $this->visitsHelper->nonOrphanVisits($params, $apiKey);
    }
}
