<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Pagerfanta\Pagerfanta;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlVisitsAction extends AbstractListVisitsAction
{
    protected const ROUTE_PATH = '/short-urls/{shortCode}/visits';

    protected function getVisitsPaginator(Request $request, VisitsParams $params, ApiKey $apiKey): Pagerfanta
    {
        $identifier = ShortUrlIdentifier::fromApiRequest($request);
        return $this->visitsHelper->visitsForShortUrl($identifier, $params, $apiKey);
    }
}
