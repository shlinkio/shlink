<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class ResolveShortUrlAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-urls/{shortCode}';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    private ShortUrlResolverInterface $urlResolver;
    private ShortUrlDataTransformer $transformer;

    public function __construct(ShortUrlResolverInterface $urlResolver, array $domainConfig)
    {
        $this->urlResolver = $urlResolver;
        $this->transformer = new ShortUrlDataTransformer($domainConfig);
    }

    public function handle(Request $request): Response
    {
        $url = $this->urlResolver->resolveShortUrl(
            ShortUrlIdentifier::fromApiRequest($request),
            AuthenticationMiddleware::apiKeyFromRequest($request),
        );

        return new JsonResponse($this->transformer->transform($url));
    }
}
