<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;

abstract class AbstractCreateShortUrlAction extends AbstractRestAction
{
    public function __construct(
        private UrlShortenerInterface $urlShortener,
        private DataTransformerInterface $transformer,
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
    abstract protected function buildShortUrlData(Request $request): ShortUrlMeta;
}
