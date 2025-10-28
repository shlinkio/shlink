<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Pagerfanta\Pagerfanta;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Visit\Model\WithDomainVisitsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class TagVisitsAction extends AbstractListVisitsAction
{
    protected const string ROUTE_PATH = '/tags/{tag}/visits';

    protected function getVisitsPaginator(Request $request, ApiKey $apiKey): Pagerfanta
    {
        $params = WithDomainVisitsParams::fromRawData($request->getQueryParams());
        $tag = $request->getAttribute('tag', '');
        return $this->visitsHelper->visitsForTag($tag, $params, $apiKey);
    }
}
