<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\CreateShortUrlData;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;

abstract class AbstractCreateShortUrlAction extends AbstractRestAction
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
            $shortUrlData = $this->buildShortUrlData($request);
            $shortUrlMeta = $shortUrlData->getMeta();
            $longUrl = $shortUrlData->getLongUrl();
            $customSlug = $shortUrlMeta->getCustomSlug();
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Provided data is invalid.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => $e->getMessage(),
            ], self::STATUS_BAD_REQUEST);
        }

        try {
            $shortUrl = $this->urlShortener->urlToShortCode(
                $longUrl,
                $shortUrlData->getTags(),
                $shortUrlMeta->getValidSince(),
                $shortUrlMeta->getValidUntil(),
                $customSlug,
                $shortUrlMeta->getMaxVisits()
            );
            $transformer = new ShortUrlDataTransformer($this->domainConfig);

            return new JsonResponse($transformer->transform($shortUrl));
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
            $this->logger->error('Unexpected error creating short url.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => $this->translator->translate('Unexpected error occurred'),
            ], self::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return CreateShortUrlData
     * @throws InvalidArgumentException
     */
    abstract protected function buildShortUrlData(Request $request): CreateShortUrlData;
}
