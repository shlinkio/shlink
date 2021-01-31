<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Model\ShortUrlEdit;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class EditShortUrlAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-urls/{shortCode}';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_PATCH, self::METHOD_PUT];

    private ShortUrlServiceInterface $shortUrlService;
    private ShortUrlDataTransformer $transformer;

    public function __construct(ShortUrlServiceInterface $shortUrlService, array $domainConfig)
    {
        $this->shortUrlService = $shortUrlService;
        $this->transformer = new ShortUrlDataTransformer($domainConfig);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $shortUrlEdit = ShortUrlEdit::fromRawData((array) $request->getParsedBody());
        $identifier = ShortUrlIdentifier::fromApiRequest($request);
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        $shortUrl = $this->shortUrlService->updateShortUrl($identifier, $shortUrlEdit, $apiKey);

        return new JsonResponse($this->transformer->transform($shortUrl));
    }
}
