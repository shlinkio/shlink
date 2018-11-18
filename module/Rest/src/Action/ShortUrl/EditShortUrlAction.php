<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use function sprintf;

class EditShortUrlAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-urls/{shortCode}';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_PUT];

    /**
     * @var ShortUrlServiceInterface
     */
    private $shortUrlService;

    public function __construct(ShortUrlServiceInterface $shortUrlService, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->shortUrlService = $shortUrlService;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $postData = (array) $request->getParsedBody();
        $shortCode = $request->getAttribute('shortCode', '');

        try {
            $this->shortUrlService->updateMetadataByShortCode(
                $shortCode,
                ShortUrlMeta::createFromRawData($postData)
            );
            return new EmptyResponse();
        } catch (Exception\InvalidShortCodeException $e) {
            $this->logger->warning('Provided data is invalid. {e}', ['e' => $e]);
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf('No URL found for short code "%s"', $shortCode),
            ], self::STATUS_NOT_FOUND);
        } catch (Exception\ValidationException $e) {
            $this->logger->warning('Provided data is invalid. {e}', ['e' => $e]);
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => 'Provided data is invalid.',
            ], self::STATUS_BAD_REQUEST);
        }
    }
}
