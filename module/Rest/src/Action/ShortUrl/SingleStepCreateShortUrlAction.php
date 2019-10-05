<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Model\CreateShortUrlData;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Zend\Diactoros\Uri;

class SingleStepCreateShortUrlAction extends AbstractCreateShortUrlAction
{
    protected const ROUTE_PATH = '/short-urls/shorten';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    /** @var ApiKeyServiceInterface */
    private $apiKeyService;

    public function __construct(
        UrlShortenerInterface $urlShortener,
        ApiKeyServiceInterface $apiKeyService,
        array $domainConfig,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($urlShortener, $domainConfig, $logger);
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * @param Request $request
     * @return CreateShortUrlData
     * @throws \InvalidArgumentException
     * @throws InvalidArgumentException
     */
    protected function buildShortUrlData(Request $request): CreateShortUrlData
    {
        $query = $request->getQueryParams();

        if (! $this->apiKeyService->check($query['apiKey'] ?? '')) {
            throw new InvalidArgumentException('No API key was provided or it is not valid');
        }

        if (! isset($query['longUrl'])) {
            throw new InvalidArgumentException('A URL was not provided');
        }

        return new CreateShortUrlData(new Uri($query['longUrl']));
    }
}
