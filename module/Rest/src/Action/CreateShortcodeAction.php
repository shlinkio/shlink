<?php
namespace Shlinkio\Shlink\Rest\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
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

    /**
     * GenerateShortcodeMiddleware constructor.
     *
     * @param UrlShortenerInterface $urlShortener
     * @param TranslatorInterface $translator
     * @param array $domainConfig
     * @param LoggerInterface|null $logger
     *
     * @Inject({UrlShortener::class, "translator", "config.url_shortener.domain", "Logger_Shlink"})
     */
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
     * @param Response $response
     * @param callable|null $out
     * @return null|Response
     */
    public function dispatch(Request $request, Response $response, callable $out = null)
    {
        $postData = $request->getParsedBody();
        if (! isset($postData['longUrl'])) {
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => $this->translator->translate('A URL was not provided'),
            ], 400);
        }
        $longUrl = $postData['longUrl'];
        $tags = isset($postData['tags']) && is_array($postData['tags']) ? $postData['tags'] : [];

        try {
            $shortCode = $this->urlShortener->urlToShortCode(new Uri($longUrl), $tags);
            $shortUrl = (new Uri())->withPath($shortCode)
                                   ->withScheme($this->domainConfig['schema'])
                                   ->withHost($this->domainConfig['hostname']);

            return new JsonResponse([
                'longUrl' => $longUrl,
                'shortUrl' => $shortUrl->__toString(),
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
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error creating shortcode.' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => $this->translator->translate('Unexpected error occurred'),
            ], 500);
        }
    }
}
