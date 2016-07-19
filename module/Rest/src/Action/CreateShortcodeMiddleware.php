<?php
namespace Shlinkio\Shlink\Rest\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Uri;

class CreateShortcodeMiddleware extends AbstractRestMiddleware
{
    /**
     * @var UrlShortener|UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var array
     */
    private $domainConfig;

    /**
     * GenerateShortcodeMiddleware constructor.
     *
     * @param UrlShortenerInterface|UrlShortener $urlShortener
     * @param array $domainConfig
     *
     * @Inject({UrlShortener::class, "config.url_shortener.domain"})
     */
    public function __construct(UrlShortenerInterface $urlShortener, array $domainConfig)
    {
        $this->urlShortener = $urlShortener;
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
                'message' => 'A URL was not provided',
            ], 400);
        }
        $longUrl = $postData['longUrl'];

        try {
            $shortCode = $this->urlShortener->urlToShortCode(new Uri($longUrl));
            $shortUrl = (new Uri())->withPath($shortCode)
                                   ->withScheme($this->domainConfig['schema'])
                                   ->withHost($this->domainConfig['hostname']);

            return new JsonResponse([
                'longUrl' => $longUrl,
                'shortUrl' => $shortUrl->__toString(),
                'shortCode' => $shortCode,
            ]);
        } catch (InvalidUrlException $e) {
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf('Provided URL "%s" is invalid. Try with a different one.', $longUrl),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => 'Unexpected error occured',
            ], 500);
        }
    }
}
