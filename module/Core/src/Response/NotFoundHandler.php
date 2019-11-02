<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Response;

use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Zend\Diactoros\Response;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Template\TemplateRendererInterface;

use function array_shift;
use function explode;
use function Functional\contains;
use function rtrim;

class NotFoundHandler implements RequestHandlerInterface
{
    public const NOT_FOUND_TEMPLATE = 'notFoundTemplate';

    /** @var TemplateRendererInterface */
    private $renderer;
    /** @var string */
    private $defaultTemplate;
    /** @var NotFoundRedirectOptions */
    private $redirectOptions;
    /** @var string */
    private $shlinkBasePath;

    public function __construct(
        TemplateRendererInterface $renderer,
        NotFoundRedirectOptions $redirectOptions,
        string $shlinkBasePath,
        string $defaultTemplate = 'ShlinkCore::error/404'
    ) {
        $this->renderer = $renderer;
        $this->defaultTemplate = $defaultTemplate;
        $this->redirectOptions = $redirectOptions;
        $this->shlinkBasePath = $shlinkBasePath;
    }

    /**
     * Dispatch the next available middleware and return the response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws InvalidArgumentException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $redirectResponse = $this->createRedirectResponse($request);
        if ($redirectResponse !== null) {
            return $redirectResponse;
        }

        $accepts = explode(',', $request->getHeaderLine('Accept'));
        $accept = array_shift($accepts);
        $status = StatusCodeInterface::STATUS_NOT_FOUND;

        // If the first accepted type is json, return a json response
        if (contains(['application/json', 'text/json', 'application/x-json'], $accept)) {
            return new Response\JsonResponse([
                'error' => 'NOT_FOUND',
                'message' => 'Not found',
            ], $status);
        }

        $notFoundTemplate = $request->getAttribute(self::NOT_FOUND_TEMPLATE, $this->defaultTemplate);
        return new Response\HtmlResponse($this->renderer->render($notFoundTemplate), $status);
    }

    private function createRedirectResponse(ServerRequestInterface $request): ?ResponseInterface
    {
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class, RouteResult::fromRouteFailure(null));
        $isBaseUrl = rtrim($request->getUri()->getPath(), '/') === $this->shlinkBasePath;

        if ($isBaseUrl && $this->redirectOptions->hasBaseUrlRedirect()) {
            return new Response\RedirectResponse($this->redirectOptions->getBaseUrlRedirect());
        }

        if (!$isBaseUrl && $routeResult->isFailure() && $this->redirectOptions->hasRegular404Redirect()) {
            return new Response\RedirectResponse($this->redirectOptions->getRegular404Redirect());
        }

        if (
            $routeResult->isSuccess() &&
            $routeResult->getMatchedRouteName() === 'long-url-redirect' &&
            $this->redirectOptions->hasInvalidShortUrlRedirect()
        ) {
            return new Response\RedirectResponse($this->redirectOptions->getInvalidShortUrlRedirect());
        }

        return null;
    }
}
