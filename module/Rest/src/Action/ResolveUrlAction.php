<?php
namespace Shlinkio\Shlink\Rest\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;

class ResolveUrlAction extends AbstractRestAction
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ResolveUrlAction constructor.
     * @param UrlShortenerInterface|UrlShortener $urlShortener
     * @param TranslatorInterface $translator
     *
     * @Inject({UrlShortener::class, "translator"})
     */
    public function __construct(UrlShortenerInterface $urlShortener, TranslatorInterface $translator)
    {
        $this->urlShortener = $urlShortener;
        $this->translator = $translator;
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
                    'message' => sprintf($this->translator->translate('No URL found for shortcode "%s"'), $shortCode),
                ], 400);
            }

            return new JsonResponse([
                'longUrl' => $longUrl,
            ]);
        } catch (InvalidShortCodeException $e) {
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf(
                    $this->translator->translate('Provided short code "%s" has an invalid format'),
                    $shortCode
                ),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => $this->translator->translate('Unexpected error occurred'),
            ], 500);
        }
    }
}
