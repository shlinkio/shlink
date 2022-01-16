<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Shlinkio\Shlink\Core\Entity\ShortUrl;

interface ShortCodeUniquenessHelperInterface
{
    public function ensureShortCodeUniqueness(ShortUrl $shortUrlToBeCreated, bool $hasCustomSlug): bool;
}
