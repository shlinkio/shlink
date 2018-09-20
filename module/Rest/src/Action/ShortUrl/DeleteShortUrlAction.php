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
use Zend\I18n\Translator\TranslatorInterface;

class DeleteShortUrlAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-urls/{shortCode}';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_DELETE];

    /**
     * @var DeleteShortUrlServiceInterface
     */
    private $deleteShortUrlService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        DeleteShortUrlServiceInterface $deleteShortUrlService,
        TranslatorInterface $translator,
        LoggerInterface $logger = null
    ) {
        parent::__construct($logger);
        $this->deleteShortUrlService = $deleteShortUrlService;
        $this->translator = $translator;
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
        } catch (Exception\InvalidShortCodeException $e) {
            $this->logger->warning(
                \sprintf('Provided short code %s does not belong to any URL.', $shortCode) . PHP_EOL . $e
            );
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => \sprintf($this->translator->translate('No URL found for short code "%s"'), $shortCode),
            ], self::STATUS_NOT_FOUND);
        } catch (Exception\DeleteShortUrlException $e) {
            $this->logger->warning('Provided data is invalid.' . PHP_EOL . $e);
            $messagePlaceholder = $this->translator->translate(
                'It is not possible to delete URL with short code "%s" because it has reached more than "%s" visits.'
            );

            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => \sprintf($messagePlaceholder, $shortCode, $e->getVisitsThreshold()),
            ], self::STATUS_BAD_REQUEST);
        }
    }
}
