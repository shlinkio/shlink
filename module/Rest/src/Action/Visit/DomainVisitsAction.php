<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Pagerfanta\Pagerfanta;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DomainVisitsAction extends AbstractListVisitsAction
{
    protected const ROUTE_PATH = '/domains/{domain}/visits';

    public function __construct(
        VisitsStatsHelperInterface $visitsHelper,
        private readonly UrlShortenerOptions $urlShortenerOptions,
    ) {
        parent::__construct($visitsHelper);
    }

    protected function getVisitsPaginator(Request $request, VisitsParams $params, ApiKey $apiKey): Pagerfanta
    {
        $domain = $this->resolveDomainParam($request);
        return $this->visitsHelper->visitsForDomain($domain, $params, $apiKey);
    }

    private function resolveDomainParam(Request $request): string
    {
        $domainParam = $request->getAttribute('domain', '');
        if ($domainParam === $this->urlShortenerOptions->defaultDomain) {
            return Domain::DEFAULT_AUTHORITY;
        }

        return $domainParam;
    }
}
