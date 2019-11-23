<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

use function sprintf;

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

    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $shortCode = $request->getAttribute('shortCode', '');

        try {
            $this->deleteShortUrlService->deleteByShortCode($shortCode);
            return new EmptyResponse();
        } catch (Exception\DeleteShortUrlException $e) {
            $this->logger->warning('Provided data is invalid. {e}', ['e' => $e]);
            $messagePlaceholder =
                'It is not possible to delete URL with short code "%s" because it has reached more than "%s" visits.';

            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf($messagePlaceholder, $shortCode, $e->getVisitsThreshold()),
            ], self::STATUS_BAD_REQUEST);
        }
    }
}
