<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Model\VisitsSummary;

final readonly class ShortUrlWithVisitsSummary
{
    private function __construct(
        public ShortUrl $shortUrl,
        private VisitsSummary|null $visitsSummary = null,
        private string|null $authority = null,
    ) {
    }

    /**
     * @param array{shortUrl: ShortUrl, visits: string|int, nonBotVisits: string|int, authority: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            shortUrl: $data['shortUrl'],
            visitsSummary: VisitsSummary::fromTotalAndNonBots(
                total: (int) $data['visits'],
                nonBots: (int) $data['nonBotVisits'],
            ),
            authority: $data['authority'] ?? null,
        );
    }

    public static function fromShortUrl(ShortUrl $shortUrl): self
    {
        return new self($shortUrl);
    }

    public function toArray(): array
    {
        return $this->shortUrl->toArray($this->visitsSummary, fn() => $this->authority);
    }
}
