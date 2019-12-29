<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Zend\Diactoros\Response\JsonResponse;

class ResolveShortUrlAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-urls/{shortCode}';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    private UrlShortenerInterface $urlShortener;
    private array $domainConfig;

    public function __construct(
        UrlShortenerInterface $urlShortener,
        array $domainConfig,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($logger);
        $this->urlShortener = $urlShortener;
        $this->domainConfig = $domainConfig;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws InvalidArgumentException
     */
    public function handle(Request $request): Response
    {
        $shortCode = $request->getAttribute('shortCode');
        $domain = $request->getQueryParams()['domain'] ?? null;
        $transformer = new ShortUrlDataTransformer($this->domainConfig);

        $url = $this->urlShortener->shortCodeToUrl($shortCode, $domain);
        return new JsonResponse($transformer->transform($url));
    }
}
