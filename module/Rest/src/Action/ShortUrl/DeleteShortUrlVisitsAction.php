<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlVisitsDeleterInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class DeleteShortUrlVisitsAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/short-urls/{shortCode}/visits';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_DELETE];

    public function __construct(private readonly ShortUrlVisitsDeleterInterface $deleter)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = ShortUrlIdentifier::fromApiRequest($request);
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        $result = $this->deleter->deleteShortUrlVisits($identifier, $apiKey);

        return new JsonResponse($result->toArray('deletedVisits'));
    }
}
