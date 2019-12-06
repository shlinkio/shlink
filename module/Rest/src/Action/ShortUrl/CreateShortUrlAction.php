<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\CreateShortUrlData;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Zend\Diactoros\Uri;

class CreateShortUrlAction extends AbstractCreateShortUrlAction
{
    protected const ROUTE_PATH = '/short-urls';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_POST];

    /**
     * @param Request $request
     * @return CreateShortUrlData
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    protected function buildShortUrlData(Request $request): CreateShortUrlData
    {
        $postData = (array) $request->getParsedBody();
        if (! isset($postData['longUrl'])) {
            throw new InvalidArgumentException('A URL was not provided');
        }

        try {
            $meta = ShortUrlMeta::createFromParams(
                $postData['validSince'] ?? null,
                $postData['validUntil'] ?? null,
                $postData['customSlug'] ?? null,
                $postData['maxVisits'] ?? null,
                $postData['findIfExists'] ?? null,
                $postData['domain'] ?? null
            );

            return new CreateShortUrlData(new Uri($postData['longUrl']), (array) ($postData['tags'] ?? []), $meta);
        } catch (ValidationException $e) {
            throw new InvalidArgumentException('Provided meta data is not valid', -1, $e);
        }
    }
}
