<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler\Model;

use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Entity\Visit;

use function rtrim;

class NotFoundType
{
    private function __construct(private string $type)
    {
    }

    public static function fromRequest(ServerRequestInterface $request, string $basePath): self
    {
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class, RouteResult::fromRouteFailure(null));
        $isBaseUrl = rtrim($request->getUri()->getPath(), '/') === $basePath;

        $type = match (true) {
            $isBaseUrl => Visit::TYPE_BASE_URL,
            $routeResult->isFailure() => Visit::TYPE_REGULAR_404,
            $routeResult->getMatchedRouteName() === RedirectAction::class => Visit::TYPE_INVALID_SHORT_URL,
            default => self::class,
        };

        return new self($type);
    }

    public function isBaseUrl(): bool
    {
        return $this->type === Visit::TYPE_BASE_URL;
    }

    public function isRegularNotFound(): bool
    {
        return $this->type === Visit::TYPE_REGULAR_404;
    }

    public function isInvalidShortUrl(): bool
    {
        return $this->type === Visit::TYPE_INVALID_SHORT_URL;
    }
}
