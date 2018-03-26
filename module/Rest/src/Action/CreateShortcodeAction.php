<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Uri;
use Zend\I18n\Translator\TranslatorInterface;

class CreateShortcodeAction extends AbstractRestAction
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
    private $translator;

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
        $postData = (array) $request->getParsedBody();
        if (! isset($postData['longUrl'])) {
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => $this->translator->translate('A URL was not provided'),
            ], self::STATUS_BAD_REQUEST);
        }
        $longUrl = $postData['longUrl'];
        $customSlug = $postData['customSlug'] ?? null;

        try {
            $shortCode = $this->urlShortener->urlToShortCode(
                new Uri($longUrl),
                (array) ($postData['tags'] ?? []),
                $this->getOptionalDate($postData, 'validSince'),
                $this->getOptionalDate($postData, 'validUntil'),
                $customSlug,
                isset($postData['maxVisits']) ? (int) $postData['maxVisits'] : null
            );
            $shortUrl = (new Uri())->withPath($shortCode)
                                   ->withScheme($this->domainConfig['schema'])
                                   ->withHost($this->domainConfig['hostname']);

            return new JsonResponse([
                'longUrl' => $longUrl,
                'shortUrl' => (string) $shortUrl,
                'shortCode' => $shortCode,
            ]);
        } catch (InvalidUrlException $e) {
            $this->logger->warning('Provided Invalid URL.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf(
                    $this->translator->translate('Provided URL %s is invalid. Try with a different one.'),
                    $longUrl
                ),
            ], self::STATUS_BAD_REQUEST);
        } catch (NonUniqueSlugException $e) {
            $this->logger->warning('Provided non-unique slug.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf(
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

    private function getOptionalDate(array $postData, string $fieldName)
    {
        return isset($postData[$fieldName]) ? new \DateTime($postData[$fieldName]) : null;
    }
}
