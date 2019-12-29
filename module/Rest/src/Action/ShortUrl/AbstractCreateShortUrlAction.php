<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\CreateShortUrlData;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Zend\Diactoros\Response\JsonResponse;

abstract class AbstractCreateShortUrlAction extends AbstractRestAction
{
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

    public function handle(Request $request): Response
    {
        $shortUrlData = $this->buildShortUrlData($request);
        $longUrl = $shortUrlData->getLongUrl();
        $tags = $shortUrlData->getTags();
        $shortUrlMeta = $shortUrlData->getMeta();

        $shortUrl = $this->urlShortener->urlToShortCode($longUrl, $tags, $shortUrlMeta);
        $transformer = new ShortUrlDataTransformer($this->domainConfig);

        return new JsonResponse($transformer->transform($shortUrl));
    }

    /**
     * @param Request $request
     * @return CreateShortUrlData
     * @throws ValidationException
     */
    abstract protected function buildShortUrlData(Request $request): CreateShortUrlData;
}
