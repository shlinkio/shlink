<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\CreateShortUrlData;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Zend\Diactoros\Uri;

class CreateShortUrlAction extends AbstractCreateShortUrlAction
{
    protected const ROUTE_PATH = '/short-urls';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_POST];

    /**
     * @throws ValidationException
     */
    protected function buildShortUrlData(Request $request): CreateShortUrlData
    {
        $postData = (array) $request->getParsedBody();
        if (! isset($postData['longUrl'])) {
            throw ValidationException::fromArray([
                'longUrl' => 'A URL was not provided',
            ]);
        }

        $meta = ShortUrlMeta::createFromParams(
            $postData['validSince'] ?? null,
            $postData['validUntil'] ?? null,
            $postData['customSlug'] ?? null,
            $postData['maxVisits'] ?? null,
            $postData['findIfExists'] ?? null,
            $postData['domain'] ?? null,
        );

        return new CreateShortUrlData(new Uri($postData['longUrl']), (array) ($postData['tags'] ?? []), $meta);
    }
}
