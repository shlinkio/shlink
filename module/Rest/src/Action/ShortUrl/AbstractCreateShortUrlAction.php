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
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;

abstract class AbstractCreateShortUrlAction extends AbstractRestAction
{
    /** @var UrlShortenerInterface */
    private $urlShortener;
    /** @var array */
    private $domainConfig;

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
     */
    public function handle(Request $request): Response
    {
        try {
            $shortUrlData = $this->buildShortUrlData($request);
        } catch (ValidationException $e) {
            $this->logger->warning('Provided data is invalid. {e}', ['e' => $e]);
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => $e->getMessage(),
            ], self::STATUS_BAD_REQUEST);
        }

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
