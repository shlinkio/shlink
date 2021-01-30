<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Model\CreateShortUrlData;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class SingleStepCreateShortUrlAction extends AbstractCreateShortUrlAction
{
    protected const ROUTE_PATH = '/short-urls/shorten';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    protected function buildShortUrlData(Request $request): CreateShortUrlData
    {
        $query = $request->getQueryParams();
        $longUrl = $query['longUrl'] ?? null;
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        return new CreateShortUrlData([], ShortUrlMeta::fromRawData([
            ShortUrlMetaInputFilter::LONG_URL => $longUrl,
            ShortUrlMetaInputFilter::API_KEY => $apiKey,
            // This will usually be null, unless this API key enforces one specific domain
            ShortUrlMetaInputFilter::DOMAIN => $request->getAttribute(ShortUrlMetaInputFilter::DOMAIN),
        ]));
    }
}
