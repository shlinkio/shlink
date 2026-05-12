<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use CuyZ\Valinor\Mapper\TreeMapper;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformerInterface;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;

abstract class AbstractCreateShortUrlAction extends AbstractRestAction
{
    public function __construct(
        private readonly UrlShortenerInterface $urlShortener,
        private readonly ShortUrlDataTransformerInterface $transformer,
        private readonly TreeMapper $treeMapper,
    ) {
    }

    public function handle(Request $request): Response
    {
        $shortUrlMeta = $this->buildShortUrlData($request);
        $result = $this->urlShortener->shorten($shortUrlMeta);

        return new JsonResponse($this->transformer->transform($result->shortUrl));
    }

    protected function mapShortUrlCreation(array $payload): ShortUrlCreation
    {
        return $this->treeMapper->map(ShortUrlCreation::class, $payload);
    }

    /**
     * @throws ValidationException
     */
    abstract protected function buildShortUrlData(Request $request): ShortUrlCreation;
}
