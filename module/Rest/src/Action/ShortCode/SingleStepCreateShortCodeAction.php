<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortCode;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Model\CreateShortCodeData;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Zend\Diactoros\Uri;
use Zend\I18n\Translator\TranslatorInterface;

class SingleStepCreateShortCodeAction extends AbstractCreateShortCodeAction
{
    protected const ROUTE_PATH = '/short-codes/shorten';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    /**
     * @var ApiKeyServiceInterface
     */
    private $apiKeyService;

    public function __construct(
        UrlShortenerInterface $urlShortener,
        TranslatorInterface $translator,
        ApiKeyServiceInterface $apiKeyService,
        array $domainConfig,
        LoggerInterface $logger = null
    ) {
        parent::__construct($urlShortener, $translator, $domainConfig, $logger);
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * @param Request $request
     * @return CreateShortCodeData
     * @throws \InvalidArgumentException
     * @throws InvalidArgumentException
     */
    protected function buildUrlToShortCodeData(Request $request): CreateShortCodeData
    {
        $query = $request->getQueryParams();

        // Check provided API key
        $apiKey = $this->apiKeyService->getByKey($query['apiKey'] ?? '');
        if ($apiKey === null || ! $apiKey->isValid()) {
            throw new InvalidArgumentException(
                $this->translator->translate('No API key was provided or it is not valid')
            );
        }

        if (! isset($query['longUrl'])) {
            throw new InvalidArgumentException($this->translator->translate('A URL was not provided'));
        }

        return new CreateShortCodeData(new Uri($query['longUrl']));
    }
}
