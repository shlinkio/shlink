<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

final readonly class ExpiredShortUrlsConditions
{
    public function __construct(public bool $pastValidUntil = true, public bool $maxVisitsReached = false)
    {
    }

    public function hasConditions(): bool
    {
        return $this->pastValidUntil || $this->maxVisitsReached;
    }
}
