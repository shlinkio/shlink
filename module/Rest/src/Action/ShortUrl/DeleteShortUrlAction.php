<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Zend\Diactoros\Response\EmptyResponse;

class DeleteShortUrlAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-urls/{shortCode}';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_DELETE];

    /** @var DeleteShortUrlServiceInterface */
    private $deleteShortUrlService;

    public function __construct(DeleteShortUrlServiceInterface $deleteShortUrlService, ?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->deleteShortUrlService = $deleteShortUrlService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $shortCode = $request->getAttribute('shortCode', '');
        $this->deleteShortUrlService->deleteByShortCode($shortCode);
        return new EmptyResponse();
    }
}
