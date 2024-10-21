<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtils;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class DomainVisitsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/domains/{domain}/visits';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(
        private readonly VisitsStatsHelperInterface $visitsHelper,
        private readonly UrlShortenerOptions $urlShortenerOptions,
    ) {
    }

    public function handle(Request $request): Response
    {
        $domain = $this->resolveDomainParam($request);
        $params = VisitsParams::fromRawData($request->getQueryParams());
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);
        $visits = $this->visitsHelper->visitsForDomain($domain, $params, $apiKey);

        return new JsonResponse(['visits' => PagerfantaUtils::serializePaginator($visits)]);
    }

    private function resolveDomainParam(Request $request): string
    {
        $domainParam = $request->getAttribute('domain', '');
        if ($domainParam === $this->urlShortenerOptions->defaultDomain()) {
            return 'DEFAULT';
        }

        return $domainParam;
    }
}
