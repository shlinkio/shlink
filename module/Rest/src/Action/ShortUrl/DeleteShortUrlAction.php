<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class DeleteShortUrlAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/short-urls/{shortCode}';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_DELETE];

    public function __construct(private readonly DeleteShortUrlServiceInterface $deleteShortUrlService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = ShortUrlIdentifier::fromApiRequest($request);
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        $this->deleteShortUrlService->deleteByShortCode($identifier, apiKey: $apiKey);

        return new EmptyResponse();
    }
}
