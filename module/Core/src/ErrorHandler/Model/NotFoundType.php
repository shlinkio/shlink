<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler\Model;

use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;

use function rtrim;

class NotFoundType
{
    private function __construct(private readonly ?VisitType $type)
    {
    }

    public static function fromRequest(ServerRequestInterface $request, string $basePath): self
    {
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class) ?? RouteResult::fromRouteFailure(null);
        $isBaseUrl = rtrim($request->getUri()->getPath(), '/') === $basePath;

        $type = match (true) {
            $isBaseUrl => VisitType::BASE_URL,
            $routeResult->isFailure() => VisitType::REGULAR_404,
            $routeResult->getMatchedRouteName() === RedirectAction::class => VisitType::INVALID_SHORT_URL,
            default => null,
        };

        return new self($type);
    }

    public function isBaseUrl(): bool
    {
        return $this->type === VisitType::BASE_URL;
    }

    public function isRegularNotFound(): bool
    {
        return $this->type === VisitType::REGULAR_404;
    }

    public function isInvalidShortUrl(): bool
    {
        return $this->type === VisitType::INVALID_SHORT_URL;
    }
}
