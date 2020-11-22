<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Closure;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function file_get_contents;
use function sprintf;

class NotFoundTemplateHandler implements RequestHandlerInterface
{
    private const TEMPLATES_BASE_DIR = __DIR__ . '/../../templates';
    public const NOT_FOUND_TEMPLATE = '404.html';
    public const INVALID_SHORT_CODE_TEMPLATE = 'invalid-short-code.html';
    private Closure $readFile;

    public function __construct(?callable $readFile = null)
    {
        $this->readFile = $readFile ? Closure::fromCallable($readFile) : fn (string $file) => file_get_contents($file);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class) ?? RouteResult::fromRouteFailure(null);
        $status = StatusCodeInterface::STATUS_NOT_FOUND;

        $template = $routeResult->isFailure() ? self::NOT_FOUND_TEMPLATE : self::INVALID_SHORT_CODE_TEMPLATE;
        $templateContent = ($this->readFile)(sprintf('%s/%s', self::TEMPLATES_BASE_DIR, $template));
        return new Response\HtmlResponse($templateContent, $status);
    }
}
