<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

final readonly class ExpiredShortUrlsConditions
{
    public function __construct(public bool $pastValidUntil = true, public bool $maxVisitsReached = false)
    {
    }

    public static function fromQuery(array $query): self
    {
        return new self(
            pastValidUntil: (bool) ($query['pastValidUntil'] ?? true),
            maxVisitsReached: (bool) ($query['maxVisitsReached'] ?? true),
        );
    }

    public function hasConditions(): bool
    {
        return $this->pastValidUntil || $this->maxVisitsReached;
    }
}
