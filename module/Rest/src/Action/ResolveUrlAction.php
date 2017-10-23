<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;

class ResolveUrlAction extends AbstractRestAction
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        UrlShortenerInterface $urlShortener,
        TranslatorInterface $translator,
        LoggerInterface $logger = null
    ) {
        parent::__construct($logger);
        $this->urlShortener = $urlShortener;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @param DelegateInterface $delegate
     * @return null|Response
     * @throws \InvalidArgumentException
     */
    public function process(Request $request, DelegateInterface $delegate)
    {
        $shortCode = $request->getAttribute('shortCode');

        try {
            $longUrl = $this->urlShortener->shortCodeToUrl($shortCode);
            return new JsonResponse([
                'longUrl' => $longUrl,
            ]);
        } catch (InvalidShortCodeException $e) {
            $this->logger->warning('Provided short code with invalid format.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf(
                    $this->translator->translate('Provided short code "%s" has an invalid format'),
                    $shortCode
                ),
            ], self::STATUS_BAD_REQUEST);
        } catch (EntityDoesNotExistException $e) {
            $this->logger->warning('Provided short code couldn\'t be found.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => sprintf($this->translator->translate('No URL found for short code "%s"'), $shortCode),
            ], self::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while resolving the URL behind a short code.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => $this->translator->translate('Unexpected error occurred'),
            ], self::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
