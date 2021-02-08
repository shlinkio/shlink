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
    private string $type;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function fromRequest(ServerRequestInterface $request, string $basePath): self
    {
        $isBaseUrl = rtrim($request->getUri()->getPath(), '/') === $basePath;
        if ($isBaseUrl) {
            return new self(Visit::TYPE_BASE_URL);
        }

        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class, RouteResult::fromRouteFailure(null));
        if ($routeResult->isFailure()) {
            return new self(Visit::TYPE_REGULAR_404);
        }

        if ($routeResult->getMatchedRouteName() === RedirectAction::class) {
            return new self(Visit::TYPE_INVALID_SHORT_URL);
        }

        return new self(self::class);
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
