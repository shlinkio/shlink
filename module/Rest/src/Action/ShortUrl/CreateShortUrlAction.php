<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class CreateShortUrlAction extends AbstractCreateShortUrlAction
{
    protected const string ROUTE_PATH = '/short-urls';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_POST];

    /**
     * @throws ValidationException
     */
    protected function buildShortUrlData(Request $request): ShortUrlCreation
    {
        $payload = (array) $request->getParsedBody();
        $payload[ShortUrlInputFilter::API_KEY] = AuthenticationMiddleware::apiKeyFromRequest($request);

        return ShortUrlCreation::fromRawData($payload, $this->urlShortenerOptions);
    }
}
