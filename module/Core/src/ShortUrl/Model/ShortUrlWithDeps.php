<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Model\VisitsSummary;

final readonly class ShortUrlWithDeps
{
    private function __construct(
        public ShortUrl $shortUrl,
        private string|null $authority,
        private VisitsSummary|null $visitsSummary = null,
    ) {
    }

    /**
     * @param array{shortUrl: ShortUrl, visits: string|int, nonBotVisits: string|int, authority: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            shortUrl: $data['shortUrl'],
            authority: $data['authority'] ?? null,
            visitsSummary: VisitsSummary::fromTotalAndNonBots(
                total: (int) $data['visits'],
                nonBots: (int) $data['nonBotVisits'],
            ),
        );
    }

    public static function fromShortUrl(ShortUrl $shortUrl): self
    {
        return new self($shortUrl, authority: $shortUrl->getDomain()?->authority);
    }

    public function toIdentifier(): ShortUrlIdentifier
    {
        return ShortUrlIdentifier::fromShortCodeAndDomain($this->shortUrl->getShortCode(), $this->authority);
    }

    public function toArray(): array
    {
        return $this->shortUrl->toArray($this->visitsSummary, fn() => $this->authority);
    }
}
