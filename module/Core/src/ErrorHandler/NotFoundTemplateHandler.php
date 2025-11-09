<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;

class NotFoundTemplateHandler implements RequestHandlerInterface
{
    public function __construct(private ErrorTemplateHandler $errorTemplateHandler)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var NotFoundType $notFoundType */
        $notFoundType = $request->getAttribute(NotFoundType::class);
        $status = StatusCodeInterface::STATUS_NOT_FOUND;

        // Create a mock exception for the error template handler
        $error = new RuntimeException('Not Found');

        // Get specific description based on NotFoundType
        $description = match (true) {
            $notFoundType->isExpiredShortUrl() => '<div class="description-box"><strong>This link has expired</strong><p>The short link you are trying to access is no longer valid because it has passed its expiration date or reached its maximum number of visits.</p></div>',
            $notFoundType->isInvalidShortUrl() => '<div class="description-box"><strong>Invalid short URL</strong><p>This short URL doesn\'t seem to be valid. Make sure you included all the characters, with no extra punctuation.</p></div>',
            default => '',
        };

        // Create error response with custom description
        return $this->errorTemplateHandler->createErrorResponse($error, $status, $description);
    }
}
