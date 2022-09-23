<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;

abstract class AbstractCreateShortUrlAction extends AbstractRestAction
{
    public function __construct(
        private readonly UrlShortenerInterface $urlShortener,
        private readonly DataTransformerInterface $transformer,
        protected readonly UrlShortenerOptions $urlShortenerOptions,
    ) {
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
    abstract protected function buildShortUrlData(Request $request): ShortUrlCreation;
}
