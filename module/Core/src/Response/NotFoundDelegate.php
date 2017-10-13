<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Response;

use Fig\Http\Message\StatusCodeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Expressive\Template\TemplateRendererInterface;

class NotFoundDelegate implements DelegateInterface
{
    /**
     * @var TemplateRendererInterface
     */
    private $renderer;
    /**
     * @var string
     */
    private $template;

    public function __construct(TemplateRendererInterface $renderer, string $template = 'ShlinkCore::error/404')
    {
        $this->renderer = $renderer;
        $this->template = $template;
    }

    /**
     * Dispatch the next available middleware and return the response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function process(ServerRequestInterface $request): ResponseInterface
    {
        $accepts = explode(',', $request->getHeaderLine('Accept'));
        $accept = array_shift($accepts);
        $status = StatusCodeInterface::STATUS_NOT_FOUND;

        // If the first accepted type is json, return a json response
        if (in_array($accept, ['application/json', 'text/json', 'application/x-json'], true)) {
            return new Response\JsonResponse([
                'error' => 'NOT_FOUND',
                'message' => 'Not found',
            ], $status);
        }

        return new Response\HtmlResponse($this->renderer->render($this->template, ['request' => $request]), $status);
    }
}
