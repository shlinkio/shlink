<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Endroid\QrCode\QrCode;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Zend\Expressive\Router\RouterInterface;

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
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     * @param DelegateInterface $delegate
     *
     * @return Response
     */
    public function process(Request $request, DelegateInterface $delegate)
    {
        // Make sure the short URL exists for this short code
        $shortCode = $request->getAttribute('shortCode');
        try {
            $shortUrl = $this->urlShortener->shortCodeToUrl($shortCode);
        } catch (InvalidShortCodeException $e) {
            $this->logger->warning('Tried to create a QR code with an invalid short code' . PHP_EOL . $e);
            return $delegate->process($request);
        } catch (EntityDoesNotExistException $e) {
            $this->logger->warning('Tried to create a QR code with a not found short code' . PHP_EOL . $e);
            return $delegate->process($request);
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
        $size = (int) $request->getAttribute('size', 300);
        if ($size < 50) {
            return 50;
        }

        return $size > 1000 ? 1000 : $size;
    }
}
