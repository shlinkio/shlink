<?php
namespace Shlinkio\Shlink\Common\Middleware;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\I18n\Translator\Translator;
use Zend\Stratigility\MiddlewareInterface;

class LocaleMiddleware implements MiddlewareInterface
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * LocaleMiddleware constructor.
     * @param Translator $translator
     *
     * @Inject({"translator"})
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
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
        if (! $request->hasHeader('Accept-Language')) {
            return $out($request, $response);
        }

        $locale = $request->getHeaderLine('Accept-Language');
        $this->translator->setLocale($this->normalizeLocale($locale));
        return $out($request, $response);
    }

    /**
     * @param string $locale
     * @return string
     */
    protected function normalizeLocale($locale)
    {
        $parts = explode('_', $locale);
        if (count($parts) > 1) {
            return $parts[0];
        }

        $parts = explode('-', $locale);
        if (count($parts) > 1) {
            return $parts[0];
        }

        return $locale;
    }
}
