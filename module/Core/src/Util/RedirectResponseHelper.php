<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\Core\Options\RedirectOptions;

use function sprintf;

class RedirectResponseHelper implements RedirectResponseHelperInterface
{
    public function __construct(private readonly RedirectOptions $options)
    {
    }

    public function buildRedirectResponse(string $location): ResponseInterface
    {
        $statusCode = $this->options->redirectStatusCode;
        $headers = ! $statusCode->allowsCache() ? [] : [
            'Cache-Control' => sprintf('private,max-age=%s', $this->options->redirectCacheLifetime),
        ];

        return new RedirectResponse($location, $statusCode->value, $headers);
    }
}
