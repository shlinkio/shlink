<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;

class EditShortUrlAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-urls/{shortCode}';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_PATCH, self::METHOD_PUT];

    private ShortUrlServiceInterface $shortUrlService;

    public function __construct(ShortUrlServiceInterface $shortUrlService, ?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->shortUrlService = $shortUrlService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $postData = (array) $request->getParsedBody();
        $identifier = ShortUrlIdentifier::fromApiRequest($request);

        $this->shortUrlService->updateMetadataByShortCode($identifier, ShortUrlMeta::fromRawData($postData));
        return new EmptyResponse();
    }
}
