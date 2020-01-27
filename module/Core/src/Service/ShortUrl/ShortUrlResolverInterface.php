<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;

interface ShortUrlResolverInterface
{
    /**
     * @throws ShortUrlNotFoundException
     */
    public function shortCodeToShortUrl(string $shortCode, ?string $domain = null): ShortUrl;

    /**
     * @throws ShortUrlNotFoundException
     */
    public function shortCodeToEnabledShortUrl(string $shortCode, ?string $domain = null): ShortUrl;
}
