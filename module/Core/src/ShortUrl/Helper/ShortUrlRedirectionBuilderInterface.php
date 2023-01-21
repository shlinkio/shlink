<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

interface ShortUrlRedirectionBuilderInterface
{
    public function buildShortUrlRedirect(
        ShortUrl $shortUrl,
        ServerRequestInterface $request,
        ?string $extraPath = null,
    ): string;
}
