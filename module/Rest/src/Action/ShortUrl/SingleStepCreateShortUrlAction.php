<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class SingleStepCreateShortUrlAction extends AbstractCreateShortUrlAction
{
    protected const ROUTE_PATH = '/short-urls/shorten';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    protected function buildShortUrlData(Request $request): ShortUrlMeta
    {
        $query = $request->getQueryParams();
        $longUrl = $query['longUrl'] ?? null;
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        return ShortUrlMeta::fromRawData([
            ShortUrlInputFilter::LONG_URL => $longUrl,
            ShortUrlInputFilter::API_KEY => $apiKey,
            // This will usually be null, unless this API key enforces one specific domain
            ShortUrlInputFilter::DOMAIN => $request->getAttribute(ShortUrlInputFilter::DOMAIN),
        ]);
    }
}
