<?php
namespace Shlinkio\Shlink\Rest\Middleware\Error;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Stratigility\ErrorMiddlewareInterface;

class ResponseTypeMiddleware implements ErrorMiddlewareInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ResponseTypeMiddleware constructor.
     * @param TranslatorInterface $translator
     *
     * @Inject({"translator"})
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Process an incoming error, along with associated request and response.
     *
     * Accepts an error, a server-side request, and a response instance, and
     * does something with them; if further processing can be done, it can
     * delegate to `$out`.
     *
     * @see MiddlewareInterface
     * @param mixed $error
     * @param Request $request
     * @param Response $response
     * @param null|callable $out
     * @return null|Response
     */
    public function __invoke($error, Request $request, Response $response, callable $out = null)
    {
        $accept = $request->getHeader('Accept');
        if (! empty(array_intersect(['application/json', 'text/json', 'application/x-json'], $accept))) {
            $status = $response->getStatusCode();
            $status = $status >= 400 ? $status : 500;

            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => $this->translator->translate('Unknown error'),
            ], $status);
        }

        return $out($request, $response, $error);
    }
}
