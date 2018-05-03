<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortCode;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\CreateShortCodeData;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Uri;
use Zend\I18n\Translator\TranslatorInterface;

abstract class AbstractCreateShortCodeAction extends AbstractRestAction
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var array
     */
    private $domainConfig;
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        UrlShortenerInterface $urlShortener,
        TranslatorInterface $translator,
        array $domainConfig,
        LoggerInterface $logger = null
    ) {
        parent::__construct($logger);
        $this->urlShortener = $urlShortener;
        $this->translator = $translator;
        $this->domainConfig = $domainConfig;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function handle(Request $request): Response
    {
        try {
            $shortCodeData = $this->buildUrlToShortCodeData($request);
            $shortCodeMeta = $shortCodeData->getMeta();
            $longUrl = $shortCodeData->getLongUrl();
            $customSlug = $shortCodeMeta->getCustomSlug();
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Provided data is invalid.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => $e->getMessage(),
            ], self::STATUS_BAD_REQUEST);
        }

        try {
            $shortCode = $this->urlShortener->urlToShortCode(
                $longUrl,
                $shortCodeData->getTags(),
                $shortCodeMeta->getValidSince(),
                $shortCodeMeta->getValidUntil(),
                $customSlug,
                $shortCodeMeta->getMaxVisits()
            );
            $shortUrl = (new Uri())->withPath($shortCode)
                                   ->withScheme($this->domainConfig['schema'])
                                   ->withHost($this->domainConfig['hostname']);

            // TODO Make response to be generated based on Accept header
            return new JsonResponse([
                'longUrl' => (string) $longUrl,
                'shortUrl' => (string) $shortUrl,
                'shortCode' => $shortCode,
            ]);
        } catch (InvalidUrlException $e) {
            $this->logger->warning('Provided Invalid URL.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => \sprintf(
                    $this->translator->translate('Provided URL %s is invalid. Try with a different one.'),
                    $longUrl
                ),
            ], self::STATUS_BAD_REQUEST);
        } catch (NonUniqueSlugException $e) {
            $this->logger->warning('Provided non-unique slug.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => \sprintf(
                    $this->translator->translate('Provided slug %s is already in use. Try with a different one.'),
                    $customSlug
                ),
            ], self::STATUS_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error creating shortcode.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => $this->translator->translate('Unexpected error occurred'),
            ], self::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return CreateShortCodeData
     * @throws InvalidArgumentException
     */
    abstract protected function buildUrlToShortCodeData(Request $request): CreateShortCodeData;
}
