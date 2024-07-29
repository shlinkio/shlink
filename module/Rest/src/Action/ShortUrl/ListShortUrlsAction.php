<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtils;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlListServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformerInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class ListShortUrlsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-urls';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(
        private readonly ShortUrlListServiceInterface $shortUrlService,
        private readonly ShortUrlDataTransformerInterface $transformer,
    ) {
    }

    public function handle(Request $request): Response
    {
        $shortUrls = $this->shortUrlService->listShortUrls(
            ShortUrlsParams::fromRawData($request->getQueryParams()),
            AuthenticationMiddleware::apiKeyFromRequest($request),
        );
        return new JsonResponse([
            'shortUrls' => PagerfantaUtils::serializePaginator($shortUrls, $this->transformer->transform(...)),
        ]);
    }
}
