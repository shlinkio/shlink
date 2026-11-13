<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Response\PixelResponse;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

class PixelAction extends AbstractTrackingAction
{
    #[Override]
    protected function createSuccessResp(ShortUrl $shortUrl, ServerRequestInterface $request): ResponseInterface
    {
        return new PixelResponse();
    }

    #[Override]
    protected function createErrorResp(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        return new PixelResponse();
    }
}
