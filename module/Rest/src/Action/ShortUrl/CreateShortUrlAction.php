<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\CreateShortUrlData;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class CreateShortUrlAction extends AbstractCreateShortUrlAction
{
    protected const ROUTE_PATH = '/short-urls';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_POST];

    /**
     * @throws ValidationException
     */
    protected function buildShortUrlData(Request $request): CreateShortUrlData
    {
        $payload = (array) $request->getParsedBody();
        if (! isset($payload['longUrl'])) {
            throw ValidationException::fromArray([
                'longUrl' => 'A URL was not provided',
            ]);
        }

        $payload[ShortUrlMetaInputFilter::API_KEY] = AuthenticationMiddleware::apiKeyFromRequest($request)->toString();
        $meta = ShortUrlMeta::fromRawData($payload);

        return new CreateShortUrlData($payload['longUrl'], (array) ($payload['tags'] ?? []), $meta);
    }
}
