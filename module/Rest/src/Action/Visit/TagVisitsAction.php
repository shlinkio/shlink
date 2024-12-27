<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Pagerfanta\Pagerfanta;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class TagVisitsAction extends AbstractListVisitsAction
{
    protected const string ROUTE_PATH = '/tags/{tag}/visits';

    protected function getVisitsPaginator(Request $request, VisitsParams $params, ApiKey $apiKey): Pagerfanta
    {
        $tag = $request->getAttribute('tag', '');
        return $this->visitsHelper->visitsForTag($tag, $params, $apiKey);
    }
}
