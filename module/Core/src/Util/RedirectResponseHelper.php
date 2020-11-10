<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;

use function sprintf;

class RedirectResponseHelper implements RedirectResponseHelperInterface
{
    private UrlShortenerOptions $options;

    public function __construct(UrlShortenerOptions $options)
    {
        $this->options = $options;
    }

    public function buildRedirectResponse(string $location): ResponseInterface
    {
        $statusCode = $this->options->redirectStatusCode();
        $headers = $statusCode === StatusCodeInterface::STATUS_FOUND ? [] : [
            'Cache-Control' => sprintf('private,max-age=%s', $this->options->redirectCacheLifetime()),
        ];

        return new RedirectResponse($location, $statusCode, $headers);
    }
}
