<?php
namespace Acelaya\UrlShortener\Middleware\Rest;

use Acelaya\UrlShortener\Exception\InvalidShortCodeException;
use Acelaya\UrlShortener\Service\UrlShortener;
use Acelaya\UrlShortener\Service\UrlShortenerInterface;
use Acelaya\UrlShortener\Util\RestUtils;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;

class ResolveUrlMiddleware extends AbstractRestMiddleware
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;

    /**
     * ResolveUrlMiddleware constructor.
     * @param UrlShortenerInterface|UrlShortener $urlShortener
     *
     * @Inject({UrlShortener::class})
     */
    public function __construct(UrlShortenerInterface $urlShortener)
    {
        $this->urlShortener = $urlShortener;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable|null $out
     * @return null|Response
     */
    public function dispatch(Request $request, Response $response, callable $out = null)
    {
        $shortCode = $request->getAttribute('shortCode');

        try {
            $longUrl = $this->urlShortener->shortCodeToUrl($shortCode);
            if (! isset($longUrl)) {
                return new JsonResponse([
                    'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                    'message' => sprintf('No URL found for shortcode "%s"', $shortCode),
                ], 400);
            }

            return new JsonResponse([
                'longUrl' => $longUrl,
            ]);
        } catch (InvalidShortCodeException $e) {
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf('Provided short code "%s" has an invalid format', $shortCode),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => 'Unexpected error occured',
            ], 500);
        }
    }
}
