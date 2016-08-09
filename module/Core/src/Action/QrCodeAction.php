<?php
namespace Shlinkio\Shlink\Core\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Endroid\QrCode\QrCode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Zend\Expressive\Router\RouterInterface;
use Zend\Stratigility\MiddlewareInterface;

class QrCodeAction implements MiddlewareInterface
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * QrCodeAction constructor.
     * @param RouterInterface $router
     * @param UrlShortenerInterface $urlShortener
     * @param LoggerInterface $logger
     *
     * @Inject({RouterInterface::class, UrlShortener::class, "Logger_Shlink"})
     */
    public function __construct(
        RouterInterface $router,
        UrlShortenerInterface $urlShortener,
        LoggerInterface $logger = null
    ) {
        $this->router = $router;
        $this->urlShortener = $urlShortener;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Process an incoming request and/or response.
     *
     * Accepts a server-side request and a response instance, and does
     * something with them.
     *
     * If the response is not complete and/or further processing would not
     * interfere with the work done in the middleware, or if the middleware
     * wants to delegate to another process, it can use the `$out` callable
     * if present.
     *
     * If the middleware does not return a value, execution of the current
     * request is considered complete, and the response instance provided will
     * be considered the response to return.
     *
     * Alternately, the middleware may return a response instance.
     *
     * Often, middleware will `return $out();`, with the assumption that a
     * later middleware will return a response.
     *
     * @param Request $request
     * @param Response $response
     * @param null|callable $out
     * @return null|Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        // Make sure the short URL exists for this short code
        $shortCode = $request->getAttribute('shortCode');
        try {
            $shortUrl = $this->urlShortener->shortCodeToUrl($shortCode);
            if (! isset($shortUrl)) {
                return $out($request, $response->withStatus(404), 'Not Found');
            }
        } catch (InvalidShortCodeException $e) {
            $this->logger->warning('Tried to create a QR code with an invalid short code' . PHP_EOL . $e);
            return $out($request, $response->withStatus(404), 'Not Found');
        }

        $path = $this->router->generateUri('long-url-redirect', ['shortCode' => $shortCode]);
        $size = $this->getSizeParam($request);

        $qrCode = new QrCode($request->getUri()->withPath($path)->withQuery(''));
        $qrCode->setSize($size)
               ->setPadding(0);
        return new QrCodeResponse($qrCode);
    }

    /**
     * @param Request $request
     * @return int
     */
    protected function getSizeParam(Request $request)
    {
        $size = intval($request->getAttribute('size', 300));
        if ($size < 50) {
            return 50;
        } elseif ($size > 1000) {
            return 1000;
        }

        return $size;
    }
}
