<?php
namespace Shlinkio\Shlink\Core\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Common\Service\PreviewGeneratorInterface;
use Shlinkio\Shlink\Common\Util\ResponseUtilsTrait;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Zend\Stratigility\MiddlewareInterface;

class PreviewAction implements MiddlewareInterface
{
    use ResponseUtilsTrait;

    /**
     * @var PreviewGeneratorInterface
     */
    private $previewGenerator;
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;

    /**
     * PreviewAction constructor.
     * @param PreviewGeneratorInterface $previewGenerator
     * @param UrlShortenerInterface $urlShortener
     *
     * @Inject({PreviewGenerator::class, UrlShortener::class})
     */
    public function __construct(PreviewGeneratorInterface $previewGenerator, UrlShortenerInterface $urlShortener)
    {
        $this->previewGenerator = $previewGenerator;
        $this->urlShortener = $urlShortener;
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
        $shortCode = $request->getAttribute('shortCode');

        try {
            $url = $this->urlShortener->shortCodeToUrl($shortCode);
            if (! isset($url)) {
                return $out($request, $response->withStatus(404), 'Not found');
            }

            $imagePath = $this->previewGenerator->generatePreview($url);
            return $this->generateImageResponse($imagePath);
        } catch (InvalidShortCodeException $e) {
            return $out($request, $response->withStatus(404), 'Not found');
        }
    }
}
