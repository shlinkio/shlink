<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlEdit;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

/** @deprecated */
class EditShortUrlTagsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-urls/{shortCode}/tags';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_PUT];

    private ShortUrlServiceInterface $shortUrlService;

    public function __construct(ShortUrlServiceInterface $shortUrlService)
    {
        $this->shortUrlService = $shortUrlService;
    }

    public function handle(Request $request): Response
    {
        $bodyParams = $request->getParsedBody();

        if (! isset($bodyParams['tags'])) {
            throw ValidationException::fromArray([
                'tags' => 'List of tags has to be provided',
            ]);
        }
        ['tags' => $tags] = $bodyParams;
        $identifier = ShortUrlIdentifier::fromApiRequest($request);
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        $shortUrl = $this->shortUrlService->updateShortUrl($identifier, ShortUrlEdit::fromRawData([
            ShortUrlInputFilter::TAGS => $tags,
        ]), $apiKey);
        return new JsonResponse(['tags' => $shortUrl->getTags()->toArray()]);
    }
}
