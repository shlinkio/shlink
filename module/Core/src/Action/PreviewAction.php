<?php
namespace Shlinkio\Shlink\Core\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Common\Service\PreviewGeneratorInterface;
use Shlinkio\Shlink\Common\Util\ResponseUtilsTrait;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;

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
        $shortCode = $request->getAttribute('shortCode');

        try {
            $url = $this->urlShortener->shortCodeToUrl($shortCode);
            if (! isset($url)) {
                return $delegate->process($request);
            }

            $imagePath = $this->previewGenerator->generatePreview($url);
            return $this->generateImageResponse($imagePath);
        } catch (InvalidShortCodeException $e) {
            return $delegate->process($request);
        }
    }
}
