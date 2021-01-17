<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Paginator\Util\PaginatorUtilsTrait;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class ListShortUrlsAction extends AbstractRestAction
{
    use PaginatorUtilsTrait;

    protected const ROUTE_PATH = '/short-urls';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    private ShortUrlServiceInterface $shortUrlService;
    private array $domainConfig;

    public function __construct(ShortUrlServiceInterface $shortUrlService, array $domainConfig)
    {
        $this->shortUrlService = $shortUrlService;
        $this->domainConfig = $domainConfig;
    }

    public function handle(Request $request): Response
    {
        $shortUrls = $this->shortUrlService->listShortUrls(
            ShortUrlsParams::fromRawData($request->getQueryParams()),
            AuthenticationMiddleware::apiKeyFromRequest($request),
        );
        return new JsonResponse(['shortUrls' => $this->serializePaginator($shortUrls, new ShortUrlDataTransformer(
            $this->domainConfig,
        ))]);
    }
}
