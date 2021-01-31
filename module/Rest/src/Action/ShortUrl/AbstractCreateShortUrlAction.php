<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;

abstract class AbstractCreateShortUrlAction extends AbstractRestAction
{
    private UrlShortenerInterface $urlShortener;
    private ShortUrlDataTransformer $transformer;

    public function __construct(UrlShortenerInterface $urlShortener, array $domainConfig)
    {
        $this->urlShortener = $urlShortener;
        $this->transformer = new ShortUrlDataTransformer($domainConfig);
    }

    public function handle(Request $request): Response
    {
        $shortUrlMeta = $this->buildShortUrlData($request);
        $shortUrl = $this->urlShortener->shorten($shortUrlMeta);

        return new JsonResponse($this->transformer->transform($shortUrl));
    }

    /**
     * @throws ValidationException
     */
    abstract protected function buildShortUrlData(Request $request): ShortUrlMeta;
}
