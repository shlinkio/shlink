<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Shlinkio\Shlink\Core\Entity\ShortUrl;

interface ShortUrlRedirectionBuilderInterface
{
    public function buildShortUrlRedirect(ShortUrl $shortUrl, array $currentQuery, ?string $extraPath = null): string;
}
