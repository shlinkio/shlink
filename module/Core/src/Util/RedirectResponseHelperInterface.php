<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Psr\Http\Message\ResponseInterface;

interface RedirectResponseHelperInterface
{
    public function buildRedirectResponse(string $location): ResponseInterface;
}
