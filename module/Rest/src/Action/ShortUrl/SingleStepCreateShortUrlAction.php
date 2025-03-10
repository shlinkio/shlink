<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class SingleStepCreateShortUrlAction extends AbstractCreateShortUrlAction
{
    protected const string ROUTE_PATH = '/short-urls/shorten';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    protected function buildShortUrlData(Request $request): ShortUrlCreation
    {
        $query = $request->getQueryParams();
        $longUrl = $query['longUrl'] ?? null;
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        return ShortUrlCreation::fromRawData([
            ShortUrlInputFilter::LONG_URL => $longUrl,
            ShortUrlInputFilter::API_KEY => $apiKey,
            // This will usually be null, unless this API key enforces one specific domain
            ShortUrlInputFilter::DOMAIN => $request->getAttribute(ShortUrlInputFilter::DOMAIN),
        ], $this->urlShortenerOptions);
    }
}
